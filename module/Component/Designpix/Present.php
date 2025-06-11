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
use Component\Goods\Goods;
use Component\Database\DBTableField;

use Component\Sms\Sms;
use Component\Sms\SmsMessage;
use Component\Sms\LmsMessage;
use Framework\Security\Otp;

use Framework\Utility\ArrayUtils;
use Framework\Utility\ImageUtils;
use Framework\Utility\SkinUtils;
use Framework\Utility\ComponentUtils;
use Framework\Utility\StringUtils;
use Framework\Utility\GodoUtils;
use Encryptor;
use Request;
use Session;
use App;
 use DateTime;

use Component\Coupon\CouponAdmin;


class Present
{

	public $cfg; 

    protected $db;
    protected $members = [];
    protected $isLogin = false;


	//관리자 주문상태 추가후 설정 
	protected $presentStep1 = 'p2';		//선물수락대기
	protected $presentStep2 = 'p3';		//선물수락거부
	protected $presentStep3 = 'p4';		//선물수락완료

	protected $expireDefaultDt = 14;  //선물만기일 미설정시 최대 기한

	protected $uploadDir = '';
	protected $uploadLimitSize = 5; //mb 단위 

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

		$this->uploadDir = Request::server()->get('DOCUMENT_ROOT')."/data/upload/card/"; 
		$this->cfg = gd_policy('dpx.giftCfg') ; 

	}





	## 결제완료시 P2 선물결제단계로 변경처리 
	public function setGiftOrder($orderNo) {

		$arrBind = [];
		$updateData = []; 
		$updateData[] = " orderStatus = ? ";

		$this->db->bind_param_push($arrBind, 's', $this->presentStep1);
		$this->db->bind_param_push($arrBind, 's', $orderNo);
		$affectedRows = $this->db->set_update_db('es_order', $updateData, " orderNo = ? ", $arrBind, false);			

		if($affectedRows){

			$arrBind = [];
			$updateData = []; 
			$updateData[] = " orderStatus = ? ";

			$this->db->bind_param_push($arrBind, 's', $this->presentStep1);
			$this->db->bind_param_push($arrBind, 's', $orderNo);

			$this->db->set_update_db('es_orderGoods', $updateData, " orderNo = ? ", $arrBind, false);			
		}

	}

	
	## designpix.kkamu 20221024.s 선물접수완료시 p1 상태로 다시 전환처리( 샵링커 연동시 p1 주문건 추출되기 위해)
	public function setGiftOrderStatus($giftSno, $orderNo){

		$arrBind = [];
		$updateData = []; 
		$updateData[] = " orderStatus = ? ";

		$this->db->bind_param_push($arrBind, 's', 'p1');
		$this->db->bind_param_push($arrBind, 's', $orderNo);
		$affectedRows = $this->db->set_update_db('es_order', $updateData, " orderNo = ? ", $arrBind, false);			

		if($affectedRows){

			$arrBind = [];
			$updateData = []; 
			$updateData[] = " orderStatus = ? ";

			$this->db->bind_param_push($arrBind, 's', 'p1');
			$this->db->bind_param_push($arrBind, 's', $orderNo);

			$this->db->set_update_db('es_orderGoods', $updateData, " orderNo = ? ", $arrBind, false);			


			$arrBind = [];
			$updateData = []; 
			$updateData[] = " giftReceiveDt = now() ";

			$this->db->bind_param_push($arrBind, 'i', $giftSno);
			$this->db->bind_param_push($arrBind, 's', $orderNo);

			$this->db->set_update_db('dpx_gift', $updateData, " sno = ? and orderNo = ? ", $arrBind, false);			

		}


	}







	public function setBatchGiftFl($req) {

		$goods = \App::load('\\Component\\Goods\\GoodsAdmin');

		$arrGoodsNo = $goods->setBatchGoodsNo(gd_isset($req['batchAll']), gd_isset($req['arrGoodsNo']), gd_isset($req['queryAll']));

		foreach($arrGoodsNo as $goodsNo){

			$arrBind = [];
			$updateData = []; 
			$updateData[] = " useGiftFl = ? ";

			$this->db->bind_param_push($arrBind, 's', $req['targetGiftFl']);
			$this->db->bind_param_push($arrBind, 's', $goodsNo);
			$affectedRows += $this->db->set_update_db('es_goods', $updateData, " goodsNo = ? ", $arrBind, false);						
		}

		return $affectedRows; 
	}





	public function getCardGroup() {

		$qry = "SELECT * from dpx_giftCardGroup where useFl ='y' " ; 
		$groupData = $this->db->query_fetch($qry);		
		return $groupData; 
	}






	public function setCardGroup($req) {

		$cardGroup = $this->getCardGroup() ; 

		foreach($cardGroup as $r){

			if($req['cardSno'][$r['sno']] && $req['cardNm'][$r['sno']]!=''){
				
				if($req['cardNm'][$r['sno']] != $r['cardNm']){

					//cardNm 업데이트
					$arrBind = [];
					$updateData = []; 

					$updateData[] = " cardNm = ? ";
					$updateData[] = " modDt=now() ";

					$this->db->bind_param_push($arrBind, 's', $req['cardNm'][$r['sno']]);
					$this->db->bind_param_push($arrBind, 'i', $r['sno']);
					$this->db->set_update_db('dpx_giftCardGroup', $updateData, " sno = ? ", $arrBind, false);			
				}

			}else{
				//카드 삭제
				$arrBind = [];
				$this->db->bind_param_push($arrBind, 'i', $r['sno']);
				$this->db->set_delete_db('dpx_giftCardGroup', " sno = ? ", $arrBind, false);		
			}
		}

		if(count($req['newCardNm'])){

			foreach($req['newCardNm'] as $cardNm){

				if(empty($cardNm)) continue; 
				$data = [];
				$data['cardNm'] = $cardNm; 
		        $arrBind = $this->db->get_binding(DBTableField::tableDpxCardGroup(), $data, 'insert');

		        $this->db->set_insert_db('dpx_giftCardGroup', $arrBind['param'], $arrBind['bind'], 'y');
			}
		}
	}







	public function getCardGroupData($groupSno=0) {

		$arrBind = []; 

		if($sno>0){
			$addQry = " and  g.sno = ?";
			$this->db->bind_param_push($arrBind, 'i', $groupSno);
		}

		$qry = "SELECT g.cardNm, c.* from dpx_giftCardGroup g left join dpx_giftCard c on g.sno=c.cardGroup  where g.useFl ='y' ".$addQry." order by g.sno asc, c.sno asc " ; 

		$cardData = $this->db->query_fetch($qry, $arrBind);

		return $cardData; 
	}







	public function addCardData($req, $multiFl=false) {

        $files = Request::files()->toArray();

		if(count($files)){
			if($multiFl){
				$imgsArr = $this->multiUploadFile($files['cardImg'], $req['cardSno']); 
				$attachFile = implode(STR_DIVISION, $imgsArr); 

				$imgsArr = $this->multiUploadFile($files['cardThumb'], 'thumb'.$req['cardSno']); 
				$thumbFile = implode(STR_DIVISION, $imgsArr); 

			}else{
				$attachFile = $this->attachUploadFile($files['cardImg'], $req['cardGroup']); 
				$thumbFile = $this->attachUploadFile($files['cardThumb'], 'thumb'.$req['cardGroup']); 

			}

			$msg = $attachFile['msg'];
		}

		$cardImg = $attachFile['fileNm'];
		$cardThumb = $thumbFile['fileNm'];

		if($cardImg){

			$qry =" insert dpx_giftCard set cardGroup = ?, cardDesc = ? , cardImg = ?, cardThumb = ?   on duplicate key update  cardGroup = ?, cardDesc = ? , cardImg = ? , cardThumb=?, modDt=now()  ";			

			$this->db->bind_param_push($arrBind, 'i', $req['cardGroup']);
			$this->db->bind_param_push($arrBind, 's', $req['cardDesc']);
			$this->db->bind_param_push($arrBind, 's', $cardImg);
			$this->db->bind_param_push($arrBind, 's', $cardThumb);
	
			$this->db->bind_param_push($arrBind, 'i', $req['cardGroup']);
			$this->db->bind_param_push($arrBind, 's', $req['cardDesc']);
			$this->db->bind_param_push($arrBind, 's', $cardImg);
			$this->db->bind_param_push($arrBind, 's', $cardThumb);

			$this->db->bind_query($qry, $arrBind);

			$msg = 'success';
		}

		return $msg; 
	}




	public function modifyCardData($req) {

        $files = Request::files()->toArray();

		$attachKey = [];
		$thumbKey = []; 

		foreach($req['cardSno'] as $cardSno){
			$attachKey[$cardSno] = $cardSno;
			$thumbKey[$cardSno] = 'thumb'.$cardSno;
		}

		$attachFileArr = $this->multiUploadFile($files['cardImgs'], $attachKey); 
		$thumbFileArr = $this->multiUploadFile($files['cardThumbs'], $thumbKey); 

		foreach($req['cardSno'] as $cardSno){
			$arrBind = [];			

			$attachFile = $attachFileArr[$cardSno];
			$thumbFile = $thumbFileArr[$cardSno];

			$msg = $attachFile['msg'];
			$cardImg = $attachFile['fileNm'];
			$cardThumb = $thumbFile['fileNm'];


			if(empty($cardImg)) $cardImg = $req['attachImgs'][$cardSno];
			if(empty($cardThumb)) $cardThumb = $req['thumbImgs'][$cardSno];


			$qry ="  update  dpx_giftCard set cardDesc = ? , cardImg = ? , cardThumb = ?, modDt=now()  where sno = ? ";			

			$this->db->bind_param_push($arrBind, 's', $req['cardDescs'][$cardSno]);
			$this->db->bind_param_push($arrBind, 's', $cardImg);
			$this->db->bind_param_push($arrBind, 's', $cardThumb);

			$this->db->bind_param_push($arrBind, 'i', $cardSno);

			$this->db->bind_query($qry, $arrBind);
		}


/*
		foreach($attachFileArr as $cardSno => $attachFile){
			$msg = $attachFile['msg'];
			$cardImg = $attachFile['fileNm'];

			if($cardImg){

				$qry ="  update  dpx_giftCard set cardDesc = ? , cardImg = ? , modDt=now()  where sno = ? ";			

				$this->db->bind_param_push($arrBind, 's', $req['cardDescs'][$cardSno]);
				$this->db->bind_param_push($arrBind, 's', $cardImg);

				$this->db->bind_param_push($arrBind, 'i', $cardSno);

				$this->db->bind_query($qry, $arrBind);
			}
		}
*/

	}



	public function deleteCardData($req) {

		$cardSno = $req['cardSno'] ; 

		foreach($cardSno as $sno){

			$arrBind = [];

			$this->db->bind_param_push($arrBind, 'i', $sno);

			$affectedRows += $this->db->set_delete_db('dpx_giftCard', " sno = ? ", $arrBind, false);					
		}

		return $affectedRows; 
	}





	public function getCardList($groupSno = 0) {


		$arrBind =[];

		if($groupSno){
			$addQry =" and g.sno = ? ";
			$this->db->bind_param_push($arrBind, 'i', $groupSno);
		}

		$qry = "SELECT g.cardNm, c.*  FROM  dpx_giftCardGroup as g left join dpx_giftCard c on g.sno = c.cardGroup  where g.useFl='y' AND c.cardImg!='' ".$addQry; 

		$tmpData = $this->db->query_fetch($qry, $arrBind);

		$cardData = []; 

		foreach($tmpData as $r){

			$cardData[$r['cardGroup']]['nm'] = $r['cardNm'] ; 
			$cardData[$r['cardGroup']]['data'][] = $r ; 
		}

		return $cardData; 

	}








	public function setGdConfig($code, $json) {

		if(empty($code)) return false; 

		$arrBind = [];
		$qry =" insert es_config set groupCode='dpx', code=?, data=?, regDt=now() on duplicate key update data= ?, modDt=now()  ";

		$this->db->bind_param_push($arrBind, 's', $code);
		$this->db->bind_param_push($arrBind, 's', $json);
		$this->db->bind_param_push($arrBind, 's', $json);

		$this->db->bind_query($qry, $arrBind);

		return $this->db->affected_rows();
	}






	protected function sendMsg($mode, $orderData) {

		if($orderData['giftSms']=='kakao'){
			$result=$this->sendKakao($mode, $orderData); 
		}else if($orderData['giftSms']=='sms') {
			$result=$this->sendSms($mode, $orderData); 
		}

		return $result; 
	}




	
	//문자 전송
	protected function sendSms($mode, $orderData) {

		$orderNo = $orderData['orderNo']; 

			switch($mode){
				case "sendGift" :   //선물 도착 안내 (수신자)
					$receiverNm=$orderData['receiverName'];
					$receiverPhone=$orderData['receiverCellPhone'];
				break;

				case "acceptGift" :   //선물 수락 안내 (수신자)
					$receiverNm=$orderData['receiverName'];
					$receiverPhone=$orderData['receiverCellPhone'];
				break;

				case "rejectGift" : //선물 취소/환불 안내 (수신자)
					$receiverNm=$orderData['receiverName'];
					$receiverPhone=$orderData['receiverCellPhone'];
				break; 

				case "senderAcceptGift" :    //선물 수락 안내 (발신자)
					$receiverNm=$orderData['orderName'];
					$receiverPhone=$orderData['orderCellPhone'];			
				break;

				case "senderRejectGift" :   //선물 취소/환불 안내 (발신자)
					$receiverNm=$orderData['orderName'];
					$receiverPhone=$orderData['orderCellPhone'];				
				break; 

			}

		$contents =" {rc_mallNm} {orderName}님이 보낸 선물이 도착했습니다.  {giftUrl} ";

		$aBasicInfo = gd_policy('basic.info');

		$replaceArguments =[
				"rc_mallNm" => $aBasicInfo['mallNm'],
				"shopUrl" =>  $aBasicInfo['mallDomain'],

				"goodsNm" => $data['orderGoodsNm'], 
				"orderNo" => $data['orderNo'],

				"orderName" => $orderData['orderName'], 
				"receiverName"	=> $orderData['receiverName'],
				"orderCellPhone" => $orderData['orderCellPhone'],
				"receiverCellPhone" => $orderData['receiverCellPhone'],

				"settlePrice" => number_format($data['settlePrice']),
				"deliveryName" => '',	//택배사
				"invoiceNo"		=> '',	//송장번호

				"giftInputUrl"	=> $orderData['gift']['giftUrl'],		//배송지 입력 링크 : 
				"giftInfoUrl"	=> $orderData['gift']['giftUrl'],		// 선물하기 확인페이지 (수신자) : 
				"myGiftInfoUrl"	=> $orderData['gift']['giftUrl']	//선물하기 확인페이지 (발신자) : 

		] ; 

		$contents = $this->replaceContents($replaceArguments, $contents); 


		if(mb_strlen($contents, 'euc-kr')>90){
		  $smsFl = 'lms';
		}else{
		  $smsFl = 'sms';
		}

		$smsSet = []; 
		$smsSet['sendFl']= $smsFl; 
		$smsSet['receiverType']="direct";
		$smsSet['smsSendType']="now";
		$smsSet['smsContents']= $contents;
		$smsSet['directReceiverNumbers'][]= $receiverPhone ;
		$smsSet['password']= \App::load(\Component\Sms\SmsUtil::class)->getPassword() ;

		$smsAdmin = \App::load('Component\\Sms\\SmsAdmin');

        $iSmsPoint = Sms::getPoint();


		if($iSmsPoint>0){
			$res = $smsAdmin->sendSms($smsSet);
		}

		$status = ($res['success'] >0 || $res =='success')?"success":"fail";

		$this->smsLog($mode, 'sms', $data['orderNo'], $contents, $status); 

	}






	protected function sendKakao($mode, $orderData){

		$receivePossibleFl = false; 

		$orderNo = $orderData['orderNo'];

		if($orderNo){

			switch($mode){

				case "sendGift" : $tCode = "order_55";  //선물 도착 안내 (수신자)
					$receiverNm=$orderData['receiverName'];
					$receiverPhone=$orderData['receiverCellPhone'];
				break;

				case "acceptGift" : $tCode = "order_68";   //선물 수락 안내 (수신자)
					$receiverNm=$orderData['receiverName'];
					$receiverPhone=$orderData['receiverCellPhone'];
				break;

				case "rejectGift" : $tCode = "order_63";  //선물 취소/환불 안내 (수신자)
					$receiverNm=$orderData['receiverName'];
					$receiverPhone=$orderData['receiverCellPhone'];
				break; 

				case "senderSendGift" : $tCode = "order_59";   //선물 도착 안내 (발신자)
					$receiverNm=$orderData['orderName'];
					$receiverPhone=$orderData['orderCellPhone'];			
				break;

				case "senderAcceptGift" : $tCode = "order_69";   //선물 수락 안내 (발신자)
					$receiverNm=$orderData['orderName'];
					$receiverPhone=$orderData['orderCellPhone'];			
				break;

				case "senderRejectGift" : $tCode = "order_62";  //선물 취소/환불 안내 (발신자)
					$receiverNm=$orderData['orderName'];
					$receiverPhone=$orderData['orderCellPhone'];				
				break; 

				case "senderUrgeGift" : $tCode = "order_72";
					$receiverNm=$orderData['orderName'];
					$receiverPhone=$orderData['orderCellPhone'];
				break; 

				case "urgeGift" : $tCode = "order_71";
					$receiverNm=$orderData['orderName'];
					$receiverPhone=$orderData['receiverCellPhone'];
				break; 

				default : $tCode="";
			}
		}

		if($tCode && $receiverPhone){
			$receivePossibleFl = true; 
		}

		if($receivePossibleFl ){

			$qry="select * from es_kakaoMessageTemplate where templateCode='$tCode' and useFlag='y' ";
			$item =  StringUtils::htmlSpecialCharsStripSlashes($this->db->fetch($qry)); 
			if(empty($item['templateName'])) return false;

			$aBasicInfo = gd_policy('basic.info');
			
			$oKakao = new \Component\Member\KakaoAlrim;

			$smsUtil = \App::load('Component\\Sms\\SmsUtil');
			$aSender = $smsUtil->getSender();

			$aSmsLog = [
				'sendFl'            => 'kakao',
				'smsType'           => $item['smsType'],
				'smsDetailType'     => 'ORDER',
				'sendType'          => 'send',
				'subject'           => $item['templateName'],
				"contents"          => $item["templateContent"],
				'receiverCnt'       => 1,
				'replaceCodeType'   => '',
				'sendDt'            => date('Y-m-d H:i:s'),
				'smsSendKey'        => '',
				'smsAutoSendOverFl' => 'none',
				'code'              => $item['templateCode'],
			];

			$scmArr[0]=1; 
			$aLogData[smsAutoCode]="ORDER"; 
			$aLogData[receiver] =[
				'smsAutoType'            => 'member',
				'type'           => 'each',
				'scmNo'	=> $scmArr
			];



			//수신자 정보
			$receiverForSaveSmsSendList[0] = [
				"memNo" => $orderData['memNo'], 
				"memNm" => $receiverNm, 
				"smsfl" => $orderData['smsFl'], 
				"cellPhone" => $receiverPhone, 
			];

			$myGiftInfo = $this->makeMyGiftUrl($orderData['orderNo']);

			$replaceArguments =[
					"rc_mallNm" => $aBasicInfo['mallNm'],
					"shopUrl" =>  $aBasicInfo['mallDomain'],
					"orderNo" => $orderData['orderNo'],
					"goodsNm" => $orderData['orderGoodsNm'], 
					"orderName" => $orderData['orderName'], 
					"receiverName"	=> $orderData['receiverName'],
					"orderCellPhone" => $orderData['orderCellPhone'],
					"receiverCellPhone" => $orderData['receiverCellPhone'],

					"settlePrice" => number_format($orderData['settlePrice']),
					"deliveryName" => '',	//택배사
					"invoiceNo"		=> '',	//송장번호


					"giftInputUrl"	=> $orderData['giftUrl'],		//배송지 입력 링크 : 
					"giftInfoUrl"	=> $orderData['giftUrl'],		//배송지 입력 링크 : 
					"myGiftInfoUrl"	=> $myGiftInfo,	// 선물하기 확인페이지 : 
					"myGiftOrderInfoUrl"	=> $myGiftOrderInfoUrl	// 선물하기 확인페이지 (발신자) : 
			] ; 


			$contents = $this->replaceContents($replaceArguments, $aSmsLog['contents'] ); 

			$iSmsPoint = Sms::getPoint();


			if ($iSmsPoint < Sms::KAKAO_POINT * count($receiverForSaveSmsSendList)) {
				$result='Point:'.$iSmsPoint;
			}else{
				$res = $oKakao->sendKakaoAlrim($aSmsLog, $aSender, $aLogData, $receiverForSaveSmsSendList, $replaceArguments, $contents);
				$result = $res->result ; 
			}
		}else{
			$result = 'error:'.$receiverPhone;
		}

		$this->smsLog($mode, 'kakao', $orderData['orderNo'], $contents, $result); 

		return $result ;

	}





    public function replaceContents($argument, $contents)
    {
        $result = $contents;

		$patterns = $values = [];
		foreach ($argument as $index => $argument) {
			$patterns[] = '{' . $index . '}';
			$values[] = $argument;
		}
		$result = str_replace($patterns, $values, $contents);
		unset($patterns, $values);

		$result = str_replace("#","", $result);

        return $result;
    }






	public function giftLog($mode, $giftSno, $orderNo, $result) {

		$arrBind =[]; 
		$setData['mode'] = $mode ; 
		$setData['giftSno'] = $giftSno ; 
		$setData['orderNo'] = $orderNo ; 
		$setData['result'] = $result ; 
		$arrBind = $this->db->get_binding(DBTableField::tableDpxGiftLog(), $setData, 'insert');
		$this->db->set_insert_db("dpx_giftLog", $arrBind['param'], $arrBind['bind'], 'y', false);
	}





	public function smsLog($mode, $smsType, $orderNo, $contents, $result) {

		$arrBind =[]; 
		$setData['mode'] = $mode ; 
		$setData['smsType'] = $smsType ; 
		$setData['orderNo'] = $orderNo ; 
		$setData['contents'] = $contents ; 
		$setData['result'] = $result ; 
		$arrBind = $this->db->get_binding(DBTableField::tableDpxGiftSmsLog(), $setData, 'insert');
		$this->db->set_insert_db("dpx_giftSmsLog", $arrBind['param'], $arrBind['bind'], 'y', false);

	}





	public function getRecentGiftInfo($orderNo='') {

		if(!is_numeric($orderNo)) return false; 

		$arrBind =[];

		$qry = "SELECT * FROM  dpx_gift where orderNo = ? order by sno desc limit 1"; 
		$this->db->bind_param_push($arrBind, 's', $orderNo);

		$giftData = $this->db->query_fetch($qry, $arrBind, false);

		return $giftData; 
	}







	protected function makeGiftKey($orderNo) {

		$encOrderNo = Encryptor::encrypt($orderNo);
		return base64_encode($encOrderNo) ; 

	}





	protected function makeGiftUrl($encOrderNo) {

		$aBasicInfo = gd_policy('basic.info');
		$shopUrl = $aBasicInfo['mallDomain'] ; 

		$url = 'https://m.'.$shopUrl."/order/gift_info.php?gkey=".$encOrderNo; 

		$giftUrl = GodoUtils::shortUrl($url);
		return $giftUrl ; 

	}

	protected function makeMyGiftUrl($encOrderNo) {

		$aBasicInfo = gd_policy('basic.info');
		$shopUrl = $aBasicInfo['mallDomain'] ; 

		$url = 'https://m.'.$shopUrl."/mypage/gift_view.php?guest=y&orderNo=".$encOrderNo; 

		$myGiftUrl = GodoUtils::shortUrl($url);
		return $myGiftUrl ; 

	}


	public function getGiftData($gkey, $admin='') {

		if($admin=='y'){
			$orderNo = $gkey;
		}else{
			$orderNo = Encryptor::decrypt(base64_decode($gkey));
		}		

		if(!is_numeric($orderNo)) return false; 

		$order = \App::load('\\Component\\Order\\OrderNew');

		$orderData = $order->getGiftOrderDataInfo($orderNo); 


		$orderData['goods'] = $order->getGiftOrderGoods($orderNo); 
		
		foreach($orderData['goods'] as $k => $r){

			$orderData['goods'][$k]['imgs']	= $this->getGiftGoodsInfo($r['goodsNo']); 
		}

		$arrBind =[];

		$qry = "SELECT g.sno, left(g.regDt,10) regDt, g.cardSno, g.cardMsg,g.giftKey, g.giftUrl, g.giftType, g.expireDt, g.giftReceiveFl,
						c.cardThumb, c.cardImg, cg.cardNm 

						FROM  dpx_gift  g left join dpx_giftCard c on g.cardSno=c.sno 
						left join dpx_giftCardGroup cg on c.cardGroup = cg.sno

						where g.orderNo = ? and replace(g.giftPhone, '-', '') = ?  and cg.useFl='y' order by sno desc limit 1	"; 

		$this->db->bind_param_push($arrBind, 's', $orderNo);
		$this->db->bind_param_push($arrBind, 's', str_replace("-","",$orderData['receiverCellPhone']));
		$giftData = $this->db->query_fetch($qry, $arrBind, false);
		$giftData['cardMsg'] = stripslashes($giftData['cardMsg']);




		//선물 수락 만기날짜 설정시 expireDt 사용 

		if($giftData['giftReceiveFl']=='y'){

			$orderData['giftReceiveFl'] = $giftData['giftReceiveFl']; 

		}else{
			if($this->cfg['expireFl']=='y'){
				$toDay = date('Y-m-d');

				if( $toDay > $giftData['expireDt']){
					$orderData['giftExpireFl'] = 'y';
				}else{

					$expireStart  = new DateTime($toDay);
					$expireEnd = new DateTime($giftData['expireDt']);
					$expireInterval = $expireStart->diff($expireEnd);

					$orderData['giftExpireRemain'] = $expireInterval->days;
				}
			}
		}

		$orderData['giftSno'] = $giftData['sno'];
		$orderData['giftKey'] = $giftData['giftKey'];  
		$orderData['giftUrl']	= $giftData['giftUrl'];  
		$orderData['giftType']	= $giftData['giftType'];  

		$orderData['gift'] = $giftData;

		return $orderData; 
	}

	public function getGiftRejectData($orderNo, $admin='') {

		if(!is_numeric($orderNo)) return false; 

		$order = \App::load('\\Component\\Order\\OrderNew');

		$orderData = $order->getGiftOrderDataInfo($orderNo); 

		$orderData['goods'] = $order->getGiftOrderGoods($orderNo); 
		
		foreach($orderData['goods'] as $k => $r){

			$orderData['goods'][$k]['imgs']	= $this->getGiftGoodsInfo($r['goodsNo']); 
		}

		$arrBind =[];

		$qry = "SELECT orderNo
				FROM es_order
				WHERE orderNo = ? AND giftFl = ? AND giftStatus != ?"; 

		$this->db->bind_param_push($arrBind, 's', $orderNo);
		$this->db->bind_param_push($arrBind, 's', 'y');
		$this->db->bind_param_push($arrBind, 's', 'reject');
		$chkData = $this->db->query_fetch($qry, $arrBind, false);

		if(!$chkData) {
			return false;
		}

		return $orderData; 
	}




	public function getGiftGoodsInfo($goodsNo, $imageKind='list') {

		$arrBind =[];
		$qry = "select g.goodsNo, g.goodsNm, g.imageStorage, g.imagePath, gi.imageName,g.makerNm,g.goodsPrice,g.totalStock, g.stockFl,				
		
						g.soldOutFl,g.regDt,g.goodsDisplayFl,g.goodsDisplayMobileFl,g.goodsSellFl,g.goodsSellMobileFl,g.goodsBenefitSetFl
						from es_goods g  LEFT JOIN es_goodsImage  gi ON g.goodsNo = gi.goodsNo AND gi.imageKind =  ? 
						where g.goodsNo  = ? AND g.delFl='n' ";

		$this->db->bind_param_push($arrBind, 's', $imageKind);
		$this->db->bind_param_push($arrBind, 's', $goodsNo);

		$goodsData = $this->db->query_fetch($qry, $arrBind, false);

         $goodsData['goodsImage'] = gd_html_preview_image($goodsData['imageName'], $goodsData['imagePath'], $goodsData['imageStorage'], '100', 'goods', $goodsData['goodsNm'],null, false, true);

		return $goodsData ; 

	}







	## 선물주문완료시 선물하기 메세지 전송
