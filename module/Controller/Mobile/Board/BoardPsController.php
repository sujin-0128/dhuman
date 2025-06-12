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
namespace Controller\Mobile\Board;

use Component\Board\BoardWrite;
use Component\Board\Board;
use Component\Board\BoardBuildQuery;
use Component\Board\BoardAct;
use Component\Board\BoardReport;
use Component\Board\BoardConfig;
use Component\Member\MemberReport;
use Component\Validator\Validator;
use Framework\Debug\Exception\AlertBackException;
use Framework\ObjectStorage\Service\ImageUploadService;
use function GuzzleHttp\Psr7\parse_query;
use View\Template;
use Request;

class BoardPsController extends \Bundle\Controller\Mobile\Controller
{

    public function index()
    {
        $req = Request::post()->toArray();
        switch ($req['mode']) {
            case 'duplicateOrderGoodsNo' :
                $cnt = BoardBuildQuery::init($req['bdId'])->selectCountByOrderGoodsNo($req['orderGoodsNo'],$req['bdSno']);
                if($cnt>0) {
                    exit('y');
                }
                exit('n');
                break;
            case 'validRegistOrderGoodsNo' :
                $boardWrite = new BoardWrite($req);
                $errorMsg = $boardWrite->checkReviewPossible();
                if ($errorMsg['possible'] == true) {
                    exit('n');
                }
                exit('y');
                break;
            case 'delete':
                try {
                    $boardAct = new BoardAct($req);
                    $result = $boardAct->deleteData($req['sno']);
                    $msg = '';
                    if ($result == 'ok') {
                        $msg = __('삭제되었습니다');
                    }
                    $data = ['result' => $result, 'msg' => $msg];
                    echo $this->json($data);
                    exit;
                } catch (\Exception $e) {
                    $this->json(['result' => 'fail', 'msg' => $e->getMessage()]);
                }
                break;
            case 'modifyCheck' :
                $boardAct = new BoardAct($req);
                $result = $boardAct->checkModifyPassword($req['writerPw']);
                if ($result) {
                    echo $this->json(['result' => 'ok', 'msg' => '']);
                } else {
                    echo $this->json(['result' => 'fail', 'msg' => __('비밀번호가 틀렸습니다.')]);
                }
                exit;
                break;
            case 'modify':
            case 'write':
            case 'reply':
                $req['isMobile'] = true;
                try {
                    $boardAct = new BoardAct($req);
                    if (method_exists($boardAct, 'setHttpStorage') === true) { //HTTP Storage setting.
                        $boardAct->setHttpStorage(false);
                    }
                    $addScrpt = '';
                    $msgs[] = $boardAct->saveData();
                    if ($msgs) {
                        foreach ($msgs as $msg) {
                            if (!$msg) continue;
                            $addScrpt .= 'alert("' . $msg . '");';
                        }
                    }
                    $returnRequest = parse_query($req['returnUrl']);

                    if (gd_isset($req['gboard']) == 'y') {
                        if(\Request::isSecure()){
                            $this->js($addScrpt . "alert('" . __('저장되었습니다.') . "');location.href='../goods/goods_ps.php?bdId=".$req['bdId']."&mode=openerReload';");
                        }
                        else {
                            $this->js($addScrpt . "alert('" . __('저장되었습니다.') . "');opener.updateBoard('".$req['bdId']."');self:close()");
                        }
                    } else if (gd_isset($req['gboard']) == 'r') {
                        if($returnRequest['windowType'] == 'popup'){
                            $hashTag = $req['bdId'] == Board::BASIC_GOODS_REIVEW_ID ? 'detail-review' : 'detail-qna';
                            $this->js($addScrpt . "alert('" . __('저장되었습니다.') . "');parent.location.href='../goods/goods_view.php?goodsNo=" . $req['goodsNo'] . "#".$hashTag."';");
                        }
                        else {
                            $this->js($addScrpt . "alert('" . __('저장되었습니다.') . "');parent.location.replace(document.referrer);");
                        }
                    } else {
                        $this->js($addScrpt . 'location.href="../board/list.php?' . $req['returnUrl'] . '&mypageFl=y";');
                    }
                    exit;

                } catch (\Exception $e) {
                    throw new AlertBackException($e->getMessage());
                }
                break;

            case 'ajaxUpload' : //ajax업로드
                try {
                    $fileData = Request::files()->get('uploadFile');
                    if(!$fileData){
                        $this->json(['result' => 'cancel']);
                    }

                    // 파일 업로드 취약점 조치
                    if (Validator::validateIncludeEval($fileData['tmp_name']) === false) {
                        throw new AlertBackException('업로드 할 수 없는 파일입니다.');
                    }

                    // 기본 저장소 인지 확인
                    if (Board::isDefaultUploadStorage($req['bdId'])) {
                        // 기본 저장소일 경우 OBS Upload 진행
                        $boardConfig = new BoardConfig($req['bdId']);
                        $result = (new ImageUploadService())->uploadImage($fileData, '/temp', true, $boardConfig->cfg['bdUploadMaxSize']);
                    } else {
                        // 저장소 변경일 경우 해당 저장소에 Upload 진행
                        $boardAct = new BoardAct($req);
                        if (method_exists($boardAct, 'setHttpStorage') === true) { //HTTP Storage setting.
                            $boardAct->setHttpStorage(true, ['target'=>'board', 'req'=>$req, 'uploadName'=>'uploadFile', 'methodName'=>'uploadAjax']);
                        }
                        $result = $boardAct->uploadAjax($fileData);
                    }

                    if ($result['result'] == false) {
                        throw new \Exception(__('업로드에 실패하였습니다.'));
                    }
                    $this->json(['result' => 'ok', 'uploadFileNm' => $result['uploadFileNm'], 'saveFileNm' => $result['saveFileNm']]);
                } catch (\Exception $e) {
                    $this->json(['result' => 'fail', 'errorMsg' => $e->getMessage()]);
                }
                break;

            case  'deleteGarbageImage' :    //ajax업로드 시 가비지이미지 삭제
                $boardAct = new BoardAct($req);
                $boardAct->deleteUploadGarbageImage($req['deleteImage']);
                break;
            case 'category': // 말머리 양식글 가져오기
                try {
                    $boardAct = new BoardAct($req);
                    $result = $boardAct->getBdCategoryTemplate($req);
                    echo $result;
                    exit;
                } catch (\Exception $e) {
                    $this->json(['result' => 'fail', 'msg' => $e->getMessage()]);
                }
                break;
            case 'report':
                try {
                    $boardAct = new BoardReport($req);
                    $result = $boardAct->getWrite($req);
                    if ($result) {
                        $this->js('alert("신고 되었습니다."); location.href="'.  $req['returnUrl'] . '";');
                    } else {
                        $this->js('alert("신고에 실패하였습니다."); location.href="'.  $req['returnUrl'] . '";');
                    }
                } catch (\Exception $e) {
                    echo $this->json(['message' => $e->getMessage()]);
                }
                break;
            case 'block':
                try {
                    $memberAct = new MemberReport($req);
                    $reportedMemNo = $req['memoWriterMemNo'] ? $req['memoWriterMemNo'] : $req['writerMemNo'];
                    $hResult = $memberAct->isHackout($reportedMemNo);
                    $sResult = $memberAct->isSleep($reportedMemNo);
                    if ($hResult === true || $sResult === true) {
                        throw new AlertBackException(__('신고 및 차단을 진행할 수 없는 회원입니다.'));
                    }
                    $result = $memberAct->getWrite($req);
                    if ($result) {
                        $this->js('alert("정상 처리되었습니다."); location.href="' . $req['returnUrl'] . '";');
                    } else {
                        $this->js('alert("신고에 실패하였습니다."); location.href="' . $req['returnUrl'] . '";');
                    }
                } catch (AlertBackException $e) {
                    throw new AlertBackException($e->getMessage());
                } catch (\Exception $e) {
                    echo $this->json(['message' => $e->getMessage()]);
                }
                break;
        }

        switch (Request::get()->get('mode')) {
            case 'searchGoods':
                try {
                    $data = Request::get()->toArray();
                    $goodsSearch = \App::load('Component\Goods\GoodsSearch');
                    $getData = $goodsSearch->getSearchedGoodsList($data);
                    $page = \App::load('Component\Page\Page', Request::get()->get('page'), 1); // 페이지 설정
                    $page->recode['total'] = $getData['cnt']['search']; // 검색 레코드 수

                    $page->set_page();
                    if (isset($getData['goodsData']) === false) {
                        $getData['goodsData'] = '';
                    }
                    $jsonData = array('goodsData' => $getData['goodsData'], 'pager' => $page->getPage('SearchGoods.search(PAGELINK)'));
                } catch (\Exception $e) {
                    $jsonData[] = 'fail';
                    $jsonData[] = alert_reform($e->getMessage());
                }

                echo 'data=' . json_encode($jsonData);
                break;
            case 'recommend' :  //추천하기
                try {
                    $boardAct = new BoardAct(['bdId' => Request::get()->get('bdId')]);
                    $recommendCount = $boardAct->recommend(Request::get()->get('sno'));
                    echo $this->json(['message' => __('추천되었습니다.'), 'recommendCount' => $recommendCount]);
                } catch (\Exception $e) {
                    echo $this->json(['message' => $e->getMessage()]);
                }
                exit;
                break;

        }
        exit;
    }
}
