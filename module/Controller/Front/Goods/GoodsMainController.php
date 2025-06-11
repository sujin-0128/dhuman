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

use Framework\Debug\Exception\AlertBackException;
use Framework\Debug\Exception\AlertOnlyException;
use Framework\Debug\Exception\Framework\Debug\Exception;
use Framework\Utility\DateTimeUtils;
use Message;
use Globals;
use Request;
use Session;

class GoodsMainController extends \Bundle\Controller\Front\Goods\GoodsMainController
{

	public function post(){
		$goodsList = $this->getData('goodsList');
		$dpx = \App::load('\\Component\\Designpix\\Dpx');
		$goods = \App::load('\\Component\\Goods\\Goods');

		//튜닝 SearchWord, 리스트와 다르게 key 값을 goodsNo 로 사용
		foreach( $goodsList as $key1=>$val1) {
			foreach( $val1 as $key2=>$val2) {
				$goodsSearchWord = $dpx->getGoodsSearchWord($val2['goodsNo']);
				
				//key값 goodsNo로 변경
				$exSword[$val2['goodsNo']] = explode(",",$goodsSearchWord['goodsSearchWord']);


				//튜닝 plusReview goodsPt
				$goodsList[$key1][$key2]['goodsPt'] = $dpx->getPlusReviewGoodsPt($val2['goodsNo']);
				$goodsList[$key1][$key2]['goodsPtCnt'] = $dpx->getPlusReviewGoodsPtCount($val2['goodsNo']);

				

				$goodsPt = $dpx->getPlusReviewGoodsPt($val2['goodsNo']);
				$goodsEx = explode(".",$goodsPt);

				$goodsList[$key1][$key2]['goodsPtUp'] = $goodsEx[0];
				$goodsList[$key1][$key2]['goodsPtDown'] = $goodsEx[1];
			}
		}

		$goodsList = $this->setData('goodsList', gd_isset($goodsList));
	}
}