//sendGift, acceptGift, rejectGift, senderAcceptGift, senderRejectGift
	public function sendGiftData($orderNo) {
		
		if($this->cfg['useGiftFl']!='y') return false; 

		$arrBind =[];

		$qry = "SELECT o.* , oi.orderCellPhone, oi.orderName, oi.orderEmail, oi.receiverName, oi.receiverCellPhone
						FROM  es_order o LEFT JOIN es_orderInfo oi ON o.orderNo = oi.orderNo 
						where o.orderNo = ?   	and giftFl='y' and giftStatus= '' and left(o.orderStatus,1) = 'p' ";   

		$this->db->bind_param_push($arrBind, 's', $orderNo);

		$orderData = $this->db->query_fetch($qry, $arrBind, false);


		if(empty($orderData['orderNo'])){

			$this->giftLog('sendGift', 0, $orderNo, 'error-orderNo') ; 
			return false; 

		}

		$arrBind =[]; 
		$setData['giftKey'] = $this->makeGiftKey($orderNo) ;  
		$setData['giftUrl']	= $this->makeGiftUrl($setData['giftKey']) ;  
		$setData['orderNo']	= $orderNo;

		$setData['cardSno']	= $orderData['giftCard']; 
		$setData['cardMsg']	= $orderData['giftMemo']; 

		$setData['giftName']	= $orderData['receiverName'];
		$setData['giftPhone']	= $orderData['receiverCellPhone'];
		$setData['giftDeliveryMemo']	= $orderData['giftMemo'];
		$setData['giftDeliveryFl']	= 'y';


		//expireDt					
	
		if($this->cfg['expireFl']=='y'){

			if($this->cfg['expireDt']>0){
				$expireDt = $this->cfg['expireDt']; 
			}else{
				$expireDt = $this->expireDefaultDt; 
			}

			$setData['expireDt']	=  date('Y-m-d', strtotime($orderData['paymentDt'].' +'.$expireDt.' day'));
		}

		$setData['expireFl'] = $this->cfg['expireFl'] ; 		

		$arrBind = $this->db->get_binding(DBTableField::tableDpxGift(), $setData, 'insert');
		$res=$this->db->set_insert_db("dpx_gift", $arrBind['param'], $arrBind['bind'], 'y', false);
		
		$giftSno = $this->db->insert_id();

		$orderData['giftSno'] = $giftSno;
		$orderData['giftKey'] = $setData['giftKey'];  
		$orderData['giftUrl']	= $setData['giftUrl'];  
		$orderData['giftType']	= $setData['giftType'];  

		$result = $this->sendMsg('sendGift', $orderData);
		$result = $this->sendMsg('senderSendGift', $orderData);

		$this->giftLog('sendGift', $giftSno, $orderNo, 'success') ; 
		return $result; 
	}






	public function acceptGiftData($gkey, $req, $admin='') {

		if($admin=='y'){
			$orderNo = $gkey;
		}else{
			$orderNo = Encryptor::decrypt(base64_decode($gkey));
		}

		$orderData = $this->getGiftData($gkey, $admin); 

		//expireDt 사용시. 수령시점 expireDt 초과할경우 
		if($this->cfg['expireFl']=='y'){
			if(date('Y-m-d') > $orderData['gift']['expireDt']){
				$err = "수령기간 초과";
			}
		}

		if(empty($orderData['giftSno'])) $err="선물번호 없음"; 

		if(!is_numeric($orderData['orderNo'])) $err = "주문번호 없음"; 

		if( substr($orderData['orderStatus'],0,1)!='p') $err = "주문상태 오류"; 

		if($err){

			$this->giftLog('acceptGift', $orderData['giftSno'], $orderNo, $err) ; 
			return  $err ; 
		}

		$arrBind = [];
		$updateData = []; 

		$updateData[] = " receiverName = ? ";
		$updateData[] = " receiverPhone = ? ";
		$updateData[] = " receiverCellPhone = ? ";
		$updateData[] = " receiverZipcode = ? ";
		$updateData[] = " receiverZonecode = ? ";
		$updateData[] = " receiverAddress = ? ";
		$updateData[] = " receiverAddressSub = ? ";
		$updateData[] = " orderMemo = ? ";

		$updateData[] = " modDt=now() ";

		$this->db->bind_param_push($arrBind, 's', $req['receiverName']);
		$this->db->bind_param_push($arrBind, 's', $req['receiverPhone']);
		$this->db->bind_param_push($arrBind, 's', $req['receiverCellPhone']);
		$this->db->bind_param_push($arrBind, 's', $req['receiverZipcode']);
		$this->db->bind_param_push($arrBind, 's', $req['receiverZonecode']);
		$this->db->bind_param_push($arrBind, 's', $req['receiverAddress']);
		$this->db->bind_param_push($arrBind, 's', $req['receiverAddressSub']);
		$this->db->bind_param_push($arrBind, 's', $req['orderMemo']);

		$this->db->bind_param_push($arrBind, 'i', $orderNo);

		$affectedRows = $this->db->set_update_db('es_orderInfo', $updateData, " orderNo = ? ", $arrBind, false);					
		
		if($affectedRows>0){

			$status = 'accept' ;  //선물수락

			$arrBind = [];
			$updateData = []; 
			$updateData[] = " giftStatus = ? ";
			$updateData[] = " modDt=now() ";
			$this->db->bind_param_push($arrBind, 's', $status);
			$this->db->bind_param_push($arrBind, 'i', $orderNo);
			$this->db->set_update_db('es_order', $updateData, " orderNo = ? ", $arrBind, false);					
			
			$arrBind = [];
			$updateData = []; 
			$updateData[] = " giftReceiveFl = 'y' ";
			$updateData[] = " modDt=now() ";

			$this->db->bind_param_push($arrBind, 'i', $orderNo);
			$this->db->bind_param_push($arrBind, 'i', $req['giftSno']);
			$this->db->set_update_db('dpx_gift', $updateData, " orderNo = ? and sno = ? ", $arrBind, false);					

			//designpix.kkamu 20221024.s
			$this->setGiftOrderStatus($req['giftSno'],$orderNo);


			$this->sendMsg('acceptGift', $orderData);				
			$this->sendMsg('senderAcceptGift', $orderData);				
		}

		$result['orderNo'] = $orderNo ;
		$result['success'] = $affectedRows ;

		$this->giftLog('acceptGift', $orderData['giftSno'], $orderNo, 'success') ; 

		return $result; 
	}


	public function rejectGiftData($gkey, $req, $admin='') {

		$orderNo = $gkey;

		$orderData = $this->getGiftRejectData($orderNo); 

		if(!is_numeric($orderData['orderNo'])) $err = "주문번호 없음"; 

		if( !(substr($orderData['orderStatus'],0,1)=='r')) $err = "주문상태 오류"; 

		if($err){

			$this->giftLog('rejectGift', $orderData['giftSno'], $orderNo, $err) ; 
			return  $err ; 
		}

		$status = 'reject' ;  //선물거절

		$arrBind = [];
		$updateData = []; 
		$updateData[] = " giftStatus = ? ";
		$updateData[] = " modDt=now() ";
		$this->db->bind_param_push($arrBind, 's', $status);
		$this->db->bind_param_push($arrBind, 'i', $orderNo);
		$this->db->set_update_db('es_order', $updateData, " orderNo = ? ", $arrBind, false);					
		
		$arrBind = [];
		$updateData = []; 
		$updateData[] = " giftReceiveFl = 'n' ";
		$updateData[] = " modDt=now() ";

		$this->db->bind_param_push($arrBind, 'i', $orderNo);
		$this->db->bind_param_push($arrBind, 'i', $orderData['giftSno']);
		$this->db->set_update_db('dpx_gift', $updateData, " orderNo = ? and sno = ? ", $arrBind, false);					

		$this->sendMsg('rejectGift', $orderData);				
		$this->sendMsg('senderRejectGift', $orderData);			

		$result['orderNo'] = $orderNo ;
		$result['success'] = $affectedRows ;

		$this->giftLog('rejectGift', $orderData['giftSno'], $orderNo, 'success') ; 

		return $result; 
	}

