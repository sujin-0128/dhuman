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
namespace Controller\Admin\Order;

use App;
use Exception;
use Request;
use Session;

/**
 * 배송 업체 리스트 페이지
 *
 * @package Bundle\Controller\Admin\Order
 * @author  Jong-tae Ahn <qnibus@godo.co.kr>
 */
class IncOrderViewController extends  \Bundle\Controller\Admin\Order\IncOrderViewController
{
    /**
     * {@inheritdoc}
     */
    public function index()
    {
        parent::index();
    }
	public function post(){
		$data  = $this->getData('data');
		$dpx = \App::load('\\Component\\Designpix\\Dpx');
		$data = $dpx->idPurchaseReturn($data);
		$data =	$dpx->getFirstEventGoods($data);
		$this->setData('data', gd_isset($data));
		
	}
}
