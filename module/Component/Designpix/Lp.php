<?php

namespace Component\Designpix;


use Component\Database\DBTableField;

use Request;
use Session;
use App;
use Cookie;


class Lp
{
	public function __construct()
    {
        if (!is_object($this->db)) {
            $this->db = App::load('DB');
        }
    }

	// 주문 완료
	public function orderPaid($orderNo, $lpInfo, $orderChannel, $couponUse){
		
		$orderInfo = $this->getOrderInfo($orderNo);
		
		if(!$orderInfo['orderName']){
			$member = \App::load('\\Component\\Member\\Member');
			$memberInfo = $member->getMemberInfo($orderInfo['memNo']);
			$orderInfo['orderName'] = $memberInfo['memNm'];
		}
		
		$data = $this->writeReport($orderInfo, $lpInfo, $orderChannel, $couponUse);

		

		$data_json = json_encode($data, JSON_UNESCAPED_UNICODE);
		
		$res = json_decode($this->curl_send($data_json));
		
		if($res[0]->is_success == true){
			if($couponUse){
				$this->setLpinfoCoupon($data, $orderChannel, $couponUse);
			} else {
				$this->setLpinfo($data, $orderChannel);
			}
		}

	}

	// 주문 확정
	public function orderConfirmed($orderNo){

		$data = $this->getJsonData($orderNo);
		
		foreach($data['products'] as $key => $val){
			$today = date("Y-m-d H:i:s");
			$data['products'][$key]['confirmed_at'] = date("c", strtotime($today));
		}
		$data_json = json_encode($data, JSON_UNESCAPED_UNICODE);

		$qry = "update dpx_lpinfo set order_status = 'confirmed', modDt = now(), data = '".$data_json."' where order_id = '".$orderNo."'"; 
		$this->db->query($qry);	
	}

	// 주문 취소
	public function orderCanceled($orderNo){
		
		$data = $this->getJsonData($orderNo);
		
		foreach($data['products'] as $key => $val){
			$today = date("Y-m-d H:i:s");
			$data['products'][$key]['canceled_at'] = date("c", strtotime($today));
		}
		$data_json = json_encode($data, JSON_UNESCAPED_UNICODE);

		$qry = "update dpx_lpinfo set order_status = 'canceled', modDt = now(), data = '".$data_json."' where order_id = '".$orderNo."'"; 
		
		$this->db->query($qry);
	}

	// 실적 목록 
	public function getOrderList($orderStatus, $date){

		$orderNo = $this->getOrderNo($orderStatus, $date);

		foreach($orderNo as $key => $val){
			$data[$key] = $this->getJsonData($val['orderNo']);	
		}
		
		$result = json_encode($data, JSON_UNESCAPED_UNICODE);
		return $result;
	
	}

	// 링크 프라이스 주문건 체크
	public function chkLpinfo($orderNo){
		$qry = "select count(*) as cnt from dpx_lpinfo where order_id = '".$orderNo."'";
		$cnt = $this->db->fetch($qry)['cnt'];
		
		if($cnt) return true;
		else return false;
	}

	// 실적 목록 주문 번호 불러오기
	protected function getOrderNo($orderStatus, $date=0){
		
		switch($orderStatus) {
			case 'paid' :
				$qry = ($date != 0 ? "select order_id as orderNo from dpx_lpinfo where regDt BETWEEN '".$date." 00:00:00' AND '".$date." 23:59:59' GROUP BY order_id" : "select order_id as orderNo from dpx_lpinfo");
			break;

			case 'confirmed':
				$qry = ($date != 0 ? "select order_id as orderNo from dpx_lpinfo where order_status = 'confirmed' AND modDt BETWEEN '".$date." 00:00:00' AND '".$date." 23:59:59' GROUP BY order_id" : "select order_id as orderNo from dpx_lpinfo where order_status = 'confirmed'");
			break;

			case 'canceled':
				$qry = ($date != 0 ? "select order_id as orderNo from dpx_lpinfo where order_status = 'canceled' AND modDt BETWEEN '".$date." 00:00:00' AND '".$date." 23:59:59' GROUP BY order_id" : "select order_id as orderNo from dpx_lpinfo where order_status = 'canceled'");
			break;
		}
		$result = $this->db->query_fetch($qry);

		return $result;
	}

