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

use Component\Database\DBTableField;
use Framework\Utility\ArrayUtils;
use Framework\Utility\ImageUtils;
use Framework\Utility\SkinUtils;
use Request;
use Session;
use App;
use Exception;
use Globals;
use Framework\Utility\DateTimeUtils;

use Component\Member\MemberSnsService;
use Component\Member\Member;
use Component\Member\Group\Util as GroupUtil;
use Component\Member\Util\MemberUtil;
use Component\Sms\Code;
use Component\Sms\SmsAuto;
use Component\Sms\SmsAutoCode;
use Component\Sms\SmsAutoObserver;
use Component\Validator\Validator;

use Component\Godo\GodoKakaoServerApi;
use Component\Policy\KakaoLoginPolicy;
use Framework\Debug\Exception\AlertCloseException;


class KakaoSync
{


    protected $db;
    private $memberDAO;

	private $syncUrl = 'https://m.dhuman.co.kr/member/kakao/kakao_login.php';

    public function __construct()
    {
        if (!is_object($this->db)) {
            $this->db = App::load('DB');
        }

		$this->basicInfo = gd_policy('basic.info'); 
        $this->memberDAO = \App::load(\Component\Member\MemberDAO::class);
    }


	protected function getNormalMemberNo($nm='', $phone='', $birth='') {
		
		if(empty($nm) || empty($phone) || empty($birth)){
			return false; 
		}

        $arrBind = [];
        $strSQL = 'SELECT memNo FROM es_member  WHERE appFl="y" and sleepFl ="n"  and memNm = ? and cellPhone = ? and birthDt = ?  order by memNo desc limit 1    ';
        $this->db->bind_param_push($arrBind, 's', $nm);
        $this->db->bind_param_push($arrBind, 's', $phone);
        $this->db->bind_param_push($arrBind, 's', $birth);
        $data = $this->db->query_fetch($strSQL, $arrBind, false);

        $arrBind = [];
        $strSQL2 = 'SELECT m.memNo 
					FROM es_member As m 
						RIGHT JOIN es_memberSns AS ms
							ON m.memNo = ms.memNo
					WHERE appFl="y" and sleepFl ="n" and memNm = ? and cellPhone = ? and birthDt = ?  order by memNo desc limit 1';
        $this->db->bind_param_push($arrBind, 's', $nm);
        $this->db->bind_param_push($arrBind, 's', $phone);
        $this->db->bind_param_push($arrBind, 's', $birth);
        $data2 = $this->db->query_fetch($strSQL2, $arrBind, false);

		if($data['memNo']>0){
			if($data2['memNo']>0) {
				throw new AlertCloseException("다른 SNS 아이디로 회원가입하신 회원은 카카오 회원가입을 사용하실 수 없습니다.");
				return false;
			} else {		
				return $data['memNo']; 
			}
		}

	}


	protected function getSnsMemberNo($snsId='') {
		

        $arrBind = [];
        $strSQL = 'SELECT memNo FROM es_member  WHERE memId = ?  order by memNo desc limit 1    ';
        $this->db->bind_param_push($arrBind, 's', $snsId);
        $data = $this->db->query_fetch($strSQL, $arrBind, false);

		if($data['memNo']>0){
			return $data['memNo']; 
		}

	}



