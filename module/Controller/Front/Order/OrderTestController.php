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
 * @link      http://www.godo.co.kr
 */

namespace Controller\Front\Order;

use App;
use Component\Cart\Cart;
use Component\Database\DBTableField;
use Component\Order\Order;
use Component\Wpay\Wpay;

use Framework\Debug\Exception\AlertRedirectException;
use Request;
use Session;

use Exception;
use Framework\Utility\ArrayUtils;
use Component\Cart\CartAdmin;

/**
 * 주문서 작성
 * @author Shin Donggyu <artherot@godo.co.kr>
 */
class OrderTestController extends \Bundle\Controller\Front\Order\OrderController
{
    /**
     * index
     *
     */

	public function pre() {

        try {

            // 모듈 설정
            $cart = new Cart();

			$dpx = \App::load('\\Component\\Designpix\\Dpx');
			$memNo = Session::get('member.memNo');
            // 선택된 상품만 주문서 상품으로
            if (Request::get()->has('cartIdx')) {
                $cartIdx = $cart->getOrderSelect(Request::get()->get('cartIdx'));
            }

            $cartInfo = $cart->getCartGoodsData($cartIdx, null, null, false, true);

			$promotionOrderPossible =  false;
			$promotionGoodsCnt =0; 

			$eventGoodsOrderPossible = false;
			$selectChkFl = false;
			$eventChkFl = '';
			foreach($cartInfo as $scmNo =>$rows){
				foreach($rows as $deliverySno => $row){
					foreach($row as $k => $r){
							
							if($r['dpxPromotionFl']=='y'){
								if( $row[$k]['goodsNo'] != $row[$k+1]['goodsNo'] ){
									//gd_Debug($row[$k]['goodsNo']);
									
									$promotionGoodsCnt++; 
									
								}
								$eventGoodsOrderPossible = true;

							}else if($r['dpxPromotionFl']=='n'){
								
								$promotionOrderPossible = true;
							}
							else {
								$promotionOrderPossible = false;
							}
							
							if($r['dpxEventFl']=='y'){
								$eventChkFl .= $dpx->eventPossibleChk($memNo);
													
							}
						$memNo = $r['memNo'];


						//골라담기 상품 중복구매 불가 튜닝 2021-04-28
						$selChk = $dpx->dpxSelectInfo($memNo);
						$selectChk = explode("|",$selChk);

						
						
						foreach( $selectChk as $key=>$val) {
							if( $r['goodsNo'] == $val ){
								$selectChkFl = true;
							}
						}
						
						//jdev.20240411.s 자동쿠폰 적용
						$jdevCart[$r['goodsNo']]['memberCouponNo'] = $r['memberCouponNo'];
						$jdevCart[$r['goodsNo']]['price'] = $r['price']['goodsPriceSum'] + $r['price']['optionPriceSum']  + $r['price']['optionTextPriceSum']+ $r['price']['addGoodsPriceSum'];						
						if(empty($r['memberCouponNo'])){
							$tmpJdevGoods = [];
							$tmpJdevGoods['goodsNo'] = $r['goodsNo'];							
							$tmpJdevGoods['cartSno'] = $r['sno'];
							$tmpJdevGoods['optionSno'] = $r['optionSno'];							  
							$jdevCartPrice[$jdevCart[$r['goodsNo']]['price']] = $tmpJdevGoods; 
						}else{
							$jdevAppliedCoupon[] = $r['memberCouponNo'];
						}
						//jdev.20240411.e 자동쿠폰 적용						
						
					}
				}
			}
		
			//$orderChkCnt = $dpx->proOrderChk($memNo);
			$memberChkFl = $dpx->proMemberChk($memNo);

			if(	strlen($eventChkFl) > 0 && strpos($eventChkFl, 'n') !== false){
				throw new AlertRedirectException(__('첫구매 상품은 가입 후 7일 이내 구매가능합니다. '), null, null, '../order/cart.php');
			}

			if ($promotionGoodsCnt>1 ) {
				throw new AlertRedirectException(__('이벤트상품은 한 개만 구매가능합니다. '), null, null, '../order/cart.php');
			}

			if ($memberChkFl == 'y' && $eventGoodsOrderPossible == true ) {
				 throw new AlertRedirectException(__('이벤트상품은 한 번만 구매가능합니다. '), null, null, '../order/cart.php');
			}

			if ($selectChkFl ) {
				throw new AlertRedirectException(__('골라담기 상품은 한 번만 구매가능합니다. '), null, null, '../order/cart.php');
			}

            // 주문불가한 경우 진행 중지
            /*
			if ($promotionOrderPossible === false ) {
                   throw new AlertRedirectException(__('프로모션상품은 단독구매가 불가합니다.  장바구니에서 확인 후 다시 주문해주세요.'), null, null, '../order/cart.php');
            }
			*/
			
			


		 
		 
if (\Request::server()->get('REMOTE_ADDR') == "211.254.148.234" || \Request::server()->get('REMOTE_ADDR') == "220.118.145.49" ){
            //jdev.20240411.s 자동쿠폰 적용
            if(Session::has('member')) {
                $coupon = App::load(\Component\Coupon\Coupon::class);
                $couponConfig = gd_policy('coupon.config');

				//jdev.20240411.s 상품쿠폰 미지정된 상품에 한하여 금액 높은순으로 쿠폰 할인 높은순으로 자동 적용
                if($couponConfig['productCouponChangeLimitType'] == 'n') { // 상품쿠폰 주문서페이지 변경 제한안함일 때
                    $goodsCouponData = $coupon->getProductCouponChangeData('layer', $cartInfo, $goodsPriceArr);
					krsort($jdevCartPrice);

					$convertGoodsCouponPriceArrData = $goodsCouponData['convertGoodsCouponPriceArrData']; 
					foreach($jdevCartPrice as $r){
						$tmpArr =	$convertGoodsCouponPriceArrData[$r['goodsNo']][$r['optionSno']]; 
						
						foreach($tmpArr['memberCouponAlertMsg'] as $couponNo =>$msg){
							
							if(empty($msg)){
								if( !in_array($couponNo, $jdevAppliedCoupon ) && $tmpArr['memberCouponSalePrice'][$couponNo]> $applyCart[$r['cartSno']]['memberCouponPrice']){
									$applyCart[$r['cartSno']]['couponNo'] = $couponNo; 
									$applyCart[$r['cartSno']]['exceptCouponPrice'] = $tmpArr['exceptCouponPrice'][$couponNo]; 					
									$applyCart[$r['cartSno']]['memberCouponPrice'] = $tmpArr['memberCouponSalePrice'][$couponNo]; 
									$jdevAppliedCoupon[] = $couponNo; 
								}
							}
						}
					}

					if(count($applyCart)>0){
						$jdev = \App::load('\\Component\\Jdevlab\\Jdev');					
						$jdev->setCartApplyCoupon($applyCart); 
					}
                }
				//jdev.20240411.e 상품쿠폰 미지정된 상품에 한하여 금액 높은순으로 쿠폰 할인 높은순으로 자동 적용

				//jdev.20240410.s 자동 주문/배송쿠폰 적용처리 
                $goodsPriceArr = [
                    'goodsPriceSum'=>$cart->totalPrice['goodsPrice'],
                    'optionPriceSum'=>$cart->totalPrice['optionPrice'],
                    'optionTextPriceSum'=>$cart->totalPrice['optionTextPrice'],
                    'addGoodsPriceSum'=>$cart->totalPrice['addGoodsPrice'],
                ];
				
                // 해당 상품의 사용가능한 회원쿠폰 리스트
                $memberCouponArrData = $coupon->getOrderMemberCouponList(Session::get('member.memNo'), $cart->payLimit);
		
                if(is_array($memberCouponArrData['order'])){
                    $memberCouponNoArr['order'] = array_column($memberCouponArrData['order'],'memberCouponNo');
                    if ($memberCouponNoArr['order']) {
                        $memberCouponNoString['order'] = implode(INT_DIVISION, $memberCouponNoArr['order']);
                        // 해당 상품의 사용가능한 회원쿠폰 리스트를 보기용으로 변환
                        $convertMemberCouponArrData['order'] = $coupon->convertCouponArrData($memberCouponArrData['order']);
                        // 해당 상품의 사용가능한 회원쿠폰의 정율도 정액으로 계산된 금액
                        $convertMemberCouponPriceArrData['order'] = $coupon->getMemberCouponPrice($goodsPriceArr, $memberCouponNoString['order']);
                    }
                }
				
	           if(is_array($memberCouponArrData['delivery'])){
                    $memberCouponNoArr['delivery'] = array_column($memberCouponArrData['delivery'],'memberCouponNo');
                    if ($memberCouponNoArr['delivery']) {
                        $memberCouponNoString['delivery'] = implode(INT_DIVISION, $memberCouponNoArr['delivery']);
                        // 해당 상품의 사용가능한 회원쿠폰 리스트를 보기용으로 변환
                        $convertMemberCouponArrData['delivery'] = $coupon->convertCouponArrData($memberCouponArrData['delivery']);
                        // 해당 상품의 사용가능한 회원쿠폰의 정율도 정액으로 계산된 금액
                        $convertMemberCouponPriceArrData['delivery'] = $coupon->getMemberCouponPrice($goodsPriceArr, $memberCouponNoString['delivery']);
                    }
                }

				$couponOrder = $convertMemberCouponPriceArrData['order']['memberCouponSalePrice'];
				$couponDelivery = $convertMemberCouponPriceArrData['delivery']['memberCouponDeliveryPrice'];
				$totalCouponOrderDcPrice = $totalCouponOrderPrice =	$totalCouponDeliveryDcPrice = $totalCouponDeliveryPrice = $totalCouponOrderMileage =0;

				//주문쿠폰
				$tmpOrderPrice = 0; 
				foreach($couponOrder as $couponNo =>$couponPrice){
					$msg = $convertMemberCouponPriceArrData['order']['memberCouponAlertMsg'][$couponNo]; 
					if(empty($msg) && $couponPrice >$tmpOrderPrice ){
						$couponOrderApply['couponNo'] = $couponNo;
						$couponOrderApply['couponPrice'] = $couponPrice; 
					}
					$tmpOrderPrice = $couponPrice ; 	
				}
				if($couponOrderApply['couponNo']){
					$couponApplyOrderNoArr[] = $couponOrderApply['couponNo'];
					$totalCouponOrderDcPrice = $totalCouponOrderPrice = 	$couponOrderApply['couponPrice']	;	
				}

				//배송비쿠폰
				$tmpDeliveryPrice = 0; 
				foreach($couponDelivery as $couponNo =>$couponPrice){
					$msg = $convertMemberCouponPriceArrData['delivery']['memberCouponAlertMsg'][$couponNo]; 					
					if(empty($msg) && $couponPrice >$tmpDeliveryPrice){
						$couponDeliveryApply['couponNo'] = $couponNo;
						$couponDeliveryApply['couponPrice'] = $couponPrice; 
					}
					$tmpDeliveryPrice = $couponPrice ; 	
				}		
				if($couponDeliveryApply['couponNo']){
					$couponApplyOrderNoArr[] = $couponDeliveryApply['couponNo'];
					$totalCouponDeliveryDcPrice = $totalCouponDeliveryPrice = 	$couponDeliveryApply['couponPrice']	;	
				}

				if($couponApplyOrderNoArr){
					$couponApplyOrderNo =implode(INT_DIVISION, $couponApplyOrderNoArr);
				}

				if($couponApplyOrderNo){
					$this->setData('couponApplyOrderNo', $couponApplyOrderNo);
					$this->setData('totalCouponOrderDcPrice', $totalCouponOrderDcPrice );
					$this->setData('totalCouponOrderPrice', $totalCouponOrderPrice );
					$this->setData('totalCouponOrderMileage', $totalCouponOrderMileage); 
					$this->setData('totalCouponDeliveryDcPrice', $totalCouponDeliveryDcPrice); 
					$this->setData('totalCouponDeliveryPrice', $totalCouponDeliveryPrice );
				}
				//jdev.20240410.e 자동 주문/배송쿠폰 적용처리 
            } 		 
            //jdev.20240411.e 자동쿠폰 적용		 			
}
			
			
			

		}   catch (Exception $e) {
                throw new AlertRedirectException(__('오류') . ' - ' . __('오류가 발생 하였습니다.'), null, null, '../order/cart.php', 'parent');
        }
	}
	
	
	
	
	
	
	
