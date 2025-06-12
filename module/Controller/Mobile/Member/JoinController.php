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

use Bundle\Component\Apple\AppleLogin;
use Bundle\Component\Godo\GodoNaverServerApi;
use Component\Facebook\Facebook;
use Component\Godo\GodoPaycoServerApi;
use Component\Member\MemberValidation;
use Component\Member\Util\MemberUtil;
use Component\SiteLink\SiteLink;
use Framework\Utility\ArrayUtils;
use Framework\Utility\SkinUtils;
use Request;

/**
 * Class 회원가입 정보입력
 * @package Bundle\Controller\Mobile\Member
 * @author  yjwee
 */
class JoinController extends \Bundle\Controller\Mobile\Controller
{
    public function index()
    {
        $logger = \App::getInstance('logger');
        $request = \App::getInstance('request');
        $session = \App::getInstance('session');
        MemberValidation::checkJoinToken($request->post()->all());
        MemberValidation::checkJoinAgreement($request->post()->get('agreementInfoFl'), $request->post()->get('privateApprovalFl'));

        $scripts = ['gd_member2.js',];

        $isNaverJoin = false;
        $isThirdParty = false;
        $isAppleJoin = false;
        $thirdPartyProfile = $paycoProfile = $naverProfile = $kakaoProfile = $appleProfile = [];
        if ($session->has(GodoPaycoServerApi::SESSION_USER_PROFILE)) {
            $paycoProfile = $session->get(GodoPaycoServerApi::SESSION_USER_PROFILE);
            if(empty($paycoProfile['mobileId']) === false) { //휴대폰번호 존재
                $paycoProfile['mobileId'] = '0' . substr($paycoProfile['mobileId'], 2);
           }
            //@formatter:off
            if(empty($paycoProfile) == false) ArrayUtils::unsetDiff($paycoProfile, ['id', 'mobileId', 'name', 'sexCode']);
            //@formatter:on
            $scripts[] = 'gd_payco.js';
        }

        if($session->has(GodoNaverServerApi::SESSION_USER_PROFILE)) {
            $naverProfile = $session->get(GodoNaverServerApi::SESSION_USER_PROFILE);
            $naverProfile['mobileId'] = '0' . substr($naverProfile['mobileId'], 2);
            $isNaverJoin = true; //네이버 로그인 인경우 비밀번호 정보 입력창을 가리기 위해서 useFl == Y 인 경우 true 를 넣는다.

			//튜닝 네이버 로그인인 경우에 기존 회원 db 조회 하여 sms 동의여부 get
			$dpx = \App::load('\\Component\\Designpix\\Dpx');
			$userNaverInfo = $dpx->getNaverInfo($naverProfile);
		
			$this->setData('sms_check', $userNaverInfo['sms_check']);


            //@formatter:off
            ArrayUtils::unsetDiff($naverProfile, ['email', 'name', 'gender', 'nickname']);
            //@formatter:on
            $scripts[] = 'gd_naver.js';
        }
        $snsLoginPolicy = new \Component\Policy\SnsLoginPolicy();
        if ($snsLoginPolicy->useGodoAppId() && $session->has(\Component\Facebook\Facebook::SESSION_METADATA)) {
            $logger->info('Facebook login use godo app id. has session metadata');
            $facebook = new \Component\Facebook\Facebook();
            $thirdPartyProfile = $facebook->getUserProfileByIdentifier();
            $logger->info('Get user profile by identifier', $thirdPartyProfile);
            $isThirdParty = count($thirdPartyProfile) > 0;
            $thirdPartyProfile = $facebook->toJsonEncode($thirdPartyProfile);
            $scripts[] = 'gd_sns.js';
        } elseif ($session->has(\Component\Facebook\Facebook::SESSION_METADATA) && $session->has(\Component\Facebook\Facebook::SESSION_ACCESS_TOKEN)) {
            $logger->info('Facebook login. has session metadata and session access token');
            $facebook = new \Component\Facebook\Facebook();
            $thirdPartyProfile = $facebook->getUserProfile();
            $logger->info('Get user profile', $thirdPartyProfile);
            $isThirdParty = count($thirdPartyProfile) > 0;
            $thirdPartyProfile = $facebook->toJsonEncode($thirdPartyProfile);
            $scripts[] = 'gd_sns.js';
        } elseif($session->has(\Component\Godo\GodoKakaoServerApi::SESSION_USER_PROFILE)) {
            $kakaoProfile = $session->get(\Component\Godo\GodoKakaoServerApi::SESSION_USER_PROFILE);
            $kakaoProfile['nickname'] = ($session->get(\Component\Member\Member::SESSION_JOIN_INFO)['rncheck'] === 'none') ? $kakaoProfile['properties']['nickname'] : '';
            $kakaoProfile['email'] = $kakaoProfile['kakao_account']['email'];
            //@formatter:off
            ArrayUtils::unsetDiff($kakaoProfile, ['nickname', 'email']);
            //@formatter:on
            $scripts[] = 'gd_kakao.js';
        } elseif ($session->has(AppleLogin::SESSION_USER_PROFILE)) {
            $appleProfile =  $session->get(AppleLogin::SESSION_USER_PROFILE);
            $isAppleJoin = true; //Apple 로그인 인경우 비밀번호 정보 입력창을 가리기 위해서 useFl == Y 인 경우 true 를 넣는다.
            $scripts[] = 'gd_apple.js';
        }

        $siteLink = new SiteLink();

        $emailDomain = gd_array_change_key_value(gd_code('01004'));
        $emailDomain = array_merge(['self' => __('직접입력')], $emailDomain);
        $this->setData('emailDomain', $emailDomain); // 메일주소 리스팅

        $this->setData('joinField', MemberUtil::getJoinField());
        $this->setData('joinActionUrl', $siteLink->link('../member/member_ps.php', 'ssl'));
        $this->setData('data', MemberUtil::saveJoinInfoBySession($request->post()->all()));
        $this->setData('phoneArea', SkinUtils::getPhoneArea());
        $this->setData('localPhoneArea', SkinUtils::getPhoneArea(false));
        $this->setData('domainUrl', $request->getDomainUrl());
        $this->setData('paycoProfile', json_encode($paycoProfile));
        $this->setData('isPaycoJoin', count($paycoProfile) > 0);
        $this->setData('naverProfile', $this->getData('naverProfile') ?? json_encode($naverProfile));
        $this->setData('isNaverJoin', $isNaverJoin);
        $this->setData('isAppleJoin', $isAppleJoin);
        $this->setData('thirdPartyProfile', $thirdPartyProfile);
        $this->setData('isThirdParty', $isThirdParty);
        $this->setData('kakaoProfile', json_encode($kakaoProfile));
        $this->setData('isKakaoJoin', count($kakaoProfile) > 0);
        $this->setData('appleProfile', json_encode($appleProfile));

        $countries = \Component\Mall\MallDAO::getInstance()->selectCountries();
        $countryPhone = [];
        foreach ($countries as $key => $val) {
            if ($val['callPrefix'] > 0) {
                if ($session->has(SESSION_GLOBAL_MALL)) {
                    $countryPhone[$val['code']] = __($val['countryName']) . '(+' . $val['callPrefix'] . ')';
                } else {
                    $countryPhone[$val['code']] = __($val['countryNameKor']) . '(+' . $val['callPrefix'] . ')';
                }
            }
        }

        // 평생회원 이벤트
        $modifyEvent = \App::load('\\Component\\Member\\MemberModifyEvent');
        $mallSno = \Component\Mall\Mall::getSession('sno');
        $mallSno = gd_isset($mallSno, DEFAULT_MALL_NUMBER);
        $activeEvent = $modifyEvent->getActiveMemberModifyEvent($mallSno, 'life');
        $couponConfig = gd_policy('coupon.config'); // 쿠폰 설정값 정보
        $mileageBasic = gd_policy('member.mileageBasic'); // 마일리지 사용 여부
        $this->setData('activeEvent', $activeEvent);
        if ($activeEvent['benefitType'] == 'coupon' && $couponConfig['couponUseType'] === 'y') {
            // --- 모듈 호출
            $coupon = \App::load('\\Component\\Coupon\\Coupon');
            if ($coupon->checkCouponType($activeEvent['benefitCouponSno'])) {
                $couponData = $modifyEvent->getDataByTable(DB_COUPON, $activeEvent['benefitCouponSno'], 'couponNo', 'couponNm');
                $this->setData('benefitInfo', gd_htmlspecialchars_stripslashes($couponData['couponNm']).'쿠폰');
            } else {
                $this->setData('memberLifeEventView', 'hidden');
            }
        } else if ($activeEvent['benefitType'] == 'mileage' && $mileageBasic['payUsableFl'] === 'y') {
            $this->setData('benefitInfo', (int)$activeEvent['benefitMileage'].'원 ' . gd_display_mileage_name());
        }
        $this->setData('benefitType', $activeEvent['benefitType']);

        // 만 14세 이상 동의 항목 (보안취약점 개선사항 : 가입연령 제한 > 만14(19) 미만의 경우 기준년도 이상 미노출)
        $joinPolicy = gd_policy('member.join');
        if ($joinPolicy['under14ConsentFl'] === 'y') {
            $limitAge = 14;
        } else {
            $limitAge = ($joinPolicy['under14Fl'] === 'no') ? $joinPolicy['limitAge'] : '';
        }

        $DateYear = [];
        $DateYearMarri = [];
        $DateMonth = [];
        $DateDay = [];
        $startYear = (!empty($limitAge)) ? (int)date("Y") - $limitAge : (int)date("Y");
        $startYearMarri = (int)date("Y");
        $endYear = 1900;
        $fixFront = '';
        for ($i=$startYear; $i>=$endYear; $i--) {
            $DateYear[$i] = $i;
        }
        for ($i=$startYearMarri; $i>=$endYear; $i--) {
            $DateYearMarri[$i] = $i;
        }
        for ($j=1; $j<=12; $j++) {
            if ($j < 10) {
                $fixFront = 0;
            }
            $DateMonth[$fixFront.$j] = $fixFront.$j;
            $fixFront = '';
        }
        for ($k=1; $k<=31; $k++) {
            if ($k < 10) {
                $fixFront = 0;
            }
            $DateDay[$fixFront.$k] = $fixFront.$k;
            $fixFront = '';
        }
		//튜닝
		$dogDateYear = [];
        $startYear = (int)date("Y");
        $endYear = $startYear-30;
		
        for ($i=$startYear; $i>=$endYear; $i--) {
            $dogDateYear[$i] = $i;
        }
        $this->setData('dogDateYear', $dogDateYear);


        $this->setData('countryPhone', $countryPhone);
        $this->setData('DateYear', $DateYear);
        $this->setData('DateYearMarri', $DateYearMarri);
        $this->setData('DateMonth', $DateMonth);
        $this->setData('DateDay', $DateDay);

        $this->addScript($scripts);
    }
}
