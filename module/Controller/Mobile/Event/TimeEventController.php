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
 * @link      http://www.godo.co.kr
 */

namespace Controller\Mobile\Event;

use App;
use Framework\Debug\Exception\AlertRedirectException;
use Framework\Debug\Exception\AlertBackException;
use Session;
use Request;

/**
 * Class AttendReplyController
 * @package Bundle\Controller\Mobile\Event
 * @author  yjwee
 */
class TimeEventController extends \Controller\Mobile\Controller
{
    public function index()
    {
		$now = date("Y-m-d H:i:s");
		$eventStart = "2021-11-24 00:10:00";
		$eventEnd = "2021-11-30 00:00:00";

		$str_now = strtotime($now);
		$str_Start = strtotime($eventStart);
		$str_End = strtotime($eventEnd);

		if($str_now < $str_Start || $str_now > $str_End) {
			throw new AlertBackException(__('이벤트기간이 아닙니다.'));			
		}

		$dpx = \App::load('\\Component\\Designpix\\Dpx');
		$getSaleData = $dpx->getSaleData();
		$this->setData('setData', $getSaleData);
    }
}
