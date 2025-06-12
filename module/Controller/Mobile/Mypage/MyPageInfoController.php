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
 * @link      http://www.godo.co.kr
 */
namespace Controller\Mobile\Mypage;

use Request;


class MyPageInfoController extends \Bundle\Controller\Mobile\Mypage\MyPageController
{
    public function index()
    {
		$memInfo = $this->getData('gMemberInfo');
		$dpx2 = \App::load('\\Component\\Designpix\\Dpx2');
		$entryDtData = $dpx2->memEntryDt($memInfo['memNm'],$memInfo['memberFl'],$memInfo['email'],$memInfo['cellPhone']);
		$this->setData('entryDtData', $entryDtData);

		$myPage = \App::load('\\Component\\Member\\MyPage');
		$memberData = $myPage->myInformation();
		
		if($memberData['smsFl'] == 'n'){
			$smsAgreementDt = '미동의';
		}else{
			$History = \App::load('\\Component\\Member\\History');
			$lastReceiveAgreementDt = $History->getLastReceiveAgreementByMember(Session::get('member.memNo'));
			$smsAgreementDt = gd_isset($lastReceiveAgreementDt['lastReceiveAgreementDt']['sms'],$memberData['entryDt']);
			$smsAgreementDt = substr($smsAgreementDt, 0, 10);
		}
		$this->setData('smsAgreementDt', $smsAgreementDt);
    }
}