<?php


namespace Controller\Mobile\Subscribe;


use Component\Subscribe\Toss\TossDpx;
use Component\Subscribe\Subscribe;

use Framework\Debug\Exception\AlertBackException;
use Framework\Debug\Exception\AlertRedirectException;

use Exception;
use Request;
use Session;



class SubscribeCardController extends \Bundle\Controller\Mobile\Controller {


	public function index() {

		if(!Session::has('member')) {
					throw new AlertRedirectException(__('로그인이 필요합니다. '), null, null, '../member/login.php?returnUrl=' . urlencode(Request::getReferer()));
		}

		$RequestUri = Request::server()->get('REQUEST_URI');
		Session::set('requestUri',$RequestUri);

		$tossDpx = new TossDpx() ; 
		$payReq = $tossDpx->authData('mobile'); 

		$this->setData('gPageName', '간편결제 카드');

		$this->setData("data", $payReq); 

		$subscribe = new Subscribe();
		$getData = $subscribe->subscribeCardList();
		$this->setData('card',$getData) ; 		
		

		$this->getView()->setPageName('subscribe/subscribe_card_toss.php');
		
	}
}

