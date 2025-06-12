<?php
	namespace Controller\Mobile\Main;
	/* DPX하영 아래 3가지 입력시 인트로대문 사용하여 메인페이지 진입할때 오류나지 않음.*/
	use Request;
	use Session;
	use Cookie;

	class MainGuestController extends \Controller\Mobile\Controller
	{
		public function index()
		{
			//모바일 비회원 페이지
		}
	}

?>