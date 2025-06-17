<script type="text/javascript">
    <!--
    $(document).ready(function () {

        $("#frmGoods").validate({
            submitHandler: function (form) {
                form.target='ifrmProcess';
                form.submit();
            },
            // onclick: false, // <-- add this option
            rules: {
                groupNm: 'required'
            },
            messages: {
                groupNm: {
                    required: '그룹명을 입력하세요.'
                }
            }
        });


        <?php if(($data['mode'] =='register' &&  Request::get()->get('scmFl')) ||  $data['mode'] =='modify') { ?>
        $('input:radio[name=scmFl]').prop("disabled", true);
        $('button.scmBtn').attr("disabled", true);
        <?php }?>


    });

    /**
     * 카테고리 연결하기 Ajax layer
     */
    function layer_register(typeStr, mode, isDisabled) {

        var addParam = {
            "mode": mode,
        };

        if (typeStr == 'scm') {
            $('input:radio[name=scmFl]:input[value=y]').prop("checked", true);
        }

        if (!_.isUndefined(isDisabled) && isDisabled == true) {
            addParam.disabled = 'disabled';
        }

        layer_add_info(typeStr,addParam);
    }


    /**
     * 상품 선택
     *
     * @param string orderNo 주문 번호
     */
    function goods_search_popup(goodsType)
    {
        //addGoodsBundleType
        $('input[name="addGoodsBundleType"]').val(goodsType);
        var mobileFl =  $("input[name='mobileFl']:checked").val();
        // window.open('../share/popup_goods.php?mobileFl='+mobileFl, 'popup_goods_search', 'width=1255, height=790, scrollbars=no');
        window.open('../share/popup_add_bundle_goods.php?mobileFl='+mobileFl+'&addGoodsBundleType='+goodsType, 'popup_goods_search', 'width=1255, height=790, scrollbars=no');
    }


    /**
     * 상품 삭제
     */
    function delete_option() {

        var chkCnt = $('input[name="itemGoodsNo[]"]:checked').length;
        if (chkCnt == 0) {
            alert('선택된 상품이 없습니다.');
            return;
        }

        dialog_confirm('선택한 ' + chkCnt + '개 상품을 삭제하시겠습니까?', function (result) {
            if (result) {
                $('input[name="itemGoodsNo[]"]:checked').each(function () {
                    field_remove('tbl_add_goods_' + $(this).val());
                });

                var cnt = $('input[name="itemGoodsNo[]"]').length;

                $('input[name="itemGoodsNo[]"]').each(function () {
                    $(".addGoodsNumber_"+$(this).val()).html(cnt);
                    cnt--;
                });
            }
        });

    }

    /**
     * 결합실패시 항목 업데이트
     */
    function update_bundle_type(val){
        var chkCnt = $('input[name="itemGoodsNo[]"]:checked').length;
        if (chkCnt == 0) {
            alert('선택된 상품이 없습니다.');
            return;
        }

        dialog_confirm('선택한 ' + chkCnt + '개 상품을 수정하시겠습니까?', function (result) {
            if (result) {
                $('#frmGoods input[name=\'mode\']').val('update_bundle_type');
                $('#frmGoods input[name=\'bundleType\']').val(val);
                $('#frmGoods').attr('method', 'post');
                $('#frmGoods').attr('action', './discount_bundle_group_ps.php');
                $('#frmGoods').submit();
            }
        });
    }



    function setAddGoods(frmData) {

        console.log(frmData);

        var addHtml = "";
        var cnt = frmData.info.length;
        var mode = frmData.mode;

        $.each(frmData.info, function (key, val) {

            // 상품 재고
            if (val.stockFl == 'n') {
                totalStock    = '∞';
            } else {
                totalStock    = val.totalStock;
            }

            if(val.soldOutFl =='y' || totalStock =='0') stockText = "품절";
            else stockText="정상";



            if(val.sortFix == true) {
                sortFix = "checked = 'checked'";
                tableCss = "style='background:#d3d3d3' class='add_goods_fix'";
            }
            else {
                sortFix = '';
                tableCss = "class='add_goods_free'";
            }

            var bundleType = $('input[name="addGoodsBundleType"]').val();


            addHtml += '<tr id="tbl_add_goods_'+val.goodsNo+'" '+tableCss+'>';
            addHtml += '<td class="center">';

            addHtml += '<input type="hidden" name="itemGoodsNm[]" value="'+val.goodsNm+'" />';
            addHtml += '<input type="hidden" name="itemGoodsPrice[]" value="'+val.goodsPrice+'" />';
            addHtml += '<input type="hidden" name="itemScmNm[]" value="'+val.scmNm+'" />';
            addHtml += '<input type="hidden" name="itemTotalStock[]" value="'+val.totalStock+'" />';
            addHtml += '<input type="hidden" name="itemBrandNm[]" value="'+val.brandNm+'" />';
            addHtml += '<input type="hidden" name="itemMakerNm[]" value="'+val.makerNm+'" />';
            addHtml += '<input type="hidden" name="itemOptionNm[]" value="'+val.optionNm+'" />';
            addHtml += '<input type="hidden" name="itemImage[]" value="'+val.image+'" />';
            addHtml += '<input type="hidden" name="itemSoldOutFl[]" value="'+val.soldOutFl+'" />';
            addHtml += '<input type="hidden" name="itemStockFl[]" value="'+val.stockFl+'" />';
            addHtml += '<input type="hidden" name="itemBundleType[]" value="'+bundleType+'" />';
            addHtml += '<input type="checkbox" name="itemGoodsNo[]" id="layer_goods_'+val.goodsNo+'"  value="'+val.goodsNo+'"/></td>';
            addHtml += '<td class="center number addGoodsNumber_'+val.goodsNo+'">'+(cnt)+'</td>';
            addHtml += '<td class="center">'+decodeURIComponent(val.image)+'</td>';
            addHtml += '<td><a href="../goods/add_goods_register.php?addGoodsNo='+val.goodsNo+'" target="_blank">'+val.goodsNm+'</a><input type="hidden" name="addGoodsNoData[]" value="'+val.goodsNo+'" /><input type="checkbox" name="sortFix[]" class="layer_sort_fix_'+val.goodsNo+'"  value="'+val.goodsNo+'" '+sortFix+'  style="display:none"></td>';
            
            addHtml += '<td class="center">'+val.goodsPrice+'</td>';
            addHtml += '<td class="center">'+val.scmNm+'</td>';
            addHtml += '<td class="center">'+totalStock+'</td>';
            addHtml += '<td class="center">'+stockText+'</td>';
            // addHtml += '<td class="center"><select name="bundleType"><option>선택</option><option value="mian">판매함</option><option value="discount">판매안함</option></select></td>';
            addHtml += '</tr>';
            cnt--;
        });

        var tblId = "tbl_add_goods_set_"+$('input[name="addGoodsBundleType"]').val();

        // if(mode =='register_ajax' && $('input[name="itemGoodsNo[]"]').length > 0)  $("#tbl_add_goods_set tbody").append(addHtml);
        // else $("#tbl_add_goods_set tbody").html(addHtml);

        if(mode =='register_ajax' && $('input[name="itemGoodsNo[]"]').length > 0)  $("#"+tblId+" tbody").append(addHtml);
        else $("#"+tblId+" tbody").html(addHtml);

        var cnt = $('input[name="itemGoodsNo[]"]').length;

        $('input[name="itemGoodsNo[]"]').each(function () {
            $(".addGoodsNumber_"+$(this).val()).html(cnt);
            cnt--;
        });


    }



    //-->
