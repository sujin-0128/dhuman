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
use DateTime;
use Request;
use Session;
use App;

//use Component\Goods\Goods;

class Dpx2
{
    protected $db;

	protected $admin = '';

    public function __construct()
    {

        if (!is_object($this->db)) {
            $this->db = App::load('DB');
        }
	}

    /**
     * getMemberMileageDay
     * 회원 마일리지 분석 - 일별
     *
     * @param $searchDate
     * @param null $mallSno
     * @return array
     * @throws \Exception
     */
    public function getMemberMileage($searchDate, $monthFl)
    {
		$eDate = new DateTime($searchDate[1]);

		$searchDate[0] = substr($searchDate[0], 0, 4).'-'.substr($searchDate[0], 4, 2).'-'.substr($searchDate[0], 6, 2);
		$searchDate[1] = substr($searchDate[1], 0, 4).'-'.substr($searchDate[1], 4, 2).'-'.substr($searchDate[1], 6, 2).' 23:59:59';

		if(($eDate->format("Y-m") != date("Y-m"))) { // 회원 사용 가능 마일리지
			$qry = 'SELECT mileageUseSum FROM dpx_mileageMonth where years="'.$eDate->format("Y").'" and month="'.$eDate->format("m").'"';
			$getDataArr['memberMileageSum'] = $this->db->query_fetch($qry)[0]['mileageUseSum'];

			if($getDataArr['memberMileageSum'] == false) {
				$getDataArr['memberMileageSum'] = '데이터가 없습니다';
			}

		} else {
			$qry = 'SELECT sum(mileage) FROM es_member';
			$getDataArr['memberMileageSum'] = $this->db->query_fetch($qry)[0]['sum(mileage)'];
		}

		$qry = 'SELECT sum(mileage) as sum, count(mileage) as cnt FROM es_memberMileage WHERE reasonCd = "00000000" and regDt BETWEEN "'.$searchDate[0].'" and "'.$searchDate[1].'"';
		$getDataArr['memberHackOut'] = $this->db->query_fetch($qry)[0]; // 탈퇴 회원
		unset($qry);

		$qry = 'SELECT sum(mileage) as sum, count(mileage) as cnt FROM es_memberMileage WHERE (reasonCd = "010059999" or reasonCd = "010059997") and regDt BETWEEN "'.$searchDate[0].'" and "'.$searchDate[1].'"';
		$getDataArr['canUse'] = $this->db->query_fetch($qry)[0];  // 유효기간 만료 소멸
		unset($qry);

		$qry = 'SELECT sum(mileage) as sum, count(mileage) as cnt FROM es_memberMileage WHERE (reasonCd = "01005001" or (reasonCd = "01005011" and mileage like "-%")) and regDt BETWEEN "'.$searchDate[0].'" and "'.$searchDate[1].'"';
		$getDataArr['use'] = $this->db->query_fetch($qry)[0]; // 마일리지 사용 건수
		unset($qry);

		$qry = 'SELECT sum(mileage) as sum, count(mileage) as cnt FROM es_memberMileage WHERE (reasonCd = "01005002" or reasonCd = "01005005" or reasonCd = "01005009" or reasonCd = "01005504" or reasonCd = "010059996" or reasonCd = "01005501" or reasonCd = "01005003" or (reasonCd = "01005011" and mileage not like "-%")) and regDt BETWEEN "'.$searchDate[0].'" and "'.$searchDate[1].'"';
		$getDataArr['supply'] = $this->db->query_fetch($qry)[0]; // 마일리지 지급 건수
		unset($qry);

		$qry = 'SELECT sum(mileage) as sum FROM es_memberSleep';
		$getDataArr['sleepAll'] = $this->db->query_fetch($qry)[0]; // 휴면 회원 전체
		unset($qry);

		$qry = 'SELECT sum(mileage) as sum, count(mileage) as cnt FROM es_memberSleep WHERE regDt BETWEEN "'.$searchDate[0].'" and "'.$searchDate[1].'"';
		$getDataArr['sleep'] = $this->db->query_fetch($qry)[0]; // 휴면 회원 기간
		unset($qry);

        return $getDataArr;
    }

