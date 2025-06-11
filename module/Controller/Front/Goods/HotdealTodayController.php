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
namespace Controller\Front\Goods;

use Component\Board\Board;
use Component\Board\BoardBuildQuery;
use Component\Board\BoardList;
use Component\Board\BoardWrite;
use Component\Naver\NaverPay;
use Component\Page\Page;
use Component\Promotion\SocialShare;
use Component\Mall\Mall;
use Framework\Debug\Exception\AlertRedirectException;
use Framework\Debug\Exception\Except;
use Framework\Debug\Exception\AlertBackException;
use Component\Validator\Validator;
use Message;
use Globals;
use Request;
use Logger;
use Session;
use Exception;
use Endroid\QrCode\QrCode as EndroidQrCode;
use SocialLinks\Page as SocialLink;
use FileHandler;

class HotdealTodayController extends \Bundle\Controller\Front\Controller
{
    public function index()
    {
		
        $getValue = Request::get()->toArray();

        // 모듈 설정
        $goods = \App::load('\\Component\\Goods\\Goods');
        $displayConfig = \App::load('\\Component\\Display\\DisplayConfig');
        $timeSale = \App::load('\\Component\\Promotion\\TimeSale');

		//튜닝
		$dpx = \App::load('\\Component\\Designpix\\Dpx');


        try {

            $getData = $timeSale->getInfoTimeSale(1);

            if($getData) {

                /*
				if (!Session::has('manager.managerId')) {
                    if ($getData['endDt'] < date('Y-m-d H:i:s')) {
                        throw new \Exception(__('타임세일이 종료되었습니다.'));
                    } else if ($getData['startDt'] > date('Y-m-d H:i:s')) {
                        throw new \Exception(__('타임세일이 존재하지 않습니다.'));
                    }
                }
				*/

                $timeSaleDuration = strtotime($getData['endDt'])- time();

                //$getData['startDt'] = gd_date_format("Y.m.d H:i", $getData['startDt']);
                //$getData['endDt'] = gd_date_format("Y.m.d H:i" ,$getData['endDt']);

                $themeInfo = $displayConfig->getInfoThemeConfig($getData['pcThemeCd']);

                if ($themeInfo['detailSet']) $themeInfo['detailSet'] = unserialize($themeInfo['detailSet']);
                $themeInfo['displayField'] = explode(",", $themeInfo['displayField']);

                $imageType = gd_isset($themeInfo['imageCd'], 'main');                        // 이미지 타입 - 기본 'main'
                $soldOutFl = $themeInfo['soldOutFl'] == 'y' ? true : false;            // 품절상품 출력 여부 - true or false (기본 true)
                $brandFl = in_array('brandCd', array_values($themeInfo['displayField'])) ? true : false;    // 브랜드 출력 여부 - true or false (기본 false)
                $couponPriceFl = in_array('coupon', array_values($themeInfo['displayField'])) ? true : false;        // 쿠폰가격 출력 여부 - true or false (기본 false)
                $optionFl = in_array('option', array_values($themeInfo['displayField'])) ? true : false;

                if($getValue['sort']) {
                    $mainOrder =$getValue['sort'];
                    if (method_exists($goods, 'getSortMatch')) {
                        $mainOrder = $goods->getSortMatch($mainOrder);
                    }
                } else {
                    if($getData['sort']) $mainOrder = $getData['sort'];
                    else $mainOrder = "FIELD(g.goodsNo," . str_replace(INT_DIVISION, ",", $getData['goodsNo']) . ")";
                }
                if ($themeInfo['soldOutDisplayFl'] == 'n') $mainOrder = "g.soldOutFl desc," . $mainOrder;

                //타임세일 더보기 추가
                if ($getData['moreBottomFl'] === 'y') {
                    if ($getValue['mode'] == 'get_more') $displayCnt = gd_isset($themeInfo['lineCnt']) * gd_isset($themeInfo['rowCnt'])*$getValue['more'];
                    else $displayCnt = (gd_isset($themeInfo['lineCnt']) * gd_isset($themeInfo['rowCnt']));
                } else {
                    $displayCnt = count(explode(INT_DIVISION,$getData['goodsNo']));
                }

                $tmpGoodsList = $goods->goodsDataDisplay('goods',$getData['goodsNo'], $displayCnt, $mainOrder, $imageType, $optionFl, $soldOutFl, $brandFl, $couponPriceFl, null, $getData['moreBottomFl'] == 'y' ? true : false);

                $page = \App::load('\\Component\\Page\\Page'); // 페이지 재설정

                if ($tmpGoodsList) {
                    $this->setData('goodsListCnt', ($getData['moreBottomFl'] === 'y') ? $page->recode['total'] : count($tmpGoodsList));
                    $goodsList = array_chunk($tmpGoodsList, $themeInfo['lineCnt']);
                }

                if ($themeInfo['displayType'] == '02' || $themeInfo['displayType'] == '11') {
                    $cartInfo = gd_policy('order.cart'); //장바구니설정
                    $this->setData('cartInfo', gd_isset($cartInfo));
                }


				//튜닝 SearchWord
				foreach( $tmpGoodsList as $key=>$val) {
					$goodsSearchWord = $dpx->getGoodsSearchWord($val['goodsNo']);
					
					$exSword[] = explode(",",$goodsSearchWord['goodsSearchWord']);

				}

				foreach( $exSword as $key=>$val) {
					foreach( $val as $k=>$v) {
						//gd_debug("#".$v);
						if( $v ){
							$v = trim($v);
							$exGoodsSearchWord[$key][$k] = "#".$v;
						}
					}
				}

				foreach( $tmpGoodsList as $key=>$val) {
					
					$tmpGoodsList[$key]['exGoodsSearchWord'] = $exGoodsSearchWord[$key];

				}



				//튜닝 plusReview goodsPt
				foreach( $tmpGoodsList as $key=>$val) {
					$tmpGoodsList[$key]['goodsPt'] = $dpx->getPlusReviewGoodsPt($val['goodsNo']);
					$tmpGoodsList[$key]['goodsPtCnt'] = $dpx->getPlusReviewGoodsPtCount($val['goodsNo']);

					$goodsPt = $dpx->getPlusReviewGoodsPt($val['goodsNo']);

					$goodsEx = explode(".",$goodsPt);

					$tmpGoodsList[$key]['goodsPtUp'] = $goodsEx[0];
					$tmpGoodsList[$key]['goodsPtDown'] = $goodsEx[1];
			
				}		


				//타임세일 종료시 미노출
				$today = date("Y-m-d h:i:s");
		
		
				if( $getData['endDt'] < $today ){
					$eventOver = "y";
				}
				else{
					$eventOver = "n";
				}

				$this->setData('eventOver', $eventOver);


                //품절상품 설정
                $soldoutDisplay = gd_policy('soldout.pc');

                //더보기 추가
                $this->setData('totalPage', gd_isset($page->page['total'],1));

                // 마일리지 정보
                $mileage = gd_mileage_give_info();
                $this->setData('timeSaleList', gd_isset($timeSale->getListTimeSale()));
                $this->setData('timeSaleSno',$getValue['sno']);
                //$this->setData('goodsList', gd_isset($goodsList));
				//array_chunk 유무 상관없이 3개 노출
				$this->setData('goodsList', gd_isset($tmpGoodsList));
                $this->setData('page', gd_isset($page));
                $this->setData('goodsData', gd_isset($goodsData));
                $this->setData('pageNum', gd_isset($pageNum));
                $this->setData('soldoutDisplay', gd_isset($soldoutDisplay));
                $this->setData('naviDisplay', gd_isset($naviDisplay));
                $this->setData('sort', gd_isset($getValue['sort']));
                $this->setData('mileageData', gd_isset($mileage['info']));
                $this->setData('themeInfo', gd_isset($themeInfo));
                $this->setData('timeSaleInfo', gd_isset($getData));
                $this->setData('timeSaleDuration', gd_isset($timeSaleDuration));

                if($getValue['mode'] == 'get_more') {
                    //$this->getView()->setPageName('goods/list/list_'.$themeInfo['displayType']);
                }

                //$this->getView()->setDefine('goodsTemplate', 'goods/list/list_'.$themeInfo['displayType'].'.html');
            } else {
                throw new \Exception(__('타임세일이 존재하지 않습니다.'));
            }

        } catch (\Exception $e) {
            throw new AlertBackException($e->getMessage());
        }


    }
}
