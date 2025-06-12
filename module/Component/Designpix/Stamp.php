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
use Component\Member\Util\MemberUtil;
use Request;
use Session;
use App;

use Framework\Utility\DateTimeUtils;
use Component\Sms\Sms;
use Component\Sms\SmsMessage;
use Component\Sms\LmsMessage;
use Framework\Security\Otp;



class Stamp
{
    protected $db;

    public function __construct()
    {

        if (!is_object($this->db)) {
            $this->db = App::load('DB');
        }
		$this->memberAdmin = App::load('\\Component\\Member\\MemberAdmin');
	}

    public function cronSetStamp()
    {
		$stampCfg = gd_policy('dpx.stampBasic');

		if($stampCfg['stampUseFl'] != 'y') return false;

		$expiryFl = $stampCfg['expiryFl'];
		$expiryDays = $stampCfg['expiryDays'];
		$expiryBeforeDays = $stampCfg['expiryBeforeDays'];
		$smsFl = $stampCfg['smsFl'];

		if($stampCfg['expiryFl'] == 'n') return false; 

		$qry="SELECT * FROM dpx_stamp WHERE delFl = 'n' and (left(stampCode, 1)) = 'p' and sendExpirySms = '' and LEFT(DATE_ADD(regDt, INTERVAL ".($stampCfg['expiryDays']-$stampCfg['expiryBeforeDays']+1) ." DAY), 10) < now()" ;

		$res = $this->db->query_fetch($qry);

		foreach ($res as $k => $v) {
			$memberData = $this->getMemberData($v['memNo']);

			$timestamp = strtotime($v['regDt']." ".($stampCfg['expiryDays']+1)." days");
			$removeStampDay = date("Y-m-d", $timestamp);
			$msg = '[듀먼]
'.$memberData['memNm'].'님의 스탬프 '.$v['leftStamp'].'개가 '.$removeStampDay.' 소멸될 예정입니다.
이용에 참고 부탁드립니다.';

			$this->db->query("update dpx_stamp set sendExpirySms='y' where sno = '".$v['sno']."'");// 문자 보낸거 'y'로 변경

			if($smsFl == 'y') {
				$this->sendSms($memberData,$msg);
			}
		}

		$qry="SELECT * FROM dpx_stamp WHERE delFl = 'n' and (left(stampCode, 1)) = 'p' and LEFT(DATE_ADD(regDt, INTERVAL ".($stampCfg['expiryDays']-$stampCfg['expiryBeforeDays']+1) ." DAY), 10) < now()" ;

		$res = $this->db->query_fetch($qry);

		foreach ($res as $k => $v) {
			$this->setRemoveStamp( $v['memNo'], null, $v['leftStamp'], 'm3');
		}

		return true;
    }

	//리스트데이타 가져오기
    public function getList( $req = null, $isPaging = true, $sort = 's.sno desc', $hasStatics = true, $listCount = 10)
    {

        $getData = $arrBind = $search = $arrWhere = $checked = $selected = null;
        gd_isset($req['page'], 1);

		$listCount = $req['pageNum']>0?$req['pageNum']:$listCount;
        $offset = ($req['page'] - 1) * ($listCount);
        $data = $this->selectList($req, $isPaging, $offset, $listCount, $sort);

        $searchCnt = $this->selectCount($req, $req['searchData']);

        if ($isPaging) {
            $totalCnt = $this->selectCount(null, $req['searchData']);

            gd_isset($req['page'], 1);
            $this->page = App::load('\\Component\\Page\\Page', $req['page'], $searchCnt, $totalCnt, $listCount);
            $this->page->setPage();
            $this->page->setUrl(http_build_query ($req));
            $getData['pagination'] = $this->page->getPage();
            $getData['cnt']['total'] = $totalCnt;
            $getData['cnt']['search'] = $searchCnt;
        }

		$stampReasons = $this->getStampReasons();
		$rewardType = $this->getRewardType();

		$search['keyword'] = $req['keyword'];
		$search['regDt'] = $req['regDt'];
		$search['groupSno'] = $req['groupSno'];
		$search['stampCode'] = $req['stampCode'];
		$search['rewardType'] = $req['rewardType'];

		$checked['mode'][$req['mode']] = 'checked="checked"';
		//--- 각 데이터 배열화

		$getData['listNo'] = $searchCnt - $offset;
        $getData['data'] = $data;
		$getData['search'] = $search;
		$getData['checked'] = $checked;
		$getData['groups'] = gd_member_groups();
		$getData['stampReasons'] = $stampReasons;
		$getData['rewardType'] = $rewardType;

        return $getData;
    }


