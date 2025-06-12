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
namespace Controller\Mobile;
 
use Request;
use session;
use app;
/**
 *
 * @author Lee Seungjoo <slowj@godo.co.kr>
 * @author Jong-tae Ahn <qnibus@godo.co.kr>
 */
class Controller extends \Bundle\Controller\Mobile\Controller
{
    /**
	 * 상품페이지별 헤더html 변경 Class
	 *
	 * @author wm3tl
	 */
	public function post()
	{
		//접근 기기 확인
		$VisitStatistics = \App::load('\\Component\\VisitStatistics\\VisitStatistics');
		$OS = $VisitStatistics -> getAgentOS();
		$this->setData('OS', $OS);

		$myapp = \App::load('Component\\Myapp\\Myapp');
		$userOs = $myapp->getMyappOsAgent();
		$this->setData('userOs',$userOs);

		if(Request::isMyapp()){
			$this->setData('DhumanApp','on');
		}else{
			$this->setData('DhumanApp','off');
		}


        
            //카카오싱크 관련
            if (gd_is_login() === false) {
                //designpix.kkamu 20220117.s 모바일에서만 인앱브라우저 적용
//                $req = \Request::get()->toArray();
//                $kakaoSync = \App::load('Component\\Designpix\\KakaoSync');
//                $syncUrl = $kakaoSync->setAutoLogin();
//
//
//
//                if ($syncUrl) {
//                    Session::set('kakaoTalkUrl', urlencode(\REQUEST::getRequestUri()));
//                    $kakaoSync->syncResult('controller', $syncUrl);
//                    $this->redirect($syncUrl);
//                    exit;
//                }
            }


            $wpay = App::load("\\Component\\Wpay\\Wpay");

            $userWpayInfo = $wpay->myWpayInfo();


            $this->setData('userWpayInfo', $userWpayInfo);


	}
}
