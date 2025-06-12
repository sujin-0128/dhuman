<?php
	include "/www/system/src/Asset/Admin/goods/goods_register_option_stock.php";
?>
<script>
    $(document).ready(function(){
        htmlMove('.section1',"#depth-toggle-layer-setPrice");
		htmlUpdate('.section2',"#depth-toggle-layer-addInfo");

		$("input:radio[name=dpxSelectFl]").click(function(){

			//var selectFl = $("input[name=dpxSelectFl]:checked").val();
			//console.log(selectFl);
			//$('#dpxPromotionFl').val(selectFl);
		});
    });

    function htmlMove(targetHtmlAddWrap, targetPasteID){
        html = $(targetHtmlAddWrap).html();
        $(targetHtmlAddWrap).remove();
        $(targetPasteID).after(html);
    }

	function htmlUpdate(targetHtmlAddWrap, targetPasteID){
		html = $(targetHtmlAddWrap).html();
        $(targetHtmlAddWrap).remove();
        $(targetPasteID).html(html);
	}

</script>
<section class="section1">
<!-- dpx.farmer 앱전용 -->
	<div class="table-title">
		<p class="text-danger">앱전용 상품 설정</p>
    </div>


    <div style="margin-bottom:20px;">
		<table class="table table-cols" style="margin-bottom:0;">
			<colgroup>
				<col class="width-lg">
				<col>
			</colgroup>
			<tbody>

			<tr>
				<th>앱전용 상품 여부</th>
				
				<td align="left" bgcolor=""  class="form-inline">

					<label class="radio-inline">
						<input type="radio" name="dpxAppFl"  value="y" <?php if($data['dpxAppFl'] == 'y') echo "checked"; ?> />사용함
					</label>


					<label class="radio-inline">
						<input type="radio" name="dpxAppFl" value="n" <?php if($data['dpxAppFl'] != 'y') echo "checked"; ?> />사용안함
					</label>
			

				</td>
			</tr>
			</tbody>
		</table>	
    </div>
<!-- dpx.farmer 앱전용 -->


	<div class="table-title">
		<p class="text-danger">기획전 노출 설정</p>
    </div>


    <div style="margin-bottom:20px;">
		<table class="table table-cols" style="margin-bottom:0;">
			<colgroup>
				<col class="width-lg">
				<col>
			</colgroup>
			<tbody>

			<tr>
				<th>기획전 노출 여부</th>
				
				<td align="left" bgcolor=""  class="form-inline">

					<label class="radio-inline">
						<input type="radio" name="dpxDisplayFl"  value="y" <?php if($data['dpxDisplayFl'] == 'y') echo "checked"; ?> />사용함
					</label>


					<label class="radio-inline">
						<input type="radio" name="dpxDisplayFl" value="n" <?php if($data['dpxDisplayFl'] != 'y') echo "checked"; ?> />사용안함
					</label>
			

				</td>
			</tr>
			</tbody>
		</table>	
    </div>



	<?php
	// 텍스트 옵션 강제로 사용함 체크
	if( $arrData['dpxSelectFl'] == 'y' ) $arrData['optionTextFl'] = 'y';