    /**
     * getMemberMileageDay
     * 회원 예치금 분석 - 일별
     *
     * @param $searchDate
     * @param null $mallSno
     * @return array
     * @throws \Exception
     */
    public function getMemberDeposit($searchDate, $monthFl)
    {
		$eDate = new DateTime($searchDate[1]);

		$searchDate[0] = substr($searchDate[0], 0, 4).'-'.substr($searchDate[0], 4, 2).'-'.substr($searchDate[0], 6, 2);
		$searchDate[1] = substr($searchDate[1], 0, 4).'-'.substr($searchDate[1], 4, 2).'-'.substr($searchDate[1], 6, 2).' 23:59:59';

		if(($eDate->format("Y-m") != date("Y-m"))) { // 회원 사용 가능 마일리지
			$qry = 'SELECT depositUseSum FROM dpx_depositMonth where years="'.$eDate->format("Y").'" and month="'.$eDate->format("m").'"';
			$getDataArr['memberDepositSum'] = $this->db->query_fetch($qry)[0]['depositUseSum'];

			if($getDataArr['memberDepositSum'] == false) {
				$getDataArr['memberDepositSum'] = '데이터가 없습니다';
			}

		} else {
			$qry = 'SELECT sum(deposit) FROM es_member';
			$getDataArr['memberDepositSum'] = $this->db->query_fetch($qry)[0]['sum(deposit)'];
		}

		$qry = 'SELECT sum(deposit) as sum, count(deposit) as cnt FROM es_memberDeposit WHERE reasonCd = "00000000" and regDt BETWEEN "'.$searchDate[0].'" and "'.$searchDate[1].'"';
		$getDataArr['memberHackOut'] = $this->db->query_fetch($qry)[0]; // 탈퇴 회원
		unset($qry);

		$qry = 'SELECT sum(deposit) as sum, count(deposit) as cnt FROM es_memberDeposit WHERE reasonCd != "00000000" and deposit < 0 and regDt BETWEEN "'.$searchDate[0].'" and "'.$searchDate[1].'"';
		$getDataArr['use'] = $this->db->query_fetch($qry)[0]; // 예치금 사용 건수
		unset($qry);

		$qry = 'SELECT sum(deposit) as sum, count(deposit) as cnt FROM es_memberDeposit WHERE reasonCd != "00000000" and deposit > 0 and regDt BETWEEN "'.$searchDate[0].'" and "'.$searchDate[1].'"';
		$getDataArr['supply'] = $this->db->query_fetch($qry)[0]; // 예치금 지급 건수
		unset($qry);

        return $getDataArr;
    }

    public function saveMileage()
    {
		$nowDate = new DateTime(date());

		$qry = 'SELECT * FROM dpx_mileageMonth WHERE years = DATE_FORMAT(now(), "%Y") AND month = DATE_FORMAT(now(), "%m")';

		$checkMonth = $this->db->query_fetch($qry);

		if(strtotime($nowDate->format("Y-m-t 23:55:00")) < strtotime(date("Y-m-d H:i:s")) && !$checkMonth) {
			$qry = 'INSERT INTO dpx_mileageMonth(years, month, mileageUseSum) VALUES (DATE_FORMAT(now(), "%Y"),DATE_FORMAT(now(), "%m"),(SELECT sum(mileage) FROM es_member))';

			$this->db->query_fetch($qry);

			return '마일리지 추가 성공';
		} else {
			return '마일리지 추가 실패';
		}
    }

