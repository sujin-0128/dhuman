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
namespace Component\Database;


use Component\Database\Traits\DBTableDpx; // 튜닝관련
use Component\Database\Traits\Wpay; // WPAY
use Session;


/**
 * DB Table 기본 Field 클래스 - DB 테이블의 기본 필드를 설정한 클래스 이며, prepare query 생성시 필요한 기본 필드 정보임
 * @package Component\Database
 * @static  tableConfig
 */
class DBTableField extends \Bundle\Component\Database\DBTableField
{

	use DBTableDpx;
    use Wpay; //wpay 관련 테이블
    
    /**
     * 플러스 리뷰 게시글
     *
     * @author sj
     * @return array board 테이블 필드 정보
     */
    public static function tablePlusReviewArticle($conf = null)
    {
        // 부모 method 상속
        $arrField = parent::tablePlusReviewArticle($conf);
        
        // 추가 필드
        $arrField[] = ['val' => 'isBestReview', 'typ' => 's', 'def' => 'n']; // 베스트 리뷰
		$arrField[] = ['val' => 'dpxBestReviewFl', 'typ' => 's', 'def' => 'n']; // 메인 베스트 리뷰 선정 : y  미선정 :n
		$arrField[] = ['val' => 'dpxDisplayFl', 'typ' => 's', 'def' => 'y']; // 리뷰 숨김여부  노출 :y  숨김 : n 

		$arrField[] = ['val' => 'dpxGoodsBestReviewFl', 'typ' => 's', 'def' => 'n']; // 상품상세 베스트 리뷰 선정 : y  미선정 :n
		
        return $arrField;
    }

	public static function tableOrder()
    {

        $arrField = parent::tableOrder();

        $arrField[] = ['val' => 'totalSubscribeDcPrice', 'typ' => 'i', 'def' => '0' ,  'name' => '정기결제 총 할인금액'];
        $arrField[] = ['val' => 'subscribePayMethod', 'typ' => 's', 'def' => '' ,  'name' => '정기결제 타입'];
        $arrField[] = ['val' => 'subscribeCnt', 'typ' => 'i', 'def' => '0' ,  'name' => '정기결제 배송회차'];
        $arrField[] = ['val' => 'subscribeMax', 'typ' => 'i', 'def' => '0' ,  'name' => '정기결제 총 회차'];
        $arrField[] = ['val' => 'subscribeCycle', 'typ' => 's', 'def' => '' ,  'name' => '정기결제 배송주기'];
        $arrField[] = ['val' => 'billSno', 'typ' => 'i', 'def' => '0' ,  'name' => '정기결제 빌키'];
        $arrField[] = ['val' => 'startDeliveryDt', 'typ' => 's', 'def' => '' ,  'name' => '고객 희망 발송 요청일'];

		$arrField[] = ['val' => 'lpinfo', 'typ' => 's', 'def' => '' ,  'name' => '링크프라이스 정보'];
		$arrField[] = ['val' => 'user_agent', 'typ' => 's', 'def' => '' ,  'name' => '사용자 에이전트'];


		$arrField[] = ['val' => 'giftFl', 'typ' => 's', 'def' => '' ,  'name' => '선물하기 주문여부'];
		$arrField[] = ['val' => 'giftSms', 'typ' => 's', 'def' => '' ,  'name' => '선물메세지 전달방식'];
		$arrField[] = ['val' => 'giftCard', 'typ' => 'i', 'def' => '0' ,  'name' => '선물메세지 카드'];
		$arrField[] = ['val' => 'giftMemo', 'typ' => 's', 'def' => '' ,  'name' => '선물메세지'];
		$arrField[] = ['val' => 'giftStatus', 'typ' => 's', 'def' => '' ,  'name' => '선물단계'];
		$arrField[] = ['val' => 'dpxAgreeInfo', 'typ' => 's', 'def' => '', 'name' => '마케팅 정보 활용 동의'];
		
		//dpx.farmer 스탬프추가 20230217
		$arrField[] = ['val' => 'dpxStampFl', 'typ' => 's', 'def' => 'n'];
        return $arrField;
    }


	public static function tableOrderGoods() {
        $arrField = parent::tableOrderGoods();

		$arrField[] = ['val' => 'dpxPromotionFl', 'typ' => 's', 'def' => 'n'];
        $arrField[] = ['val' => 'subscribeDcPrice', 'typ' => 'i', 'def' => '0' ,  'name' => '정기결제 할인금액'];

		
        return $arrField;
    }


