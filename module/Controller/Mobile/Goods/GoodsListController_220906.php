<?php

/**
 * This is commercial software, only users who have purchased a valid license
 * and accept to the terms of the License Agreement can install and use this
 * program.
 *
 * Do not edit or add to this file if you wish to upgrade Godomall5 to newer
 * versions in the future.
 *
 * @copyright �� 2016, NHN godo: Corp.
 * @link http://www.godo.co.kr
 */
namespace Controller\Mobile\Goods;

use Framework\Debug\Exception\AlertBackException;
use Framework\Debug\Exception\AlertRedirectException;
use Framework\Debug\Exception\Framework\Debug\Exception;
use Message;
use Globals;
use Request;
use Cookie;

class GoodsListController extends \Bundle\Controller\Mobile\Goods\GoodsListController
{

	/**
     * ��ǰ���
     *
     * @author artherot, sunny
     * @version 1.0
     * @since 1.0
     * @copyright Copyright (c), Godosoft
     * @throws Except
     */
    public function index()
    {
		if(Request::isMyapp()){
			$this->setData('DhumanApp','on');
		}else{
			$this->setData('DhumanApp','off');
		}
        $checkParameter = ['cateCd', 'brandCd'];
        $getValue = Request::get()->length($checkParameter)->toArray();

		//Ʃ��
		$dpx = \App::load('\\Component\\Designpix\\Dpx');
        // ��� ����
        $goods = \App::load('\\Component\\Goods\\Goods');
        if($getValue['brandCd']) $cate = \App::load('\\Component\\Category\\Brand');
        else $cate = \App::load('\\Component\\Category\\Category');

        try {
            // ī�װ� ����
            if($getValue['brandCd'])  {
                $cateCd =  $getValue['brandCd'];
                $cateType = "brand";
                $cateMode = "brand";
                $naviDisplay = gd_policy('display.navi_brand');
            } else {
                $cateCd = $getValue['cateCd'];
                $cateType = "cate";
                $cateMode = "category";
                $naviDisplay = gd_policy('display.navi_category');
            }

            $cateInfo = $cate->getCategoryGoodsList($cateCd,'y');
            $goodsCategoryList = $cate->getCategories($cateCd,'y');
            $goodsCategoryListNm = array_column($goodsCategoryList, 'cateNm');

            if(gd_isset($cateInfo['themeCd']) ===null) {
                throw new \Exception(__('��ǰ�� �׸� ������ Ȯ�����ּ���.'));
            }

            // ���ϸ��� ����
            $mileage = gd_mileage_give_info();

            $this->setData('gPageName', $goodsCategoryList[$cateCd]['cateNm']);
            $this->setData('cateInfo', gd_isset($cateInfo));

            Request::get()->set('page',$getValue['page']);
            Request::get()->set('sort',$getValue['sort']);

            if($cateInfo['recomDisplayMobileFl'] =='y' && $cateInfo['recomGoodsNo'])
            {
                $recomTheme = $cateInfo['recomTheme'];
                if ($recomTheme['detailSet']) {
                    $recomTheme['detailSet'] = unserialize($recomTheme['detailSet']);
                }

                gd_isset($recomTheme['lineCnt'],4);
                $imageType		= gd_isset($recomTheme['imageCd'],'list');						// �̹��� Ÿ�� - �⺻ 'main'
                $soldOutFl		= $recomTheme['soldOutFl'] == 'y' ? true : false;			// ǰ����ǰ ��� ���� - true or false (�⺻ true)
                $brandFl		= in_array('brandCd',array_values($recomTheme['displayField']))  ? true : false;	// �귣�� ��� ���� - true or false (�⺻ false)
                $couponPriceFl	= in_array('coupon',array_values($recomTheme['displayField']))  ? true : false;		// �������� ��� ���� - true or false (�⺻ false)
                $optionFl = in_array('option',array_values($recomTheme['displayField']))  ? true : false;

                if($cateInfo['recomSortAutoFl'] =='y') $recomOrder = $cateInfo['recomSortType'].",g.goodsNo desc";
                else $recomOrder = "FIELD(g.goodsNo," . str_replace(INT_DIVISION, ",", $cateInfo['recomGoodsNo']) . ")";
                if ($recomTheme['soldOutDisplayFl'] == 'n') $recomOrder = "soldOut asc," . $recomOrder;
                $recomTheme['goodsDiscount'] = explode(',', $recomTheme['goodsDiscount']);
                $recomTheme['priceStrike'] = explode(',', $recomTheme['priceStrike']);
                $recomTheme['displayAddField'] = explode(',', $recomTheme['displayAddField']);

                $goods->setThemeConfig($recomTheme);
                $goodsRecom	= $goods->goodsDataDisplay('goods', $cateInfo['recomGoodsNo'], (gd_isset($recomTheme['lineCnt']) * gd_isset($recomTheme['rowCnt'])), $recomOrder, $imageType, $optionFl, $soldOutFl, $brandFl, $couponPriceFl);

                if($goodsRecom) $goodsRecom = array_chunk($goodsRecom,$recomTheme['lineCnt']);

                $this->setData('widgetGoodsList', gd_isset($goodsRecom));
                $this->setData('widgetTheme', $recomTheme);
            }


            if($cateInfo['soldOutDisplayFl'] =='n')  $displayOrder[] = "soldOut asc";

            if ($cateInfo['sortAutoFl'] == 'y') $displayOrder[] = "gl.fixSort desc," . gd_isset($cateInfo['sortType'], 'gl.goodsNo desc');
            else $displayOrder[] = "gl.fixSort desc,gl.goodsSort desc";

            // ��ǰ ����
            $displayCnt = gd_isset($cateInfo['lineCnt']) * gd_isset($cateInfo['rowCnt']);
            $pageNum = gd_isset($getValue['pageNum'],$displayCnt);
            $optionFl = in_array('option',array_values($cateInfo['displayField']))  ? true : false;
            $soldOutFl = (gd_isset($cateInfo['soldOutFl']) == 'y' ? true : false); // ǰ����ǰ ��� ����
            $brandFl =  in_array('brandCd',array_values($cateInfo['displayField']))  ? true : false;
            $couponPriceFl =in_array('coupon',array_values($cateInfo['displayField']))  ? true : false;	 // ������ ��� ����
            $goods->setThemeConfig($cateInfo);
            $goodsData = $goods->getGoodsList($cateCd, $cateMode, $pageNum,$displayOrder, gd_isset($cateInfo['imageCd']), $optionFl, $soldOutFl, $brandFl, $couponPriceFl,null,$displayCnt);



			//Ʃ�� SearchWord
			foreach( $goodsData['listData'] as $key=>$val) {
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

			foreach( $goodsData['listData'] as $key=>$val) {
				
				$goodsData['listData'][$key]['exGoodsSearchWord'] = $exGoodsSearchWord[$key];

			}

			//////////


            if($goodsData['listData']) $goodsList = array_chunk($goodsData['listData'],$cateInfo['lineCnt']);
            $page = \App::load('\\Component\\Page\\Page'); // ������ �缳��
            unset($goodsData['listData']);
            //ǰ����ǰ ����
            $soldoutDisplay = gd_policy('soldout.mobile');

            // ī�װ� �����׸� �� ��ǰ���ΰ�
            if (in_array('goodsDcPrice', $cateInfo['displayField'])) {
                foreach ($goodsList as $key => $val) {
                    foreach ($val as $key2 => $val2) {
                        $goodsList[$key][$key2]['goodsDcPrice'] = $goods->getGoodsDcPrice($val2);
                    }
                }
            }

            // ��ٱ��� ����
            if ($cateInfo['displayType'] == '11') {
                $cartInfo = gd_policy('order.cart');
                $this->setData('cartInfo', gd_isset($cartInfo));
            }



			//Ʃ�� plusReview goodsPt
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



            $this->setData('cateCd', $cateCd);
            $this->setData('goodsCategoryListNm', gd_isset($goodsCategoryListNm));
            $this->setData('brandCd', $getValue['brandCd']);
            $this->setData('cateType', $cateType);
            $this->setData('themeInfo', gd_isset($cateInfo));
            $this->setData('goodsList', gd_isset($goodsList));
            $this->setData('page', gd_isset($page));
            $this->setData('naviDisplay', gd_isset($naviDisplay));
            $this->setData('soldoutDisplay', gd_isset($soldoutDisplay));
            $this->setData('mileageData', gd_isset($mileage['info']));
            $this->setData('currency', Globals::get('gCurrency'));

            if($getValue['mode'] == 'data') {
                $this->getView()->setPageName('goods/list/list_'.$getValue['displayType']);
            } else {
                $this->getView()->setDefine('goodsTemplate', 'goods/list/list_'.$cateInfo['displayType'].'.html');
            }

        } catch (\Exception $e) {
            throw new AlertRedirectException($e->getMessage(),null,null,"/");
        }
    }

	public function post(){
			$mPc='m';
			$dpx = \App::load('\\Component\\Designpix\\Dpx');
			$cateType = $this->getData('cateType');
			$cateCd = $this->getData('cateCd');
			$cateSet = $dpx->setCateNmLink($cateCd,$cateType,$mPc);
			$cateSet2 = $dpx->setCateNmLink2($cateCd,$cateType,$mPc);
			$this->setData('cateSet', gd_isset($cateSet));
			$this->setData('cateSet2', gd_isset($cateSet2));

	}
}
