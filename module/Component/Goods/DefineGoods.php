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
namespace Component\Goods;

use Request;

/**
 * Goods 관련 정보를 담은 클래스
 *
 * @package Bundle\Component\Goods
 * @author  yjwee <yeongjong.wee@godo.co.kr>
 */
class DefineGoods extends \Bundle\Component\Goods\DefineGoods
{
	public $goodsStateList = [];
    public $goodsPermissionList = [];
    public $goodsPayLimit = [];
    public $goodsImportType = [];
    public $goodsSellType = [];
    public $goodsAgeType = [];
    public $goodsGenderType = [];
    public $fixedSales = [];
    public $fixedOrderCnt = [];
    public $hscode = [];
    public $kcmarkCode = [];
 
    public function __construct()
    {
		parent::__construct();
		$this->goodsPermissionList = [
			'all'    => __('전체(회원+비회원)'),
			'member' => __('회원전용(비회원제외)'),
			'guest'	 => __('비회원'),
			'group'  => __('특정회원등급'),
		];
	}
}