	public static function tableGoods() {
		$arrField = parent::tableGoods();
		$arrField[] = ['val' => 'subscribeGoodsFl', 'typ' => 's', 'def' => 'n', 'name' => ''];	
		$arrField[] = ['val' => 'dpxSelectFl', 'typ' => 's', 'def' => 'n'];
		$arrField[] = ['val' => 'selectFl', 'typ' => 's', 'def' => 'n'];
		$arrField[] = ['val' => 'dpxSelectCnt', 'typ' => 'i', 'def' => null];
		$arrField[] = ['val' => 'dpxPromotionFl', 'typ' => 's', 'def' => 'n'];
		$arrField[] = ['val' => 'dpxEventFl', 'typ' => 's', 'def' => '' ,  'name' => '이벤트 사용여부'];
		$arrField[] = ['val' => 'dpxEventPrice', 'typ' => 'i', 'def' => '' ,  'name' => '이벤트가'];
		$arrField[] = ['val' => 'dpxEventDayFl', 'typ' => 's', 'def' => '' ,  'name' => '이벤트 사용여부'];
		$arrField[] = ['val' => 'dpxEventDayPrice', 'typ' => 'i', 'def' => '' ,  'name' => '이벤트가'];
		$arrField[] = ['val' => 'dpxEventUseStartDate', 'typ' => 's', 'def' => '' ,  'name' => '이벤트 시작일'];
		$arrField[] = ['val' => 'dpxEventUseEndDate', 'typ' => 's', 'def' => 'y' ,  'name' => '이벤트 종료일'];
		$arrField[] = ['val' => 'dpxEventDateFl', 'typ' => 's', 'def' => 'y' ,  'name' => '이벤트 기간 설정'];
		$arrField[] = ['val' => 'dpxAgreeInfoFl', 'typ' => 's', 'def' => 'n' , 'name' => '마케팅 정보 활용 동의'];

		//designpix.kkamu 20211108 선물상품 설정
		$arrField[] = ['val' => 'useGiftFl', 'typ' => 's', 'def' => 'n' ,  'name' => '선물상품 설정'];

		//dpx.farmer 20211228 기획전 노출가능여부 설정
		$arrField[] = ['val' => 'dpxDisplayFl', 'typ' => 's', 'def' => 'n' ,  'name' => '기획전 노출 설정'];
		$arrField[] = ['val' => 'dpxAppFl', 'typ' => 's', 'def' => 'n' ,  'name' => '앱전용상품 여부'];
        return $arrField;
    }

	//dpx.farmer 필드추가
	public static function tableDisplayTheme()
    {
        // @formatter:off
        $arrField = parent::tableDisplayTheme();
		$arrField[] = ['val' => 'dpxIgnoreFl', 'typ' => 's', 'def' => 'n', 'name' => ''];	

        return $arrField;
    }
	
	public static function tableGoodsSearch()
    {

		$arrField = parent::tableGoodsSearch();
		$arrField[] = ['val' => 'dpxEventFl', 'typ' => 's', 'def' => '' ,  'name' => '이벤트 사용여부'];
		$arrField[] = ['val' => 'dpxEventPrice', 'typ' => 'i', 'def' => '' ,  'name' => '이벤트가'];
		$arrField[] = ['val' => 'dpxEventDayFl', 'typ' => 's', 'def' => '' ,  'name' => '이벤트 사용여부'];
		$arrField[] = ['val' => 'dpxEventDayPrice', 'typ' => 'i', 'def' => '' ,  'name' => '이벤트가'];
		$arrField[] = ['val' => 'dpxEventUseStartDate', 'typ' => 's', 'def' => '' ,  'name' => '이벤트 시작일'];
		$arrField[] = ['val' => 'dpxEventUseEndDate', 'typ' => 's', 'def' => 'y' ,  'name' => '이벤트 종료일'];
		$arrField[] = ['val' => 'dpxEventDateFl', 'typ' => 's', 'def' => 'y' ,  'name' => '이벤트 기간 설정'];

		//designpix.kkamu 20211108 선물상품 설정
		$arrField[] = ['val' => 'useGiftFl', 'typ' => 's', 'def' => 'n' ,  'name' => '선물상품 설정'];

		//dpx.farmer 20211228 기획전 노출가능여부 설정
		$arrField[] = ['val' => 'dpxDisplayFl', 'typ' => 's', 'def' => 'n' ,  'name' => '기획전 노출 설정'];

	return $arrField;
    }
	
	########  Designpix.kkamu 정기결제 #######





