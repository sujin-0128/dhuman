<?php

namespace Controller\Mobile\Member;

use App;
use Request;

class KakaoChkController extends \Controller\Mobile\Controller
{
    /** 웹앤모바일 카카오싱크 유효성 검사 */
    private $db="";

    public function pre()
    {
        if(!is_object($this->db)){
            $this->db = App::load("DB");
        }
    }

    public function index()
    {
        $post = Request::post()->toArray();
        $get = Request::get()->toArray();
        $req = array_merge($post,$get);

        $data = array();
        switch ($req['mode']) {
            case 'email_chk':
                if(Request::isAjax()){
                    $email=$req['email'];
                    $data['result']="success";

                    if(!empty($email)){
                        $strSQL="select count(memNo) as cnt from ".DB_MEMBER." where email=?";
                        $row = $this->db->query_fetch($strSQL,['s',$email],false);

                        if($row['cnt']>0) $data['result']="fail";

                    }else{
                        $data['result']="fail";
                    }
                }else{
                    $data['result']="fail";
                }

                echo json_encode($data);
                break;
        }
        exit();

    }
}