?>
	<div class="table-title">
		<p class="text-danger">이벤트 상품 설정</p>
    </div>


    <div style="margin-bottom:20px;">
		<table class="table table-cols" style="margin-bottom:0;">
			<colgroup>
				<col class="width-lg">
				<col>
			</colgroup>
			<tbody>

			<tr>
				<th>이벤트 상품 여부</th>
				
				<td align="left" bgcolor=""  class="form-inline">

					<label class="radio-inline" title="프로모션상품일경우 선택하세요">
						<input type="radio" name="dpxPromotionFl"  value="y" <?php if($data['dpxPromotionFl'] == 'y') echo "checked"; ?> />이벤트상품
					</label>


					<label class="radio-inline" title="일반품일경우 선택하세요">
						<input type="radio" name="dpxPromotionFl" value="n" <?php if($data['dpxPromotionFl'] != 'y') echo "checked"; ?> />일반상품
					</label>

					<span class="notice-info">이벤트 상품일경우 아이디당 1회만 구매 가능합니다. </span>

				</td>
			</tr>
			</tbody>
		</table>	
    </div>

    <div class="table-title">
		<p class="text-danger">골라담기 설정</p>
    </div>


    <div style="margin-bottom:20px;">
		<table class="table table-cols" style="margin-bottom:0;">
			<colgroup>
				<col class="width-lg">
				<col>
			</colgroup>
			<tbody>

			<tr>
				<th>골라담기 사용 여부</th>
				
				<td align="left" bgcolor=""  class="form-inline">

					<!-- <input type="hidden" name="dpxPromotionFl" id="dpxPromotionFl" value="" /> -->

					<label class="radio-inline" title="사용 선택하세요">
						<input type="radio" name="dpxSelectFl"  value="y" <?php if($data['dpxSelectFl'] == 'y') echo "checked"; ?> />사용
					</label>


					<label class="radio-inline" title="사용안함 선택하세요">
						<input type="radio" name="dpxSelectFl" value="n" <?php if($data['dpxSelectFl'] != 'y') echo "checked"; ?> />사용안함
					</label>
				</td>
			</tr>
			<tr>
				<th>골라담기 갯수</th>
				<td align="left" bgcolor=""  class="form-inline">
					<input type="text" name="dpxSelectCnt" class="form-control width-sm" value="<?=$data['dpxSelectCnt']?>">
				</td>
			</tr>
			<tr>
				<th>1회 제한 사용 여부</th>
				
				<td align="left" bgcolor=""  class="form-inline">

					<!-- <input type="hidden" name="dpxPromotionFl" id="dpxPromotionFl" value="" /> -->

					<label class="radio-inline" title="사용 선택하세요">
						<input type="radio" name="selectFl"  value="y" <?php if($data['selectFl'] == 'y') echo "checked"; ?> />사용
					</label>


					<label class="radio-inline" title="사용안함 선택하세요">
						<input type="radio" name="selectFl" value="n" <?php if($data['selectFl'] != 'y') echo "checked"; ?> />사용안함
					</label>
				</td>
			</tr>
<!--
			<tr>
				<th>골라담기 상품</th>
				<td align="left" bgcolor=""  class="form-inline">
					  <input type="button" class="checkRegister btn btn-sm btn-black" type="button" onclick="dpx_goods_search_popup()" value="상품 선택"/>
					<table cellpadding="0" cellpadding="0" width="100%" id="tbl_dpx_goods_set" class="table table-rows table-fixed">
                            <thead>
                            <tr id="goodsRegisteredTrArea">
                                <th class="width5p">번호</th>
                                <th class="width10p">이미지</th>
                                <th class="width10p">상품명</th>
                                <th class="width10p">판매가</th>
                            </tr>
                            </thead>
                                <tbody id="dpxGoodsList">
<?php 
									foreach($selectGoodsDatas['goodsNo'] as $k => $v){					
?>
										<tr id="tbl_dpx_goods_<?=$v?>">
											<td class="center number addGoodsNumber_<?=$v?>"><?=($v+1)?></td>

											<td class="center">
											<input type="hidden" name="selectGoodsNm[]" value="<?=$selectGoodsDatas['goodsNm'][$k]?>" />
											<input type="hidden" name="selectGoodsPrice[]" value="<?=$selectGoodsDatas['goodsPrice'][$k]?>" />
											<input type="hidden" name="selectImage[]" value="<?=$selectGoodsDatas['goodsImage'][$k]?>" />
											<?=urldecode($selectGoodsDatas['goodsImage'][$k])?></td>
											<td><?=$selectGoodsDatas['goodsNm'][$k]?><input type="hidden" name="selectGoodsData[]" value="<?=$v?>" /></td>
											<td class="center"><?=$selectGoodsDatas['goodsPrice'][$k]?></td>
										</tr>
<?php
									}									
