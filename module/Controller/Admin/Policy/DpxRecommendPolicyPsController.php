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
namespace Controller\Admin\Policy;

use Framework\Debug\Exception\LayerException;
use Framework\Debug\Exception\LayerNotReloadException;
use Message;
use Request;
use Exception;

/**
 * 상품 정책 저장 처리
 * @author Shin Donggyu <artherot@godo.co.kr>
 */
class DpxRecommendPolicyPsController extends \Bundle\Controller\Admin\Controller
{

    /**
     * index
     *
     * @throws LayerException
     */
    public function index()
    {

		$db = \App::getInstance('DB');

        switch (Request::post()->get('mode')) {

            // --- 상품정보 노출설정
            case 'dpx_recommend_policy':
                try {

					$req = Request::post()->toArray();
					unset($req[mode]);
					$json = json_encode($req,JSON_UNESCAPED_UNICODE); 
					$qry =" insert es_config set groupCode='dpx', code='recommend', data='$json', regDt=now() on duplicate key update data='$json', modDt=now()  ";
					$db->query($qry);

                    throw new LayerException();
                } catch (Exception $e) {
                    throw $e;
                }
                break;
        }
        exit();
    }
}
