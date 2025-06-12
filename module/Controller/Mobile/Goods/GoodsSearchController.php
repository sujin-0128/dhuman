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
namespace Controller\Mobile\Goods;

use Framework\Debug\Exception\AlertBackException;
use Component\Nhn\PaycosearchApi;
use Component\Page\Page;
use Request;
use Globals;
use Message;

class GoodsSearchController  extends \Bundle\Controller\Mobile\Goods\GoodsSearchController
{

    /**
     * 상품 검색
     *
     * @author artherot
     * @version 1.0
     * @since 1.0
     * @copyright Copyright (c), Godosoft
     */
    public function index()
    {
        try {
            // 모듈 설정
            $goods = \App::load('\\Component\\Goods\\Goods');
            $paycosearch = \App::load('\\Component\\Nhn\\PaycosearchApi');
            $getValue = Request::get()->xss()->toArray();
            $cate = \App::load('\\Component\\Category\\Category');

            // xss 보안 취약점 개선
            if (strpos($getValue['keyword'], 'alert(\'') !== false) {
                $getValue['keyword'] = str_replace(array('\'+alert(\'', '\')+\''), '', $getValue['keyword']);
            }

            Request::get()->set('page',($getValue['page'] ?? 1));
            Request::get()->set('sort',$getValue['sort']);
            Request::get()->set('keyword',$getValue['keyword']);
            Request::get()->set('key','all'); // 통합검색

            //설정
            $goodsConfig = gd_policy('search.goods');
            gd_isset($goodsConfig['mobileThemeCd'], 'A0000002');


            //테마정보
            $displayConfig = \App::load('\\Component\\Display\\DisplayConfig');
            $themeInfo = $displayConfig->getInfoThemeConfig($goodsConfig['mobileThemeCd']);
            $themeInfo['displayField'] = explode(",", $themeInfo['displayField']);
            $themeInfo['goodsDiscount'] = explode(",", $themeInfo['goodsDiscount']);
            $themeInfo['priceStrike'] = explode(",", $themeInfo['priceStrike']);
            $themeInfo['displayAddField'] = explode(",", $themeInfo['displayAddField']);

            $cateInfo = $displayConfig->getInfoThemeConfigCate('B','y')[0];
            $cateInfo['displayField'] = explode(",", $cateInfo['displayField']);

            $displayCnt = gd_isset($themeInfo['lineCnt']) * gd_isset($themeInfo['rowCnt']);
            $pageNum = gd_isset($getValue['pageNum'],$displayCnt);
            $optionFl = in_array('option',array_values($themeInfo['displayField']))  ? true : false;
            $soldOutFl = (gd_isset($themeInfo['soldOutFl']) == 'y' ? true : false); // 품절상품 출력 여부
            $brandFl =  in_array('brandCd',array_values($themeInfo['displayField']))  ? true : false;
            $couponPriceFl =in_array('coupon',array_values($themeInfo['displayField']))  ? true : false;	 // 쿠폰가 출력 여부
            $brandDisplayFl =in_array('brand',array_values($goodsConfig['searchType']))  ? true : false;	 // 브랜드 출력여부


            if ($themeInfo['soldOutDisplayFl'] == 'n') $goodsConfig['sort'] = "soldOut asc," . $goodsConfig['sort'];

            $paycosearchDataCheck = false;
            if ($paycosearch->paycoSearchActionPoint() === true) {
                // 페이코 서치 사용
                $paycoSearchReturnData = $paycosearch->paycoSearchDataProcess($getValue,'mobile', gd_isset($themeInfo['imageCd']));
                $goodsData = $paycosearch->paycoSearchSortData($paycoSearchReturnData, $displayCnt);

                if(!empty($goodsData['listData'])) {
                    $paycosearchDataCheck = true;
                    $this->setData('mileageData', gd_isset($paycoSearchReturnData['mileageData']));

                    $page = \App::load('\\Component\\Page\\Page', $getValue['page']);
                    $page->page['list'] = $pageNum; // 페이지당 리스트 수
                    $page->block['cnt'] = !Request::isMobile() ? 10 : 5; // 블록당 리스트 개수
                    $page->setPage();
                    $page->setUrl(\Request::getQueryString());
                    $page->recode['total'] = $paycoSearchReturnData['paycoSearchTotal'];
                    $page->setPage();
                    $paycosearchUse = true;
                }
            }

            if(!$paycosearchDataCheck && $paycosearch->paycoSearchActionPoint() === false) {
                // 최근 본 상품 진열
                $goods->setThemeConfig($themeInfo);
                $goodsData	= $goods->getGoodsSearchList($pageNum, gd_isset($goodsConfig['sort']), gd_isset($themeInfo['imageCd']), $optionFl , $soldOutFl , $brandFl, $couponPriceFl ,$displayCnt,$brandDisplayFl);
                $paycosearchUse = false;
            }

            // 해당몰 상품검색
            $reCount = 0; // 검색결과에서 해외카테고리로 제외될 상품수
            foreach ($goodsData['listData'] as $key => $val) {
                $goodsCategoryArray = $cate->getCateCd($val['goodsNo']);
                if ($goodsCategoryArray) {
                    $mallIsSet = false;
                    foreach($goodsCategoryArray as $key2 => $val2) {
                        if ($cate->getCategoryData($val2, null, 'mallDisplay')[0]['mallDisplay']) {
                            $mallIsSet = true;
                        }
                    }
                    if (!$mallIsSet) {
                        unset($goodsData['listData'][$key]);
                        $reCount += 1;
                    }
                }
            }

            if($goodsData['listData']) $goodsList = array_chunk($goodsData['listData'],$themeInfo['lineCnt']);

            $pager = \App::load('\\Component\\Page\\Page'); // 페이지 재설정

            // 총검색수 재설정
            if($reCount) {
                $pager->setTotal($pager->getTotal()-$reCount);
                $pager->recode['limit'] = $pager->getTotal();
            }

            //품절상품 설정
            $soldoutDisplay = gd_policy('soldout.mobile');

            if ($soldoutDisplay['soldout_icon_img']) {
                $fileSplit = explode(DIRECTORY_SEPARATOR, $soldoutDisplay['soldout_icon_img']);
                $soldout_icon_img = array_splice($fileSplit, -1, 1, DIRECTORY_SEPARATOR);
                $soldoutDisplay['soldout_icon_img_filename'] = $soldout_icon_img[0];
            }

            if ($soldoutDisplay['soldout_price_img']) {
                $fileSplit = explode(DIRECTORY_SEPARATOR, $soldoutDisplay['soldout_price_img']);
                $soldout_price_img = array_splice($fileSplit, -1, 1, DIRECTORY_SEPARATOR);
                $soldoutDisplay['soldout_price_img_filename'] = $soldout_price_img[0];
            }

            // 최근검색어 쿠키 저장
            if (empty($getValue['keyword']) === false) {
                $goods->getRecentKeywordSearch($getValue['keyword']);
            }

            if ($getValue['mode'] == 'get_search_list') {
                $this->getView()->setPageName('goods/list/list_'.$getValue['displayType']);
            }

            // 카테고리 노출항목 중 상품할인가
            if (in_array('goodsDcPrice', $themeInfo['displayField'])) {
                foreach ($goodsList as $key => $val) {
                    foreach ($val as $key2 => $val2) {
                        $goodsList[$key][$key2]['goodsDcPrice'] = $goods->getGoodsDcPrice($val2);
                    }
                }
            }

			//디자인픽스 튜닝 220804
			$dpx = \App::load('\\Component\\Designpix\\Dpx');
			foreach( $goodsList as $key=>$val) {
				foreach($val as $k=>$v) {
					$goodsList[$key][$k]['goodsPt'] = $dpx->getPlusReviewGoodsPt($v['goodsNo']);
					$goodsList[$key][$k]['goodsPtCnt'] = $dpx->getPlusReviewGoodsPtCount($v['goodsNo']);

					$goodsPt = $dpx->getPlusReviewGoodsPt($v['goodsNo']);

					$goodsEx = explode(".",$goodsPt);

					$goodsList[$key][$k]['goodsPtUp'] = $goodsEx[0];
					$goodsList[$key][$k]['goodsPtDown'] = $goodsEx[1];
				}			
			}
			//튜닝

            $this->setData('cateInfo', gd_isset($cateInfo));
            $this->setData('page', gd_isset($pager));
            $this->setData('soldoutDisplay', $soldoutDisplay);
            $this->setData('goodsList', $goodsList);
            $this->setData('paycosearchUse', $paycosearchUse);

            //facebook Dynamic Ads 외부 스크립트 적용
            $facebookAd = \App::Load('\\Component\\Marketing\\FacebookAd');
            $fbConfig = $facebookAd->getExtensionConfig();
            if(empty($fbConfig)===false && $fbConfig['fbUseFl'] == 'y') {
                // 상품번호 추출
                $goodsNo = [];
                foreach ($goodsList as $key => $val){
                    foreach($val as $key2){
                        $goodsNo[] = $key2['goodsNo'];
                    }
                }
                $fbScript = $facebookAd->getFbSearchScript($getValue['keyword'], $goodsNo);
                $this->setData('fbSearchScript', $fbScript);
            }

        } catch (\Exception $e) {
            throw new AlertBackException($e->getMessage());
        }

        $mileage = gd_mileage_give_info();
        $this->setData('mileageData', gd_isset($mileage['info']));

        $this->setData('goodsConfig', gd_isset($goodsConfig));
        $this->setData('hitKeywordConfig', gd_isset($hitKeywordConfig));
        $this->setData('keywordConfig', gd_isset($keywordConfig));
        $this->setData('quickConfig', gd_isset($quickConfig));

        $this->setData('themeInfo', gd_isset($themeInfo));
        $this->setData('keyword', gd_isset($getValue['keyword']));

    }
}