     public function index()
    {
		parent::index();
        /* Webnmobile Tuning v 2023-03-06, wpay config load [start] */
        $wpay = App::load(Wpay::class);

        $wpayConf = $wpay->getCfg();

        if(\Request::getRemoteAddress() == '182.216.219.157' || \Request::getRemoteAddress() === '112.146.205.124' || \Request::getRemoteAddress() === '121.141.26.133' || \Request::getRemoteAddress() === '121.141.26.134' || \Request::getRemoteAddress() === '121.167.104.240') {

            /**
             * 121.141.26.133
            121.141.26.134
            121.167.104.240
             */

            $userWpayInfo = $wpay->myWpayInfo();
            $this->setData('isWpayUser', $userWpayInfo);
            $this->setData('dev', true);
            $this->setData('wpayConf', $wpayConf);
            $this->addScript(['wpay/wpay.js']);
			
			

        }
		
		//if(\Request::getRemoteAddress() == '182.216.219.157' ||  \Request::getRemoteAddress() == "112.146.205.124" ||  \Request::getRemoteAddress() == "121.167.104.240" || \Request::getRemoteAddress() === '121.141.26.133' || \Request::getRemoteAddress() === '121.141.26.134' ){
		//if(\Request::getRemoteAddress() == '182.216.219.157'){
			    //toss
			$subscribe = new \Component\Subscribe\Subscribe();
			
			$getData = $subscribe->subscribeCardList();
			
			if(\Request::getRemoteAddress() == '182.216.219.157'){
				//gd_debug($getData);
			}
			$this->setData("billing_key",$getData);
			
			$this->getView()->setPageName('order/order_new');
		//}
		
		

        /* Webnmobile Tuning v 2023-03-06, wpay config load [end] */

		$cartInfo = $this->getData('cartInfo');
		$coupouDpxDel = 0;
		$coupouDpxDelFl = 'n';	
		foreach ($cartInfo as $key => $val){
			foreach($val as $key2 => $val2){
				foreach($val2 as $key3){
					if($key3['delcoupon']=='y' || $key3['delcouponDay']=='y'){
						$coupouDpxDel++;
					}
				}
			}
		}

		if($coupouDpxDel>0){
			$coupouDpxDelFl = 'y';	
		}
		$this->setData('coupouDpxDel',$coupouDpxDelFl);

		
		if (\Cookie::has('LPINFO') === true){
			$this->setData('lpinfo',\Cookie::get('LPINFO'));
		}
		
		$this->setData('user_agent', Request::server()->get('HTTP_USER_AGENT'));
		
		
		

		//jdev.20240408.s 일반결제 정렬
		$tmpSettle = $this->getData('settle') ; 
		$settle['general']['pc'] =  $tmpSettle['general']['pc']; 
		$settle['general']['pn'] =  $tmpSettle['general']['pn']; 
		$settle['general']['pk'] =  $tmpSettle['general']['pk']; 		
		$settle['general']['gb'] =  $tmpSettle['general']['gb']; 		
		$settle['general']['pv'] =  $tmpSettle['general']['pv']; 				

		if($tmpSettle['payco']['fc']['useFl'] =='y'){
			$tmpSettle['payco']['fc']['name'] = 'PAYCO';		
			
			$settle['general']['fc'] = $tmpSettle['payco']['fc'];
		}
		
		$this->setData('payco',  null);	
		$this->setData('settle', $settle); 
		//jdev.20240408.e 일반결제 정렬
		
		
		
		
		
		
		
		
		
		
		
		
		
		
		
		
		
		
		
		
		
		
		
		
		
		
		
		
		
		
		
		
		
		
		
		
		
		
		
		


if (\Request::server()->get('REMOTE_ADDR') == "211.254.148.234" ){ 
	
                $post = Request::post()->toArray();
                $cart = new CartAdmin(Session::get('member.memNo'), true);
                $coupon = App::load(\Component\Coupon\Coupon::class);

                // 장바구니(주문)의 상품 데이터
                $cartInfo = $cart->getCartGoodsData($post['cartSno']);
                $this->setData('cartInfo', $cartInfo);
                $couponConfig = gd_policy('coupon.config');
                $this->setData('productCouponChangeLimitType', $couponConfig['productCouponChangeLimitType']); //상품쿠폰이 주문서 수정 제한 여부
                // 사은품 증정 정책
                $giftConf = gd_policy('goods.gift');
                $this->setData('giftConf', $giftConf);

                $goodsPriceArr = [
                    'goodsPriceSum'=>$cart->totalPrice['goodsPrice'],
                    'optionPriceSum'=>$cart->totalPrice['optionPrice'],
                    'optionTextPriceSum'=>$cart->totalPrice['optionTextPrice'],
                    'addGoodsPriceSum'=>$cart->totalPrice['addGoodsPrice'],
                ];
                if($post['couponApplyOrderNo']) {
                    // 장바구니에 사용된 회원쿠폰 리스트
                    $cartCouponNoArr = explode(INT_DIVISION,$post['couponApplyOrderNo']);
                    foreach($cartCouponNoArr as $cartCouponKey => $cartCouponVal) {
                        if ($cartCouponVal) {
                            $cartCouponArrData[$cartCouponKey] = $coupon->getMemberCouponInfo($cartCouponVal);
                        }
                    }
                    // 장바구니에 사용된 회원쿠폰 리스트를 보기용으로 변환
                    $convertCartCouponArrData = $coupon->convertCouponArrData($cartCouponArrData);
                    // 장바구니에 사용된 회원쿠폰의 정율도 정액으로 계산된 금액
                    $convertCartCouponPriceArrData = $coupon->getMemberCouponPrice($goodsPriceArr, $post['couponApplyOrderNo']);
                    $this->setData('cartCouponArrData', $cartCouponArrData);
                    $this->setData('convertCartCouponArrData', $convertCartCouponArrData);
                    $this->setData('convertCartCouponPriceArrData', $convertCartCouponPriceArrData);
                }

                // 해당 상품의 사용가능한 회원쿠폰 리스트
                $memberCouponArrData = $coupon->getOrderMemberCouponList(Session::get('member.memNo'), $cart->payLimit);
                if(is_array($memberCouponArrData['order'])){
                    $memberCouponNoArr['order'] = array_column($memberCouponArrData['order'],'memberCouponNo');
                    if ($memberCouponNoArr['order']) {
                        $memberCouponNoString['order'] = implode(INT_DIVISION, $memberCouponNoArr['order']);
                        // 해당 상품의 사용가능한 회원쿠폰 리스트를 보기용으로 변환
                        $convertMemberCouponArrData['order'] = $coupon->convertCouponArrData($memberCouponArrData['order']);
                        // 해당 상품의 사용가능한 회원쿠폰의 정율도 정액으로 계산된 금액
                        $convertMemberCouponPriceArrData['order'] = $coupon->getMemberCouponPrice($goodsPriceArr, $memberCouponNoString['order']);
                    }
                }
                if(is_array($memberCouponArrData['delivery'])){
                    $memberCouponNoArr['delivery'] = array_column($memberCouponArrData['delivery'],'memberCouponNo');
                    if ($memberCouponNoArr['delivery']) {
                        $memberCouponNoString['delivery'] = implode(INT_DIVISION, $memberCouponNoArr['delivery']);
                        // 해당 상품의 사용가능한 회원쿠폰 리스트를 보기용으로 변환
                        $convertMemberCouponArrData['delivery'] = $coupon->convertCouponArrData($memberCouponArrData['delivery']);
                        // 해당 상품의 사용가능한 회원쿠폰의 정율도 정액으로 계산된 금액
                        $convertMemberCouponPriceArrData['delivery'] = $coupon->getMemberCouponPrice($goodsPriceArr, $memberCouponNoString['delivery']);
                    }
                }

                $this->setData('memberCouponArrData', $memberCouponArrData);
                $this->setData('convertMemberCouponArrData', $convertMemberCouponArrData);
                $this->setData('convertMemberCouponPriceArrData', $convertMemberCouponPriceArrData);

                // 해당 상품의 사용가능한 상품쿠폰 리스트
                if($couponConfig['productCouponChangeLimitType'] == 'n') { // 상품쿠폰 주문서페이지 변경 제한안함일 때
                    $goodsCouponData = $coupon->getProductCouponChangeData('layer', $cartInfo, $goodsPriceArr);
                    $this->setData('cartCouponNoArr', $goodsCouponData['cartCouponNoArr']); // cart 쿠폰 데이터
                    foreach($goodsCouponData['cartCouponNoArr'] as $cNoKey => $cNoVal) { // 적용된 상품 cart 쿠폰배열
                        foreach($cNoVal as $cNoSnoVal) {
                            if($cNoSnoVal) {
                                $goodsCouponSnoString[] = $cNoKey . INT_DIVISION . $cNoSnoVal;
                            }
                        }
                    }
                    $this->setData('goodsCouponSnoArr', $goodsCouponData['goodsCouponSnoArr']);  // 카트 일련번호 매칭
                    $this->setData('couponApplyGoodsData', $goodsCouponData['couponApplyGoodsData']);  // 상품에 적용된 상품쿠폰 데이터
                    $this->setData('cartCouponNoDivisionArr', implode(STR_DIVISION, $goodsCouponSnoString)); // cart 쿠폰 데이터 매칭 배열 string 변환

                    // 장바구니에서 다른 상품에 이미 적용된 혜택금액을 가져온다
                    unset($cart);
                    $cart = \App::load('\\Component\\Cart\\Cart');
                    $cartInfo = $cart->getCartGoodsData();
                    foreach ($cartInfo as $key => $value) {
                        foreach ($value as $key1 => $value1) {
                            foreach ($value1 as $key2 => $value2) {
                                if ($value2['memberCouponNo']) {
                                    foreach ($goodsCouponData['goodsMemberCouponNoArr'] as $goodsNo => $goodsCouponArrData) {
                                        foreach ($goodsCouponArrData as $key => $memberCouponNo) {
                                            $cartCouponNoArr = explode(INT_DIVISION, $value2['memberCouponNo']);
                                            if (in_array($memberCouponNo, $cartCouponNoArr)) {
                                                $memberCouponData = $coupon->getMemberCouponInfo($memberCouponNo, 'c.couponNo, mc.memberCouponState');
                                                $goodsCouponData['goodsCouponArrData'][$goodsNo][$key]['couponGoodsDcPrice'] = ($value2['coupon'][$memberCouponNo]['couponKindType'] == 'sale') ? $value2['coupon'][$memberCouponNo]['couponGoodsDcPrice'] : $value2['coupon'][$memberCouponNo]['couponGoodsMileage'];
                                                $goodsCouponData['goodsCouponArrData'][$goodsNo][$key]['goodsNo'] = $value2['goodsNo'].'_'.$value2['optionSno'];
                                                $goodsCouponData['goodsCouponArrData'][$goodsNo][$key]['couponNo'] = $memberCouponData['couponNo'];
                                                $goodsCouponData['goodsCouponArrData'][$goodsNo][$key]['memberCouponState'] = $memberCouponData['memberCouponState'];
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                    $this->setData('goodsCouponArrData', $goodsCouponData['goodsCouponArrData']); // 쿠폰 DB 데이터
                    $this->setData('convertGoodsCouponArrData', $goodsCouponData['convertGoodsCouponArrData']); // 변환 쿠폰 데이터
                    $this->setData('convertGoodsCouponPriceArrData', $goodsCouponData['convertGoodsCouponPriceArrData']); // 쿠폰 가격 데이터

                    // 주문서 작성페이지의 기존 할인데이터를 가져온다
                    $originalOrderData = array();
                    $originalOrderData['couponApplyGoodsData'] = $goodsCouponData['couponApplyGoodsData'];
                    $originalOrderData['couponApplyOrderNoArr'] = explode(INT_DIVISION, $post['couponApplyOrderNo']);
                    $originalOrderData['totalCouponOrderDcPrice'] = $post['totalCouponOrderDcPrice'];
                    $originalOrderData['totalCouponOrderPrice'] = $post['totalCouponOrderPrice'];
                    $originalOrderData['totalCouponOrderMileage'] = $post['totalCouponOrderMileage'];
                    $originalOrderData['totalCouponDeliveryDcPrice'] = $post['totalCouponDeliveryDcPrice'];
                    $originalOrderData['totalCouponDeliveryPrice'] = $post['totalCouponDeliveryPrice'];

                    $this->setData('originalOrderData', $originalOrderData);
                }
					
					
					
					
					
					
					
					
					
					
					
					
					
					
					
					
					
					
					
					
					
					
$goodsCouponArrData = $this->getData('goodsCouponArrData')			;

$convertGoodsCouponArrData = $this->getData('convertGoodsCouponArrData');
$convertGoodsCouponPriceArrData = $this->getData('convertGoodsCouponPriceArrData');	

//gd_debug($goodsCouponArrData);
//gd_debug($convertGoodsCouponArrData);

gd_debug($convertGoodsCouponPriceArrData );
//exit;


foreach($convertGoodsCouponPriceArrData as $goodsNo =>$row){
	
	
	foreach($row as $optionSno =>$ro){
	
		gd_Debug($optionSno);
		
		foreach($ro['memberCouponAlertMsg'] as $memberCouponNo => $msg){
			
			$couponList[$memberCouponNo][$goodsNo][$optionSno]['memberCouponAlertMsg'] = $msg; 
			$couponList[$memberCouponNo][$goodsNo][$optionSno]['memberCouponSalePrice'] = $ro['memberCouponSalePrice'][$memberCouponNo]; 
			$couponList[$memberCouponNo][$goodsNo][$optionSno]['exceptCouponPrice'] = $ro['exceptCouponPrice'][$memberCouponNo]; 			
			
		}
	}
}

gd_debug($couponList);
exit;








				if(Session::get('member.memNo')){
	                $coupon = App::load(\Component\Coupon\Coupon::class);
					$couponListArr = $coupon->getMemberCouponList(Session::get('member.memNo'), 'mobile') ; 
					

					
					foreach($couponListArr['data'] as $r){
						
						$arr =[];
						$arr['goodsNo'] = $r['goodsNo']; 
						$arr['couponNm'] = $r['couponNm']; 						
							
						$couponList[$r['couponUseType']][$r['memberCouponNo']] = $arr; 	
					}

					foreach($couponList['product'] as $memberCouponNo =>$row){
						
						foreach($goodsCouponArrData as $goodsNo =>$ro){
							
							
gd_debug($ro);							
							foreach($ro as $k => $r){
							
								if($memberCouponNo == $r['memberCouponNo']){

									$couponList['product'][$memberCouponNo]['info'] = $convertGoodsCouponArrData['goodsNo'][$k];				

gd_Debug($r['goodsNo']);
									if($r['goodsNo']){									
										
										$goodsNoArr = explode('_', $r['goodsNo']);
										$optionSno = $goodsNoArr[1]; 
										
										$memberCouponAlertMsg = $convertGoodsCouponPriceArrData[$goodsNo][$optionSno]['memberCouponAlertMsg'][$memberCouponNo]; 
										$memberCouponSalePrice = $convertGoodsCouponPriceArrData[$goodsNo][$optionSno]['memberCouponSalePrice'][$memberCouponNo]; 
										$exceptCouponPrice = $convertGoodsCouponPriceArrData[$goodsNo][$optionSno]['exceptCouponPrice'][$memberCouponNo]; 	

										$r['memberCouponAlertMsg'] = $memberCouponAlertMsg ;		
										$r['memberCouponSalePrice'] = $memberCouponSalePrice ;		
										$r['exceptCouponPrice'] = $exceptCouponPrice ;							
										
										$couponList['product'][$memberCouponNo]['goods'][] = $goodsNo;											
									}

		
									
								}
							}

						}
						
					}
					
			gd_debug($couponList['product']);
			//			exit;

//gd_debug($goodsCouponArrData);
//exit;













			foreach($goodsCouponArrData as $goodsNo => $row){
					foreach($row as $k =>$r){
						
						$memberCouponNo[$r['memberCouponNo']]['info'] = 	$convertGoodsCouponArrData[$goodsNo][$k]; 
						$r['extraCouponPriceArr'] = 	$convertGoodsCouponPriceArrData[$goodsNo][$k]; 			
						$memberCouponNo[$r['memberCouponNo']]['data'][][$r['goodsNo']] = $r; 			
					}
			}

	}	
}
				
		
				
		
		
		
		
		exit;
    }

}
