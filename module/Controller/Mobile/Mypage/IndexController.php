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

use Component\Database\DBTableField;
use Component\Designpix\SmsAgree;
use Component\Goods\GoodsCate;
use Component\Page\Page;
use Cookie;
use Exception;
use Framework\Utility\DateTimeUtils;
use Request;
use Session;

/**
 * Class MypageQnaController
 *
 * @package Bundle\Controller\Mobile\Mypage
 * @author  Jong-tae Ahn <qnibus@godo.co.kr>
 */
class IndexController extends \Bundle\Controller\Mobile\Mypage\IndexController
{
    /**
     * {@inheritdoc}
     */
    public function pre()
    {
		$timenow = date("Y-m-d"); 
		$timetarget = "2023-05-03";
		$str_now = strtotime($timenow);
		$str_target = strtotime($timetarget);
		 
		if($str_now >= $str_target){
			$this->setData('stampTime', 1);
		}

		if (Request::server()->get('REMOTE_ADDR') == "220.118.145.49") {
			$this->setData('stampTime', 1);
		}

		
		//dpx-jd-240620 회원가입일 추가 S
		$memInfo = $this->getData('gMemberInfo');
		
		$dpx2 = \App::load('\\Component\\Designpix\\Dpx2');
		$entryDtData = $dpx2->memEntryDt($memInfo['memNm'],$memInfo['memberFl'],$memInfo['email'],$memInfo['cellPhone']);
		$this->setData('entryDtData', $entryDtData);
		//dpx-jd-240620 회원가입일 추가 E

        #region designpix.cjchan
        $smsAgree = new SmsAgree();

        $this->setData('smsAgree', $smsAgree->getSmsAgreement(Session::get('member.memNo')));
        #endregion
	}
}

