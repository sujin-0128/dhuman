<?php

namespace Controller\Front\Wpay;

use App;
use Request;
use Component\Wpay\WpayMy;

/**
 * Class AjaxWpayMyInfoController
 * @package Controller\Front\Wpay
 * @author Webnmobile kw
 * @date 2021/11/01
 */
class AjaxWpayMyInfoController extends \Controller\Front\Controller {
    public function index() {
        $wpay = App::load(WpayMy::class);
        $wpayMy = $wpay->myWpayInfo();
        $status = 0;

        if($wpayMy) {
            if ($wpayMy['status'] == '00')
                $status = 1;
            else if ($wpayMy['status'] == '01' || $wpayMy['status'] == '03')
                $status = 2;
        }
        echo $status;
        exit;
    }
}