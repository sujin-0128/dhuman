<script type="text/javascript">
    $(document).ready(function () {
        // 삭제 
        $('button.checkDelete').click(function () {
            var chkCnt = $('input[name*="sno["]:checkbox:checked').length;
            if (chkCnt == 0) {
                alert('선택된 그룹이 없습니다.');
                return;
            }

            dialog_confirm('선택한 ' + chkCnt + '개 그룹을  정말로 삭제하시겠습니까?\n삭제시 정보는 복구 되지 않습니다.', function (result) {
                if (result) {
                    $('#frmList input[name=\'mode\']').val('group_delete');
                    $('#frmList').attr('method', 'post');
                    $('#frmList').attr('action', './discount_bundle_group_ps.php');
                    $('#frmList').submit();
                }
            });

        });

        $('#frmList').validate({
            dialog: false,
            submitHandler: function (form) {
                form.target = 'ifrmProcess';
                dialog_confirm('선택한 게시판을 삭제하시겠습니까?\n\r영구 삭제되어 복원 불가능합니다.', function (result) {
                    if (result) {
                        form.submit();
                    }
                });

            },
            rules: {
                'sno[]': {
                    required: true
                }
            },
            messages: {
                'sno[]': {
                    required: '선택한 게시판이 없습니다.'
                },

            },
        });

        // 등록
        $('.js-register').click(function () {
            location.href = 'discount_bundle_group_register.php';
        });

        $('select[name=\'pageNum\']').change(function () {
            $('#frmSearchBase').submit();
        });

        $('select[name=\'sort\']').change(function () {
            $('#frmSearchBase').submit();
        });

        
        // 관리자 접속 후 사용자화면 클릭했을경우 fl추가
        $('.user-board').click(function (){
            $.post('./article_ps.php', {
                'mode': 'userBoardChk',
                'fl': $(this).data('fl')
            }, function (data) {
                console.log(data);
            });
        });

        //검색어 변경 될 때 placeHolder 교체 및 검색 종류 변환 및 검색 종류 변환
        var searchKeyword = $('#frmSearchBase input[name="keyword"]');
        var searchKind = $('#frmSearchBase #searchKind');
        setKeywordPlaceholder(searchKeyword, searchKind);
        searchKind.change(function (e) {
            setKeywordPlaceholder(searchKeyword, searchKind);
        });
    });
</script>
<div class="page-header js-affix">
    <h3><?=end($naviMenu->location); ?></h3>
    <div class="btn-group">
        <input type="button" id="checkRegister" value="결합 할인 그룹 등록" class="btn btn-red-line js-register"/>
    </div>
</div>

<form id="frmSearchBase" name="frmSearchBase" method="get" class="js-form-enter-submit">
    <div class="table-title gd-help-manual">
        결합 할인 상품 검색
    </div>
    <div class="search-detail-box">
        <table class="table table-cols">
            <colgroup>
                <col class="width-sm"/>
                <col/>
            </colgroup>
            <tr>
                <th>검색어</th>
                <td><div class="form-inline">
                        <?=gd_select_box('key', 'key', $search['combineSearch'], null, $search['key'], null); ?>
                        <input type="text" name="keyword" value="<?=$search['keyword']; ?>" class="form-control"/>
                        </div>
                </td>
            </tr>
            <!-- <tr>
                <th>결합 할인 실패 시</th>
                <td><div class="form-inline">
                        <label class="radio-inline"><input type="radio" name="allowNoBundleSale" value="all" <?=gd_isset($checked['allowNoBundleSale']['all']); ?>/>전체</label>
                        <label class="radio-inline"><input type="radio" name="allowNoBundleSale" value="y" <?=gd_isset($checked['allowNoBundleSale']['y']); ?>/>판매함</label>
                        <label class="radio-inline"><input type="radio" name="allowNoBundleSale" value="n" <?=gd_isset($checked['allowNoBundleSale']['n']); ?>/>판매안함</label>
                </td>
            </tr> -->
            <!-- <tr>
                <th >기간검색</th>
                <td> <div class="form-inline">
                        <select name="searchDateFl" class="form-control">
                            <option value="regDt" <?=gd_isset($selected['searchDateFl']['regDt']); ?>>등록일</option>
                            <option value="modDt" <?=gd_isset($selected['searchDateFl']['modDt']); ?>>수정일</option>
                        </select>
 
                        <div class="input-group js-datepicker">
                            <input type="text" class="form-control width-xs" name="searchDate[]" value="<?=$search['searchDate'][0]; ?>" >
                    <span class="input-group-addon">
                        <span class="btn-icon-calendar">
                        </span>
                    </span>
                        </div>
 
                        ~  <div class="input-group js-datepicker">
                            <input type="text" class="form-control width-xs" name="searchDate[]" value="<?=$search['searchDate'][1]; ?>" >
                    <span class="input-group-addon">
                        <span class="btn-icon-calendar">
                        </span>
                    </span>
                        </div>
                        <?=gd_search_date($search['searchPeriod'])?>
                    </div>
                </td>
            </tr> -->
        </table>
    </div>
 
 
    <div class="table-btn">
        <input type="submit" value="검색" class="btn btn-lg btn-black">
    </div>
 
    <div class="table-header">
        <div class="pull-left">
            검색 <strong><?=number_format($page->recode['total']);?></strong>개 /
            전체 <strong><?=number_format($page->recode['amount']);?></strong>개
        </div>
        <div class="pull-right form-inline">
            <?=gd_select_box('sort', 'sort', $search['sortList'], null, $search['sort']); ?>
            <?=gd_select_box('pageNum', 'pageNum', gd_array_change_key_value([10, 20, 30, 40, 50, 60, 70, 80, 90, 100, 200, 300, 500]), '개 보기', Request::get()->get('pageNum'), null); ?>
        </div>
    </div>
 