	public static function tableDpxTossApiBilling()
	{
		$arrField = [

			['val' => "orderNo", 'typ' => 's', 'def' => ''],			// 주문번호
			['val' => "tid", 'typ' => 's', 'def' => ''],			// 결제 tid
			['val' => "price", 'typ' => 'i', 'def' => '0'],		// 결제금액
			['val' => "resultCode", 'typ' => 's', 'def' => ''],		// 결과코드
			['val' => "resultMsg", 'typ' => 's', 'def' => ''],		// 결과 
			['val' => "payType", 'typ' => 's', 'def' => ''],		// 결제타입
			['val' => "payDate", 'typ' => 's', 'def' => ''],		// 결제일

			['val' => "financeCode", 'typ' => 's', 'def' => ''],		// 
			['val' => "financeName", 'typ' => 's', 'def' => ''],		// 

			['val' => "result", 'typ' => 's', 'def' => ''],		// 결과내용
			['val' => "refundSno", 'typ' => 's', 'def' => ''],		// 결제실패 환불로그
			['val' => "regDt", 'typ' => 's', 'def' => ''],				// 등록일
		];
		return $arrField;
	}


	public static function tableDpxTossApiRefund()
	{
		$arrField = [

			['val' => "orderNo", 'typ' => 's', 'def' => ''],			// 주문번호
			['val' => "tid", 'typ' => 's', 'def' => ''],			// 결제 tid
			['val' => "resultCode", 'typ' => 's', 'def' => ''],		// 결과코드
			['val' => "resultMsg", 'typ' => 's', 'def' => ''],		// 결과 
			['val' => "result", 'typ' => 's', 'def' => ''],		// 결과내용

			['val' => "cancelDate", 'typ' => 's', 'def' => ''],		// 결제일
			['val' => "regDt", 'typ' => 's', 'def' => ''],				// 등록일
		];
		return $arrField;
	}




	//iniStd 인증 반환결과로그 	
	public static function tableDpxTossAuthResult()
	{
		$arrField = [
			['val' => "memNo", 'typ' => 'i', 'def' => '0'],		// 회원번호
			['val' => "authDevice", 'typ' => 's', 'def' => ''],	// 인증타입 pc.mobile 

			['val' => "payDate", 'typ' => 's', 'def' => ''],		// 승인일
			['val' => "payType", 'typ' => 's', 'def' => ''],		 // 승인시간

			['val' => "resultCode", 'typ' => 's', 'def' => ''],		// 결과코드
			['val' => "resultMsg", 'typ' => 's', 'def' => ''],		// 결과메세지
			['val' => "result", 'typ' => 's', 'def' => ''],		// 결과메세지

			['val' => "financeCode", 'typ' => 's', 'def' => ''],					// 금융사 코드
			['val' => "financeName", 'typ' => 's', 'def' => ''],		// 금융사 이름

			['val' => "ipAddress", 'typ' => 's', 'def' => ''],				// 아이피
			['val' => "httpReferer", 'typ' => 's', 'def' => ''],				// 접근경로
			['val' => "userAgent", 'typ' => 's', 'def' => ''],				// 환경

			['val' => "regDt", 'typ' => 's', 'def' => ''],				// 등록일
		];
		return $arrField;
	}



	//billKey 관리 
	public static function tableDpxSubscribeBillKey()
	{
		$arrField = [
			['val' => "memNo", 'typ' => 'i', 'def' => '0'],				// 회원번호
			['val' => "authSno", 'typ' => 'i', 'def' => '0'],				// 승인기록Sno
			['val' => "moid", 'typ' => 's', 'def' => ''],	
			['val' => "tid", 'typ' => 's', 'def' => ''],	

			['val' => "billKey", 'typ' => 's', 'def' => ''],					// 빌키
			['val' => "billType", 'typ' => 's', 'def' => 'card'],					
			['val' => "authDevice", 'typ' => 's', 'def' => ''],	

			['val' => "cardNm", 'typ' => 's', 'def' => ''],				// 카드명
			['val' => "cardNum", 'typ' => 's', 'def' => ''],				// 카드번호
			['val' => "cardCode", 'typ' => 's', 'def' => ''],		// 카드코드
			['val' => "cardBankCode", 'typ' => 's', 'def' => ''],		// 카드은행코드

			['val' => "hppCorp", 'typ' => 's', 'def' => ''],	
			['val' => "hppNum", 'typ' => 's', 'def' => ''],	

			['val' => "useFl", 'typ' => 's', 'def' => 'n'],					// 사용가능여부
			['val' => "regDt", 'typ' => 's', 'def' => ''],						// 등록일
		];
		return $arrField;
	}
	