	public function selectList($req = null, $isPaging, $offset = 0, $limit = null, $sort = 's.sno desc')
    {
		$stampCfg = gd_policy('dpx.stampBasic');

		$addQry=" where 1=1";

		if(count($req))	$addQry.= ($this->searchAdd($req))?" and ".$this->searchAdd($req):"" ;  

		$orderby = " order by ".$sort; 

		if($isPaging)	$addLimit = " limit {$offset},{$limit} ";

		if($req['searchData'] == 'stamp') {
			$select = "SELECT m.memId, m.memNm, mg.groupNm, s.*, LEFT(DATE_ADD(s.regDt, INTERVAL ". ($stampCfg['expiryDays']+1) ." DAY), 10) as delDt
				FROM dpx_stamp as s";
		} else if($req['searchData'] == 'rewardLog') {
			$select = "SELECT m.memId, m.memNm, mg.groupNm, s.*
				FROM dpx_rewardLog as s";
		}

		$qry = $select." left join es_member as m
						on m.memNo = s.memNo
					left join es_memberGroup as mg
						on m.groupSno = mg.sno
				 ". $addQry.$orderby.$addLimit ;

		$res = $this->db->query_fetch($qry);

		return $res; 
    }

	public function searchAdd($req){

		$result="";

		if(count($req[regDt])){

			if($req[regDt][0]){
				$data[]=  "s.regDt >= '".$req[regDt][0]." 00:00:00' ";
			}
			if($req[regDt][1]){
				$data[]=  "s.regDt <= '".$req[regDt][1]." 23:59:59' ";
			}
		}

		if($req[keyword]){
			if($req[searchField]){
				$data[]=  $req[searchField] ." = '".$req[keyword]."'";
			}else{
				$data[]=  " ( m.memId like \"%".$req[keyword]."%\" or  m.memNm like \"%".$req[keyword]."%\" or  m.cellPhone like \"%".$req[keyword]."%\" )";
			}
		}

		if($req[groupSno]){
			$data[]=  " mg.sno = $req[groupSno]";
		}

		if($req[stampCode]){
			$data[]=  " s.stampCode = '$req[stampCode]'";
		}

		if($req[mode]){
			if($req[mode] != 'all') {
				$data[]=  " ( left(s.stampCode, 1)) = '".$req[mode]."'";
			}
		}

		if($req[rewardType]){
			$data[]=  " s.rewardType = '".$req[rewardType]."'";
		}

		if( count($data)){
			$result = implode(" and ",$data);
		}

		return $result; 
	}

