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
namespace Controller\Admin\Order;


use Framework\Debug\Exception\AlertReloadException;

use App;
use Request;

/**
 * Class [관리자 모드] 엑셀 처리 페이지
 * @package Bundle\Controller\Admin\Member
 * @author  yjwee
 */
class OrderListGiftPsController extends \Bundle\Controller\Admin\Controller
{
    public function index()
    {
        $req = Request::post()->all();

		$present = \App::load('\\Component\\Designpix\\Present');
        switch ($req['mode']) {
            // 엑셀 샘플 다운로드
            case 'sendMsg':
                $result = $present->sendGiftData($req['orderNo']);

                    $this->json(__('선택한 주문의 선물안내가 전송되었습니다.'));

                exit();
                break;

        }
    }
}
