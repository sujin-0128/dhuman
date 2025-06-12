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

class DiscountBundleGroupRegisterController extends \Bundle\Controller\Admin\Controller
{
	public function index(){


        // --- 메뉴 설정
        if (Request::get()->has('sno')) {
            $this->callMenu('goods', 'discountBundle', 'discountBundleGroupModify');
        } else {
            $this->callMenu('goods', 'discountBundle', 'discountBundleGroupRegister');
        }

        // --- 모듈 호출
        $dpx = \App::load('\\Component\\Designpix\\Dpx');


        try {

            $data = $dpx->getDiscountBundleGroup(Request::get()->get('sno'));

            // --- 관리자 디자인 템플릿
            if (Request::get()->get('popupMode')) {
                $this->getView()->setDefine('layout', 'layout_blank.php');
            }

            $this->addScript([
                'jquery/jquery.multi_select_box.js',
            ]);

            $this->setData('data', $data['data']);
            $this->setData('discountBundleGroupGoodsList', $data['discountBundleGroupGoodsList']);
            $this->setData('checked', $data['checked']);

            // 공급사와 동일한 페이지 사용
            $this->getView()->setPageName('goods/discount_bundle_group_register.php');

            // var_dump($data['discountBundleGroupGoodsList']);

        } catch (\Exception $e) {
            throw $e;
        }


 
	}
}