<?php

namespace Controller\Mobile\Service;

use Framework\Utility\GodoUtils;
use League\Flysystem\Exception;
use Request;

class FeedCalPsController extends \Bundle\Controller\Mobile\Controller
{
    /**
     * {@inheritdoc}
     */
    public function index()
    {
		$dpx = \App::load('\\Component\\Designpix\\Dpx2');
        $post = Request::post()->toArray();

        switch ($post['mode']) {
            case 'get_food_nm':
                try {
					$result = $dpx->getFoodNm($post['food_brand']);

	                echo json_encode($result);
                    exit;
				} catch (\Exception $e) {
                    echo json_encode($e);
                }
                break;

            case 'get_calorie':
                try {
					$result = $dpx->getCalorie($post['pet_weight'], $post['food_brand'], $post['food_name'], $post['food_mix']);

	                echo json_encode($result);
                    exit;
				} catch (\Exception $e) {
                    echo json_encode($e);
                }
                break;
		}

        exit();
    }
}
