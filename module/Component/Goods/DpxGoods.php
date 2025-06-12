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

use Component\Database\DBTableField;
use Component\ExchangeRate\ExchangeRate;
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

/**
 * 상품 class
 */
class DpxGoods  extends \Bundle\Component\Goods\Goods
{


    /**
     * 생성자
     */
    public function __construct()
    {
		parent::__construct();

    }

	/**
     * 상품 정보 출력 (상품 상세)
     *
     * @param string $goodsNo 상품 번호
     *
     * @return array 상품 정보
     * @throws Except
     */
    public function getGoodsView($goodsNo)
    {
		$mallBySession = SESSION::get(SESSION_GLOBAL_MALL);

        // Validation - 상품 코드 체크
        if (Validator::required($goodsNo, true) === false) {
            throw new Exception(__('상품 코드를 확인해주세요.'));
        }

        // 필드 설정
        $arrExcludeGoods = ['goodsIconStartYmd', 'goodsIconEndYmd', 'goodsIconCdPeriod', 'goodsIconCd', 'memo'];
        $arrFieldGoods = DBTableField::setTableField('tableGoods', null, $arrExcludeGoods, 'g');
        $this->db->strField = implode(', ', $arrFieldGoods) . ',
            ( if (g.soldOutFl = \'y\' , \'y\', if (g.stockFl = \'y\' AND g.totalStock <= 0, \'y\', \'n\') ) ) as soldOut,
            ( if (g.' . $this->goodsSellFl . ' = \'y\', g.' . $this->goodsSellFl . ', \'n\')  ) as orderPossible';

        // 조건절 설정
        //if(!Session::has('manager.managerId')) $arrWhere[] = 'g.' . $this->goodsDisplayFl . ' = \'y\'';
        $arrWhere[] = 'g.delFl = \'n\'';
        $arrWhere[] = 'g.applyFl = \'y\'';

        $this->db->strWhere = implode(' AND ', $arrWhere);

        // 상품 기본 정보
        $getData = $this->getGoodsInfo($goodsNo);


/*
        // 아이콘 테이블 분리로 인한 추가
        $tmpGoodsIcon = [];
        $iconList = $this->getGoodsDetailIcon($goodsNo);
        foreach ($iconList as $iconKey => $iconVal) {
            if ($iconVal['iconKind'] == 'pe') {
                if (empty($iconVal['goodsIconStartYmd']) === false && empty($iconVal['goodsIconEndYmd']) === false && empty($iconVal['goodsIconCd']) === false && strtotime($iconVal['goodsIconStartYmd'] . ' 00:00:00') <= time() && strtotime($iconVal['goodsIconEndYmd'] . ' 23:59:59') >= time()) {
                    $tmpGoodsIcon[] = $iconVal['goodsIconCd'];
                }
            }

            if ($iconVal['iconKind'] == 'un') {
                $tmpGoodsIcon[] = $iconVal['goodsIconCd'];
            }
        }

        $getData['goodsIcon'] = implode(INT_DIVISION,$tmpGoodsIcon);

        //상품 혜택 정보
        $goodsBenefit = \App::load('\\Component\\Goods\\GoodsBenefit');
        $getData = $goodsBenefit->goodsDataFrontConvert($getData,null,'goodsIcon');
*/

		//designpix.kwc

		$getData[err]="";

        if (empty($getData) === true && !Session::has('manager.managerId')) {
			//designpix.kwc
			$getData[err]="해당 상품은 쇼핑몰 노출안함 상태로 검색되지 않습니다.";
        }

        // 삭제된 상품에 접근시 예외 처리
        if ($getData['delFl'] === 'y') {
			//designpix.kwc
			$getData[err]="본 상품은 삭제되었습니다.";
        }


        // 승인중인 상품에 대한 접근 예외 처리
        if ($getData['applyFl'] != 'y') {
			//designpix.kwc
			$getData[err]="본 상품은 접근이 불가능 합니다.";
        }

        // 브랜드 정보
        if (empty($getData['brandCd']) === false) {
            $brand = \App::load('\\Component\\Category\\Brand');
            $getData['brandNm'] = $brand->getCategoryData($getData['brandCd'], null, 'cateNm')[0]['cateNm'];
        } else {
            $getData['brandNm'] = '';
        }

        if($mallBySession) {
            $arrFieldGoodsGlobal = DBTableField::setTableField('tableGoodsGlobal',null,['mallSno']);
            $strSQLGlobal = "SELECT gg." . implode(', gg.', $arrFieldGoodsGlobal) . " FROM ".DB_GOODS_GLOBAL." as gg WHERE   gg.goodsNo  = '".$getData['goodsNo']."' AND gg.mallSno = '".$mallBySession['sno']."'";
            $tmpData = $this->db->query_fetch($strSQLGlobal,null,false);
            if($tmpData) $getData = array_replace_recursive($getData, array_filter(array_map('trim',$tmpData)));
        }
        //카테고리 정보
        $cate = \App::load('\\Component\\Category\\Category');
        $tmpCategoryList = $cate->getCateCd($getData['goodsNo']);
        if($tmpCategoryList) {
            foreach($tmpCategoryList as $k => $v) {
                $categoryList[$v] = gd_htmlspecialchars_decode($cate->getCategoryPosition($v));
            }
        }
        if($categoryList) $getData['categoryList'] = $categoryList;

        // 대표카테고리명 정보
        if (empty($getData['cateCd']) === false) {
            $getData['cateNm'] = $cate->getCategoryData($getData['cateCdCd'], null, 'cateNm')[0]['cateNm'];
        } else {
            $getData['cateNm'] = '';
        }

        // 추가항목 정보
        $getData['addInfo'] = $this->getGoodsAddInfo($goodsNo); // 추가항목 정보

        // 이미지 정보
        $tmp['image'] = $this->getGoodsImage($goodsNo, ['detail', 'magnify']);

        // 상품 아이콘
        if ($getData['goodsIcon']) {
            $tmp['goodsIcon'] = $this->getGoodsIcon($getData['goodsIcon']);
        }

        // 상품 아이콘
        if ($getData['goodsBenefitIconCd']) {
            $tmp['goodsBenefitIconCd'] = $this->getGoodsIcon($getData['goodsBenefitIconCd']);
        }

        $imgConfig = gd_policy('goods.image');

        //품절상품 설정
        if(Request::isMobile()) {
            $soldoutDisplay = gd_policy('soldout.mobile');
        } else {
            $soldoutDisplay = gd_policy('soldout.pc');
        }

        // 상품 이미지 처리
        $getData['magnifyImage'] = 'n';
        if (empty($tmp['image'])) {
            $getData['image']['detail'][0] = '';
            $getData['image']['thumb'][0] = '';
        } else {
            foreach ($tmp['image'] as $key => $val) {
                $imageHeightSize = '';
                if ($imgConfig['imageType'] == 'fixed') {
                    foreach ($imgConfig[$val['imageKind']] as $k => $v) {
                        if (stripos($k, 'size') === 0) {
                            if ($val['imageSize'] == $v) {
                                $imageHeightSize = $imgConfig[$val['imageKind']]['h' . $k];
                                break;
                            }
                        }
                    }
                }

                // 이미지 사이즈가 없는 경우
                if (empty($val['imageSize']) === true) {
                    $imageSize = $imgConfig[$val['imageKind']]['size1'];
                } else {
                    $imageSize = $val['imageSize'];
                }

                //실제 이미지 사이즈가 있는 경우
                if($val['imageRealSize']) {
                    $imageSize = explode(",",$val['imageRealSize'])[0];
                }

                // 모바일샵 접속인 경우
                if (Request::isMobile()) {
                    $imageSize = 140;
                    $imageHeightSize = '';
                }

                $getData['image'][$val['imageKind']]['img'][] = gd_html_preview_image($val['imageName'], $getData['imagePath'], $getData['imageStorage'], $imageSize, 'goods', $getData['goodsNm'], null, false, false, $imageHeightSize);

                $getData['image'][$val['imageKind']]['thumb'][] = gd_html_preview_image($val['imageName'], $getData['imagePath'], $getData['imageStorage'], 68, 'goods', $getData['goodsNm'], null, false, true);

                if ($val['imageKind'] == 'magnify') {
                    $getData['magnifyImage'] = 'y';
                }
            }
            if (isset($getData['image']) === false) {
                $getData['image']['detail'][0] = '';
                $getData['image']['thumb'][0] = '';
            }
        }

        // 소셜 공유용 이미지 처리(이미지 없는경우 빈 이미지 출력되도록 수정)
        $socialShareImage = SkinUtils::imageViewStorageConfig($tmp['image'][0]['imageName'], $getData['imagePath'], $getData['imageStorage'], $imageSize, 'goods');
        $getData['social'] = $socialShareImage[0];


        // 상품 혜택 아이콘 처리
        $getData['goodsIcon'] = '';
        $getData['goodsBenefitIconCd'] = '';
        if (empty($tmp['goodsBenefitIconCd']) === false) {
            foreach ($tmp['goodsBenefitIconCd'] as $key => $val) {
                $getData['goodsIcon'] .= gd_html_image(UserFilePath::icon('goods_icon', $val['iconImage'])->www(), $val['iconNm']) . ' ';
            }
        }

        // 상품 아이콘 처리
        if (empty($tmp['goodsIcon']) === false) {
            foreach ($tmp['goodsIcon'] as $key => $val) {
                $getData['goodsIcon'] .= gd_html_image(UserFilePath::icon('goods_icon', $val['iconImage'])->www(), $val['iconNm']) . ' ';
            }
        }


        // 옵션 체크, 옵션 사용인 경우
        if ($getData['optionFl'] === 'y') {
            // 옵션 & 가격 정보
            $getData['option'] = gd_htmlspecialchars($this->getGoodsOption($goodsNo, $getData));
            if($getData['option']) {
                $getData['optionEachCntFl'] = 'many'; // 옵션 개수
                if (empty($getData['option']['optVal'][2]) === true) {
                    $getData['optionEachCntFl'] = 'one'; // 옵션 개수

                    // 분리형 옵션인데 옵션이 하나인 경우 일체형으로 변경
                    if ($getData['optionDisplayFl'] == 'd') {
                        $getData['optionDisplayFl'] = 's';
                    }
                }


                // 상품 옵션 아이콘
                $tmp['optionIcon'] = $this->getGoodsOptionIcon($goodsNo);

                if (empty($tmp['optionIcon']) === false) {
                    $imageSize = $imgConfig['detail'];
                    foreach ($tmp['optionIcon'] as $key => $val) {
                        if (empty($val['goodsImage']) === false) {
                            $getData['optionIcon']['goodsImage'][$val['optionValue']] =SkinUtils::imageViewStorageConfig($val['goodsImage'], $getData['imagePath'], $getData['imageStorage'], '100', 'goods')[0];
                            if( $getData['optionImageDisplayFl'] =='y') {
                                $optionImagePreview = gd_html_preview_image($val['goodsImage'], $getData['imagePath'], $getData['imageStorage'], $imageSize, 'goods', $getData['goodsNm'], null, false, false);;
                                $getData['image']['detail']['img'][] =$optionImagePreview;
                                $getData['image']['detail']['thumb'][] = $optionImagePreview;
                            }
                        }
                    }
                    // 옵션 값을 json_encode 처리함
                    //$getData['optionIcon'] = json_encode($getData['optionIcon']);
                }
                // 분리형 옵션인 경우
                if ($getData['optionDisplayFl'] == 'd') {
                    // 옵션명
                    $getData['optionName'] = explode(STR_DIVISION, $getData['optionName']);

                    // 첫번째 옵션 값
                    $getData['optionDivision'] = $getData['option']['optVal'][1];

                    unset($getData['option']['optVal']);
                    // 일체형 옵션인 경우
                } else if ($getData['optionDisplayFl'] == 's') {
                    unset($getData['option']['optVal']);

                    // 옵션명
                    $getData['optionName'] = str_replace(STR_DIVISION, '/', $getData['optionName']);

                    foreach ($getData['option'] as $key => $val) {

                        if($getData['optionIcon']['goodsImage'][$val['optionValue1']]) {
                            $getData['option'][$key]['optionImage'] = $getData['optionIcon']['goodsImage'][$val['optionValue1']];
                        }

                        $optionValue[$key] = [];
                        for ($i = 1; $i <= DEFAULT_LIMIT_OPTION; $i++) {
                            if (is_null($val['optionValue' . $i]) === false && strlen($val['optionValue' . $i]) > 0) {
                                $optionValue[$key][] = $val['optionValue' . $i];
                            }
                            unset($getData['option'][$key]['optionValue' . $i]);
                        }
                        $getData['option'][$key]['optionValue'] = implode('/', $optionValue[$key]);
                    }
                }

                $getData['stockCnt'] = $getData['option'][0]['stockCnt'];

            } else {
                throw new Exception(__('상품 옵션을 확인해주세요.'));
            }
        } else {
            $getData['option'] = gd_htmlspecialchars($this->getGoodsOption($goodsNo, $getData));
            $getData['stockCnt'] = $getData['totalStock'];
            if($getData['option'][0]['optionPrice'] > 0) $getData['option'][0]['optionPrice'] = 0; //옵션사용안함으로 가격 없음
            if($getData['stockFl'] =='y' && $getData['minOrderCnt'] > $getData['totalStock'])  $getData['orderPossible'] = 'n';
        }

        //상품 상세 설명 관련
        if($getData['goodsDescriptionSameFl'] =='y') {
            $getData['goodsDescriptionMobile'] = $getData['goodsDescription'];
        }

        /* 타임 세일 관련 */
        $getData['timeSaleFl'] = false;
        if (gd_is_plus_shop(PLUSSHOP_CODE_TIMESALE) === true) {
            $timeSale = \App::load('\\Component\\Promotion\\TimeSale');
            $timeSaleInfo = $timeSale->getGoodsTimeSale($goodsNo);
            if($timeSaleInfo) {
                $getData['timeSaleFl'] = true;
                if($timeSaleInfo['timeSaleCouponFl'] =='n') $couponConfig['couponUseType']  = "n";
                $timeSaleInfo['timeSaleDuration'] = strtotime($timeSaleInfo['endDt'])- time();
                if($timeSaleInfo['orderCntDisplayFl'] =='y' ) { //타임세일 진행기준 판매개수
                    $arrTimeSaleBind = [];
                    $strTimeSaleSQL = "SELECT sum(orderCnt) as orderCnt FROM " . DB_GOODS_STATISTICS . " WHERE goodsNo = ?";
                    $this->db->bind_param_push($arrTimeSaleBind, 'i', $goodsNo);
                    if($timeSaleInfo['orderCntDateFl'] =='y' ) {
                        $strTimeSaleSQL .= " AND UNIX_TIMESTAMP(regDt) <  ? AND  UNIX_TIMESTAMP(regDt)  > ?";
                        $this->db->bind_param_push($arrTimeSaleBind, 'i', strtotime($timeSaleInfo['endDt']));
                        $this->db->bind_param_push($arrTimeSaleBind, 'i', strtotime($timeSaleInfo['startDt']));
                    }
                    $timeSaleInfo['orderCnt'] = $this->db->query_fetch($strTimeSaleSQL, $arrTimeSaleBind, false)['orderCnt'];
                    unset($arrTimeSaleBind,$strTimeSaleSQL);
                }

                $getData['timeSaleInfo'] = $timeSaleInfo;
                if($getData['goodsPrice'] > 0 ) {
                    $getData['oriGoodsPrice'] = $getData['goodsPrice'] ;
                    $getData['goodsPrice'] = gd_number_figure($getData['goodsPrice'] - (($timeSaleInfo['benefit'] / 100) * $getData['goodsPrice']), $this->trunc['unitPrecision'], $this->trunc['unitRound']);
                }

                //상품 옵션가(일체형) 타임세일 할인율 적용 ( 텍스트 옵션가 / 추가상품가격 제외)
                if($getData['optionFl'] === 'y'){
                    foreach ($getData['option'] as $key => $val){
                        $getData['option'][$key]['optionPrice'] = gd_number_figure($val['optionPrice'] - (($timeSaleInfo['benefit'] / 100) * $val['optionPrice']), $this->trunc['unitPrecision'], $this->trunc['unitRound']);
                    }
                }
            }
        }
        $couponConfig = gd_policy('coupon.config');

        // 쿠폰가 회원만 노출
        if ($couponConfig['couponDisplayType'] == 'member') {
            if (gd_check_login()) {
                $couponPriceYN = true;
            } else {
                $couponPriceYN = false;
            }
        } else {
            $couponPriceYN = true;
        }

        // 쿠폰 할인 금액
        if ($couponConfig['couponUseType'] == 'y' && $couponPriceYN  && $getData['goodsPrice'] > 0 && empty($getData['goodsPriceString']) === true) {
            // 쿠폰 모듈 설정

            $coupon = \App::load('\\Component\\Coupon\\Coupon');
            // 해당 상품의 모든 쿠폰
            $couponArrData = $coupon->getGoodsCouponDownList($getData['goodsNo']);

            // 해당 상품의 쿠폰가
            $couponSalePrice = $coupon->getGoodsCouponDisplaySalePrice($couponArrData, $getData['goodsPrice']);
            if ($couponSalePrice) {
                $getData['couponPrice'] = $getData['goodsPrice'] - $couponSalePrice;
                $getData['couponSalePrice'] = $couponSalePrice;
                if ($getData['couponPrice'] < 0) {
                    $getData['couponPrice'] = 0;
                }
            }
        }


        //추가 상품 정보
        if ($getData['addGoodsFl'] === 'y' && empty($getData['addGoods']) === false) {

            $getData['addGoods'] = json_decode(gd_htmlspecialchars_stripslashes($getData['addGoods']), true);

            //필수 추가상품 중 승인완료가 아닌 상품이 있는 경우 구매 불가
            $addGoods = \App::load('\\Component\\Goods\\AddGoods');
            if ($getData['addGoods']) {
                foreach ($getData['addGoods'] as $k => $v) {

                    if($v['addGoods']) {
                        if($v['mustFl'] =='n') $addGoods->arrWhere[] = "applyFl = 'y'";
                        else {
                            $applyCheckCnt = $this->db->getCount(DB_ADD_GOODS, 'addGoodsNo', 'WHERE applyFl !="y"  AND addGoodsNo IN ("' . implode('","', $v['addGoods']) . '")');
                            if($applyCheckCnt > 0 ) {
                                $getData['orderPossible'] = 'n';
                                break;
                            } else {
                                $addGoods->arrWhere[] = "applyFl != ''";
                            }
                        }

                        foreach ($v['addGoods']as $k1 => $v1) {
                            $tmpField[] = 'WHEN \'' . $v1 . '\' THEN \'' . sprintf("%0".strlen(count($v['addGoods']))."d",$k1) . '\'';
                        }

                        $sortField = ' CASE ag.addGoodsNo ' . implode(' ', $tmpField) . ' ELSE \'\' END ';
                        unset($tmpField);

                        $getData['addGoods'][$k]['addGoodsList'] = $addGoods->getInfoAddGoodsGoods($v['addGoods'],null,$sortField);
                        $getData['addGoods'][$k]['addGoodsImageFl'] = "n";
                        if($getData['addGoods'][$k]['addGoodsList']) {
                            foreach($getData['addGoods'][$k]['addGoodsList'] as $k1 => $v1) {
                                // strip_tags 처리를 통해 결제오류 수정
                                $getData['addGoods'][$k]['addGoodsList'][$k1]['goodsNm'] = htmlentities(stripslashes(StringUtils::stripOnlyTags($getData['addGoods'][$k]['addGoodsList'][$k1]['goodsNm'])));
                                $getData['addGoods'][$k]['addGoodsList'][$k1]['optionNm'] = htmlentities(stripslashes(StringUtils::stripOnlyTags($getData['addGoods'][$k]['addGoodsList'][$k1]['optionNm'])));

                                //추가 상품등록페이지 - 추가 상품명
                                if($v1['globalGoodsNm']) $getData['addGoods'][$k]['addGoodsList'][$k1]['goodsNm'] = htmlentities(stripslashes(StringUtils::stripOnlyTags($v1['globalGoodsNm'])));
                                if($v1['imageNm']) {
                                    $getData['addGoods'][$k]['addGoodsList'][$k1]['imageSrc'] = SkinUtils::imageViewStorageConfig($v1['imageNm'], $v1['imagePath'], $v1['imageStorage'], '50', 'add_goods')['0'];
                                    $getData['addGoods'][$k]['addGoodsImageFl'] = "y";
                                }
                            }
                        }
                    }
                }
            }
        }


        // 텍스트 옵션 정보
        if ($getData['optionTextFl'] === 'y') {
            $getData['optionText'] = gd_htmlspecialchars($this->getGoodsOptionText($goodsNo));
        }

        // QR코드
        if (gd_is_plus_shop(PLUSSHOP_CODE_QRCODE) === true) {
            $qrcode = gd_policy('promotion.qrcode'); // QR코드 설정
            if ($qrcode['useGoods'] !== 'y') {
                $getData['qrCodeFl'] = 'n';
            }
        } else {
            $getData['qrCodeFl'] = 'n';
        }

        // 상품 정보 처리
        $getData['goodsNmDetail'] = StringUtils::htmlSpecialCharsStripSlashes($this->getGoodsName($getData['goodsNmDetail'], $getData['goodsNm'], $getData['goodsNmFl'])); // 상품 상세 페이지 -  상품명
        if (Validator::date($getData['makeYmd'], true) === false) { // 제조일 체크
            $getData['makeYmd'] = null;
        }
        if (Validator::date($getData['launchYmd'], true) === false) { // 출시일 체크
            $getData['launchYmd'] = null;
        }

        //배송비 관련
        if ($getData['deliverySno']) {
            $delivery = \App::load('\\Component\\Delivery\\Delivery');
            $deliveryData = $delivery->getDataSnoDelivery($getData['deliverySno']);
            if ($deliveryData['basic']['areaFl'] == 'y' && gd_isset($deliveryData['basic']['areaGroupNo'])) {
                $deliveryData['areaDetail'] = $delivery->getSnoDeliveryArea($deliveryData['basic']['areaGroupNo']);
            }

            $deliveryData['basic']['fixFlText'] = $delivery->getFixFlText($deliveryData['basic']['fixFl']);
            $deliveryData['basic']['goodsDeliveryFlText'] = $delivery->getGoodsDeliveryFlText($deliveryData['basic']['goodsDeliveryFl']);
            $deliveryData['basic']['collectFlText'] = $delivery->getCollectFlText($deliveryData['basic']['collectFl']);
            $deliveryData['basic']['areaFlText'] = $delivery->getAddFlText($deliveryData['basic']['areaFl']);
            $deliveryData['basic']['pricePlusStandard'] = explode(STR_DIVISION, $deliveryData['basic']['pricePlusStandard']);
            $deliveryData['basic']['priceMinusStandard'] = explode(STR_DIVISION, $deliveryData['basic']['priceMinusStandard']);
            // 가공된 배송 방식 데이터
            $deliveryData['basic']['deliveryMethodFlData'] = [];
            $deliveryMethodFlArr = array_values(array_filter(explode(STR_DIVISION, $deliveryData['basic']['deliveryMethodFl'])));
            if($deliveryMethodFlArr > 0){
                foreach($deliveryMethodFlArr as $key => $value){
                    if($value === 'etc'){
                        $deliveryMethodListName = gd_get_delivery_method_etc_name();
                    }
                    else {
                        $deliveryMethodListName = $delivery->deliveryMethodList['name'][$value];
                    }
                    $deliveryData['basic']['deliveryMethodFlData'][$value] = $deliveryMethodListName;

                    if($key === 0){
                        $deliveryData['basic']['deliveryMethodFlFirst'] = [
                            'code' => $value,
                            'name' => $deliveryMethodListName,
                        ];
                    }
                }
            }
            //배송방식 방문수령지
            if($deliveryData['basic']['dmVisitTypeDisplayFl'] !== 'y'){
                $deliveryMethodVisitArea = [];
                $deliveryMethodVisitArea[] = trim($deliveryData['basic']['dmVisitTypeAddress']);
                $deliveryMethodVisitArea[] = trim($deliveryData['basic']['dmVisitTypeAddressSub']);
                $deliveryData['basic']['deliveryMethodVisitArea'] = implode(" ", $deliveryMethodVisitArea);
            }

            $getData['delivery'] = $deliveryData;

            // 상품판매가를 기준으로 배송비 선택해서 charge의 키를 저장한다.
            $getData['selectedDeliveryPrice'] = 0;
            if (in_array($deliveryData['basic']['fixFl'], ['price', 'weight'])) {
                // 비교할 필드값 설정
                $compareField = $getData['goods' . ucfirst($deliveryData['basic']['fixFl'])];
                foreach ($getData['delivery']['charge'] as $dKey => $dVal) {
                    // 금액 or 무게가 범위에 없으면 통과
                    if (floatval($dVal['unitEnd']) > 0) {
                        if (floatval($dVal['unitStart']) <= floatval($compareField) && floatval($dVal['unitEnd']) > floatval($compareField)) {
                            $getData['selectedDeliveryPrice'] = $dKey;
                            break;
                        }
                    } else {
                        if (floatval($dVal['unitStart']) <= floatval($compareField)) {
                            $getData['selectedDeliveryPrice'] = $dKey;
                            break;
                        }
                    }
                }
            }

            /*
             * 수량별 배송비 이면서 범위 반복 설정을 사용 할 경우 수량1의 기준으로 배송비 노출
             * @todo 추후 금액별, 무게별 배송비의 범위 반복 설정 사용일 경우도 계산해서 노출해야 하므로 임시로 노출시킨다.
             */
            if ($deliveryData['basic']['fixFl'] === 'count' && $deliveryData['basic']['rangeRepeat'] === 'y') {
                if((int)$deliveryData['charge'][0]['unitEnd'] <= 1){
                    $getData['selectedDeliveryPrice'] = 1;
                }
            }
        }

        // 상품 필수 정보
        $getData['goodsMustInfo'] = json_decode(gd_htmlspecialchars_stripslashes($getData['goodsMustInfo']), true);


        // 마일리지 설정
        $mileage = gd_mileage_give_info();

        $getData['goodsMileageFl'] = 'y';
        // 통합 설정인 경우 마일리지 설정
        if ($getData['mileageFl'] == 'c' && $mileage['give']['giveFl'] == 'y') {
            $mileagePercent = $mileage['give']['goods'] / 100;

            // 상품 기본 마일리지 정보
            $getData['mileageBasic'] = gd_number_figure($getData['goodsPrice'] * $mileagePercent, $mileage['trunc']['unitPrecision'], $mileage['trunc']['unitRound']);

            // 상품 옵션 마일리지 정보
            if ($getData['optionFl'] === 'y') {
                foreach ($getData['option'] as $key => $val) {
                    $getData['option'][$key]['mileageOption'] = gd_number_figure($val['optionPrice'] * $mileagePercent, $mileage['trunc']['unitPrecision'], $mileage['trunc']['unitRound']);
                }
            }


            // 추가 상품 마일리지 정보
            if ($getData['addGoodsFl'] === 'y' && empty($getData['addGoods']) === false && empty($getData['addGoodsGoodsNo']) === false) {
                foreach ($getData['addGoods'] as $key => $val) {
                    $getData['addGoods'][$key]['mileageAddGoods'] = gd_number_figure($val['goodsPrice'] * $mileagePercent, $mileage['trunc']['unitPrecision'], $mileage['trunc']['unitRound']);
                }
            }


            // 상품 텍스트 옵션 마일리지 정보
            if ($getData['optionTextFl'] === 'y') {
                foreach ($getData['optionText'] as $key => $val) {
                    $getData['optionText'][$key]['mileageOptionText'] = gd_number_figure($val['addPrice'] * $mileagePercent, $mileage['trunc']['unitPrecision'], $mileage['trunc']['unitRound']);
                }
            }

            // 개별 설정인 경우 마일리지 설정
        } else if ($getData['mileageFl'] == 'g') {
            $mileagePercent = $getData['mileageGoods'] / 100;

            // 상품 기본 마일리지 정보
            if ($getData['mileageGoodsUnit'] === 'percent') {
                $getData['mileageBasic'] = gd_number_figure($getData['goodsPrice'] * $mileagePercent, $mileage['trunc']['unitPrecision'], $mileage['trunc']['unitRound']);
            } else {
                // 정액인 경우 해당 설정된 금액으로
                $getData['mileageBasic'] = $getData['mileageGoods'];
            }

            // 상품 옵션 마일리지 정보
            if ($getData['optionFl'] === 'y') {
                foreach ($getData['option'] as $key => $val) {
                    if ($getData['mileageGoodsUnit'] === 'percent') {
                        $getData['option'][$key]['mileageOption'] = gd_number_figure($val['optionPrice'] * $mileagePercent, $mileage['trunc']['unitPrecision'], $mileage['trunc']['unitRound']);
                    } else {
                        // 정액인 경우 0 (상품 기본에만 있음)
                        $getData['option'][$key]['mileageOption'] = 0;
                    }
                }
            }

            // 추가 상품 마일리지 정보
            if ($getData['addGoodsFl'] === 'y' && empty($getData['addGoods']) === false && empty($getData['addGoodsGoodsNo']) === false) {
                foreach ($getData['addGoods'] as $key => $val) {
                    if ($getData['mileageGoodsUnit'] === 'percent') {
                        $getData['addGoods'][$key]['mileageAddGoods'] = gd_number_figure($val['goodsPrice'] * $mileagePercent, $mileage['trunc']['unitPrecision'], $mileage['trunc']['unitRound']);
                    } else {
                        // 정액인 경우 0 (상품 기본에만 있음)
                        $getData['addGoods'][$key]['mileageAddGoods'] = 0;
                    }
                }
            }

            // 상품 텍스트 옵션 마일리지 정보
            if ($getData['optionTextFl'] === 'y') {
                foreach ($getData['optionText'] as $key => $val) {
                    if ($getData['mileageGoodsUnit'] === 'percent') {
                        $getData['optionText'][$key]['mileageOptionText'] = gd_number_figure($val['addPrice'] * $mileagePercent, $mileage['trunc']['unitPrecision'], $mileage['trunc']['unitRound']);
                    } else {
                        // 정액인 경우 0 (상품 기본에만 있음)
                        $getData['optionText'][$key]['mileageOptionText'] = 0;
                    }
                }
            }
        } else {
            $getData['goodsMileageFl'] = 'n';
        }


        $getData['mileageConf'] = $mileage;

        //상품 가격 노출 관련
        $goodsPriceDisplayFl = gd_policy('goods.display')['priceFl'];
        $getData['goodsPriceDisplayFl'] = 'y';


        //상품별할인
        if ($getData['goodsDiscountFl'] == 'y') {
            if ($getData['goodsDiscountUnit'] == 'price') $getData['goodsDiscountPrice'] = $getData['goodsPrice'] - $getData['goodsDiscount'];
            else $getData['goodsDiscountPrice'] = $getData['goodsPrice'] - (($getData['goodsDiscount'] / 100) * $getData['goodsPrice']);
        }

        //회원관련
        if (gd_is_login() === true) {
            // 회원 그룹 설정
            $memberGroup = \App::load('\\Component\\Member\\MemberGroup');
            $getData['memberDc'] = $memberGroup->getGroupForSale($goodsNo, $getData['cateCd']);

            //회원 할인가
            if ($getData['memberDc'] && $getData['dcLine'] && $getData['dcPrice']) {
                $getData['memberDcPriceFl'] = 'y';
                if ($getData['memberDc']['dcType'] == 'price') $getData['memberDcPrice'] = $getData['memberDc']['dcPrice'];
                else $getData['memberDcPrice'] = (($getData['memberDc']['dcPercent'] / 100) * $getData['goodsPrice']);

            } else $getData['memberDcPriceFl'] = 'n';


            //회원 적립
            if ($getData['memberDc'] && $getData['mileageLine'] && $getData['mileageLine']) $getData['memberMileageFl'] = 'y';
            else $getData['memberMileageFl'] = 'n';

            //결제수한제단 체크
            if( $getData['memberDc']['settleGb'] !='all' && $getData['payLimitFl'] == 'y' && gd_isset($getData['payLimit'])) {
                $getData['memberDc']['settleGb'] = $getData['memberDc']['settleGb'] =='bank'  ?  ['gb','gm','gd'] : ['pg','gm','gd'];
                $payLimit = array_intersect($getData['memberDc']['settleGb'], explode(STR_DIVISION, $getData['payLimit']));

                if(count($payLimit) == 0) {
                    $getData['orderPossible'] = 'n';
                }
            }

        } else {
            $getData['memberDcPriceFl'] = 'n';
            $getData['memberMileageFl'] = 'n';
        }

        // 구매 가능여부 체크
        if ($getData['soldOut'] == 'y') {
            $getData['orderPossible'] = 'n';
            if($goodsPriceDisplayFl =='n' && $soldoutDisplay['soldout_price'] !='price') $getData['goodsPriceDisplayFl'] = 'n';
        }

        //구매불가 대체 문구 관련
        if($getData['goodsPermission'] !='all' && (($getData['goodsPermission'] =='member'  && gd_is_login() === false) || ($getData['goodsPermission'] =='group'  && !in_array(Session::get('member.groupSno'),explode(INT_DIVISION,$getData['goodsPermissionGroup']))))) {
            if($getData['goodsPermissionPriceStringFl'] =='y' ) $getData['goodsPriceString'] = $getData['goodsPermissionPriceString'];
            $getData['orderPossible'] = 'n';
        }

        if (((gd_isset($getData['salesStartYmd']) != '' && gd_isset( $getData['salesEndYmd']) != '') && ($getData['salesStartYmd'] != '0000-00-00 00:00:00' && $getData['salesEndYmd'] != '0000-00-00 00:00:00')) && (strtotime($getData['salesStartYmd']) > time() || strtotime($getData['salesEndYmd']) < time())) {
            $getData['orderPossible'] = 'n';
        }

        if ($getData['goodsMileageFl'] == 'y' || $getData['memberMileageFl'] == 'y' || $getData['goodsDiscountFl'] == 'y' || $getData['memberDcPriceFl'] == 'y') {
            $getData['benefitPossible'] = 'y';
        } else $getData['benefitPossible'] = 'n';

        //판매기간 사용자 노출
        if (((gd_isset($getData['salesStartYmd']) != '' && gd_isset( $getData['salesEndYmd']) != '') && ($getData['salesStartYmd'] != '0000-00-00 00:00:00' && $getData['salesEndYmd'] != '0000-00-00 00:00:00'))) {
            $getData['salesData'] = $getData['salesStartYmd']." ~ ".$getData['salesEndYmd'];
        } else {
            $getData['salesData'] = __('제한없음');
        }

        // 관련 상품
        $getData['relation']['relationFl'] = $getData['relationFl'];
        $getData['relation']['relationCnt'] = $getData['relationCnt'];
        $getData['relation']['relationGoodsNo'] = $getData['relationGoodsNo'];
        $getData['relation']['cateCd'] = $getData['cateCd'];
        unset($getData['relationFl'], $getData['relationCnt'], $getData['relationGoodsNo']);

        // 상품 이용 안내
        $getData['detailInfo']['detailInfoDelivery'] = $getData['detailInfoDelivery'];
        $getData['detailInfo']['detailInfoAS'] = $getData['detailInfoAS'];
        $getData['detailInfo']['detailInfoRefund'] = $getData['detailInfoRefund'];
        $getData['detailInfo']['detailInfoExchange'] = $getData['detailInfoExchange'];
        unset($getData['detailInfoDelivery'], $getData['detailInfoAS'], $getData['detailInfoRefund'], $getData['detailInfoExchange']);

        // 가격 대체 문구가 있는 경우 주문금지
        if (empty($getData['goodsPriceString']) === false) {
            $getData['orderPossible'] = 'n';
            if($goodsPriceDisplayFl =='n') $getData['goodsPriceDisplayFl'] = 'n';
        }


        //최소구매수량 관련
        if ($getData['fixedSales'] != 'goods' && gd_isset($getData['salesUnit'], 0) > $getData['minOrderCnt']) {
            $getData['minOrderCnt'] = $getData['salesUnit'];
        }

        //초기상품수량
        $getData['goodsCnt'] = 1;
        if ($getData['fixedSales'] != 'goods') {
            if ($getData['salesUnit'] > 1) {
                $getData['goodsCnt'] = $getData['salesUnit'];
            } else {
                if ($getData['fixedOrderCnt'] != 'goods') {
                    $getData['goodsCnt'] = $getData['minOrderCnt'];
                }
            }
        }

        //
        if (gd_is_plus_shop(PLUSSHOP_CODE_COMMONCONTENT) === true) {
            $commonContent = \App::load('\\Component\\Goods\\CommonContent');
            $getData['commonContent'] = $commonContent->getCommonContent($getData['goodsNo'], $getData['scmNo']);
        }

        //상품 재입고 노출여부
        if (gd_is_plus_shop(PLUSSHOP_CODE_RESTOCK) === true) {
            $getData['restockUsableFl'] = $this->setRestockUsableFl($getData);
        }

        // 재고량 체크
        $getData['stockCnt'] = $this->getOptionStock($goodsNo, null, $getData['stockFl'], $getData['soldOutFl']);

		if($getData['orderPossible'] == 'n'){
			$getData['err'] = '구매불가상품입니다.';
		}

        // 상품혜택관리 치환코드 생성
//        $getData = $goodsBenefit->goodsDataFrontReplaceCode($getData);

        return $getData;
	}
}