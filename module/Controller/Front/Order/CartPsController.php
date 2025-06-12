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

use Component\Cart\Cart;
use Exception;
use Framework\Debug\Exception\AlertBackException;
use Framework\Debug\Exception\AlertOnlyException;
use Framework\Debug\Exception\AlertRedirectException;
use Framework\Debug\Exception\AlertReloadException;
use Framework\Debug\Exception\Except;
use Request;
use Respect\Validation\Validator as v;
use Bundle\Component\Database\DBTableField;
use Session;
use Framework\Utility\StringUtils;

/**
 * 장바구니 처리 페이지
 * @author Shin Donggyu <artherot@godo.co.kr>
 */
class CartPsController extends \Bundle\Controller\Front\Order\CartPsController
{
    /**
     * index
     *
     * @throws Except
     */
	public function pre() {
		$req = Request::post()->toArray();

		if($req[mode]=='orderSelect'){
			$cart = \App::Load(\Component\Cart\Cart::class);
			$dpx = \App::Load(\Component\Designpix\Dpx::class);
			$cartInfo = $cart->getCartGoodsData($req['cartSno']);
			
			foreach($cartInfo as $key => $val){
				foreach($val as $key2 => $val2){
					foreach($val2 as $key3 => $val3){
						if($val3['dpxSelectFl'] == 'y'){
							$goods[$val3['goodsNo']] += $val3['goodsCnt'];
						}
					}
				}
			}

			if($goods) {
				foreach($goods as $goodsNo => $goodsCnt){
					$dpxSelectCnt = $dpx->getDpxSelectCnt($goodsNo);
					if($goodsCnt != $dpxSelectCnt){
						throw new AlertReloadException(__('골라담기 상품 선택 수량 불일치합니다.'), null, null, 'parent');
						exit;
					}
				}
			}
		}


		if($req[mode]=='cartIn'){
			if($req[modeDpx]=='cart'){
				$cart = \App::Load(\Component\Cart\Cart::class);
				$cartInfo = $cart->getCartGoodsData(null, null, null, false, true);	
				foreach ($cartInfo as $key => $val) {// 상품번호 추출
					foreach ($val as $key2 => $val2) {
						foreach ($val2 as $key3 => $val3) {
							if($val3['dpxSelectFl']=='y'){
								$goodsNo[] = $val3['goodsNo'];
							}
						}
					}
				}
				foreach($req[goodsNo] as $k => $v){
					foreach($goodsNo as $key => $val){
						if($v==$val){
							if (Request::isAjax()) {
								$this->json([
									'error' => 1,
									'message' => '구성팩이 이미 있습니다.',
								]);
							}
						}
					}
				}	
			}
		}
		
		if($req[mode]=='goodsCouponOrderApply2'){
			try {
				$cart = \App::Load(\Component\Cart\Cart::class);				
				if($req['cartIdx']) {
					// 상품적용 쿠폰 제거
					sort($req['cartIdx']);
					foreach ($req['cartIdx'] as $delKey => $cartSno) {
						$cart->setMemberCouponDelete($cartSno);
					}
				}
				if($req['cart']) {
					// 상품적용 쿠폰 적용 / 변경
					foreach ($req['cart'] as $cartApplyData =>$cartKey) {
						if ($cartApplyData && $cartKey >0 ) {
							$cart->setMemberCouponApply($cartKey, $cartApplyData);
						}
					}
				}

			} catch (Exception $e) {
				$this->json([
					'error' => 1,
					'message' => $e->getMessage(),
				]);
			}
			exit;
		}
			
		
		
	}
    public function index()
    {
        parent::index();
    }
}