	//스탬프적립
	public function setStamp($orderNo,$orderGoodsArr){
		//Order->statusChangeCodeS 실행. 구매확정 변경시에 실행
		$stampCfg = gd_policy('dpx.stampBasic');
		
		if($stampCfg['stampUseFl'] != 'y') return true;

		$arrBind = [];
		// 230801 듀먼 요청 23-08-01 이전에 생성된 주문 건에만 적용
		/*
		$qry="select 
					o.memNo, o.settlePrice,  
					m.memNm, m.memId
					from es_order o 
					left join es_member m on o.memNo = m.memNo  
					where  o.orderNo = ? and o.dpxStampFl='n' " ;
		*/
		$qry="select 
					o.memNo, o.settlePrice,  
					m.memNm, m.memId
					from es_order o 
					left join es_member m on o.memNo = m.memNo  
					where  o.orderNo = ? and o.dpxStampFl='n' and o.regDt < '2023-08-01'" ;


		$this->db->bind_param_push($arrBind, 'i', $orderNo);
		
		$res = $this->db->query_fetch($qry, $arrBind)[0];

		if(!$res['memNo']) {
			return true;
		}

		$goodsPrice = $res['settlePrice'];
		$stampCnt = floor($goodsPrice / $stampCfg['stampPerPrice']);

		if($stampCnt == 0) return true; 

		$this->db->query("update es_order set dpxStampFl='y' where orderNo = '".$orderNo."'");

		//회원 스탬프 저장
		$this->saveStamp($res['memNo'],$stampCnt, $orderNo, $orderGoodsNo, 'p1');

		//회원정보
		$memberData = $this->getMemberData($res['memNo']);

		$this->paymentStampBenefit($memberData, $orderNo); //초과 스탬프만큼 혜택 지급
	}

	//리워드지급을위한 스탬프 갯수찾기
	public function getStampCnt($memNo){
		$qry = 'select * from dpx_stamp where memNo = '.$memNo. ' order by sno desc limit 0,1';

		$res = $this->db->fetch($qry);
		return $res;
	}

	//리워드지급 여부
	public function getRewardCnt($memNo){
		$qry = 'select count(*) as cnt from dpx_rewardLog where memNo = '.$memNo;

		$res = $this->db->fetch($qry)['cnt'];
		return $res;
	}

	//회원 스탬프저장
	public function saveStamp($memNo,$stampCnt,$orderNo,$orderGoodsNo,$stampCode,$content){
		$stampData = $this->getStampCnt($memNo);

		if(!$stampData){
			$beforeStamp = 0;
			$afterStamp = $stampCnt;
		}else{
			$beforeStamp = $stampData['afterStamp'];
			$afterStamp = $stampCnt + $stampData['afterStamp'];
		}

		$qry2="insert dpx_stamp set 
				memNo			= $memNo, 
				beforeStamp		= $beforeStamp,
				stamp			= $stampCnt,
				leftStamp		= $stampCnt,
				afterStamp		= $afterStamp,
				orderNo			= '$orderNo',
				stampCode		= '$stampCode',
				content			= '$content'
				";

		$this->db->query($qry2);
		unset($qry2);
	}

	//문자 전송
	public function sendSms($memberData,$msg) {
		//문자수신동의한 회원만 문자발송
		if($memberData['smsFl'] =='y'){
			if(mb_strlen($msg, 'euc-kr')>90){
			  $smsFl = 'lms';
			}else{
			  $smsFl = 'sms';
			}
			
			$smsSet = []; 

			$smsSet['sendFl']= $smsFl; 
			$smsSet['receiverType']="direct";
			$smsSet['smsSendType']="now";
			$smsSet['smsContents']= $msg;
			$smsSet['directReceiverNumbers'][] = $memberData['cellPhone'];
			$smsSet['password']= \App::load(\Component\Sms\SmsUtil::class)->getPassword() ;

			$smsAdmin = \App::load('Component\\Sms\\SmsAdmin');
			$iSmsPoint = Sms::getPoint();

			if($iSmsPoint>0){
				$res = $smsAdmin->sendSms($smsSet);
			}

			return $res;
		}
	}

    public function selectCount($req = null, $selectTable)
    {
		$addJoin = " left join es_member as m
						on m.memNo = s.memNo
					left join es_memberGroup as mg
						on m.groupSno = mg.sno";

		$addQry = " where 1=1";

		if(count($req))	$addQry.= ($this->searchAdd($req))?" and ".$this->searchAdd($req):"" ;  

        $query = $this->db->query_complete();
        $strSQL = 'SELECT count(*) as cnt FROM dpx_'.$selectTable.' as s '.$addJoin.$addQry;

        $data = $this->db->query_fetch($strSQL);

        return $data[0]['cnt'];
	}

