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
use Request;
use Session;
use App;

class Recommend
{
    protected $db;

	protected $dpxCfg = array(); 


    public function __construct()
    {
        if (!is_object($this->db)) {
            $this->db = App::load('DB');
        }

		if (\Request::server()->get('REMOTE_ADDR') == "220.118.145.49" || \Request::server()->get('REMOTE_ADDR') == "175.198.43.215" ){ 
	        $this->dpxCfg = gd_policy('dpx.recommend');		
		}

	}





	public function setSaveCommission($orderNo){
		//    Order->statusChangeCodeS 실행. 구매확정 변경시에 실행

		if($this->dpxCfg['useCommissionFl'] != 'y') return false; 


		$mileage = App::load('\\Component\\Mileage\\Mileage');

		// 설정에 따른 추천인 지급금 지급처리 
		$arrBind = [];
		$qry="select 
						o.memNo, o.orderChannelFl,  og.* ,  
						m.memNm, m.memId, m.groupSno, m.recommId , m2.memNo AS recommNo

						from es_order o left join es_orderGoods og on o.orderNo=og.orderNo 
						left join es_goods g on og.goodsNo=g.goodsNo 
						left join es_member m on o.memNo = m.memNo  
						left join es_member m2 ON m.recommId = m2.memId  
						where  o.orderNo = ?   and left(o.orderStatus,1) = 's' and og.dpxCommissionEndFl='n'  " ; 

		$this->db->bind_param_push($arrBind, 's', $orderNo);
		$res = $this->db->query_fetch($qry, $arrBind);

		
		$save = [];
		foreach($res as $k => $r){
	
			$recommId= $r['recommId'];
			$recommNo= $r['recommNo'];

			if(empty($recommId)) {
				$this->saveLog($r,$recommNo,'err-recomm','', 0) ; 				
				break; 
			}


			if($this->dpxCfg['recommendCommissionFl']=='y' && $this->dpxCfg['recommendCommission']>0 && $r['recommNo']){

				$commissionSave =0;
				$commissionPrice = $this->dpxCfg['recommendCommission'];
				$commissionType = 'p';



				$goodsPrice = $r['goodsPrice'] + $r['optionPrice'] + $r['addGoodsPrice'] + $r['optionTextPrice'] - $r['goodsDcPrice'] - $r['memberDcPrice'] - $r['memberOverlapDcPrice'] - $r['couponGoodsDcPrice'] - $r['timeSalePrice'];


				if($commissionType=='p'){
					//%할인
					if($commissionPrice<100){
						$commissionSave = ceil($goodsPrice * ($commissionPrice/100) )  * $r['goodsCnt'] ;
					}
				}else {
					//원할인
					$commissionSave = $commissionPrice * $r['goodsCnt'];
				}	
				

				//추천인 지급
				if($commissionSave>0  ){

					$orderNm = ($r['memNm'])?$r['memNm']:"비회원";

					$msg = "[".$orderNm."]님 상품 구매확정으로 추천인 마일리지 ".number_format($commissionSave)."이 지급되었습니다.  "; 

					$handleCd = $r['orderNo'];
					$handleNo = $r['sno']; 

					$result = $mileage->setMemberMileage($recommNo, $commissionSave, '01005011', 'p',$handleCd, $handleNo, $msg);


					if($result){
						$this->saveLog($r,$recommNo,'success','seller',$commissionSave ) ; 				

						//테스트시 주석처리
						$this->db->query("update es_orderGoods set dpxCommissionEndFl='y', dpxCommissionEndDt=now() , dpxCommissionEndPrice='".$commissionSave."'   where sno = '".$r['sno']."' ");

						$save['commissionSave'] += $commissionSave; 
					}else{
						// error Log
						$this->saveLog($r,$recommNo,'err-exec','seller',$commissionSave) ; 				
					}
				}else{
						// error Log
						$this->saveLog($r,$recommNo,'err-commission','seller',$commissionSave) ; 				
				}

			} //recommendCommission




			//추천한 구매자회원 마일리지 지급 . 단 마일리지, %로만 지급처리 
			if($this->dpxCfg['orderCommissionFl']=='y' && $this->dpxCfg['orderCommission']>0){

				if($this->dpxCfg['orderCommission']<100 && $this->dpxCfg['orderCommission']>0 ){
					$recommendSave = ceil($goodsPrice * ($this->dpxCfg['orderCommission']/100) )  * $r['goodsCnt'] ;

					$msg2 = "[".$r['memNm']."]님 상품 구매확정으로 ".$this->dpxCfg['commissionNm']." ".number_format($recommendSave)."원이 지급되었습니다.  "; 

					$result2 = $mileage->setMemberMileage($r['memNo'], $recommendSave, '01005011', 'p',$handleCd, $handleNo, $msg2);

					if($result2){
						$this->saveLog($r, $r['memNo'],'success','member', $recommendSave) ; 				

						$save['recommendSave'] += $recommendSave; 

					}else{
						// error Log
						$this->saveLog($r, $r['memNo'],'err-exec','member', $recommendSave) ; 				
					}
				}else{
					// error Log
					$this->saveLog($r, $r['memNo'],'err-commission','member', $recommendSave) ; 				
				}
			} // orderCommission

		} //foreach 
		
		return $save; 

	}






	## 지급금 적립 로그
	public function saveLog($order, $saveMemNo,  $msg, $target, $savePrice, $saveType='mileage'){

		$memId= $order['memId'];
		$orderNo = $order['orderNo'];
		$orderGoodsNo = $order['sno']; 
		$groupSno = $order['groupSno'];

		$qry=" insert dpx_recommLog set 
					msg						= '$msg', 
					target					= '$target',
					memId				= '$memId',
					groupSno			= '$groupSno' , 
					saveMemNo		= '$saveMemNo', 
					orderNo				= '$orderNo',
					orderGoodsNo	= '$orderGoodsNo',
					
					savePrice			= '$savePrice',
					saveType				= '$saveType',

					ip							= '".Request::server()->get('REMOTE_ADDR')."',
					referer					= '".Request::server()->get('HTTP_REFERER')."'		";

		$this->db->query($qry);	
	}



}
