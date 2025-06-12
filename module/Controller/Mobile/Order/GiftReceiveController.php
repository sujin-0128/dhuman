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
namespace Controller\Mobile\Order;

use Session;
use Request;
use Component\Policy\Policy;
use Framework\Debug\Exception\AlertRedirectException;

/**
 * 주문서 작성
 * @author Shin Donggyu <artherot@godo.co.kr>
 */
class GiftReceiveController extends \Bundle\Controller\Mobile\Controller
{


	public function index(){
        try {


			$present = \App::load('\\Component\\Designpix\\Present');

			$giftKey = Request::post()->get('gkey'); 

			$giftInfo = $present->getGiftData($giftKey);

			if($giftInfo['giftExpireFl']=='y'){
				throw new AlertRedirectException('선물 수령기간이 지났습니다. ', null, null, '/', 'parent');
			}

			if(empty($giftInfo['orderNo'])){
				throw new AlertRedirectException('올바른 접근이 아닙니다.', null, null, '/', 'parent');
			}

			if(empty($giftInfo['giftSno'])){
				throw new AlertRedirectException('유효한 선물이 아닙니다.', null, null, '/', 'parent');
			}

			if($giftInfo['giftReceiveFl']=='y'){
				throw new AlertRedirectException('주문정보가 완료된 선물입니다. ', null, null, '/', 'parent');
			}

			$this->setData('giftKey', $giftKey); 
			$this->setData('giftSno', $giftInfo['giftSno']); 

			$this->setData('giftInfo', $giftInfo); 
			$this->setData('goods', $giftInfo['goods']); 
			$this->setData('gift', $giftInfo['gift']); 


	    }
        catch (Exception $e) {

			throw new AlertRedirectException('올바른 접근이 아닙니다.', null, null, '/', 'parent');

        }
	}
}