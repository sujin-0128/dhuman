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
namespace Controller\Mobile\Intro;
 
/**
 * 인트로 - 일반
 * @author Shin Donggyu <artherot@godo.co.kr>
 */
class IntroController extends \Controller\Mobile\Controller
{
 
    /**
     * index
     *
     */
    public function index()
    {
		if (Request::server()->get('REMOTE_ADDR') == "220.118.145.49"){ 
			gd_debug('dd')
		}
    }
}