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
namespace Controller\Front\Goods;

use Component\Board\Board;
use Component\Board\BoardBuildQuery;
use Component\Board\BoardList;
use Component\Board\BoardWrite;
use Component\Naver\NaverPay;
use Component\Page\Page;
use Component\Promotion\SocialShare;
use Component\Mall\Mall;
use Framework\Debug\Exception\AlertRedirectException;
use Framework\Debug\Exception\Except;
use Framework\Debug\Exception\AlertBackException;
use Component\Validator\Validator;
use Message;
use Globals;
use Request;
use Logger;
use Session;
use Exception;
use Endroid\QrCode\QrCode as EndroidQrCode;
use SocialLinks\Page as SocialLink;
use FileHandler;

class BestController extends \Bundle\Controller\Front\Controller
{
    public function index()
    {
		//튜닝
		//$dpx = \App::load('\\Component\\Designpix\\Dpx');

		//$rankData[] = $dpx->rankData();

		//gd_debug($rankData);

		
		//$this->setData('rankData', gd_isset($rankData));


		//gd_debug($rankData);


		//$this->getView()->setDefine('goodsTemplate', 'goods/list/list_11'.'.html');
    }
}
