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

use Bundle\Component\Board\BoardAdmin;
use Bundle\Component\Board\BoardList;
use Component\Board\Board;
use Component\Board\BoardUtil;
use Component\Naver\NaverPay;
use Component\Promotion\SocialShare;
use Component\Validator\Validator;
use Component\Mall\Mall;
use Exception;
use Framework\Debug\Exception\AlertBackException;
use Framework\Debug\Exception\Except;
use Globals;
use Logger;
use Request;
use Session;
use FileHandler;
use Cookie;

class GoodsViewController extends \Bundle\Controller\Mobile\Goods\GoodsViewController
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
		
		$this->setData('goodsView', $goodsView);

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

		$ip = trim(Request::server()->get('REMOTE_ADDR'));
		if ($ip == '220.118.145.49') {

		}

    }




	public function post(){

		$goodsView =  $this->getData('goodsView');	

		## designpix.kkamu 20211123.s

		$present = \App::load('\\Component\\Designpix\\Present');
		
		if($present->cfg['useGiftFl']=='y'){
			if($goodsView['useGiftFl']=='y'){
				$this->setData('giftFl', $goodsView['useGiftFl']) ; 
			}
		}

		//dpx.farmer 상품상세 리뷰 베스트 s
		$dpxGoodsNo = $goodsView['goodsNo'];
		$dpx = \App::load('\\Component\\Designpix\\Dpx');
		$getBestReview = $dpx->getGoodsBestReview($dpxGoodsNo);
		
		foreach($getBestReview as $key=>$val) {
			$bestReviewListGoods[$key] = $dpx->bestReviewListGoods($val['goodsNo']);	//리뷰리스트 상품정보	
		
			$getBestReview[$key]['goodsNm'] = $bestReviewListGoods[$key]['goodsNm'];
			$getBestReview[$key]['goodsPrice'] = $bestReviewListGoods[$key]['goodsPrice'];
			$getBestReview[$key]['fixedPrice'] = $bestReviewListGoods[$key]['fixedPrice'];
			$getBestReview[$key]['goodsImagePath'] = $bestReviewListGoods[$key]['imagePath'];
			$getBestReview[$key]['goodsImage'] = $bestReviewListGoods[$key]['goodsImage'];
			$getBestReview[$key]['reviewCnt'] = $bestReviewListGoods[$key]['plusReviewCnt'] + $bestReviewListGoods[$key]['naverReviewCnt'];
			$getBestReview[$key]['saveFileNm'] = explode('^|^',$getBestReview[$key]['saveFileNm'])[0];
			$getBestReview[$key]['contents'] = str_replace("\\r\\n","<br>",$getBestReview[$key]['contents']);
		}

		$this->setData('getBestReview',$getBestReview);
		//dpx.farmer 상품상세 리뷰 베스트 e
		
		//dpx.farmer 앱전용상품 튜닝 s
		//디바이스 앱 플래그
		$chkAppFl  = \Request::isMyapp();

		if(Request::server()->get('HTTP_X_REQUESTED_WITH') == 'com.goobne.dhuman') {
			$chkAppFl = 'y';
		}

		$this->setData('chkAppFl',$chkAppFl);
		//dpx.farmer 앱전용상품 튜닝 e
		
	}



}
