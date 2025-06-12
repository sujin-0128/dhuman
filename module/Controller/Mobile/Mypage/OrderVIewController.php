<?php

namespace Controller\Mobile\Mypage;

use Component\PlusShop\PlusReview\PlusReviewArticleFront;
use Component\Board\BoardWrite;
use Component\Board\Board;
use Framework\Debug\Exception\AlertRedirectException;
use Framework\Debug\Exception\AlertBackException;
use Exception;
use Framework\Utility\DateTimeUtils;
use Framework\Utility\GodoUtils;
use Request;
use App;
use Session;

class OrderViewController extends \Bundle\Controller\Mobile\Mypage\OrderViewController
{
    public function index()
    {
        parent::index();
    }
}