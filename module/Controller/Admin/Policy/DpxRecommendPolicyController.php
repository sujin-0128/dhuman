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

use Request;

/**
 * 상품 정보 노출 설정 페이지
 * @author atomyang
 */
class DpxRecommendPolicyController extends \Bundle\Controller\Admin\Controller
{
    /**
     * index
     *
     */
    public function index()
    {
        // --- 메뉴 설정
        $this->callMenu('policy', 'dpxRecommend', 'dpxRecommendPolicy');

        // --- 설정 config 불러오기
        $data['config'] = gd_policy('dpx.recommend');

        //기본값 세팅
        gd_isset($data['config']['recommendCommissionFl'],'n'); //추천인 구매적립 사용여부
        gd_isset($data['config']['recommendCommission'], '0.00'); //추천인 일괄적용 지급율

        gd_isset($data['config']['orderCommissionFl'],'n'); //추천인 구매적립 사용여부
        gd_isset($data['config']['orderCommission'], '0.00'); //추천인 일괄적용 지급율


		//체크박스
		$data['checked']['recommendCommissionFl'][$data['config']['recommendCommissionFl']]  =			
		$data['checked']['orderCommissionFl'][$data['config']['orderCommissionFl']]  =		
		$data['checked']['useCommissionFl'][$data['config']['useCommissionFl']]  =		
		$data['checked']['saveMode'][$data['config']['saveMode']]  = "checked='checked'";

        // --- 관리자 디자인 템플릿
        $this->setData('data', $data);
    }
}
