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

namespace Controller\Admin\Order;

use Framework\Debug\Exception\LayerException;
use Component\Policy\ManageSecurityPolicy;
use Framework\Utility\NumberUtils;
use Request;

/**
 * Class LayerExcelPsController
 * @package Bundle\Controller\Admin\Share
 * @author  yjwee <yeongjong.wee@godo.co.kr>
 */
class DhumanExcelPsController extends \Controller\Admin\Controller
{
    public function index()
    {

        ini_set('memory_limit', '-1');
        set_time_limit(RUN_TIME_LIMIT);
		if(Request::server()->get('REMOTE_ADDR') != '220.118.145.49'){
        $this->streamedDownload('자사몰매출데이터.xls');
		}

        $request = \App::getInstance('request');
        $logger = \App::getInstance('logger');
        $session = \App::getInstance('session');
        $postValue = $request->post()->toArray();
        $excelRequest = \App::load('Component\\Excel\\ExcelRequest');
        $logger->info(__METHOD__, $postValue);

        // --- 검색 설정
        $orderAdmin = \App::load('\\Component\\Order\\OrderAdmin');

        $postValue['dhumanExcel'] = true;
        $orderData = $orderAdmin->getOrderListForAdminExcel($postValue,-1, false, 'goods');


        $excelList = [];
        $refundList = [];
        foreach($orderData as $k => $v) {
			//if(Request::server()->get('REMOTE_ADDR') == '220.118.145.49'){
				//gd_debug($v);
				//exit;
			//}
            $orderNo = $v['orderNo'];
            $orderStatus = substr($v['orderStatus'],0,1);

			if(!$excelList[$orderNo]) {
				$excelList[$orderNo]['orderNo'] = $v['orderNo'];
				$excelList[$orderNo]['orderNm'] = $v['orderGoodsNm'];
				$excelList[$orderNo]['orderChannelFl'] = $v['orderChannelFl'];
				$excelList[$orderNo]['settleKind'] = $v['settleKind'];
				$excelList[$orderNo]['orderStatus'] = $v['orderStatus'];
				$excelList[$orderNo]['orderName'] = $v['orderName'];
				$excelList[$orderNo]['paymentDt'] = $v['paymentDt'];


				$excelList[$orderNo]['totalDcPrice'] =  $v['totalMemberDcPrice'] + $v['totalGoodsDcPrice'] + $v['totalCouponDcPrice']+ $v['totalCouponOrderDcPrice'] + $v['totalCouponDeliveryDcPrice'];
				$excelList[$orderNo]['totalUseDeposit'] = $v['totalUseDeposit'];
				$excelList[$orderNo]['totalUseMileage'] = $v['totalUseMileage'];
				$excelList[$orderNo]['totalSettlePrice'] = $v['totalSettlePrice'];
				//241030 dpx-kwc 주문취소된 현금영수증 n처리 추가
				$excelList[$orderNo]['receiptFl'] = $orderStatus == 'r' && $v['receiptFl'] == 'r' ? 'n' : $v['receiptFl'];


            }
			$excelList[$orderNo]['scm'][$v['scmNo']]['scmNm'] = $v['scmNm'];
			$excelList[$orderNo]['scm'][$v['scmNo']]['scmNo'] = $v['scmNo'];
			

            $taxInfo = explode(STR_DIVISION, $v['goodsTaxInfo']);


            if($orderStatus =='c' || $orderStatus =='r') {
                if($postValue['treatDate'][0] <= substr($v['handleDt'],0,10) && $postValue['treatDate'][1] >= substr($v['handleDt'],0,10)) {
					//$refundList[$orderNo]['scm'][$v['scmNo']]['goodsPrice'][$taxInfo[0]] += ($v['refundPrice']+$v['refundUseMileage']+$v['divisionGoodsDeliveryUseMileage']+$v['memberDcPrice']+$v['couponGoodsDcPrice']);
					$dcPrice = $v['memberDcPrice'] + $v['goodsDcPrice'] + $v['couponGoodsDcPrice'];
					$refundUseMileage = $v['refundUseMileage'] + $v['refundDeliveryUseMileage'];
					$deliveryCharge = $v['refundDeliveryCharge']+$v['refundDeliveryUseMileage']+$v['refundDeliveryUseDeposit'];

					$refundList[$orderNo]['scm'][$v['scmNo']]['goodsPrice'][$taxInfo[0]] += $v['refundPrice'] + $dcPrice + $refundUseMileage - $deliveryCharge;
					$refundList[$orderNo]['scm'][$v['scmNo']]['dcPrice'][$taxInfo[0]] += $dcPrice;
					$refundList[$orderNo]['scm'][$v['scmNo']]['refundUseMileage'] += $refundUseMileage;
                    $refundList[$orderNo]['refundDt']= $v['handleDt'];
                    $refundList[$orderNo]['scm'][$v['scmNo']]['deliveryCharge'] += $deliveryCharge;
					$refundList[$orderNo]['refundUseDeposit'] += $v['refundUseDeposit']+$v['refundDeliveryUseDeposit'];
					if($k == 0){
						$refundList[$orderNo]['scm'][$v['scmNo']]['deliveryCharge'] += $v['totalCouponDeliveryDcPrice'];
					}
                }
            }

            $excelList[$orderNo]['scm'][$v['scmNo']]['goodsPrice'][$taxInfo[0]]+= ($v['goodsPrice'] + $v['optionPrice'] + $v['optionTextPrice'])*$v['goodsCnt'];
            $excelList[$orderNo]['scm'][$v['scmNo']]['dcPrice'][$taxInfo[0]] +=  $v['memberDcPrice'] + $v['goodsDcPrice'] + $v['couponGoodsDcPrice'];
            $excelList[$orderNo]['scm'][$v['scmNo']]['deliveryCharge'][$v['orderDeliverySno']] = $v['deliveryCharge'];
			ksort($excelList[$orderNo]['scm']);
        }

        $settleKind = $orderAdmin->getSettleKind();


        $excelHeader= '<html xmlns="http://www.w3.org/1999/xhtml" lang="ko" xml:lang="ko">' . chr(10);
        $excelHeader.= '<head>' . chr(10);
        $excelHeader.= '<title>Excel Down</title>' . chr(10);
        $excelHeader.= '<meta http-equiv="Content-Type" content="text/html; charset=' . SET_CHARSET . '" />' . chr(10);
        $excelHeader.= '<style>' . chr(10);
        $excelHeader.= 'br{mso-data-placement:same-cell;}' . chr(10);
        $excelHeader.= '.xl31{mso-number-format:"0_\)\;\\\(0\\\)";}' . chr(10);
        $excelHeader.= '.xl24{mso-number-format:"\@";} ' . chr(10);
        $excelHeader.= '.title{font-weight:bold; background-color:#F6F6F6; text-align:center;} ' . chr(10);
        $excelHeader.= '</style>' . chr(10);
        $excelHeader.= '</head>' . chr(10);
        $excelHeader.= '<body>' . chr(10);

        $excelBody = "<table border='1'>";
        $excelBody .= "<tr><th>결제대행사</th>";
        $excelBody .= "<th>결제수단</th>";
        $excelBody .= "<th>주문번호</th>";
        $excelBody .= "<th>상품명</th>";
        $excelBody .= "<th>구매자</th>";
        $excelBody .= "<th>구분</th>";
        $excelBody .= "<th>결제일/취소일</th>";
        $excelBody .= "<th>과세상품금액<br/>(공급가액)</th>";
        $excelBody .= "<th>과세상품금액<br/>(부가세)</th>";
        $excelBody .= "<th>면세상품금액</th>";
        $excelBody .= "<th>배송비(공급가액)</th>";
        $excelBody .= "<th>배송비(부가세)</th>";
        $excelBody .= "<th>입점사상품금액</th>";
        $excelBody .= "<th>입점사배송비</th>";
        $excelBody .= "<th>판매금액</th>";
        $excelBody .= "<th>적립금사용금액</th>";
        $excelBody .= "<th>예치금 사용금액<br/>(공급가액)</th>";
        $excelBody .= "<th>예치금 사용금액<br/>(부가세)</th>";
        $excelBody .= "<th>최종 결제금액</th>";
        $excelBody .= "<th>현금영수증 발급</th>";
		$excelBody .= "<th>입점사명</th>";
        $excelBody .= "</tr>";


        foreach($excelList as $k => $v) {

            if($postValue['treatDate'][0] <= substr($v['paymentDt'],0,10) && $postValue['treatDate'][1] >= substr($v['paymentDt'],0,10)) {
				foreach($v['scm'] as $key => $val){
					$excelBody .= "<tr>";
					
					if($v['orderChannelFl'] =='payco') {
							$excelBody .= "<td>페이코</td>";
							$excelBody .= "<td>" . $settleKind[$v['settleKind']]['name'] . "</td>";
					} else if($v['orderChannelFl']  =='naverpay') {
							$excelBody .= "<td>네이버페이</td>";
							$excelBody .= "<td>" . $settleKind[$v['settleKind']]['name'] . "</td>";
					} else  {
						//240701 kwc 전액할인,무통장 입금시 토스제거
						if($v['settleKind'] == 'gz' || $v['settleKind'] == 'gb'){
							$excelBody .= "<td></td>";
							$excelBody .= "<td>" . $settleKind[$v['settleKind']]['name'] . "</td>";
						}else if ($v['settleKind'] != 'pn' && $v['settleKind'] != 'pk') {
							$excelBody .= "<td>토스</td>";
							$excelBody .= "<td>" . $settleKind[$v['settleKind']]['name'] . "</td>";
						} else {
							$excelBody .= "<td>" . $settleKind[$v['settleKind']]['name'] . "</td>";
							$excelBody .= "<td></td>";
						}
					}


					$excelBody .= "<td class='xl24'>" . $v['orderNo'] . "</td>";
					$excelBody .= "<td>" . $v['orderNm'] . "</td>";
					$excelBody .= "<td>" . $v['orderName'] . "</td>";
					$excelBody .= "<td>결제</td>";
					$excelBody .= "<td>" . $v['paymentDt'] . "</td>";

					$goodsPrice['supply'] = 0;
					$goodsPrice['tax'] = 0;
					$goodsPriceFree = 0;
					$deliveryCharge['supply'] = 0;
					$deliveryCharge['tax'] = 0;
					//본사 
					if ($val['scmNo'] == '1') {
						//과세상품금액
						if($val['goodsPrice']['t']){
							$goodsPrice = NumberUtils::taxAll($val['goodsPrice']['t'] - $val['dcPrice']['t'], '10', 't');
						}

						//면세상품금액
						if($val['goodsPrice']['f']){
							$goodsPriceFree = $val['goodsPrice']['f'] - $val['dcPrice']['f'];
						}

						//본사 배송비
						$deliveryCharge = NumberUtils::taxAll(array_sum($val['deliveryCharge']), '10', 't');
						//예치금
						$totalUseDeposit = NumberUtils::taxAll($v['totalUseDeposit'], '10', 't');

						$totalSettlePrice = (array_sum($val['deliveryCharge']) + array_sum($val['goodsPrice'])) - (array_sum($val['dcPrice']) + $v['totalUseMileage'] + array_sum($totalUseDeposit));
					// 입점사
					} else {
						$totalSettlePrice = (array_sum($val['deliveryCharge']) + array_sum($val['goodsPrice'])) - array_sum($val['dcPrice']);
					}

					
					

					$excelBody .= "<td class='xl24'>" . number_format($goodsPrice['supply']) . "</td>"; //과세상품금액, 실결제금액 / 1.1
					$excelBody .= "<td class='xl24'>" . number_format($goodsPrice['tax']) . "</td>"; //과세상품금액(부가세)
					$excelBody .= "<td class='xl24'>" . number_format($goodsPriceFree) . "</td>"; //면세상품금액
					$excelBody .= "<td class='xl24'>" . number_format($deliveryCharge['supply']) . "</td>"; //배송비
					$excelBody .= "<td class='xl24'>" . number_format($deliveryCharge['tax']) . "</td>"; //배송비 부가세
					if($val['scmNo'] == '1'){
						$excelBody .= "<td class='xl24'>" . number_format(0) . "</td>"; //입점사상품금액
						$excelBody .= "<td class='xl24'>" . number_format(0) . "</td>"; //입점사배송비
					} else {
						$excelBody .= "<td class='xl24'>" . number_format($val['goodsPrice']['t']) . "</td>"; //입점사상품금액
						$excelBody .= "<td class='xl24'>" . number_format(array_sum($val['deliveryCharge'])) . "</td>"; //입점사배송비
					}
					$excelBody .= "<td class='xl24'>" . number_format((array_sum($val['deliveryCharge']) + array_sum($val['goodsPrice'])) - array_sum($val['dcPrice'])) . "</td>"; // 판매금액
					$excelBody .= "<td class='xl24'>" . number_format($v['totalUseMileage']) . "</td>"; //적립금 사용금액
					$excelBody .= "<td class='xl24'>" . number_format($totalUseDeposit['supply']) . "</td>"; //예치금사용금색 / 1.1
					$excelBody .= "<td class='xl24'>" . number_format($totalUseDeposit['tax']) . "</td>"; //예치금부가세
					$excelBody .= "<td class='xl24'>" . number_format($totalSettlePrice) . "</td>"; //최종결제금액
					if ($v['receiptFl'] == 'r') $excelBody .= "<td>Y</td>"; //현금영수증
					else  $excelBody .= "<td>N</td>"; //현금영수증
					if($val['scmNo'] != '1') $excelBody .= "<td>" . $val['scmNm'] . "</td>"; //입점사명
					else $excelBody .= "<td></td>";



					$excelBody .= "</tr>";
				}
            }

			//환불 행
            if($refundList[$k]) {

                $excelBody .= "<tr>";
				if($v['orderChannelFl'] =='payco') {
						$excelBody .= "<td>페이코</td>";
						$excelBody .= "<td>" . $settleKind[$v['settleKind']]['name'] . "</td>";
				} else if($v['orderChannelFl']  =='naverpay') {
						$excelBody .= "<td>네이버페이</td>";
						$excelBody .= "<td>" . $settleKind[$v['settleKind']]['name'] . "</td>";
				} else  {
					if($v['settleKind'] == 'gz' || $v['settleKind'] == 'gb'){
						$excelBody .= "<td></td>";
						$excelBody .= "<td>" . $settleKind[$v['settleKind']]['name'] . "</td>";
					}else if ($v['settleKind'] != 'pn' && $v['settleKind'] != 'pk') {
						$excelBody .= "<td>토스</td>";
						$excelBody .= "<td>" . $settleKind[$v['settleKind']]['name'] . "</td>";
					} else {
						$excelBody .= "<td>" . $settleKind[$v['settleKind']]['name'] . "</td>";
						$excelBody .= "<td></td>";
					}
				}
				$refundList[$k]['scmGoodsPrice'] = 0;
				$refundList[$k]['scmDeliveryCharge'] = 0;
				$refundList[$k]['totalRefundUseMileage'] = 0;
				
				foreach($refundList[$k]['scm'] as $key => $val){
					if($key != '1'){
						$refundList[$k]['scmGoodsPrice'] = array_sum($val['goodsPrice']);
						$refundList[$k]['scmDeliveryCharge'] += $val['deliveryCharge'];
					}
					$refundList[$k]['totalRefundUseMileage'] += $refundList[$k]['scm'][$key]['refundUseMileage'];
					$refundList[$k]['totalgoodsPrice'] += (array_sum($refundList[$k]['scm'][$key]['goodsPrice']) + $val['deliveryCharge']) - $refundList[$k]['scm'][$key]['refundUseMileage'] - array_sum($refundList[$k]['scm'][$key]['dcPrice']);
				}

				if($v['totalSettlePrice'] == $refundList[$k]['totalgoodsPrice']){
					$refundList[$k]['totalgoodsPrice'] = 0;
					$refundList[$k]['scmGoodsPrice'] = 0;
					foreach($v['scm'] as $key => $val){
						if(gd_isset($val['goodsPrice']['t'])) $refundList[$k]['scm'][$key]['goodsPrice']['t'] = $val['goodsPrice']['t'];
						if(gd_isset($val['goodsPrice']['f'])) $refundList[$k]['scm'][$key]['goodsPrice']['f'] = $val['goodsPrice']['f'];
						if($key != '1') $refundList[$k]['scmGoodsPrice'] += $val['goodsPrice']['t'];
						$refundList[$k]['totalgoodsPrice'] += ($val['goodsPrice']['t'] + $val['goodsPrice']['f'] + array_sum($val['deliveryCharge'])) - ($val['dcPrice']['t'] + $val['dcPrice']['f']);
					}
					$refundList[$k]['totalgoodsPrice'] -= $refundList[$k]['totalRefundUseMileage'] + $refundList[$k]['refundUseDeposit'];
				}
				

                //본사 과세상품금액
                $goodsPrice = $refundList[$k]['scm']['1']['goodsPrice']['t']  - $refundList[$k]['scm']['1']['dcPrice']['t'];
                $goodsPrice = NumberUtils::taxAll($goodsPrice,'10','t');
				//본사 면세상품금액
				$goodsPriceFree = $refundList[$k]['scm']['1']['goodsPrice']['f'] - $refundList[$k]['scm']['1']['dcPrice']['f'];


                //본사 배송비
                $deliveryCharge = NumberUtils::taxAll($refundList[$k]['scm']['1']['deliveryCharge'],'10','t');

				//환불 예치금
				$refundUseDeposit = NumberUtils::taxAll($refundList[$k]['refundUseDeposit'],'10','t');


                $excelBody .= "<td class='xl24'>".$v['orderNo']."</td>";
                $excelBody .= "<td>".$v['orderNm']."</td>";
                $excelBody .= "<td>".$v['orderName']."</td>";
                $excelBody .= "<td>취소</td>";
                $excelBody .= "<td>".$refundList[$k]['refundDt']."</td>";

                if($refundList[$k]['scm']['1']['goodsPrice']['t']) {
                    $excelBody .= "<td class='xl24'>-".number_format($goodsPrice['supply'])."</td>"; //과세상품금액, 실결제금액 / 1.1
                    $excelBody .= "<td class='xl24'>-".number_format($goodsPrice['tax'])."</td>"; //과세상품금액(부가세)
                } else {
                    $excelBody .= "<td>0</td>";
                    $excelBody .= "<td>0</td>";
                }

                $excelBody .= "<td class='xl24'>-".number_format($goodsPriceFree)."</td>"; //면세상품금액
                $excelBody .= "<td class='xl24'>-".number_format($deliveryCharge['supply'])."</td>"; //배송비
                $excelBody .= "<td class='xl24'>-".number_format($deliveryCharge['tax'])."</td>"; //배송비 부가세
                //if($refundList[$k]['goodsPrice']['etc']) {
                    //$goodsPrice = array_sum($refundList[$k]['goodsPrice']['etc']) - $refundList[$k]['deliveryCharge']['etc'];

                    $excelBody .= "<td class='xl24'>-".number_format($refundList[$k]['scmGoodsPrice'])."</td>"; //입점사상품금액
                    $excelBody .= "<td>-".number_format( $refundList[$k]['scmDeliveryCharge'])."</td>"; //입점사배송비
                /*} else {
                    $excelBody .= "<td>0</td>"; //입점사상품금액
                    $excelBody .= "<td>0</td>"; //입점사배송비
                }*/
                $excelBody .= "<td class='xl24'>-".number_format(array_sum($goodsPrice) + $goodsPriceFree + array_sum($deliveryCharge) + $refundList[$k]['scmGoodsPrice'] + $refundList[$k]['scmDeliveryCharge'])."</td>"; //최종결제금액
                $excelBody .= "<td>-".number_format($refundList[$k]['totalRefundUseMileage'])."</td>"; //적립금
                $excelBody .= "<td>-". number_format($refundUseDeposit['supply']) ."</td>";	//예치금
                $excelBody .= "<td>-". number_format($refundUseDeposit['tax']) ."</td>";		//예치금 부가세
                $excelBody .= "<td class='xl24'>-".number_format($refundList[$k]['totalgoodsPrice'])."</td>"; //최종결제금액
                if ($v['receiptFl'] == 'r') $excelBody .= "<td>Y</td>"; //현금영수증
				else  $excelBody .= "<td>N</td>"; //현금영수증
				$excelBody .= "<td></td>"; //입점사명


                $excelBody .= "</tr>";

            }

        }
        $excelBody .= "</table>";

        $excelFooter= "</body></html>";

        echo $excelHeader;
        echo $excelBody;
        echo $excelFooter;






    }
}
