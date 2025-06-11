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
	}
}
