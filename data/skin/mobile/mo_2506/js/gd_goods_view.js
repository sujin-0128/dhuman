
var gd_goods_view = function () {
    var setOptionFl = "n";
    var setOptNo  = '';
    var setOptionTextFl = "n";
    var setOptionDisplayFl = "s";
    var setAddGoodsFl = "n";
    var setIntDivision = "";
    var setStrDivision = "";
    var setMileageUseFl = "n";
    var setCouponUseFl = "n";
    var setMinOrderCnt = 1;
    var setMaxOrderCnt = 0;
    var setStockFl = "n";
    var setSalesUnit = 1;
    var setDecimal= 0;
    var setGoodsPrice = 0;
    var setGoodsNo = '';
    var setMileageFl = '';
    var setControllerName = "";
    var setCartTabFl = "n";
    var setTemplate = "";
    var setFixedSales = "option";
    var setFixedOrderCnt = "option";
    var setGoodsNm = '';
    var noChangeFl = 'n';
    var setOptionPriceFl;
    var setStockCnt = 0;
    var setOriginalMinOrderCnt = 0;

    /**
     * 최소수량 체크
     *
     * @param string keyNo 상품 배열 키값
     */
    this.input_count_change = function(inputName,goodsFl)
    {

        var frmId = "#"+$(inputName).closest("form").attr('id')+':eq(0)';
        if($(inputName).val()=='') {
            $(inputName).val('0');
        }
        $(inputName).val($(inputName).val().replace(/[^0-9\-]/g,""));

        var itemNo = $(inputName).data('key');
        if(itemNo) {
            var optionDisplay =itemNo.split(setIntDivision);
            var optionDisplay = optionDisplay[0];
        } else {
            var optionDisplay = "0";
            itemNo = 0;
        }

        if($("#option_display_item_"+optionDisplay+" input[name='couponApplyNo[]']").val()) {
            alert(__('쿠폰이 적용된 상품은 수량변경을 할 수 없습니다.%1$s쿠폰취소 후 수량을 변경해주세요.', '\n'));
            $(inputName).val($(inputName).data('value'));
            return false;
        }

        if(goodsFl) {

            var nowCnt	= parseInt($('input.goodsCnt'+setOptNo+'_'+itemNo).val());

            var minCnt = parseInt(setMinOrderCnt);
            var maxCnt = parseInt(setMaxOrderCnt);

            var stockFl		= setStockFl;
            var setStock	= parseInt($(inputName).data('stock'));
            if (((setStock > 0 &&  maxCnt ==0) || (setStock <= maxCnt)) && stockFl == 'y') {
                maxCnt = parseInt(setStock);
            }

            var salesUnit = parseInt(setSalesUnit);

        } else {
            var nowCnt	= parseInt($(inputName).val());
            var minCnt = 1;
            var salesUnit = 1;

            if($(inputName).data('stock-fl') =='1') var maxCnt =  parseInt($(inputName).data('stock'));
            else var maxCnt = 0 ;
        }

        if(parseInt( nowCnt % salesUnit) > 0 ) {
            alert(setGoodsNm+__("%s개 단위로 묶음 주문 상품입니다.", salesUnit));
            $(inputName).val($(inputName).data('value'));
            return false;
        }

        if (nowCnt < minCnt && minCnt != 0 && minCnt != '' && typeof minCnt != 'undefined') {
            alert(setGoodsNm+__('최소수량은 %1$s이상입니다.', minCnt));
            $(inputName).val($(inputName).data('value'));
            return false;
        }

        if (nowCnt > maxCnt && maxCnt != 0 && maxCnt != '' && typeof maxCnt != 'undefined') {

            if(parseInt( maxCnt % salesUnit) > 0 ) {
                alert(setGoodsNm+__("최대 주문 가능 수량을 확인해주세요."));
                $(inputName).val($(inputName).data('value'));
                return false;
            } else {
                alert(setGoodsNm+__('최대수량은 %1$s이하입니다.', maxCnt));
                $(inputName).val($(inputName).data('value'));
                return false;
            }
        }

        $(inputName).data('value', $(inputName).val());

        this.goods_calculate(frmId, goodsFl, itemNo, $(inputName).val());
    }


    /**
     * 수량 변경하기
     *
     * @param string inputName input box name
     * @param string modeStr up or dn
     * @param integer minCnt 최소수량
     * @param integer maxCnt 최대수량
     */
    this.count_change = function(inputName,goodsFl)
    {
        var frmId = "#"+$(inputName).closest("form").attr('id')+':eq(0)';

        var tmpStr =  $(inputName).val().split(setStrDivision);
        var modeStr =  tmpStr[0];
        var itemNo =  tmpStr[1];

        var optionDisplay =itemNo.split(setIntDivision);

        if($("#option_display_item_"+optionDisplay[0]+" input[name='couponApplyNo[]']").val()) {
            alert(__('쿠폰이 적용된 상품은 수량변경을 할 수 없습니다.%1$s쿠폰취소 후 수량을 변경해주세요.', '\n'));
            return false;
        }
        $('button.goods_cnt').attr('disabled', true);
        $('button.add_goods_cnt').attr('disabled', true);
        var minCnt =parseInt(setMinOrderCnt);
        var maxCnt = parseInt(setMaxOrderCnt);

        var nowCnt	= parseInt($(inputName).closest('span.count').find('input').val());
        var nowCntChangeFl = false;

        if(goodsFl) {

            var salesUnit = parseInt(setSalesUnit);
            if(setOptNo != "" && setOptNo != undefined){
                salesUnit = minCnt = 1;
            }

            // 최소 수량 체크
            if (minCnt == 0 || minCnt == '' || typeof minCnt == 'undefined') {
                minCnt = 1;
            }
            if (nowCnt < minCnt) {
                nowCnt = parseInt(minCnt);
                nowCntChangeFl = true;
            }

            // 최대 수량 체크
            if (maxCnt == 0 || maxCnt == '' || typeof maxCnt == 'undefined') {
                var maxCntChk = false;
            } else {
                var maxCntChk = true;
                maxCnt = parseInt(maxCnt);
            }

            var stockFl		= setStockFl;
            var setStock	= parseInt($(inputName).closest('span.count').find('input').data('stock'));
            if (((setStock > 0 &&  maxCnt ==0) || (setStock <= maxCnt)) && stockFl == 'y') {
                maxCnt = setStock;
                var maxCntChk = true;
            }

        }
        else {
            var salesUnit = 1;
            if(setOptNo != "" && setOptNo != undefined){
                salesUnit = minCnt = 1;
            }
            minCnt = 1;

            if($(inputName).closest('span.count').find('input').data('stock-fl') =='1') {
                var maxCnt =  parseInt($(inputName).closest('span.count').find('input').data('stock'));
                var maxCntChk = true;
            }
            else var maxCnt = 0 ;
        }

        if (isNaN(nowCnt) === true) {
            var thisCnt	= minCnt;
        } else {
            if (modeStr == 'up') {
                if ((maxCntChk === true && (nowCnt+salesUnit) > maxCnt) || nowCntChangeFl) {
                    var thisCnt	= nowCnt;
                } else {
                    var thisCnt	= nowCnt + salesUnit;
                }
            } else if (modeStr == 'dn') {
                if (nowCnt > minCnt) {
                    var thisCnt	= nowCnt - salesUnit;
                }
                else{
                    var thisCnt = nowCnt;
                }
            }
        }

        var goodsCntInput =  $(inputName).closest('span.count').find('input');
        if(setOptNo != "" &&  setOptNo != undefined) {
            goodsCntInput =  $(inputName).closest('span.count').find('input[type="number"]');
        }
        $(goodsCntInput).val(thisCnt);
        $(goodsCntInput).data('value',thisCnt);


        this.goods_calculate(frmId, goodsFl, itemNo, thisCnt);
    }


    /**
     * 옵션에 따른 가격 출력
     *
     * @param integer optionNo 상품 배열 키값 (기본 0)
     */
    this.option_price_display = function(inputName)
    {
        // 구매불가 상품 가격 미출력
        if ($('input[name="orderPossible"]').length && $('input[name="orderPossible"]').val() === 'n') {
            return false;
        }
        // setOptNo : 상품상세 관련상품은 상품번호 대입, 장바구니/찜리스트/분류리스트(장바구니형) 옵션변경은 미정의
        var frmId = "#"+$(inputName).closest("form").attr('id')+':eq(0)';

        if(setOptNo != "" &&  setOptNo != undefined) {
            if($(inputName).data('params') ==undefined) {
                $('[id^=option_display_item_'+setOptNo+']').remove();
                if($('.js_option_select' + setOptNo).length == 1) $('.js_option_select' + setOptNo).html(__("옵션을 선택하세요."));
            }
        }

        if(setOptionTextFl =='y') {
            if(!this.option_text_valid(frmId))
            {
                if(setOptNo == "" || setOptNo == undefined) {
                    if (setOptionDisplayFl == 's') {
                        $(frmId + ' select[name="optionSnoInput' + setOptNo + '"]').val('');
                    } else {
                        $(frmId + ' select[name*="optionNo_"]').val('');
                    }

                    alert(__('선택한 옵션의 필수 텍스트 옵션 내용을 먼저 입력해주세요.'));

                    return false;
                }
            }
            $(frmId+' input[name*="optionTextInput'+setOptNo+'"]').val('');
        }


        if(setAddGoodsFl =='y') {
            if(!this.add_goods_valid(frmId))
            {
                if(setOptNo == "" || setOptNo == undefined) {
                    if (setOptionDisplayFl == 's') {
                        $(frmId + ' select[name="optionSnoInput' + setOptNo + '"]').val('');
                    } else {
                        $(frmId + ' select[name*="optionNo_"]').val('');
                        $(frmId + ' select[name*="optionNo_"]').trigger('chosen:updated');
                    }

                    alert(__('선택한 옵션의 필수 추가 상품 먼저 선택해주세요.'));
                    return false;
                }
            }
        }


        if($(frmId+" input[name='selectGoodsFl']").length && $(frmId+" input[name='selectGoodsFl']").val()) {
            $(frmId+" div.option_display_area").html('');
        }

        if(setOptionDisplayFl =='s') {
            if($(inputName).data('params') !='') {
                var valTmp	= $(inputName).data('params');
            }
        } else if(setOptionDisplayFl =='d') {
            var valTmp		= $(frmId+' input[name="optionSnoInput'+setOptNo+'"]').val();
            $(".js_multi_option .js_option_select").html(__("옵션을 선택하세요."));
            $(".js_multi_option .js_option_select:not(:first)").addClass("disabled");
        }

        if (typeof valTmp == 'undefined') return false;

        var arrTmp	= new Array();
        var arrTmp	= valTmp.split(setStrDivision);
        var optionName = arrTmp[1].trim();
        var optionInput = arrTmp[0];
        var optionSellCodeValue = arrTmp[2];
        var optionDeliveryCodeValue = arrTmp[3];

        if(optionSellCodeValue != "" && optionSellCodeValue != undefined){
            optionSellCodeValue = '[' + optionSellCodeValue + ']';
        }
        if(optionDeliveryCodeValue != "" && optionDeliveryCodeValue != undefined){
            optionDeliveryCodeValue = '[' + optionDeliveryCodeValue + ']';
        }

        var arrTmp	= optionInput.split(setIntDivision);

        if(setMileageUseFl =='y' && arrTmp[2]) {
            $(frmId+' input[name="set_goods_mileage"]').val(parseFloat(arrTmp[2].trim()));
        }


        if(arrTmp[3]) $(frmId+' input[name="set_goods_stock"]').val(parseFloat(arrTmp[3].trim()));

        var optionPrice = arrTmp[1].trim();
        var optionStock = parseFloat(arrTmp[3].trim());

        var displayOptionkey = arrTmp[0]+'_'+$.now();
		var dpxMaxSelectCnt = $('input[name="dpxMaxSelectCnt"]').val();
		var totalCnt = 0;

		$('.dpxCnt').each(function(){
			totalCnt+=Number($(this).val());
		});

        if(setOptNo != "" && setOptNo != undefined) {
            $('[id^="option_display_item_'+setOptNo+'"]').remove();
        }

        if($(frmId+" div.optionKey_"+arrTmp[0]).length && setOptionTextFl !='y') {
            var up_chk_body = document.getElementsByClassName("up_chk_body");
			var chkIndex;
			var up_chk_basket = {};

			console.log(up_chk_body);

            /*$($(inputName).find('option')).each(function (key) {
				if($(this).val().substring(0,4) == arrTmp[0]) {
					chkIndex = key;
					//chkVal = $(this).val().substring(0,4);
				}
            });*/


			for (var v = 0; v < up_chk_body.length; v++) {
				up_chk_basket['basket' + v] = up_chk_body[v];
				var basket_sub = 'basket' + v;
				var up_body_length = up_chk_body[v];
				var up_body_value = up_body_length.classList[1].substring(10, 14);
				var up_body_click = up_body_length.getElementsByClassName("up")[0];
					
				console.log(up_body_length);
				console.log(up_body_value);
				console.log(up_chk_basket[basket_sub].classList[1].substring(10, 14));
				console.log(arrTmp[0]);

				if (up_chk_basket[basket_sub].classList[1].substring(10, 14) == arrTmp[0]) {
					console.log('run')
					up_body_click.click();
					return false;
				}
			};
			
			/*if (up_body_value == arrTmp[0]) {
				console.log('run')
				up_body_click.click();
				return false;
			}*/
			
           // alert(__("이미 선택된 옵션입니다."));
           // return false;
        } else if(dpxMaxSelectCnt != 0 && dpxMaxSelectCnt <= totalCnt) {
			alert('팩구성은 '+dpxMaxSelectCnt+'개까지 가능합니다.');
            return false;
		} else {
            var addHtml = "";
            var complied = _.template($('#optionTemplate' + setTemplate).html());
            if(setOptNo != "" && setOptNo != undefined){
                complied = _.template($('#optionTemplateRelated'+setOptNo + setTemplate).html());
            }
            addHtml += complied({
                displayOptionkey: displayOptionkey,
                optionSno: arrTmp[0],
                optionName:optionName,
                optionPrice: optionPrice,
                optionStock : optionStock,
                optionSellCodeValue: optionSellCodeValue,
                optionDeliveryCodeValue: optionDeliveryCodeValue
            });

            $(frmId+" div.option_display_area"+setOptNo).append(addHtml);

            // 상품 옵션가 표시 설정
            if (setOptionPriceFl == 'y' && optionPrice) {
                if(optionPrice > 0 ) var addPlus = "+";
                else var addPlus = "";

                var optionDisplayTextPrice = ' ('+ addPlus + gdCurrencySymbol + gd_money_format(optionPrice) + gdCurrencyString+')';
                $('[id^=option_display_item_'+displayOptionkey+'] dl > dt').append(optionDisplayTextPrice);
            }

            $(frmId + ' div#option_display_item_' + displayOptionkey+' div.optionKey_'+arrTmp[0] + '  .goods_cnt').on('click', function(e){
                setControllerName.count_change(this,1);
            });

            $(frmId+' div.optionKey_'+arrTmp[0] + '  button.delete_goods').on('click', function(e){
                setControllerName.remove_option($(this).data('key'));
            });


            this.goods_calculate(frmId, 1, displayOptionkey, setMinOrderCnt);

            $(frmId+" div.option_total_display_area").show();

        }

    }

    this.option_select = function(inputName,thisKey,stockViewFl)
    {
        // setOptNo : 상품상세 관련상품은 상품번호 대입, 장바구니/찜리스트/분류리스트(장바구니형) 옵션변경은 미정의
        var frmId = "#"+$(inputName).closest("form").attr('id')+':eq(0)';

        // 무한정 판매 여부
        var stockFl				= setStockFl;

        // 옵션의 갯수
        var optionCnt			= $(frmId+ ' input[name="optionCntInput'+setOptNo+'"]').val();

        // 옵션 가격 출력 여부
        var optionPriceFl		= 'y';

        // 옵션 가격이 다른 경우 출력 여부
        var optionPriceDiffFl	= 'y';

        // 기본 상품 가격
        var defaultGoodsPrice	= parseFloat(setGoodsPrice);

        // 선택된 옵션
        var optionVal	= new Array();

        for (var i = 0; i <= thisKey; i++) {
            optionVal[i]	= $(frmId+' li.js_multi_option'+setOptNo+'.optionNo_'+i+' ul.optlst li.selected').data('params');
            // 선택값이 없는경우 disabled 처리
            if (optionVal[i] == '') {
                for (var j = (i+1); j <= optionCnt; j++) {
                    if (j != 0) {
                        $(frmId+' li.optionNo_'+j+' .js_option_select'+setOptNo).addClass('disabled');
                    }
                }

                return true;
            }
        }

        $.post('../goods/goods_ps.php',{'mode' : 'option_select', 'optionVal' : optionVal, 'optionKey' : thisKey, 'goodsNo' : setGoodsNo, 'mileageFl' :setMileageFl}, function(data){

            if (typeof data.optionSno == 'string') {
                //분리형 옵션 - 타임세일 할인율 적용된 옵션가격 설정.
                var optionInfo = data.optionSno.split('||');
                optionInfo[1] = data.optionPrice[0];
                var optionSno = optionInfo.join('||');

                $(frmId+' input[name=\'optionSnoInput'+setOptNo+'\']').val(optionSno+setStrDivision+optionVal.join('/') + setStrDivision + data.stockCodeValue + setStrDivision + data.deliveryCodeValue);
                setControllerName.option_price_display($(frmId+' input[name=\'optionSnoInput'+setOptNo+'\']'));

                return true;
            } else {
                $(frmId+' input[name=\'optionSnoInput\']').val('');
            }

            for (var i = 0; i <= optionCnt; i++) {
                if (i <= data.nextKey) {
                    $(frmId+' li.optionNo_'+i+' .js_option_select'+setOptNo).removeClass('disabled');
                    if (i == data.nextKey) {
                        $(frmId+' li.js_multi_option'+setOptNo+'.optionNo_'+i+' ul.optlst').html("");

                        nextVal =  $(frmId+' li.optionNo_'+(i-1)+' .js_option_select'+setOptNo).data("next-option");

                        var addSelectOption		= '';
                        for (var j = 0; j < data.nextOption.length; j++) {
                            if(data.optionViewFl[j] =='y') {
                                if(j == 0)
                                {
                                    // 옵션 선택명
                                    addSelectOption	+= '<li class="disabled2">';
                                    if(setOptNo != "" && setOptNo != undefined){
                                        addSelectOption	+= ' <nobr>';
                                    }
                                    addSelectOption	+= '== '+nextVal+' 선택';

                                    // 마지막 옵션의 경우 가격, 재고 출력
                                    if ((parseInt(data.nextKey) + 1) == parseInt(optionCnt)) {
                                        if (optionPriceFl == 'y' && optionPriceDiffFl == 'y') {
                                            addSelectOption	+= ' : ' + __('가격');
                                        }
                                        if (stockFl == 'y' && stockViewFl == 'y') {
                                            addSelectOption	+= ' : ' + __('재고');
                                        }
                                    }

                                    addSelectOption	+= '==';
                                    if(setOptNo != "" && setOptNo != undefined){
                                        addSelectOption	+= '</nobr>';
                                    }
                                    addSelectOption	+= '</li>';
                                }

                                // 옵션값
                                addSelectOption	+= '<li data-index="'+i+'" data-params="'+data.nextOption[j]+'"';

                                // 재고 체크 (품절이면 disabled 처리) : 분리형 옵션 Disabled 표기 여부가 't'인 경우
                                if (data.optionDivisionDisabledMark == 't' && ((stockFl == 'y' && setStockCnt <  setOriginalMinOrderCnt)  || (stockFl == 'y' && setFixedOrderCnt == 'option' && data.stockCnt[j] < setMinOrderCnt) || (stockFl == 'y' && data.stockCnt[j] == 0)  || data.optionSellFl[j] == 'n' || data.optionSellFl[j] == 't')) {
                                    addSelectOption	+= ' class="disabled"';
                                }
                                // 마지막 옵션의 경우 재고 체크 (품절이면 disabled 처리) : 구버전 레거시 조건
                                else if ((parseInt(data.nextKey) + 1) == parseInt(optionCnt) && ((stockFl == 'y' && setStockCnt <  setOriginalMinOrderCnt)  || (stockFl == 'y' && setFixedOrderCnt == 'option' && data.stockCnt[j] < setMinOrderCnt) || (stockFl == 'y' && data.stockCnt[j] == 0)  || data.optionSellFl[j] == 'n' || data.optionSellFl[j] == 't')) {
                                    addSelectOption	+= ' class="disabled"';
                                }

                                // 옵션값
                                addSelectOption	+= '>'+data.nextOption[j];

                                // 마지막 옵션의 경우 재고 체크 및 가격
                                if ((parseInt(data.nextKey) + 1) == parseInt(optionCnt)) {
                                    // 가격 출력여부

                                    if(parseInt(data.optionPrice[j]) !='0') {
                                        if(data.optionPrice[j] > 0 ) var addPlus = "+";
                                        else var addPlus = "";
                                        if (optionPriceFl == 'y' && optionPriceDiffFl == 'y') {
                                            addSelectOption	+= ' : '+addPlus+gd_money_format(data.optionPrice[j].toString())+'';
                                        } else if (optionPriceFl == 'y' && optionPriceDiffFl == 'n' && defaultGoodsPrice != parseFloat(data.optionPrice[j])) {
                                            addSelectOption	+= ' ('+addPlus+gd_money_format(data.optionPrice[j].toString())+')';
                                        }
                                    }

                                    // 재고 체크
                                    if (typeof data.stockCodeValue !== 'undefined' && data.optionSellFl[j] == 't' && data.stockCodeValue[j] != '' && data.stockCodeValue[j] != null) {
                                        addSelectOption	+= ' [' + data.stockCodeValue[j] + ']';
                                    }else if (typeof data.stockCodeValue !== 'undefined' && ((stockFl == 'y' && data.stockCnt[j] == 0) || data.optionSellFl[j] == 'n')) {
                                        addSelectOption	+= ' [' + data.stockCodeValue['n'] + ']';
                                    } else if (stockFl == 'y' && stockViewFl == 'y') {
                                        addSelectOption	+= ' : '+numeral(data.stockCnt[j]).format()+__('개');
                                    }
                                    // 배송 상태 설정
                                    if (typeof data.deliveryCodeValue !== 'undefined' && data.deliveryCodeValue[j] != '' && data.deliveryCodeValue[j] != null) {
                                        addSelectOption += '[' + data.deliveryCodeValue[j] + ']';
                                    }
                                } else if (data.optionDivisionDisabledMark == 't') { // 분리형 옵션 Disabled 표기 여부가 't'인 경우
                                    // 재고 체크
                                    if (typeof data.stockCodeValue !== 'undefined' && data.optionSellFl[j] == 't' && data.stockCodeValue[j] != '' && data.stockCodeValue[j] != null) {
                                        addSelectOption	+= ' [' + data.stockCodeValue[j] + ']';
                                    }else if (typeof data.stockCodeValue !== 'undefined' && ((stockFl == 'y' && data.stockCnt[j] == 0) || data.optionSellFl[j] == 'n')) {
                                        addSelectOption	+= ' [' + data.stockCodeValue['n'] + ']';
                                    }
                                }

                                // 옵션값
                                addSelectOption	+= '</li>';
                            }
                        }

                        $(frmId+' li.js_multi_option'+setOptNo+'.optionNo_'+i+' ul.optlst').html(addSelectOption);
                    }
                } else {
                    if (i != 0) {
                        $(frmId+' li.optionNo_'+i+' .js_option_select'+setOptNo).addClass('disabled');
                    }
                }
            }


        }, 'json');
    }


    /**
     * 옵션 삭제
     *
     * @param optionId 삭제 옵션 아이디
     */
    this.remove_option = function(optionId) {

        var frmId = "#"+$("#"+optionId).closest("form").attr('id')+':eq(0)';

        $("#"+optionId).remove();
        if (typeof gd_total_calculate !== 'undefined' && $.isFunction(gd_total_calculate)) gd_total_calculate();

        var ontionCnt = $('div[id*=\'option_display_item_\']').length;
        if(ontionCnt == 0) $(frmId+" div.option_total_display_area").hide();

        // 옵션을 여러개 생성 후 가운데 옵션을 삭제 했을 때 배열 순서 재정의
        if(setOptionTextFl =='y') {
            $(frmId + ' div[id*=\'option_display_item_' + setOptNo + '\']').each(function (key, div) {
                $(div).find('input[name*=\'optionText[\']').each(function (_, input) {
                    var newName = $(input).attr('name').replace(/\[(\s*?.*?)*?\]/, '[' + key + ']');
                    $(input).attr('name', newName);
                });
            });
        }
    }


    /**
     * 텍스트 옵션 선택
     */
    this.option_text_select = function(inputName) {

        var frmId = "#"+$(inputName).closest("form").attr('id');

        var optionText = '';
        var optionTextPrice = 0;
        var optionTextTotalPrice = 0;
        var optionTextSno = '';
        var optionTextKey = "";

        var displayOptionDisplay = $(frmId+' div[id*=\'option_display_item_'+setOptNo+'\']').last().attr('id');

        if(displayOptionDisplay)
        {
            if(setOptNo != "" &&  setOptNo != undefined) {
                if($(frmId+' #'+displayOptionDisplay+' div.check').length == 0){
                    displayOptionDisplay = displayOptionDisplay + '_0';
                }
            }
            var checkKey = $(frmId+' #'+displayOptionDisplay+' div.check').attr('class').replace("check","").trim();
            var displayOptionkey = displayOptionDisplay.replace("option_display_item_", "");


            //if(setOptionFl =='y') {
                var optionItemNo = $(frmId+' div[id*=\'option_display_item_\']').length-1;
            //} else {
            //    var optionItemNo = 0;
            //}

            if($(frmId+' #option_text_display_' + displayOptionkey).html() !== '' && noChangeFl == 'n') {
                var optionTextInputCnt = $(frmId+' input[name*=\'optionTextInput_\']').length;
                var emCnt = $(frmId+' div[id*=\'option_display_item_\']').last().find('em').length;
                if(optionTextInputCnt <= emCnt) {
                    optionItemNo++;
                    var addHtml = "";
                    var complied = _.template($('#optionTemplate'+setTemplate).html());
                    var $lastDiv = $(frmId+' div[id*=\'option_display_item_'+ setOptNo +'\']').last();
                    var optionSno = $lastDiv.find('input[name=\'optionSno[]\']').val();
                    var optionName = $lastDiv.find('dt').first().text();
                    var optionPrice = $lastDiv.find('input[name*=\'option_price_\']').last().val();
                    var optionStock = $lastDiv.find('input[name*=\'goodsCnt\']').attr('data-stock');
                    var optionSellCodeValue = '';
                    var optionDeliveryCodeValue = '';

                    displayOptionkey = optionSno+'_'+$.now();
                    addHtml += complied({
                        displayOptionkey: displayOptionkey,
                        optionSno: optionSno,
                        optionName: optionName,
                        optionPrice: optionPrice,
                        optionStock : optionStock,
                        optionSellCodeValue: optionSellCodeValue,
                        optionDeliveryCodeValue: optionDeliveryCodeValue
                    });
                    $(frmId+" div.option_display_area").append(addHtml);

                    $(frmId+' #option_display_item_'+displayOptionkey + '  .goods_cnt').on('click', function (e) {
                        setControllerName.count_change(this,1);
                    });

                    $(frmId+' #option_display_item_'+displayOptionkey + '  button.delete_goods').on('click', function (e) {
                        setControllerName.remove_option($(this).data('key'));
                    });
                }
            }

            $(frmId+' input[name*=\'optionTextInput'+setOptNo+'\']').each(function (key) {
                if ($(this).val()) {

                    var optionTextLimit =  $(frmId+' input[name="optionTextLimit'+setOptNo+'_'+key+'"]').val();
                    if($(this).val().length > optionTextLimit) {
                        $(this).val($(this).val().substring(0,optionTextLimit));
                    }

                    var optionValue = $(this).val().replace(/'/g, '&#39;').replace(/"/g, '&quot;');

                    optionTextPrice =parseFloat($(this).next('input').val());
                    optionTextSno += '<input type="hidden"  value="' + optionValue + '" name="optionText'+setOptNo+'[' + optionItemNo + '][' + $(this).prev('input').val() + ']">';


                    var optionTextNm = $(frmId+' span.optionTextNm_'+key).text();
                    var optionDisplayText = optionTextNm+' : '+optionValue;

                    // 상품 옵션가 표시 설정
                    if (setOptionPriceFl == 'y') {
                        if(optionTextPrice > 0 ) var addPlus = "+";
                        else var addPlus = "";
                        var optionDisplayTextPrice = ' <b>(' + addPlus + gdCurrencySymbol + gd_money_format(optionTextPrice) + gdCurrencyString + ')</b>';
                        optionDisplayText = optionDisplayText + optionDisplayTextPrice;
                    }

                    optionTextSno += '<em>'+optionDisplayText+'</em>';


                    for (var i = 0, m = optionValue.length; i < m; i++) {
                        optionTextKey += optionValue.charCodeAt(i);
                    }

                } else {
                    optionTextSno +='';
                    optionTextPrice = 0;
                }

                optionTextTotalPrice += optionTextPrice;

            });


            var tmpStr = displayOptionkey.split('_');


            if($(frmId+' div.optionKey_'+tmpStr[0]+optionTextKey).length) {
                alert("이미 선택된 옵션입니다");
                return false;
            } else {
                if(optionTextKey)   $('#'+displayOptionDisplay+' div.'+checkKey +' .name strong').addClass('btm_txt');
                else  $('#'+displayOptionDisplay+' div.'+checkKey +' .name strong').removeClass('btm_txt');

                optionText = optionTextSno + '<input type="hidden" value="' + optionTextTotalPrice + '" name="optionTextPriceSum'+setOptNo+'[]" ><input type="hidden" value="' + optionTextTotalPrice + '" name="option_text_price_' + displayOptionkey + '"></div>';

                $('#option_text_display'+setOptNo+'_' + displayOptionkey).html(optionText);
                if(setOptNo != "" &&  setOptNo != undefined) {
                    $('span[id*="option_text_display' + setOptNo + '"]').html(optionText);
                }else{
                    $('#'+displayOptionDisplay+' div.'+checkKey).attr('class', 'check optionKey_' + tmpStr[0] + optionTextKey);
                }


                var goodsCnt = $(frmId+" input.goodsCnt_"+displayOptionkey).val();
                this.goods_calculate(frmId, 1, displayOptionkey, goodsCnt);
            }

        } else{
            alert(__("옵션을 먼저 선택해주세요"));
            $(frmId+' input[name*=\'optionTextInput\']').val('');
            return false;
        }

    }

    /**
     * 텍스트 옵션 유효성체크
     */
    this.option_text_valid = function(frmId) {

        if($(frmId+' input[name="optionSno'+setOptNo+'[]"]').length) {
            var returnFl = true;

            $(frmId+' input[name="optionSno'+setOptNo+'[]"]').each(function (key) {

                $(frmId +' input[name*=\'optionTextInput'+setOptNo+'\']').each(function (textKey) {

                    var optionTextSno = $(frmId +' input[name="optionTextSno'+setOptNo+'_'+textKey+'"]').val();
                    var optionText = $(frmId+' input[name="optionText'+setOptNo+'[' + key + '][' + optionTextSno + ']"]');
                    if(setOptNo != "" &&  setOptNo != undefined) {
                        optionText = $(frmId+' input[name*="optionText'+setOptNo+'"]');
                    }


                    if ( $(frmId +' input[name="optionTextMust'+setOptNo+'_'+textKey+'"]').val() == 'y') {

                        if (optionText.length == '0') {
                            $(frmId +' input[name="optionTextInput'+setOptNo+'_'+textKey+'"]').focus();
                            returnFl =  false;
                        }
                    }
                });
            });
            return returnFl;

        }
        else return true;

    }

    /**
     * 텍스트 옵션 재고 체크
     */
    this.option_text_cnt_valid = function(frmId) {
        var checkOption = [];
        var checkOptionCnt = false;
        $(frmId + ' input[name*="goodsCnt[]"]').each(function (index) {
            var stock = $(this).data('stock');
            var _key = 0;
            if(setOptionFl == 'y') {
                _key = $(this).data('key');
                _key = _key.split('_');
                _key = _key[0];
            }
            if(checkOption[_key] > 0) checkOption[_key] = parseFloat(checkOption[_key]) + parseFloat($(this).val());
            else checkOption[_key] = parseFloat($(this).val());
            if(checkOption[_key] > stock) {
                checkOptionCnt = stock;
                return true;
            }
        });
        return checkOptionCnt;
    }

    this.add_goods_select = function(inputName) {

        var frmId = "#"+$(inputName).closest("form").attr('id')+':eq(0)';
        var selAddGoods = $(inputName).data('key');

        if ($(inputName).data('params') != '') {


            var displayOptionDisplay = $(frmId+' div[id*=\'option_display_item_'+setOptNo+'\']').last().attr('id');

            if(displayOptionDisplay)
            {

                var displayOptionkey = displayOptionDisplay.replace("option_display_item_", "");

                if(setOptNo != "" && setOptNo != undefined){
                    var tmp = displayOptionkey.split('_');
                    displayOptionkey = tmp[0] + '_' + selAddGoods;
                }

                if ($('#'+displayOptionDisplay + ' .add').length == 0) {
                    $('#'+displayOptionDisplay).append('<div class="add"></div>');
                }

                if($(inputName).data('value') == '0'){
                    $('[id^=add_goods_display_item_'+displayOptionkey+']').remove();
                    return;
                }
                var arrTmp = new Array();
                var arrTmp = $(inputName).data('params').split(setStrDivision);
                var addGoodsName = arrTmp[1].trim();
                var addGoodsimge = decodeURIComponent(arrTmp[2].trim());
                var addGoodsGroup = arrTmp[3].trim();
                var addGoodsValue = arrTmp[0];
                var addGoodsStockFl = arrTmp[4];
                var addGoodsStock = arrTmp[5];
                var arrTmp = addGoodsValue.split(setIntDivision);


                var displayAddGoodsKey = arrTmp[0];
                var optionIndex = $(frmId+ ' #'+displayOptionDisplay).index();

                if(setOptNo != "" && setOptNo != undefined){
                    optionIndex = 0;
                    setControllerName.remove_add_goods(displayOptionkey,displayAddGoodsKey);
                    $('#'+displayOptionDisplay).append('<div class="add"></div>');
                }

                if($(frmId+ ' #add_goods_display_item_' + displayOptionkey + '_' + displayAddGoodsKey).length && (setOptNo == "" ||  setOptNo == undefined)) {
                    alert(__("이미 선택된 추가상품 입니다."));
                    return false;
                } else {

                    var addHtml='';
                    var complied = _.template($('#addGoodsTemplate'+setTemplate).html());
                    if(setOptNo != "" && setOptNo != undefined){
                        complied = _.template($('#addGoodsTemplateRelated'+setOptNo + setTemplate).html());
                    }
                    addHtml += complied({
                        displayOptionkey: displayOptionkey,
                        displayAddGoodsKey: displayAddGoodsKey,
                        addGoodsimge:addGoodsimge,
                        addGoodsGroup:addGoodsGroup,
                        optionIndex: optionIndex,
                        optionSno: arrTmp[0],
                        addGoodsName: addGoodsName,
                        addGoodsStockFl: addGoodsStockFl,
                        addGoodsStock: addGoodsStock,
                        addGoodsPrice:  parseFloat(arrTmp[1].trim())
                    });


                    if(setOptNo != "" && setOptNo != undefined){
                        $('[id^=add_goods_display_item_'+displayOptionkey+']').remove();
                        $('[id^=add_goods_display_area_'+displayOptionkey+']').append(addHtml);
                    }else {
                        $(frmId + ' #option_display_item_' + displayOptionkey + ' .add').append(addHtml);
                    }


                    $(frmId+ ' #add_goods_display_item_'+displayOptionkey + '_' + displayAddGoodsKey+' .add_goods_cnt').on('click', function(e){
                        setControllerName.count_change(this,0);
                    });

                    $(frmId+ ' #add_goods_display_item_'+displayOptionkey + '_' + displayAddGoodsKey+'  button.delete_add_goods').on('click', function(e){
                        setControllerName.remove_add_goods(displayOptionkey,displayAddGoodsKey);
                    });


                    var itemNo = displayOptionkey + setIntDivision + displayAddGoodsKey;

                    this.goods_calculate(frmId, 0, itemNo, 1);

                    if(setCouponUseFl =='y') {
                        //if (typeof bindBtnOpenLayer !== 'undefined' && $.isFunction(bindBtnOpenLayer)) bindBtnOpenLayer();
                    }
                }
            }
            else
            {
                alert(__("옵션을 먼저 선택해주세요."));
                $(frmId+' select[name*=\'addGoodsInput\']').val('');
                return false;
            }
        }
    }


    /**
     * 추가상품 유효성검사
     */
    this.add_goods_valid = function(frmId) {
        if($(frmId+' input[name="addGoodsInputMustFl'+setOptNo+'[]"]').length) {
            var checkMustCnt = $(frmId+' input[name="addGoodsInputMustFl'+setOptNo+'[]"]').length;
            if($(frmId+' input[name="optionSno'+setOptNo+'[]"]').length) {

                var totalOptionCnt = 0; // 총 옵션수
                var mustCnt = 0; // 옵션별 필수상품수
                var haveToCnt = 0; // 옵션1개별 필수체크 통과한 수
                $(frmId+" div[id*='option_display_item_"+setOptNo+"']").each(function (key) {
                    var itemId = this.id;

                    $(frmId+' input[name="addGoodsInputMustFl'+setOptNo+'[]"]').each(function () {
                        var addGoodsValue = this.value;

                        var items = $("#"+itemId+" input[name='addGoodsNo"+setOptNo+"["+key+"][]']");
                        if(setOptNo != "" &&  setOptNo != undefined) {
                            items = $("input[name='addGoodsNo"+setOptNo+"["+key+"][]']");
                        }
                        items.each(function () {
                            var group = $(this).data('group');

                            if (addGoodsValue == group) {
                                mustCnt++;
                                return false;
                            }
                        });
                    });

                    if(mustCnt == $(frmId+' input[name="addGoodsInputMustFl'+setOptNo+'[]"]').length) { // 옵션 1개 별로 필수 체크 통과하면 haveToCnt증가
                        haveToCnt++;
                    }

                    mustCnt = 0; // 옵션 1개별로 필수 체크 초기화
                    totalOptionCnt++; // 옵션별 카운트

                });

                if (totalOptionCnt == haveToCnt) { //옵션수와 필수체크통과한 옵션수가 동일하면 통과
                    return true;
                } else {
                    return false;
                }
            }
            else return true;

        } else return true;

    }

    /**
     * 추가상품 삭제
     */
    this.remove_add_goods= function(optionId,addGoodsId) {
        $("#add_goods_display_item_"+optionId+"_"+addGoodsId).remove();

        var addGoodsCnt = $('div[id=\'option_display_item_'+optionId+'\']').find('div[id*=\'add_goods_display_item_\']').length;

        if(addGoodsCnt == 0) $('div[id=\'option_display_item_'+optionId+'\']').find('.add').remove();

        var setAddGoodsPrice =  0;

        $("#option_display_item_"+optionId+" input[name*='add_goods_total_price']").each(function () {
            setAddGoodsPrice += parseFloat($(this).val());
        });

        $("#option_display_item_"+optionId+" input[name='addGoodsPriceSum[]']").val(setAddGoodsPrice);


        if(setCartTabFl =='y') {
            $("#frmCartTabViewLayer .add_goods_display_item_"+optionId+"_"+addGoodsId).remove();
        }
        if (typeof gd_total_calculate !== 'undefined' && $.isFunction(gd_total_calculate))   gd_total_calculate();
    }


    /**
     * 상품 가격 계산
     * @param integer goodsFl 1: 상품 0:추가상품
     * @param integer itemNo 선택상품명
     * @param integer goodsCnt 상품 갯수
     */
    this.goods_calculate = function(frmId,goodsFl,itemNo,goodsCnt) {

        var goodsPrice = parseFloat($(frmId+' input[name="set_goods_price'+setOptNo+'"]').val());

        if(goodsFl)
        {
            var optionTextPrice = 0;
            if(setOptionTextFl =='y') {
                if($(frmId+' input[name="option_text_price'+setOptNo+'_'+itemNo+'"]').length) optionTextPrice = parseFloat($(frmId+' input[name="option_text_price'+setOptNo+'_'+itemNo+'"]').val());
            }

            var optionPrice = parseFloat($(frmId+' input[name="option_price'+setOptNo+'_'+itemNo+'"]').val());

            var optionTotalPrice = (optionTextPrice)+(optionPrice);
            $(frmId+' .option_price_display_' + setOptNo + itemNo).html( gd_money_format(((optionTotalPrice+goodsPrice)*goodsCnt)).format());

            if(setOptNo == "" || setOptNo == undefined) {
                $('#option_display_item_' + itemNo + ' input[name="optionPriceSum[]"]').val((optionPrice * goodsCnt));
                $('#option_display_item_' + itemNo + ' input[name="optionTextPriceSum[]"]').val((optionTextPrice * goodsCnt));
                $("#option_display_item_" + itemNo + " input[name='goodsPriceSum[]']").val((goodsPrice * goodsCnt));
            }else{
                $('#option_display_item_' + setOptNo + '_' + itemNo + ' input[name="optionPriceSum' + setOptNo + '[]"]').val((optionPrice * goodsCnt).toFixed(setDecimal));
                $('#option_display_item_' + setOptNo + '_' + itemNo + ' input[name="optionTextPriceSum' + setOptNo + '[]"]').val((optionTextPrice * goodsCnt).toFixed(setDecimal));
                $("#option_display_item_" + setOptNo + '_' + itemNo + " input[name='goodsPriceSum" + setOptNo + "[]']").val((goodsPrice * goodsCnt).toFixed(setDecimal));
            }

        }
        else if(itemNo.split(setIntDivision).length > 1)
        {
            var tmpStr = itemNo.split(setIntDivision);
            itemNo = tmpStr[0];
            var addGoodsItemNo =  tmpStr[1];
            var addGoodsPrice = parseFloat($(frmId+' input[name="add_goods_price'+setOptNo+'_'+ itemNo+'_'+addGoodsItemNo+'"]').val());
            var addGoodsTotalPrice = parseFloat(addGoodsPrice*goodsCnt);

            if(setOptNo == "" || setOptNo == undefined) {
                $(frmId + ' .add_goods_price_display_' + itemNo + '_' + addGoodsItemNo).html(gd_money_format(addGoodsTotalPrice.toFixed(setDecimal)));
            }else{
                $(frmId + ' .add_goods_price_display_' + setOptNo + '_' + itemNo + '_' + addGoodsItemNo).html(gd_money_format(addGoodsTotalPrice.toFixed(setDecimal)));
            }

            if(setOptNo == "" || setOptNo == undefined) {
                $('#add_goods_display_item_' + itemNo + '_' + addGoodsItemNo + ' input[name*="add_goods_total_price"]').val(addGoodsTotalPrice);
            }else{
                $('input[name*="add_goods_total_price'+setOptNo+'"]').val(addGoodsTotalPrice);
            }

            var setAddGoodsPrice =  0;

            if(setOptNo == "" || setOptNo == undefined) {
                $("#option_display_item_"+itemNo+" input[name*='add_goods_total_price']").each(function () {
                    setAddGoodsPrice += parseFloat($(this).val());
                });
                $("#option_display_item_" + itemNo + " input[name='addGoodsPriceSum[]']").val(setAddGoodsPrice);
            }else{
                $("#option_display_item_" + setOptNo + '_' +itemNo+" input[name*='add_goods_total_price"+setOptNo+"']").each(function () {
                    setAddGoodsPrice += parseFloat($(this).val());
                });
                $("#option_display_item_" + setOptNo + '_' + itemNo + " input[name='addGoodsPriceSum" + setOptNo + "[]']").val(setAddGoodsPrice);
            }
        }

        if(setOptNo == "" || setOptNo == undefined){
            if (typeof gd_total_calculate !== 'undefined' && $.isFunction(gd_total_calculate)) gd_total_calculate();
        }else{
            var funcName = 'total_calculate_'+setOptNo;
            window[funcName]();
        }
    }


    this.init = function(param)
    {
        setOptionTextFl = param.setOptionTextFl;
        setOptNo = param.setOptNo;
        if(setOptNo == undefined) setOptNo = "";
        setOptionDisplayFl = param.setOptionDisplayFl;
        setAddGoodsFl =param.setAddGoodsFl;
        setIntDivision =param.setIntDivision;
        setStrDivision =param.setStrDivision;
        setMileageUseFl =param.setMileageUseFl;
        setCouponUseFl = param.setCouponUseFl;
        setStockFl = param.setStockFl;
        setDecimal = param.setDecimal;
        setOptionFl = param.setOptionFl;
        setGoodsPrice = param.setGoodsPrice;
        setGoodsNo = param.setGoodsNo;
        setMileageFl = param.setMileageFl;
        setControllerName = param.setControllerName;
        setFixedSales = param.setFixedSales;
        setFixedOrderCnt = param.setFixedOrderCnt;
        setOptionPriceFl = param.setOptionPriceFl;
        setStockCnt = parseInt(param.setStockCnt);
        setOriginalMinOrderCnt = parseInt(param.setMinOrderCnt);

        if(param.setGoodsNm != "" && typeof param.setGoodsNm != 'undefined'){
            setGoodsNm = param.setGoodsNm + ': ';
        }else{
            setGoodsNm = '';
        }
        if (setFixedOrderCnt == 'option') {
            setMinOrderCnt = parseInt(param.setMinOrderCnt);
            setMaxOrderCnt = parseInt(param.setMaxOrderCnt);
        }
        if (setFixedSales != 'goods' || (setFixedSales == 'goods' && setOptionFl == 'n' && setOptionTextFl == 'n')) {
            setSalesUnit = parseInt(param.setSalesUnit);
            if (setSalesUnit > setMinOrderCnt) {
                setMinOrderCnt = parseInt(param.setSalesUnit);
            }
        }

        if(param.type == 'cart'){
            noChangeFl = 'y';
        }

    }
    this.initCartTab = function(cartTabFl)
    {
        setCartTabFl = cartTabFl;
    }

    this.max_length_alert = function(inputName) {
        var el = $(inputName);
        var textLength = el.val().length;
        var maxLength = el.attr('maxlength');
        if (textLength >= maxLength ) {
            alert((maxLength-1) + __("자 이상 등록할 수 없습니다."));
        }
    }

}
