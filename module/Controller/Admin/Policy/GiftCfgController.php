<?php

namespace Controller\Admin\Policy;

use Globals;
use Request;


class GiftCfgController  extends \Bundle\Controller\Admin\Controller
{
    public function index()
    {
		$this->callMenu('policy', 'gift', 'giftCfg');

			$present = \App::load('\\Component\\Designpix\\Present');
/*
			//$present->sendGiftData('2112201447000254');


			$req['receiverName'] = 'test';
			$req['receiverPhone'] = '010-5051-4254';
			$req['receiverCellPhone'] = '010-5051-4254';
			$req['receiverZipcode'] = '12345';
			$req['receiverZonecode'] = '';
			$req['receiverAddress'] = '경기도 부천시 오정구 ';
			$req['receiverAddressSub'] = '11';
			$req['orderMemo'] = '주문 메모';
			$req['giftSno'] = 56;

//			$present->acceptGiftData('WVRveU9udHBPakE3Y3pvek1qb2lqU3dyaUV4dXFoTnYyelFtaUhtRk00Tjkza05EVVZIRjIvTWlJbmVId1FVaU8yazZNVHR6T2pNeU9pSVRMa1Jsblg0VkZPWDlFVnhYL2NuME92UTR2cmpuejlrYzZYUnZnRE5WNXlJN2ZRPT0=', $req);
			$present->rejectGiftData('WVRveU9udHBPakE3Y3pvek1qb2lqU3dyaUV4dXFoTnYyelFtaUhtRk00Tjkza05EVVZIRjIvTWlJbmVId1FVaU8yazZNVHR6T2pNeU9pSVRMa1Jsblg0VkZPWDlFVnhYL2NuME92UTR2cmpuejlrYzZYUnZnRE5WNXlJN2ZRPT0=', $req);
*/


        $postValue = Request::post()->toArray();

		$giftCfg = gd_policy('dpx.giftCfg') ; 




		$this->setData('cfg', $giftCfg);

		$cardGroup = $present->getCardGroup();
		$this->setData('cardGroup', $cardGroup);		



		$couponList = $present->getCouponList();
		$this->setData('couponList', $couponList);

    }


}