	// 주문 정보 불러오기
	protected function getOrderInfo($orderNo){
		$qry = "select o.memNo, o.totalGoodsPrice, o.overseasSettleCurrency, o.useMileage, o.totalCouponGoodsDcPrice, o.orderNo, oi.orderName from es_order o inner join es_orderInfo oi on o.orderNo = oi.orderNo where o.orderNo = '".$orderNo."'";
		$orderInfo = $this->db->query_fetch($qry)[0];
		$qry2 = "select if(parentGoodsNo != '0', parentGoodsNo, goodsNo) as goodsNo, goodsNm, goodsCnt, goodsPrice, couponGoodsDcPrice, cateCd, regDt, finishDt, cancelDt from es_orderGoods where orderNo = '".$orderNo."'";
		$orderInfo['goods'] = $this->db->query_fetch($qry2);

		return $orderInfo;
	}
	
	// 쿠폰 사용 체크
	/*
	public function chkCouponUse($memNo, $orderNo){
		$qry = 'select coc.couponOfflineCodeUser as couponUse from es_couponOfflineCode as coc left join es_orderCoupon as oc on coc.memberCouponNo = oc.memberCouponNo where coc.memNo = ? and coc.couponNo = 33 and oc.orderNo = ? ';
	
		$arrBind = [];	
        $this->db->bind_param_push($arrBind, 'i', $memNo);
        $this->db->bind_param_push($arrBind, 's', $orderNo);

        $res = $this->db->query_fetch($qry, $arrBind);

		return $res[0]['couponUse'];
	}
	*/

	public function chkCouponUse($memNo, $memberCouponNo){
		$qry = 'select couponNo from es_memberCoupon where memNo = '.$memNo.' and memberCouponNo = '.$memberCouponNo;
		$res = $this->db->fetch($qry);
		if($res['couponNo'] == '33') return true;
		else return false;
	}
	
	// 링크프라이스 실적 작성
	protected function writeReport($orderInfo, $lpInfo, $orderChannel='', $couponUse=false){
			
		$cate = \App::load('\\Component\\Category\\Category');		
		
		$data['order']['order_id'] = $orderInfo['orderNo'];
		$data['order']['final_paid_price'] = floatval($orderInfo['totalGoodsPrice']-$orderInfo['useMileage']-$orderInfo['totalCouponGoodsDcPrice']);
		//$data['order']['currency'] = $orderInfo['overseasSettleCurrency'];
		$data['order']['currency'] = ($orderChannel == 'naverpay' ? "KRW" : $orderInfo['overseasSettleCurrency']);
		$data['order']['user_name'] = ($orderChannel == 'naverpay' ? "네이버페이 구매자(".$orderInfo['orderName'].")" : $orderInfo['orderName']);
		
		$i=1;
		foreach($orderInfo['goods'] as $key => $val){
			$data['products'][$key]['product_id'] = $val['goodsNo'];
			$data['products'][$key]['product_name'] = $val['goodsNm'];
			
			if($orderInfo['goods'][$key+1]['goodsNo'] == $val['goodsNo'] || $orderInfo['goods'][$key-1]['goodsNo'] == $val['goodsNo']){
				$data['products'][$key]['product_id'] .= '_'.($i);	
				$i++;
			}
			
			$data['products'][$key]['category_code'] = gd_isset($val['cateCd'], '000');
			$data['products'][$key]['category_name'] = explode(' > ', gd_htmlspecialchars_decode($cate->getCategoryPosition($val['cateCd'])));
			$data['products'][$key]['quantity'] = $val['goodsCnt'];
			
			$data['products'][$key]['product_final_price'] = round(($val['goodsPrice'] * $val['goodsCnt']) - $orderInfo['useMileage'] * ($val['goodsPrice'] * $val['goodsCnt']) / $orderInfo['totalGoodsPrice'] - $val['couponGoodsDcPrice']);
		
			$data['products'][$key]['paid_at'] = date("c", strtotime($val['regDt']));
			$data['products'][$key]['confirmed_at'] = '';  
			$data['products'][$key]['canceled_at'] = '';   		
		}

		$data['linkprice']['merchant_id'] = 'dhuman';

		if($couponUse){
			$data['linkprice']['event_code'] = 'dhumansale';
			$data['linkprice']['promo_code'] = 'linkprice00';
		}
		else{
			$data['linkprice']['lpinfo'] = $lpInfo['lpinfo'];
		}
		$data['linkprice']['user_agent'] = $lpInfo['user_agent'];
		$data['linkprice']['remote_addr'] = $lpInfo['remote_addr'];
		if($lpInfo['device_type'] == 'pc'){
			$data['linkprice']['device_type'] = 'web-pc';
		}
		else if($lpInfo['device_type'] == 'mobile'){
			$data['linkprice']['device_type'] = 'web-mobile';
		}
		
		
		return $data;
	}

