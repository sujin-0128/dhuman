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

namespace Controller\Mobile\Mypage;

use Exception;
use Message;
use Request;
use Session;

/**
 * Class MypageQnaController
 *
 * @package Bundle\Controller\Mobile\Mypage
 * @author  Jong-tae Ahn <qnibus@godo.co.kr>
 * @author Shin Donggyu <artherot@godo.co.kr>
 */
class OrderPsController extends \Bundle\Controller\Mobile\Mypage\OrderPsController
{


	public function pre() {
       try {
            // 리퀘스트 처리
            $postValue = Request::post()->xss()->toArray();

            switch ($postValue['mode']) {

                case 'sendGiftMsg':
			
					$present = \App::load('\\Component\\Designpix\\Present');

                    $res = $present->sendGiftData($postValue['orderNo']);

					if($res){
						$this->json(
							[
								'code'    => 200,
								'message' => __('메세지가 전송되었습니다.'),
							]
						);
					}else{
						$this->json(
							[
								'code'    => 400,
								'message' => __('메세지가 전송이 실패하였습니다.'),
							]
						);
					}
		            exit();
                    break;
            }

        } catch (Exception $e) {
            if (Request::isAjax()) {
                $this->json(
                    [
                        'code'    => 0,
                        'message' => $e->getMessage(),
                    ]
                );
            } else {
                throw $e;
            }
        }
	}



}
