<?php

namespace Component\Database\Traits;

trait Wpay {

    /**
     * wpay 설정 관련 테이블
     * @return array
     */
    public static function tableWpaySet() {
        return $arrField = [
            ['val' => 'useMode', 'typ' => 's', 'def' => 'test'],
            ['val' => 'isUseOrder', 'typ' => 'i', 'def' => 0],
            ['val' => 'MID', 'typ' => 's', 'def' => null],
            ['val' => 'SEEDKEY', 'typ' => 's', 'def' => null],
            ['val' => 'SEEDIV', 'typ' => 's', 'def' => null],
            ['val' => 'HASHKEY', 'typ' => 's', 'def' => null],
            ['val' => 'payName', 'typ' => 's', 'def' => null],
            ['val' => 'payGuideText', 'typ' => 's', 'def' => null],
            ['val' => 'useCheckout', 'typ' => 'i', 'def' => 0],
            ['val' => 'titleBarColor', 'typ' => 's', 'def' => null],
            ['val' => 'content', 'typ' => 's', 'def' => null],
            ['val' => 'authBtnColor', 'typ' => 's', 'def' => null],
            ['val' => 'authBtnTextcolor', 'typ' => 's', 'def' => null],
            ['val' => 'clauseDetailUrl', 'typ' => 's', 'def' => null],
            ['val' => 'clausePersonInfoUrl', 'typ' => 's', 'def' => null],
            ['val' => 'passwdInfoText', 'typ' => 's', 'def' => null],
            ['val' => 'passwdReInfoText', 'typ' => 's', 'def' => null],
            ['val' => 'secuKeypadPinType', 'typ' => 's', 'def' => 'A'],
            ['val' => 'cardBenefitBtnColor', 'typ' => 's', 'def' => null],
            ['val' => 'cardBenefitTextColor', 'typ' => 's', 'def' => null],
            ['val' => 'secuKeypadCardType', 'typ' => 's', 'def' => 'A'],
            ['val' => 'cancelInfoText', 'typ' => 's', 'def' => null],
            ['val' => 'closeBtnType', 'typ' => 's', 'def' => 'A'],
        ];
    }

    /**
     * wpay 사용자 관련 정보 테이블
     * @return array[]
     */
    public static function tableWpayUsers() {

        return [
            ['val' => 'regStamp', 'typ' => 'i', 'def' => 0], // 등록 타임스트링
            ['val' => 'modStamp', 'typ' => 'i', 'def' => 0], // 수정 타임스트링
            ['val' => 'm_no', 'typ' => 'i', 'def' => 0], //회원번호
            ['val' => 'userId', 'typ' => 's', 'def' => null], //회원 아이디
            ['val' => 'wtid', 'typ' => 's', 'def' => null], //wpay transaction ID
            ['val' => 'wpayUserKey', 'typ' => 's', 'def' => null], // 이니시스에서 발행한 wpayUserKey
        ];
    }

    public static function tableWpayPayReqAuth() {
        return [
            ['val' => 'regStamp', 'typ' => 'i', 'def' => null],
            ['val' => 'resultCode', 'typ' => 's', 'def' => null],
            ['val' => 'resultMsg', 'typ' => 's', 'def' => null],
            ['val' => 'wtid', 'typ' => 's', 'def' => null],
            ['val' => 'wpayUserKey', 'typ' => 's', 'def' => null],
            ['val' => 'wpayToken', 'typ' => 's', 'def' => null],
        ];
    }
}