	//정기배송 주문  기본내용
	public static function tableDpxSubscribe()
	{
		$arrField = [
			['val' => "memNo", 'typ' => 'i', 'def' => '0'],				// 회원번호
			['val' => "baseOrderNo", 'typ' => 's', 'def' => '0'],		// 기준 주문번호
			['val' => "billKeySno", 'typ' => 'i', 'def' => ''],				// 빌키 Sno

			['val' => "subscribeDcType", 'typ' => 's', 'def' => 'p'],				// 정기배송 할인방식 p,w 
			['val' => "subscribeDc", 'typ' => 'i', 'def' => '0'],				// 정기배송 할인액 

			['val' => "subscribePayMethod", 'typ' => 's', 'def' => ''],				// 정기배송 결제방식
			['val' => "subscribeCycle", 'typ' => 's', 'def' => ''],				// 정기배송 주기
			['val' => "subscribeMax", 'typ' => 'i', 'def' => '0'],				// 정기배송 총 회차

			['val' => "subscribeStartDt", 'typ' => 's', 'def' => ''],		// 정기배송 기간 시작일
			['val' => "subscribeEndDt", 'typ' => 's', 'def' => ''],			// 정기배송 기간 종료일

			['val' => "subscribeCancelFl", 'typ' => 's', 'def' => 'n'],			// 정기배송 진행여부
			['val' => "subscribeCancelRequestFl", 'typ' => 's', 'def' => 'n'],			// 정기구독 사용자 취소요청 여부
			['val' => "subscribeCancelRequestDt", 'typ' => 's'],			// 정기구독 사용자 취소요청일

			['val' => "regDt", 'typ' => 's', 'def' => ''],						//등록일
		];
		return $arrField;
	}




	//정기배송  상품 장바구니 
    public static function tableDpxSubscribeCart()
    {
        // @formatter:off
        $arrField = [

            ['val' => 'memNo', 'typ' => 'i', 'def' => '0'],							// 회원 번호
            ['val' => 'directCart', 'typ' => 's', 'def' => 'n'],						// 바로구매 여부

            ['val' => 'goodsNo', 'typ' => 'i', 'def' => 's'],						// 상품 번호
            ['val' => 'optionSno', 'typ' => 'i', 'def' => null],					// 옵션 번호 (sno)
            ['val' => 'goodsCnt', 'typ' => 'i', 'def' => 1],							// 상품 수량
            ['val' => 'addGoodsNo', 'typ' => 's', 'def' => null],				// 추가 상품 sno 정보 (json_encode(sno))
            ['val' => 'addGoodsCnt', 'typ' => 's', 'def' => null],				// 추가 상품 수량 정보 (json_encode(수량))
            ['val' => 'optionText', 'typ' => 's', 'def' => null],					// 텍스트 옵션 정보 (json_encode(sno, 내용))
            ['val' => 'deliveryCollectFl', 'typ' => 's', 'def' => 'pre'],		// 배송비 결제방법 (pre - 선불, later - 착불)
            ['val' => 'deliveryMethodFl', 'typ' => 's', 'def' => 'delivery'], // 배송방식
            ['val' => 'memberCouponNo', 'typ' => 's', 'def' => ''],		// 회원 쿠폰 번호 (상품 쿠폰) INI_DIVISION 구분자로 생성된 쿠폰 STRING
            ['val' => 'tmpOrderNo', 'typ' => 's', 'def' => null],				// 주문번호 (주문 후 삭제를 위한 키)
        ];
        // @formatter:on
        return $arrField;
    }



