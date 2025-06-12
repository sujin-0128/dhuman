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

// use Framework\Debug\Exception\AlertBackException;
// use Framework\Debug\Exception\LayerException;
// use App;
// use Exception;
// use Globals;
use Request;
// use Session;

class DiscountBundleGroupListController extends \Bundle\Controller\Admin\Controller
{
	public function index(){
		// --- 각 배열을 trim 처리
		$this->callMenu('goods', 'discountBundle', 'discountBundleGroupList');

        // --- 모듈 호출
        $dpx = \App::load('\\Component\\Designpix\\Dpx');

        // --- 추가상품 데이터
        try {
            $getData = $dpx->getDiscountBundleGroupList();
            $page = \App::load('\\Component\\Page\\Page'); // 페이지 재설정

            $this->addScript(['jquery/jquery.multi_select_box.js']);
            $this->setData('data', $getData['data']);
            $this->setData('search', $getData['search']);
            $this->setData('sort', $getData['sort']);
            $this->setData('checked', $getData['checked']);
            $this->setData('page', $page);

            // 공급사와 동일한 페이지 사용
            $this->getView()->setPageName('goods/discount_bundle_group_list.php');

        } catch (\Exception $e) {
            throw $e;
        }


	}
}