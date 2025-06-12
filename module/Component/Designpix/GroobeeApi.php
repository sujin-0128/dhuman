<?php

/**
 * This is commercial software, only users who have purchased a valid license
 * and accept to the terms of the License Agreement can install and use this
 * program.
 *
 * Do not edit or add to this file if you wish to upgrade Godomall5 to newer
 * versions in the future.
 *
 * @copyright ¨Ï 2016, NHN godo: Corp.
 * @link http://www.godo.co.kr
 */
namespace Component\Designpix;

use Component\Validator\Validator;
use Component\Page\Page;
use Component\Storage\Storage;
use Component\Database\DBTableField;
use Framework\Utility\ArrayUtils;
use Framework\Utility\ImageUtils;
use Framework\Utility\SkinUtils;
use Component\Member\Util\MemberUtil;
use DateTime;
use Request;
use Session;
use App;

//use Component\Goods\Goods;

class GroobeeApi
{
    protected $db;

	protected $admin = '';

    public function __construct()
    {

        if (!is_object($this->db)) {
            $this->db = App::load('DB');
        }
	}

    public function sendApi($member, $type)
    {
		$memId = $member['memId'];
		$phoneNo = $member['cellPhone'];

		if($member['smsFl'] == 'y') {
			$smsFl = 'Y';
		} else {
			$smsFl = 'N';
		}


		if($type = 'POST') {
		    $post_field_string = '[{"memberId":"'.$memId.'", "phoneNumber":"'.$phoneNo.'", "isReceive":"'.$smsFl.'"}]';
		} else if($type = 'DELETE') {
			$post_field_string = '["'.$memId.'"]';
		}

		$ch = curl_init();

		curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_URL,'https://api.groobee.io/v1/users/sms');
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $type);

	    curl_setopt($ch, CURLOPT_POST, TRUE);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post_field_string);
		curl_setopt($ch, CURLOPT_HTTPHEADER,  array('Content-Type: application/json', 'x-api-key: a4f1ddebe2124658812d6746a6e0e398'));

        $response = curl_exec($ch);
        curl_close($ch);

        $response = json_decode($response, true);
	}
}