	//리워드지급을위한 스탬프 갯수찾기
	public function getDelStamp($memNo){
		$qry = 'select * from dpx_stamp where memNo = '.$memNo. ' and delFl = "n" and (left(stampCode, 1) = "p") order by sno asc';
		$res = $this->db->query_fetch($qry);
		return $res;
	}

	public function paymentStampBenefit($memberData, $orderNo){
		$stampCfg = gd_policy('dpx.stampBasic');
		$mileage = App::load('\\Component\\Mileage\\Mileage');
		$coupon = App::load('\\Component\\Coupon\\CouponAdmin');
		
		$rewardCnt = $stampCfg['rewardStandard'];
		$getStampData = $this->getStampCnt($memberData['memNo']);
		$rewardCount = $getStampData['afterStamp']/$stampCfg['rewardStandard'];
		$rewardAll = $rewardCnt * floor($rewardCount);

		$rewardCountChk = 1;

		if($rewardCount >= 1) {
			$this->setRemoveStamp($memberData['memNo'], $orderNo, $rewardAll, 'm1');

			while ($rewardCount >= $rewardCountChk) {
				//리워드지급
				if($stampCfg['rewardMethod'] == 'coupon'){
					$memNoArr[0] = $memberData['memNo'];
					$msg ='[듀먼]
★축★달성!
'.$memberData['memNm'].'님의 스탬프 리워드가 쿠폰함으로 지급되었습니다.
※지급된 쿠폰은 [마이페이지]-[쿠폰함]에서 확인 가능합니다.
https://www.dhuman.co.kr/mypage/coupon.php';
					
					$rewardResult = $coupon->saveMemberDpxStampCoupon($memNoArr);
					if($rewardResult) {
						$this->saveRewardLog($memberData['memNo'], $orderNo, 'coupon');
					}
				} else if($stampCfg['rewardMethod'] == 'mileage') {
					$handleCd = $orderNo;
					$rewardMileage =  $stampCfg['mileage'];
					$msg ='[듀먼]
★축★달성!
'.$memberData['memNm'].'님의 스탬프 리워드 '.$rewardMileage.'원이 지급되었습니다.
※지급된 마일리지는 [마이페이지]에서 확인 가능합니다.';

					$rewardResult = $mileage->setMemberMileage($memberData['memNo'], $rewardMileage, '01005011', 'p', $handleCd, null, $msg);
					if($rewardResult) {
						$this->saveRewardLog($memberData['memNo'], $orderNo, 'mileage');
					}
				}
				//쿠폰/마일리지 지급 테이블 insert

				$rewardCountChk++;
			}

			//문자발송
			if($stampCfg['stampSmsFl'] == 'y' && $rewardResult){
				$nowHour = (int)date("H");

				if($nowHour < 10 || $nowHour >= 21) { // 오후 9시부터 오전 10시까지 문자 보내질 경우 저장
					$this->saveReservationSms($memberData['memNo'],$msg);
				} else {
					$this->sendSms($memberData,$msg);
				}
			}
		}
	}

