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
use Logger;
use Request;
use Session;
use UserFilePath;
use Globals;
use Vendor\Spreadsheet\Excel\Reader as SpreadsheetExcelReader;
use App; 
use Encryptor;
/**
 * Class ExcelDataConvert
 *
 * Excel 저장 및 다운 로드
 * 상품, 회원 Excel 업로드 및 다운로드 관련 Class
 *
 * @package Bundle\Component\Excel
 * @author  artherot
 */
class ExcelExtraDataConvert
{
    /** @var  SpreadsheetExcelReader */
    protected $excelReader;
    /** @var \Framework\Database\DBTool $db */
    protected $db;
    protected $excelHeader;
    protected $excelFooter;
    protected $excelBody = [];
    protected $fields = [];
    protected $fieldTexts = [];
    protected $dbNames = [];
    protected $tableKeys = [];

    private $arrWhere = [];

    protected $gGlobal;

    public function __construct()
    {
        ini_set('memory_limit', '-1');
        set_time_limit(0);

        if (!is_object($this->db)) {
            $this->db = \App::load('DB');
        }
        $this->initHeader();
        $this->initFooter();
        $this->gGlobal = Globals::get('gGlobal');
    }



    /**
     * 엑셀 샘플 다운
     *
     * @author sunny
     */
    public function setExcelExtraSampleDown()
    {
       // 기본 설정
        $excelData = new ExcelExtra();
        $excelField = $excelData->formatData();
        $arrField = [
            'text',
            'excelKey',
            'comment',
        ];

        // 전체 항목 선택
        foreach ($excelField as $key => $val) {
            $setData['fieldCheck'][$key] = $val['dbKey'];
        }

        // 엑셀 상단
        echo $this->excelHeader;
        echo '<table border="1">' . chr(10);
        for ($i = 0; $i < count($arrField); $i++) {
            echo '<tr>' . chr(10);
            foreach ($excelField as $key => $val) {
                if (in_array($val['dbKey'], $setData['fieldCheck'])) {
                    echo '<td class="title">' . $val[$arrField[$i]] . '</td>' . chr(10);
                }
            }
            echo '</tr>' . chr(10);
        }

        // 샘플 데이타
        foreach ($excelField as $key => $val) {
            $getData[0][$val['dbKey']] = $val['sample'];
        }
        unset($excelField, $arrField);

        // 엑셀 내용
        echo '<tr>' . chr(10);
        foreach ($getData as $sampleData) {
            foreach ($setData['fieldCheck'] as $fVal) {
                // 필드 포인트효과
                if ($fVal == 'build1' || $fVal == 'build2' || $fVal == 'no') {
                    $className = 'xl31';
                } else {
                    $className = 'xl24';
                }
                echo '<td class="' . $className . '">' . $sampleData[$fVal] . '</td>' . chr(10);
            }
        }
        echo '</tr>' . chr(10);
        echo '</table>' . chr(10);

        // 엑셀 하단
        echo $this->excelFooter;
    }

    /**
     * 엑셀 다운
     *
     * @author sunny
     * @return array $setData 선택된 필드값
     */
    public function setExcelExtraDown($req)
    {

        // 기본 설정
/*
        $excelData = new ExcelExtra();
        $excelField = $excelData->formatData(true);
        $arrField = [
            'text',
            'excelKey',
            'comment',
        ];
        unset($excelField, $arrField);
*/

        // 엑셀 상단
        echo $this->excelHeader;

		echo '<table border="1">' . chr(10);
		echo '<tr>' . chr(10);
		echo '<td class="title">회원아이디</td>' . chr(10);
		echo '<td class="title">등급</td>' . chr(10);
		echo '<td class="title">방문횟수</td>' . chr(10);
		echo '<td class="title">방문주기</td>' . chr(10);
		echo '<td class="title">방문경과일</td>' . chr(10);
		echo '<td class="title">SMS동의</td>' . chr(10);
		echo '<td class="title">구매횟수</td>' . chr(10);

		echo '<td class="title">객단가</td>' . chr(10);

		echo '<td class="title">구매주기</td>' . chr(10);

		echo '</tr>' . chr(10);

		$dpx = \App::load('\\Component\\Designpix\\Statistics');


        $data = $dpx->getRecycleStatisticsXls();


		foreach($data as $k => $_r){

			if(!is_numeric($k)) continue; 



			echo '<tr>' . chr(10);

			echo '<td class="center">'.$_r['memNo'].'</td>' . chr(10);
			echo '<td class="center">'.$_r['groupNm'].'</td>' . chr(10);

			echo '<td class="center">'.number_format($_r['visitCnt']).'</td>' . chr(10);
			echo '<td class="center">'.number_format($_r['visitCycle'],1).'</td>' . chr(10);
			echo '<td class="xl31">'.number_format($_r['visitOver']).'</td>' . chr(10);
			echo '<td class="center">'.$_r['smsFl'].'</td>' . chr(10);

			echo '<td class="center">'.number_format($_r['orderCnt']).'</td>' . chr(10);
			echo '<td class="center">'.number_format($_r['divisionOrderPrice']).'</td>' . chr(10);
			echo '<td class="center">'.number_format($_r['saleCycle'],1).'</td>' . chr(10);

			echo '</tr>' . chr(10);
		}


        echo '</table>' . chr(10);

        // 엑셀 하단
        echo $this->excelFooter;
    }


