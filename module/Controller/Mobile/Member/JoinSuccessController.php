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
namespace Controller\Mobile\Member;

/**
 * Class 회원가입완료
 * @package Bundle\Controller\Mobile\Member
 * @author  yjwee
 */
class JoinSuccessController extends \Controller\Mobile\Member\JoinOkController
{
	public function index()
    {
        /** @var \Bundle\Controller\Front\Member\JoinOkController $front */
        $front = \App::load('\\Controller\\Front\\Member\\JoinOkController');
        $front->index();

        $this->setData($front->getData());
        $this->setData('gPageName', __('회원가입'));
		$this->getView()->setPageName('member/join_ok');


        //facebook Dynamic Ads 외부 스크립트 적용
        $facebookAd = \App::Load('\\Component\\Marketing\\FacebookAd');
        $fbScript = $facebookAd->getFbCompleteRegistrationScript();
        $this->setData('fbCompleteRegistrationScript', $fbScript);
    }
}