	public function setRemoveStamp($memNo, $orderNo, $removeStamp, $stampCode, $content){
		//초과분 차감
		$stampCfg = gd_policy('dpx.stampBasic');
		$getStampData = $this->getStampCnt($memNo);
		
		$oriRemoveStamp = $removeStamp;
		$rewardCnt = $stampCfg['rewardStandard'];
		$totalStamp = $totalStampInsert = $getStampData['afterStamp'];
		$removeCount = $removeStamp/$rewardCnt;
		$delArr = $this->getDelStamp($memNo);
		$removeCountChk = 0;

		foreach ($delArr as $v) {
			if($removeStamp >= $v['leftStamp']) {
				$qry = 'UPDATE dpx_stamp SET leftStamp = 0, delFl = "y" WHERE sno = "'.$v['sno'].'"';
				$this->db->fetch($qry);

				$removeStamp -= $v['leftStamp'];
			} else {
				$qry = 'UPDATE dpx_stamp SET leftStamp = leftStamp - '.$removeStamp.' WHERE sno = "'.$v['sno'].'"';

				$this->db->fetch($qry);
			}
		}

		$beforeStamp = $afterStamp = $totalStampInsert;

		while ($removeCount > $removeCountChk) {
			$beforeStamp = $afterStamp;

			if($afterStamp > 0) {
				if($beforeStamp >= $rewardCnt) {
					$removeCnt = $rewardCnt;
				} else {
					if($oriRemoveStamp >= $beforeStamp) {
						$removeCnt = $beforeStamp;
					} else {
						$removeCnt = $oriRemoveStamp;
					}
				}

				$afterStamp = $beforeStamp - $removeCnt;

				$qry="insert dpx_stamp set 
						memNo			= $memNo, 
						beforeStamp		= $beforeStamp,
						stamp			= '-$removeCnt',
						afterStamp		= $afterStamp,
						orderNo			= '$orderNo',
						stampCode		= '$stampCode',
						content			= '$content'
						";

				$this->db->fetch($qry);
			}
			$removeCountChk++;
		}
	}

	//리워드 지급 저장
	public function saveRewardLog($memNo, $orderNo, $rewardType){
		$stampCfg = gd_policy('dpx.stampBasic');
		$rewardCnt = $stampCfg['rewardStandard'];
		$couponSno = $stampCfg['couponSno'];

		switch ($rewardType) {
		  case 'coupon':
			$qry = 'select couponNm from es_coupon where couponNo = '.$couponSno;
			$res = $this->db->fetch($qry);

			$rewardDetail = $res['couponNm'];
			break;
		  case 'mileage':
			$rewardDetail = $stampCfg['mileage'];

			break;
		}

		$qry="insert dpx_rewardLog set 
				memNo			= $memNo, 
				useStamp		= $rewardCnt,
				rewardType		= '$rewardType',
				orderNo			= '$orderNo',
				couponNo		= '$couponSno',
				rewardDetail	= '$rewardDetail'
				";

		$this->db->fetch($qry);
	}

	public function rejectStamp($orderNo){ //환불 시 차감
		$qry="select 
					s.sno, m.memNo, s.stamp
					from es_order o 
					left join es_member m on o.memNo = m.memNo  
					left join dpx_stamp s on o.orderNo = s.orderNo
					where  o.orderNo = ? and o.dpxStampFl='y' and s.stampCode = 'p1'" ; 

		$this->db->bind_param_push($arrBind, 'i', $orderNo);
		
		$res = end($this->db->query_fetch($qry, $arrBind));

		$this->setRemoveStamp($res['memNo'], $orderNo, $res['stamp'], 'm2');
	}

	public function getStampReasons(){
		$return['p1'] = '상품 구매로 지급';
		$return['p2'] = '관리자 지급';
		$return['m1'] = '리워드 지급으로 차감';
		$return['m2'] = '환불 차감';
		$return['m3'] = '유효기간 만료 차감';
		$return['m4'] = '관리자 수동 차감';

		return $return;
	}

	public function getRewardType(){
		$return['coupon'] = '쿠폰';
		$return['mileage'] = '마일리지';

		return $return;
	}

	//스탬프 지급
	public function addStamp($arrData){
        $arrBind = $search = $arrWhere = [];

        $arrWhere[] = "find_in_set(memNo,?)";
        if (is_array($arrData['chk'])) {
            $this->db->bind_param_push($arrBind, 's', implode(',', $arrData['chk']));
        } else {
            $this->db->bind_param_push($arrBind, 's', $arrData['chk']);
        }

        return $this->getResultByAddMileage($arrData, $arrWhere, $arrBind);
	}