	//마일리지 다운
	public function setExcelMeberMileage($req){

		
		// 엑셀 상단
        echo $this->excelHeader;

		echo '<table border="1">' . chr(10);
		echo '<tr>' . chr(10);
		echo '<td class="title">아이디</td>' . chr(10);
		echo '<td class="title">이름</td>' . chr(10);
		echo '<td class="title">지급액</td>' . chr(10);
		echo '<td class="title">차감액</td>' . chr(10);
		echo '<td class="title">지급/차감일</td>' . chr(10);
		echo '<td class="title">소멸예정일</td>' . chr(10);
		echo '<td class="title">결제완료일</td>' . chr(10);
		echo '<td class="title">사유</td>' . chr(10);
		echo '<td class="title">주문번호</td>' . chr(10);

		echo '</tr>' . chr(10);

		$dpx = \App::load('\\Component\\Designpix\\Dpx');
		$data = $dpx->getMileageMember($req);

		foreach($data as $k => $_r){
			
			if(!is_numeric($k)) continue; 

			echo '<tr>' . chr(10);

			echo '<td class="center">'.$_r['memId'].'</td>' . chr(10);
			echo '<td class="center">'.$_r['memNm'].'</td>' . chr(10);

			echo '<td class="center">'.$_r['pmileage'].'</td>' . chr(10);
			echo '<td class="center">'.$_r['omileage'].'</td>' . chr(10);
			echo '<td class="center">'.$_r['regDt'].'</td>' . chr(10);
			echo '<td class="center">'.$_r['deleteScheduleDt'].'</td>' . chr(10);

			echo '<td class="center">'.$_r['paymentDt'].'</td>' . chr(10);

			//dpx.farmer 유효기간만료 구분(reasonCd에 따라) - 20220822
			if($_r['reasonCd'] == '010059999'){
				echo '<td class="center">유효기간 만료로 마일리지 소멸</td>' . chr(10);
			}else{
				echo '<td class="center">'.$_r['contents'].'</td>' . chr(10);
			}

			if($_r['reasonCd'] == '01005001'){
				echo '<td class="center" style=mso-number-format:"\@">'.$_r['handleCd'].'</td>' . chr(10);
			}else{
				echo '<td class="center"></td>' . chr(10);
			}

			echo '</tr>' . chr(10);
		}


        echo '</table>' . chr(10);

        // 엑셀 하단
        echo $this->excelFooter;

	}


	//마일리지 다운
	public function setExcelMeberSleep($req){

		
		// 엑셀 상단
        echo $this->excelHeader;

		echo '<table border="1">' . chr(10);
		echo '<tr>' . chr(10);
		echo '<td class="title">휴면 일괄 번호</td>' . chr(10);
		echo '<td class="title">휴면회원 전환일</td>' . chr(10);
		echo '<td class="title">아이디</td>' . chr(10);
		echo '<td class="title">이름</td>' . chr(10);
		echo '<td class="title">회원등급</td>' . chr(10);
		echo '<td class="title">마일리지</td>' . chr(10);
		echo '<td class="title">예치금</td>' . chr(10);
		echo '<td class="title">회원가입일</td>' . chr(10);

		echo '</tr>' . chr(10);


		$req['page'] = 1;
		$req['searchFl'] = 'y';
		$req['pageNum'] = 10;


		$memberSleep = new \Component\Member\MemberSleep();
		$groupName = \Component\Member\Group\Util::getGroupName();
		$total = $req['recodeTotal'];

		$data = $memberSleep->lists($req, 1, $total);

		foreach($data as $k => $_r){
			
			if(!is_numeric($k)) continue; 

			echo '<tr>' . chr(10);
			echo '<td class="center">'.$_r['sleepNo'].'</td>' . chr(10);
			echo '<td class="center">'.$_r['sleepDt'].'</td>' . chr(10);
			echo '<td class="center">'.$_r['memId'].'</td>' . chr(10);
			echo '<td class="center">'.$_r['memNm'].'</td>' . chr(10);

			echo '<td class="center">'.$groupName[$_r['groupSno']].'</td>' . chr(10);
			echo '<td class="center">'.$_r['mileage'].'</td>' . chr(10);
			echo '<td class="center">'.$_r['deposit'].'</td>' . chr(10);
			echo '<td class="center">'.$_r['entryDt'].'</td>' . chr(10);
			echo '</tr>' . chr(10);
		}


        echo '</table>' . chr(10);

        // 엑셀 하단
        echo $this->excelFooter;

	}

