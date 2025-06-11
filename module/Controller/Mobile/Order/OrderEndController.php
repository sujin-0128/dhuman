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

use Component\CartRemind\CartRemind;
use Component\Mall\Mall;
use DateTime;
use Framework\Debug\Exception\AlertRedirectException;
use Message;
use Globals;
use Session;
use Request;
use Cookie;

/**
 * 주문 완료 페이지
 *
 * @package Bundle\Controller\Mobile\Order
 * @author  Jong-tae Ahn <qnibus@godo.co.kr>
 */
class OrderEndController extends \Bundle\Controller\Mobile\Order\OrderEndController
{
    public function post()
	{

		parent::index();

	   	// 주문번호
        $orderNo = Request::get()->get('orderNo');
		$memInfo = Session::get('member');

		// 주문 상품 정보
        $order = \App::load('\\Component\\Order\\Order');
        $orderInfo = $order->getOrderView(gd_isset($orderNo));

		//튜닝
		$dpx = \App::load('\\Component\\Designpix\\Dpx');
	
		if($orderInfo['orderStatus'] != 'f'){
			$promotionChk = false;	//이벤트 상품 체크
			$selectChk = false;		//select Fl -- 골라담기 체크
			foreach( $orderInfo['goods'] as $key=>$val) {

				if( $val['dpxSelectFl'] == 'y' && $val['selectFl'] == 'y' ){
					$selectChk = true;
				}

				$resultChk = $dpx->dpxPromotionChk($val['goodsNo']);

				if($resultChk == 'y'){
					$promotionChk = true;
				}
			}		
			if( $promotionChk == true ){
				$dpx->dpxPromotionUdt($memInfo);
			}	
			if( $selectChk == true ){
				$dpx->dpxSelectGoods($orderInfo['orderNo']);
			}
			if( $orderInfo['firstSaleFl'] == 'y' ){
				$dpx->dpxFirstSaleChk($orderInfo['memNo']);
			}
		}
		

		## designpix.kkamu 20211117.s


		$present = \App::load('\\Component\\Designpix\\Present');
		
		if($present->cfg['useGiftFl']=='y'){
			$giftFl = $orderInfo['giftFl']; 
			if($giftFl == 'y') {
				$this->getView()->setPageName('order/order_end_gift');
				$this->setData('imageName', $orderInfo['goods'][0]['imageName']);
				$this->setData('imagePath', $orderInfo['goods'][0]['imagePath']);
			}
		}

		## designpix.kkamu 20211117.e

		//AdiSON 202406
		$click_key = Cookie::get('AD_SESSION');
		$goodsNo = Cookie::get('AD_GOODS_NO');
		if ($click_key != '' && $click_key != null && $goodsNo != '' && $goodsNo != null) {
			if ($orderInfo['settleMethod'] == 'v' || $orderInfo['settleMethod'] == 'b') {
				if (!is_object($this->db)) {
					$this->db = \App::load('DB');
				}
				$this->db->query("INSERT INTO `es_adInfo` (`orderNo`, `goodsNo`, `adChannel`, `adKey`, `memo`, `result`, `regDt`) VALUES ('".$orderNo."', '".$goodsNo."', 'AdiSON', '".$click_key."', 'OrderEndM', '', now());");
			}
		}
		
	}
}

