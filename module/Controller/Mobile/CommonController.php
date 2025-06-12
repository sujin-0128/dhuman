<?php

namespace Controller\Mobile;

use Component\Member\Member;
use Component\Member\Util\MemberUtil;
use Session;
use Validation;
use Request;

// ���ظ���� īī����ũ Ʃ�� 21-10-22
use Component\Godo\GodoKakaoServerApi;
use Framework\Object\SimpleStorage;
use Component\Member\MemberSnsService;
use Cookie;
use Component\Attendance\AttendanceCheckLogin;
use Component\SiteLink\SiteLink;
use Encryptor;

/**
 * Class pro ����ڵ��� ��� ��Ʈ�ѷ��� �������� ����� �� �ִ� ��Ʈ�ѷ�
 * ��Ʈ�ѷ����� �����ϴ� �޼ҵ���� ����� �� �ֽ��ϴ�. http://doc.godomall5.godomall.com/Godomall5_Pro_Guide/Controller
 */
class CommonController
{
    public function index($controller)
    {
        /* ���ظ���� īī����ũ ���� 23-01-02 - kakaosyncReturnUrl ���� */
        $request = \App::getInstance('request');
        $phpSelf = gd_php_self();
        $urlDefaultCheck = true;

        if ($request->request()->has('returnUrl')) {
            // returnUrl ����Ÿ Ÿ�� üũ
            try {
                Validation::setExitType('throw');
                Validation::defaultCheck(gd_isset($request->request()->get('returnUrl')), 'url');
            } catch (\Exception $e) {
                $urlDefaultCheck = false;
                $kakaosyncReturnUrl = $request->getReferer();
            }

            if ($urlDefaultCheck) {
                $kakaosyncReturnUrl = $request->getReturnUrl();// url�� Ư���� ���·� ���� ġȯ�ڵ� ����
                // ���ظ���� ���� 23-01-02 - �� ���ǿ����� returnUrl�� ���ϴ� ���·� ���������ʾƼ� ���¸� ������
                $kakaosyncReturnUrl = $request->getScheme() . "://" . $request->getServerName() . $kakaosyncReturnUrl;
            }
        } else {
            $kakaosyncReturnUrl = $request->getReferer();
            // �α���, ȸ������ �������� PS��Ʈ�ѷ��� �ƴϸ� īī����ũ returnUrl ������
            if (strpos($phpSelf, ',main/index.php') === false && strpos($phpSelf, 'member/login.php') === false && strpos($phpSelf, 'member/join_method.php') === false && strpos($phpSelf, '_ps.php') === false) {
                $kakaosyncReturnUrl = $request->getScheme() . "://" . $request->getServerName() . $request->getRequestUri();
            }
        }

        $controller->setData('kakaosyncReturnUrl', urlencode($kakaosyncReturnUrl));
        /* ���ظ���� ���� �� */


        // īī�� �ڵ��α��� Ʃ��
        $no = \Cookie::get('no') ? \Cookie::get('no') : \Cookie::get('kakao1');
        if ($no) {
            if (gd_is_login() === false) {
                $snsno = \App::load("\\Component\\Wm\\Wm");
                $end = $snsno->getToken($no);
                $memId = $end['uuid'];
                $memberSnsService = new MemberSnsService();
                $memberSnsService->loginBySns($memId);
                \Cookie::set('kakao1', $no, (3600 * 24 * 10));
            }
        }

//        }

        //if (\Request::getRemoteAddress() === '182.216.219.157' || \Request::getRemoteAddress() == "112.146.205.124" || \Request::getRemoteAddress() == "121.167.104.240" || \Request::getRemoteAddress() === '121.141.26.133' || \Request::getRemoteAddress() === '121.141.26.134') {
        if (\Request::getRemoteAddress() === '182.216.219.157'){
            $controller->setData('setPage', 1);
        }




        // 웹앤모바일 마이페이지 배송추적 버튼 출력 및 혜택 노출 ================================================== START
        $wmInvoice = new \Component\Wm\WmInvoice();
        if($wmInvoice->applyFl){
            $controller->setData('wm_invoice', true);
        }
        // 웹앤모바일 마이페이지 배송추적 버튼 출력 및 혜택 노출 ================================================== END

		//디자인픽스 앱 확인
		$useragent=Request::server()->get('HTTP_USER_AGENT');

		if(preg_match('/Byapps/u',$useragent)) {
			$controller->setData('DhumanApp2','on');
		}else{
			$controller->setData('DhumanApp2','off');
		}
		/*
		if (Request::server()->get('REMOTE_ADDR') == "220.118.145.49"){ 
			gd_debug($useragent);
		}
		*/



    }
}