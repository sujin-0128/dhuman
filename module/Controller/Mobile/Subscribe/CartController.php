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

namespace Controller\Mobile\Subscribe;

use Component\CartRemind\CartRemind;
use Component\Naver\NaverPay;
use Component\Cart\Cart;
use Component\Member\Member;
use Component\Mall\Mall;
use Framework\Debug\Exception\AlertOnlyException;
use Framework\Debug\Exception\AlertRedirectException;
use Globals;
use Session;
use Response;
use Request;
use Password;

/**
 * 장바구니
 *
 * @author Ahn Jong-tae <qnibus@godo.co.kr>
 * @author Shin Donggyu <artherot@godo.co.kr>
 */
class CartController extends \Controller\Front\Subscribe\CartController
{

    public function index()
    {
        parent::index();
    }  

}
