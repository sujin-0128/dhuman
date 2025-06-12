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

namespace Controller\Mobile\Order;

use Component\Subscribe\Toss\TossDpx;
use Component\Subscribe\Subscribe;
use App;
use Component\Agreement\BuyerInformCode;
use Framework\Debug\Exception\Except;
use Framework\Debug\Exception\AlertRedirectException;
use Framework\Utility\ArrayUtils;
use Framework\Utility\ComponentUtils;
use Framework\Utility\NumberUtils;
use Framework\Utility\StringUtils;
use Component\Database\DBTableField;
use Component\Cart\Cart;
use Component\Member\Member;
use Component\Order\Order;
use Component\Mall\Mall;
use Globals;
use Session;
use Request;
use Message;
use Encryptor;

use Component\Wpay\Wpay;

use Exception;
use Component\Cart\CartAdmin;


/**
 * 주문서 작성
 * @author Shin Donggyu <artherot@godo.co.kr>
 */
class OrderController extends \Bundle\Controller\Mobile\Order\OrderController
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
			
			//카드등록 세션 초기화 (dpx-jd-custom);
			Session::set('requestUri','');

            $cartInfo = $cart->getCartGoodsData($cartIdx, null, null, false, true);

			$promotionOrderPossible =  false;
			$promotionGoodsCnt =0; 

			$eventGoodsOrderPossible = false;
			$selectChkFl = false;
			$eventChkFl = '';
			$applyCouponCnt = 0;
			foreach($cartInfo as $scmNo =>$rows){
				foreach($rows as $deliverySno => $row){
					foreach($row as $k => $r){
							if($r['dpxSelectFl'] == 'y'){
								$goods[$r['goodsNo']] += $r['goodsCnt'];
							}
							
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
							$applyCouponCnt++;
						}
						//jdev.20240411.e 자동쿠폰 적용									


					}
				}
			}
			
			$this->setData('applyCouponCnt', $applyCouponCnt); 			

			if($goods){
				foreach($goods as $goodsNo => $goodsCnt){
					$dpxSelectCnt = $dpx->getDpxSelectCnt($goodsNo);
					if($goodsCnt != $dpxSelectCnt){
						throw new AlertRedirectException(__('골라담기 상품 선택 수량 불일치합니다.'), null, null, '../order/cart.php');
					}
				}
			}

			
			//$orderChkCnt = $dpx->proOrderChk($memNo);
			$memberChkFl = $dpx->proMemberChk($memNo);

			if ($promotionGoodsCnt>1 ) {
				throw new AlertRedirectException(__('이벤트상품은 한 개만 구매가능합니다. '), null, null, '../order/cart.php');
			}

			
			if ( strlen($eventChkFl) > 0 && strpos($eventChkFl, 'n') !== false){
				throw new AlertRedirectException(__('첫구매 상품은 가입 후 7일 이내 구매가능합니다. '), null, null, '../order/cart.php');
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


/*		 
if (\Request::server()->get('REMOTE_ADDR') == "211.254.148.2341" || \Request::server()->get('REMOTE_ADDR') == "220.118.145.491" ){
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

			$this->setData('applyCouponCnt', count($jdevAppliedCoupon)); 
}
*/			




		}   catch (Exception $e) {
				//240715 듀먼측 요청으로 안내문구 변경
                throw new AlertRedirectException(__('오류 : 구매 가능 횟수를 초과하여 구매하실 수 없습니다.') . ' - ' . __('해당 상품을 이미 구매하셨거나 이벤트 상품을 구매하신 이력이 확인됩니다. 추가 문의사항이 있으시면 고객센터로 문의해주세요.'), null, null, '../order/cart.php', 'parent'); 
				// 240715 수정전 원본 throw new AlertRedirectException(__('오류') . ' - ' . __('오류가 발생 하였습니다.'), null, null, '../order/cart.php', 'parent');
        }
	}
	
	
	
	
	
	
	
	
    public function index()
    {
		parent::index();

        /* Webnmobile Tuning v 2023-03-06, wpay config load [start] */
        $wpay = App::load(Wpay::class);

        $wpayConf = $wpay->getCfg();

        if ( \Request::getRemoteAddress() === '182.216.219.157' || \Request::getRemoteAddress() === '112.146.205.124' || \Request::getRemoteAddress() === '121.141.26.133' || \Request::getRemoteAddress() === '121.141.26.134' || \Request::getRemoteAddress() === '121.167.104.240') {
            $userWpayInfo = $wpay->myWpayInfo();

            $this->addScript(['wpay/wpay.js']);

            $this->setData('dev', true);
            $this->setData('wpayConf', $wpayConf);
            $this->setData('isWpayUser', $userWpayInfo);
        }

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

		/* designpix 20220304 구매정보 동의 항목추가 */
		$dpx2 = \App::load('\\Component\\Designpix\\Dpx2');
		$agreeinfo = $dpx2->getAgreeInfo($cartInfo);
		$this->setData('agreeinfo', $agreeinfo);
		/* designpix 20220304 구매정보 동의 항목추가 */

		if (Request::server()->get('REMOTE_ADDR') == "220.118.145.49"){ 
			//gd_debug($cartInfo);		
		}

    }






	/* designpix.kkamu 20210923.s 선물하기 */
	public function post() {
		$cartInfo = $this->getData('cartInfo');

		$giftFl = false; 

		foreach($cartInfo as $scmNo =>$rows){
			foreach($rows as $deliverySno => $row){
				foreach($row as $k => $r){
					if($r['directCart']=='y' &&  $r['giftFl']=='y'){
						$giftFl = true;
					}
				}
			}
		}


		if($giftFl){
			$present = \App::load('\\Component\\Designpix\\Present');

			if($present->cfg['useGiftFl'] == 'y'){

				$cardList = $present->getCardList();
				$i=1;
				foreach($cardList as $cards){

					foreach($cards['data'] as $r){
						$tmpCardList[$i] = $r; 
							$i++; 
					}
				}

				$this->setData('cardList', $tmpCardList); 
				$this->setData('giftFl','y');
				$this->getView()->setPageName('order/order_gift');
			}
		}
	/* designpix.kkamu 20210923.e 선물하기 */
	
		//2023.03.21민트웹 추가시작
		if(!$giftFl){
			//if(\Request::getRemoteAddress() == '182.216.219.157' ||  \Request::getRemoteAddress() == "112.146.205.124" ||  \Request::getRemoteAddress() == "121.167.104.240" || \Request::getRemoteAddress() === '121.141.26.133' || \Request::getRemoteAddress() === '121.141.26.134' ){
				//toss
				$subscribe = new \Component\Subscribe\Subscribe();
				
				$getData = $subscribe->subscribeCardList();
				$this->setData("billing_key",$getData);
				


				$this->getView()->setPageName('order/order_new');
	
			//}
		}
		//2023.03.21민트웹 추가시작
		
		

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


		//dpx_jd_240513.s 간편카드등록

		
		$tossDpx = new TossDpx() ; 
		$payReq = $tossDpx->authData(); 

		$this->setData("data", $payReq); 

		$subscribe = new Subscribe();
		$getData = $subscribe->subscribeCardList();

		$this->setData('card',$getData);

		//dpx_jd_240513.s 간편카드등록

	}

}