	// 스탬프 일괄 지급
	public function allAddStamp($arrData, $searchJson){
        $searchJson = json_decode($searchJson);
        $searchJson = ArrayUtils::objectToArray($searchJson);
        $tmp = $this->memberAdmin->searchMemberWhere($searchJson);
        $arrBind = $tmp['arrBind'];
        $arrWhere = $tmp['arrWhere'];

        return $this->getResultByAddMileage($arrData, $arrWhere, $arrBind);
	}

	// 스탬프 제거
	public function removeStamp($arrData){
		$db = App::load('DB');

        $arrBind = $search = $arrWhere = [];

        $arrWhere[] = "find_in_set(memNo,?)";
        if (is_array($arrData['chk'])) {
            $db->bind_param_push($arrBind, 's', implode(',', $arrData['chk']));
        } else {
            $db->bind_param_push($arrBind, 's', $arrData['chk']);
        }

        return $this->getResultByRemoveMileage($arrData, $arrWhere, $arrBind);
	}

	// 스탬프 일괄 제거
	public function allRemoveStamp($arrData, $searchJson){

        $searchJson = json_decode($searchJson);
        $searchJson = ArrayUtils::objectToArray($searchJson);
        $tmp = $this->memberAdmin->searchMemberWhere($searchJson);
        $arrBind = $tmp['arrBind'];
        $arrWhere = $tmp['arrWhere'];

        return $this->getResultByRemoveMileage($arrData, $arrWhere, $arrBind);
	}

	//스탬프 지급 함수
    public function getResultByAddMileage($arrData, $arrWhere, $arrBind)
    {
        $logger = \App::getInstance('logger');
        $where = (count($arrWhere) ? ' WHERE ' . implode(' and ', $arrWhere) : '');
        $strSQL = 'SELECT memId, memNo, memNm, mileage, cellPhone, email, maillingFl, smsFl FROM ' . DB_MEMBER . ' as m ' . $where;
        $data = $this->db->query_fetch($strSQL, (empty($arrBind) === false ? $arrBind : null));

        $stampCnt = $arrData['stampValue'];
        $reasonCd = $arrData['reasonCd'];

        if (isset($data) && is_array($data)) {
            foreach ($data as $val) {
				$this->saveStamp($val['memNo'], $stampCnt, null, null, 'p2', $arrData['content']);
				
				if($arrData['sendSmsFl'] == 'sms') {
					$msg = '[듀먼]
'.$val['memNm'].'님께 스탬프 '.$stampCnt.'개가 지급되었습니다.';
					// 230801 듀먼 요청 수동 지급 시 문자 보내기 제한
					//$this->sendSms($val,$msg);
				}
				$this->paymentStampBenefit($val, null); //초과 스탬프만큼 혜택 지급
            }

            return true;
        }

        return false;
    }

	//스탬프 제거 함수
    public function getResultByRemoveMileage($arrData, $arrWhere, $arrBind)
    {
        $logger = \App::getInstance('logger');
        $where = (count($arrWhere) ? ' WHERE ' . implode(' and ', $arrWhere) : '');
        $strSQL = 'SELECT memNo, memId, memNm, mileage, cellPhone, email, maillingFl, smsFl FROM ' . DB_MEMBER . ' as m ' . $where;
        $data = $this->db->query_fetch($strSQL, (empty($arrBind) === false ? $arrBind : null));

        $stampCnt = $arrData['stampValue'];
        $reasonCd = $arrData['reasonCd'];

        if (isset($data) && is_array($data)) {
            foreach ($data as $val) {
				$this->setRemoveStamp($val['memNo'], null, $stampCnt, 'm4', $arrData['content']);
				if($arrData['sendSmsFl'] == 'sms') {
					$msg = '[듀먼]
'.$val['memNm'].'님께 스탬프 '.$stampCnt.'개가 차감되었습니다.';
					// 230801 듀먼 요청 수동 차감 시 문자 보내기 제한
					//$this->sendSms($val,$msg); 
				}			
			}
            return true;
        }
        return false;
    }

	
    public function addStampData($dataArr)
    {
		foreach ($dataArr as $k => $v) {
			$stampCnt = $this->getStampCnt($v['memNo'])['afterStamp'];

			if(!$stampCnt){
				$stampCnt = 0;
			}
			$dataArr[$k]['userStamp'] = $stampCnt;
		}

        return $dataArr;
    }

