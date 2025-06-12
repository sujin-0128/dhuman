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

namespace Controller\Mobile\Goods;

use App;
use Framework\Utility\StringUtils;
use Session;
use Request;
use Exception;
use Framework\Utility\ArrayUtils;
use Framework\Utility\SkinUtils;
use Globals;

/**
 * Class LayerDeliveryAddress
 *
 * @package Bundle\Controller\Front\Order
 * @author  su
 */
class LayerOptionController extends \Bundle\Controller\Mobile\Goods\LayerOptionController
{
    public function index()
    {
        parent::index();
    }

	public function post(){

		$goodsView =  $this->getData('goodsView');	
		//gd_debug($goodsView);
		if($goodsView['dpxSelectFl']=='y'){
			$this->getView()->setPageName('goods/layer_option_dpx');
		}

		if( Request::server()->get('REMOTE_ADDR') == "220.118.145.49"){
			if($goodsView['subscribeGoodsFl']=='y'){
				$this->getView()->setPageName('subscribe/layer_option');
				$periodDc = gd_policy('dpx.subscribe.periodDc');
				$prepayDc = gd_policy('dpx.subscribe.prepayDc');
	
				$this->setData('periodDc', $periodDc);
				$this->setData('prepayDc', $prepayDc);
			}
		}



		## designpix.kkamu 20211123.s
		$present = \App::load('\\Component\\Designpix\\Present');
		
		if($present->cfg['useGiftFl']=='y'){
			if($goodsView['useGiftFl']=='y'){
				$this->setData('giftFl', $goodsView['useGiftFl']) ; 
			}
		}

		$ip = trim(Request::server()->get('REMOTE_ADDR'));
		if ($ip == '220.118.145.49') {
			// var_dump($goodsView);
			// --- 모듈 호출
			$dpx = \App::load('\\Component\\Designpix\\Dpx');

			// 결합 할인 상품인지 확인
			$getData = $dpx->checkAllowNoBundleSale($goodsView['goodsNo']);
			$layerBundleBuy = '';
			$layerBundleCart = '';
			if(count($getData) > 0){

				$layerBundleBuy = '../goods/layer_bundle_main_buy.php';
				if($getData[0]['bundleType'] == 'main'){
						$layerBundleCart = '../goods/layer_bundle_main_cart.php';
				}else{
					$layerBundleCart = '../goods/layer_bundle_discout_cart.php';
				}

				$this->setData('bundleType', $getData[0]['bundleType']);                    // 상품 구분(main : 메인상품(판매함), discount:할인상품(판매안함))
				$this->setData('showNoBundlePopup', $getData[0]['showNoBundlePopup']);      // 상품 바로구매 시 결합상품 안내 레이어팝업 노출 여부(y:노출, n:비노출), 메인상품만 해당
				$this->setData('preCartBundlePopup', $getData[0]['preCartBundlePopup']);    // 상품 장바구니 시 결합상품 안내 레이어팝업 노출 여부(y:노출, n:비노출), 메인, 할인 둘다 해당 
				$this->setData('layerBundleBuy', $layerBundleBuy);                          // 바로구매시 안내 레이어 url
				$this->setData('layerBundleCart', $layerBundleCart);                        // 장바구니시 안내 레이어 url

			}
			
		}

		
	}
}
