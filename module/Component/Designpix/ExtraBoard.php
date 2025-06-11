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
namespace Component\Designpix;

use Component\Validator\Validator;
use Component\Page\Page;
use Component\Storage\Storage;
use Component\Database\DBTableField;
use Framework\Utility\ArrayUtils;
use Framework\Utility\ImageUtils;
use Framework\Utility\SkinUtils;
use Framework\Utility\ComponentUtils;
use Framework\Utility\StringUtils;


use Request;
use Session;
use App;

//use Component\Goods\Goods;

class ExtraBoard
{

    protected $db;
    protected $members = [];
    protected $isLogin = false;

	public $extraBoard = "event";


    public function __construct()
    {

        if (!is_object($this->db)) {
            $this->db = App::load('DB');
        }

        $this->isLogin = gd_is_login();

       $this->members = [
            'memNo' => Session::get('member.memNo'),
            'groupSno' => Session::get('member.groupSno'),
        ];
	}




	public function getExtraList($list) {

		$bdId = $this->extraBoard ;

		foreach($list as $k => $r){
			$extraData = $this->getExtra($r);

			if($extraData['extaStatus']){
				$list[$k]['extraSno'] = $extraData['sno'] ;

				switch($extraData['extraStatus']){
					case 1 : $status='status1.png'; $statusStr='처리중'; break;
					case 2 : $status='status2.png'; $statusStr='답변완료';  break;
					case 3 : $status='status3.png'; $statusStr='배송준비중';  break;
					case 4 : $status='status4.png'; $statusStr='배송중';  break;
					case 5 : $status='status5.png'; $statusStr='배송완료';  break;
					default: 	$status='status0.png'; $statusStr='견적요청';  break;
				}
				$list[$k]['extraStatusImg'] = $status;
				$list[$k]['extraStatusStr'] = $statusStr;
			}
		}
	 	return $list;
	}



  	public function getExtra($req) {

		$arrBind =[];
		$query = "SELECT * from dpx_boardExtra  where bdid = ? and bdSno = ?  ";
		$this->db->bind_param_push($arrBind, 's', $req['bdId']);
		$this->db->bind_param_push($arrBind, 'i', $req['sno']);

		$extraData = $this->db->query_fetch($query, $arrBind, false);

		return $extraData;
	}




	public function setExtra($bdSno=0, $req) {

		$arrBind = [];
		$qry =" insert dpx_boardExtra  set bdId = ? , bdSno = ? , useExtraEventFl = ? ,  useExtraPcFl = ? , useExtraMobileFl = ?   on duplicate key 
						update useExtraEventFl = ? ,  useExtraPcFl = ? , useExtraMobileFl = ?, modDt=now()  ";

		## insert
		$this->db->bind_param_push($arrBind, 's', $this->extraBoard);
		$this->db->bind_param_push($arrBind, 'i', $bdSno);
		$this->db->bind_param_push($arrBind, 's', $req['useExtraEventFl']);
		$this->db->bind_param_push($arrBind, 's', $req['useExtraPcFl']);
		$this->db->bind_param_push($arrBind, 's', $req['useExtraMobileFl']);

		## update
		$this->db->bind_param_push($arrBind, 's', $req['useExtraEventFl']);
		$this->db->bind_param_push($arrBind, 's', $req['useExtraPcFl']);
		$this->db->bind_param_push($arrBind, 's', $req['useExtraMobileFl']);

		$this->db->bind_query($qry, $arrBind);

		return $this->db->affected_rows();
	}




}
