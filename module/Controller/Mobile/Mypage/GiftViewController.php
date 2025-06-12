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

namespace Controller\Mobile\Mypage;

use Component\PlusShop\PlusReview\PlusReviewArticleFront;
use Component\Board\BoardWrite;
use Component\Board\Board;
use Framework\Debug\Exception\AlertRedirectException;
use Framework\Debug\Exception\AlertBackException;
use Exception;
use Framework\Utility\DateTimeUtils;
use Framework\Utility\GodoUtils;
use Request;
use App;
use Session;

/**
 * 주문 상세 보기 페이지
 *
 * @author artherot
 * @version 1.0
 * @since 1.0
 * @copyright Copyright (c), Godosoft
 */
class GiftViewController extends \Bundle\Controller\Mobile\Controller
{
    /**
     * @inheritdoc
     *
     * @throws AlertRedirectException
     */
    public function index()
    {
        try {
            $this->addScript([
                'iscroll/iscroll.js',
            ]);


            // 모듈 설정
            $order = \App::load('\\Component\\Order\\OrderNew');

            // 주문 리스트 정보
            $orderData = $order->getOrderGiftView(Request::get()->get('orderNo'));

			$this->setData('orderInfo', $orderData) ; 

			$orderDatas[] = $orderData;

            $ordersByRegisterDay = [];
            foreach ($orderDatas as $index => $item) {
                if (empty($item['regDt']) === false) {
                    $ordersByRegisterDay[DateTimeUtils::dateFormat('Y-m-d', $item['regDt'])][] = $item;
                    $ordersByRegisterDay[DateTimeUtils::dateFormat('Y-m-d', $item['regDt'])][$index]['orderGoodsCnt'] = count($item['goods']);
                }
            }

            $this->setData('ordersByRegisterDay', $ordersByRegisterDay);

        } catch (Exception $e) {
            throw new AlertBackException($e->getMessage());
        }
    }
}
