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
class GiftReceiveEndController extends \Bundle\Controller\Mobile\Controller
{


	public function index(){
        try {

			$orderNo = Request::get()->get('orderNo'); 

			if(empty($orderNo)){
				throw new AlertRedirectException('올바른 접근이 아닙니다.', null, null, '/', 'parent');
			}

			$this->setData('orderNo', $orderNo); 
            

	    }
        catch (Exception $e) {

			throw new AlertRedirectException('올바른 접근이 아닙니다.', null, null, '/', 'parent');

        }


	}
}