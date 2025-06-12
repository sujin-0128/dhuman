<?php

namespace Controller\Front\Wpay;

use App;
use Request;
use Component\Wpay\WpayCallback;
use Framework\Debug\Exception\AlertOnlyException;

/**
 * Class CallbackMemberCancelController
 * @package Controller\Front\Wpay
 * @author Webnmobile kw
 * @date 2021/11/01
 */
class CallbackMemberCancelController extends \Controller\Front\Controller {
    public function index() {

        $request = Request::request()->all();

        if(class_exists('validation') && method_exists('validation','xssCleanArray')){
            $in = validation::xssCleanArray($request, array(
                validation::DEFAULT_KEY => 'text',
            ));
        }

        $wpay = App::load(WpayCallback::class);

        try {

            $result = $wpay->setData($request)->callback('memberCancel');
            if($result === true) $this->js("opener.location.reload(); self.close();");

        } catch(AlertOnlyException $e) {
            throw $e;
        }
    }
}