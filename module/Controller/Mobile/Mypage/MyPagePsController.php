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

use Exception;
use Framework\Debug\Exception\AlertOnlyException;
use Framework\Debug\Exception\AlertRedirectException;
use Request;




class MyPagePsController extends \Bundle\Controller\Mobile\Mypage\MyPagePsController

{

	public function index()
	{
		$referer = Request::getReferer();
		$referer = str_replace('my_page', 'index', $referer);
		Request::server()->set('HTTP_REFERER', $referer);
		parent::index();
	}
}