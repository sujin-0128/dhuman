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
namespace Controller\Admin\Order;

use App;

use Exception;
use Request;
use Session;

class IdPurchaseController extends \Bundle\Controller\Admin\Controller
{
    public function index()
    {



		$get = Request::post()->toArray();
	
		
		$dpx = App::load('\\Component\\Designpix\\Dpx');

		if( $get['mode'] =='idPurchaes'){
			$reulst = $dpx->deleteEventIdGoods($get);
		}
		else if($get['mode'] =='promotion'){
			$reulst = $dpx->deleteEventFl($get['memNo']);
		}
		else if($get['mode'] =='dpxEvent'){
			$result = $dpx->dpxEventReset($get['memNo']);
		}

		$this->json(
					[
						'result' => $result,
					]
		);
		 		exit;
	}
}
