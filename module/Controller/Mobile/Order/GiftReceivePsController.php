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
namespace Controller\Mobile\Order;

use Framework\Debug\Exception\AlertOnlyException;
use Framework\Debug\Exception\AlertBackException;
use Framework\Debug\Exception\AlertRedirectException;
use Component\Member\Util\MemberUtil;
use Exception;
use Message;
use Request;
use Session;
/**
 * 상품 보관함 처리 페이지
 *
 * @author artherot
 * @version 1.0
 * @since 1.0
 * @copyright Copyright (c), Godosoft
 */
class GiftReceivePsController extends \Bundle\Controller\Mobile\Controller
{

	public function index() {

        // --- 상품 보관함 class

        $postValue = Request::post()->toArray();
        $getValue = Request::get()->toArray();
        $request = \App::getInstance('request');




			
		$present = \App::load('\\Component\\Designpix\\Present');

        // --- 각 모드별 처리
        switch ($postValue['mode']) {

            // 상품 보관함 추가
            case 'receiveOk':

				$result = $present->acceptGiftData($postValue['giftKey'], $postValue) ; 

				if ($result['success']>0) {

					$returnUrl = './gift_receive_end.php?orderNo='.$result['orderNo'];
					$this->redirect($returnUrl, null, 'parent');  //direct
//                    throw new AlertRedirectException('선물배송정보가 처리되었습니다.', null, null, $returnUrl, 'parent'); //ifrmProcess
				} else {
					$returnUrl = '/';
                    throw new AlertRedirectException('선물배송정보 처리가 실패하였습니다. ['.$result.']', null, null, $returnUrl, 'parent'); //ifrmProcess
				}
                break;

            case 'queryGift':

				$order = \App::load('\\Component\\Order\\OrderNew');

				$receiverNm = Request::post()->get('receiverNm');
				$orderNo = Request::post()->get('orderNo');
				$aResult = $order->isGuestGiftOrder($orderNo, $receiverNm);


				if ($aResult['result']) {
					$orderNo = $aResult['orderNo'];
					MemberUtil::guestOrder($orderNo, $orderNm);

					// 마이앱 로그인뷰 스크립트
					if (\Request::isMyapp() && $useMyapp && $useMyappQuickLogin) {
						$this->redirect($returnUrl . '?orderNo='.$orderNo, null, 'top');
						break;
					}

					$this->json(
						[
							'result'  => '0',
							'message' => __('주문조회에 성공했습니다.'),
							'orderNo' => $orderNo,
						]
					);
				} else {
					$this->json(
						[
							'result'  => '-1',
							'message' => __('주문자명과 주문번호가 일치하는 주문이 존재하지 않습니다. 다시 입력해 주세요. 주문번호와 비밀번호를 잊으신 경우, 고객센터로 문의하여 주시기 바랍니다.'),
						]
					);
				}
				break;



        }

        exit();

	}


}
