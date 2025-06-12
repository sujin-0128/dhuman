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

namespace Controller\Front\Order;

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
class LayerCouponApplyOrderController extends \Bundle\Controller\Front\Order\LayerCouponApplyOrderController
{
    /**
     * @inheritdoc
     */
	
    


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

		$this->setData('scmChkFl', $scmChkFl);
	
	
	}
	
	

}
