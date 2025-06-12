<?php

namespace Component\Database\Traits;


trait DBTableDpx
{


	## 카카오싱크 로그
    public static function tableDpxSyncResult()
    {

        $arrField[] = ['val' => 'kakaoType', 'typ' => 's', 'def' => ''];	
        $arrField[] = ['val' => 'result', 'typ' => 's', 'def' => ''];	
        $arrField[] = ['val' => 'ipAddr', 'typ' => 's', 'def' => ''];				

        return $arrField;
    }




	


}