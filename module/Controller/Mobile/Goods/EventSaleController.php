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

namespace Controller\Mobile\Goods;

use Component\Member\Manager;
use Framework\Debug\Exception\AlertBackException;
use Framework\StaticProxy\Proxy\Request;
use Session;

class EventSaleController extends \Bundle\Controller\Mobile\Goods\EventSaleController
{
	public function pre()
	{
		$memNo = Session::get('member');
		$this->setData('dpxGroupNm', Session::get('member.groupNm'));
		$this->setData('dpxMemNm', Session::get('member.memNm'));
	}
}

