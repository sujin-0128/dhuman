<?php

/**
 * This is commercial software, only users who have purchased a valid license
 * and accept to the terms of the License Agreement can install and use this
 * program.
 *
 * Do not edit or add to this file if you wish to upgrade Godomall5 to newer
 * versions in the future.
 *
 * @copyright ⓒ 2016, NHN godo: Corp.
 * @link http://www.godo.co.kr
 */

namespace Component\Goods;

use Component\PlusShop\PlusReview\PlusReviewConfig;
use Component\Database\DBTableField;
use Component\ExchangeRate\ExchangeRate;
use Component\Member\Group\Util;
use Component\Validator\Validator;
use Cookie;
use Exception;
use Framework\Utility\ArrayUtils;
use Framework\Utility\SkinUtils;
use Framework\Utility\StringUtils;
use Globals;
use Request;
use Session;
use UserFilePath;
use Framework\Utility\DateTimeUtils;

/**
 * 상품 class
 */
class Goods extends \Bundle\Component\Goods\Goods
{

    /**
     * 상품 정보 출력 (상품 리스트)
     *
     * @param string $cateCd 카테고리 코드
     * @param string $cateMode 카테고리 모드 (category, brand)
     * @param int $pageNum 페이지 당 리스트수 (default 10)
     * @param string $displayOrder 상품 기본 정렬 - 'sort asc', Category::getSort() 참고
     * @param string $imageType 이미지 타입 - 기본 'main'
     * @param boolean $optionFl 옵션 출력 여부 - true or false (기본 false)
     * @param boolean $soldOutFl 품절상품 출력 여부 - true or false (기본 true)
     * @param boolean $brandFl 브랜드 출력 여부 - true or false (기본 true)
     * @param boolean $couponPriceFl 쿠폰가격 출력 여부 - true or false (기본 false)
     * @param integer $imageViewSize 이미지 크기 (기본 "0" - 0은 원래 크기)
     * @param integer $displayCnt 상품 출력 갯수 - 기본 10개
     * @return array 상품 정보
     * @throws Exception
     */
    public function getGoodsList($cateCd, $cateMode = 'category', $pageNum = 10, $displayOrder = 'sort asc', $imageType = 'list', $optionFl = false, $soldOutFl = true, $brandFl = false, $couponPriceFl = false, $imageViewSize = 0, $displayCnt = 10)
    {

        $delivery = \App::load('\\Component\\Delivery\\Delivery');

        $display = \App::load('\\Component\\Display\\DisplayConfigAdmin');
        $displayNavi = $display->getDateNaviDisplay();

        gd_isset($this->goodsTable,DB_GOODS);
        $mallBySession = SESSION::get(SESSION_GLOBAL_MALL);
        // Validation - 상품 코드 체크
        if (Validator::required($cateCd, true) === false) {
            throw new Exception(self::ERROR_VIEW . self::TEXT_NOT_EXIST_CATECD);
        }

        $getValue = Request::get()->toArray();

        // --- 정렬 설정
        $sortGoodsOnly = 'n'; // 상품 번호 최신순 정렬만 사용 (2022.06 상품리스트 및 상세 성능개선)
        if (gd_isset($getValue['sort'])) {
            $dSort = $getValue['sort'];
            if (method_exists($this, 'getSortMatch')) {
                $dSort = $this->getSortMatch($dSort);
            }

            // 품절상품 정렬 추가 (정렬시 최우선)
            if ($this->soldOutDisplayFl === 'n' && strpos($dSort, "soldOut asc") === false) {
                $dSort = 'soldOut asc, '.$dSort;
            }

            $sort[] = $dSort;
        } else {

            if ($displayOrder) {
                if (is_array($displayOrder)) $sort[] = implode(",", $displayOrder);
                else $sort[] = $displayOrder;

            } else {
                if ($this->goodsSortFl === 'y') $sort[] = "gl.goodsSort desc";
            }
        }

        $sort = implode(',', $sort);
        if($sort) {
            if(strpos($sort, "regDt") !== false) $sort = str_replace("regDt","goodsNo",$sort);
            if(strpos($sort, "goodsNo") === false) $sort = $sort.', goodsNo desc ';
            if ($sort == 'g.goodsNo desc') $sortGoodsOnly = 'y';
        } else {
            // 상품 번호 최신순 정렬만 사용 (2022.06 상품리스트 및 상세 성능개선)
            $sort = 'g.goodsNo desc';
            $sortGoodsOnly = 'y';
        }

        if(strpos($displayOrder, "soldOut") !== false) $addField = ",( if (g.soldOutFl = 'y' , 'y', if (g.stockFl = 'y' AND g.totalStock <= 0, 'y', 'n') ) ) as soldOut";

        // --- 페이지 기본설정
        gd_isset($getValue['page'], 1);

        // 배수 설정
        $getData['multiple'] = range($displayCnt, $displayCnt * 4, $displayCnt);

        $page = \App::load('\\Component\\Page\\Page', $getValue['page']);
        $page->page['list'] = $pageNum; // 페이지당 리스트 수
        $page->block['cnt'] = Request::isMobile() ? 5 : 10; // 블록당 리스트 개수
        $page->setPage();
        $page->setUrl(\Request::getQueryString());

        // 카테고리 종류에 따른 설정
        if ($cateMode == 'category') {
            $dbTable = DB_GOODS_LINK_CATEGORY;
            $viewName="goods";
        } else {
            $dbTable = DB_GOODS_LINK_BRAND;
            $viewName="brand";
        }

        // 조인 설정
        $arrJoin[] = ($sortGoodsOnly === 'n') ? ' INNER JOIN '.$this->goodsTable.' g ON gl.goodsNo = g.goodsNo ' : ' INNER JOIN '.$dbTable.' gl ON gl.goodsNo = g.goodsNo ';

        // 조건절 설정
        $this->db->bind_param_push($this->arrBind, 's', $cateCd);
        $this->arrWhere[] = 'gl.cateCd = ?';
        if ($cateMode == 'category' || ($cateMode == 'brand' && $displayNavi['data']['brand']['linkUse'] != 'y')) {
            $this->arrWhere[] = 'gl.cateLinkFl = \'y\'';
        }
        $this->arrWhere[] = 'g.delFl = \'n\'';
        $this->arrWhere[] = 'g.applyFl = \'y\'';
        $this->arrWhere[] = 'g.' . $this->goodsDisplayFl . ' = \'y\'';
        $this->arrWhere[] = '(g.goodsOpenDt IS NULL  OR g.goodsOpenDt < NOW())';

        //접근권한 체크
		//dpx-kwc 비회원상품(guest) 조건 추가
        if (gd_check_login()) {
			$this->arrWhere[] = '(g.goodsAccess !=\'group\' OR (g.goodsAccess=\'group\' AND FIND_IN_SET(\''.Session::get('member.groupSno').'\', REPLACE(g.goodsAccessGroup,"'.INT_DIVISION.'",","))) OR (g.goodsAccess=\'group\' AND !FIND_IN_SET(\''.Session::get('member.groupSno').'\', REPLACE(g.goodsAccessGroup,"'.INT_DIVISION.'",",")) AND g.goodsAccessDisplayFl =\'y\'))';
			if(gd_check_login() != 'guest'){
				$this->arrWhere[] = 'g.goodsAccess !=\'guest\'';
			}
        } else {
			$this->arrWhere[] = '(g.goodsAccess=\'all\' OR g.goodsAccess=\'guest\' OR (g.goodsAccess !=\'all\' AND g.goodsAccessDisplayFl =\'y\'))';
        }

        //성인인증안된경우 노출체크 상품은 노출함
        if (gd_check_adult() === false) {
            $this->arrWhere[] = '(onlyAdultFl = \'n\' OR (onlyAdultFl = \'y\' AND onlyAdultDisplayFl = \'y\'))';
        }

        if ($soldOutFl === false) { // 품절 처리 여부
            $this->arrWhere[] = 'NOT(g.stockFl = \'y\' AND g.totalStock = 0) AND g.soldOutFl = \'n\'';
        }

        // 필드 설정
        $this->setGoodsListField(); // 상품 리스트용 필드
        if($this->goodsDivisionFl) {
            $this->db->strField = 'STRAIGHT_JOIN g.goodsNo'.gd_isset($addField);
        } else {
            $this->db->strField = 'STRAIGHT_JOIN ' . $this->goodsListField . gd_isset($addField);
        }

        $this->db->strJoin = implode('', $arrJoin);
        $this->db->strWhere = implode(' AND ', gd_isset($this->arrWhere));
        $this->db->strOrder = $sort;
        $this->db->strLimit = $page->recode['start'] . ',' . $pageNum;

        $query = $this->db->query_complete();
        $strSQL = ($sortGoodsOnly === 'n') ? 'SELECT ' . array_shift($query) . ' FROM ' . $dbTable . ' gl ' . implode(' ', $query) : 'SELECT ' . array_shift($query) . ' FROM ' . $this->goodsTable . ' g ' . implode(' ', $query);

        $data = $this->db->slave()->query_fetch($strSQL, $this->arrBind);

        if($data) {
            if($this->goodsDivisionFl) {
                /* 상품 테이블에서 정보 가져옴 */
                $strGoodsSQL = 'SELECT ' . $this->goodsListField . ' FROM ' . DB_GOODS . ' g WHERE goodsNo IN (' . implode(',', array_column($data, 'goodsNo')) . ') ORDER BY FIELD(g.goodsNo,' . implode(',', array_column($data, 'goodsNo')) . ')';
                $data = $this->db->query_fetch($strGoodsSQL);
            }

            /* 검색 count 쿼리 */
            $totalCountSQL =  ($sortGoodsOnly === 'n') ? ' SELECT COUNT(gl.goodsNo) AS totalCnt FROM ' . $dbTable . ' as gl '.implode('', $arrJoin).'  WHERE '.implode(' AND ', $this->arrWhere) : ' SELECT COUNT(gl.goodsNo) AS totalCnt FROM ' . $this->goodsTable . ' as g '.implode('', $arrJoin).'  WHERE '.implode(' AND ', $this->arrWhere);            $dataCount = $this->db->slave()->query_fetch($totalCountSQL, $this->arrBind,false);
            unset($this->arrBind, $this->arrWhere);

            // 검색 레코드 수
            $page->recode['total'] = $dataCount['totalCnt']; //검색 레코드 수
            $page->setPage();

            // 상품 정보 세팅
            if (empty($data) === false) {
                $this->setGoodsListInfo($data, $imageType, $optionFl, $couponPriceFl, $imageViewSize,$viewName,$brandFl);
            }
        }

        unset($this->arrBind, $this->arrWhere);

        // 배송비 유형 설정
        //        foreach($data as $key => $goodInfo) {
        //            $goodsView = $this->getGoodsView($goodInfo['goodsNo']);
        //            $deliveryInfo = $delivery->getDeliveryType($goodsView['deliverySno']);
        //            $data[$key]['deliveryType'] = $deliveryInfo['deliveryType'];
        //            $data[$key]['deliveryMethod'] = $deliveryInfo['method'];
        //            $data[$key]['deliveryDes'] = $deliveryInfo['description'];
        //        }

        // 각 데이터 배열화
        $getData['listData'] = gd_htmlspecialchars_stripslashes(gd_isset($data));

		$dpx = \App::load('\\Component\\Designpix\\Dpx');
		//튜닝
		foreach( $getData['listData'] as $key=>$val) {
			$goodsSearchWord = $dpx->getGoodsSearchWord($val['goodsNo']);
			
			$exSword[] = explode(",",$goodsSearchWord['goodsSearchWord']);

		}

		foreach( $exSword as $key=>$val) {
			foreach( $val as $k=>$v) {
				//gd_debug("#".$v);
				if( $v ){
					$exGoodsSearchWord[$key][$k] = "#".$v;
				}
			}
		}

		foreach( $getData['listData'] as $key=>$val) {
			
			$getData['listData'][$key]['exGoodsSearchWord'] = $exGoodsSearchWord[$key];

		}

		//gd_debug($getData['listData']);




        $getData['listSort'] = $displayOrder;
        $getData['listSearch'] = gd_htmlspecialchars($this->search);
        unset($this->search);
        return $getData;

    }


