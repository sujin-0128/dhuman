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

class DiscountBundleGroupPsController extends \Bundle\Controller\Admin\Controller
{
	public function index()
    {
        $postData = Request::post()->toArray();

        $mode = $postData['mode'] ?? null;

        $dpx = \App::load('\\Component\\Designpix\\Dpx');

        try {
            switch ($mode) {
                // 그룹 등록 / 수정
                case 'group_register':
                case 'group_modify':
                    $result = $dpx->saveInfoDiscountBundleGroup($postData);
                    $this->layer(__('등록 하였습니다.'));
                    break;

                // 그룹 삭제    
                case 'group_delete':
                    try {

                        if (empty(Request::post()->get('sno')) === false) {
                            foreach ($postData['sno'] as $sno) {
                                $dpx->deleteDiscountBundleGroup($sno, $postData['groupCd'][$sno]);
                            }
                        }

                        unset($postArray);

                        $this->layer(__('삭제 되었습니다.'));
                        
                    } catch (Exception $e) {
                        $this->layer($e->getMessage());
                    }
                    break;

                // 추가 상품 등록    
                case 'register_ajax':
                    $result = $dpx->saveInfoDiscountBundleGroup($postData);
                    break;

                // 상품 결합실패여부 업데이트(메인, 결합상품 구분)    
                case 'update_bundle_type':
                    try {
                        $result = $dpx->updateBundleType($postData);
                        $this->layer(__('수정하였습니다.'), 'parent.location.reload()');
                    } catch (Exception $e) {
                        $this->layer($e->getMessage());
                    }
                    break;

                case 'delete':
                    // $bundleNo = Request::get()->get('bundleNo');
                    // $discountBundle->deleteBundle($bundleNo);
                    break;

                default:
                    throw new Exception('잘못된 요청입니다.');
            }

            // Response::redirect('./bundle_discount_list.php', '처리가 완료되었습니다.', 'parent');

        } catch (Exception $e) {
            Response::alertBack($e->getMessage());
        }
    }
}   