<?php

namespace Controller\Mobile\Service;

use Request;
use session;
use app;
/**
 * Class CooperationController
 * @package Bundle\Controller\Mobile\Service
 * @author  atomyang
 */
class FeedCalController extends \Bundle\Controller\Mobile\Controller
{
    /**
     * {@inheritdoc}
     */
    public function index()
    {
		$dpx = \App::load('\\Component\\Designpix\\Dpx2');
		$foodBrand = $dpx->getFoodBrand();

		$this->setData('foodBrand', $foodBrand);
		$this->setData('gPageName', '급여량 계산기');
    }
}