    public function joinKakaoSync($userInfo)
    {
        $session = \App::getInstance('session');
        $globals = \App::getInstance('globals');

		switch($userInfo['kakao_account']['gender']){
			case 'male': $sexFl = 'm'; break;
			case 'female': $sexFl = 'w'; break;
			default : $sexFl = ''; break;			
		}

		$memNm = ($userInfo['kakao_account']['name'])?$userInfo['kakao_account']['name'] :$userInfo['kakao_account']['profile']['nickname']; 
		$cellPhone =  str_replace("+82 ","0",$userInfo['kakao_account']['phone_number']); 
		$birthDay = []; 
		$birthDay[] = $userInfo['kakao_account']['birthyear']; 
		$birthDay[] = substr($userInfo['kakao_account']['birthday'],0,2) ; 
		$birthDay[] = substr($userInfo['kakao_account']['birthday'],2,2) ; 

		$birthDt = implode("-", $birthDay);
		
		
		// 3항목 매칭으로 회원번호 호출. 존재할경우 해당 회원번호에 카카오싱크 연결처리 
		$memNo = $this->getNormalMemberNo($memNm, $cellPhone, $birthDt) ; 

		$memberFl = false; 

		$snsId = 'ks_'.$userInfo['id']; 

		if( $memNo > 0){

			$this->syncResult('join-conn', $snsId); 		
			$memberFl = true; 

		}else {

			// 중복아이디 방지 20220118.s
			$existMemNo = $this->getSnsMemberNo($snsId);

			if($existMemNo){
				return true; 
			}
			// 중복아이디 방지 20220118.e


			//동기화 회원 없는경우 신규처리 
			$this->syncResult('join-new', $snsId); 

			$params['kakaoSyncFl']	= "y";    
			$params['appFl']				= "y";    
			$params['memId']			= $snsId;
			$params['memPw']			= $userInfo['id'];
			$params['memNm']			= $memNm;
			$params['nickNm']			= $userInfo['kakao_account']['profile']['nickname'];
			$params['email']				= $userInfo['kakao_account']['email'];
			$params['birthDt']			=	$birthDt; 
			$params['sexFl']			=	$sexFl ; 
			$params['cellPhone']		= $cellPhone;
			$params['smsFl']				= "y";


			$vo = $params;
			if (is_array($params)) {
				DBTableField::setDefaultData('tableMember', $params);
				$vo = new \Component\Member\MemberVO($params);
			}

			$vo->databaseFormat();
			$vo->setEntryDt(date('Y-m-d H:i:s'));
			$vo->setGroupSno(GroupUtil::getDefaultGroupSno());

			$hasKakaoUserProfile = $session->has(GodoKakaoServerApi::SESSION_USER_PROFILE);

			$passValidation = $hasKakaoUserProfile;

			$member = $vo->toArray();

			if ($member['appFl'] == 'y') {
				$member['approvalDt'] = date('Y-m-d H:i:s');
			}


			if ($hasKakaoUserProfile) {
				$memNo = $this->memberDAO->insertMemberByThirdParty($member);

				$member['memNo'] = $memNo;

			}
	
 

			$session->set(Member::SESSION_NEW_MEMBER, $member['memNo']);


			if ($vo->isset($member['cellPhone'])) {
		
				$members = \App::load(\Component\Member\Member::class);

	           $members->benefitJoin(new \Component\Member\MemberVO($member));


				$aBasicInfo = gd_policy('basic.info');
				$aMemInfo = $members->getMemberId($memNo);
				$smsAuto = \App::load('\\Component\\Sms\\SmsAuto');
				$observer = new SmsAutoObserver();

				$replaceArguments =	
					[
						'name'      => $member['memNm'],
						'memNm'     => $member['memNm'],
						'memId'     => $member['memId'],
						'appFl'     => $member['appFl'],
						'groupNm'   => $aMemInfo['groupNm'],
						'mileage'   => 0,
						'deposit'   => 0,
						'rc_mallNm' => Globals::get('gMall.mallNm'),
						'shopUrl'   => $aBasicInfo['mallDomain'],
					];


				if ($smsAuto->useObserver()) {
					$observer->setSmsType(SmsAutoCode::MEMBER);
					$observer->setSmsAutoCodeType(Code::JOIN);
					$observer->setReceiver($member);
					$observer->setReplaceArguments($replaceArguments);
					$smsAuto->attach($observer);
				}else{
					$smsAuto = new SmsAuto();
					$smsAuto->setSmsType(SmsAutoCode::MEMBER);
					$smsAuto->setSmsAutoCodeType(Code::JOIN);
					$smsAuto->setReceiver($member);
					$smsAuto->setReplaceArguments($replaceArguments);
					$smsAuto->autoSend();				
				}
			}

		}


		// 카카오 sns 자동가입 
		$kakaoToken = $session->get(GodoKakaoServerApi::SESSION_ACCESS_TOKEN);
		$kakaoProfile = $session->get(GodoKakaoServerApi::SESSION_USER_PROFILE);
		$session->del(GodoKakaoServerApi::SESSION_ACCESS_TOKEN);
		$session->del(GodoKakaoServerApi::SESSION_USER_PROFILE);
		$memberSnsService = new MemberSnsService();
		$memberSnsService->joinBySns($memNo, $kakaoProfile['id'], $kakaoToken['access_token'], 'kakao');



		if($memberFl ){
			//member.kakaoSyncFl 동기화 처리 
			$this->db->bind_query('update es_member set kakaoSyncFl = "y", modDt = now()  where memNo = ?', ['i', $memNo] );
		}

		return $memberFl; 

	}	








