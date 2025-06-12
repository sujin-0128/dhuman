<form id="frmGoodsReview" name="frmGoodsReview" action="dpx_goods_review_ps.php" method="post">
	<input type="hidden" name="mode" value="review_copy" />
	<div class="page-header js-affix">
		<h3>상품 리뷰 복사</h3>
		<div class="btn-group">
			<input type="button" value="복사" class="btn btn-red js-review-copy">
		</div>
	</div>
 
	<div class="table-title gd-help-manual">
		<?=end($naviMenu->location); ?>
	</div>
	
        
    

	<table class="table table-cols">
		<colgroup>
			<col class="width-sm" /><col/>
			<col class="width-sm" /><col/>
		</colgroup>
		<tr>
			<th>(copy)상품코드</th>
			<td><input type="text" name="copyGoodsNo" id="copyGoodsNo" value=""></td>
			<th>(paste)상품코드</th>
			<td><input type="text" name="pasteGoodsNo" id="pasteGoodsNo" value=""></td>
		</tr>
	</table>
</form>
<script>
	$(document).on('click', '.js-review-copy', function (e) {
		var addParam = {};
		addParam['layerTitle'] = "상품리뷰복사";
        addParam['size'] = "wide";

		if($("#copyGoodsNo").val() ==''){
			alert('(copy)상품코드를 입력해주세요.');
			return false;
		}
		if($("#pasteGoodsNo").val() == ''){
			alert('(paste)상품코드를 입력해주세요.');
			return false;
		}

		$.ajax({
			url: "./dpx_goods_review_ps.php",
			type: "post",
			data: $("#frmGoodsReview").serialize(),
			success: function (data) {
				var layerForm = data;
				var configure = {
					title: addParam['layerTitle'],
					size: get_layer_size(addParam['size']),
					message: $(layerForm),
					closable: true
				};
				
				BootstrapDialog.show(configure);
			}, beforeSend: function () {              
				var width = 0;
				var height = 0;
				var left = 0;
				var top = 0;
 
				width = 150;
				height = 150;
				top = ( $(window).height() - height ) / 2 + $(window).scrollTop();
				left = ( $(window).width() - width ) / 2 + $(window).scrollLeft();
 
				if($("#div_ajax_load_image").length != 0) {
					$("#div_ajax_load_image").css({
						"top": top+"px",
						"left": left+"px"
					});
					$("#div_ajax_load_image").show();
				}
				else {
					$('body').append('<div id="div_ajax_load_image" style="position:absolute; top:' + top + 'px; left:' + left + 'px; width:' + width + 'px; height:' + height + 'px; z-index:9999; background:#f0f0f0; filter:alpha(opacity=50); opacity:alpha*0.5; margin:auto; padding:0; "><img src="/data/common/loading.gif" style="width:150px; height:150px;"></div>');
				}
			}, complete: function () {
				$("#div_ajax_load_image").hide();
			}
		});
	});
</script>