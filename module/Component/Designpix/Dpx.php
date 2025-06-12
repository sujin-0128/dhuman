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
use Framework\Debug\Exception\AlertBackException;
use Framework\Debug\Exception\LayerException;
use Exception;
use DB;
use Request;
use Session;
use App;

//use Component\Goods\Goods;

class Dpx
{
	protected $db;

	protected $admin = '';

	public function __construct()
	{

		if (!is_object($this->db)) {
			$this->db = App::load('DB');
		}
	}

	//get goodsSearchWord
	public function getGoodsSearchWord($goodsNo)
	{
		$qry = "SELECT goodsSearchWord FROM es_goods where goodsNo = '$goodsNo' ";
		$res = $this->db->fetch($qry);
		return $res;
	}

	//관리자 대표상품 지정 timesale_ps
	public function timeSaleSelect($goodsNo)
	{

		$qry = "UPDATE es_timeSale set repre = '$goodsNo' where sno = 1 ";

		$this->db->query($qry);
	}

	//index페이지 대표상품 노출
	public function getRepreData($goodsNo)
	{

		$qry = "SELECT * FROM es_goods g LEFT JOIN es_goodsImage gi ON g.goodsNo = gi.goodsNo where g.goodsNo = '$goodsNo' && gi.imageKind = 'add5' ";
		$res = $this->db->fetch($qry);

		return $res;
	}

	//plusReview goodsPt 
	public function getPlusReviewGoodsPt($goodsNo)
	{
		$qry = "SELECT ROUND(AVG(goodsPt),1) FROM es_plusReviewArticle where goodsNo = '$goodsNo' ";
		$res = $this->db->fetch($qry);
		return $res['ROUND(AVG(goodsPt),1)'];
	}

	//plusReview goodsPt Counting
	public function getPlusReviewGoodsPtCount($goodsNo)
	{
		$qry = "SELECT COUNT(goodsPt) FROM es_plusReviewArticle where goodsNo = '$goodsNo' ";
		$res = $this->db->fetch($qry);
		return $res['COUNT(goodsPt)'];
	}
	public function setCateNmLink($cateCd, $cateType, $mPc)
	{

		if ($cateType == 'cate') {
			$dbName = 'es_categoryGoods';
		} else {
			$dbName = 'es_categoryBrand';
		}

		$cnt = strlen($cateCd);
		$num = $cnt / 3;
		$nowCateArr = array();
		for ($i = 1; $i <= $num; $i++) {
			$cutWord = 3;
			$dpxCateCd = substr($cateCd, 0, $cutWord);
			$qry = "select * from " . $dbName . " where cateCd='$dpxCateCd' and cateDisplayMobileFl='y'";
			$nowCateArr[$dpxCateCd] = $this->db->fetch($qry);
		}

		$dpxCateCd2 = substr($cateCd, 0, 3);
		if ($mPc == 'p') {
			$cateDisplay = 'cateDisplayFl';
		} else {
			$cateDisplay = 'cateDisplayMobileFl';
		}

		$titleWord = array();
		foreach ($nowCateArr as $cateCd => $val) {
			$titleWord[] = $val[cateNm];
		}

		foreach ($titleWord as $k => $v) {

			$word .= $v;
		}
		return $word;
	}
	public function setCateNmLink2($cateCd, $cateType, $mPc)
	{
		if ($cateType == 'cate') {
			$dbName = 'es_categoryGoods';
		} else {
			$dbName = 'es_categoryBrand';
		}

		$cnt = strlen($cateCd);
		$num = $cnt / 3;
		$nowCateArr = array();
		for ($i = 1; $i <= $num; $i++) {
			$cutWord = $i * 3;
			$dpxCateCd = substr($cateCd, 0, $cutWord);
			$qry = "select * from $dbName where LENGTH(cateCd)='3'and cateDisplayMobileFl='y'";
			$nowCateArr = $this->db->query_fetch($qry);
		}

		$titleWord = array();

		foreach ($nowCateArr as $k => $val) {

			//$titleWord[]='<a href="../goods/goods_list.php?cateCd='.$cateCd.'">'.$val[cateNm].'</a>';
			if ($val[cateCd] == $cateCd) {
				$titleWord[] = '<a href="goods_list.php?cateCd=' . $val[cateCd] . '" class="ddc_sel">' . $val[cateNm] . '</a>';
			} else {
				$titleWord[] = '<a href="goods_list.php?cateCd=' . $val[cateCd] . '">' . $val[cateNm] . '</a>';
			}
		}

		foreach ($titleWord as $k => $v) {
			$word .= $v;
		}
		return $word;
	}


	public function rankData()
	{

		$today = date("Ymd");

		$qry = "SELECT goodsNo, orderCnt, (SELECT COUNT(og.goodsNo) FROM es_order o
				LEFT JOIN es_orderGoods og ON o.orderNo = og.orderNo 
				WHERE o.regDt < DATE_FORMAT(DATE_ADD(CURDATE(), INTERVAL -1 DAY),'%Y-%m-%d 23:59:59') 
				AND o.regDt > DATE_FORMAT(DATE_ADD(CURDATE(), INTERVAL -1 DAY), '%Y-%m-%d 00:00:00')
				AND og.goodsNo = A.goodsNo ) AS rank FROM es_goods A ORDER BY rank DESC limit 10";
		$res = $this->db->query_fetch($qry);

		foreach ($res as $key => $val) {
			$qry2 = "select * from es_goods where goodsNo = '" . $val['goodsNo'] . "' ";

			$rankData[] = $this->db->fetch($qry2);
		}


		//gd_debug($rankData);exit;