    /**
     * 상품 검색 정보 출력
     *
     * @param string  $searchData    검색 데이타
     * @param integer $displayCnt    상품 출력 갯수 - 기본 10개
     * @param string  $displayOrder  상품 기본 정렬 - 'sort asc', Category::getSort() 참고
     * @param string  $imageType     이미지 타입 - 기본 'main'
     * @param boolean $optionFl      옵션 출력 여부 - true or false (기본 false)
     * @param boolean $soldOutFl     품절상품 출력 여부 - true or false (기본 true)
     * @param boolean $brandFl       브랜드 출력 여부 - true or false (기본 false)
     * @param boolean $couponPriceFl 쿠폰가격 출력 여부 - true or false (기본 false)
     * @param boolean $usePage       paging 사용여부
     * @param integer $limit         상품수
     * @param array $goodsNo         상품번호
     *
     * @return array 상품 정보
     */
    public function getGoodsSearchList($pageNum = 10, $displayOrder = 'g.regDt asc', $imageType = 'list', $optionFl = false, $soldOutFl = true, $brandFl = false, $couponPriceFl = false, $displayCnt = 10, $brandDisplayFl = false, $usePage = true, $limit = null,array $goodsNo = null)
    {
        gd_isset($this->goodsTable,DB_GOODS);
        $arrBind = null;
        $mallBySession = SESSION::get(SESSION_GLOBAL_MALL);

        $getValue = Request::get()->toArray();

        // --- 정렬 설정
        if (gd_isset($getValue['sort'])) {
            $sort = $getValue['sort'];
            if (method_exists($this, 'getSortMatch')) {
                $sort = $this->getSortMatch($sort);
            }
        } else {
            $sort = $displayOrder;
        }

        // --- 페이지 기본설정
        gd_isset($getValue['page'], 1);

        // 배수 설정
        $getData['multiple'] = range($displayCnt, $displayCnt * 4, $displayCnt);


        if ($usePage === true) {
            $page = \App::load('\\Component\\Page\\Page', $getValue['page']);
            $page->page['list'] = $pageNum; // 페이지당 리스트 수
            $page->block['cnt'] = !Request::isMobile() ? 10 : 5; // 블록당 리스트 개수
            $page->setPage();
            $page->setUrl(\Request::getQueryString());
        }

        // --- 검색 설정
        $terms = gd_policy('search.terms');
        $this->setSearchGoodsList(null, $terms);

        if (in_array('glb', $this->useTable) === true) {
            $arrJoin[] = ' LEFT JOIN ' . DB_GOODS_LINK_BRAND . ' glb ON g.goodsNo = glb.goodsNo AND glb.cateLinkFl != \'n\'';
            if($mallBySession){
                $arrJoin[] = ' LEFT JOIN ' . DB_CATEGORY_BRAND . ' cb ON g.brandCd = cb.cateCd AND  FIND_IN_SET('.$mallBySession['sno'].',cb.mallDisplay)';
                $arrJoin[] = ' LEFT JOIN ' . DB_CATEGORY_BRAND_GLOBAL . ' cbg ON cb.cateCd = cbg.cateCd AND mallSno = '.$mallBySession['sno'];
            }
            else $arrJoin[] = ' LEFT JOIN ' . DB_CATEGORY_BRAND . ' cb ON g.brandCd = cb.cateCd   ';
        }

        if (in_array('glc', $this->useTable) === true) {
            $arrJoin[] = ' INNER JOIN ' . DB_GOODS_LINK_CATEGORY . ' glc ON g.goodsNo = glc.goodsNo ';
        }

        $goodsBenefit = \App::load('\\Component\\Goods\\GoodsBenefit');
        $goodsBenefitUse = $goodsBenefit->getConfig();

        //상품 혜택에서 현재 진행중인 아이콘 검색
        if ($this->search['goodsIcon']) {
            if($goodsBenefitUse == 'y') {
                $arrJoin[] = 'LEFT JOIN
                (
                select t1.goodsNo,t1.benefitSno,t1.goodsIconCd
                from ' . DB_GOODS_LINK_BENEFIT . ' as t1,
                (select goodsNo, min(linkPeriodStart) as min_start from ' . DB_GOODS_LINK_BENEFIT . ' where ((benefitUseType=\'periodDiscount\' or benefitUseType=\'newGoodsDiscount\') AND linkPeriodStart < NOW() AND linkPeriodEnd > NOW()) or benefitUseType=\'nonLimit\'  group by goodsNo) as t2
                where t1.linkPeriodStart = t2.min_start and t1.goodsNo = t2.goodsNo
                ) as gbs on g.goodsNo = gbs.goodsNo ';
            }

            //상품 아이콘 테이블추가
            if ($this->search['goodsIconCdPeriod'] && !$this->search['goodsIconCd']) {
                $arrJoin[] = ' LEFT JOIN ' . DB_GOODS_ICON . ' as gi ON g.goodsNo = gi.goodsNo ';
            } else {
                if($goodsBenefitUse == 'y') {
                    $arrJoin[] = ' LEFT JOIN ' . DB_GOODS_ICON . ' as gi ON g.goodsNo = gi.goodsNo OR (gbs.benefitSno = gi.benefitSno AND gi.iconKind = \'pr\')';
                }else{
                    $arrJoin[] = ' LEFT JOIN ' . DB_GOODS_ICON . ' as gi ON g.goodsNo = gi.goodsNo ';
                }

            }

            // 검색 조건에 아이콘 검색이 있는 경우 group by 추가
            $this->db->strGroup = "g.goodsNo ";
            $goodsIconStrGroup = " GROUP BY g.goodsNo";
        }

		//dpx.farmer 튜닝 기획전 플래그 설정 s
		$ignoreFl = $getValue['mainLinkData']['dpxIgnoreFl'];

		if($ignoreFl =='y' ){
			//$this->arrWhere[] = 'g.' . $this->goodsDisplayFl . ' = \'n\'';	
			$this->arrWhere[] = 'g.dpxDisplayFl = \'y\'';
			$this->arrWhere[] = 'g.delFl = \'n\'';
			$this->arrWhere[] = 'g.applyFl = \'y\'';
			$this->arrWhere[] = '(UNIX_TIMESTAMP(g.goodsOpenDt) IS NULL  OR UNIX_TIMESTAMP(g.goodsOpenDt) = 0 OR UNIX_TIMESTAMP(g.goodsOpenDt) < UNIX_TIMESTAMP())';
		}else{
        $this->arrWhere[] = 'g.' . $this->goodsDisplayFl . ' = \'y\'';	
        $this->arrWhere[] = 'g.delFl = \'n\'';
        $this->arrWhere[] = 'g.applyFl = \'y\'';
        $this->arrWhere[] = '(UNIX_TIMESTAMP(g.goodsOpenDt) IS NULL  OR UNIX_TIMESTAMP(g.goodsOpenDt) = 0 OR UNIX_TIMESTAMP(g.goodsOpenDt) < UNIX_TIMESTAMP())';
		}
		//dpx.farmer 튜닝 기획전 플래그 설정 e
		
		//ori 
		/*
		 // 기본 조건절 설정
		$this->arrWhere[] = 'g.' . $this->goodsDisplayFl . ' = \'y\'';	
        $this->arrWhere[] = 'g.delFl = \'n\'';
        $this->arrWhere[] = 'g.applyFl = \'y\'';
        $this->arrWhere[] = '(UNIX_TIMESTAMP(g.goodsOpenDt) IS NULL  OR UNIX_TIMESTAMP(g.goodsOpenDt) = 0 OR UNIX_TIMESTAMP(g.goodsOpenDt) < UNIX_TIMESTAMP())';
		*/

        if(is_array($goodsNo)){
            $bindQuery = null;
            foreach($goodsNo as $val){
                $bindQuery[] = '?';
                $this->db->bind_param_push($this->arrBind,'i',$val);
            }
            $this->arrWhere[]  = " g.goodsNo in (".implode(',',$bindQuery).")";
        }

        //접근권한 체크
		//dpx-kwc 비회원상품(guest) 조건 추가
        if (gd_check_login()) {
            $this->arrWhere[] = '(g.goodsAccess !=\'group\' OR (g.goodsAccess=\'group\' AND FIND_IN_SET(\''.Session::get('member.groupSno').'\', REPLACE(g.goodsAccessGroup,"'.INT_DIVISION.'",","))) OR (g.goodsAccess=\'group\' AND !FIND_IN_SET(\''.Session::get('member.groupSno').'\', REPLACE(g.goodsAccessGroup,"'.INT_DIVISION.'",",")) AND g.goodsAccessDisplayFl =\'y\'))';
			if(gd_check_login() != 'guest'){
				$this->arrWhere[] = 'g.goodsAccess !=\'guest\'';
			}
        } else {
            $this->arrWhere[] = '(g.goodsAccess=\'all\' OR g.goodsAccess =\'guest\' OR (g.goodsAccess !=\'all\' AND g.goodsAccessDisplayFl =\'y\'))';
        }

        //성인인증안된경우 노출체크 상품은 노출함
        if (gd_check_adult() === false) {
            $this->arrWhere[] = '(onlyAdultFl = \'n\' OR (onlyAdultFl = \'y\' AND onlyAdultDisplayFl = \'y\'))';
        }

        if ($soldOutFl === false) { // 품절 처리 여부
            $this->arrWhere[] = 'NOT(g.stockFl = \'y\' AND g.totalStock = 0) AND g.soldOutFl = \'n\'';
        }

        // 필드 설정
        $this->setGoodsListField(); // 상품 리스트용 필드

        if($mallBySession) {
            $arrJoin[] = ' LEFT JOIN ' . DB_GOODS_GLOBAL . ' gg ON g.goodsNo = gg.goodsNo AND gg.mallSno = '.$mallBySession['sno'];
        }

        if($sort) {
            if(strpos($sort, "regDt") !== false) $sort = str_replace("regDt","goodsNo",$sort);
            if(strpos($sort, "goodsNo") === false) $sort = $sort.', goodsNo desc ';
            if(strpos($sort, "goodsNm") !== false) $sort = str_replace("goodsNm","g.goodsNm",$sort); //글로벌과 동시에 선언시 필드모호성때문에 추가
        } else {
            $sort = "goodsNo desc";
        }

        if(strpos($sort, "soldOut") !== false) $addField = ",( if (g.soldOutFl = 'y' , 'y', if (g.stockFl = 'y' AND g.totalStock <= 0, 'y', 'n') ) ) as soldOut";

        $this->db->strJoin = implode('', $arrJoin);
        if($this->goodsDivisionFl) {
            $this->db->strField = 'g.goodsNo' . gd_isset($addField);
            $this->db->strGroup = 'g.goodsNo';
        } else {
            $this->db->strField = $this->goodsListField . gd_isset($addField);
        }

        $this->db->strOrder = $sort;  //$sort가 null인경우가 있어서 검색조건 추가
        $this->db->strWhere = implode(' AND ', gd_isset($this->arrWhere));
        if ($usePage === true) {
            $this->db->strLimit = $page->recode['start'] . ',' . $pageNum;
        }else {
            if (empty($limit) === false) {
                $this->db->strLimit = '0,' . $limit;
            } else {
                $this->db->strLimit = '0,' . $pageNum;
            }
        }
        $query = $this->db->query_complete();
        $strSQL = 'SELECT ' . array_shift($query) . ' FROM ' . $this->goodsTable . ' g ' . implode(' ', $query);
        $data = $this->db->query_fetch($strSQL, $this->arrBind);


        if($data) {
            if($this->goodsDivisionFl) {
                //검색테이블에서 검색 후 상품정보 가져옴
                $strSQL = 'SELECT ' . $this->goodsListField . ' FROM ' . DB_GOODS . ' g WHERE ';
				
                $bindQuery = $arrBind = null;
                foreach(array_column($data, 'goodsNo') as $val){
                    $bindQuery[] = '?';
                    $this->db->bind_param_push($arrBind,'i',$val);
                }
                $strSQL .= " goodsNo IN  (".implode(',',$bindQuery).")";
                $strSQL .=' ORDER BY ' . $sort;
                $tmpGoodsData = $this->db->query_fetch($strSQL, $arrBind);
                $data = array_combine(array_column($tmpGoodsData, 'goodsNo'), $tmpGoodsData);
				
            }

            if ($brandDisplayFl) {
                //현재 그룹 정보
                $myGroup = \Session::get('member.groupSno');

                if ($mallBySession) {
                    $tmpJoin[] = ' INNER JOIN ' . DB_GOODS_LINK_BRAND . ' glb ON g.goodsNo = glb.goodsNo';
                    $tmpJoin[] = ' LEFT JOIN ' . DB_CATEGORY_BRAND . ' cb ON g.brandCd = cb.cateCd AND FIND_IN_SET(' . $mallBySession['sno'] . ', cb.mallDisplay)';
                    $tmpJoin[] = ' LEFT JOIN ' . DB_CATEGORY_BRAND_GLOBAL . ' cbg ON cb.cateCd = cbg.cateCd AND mallSno = '.$mallBySession['sno'];
                    $tmpJoin[] = ' LEFT JOIN ' . DB_GOODS_GLOBAL . ' gg ON g.goodsNo = gg.goodsNo AND gg.mallSno = '.$mallBySession['sno'];
                    $this->db->strJoin .= implode('', $tmpJoin);
                } else {
                    $this->db->strJoin = ' INNER JOIN ' . DB_GOODS_LINK_BRAND . ' glb ON g.goodsNo = glb.goodsNo  LEFT JOIN ' . DB_CATEGORY_BRAND . ' cb ON g.brandCd = cb.cateCd';
                }
                if (in_array('glc', $this->useTable) === true) {
                    $this->db->strJoin .= ' INNER JOIN ' . DB_GOODS_LINK_CATEGORY . ' glc ON g.goodsNo = glc.goodsNo ';
                }

                //상품 혜택에서 현재 진행중인 아이콘 검색
                if ($this->search['goodsIcon']) {
                    if($goodsBenefitUse == 'y') {
                        $this->db->strJoin .= ' LEFT JOIN
                        (
                        select t1.goodsNo,t1.benefitSno,t1.goodsIconCd
                        from ' . DB_GOODS_LINK_BENEFIT . ' as t1,
                        (select goodsNo, min(linkPeriodStart) as min_start from ' . DB_GOODS_LINK_BENEFIT . ' where ((benefitUseType=\'periodDiscount\' or benefitUseType=\'newGoodsDiscount\') AND linkPeriodStart < NOW() AND linkPeriodEnd > NOW()) or benefitUseType=\'nonLimit\'  group by goodsNo) as t2
                        where t1.linkPeriodStart = t2.min_start and t1.goodsNo = t2.goodsNo
                        ) as gbs on g.goodsNo = gbs.goodsNo ';
                    }

                    //상품 아이콘 테이블추가
                    if ($this->search['goodsIconCdPeriod'] && !$this->search['goodsIconCd']) {
                        $this->db->strJoin .= ' LEFT JOIN ' . DB_GOODS_ICON . ' as gi ON g.goodsNo = gi.goodsNo ';
                    } else {
                        if($goodsBenefitUse == 'y') {
                            $this->db->strJoin .= ' LEFT JOIN ' . DB_GOODS_ICON . ' as gi ON g.goodsNo = gi.goodsNo OR (gbs.benefitSno = gi.benefitSno AND gi.iconKind = \'pr\')';
                        }else{
                            $this->db->strJoin .= ' LEFT JOIN ' . DB_GOODS_ICON . ' as gi ON g.goodsNo = gi.goodsNo ';
                        }
                    }
                }

                //접근권한 체크
                if (gd_check_login()) {
                    $brandArrWhere = '(catePermission !=\'2\'  OR (catePermission=\'2\' AND FIND_IN_SET(\''.Session::get('member.groupSno').'\', REPLACE(catePermissionGroup,"'.INT_DIVISION.'",","))) OR (catePermission=\'2\' AND !FIND_IN_SET(\''.Session::get('member.groupSno').'\', REPLACE(catePermissionGroup,"'.INT_DIVISION.'",",")) AND catePermissionDisplayFl =\'y\'))';
                } else {
                    $brandArrWhere = '(catePermission=\'all\' OR (catePermission !=\'all\' AND catePermissionDisplayFl =\'y\'))';
                }

                if (gd_check_adult() === false) {
                    $brandArrWhere .= ' AND (cateOnlyAdultFl = \'n\' OR (cateOnlyAdultFl = \'y\' AND cateOnlyAdultDisplayFl = \'y\'))';
                }

                $this->db->strWhere = implode(' AND ', gd_isset($this->arrWhere))." AND glb.cateLinkFl='y' AND ".$brandArrWhere;
                $this->db->strGroup = "g.brandCd";
                $this->db->strField = 'cb.cateNm as brandNm , count(cb.cateCd) as brandCnt , cb.cateCd as brandCd ,cb.catePermission,cb.catePermissionGroup,cb.cateOnlyAdultFl';
                $query = $this->db->query_complete();
                $strSQL = 'SELECT ' . array_shift($query) . ' FROM ' . $this->goodsTable . ' g ' . implode(' ', $query);
                $brandSearchList = $this->db->query_fetch($strSQL, $this->arrBind);
                $brandSearchList = array_combine(array_column($brandSearchList, 'brandCd'), $brandSearchList);

                if($mallBySession) {
                    $bindQuery = $arrBind = null;
                    foreach(array_column($brandSearchList, 'brandCd') as $val){
                        $bindQuery[] = '?';
                        $this->db->bind_param_push($arrBind,'s',$val);
                    }
                    if (empty($bindQuery) === false) {
                        $cateCdWhere = 'cateCd IN (' . implode(",", $bindQuery) . ')';
                    } else {
                        $cateCdWhere = '1';
                    }
                    $strSQLGlobal = "SELECT cateNm as brandNm, cateCd as brandCd FROM " . DB_CATEGORY_BRAND_GLOBAL . "  WHERE " . $cateCdWhere . " AND mallSno = " . $mallBySession['sno'];
                    $tmpData = $this->db->query_fetch($strSQLGlobal,$arrBind);
                    $brandData = array_combine(array_column($tmpData, 'brandCd'), $tmpData);
                    if($brandData) {
                        $brandSearchList = array_replace_recursive($brandSearchList,$brandData);
                    }
                }

                foreach($brandSearchList as $k => $v) {

                    $disabledFl = false;
                    if ($v['cateOnlyAdultFl'] =='y' && gd_check_adult() === false) {
                        $disabledFl = true;
                    }

                    // 현 카테고리의 권한 정보
                    if ($v['catePermission'] > 0) {
                        // 현재 카테고리 권한에 따른 정보 카테고리 체크
                        if (gd_is_login() === false) {
                            $disabledFl = true;
                        }

                        if($v['catePermission'] =='2' && $v['catePermissionGroup'] && !in_array( $myGroup,explode(INT_DIVISION,$v['catePermissionGroup']))) {
                            $disabledFl = true;
                        }
                    }

                    $brandSearchList[$k]['disabledFl'] = $disabledFl;
                }
                $this->search['brandSearchList'] = $brandSearchList;
                unset($this->db->strGroup, $this->db->strField);
            }


            /* 검색 count 쿼리 */
            if ($usePage === true) {
                if ($this->search['goodsIcon']) {
                    $totalCountSQL =  ' SELECT COUNT(1) as totalCnt FROM ( SELECT g.goodsNo FROM '.$this->goodsTable.' as g  '.implode('', $arrJoin).'  WHERE '.implode(' AND ', $this->arrWhere) . $goodsIconStrGroup . ') AS tbl';
                } else {
                    $totalCountSQL =  ' SELECT COUNT(DISTINCT g.goodsNo) AS totalCnt FROM '.$this->goodsTable.' as g  '.implode('', $arrJoin).'  WHERE '.implode(' AND ', $this->arrWhere);
                }
                $dataCount = $this->db->slave()->query_fetch($totalCountSQL, $this->arrBind,false);
                $page->recode['total'] = $dataCount['totalCnt']; //검색 레코드 수

                if ($getValue['offsetGoodsNum'] && $page->recode['total'] > $getValue['offsetGoodsNum']) {
                    $page->recode['total'] = $getValue['offsetGoodsNum'];
                }

                $page->setPage();
            }

            // 상품 정보 세팅
            if (empty($data) === false) {
                if($getValue['isMain']) $this->setGoodsListInfo($data, $imageType, $optionFl, $couponPriceFl, null,'main',$brandFl, $getValue['mainLinkData']);
                else $this->setGoodsListInfo($data, $imageType, $optionFl, $couponPriceFl, null,null,$brandFl);
            }

        }

        unset($this->arrBind, $this->arrWhere);

        // 각 데이터 배열화
        $getData['listData'] = gd_htmlspecialchars_stripslashes(gd_isset($data));
        $getData['listSearch'] = gd_htmlspecialchars($this->search);
        unset($this->search);

        return $getData;
    }

	public function getGoodsView($goodsNo){
		$getData = parent::getGoodsView($goodsNo);

		//dpx-kwc 비회원이 비회원상품 접근시 접근되도록 추가
		if(!gd_check_login() && $getData['goodsAccess'] == 'guest'){
			$getData['goodsAccess'] = 'all';
			$getData['goodsPermission'] = 'all';
		}
		return $getData;
	}
}