    public function saveDeposit()
    {
		$nowDate = new DateTime(date());

		$qry = 'SELECT * FROM dpx_depositMonth WHERE years = DATE_FORMAT(now(), "%Y") AND month = DATE_FORMAT(now(), "%m")';

		$checkMonth = $this->db->query_fetch($qry);

		if(strtotime($nowDate->format("Y-m-t 23:55:00")) < strtotime(date("Y-m-d H:i:s")) && !$checkMonth) {
			$qry = 'INSERT INTO dpx_depositMonth(years, month, depositUseSum) VALUES (DATE_FORMAT(now(), "%Y"),DATE_FORMAT(now(), "%m"),(SELECT sum(deposit) FROM es_member))';

			$this->db->query_fetch($qry);

			return '마일리지 추가 성공';
		} else {
			return '마일리지 추가 실패';
		}
    }

	/* designpix 20220304 구매정보 동의 항목추가 */
	public function getAgreeInfo($cartInfo) {
		$agreeChk = 'n';

		foreach( $cartInfo[1] as $key=>$val) {
			foreach( $val as $key2=>$val2) {
				$qry = 'SELECT dpxAgreeInfoFl FROM es_goods WHERE goodsNo = "'.$val2['goodsNo'].'"';
				$getData = $this->db->query_fetch($qry);

				if($getData[0]['dpxAgreeInfoFl'] == 'y') {
					$agreeChk = 'y';
				}
			}
		}

		return $agreeChk;
	}
	/* designpix 20220304 구매정보 동의 항목추가 */

	public function dpxAgree($agreeInfo, $orderNo) {
		$qry = 'UPDATE es_order SET dpxAgreeInfo="'.$agreeInfo.'" WHERE orderNo = "'.$orderNo.'"';
		$this->db->query($qry);
		
	}

	public function getFoodBrand() { // 브랜드명
		$qry = 'select brand from dpx_foodCalcu group by brand order by brand';
		$getData = $this->db->query_fetch($qry);

		foreach($getData as $dataNo => $rows){
			$returnData[$dataNo] = $rows['brand'];
		}

        return $returnData;
	}

	public function getFoodNm($brandNm) { // 상품 이름
		$qry = 'SELECT foodNm FROM dpx_foodCalcu WHERE brand = "'.$brandNm.'" group by foodNm order by foodNm';
		$getData = $this->db->query_fetch($qry);

		$returnData = '<option id="select_third" value="plz_select">선택해주세요</option>';
		foreach($getData as $dataNo => $rows){
			$returnData .= '<option value="'.$rows['foodNm'].'">'.$rows['foodNm'].'</option>';
		}

        return $returnData;
	}

