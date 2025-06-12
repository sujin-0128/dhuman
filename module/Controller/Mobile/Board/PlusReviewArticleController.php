<?php
/**
 * This is commercial software, only users who have purchased a valid license
 * and accept to the terms of the License Agreement can install and use this
 * program.
 *
 * Do not edit or add to this file if you wish to upgrade Enamoo S5 to newer
 * versions in the future.
 *
 * @copyright Copyright (c) 2015 GodoSoft.
 * @link http://www.godo.co.kr
 */

namespace Controller\Mobile\Board;


use Component\PlusShop\PlusReview\PlusReviewArticleFront;
use App;
use Component\Designpix\Dpx;
use Request;

class PlusReviewArticleController extends \Bundle\Controller\Mobile\Controller
{
    public function index()
    {
		if(Request::isMyapp()){
			$this->setData('DhumanApp','on');
		}else{
			$this->setData('DhumanApp','off');
		}

        if(\SESSION::get(SESSION_GLOBAL_MALL || \GodoUtils::isPlusShop(PLUSSHOP_CODE_REVIEW) === false)){
            $this->redirect('../main/index.php');
        }

        $get = \Request::get()->all();

        $plusReviewArticle = new PlusReviewArticleFront();
        if($plusReviewArticle->getConfig('useFl') != 'y'){
            $this->redirect('../main/index.php');
        }
        $data = $plusReviewArticle->getArticleList($get);
        $this->setData('isMypage',$get['isMypage']);

        if($get['isMypage'] == 'y'){
            $this->setData('gPageName', '나의 상품후기');
        }
        else {
            $this->setData('gPageName', '전체리뷰');
        }

        $category = App::load(\Component\Category\Category::class);
        $getData = $category->getCategoryCodeInfo(null, 1,true,false,'mobile',false,false);
        $this->setData('category', $getData);

        $this->setData('plusReviewConfig',$plusReviewArticle->getConfig());
        $this->setData('get',$get);
        $this->setData('data',$data);

		//튜닝 베스트 리뷰 리스트	
		$dpx = new Dpx();
		$bestReviewList = $dpx->bestReviewList();		//리뷰리스트

		foreach( $bestReviewList as $key=>$val) {
			$bestReviewListGoods[$key] = $dpx->bestReviewListGoods($val['goodsNo']);	//리뷰리스트 상품정보	

			$bestReviewList[$key]['maskingId'] = preg_replace('/(.{4})(.*?)$/su','$1**',$val['writerId']);

			$bestReviewList[$key]['goodsNm'] = $bestReviewListGoods[$key]['goodsNm'];
			$bestReviewList[$key]['goodsPrice'] = $bestReviewListGoods[$key]['goodsPrice'];
			$bestReviewList[$key]['fixedPrice'] = $bestReviewListGoods[$key]['fixedPrice'];
			$bestReviewList[$key]['goodsImagePath'] = $bestReviewListGoods[$key]['imagePath'];
			$bestReviewList[$key]['goodsImage'] = $bestReviewListGoods[$key]['goodsImage'];
			$bestReviewList[$key]['reviewCnt'] = $bestReviewListGoods[$key]['plusReviewCnt'];

			$regDt = explode(" ",$val['regDt']);

			$bestReviewList[$key]['regDate'] = str_replace("-", ".", $regDt[0]);
	
		}

		$this->setData('bestReviewList',$bestReviewList);

        #region designpix.cjchan
        // 평점 그래프
        $reviewAvg = $dpx->getReviewAvg();
        $reviewGraph = $dpx->getReviewGraph();
        $this->setData('reviewAvg', $reviewAvg);
        $this->setData('reviewGraph', $reviewGraph);
        #endregion
    }

	public function post() {
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
		if(Request::getRemoteAddress() == '220.118.145.49'){
			//gd_debug($getBestReview);
		}
		$this->setData('getBestReview',$getBestReview);
	}
}
