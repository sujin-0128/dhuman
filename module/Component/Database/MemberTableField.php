<?php

namespace Component\Database;

class MemberTableField extends \Bundle\Component\Database\MemberTableField
{


    public  function tableMember()
    {
		$arrField = parent::tableMember();
		$addField = [
			['val' => 'giftCnt', 'typ' => 'i', 'def' => '0' , 'name' => '선물 주문 횟수'],
			['val' => 'giftUserFl', 'typ' => 's', 'def' => '' , 'name' => '선물 수령자 가입여부'],
		];

		$arrField = array_merge($arrField , $addField );
        return $arrField;
    }


	
	
    public  function tableMemberGroup()
    {
		$arrField = parent::tableMemberGroup();
		$addField = [
			['val' => 'orderMonMoney', 'typ' => 'i', 'def' => '0' , 'name' => '월 사용 한도'],

			['val' => 'orderMonLimitFl', 'typ' => 's', 'def' => '' , 'name' => '월 사용 한도 사용 여부'],

			['val' => 'apprFigureOrderCountFl', 'typ' => 's', 'def' => 'n' , 'name' => '실적수치제주문건수'],
			['val' => 'apprFigureOrderCount', 'typ' => 'i', 'def' => '0' , 'name' => '실적수치제주문횟수'],

			
		];
		$arrField = array_merge($arrField , $addField );

        return $arrField;
    }
}
