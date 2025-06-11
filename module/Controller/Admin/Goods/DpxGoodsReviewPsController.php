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

namespace Controller\Admin\Goods;

use Framework\Debug\Exception\AlertBackException;
use Framework\Debug\Exception\LayerException;
use App;
use Exception;
use Globals;
use Request;
use Session;

class DpxGoodsReviewPsController extends \Bundle\Controller\Admin\Controller
{
	public function index(){
		// --- 각 배열을 trim 처리
		$postValue = Request::post()->toArray();

		$dpx = \App::load('\\Component\\Designpix\\Dpx');

		try {

			switch($postValue['mode']){
				case 'review_copy':
					unset($postValue['mode']);
					$result = $dpx->getGoodsReview($postValue);

					$this->layer(__($result.'개의 리뷰가 복사되었습니다.'));
					break;
			}
		} catch (Exception $e) {
			throw new LayerException($e->getMessage());
		}
	}
}