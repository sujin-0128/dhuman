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
namespace Controller\Mobile;

use Component\Member\Util\MemberUtil;
use Request;
use Session;
use Cookie;

/**
 * 모바일 접속 페이지
 *
 * @author Jong-tae Ahn <qnibus@godo.co.kr>
 */
class IndexController extends \Bundle\Controller\Mobile\Controller
{
    /**
     * {@inheritdoc}
     */
    public function index()
    {
		if(Request::isMyapp()){
			$this->setData('DhumanApp','on');
		}else{
			$this->setData('DhumanApp','off');
		}



		//$getValue['sno'] = 1;

		// 모듈 설정
        $goods = \App::load('\\Component\\Goods\\Goods');
        $displayConfig = \App::load('\\Component\\Display\\DisplayConfig');
        $timeSale = \App::load('\\Component\\Promotion\\TimeSale');
		$dpx = \App::load('\\Component\\Designpix\\Dpx');

		$getValue['sno'] = $dpx->getTimeSaleSno();

		$getData = $timeSale->getInfoTimeSale($getValue['sno']);

		$getData['repre'] = $getData['goodsNo'];

		$this->setData('timeSaleInfo', gd_isset($getData));

		$today = date("Y-m-d H:i:s");
		
		
		if( $getData['endDt'] > $today && $getData['startDt'] < $today ){
			$eventOver = "n";
		}else{
			$eventOver = "y";
		}

		$this->setData('eventOver', $eventOver);


		$repreData = $dpx->getRepreData($getData['repre']);

		$this->setData('repreData', $repreData);
		$this->setData('getData', $getData);
		

        // main/index 파일을 호출
        // naver 정책에 의해 index 파일 무조건 해당 위치로
		
		//20250331 듀먼측 요청으로 첫구매 페이지 처음으로 랜딩되도록 수정
		//20250513 듀먼측 요청으로 특정일(20250514 00시) 이후 회원페이지로 랜딩되도록 수정
		echo "<script>
			const baseDate = new Date('2025-05-14T00:00:00');
			const now = new Date();
			
			if(now < baseDate){
				window.location.replace('/main/main_first.php');
			}else{
				window.location.replace('/main/main_sub.php');
			}
		</script>";		
		//$this->getView()->setPageName(gd_entryway('mobile'));
	


		//dpx.farmer 2021209 메인페이지 베스트리뷰 가져오기
		$getBestReview = $dpx->getMainBestReview();
		
		foreach( $getBestReview as $key=>$val) {
			$bestReviewListGoods[$key] = $dpx->bestReviewListGoods($val['goodsNo']);	//리뷰리스트 상품정보	
		
			$getBestReview[$key]['goodsNm'] = $bestReviewListGoods[$key]['goodsNm'];
			$getBestReview[$key]['goodsPrice'] = $bestReviewListGoods[$key]['goodsPrice'];
			$getBestReview[$key]['fixedPrice'] = $bestReviewListGoods[$key]['fixedPrice'];
			$getBestReview[$key]['goodsImagePath'] = $bestReviewListGoods[$key]['imagePath'];
			$getBestReview[$key]['goodsImage'] = $bestReviewListGoods[$key]['goodsImage'];
			$getBestReview[$key]['reviewCnt'] = $bestReviewListGoods[$key]['plusReviewCnt'] + $bestReviewListGoods[$key]['naverReviewCnt'];
			$getBestReview[$key]['saveFileNm'] = explode('^|^',$getBestReview[$key]['saveFileNm'])[0];
			$getBestReview[$key]['contents'] = str_replace("\\r\\n","<br>",$getBestReview[$key]['contents']);
		}

		$this->setData('getBestReview',$getBestReview);
		//dpx-hy 240725 디파이너리 관련 S
		$memInfo = $this->getData('gMemberInfo');
		
		$dpx2 = \App::load('\\Component\\Designpix\\Dpx2');
		$dUserData = $this->getData('gMemberInfo');
		$entryDtData = $dpx2->memEntryDt($memInfo['memNm'],$memInfo['memberFl'],$memInfo['email'],$memInfo['cellPhone']);
		$this->setData('entryDtData', $entryDtData);
		$this->setData('dUserData', $dUserData);
		//dpx-hy 240725 디파이너리 관련 E

        if (\Request::isMyapp()) {
            $isMyApp = \Cookie::get('wmAutoLogin');
            if ($isMyApp) {

                if (gd_is_login() === false) {
                    if (MemberUtil::isLogin() === false) {
                        MemberUtil::logout();
                        \Cookie::set(MemberUtil::COOKIE_LOGIN, $isMyApp, (3600 * 24 * 10));
                        $data = MemberUtil::getCookieByLogin();

                        if($data['id']){
                            $member = \App::load('\\Component\\Member\\Member');
                            $member->login(Encryptor::decrypt($data['id']), Encryptor::decrypt($data['password']));
                        }

                    }
                }
            }
        }
    }
}
