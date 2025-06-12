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

namespace Controller\Mobile\Mypage;

use Component\PlusShop\PlusReview\PlusReviewArticleFront;
use Component\Board\BoardWrite;
use Component\Board\Board;
use Component\Order\Order;
use Framework\Debug\Exception\AlertRedirectException;
use App;
use Exception;
use Framework\Utility\DateTimeUtils;
use Framework\Utility\GodoUtils;
use Framework\Utility\StringUtils;
use Request;
use Globals;
use Session;

/**
 * 마이페이지 > 주문배송/조회
 *
 * @package Bundle\Controller\Mobile\Mypage
 * @author  Jong-tae Ahn <qnibus@godo.co.kr>/**
 */
class OrderListController extends \Bundle\Controller\Mobile\Controller
{
    /**
     * @inheritdoc
     *
     * @throws AlertRedirectException
     */
    public function index()
    {
		if(Request::isMyapp()){
			$this->setData('DhumanApp','on');
		}else{
			$this->setData('DhumanApp','off');
		}
        try {
            // 모듈 설정
            $order = new Order();

			$this->setData('eachOrderStatus', $order->getEachOrderStatus(Session::get('member.memNo'), null, 30));

            // 기간 조회
            $searchDate = [
                '0'   => __('오늘'),
                '7'   => __('최근 %d일', 7),
                '15'  => __('최근 %d일', 15),
                '30'  => __('최근 %d개월', 1),
                '90'  => __('최근 %d개월', 3),
                '180' => __('최근 %d개월', 6),
                '365' => __('최근 %d년', 1),
            ];
            $this->setData('searchDate', $searchDate);

            if (\Component\Order\OrderMultiShipping::isUseMultiShipping() === true) {
                $this->setData('isUseMultiShipping', true);
            }

            if (is_numeric(Request::get()->get('searchPeriod')) === true && Request::get()->get('searchPeriod') >= 0) {
                $selectDate = Request::get()->get('searchPeriod');
            } else {
                $selectDate = 90;
            }
            $startDate = date('Y-m-d', strtotime("-$selectDate days"));
            $endDate = date('Y-m-d', strtotime("now"));
            $wDate = Request::get()->get(
                'wDate',
                [
                    $startDate,
                    $endDate,
                ]
            );
            foreach ($wDate as $searchDateKey => $searchDateValue) {
                $wDate[$searchDateKey] = StringUtils::xssClean($searchDateValue);
            }

            $this->setData('selectDate', $selectDate);

            // 주문 모드 값 체크
            $mode = StringUtils::xssClean(Request::get()->get('mode'));
            switch ($mode) {
                case 'cancel':
                    $gPageName=__("취소/반품/교환 처리 내역");
                    break;
                case 'cancelRequest':
                    $gPageName=__("취소/반품/교환 신청 내역");
                    break;
                case 'refund':
                    $gPageName=__("환불/입금 처리 내역");
                    break;
                case 'refundRequest':
                    $gPageName=__("환불 신청 내역");
                    break;
                default:
                    $mode = '';
                    $gPageName=__("주문목록/배송조회");
                    break;
            }

            // 사용자 반품/교환/환불 신청 사용여부에 따라 데이터 가공
            if (gd_is_plus_shop(PLUSSHOP_CODE_USEREXCHANGE) === true) {
                $orderBasic = gd_policy('order.basic');
                $this->setData('userHandleFl', gd_isset($orderBasic['userHandleFl'], 'y') === 'y');
                $this->addScript(['plusReview/gd_plus_review.js?popup=no']);
            }

            $orderData = $order->getOrderList(10, $wDate, $mode);

            $board = new BoardWrite(['bdId'=>\Bundle\Component\Board\Board::BASIC_GOODS_REIVEW_ID]);
            $isPlusReview = false;
            if(GodoUtils::isPlusShop(PLUSSHOP_CODE_REVIEW)){
                $plusReview = new PlusReviewArticleFront();
                $isPlusReview =  true;
                $this->addScript(['plusReview/gd_plus_review.js?popup=no']);
            }
            $orderReorderCalculation = \App::load('\\Component\\Order\\ReOrderCalculation');
            foreach($orderData as &$val){
                foreach($val['goods'] as &$orderGoods){
                    $handleData = $orderReorderCalculation->getOrderHandleData($val['orderNo'], null, null, $orderGoods['handleSno']);
                    $orderGoods['handleDetailReasonShowFl'] = $handleData[0]['handleDetailReasonShowFl'];
                    //교환추가 출력안되게
                    if($handleData[0]['handleMode'] == 'z'){
                        $orderGoods['handleDetailReasonShowFl'] = 'n';
                    }
                    $orderGoods['viewWriteGoodsReview'] = $board->viewWriteGoodsReview($orderGoods);
                    if($isPlusReview) {
                        $orderGoods['viewWritePlusReview'] = $plusReview->viewMypageReviewBtn($orderGoods);
                    }
                }
            }
            // 사용자 반품/교환/환불 신청 데이터 생성
            $orderData = $order->getOrderClaimList($orderData, $mode);

            // 배송 중, 배송 완료된 상품 카운트해서 버튼 생성 여부
            $orderData = $order->getOrderSettleButton($orderData);

            // 주문 리스트 정보
            $this->setData('orderData', gd_isset($orderData));

            $this->setData('pageName', 'list');

			$goods = \App::load('\\Component\\Goods\\DpxGoods');

            $ordersByRegisterDay = [];
            foreach ($orderData as $index => $item) {
				//240923 dpx-kwc 다시담기를위해 상품데이터 추가
				foreach($item['goods'] as $key => &$val){
					$val['goodsView'] = $goods->getGoodsView($val['goodsNo']);
					//dpx-kwc 주문한 상품옵션번호가 상품옵션에 없을경우 다시담기X처리
					if($val['optionSno']){
						$optionKey = array_search($val['optionSno'], array_column($val['goodsView']['option'], 'sno'));
						if($val['optionSno'] != $val['goodsView']['option'][$optionKey]['sno']){
							$val['goodsView']['err'] = '주문한 상품의 옵션이 다릅니다.';
						}
					}
				}
                if (empty($item['regDt']) === false) {
                    $ordersByRegisterDay[DateTimeUtils::dateFormat('Y-m-d', $item['regDt'])][] = $item;
                    $ordersByRegisterDay[DateTimeUtils::dateFormat('Y-m-d', $item['regDt'])][$index]['orderGoodsCnt'] = count($item['goods']);
                }
            }
            $this->setData('ordersByRegisterDay', gd_isset($ordersByRegisterDay));

if (\Request::server()->get('REMOTE_ADDR') == "220.118.145.49"){ 

//gd_debug($ordersByRegisterDay) ; 

}




            if (Request::get()->get('listMode') == 'data') {
                $this->getView()->setPageName('mypage/_order_goods_list');
            } else if (Request::get()->get('listMode') == 'food_data') {
                // food_story 별도 파일 이용
                $this->getView()->setPageName('mypage/_order_goods');
            }

            $this->setData('isOrderList', true);

            // 주문셀 합치는 조건
            $this->setData('cellCombineStatus', $order->statusListCombine);

            // 세금계산서 이용안내
            $taxInfo = gd_policy('order.taxInvoice');
            if (gd_isset($taxInfo['taxInvoiceUseFl']) == 'y') {
                $taxInvoiceInfo = gd_policy('order.taxInvoiceInfo');
                if ($taxInfo['taxinvoiceInfoUseFl'] == 'y') {
                    $this->setData('taxinvoiceInfo', nl2br($taxInvoiceInfo['taxinvoiceInfo']));
                }
            }








			//튜닝
			$mem_id = \Session::get('member.memId');

			//키값
			$key_value = "5UWZ40OEE6T3WSTYBSXN2F4VNEETUVEW";
			$key_value = substr($key_value, 0, 32);

			// Initial Vector(IV)는 128 bit(16 byte)입니다.
			$iv = chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0);

			// 암호화
			$cst_id = base64_encode(openssl_encrypt($mem_id, 'aes-256-cbc', $key_value, OPENSSL_RAW_DATA, $iv));

			// 복호화
			$decrypted = openssl_decrypt(base64_decode($cst_id), 'aes-256-cbc', $key_value, OPENSSL_RAW_DATA, $iv);

			$this->setData('cst_id', $cst_id);

			//gd_debug($mode);













            // 상품 옵션가 표시설정 config 불러오기
            $optionPriceConf = gd_policy('goods.display');
            $this->setData('optionPriceFl', gd_isset($optionPriceConf['optionPriceFl'], 'y')); // 상품 옵션가 표시설정

            // 페이지 재설정
            $page = \App::load('\\Component\\Page\\Page');
            $this->setData('page', gd_isset($page));
            $this->setData('total', $page->recode['total']);
            $this->setData('gPageName', $gPageName);
            $this->setData('goodsReviewId',Board::BASIC_GOODS_REIVEW_ID);
            $this->setData('mode', $mode);
        } catch (Exception $e) {
            throw new AlertRedirectException($e->getMessage(), null, null, URI_HOME);
        }





    }
}
