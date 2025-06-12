<?php

namespace Controller\Mobile\Member\Kakao;

use Component\Member\MemberSnsService;
use Component\Member\MyPage;
use Component\Godo\GodoKakaoServerApi;
use Component\Attendance\AttendanceCheckLogin;
use Component\Member\Util\MemberUtil;
use Component\Member\Member;
use Exception;
use Framework\Debug\Exception\AlertRedirectException;
use Framework\Debug\Exception\AlertCloseException;
use Component\Member\MemberSnsDAO;
use Request;

/**
 * Class KakaoLoginController
 * @package Bundle\Controller\Mobile\Member\Kakao
 */
class KakaoLoginController extends \Bundle\Controller\Mobile\Member\Kakao\KakaoLoginController
{
    protected $client_id = '018e19e9961eb700a305aaed1ba51bce'; // REST KEY 쇼핑몰 마다 수정 바람

    public function index()
    {




            $request = \App::getInstance('request');
            $code = $request->get()->get('code');
            $memId = $request->get()->get('memId');
            $autologin = $request->get()->get('saveAutoLogin'); //250227 회원정보 변경시 인증오류로 PC와 동일하게 작업해놓음 원본 :: $autologin = 'y';
            $session = \App::getInstance('session');
            $returnUrl1 = $request->get()->get('returnUrl');
            $no = \Cookie::get('no');
            $response = '';
            $state1 = $request->get()->get('state');
            $db = \App::load('DB');

            $state1 = explode('^|^', $state1);

            if ($no && !$code && $autologin == 'y') {
                $snsno = \App::load("\\Component\\Wm\\Wm");
                $end = $snsno->getToken($no);
                $memId = $end['uuid'];
            }


            if ((!$returnUrl1 && ($state1[sizeof($state1) - 1] == "n" || $state1[sizeof($state1) - 1] == "y")) && !$memId) { // 자동로그인 여부 검사

                $redirect_uri = $request->getScheme() . '://' . 'm.dhuman.co.kr/member/kakao/kakao_login.php'; // redirect_uri 쇼핑몰 마다 수정 바람
                $token_request = "https://kauth.kakao.com/oauth/token?grant_type=authorization_code&client_id={$this->client_id}&redirect_uri={$redirect_uri}&code={$code}";
                $isPost = false;
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $token_request);
                curl_setopt($ch, CURLOPT_POST, $isPost);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $loginResponse = curl_exec($ch);
                curl_close($ch);
                $loginToken = json_decode($loginResponse, true);
                $member_url = "https://kapi.kakao.com/v2/user/me";
                $accessToken = json_decode($loginResponse)->access_token; //Access Token만 따로 뺌
                // 웹앤모바일 추가 21-10-21 - 동일한 이메일 회원이 있는 경우 예외 처리
                $refresh_token = json_decode($loginResponse)->refresh_token;
                // 회원정보 받아오기
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $member_url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POST, $isPost);
                curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Bearer ' . $accessToken));
                $response1 = curl_exec($ch);
                curl_close($ch);
                // 회원 정보
                $response = json_decode($response1, true);

            }
            if ($memId) $response['id'] = $memId;

            $logger = \App::getInstance('logger');
            $logger->info(sprintf('start controller: %s', __METHOD__));

            $in = \Request::Request()->all();

            $kakaoApi = new GodoKakaoServerApi();
            $memberSnsService = new MemberSnsService();

            $functionName = 'popup';
            if (gd_is_skin_division()) {
                $functionName = 'gd_popup';
            }

			//250227 회원정보 변경시 인증오류로 주석처리함. 시작
            //if ($in['kakaoType'] == 'my_page_password') {
            //   $in['mode'] = 'mypage';
            //}

            //if ($in['kakaoType'] == 'hack_out') {
            //    $in['mode'] = 'mypage';
            //}
			//250227 회원정보 변경시 인증오류로 주석처리함. 끝
            
            // 마이페이지 회원 정보 수정 검증
            if ($in['mode'] == 'mypage') {//250227 회원정보 변경시 인증오류 수정함 원본 ::  if ($in['mode'] == 'mypage' && $no)

                $kakaoType = $in['kakaoType'];
                $returnURLFromAuth = $in['returnURLFromAuth'];
                //사용자 정보
                $userInfo = $kakaoApi->getUserInfo($in['wm_access_token']['access_token']);

                // 세션에 사용자 정보 저장
                $session->set(GodoKakaoServerApi::SESSION_ACCESS_TOKEN, $in['wm_access_token']);


                    $snsno = \App::load("\\Component\\Wm\\Wm");
                    $end = $snsno->getToken($no);

                    $memberSns = $memberSnsService->getMemberSnsByUUID($end['uuid']);

//                    $memberSns = $memberSnsService->getMemberSnsByUUID($userInfo['id']);


                // kakao 아이디로 회원가입한 회원인지 검증

                if ($memberSnsService->validateMemberSns($memberSns)) {
                    $logger->channel('kakaoLogin')->info('pass validationMemberSns');

                    if ($session->has(Member::SESSION_MEMBER_LOGIN)) {
                        // 마이페이지 회원정보 수정시 인증
                        if ($kakaoType == 'my_page_password') {


//                                $memberSnsService->saveToken($userInfo['id'], $in['wm_access_token']['access_token'], $in['wm_access_token']['refresh_token']);

                            $logger->channel('kakaoLogin')->info('move my page');
                            $session->set(MyPage::SESSION_MY_PAGE_PASSWORD, true);
                            $js = "
                                 if (typeof(window.top.layerSearchArea) == \"object\") {
                                        parent.location.href='../../mypage/my_page.php';
                                    } else if (window.opener === null) {
                                        location.href='" . gd_isset($returnURLFromAuth, '../../mypage/my_page.php') . "';
                                    } else {
                                        opener.location.href='../../mypage/my_page.php';
                                        self.close();
                                    }
                            ";
                            $this->js($js);
                        }

                        if ($kakaoType == 'hack_out') {
                            $logger->channel('kakaoLogin')->info('hack out kakao id');
                            $session->set(GodoKakaoServerApi::SESSION_KAKAO_HACK, true);
                            $js = "
                                   if (window.opener === null || Object.keys(window.opener).indexOf('" . $functionName . "') < 0) {
                                       location.href='../../mypage/hack_out.php';
                                   } else {
                                       opener.location.href='../../mypage/hack_out.php';
                                       self.close();
                                   }
                                   ";
                            $this->js($js);
                        }
                    }
                    $saveAutoLogin = 'y';
                    // 카카오 로그인

                    $memberSnsService->saveToken($userInfo['id'], $in['wm_access_token']['access_token'], $in['wm_access_token']['refresh_token']);
                    $memberSnsService->loginBySns($userInfo['id']);
                    if ($saveAutoLogin == 'y') {
                        $session->set(Member::SESSION_MYAPP_SNS_AUTO_LOGIN, 'y');
                        \Cookie::set('no', \Encryptor::encrypt($memberSns['memNo']), (3600 * 24 * 10), '/', true, true);
                    }
                    $logger->channel('kakaoLogin')->info('success login by kakao');
                }

                //마이페이지 회원 인증 다를경우
                if ($kakaoType == 'my_page_password') {
                    //현재 받은 세션값으로 로그아웃 시키기
                    \Logger::channel('kakaoLogin')->info('different inform', $session->get(GodoKakaoServerApi::SESSION_USER_PROFILE));
                    $js = "alert('" . __('로그인 시 인증한 정보와 다릅니다 .') . "');
                    if (window.opener === null || Object.keys(window.opener).indexOf('" . $functionName . "') < 0) {
                        location.href='../../mypage/my_page_password.php';
                    } else {
                        opener.location.href='../../mypage/my_page_password.php';
                        self.close();
                    }
                ";
                    $this->js($js);
                }
            }
            
            if ($response) {


                //탈퇴를 확인하기 위한 모듈 로드
                $hackOut = \App::load('\\Component\\Member\\HackOut\\HackOutServiceSec');

                $result = $hackOut->checkRejoinByMemberId($response['id']);

                if ($result['isFl'] == 'n') { //탈퇴한 경우

                    $this->js("alert('" . $result['message'] . "'); location.href = '../../member/login.php'");
                }
                $kakaoType = null;
                try {
                    $functionName = 'popup';
                    if (gd_is_skin_division()) {
                        $functionName = 'gd_popup';
                    }


                    $kakaoApi = new GodoKakaoServerApi();
                    $memberSnsService = new MemberSnsService();

                    $state = $request->get()->get('state');
                    $state1 = explode('^|^' , $state);
                    //state 값을 이용해 분기처리
                    $kakaoType = $state['kakaoType'];
                    //returnUrl 추출
                    $returnURLFromAuth = rawurldecode($state['returnUrl']);
                    // saveAutologin
                    $saveAutoLogin = 'y';


                    //카카오계정 로그인 팝업창에서 동의안함 클릭시 팝업창 닫힘 처리
                    if ($request->get()->get('error') == 'access_denied') {
                        $logger->channel('kakaoLogin')->info($request->get()->get('error_description'));
                        $js = "
                if (window.opener === null || Object.keys(window.opener).indexOf('" . $functionName . "') < 0) {
                    if('" . $kakaoType . "' == 'join_method'){
                        location.href='../../member/join_method.php';
                    }else{
                        location.href='../../mypage/my_page.php';
                    }
                } else {
                    opener.location.reload();
                    self.close();
                }";
                        $this->js($js);
                    }

                    if ($response) {
                        $memberSns = $memberSnsService->getMemberSnsByUUID($response['id']);

                        if (!empty($loginToken))
                            $session->set(GodoKakaoServerApi::SESSION_ACCESS_TOKEN, $loginToken);
                        $logger->channel('kakaoLogin')->info('pass validationMemberSns');


                        $wm_access_token = $accessToken;

                        if ($memberSnsService->validateMemberSns($memberSns)) {

                            // 카카오 로그인 부분

                            // 카카오 아이디 로그인 wm_access_token
                            $memberSnsService->saveToken($response['id'], $wm_access_token, $loginToken['refresh_token']);
                            $memberSnsService->loginBySns($response['id']);

                            if ($saveAutoLogin == 'y') {
                                $session->set(Member::SESSION_MYAPP_SNS_AUTO_LOGIN, 'y');
                                \Cookie::set('no', \Encryptor::encrypt($memberSns['memNo']), (3600 * 24 * 10), '/', true, true);
                                $session->set('kakao1', \Encryptor::encrypt($memberSns['memNo']));
                            }

                            $logger->channel('kakaoLogin')->info('success login by kakao');

                            $db = \App::getInstance('DB');
                            try {
                                $db->begin_tran();
                                $check = new AttendanceCheckLogin();
                                $message = $check->attendanceLogin();
                                $db->commit();

                                // 에이스 카운터 로그인 스크립트
                                $acecounterScript = \App::load('\\Component\\Nhn\\AcecounterCommonScript');
                                $acecounterUse = $acecounterScript->getAcecounterUseCheck();
                                if ($acecounterUse) {
                                    echo $acecounterScript->getLoginScript();
                                }

                                $logger->info('commit attendance login');
                                if ($message) {
                                    $logger->info(sprintf('has attendance message: %s', $message));

                                }

                                $url = ($returnUrl1) ? $returnUrl1 : urldecode($state1[0]);

                                if (strpos($url, '../') === 0) {
                                    $url = "../" . $url;
                                }
                                if ($url == 'n' || $url == 'y' || $url == '')
                                    $url = "../../main/index.php";
                                $this->redirect($url, null, parent);
                                //

                            } catch (Exception $e) {
                                $db->rollback();
                                $logger->error(__METHOD__ . ', ' . $e->getFile() . '[' . $e->getLine() . '], ' . $e->getMessage());
                            }
                        } else {

                                if (empty(\Request::get()->get("memId"))) {

                                    \Session::set("accessToken", $accessToken);
                                    \Session::set("refresh_token", $refresh_token);

                                    $uuid = $response['id'];

                                    $email = $response['kakao_account']['email'];
                                    $cellPhone = $response['kakao_account']['phone_number'] ? str_replace("+82 ", "0", $response['kakao_account']['phone_number']) : "010-0000-0000";

                                    if ($response['kakao_account']['gender'] == 'male') {
                                        $sexFl = 'm';
                                    } else
                                        $sexFl = 'w';

                                    $birthYear = $response['kakao_account']['birthyear'] ? $response['kakao_account']['birthyear'] : "1980";
                                    $birthMonth = $response['kakao_account']['birthday'] ? substr($response['kakao_account']['birthday'], 0, 2) : "11";
                                    $birthDay = $response['kakao_account']['birthday'] ? substr($response['kakao_account']['birthday'], 2, 2) : "11";

                                    if (!empty($email)) {
                                        $strSQL = "select memId,email from " . DB_MEMBER . " where email='$email'";
                                        $mrow = $db->fetch($strSQL);

                                        if (!empty($mrow['email']) && $uuid !== $mrow['memId']) {
                                            ?>
                                            <form name="snsForm" method="post" action="../../member/sns_member.php">
                                                <input type="hidden" name="wm_access_token" value="<?= $accessToken ?>">
                                                <input type="hidden" name="accessToken" value="<?= $accessToken ?>">
                                                <input type="hidden" name="refresh_token"
                                                       value="<?= $loginToken['refresh_token'] ?>">
                                                <input type="hidden" name="directKakao" value="1">
                                                <input type="hidden" name="rncheck" value="none">
                                                <input type="hidden" name="mode" value="join">
                                                <input type="hidden" name="memId" value="<?= $response['id'] ?>">
                                                <input type="hidden" name="email" value="<?= $email ?>">
                                                <input type="hidden" name="cellPhone" value="<?= $cellPhone ?>">
                                                <input type="hidden" name="sexFl" value="<?= $sexFl ?>">
                                                <input type="hidden" name="birthYear" value="<?= $birthYear ?>">
                                                <input type="hidden" name="birthMonth" value="<?= $birthMonth ?>">
                                                <input type="hidden" name="birthDay" value="<?= $birthDay ?>">
                                                <input type="hidden" name="memNm"
                                                       value="<?= $response['kakao_account']['name'] ?>">
                                                <input type="hidden" name="returnTo"
                                                       value="<?= !empty($returnUrl1) ? $returnUrl1 : urldecode($state1[0]) ?>">
                                            </form>
                                            <script>
                                                document.snsForm.submit();
                                            </script>

                                            <?php
                                            exit();
                                        }
                                    }
                                }

                            /*웹앤모바일 20200311 튜닝 카카오 로그인시 바로 회원가입으로 보내버리기*/
                            // 회원가입해야할 경우 member_ps 쪽으로 회원정보 전송
                            $memId = $response['id'];
                            $memNm = $response['kakao_account']['name'] ? $response['kakao_account']['name'] : 'user' . $memId;
                            $directKakao = 1;
                            $rncheck = 'none';
                            $mode = 'join';


                                $email = $response['kakao_account']['email'] ? $response['kakao_account']['email'] : '';
                                if ($response['kakao_account']['gender'] == 'male') {
                                    $sexFl = 'm';
                                } else $sexFl = 'w';


                            $cellPhone = $response['kakao_account']['phone_number'] ? str_replace("+82 ", "0", $response['kakao_account']['phone_number']) : "010-0000-0000";
                            $birthYear = $response['kakao_account']['birthyear'] ? $response['kakao_account']['birthyear'] : "1980";
                            $birthMonth = $response['kakao_account']['birthday'] ? substr($response['kakao_account']['birthday'], 0, 2) : "11";
                            $birthDay = $response['kakao_account']['birthday'] ? substr($response['kakao_account']['birthday'], 2, 2) : "11";
                            // 웹앤모바일 21-10-21 - 회원 가입시 필요한 returnUrl 추가

                                $this->redirect("../member_ps.php?wm_access_token=" . $accessToken . "&directKakao=" . $directKakao . "&rncheck=" . $rncheck . "&mode=" . $mode . "&memId=" . $memId . "&memNm=" . $memNm . "&email=" . $email . "&cellPhone=" . $cellPhone . "&sexFl=" . $sexFl . "&birthYear=" . $birthYear . "&birthMonth=" . $birthMonth . "&birthDay=" . $birthDay . "&returnTo=" . $state1[0], null, parent);

                        }
                        exit;
                    }
                    // 카카오 로그인 팝업을 띄우는 케이스
                    $callbackUri = $request->getRequestUri();
                    $state = array();
                    if ($startLen = strpos($request->getRequestUri(), "?")) {
                        $requestUriArray = explode('&', substr($request->getRequestUri(), ($startLen + 1)));
                        //\Logger::channel('kakaoLogin')->info('requestUriArray 354 %s', json_encode($requestUriArray));

                        $kakaoTypeInRequestUri = $requestUriArray[0];
                        $kakaoTypeToState = explode('=', $kakaoTypeInRequestUri);
                        $state['kakaoType'] = $kakaoTypeToState[1];
                        //returnUrl이 여러 개 있을 경우
                        foreach ($requestUriArray as $key => $val) {
                            $isReturnUrl = strstr($val, 'returnUrl');
                            if ($isReturnUrl) {
                                /* 웹앤모바일 수정 21-10-21 - returnUrl 관련 로직 최신화 */
                                $returnUrlToState = str_replace('returnUrl=', '', $val);
                                $state['returnUrl'] = $returnUrlToState;
                            }
                        }
                        $state['referer'] = $request->getReferer();
                        if ($request->get()->get('saveAutoLogin') == 'y') $state['saveAutoLogin'] = 'y';
                        $callbackUri = substr($request->getRequestUri(), 0, $startLen);
                    }
                    $redirectUri = $request->getDomainUrl() . $callbackUri;
                    \Logger::channel('kakaoLogin')->info('Redirect URI is %s', $redirectUri);

                    $getCodeURL = $kakaoApi->getCodeURL($redirectUri, $state);
                    \Logger::channel('kakaoLogin')->info('Code URI is %s', $getCodeURL);
                    $this->redirect($getCodeURL);
                } catch (AlertRedirectException $e) {
                    $logger->error($e->getTraceAsString());
                    MemberUtil::logout();
                    throw $e;
                } catch (AlertRedirectCloseException $e) {
                    $logger->error($e->getTraceAsString());
                    throw $e;
                } catch (Exception $e) {
                    $logger->error($e->getTraceAsString());
                    if ($request->isMobile()) {
                        MemberUtil::logout();
                        throw new AlertRedirectException($e->getMessage(), $e->getCode(), $e, '../../member/login.php', 'parent');
                    } else {
                        MemberUtil::logout();
                        throw new AlertCloseException($e->getMessage(), $e->getCode(), $e);
                    }
                }


            } else {

                parent::index();
            }
    }
}