	//회차별 주문예정관리- 완료주문번호 생성전 수정가능, 주문정보에 연락처, 상품변경
	public static function tableDpxSubscribeOrder()
	{
		$arrField = [

			['val' => "subscribeSno", 'typ' => 'i', 'def' => '0'],		// 정기배송 주문 기본 Sno
			['val' => "memNo", 'typ' => 'i', 'def' => ''],		// 회원번호
			['val' => "orderNo", 'typ' => 's', 'def' => ''],				// 완료주문번호

			['val' => "subscribeDt", 'typ' => 's', 'def' => ''],				// 정기배송 주문예정일
			['val' => "subscribePrice", 'typ' => 'i', 'def' => '0'],			// 정기배송  결제 금액
			['val' => "subscribeCnt", 'typ' => 'i', 'def' => '0'],				// 정기배송 회차
			['val' => "subscribeMax", 'typ' => 'i', 'def' => '0'],				// 정기배송 총회차
			['val' => "subscribeWeek", 'typ' => 's', 'def' => ''],				// 정기배송 주문예정요일
			['val' => "subscribeGoodsNm", 'typ' => 's', 'def' => ''],	// 정기배송 상품명


			['val' => "orderPrice", 'typ' => 'i', 'def' => '0'],			// 일반배송 금액
			['val' => "deliveryPrice", 'typ' => 'i', 'def' => '0'],			// 배송비
			['val' => "deliveryAreaPrice", 'typ' => 'i', 'def' => '0'],			// 지역배송비
			['val' => "deliveryFreePrice", 'typ' => 'i', 'def' => '0'],			// 지역배송비

			['val' => "subscribeDcPrice", 'typ' => 'i', 'def' => '0'],			// 정기배송 할인 금액

			['val' => "receiverNm", 'typ' => 's', 'def' => ''],				// 주문자정보 이름
			['val' => "receiverTel", 'typ' => 's', 'def' => ''],					// 주문자정보 연락처
			['val' => "receiverEmail", 'typ' => 's', 'def' => ''],			// 주문자정보 이메일
			['val' => "receiverZonecode", 'typ' => 's', 'def' => ''],		// 주문자정보 우편번호 5자리
			['val' => "receiverAddr1", 'typ' => 's', 'def' => ''],			// 주문자정보 주소1
			['val' => "receiverAddr2", 'typ' => 's', 'def' => ''],			// 주문자정보 주소2
			['val' => "orderMemo", 'typ' => 's', 'def' => ''],			// 주문자정보 요청메모

			['val' => "cancelRequestFl", 'typ' => 's', 'def' => 'n'],	// 사용자 정기주문예정 취소 신청
			['val' => "orderFl", 'typ' => 's', 'def' => 'n'],				// 주문서 생성 여부
			['val' => "payFl", 'typ' => 's', 'def' => 'n'],				// 결제 여부
			['val' => "cancelFl", 'typ' => 's', 'def' => 'n'],				// 중도취소 여부
			['val' => "smsSendFl", 'typ' => 's', 'def' => 'n'],				// 정기배송 발송안내 발송여부

			['val' => "smsSendDt", 'typ' => 's', 'def' => ''],				// 정기배송 발송안내 발송일
			['val' => "modDt", 'typ' => 's', 'def' => ''],					// 수정일
			['val' => "regDt", 'typ' => 's', 'def' => ''],						// 등록일
		];

		return $arrField;
	}


	//정기배송 주문예정관리 관련 상품 장바구니 
    public static function tableDpxSubscribeOrderCart()
    {
        // @formatter:off
        $arrField = [

            ['val' => 'subscribeSno', 'typ' => 'i', 'def' => ''],						// 정기배송 주문일정관리 Sno
            ['val' => 'subscribeOrderSno', 'typ' => 's', 'def' => ''],							// 정기배송 장바구니키

            ['val' => 'memNo', 'typ' => 'i', 'def' => '0'],							// 회원 번호
            ['val' => 'goodsNo', 'typ' => 'i', 'def' => 's'],						// 상품 번호
            ['val' => 'optionSno', 'typ' => 'i', 'def' => null],					// 옵션 번호 (sno)
            ['val' => 'goodsCnt', 'typ' => 'i', 'def' => 1],							// 상품 수량
            ['val' => 'addGoodsNo', 'typ' => 's', 'def' => null],				// 추가 상품 sno 정보 (json_encode(sno))
            ['val' => 'addGoodsCnt', 'typ' => 's', 'def' => null],				// 추가 상품 수량 정보 (json_encode(수량))
            ['val' => 'optionText', 'typ' => 's', 'def' => null],					// 텍스트 옵션 정보 (json_encode(sno, 내용))
            ['val' => 'deliveryCollectFl', 'typ' => 's', 'def' => 'pre'],		// 배송비 결제방법 (pre - 선불, later - 착불)
            ['val' => 'deliveryMethodFl', 'typ' => 's', 'def' => 'delivery'], // 배송방식
            ['val' => 'memberCouponNo', 'typ' => 's', 'def' => ''],		// 회원 쿠폰 번호 (상품 쿠폰) INI_DIVISION 구분자로 생성된 쿠폰 STRING
            ['val' => 'tmpOrderNo', 'typ' => 's', 'def' => null],				// 주문번호 (주문 후 삭제를 위한 키)

        ];
        // @formatter:on
        return $arrField;
    }