	public function getCalorie($petWeight, $brandNm, $foodNm, $mixFl) { // 칼로리\
		if($mixFl == 'mix') {
			$qry = 'select fcc.recommendCalorie, fcc.calorie as foodCalorie, fcc.foodType, fcc.foodAmount, fcl.calorie, fcl.shortText
					from dpx_foodCalcu as fcc
						left join dpx_foodCalorie as fcl
							on fcc.foodType = fcl.foodNm
					WHERE fcc.weight='.$petWeight.' and fcc.brand = "'.$brandNm.'" and fcc.foodNm =  "'.$foodNm.'"
					order by fcl.seqStatus';
			$getDataArr = $this->db->query_fetch($qry); 

			$returnData = null;
			$photoNum = 1;
			$HTTP_HOST = \Request::server()->get('HTTP_HOST');

			$returnData .= '<div class="fc_menu_name data_one">';
			$returnData .= '	<ul class="fnm_01">';
			$returnData .= '		<li class="mn_first">듀먼화식</li>';
			$returnData .= '		<li class="mn_second">1일 칼로리</li>';
			$returnData .= '		<li class="mn_third">화식량(g)</li>';
			$returnData .= '		<li class="mn_fourth">사료량(g)</li>';
			$returnData .= '	</ul>';
			$returnData .= '	<ul class="fnm_02">';

			foreach($getDataArr as $dataNo => $rows){
				$calorieG = round($rows['foodCalorie']/1000, 1);
				$foodAmount = ($rows['recommendCalorie']-$rows['calorie']*($rows['foodAmount']/100))/$calorieG;
				$foodRatio = $foodAmount/($foodAmount+$rows['foodAmount'])*100;

				$returnData .= '<li>';
				$returnData .= '	<img src="https://'.$HTTP_HOST.'/data/service/food_picture_0'.$photoNum.'.png">';
				$returnData .= '	<input type="text" value='.$rows['recommendCalorie'].' readonly>';
				$returnData .= '	<input type="text" value='.$rows['foodAmount'].' readonly>';
				$returnData .= '	<input type="text" value='.round($foodAmount).' readonly>';
				$returnData .= '</li>';
				$returnData .= '<li class="two_word"><p>'.$rows['shortText'].'</p></li>';

				$photoNum++;
			}
			$returnData .= '	</ul>';
			$returnData .= '</div>';
		
		} else if($mixFl == 'one') {
			$qry = 'select fcc.recommendCalorie, fcc.calorie as foodCalorie, fcc.foodAmount, fcl.shortText, fcl.calorie
					from dpx_foodCalcu as fcc
						left join dpx_foodCalorie as fcl
							on fcc.foodType = fcl.foodNm
					WHERE fcc.weight='.$petWeight.'
					group by fcc.foodType
					order by fcl.seqStatus';
			$getDataArr = $this->db->query_fetch($qry);

			$returnData = null;
			$photoNum = 1;
			$HTTP_HOST = \Request::server()->get('HTTP_HOST');

			$returnData .= '<div class="fc_menu_name data_mix">';
			$returnData .= '	<ul class="fnm_01">';
			$returnData .= '		<li class="mn_first">듀먼화식</li>';
			$returnData .= '		<li class="mn_second">1일 칼로리</li>';
			$returnData .= '		<li class="mn_third">화식량(g)</li>';
			$returnData .= '	</ul>';
			$returnData .= '	<ul class="fnm_02">';

			foreach($getDataArr as $dataNo => $rows){
				$calorieG = round($rows['foodCalorie']/1000, 1);
				$foodAmount = round($rows['recommendCalorie']/$rows['calorie']*100);
				$foodRatio = $foodAmount/($foodAmount+$rows['foodAmount'])*100;
				$returnData .= '<li>';
				$returnData .= '	<img src="https://'.$HTTP_HOST.'/data/service/food_picture_0'.$photoNum.'.png">';
				$returnData .= '	<input type="text" value='.$rows['recommendCalorie'].' readonly>';
				$returnData .= '	<input type="text" value='.$foodAmount.' readonly>';
				$returnData .= '</li>';
				$returnData .= '<li class="two_word"><p>'.$rows['shortText'].'</p></li>';

				$photoNum++;
			}
			$returnData .= '	</ul>';
			$returnData .= '</div>';
		} else {
			$returnData .= '<script>';
			$returnData .= '	alert("데이터를 다시 입력해주세요");';
			$returnData .= '	location.reload();';
			$returnData .= '</script>';
		}


        return $returnData;
	}

	/**
     * memEntryDt
     * 회원가입일 조회
     *
     * @param $memInfo['memNm'],$memInfo['memberFl'],$memInfo['email'],$memInfo['cellPhone']
     * @return array
     * @throws \Exception
     */
	
	public function memEntryDt($memNm,$memberFl,$email,$cellPhone) { // 브랜드명
		$qry = 'select entryDt from es_member where memNm= "'.$memNm.'" AND memberFl = "'.$memberFl.'" AND email= "'.$email.'" AND cellPhone ="'.$cellPhone.'"';
		$getData = $this->db->query_fetch($qry);
		$entryDt = $getData[0]['entryDt'];
		$date = substr($entryDt, 0, 10);
		
		return $date;
	}

