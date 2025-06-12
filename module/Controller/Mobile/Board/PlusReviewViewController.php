<?php

namespace Controller\Mobile\Board;

use Component\Designpix\Dpx;

class PlusReviewViewController extends \Bundle\Controller\Mobile\Board\PlusReviewViewController
{
    public function index()
    {
        parent::index();

        #region designpix.cjchan
		$dpx = new Dpx();
        
        $req = $this->getData('req');
        // 리뷰 데이터에 추천 여부 추가
        $req['isRecommended'] = $dpx->getIsRecommended($req['sno']);
        $this->setData('req', $req);
        #endregion
    }
}