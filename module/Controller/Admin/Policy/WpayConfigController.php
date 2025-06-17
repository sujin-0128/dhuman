<?php

namespace Controller\Admin\Policy;

use App;
use Component\Wpay\Wpay;

/**
 * WPAY 관련 설정 페이지
 * Class WpayConfigController
 * @package Controller\Admin\Policy
 * @author Webnmobile
 * @date 2021/10/28
 */
class WpayConfigController extends \Controller\Admin\Controller {

    public function index() {
        $this->callMenu('policy', 'settle', 'wpay' );

        //wpay 모듈 로드
        $wpay = App::load(Wpay::class);

        $cfg = $wpay->getCfg();

        $this->setData('conf', $cfg);
    }
}