	// 회원 정보 검색
    public function getMemberData($memberNo)
    {
		$memQry = 'SELECT memNo,memId,memNm,cellPhone,smsFl FROM es_member where sleepFl="n" AND memNo ='.$memberNo;
		$memberData = $this->db->fetch($memQry);
		
		return $memberData;
    }

	// 마이페이지 스탬프 이미지 출력
	public function getMypageStamp($memNo, $display){
		$stampCfg = gd_policy('dpx.stampBasic');

		if($stampCfg['stampUseFl'] != 'y') return false;

		$stampCnt = $this->getStampDate($memNo);
		$getRewardCnt = $this->getRewardCnt($memNo);

		if($display == 'mobile') {
			$mobileMargin = ' style="margin-top: 20px;"';

		}

		$stampView .= '<div class="mypage_stamp_list"'.$mobileMargin.'>';
		$stampView .= '	<div class="stamp_list_wrap">';
		$stampView .= '		<img class="stamp_list_main_img" src="/data/mypage/stamp_name.png">';


		if(!$stampCnt) {
			$stampView .= '		<ul class="main_stamp">';
				for($i=0; $i<$stampCfg['rewardStandard']; $i++) {

					if($i+1 ==$stampCfg['rewardStandard']) {
						$stampBack = '	<img class="stamp_null"  src="/data/mypage/stamp_hollow_give.png">';
					} else {
						$stampBack = '	<img class="stamp_null"  src="/data/mypage/stamp_hollow_new.png">';
					}
					
					$stampView .= '	<li>';
					$stampView .= $stampBack;
					$stampView .= '	</li>';
					if(($i+1)%5 == 0 && $i+1 != $stampCfg['rewardStandard']) {
						$stampView .= '		</ul>';
						$stampView .= '		<ul class="ten_stamp_use">';
					}
					unset($stampBack);
				}
			$stampView .= '		</ul>';
		} else {
			$stampView .= '		<ul class="main_stamp stamp_01">';
			for($i=0; $i<$stampCfg['rewardStandard']; $i++) {
				if($i+1 == $stampCfg['rewardStandard']) {
					$stampBack = '	<img class="stamp_null"  src="/data/mypage/stamp_hollow_give.png">';
				} else {
					$stampBack = '	<img class="stamp_null"  src="/data/mypage/stamp_hollow_new.png">';
				}

				if(count($stampCnt)>$i) {
					if($i+1 == $stampCfg['rewardStandard']) {
						$stampText = '<p class="day_collector"><img class="stamp_date" src="/data/mypage/present-txt_gif.gif" alt=""><span class="blind">증정 칸</span></p>';
					} else {
						if($i+1 == count($stampCnt)) {
							if($i+2 == $stampCfg['rewardStandard']) {
								$stampImg = '<img class="stamp_check" src="/data/mypage/stamp_clear.gif">';
							} else {
								$stampImg = '<img class="stamp_check" src="/data/mypage/stamp_gif.gif">';
							}
						} else {
							$stampImg = '<img class="stamp_check" src="/data/mypage/stamp_png.png">';
						}
						$stampText = '<p class="day_collector"><img class="stamp_date" src="/data/mypage/stamp_collect-day.png" alt=""><span class="blind">날짜 칸</span></p><p class="collector_day">'.$stampCnt[$i].'</p>';
					}
				}

				$stampView .= '	<li>';
				$stampView .= $stampBack;
				$stampView .= $stampImg;
				$stampView .= $stampText;
				$stampView .= '	</li>';

				if(($i+1)%5 == 0 && $i+1 != $stampCfg['rewardStandard']) {
					$stampView .= '		</ul>';
					$stampView .= '		<ul class="ten_stamp_use stamp_02">';
				}

				unset($stampBack);
				unset($stampImg);
				unset($stampText);
			}
			$stampView .= '		</ul>';
		}

		$stampView .= '	</div>';
		$stampView .= '	<a href="/mypage/stamp_list.php" class="stamp_list_btn" style="position: relative; z-index: 10;"><img src="/data/mypage/stamp_btn.png"><span class="blind">스탬프 현황 버튼</span></a>';
		$stampView .= '</div>';

		return $stampView;
	}

