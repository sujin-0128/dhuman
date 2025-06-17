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
namespace Controller\Admin\Policy;

use Framework\Debug\Exception\LayerException;
use Framework\Debug\Exception\LayerNotReloadException;
use Message;
use Request;
use Exception;

/**
 * 상품 정책 저장 처리
 * @author Shin Donggyu <artherot@godo.co.kr>
 */
class GiftPolicyPsController extends \Bundle\Controller\Admin\Controller
{

    /**
     * index
     *
     * @throws LayerException
     */
    public function index()
    {

		$db = \App::getInstance('DB');
		$req = Request::post()->toArray();


		$present = \App::load('\\Component\\Designpix\\Present');

        switch (Request::request()->get('mode')) {

            case 'setCfg':
                try {

					$cfgData['useGiftFl'] = $req['useGiftFl'];
					$cfgData['applyGiftAllFl'] = $req['applyGiftAllFl'];
					$cfgData['expireFl'] = $req['expireFl'];
					$cfgData['expireDt'] = $req['expireDt'];
					$cfgData['expireLimitDt'] = $req['expireLimitDt'];


					$cfgData['benefitSenderMileageFl'] = $req['benefitSenderMileageFl'];
					$cfgData['benefitSenderMileage'] = $req['benefitSenderMileage'];

					$cfgData['benefitReceiverMileageFl'] = $req['benefitReceiverMileageFl'];
					$cfgData['benefitReceiverMileage'] = $req['benefitReceiverMileage'];

					$cfgData['benefitReceiverCouponFl'] = $req['benefitReceiverCouponFl'];
					$cfgData['benefitReceiverCoupon'] = $req['benefitReceiverCoupon'];


					$json = json_encode($cfgData,JSON_UNESCAPED_UNICODE); 
					$present->setGdConfig('giftCfg', $json)	;

					$present->setCardGroup($req); 


                    $this->layer(__('저장이 완료되었습니다.'), 'top.location.reload();');
                } catch (Exception $e) {
                    throw new AlertReloadException('정상적으로 저장되지 않았습니다. 내용을 다시 입력 후 저장해주세요', null, null, 'top');
                }

                break;


                // 빠른 가격 수정
                case 'batchGiftFl':

					$applyFl = $present->setBatchGiftFl($req);

					$this->layer(__('선물상품 상태가 변경되었습니다. '));

                    break;


                case 'addCard':

					$result = $present->addCardData($req);
	
					if($result =='success'){
						$this->layer(__('선물카드가 등록되었습니다. '));
					}else{
						$this->layer($result);
					}
                    break;

                case 'modifyCard':

					$result = $present->modifyCardData($req);
	
					$this->layer(__('선물카드가 수정되었습니다.'));

                    break;

                case 'deleteCard':

					$result = $present->deleteCardData($req);
	
					$this->layer(__('선물카드가 삭제되었습니다. '));
                    break;

		}

        exit();
	}
}
