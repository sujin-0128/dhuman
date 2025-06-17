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

use App;
use Session;
use Request;
use Exception;

/**
 * Class LayerBundleMainBuyController
 *
 * @package Bundle\Controller\Mobile\Goods
 * @author  su
 */
class LayerBundleMainBuyController extends \Controller\Mobile\Controller
{
    public $bannerPathDefault = 'img/banner';

    /**
     * @inheritdoc
     */
    public function index()
    {

        $postValue = Request::post()->toArray();
        $bannerHtml = '';
        $bannerLink = '';
        $currentPage = $postValue['currentPage'];

        $dpx = \App::load('\\Component\\Designpix\\Dpx');
		$getData = $dpx->checkAllowNoBundleSale($postValue['goodsNo']);


        if($getData[0]['mainBuyBanner']){
            $designBanner = \App::load('\\Component\\Design\\DesignBanner');
            $bannerDeviceType = $this->getRootDirecotory();
            $skinName = \Globals::get('gSkin.' . $bannerDeviceType . 'SkinName');

            $getBannerData = $dpx->getBundelBannerData($skinName,$getData[0]['mainBuyBanner']);

            if($getBannerData){
                $bannerImagePath = $bannerDeviceType . DS . $skinName . DS . $this->bannerPathDefault . DS;
                $bannerHtml = gd_html_banner_image($bannerImagePath . $getBannerData[0]['bannerImage'], $getBannerData[0]['bannerImageAlt']);
                $bannerLink = $getBannerData[0]['bannerLink'];
            }
            
        }

        $this->setData('bannerHtml', gd_isset($bannerHtml));
        $this->setData('bannerLink', gd_isset($bannerLink));
        $this->setData('currentPage', gd_isset($currentPage));

    }
}
