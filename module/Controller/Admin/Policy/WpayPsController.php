<?php

namespace Controller\Admin\Policy;

use App;
use Request;
use Component\Wpay\Wpay;
use Framework\Debug\Exception\AlertOnlyException;
use Component\Storage\Storage;
use UserFilePath;
use FileHandler;

/**
 * wpay 설정 저장관련 프로세스
 * Class WpayPsController
 * @package Controller\Admin\Policy
 * @author Webnmobile
 * @date 2021/10/28
 */
class WpayPsController extends \Controller\Admin\Controller {

    public function index() {
        $req = Request::request()->all();
        $file = Request::files()->toArray();

        //wpay 설정 저장
        $wpay = App::load(Wpay::class);

        try {
            switch ($req['mode']) {
                case 'update_set': // wpay 결제 설정
                    $wpay->setWpayConfig($req);

                    //페이 관련 파일이 하나 이상 업로드 됐을 경우
                    if(count($file) > 0) {
                        $wpay->wpayFileHandler($file);
                    }

                    $this->layer('저장되었습니다.');
                    break;
            }
        } catch (AlertOnlyException $e) {
            throw $e;
        }
    }
}