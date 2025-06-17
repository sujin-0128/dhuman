<?php

namespace Controller\Admin\Policy;

use Globals;
use Request;


class GiftCardListController  extends \Bundle\Controller\Admin\Controller
{
    public function index()
    {
		$this->callMenu('policy', 'gift', 'giftCard');

        $postValue = Request::post()->toArray();

		$giftCfg = gd_policy('dpx.giftCfg') ; 

		$this->setData('cfg', $giftCfg);


		$present = \App::load('\\Component\\Designpix\\Present');
		$cardGroup = $present->getCardGroup();

		$this->setData('cardGroup', $cardGroup);		

		$cardList = $present->getCardList();

		$this->setData('cardList', $cardList); 

    }


}