	// 마이페이지 스탬프 날짜 출력
	public function getStampDate($memNo){
		$qry = 'select * from dpx_stamp where memNo = '.$memNo.' and leftStamp != 0 order by sno asc';

		$res = $this->db->query_fetch($qry);
		$dataNo = 0;

		foreach ($res as $k => $v) {
			$rowNo = 0;

			while($rowNo < $v['leftStamp']){
				$return[$dataNo] = substr($v['regDt'], 5,2).'/'.substr($v['regDt'], 8,2);
				$rowNo++;
				$dataNo++;
			}
		}
		return $return;
	}

	// 마이페이지 리스트 검색
	public function selectMypageList($date, $memNo, $isPaging, $offset = 0, $limit = null, $sort = 's.sno desc')
    {
		$stampCfg = gd_policy('dpx.stampBasic');

		$addQry= " where s.regDt >= '".$date[0]." 00:00:00' and s.regDt <= '".$date[1]." 23:59:59'";
		$addQry.= " and s.memNo = ".$memNo;

		$orderby = " order by ".$sort; 

		if($isPaging)	$addLimit = " limit {$offset},{$limit} ";

		$select = "SELECT m.memId, m.memNm, mg.groupNm, s.*, LEFT(DATE_ADD(s.regDt, INTERVAL ". ($stampCfg['expiryDays']+1) ." DAY), 10) as delDt
				FROM dpx_stamp as s";

		$qry = $select." left join es_member as m
						on m.memNo = s.memNo
					left join es_memberGroup as mg
						on m.groupSno = mg.sno
				 ".$addQry.$orderby.$addLimit ;

		$res = $this->db->query_fetch($qry);

		$qry = "SELECT COUNT(*) as cnt FROM dpx_stamp as s
					left join es_member as m
						on m.memNo = s.memNo
					left join es_memberGroup as mg
						on m.groupSno = mg.sno
				 ".$addQry ;

		$resTotal = $this->db->fetch($qry);

		$result['data'] = $res;
		$result['total'] = $resTotal['cnt'];
		$result['cfg'] = $stampCfg;

		return $result; 
    }

	// 문자 예약 저장
	public function saveReservationSms($memNo, $msg)
    {
		$qry="insert dpx_stampReservationSms set 
				memNo			= $memNo, 
				msg				= '$msg'";

		$this->db->query($qry);
    }

	// sms 전일 예약 문자 전송
	public function sendStampReservationSms()
    {
		$stampCfg = gd_policy('dpx.stampBasic');

		$smsFl = $stampCfg['smsFl'];
		$date = date('Y-m-d H:i:s');

		$qry="select * from dpx_stampReservationSms where sendFl = 'n'";

		$dataArr = $this->db->query($qry);

		foreach ($dataArr as $k => $v) {
			$memberData = $this->getMemberData($v['memNo']);
			if($smsFl == 'y') {
				$result = $this->sendSms($memberData,$v['msg']);
			}
			$this->db->query("update dpx_stampReservationSms set sendFl='y', smsSendTime = '$date' where sno = '".$v['sno']."'");// 문자 보낸거 'y'로 변경
			unset($memberData);
		}
	}
}