</script>
<form id="frmGoods" name="frmGoods" action="./discount_bundle_group_ps.php" method="post" enctype="multipart/form-data" target="ifrmProcess" >
    <input type="hidden" name="mode" value="group_<?=$data['mode']; ?>"/>
    <input type="hidden" name="bundleType" value=""/> 
    <input type="hidden" name="groupCd" value="<?=gd_isset($data['groupCd']); ?>"/>      
    <input type="hidden" name="addGoodsBundleType" value=""/>              

    <?php if ($data['mode'] == 'modify') { ?><input type="hidden" name="sno" value="<?=gd_isset($data['sno']); ?>" /><?php } ?>

    <div class="page-header js-affix">
        <h3><?=end($naviMenu->location); ?></h3>
        <div class="btn-group">
            <input type="button" value="목록" class="btn btn-white btn-icon-list" onclick="goList('./discount_bundle_group_list.php');" />
            <input type="submit" value="저장" class="btn btn-red" />
        </div>
    </div>

    <div class="table-title gd-help-manual">
        기본정보
    </div>
        <table class="table table-cols">
            <colgroup>
                <col style="width: 250px;"/>
                <col/>
            </colgroup>
            <?php if(gd_use_provider()) { ?>
            <?php if(gd_is_provider()) { ?>
                <input type="hidden" name="scmNo" value="<?=$data['scmNo']?>">
            <?php }  else { ?>
            <tr>
                <th class="input_title r_space ">공급사 구분</th>
                <td>
                    <label class="radio-inline"><input type="radio" name="scmFl"
                                  value="n" <?=gd_isset($checked['scmFl']['n']); ?>    onclick="$('#scmLayer').html('')";/>본사</label>
                    <label class="radio-inline"><input type="radio" name="scmFl" value="y" <?=gd_isset($checked['scmFl']['y']); ?>
                                  onclick="layer_register('scm','radio',true)"/>공급사</label>
                    <label > <button type="button" class="btn btn-sm btn-gray scmBtn" onclick="layer_register('scm','radio',true)">공급사 선택</button></label>
                    <div id="scmLayer" class="selected-btn-group <?= $data['scmNo'] != DEFAULT_CODE_SCMNO && $data['scmNoNm'] ? 'active' : ''?>">
                        <h5>선택된 공급사 : </h5>
                        <?php if ($data['scmNo']) { ?>
                            <span id="info_scm_<?= $data['scmNo'] ?>" class="btn-group btn-group-xs">
							<input type="hidden" name="scmNo" value="<?= $data['scmNo'] ?>"/>
							<input type="hidden" name="scmNoNm" value="<?= $data['scmNoNm'] ?>"/>
                                <?php if($data['scmNo'] != DEFAULT_CODE_SCMNO) { ?>
                                    <span class="btn"><?= $data['scmNoNm'] ?></span>
                                        <?php if($data['mode'] =='register' &&  !Request::get()->get('scmFl')) { ?>
                                        <button type="button" class="btn btn-danger" data-toggle="delete" data-target="#info_scm_<?= $data['scmNo'] ?>">삭제</button> <?php } ?>
                                <?php }?>
					        </span>
                        <?php } ?>
                    </div>

                </td>
            </tr>
            <?php } ?>
            <?php } ?>
            <tr>
                <th class="input_title r_space ">그룹코드</th>
                <td>
                    <?php if ($data['groupCd']) { ?><?= $data['groupCd'] ?> <label title=""><input type="hidden"
                                                                                                   name="groupCd"
                                                                                                   value="<?=gd_isset($data['groupCd']); ?>"/></label>
                    <?php } else {
                        echo '추가 상품 그룹 등록 저장 시 자동 생성됩니다.';
                    } ?>
                </td>
            </tr>
            <tr>
                <th class="input_title r_space require">그룹명</th>
                <td class="input_area" >
                    <label title=""><input type="text" name="groupNm" value="<?=gd_isset($data['groupNm']); ?>"
                                           class="form-control width-3xl js-maxlength" maxlength="250"/></label>
                </td>
            </tr>
            <tr>
                <th>그룹 설명</th>
                <td class="input_area" >
                    <label title=""><input type="text" name="groupDescription" value="<?=gd_isset($data['groupDescription']); ?>"
                                           class="form-control width-3xl js-maxlength" maxlength="250"/></label>
                </td>
            </tr>
            <!-- <tr>
                <th>결합실패 시</th>
                <td class="input_area" >
                    <label class="radio-inline"><input type="radio" name="allowNoBundleSale" value="y" <?=gd_isset($checked['allowNoBundleSale']['y']); ?>/>판매함</label>
                    <label class="radio-inline"><input type="radio" name="allowNoBundleSale" value="n" <?=gd_isset($checked['allowNoBundleSale']['n']); ?>/>판매안함</label>
                </td>
            </tr> -->
            <tr>
                <th>메인 상품 바로구매 시 팝업 노출 여부</th>
                <td class="input_area" >
                    <label class="radio-inline"><input type="radio" name="showNoBundlePopup" value="y" <?=gd_isset($checked['showNoBundlePopup']['y']); ?>/>사용</label>
                    <label class="radio-inline"><input type="radio" name="showNoBundlePopup" value="n" <?=gd_isset($checked['showNoBundlePopup']['n']); ?>/>미사용</label>
                </td>
            </tr>
            <tr>
                <th>메인 상품 바로구매 시 배너코드</th>
                <td class="input_area" >
                    <!-- <div class="radio form-inline reg-couponimg pdt10">
                        <input type="file" name="mainBuyImage" class="form-control"/>
                        <?php if($data['mainBuyImage']){ ?>
                            <img width="100" height="100" src="/data/upload/bundle/<?=$data['mainBuyImage']?>"> 
                        <?php } ?>
                    </div> -->
                    <label title=""><input type="text" name="mainBuyBanner" value="<?=gd_isset($data['mainBuyBanner']); ?>"
                                           class="form-control width-3xl js-maxlength" maxlength="250"/></label>
                </td>
            </tr>
            <tr>
                <th>장바구니 담기 팝업 노출여부</th>
                <td class="input_area" >
                    <label class="radio-inline"><input type="radio" name="preCartBundlePopup" value="y" <?=gd_isset($checked['preCartBundlePopup']['y']); ?>/>사용</label>
                    <label class="radio-inline"><input type="radio" name="preCartBundlePopup" value="n" <?=gd_isset($checked['preCartBundlePopup']['n']); ?>/>미사용</label>
                </td>
            </tr>
            <tr>
                <th>메인 상품 장바구니 담기시 배너코드</th>
                <td class="input_area" >
                    <!-- <div class="radio form-inline reg-couponimg pdt10">
                        <input type="file" name="mainCartImage" class="form-control"/>
                        <?php if($data['mainCartImage']){ ?>
                            <img width="100" height="100" src="/data/upload/bundle/<?=$data['mainCartImage']?>"> 
                        <?php } ?>
                    </div> -->
                    <label title=""><input type="text" name="mainCartBanner" value="<?=gd_isset($data['mainCartBanner']); ?>"
                                           class="form-control width-3xl js-maxlength" maxlength="250"/></label>
                </td>
            </tr>
            <tr>
                <th>혜택 상품 장바구니 담기시 배너코드</th>
                <td class="input_area" >
                    <!-- <div class="radio form-inline reg-couponimg pdt10">
                        <input type="file" name="discountCartImage" class="form-control"/>
                        <?php if($data['discountCartImage']){ ?>
                            <img width="100" height="100" src="/data/upload/bundle/<?=$data['discountCartImage']?>"> 
                        <?php } ?>
                    </div> -->
                    <label title=""><input type="text" name="discountCartBanner" value="<?=gd_isset($data['discountCartBanner']); ?>"
                                           class="form-control width-3xl js-maxlength" maxlength="250"/></label>
                </td>
            </tr>
            <!-- <tr>
                <th>결합 실패 시 팝업배너 치환코드</th>
                <td class="input_area" >
                    <label title=""><input type="text" name="noBundlePopupCode" value="<?=gd_isset($data['noBundlePopupCode']); ?>"
                                           class="form-control width-3xl js-maxlength" maxlength="250"/></label>
                </td>
            </tr> -->
            <tr>
                <th>장바구니 사용 여부</th>
                <td class="input_area" >
                    <label class="radio-inline"><input type="radio" name="showCartBtnForBundle" value="y" <?=gd_isset($checked['showCartBtnForBundle']['y']); ?>/>사용</label>
                    <label class="radio-inline"><input type="radio" name="showCartBtnForBundle" value="n" <?=gd_isset($checked['showCartBtnForBundle']['n']); ?>/>미사용</label>
                </td>
            </tr>
            
            <!-- <tr>
                <th>장바구니 담기 시 팝업배너 치환코드</th>
                <td class="input_area" >
                    <label title=""><input type="text" name="cartBundlePopupCode" value="<?=gd_isset($data['cartBundlePopupCode']); ?>"
                                           class="form-control width-3xl js-maxlength" maxlength="250"/></label>
                </td>
            </tr> -->
        </table>

    <div class="bundle-goods-wrapper" style="border:1px solid #ddd; padding:10px;">
        <div class="table-title gd-help-manual">
            메인상품(판매함)
        </div>
        <div class="bundle-goods-table-container" style="max-height:200px; overflow-y:auto;">
            <table cellpadding="0" cellpadding="0" width="100%" id="tbl_add_goods_set_main" class="table table-rows">
                <thead>
                <tr id="goodsRegisteredTrArea">
                    <th class="width5p"><input type="checkbox" id="allCheck" value="y" class="js-checkall" data-target-name="itemGoodsNo"/></th>
                    <th class="width5p">번호</th>
                    <th class="width5p">이미지</th>
                    <th >상품명</th>
                    <th class="width10p">판매가</th>
                    <th class="width10p">공급사</th>
                    <th class="width10p">재고</th>
                    <th class="width10p">품절상태</th>
                    <!-- <th class="width15p">결합할인 상품구분</th> -->
                </tr>
                </thead>
                <tbody>
                    <?php
                    if (count(gd_isset($discountBundleMainGoodsList))) {
                        foreach ($discountBundleMainGoodsList as $key => $val) {

                                if($val['stockUseFl'] =='0') {
                                    $stockUseFl = "n";
                                } else {
                                    $stockUseFl = "y";
                                }

                                list($totalStock,$stockText) = gd_is_goods_state($val['stockFl'],$val['totalStock'],$val['soldOutFl']);

                                if($val['bundleType'] == 'main'){
                                    $bundleType = '판매함';
                                }elseif($val['bundleType'] == 'discount'){
                                    $bundleType = '판매안함';
                                }

                            ?>

                            <tr id="tbl_add_goods_<?=$val['goodsNo'];?>" class="add_goods_free">
                                <td class="center">
                                    <input type="hidden" name="bundleGroupGoodsSno[]" value="<?=gd_isset($val['dbgg_sno'])?>" />
                                    <input type="hidden" name="itemGoodsNm[]" value="<?=strip_tags($val['goodsNm'])?>" />
                                    <input type="hidden" name="itemGoodsPrice[]" value="<?=gd_currency_display($val['goodsPrice'])?>" />
                                    <input type="hidden" name="itemScmNm[]" value="<?=$val['scmNm']?>" />
                                    <input type="hidden" name="itemTotalStock[]" value="<?=$totalStock?>" />
                                    <input type="hidden" name="itemSoldOutFl[]" value="<?=gd_isset($val['soldOutFl'])?>" />
                                    <input type="hidden" name="itemStockFl[]" value="<?=gd_isset($val['stockUseFl'])?>" />
                                    <input type="hidden" name="itemImage[]" value="<?=rawurlencode(gd_html_goods_image($val['goodsNo'], $val['imageName'], $val['imagePath'], $val['imageStorage'], 40, $val['goodsNm'], '_blank')); ?>" />
                                    <input type="hidden" name="itemBundleType[]" value="main" />                                    
                                    <input type="checkbox" name="itemGoodsNo[]" id="layer_goods_<?=$val['goodsNo'];?>"  value="<?=$val['goodsNo']; ?>"/></td>
                                <td class="center number addGoodsNumber_<?=$val['goodsNo'];?>"><?=$key+1?></td>
                                <td>
                                    <?=gd_html_goods_image($val['goodsNo'], $val['imageName'], $val['imagePath'], $val['imageStorage'], 40, $val['goodsNm'], '_blank'); ?>
                                </td>
                                <td>
                                    <a href="#" target="_blank" onclick="goods_register_popup('<?=$val['goodsNo'];?>' );" ><?=$val['goodsNm'];?> </a>
                                    <input type="hidden" name="addGoodsNoData[]" value="<?=$val['goodsNo']?>" /> <input type="checkbox" name="sortFix[]" class="layer_sort_fix_<?=$val['goodsNo'];?>"  value="<?=$val['goodsNo']; ?>" <?php  if($data['fixGoodsNo'] && in_array($val['goodsNo'],$data['fixGoodsNo'])) { echo "checked='true'"; }  ?> style="display:none">
                                </td>
                                
                                <td class="center"><?=gd_currency_display($val['goodsPrice']); ?></td>
                                <td class="center"><?=$val['scmNm']; ?></td>
                                <td class="center"><?=$totalStock; ?></td>
                                <td class="center"><?=$stockText?></td>
                                <!-- <td class="center">
                                    <select name="bundleType[]">
                                        <option value="">선택</option>
                                        <option value="main" <?php if($val['bundleType'] == 'main'){echo "selected";} ?> >메인상품(판매함)</option>
                                        <option value="discount" <?php if($val['bundleType'] == 'discount'){echo "selected";} ?> >혜택상품(판매안함)</option>
                                    </select>
                                </td> -->
                            </tr>
                            <?php
                        }
                    } else {
                        ?>
                        <tr id="tbl_add_goods_tr_none"><td class="no-data" colspan="9">선택된 추가 상품이 없습니다.</td></tr>
                        <?php
                    }
                    ?>
                </tbody>
            </table>
        </div>
    
        <div class="table-action">
            <div class="pull-left">
                <button class="checkDelete btn btn-white" type="button" onclick="delete_option()">선택 삭제</button>
                <!-- <button class="checkDelete btn btn-white" type="button" onclick="update_bundle_type('main')">판매함으로 설정</button>
                <button class="checkDelete btn btn-white" type="button" onclick="update_bundle_type('discount')">판매안함으로 설정</button> -->
            </div>

            <div class="pull-right">
            <button class="checkRegister btn btn-white" type="button"  onclick="goods_search_popup('main')">상품 불러오기</button>
            </div>
        </div>
    </div>    
    <p>

    <div class="bundle-goods-wrapper" style="border:1px solid #ddd; padding:10px;">
        <div class="table-title gd-help-manual">
            혜택상품(판매안함)
        </div>
        <div class="bundle-goods-table-container" style="max-height:200px; overflow-y:auto;">
            <table cellpadding="0" cellpadding="0" width="100%" id="tbl_add_goods_set_discount" class="table table-rows">
                <thead>
                <tr id="goodsRegisteredTrArea">
                    <th class="width5p"><input type="checkbox" id="allCheck" value="y" class="js-checkall" data-target-name="itemGoodsNo"/></th>
                    <th class="width5p">번호</th>
                    <th class="width5p">이미지</th>
                    <th >상품명</th>
                    <th class="width10p">판매가</th>
                    <th class="width10p">공급사</th>
                    <th class="width10p">재고</th>
                    <th class="width10p">품절상태</th>
                    <!-- <th class="width15p">결합할인 상품구분</th> -->
                </tr>
                </thead>
                <tbody>
                    <?php
                    if (count(gd_isset($discountBundleDiscountGoodsList))) {
                        foreach ($discountBundleDiscountGoodsList as $key => $val) {

                                if($val['stockUseFl'] =='0') {
                                    $stockUseFl = "n";
                                } else {
                                    $stockUseFl = "y";
                                }

                                list($totalStock,$stockText) = gd_is_goods_state($val['stockFl'],$val['totalStock'],$val['soldOutFl']);

                                if($val['bundleType'] == 'main'){
                                    $bundleType = '판매함';
                                }elseif($val['bundleType'] == 'discount'){
                                    $bundleType = '판매안함';
                                }

                            ?>

                            <tr id="tbl_add_goods_<?=$val['goodsNo'];?>" class="add_goods_free">
                                <td class="center">
                                    <input type="hidden" name="bundleGroupGoodsSno[]" value="<?=gd_isset($val['dbgg_sno'])?>" />
                                    <input type="hidden" name="itemGoodsNm[]" value="<?=strip_tags($val['goodsNm'])?>" />
                                    <input type="hidden" name="itemGoodsPrice[]" value="<?=gd_currency_display($val['goodsPrice'])?>" />
                                    <input type="hidden" name="itemScmNm[]" value="<?=$val['scmNm']?>" />
                                    <input type="hidden" name="itemTotalStock[]" value="<?=$totalStock?>" />
                                    <input type="hidden" name="itemSoldOutFl[]" value="<?=gd_isset($val['soldOutFl'])?>" />
                                    <input type="hidden" name="itemStockFl[]" value="<?=gd_isset($val['stockUseFl'])?>" />
                                    <input type="hidden" name="itemImage[]" value="<?=rawurlencode(gd_html_goods_image($val['goodsNo'], $val['imageName'], $val['imagePath'], $val['imageStorage'], 40, $val['goodsNm'], '_blank')); ?>" />
                                    <input type="hidden" name="itemBundleType[]" value="discount" />
                                    <input type="checkbox" name="itemGoodsNo[]" id="layer_goods_<?=$val['goodsNo'];?>"  value="<?=$val['goodsNo']; ?>"/></td>
                                <td class="center number addGoodsNumber_<?=$val['goodsNo'];?>"><?=$key+1?></td>
                                <td>
                                    <?=gd_html_goods_image($val['goodsNo'], $val['imageName'], $val['imagePath'], $val['imageStorage'], 40, $val['goodsNm'], '_blank'); ?>
                                </td>
                                <td>
                                    <a href="#" target="_blank" onclick="goods_register_popup('<?=$val['goodsNo'];?>' );" ><?=$val['goodsNm'];?> </a>
                                    <input type="hidden" name="addGoodsNoData[]" value="<?=$val['goodsNo']?>" /> <input type="checkbox" name="sortFix[]" class="layer_sort_fix_<?=$val['goodsNo'];?>"  value="<?=$val['goodsNo']; ?>" <?php  if($data['fixGoodsNo'] && in_array($val['goodsNo'],$data['fixGoodsNo'])) { echo "checked='true'"; }  ?> style="display:none">
                                </td>
                                
                                <td class="center"><?=gd_currency_display($val['goodsPrice']); ?></td>
                                <td class="center"><?=$val['scmNm']; ?></td>
                                <td class="center"><?=$totalStock; ?></td>
                                <td class="center"><?=$stockText?></td>
                                <!-- <td class="center">
                                    <select name="bundleType[]">
                                        <option value="">선택</option>
                                        <option value="main" <?php if($val['bundleType'] == 'main'){echo "selected";} ?> >메인상품(판매함)</option>
                                        <option value="discount" <?php if($val['bundleType'] == 'discount'){echo "selected";} ?> >혜택상품(판매안함)</option>
                                    </select>
                                </td> -->
                            </tr>
                            <?php
                        }
                    } else {
                        ?>
                        <tr id="tbl_add_goods_tr_none"><td class="no-data" colspan="9">선택된 추가 상품이 없습니다.</td></tr>
                        <?php
                    }
                    ?>
                </tbody>
            </table>
        </div>
    
        <div class="table-action">
            <div class="pull-left">
                <button class="checkDelete btn btn-white" type="button" onclick="delete_option()">선택 삭제</button>
                <!-- <button class="checkDelete btn btn-white" type="button" onclick="update_bundle_type('main')">판매함으로 설정</button>
                <button class="checkDelete btn btn-white" type="button" onclick="update_bundle_type('discount')">판매안함으로 설정</button> -->
            </div>

            <div class="pull-right">
            <button class="checkRegister btn btn-white" type="button"  onclick="goods_search_popup('discount')">상품 불러오기</button>
            </div>
        </div>
    </div>  

</form>
