<?php

namespace Component\Database;

class MemberTableField extends \Bundle\Component\Database\MemberTableField
{


    public  function tableMember()
    {
		$arrField = parent::tableMember();
		$addField = [
			['val' => 'giftCnt', 'typ' => 'i', 'def' => '0' , 'name' => '���� �ֹ� Ƚ��'],
			['val' => 'giftUserFl', 'typ' => 's', 'def' => '' , 'name' => '���� ������ ���Կ���'],
		];

		$arrField = array_merge($arrField , $addField );
        return $arrField;
    }


	
	
    public  function tableMemberGroup()
    {
		$arrField = parent::tableMemberGroup();
		$addField = [
			['val' => 'orderMonMoney', 'typ' => 'i', 'def' => '0' , 'name' => '�� ��� �ѵ�'],

			['val' => 'orderMonLimitFl', 'typ' => 's', 'def' => '' , 'name' => '�� ��� �ѵ� ��� ����'],

			['val' => 'apprFigureOrderCountFl', 'typ' => 's', 'def' => 'n' , 'name' => '������ġ���ֹ��Ǽ�'],
			['val' => 'apprFigureOrderCount', 'typ' => 'i', 'def' => '0' , 'name' => '������ġ���ֹ�Ƚ��'],

			
		];
		$arrField = array_merge($arrField , $addField );

        return $arrField;
    }
}