/*
	public function rejectGiftData($gkey, $req, $admin='') {

		if($admin=='y'){
			$orderNo = $gkey;
		}else{
			$orderNo = Encryptor::decrypt(base64_decode($gkey));
		}
		
		$orderData = $this->getGiftData($gkey, $admin); 

		//expireDt 사용시. 수령시점 expireDt 초과할경우 
		if($this->cfg['expireFl']=='y'){
			if(date('Y-m-d') > $orderData['gift']['expireDt']){
				$err = "수령기간 초과";
			}
		}

		if(empty($orderData['giftSno'])) $err="선물번호 없음"; 

		if(!is_numeric($orderData['orderNo'])) $err = "주문번호 없음"; 

		if( substr($orderData['orderStatus'],0,1)!='p') $err = "주문상태 오류"; 

		if($err){

			$this->giftLog('rejectGift', $orderData['giftSno'], $orderNo, $err) ; 
			return  $err ; 
		}


		$status = 'reject' ;  //선물거절

		$arrBind = [];
		$updateData = []; 
		$updateData[] = " giftStatus = ? ";
		$updateData[] = " modDt=now() ";
		$this->db->bind_param_push($arrBind, 's', $status);
		$this->db->bind_param_push($arrBind, 'i', $orderNo);
		$this->db->set_update_db('es_order', $updateData, " orderNo = ? ", $arrBind, false);					
		
		$arrBind = [];
		$updateData = []; 
		$updateData[] = " giftReceiveFl = 'n' ";
		$updateData[] = " modDt=now() ";

		$this->db->bind_param_push($arrBind, 'i', $orderNo);
		$this->db->bind_param_push($arrBind, 'i', $req['giftSno']);
		$this->db->set_update_db('dpx_gift', $updateData, " orderNo = ? and sno = ? ", $arrBind, false);					

		$this->sendMsg('rejectGift', $orderData);				
		$this->sendMsg('senderRejectGift', $orderData);			

		$result['success'] = $affectedRows ;

		$this->giftLog('rejectGift', $orderData['giftSno'], $orderNo, 'success') ; 

		return $result; 
	}
*/



	
	public function multiUploadFile($files, $attachKey=array()) {

		foreach($files['name']	 as $k =>$nm){

			if($files['error'][$k] == 0 && $files['size'][$k] >0){
				$attach = [];
				$attach['name'] = $nm;
				$attach['type'] = $files['type'][$k];
				$attach['tmp_name'] = $files['tmp_name'][$k];
				$attach['error'] = $files['error'][$k];
				$attach['size'] = $files['size'][$k];

		
				$result[$k] =$this->attachUploadFile($attach, $attachKey[$k]);
			}
		}

		return $result; 
	}







	//designpix.kkamu 파일첨부 업로드 
	public function attachUploadFile($attach, $attachKey){


		if(empty($attach['name']) || $attach['size']==0){
			$result['msg'] = '파일이 존재하지 않습니다. 파일을 첨부하세요.';			
		}else{


			//경로 확장자 추출
			$fileExt = pathinfo($attach['name'], PATHINFO_EXTENSION);


			$targetNm= date('YmdHis')."_".$attachKey.".".$fileExt;
			$targetFile = $this->uploadDir . $targetNm;

			if (file_exists($targetFile)) {
				$result['msg']='exists';
				return $result; 
			}

			if ($attach["size"] > ($this->uploadLimitSize * 1024 * 1024) ) {
				$result['msg']='허용용량 '.$this->uploadLimitSize.'Mb를 초과하였습니다. ';
				return $result; 
			}

			$allowExt = array("jpg","png","jpeg","gif","pdf");	

			if(in_array( strtolower($fileExt), $allowExt)===false ) {
				$result['msg']=$fileExt.'는 허용하지 않는 확장자입니다.';
				return $result; 
			}

			if (move_uploaded_file($attach["tmp_name"], $targetFile)) {
				$result['result'] = 'ok';
				$result['fileNm'] = $targetNm;
			} else {
				$result['msg']='업로드에 실패하였습니다.';
			}
		}

		return $result; 
	}





	//선물하기 혜택용 쿠폰 리스트 
	public function getCouponList() {

		$arrBind =[]; 
		$query = "select * from es_coupon where couponKind = ? and couponType='y' and couponSaveType='manual' ";
		$this->db->bind_param_push($arrBind, 's', 'online');
		$couponList = $this->db->query_fetch($query, $arrBind);
		return $couponList ;
	}





	//선물받은 수령자 휴대폰번호 기준으로 대상인지 확인
	public function setGiftJoinBenefit($member) {

		$giftCfg = gd_policy('dpx.giftCfg') ; 

		if($giftCfg['useGiftFl'] !='y') return false;

		if(empty($member['cellPhone']) ) return false; 

		$arrBind =[]; 
		$query = "select o.*, i.orderName, i.receiverName from es_order o left join es_orderInfo i on o.orderNo=i.orderNo where o.giftFl = 'y'  and left(orderStatus,1) in('s','d') and replace(i.receiverCellPhone, '-', '') = ?   order by o.regDt desc limit 1 ";
		$this->db->bind_param_push($arrBind, 's', str_replace("-","",$member['cellPhone']));
		$orderData = $this->db->query_fetch($query, $arrBind, false);		

		if($orderData['orderNo']){

			//선물수령회원 플래그 처리 
			$this->db->bind_query('update es_member set giftUserFl = "y"  where memNo = ?', ['i', $member['memNo']] );


			//마일리지 지급
			if($giftCfg['benefitReceiverMileageFl']=='y'){

				$mileage = \App::load('\\Component\\Mileage\\Mileage');

				$msg = "[".$member['memNm']."]님 선물수신자 가입으로 마일리지 ".number_format($giftCfg['benefitReceiverMileage'])."이 지급되었습니다.  "; 

				$handleCd = $orderData['orderNo'];
				$handleNo =''; 

				$result = $mileage->setMemberMileage($member['memNo'], $giftCfg['benefitReceiverMileage'], '01005011', 'p',$handleCd, $handleNo, $msg);

				$result['mileage'] = $giftCfg['benefitReceiverMileage']; 

				$this->giftBenefitLog('receiver', 'mileage', $member['memNo'], $result) ; 				
			}

			//쿠폰 지급
			if($giftCfg['benefitReceiverCouponFl']=='y'){

	            $couponAdmin = \App::load('\\Component\\Coupon\\CouponAdmin');

				foreach($giftCfg['benefitReceiverCoupon'] as $couponNo){

					//쿠폰 발급 가능 여부
                    if($couponAdmin->checkCouponType($couponNo)) {

						$arrData = [];
						$arrData['couponNo'] = $couponNo;
						$arrData['couponSaveAdminId'] = "선물수신자 가입 혜택 쿠폰";
						$arrData['managerNo'] = 0;
						$arrData['memberCouponStartDate'] = $couponAdmin->getMemberCouponStartDate($couponNo);
						$arrData['memberCouponEndDate'] = $couponAdmin->getMemberCouponEndDate($couponNo);
						$arrData['memberCouponState'] = 'y';
						$arrData['memNo'] = $member['memNo'];

						// 저장
						$arrBind = $this->db->get_binding(DBTableField::tableMemberCoupon(), $arrData, 'insert', array_keys($arrData), ['memberCouponNo']);
						$this->db->set_insert_db(DB_MEMBER_COUPON, $arrBind['param'], $arrBind['bind'], 'y');
						$couponAdmin->setCouponMemberSaveCount($couponNo);
						
						$this->giftBenefitLog('receiver', 'coupon', $member['memNo'], $arrData) ; 				

					}
				}
			}

		}
	}







	//주문 구매확정시 마일리지 처리 및 giftCnt 증가 
	public function setGiftOrderBenefit($orderNo) {

		$giftCfg = gd_policy('dpx.giftCfg') ; 

		if($giftCfg['useGiftFl'] !='y') return false;

		$arrBind =[]; 
		$query = "select o.*, i.orderName, i.receiverName from es_order o left join es_orderInfo i on o.orderNo=i.orderNo where o.giftFl = 'y' and o.orderNo = ? and left(orderStatus,1) ='s' ";
		$this->db->bind_param_push($arrBind, 's', $orderNo);
		$orderData = $this->db->query_fetch($query, $arrBind, false);		

		if($orderData['memNo']){
			//선물횟수 추가 
			$this->db->bind_query('update es_member set giftCnt = giftCnt+1 , modDt = now()  where memNo = ?', ['i', $orderData['memNo']] );

			if($giftCfg['benefitSenderMileageFl']=='y'){
				//마일리지 지급 설정일경우 해당 마일리지 지급처리 
				$mileage = \App::load('\\Component\\Mileage\\Mileage');

				$msg = "[".$orderData['orderName']."]님 선물상품 구매확정으로 마일리지 ".number_format($giftCfg['benefitSenderMileage'])."이 지급되었습니다.  "; 

				$handleCd = $orderData['orderNo'];
				$handleNo =''; 

				$result = $mileage->setMemberMileage($orderData['memNo'], $giftCfg['benefitSenderMileage'], '01005011', 'p',$handleCd, $handleNo, $msg);

				$result['orderNo'] = $orderData['orderNo'];
				$result['mileage'] = $giftCfg['benefitSenderMileage']; 

				$this->giftBenefitLog('sender', 'mileage', $orderData['memNo'], $result) ; 
			}

			//선물 받기 확정시 주문 받기로 한 회원 전화번호로 가입된 회원들에게 마일리지 지급
			if($giftCfg['benefitReceiverMileageFl']=='y'){
				$orderNo2 = $orderData['orderNo']; 
				$query2 = "select receiverCellPhone, receiverName  from es_orderInfo where orderNo = $orderNo2";
				$receiverInfoData = $this->db->query_fetch($query2, '',false);


				$receiverCellPhone = str_replace('-', '', $receiverInfoData['receiverCellPhone']);
				$query2 = "select memNo from es_member where replace(cellPhone,'-','') = '$receiverCellPhone';";
				$memberPhoneData = $this->db->query_fetch($query2);


				$mileage = \App::load('\\Component\\Mileage\\Mileage');
				if(count($memberPhoneData) > 0){
					foreach($memberPhoneData as $value){
						$msg2 = "[".$receiverInfoData['receiverName']."]님 선물상품 구매확정으로 마일리지 ".number_format($giftCfg['benefitReceiverMileage'])."이 지급되었습니다."; //

						$result = $mileage->setMemberMileage($value['memNo'], $giftCfg['benefitReceiverMileage'], '01005011', 'p',$handleCd, $handleNo, $msg2);
					}
				}
			}
		}

		return true; 
	}







	public function giftBenefitLog($mode, $benefit, $memNo, $msg) {

		if(is_array($msg) || is_object($msg) ){
			ob_start();
			print_r( $msg );
			$ob_msg = ob_get_contents();
			ob_clean();
		}else{
			$ob_msg = $msg;
		}

		$arrBind =[]; 
		$setData['mode'] = $mode ; 
		$setData['benefit'] = $benefit ; 
		$setData['memNo'] = $memNo ; 
		$setData['result'] = $ob_msg ; 
		$arrBind = $this->db->get_binding(DBTableField::tableDpxGiftBenefitLog(), $setData, 'insert');
		$this->db->set_insert_db("dpx_giftBenefitLog", $arrBind['param'], $arrBind['bind'], 'y', false);

	}

	public function autoSendMsg() {
		$type = 'gift';
		$cronSno = $this->cronLog($type, $sno=0, $execCnt=0);

		$qry = "SELECT g.*, o.*, oi.receiverName, oi.orderName, oi.receiverCellPhone, oi.orderCellPhone  
				from dpx_gift as g
				left join es_order as o
					on o.orderNo = g.orderNo
				left join es_orderInfo as oi
					on o.orderNo = oi.orderNo
				WHERE g.giftReceiveFl ='' 
					and now() BETWEEN date_add(g.regDt, interval  +5 day) and date_add(g.regDt, interval  +6 day)
					and o.orderStatus = 'p2'
					and g.urgeMsg != 'y';
				" ;

		$selectData = $this->db->query_fetch($qry);

		
		foreach($selectData as $orderData){
			$this->sendMsg('urgeGift', $orderData);
			$this->sendMsg('senderUrgeGift', $orderData);
			$updateUrge = "update dpx_gift set urgeMsg ='y' where sno = $orderData[sno]";

			$this->db->query($updateUrge);
			$execCnt++;
		}

		$this->cronLog($type, $cronSno, $execCnt);
		
		return $execCnt;
	}

	public function cronLog($type, $sno=0, $execCnt=0) {
		$execDt = date('YmdH');

		if($sno){
			$qry=" update dpx_cronLog set execFl ='y' ,  execCnt = $execCnt where cronType='$type' and sno = $sno	";
			$this->db->query($qry); 


		}else{
			$qry=" insert dpx_cronLog set
						cronType='$type' , 
						execDt = '$execDt',
						ipAddr					= '".Request::server()->get('REMOTE_ADDR')."',
						referer					= '".Request::server()->get('HTTP_REFERER')."'
						";
			$this->db->query($qry); 
			return $this->db->insert_id(); 
		}
	}
}