		return $rankData;
	}





	public function rank()
	{

		$today = date("Ymd H:i:s");


		/*
		$qry = "SELECT goodsNo, orderCnt - (SELECT COUNT(og.goodsNo) FROM es_order o
				LEFT JOIN es_orderGoods og ON o.orderNo = og.orderNo 
				WHERE o.regDt < DATE_FORMAT(DATE_ADD(CURDATE(), INTERVAL -1 DAY),'%Y-%m-%d 23:59:59')
				AND	o.regDt > DATE_FORMAT(DATE_ADD(CURDATE(), INTERVAL -1 DAY), '%Y-%m-%d 00:00:00')
				AND og.goodsNo = A.goodsNo ) FROM es_goods A ORDER BY orderCnt DESC";
		$res = $this->db->query_fetch($qry);
		*/

		$qry = "SELECT goodsNo, orderCnt, (SELECT COUNT(og.goodsNo) FROM es_order o
				LEFT JOIN es_orderGoods og ON o.orderNo = og.orderNo 
				WHERE o.regDt < DATE_FORMAT(DATE_ADD(CURDATE(), INTERVAL -1 DAY),'%Y-%m-%d 23:59:59') 
				AND o.regDt > DATE_FORMAT(DATE_ADD(CURDATE(), INTERVAL -1 DAY), '%Y-%m-%d 00:00:00')
				AND og.goodsNo = A.goodsNo ) AS rank FROM es_goods A WHERE A.delFl = 'n' ORDER BY rank DESC limit 50";
		$res = $this->db->query_fetch($qry);


		foreach ($res as $key => $val) {

			//gd_debug($val['goodsNo']);

			if ($key == 0) {
				$rankNo = $val['goodsNo'];
			} else {
				$rankNo .= "||" . $val['goodsNo'];
			}
		}

		$qry2 = "select rank from es_displayTheme where sno = 1 ";
		$result = $this->db->fetch($qry2);

		//gd_debug($result);exit;

		//if( $result['rank'] && $result['rank'] == $today ){

		//}else{
		$qry = "update es_displayTheme set goodsNo = '" . $rankNo . "', rank = '" . $today . "' where sno = 1 ";
		$res = $this->db->query_fetch($qry);

		$qry2 = "update es_displayTheme set goodsNo = '" . $rankNo . "', rank = '" . $today . "' where sno = 56 ";
		$res2 = $this->db->query_fetch($qry2);

		$qry3 = "update es_displayTheme set goodsNo = '" . $rankNo . "', rank = '" . $today . "' where sno = 74 ";
		$res2 = $this->db->query_fetch($qry3);

		$qry4 = "update es_displayTheme set goodsNo = '" . $rankNo . "', rank = '" . $today . "' where sno = 76 ";
		$res2 = $this->db->query_fetch($qry4);
		//}


		//return $res;

	}

	//리뷰 베스트 GET
	public function bestReviewList()
	{

		$qry = "SELECT * FROM es_plusReviewArticle WHERE isBestReview = 'y' ORDER BY sno DESC";
		$res = $this->db->query_fetch($qry);





		foreach ($res as $key => $val) {

			$qry2 = "SELECT ROUND(AVG(goodsPt),1) as goodsPt FROM es_plusReviewArticle WHERE goodsNo = '" . $val['goodsNo'] . "'";
			$res2 = $this->db->fetch($qry2);


			$res[$key]['goodsPtAVG'] = $res2['goodsPt'];


			$goodsEx = explode(".", $res2['goodsPt']);

			$res[$key]['goodsPtUp'] = $goodsEx[0];
			$res[$key]['goodsPtDown'] = $goodsEx[1];
		}

		//gd_debug($res);


		return $res;
	}

	//베스트 리뷰 상품정보
	public function bestReviewListGoods($goodsNo)
	{

		$qry = "SELECT * FROM es_goods WHERE goodsNo = '$goodsNo' ";
		$res = $this->db->fetch($qry);


		$qry2 = "SELECT * FROM es_goodsImage WHERE goodsNo = '" . $res['goodsNo'] . "' && imageKind = 'list' ";
		$res2 = $this->db->fetch($qry2);

		$res['goodsImage'] = $res2['imageName'];


		return $res;
	}

	//반려동물 오늘 생일 뽑아오기
	public function getDogBirth()
	{
		$today = date('md');

		$qry = "SELECT memId, memNo, memNm, ex4 FROM es_member WHERE ex4 LIKE ('____" . $today . "')";
		$res = $this->db->query_fetch($qry);

		//gd_debug($res);

		return $res;
	}

	//Coupon
	public function dpxSaveCoupon($memNo)
	{


		$nowTime = date('Y-m-d H:i:s');
		$time = time();


		//쿠폰값 고정값 셋팅
		$req['couponNo'] = 5;
		$req['couponUsePeriodType'] == 'day';


		if ($req['couponUsePeriodType'] == 'day') {
			$req['couponUsePeriodStartDate'] = $nowTime;
			$req['couponUsePeriodEndDate'] = date("Y-m-d H:i:s", strtotime("+1 week", $time));
		}
		//$memNo = Session::get('member.memNo');

		$qry = " insert es_memberCoupon set 
						couponNo = ? ,
						memNo = ? ,
						couponSaveAdminId = ? ,
						orderNo = ? , 
						goodsNo = ? ,
						memberCouponStartDate = ?	,
						memberCouponEndDate = ? ,
						regDt = ? ";



		$arrBind = [];
		$this->db->bind_param_push($arrBind['bind'], 's', $req['couponNo']);
		$this->db->bind_param_push($arrBind['bind'], 's', $memNo);
		$this->db->bind_param_push($arrBind['bind'], 's', '반려동물 생일쿠폰');
		$this->db->bind_param_push($arrBind['bind'], 's', '');
		$this->db->bind_param_push($arrBind['bind'], 's', '0');
		$this->db->bind_param_push($arrBind['bind'], 's', $req['couponUsePeriodStartDate']);
		$this->db->bind_param_push($arrBind['bind'], 's', $req['couponUsePeriodEndDate']);
		$this->db->bind_param_push($arrBind['bind'], 's', $nowTime);
		$this->db->bind_query($qry, $arrBind['bind']);
	}



	//user id, user nm, user phone 조회
	public function userFinder($userInfo)
	{


		$qry = "SELECT memNo, memId,memPw, memNm, cellPhone FROM es_member 
					where memId='" . $userInfo['user_id'] . "' && memNm = '" . $userInfo['user_nm'] . "' && cellPhone = '" . $userInfo['user_phone'] . "' ";
		$res = $this->db->fetch($qry);

		$passCheck = 'n';
		if (substr($res['memPw'], 0, 1) == "*") {
			$passCheck = 'y';
		} else {
			$passCheck = 'n';
		}


		//gd_debug($passCheck);exit;

		if ($passCheck == 'y') {
			$res['passCheck'] = $passCheck;
			return $res;
		}
	}


	public function setPassword($userInfo)
	{

		$encryptionNewPwd = hash('sha512', $userInfo['reset_user_pw']);

		$qry = "UPDATE es_member set memPw = '" . $encryptionNewPwd . "' where memId = '" . $userInfo['change_id'] . "' ";

		$this->db->query($qry);
	}


	public function setDcInfo($dcInfo)
	{

		$birth = $dcInfo['dcYear'] . $dcInfo['dcMonth'] . $dcInfo['dcDay'];

		$qry = "UPDATE es_member set ex4 = '" . $birth . "' where memId = '" . $dcInfo['memId'] . "' ";

		$this->db->query($qry);
	}

	public function getNaverInfo($naverProfile)
	{

		$qry = "select * from dpx_memberNaver where user_email = '" . $naverProfile['email'] . "'";

		$result = $this->db->fetch($qry);

		return $result;
	}

	public function setMileage($memNo, $naverProfile)
	{

		$naverMemInfo = $this->getNaverInfo($naverProfile);
		//gd_debug($naverMemInfo);exit;

		//마일리지 지급
		$mileage = App::load('\\Component\\Mileage\\Mileage');


		/**
		 * 회원 마일리지 가감 처리
		 *
		 * @param      $memNo            int 회원 번호
		 * @param      $targetMileage    int 처리할 마일리지
		 * @param      $reasonCd         string 지급 사유 코드
		 * @param      $handleMode       string 처리 모드 (m - 회원, o - 주문, b - 게시판, r - 추천인, c - 쿠폰)
		 * @param      $handleCd         string 처리 코드 (주문 번호, 게시판 코드, 추천한 사람 ID)
		 * @param null $handleNo         string 처리 번호 (상품 번호, 게시물 번호)
		 * @param null $contents         string 사유(reasonCd가 기타가 아니면 입력할 필요 없는 항목)
		 *
		 * @return bool
		 */
		$msg = "기존 회원 적립금 '" . number_format($naverMemInfo['get_bns']) . "'원이 지급되었습니다.";
		$mileage->setMemberMileage($memNo, $naverMemInfo['get_bns'], '01005011', 'm', $memNo, $memNo, $msg);
	}

	//프로모션상품 주문내역 확인/반환
	public function checkPossibleGoods($data)
	{

		$possibleFl = 'y';
		$mb = Session::get('member');
		$qry = "SELECT  COUNT(*) AS cnt FROM dpx_promotionGoods  AS dp
				LEFT JOIN es_orderGoods AS og
				ON dp.orderNo = og.orderNo
				WHERE dp.goodsNo=? AND dp.memNo = ? AND og.orderStatus IN ('o1','p1','g1','g2','g3','g4','d1','d2','g1','s1')";
		//$qry = "SELECT  count(*) cnt from dpx_promotionGoods WHERE goodsNo=? and memNo = ? ";

		$arrBind = [];
		$this->db->bind_param_push($arrBind, 'i', $data['goodsNo']);
		$this->db->bind_param_push($arrBind, 'i', $mb['memNo']);
		$result = $this->db->query_fetch($qry, $arrBind);

		if ($result[0]['cnt'] > 0) $possibleFl = 'n';

		return $possibleFl;
	}




	//프로모션상품 주문번호생성시 unique DB저장
	public function setPossibleGoods($orderNo, $cartInfo)
	{

		$mb = Session::get('member');

		foreach ($cartInfo as $scmNo => $rows) {
			foreach ($rows as $deliverySno => $row) {

				foreach ($row as $r) {

					//프로모션상품확인 
					if ($r['dpxPromotionFl'] == 'y') {

						$qry = " insert dpx_promotionGoods set 
										memNo = ? ,
										goodsNo = ? ,
										orderNo = ?	";

						$arrBind = [];
						$this->db->bind_param_push($arrBind['bind'], 'i', $mb['memNo']);
						$this->db->bind_param_push($arrBind['bind'], 'i', $r['goodsNo']);
						$this->db->bind_param_push($arrBind['bind'], 'i', $orderNo);

						$this->db->bind_query($qry, $arrBind['bind']);
						//$insId = $this->db->insert_id();
					}
				}
			}
		}
	}

	//주문건으로 조회하여 프로모션 체크
	public function proOrderChk($memNo)
	{
		if ($memNo == '') {
			$memInfo = Session::get('member');

			$memNo = $memInfo['memNo'];
		}


		$qry = "SELECT COUNT(*) AS cnt FROM es_order o 
				LEFT JOIN es_orderGoods og ON o.orderNo = og.orderNo 
				WHERE o.memNo = '" . $memNo . "' && og.dpxPromotionFl = 'y'";

		$result = $this->db->fetch($qry);


		return $result;
	}

	//회원정보 flag로 조회하여 프로모션 체크
	public function proMemberChk($memNo)
	{

		$qry = "select dpxPromotionFl as flag from es_member where memNo = '" . $memNo . "'";

		$result = $this->db->fetch($qry);


		return $result['flag'];
	}

	public function dpxPromotionChk($goodsNo)
	{
		$qry = "select dpxPromotionFl as flag from es_goods where goodsNo = '$goodsNo'";
		$result = $this->db->fetch($qry);

		return $result['flag'];
	}

	public function dpxPromotionUdt($memInfo)
	{

		$qry = "UPDATE es_member set dpxPromotionFl = 'y' where memNo = '" . $memInfo['memNo'] . "' ";

		$this->db->query($qry);
	}

	//골라담기 중복구매 불가튜닝
	public function dpxSelectGoods($orderNo)
	{
		$qry = "select * from es_orderGoods where orderNo = '" . $orderNo . "' ";

		$orderGoods = $this->db->query_fetch($qry);

		foreach ($orderGoods as $key => $val) {
			$tmpGoods[] = $val['goodsNo'];
		}

		$qry2 = "select dpxSelectGoods from es_member where memNo = (SELECT memNo FROM es_order WHERE orderNo = '" . $orderNo . "') ";
		$memSelect = $this->db->fetch($qry2);

		$dpxGoods = explode("|", $memSelect['dpxSelectGoods']);
		$dpxGoods = array_filter($dpxGoods);

		foreach ($dpxGoods as $key => $val) {
			array_push($tmpGoods, $val);
		}

		$tmpGoods = array_unique($tmpGoods);




		foreach ($tmpGoods as $key => $val) {
			if ($key == 0) {
				$tmpSelect = $val . "|";
			} else {
				$tmpSelect .= $val . "|";
			}
		}

		$qry3 = "UPDATE es_member set dpxSelectGoods = '" . $tmpSelect . "' where memNo = (SELECT memNo FROM es_order WHERE orderNo = '" . $orderNo . "') ";

		$this->db->query($qry3);
	}

	public function dpxFirstSaleChk($memNo)
	{
		$qry = "update es_member set dpxFirstSaleFl = 'y' where memNo = '" . $memNo . "'";
		$this->db->query($qry);
	}

	public function dpxSelectInfo($memNo)
	{
		$qry = "select dpxSelectGoods from es_member where memNo = '" . $memNo . "' ";

		$result = $this->db->fetch($qry);

		return $result['dpxSelectGoods'];
	}



	public function idPurchaseReturn($data)
	{


		$qry = "SELECT  * from es_memberOrderGoodsCountLog WHERE orderNo=? and memNo = ? ";

		$arrBind = [];
		$this->db->bind_param_push($arrBind, 's', $data['orderNo']);
		$this->db->bind_param_push($arrBind, 's', $data['memNo']);
		$result = $this->db->query_fetch($qry, $arrBind);

		$chkGoodsNo = array();
		foreach ($data['goods'] as $k => $v) {
			foreach ($v as $k2 => $v2) {
				foreach ($v2 as $k3 => $v3) {
					$data['goods'][$k][$k2][$k3]['dpIdPurchase'] = 'false';
					foreach ($result as $key => $value) {
						if ($value['goodsNo'] == $v3['goodsNo']) {
							$data['goods'][$k][$k2][$k3]['dpIdPurchase'] = 'true';
						}
					}
				}
			}
		}
		return $data;
	}

	public function getFirstEventGoods($data)
	{
		foreach ($data['goods'] as $k => $v) {
			foreach ($v as $k2 => $v2) {
				foreach ($v2 as $k3 => $v3) {
					$qry = "SELECT dpxEventFl FROM es_goods where goodsNo='" . $v3['goodsNo'] . "'";
					$data['goods'][$k][$k2][$k3]['dpxEventFl'] = $this->db->query_fetch($qry)[0]['dpxEventFl'];
				}
			}
		}
		return $data;
	}

	public function deleteEventIdGoods($get)
	{
		$qry = "DELETE FROM es_memberOrderGoodsCount WHERE memNo = '" . $get['memNo'] . "' and goodsNo= '" . $get['goodsNo'] . "'";
		$this->db->query($qry);
		$qry2 = "DELETE FROM es_memberOrderGoodsCountLog WHERE memNo = '" . $get['memNo'] . "' and goodsNo= '" . $get['goodsNo'] . "'";
		$this->db->query($qry2);

		return 'ok';
	}

	public function deleteEventFl($memNo)
	{
		$qry = "update es_member set dpxPromotionFl = 'n' where memNo = '$memNo' ";

		$this->db->query($qry);

		return 'ok';
	}

	public function dpxEventReset($memNo)
	{
		$qry = "update es_member set dpxFirstSaleFl = 'n', dpxResetDt = '" . date("Y-m-d h:i:s", time()) . "' where memNo = '" . $memNo . "'";
		$this->db->query($qry);
		return 'ok';
	}

	public function eventApply($post)
	{


		if ($post['batchChk'] == 'y') {
			$dpxQry = explode('LIMIT', $post['dpxQry']);
			$cnt = count($dpxQry) - 1;
			$strQry = '';
			foreach ($dpxQry as $k => $v) {
				if ($k < $cnt) {
					$strQry .= $v;
				}
			}
			$data = $this->db->query_fetch($strQry, $post['dpxBind']);
			$goodsList = array();

			foreach ($data as $k => $v) {
				$goodsList[$k] = $v['goodsNo'];
			}
			$post['goodsList'] = $goodsList;
		}


		foreach ($post['goodsList'] as $key => $goodsno) {

			$qry = "update " . DB_GOODS . " set dpxEventFl = ?,
									dpxEventPrice = ?
				where goodsNo = ? ";
			$qry2 = "update " . DB_GOODS_SEARCH . " set dpxEventFl = ?,
									dpxEventPrice = ?
				where goodsNo = ? ";
			$arrBind = [];
			$this->db->bind_param_push($arrBind['bind'], 's', $post['dpxEventFl']);
			$this->db->bind_param_push($arrBind['bind'], 'i', $post['dpxEventPrice']);
			$this->db->bind_param_push($arrBind['bind'], 's', $goodsno);
			$this->db->bind_query($qry, $arrBind['bind']);
			$this->db->bind_query($qry2, $arrBind['bind']);
			unset($arrBind);
		}
	}
	public function eventApplyDay($post)
	{


		if ($post['batchChk'] == 'y') {
			$dpxQry = explode('LIMIT', $post['dpxQry']);
			$cnt = count($dpxQry) - 1;
			$strQry = '';
			foreach ($dpxQry as $k => $v) {
				if ($k < $cnt) {
					$strQry .= $v;
				}
			}
			$data = $this->db->query_fetch($strQry, $post['dpxBind']);
			$goodsList = array();

			foreach ($data as $k => $v) {
				$goodsList[$k] = $v['goodsNo'];
			}
			$post['goodsList'] = $goodsList;
		}
		if ($post['dpxEventDateFl'] == 'n') {
			$post['dpxEventDate1'] = '';
			$post['dpxEventDate2'] = '';
		}

		foreach ($post['goodsList'] as $key => $goodsno) {

			$qry = "update " . DB_GOODS . " set dpxEventDayFl = ?,
									dpxEventDayPrice = ?,
									dpxEventUseStartDate = ?,
									dpxEventUseEndDate = ?,
									dpxEventDateFl = ?
				where goodsNo = ? ";
			$qry2 = "update " . DB_GOODS_SEARCH . " set dpxEventDayFl = ?,
									dpxEventDayPrice = ?,
									dpxEventUseStartDate = ?,
									dpxEventUseEndDate = ?,
									dpxEventDateFl = ?
				where goodsNo = ? ";
			$arrBind = [];
			$this->db->bind_param_push($arrBind['bind'], 's', $post['dpxEventFl']);
			$this->db->bind_param_push($arrBind['bind'], 'i', $post['dpxEventPrice']);
			$this->db->bind_param_push($arrBind['bind'], 's', $post['dpxEventDate1']);
			$this->db->bind_param_push($arrBind['bind'], 's', $post['dpxEventDate2']);
			$this->db->bind_param_push($arrBind['bind'], 's', $post['dpxEventDateFl']);
			$this->db->bind_param_push($arrBind['bind'], 's', $goodsno);
			$this->db->bind_query($qry, $arrBind['bind']);
			$this->db->bind_query($qry2, $arrBind['bind']);
			unset($arrBind);
		}
	}
	public function eventKindChk($goodsView)
	{
		$memNo = Session::get('member.memNo');

		$firstCnt = $this->firstSaleChk($memNo);
		if ($firstCnt == 0) {
			return 'first';
		}
		$dayCnt = $this->daySaleChk($memNo, $goodsView);
		if ($dayCnt == 0) {
			return 'day';
		}

		return 'no';
	}

	public function eventPossibleChk($memNo)
	{

		$qry1 = "SELECT IF(dpxResetDt > 0, dpxResetDt, entryDt) AS entryDt FROM es_member WHERE memNo ='" . $memNo . "'";
		$entryDt = $this->db->fetch($qry1)['entryDt'];


		$today = date("Y-m-d h:i:s", time());
		$limit = date("Y-m-d h:i:s", strtotime($today . "-7 days"));

		if (Request::server()->get('REMOTE_ADDR') == "220.118.145.49") {
			//gd_debug($qry1);
			//exit;
		}


		if ($limit <= $entryDt) {
			return 'y';
		} else {
			return 'n';
		}
	}

	public function firstSaleChk($memNo)
	{
		/*$qry="SELECT COUNT(*) AS cnt FROM es_order WHERE memNo=? AND firstSaleFl=?";*/
		$qry = "SELECT COUNT(*) AS cnt FROM es_member WHERE memNo=? AND dpxFirstSaleFl=?";
		$arrBind = [];
		$this->db->bind_param_push($arrBind, 's', $memNo);
		$this->db->bind_param_push($arrBind, 's', 'y');
		$result = $this->db->query_fetch($qry, $arrBind);
		unset($arrBind);
		$cnt = $result[0][cnt];

		return (int)$cnt;
	}
	public function daySaleChk($memNo, $goodsView)
	{
		$config = gd_policy('dpx.dpxevent');
		$today = date('Y-m-d');
		$startday = date('Y-m-d', strtotime($today . ' -' . $config['lastPurchaseDay'] . ' day'));
		if ($goodsView['dpxEventDateFl'] == 'y') {
			if ($goodsView['dpxEventUseStartDate'] <= $today && $today <= $goodsView['dpxEventUseEndDate']) {
				$eventFl = 'y';
			} else {
				$eventFl = 'n';
			}
		}
		$cnt = 0;
		if ($goodsView['dpxEventDayFl'] == 'y' && $eventFl == 'y') {
			$qry = "select COUNT(*) AS cnt from es_order where regDt >=? and memNo=?";
			$arrBind = [];
			$this->db->bind_param_push($arrBind, 's', $startday);
			$this->db->bind_param_push($arrBind, 's', $memNo);
			$result = $this->db->query_fetch($qry, $arrBind);
			unset($arrBind);
			$cnt = $result[0][cnt];
		}
		return (int)$cnt;
	}

	public function memberEventChk($memNo)
	{

		$firstCnt = $this->firstSaleChk($memNo);
		$dayCnt = $this->daySaleChk2($memNo);
		if ($firstCnt == 0) {
			return 'first';
		}
		if ($dayCnt == 0) {
			return 'day';
		}
		return 'no';
	}
	public function daySaleChk2($memNo)
	{
		$config = gd_policy('dpx.dpxevent');
		$today = date('Y-m-d');
		$startday = date('Y-m-d', strtotime($today . ' -' . $config['lastPurchaseDay'] . ' day'));

		$qry = "select COUNT(*) AS cnt from es_order where regDt >=? and memNo=?";
		$arrBind = [];
		$this->db->bind_param_push($arrBind, 's', $startday);
		$this->db->bind_param_push($arrBind, 's', $memNo);
		$result = $this->db->query_fetch($qry, $arrBind);
		unset($arrBind);
		$cnt = $result[0][cnt];

		return (int)$cnt;
	}

	public function dpxTotalPrice($data, $eventKind)
	{
		$totalPrice = 0;

		if ($eventKind == 'first') {
			$firstCnt = 0;
			foreach ($data as $k => $v) {
				if ($v['dpxEventFl'] == 'y' && $firstCnt < 1 && $v['goodsCnt'] < 2) {
					$totalPrice += $v['dpxEventPrice'];
					$firstCnt++;
				} else {
					$totalPrice += ($v['goodsPrice'] + $v['optionPrice']) * $v['goodsCnt'];
				}
			}
		}
		if ($eventKind == 'day') {
			$dayCnt = 0;
			foreach ($data as $k => $v) {
				if ($v['dpxEventDayFl'] == 'y' && $dayCnt < 1 && $v['goodsCnt'] < 2) {
					$totalPrice += $v['dpxEventDayPrice'];
					$dayCnt++;
				} else {
					$totalPrice += ($v['goodsPrice'] + $v['optionPrice']) * $v['goodsCnt'];
				}
			}
		}

		return $totalPrice;
	}
	public function getCheckMemberGroupDcMemInfo($sumPrice)
	{

		$arrField['memberGroup'] = DBTableField::setTableField('tableMemberGroup', null, $arrExclude['memberGroup']);
		$groupSno = Session::get('member.groupSno');
		$memNo = Session::get('member.memNo');


		$column = implode($arrField['memberGroup'], ',');
		$qry = "select * from es_memberGroup where sno='" . $groupSno . "'";
		$result = $this->db->fetch($qry);
		$month = date('Y-m');

		$qry2 = "SELECT og.orderNo,og.goodsCnt,((og.goodsPrice+og.optionPrice)*og.goodsCnt) AS orderPrice,o.memNo, m.groupSno FROM es_orderGoods og
					LEFT JOIN es_goods g ON og.goodsNo = g.goodsNo
					LEFT JOIN es_order o ON og.orderNo = o.orderNo
					LEFT JOIN es_member m ON m.memNo = o.memNo
					LEFT JOIN es_memberGroup mg ON mg.sno = m.groupSno
					WHERE 
					og.orderStatus IN ('o1','p1','g1','d1','d2','s1') AND
					LEFT(og.regDt,7) = DATE_FORMAT(NOW(),?) AND 
					o.memNo = ?";
		$arrBind = [];
		$this->db->bind_param_push($arrBind, 's', $month);
		$this->db->bind_param_push($arrBind, 's', $memNo);
		$res = $this->db->query_fetch($qry2, $arrBind);
		$orderPrice = 0;
		if (count($res) > 0) {
			foreach ($res as $key => $val) {
				$orderPrice += $val['orderPrice'];
			}
		}
		if (Request::server()->get('REMOTE_ADDR') == "220.118.145.49") {
			$result['orderMonLimitFl'] = 'y';
		}


		if ($result['orderMonMoney'] < ($sumPrice + $orderPrice)  && $result['orderMonLimitFl'] == 'y') {
			$changeQry = "select " . $column . " from es_memberGroup where sno='24'";
			$group = $this->db->fetch($changeQry);
			$group['change'] = 'ok';
		} else {
			$group['change'] = 'no';
		}

		return $group;
	}

	public function getMemberLimitMoney()
	{
		$arrField['memberGroup'] = DBTableField::setTableField('tableMemberGroup', null, $arrExclude['memberGroup']);
		$groupSno = Session::get('member.groupSno');
		$memNo = Session::get('member.memNo');


		$column = implode($arrField['memberGroup'], ',');
		$qry = "select * from es_memberGroup where sno='" . $groupSno . "'";
		$result = $this->db->fetch($qry);
		$month = date('Y-m');

		$qry2 = "SELECT og.orderNo,og.goodsCnt,((og.goodsPrice+og.optionPrice)*og.goodsCnt) AS orderPrice,o.memNo, m.groupSno FROM es_orderGoods og
				LEFT JOIN es_goods g ON og.goodsNo = g.goodsNo
				LEFT JOIN es_order o ON og.orderNo = o.orderNo
				LEFT JOIN es_member m ON m.memNo = o.memNo
				LEFT JOIN es_memberGroup mg ON mg.sno = m.groupSno
				WHERE 
				og.orderStatus IN ('o1','p1','g1','d1','d2','s1') AND
				LEFT(og.regDt,7) = DATE_FORMAT(NOW(),?) AND 
				o.memNo = ?";
		$arrBind = [];
		$this->db->bind_param_push($arrBind, 's', $month);
		$this->db->bind_param_push($arrBind, 's', $memNo);
		$res = $this->db->query_fetch($qry2, $arrBind);
		$orderPrice = 0;
		if (count($res) > 0) {
			foreach ($res as $key => $val) {
				$orderPrice += $val['orderPrice'];
			}
		}
		if ($result['orderMonLimitFl'] == 'y') {
			return $result['orderMonMoney'] - $orderPrice;
		} else {

			return 0;
		}
	}

	public function getMileageMember($req)
	{

		/*
		$qry = "select mm.sno, mm.memNo, mm.managetId, mm.handleMode, mm.handleNo, mm.beforeMileage, mm.afterMileage, mm.mileage, mm.contents, mm.deleteScheduleDt, mm.regDt, mm.modDt, m.memId, m.groupSno, m.memNm, mg.groupNm from es_memberMileage mm 
				LEFT JOIN es_member m ON mm.memNo=m.memNo
				LEFT JOIN es_memberGroup mg ON m.groupSno=mg.sno
				where mm.regDt > '".$req['periodDate'][0]."' || mm.regDt >= '".$req['periodDate'][1]."' ORDER BY mm.sno DESC ";
		*/

		/*
		$qry = "select * from es_memberMileage mm 
				LEFT JOIN es_member m ON mm.memNo=m.memNo
				LEFT JOIN es_memberGroup mg ON m.groupSno=mg.sno
				where mm.regDt > '".$req['periodDate'][0]."' || mm.regDt >= '".$req['periodDate'][1]."' ORDER BY mm.sno DESC ";
		*/

		//dpx.farmer a.reasonCd 필드추가 (유효기간만료 확인을위해서) - 20220822
		$qry = "SELECT b.memId, b.memNm, a.reasonCd, IF(a.mileage>0, a.mileage, '') AS pmileage, IF(a.mileage<0, a.mileage, '') AS omileage, LEFT(a.regDt,16) AS regDt, IF(a.mileage>0,CONCAT(LEFT(a.deleteScheduleDt,10),' 23:59'),'') AS deleteScheduleDt, a.contents, c.paymentDt, a.handleCd FROM es_memberMileage a LEFT JOIN es_member b ON a.memNo=b.memNo LEFT JOIN es_order c ON a.handleCd=c.orderNo WHERE a.regDt>='" . $req['periodDate'][0] . "' && a.regDt<='" . $req['periodDate'][1] . "+23:59:59' ORDER BY a.regDt DESC";

		$result = $this->db->query($qry);

		return $result;
	}

	//출석체크 리스트

	public function getAttendanceList($req)
	{
		$qry = "SELECT m.memID, m.memNm, a.attendanceHistory, a.conditionDt, a.benefitDt
						FROM es_attendanceCheck AS a JOIN es_member AS m ON m.memNo = a.memNo
						WHERE a.attendanceSno = " . $req['attenSno'] . " ORDER BY a.sno DESC";
		$result = $this->db->query($qry);
		return $result;
	}

	public function getSaleData()
	{
		$qry = 'SELECT t.goodsNo, t.startDt, g.totalStock, g.stockFl
				FROM es_timeSale AS t LEFT JOIN es_goods AS g ON t.goodsNo = g.goodsNo
				WHERE t.sno IN ("5","6","7","8","9")
				GROUP BY startDt ASC ';

		$result = $this->db->query_fetch($qry);
		return $result;
	}

	public function getTimeSaleSno()
	{
		$qry = 'SELECT * FROM es_timeSale WHERE startDt < NOW() && endDt > NOW()';

		$result = $this->db->query_fetch($qry)[0]['sno'];

		return $result;
	}


	public function updateReviewBestOn($sno)
	{
		$qry = 'update es_plusReviewArticle set dpxBestReviewFl ="y" where sno=' . $sno . ';';

		$update = $this->db->query_fetch($qry);

		return true;
	}

	public function updateReviewBestOff($sno)
	{
		$qry = 'update es_plusReviewArticle set dpxBestReviewFl ="n" where sno=' . $sno . ';';

		$update = $this->db->query_fetch($qry);

		return true;
	}


	public function updateReviewBestOn2($sno)
	{
		$qry = 'update es_plusReviewArticle set dpxGoodsBestReviewFl ="y" where sno=' . $sno . ';';

		$update = $this->db->query_fetch($qry);

		return true;
	}

	public function updateReviewBestOff2($sno)
	{
		$qry = 'update es_plusReviewArticle set dpxGoodsBestReviewFl ="n" where sno=' . $sno . ';';

		$update = $this->db->query_fetch($qry);

		return true;
	}



	public function updateDisplayOn($sno)
	{
		$qry = 'update es_plusReviewArticle set dpxDisplayFl ="y" where sno=' . $sno . ';';

		$update = $this->db->query_fetch($qry);

		return true;
	}

	public function updateDisplayOff($sno)
	{
		$qry = 'update es_plusReviewArticle set dpxDisplayFl ="n" where sno=' . $sno . ';';

		$update = $this->db->query_fetch($qry);

		return true;
	}

	public function getGoodsViewBestReview($goodsNo)
	{
		$qry = 'select * from es_plusReviewArticle where dpxDisplayFl = "y" and dpxBestReviewFl= "y" and goodsNo =' . $goodsNo . ';';

		$getBestReview = $this->db->query_fetch($qry);
		return $getBestReview;
	}
	//메인페이지 베스트리뷰 가져오기
	public function getMainBestReview()
	{
		$qry = 'select * from es_plusReviewArticle where dpxDisplayFl = "y" and dpxBestReviewFl= "y" ORDER BY regDt DESC LIMIT 3';


		$getBestReview = $this->db->query_fetch($qry);

		foreach ($getBestReview as $key => $val) {

			$qry2 = "SELECT ROUND(AVG(goodsPt),1) as goodsPt FROM es_plusReviewArticle WHERE goodsNo = '" . $val['goodsNo'] . "'";
			$res2 = $this->db->fetch($qry2);

			$qry3 = "SELECT thumbImageUrl FROM es_bd_goodsreviewAttachments WHERE plusreviewNo = '" . $val['sno'] . "' limit 1;";
			$res3 = $this->db->fetch($qry3);

			$getBestReview[$key]['goodsPtAVG'] = $res2['goodsPt'];

			if ($res3['thumbImageUrl']) {
				$getBestReview[$key]['uploadFileNm'] = $res3['thumbImageUrl'];
				$getBestReview[$key]['cdn'] = 'y';
			}

			$goodsEx = explode(".", $res2['goodsPt']);

			$getBestReview[$key]['goodsPtUp'] = $goodsEx[0];
			$getBestReview[$key]['goodsPtDown'] = $goodsEx[1];
		}

		return $getBestReview;
	}
	//상품상세 베스트리뷰가져오기
	public function getGoodsBestReview($goodsNo)
	{
		$qry = 'select * from es_plusReviewArticle where dpxDisplayFl = "y" and dpxGoodsBestReviewFl= "y" and goodsNo ="' . $goodsNo . '" ORDER BY regDt DESC LIMIT 5';

		//gd_debug($qry);
		$getBestReview = $this->db->query_fetch($qry);


		return $getBestReview;
	}

	//쿠폰 넘버 가져오기
	public function getCouponNo($couponCode)
	{
		$qry = 'SELECT couponNo FROM es_couponOfflineCode WHERE couponOfflineCode = "' . $couponCode . '"';

		$getCouponNo = $this->db->query_fetch($qry)[0]['couponNo'];

		return $getCouponNo;
	}

	public function eventReset($data)
	{

		foreach ($data['chk'] as $val) {

			$qry = "update es_member set dpxFirstSaleFl = ? where memNo = ? ";

			$arrBind = [];
			$this->db->bind_param_push($arrBind['bind'], 's', 'n');
			$this->db->bind_param_push($arrBind['bind'], 's', $val);
			$this->db->bind_query($qry, $arrBind['bind']);
		}
	}


	public function couponDownChk($data)
	{
		$qry = "SELECT goodsNo FROM es_displayTheme WHERE sno = '116'";
		$getGoodsArr = $this->db->query_fetch($qry);

		$goodsArr = explode('||', $getGoodsArr[0]['goodsNo']);

		if (in_array($data['goodsNo'], $goodsArr)) {
			$result = 'y';
		} else {
			$result = 'n';
		}
		return $result;
	}

	public function getUserMileage($memNo)
	{
		$query = "SELECT memId, mileage FROM es_member WHERE memNo = " . $memNo;
		$res = $this->db->query_fetch($query);

		return $res[0]['mileage'];
	}

	public function insertMemberMileageHackOutDpx($memNo, $memMileage)
	{

		$query = "INSERT es_memberMileage SET 
				memNo = '" . $memNo . "',
				managerId = '',
				handleMode = 'h',
				handleCd = '탈퇴회원',
				beforeMileage = '" . $memMileage . "',
				afterMileage = '0',
				mileage = '-" . $memMileage . "',
				reasonCd = '00000000',
				contents = '회원탈퇴',
				regIp = '" . Request::server()->get('REMOTE_ADDR') . "',
				deleteDt = '0000-00-00 00:00:00',
				deleteFl = 'y',
				regDt = '" . date('Y-m-d H:i:s') . "'";

		$this->db->query_fetch($query);
	}

	public function getUserDeposit($memNo)
	{
		$query = "SELECT memId, deposit FROM es_member WHERE memNo = " . $memNo;
		$res = $this->db->query_fetch($query);

		return $res[0]['deposit'];
	}

	public function insertMemberDepositHackOutDpx($memNo, $memDeposit)
	{
		if (!$memDeposit) {
			return true;
		}

		$query = "INSERT es_memberMileage SET 
				memNo = '" . $memNo . "',
				managerId = '',
				handleMode = 'h',
				handleCd = '탈퇴회원',
				beforeDeposit = '" . $memDeposit . "',
				afterDeposit = '0',
				deposit = '-" . $memDeposit . "',
				reasonCd = '00000000',
				contents = '회원탈퇴',
				regIp = '" . Request::server()->get('REMOTE_ADDR') . "',
				deleteDt = '0000-00-00 00:00:00',
				deleteFl = 'y',
				regDt = '" . date('Y-m-d H:i:s') . "'";

		$this->db->query_fetch($query);
	}

	//골라담기상품 주문내역 확인/반환
	public function checkSelectGoods($data)
	{
		$possibleFl = 'y';
		$mb = Session::get('member');
		$siteKey = Session::get('siteKey');

		if ($mb['memNo']) {
			$qry = "SELECT g.goodsNo, dpxSelectCnt, sum(c.goodsCnt)
					FROM es_cart as c
						LEFT JOIN es_goods as g
							ON c.goodsNo = g.goodsNo
					WHERE memNo = ?
						AND g.goodsNo = ?
						AND g.dpxSelectFl = 'y'
					GROUP BY g.goodsNo
						HAVING sum(c.goodsCnt) % g.dpxSelectCnt != 0
							OR sum(c.goodsCnt) / g.dpxSelectCnt > 1";

			$arrBind = [];
			$this->db->bind_param_push($arrBind, 'i', $mb['memNo']);
			$this->db->bind_param_push($arrBind, 'i', $data['goodsNo']);
		} else {
			$qry = "SELECT g.goodsNo, dpxSelectCnt, sum(c.goodsCnt)
					FROM es_cart as c
						LEFT JOIN es_goods as g
							ON c.goodsNo = g.goodsNo
					WHERE memNo = 0
						AND c.siteKey = ?
						AND g.goodsNo = ?
						AND g.dpxSelectFl = 'y'
					GROUP BY g.goodsNo
						HAVING sum(c.goodsCnt) % g.dpxSelectCnt != 0
							OR sum(c.goodsCnt) / g.dpxSelectCnt > 1";

			$arrBind = [];
			$this->db->bind_param_push($arrBind, 's', $siteKey);
			$this->db->bind_param_push($arrBind, 'i', $data['goodsNo']);
		}

		$result = $this->db->query_fetch($qry, $arrBind);

		if ($result) $possibleFl = 'n';

		return $possibleFl;
	}

	public function getDpxSelectCnt($goodsNo)
	{
		$qry = "SELECT dpxSelectCnt FROM es_goods WHERE goodsNo = ?";
		$arrBind = [];
		$this->db->bind_param_push($arrBind, 'i', $goodsNo);
		$result = $this->db->query_fetch($qry, $arrBind, false);
		return $result['dpxSelectCnt'];
	}

	public function getGoodsReview($data)
	{
		//$goodsNo = '1000001188';
		$qry = "SELECT * FROM es_plusReviewArticle WHERE goodsNo = ?";
		$arrBind = [];
		$this->db->bind_param_push($arrBind, 'i', $data['copyGoodsNo']);
		$result = $this->db->query_fetch($qry, $arrBind, false);

		if (count($result) == 0) {
			throw new LayerException(__("가져올 상품의 리뷰가 없습니다."));
		}

		//$newGoodsNo = '1000001410';
		$cnt = 0;
		foreach ($result as $key => &$val) {
			//$val['goodsNo'] = $newGoodsNo;
			$groupNo = $this->plusGroupNo();
			$val['groupNo'] = $groupNo;

			$qry = "insert " . DB_PLUS_REVIEW_ARTICLE . " set 
				channel = ?, 
				groupNo = ?, 
				memNo = ?,
				writerNm = ?,
				writerId = ?,
				writerNick = ?,
				writerPw = ?,
				writerIp = ?,
				contents = ?,
				uploadFileNm =?,
				saveFileNm = ?,
				hit = ?,
				memoCnt = ?,
				goodsNo = ?,
				goodsPt = ?,
				orderGoodsNo = ?,
				recommend = ?,
				isShow = ?,
				isMobile = ?,
				applyFl = ?,
				firstReviewFl = ?,
				addFormData = ?,
				mileage = ?,
				mileageGiveDt = ?,
				checkoutData = ?,
				goodsReviewSno = ?,
				migrationDt = ?,
				isBestReview = ?,
				dpxBestReviewFl = ?,
				dpxDisplayFl = ?,
				dpxGoodsBestReviewFl = ?,
				regDt = ? ";
			$arrBind = [];
			$this->db->bind_param_push($arrBind['bind'], 's', $val['channel']);
			$this->db->bind_param_push($arrBind['bind'], 'i', $groupNo);
			$this->db->bind_param_push($arrBind['bind'], 'i', $val['memNo']);
			$this->db->bind_param_push($arrBind['bind'], 's', $val['writerNm']);
			$this->db->bind_param_push($arrBind['bind'], 's', $val['writerId']);
			$this->db->bind_param_push($arrBind['bind'], 's', $val['writerNick']);
			$this->db->bind_param_push($arrBind['bind'], 's', $val['writerPw']);
			$this->db->bind_param_push($arrBind['bind'], 's', $val['writerIp']);
			$this->db->bind_param_push($arrBind['bind'], 's', gd_htmlspecialchars_stripslashes($val['contents']));
			$this->db->bind_param_push($arrBind['bind'], 's', $val['uploadFileNm']);
			$this->db->bind_param_push($arrBind['bind'], 's', $val['saveFileNm']);
			$this->db->bind_param_push($arrBind['bind'], 'i', $val['hit']);
			$this->db->bind_param_push($arrBind['bind'], 'i', $val['memoCnt']);
			$this->db->bind_param_push($arrBind['bind'], 'i', $data['pasteGoodsNo']);
			$this->db->bind_param_push($arrBind['bind'], 'i', $val['goodsPt']);
			$this->db->bind_param_push($arrBind['bind'], 'i', $val['orderGoodsNo']);
			$this->db->bind_param_push($arrBind['bind'], 'i', $val['recommend']);
			$this->db->bind_param_push($arrBind['bind'], 's', $val['isShow']);
			$this->db->bind_param_push($arrBind['bind'], 's', $val['isMobile']);
			$this->db->bind_param_push($arrBind['bind'], 's', $val['applyFl']);
			$this->db->bind_param_push($arrBind['bind'], 's', $val['firstReviewFl']);
			$this->db->bind_param_push($arrBind['bind'], 's', $val['addFormData']);
			$this->db->bind_param_push($arrBind['bind'], 'i', $val['mileage']);
			$this->db->bind_param_push($arrBind['bind'], 's', $val['mileageGiveDt']);
			$this->db->bind_param_push($arrBind['bind'], 's', $val['checkoutData']);
			$this->db->bind_param_push($arrBind['bind'], 'i', $val['goodsReviewSno']);
			$this->db->bind_param_push($arrBind['bind'], 's', $val['migrationDt']);
			$this->db->bind_param_push($arrBind['bind'], 's', $val['isBestReview']);
			$this->db->bind_param_push($arrBind['bind'], 's', $val['dpxBestReviewFl']);
			$this->db->bind_param_push($arrBind['bind'], 's', $val['dpxDisplayFl']);
			$this->db->bind_param_push($arrBind['bind'], 's', $val['dpxGoodsBestReviewFl']);
			$this->db->bind_param_push($arrBind['bind'], 's', $val['regDt']);
			$this->db->bind_query($qry, $arrBind['bind']);

			$reviewSno = $this->db->insert_id();
			$this->plusImage($val['sno'], $reviewSno);

			$cnt++;
		}

		return $cnt;
	}

	public function plusGroupNo()
	{
		$qry = "select MIN(groupNo) FROM es_plusReviewArticle";
		list($groupNo) = DB::fetch($qry, 'row');
		if ($groupNo == null) {
			return -1;
		}

		return $groupNo - 1;
	}

	public function plusImage($reviewNo, $reviewSno)
	{
		$qry = "select * from es_bd_goodsreviewAttachments where plusreviewNo = '" . $reviewNo . "'";
		$result = $this->db->query_fetch($qry, $arrBind);

		foreach ($result as $key => $val) {
			$qry = "insert es_bd_goodsreviewAttachments set
				reviewNo = ?,
				plusreviewNo = ?,
				reviewType = ?,
				uploadFileNm = ?,
				saveFileNm = ?,
				imageFolder = ?,
				thumbImageFolder = ?,
				imageUrl = ?,
				thumbImageUrl = ?,
				regDt = ?
			";
			$arrBind = [];
			$this->db->bind_param_push($arrBind['bind'], 'i', $val['reviewNo']);
			$this->db->bind_param_push($arrBind['bind'], 'i', $reviewSno);
			$this->db->bind_param_push($arrBind['bind'], 's', $val['reviewType']);
			$this->db->bind_param_push($arrBind['bind'], 's', $val['uploadFileNm']);
			$this->db->bind_param_push($arrBind['bind'], 's', $val['saveFileNm']);
			$this->db->bind_param_push($arrBind['bind'], 's', $val['imageFolder']);
			$this->db->bind_param_push($arrBind['bind'], 's', $val['thumbImageFolder']);
			$this->db->bind_param_push($arrBind['bind'], 's', $val['imageUrl']);
			$this->db->bind_param_push($arrBind['bind'], 's', $val['thumbImageUrl']);
			$this->db->bind_param_push($arrBind['bind'], 's', $val['regDt']);
			$this->db->bind_query($qry, $arrBind['bind']);
		}
	}

	public function getReviewAvg()
	{
		// 상품 구분 없이 전체 리뷰의 평균 점수
		$qry = "SELECT ROUND(AVG(goodsPt),1) as goodsPt FROM es_plusReviewArticle WHERE dpxDisplayFl = 'y'";
		$result = $this->db->fetch($qry);
		return $result['goodsPt'] ?? 0;
	}

	public function getReviewGraph()
	{
		$qry = "SELECT goodsPt, count(distinct sno) as cnt FROM es_plusReviewArticle WHERE dpxDisplayFl = 'y' GROUP BY goodsPt ORDER BY goodsPt DESC";
		$result = $this->db->query_fetch($qry);

		$total = array_reduce($result, function ($carry, $item) {
			return $carry + $item['cnt'];
		}, 0);
		foreach ($result as $key => $val) {
			$result[$key]['percent'] = round(($val['cnt'] / $total) * 100, 1);
			$result[$key]['goodsPt'] = $val['goodsPt'] * 1;
			$result[$key]['cnt'] = $val['cnt'] * 1;
		}

		return $result;
	}

	public function getIsRecommended($sno)
	{
		$memNo = Session::get('member.memNo');
		// table name : es_plusReviewRecommend
		// column name : articleSno, memNo
		$qry = "SELECT COUNT(*) as cnt FROM es_plusReviewRecommend WHERE articleSno = $sno AND memNo = $memNo";
		$result = $this->db->fetch($qry);
		return $result['cnt'] > 0;
	}


	/**
     * getDiscountBundleGroupList
     *
	 * @sjlee
     */
	public function getDiscountBundleGroupList()
	{
		$getValue = Request::get()->toArray();

		// --- 검색 설정
		$this->setSearchDiscountBundleGroupList($getValue);


		// --- 정렬 설정
		$sort = gd_isset($getValue['sort'], 'bg.regDt desc');

		// --- 페이지 기본설정
		gd_isset($getValue['page'], 1);
        gd_isset($getValue['pageNum'], 10);
		
		$page = \App::load('\\Component\\Page\\Page', $getValue['page']);
        $page->page['list'] = $getValue['pageNum']; // 페이지당 리스트 수

		$strSQL = ' SELECT COUNT(*) AS cnt FROM dpx_discount_bundle_group ' ;
        $res = $this->db->query_fetch($strSQL, null, false);
        $page->recode['amount'] = $res['cnt']; // 전체 레코드 수
        $page->setPage();
        $page->setUrl(\Request::getQueryString());
		

		// --- 데이터 조회 쿼리
		$this->db->strField = "
			bg.sno,
			bg.groupCd,
			bg.groupNm,
			bg.showNoBundlePopup,
			bg.preCartBundlePopup,
			bg.regDt,
			bg.modDt,
			(
				SELECT COUNT(*) FROM dpx_discount_bundle_group_goods bgg
				WHERE bgg.groupCd = bg.groupCd
			) AS goodsCount
		";
		$this->db->strFrom = 'dpx_discount_bundle_group bg';
		if(gd_isset($this->arrWhere))   $this->db->strWhere = implode(' AND ', gd_isset($this->arrWhere));
		$this->db->strOrder = $sort;
		$this->db->strLimit = $page->recode['start'] . ',' . $getValue['pageNum'];

		// 검색 카운트
        $strSQL = ' SELECT COUNT(*) AS cnt FROM dpx_discount_bundle_group  as bg ';

        if($this->db->strWhere){
            $strSQL .= ' WHERE ' . $this->db->strWhere;
        }
        $res = $this->db->query_fetch($strSQL, $this->arrBind, false);
        $page->recode['total'] = $res['cnt']; // 검색 레코드 수
        $page->setPage();
        $page->setUrl(\Request::getQueryString());

		$query = $this->db->query_complete();
        $strSQL = 'SELECT ' . array_shift($query) . ' FROM dpx_discount_bundle_group as bg ' . implode(' ', $query);
        $data = $this->db->query_fetch($strSQL, $this->arrBind);

		// echo $strSQL;


		// --- 결과 정리
		$getData['data'] = gd_htmlspecialchars_stripslashes($data);
		$getData['sort'] = $sort;
		$getData['search'] = gd_htmlspecialchars($this->search);
		$getData['checked'] = $this->checked;
		$getData['selected'] = $this->selected;

		return $getData;
	}



    /**
     * setSearchBundleList
     *
     * @param $searchData
     * @param int $searchPeriod
	 * @sjlee
     */
    public function setSearchBundleList($searchData, $searchPeriod = '-1')
    {
        // 검색을 위한 bind 정보
        $fieldType = DBTableField::getFieldTypes('tableBundleDiscountGoods');
        /* @formatter:off */
        $this->search['combineSearch'] =[
            'main.goodsNm' => __('상품명'),
            'bd.mainGoodsNo' => __('상품코드'),
        ];
        /* @formatter:on */

        /* @formatter:off */
        $this->search['sortList'] = [
            'bd.createdAt desc' => __('등록일 ↓'),
            'bd.createdAt asc' => __('등록일 ↑'),
            'main.goodsNm asc' => __('상품명 ↓'),
            'main.goodsNm desc' => __('상품명 ↑'),
            'main.goodsPrice asc' => __('판매가 ↓'),
            'main.goodsPrice desc' => __('판매가 ↑')
        ];
        /* @formatter:on */

        // --- 검색 설정
        $this->search['sort'] = gd_isset($searchData['sort'], 'bd.createdAt desc');
        $this->search['key'] = gd_isset($searchData['key']);
        $this->search['keyword'] = gd_isset($searchData['keyword']);


        if (empty($this->arrBind)) {
            $this->arrBind = null;
        }

    }

	/**
     * setSearchDiscountBundleGroupList
     *
     * @param $searchData
     * @param int $searchPeriod
     */
    public function setSearchDiscountBundleGroupList($searchData, $searchPeriod = '-1')
    {
        // 검색을 위한 bind 정보
        // $fieldType = DBTableField::getFieldTypes('tableBundleDiscountGoods');
        /* @formatter:off */
        $this->search['combineSearch'] =[
            'groupCd' => __('그룹코드'),
			'groupNm' => __('그룹명'),
            'goodsNo' => __('상품코드'),
        ];
        /* @formatter:on */

        /* @formatter:off */
        $this->search['sortList'] = [
            'bg.regDt desc' => __('등록일 ↓'),
            'bg.regDt asc' => __('등록일 ↑'),
            'bg.groupNm asc' => __('그룹명 ↓'),
            'bg.groupNm desc' => __('그룹명 ↑')
        ];
        /* @formatter:on */

		$getValue = Request::get()->toArray();

        // --- 검색 설정
        $this->search['sort'] = gd_isset($searchData['sort'], 'bg.regDt desc');
        $this->search['key'] = gd_isset($getValue['key']);
        $this->search['keyword'] = gd_isset($getValue['keyword']);
		// $this->search['allowNoBundleSale'] = gd_isset($searchData['allowNoBundleSale']);
		$this->search['allowNoBundleSale'] = gd_isset($searchData['allowNoBundleSale'], $getValue['key']? '' : 'all');

		$this->checked['allowNoBundleSale'][ $this->search['allowNoBundleSale']] = "checked='checked'";
		


		// 검색어 검색 
		if ($this->search['key'] && $this->search['keyword']) {
			switch ($this->search['key']) {
				case 'groupCd':
					$this->arrWhere[] = 'bg.groupCd LIKE ?';
					$this->db->bind_param_push($this->arrBind, 's', '%' .trim($this->search['keyword']). '%');
					break;

				case 'groupNm':
					$this->arrWhere[] = 'bg.groupNm LIKE ?';
					$this->db->bind_param_push($this->arrBind, 's', '%' .trim($this->search['keyword']). '%');
					break;

				case 'goodsNo':
					$this->arrWhere[] = 'EXISTS (
						SELECT 1 FROM dpx_discount_bundle_group_goods bgg
						WHERE bgg.groupCd = bg.groupCd AND bgg.goodsNo = ?
					)';
					$this->db->bind_param_push($this->arrBind, 'i', $this->search['keyword']);
					break;
			}
		}

		// 결합 할인 실패시 검색 
		if ($this->search['allowNoBundleSale'] != 'all') {
			$this->arrWhere[] = 'bg.allowNoBundleSale = ?';
			$this->db->bind_param_push($this->arrBind, 's', $this->search['allowNoBundleSale']);
		}



        if (empty($this->arrBind)) {
            $this->arrBind = null;
        }

    }


	/**
     * getDiscountBundleGroup
     *
	 * @sjlee
     */
	public function getDiscountBundleGroup($sno = null)
	{
		if (!$sno) {
            // 기본 정보
            $data['mode'] = 'register';
            // 기본값 설정
            DBTableField::setDefaultData('tableDiscountBundleGroup', $data);
            $discountBundleGroupList = array();

            // --- 수정인 경우
        } else {
            // 추가상품 정보
            $data = $this->getInfoDiscountBundleGroup($sno);

            $discountBundleGroupGoodsList = $this->getInfoDiscountBundleGroupGoods($data['groupCd']);

            $data['mode'] = 'modify';

            // 기본값 설정
            DBTableField::setDefaultData('tableDiscountBundleGroup', $data);
        }

        $checked = array();

        if ($data['scmNo'] == DEFAULT_CODE_SCMNO) $data['scmFl'] = "n";
        else  $data['scmFl'] = "y";

        $getData['data'] = $data;
        $getData['discountBundleGroupGoodsList'] = $discountBundleGroupGoodsList;
        $checked['scmFl'][$data['scmFl']] = "checked = 'checked'";
		$checked['allowNoBundleSale'][$data['allowNoBundleSale']] = "checked = 'checked'";
		$checked['showNoBundlePopup'][$data['showNoBundlePopup']] = "checked = 'checked'";
		$checked['showCartBtnForBundle'][$data['showCartBtnForBundle']] = "checked = 'checked'";
		$checked['preCartBundlePopup'][$data['preCartBundlePopup']] = "checked = 'checked'";

        $getData['checked'] = $checked;

        return $getData;	
	}


	/**
     * getInfoDiscountBundleGroup
     *
	 * @sjlee
     */
	public function getInfoDiscountBundleGroup($sno = null, $goodsField = null, $arrBind = null, $dataArray = false)
	{
		if ($sno) {
            if ($this->db->strWhere) {
                $this->db->strWhere = " dbg.sno = ? AND " . $this->db->strWhere;
            } else {
                $this->db->strWhere = " dbg.sno = ?";
            }
            $this->db->bind_param_push($arrBind, 'i', $sno);
        }

		$query = $this->db->query_complete();
        $strSQL = 'SELECT ' . array_shift($query) . ' FROM dpx_discount_bundle_group dbg ' . implode(' ', $query);
        $getData = $this->db->query_fetch($strSQL, $arrBind);

        if (count($getData) == 1 && $dataArray === false) {
            return gd_htmlspecialchars_stripslashes($getData[0]);
        }

        return gd_htmlspecialchars_stripslashes($getData);
	}


	/**
     * getInfoDiscountBundleGroupGoods
     *
	 * @sjlee
     */
	public function getInfoDiscountBundleGroupGoods($groupCd,$arrBind = null)
	{
		// $getValue = Request::get()->toArray();
		// if($getValue['goodsNo']){
		// 	$this->arrWhere[] = "dbgg.goodsNo='".$getValue['goodsNo']."'";
		// }
		if($groupCd != ''){
			$this->arrWhere[] = "dbgg.groupCd='".$groupCd."'";
		}
		

        $join[] =  ' INNER JOIN dpx_discount_bundle_group as dbg ON dbg.groupCd = dbgg.groupCd ';
        $join[] =  ' INNER JOIN es_goods as g ON g.goodsNo = dbgg.goodsNo ';
		$join[] =  ' INNER JOIN es_scmManage as sm ON sm.scmNo = g.scmNo ';

        $this->db->strJoin = implode('', $join);
        $this->db->strOrder = "dbgg.sno asc";
        $this->db->strField = "dbg.*, g.*,sm.companyNm as scmNm, (select gi.imageName from es_goodsImage gi where gi.goodsNo = dbgg.goodsNo limit 1) as imageName, dbgg.bundleType, dbgg.sno as dbgg_sno ";
        $this->db->strWhere = implode(' AND ', gd_isset($this->arrWhere));

        $query = $this->db->query_complete();
        $strSQL = 'SELECT ' . array_shift($query) . ' FROM dpx_discount_bundle_group_goods as dbgg ' . implode(' ', $query);
        $data = $this->db->query_fetch($strSQL);

		// echo '<pre>'.$strSQL;

        return $data;
	}

	/**
     * saveInfoDiscountBundleGroup
     *
     * @param $arrData
     * @throws Except
	 * @sjlee
     */
	public function saveInfoDiscountBundleGroup($arrData)
	{
		// 그룹명 체크
        if (Validator::required(gd_isset($arrData['groupNm'])) === false) {
            throw new \Exception(__('그룹명은 필수 항목입니다.'), 500);
        }

		$arrData['addGoodsCnt'] = count($arrData['addGoodsNoData']);

        // 정보 저장
        if ($arrData['mode'] == 'group_modify') {

            if (Validator::required(gd_isset($arrData['sno'])) === false) {
                throw new Except('REQUIRE_VALUE', sprintf(__('%s은(는) 필수 항목 입니다.'), '추가상품 그룹번호'));
            }

            $arrBind = $this->db->get_binding(DBTableField::tableDiscountBundleGroup(), $arrData, 'update');
            $this->db->bind_param_push($arrBind['bind'], 's', $arrData['sno']);
            $this->db->set_update_db('dpx_discount_bundle_group', $arrBind['param'], 'sno = ?', $arrBind['bind']);


        } else {
            $arrData['groupCd'] = $this->newGroupCode();
            $arrBind = $this->db->get_binding(DBTableField::tableDiscountBundleGroup(), $arrData, 'insert');
            $this->db->set_insert_db('dpx_discount_bundle_group', $arrBind['param'], $arrBind['bind'], 'y');
            $arrData['sno'] = $this->db->insert_id();
        }

		
        if ($arrData['addGoodsNoData']) {
			
            //관련 상품 새로 지우고 새로 등록
            $this->db->set_delete_db('dpx_discount_bundle_group_goods', 'groupCd = "' . $arrData['groupCd'] . '"');

            foreach ($arrData['addGoodsNoData'] as $k => $v) {
                $groupDatap['groupCd'] = $arrData['groupCd'];
                $groupDatap['goodsNo'] = $v;
				$groupDatap['bundleType'] = $arrData['bundleType'][$k];
                $arrBind = $this->db->get_binding(DBTableField::tableDiscountBundleGroupGoods(), $groupDatap, 'insert');
                $this->db->set_insert_db('dpx_discount_bundle_group_goods', $arrBind['param'], $arrBind['bind'], 'y');
            }
        }


        unset($arrBind);

        if ($arrData['mode'] == 'modify') {
            // 전체 로그를 저장합니다.
            // LogHandler::wholeLog('add_goods', null, 'modify', $arrData['addGoodsNo'], $arrData['goodsNm']);
        }
	}

	/**
     * newGroupCode (그룹코드 생성)
     *
     * @return string
	 * @sjlee
     */
    private function newGroupCode()
    {
        $strSQL = 'SELECT MAX(substring(groupCd,2)) FROM dpx_discount_bundle_group ';
        list($tmp) = $this->db->fetch($strSQL, 'row');
        return sprintf('%07d', ($tmp + 1));
    }

	/**
     * checkAllowNoBundleSale (결합 상품 판매 가능한지 확인)
     *
     * @return string
	 * @sjlee
     */
	public function checkAllowNoBundleSale($goodsNo)
	{
		// $getValue = Request::get()->toArray();
		// $goodsNo = $getValue['goodsNo'];

		if ($goodsNo) {
    			// $qry = "SELECT dbg.groupCd, count(dbgg.sno)
				// 	FROM es_discount_bundle_group_goods as dbgg
				// 		JOIN es_discount_bundle_group as dbg
				// 			ON dbgg.groupCd = dbg.groupCd
				// 	WHERE dbg.allowNoBundleSale = 'y'
				// 		AND dbgg.goodsNo = ?
				// 	GROUP BY dbg.groupCd
				// 	";

				$qry = "SELECT dbg.*, dbgg.*, b.sno as b_sno, b.bannerImage, b.bannerImageAlt
						FROM dpx_discount_bundle_group_goods as dbgg
							JOIN dpx_discount_bundle_group as dbg ON dbgg.groupCd = dbg.groupCd
							JOIN es_designBanner as b ON dbg.mainCartBannerCode = b.bannerGroupCode
						WHERE dbgg.goodsNo = ?
						limit 1
					";
					
			$arrBind = [];
			$this->db->bind_param_push($arrBind, 's', $goodsNo);
			$result = $this->db->query_fetch($qry, $arrBind);
			return $result;
        }else{
			return;
		}

		
	}

	/**
     * updateBundleType (결합 실패 여부 업데이트 (메인, 결합상품 구분))
     *
     * @return string
	 * @sjlee
     */
	public function updateBundleType($arrData)
	{

		if (count($arrData['itemGoodsNo']) == 0) {
            throw new \Exception(__('설정할 상품을 선택해주세요.'), 500);
        }

		foreach ($arrData['itemGoodsNo'] as $k => $v) {
			$qry = "UPDATE dpx_discount_bundle_group_goods set bundleType = '".$arrData['bundleType']."' where groupCd = '" . $arrData['groupCd'] . "' and goodsNo = ".$v." ";
			$res = $this->db->query($qry);
        }
		return $res;
	}


	public function deleteDiscountBundleGroup($sno, $groupCd)
	{
		$this->db->bind_param_push($arrBind['bind'], 's', $sno);
        $this->db->set_delete_db('dpx_discount_bundle_group', 'sno = ?', $arrBind['bind']);

        unset($this->arrBind);

        $this->db->set_delete_db('dpx_discount_bundle_group_goods', 'groupCd = "' . $groupCd . '"');
	}



}
