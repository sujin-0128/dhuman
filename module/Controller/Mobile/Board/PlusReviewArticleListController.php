<?php

/**
 * This is commercial software, only users who have purchased a valid license
 * and accept to the terms of the License Agreement can install and use this
 * program.
 *
 * Do not edit or add to this file if you wish to upgrade Enamoo S5 to newer
 * versions in the future.
 *
 * @copyright Copyright (c) 2015 GodoSoft.
 * @link http://www.godo.co.kr
 */
namespace Controller\Mobile\Board;

use Component\Designpix\Dpx;

class PlusReviewArticleListController extends \Bundle\Controller\Mobile\Board\PlusReviewArticleListController
{
    public function index()
    {
        parent::index();

        #region designpix.cjchan
		$dpx = new Dpx();
        
        $data = $this->getData('data');
        // 리뷰 데이터에 추천 여부 추가
        foreach ($data['list'] as $key => $val) {
            $data['list'][$key]['isRecommended'] = $dpx->getIsRecommended($val['sno']);
        }
        $this->setData('data', $data);
        #endregion
    }
}