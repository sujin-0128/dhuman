<?php

namespace Controller\Mobile\Mypage;

use Component\Designpix\SmsAgree;
use Request;
use Session;
use Exception;
use Framework\Debug\Exception\AlertOnlyException;
use Framework\Debug\Exception\AlertRedirectException;

class SmsAgreePsController extends \Controller\Mobile\Controller
{
    public function index()
    {
        try {
            $mode = Request::post()->get('mode', '');
            switch ($mode) {
                case 'sms':
                    $smsFl = Request::post()->get('smsAgree', '');
                    if (in_array($smsFl, ['y', 'n']) === false) {
                        throw new Exception(__('잘못된 접근입니다.'));
                    }

                    $smsAgree = new SmsAgree();
                    $res = $smsAgree->updateSmsAgreement(Session::get('member.memNo'), $smsFl);

                    if ($res) {
                        $this->json(['code' => 200, 'message' => 'ok']);
                        break;
                    }
                default:
                    $this->json(['code' => 501, 'message' => 'failed']);
                    throw new AlertRedirectException(__('해당 요청을 수행할 수 없습니다.'), 501, null, '/', 'top');
                    break;
            }
        } catch (Exception $e) {
            if (Request::isAjax()) {
                $this->json($this->exceptionToArray($e));
            } else {
                throw new AlertOnlyException($e->getMessage());
            }
        }
    }
}
