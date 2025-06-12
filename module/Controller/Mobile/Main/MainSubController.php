<?php
	namespace Controller\Mobile\Main;
	/* DPX하영 아래 3가지 입력시 인트로대문 사용하여 메인페이지 진입할때 오류나지 않음.*/
	use Request;
	use Session;
	use Cookie;

	class MainSubController extends \Controller\Mobile\Controller
	{
		public function index()
		{





			// 모듈 설정
			$goods = \App::load('\\Component\\Goods\\Goods');
			$displayConfig = \App::load('\\Component\\Display\\DisplayConfig');
			$timeSale = \App::load('\\Component\\Promotion\\TimeSale');
			$dpx = \App::load('\\Component\\Designpix\\Dpx');

			$getValue['sno'] = $dpx->getTimeSaleSno();

			$getData = $timeSale->getInfoTimeSale($getValue['sno']);

			$getData['repre'] = $getData['goodsNo'];

			$this->setData('timeSaleInfo', gd_isset($getData));

			$today = date("Y-m-d H:i:s");
			
			
			if( $getData['endDt'] > $today && $getData['startDt'] < $today ){
				$eventOver = "n";
			}else{
				$eventOver = "y";
			}

			$this->setData('eventOver', $eventOver);

			$repreData = $dpx->getRepreData($getData['repre']);

			$this->setData('repreData', $repreData);
			$this->setData('getData', $getData);





		}
	}

?>