	public function memGroupText($memNo){
		$config = gd_policy('member.group');
		$config = gd_htmlspecialchars_stripslashes($config);
		//gd_debug($config);

		switch ($config['calcPeriodBegin']) {
			case '-1w':
				$startDate = strtotime('-1 week');
				break;
			case '-2w':
				$startDate = strtotime('-2 week');
				break;
			case '-1m':
				$startDate = strtotime('-1 month');
				break;
			default:
				$startDate = strtotime('-1 day');
				break;
		}

		$calcPeriodMonth = $config['calcPeriodMonth'];
		$calcPeriodMonth = gd_isset($calcPeriodMonth, 1);
		$time = '-' . $calcPeriodMonth . ' month';
		$endDate = strtotime($time, $startDate);

		$endPeriod = $startDate;
		$startPeriod = $endDate;
		$startDt = date('Y-m-d', $startPeriod) . " 00:00:00";
		$endDt = date('Y-m-d', $endPeriod) . " 23:59:59";

		if(Request::server()->get('REMOTE_ADDR') == '220.118.145.49'){
			//$memNo = '1606473';
		}

		// 실결제 금액 체크 시작
		$memberInfo = $this->db->fetch("select * from es_member where memNo='{$memNo}'");
		$groups = \Component\Member\Group\Util::getGroupName();
		$memberInfo['groupNm'] = $groups[$memberInfo['groupSno']];

		$arrData = null;
		$arrData[] = "o.memNo = '{$memNo}'";
		$arrData[] = "og.finishDt between '{$startDt}' and '{$endDt}'"; // 구매확정 된 날짜 (회원등급 평가설정 기준)
		$arrData[] = "og.orderStatus = 's1'"; // 구매확정 (회원등급 평가설정 기준)

		$sql = "select og.* from es_order as o left join es_orderGoods as og on o.orderNo=og.orderNo where " . implode(" and ", $arrData);
		$data = $this->db->query_fetch($sql);
		$sumPrice = 0;
		foreach ($data as $val) {
			$sumPrice += ($val['realTaxSupplyGoodsPrice'] + $val['realTaxVatGoodsPrice'] + $val['realTaxFreeGoodsPrice']);
		}

		$orderNos = array_unique(array_column($data, 'orderNo'));
		$sumCount = count($orderNos);	//주문수

		if ($sumPrice % 1000 > 0) {
			$sumPrice = $sumPrice - ($sumPrice % 1000); // 업체 요청에 의해 100의자리까지 버림.
		}

		$getData['sumPrice'] = $sumPrice;

		// 다음 등급 체크 시작
		$memberGroupService = App::load('\\Component\\Member\\MemberGroup');
		$groupList = $memberGroupService->getGroupList();
		//gd_debug($groupList['data']);
		$groupData = null;
		$mileagePercent = null;
		$nextGroup = [
			1 => 2,
			2 => 5,
			5 => 27,
			27 => 27
		];
		foreach ($groupList['data'] as $val) {
			if ($val['sno'] != $nextGroup[$memberInfo['groupSno']]) continue;
			$arrData = null;
			$arrData['sno'] = $val['sno'];
			$arrData['groupNm'] = $val['groupNm'];
			$arrData['apprFigureOrderPriceMore'] = $val['apprFigureOrderPriceMore'] * 10000; // 단위 : 만원
			$arrData['apprFigureOrderCount'] = $val['apprFigureOrderCount'];
			$mileagePercent = $val['mileagePercent'];
			$groupData[] = $arrData;
		}
		$groupData = array_reverse($groupData);

		$groupName = null;
		$minPrice = 0;
		$minCount = 0;
		$percent = 100;
		foreach ($groupData as $key=>$val) {
			if($val['sno'] == $nextGroup[$memberInfo['groupSno']]) {
				$groupName = $val['groupNm'];
				if ($sumPrice < $val['apprFigureOrderPriceMore']) {
					$minPrice = $val['apprFigureOrderPriceMore'] - $sumPrice;
					$percent = round(($sumPrice / $val['apprFigureOrderPriceMore']) * 100);
				}

				if($sumCount < $val['apprFigureOrderCount']){
					$minCount = $val['apprFigureOrderCount'] - $sumCount;
				}
			}
		}

		$getData['groupName'] = $groupName;
		$getData['minPrice'] = $minPrice;
		$getData['percent'] = $percent;
		$getData['minCount'] = $minCount;
		$getData['mileagePercent'] = $mileagePercent;
		unset($data);

		return $getData;
	}
}
