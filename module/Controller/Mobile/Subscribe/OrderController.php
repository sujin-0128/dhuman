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

namespace Controller\Mobile\Subscribe;

use Component\Agreement\BuyerInformCode;
use Component\Cart\Cart;
use Component\Database\DBTableField;
use Component\Member\Member;
use Component\Member\Util\MemberUtil;
use Component\Order\Order;
use Component\Mall\Mall;
use Framework\Debug\Exception\AlertRedirectException;
use Framework\Utility\ComponentUtils;
use Framework\Utility\StringUtils;
use Request;
use Session;

/**
 * 주문서 작성
 * @author Shin Donggyu <artherot@godo.co.kr>
 */
class OrderController extends \Controller\Front\Subscribe\OrderController
{

    public function index()
    {
        parent::index();
    }

}
