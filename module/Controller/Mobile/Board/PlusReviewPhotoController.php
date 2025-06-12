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


use Bundle\Component\PlusShop\PlusReview\PlusReviewArticleFront;
use App;
use Component\Board\Board;
use Component\Page\Page;
use Request;

class PlusReviewPhotoController extends \Bundle\Controller\Mobile\Controller
{
	public function index()
	{
		if (Request::isMyapp()) {
			$this->setData('DhumanApp', 'on');
		} else {
			$this->setData('DhumanApp', 'off');
		}

		if (\SESSION::get(SESSION_GLOBAL_MALL)) {
			$this->redirect('../main/index.php');
		}

		$req = \Request::get()->all();
		gd_isset($req['page'], 1);
		$this->addScript(['jquery/pinterest-grid/pinterest_grid.js']);
		$plusReviewArticle = new PlusReviewArticleFront();
		if ($plusReviewArticle->getConfig('useFl') === 'n') {
			return true;
		}

		$category = App::load(\Component\Category\Category::class);
		$getData = $category->getCategoryCodeInfo(null, 1, true, false, 'mobile', false, false);
		$this->setData('category', $getData);

		$data = $plusReviewArticle->getListPhotoByGoodsNo(null, $req['page'], null, null, $req);

		// 포토후기 페이징 처리 수정
		$data['paging']->setBlockCount(Board::PAGINATION_MOBILE_COUNT);
		$data['paging']->setPage();
		$data['pagination'] = $data['paging']->getPage();



		//튜닝 베스트 리뷰 리스트	
		/** @var \Component\Designpix\Dpx */
		$dpx = \App::load('\\Component\\Designpix\\Dpx');
		$bestReviewList = $dpx->bestReviewList();		//리뷰리스트

		foreach ($bestReviewList as $key => $val) {
			$bestReviewListGoods[$key] = $dpx->bestReviewListGoods($val['goodsNo']);	//리뷰리스트 상품정보	

			$bestReviewList[$key]['goodsNm'] = $bestReviewListGoods[$key]['goodsNm'];
			$bestReviewList[$key]['goodsPrice'] = $bestReviewListGoods[$key]['goodsPrice'];
			$bestReviewList[$key]['fixedPrice'] = $bestReviewListGoods[$key]['fixedPrice'];
			$bestReviewList[$key]['goodsImagePath'] = $bestReviewListGoods[$key]['imagePath'];
			$bestReviewList[$key]['goodsImage'] = $bestReviewListGoods[$key]['goodsImage'];
			$bestReviewList[$key]['reviewCnt'] = $bestReviewListGoods[$key]['plusReviewCnt'];
		}




		$this->setData('bestReviewList', $bestReviewList);
		$this->setData('data', $data);
		$this->setData('gPageName', '베스트후기');
		$this->setData('req', $req);
	}

	public function post()
	{
		//dpx.farmer 2021209 메인페이지 베스트리뷰 가져오기
		$dpx = \App::load('\\Component\\Designpix\\Dpx');
		$getBestReview = $dpx->getMainBestReview();

		foreach ($getBestReview as $key => $val) {
			$bestReviewListGoods[$key] = $dpx->bestReviewListGoods($val['goodsNo']);	//리뷰리스트 상품정보	

			$getBestReview[$key]['goodsNm'] = $bestReviewListGoods[$key]['goodsNm'];
			$getBestReview[$key]['goodsPrice'] = $bestReviewListGoods[$key]['goodsPrice'];
			$getBestReview[$key]['fixedPrice'] = $bestReviewListGoods[$key]['fixedPrice'];
			$getBestReview[$key]['goodsImagePath'] = $bestReviewListGoods[$key]['imagePath'];
			$getBestReview[$key]['goodsImage'] = $bestReviewListGoods[$key]['goodsImage'];
			$getBestReview[$key]['reviewCnt'] = $bestReviewListGoods[$key]['plusReviewCnt'] + $bestReviewListGoods[$key]['naverReviewCnt'];
			$getBestReview[$key]['saveFileNm'] = explode('^|^', $getBestReview[$key]['saveFileNm'])[0];
			$getBestReview[$key]['contents'] = str_replace("\\r\\n", "<br>", $getBestReview[$key]['contents']);
		}
		$this->setData('getBestReview', $getBestReview);
	}
}