?>
-->
                                </tbody>

                        </table>
				<!--
					<div class="buttons">   
						<?php 
							if($dpxSelectGoods){ 
							foreach($dpxSelectGoods as $k =>$v){
								if($k>0){
						?>
								<input type="text" name="dpxSelectGoods[]" class="form-control width-sm" value="<?=$v?>"> <input type="button" class="btnRemove form-control " value="삭제"><br>
						<?php 
								}else{
						?>
							<input type="text" name="dpxSelectGoods[]" class="form-control width-sm" value="<?=$v?>"> <input type="button" class="btnAdd form-control " value="추가"><br>
						<?php 
								}
							}
						?>
							

						<?php } else { ?>
						<input type="text" name="dpxSelectGoods[]" class="form-control width-sm"> <input type="button" class="btnAdd form-control " value="추가"><br>
						<?php } ?>
					</div>
					<span class="notice-info">상품코드를 추가해 주세요.</span>
				-->
				</td>

			</tr>
			</tbody>
		</table>	
    </div>
	<script>
		/*
		$(document).ready(function(){
			
		});
		*/
	</script>
	<!--
	<script>        
        $(document).ready (function () {                

			$('body').on('click','.btnAdd', function(){                                     
                $('.buttons').append (                        
                    '<input type="text" name="dpxSelectGoods[]" class="form-control width-sm"> <input type="button" class="btnRemove form-control " value="삭제"><br>'                    
                ); // end append       
				
            }); // end click    
			$('body').on('click','.btnRemove', function(){     
				$(this).prev().remove (); // remove the textbox
				$(this).next ().remove (); // remove the <br>
				$(this).remove (); // remove the button
			});
        }); // end ready        
	</script>
-->	

	<!-- designpix.hj end -->

    <div class="table-title gd-help-manual text-red">
        정기결제 상품 설정
    </div>
    <div id="depth-toggle-layer-goodsDisplay">
        <table class="table table-cols">
            <colgroup>
                <col class="width-lg">
                <col>
            </colgroup>
            <tr>
                <th>정기결제 가능 여부</th>
                <td>
                    <label class="radio-inline" title="정기결제 가능 상품을 선택하세요!">
                        <input type="radio" name="subscribeGoodsFl" value="y" <?=($data['subscribeGoodsFl']=="y")?"checked=checked":"" ?> />정기결제 가능
                    </label>
                    <label class="radio-inline" title="정기결제 불가상품은 일반상품을 선택하세요!">
                        <input type="radio" name="subscribeGoodsFl" value="n" <?=($data['subscribeGoodsFl']!="y")?"checked=checked":"" ?> />정기결제 불가
                    </label>
                    <span class="notice-info">정기결제 불가일경우 정기결제 선택메뉴 및 정기결제로 구매가 불가합니다. </span>
                </td>
			</tr>
		</table>
	</div>


	<div class="table-title">
		<p class="text-danger">선물하기 상품 설정</p>
    </div>


    <div style="margin-bottom:20px;">
		<table class="table table-cols" style="margin-bottom:0;">
			<colgroup>
				<col class="width-lg">
				<col>
			</colgroup>
			<tbody>

			<tr>
				<th>선물하기 사용 여부</th>
				
				<td align="left" bgcolor=""  class="form-inline">

					<label class="radio-inline" title="선물가능상품일경우 선택하세요">
						<input type="radio" name="useGiftFl"  value="y" <?php if($data['useGiftFl'] == 'y') echo "checked"; ?> />사용함
					</label>


					<label class="radio-inline" title="선물하기상품이 아닌경우 선택하세요">
						<input type="radio" name="useGiftFl" value="n" <?php if($data['useGiftFl'] != 'y') echo "checked"; ?> />사용안함
					</label>

				</td>
			</tr>
			</tbody>
		</table>	
    </div>

	
	<div class="table-title">
		<p class="text-danger">구매정보 활용 동의 설정</p>
	</div>

	<div style="margin-bottom: 20px;">
		<table class="table table-cols" style="margin-bottom:0;">
			<colgroup>
				<col class="width-lg"></col>
			</colgroup>
			<tbody>

			<tr>
				<th>구매정보 활용 동의 여부</th>
				<td align="left" bgcolor="" class="form-inline">

					<label class="radio-inline" title="사용 선택하세요">
						<input type="radio" name="dpxAgreeInfoFl" value="y" <?php if($data['dpxAgreeInfoFl'] == 'y') echo "checked"; ?> />사용함
					</label>
					
					<label class="radio-inline" title="사용안함 선택하세요">
						<input type="radio" name="dpxAgreeInfoFl" value="n" <?php if($data['dpxAgreeInfoFl'] != 'y') echo "checked"; ?> />사용안함
					</label>

				</td>
			</tr>
		</table>
	</div>




