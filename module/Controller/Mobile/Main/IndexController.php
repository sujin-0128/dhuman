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
namespace Controller\Mobile\Main;

use Component\Board\BoardAdmin;
use Request;

/**
 * 모바일 메인 페이지
 * @author Shin Donggyu <artherot@godo.co.kr>
 */
class IndexController extends \Bundle\Controller\Mobile\Controller
{

    /**
     * index
     *
     */
    public function index()
    {
		if(Request::isMyapp()){
			$this->setData('DhumanApp','on');
		}else{
			$this->setData('DhumanApp','off');
		}

        $board = new BoardAdmin();
        $boardList = $board->selectList();
        $this->setData('boardList', $boardList);

		if (Request::server()->get('REMOTE_ADDR') == "220.118.145.49"){ 
			echo "<script>window.location.replace('../');</script>";		
		}else{
		//main/index.php 로 이동시 오류로 돌림
			echo "<script>window.location.replace('../');</script>";		
		}
		
			
		
		//dpx.farmer 2021209 메인페이지 베스트리뷰 가져오기
		$dpx = \App::load('\\Component\\Designpix\\Dpx');
		$getBestReview = $dpx->getMainBestReview();
		
		foreach( $getBestReview as $key=>$val) {
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
    }

}
