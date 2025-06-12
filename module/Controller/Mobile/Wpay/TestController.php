<?php

namespace Controller\Front\Wpay;

use App;
use Request;
use Component\Wpay\WpayPay;

/**
 * 웹앤모바일 제작
 * WPAY 작업 할 떄 수동으로 확인할 정보 또는 흐름도 테스트 환경입니다.
 *
 */
class TestController extends \Controller\Front\Controller {
    public function index() {


        $db = App::load('DB');
        $requestValue = Request::request()->all();

        gd_Debug($requestValue);


        switch($requestValue['mode']) {
            case 'deleteUserInDb':
                $sql = "DELETE FROM wm_wpayUsers WHERE userId = 'kyuwonism'";
                $result = $db->fetch($sql);

                gd_Debug($result);


                break;

            case 'checkUserInDb':
                $sql = "SELECT * FROM wm_wpayUsers WHERE userId = 'kyuwonism'";
                $result = $db->query_fetch($sql);

                gd_Debug($result);
                exit;
                break;

            case 'checkOrderInfo':
                $sql = "SELECT * FROM " . DB_ORDER . " WHERE orderNo = '" . $requestValue['orderNo'] . "'";
                $result = $db->query_fetch($sql);

                gd_Debug($result);


            case 'payCancel':
                $wpay = App::load(WpayPay::class);

                $result = $wpay->cancel($requestValue['orderNo'], $requestValue['tid']);

                gd_debug($result);
                break;
        }

        exit;
    }
}