	//정기 배송 sms 로그
	public static function tableDpxSubscribeSmsLog()
	{
		$arrField = [
			['val' => "mode", 'typ' => 's', 'def' => ''],	
			['val' => "subscribeOrderSno", 'typ' => 'i', 'def' => '0'],
			['val' => "contents", 'typ' => 's', 'def' => ''],	
			['val' => "result", 'typ' => 's', 'def' => ''],		
		];
		return $arrField;
	}


	public static function setTableField($funcName, $arrInclude = null, $arrExclude = null, $prefix = null)
	{
		if( $funcName == 'tableGoods'){
			$arrInclude2 = ['dpxSelectFl'];
			$arrInclude = array_merge($arrInclude, $arrInclude2);

			$arrInclude3 = ['selectFl'];
			$arrInclude = array_merge($arrInclude, $arrInclude3);
		}
		$setField = parent::setTableField($funcName, $arrInclude, $arrExclude, $prefix);
		return $setField;
	}





	//designpix.kkamu 20211022.s 선물하기

    public static function tableDpxGift()
    {

        $arrField = [
            ['val' => 'orderNo', 'typ' => 's', 'def' => ''],
            ['val' => 'cardSno', 'typ' => 'i', 'def' => '0'],
            ['val' => 'cardMsg', 'typ' => 's', 'def' => ''],

            ['val' => 'giftKey', 'typ' => 's', 'def' => ''],
            ['val' => 'giftUrl', 'typ' => 's', 'def' => ''],
            ['val' => 'giftType', 'typ' => 's', 'def' => 'kakao'],
            ['val' => 'giftName', 'typ' => 's', 'def' => ''],
            ['val' => 'giftPhone', 'typ' => 's', 'def' => ''],
            ['val' => 'giftDeliveryMemo', 'typ' => 's', 'def' => ''],
            ['val' => 'giftReceiveFl', 'typ' => 's', 'def' => ''],
            ['val' => 'expireFl', 'typ' => 's', 'def' => 'n'],
            ['val' => 'expireDt', 'typ' => 's', 'def' => ''],
        ];

        return $arrField;
    }


	public static function tableDpxGiftLog()
    {
        
        $arrField = [
			['val' => 'mode', 'typ' => 's', 'def' => ''], 
			['val' => 'orderNo', 'typ' => 's', 'def' => ''], 
			['val' => 'giftSno', 'typ' => 'i', 'def' => 0], 
			['val' => 'result', 'typ' => 's', 'def' => ''], 
		];

        return $arrField;
    }

	public static function tableDpxGiftBenefitLog()
    {
        
        $arrField = [
			['val' => 'mode', 'typ' => 's', 'def' => ''], 
			['val' => 'benefit', 'typ' => 's', 'def' => ''], 
			['val' => 'memNo', 'typ' => 'i', 'def' => 0], 
			['val' => 'result', 'typ' => 's', 'def' => ''], 
		];

        return $arrField;
    }



	public static function tableDpxGiftSmsLog()
    {
        
        $arrField = [
			['val' => 'mode', 'typ' => 's', 'def' => ''], 
			['val' => 'smsType', 'typ' => 's', 'def' => ''], 
			['val' => 'orderNo', 'typ' => 's', 'def' => ''], 
			['val' => 'contents', 'typ' => 's', 'def' => ''], 
			['val' => 'result', 'typ' => 's', 'def' => ''], 
		];

        return $arrField;
    }



    public static function tableDpxCard()
    {
        $arrField = [
            ['val' => 'cardGroup', 'typ' => 'i', 'def' => 0],
            ['val' => 'cardDesc', 'typ' => 's', 'def' => ''],
            ['val' => 'cardImg', 'typ' => 's', 'def' => ''],
        ];
        return $arrField;
    }




    public static function tableDpxCardGroup()
    {
        $arrField = [
            ['val' => 'cardNm', 'typ' => 's', 'def' => ''],
            ['val' => 'useFl', 'typ' => 's', 'def' => 'y'],
        ];
        return $arrField;
    }



	public static function tableCart()
    {

        $arrField = parent::tableCart();

        $arrField[] = ['val' => 'giftFl', 'typ' => 's', 'def' => '' ,  'name' => '선물하기 상품 여부'];
        $arrField[] = ['val' => 'fixCouponFl', 'typ' => 's', 'def' => '' ,  'name' => '고정쿠폰 해제불가'];
        return $arrField;
    }

	//designpix.kkamu 20211022.e 선물하기









}