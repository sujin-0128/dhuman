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
namespace Controller\Front\Goods;

use Component\Board\Board;
use Component\Board\BoardBuildQuery;
use Component\Board\BoardList;
use Component\Board\BoardWrite;
use Component\Naver\NaverPay;
use Component\Page\Page;
use Component\Promotion\SocialShare;
use Component\Mall\Mall;
use Framework\Debug\Exception\AlertRedirectException;
use Framework\Debug\Exception\Except;
use Framework\Debug\Exception\AlertBackException;
use Component\Validator\Validator;
use Message;
use Globals;
use Request;
use Logger;
use Session;
use Exception;
use Endroid\QrCode\QrCode as EndroidQrCode;
use SocialLinks\Page as SocialLink;
use FileHandler;
use app;
use Cookie;

class GoodsViewController extends \Bundle\Controller\Front\Goods\GoodsViewController
{

    /**
     * 상품 상세 페이지
     *
     * @author    artherot
     * @version   1.0
     * @since     1.0
     * @copyright Copyright (c), Godosoft
     * @throws Except
     */
    public function index()
    {
		parent::index();
       
		//AdiSON 202406
		$req = array_merge(Request::get()->toArray(), Request::post()->toArray());
		$click_key = $req['click_key'];
		$goodsNo = $req['goodsNo'];
		if ($click_key != "" && $goodsNo != "") {
			Cookie::set('AD_SESSION',$click_key, (86400 * 365), '/', true, true); 
			Cookie::set('AD_GOODS_NO',$goodsNo, (86400 * 365), '/', true, true); 
		}

		$goodsView = $this->getData('goodsView');


		$exSword = explode(",",$goodsView['goodsSearchWord']);

		//gd_debug($exSword);

		foreach( $exSword as $key=>$val) {

			if( $val ){
				$val = trim($val);
			
				$exGoodsSearchWord[$key] = "#".$val;
			}
		}

		$goodsView['exGoodsSearchWord'] = $exGoodsSearchWord;

		if($goodsView['dpxSelectFl']=='y'){
			$this->getView()->setPageName('goods/goods_view_dpx2');
		}
		
		$this->setData('goodsView', $goodsView);
		if( Request::server()->get('REMOTE_ADDR') == "220.118.145.49" || Request::server()->get('REMOTE_ADDR') == "175.198.43.215"  || Request::server()->get('REMOTE_ADDR')=="182.216.219.157"){
			if($goodsView['subscribeGoodsFl']=='y'){
				$periodDc = gd_policy('dpx.subscribe.periodDc');
				$prepayDc = gd_policy('dpx.subscribe.prepayDc');
			
				$this->setData('periodDc', $periodDc);
				$this->setData('prepayDc', $prepayDc);
				$this->getView()->setPageName('goods/goods_view_subscribe');
			}
		}
		
		if (Request::server()->get('REMOTE_ADDR') == "220.118.145.49" || Request::server()->get('REMOTE_ADDR') == "175.198.43.215" ){ 
			//$this->getView()->setPageName('goods/goods_view_subscribe');
		}

		$dpx = \App::load('\\Component\\Designpix\\Dpx');
		$eventFl = $dpx->eventKindChk($goodsView);
		$couponDownFl = $dpx->couponDownChk($goodsView);
		$this->setData('couponDownChk', $couponDownFl);

		$this->setData('nowTime', date('Y-m-d H:i:s'));
	
		if($goodsView['dpxEventFl']=='y'){
			$this->getView()->setPageName('goods/goods_view_dpxFirst');
		}
		if($eventFl=='day' && $goodsView['dpxEventDayFl']=='y'){
			$this->getView()->setPageName('goods/goods_view_dpxDay');
		}

    }

}