</section>

<section class="section2">
	<table class="table table-cols">
		<colgroup>
			<col class="width-lg"/>
			<col class="width-2xl"/>
			<col class="width-md"/>
			<col/>
		</colgroup>
		<?php if(gd_is_plus_shop(PLUSSHOP_CODE_PURCHASE) === true && gd_is_provider() === false) { ?>
		<tr>
			<th>매입처</th>
			<td class="input_area" colspan="3">
				<label><input type="text" name="purchaseNoNm" value="<?=$data['purchaseNoNm']; ?>"
							  class="form-control"  onclick="layer_register('purchase', 'radio')" readonly/></label>
				<label>
					<button type="button" class="btn btn-sm btn-gray" onclick="layer_register('purchase', 'radio')">매입처 선택</button>
				</label>
				<a href="./purchase_register.php" target="_blank" class="btn btn-sm btn-white btn-icon-plus">매입처 추가</a>
				<label id="purchaseNoDel" style="display:<?= $data['purchaseNoNm'] ? '':'none'; ?>"><input type="checkbox" name="purchaseNoDel" value="y"> <span class="text-red">체크시 삭제</span></label>
				<div id="purchaseLayer" class="width100p">
					<?php if ($data['purchaseNo']) { ?>
					<span id="info_parchase_<?= $data['purchaseNo'] ?>" class="pull-left">
					<input type="hidden" name="purchaseNo" value="<?= $data['purchaseNo'] ?>"/>
					</span>
					<?php } ?>
				</div>
			</td>
		</tr>
	<?php } ?>
		<tr>
			<th>매입처 상품명</th>
			<td colspan="3">
				<div class="mgt5 mgb5">
					<label class="checkbox-inline" title="체크시 기본 상품명이 매입처 상품명에 추가됩니다.">
						<input type="checkbox" name="purchaseNmFl" value="y"/>체크시 기본 상품명이 매입처 상품명에 추가됩니다.
					</label>
				</div>
				<label class="label-width">
					<input type="text" name="purchaseGoodsNm" value="<?=$data['purchaseGoodsNm']; ?>" class="form-control input-width js-maxlength" maxlength="250"/>
				</label>
			</td>
		</tr>
		<tr>
			<th>브랜드</th>
			<td class="input_area">
				<label><input type="text" name="brandCdNm" value="<?=$data['brandCdNm']; ?>"
							  class="form-control"  onclick="layer_register('brand', 'radio')" readonly/></label>
				<label>
					<button type="button" class="btn btn-sm btn-gray" onclick="layer_register('brand', 'radio')">브랜드 선택</button>
				</label>
				<?php if (gd_is_provider() === false) { ?>
					<a href="./category_tree.php?cateType=brand" target="_blank" class="btn btn-sm btn-white btn-icon-plus">브랜드 추가</a>
				<?php } ?>
				<label id="brandCdDel" style="display:<?= $data['brandCdNm'] ? '':'none'; ?>"><input type="checkbox" name="brandCdDel" value="y"> <span class="text-red">체크시 삭제</span></label>

				<div id="brandLayer" class="width100p">
					<?php if ($data['brandCd']) { ?>
						<span id="info_brand_<?= $data['brandCd'] ?>" class="pull-left">
					<input type="hidden" name="brandCd" value="<?= $data['brandCd'] ?>"/>
					</span>
					<?php } ?>
				</div>
				<?php if ($gGlobal['isUse'] === true) { ?>
					<p class="notice-danger">
						대표 카테고리와 노출상점이 다른 경우 <br/>브랜드 페이지에 상품이 노출되지않습니다.
					</p>
				<?php } ?>
			</td>
			<th>제조사</th>
			<td>
				<input type="text" name="makerNm" value="<?=$data['makerNm']; ?>" class="form-control width-md js-maxlength" maxlength="40"/>
			</td>

		</tr>
		<tr>
			<th>원산지</th>
			<td>
				<input type="text" name="originNm" value="<?=$data['originNm']; ?>" class="form-control width-md js-maxlength" maxlength="40"/>
			</td>
			<th>모델번호</th>
			<td>
				<label title="상품의 모델번호를 작성해 주세요!">
					<input type="text" name="goodsModelNo" value="<?=$data['goodsModelNo']; ?>" class="form-control width-md js-maxlength" maxlength="30"/>
				</label>
			</td>

		</tr>
		<tr>
			<th>HS코드</th>
			<td colspan="3">
				<div class="js-hscode-info">
				</div>
				<div class="notice-info">추가 버튼을 이용하여 국가별 HS코드를 추가 입력할 수 있습니다.</div>
			</td>
		</tr>
		<tr>
			<th>제조일</th>
			<td>
				<label title="상품의 제조일을 선택/작성(yyyy-mm-dd)해 주세요!">
					<div class="form-inline">
						<div class="input-group js-datepicker">
							<input type="text" name="makeYmd" class="form-control" value="<?=$data['makeYmd']; ?>" placeholder="수기입력 가능">
							<span class="input-group-addon">
					<span class="btn-icon-calendar">
					</span>
				</span>
						</div>
					</div>
				</label>
			</td>
			<th>출시일</th>
			<td>
				<label title="상품의 출시일을 선택/작성(yyyy-mm-dd)해 주세요!">
					<div class="form-inline">
						<div class="input-group js-datepicker">
							<input type="text" name="launchYmd" class="form-control" value="<?=$data['launchYmd']; ?>" placeholder="수기입력 가능">
							<span class="input-group-addon">
					<span class="btn-icon-calendar">
					</span>
				</span>
						</div>
					</div>
				</label>
			</td>
		</tr>
		<tr>
			<th>유효일자</th>
			<td  <?php if (gd_is_plus_shop(PLUSSHOP_CODE_QRCODE) === false) { ?>colspan="3"<?php } ?>>
				<div class="form-inline">
					시작일 / 종료일
					<label title="상품의 유효일자 시작일을 선택/작성(yyyy-mm-dd)해 주세요!">
						<div class="form-inline">
							<div class="input-group js-datepicker">
								<input type="text" name="effectiveStartYmd" class="form-control width-xs" value="<?=$data['effectiveStartYmd']; ?>" placeholder="수기입력 가능">
								<span class="input-group-addon">
					<span class="btn-icon-calendar">
					</span>
				</span>
							</div>
						</div>
					</label>
					~
					<label title="상품의 유효일자 종료일을 선택/작성(yyyy-mm-dd)해 주세요!">
						<div class="form-inline">
							<div class="input-group js-datepicker">
								<input type="text" name="effectiveEndYmd" class="form-control width-xs" value="<?=$data['effectiveEndYmd']; ?>" placeholder="수기입력 가능">
								<span class="input-group-addon">
					<span class="btn-icon-calendar">
					</span>
				</span>
							</div>
						</div>
					</label>
				</div>
			</td>
			<?php if (gd_is_plus_shop(PLUSSHOP_CODE_QRCODE) === true) { ?>
				<th>QR코드 노출상태</th>
				<td>
					<?php
					if ($conf['qrcode']['useGoods'] == 'y') {
						?>
						<label title="상품 QR코드 설정을 사용하시려면 선택해 주세요!" class="radio-inline">
							<input type="radio" name="qrCodeFl" value="y" <?=gd_isset($checked['qrCodeFl']['y']); ?> />노출함
						</label>
						<label title="상품 QR코드 설정을 사용하지 않으시려면 선택해 주세요!" class="radio-inline">
							<input type="radio" name="qrCodeFl" value="n" <?=gd_isset($checked['qrCodeFl']['n']); ?> />노출안함
						</label>
						<?php
					} else {
						echo '<div class="notice-info if-btn">QR코드 사용 여부를 확인해 주세요.</div>';
					}
					?>
				</td>
			<?php } ?>
		</tr>
		<tr>
			<th>구매가능 회원등급</th>
			<td colspan="3" >
				<div style="position:absolute;left:820px;">
					<div  class="form-inline">
						<label class="checkbox-inline">
							<input type="checkbox" name="goodsPermissionPriceStringFl" value="y" <?=gd_isset($checked['goodsPermissionPriceStringFl']['y']); ?>  />구매불가 고객 가격 대체문구 사용
						</label>
						<span class="js-goods-permission-price-string">
						<input type="text" name="goodsPermissionPriceString"  value="<?=$data['goodsPermissionPriceString']; ?>" maxlength="30" class="form-control js-maxlength"/>
						 </span>
					</div>
				</div>
				<div>
					<?php foreach ($goodsPermissionList as $k => $v) { ?>
						<label class="radio-inline">
							<input type="radio" name="goodsPermission" value="<?=$k; ?>" <?=gd_isset($checked['goodsPermission'][$k]); ?> onclick="set_goods_permission(this.value,'memberGroup','goodsPermissionPriceStringFl')"/>
							<?=$v; ?>
						</label>
					<?php } ?>
					<label>
						<button type="button" class="btn btn-sm btn-gray" id="memberGroupBtn" onclick="layer_register('memberGroup')" <?php if ($data['goodsPermission'] !== 'group') echo 'disabled="disabled"'; ?>>회원등급 선택</button>
					</label>
					<div id="memberGroupLayer" class="selected-btn-group <?= is_array($data['goodsPermissionGroup']) ? 'active' : '' ?>">
						<?php if (is_array($data['goodsPermissionGroup'])) { ?>
							<h5>선택된 회원등급</h5>
							<?php foreach ($data['goodsPermissionGroup'] as $k => $v) { ?>
								<span id="infoMemberGroup_<?= $k ?>" class="btn-group btn-group-xs">
								<input type="hidden" name="memberGroupNo[]" value="<?= $k ?>"/>
								<span class="btn"><?= $v ?></span>
								<button type="button" class="btn btn-white btn-icon-delete" data-toggle="delete" data-target="#infoMemberGroup_<?= $k ?>">삭제</button>
							</span>
							<?php }
						} ?>

					</div>
				</div>
				<p class="notice-info mgb10 if-btn">구매불가 고객 가격 대체문구 사용"에 체크 및 내용 입력 시, 구매가 불가능한 고객들을 대상으로 가격 대신 해당 문구가 노출됩니다.</p>
			</td>
		</tr>
		<tr>
			<th>성인인증</th>
			<td colspan="3">
				<div class="form-inline">

					<label class="radio-inline">
						<input name="onlyAdultFl" value="n" type="radio" <?=gd_isset($checked['onlyAdultFl']['n']); ?>>사용안함
					</label>
					<label class="radio-inline">
						<input name="onlyAdultFl" value="y" type="radio" <?=gd_isset($checked['onlyAdultFl']['y']); ?> >사용함
					</label>

					<label class="checkbox-inline" style="padding-left:10px;">
						<input type="checkbox" name="onlyAdultDisplayFl" value="y" <?=gd_isset($checked['onlyAdultDisplayFl']['y']); ?> /> 미인증 고객 상품 노출함
					</label>
					<label class="checkbox-inline">
						<input type="checkbox" name="onlyAdultImageFl" value="y" <?=gd_isset($checked['onlyAdultImageFl']['y']); ?> /> 미인증 고객 상품 이미지 노출함
					</label>
				</div>

				<p class="notice-info mgb10 if-btn">
					해당 상품의 상세페이지 접근시 성인인증확인 인트로 페이지가 출력되며, 진열 이미지는 19금 이미지로 대체되어 보여집니다. <br/>
					<?php if (gd_is_provider() === false && !gd_use_ipin() && !gd_use_auth_cellphone() ) { ?> 성인인증 기능은 별도의 인증 서비스 신청완료 후 이용 가능합니다.<br/>

						<a href="../policy/member_auth_cellphone.php" target="_blank" class="btn-link">휴대폰인증 설정 바로가기</a>  <a href="../policy/member_auth_ipin.php" target="_blank" class="btn-link">아이핀인증 설정 바로가기</a>
						<br/><?php } ?>

				</p>
				<p class="notice-danger">
					구 실명인증 서비스는 성인인증 수단으로 연결되지 않습니다.<br/>
				</p>
			</td>
		</tr>
		<tr>
			<th>접근 권한</th>
			<td colspan="3">
				<div style="position:absolute;left:820px;">
					<div class="form-inline" >
						<label class="checkbox-inline">
							<input type="checkbox" name="goodsAccessDisplayFl" value="y" <?=gd_isset($checked['goodsAccessDisplayFl']['y']); ?> />접근불가 고객 상품 노출함
						</label>
					</div>
				</div>
				<div>
					<?php foreach ($goodsPermissionList as $k => $v) { ?>
						<label class="radio-inline">
							<input type="radio" name="goodsAccess" value="<?=$k; ?>" <?=gd_isset($checked['goodsAccess'][$k]); ?> onclick="set_goods_permission(this.value,'accessMemberGroup','goodsAccessDisplayFl')"/>
							<?=$v; ?>
						</label>
					<?php } ?>
					<label>
						<button type="button" class="btn btn-sm btn-gray" id="accessMemberGroupBtn" onclick="layer_register('accessMemberGroup')" <?php if ($data['goodsAccess'] !== 'group') echo 'disabled="disabled"'; ?>>회원등급 선택</button>
					</label>

					<div id="accessMemberGroupLayer" class="selected-btn-group <?= is_array($data['goodsAccessGroup']) ? 'active' : '' ?>">
						<?php if (is_array($data['goodsAccessGroup'])) { ?>
							<h5>선택된 회원등급</h5>
							<?php foreach ($data['goodsAccessGroup'] as $k => $v) { ?>
								<span id="infoAccessMemberGroup_<?= $k ?>" class="btn-group btn-group-xs">
								<input type="hidden" name="accessMemberGroupNo[]" value="<?= $k ?>"/>
								<span class="btn"><?= $v ?></span>
								<button type="button" class="btn btn-white btn-icon-delete" data-toggle="delete" data-target="#infoAccessMemberGroup_<?= $k ?>">삭제</button>
							</span>
							<?php }
						} ?>
					</div>
				</div>
			</td>
		</tr>
		<tr>
			<th>추가항목</th>
			<td colspan="3">
				<p>
					<button type="button" class="btn btn-sm btn-white btn-icon-plus" onclick="add_info();">항목추가</button>
					<span class="notice-info mgl10">상품특성에 맞게 항목을 추가할 수 있습니다 (예. 감독, 저자, 출판사, 유통사, 상품영문명 등)</span>
				</p>

				<table class="table table-rows" id="addInfoItem">
					<thead>
					<tr>
						<th class="width-2xs">순서</th>
						<th class="width-lg">항목</th>
						<th>내용</th>
						<th class="width-2xs">삭제</th>
					</tr>
					</thead>
					<?php
					if (!empty($data['addInfo'])) {
						foreach ($data['addInfo'] as $key => $val) {
							$nextNo = $key + 1;
							?>
							<tr id="addInfoItem<?=$nextNo; ?>">
								<td class="center"><?php if ($applyGoodsCopy === false) { ?>
										<input type="hidden" name="addInfo[sno][]" value="<?=$val['sno']; ?>" /><?php } ?><?=$nextNo; ?>
								</td>
								<td class="center">
									<input type="text" name="addInfo[infoTitle][]" value="<?=$val['infoTitle']; ?>" class="form-control width-lg"/>
								</td>
								<td class="center">
									<input type="text" name="addInfo[infoValue][]" value="<?=$val['infoValue']; ?>" class="form-control"/>
								</td>
								<td class="center">
									<input type="button" class="btn btn-sm btn-white btn-icon-minus" onclick="field_remove('addInfoItem<?=$nextNo; ?>');" value="삭제"/></span>
								</td>
							</tr>
							<?php
						}
					}
					?>
				</table>
			</td>
		</tr>
	</table>
</section>