	//카카오 연결해제 
	public function disconnectKakao() {

		$mb = Session::get('member'); 

		if(empty($mb['memNo'])) return false; 
		
        $arrBind = [];
        $qry = "SELECT * FROM es_memberSns  WHERE snsTypeFl = 'kakao' and connectFl ='y' and memNo = ? ";
        $this->db->bind_param_push($arrBind, 'i', $mb['memNo']);
        $sns = $this->db->query_fetch($qry, $arrBind, false);		

		if($sns['sno']){
			$this->db->bind_query('update es_memberSns set connectFl = "n", modDt = now()  where sno = ?', ['i', $sns['sno']] );
			
			return $this->db->affected_rows(); 
		}
	}








	public function syncResult($mode, $msg) {

		if(is_array($msg) || is_object($msg) ){
			ob_start();
			print_r( $msg );
			$ob_msg = ob_get_contents();
			ob_clean();
		}else{
			$ob_msg = $msg;
		}

		$arrBind =[]; 
		$setData['kakaoType'] = $mode ; 
		$setData['result'] = $ob_msg ; 
		$setData['url'] = \Request::server()->get('REQUEST_URI') ; 
		$setData['ipAddr'] = \Request::server()->get('REMOTE_ADDR') ; 

		$arrBind = $this->db->get_binding(DBTableField::tableDpxSyncResult(), $setData, 'insert');
		$this->db->set_insert_db("dpx_syncResult", $arrBind['param'], $arrBind['bind'], 'y', false);
	}







	## controller 자동로그인 연동	20220113.s

	public function setAutoLogin() {


            if (! preg_match('/KAKAOTALK/', \Request::getUserAgent())) {
                return false;
            }




		$session = \App::getInstance('session');


            if($session->get('kakaoTalkFl')){
                return false;
            }



		$session->set('kakaoTalkFl', 'y');

        $kakaoApi = new GodoKakaoServerApi();

        $policy = gd_policy(KakaoLoginPolicy::KEY);

		$redirectUri = $this->syncUrl ;

        $state = json_encode($state, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);


        $params = [
            'client_id'=>$policy['restApiKey'],
            'redirect_uri' => $redirectUri,
            'response_type' =>"code",
            'prompt' =>"none",
        ];
		$kakaoUrl = $kakaoApi->getKakaoUriWithProtocol('getCode'). "?" . http_build_query($params);


        $params['kakaoUrl'] = $kakaoUrl ;
		$this->syncResult('auto-login', $params); 	

		return $kakaoUrl; 
	}




	public function autoLoginLogout(){

		//테스트시 주석 풀기
		//Session::del('kakaoTalkFl');
		Session::del('kakaoTalkUrl');
		
	}

	




	## 카카오 API 전송 
    public function curlSend($url, $post = true, $access_token='')
    {


        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

		if($post){
	        curl_setopt($ch, CURLOPT_POST, TRUE);
		}else{
	        curl_setopt($ch, CURLOPT_POST, FALSE);
		}

		if($access_token){
	        curl_setopt($ch, CURLOPT_HTTPHEADER,  array('Authorization: Bearer '.$access_token));
		}

        $response = curl_exec($ch);

        curl_close($ch);

        $response = json_decode($response, true);

		return $response; 
	}


	## controller 자동로그인 연동	20220113.e


}



