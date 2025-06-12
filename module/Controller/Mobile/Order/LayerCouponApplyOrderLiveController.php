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

namespace Controller\Mobile\Order;

use App;
use Session;
use Request;
use Exception;
use Framework\Utility\ArrayUtils;
use Component\Cart\CartAdmin;

/**
 * 주문 쿠폰 적용
 *
 * @author  su
 */
class LayerCouponApplyOrderLiveController extends \Bundle\Controller\Mobile\Order\LayerCouponApplyOrderLiveController
{
   

	public function post()
    {
		$cartInfo = $this->getData('cartInfo');
		
		$scmNoArray = array();
		foreach ($cartInfo as $key => $value) {
			foreach ($value as $key1 => $value1) {
				foreach ($value1 as $key2 => $value2) {
					$scmNoArray[] = $value2['scmNo'];	  
				}
			}
		}

		//dpx.farmer 공급사 플래그값 y 일시 주문적용 쿠폰 노출
		$scmChkFl = 'y';
		foreach ($scmNoArray as $key => $value) {
			if($value != 1 && $value != 2) {
				$scmChkFl = 'n';
			}
		}



/*
		if(count(array_unique($scmNoArray)) == 1 && array_unique($scmNoArray)[0] == 1){
			$scmChkFl = 'y';
		}else{
			$scmChkFl = 'n';
		}
*/		
		$this->setData('scmChkFl', $scmChkFl);
	


if (\Request::server()->get('REMOTE_ADDR') == "211.254.148.234" || \Request::server()->get('REMOTE_ADDR') == "220.118.145.49" || \Request::server()->get('REMOTE_ADDR') == "121.141.26.134" ){ 

		$coupon = App::load(\Component\Coupon\Coupon::class);
		$couponListArr = $coupon->getMemberCouponList(Session::get('member.memNo'), 'mobile') ; 

		$cartInfo = $this->getData('cartInfo')	;
		
		foreach($cartInfo as $scmNo =>$rows){
			foreach($rows as $deliverySno =>$row){
				foreach($row as $k =>$r){
					$cartData[$r['goodsNo']][$r['optionSno']] = $r; 
					
					if($r['memberCouponNo']){
						$applyGoodsCoupon[$r['memberCouponNo']] = $r['sno']; 
					}
				}
			}
		}
		
		$goodsCouponArrData = $this->getData('goodsCouponArrData') ;
		$convertGoodsCouponArrData = $this->getData('convertGoodsCouponArrData');
		$convertGoodsCouponPriceArrData = $this->getData('convertGoodsCouponPriceArrData');	

		foreach($goodsCouponArrData as $goodsNo =>$row){
			foreach($row as $k =>$r){
				$goodsCoupon[$r['memberCouponNo']] = $convertGoodsCouponArrData[$goodsNo][$k]; 
				
				//jdev.20240429
				$goodsCoupon[$r['memberCouponNo']]['memberCouponAddMileage'] = $r['memberCouponAddMileage']; 				
				
				$goodsCoupon[$r['memberCouponNo']]['couponUseAblePaymentType'] = $r['couponUseAblePaymentType']; 								
				$goodsCoupon[$r['memberCouponNo']]['memberCouponState'] = $r['memberCouponState']; 												
				
				$goodsCoupon[$r['memberCouponNo']]['couponKindTypeCd'] = $r['couponKindType']; 						
		
			}
		}

		foreach($convertGoodsCouponPriceArrData as $goodsNo =>$row){
			foreach($row as $optionSno =>$ro){
				
				foreach($ro['memberCouponAlertMsg'] as $memberCouponNo => $msg){
					$couponList[$memberCouponNo][$goodsNo][$optionSno] = $cartData[$goodsNo][$optionSno]; 
					
					$couponList[$memberCouponNo][$goodsNo][$optionSno]['memberCouponAlertMsg'] = $msg; 
					$couponList[$memberCouponNo][$goodsNo][$optionSno]['memberCouponSalePrice'] = $ro['memberCouponSalePrice'][$memberCouponNo]; 
					$couponList[$memberCouponNo][$goodsNo][$optionSno]['exceptCouponPrice'] = $ro['exceptCouponPrice'][$memberCouponNo]; 			
				}
			}
		}

		foreach($couponListArr['data'] as $r){
			if($r['couponUseType'] =='product'){
				$couponListData[$r['memberCouponNo']] = $goodsCoupon[$r['memberCouponNo']]; 	
				$couponListData[$r['memberCouponNo']]['applyCartSno'] = $applyGoodsCoupon[$r['memberCouponNo']]; 					
				
				$couponListData[$r['memberCouponNo']]['goods'] = $couponList[$r['memberCouponNo']]; 								
			}
		}
		
		$this->setData('couponListData', $couponListData) ; 

}
				
		
	}
}