	//디자인픽스 튜닝 출석체크 리스트
	public function setExcelAttendanceList($req){

		// 엑셀 상단
        echo $this->excelHeader;

		echo '<table border="1">' . chr(10);
		echo '<tr>' . chr(10);
		echo '<td class="title">번호</td>' . chr(10);
		echo '<td class="title">아이디</td>' . chr(10);
		echo '<td class="title">이름</td>' . chr(10);
		echo '<td class="title">최종참여일시</td>' . chr(10);
		echo '<td class="title">누적참여횟수</td>' . chr(10);
		echo '<td class="title">조건달성일시</td>' . chr(10);
		echo '<td class="title">혜택지급일시</td>' . chr(10);

		echo '</tr>' . chr(10);

		$dpx = \App::load('\\Component\\Designpix\\Dpx');
		$data = $dpx->getAttendanceList($req);
		$cnt = 1;

		foreach($data as $k => $_r){

			
			$joinArr = json_decode($_r['attendanceHistory'],true);
			$lastJoin = end($joinArr['history']);
			$lastJoinCnt = count($joinArr['history']);

			if(!is_numeric($k)) continue; 

			echo '<tr>' . chr(10);

			echo '<td class="center">=IF(A'.$cnt.'="번호", 1, A'.$cnt.'+1)</td>' . chr(10);
			echo '<td class="center">'.$_r['memID'].'</td>' . chr(10);
			echo '<td class="center">'.$_r['memNm'].'</td>' . chr(10);
			echo '<td class="center">'.$lastJoin.'</td>' . chr(10);
			echo '<td class="center">'.$lastJoinCnt.'</td>' . chr(10);
			echo '<td class="center">'.$_r['conditionDt'].'</td>' . chr(10);
			echo '<td class="center">'.$_r['benefitDt'].'</td>' . chr(10);

			echo '</tr>' . chr(10);

			$cnt++;
		}


        echo '</table>' . chr(10);

        // 엑셀 하단
        echo $this->excelFooter;

	}


    private function initHeader()
    {
        $this->excelHeader = '<html xmlns="http://www.w3.org/1999/xhtml" lang="ko" xml:lang="ko">' . chr(10);
        $this->excelHeader .= '<head>' . chr(10);
        $this->excelHeader .= '<title>Excel Down</title>' . chr(10);
        $this->excelHeader .= '<meta http-equiv="Content-Type" content="text/html; charset=' . SET_CHARSET . '" />' . chr(10);
        $this->excelHeader .= '<style>' . chr(10);
        $this->excelHeader .= 'br{mso-data-placement:same-cell;}' . chr(10);
        $this->excelHeader .= '.xl31{mso-number-format:"0_\)\;\\\(0\\\)";}' . chr(10);
        $this->excelHeader .= '.xl24{mso-number-format:"\@";} ' . chr(10);
        $this->excelHeader .= '.title{font-weight:bold; background-color:#F6F6F6; text-align:center;} ' . chr(10);
        $this->excelHeader .= '.center{text-align:center;} ' . chr(10);
        $this->excelHeader .= '</style>' . chr(10);
        $this->excelHeader .= '</head>' . chr(10);
        $this->excelHeader .= '<body>' . chr(10);
    }

    private function initFooter()
    {
        $this->excelFooter = '</body>' . chr(10);
        $this->excelFooter .= '</html>' . chr(10);
    }

    public function hasError()
    {
        return Request::files()->get('excel')['error'] > 0;
    }

    public function read()
    {
        $this->excelReader = new SpreadsheetExcelReader();
        $this->excelReader->setOutputEncoding('CP949');

        return ($this->excelReader->read(Request::files()->get('excel')['tmp_name']) !== false);
    }



    protected function printExcel()
    {
        echo $this->excelHeader;
        echo join('', $this->excelBody);
        echo $this->excelFooter;
    }



    /**
     * @return SpreadsheetExcelReader
     */
    public function getExcelReader()
    {
        return $this->excelReader;
    }


	
	/**
     * @param SpreadsheetExcelReader $excelReader
     */
    public function setExcelReader($excelReader)
    {
        $this->excelReader = $excelReader;
    }
}
