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

use App;
use Session;
use Request;
use Exception;

/**
 * Class LayerDeliveryAddress
 *
 * @package Bundle\Controller\Front\Goods
 * @author  su
 */
class LayerBundleMainCartController extends \Controller\Mobile\Controller
{
    /**
     * @inheritdoc
     */
    public function index()
    {

        // gd_debug($this);

        // try {
        //     if (!Request::isAjax()) {
        //         throw new Exception('Ajax ' . __('전용 페이지 입니다.'));
        //     }
        //     // 로그인 체크
        //     $coupon = App::load(\Component\Coupon\Coupon::class);
        //         $post = Request::post()->toArray();

        //         // 상품의 쿠폰리스트
        //         $goodsCouponArrData = $coupon->getGoodsCouponDownList($post['goodsNo'],Session::get('member.memNo'),Session::get('member.groupSno'), null, null, $post['scmNo'], $post['brandCd']);
        //         // 해당 상품의 모든 쿠폰을 보기용으로 변환
        //         $convertCouponArrData = $coupon->convertCouponArrData($goodsCouponArrData);
        //         // 상품의 다운받을 수 있는 쿠폰의 개수
        //         $goodsCouponCnt = $coupon->getGoodsCouponDownListCount($goodsCouponArrData);

        //         $this->setData('goodsCouponArrData', $goodsCouponArrData);
        //         $this->setData('convertCouponArrData', $convertCouponArrData);
        //         $this->setData('goodsNo', $post['goodsNo']);
        //         $this->setData('scmNo', $post['scmNo']);
        //         $this->setData('brandCd', $post['brandCd']);
        //         $this->setData('goodsCouponCnt', $goodsCouponCnt);
        // } catch (Exception $e) {
        //     $this->json([
        //         'error' => 0,
        //         'message' => $e->getMessage(),
        //     ]);
        // }
    }
}
