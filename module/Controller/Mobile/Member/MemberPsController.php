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

namespace Controller\Mobile\Member;

use App;
use Bundle\Component\Apple\AppleLogin;
use Component\Facebook\Facebook;
use Component\Godo\GodoPaycoServerApi;
use Component\Godo\GodoNaverServerApi;
use Component\Godo\GodoKakaoServerApi;
use Component\Godo\GodoWonderServerApi;
use Component\Member\MemberSnsService;
use Component\Member\MemberValidation;
use Component\Attendance\AttendanceCheckLogin;
use Component\Member\Util\MemberUtil;
use Component\Member\Member;
use Component\Coupon\Coupon;
use Component\Policy\SnsLoginPolicy;
use Component\Storage\Storage;
use Framework\Debug\Exception\DatabaseException;
use Framework\Object\SimpleStorage;
use Exception;
use Framework\Debug\Exception\AlertBackException;
use Framework\Utility\ComponentUtils;
use Framework\Utility\StringUtils;
use Request;
use Session;
use Validation;

/**
 * Class MemberPsController
 * @package Bundle\Controller\Mobile\Member
 * @author  yjwee
 */
class MemberPsController extends \Bundle\Controller\Mobile\Member\MemberPsController
{
    /**
     * @inheritdoc
     */
    public function index()
    {
        try {
            // myapp checker
            if (Request::isMyapp() === true) {
                $useMyapp = empty(gd_policy('myapp.config')['builder_auth']['clientId']) === false
                    && empty(gd_policy('myapp.config')['builder_auth']['secretKey']) === false;
                $useMyappQuickLogin = gd_policy('myapp.config')['useQuickLogin'] == 'true' ? true : false;
            }

            if (Request::isMyapp() === true && Request::post()->get('hash')) {
                $myApp = App::load('\\Component\\Myapp\\Myapp');
                $code = Request::post()->get('code');
                if ($code) {
                    $guestOrderInfo = $myApp->getGuestOrderInfo($code);
                    Request::post()->set('orderNm', $guestOrderInfo['orderNm']);
                    Request::post()->set('orderNo', $guestOrderInfo['orderNo']);
                } else {
                    $postData = Request::post()->all();
                    if ($myApp->hmacValidate($postData) !== true) {
                        \Logger::channel('myapp')->error('Wrong Hash : ' . json_encode(Request::post()->all()));
                        throw new Exception(__("요청을 찾을 수 없습니다."));
                    }
                }
            } else {
                if ((Request::getReferer() == Request::getDomainUrl()) || empty(Request::getReferer()) === true) {
                    \Logger::error(__METHOD__ . ' Access without referer');
                    throw new Exception(__("요청을 찾을 수 없습니다."));
                }
            }

            $session = \App::getInstance('session');
            $request = \App::getInstance('request');
            $logger = \App::getInstance('logger');

            // 웹앤모바일 카카오싱크 튜닝 23-02-14
            $in = \Request::request()->all();

            if ($in['directKakao']) {

                try {
                    /** @var  \Bundle\Component\Member\Member $member */
                    $member = \App::load('\\Component\\Member\\Member');

                    $returnUrl = urldecode($request->post()->get('returnUrl'));
                    if (empty($returnUrl) || strpos($returnUrl, "member_ps") !== false) {
                        $returnUrl = $request->getReferer();
                    }

                    $memberVO = null;
                    try {
                        if ($session->has(GodoWonderServerApi::SESSION_USER_PROFILE)) {
                            \Component\Member\Util\MemberUtil::saveJoinInfoBySession($request->post()->all());
                        }
                        $memberSnsService = \App::load('Component\\Member\\MemberSnsService');
                        \DB::begin_tran();
                        $in = \Request::request()->all();

                        if ($in['directKakao']) {
                            $kakaoToken = \Session::get(GodoKakaoServerApi::SESSION_ACCESS_TOKEN);
                            $kakaoApi = new GodoKakaoServerApi();
                            $kakaoApi->appLink($kakaoToken['access_token']);
//                        $userInfo = $kakaoApi->getUserInfo($in['wm_access_token']['access_token']);
                            $userInfo = $kakaoApi->getUserInfo($in['wm_access_token']);
                            \Session::set(GodoKakaoServerApi::SESSION_USER_PROFILE, $userInfo);
                            $in['appFl'] = 'y';
                            $in['rncheck'] = 'authCellphone';
                            // 웹앤모바일 추가 튜닝 카카오 디벨로퍼에서 email, sms 수신 여부 판단 후 회원가입에 반영
                            $member_url = "https://kapi.kakao.com/v1/user/service/terms";
                            $accessToken = $kakaoToken['access_token'];
                            $ch = curl_init();
                            curl_setopt($ch, CURLOPT_URL, $member_url);
                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                            curl_setopt($ch, CURLOPT_POST, false);
                            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Bearer ' . $accessToken));
                            $end = curl_exec($ch);
                            curl_close($ch);
                            $end = json_decode($end, true);

                            foreach ($end['allowed_service_terms'] as $k => $v) {
                                if ($v['tag'] == 'maillingFl') {
                                    $in['maillingFl'] = 'y';
                                }
                                if ($v['tag'] == 'smsFl') {
                                    $in['smsFl'] = 'y';
                                }
                            }
                            // end


                                // 이메일 중복 여부 판단
                                $email = \App::load("\Component\Wm\KakaoJoin");
                                $result = $email->getEmail($in['email']);
                                if ($result) {
                                    $in['email'] = $in['memId'] . '_@email.com';
                                }

                                if (empty($in['email'])) {
                                    $in['email'] = $in['memId'] . '_@email.com';
                                }

                                if (empty($in['cellPhone'])) {
                                    $in['cellPhone'] = '010-0000-0000';
                                }

                                if (empty($in['sexFl'])) {
                                    $in['sexFl'] = 'm';
                                }


                            // 웹앤모바일 수정 21-09-23 - 배송지 정보를 받아서 회원정보의 주소로 입력
                            $shipping_url = 'https://kapi.kakao.com/v1/user/shipping_address';
                            $ch2 = curl_init();
                            curl_setopt($ch2, CURLOPT_URL, $shipping_url);
                            curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
                            curl_setopt($ch2, CURLOPT_POST, false);
                            curl_setopt($ch2, CURLOPT_HTTPHEADER, array('Authorization: Bearer ' . $accessToken));
                            $shipAddr = curl_exec($ch2);
                            curl_close($ch2);
                            $shipAddr = json_decode($shipAddr, true);

                            if ($shipAddr['shipping_addresses'][0]) {
                                $in['zonecode'] = $shipAddr['shipping_addresses'][0]['zone_number'];
                                $in['address'] = $shipAddr['shipping_addresses'][0]['base_address'];
                                $in['addressSub'] = $shipAddr['shipping_addresses'][0]['detail_address'];
                            }

                            $memberVO = $member->join2($in);
                        } else {
                            $memberVO = $member->join($request->post()->xss()->all());
                        }
                        $session->del('isFront');
                        if ($session->has(GodoPaycoServerApi::SESSION_USER_PROFILE)) {
                            $paycoToken = $session->get(GodoPaycoServerApi::SESSION_ACCESS_TOKEN);
                            $userProfile = $session->get(GodoPaycoServerApi::SESSION_USER_PROFILE);
                            $session->del(GodoPaycoServerApi::SESSION_ACCESS_TOKEN);
                            $session->del(GodoPaycoServerApi::SESSION_USER_PROFILE);
                            $memberSnsService->joinBySns($memberVO->getMemNo(), $userProfile['idNo'], $paycoToken['access_token'], 'payco');
                            $paycoApi = new GodoPaycoServerApi();
                            $paycoApi->logByJoin();
                        } elseif ($session->has(Facebook::SESSION_USER_PROFILE)) {
                            $userProfile = $session->get(Facebook::SESSION_USER_PROFILE);
                            $accessToken = $session->get(Facebook::SESSION_ACCESS_TOKEN);
                            $memberSnsService->joinBySns($memberVO->getMemNo(), $userProfile['id'], $accessToken, SnsLoginPolicy::FACEBOOK);
                        } elseif ($session->has(GodoNaverServerApi::SESSION_USER_PROFILE)) {
                            $naverToken = $session->get(GodoNaverServerApi::SESSION_ACCESS_TOKEN);
                            $userProfile = $session->get(GodoNaverServerApi::SESSION_USER_PROFILE);
                            $session->del(GodoNaverServerApi::SESSION_ACCESS_TOKEN);
                            $session->del(GodoNaverServerApi::SESSION_USER_PROFILE);
                            $memberSnsService = new MemberSnsService();
                            $memberSnsService->joinBySns($memberVO->getMemNo(), $userProfile['id'], $naverToken['access_token'], 'naver');
                            $naverApi = new GodoNaverServerApi();
                            $naverApi->logByJoin();
                        } elseif ($session->has(GodoKakaoServerApi::SESSION_USER_PROFILE)) {
                            $kakaoToken = $session->get(GodoKakaoServerApi::SESSION_ACCESS_TOKEN);
                            $kakaoProfile = $session->get(GodoKakaoServerApi::SESSION_USER_PROFILE);
                            $session->del(GodoKakaoServerApi::SESSION_ACCESS_TOKEN);
                            $session->del(GodoKakaoServerApi::SESSION_USER_PROFILE);
                            $memberSnsService = new MemberSnsService();
                            $memberSnsService->joinBySns($memberVO->getMemNo(), $kakaoProfile['id'], $kakaoToken['access_token'], 'kakao');

                        } elseif ($session->has(GodoWonderServerApi::SESSION_USER_PROFILE)) {
                            $wonderToken = $session->get(GodoWonderServerApi::SESSION_ACCESS_TOKEN);
                            $userProfile = $session->get(GodoWonderServerApi::SESSION_USER_PROFILE);
                            $session->del(GodoWonderServerApi::SESSION_ACCESS_TOKEN);
                            $session->del(GodoWonderServerApi::SESSION_USER_PROFILE);
                            $memberSnsService = new MemberSnsService();
                            $memberSnsService->joinBySns($memberVO->getMemNo(), $userProfile['mid'], $wonderToken['access_token'], 'wonder');
                            $wonderApi = new GodoWonderServerApi();
                            $wonderApi->logByJoin();
                        }

                        \DB::commit();

                    } catch (\Throwable $e) {
                        \DB::rollback();
                        throw $e;
                    }
                    if ($session->get('ps_event')) {
                        PlusShopWrapper::event($session->get('ps_event'), ['memNo' => $memberVO->getMemNo()]);
                    }
                    if ($memberVO != null) {
                        $smsAutoConfig = ComponentUtils::getPolicy('sms.smsAuto');
                        $kakaoAutoConfig = ComponentUtils::getPolicy('kakaoAlrim.kakaoAuto');
                        if ($kakaoAutoConfig['useFlag'] == 'y' && $kakaoAutoConfig['memberUseFlag'] == 'y') {
                            $smsDisapproval = $kakaoAutoConfig['member']['JOIN']['smsDisapproval'];
                        } else {
                            $smsDisapproval = $smsAutoConfig['member']['JOIN']['smsDisapproval'];
                        }
                        StringUtils::strIsSet($smsDisapproval, 'n');
                        $sendSmsJoin = ($memberVO->getAppFl() == 'n' && $smsDisapproval == 'y') || $memberVO->getAppFl() == 'y';
                        $mailAutoConfig = ComponentUtils::getPolicy('mail.configAuto');
                        $mailDisapproval = $mailAutoConfig['join']['join']['mailDisapproval'];
                        StringUtils::strIsSet($smsDisapproval, 'n');
                        $sendMailJoin = ($memberVO->getAppFl() == 'n' && $mailDisapproval == 'y') || $memberVO->getAppFl() == 'y';
                        if ($sendSmsJoin) {
                            /** @var \Bundle\Component\Sms\SmsAuto $smsAuto */
                            $smsAuto = \App::load('\\Component\\Sms\\SmsAuto');
                            $smsAuto->notify();
                        }
                        if ($sendMailJoin) {
                            $member->sendEmailByJoin($memberVO);
                        }
                        

                        if ($in['directKakao']) {
							//↓↓↓↓ 240717 주석처리하였음
                            // 이 부분 수정 해야함
                            // 회원 가입이 완료되면 자동 로그인을 위해 로그인쪽으로 데이터 전송
                            // 웹앤모바일 수정 21-10-22 - 이메일 중복으로 재설정한 경우와 아닌 경우 구분
                            //if (\Request::request()->get('emailCheck') == 'y') {
                            //    $this->redirect("./kakao/kakao_login.php?memId=" . $in['memId'] . "&returnUrl=" . \Request::post()->get('returnTo'), null, 'top');
                            //} else {
                            //    $this->redirect("./kakao/kakao_login.php?memId=" . $in['memId'] . "&returnUrl=" . urlencode(\Request::get()->get('returnTo')), null, 'top');
                           // }
						   //↑↑↑↑ 240717 주석처리하였음

							//240809 dpx-kwc kakao회원가입시 자동로그인 처리
							$memberSns = $memberSnsService->getMemberSnsByUUID($in['memId']);
							if ($memberSnsService->validateMemberSns($memberSns)) {
								$memberSnsService->saveToken($in['memId'], $accessToken, $session->get('refresh_token'));
								$memberSnsService->loginBySns($in['memId']);

								$session->set(Member::SESSION_MYAPP_SNS_AUTO_LOGIN, 'y');
								\Cookie::set('no', \Encryptor::encrypt($memberSns['memNo']), (3600 * 24 * 10), '/', true, true);
								$session->set('kakao1', \Encryptor::encrypt($memberSns['memNo']));

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
								}catch (Exception $e) {
									$db->rollback();
									$logger->error(__METHOD__ . ', ' . $e->getFile() . '[' . $e->getLine() . '], ' . $e->getMessage());
								}
							}
							//240809 dpx-kwc 로그인처리 후 join_ok호출시 index로 강제이동으로 수정
							$this->js("window.location.href = './join_success.php';");
							//$this->js("alert('회원가입이 완료되었습니다.');window.location.href = './join_ok.php';"); 원본
							echo 'join_ok';
                            exit;
                        }

                    }

                    $sitelink = new SiteLink();
                    $returnUrl = $sitelink->link('../member/join_ok.php');
                    if ($wonderToken && $userProfile && $session->has(GodoWonderServerApi::SESSION_PARENT_URI)) {
                        $returnUrl = $session->get(GodoWonderServerApi::SESSION_PARENT_URI) . '../member/wonder_join_ok.php';
                        $this->js('location.href=\'' . $returnUrl . '\';');
                    } else {
                        $this->js('parent.location.href=\'' . $returnUrl . '\'');
                    }
                } catch (AlertRedirectException $e) {
                    throw $e;
                } catch (\Throwable $e) {
                    if ($request->isAjax() === true) {
                        $logger->error($e->getTraceAsString());
                        $this->json($this->exceptionToArray($e));
                    } else {
                        throw new AlertBackException($e->getMessage(), $e->getCode(), $e);
                    }
                }
            } else { // 카카오싱크 튜닝 끝 =================================================================================================================================


                /** @var  \Bundle\Component\Member\Member $member */
                $member = App::load('\\Component\\Member\\Member');

                // returnUrl 데이타 타입 체크
                if (Request::post()->has('returnUrl')) {
                    try {
                        Validation::setExitType('throw');
                        Validation::defaultCheck(gd_isset(Request::post()->get('returnUrl')), 'url');
                    } catch (\Exception $e) {
                        Request::post()->set('returnUrl', Request::getReferer());
                    }

                    // 웹 치약점 개선사항
                    $scheme = Request::getScheme() . '://';
                    $getHost = $scheme . Request::getHost();
                    $getReturnUrl = explode('returnUrl=', Request::getReferer());
                    $getReturnUrl = urldecode($getReturnUrl[1]);
                    if (strpos($getReturnUrl, '://') !== false && strpos($getReturnUrl, $getHost) === false) {
                        Request::post()->set('returnUrl', '../member/login.php');
                    }
                }

                // --- 수신 정보
                $returnUrl = urldecode(Request::post()->get('returnUrl'));
                if (empty($returnUrl) || strpos($returnUrl, "member_ps") !== false) {
                    $returnUrl = Request::getReferer();
                }

                $mode = Request::post()->get('mode', Request::get()->get('mode'));
                switch ($mode) {

                    //튜닝 finder : userPwChange
                    case 'finder':
                        $dpx = \App::load('\\Component\\Designpix\\Dpx');

                        $userInfo['user_id'] = Request::post()->get('user_id');
                        $userInfo['user_nm'] = Request::post()->get('user_nm');
                        $userInfo['user_phone'] = Request::post()->get('user_phone');

                        $res = $dpx->userFinder($userInfo);

                        if ($res['passCheck'] == 'y') {
                            $this->json(['result' => '0', 'passCheck' => 'y']);
                        } else {
                            $this->json(['result' => '-1', 'passCheck' => 'n']);
                        }


                        /*
                        if ( $result['passCheck'] == 'y' ) {
                            $this->json(__('인증되었습니다.'));
                            $this->json(['passCheck' => 'y']);
                        } else {
                            throw new Exception(__("입력하신 정보와 일치하는 데이터가 없습니다.") . " " . __("다시 확인 후 입력 해 주세요."));
                        }
                        */

                        break;
                    case 'userPwChange':

                        $userInfo['change_id'] = Request::post()->get('change_id');
                        $userInfo['change_nm'] = Request::post()->get('change_nm');
                        $userInfo['change_phone'] = Request::post()->get('change_phone');
                        $userInfo['reset_user_pw'] = Request::post()->get('reset_user_pw');

                        $dpx = \App::load('\\Component\\Designpix\\Dpx');
                        $dpx->setPassword($userInfo);

                        break;

                    // 비회원 로그인 및 비회원 주문하기
                    case 'guest':
                        MemberUtil::guest();
                        $this->redirect($returnUrl, null, 'top');
                        break;

                    // 비회원 주문조회 하기 (최종 주문상세보기 페이지 이동은 AJAX에서 처리)
                    case 'guestOrder':
                        $order = App::load('\\Component\\Order\\Order');

                        $orderNm = Request::post()->get('orderNm');
                        $orderNo = Request::post()->get('orderNo');
                        $aResult = $order->isGuestOrder($orderNo, $orderNm);
                        if ($aResult['result']) {
                            $orderNo = $aResult['orderNo'];
                            MemberUtil::guestOrder($orderNo, $orderNm);

                            // 마이앱 로그인뷰 스크립트
                            if (\Request::isMyapp() && $useMyapp && $useMyappQuickLogin) {
                                $this->redirect($returnUrl . '?orderNo=' . $orderNo, null, 'top');
                                break;
                            }

                            $this->json(
                                [
                                    'result' => '0',
                                    'message' => __('주문조회에 성공했습니다.'),
                                    'orderNo' => $orderNo,
                                ]
                            );
                        } else {
                            $this->json(
                                [
                                    'result' => '-1',
                                    'message' => __('주문자명과 주문번호가 일치하는 주문이 존재하지 않습니다. 다시 입력해 주세요. 주문번호와 비밀번호를 잊으신 경우, 고객센터로 문의하여 주시기 바랍니다.'),
                                ]
                            );
                        }
                        break;

                    // 성인 비회원 로그인
                    case 'adultGuest':
                        if (empty($returnUrl)) {
                            $returnUrl = Request::getReferer();
                        }
                        MemberUtil::adultGuest(Request::post()->toArray());
                        $this->redirect($returnUrl, null, 'top');
                        break;

                    // 회원가입
                    case 'join':
                        $memberVO = null;
                        try {
                            if (Session::has(GodoWonderServerApi::SESSION_USER_PROFILE)) {
                                \Component\Member\Util\MemberUtil::saveJoinInfoBySession(Request::post()->all());
                            }
                            \DB::begin_tran();
                            Session::set('isFront', 'y');
                            if (Session::has('pushJoin')) {
                                Request::post()->set('simpleJoinFl', 'push');
                            }
                            $in = \Request::request()->toArray();
                            if ($in['kakao']) {
                                $memberVO = $member->join2($in);
                            } else {
                                $memberVO = $member->join($request->post()->xss()->all());
                            }
                            Session::del('isFront');
                            if (Session::has(GodoPaycoServerApi::SESSION_USER_PROFILE)) {
                                $paycoToken = Session::get(GodoPaycoServerApi::SESSION_ACCESS_TOKEN);
                                $userProfile = Session::get(GodoPaycoServerApi::SESSION_USER_PROFILE);
                                Session::del(GodoPaycoServerApi::SESSION_ACCESS_TOKEN);
                                Session::del(GodoPaycoServerApi::SESSION_USER_PROFILE);
                                $memberSnsService = new MemberSnsService();
                                $memberSnsService->joinBySns($memberVO->getMemNo(), $paycoToken['idNo'], $paycoToken['access_token'], 'payco');
                                $paycoApi = new GodoPaycoServerApi();
                                $paycoApi->logByJoin();
                            } elseif (Session::has(Facebook::SESSION_USER_PROFILE)) {
                                $userProfile = Session::get(Facebook::SESSION_USER_PROFILE);
                                $accessToken = Session::get(Facebook::SESSION_ACCESS_TOKEN);
                                $memberSnsService = new MemberSnsService();
                                $memberSnsService->joinBySns($memberVO->getMemNo(), $userProfile['id'], $accessToken, SnsLoginPolicy::FACEBOOK);
                            } elseif (Session::has(GodoNaverServerApi::SESSION_USER_PROFILE)) {
                                $naverToken = Session::get(GodoNaverServerApi::SESSION_ACCESS_TOKEN);
                                $userProfile = Session::get(GodoNaverServerApi::SESSION_USER_PROFILE);
								//240924 designpix-kwc 로그인 처리해야함으로 주석
                                //Session::del(GodoNaverServerApi::SESSION_ACCESS_TOKEN);
                                //Session::del(GodoNaverServerApi::SESSION_USER_PROFILE);
                                $memberSnsService = new MemberSnsService();
                                $memberSnsService->joinBySns($memberVO->getMemNo(), $userProfile['id'], $naverToken['access_token'], 'naver');
                                $naverApi = new GodoNaverServerApi();
                                $naverApi->logByJoin();

                                //튜닝 기존 회원 적립금 지급
                                $dpx = \App::load('\\Component\\Designpix\\Dpx');
                                $dpx->setMileage($memberVO->getMemNo(), $userProfile);


                            } elseif (Session::has(GodoKakaoServerApi::SESSION_USER_PROFILE)) {
                                $kakaoToken = Session::get(GodoKakaoServerApi::SESSION_ACCESS_TOKEN);
                                $kakaoProfile = Session::get(GodoKakaoServerApi::SESSION_USER_PROFILE);
                                Session::del(GodoKakaoServerApi::SESSION_ACCESS_TOKEN);
                                Session::del(GodoKakaoServerApi::SESSION_USER_PROFILE);
                                $memberSnsService = new MemberSnsService();
                                $memberSnsService->joinBySns($memberVO->getMemNo(), $kakaoProfile['id'], $kakaoToken['access_token'], 'kakao');
                            } elseif (Session::has(GodoWonderServerApi::SESSION_USER_PROFILE)) {
                                $wonderToken = Session::get(GodoWonderServerApi::SESSION_ACCESS_TOKEN);
                                $userProfile = Session::get(GodoWonderServerApi::SESSION_USER_PROFILE);
                                Session::del(GodoWonderServerApi::SESSION_ACCESS_TOKEN);
                                Session::del(GodoWonderServerApi::SESSION_USER_PROFILE);
                                $memberSnsService = new MemberSnsService();
                                $memberSnsService->joinBySns($memberVO->getMemNo(), $userProfile['mid'], $wonderToken['access_token'], 'wonder');
                                $wonderApi = new GodoWonderServerApi();
                                $wonderApi->logByJoin();
                            } elseif (Session::has(AppleLogin::SESSION_USER_PROFILE)) {
                                $userProfile = Session::get(AppleLogin::SESSION_USER_PROFILE);
                                $access_token = Session::get(AppleLogin::SESSION_ACCESS_TOKEN);
                                Session::del(AppleLogin::SESSION_USER_PROFILE);
                                Session::del(AppleLogin::SESSION_ACCESS_TOKEN);

                                $memberSnsService = new MemberSnsService();
                                $memberSnsService->joinBySns($memberVO->getMemNo(), $userProfile['uuid'], $access_token['access_token'], 'apple');
                            }
                            \DB::commit();


                            //튜닝 반려동물생일

                            $dpx = \App::load('\\Component\\Designpix\\Dpx');

                            $dcInfo['dcYear'] = Request::post()->get('dcYear');
                            $dcInfo['dcMonth'] = Request::post()->get('dcMonth');
                            $dcInfo['dcDay'] = Request::post()->get('dcDay');

                            $dcInfo['memId'] = Request::post()->get('memId');

                            $dpx->setDcInfo($dcInfo);


                        } catch (Exception $e) {
                            \DB::rollback();
                            if (get_class($e) == Exception::class) {
                                if ($e->getMessage()) {
                                    $this->js("alert('" . $e->getMessage() . "');window.parent.callback_not_disabled();");
                                }
                            } else {
                                throw $e;
                            }
                        }
                        if ($memberVO != null) {
                            $smsAutoConfig = ComponentUtils::getPolicy('sms.smsAuto');
                            $kakaoAutoConfig = ComponentUtils::getPolicy('kakaoAlrim.kakaoAuto');
                            $kakaoLunaAutoConfig = ComponentUtils::getPolicy('kakaoAlrimLuna.kakaoAuto');
                            if (gd_is_plus_shop(PLUSSHOP_CODE_KAKAOALRIMLUNA) === true && $kakaoLunaAutoConfig['useFlag'] == 'y' && $kakaoLunaAutoConfig['memberUseFlag'] == 'y') {
                                $smsDisapproval = $kakaoLunaAutoConfig['member']['JOIN']['smsDisapproval'];
                            } else if ($kakaoAutoConfig['useFlag'] == 'y' && $kakaoAutoConfig['memberUseFlag'] == 'y') {
                                $smsDisapproval = $kakaoAutoConfig['member']['JOIN']['smsDisapproval'];
                            } else {
                                $smsDisapproval = $smsAutoConfig['member']['JOIN']['smsDisapproval'];
                            }
                            StringUtils::strIsSet($smsDisapproval, 'n');
                            $sendSmsJoin = ($memberVO->getAppFl() == 'n' && $smsDisapproval == 'y') || $memberVO->getAppFl() == 'y';
                            $mailAutoConfig = ComponentUtils::getPolicy('mail.configAuto');
                            $mailDisapproval = $mailAutoConfig['join']['join']['mailDisapproval'];
                            StringUtils::strIsSet($smsDisapproval, 'n');
                            $sendMailJoin = ($memberVO->getAppFl() == 'n' && $mailDisapproval == 'y') || $memberVO->getAppFl() == 'y';
                            if ($sendSmsJoin) {
                                /** @var \Bundle\Component\Sms\SmsAuto $smsAuto */
                                $smsAuto = \App::load('\\Component\\Sms\\SmsAuto');
                                $smsAuto->notify();
                            }
                            if ($sendMailJoin) {
                                $member->sendEmailByJoin($memberVO);
                            }
                            if (Session::has('pushJoin')) {
                                $memNo = $memberVO->getMemNo();
                                $memberData = $member->getMember($memNo, 'memNo', 'memNo, memId, appFl, groupSno, mileage');
                                $coupon = new Coupon();
                                $getData = $coupon->getMemberSimpleJoinCouponList($memNo);
                                $member->setSimpleJoinLog($memNo, $memberData, $getData, 'push');
                                Session::del('pushJoin');
                            }
                        }

                        // 에이스카운터 회원가입 스크립트
                        $acecounterScript = \App::load('\\Component\\Nhn\\AcecounterCommonScript');
                        $acecounterUse = $acecounterScript->getAcecounterUseCheck();
                        if ($acecounterUse) {
                            echo $acecounterScript->getJoinScript($memberVO->getMemNo());
                        }


                        // 평생회원 이벤트
                        if (Request::post()->get('expirationFl') === '999') {
                            $modifyEvent = \App::load('\\Component\\Member\\MemberModifyEvent');
                            $memberData = $member->getMember($memberVO->getMemNo(), 'memNo', 'memNo, memNm, mallSno, groupSno'); // 회원정보
                            $resultLifeEvent = $modifyEvent->applyMemberLifeEvent($memberData, 'life');
                            if (empty($resultLifeEvent['msg']) == false) {
                                $msg = 'alert("' . $resultLifeEvent['msg'] . '");';
                            }
                        }
						//240924 designpix-kwc 네이버 회원가입 로그인처리
						if($naverToken){
							$memberSns = $memberSnsService->getMemberSnsByUUID($naverToken['response']['id']);
							if ($memberSnsService->validateMemberSns($memberSns)) {
								$memberSnsService->saveToken($naverToken['response']['id'], $naverToken['access_token'], $naverToken['refresh_token']);
								$memberSnsService->loginBySns($naverToken['response']['id']);
								$naverApi->logByLogin();
							}
						//240924 designpix-kwc 일반 회원가입 로그인 처리
						}else{
							$request->post()->set('loginId', trim($in['memId']));
							$request->post()->set('loginPwd', trim($in['memPw']));
							$member->login($in['memId'], $in['memPw']);

							$storage = new SimpleStorage($request->post()->all());
							MemberUtil::saveCookieByLogin($storage);
						}
						try {
							\DB::begin_tran();
							$check = new AttendanceCheckLogin();
							$message = $check->attendanceLogin();
							\DB::commit();
		 
							// 에이스 카운터 로그인 스크립트
							$acecounterScript = \App::load('\\Component\\Nhn\\AcecounterCommonScript');
							$acecounterUse = $acecounterScript->getAcecounterUseCheck();
							if($acecounterUse) {
								$returnScript = $acecounterScript->getLoginScript();
								echo $returnScript;
								usleep(200);
							}
						} catch (\Exception $e) {
							\DB::rollback();
							$logger->info(__METHOD__ . ', ' . $e->getFile() . '[' . $e->getLine() . '], ' . $e->getMessage());
						}
						if($message){
							throw new AlertRedirectException($message, 0, null, $returnUrl);
						} else {
							$this->js($msg . 'parent.location.href=\'../member/join_success.php\'');
						}
                        //$this->js($msg . 'parent.location.href=\'../member/join_ok.php\'');
                        break;
                    case 'simpleJoin':
                        $memberVO = null;
                        try {
                            \DB::begin_tran();
                            Session::set('isFront', 'y');
                            Session::set('simpleJoin', 'y');
                            $data = Request::post()->toArray();
                            Request::post()->set('simpleJoinFl', 'order');
                            Request::post()->set('appFl', 'y');
                            $memberVO = $member->join(Request::post()->xss()->all());
                            Session::del('isFront');
                            \DB::commit();
                        } catch (\Throwable $e) {
                            \DB::rollback();
                            if (get_class($e) == Exception::class) {
                                if ($e->getMessage()) {
                                    $this->json(['result' => 'false', 'message' => $e->getMessage()]);
                                }
                            } else {
                                throw $e;
                            }
                        }
                        if ($memberVO != null) {
                            $mailAutoConfig = ComponentUtils::getPolicy('mail.configAuto');
                            $mailDisapproval = $mailAutoConfig['join']['join']['mailDisapproval'];
                            StringUtils::strIsSet($smsDisapproval, 'n');
                            $sendMailJoin = ($memberVO->getAppFl() == 'n' && $mailDisapproval == 'y') || $memberVO->getAppFl() == 'y';
                            if ($sendMailJoin) {
                                $member->sendEmailByJoin($memberVO);
                            }

                            Request::post()->set('loginId', $data['email']);
                            Request::post()->set('loginPwd', $data['memPw']);
                            $member->login($data['email'], $data['memPw']);
                            $storage = new SimpleStorage(Request::post()->all());
                            MemberUtil::saveCookieByLogin($storage);

                            $memNo = $memberVO->getMemNo();
                            $memberData = $member->getMember($memNo, 'memNo', 'memNo, memId, appFl, groupSno, mileage');
                            $coupon = new Coupon();
                            $getData = $coupon->getMemberSimpleJoinCouponList($memNo, null, 'c.couponBenefitType ASC, c.couponBenefit DESC, c.regDt DESC');
                            $member->setSimpleJoinLog($memNo, $memberData, $getData, 'order');
                            $couponCnt = count($getData);
                            if ($couponCnt == 1) {
                                $c = '쿠폰: [' . $getData[0]['couponNm'] . ']';
                            } else if ($couponCnt > 1) {
                                $c = '쿠폰: [' . $getData[0]['couponNm'] . '] 외 ' . ($couponCnt - 1) . '장';
                            } else {
                                $c = 'false';
                            }
                            $this->json(['result' => 'true', 'mileage' => $memberData['mileage'], 'coupon' => $c]);
                        } else {
                            $this->json(['result' => 'false', 'message' => '요청을 찾을 수 없습니다.']);
                        }
                        exit;
                        break;
                    // 이메일중복확인
                    case 'overlapEmail':
                        $memId = Request::post()->get('memId');
                        if (\App::load('Component\\Member\\Util\\MemberUtil')->simpleOverlapEmail(Request::post()->get('email'), $memId) === false) {
                            $this->json(__('사용가능한 이메일입니다.'));
                        } else {
                            throw new Exception(__("이미 등록된 이메일 주소입니다.") . " " . __("다른 이메일 주소를 입력해 주세요."));
                        }
                        break;
                    // 아이디중복확인
                    case 'overlapMemId':
                        $memId = Request::post()->get('memId');
                        if (MemberUtil::overlapMemId($memId) === false) {
                            $this->json(__("사용가능한 아이디입니다."));
                        } else {
                            throw new Exception(__("이미 등록된 아이디입니다.") . " " . __("다른 아이디를 입력해 주세요."));
                        }
                        break;

                    // 닉네임중복확인
                    case 'overlapNickNm':
                        $memId = Request::post()->get('memId');
                        $nickNm = Request::post()->get('nickNm');

                        if (MemberUtil::overlapNickNm($memId, $nickNm) === false) {
                            $this->json(__('사용가능한 닉네임입니다.'));
                        } else {
                            throw new Exception(__("이미 등록된 닉네임입니다. 다른 닉네임을 입력해 주세요."));
                        }
                        break;
                    case 'overlapBusiNo':
                        $memId = Request::post()->get('memId');
                        $busiNo = Request::post()->get('busiNo');
                        $busiLength = Request::post()->get('charlen');

                        if (strlen($busiNo) != $busiLength) {
                            throw new Exception(sprintf(__("사업자번호는 %s자로 입력해야 합니다."), $busiLength));
                        }
                        if (MemberUtil::overlapBusiNo($memId, $busiNo) === false) {
                            $this->json(__("사용가능한 사업자번호입니다."));
                        } else {
                            throw new Exception(__("이미 등록된 사업자번호입니다."));
                        }
                        break;
                    case 'checkRecommendId':
                        // 추천인 아이디 체크
                        if (MemberUtil::checkRecommendId(Request::post()->get('recommId'), Request::post()->get('memId'))) {
                            $this->json(__('아이디가 정상적으로 확인되었습니다.'));
                        } else {
                            throw new Exception(__('추천인 아이디를 다시 확인해 주세요.'));
                        }
                        break;
                    case 'validateMemberPassword':
                        // 비밀번호 검증
                        $memberPassword = Request::post()->get('memPw');
                        $result = MemberValidation::validateMemberPassword($memberPassword);
                        $this->json($result);
                        break;
                    case 'under14Download':
                        // 14세 미만 가입동의서 다운로드
                        $downloadPath = Storage::disk(Storage::PATH_CODE_COMMON)->getDownLoadPath('under14sample.docx');
                        $this->download($downloadPath, __('만14세미만회원가입동의서(샘플).docx'));
                        break;
                    case 'setSimpleJoinPushClosed':
                        Session::set('joinEventPush.joinEventPushClose', 'y');
                        break;
                    case 'setSimpleJoinPushLog':
                        Session::set('pushJoin', 'y');
                        $eventType = Request::post()->get('eventType');
                        $member->setSimpleJoinPushLog($eventType);
                        break;
                    default:
                        /** @var \Bundle\Controller\Front\Member\MemberPsController $front */
                        $front = \App::load('\\Controller\\Front\\Member\\MemberPsController');
                        $front->index();
                        break;
                }

            }
        } catch (DatabaseException $e) {
            if ($e->getCode() == '1062') {
                throw new AlertBackException('이미 등록된 회원입니다.', $e->getCode(), $e);
            } else {
                throw new AlertBackException($e->getCode(), $e->getCode(), $e);
            }
        } catch (Exception $e) {
            \Logger::error(__METHOD__ . ', ' . $e->getFile() . '[' . $e->getLine() . '], ' . $e->getMessage(), $e->getTrace());
            if (Request::isAjax() === true) {
                $this->json($this->exceptionToArray($e));
            } else {
                throw new AlertBackException($e->getMessage());
            }
        }
    }
}

