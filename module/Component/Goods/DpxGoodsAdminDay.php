<?php

/**
 * 상품 class
 *
 * 상품 관련 관리자 Class
 * @author artherot
 * @version 1.0
 * @since 1.0
 * @copyright Copyright (c), Godosoft
 */
namespace Component\Goods;

use Component\Member\Group\Util as GroupUtil;
use Component\Member\Manager;
use Component\Page\Page;
use Component\Storage\Storage;
use Component\Database\DBTableField;
use Component\Validator\Validator;
use Framework\Debug\Exception\HttpException;
use Framework\Debug\Exception\AlertBackException;
use Framework\File\FileHandler;
use Framework\Utility\ImageUtils;
use Framework\Utility\StringUtils;
use Framework\Utility\ArrayUtils;
use Encryptor;
use Globals;
use LogHandler;
use UserFilePath;
use Request;
use Exception;
use Session;
use App;

//use Component\File\DataFileFactory;

class DpxGoodsAdminDay extends \Bundle\Component\Goods\GoodsAdmin
{
	
      /**
     * 관리자 상품 리스트
     *
     * @param string $mode 일반리스트 인지 레이어 리스트인지 구부 (null or layer) or orderWrite
     * @param integer $pageNum 레이어 리스트의 경우 페이지당 리스트 수
     * @return array 상품 리스트 정보
     */
    public function getAdminListGoods($mode = null, $pageNum = 5)
    {
        gd_isset($this->goodsTable,DB_GOODS);

        // --- 검색 설정
        $getValue = Request::get()->toArray();
		if(!$getValue['searchEventFl']){
			$getValue['searchEventFl']='';
		}
        gd_isset($getValue['delFl'], 'n');
        // --- 정렬 설정
        if ($getValue['delFl'] == 'y') {
           $sort = gd_isset($getValue['sort'], 'delDt desc , g.goodsNo desc');
           if ($this->goodsDivisionFl) {
               $join[] = ' LEFT JOIN ' . DB_GOODS . ' as goods ON g.goodsNo = goods.goodsNo ';
           }
       } else {
            $sort = gd_isset($getValue['sort'], 'g.goodsNo desc');
       }
        $this->setSearchGoods($getValue);

        //상품 헤택 모듈
        $goodsBenefit = \App::load('\\Component\\Goods\\GoodsBenefit');
        $goodsBenefitUse = $goodsBenefit->getConfig();

        //수기주문일시
        /*
        if($mode === 'orderWrite'){
            $this->goodsTable = DB_GOODS;

            $this->setSearchGoodsOrderWrite($param);
        }*/

        if ($mode == 'layer') {
            // --- 페이지 기본설정
            if (gd_isset($getValue['pagelink'])) {
                $getValue['page'] = (int)str_replace('page=', '', preg_replace('/^{page=[0-9]+}/', '', gd_isset($getValue['pagelink'])));
            } else {
                $getValue['page'] = 1;
            }
            gd_isset($getValue['pageNum'], $pageNum);
        } else {
            // --- 페이지 기본설정
            gd_isset($getValue['page'], 1);
            gd_isset($getValue['pageNum'], 10);
        }

        $page = \App::load('\\Component\\Page\\Page', $getValue['page'],0,0,$getValue['pageNum']);
        $page->setCache(true); // 페이지당 리스트 수
        if ($mode != 'layer') {
            $page->setUrl(\Request::getQueryString());
        }

        // 현 페이지 결과
        if (!empty($this->search['cateGoods']) || !empty($this->search['displayTheme'][1])) {
            $join[] = ' LEFT JOIN ' . DB_GOODS_LINK_CATEGORY . ' as gl ON g.goodsNo = gl.goodsNo ';
        }

        if(($getValue['key'] == 'companyNm' && $getValue['keyword']) || strpos($sort, "companyNm") !== false) $join[] = ' LEFT JOIN ' . DB_SCM_MANAGE . ' as s ON s.scmNo = g.scmNo ';
        if(gd_is_provider() === false && gd_is_plus_shop(PLUSSHOP_CODE_PURCHASE) === true )  {
            if( $getValue['key'] == 'purchaseNm' && $getValue['keyword']) {
                $join[] = ' LEFT JOIN ' . DB_PURCHASE . ' as p ON p.purchaseNo = g.purchaseNo and p.delFl = "n"';
            } else if($getValue['purchaseNoneFl'] =='y') {
                $join[] = ' LEFT JOIN ' . DB_PURCHASE . ' as p ON p.purchaseNo = g.purchaseNo';
            }
        }

        //추가정보 검색
        if($getValue['key'] == 'addInfo' && $getValue['keyword']) {
            $addInfoQuery = "(SELECT goodsNo,count(*) as addInfoCnt FROM ".DB_GOODS_ADD_INFO." WHERE infoTitle LIKE concat('%','".$this->db->escape($getValue['keyword'])."','%') GROUP BY goodsNo)";
            $join[] = ' LEFT JOIN ' . $addInfoQuery . ' as gai ON  gai.goodsNo = g.goodsNo';
            $this->arrWhere[] = " addInfoCnt  > 0";
        }
		if($getValue['searchEventFl']=='d'){
			 $this->arrWhere[] = " g.dpxEventDayFl ='y'";

		}
        //상품 혜택 검색
        if (!empty($this->search['goodsBenefitSno']) || $this->search['goodsBenefitNoneFl'] == 'y') {
            $join[] = ' LEFT JOIN ' . DB_GOODS_LINK_BENEFIT . ' as gbl ON g.goodsNo = gbl.goodsNo ';
        }

        //상품 혜택 아이콘 검색
        if ($goodsBenefitUse == 'y' && $this->search['goodsIconCd']) {
            $join[] = 'LEFT JOIN
            (
            select t1.goodsNo,t1.benefitSno,t1.goodsIconCd
            from ' . DB_GOODS_LINK_BENEFIT . ' as t1,
            (select goodsNo, min(linkPeriodStart) as min_start from ' . DB_GOODS_LINK_BENEFIT . ' where ((benefitUseType=\'periodDiscount\' or benefitUseType=\'newGoodsDiscount\') AND linkPeriodStart < NOW() AND linkPeriodEnd > NOW()) or benefitUseType=\'nonLimit\'  group by goodsNo) as t2
            where t1.linkPeriodStart = t2.min_start and t1.goodsNo = t2.goodsNo
            ) as gbs on g.goodsNo = gbs.goodsNo ';
        }

        //상품 아이콘 테이블추가
        if ($this->search['goodsIconCdPeriod'] || $this->search['goodsIconCd']) {
            if ($this->search['goodsIconCdPeriod'] && !$this->search['goodsIconCd']) {
                $join[] = ' LEFT JOIN ' . DB_GOODS_ICON . ' as gi ON g.goodsNo = gi.goodsNo ';
            } else {
                if ($goodsBenefitUse == 'y'){
                    $join[] = ' LEFT JOIN ' . DB_GOODS_ICON . ' as gi ON g.goodsNo = gi.goodsNo OR (gbs.benefitSno = gi.benefitSno AND gi.iconKind = \'pr\')';
                }else {
                    $join[] = ' LEFT JOIN ' . DB_GOODS_ICON . ' as gi ON g.goodsNo = gi.goodsNo ';
                }
            }
            // 검색 조건에 아이콘 검색이 있는 경우 group by 추가
            $this->db->strGroup = "g.goodsNo ";
            $goodsIconStrGroup = " GROUP BY g.goodsNo";
        }

        $this->db->strField = "g.goodsNo";
        // 구매율의 경우 계산 필드를 삽입을 위해 변경
        if($sort == 'orderRate desc') {
            $this->db->strField = "g.goodsNo, round(((g.orderGoodsCnt / g.hitCnt)*100), 2) as orderRate";
        }
        $this->db->strJoin = implode('', $join);
        $this->db->strWhere = implode(' AND ', gd_isset($this->arrWhere));
        $this->db->strOrder = $sort;
        // 검색 조건에 메인분류 검색이 있는 경우 group by 추가
        if(!empty($this->search['displayTheme'][1])) {
            $this->db->strGroup = "g.goodsNo ";
            $mainDisplayStrGroup = " GROUP BY g.goodsNo";
        }

        //상품 아이콘 기간제한 & 무제한 모두 검색시 index 태우기 위해 UNION 추가
        if ($this->search['goodsIconCdPeriod'] && $this->search['goodsIconCd']) {
            $page->recode['union_start'] = $page->recode['start'] + 10;
            $this->db->strLimit = '0 ,' . $page->recode['union_start'];

            //기간제한 아이콘
            if ($this->search['goodsIconCdPeriod']) {
                $this->arrWhere[] = 'gi.goodsIconCd = ? AND gi.iconKind = \'pe\'';
                $this->db->bind_param_push($this->arrBind, 's', $this->search['goodsIconCdPeriod']);
                $this->db->strWhere = implode(' AND ', $this->arrWhere);
            }

            $query = $query2 = $this->db->query_complete();

            $strSQL = 'SELECT goodsNo FROM ((SELECT ' . array_shift($query) . ' FROM ' . $this->goodsTable . ' g ' . implode(' ', $query) . ') UNION';

            //무제한 아이콘 검색
            if ($this->search['goodsIconCd']) {
                if ($goodsBenefitUse == 'y') {
                    $query2['where'] = str_replace('gi.goodsIconCd = ? AND gi.iconKind = \'pe\'', '(gi.goodsIconCd = ? AND gi.iconKind = \'un\' OR gi.goodsIconCd = ? AND gi.iconKind = \'pr\')', $query2['where']);
                }else{
                    $query2['where'] = str_replace('gi.goodsIconCd = ? AND gi.iconKind = \'pe\'', '(gi.goodsIconCd = ? AND gi.iconKind = \'un\' )', $query2['where']);
                }
                foreach ($this->arrBind as $bind_key => $bind_val) {
                    if ($bind_key > 0) {
                        if ($this->search['goodsIconCdPeriod'] != $bind_val) {
                            $this->arrBind[count($this->arrBind)] = $bind_val;
                            $this->arrBind[0] .= 's';
                        }
                    }
                }
                $this->db->bind_param_push($this->arrBind, 's', $this->search['goodsIconCd']);
                if ($goodsBenefitUse == 'y') {
                    $this->db->bind_param_push($this->arrBind, 's', $this->search['goodsIconCd']);
                }

                $strSQL .= '(SELECT ' . array_shift($query2) . ' FROM ' . $this->goodsTable . ' g ' . implode(' ', $query2) . '))a order by goodsNo desc LIMIT ' .  $page->recode['start'] . ',' . $getValue['pageNum'];
            }
        } else {
           $this->db->strLimit = $page->recode['start'] . ',' . $getValue['pageNum'];

            //기간제한 아이콘
            if ($this->search['goodsIconCdPeriod']) {
                $this->arrWhere[] = 'gi.goodsIconCd = ? AND gi.iconKind = \'pe\'';
                $this->db->bind_param_push($this->arrBind, 's', $this->search['goodsIconCdPeriod']);
                $this->db->strWhere = implode(' AND ', $this->arrWhere);
            }

            //무제한 아이콘
            if ($this->search['goodsIconCd']) {
                if ($goodsBenefitUse == 'y') {
                    $this->arrWhere[] = '(gi.goodsIconCd = ? AND gi.iconKind = \'un\' OR gi.goodsIconCd = ? AND gi.iconKind = \'pr\')';
                    $this->db->bind_param_push($this->arrBind, 's', $this->search['goodsIconCd']);
                    $this->db->bind_param_push($this->arrBind, 's', $this->search['goodsIconCd']);
                }else{
                    $this->arrWhere[] = '(gi.goodsIconCd = ? AND gi.iconKind = \'un\')';
                    $this->db->bind_param_push($this->arrBind, 's', $this->search['goodsIconCd']);
                }

                $this->db->strWhere = implode(' AND ', $this->arrWhere);
            }

            $query = $this->db->query_complete();
            $strSQL = 'SELECT ' . array_shift($query) . ' FROM ' . $this->goodsTable . ' g ' . implode(' ', $query);

        }

        // 빠른 이동/복사/삭제 인 경우 검색이 없으면 리턴
        if ($mode == 'batch' && empty($this->arrWhere)) {
            $data = null;
        } else {
            $data = $this->db->query_fetch($strSQL, $this->arrBind);
			$getData['dpxQry'] = $strSQL;
			$getData['dpxBind'] = $this->arrBind;
            /* 검색 count 쿼리 */
            if($page->hasRecodeCache('total') === false){
                $totalCountSQL = ' SELECT COUNT(g.goodsNo) AS totalCnt FROM ' . $this->goodsTable . ' as g  ' . implode('', $join) . '  WHERE ' . implode(' AND ', $this->arrWhere) . $mainDisplayStrGroup;
                $dataCount = $this->db->query_fetch($totalCountSQL, $this->arrBind);
            }
            /* 검색 count 쿼리 */
            if ($this->search['goodsIconCdPeriod'] && $this->search['goodsIconCd']) {
                //무제한 아이콘 검색
                if ($this->search['goodsIconCd']) {
                    $this->arrWhere2 = $this->arrWhere;
                    foreach ($this->arrWhere2 as $k => $v) {
                        if(strpos($v, 'gi.goodsIconCd = ? AND gi.iconKind = \'pe\'') !== false) {
                            if ($goodsBenefitUse == 'y') {
                                $this->arrWhere2[$k] = '(gi.goodsIconCd = ? AND gi.iconKind = \'un\' OR gi.goodsIconCd = ? AND gi.iconKind = \'pr\')';
                            }else{
                                $this->arrWhere2[$k] = '(gi.goodsIconCd = ? AND gi.iconKind = \'un\' )';
                            }
                        }
                    }
                }

                //상품 아이콘 검색시 카운트
                $totalCountSQL =  ' SELECT COUNT(1) as totalCnt FROM (( SELECT g.goodsNo FROM ' . $this->goodsTable . ' as g  '.implode('', $join).'  WHERE '.implode(' AND ', $this->arrWhere) . $goodsIconStrGroup. ') UNION';

                if ($this->search['goodsIconCd']) {
                    $totalCountSQL .=  ' ( SELECT g.goodsNo FROM ' . $this->goodsTable . ' as g  '.implode('', $join).'  WHERE '.implode(' AND ', $this->arrWhere2) . $goodsIconStrGroup. ')) tbl';
                }
            } else {
                // 검색 조건에 메인분류 검색이 있는 경우 아이콘 group by 비움
                if ($mainDisplayStrGroup) {
                    $goodsIconStrGroup = '';
                }
                $totalCountSQL = ' SELECT COUNT(1) as totalCnt FROM ( SELECT g.goodsNo FROM ' . $this->goodsTable . ' as g  ' . implode('', $join) . '  WHERE ' . implode(' AND ', $this->arrWhere) . $mainDisplayStrGroup. $goodsIconStrGroup . ') AS tbl';
            }
            if($page->hasRecodeCache('total') === false) {
                $dataCount = $this->db->query_fetch($totalCountSQL, $this->arrBind);
            }

            unset($this->arrBind);

            $page->recode['total'] = $dataCount[0]['totalCnt']; //검색 레코드 수

            if (Session::get('manager.isProvider')) { // 전체 레코드 수
                if($page->hasRecodeCache('amount') === false) {
                    $page->recode['amount'] = $this->db->getCount($this->goodsTable, 'goodsNo', 'WHERE delFl=\'' . $getValue['delFl'] . '\'  AND scmNo = \'' . Session::get('manager.scmNo') . '\'');
                }
                $scmWhereString = " AND g.scmNo = '" . (string)Session::get('manager.scmNo') . "'"; // 공급사인 경우
            }  else {
                if($page->hasRecodeCache('amount') === false) {
                    $page->recode['amount'] = $this->db->getCount($this->goodsTable, 'goodsNo', 'WHERE delFl=\'' . $getValue['delFl'] . '\'');
                }
            }

            // 아이콘  설정
            if (empty($data) === false) {
                $this->setAdminListGoods($data,",g.goodsBenefitSetFl, g.optionFl,g.dpxEventFl,g.dpxEventPrice,g.dpxEventDayFl,g.dpxEventDayPrice,g.dpxEventUseStartDate,g.dpxEventUseEndDate,g.dpxEventDateFl");
            }

            // 상품그리드
            if($mode == null) {
                // 상품리스트 그리드 설정
                $goodsAdminGrid = \App::load('\\Component\\Goods\\GoodsAdminGrid');
                $goodsAdminGridMode = $goodsAdminGrid->getGoodsAdminGridMode();
                $this->goodsGridConfigList = $goodsAdminGrid->getSelectGoodsGridConfigList($goodsAdminGridMode, 'all');
                if (empty($this->goodsGridConfigList) === false) {
                    $getData['goodsGridConfigList'] = $this->goodsGridConfigList;
                    $gridAddDisplayArray = ['best', 'main', 'cate']; // 그리드 추가진열 레이어 노출 항목
                    $getData['goodsGridConfigListDisplayFl'] = false; // 그리드 추가 진열 레이어 노출 여부
                    foreach($gridAddDisplayArray as $displayPassVal) {
                        if(array_key_exists($displayPassVal, $getData['goodsGridConfigList']['display']) === true) {
                            $getData['goodsGridConfigListDisplayFl'] = true; // 그리드 추가 진열 레이어 노출 사용
                            break;
                        }
                    }
                    if($goodsAdminGridMode == 'goods_list') {
                        $getData['goodsGridConfigList']['btn'] = '수정';
                    }
                }

                // 상품 리스트 품절, 노출 PC/mobile, 미노출 PC/mobile 카운트 쿼리
                if($goodsAdminGridMode == 'goods_list') {
                    $dataStateCount = [];
                    $dataStateCountQuery = [
                        'pcDisplayCnt' => " g.goodsDisplayFl='y'",
                        'mobileDisplayCnt' => " g.goodsDisplayMobileFl='y'",
                        'pcNoDisplayCnt' => " g.goodsDisplayFl='n'",
                        'mobileNoDisplayCnt' => " g.goodsDisplayMobileFl='n'",
                    ];
                    foreach ($dataStateCountQuery as $stateKey => $stateVal) {
                        if($page->hasRecodeCache($stateKey)) {
                            $dataStateCount[$stateKey]  = $page->getRecodeCache($stateKey);
                            continue;
                        }
                        $dataStateSQL = " SELECT COUNT(g.goodsNo) AS cnt FROM " . $this->goodsTable . " as g WHERE  " . $stateVal . " AND g.delFl ='n'" . $scmWhereString;
                        $dataStateCount[$stateKey] = $this->db->query_fetch($dataStateSQL)[0]['cnt'];
                        $page->recode[$stateKey] = $dataStateCount[$stateKey];
                    }
                    // 품절의 경우 OR 절 INDEX 경유하지 않기에 별도 쿼리 실행 - DBA
                    //                    if(!\Request::get()->get('__soldOutCnt')) {
                    if($page->hasRecodeCache('soldOutCnt') === false) {
                        $dataStateSoldOutSql = "select sum(cnt) as cnt from ( SELECT count(1) AS cnt FROM  " . $this->goodsTable . "  as g1 WHERE   g1.soldOutFl = 'y' AND g1.delFl ='n' union all SELECT count(1) AS cnt FROM  " . $this->goodsTable . "  as g2 WHERE  g2.soldOutFl = 'n' and g2.stockFl = 'y' AND g2.totalStock <= 0  AND g2.delFl ='n') gQ";
                        $dataStateCount['soldOutCnt'] = $this->db->query_fetch($dataStateSoldOutSql)[0]['cnt'];
                        $page->recode['soldOutCnt'] = $dataStateCount['soldOutCnt'];
                    }
                    else {
                        $dataStateCount['soldOutCnt'] = $page->getRecodeCache('soldOutCnt');
                    }
                    $getData['stateCount'] = $dataStateCount;
                }
            }
        }

        $page->setPage(null,['soldOutCnt','pcDisplayCnt','mobileDisplayCnt','pcNoDisplayCnt','mobileNoDisplayCnt']);

        // 각 데이터 배열화
        $getData['data'] = gd_htmlspecialchars_stripslashes(gd_isset($data));
        $getData['sort'] = $sort;
        $getData['search'] = gd_htmlspecialchars($this->search);
		$getData['search']['searchEventFl'] = $getValue['searchEventFl'];
        $getData['checked'] = $this->checked;
        $getData['selected'] = $this->selected;
		

        return $getData;
    }
}