</form>


<form id="frmList" action="" method="get" target="ifrmProcess">
    <input type="hidden" name="mode" value="">
        <table class="table table-rows">
            <thead>
        <tr>
            <th class="width3p center"><input type="checkbox" class="js-checkall" data-target-name="sno"></th>
            <th class="width5p">번호</th>
            <th >그룹코드</th>
            <th >그룹명</th>
            <!-- <th class="width15p">결합실패 실패 시</th> -->
            <th class="width15p">결합실패 팝업노출</th>
            <th class="width10p">장바구니 팝업노출</th>
            <th class="width10p">추가상품 개수</th>
            <th class="width5p">등록일/수정일</th>
            <th class="width5p">수정</th>
        </tr>
        </thead>
        <tbody>
        <?php
        if (gd_isset($data)) {
 
            foreach ($data as $key => $val) {
                $arrAllowNoBundleSale = array('y' => '판매함', 'n' => '판매안함');
                $arrShowNoBundlePopup = array('y' => '사용', 'n' => '미사용');
                $arrPreCartBundlePopup = array('y' => '사용', 'n' => '미사용');
                ?>
 
                <tr>
                    <td class="center"><input type="checkbox" name="sno[<?=$val['sno']; ?>]" value="<?=$val['sno']; ?>"/></td>
                    <td class="center number"><?=number_format($page->idx--); ?></td>
                    <td  class="center"><?=$val['groupCd']; ?><input type="hidden" name="groupCd[<?=$val['sno']; ?>]" value="<?=$val['groupCd']?>"></td>
                    <td class="center"><?=$val['groupNm']; ?></td>
                    <!-- <td  class="center"><?=$arrAllowNoBundleSale[$val['allowNoBundleSale']]?></td> -->
                    <td  class="center"><?=$arrShowNoBundlePopup[$val['showNoBundlePopup']]?></td>
                    <td class="center"><?=$arrPreCartBundlePopup[$val['preCartBundlePopup']]?></td>
                    <td class="center"><?=$val['goodsCount']; ?></td>
                    <td class="center date"><?=gd_date_format('Y-m-d', $val['regDt']); ?><?php if ($val['modDt']) {
                            echo "<br/>" . gd_date_format('Y-m-d', $val['modDt']);
                        } ?></td>
                    <td class="center padlr10"><a
                                href="./discount_bundle_group_register.php?sno=<?=$val['sno']; ?>" class="btn btn-white btn-xs">수정</a></span>
                    </td>
                </tr>
                <?php
            }
        } else {
            ?>
            <tr>
                <td class="center" colspan="11">검색된 정보가 없습니다.</td>
            </tr>
            <?php
        }
        ?>
        </tbody>
    </table>
 
    <div class="table-action">
        <div class="pull-left">
            <button type="button" class="btn btn-white checkDelete">선택 삭제</button>
        </div>
    </div>
 
</form>

<div class="center"><?=$page->getPage(); ?></div>