	// 데이터 전송
	protected function curl_send($data)	{
		$url = "https://service.linkprice.com/lppurchase_cps_v4.php";

		$ch = curl_init();                                 //curl 초기화
		curl_setopt($ch, CURLOPT_URL, $url);               //URL 지정하기
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);    //요청 결과를 문자열로 반환 
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);      //connection timeout 10초 
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);   //원격 서버의 인증서가 유효한지 검사 안함
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);       //POST data
		curl_setopt($ch, CURLOPT_POST, true);              //true시 post 전송 
		 
		$res = curl_exec($ch);				
		
		curl_close($ch);
		
		return $res;
	}

	// 주문 저장
	protected function setLpinfo($data, $orderChannel) {
		$data_json = json_encode($data, JSON_UNESCAPED_UNICODE);
		$qry = "insert dpx_lpinfo set 
					order_id='".$data['order']['order_id']."',
					order_channel='".$orderChannel."',
					order_status='paid',
					data='".$data_json."',
					lpinfo='".$data['linkprice']['lpinfo']."', 
					user_agent='".$data['linkprice']['user_agent']."', 
					ip='".$data['linkprice']['remote_addr']."', 
					device_type='".$data['linkprice']['device_type']."',
					regDt=now()
					";
		$this->db->query($qry);
	}
	// 쿠폰 사용 주문 저장
	protected function setLpinfoCoupon($data, $orderChannel, $couponUse) {
		$data_json = json_encode($data, JSON_UNESCAPED_UNICODE);
		$qry = "insert dpx_lpinfo set 
					order_id='".$data['order']['order_id']."',
					order_channel='".$orderChannel."',
					order_status='paid',
					data='".$data_json."',
					lpinfo='".$data['linkprice']['lpinfo']."', 
					user_agent='".$data['linkprice']['user_agent']."', 
					ip='".$data['linkprice']['remote_addr']."', 
					device_type='".$data['linkprice']['device_type']."',
					regDt=now(),
					event_code='dhumansale',
					promo_code='linkprice00'
					";
		$this->db->query($qry);
	}

	// 자동 취소 주문 불러오기
	public function getCancelOrder(){

		$qry = 'select lp.order_id 
			from dpx_lpinfo lp
			inner join es_order o 
			on lp.order_id = o.orderNo
			where lp.regDt >= "'.date("Y-m-d", strtotime(date("Y-m-d")."-5 day")).' 00:00:00" 
			AND  lp.regDt < "'.date("Y-m-d", strtotime(date("Y-m-d")."-5 day")).' 23:59:59"
			AND lp.modDt IS NULL
			AND o.orderStatus = "o1"';

		$res = $this->db->query_fetch($qry);

		return $res;
	}

	// lpinfo 불러오기
	protected function getLpinfo($orderNo){
		$qry = "select lpinfo, user_agent, ip, device_type dpx_lpinfo where order_id = '".$orderNo."'";
		$result = $this->db->fetch($qry);

		return $result;
	}

	// JSON 불러오기
	protected function getJsonData($orderNo){
		$qry = "select data from dpx_lpinfo where order_id = '".$orderNo."'";
		$json = $this->db->fetch($qry);
		$result = json_decode($json['data'], true);

		return $result;
	}
}