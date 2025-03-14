<form class="form-horizontal" id="search-form" method="get" role="form">
<div class="panel panel-primary hidden">

    <div class="panel-heading">
        <h4 class="panel-title">
            <i class="fa fa-search"></i> <?php echo lang("lang.search"); ?>
            <span class="pull-right">
                <a data-toggle="collapse" href="#collapseCashbackReport" class="btn btn-xs btn-primary <?=$this->config->item('default_open_search_panel') ? '' : 'collapsed'?>"></a>
            </span>
        </h4>
    </div>

    <div id="collapseCashbackReport" class="panel-collapse <?=$this->config->item('default_open_search_panel') ? '' : 'collapse in'?>">
        <div class="panel-body">
            <div class="row">
                <div class="col-md-3">
                    <label class="control-label"><?=lang('player.38')?>:</label>
                    <div class="input-group">
                        <input id="search_registration_date" class="form-control input-sm dateInput" data-start="#registration_date_from" data-end="#registration_date_to" data-time="true"/>
                        <span class="input-group-addon input-sm">
                            <input type="checkbox" name="search_reg_date" id="search_reg_date" data-size='mini' <?= $conditions['search_reg_date']  == 'on' ? 'checked="checked"' : '' ?> value='<?php echo $conditions['search_reg_date']?>' />
                        </span>
                        <input type="hidden" name="registration_date_from" id="registration_date_from" value="<?=$conditions['registration_date_from'];?>" />
                        <input type="hidden" name="registration_date_to" id="registration_date_to" value="<?=$conditions['registration_date_to'];?>" />
                    </div>
                </div>
            </div>
            <div class="row">
                <!-- Date -->
                <div class="col-md-3 col-lg-3">
                    <label class="control-label"><?php echo lang('Date'); ?></label>
                    <input id="search_cashback_date" class="form-control input-sm dateInput user-success" data-start="#by_date_from" data-end="#by_date_to" data-time="false" autocomplete="off">
                    <input type="hidden" id="by_date_from" name="by_date_from" value="<?=$conditions['by_date_from'];?>">
                    <input type="hidden" id="by_date_to" name="by_date_to" value="<?=$conditions['by_date_to'];?>">
                </div>
                <div class="col-md-3 col-lg-3 hide">
                    <label class="control-label"><?php echo lang('Enabled date'); ?></label>
                    <input type="checkbox" data-off-text="<?php echo lang('off'); ?>" data-on-text="<?php echo lang('on'); ?>"  name="enable_date" id="enable_date" data-size='mini' value='true' <?php echo $conditions['enable_date'] ? 'checked="checked"' : ''; ?>>
                </div>
                <!-- Paid -->
                <div class="col-md-3 col-lg-3">
                    <label class="control-label"><?php echo lang('paid.cashback'); ?></label>
                    <select name="by_paid_flag" id="by_paid_flag" class="form-control input-sm">
                        <option value="" <?=empty($conditions['by_paid_flag']) ? 'selected' : ''?>>--  <?php echo lang('None'); ?> --</option>
                        <option value="1" <?php echo ($conditions['by_paid_flag'] === '1') ? 'selected' : ''; ?>><?php echo lang('Paid'); ?></option>
                        <option value="0" <?php echo ($conditions['by_paid_flag'] === '0') ? 'selected' : ''; ?> ><?php echo lang('Not pay'); ?></option>
                    </select>
                </div>
                <!-- Player Username -->
                <div class="col-md-3 col-lg-3">
                    <label class="control-label"><?php echo lang('Player Username'); ?></label>
                    <input type="text" name="by_username" id="by_username" class="form-control input-sm" placeholder='<?php echo lang('Enter Username'); ?>'
                    value="<?php echo $conditions['by_username']; ?>"/>
                </div>
                <!-- Player Level -->
                <div class="col-md-3 col-lg-3">
                    <label class="control-label"><?php echo lang('VIP Level'); ?></label>
                    <?php echo form_dropdown('by_player_level', $vipgrouplist, $conditions['by_player_level'], 'id="by_player_level" class="form-control input-sm"') ?>
                </div>
            </div>
            <div class="row">
                <div class="col-md-3 col-lg-3">
                    <label class="control-label"><?php echo lang('Cashback Amount <='); ?></label>
                    <input type="text" name="by_amount_less_than" id="by_amount_less_than" value="<?=$conditions['by_amount_less_than']?>" class="form-control input-sm number_only" placeholder='<?php echo lang('Enter Amount'); ?>'/>
                </div>
                <div class="col-md-3 col-lg-3">
                    <label class="control-label"><?php echo lang('Cashback Amount >='); ?></label>
                    <input type="text" name="by_amount_greater_than" id="by_amount_greater_than" value="<?=$conditions['by_amount_greater_than']?>" class="form-control input-sm number_only" placeholder='<?php echo lang('Enter Amount'); ?>'/>
                </div>
                <?php if($this->utils->isEnabledFeature('enable_friend_referral_cashback') && false){?>
                    <div class="col-md-3 col-lg-3">
                        <label class="control-label"><?php echo lang('Cashback Type'); ?></label>
                        <select name="by_cashback_type" id="by_cashback_type" class="form-control input-sm">
                            <option value="" <?=empty($conditions['by_cashback_type']) ? 'selected' : ''?>>--  <?php echo lang('None'); ?> --</option>
                            <option value="1" <?php echo ($conditions['by_cashback_type'] == Group_level::NORMAL_CASHBACK) ? 'selected' : ''; ?>><?php echo lang('Normal Cashback'); ?></option>
                            <option value="2" <?php echo ($conditions['by_cashback_type'] == Group_level::FRIEND_REFERRAL_CASHBACK) ? 'selected' : ''; ?> ><?php echo lang('Friend Referral Cashback'); ?></option>
                        </select>
                    </div>
                <?php }?>
                <!-- Affiliate Username -->
                <div class="col-md-3 col-lg-3">
                    <label class="control-label"><?php echo lang('Affiliate Username'); ?></label>
                    <input type="text" name="affiliate_username" id="affiliate_username" class="form-control input-sm" placeholder='<?php echo lang('Enter Affiliate Username'); ?>'
                    value="<?php echo $conditions['affiliate_username']; ?>"/>
                </div>
                <!-- Player Tag -->
                <div class="col-md-3 col-lg-3">
                    <label class="control-label"><?=lang('Player Tag')?>:</label>
                    <select name="tag_list[]" id="tag_list" multiple="multiple" class="form-control input-md">
                        <?php if (!empty($tags)): ?>
                            <option value="notag" id="notag" <?=is_array($selected_tags) && in_array('notag', $selected_tags) ? "selected" : "" ?>><?=lang('player.tp12')?></option>
                            <?php foreach ($tags as $tag): ?>
                                <option value="<?=$tag['tagId']?>" <?=is_array($selected_tags) && in_array($tag['tagId'], $selected_tags) ? "selected" : "" ?> ><?=$tag['tagName']?></option>
                            <?php endforeach ?>
                        <?php endif ?>
                    </select>
                </div>
            </div>
            <div class="row">
                <div class="col-lg-12 col-md-12 p-t-15 text-right">
                    <?php if ($this->permissions->checkPermissions('manually_calculate_cashback') && $this->authentication->isSuperAdmin()) {?>
                    <!-- <input class="btn btn-sm btn-primary btn_calc_cashback" type="button" value="<?php echo lang('Manually Calculate Cashback'); ?>" /> -->
                    <?php }?>
                    <?php if ($this->permissions->checkPermissions('manually_pay_cashback')) {?>
                        <input class="btn btn-sm btn_payall m-r-30 btn-portage" type="button" value="<?php echo lang('Pay Today Cashback'); ?>" />
                    <?php }?>
                    <input type="button" id="btnResetFields" value="<?php echo lang('Reset'); ?>" class="btn btn-sm btn-linkwater">
                    <input class="btn btn-sm btn-primary" type="submit" value="<?php echo lang('lang.search'); ?>" />
                </div>
            </div>
        </div>
    </div>
</div>
</form>
        <!--end of Sort Information-->


        <div class="panel panel-primary">
            <div class="panel-heading custom-ph">
                <h4 class="panel-title custom-pt"><i class="icon-bullhorn"></i> <?php echo lang('Cashback Report'); ?></h4>
            </div>
            <div class="panel-body">
                <?php if ($this->permissions->checkPermissions('manually_pay_cashback')) {?>
<!--                 <input class="form-input select_all" type="checkbox" value="true"> <?php echo lang('Select All'); ?>
                <input class="btn btn-sm btn-danger btn_paynow" type="button" value="<?php echo lang('Only Pay Selected'); ?>" />
 -->                <?php }?>
                <!-- result table -->
                <div id="logList" class="table-responsive">
                    <table class="table table-striped table-hover table-condensed" id="report_table" style="width:100%;">
                        <thead>
                            <tr>
                                <th><?php echo lang('Date'); ?></th>
                                <th><?php echo lang('Player Username'); ?></th>
                                <th><?php echo lang('a_header.affiliate'); ?></th>
                                <th><?php echo lang('Player Tag'); ?></th>
                                <th><?php echo lang('VIP Level'); ?></th>
                                <th><?php echo lang('Amount'); ?></th>
                                <th><?php echo lang('Bet Amount'); ?></th>
                                <th><?php echo lang('Original Bet Amount'); ?></th>
                                <th><?php echo lang('paid.cashback'); ?></th>
                                <th><?php echo lang('Game Platform'); ?></th>
                                <th><?php echo lang('Game Type'); ?></th>
                                <th><?php echo lang('Game'); ?></th>
                                <th><?php echo lang('Updated at'); ?></th>
                                <th><?php echo lang('Paid date'); ?></th>
                                <th id="colRegOn"><?=lang('player.38');?></th>
                                <th><?php echo lang('Paid amount'); ?></th>
                                <th><?php echo lang('Withdraw Condition amount'); ?></th>
                                <?php if($this->utils->isEnabledFeature('enable_friend_referral_cashback') && false){?>
                                    <th><?php echo lang('Cashback Type'); ?></th>
                                    <th><?php echo lang('Referred Player for Cashback'); ?></th>
                                <?php }?>
                            </tr>
                        </thead>
                        <tfoot>
                            <tr>
                                <th colspan="3"><?= lang('Subtotal') ?></th>
                                <td colspan="2"></td>
                                <th class="total amount">&mdash;</td>
                                <!-- <td colspan="8"></td>
                                <th class="total paid">&mdash;</td> -->
                            <?php /*

                                <td colspan="<?php echo ($this->utils->isEnabledFeature('enable_friend_referral_cashback') && false) ? 16 : 14 ?>" class="text-right">
                                    <?php echo lang('Total'); ?>: <span id="total_amount">0.00</span><br>
                                </td>
                            */ ?>
                           </tr>
                            <?php if($this->utils->getConfig('enabled_display_cashback_report_total_amount')):?>
                                <tr>
                                    <th class="text-primary" colspan="3"><?= lang('Total') ?></th>
                                    <td colspan="2"></td>
                                    <th class="text-primary text-right" id="total_amount">&mdash;</td>
                                </tr>
                            <?php endif;?>
                        </tfoot>
                    </table>
                </div>
            </div>
            <!--end of result table -->
            <div class="panel-footer"></div>
        </div>
        <?php if($this->utils->isEnabledFeature('export_excel_on_queue')){?>
        <form id="_export_excel_queue_form" class="hidden" method="POST" target="_blank">
        <input name='json_search' type="hidden">
        </form>
        <form id="_export_csv_form" class="hidden" method="POST" target="_blank">
        <input name='json_search' id = "json_csv_search" type="hidden">
        </form>
<?php }?>
<!-- <form method="POST" id="frm_selected_all" action="<?php echo site_url('/vipsetting_management/pay_selected'); ?>">
<input type="hidden" name="selected_id_value" id="selected_id_value">
<input type="hidden" name="redirect_url" id="redirect_url">
</form>
 -->


 <script type="text/template" id="tpl-cashback-detail-row-list">
    <div class="container-fluid cashback-detail-row-container">
        <div class="row amount-row"> <!-- common_cashback_multiple_range_rules.rule_id_246.resultsByTier -->
            <div class="col-sm-4 text-align-right">
                <?=lang('Amount')?>
            </div>

            <div class="col-sm-5">
                ${amount}
            </div>
        </div>
        <div class="row percentage-row">
            <div class="col-sm-4 text-align-right">
                <?=lang('Percentage')?>
            </div>

            <div class="col-sm-5">
                ${percentage}
            </div>
        </div>
        <div class="row deduction-bet-row">
            <div class="col-sm-4 text-align-right">
                <?=lang('Deduction Bet')?>
            </div>

            <div class="col-sm-5">
                ${deduction}
            </div>
        </div>
    </div>
 </script>

 <script type="text/template" id="tpl-bet-detail-row-list">
    <div class="container-fluid bet-detail-row-container">
        <div class="row game_description-row">
            <div class="col-sm-4 text-align-right">
            <?=lang('Game Name')?> <!-- game_description_id -->
            </div>

            <div class="col-sm-5">
            ${game_description_of_platform}
            <!-- KY棋牌 > Poker > Golden Flower Beginner’s Room -->
            <!-- game_description_id -->
            </div>
        </div>
        <div class="row bet-amount">
            <div class="col-sm-4 text-align-right">
                <?=lang('Amount')?>
            </div>

            <div class="col-sm-5">
            ${betting_total} <!-- betting_total -->
            </div>
        </div>
    </div>
 </script>

 <!-- cashbackAmount Modal Start -->
<div class="modal fade" id="cashbackAmountDetail" tabindex="-1" role="dialog" aria-labelledby="cashbackAmountDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="cashbackAmountModalLabel"><?=lang('Cashback Amount Detail')?></h4>
            </div>
            <div class="modal-body cashbackAmountModalBody">

                <div class="container-fluid container-loader">
                    <div class="row" style="background-color: #eee;">
                        <div class="col-md-offset-4 col-md-4">
                            <div class="loader"></div>
                        </div>
                    </div>
                </div>

                <div class="container-fluid">
                    <div class="row cashback-detail-row">
                        <div class="col-sm-12 text-align-center">
                            <b>
                                <?=lang('Cashback Detail')?> <!-- common_cashback_multiple_range_rules -->
                            </b>
                        </div>
                    </div>
                    <div class="container-fluid cashback-detail-row-container">
                        <div class="row amount-row"> <!-- common_cashback_multiple_range_rules.rule_id_246.resultsByTier -->
                            <div class="col-sm-4 text-align-right">
                                <?=lang('Amount')?>
                            </div>

                            <div class="col-sm-5">
                            5
                            </div>
                        </div>
                        <div class="row percentage-row">
                            <div class="col-sm-4 text-align-right">
                                <?=lang('Percentage')?>
                            </div>

                            <div class="col-sm-5">
                            0.590
                            </div>
                        </div>
                        <div class="row deduction-bet-row">
                            <div class="col-sm-4 text-align-right">
                                <?=lang('Deduction Bet')?>
                            </div>

                            <div class="col-sm-5">
                            714.285
                            </div>
                        </div>
                    </div> <!-- EOF .cashback-detail-row-container -->
                </div>

                &nbsp;

                <div class="container-fluid">
                    <div class="row bet-detail-row">
                        <div class="col-sm-12 text-align-center">
                            <b>
                                <?=lang('Bet Detail')?> <!-- total_player_game_hour -->
                            </b>
                        </div>
                    </div>

                    <div class="container-fluid bet-detail-row-container">
                        <div class="row game_description-row">
                            <div class="col-sm-4 text-align-right">
                            <?=lang('Game Name')?> <!-- game_description_id -->
                            </div>

                            <div class="col-sm-5">
                            KY棋牌 > Poker > Golden Flower Beginner’s Room <!-- game_description_id -->
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-sm-4 text-align-right">
                                <?=lang('Amount')?>
                            </div>

                            <div class="col-sm-5">
                            5 <!-- betting_total -->
                            </div>
                        </div>
                    </div> <!-- EOF .bet-detail-row-container -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-dismiss="modal"><?=lang('Confirm')?></button>
            </div>
        </div>
    </div>
</div>
<!-- cashbackAmount Modal End -->


<script type="text/javascript">
    // $(".letters_numbers_only").keydown(function (e) {
    //         var code = e.keyCode || e.which;
    //         // Allow: backspace, delete, tab, escape, enter and .
    //         if ($.inArray(code, [46, 8, 9, 27, 32, 13, 110, 190]) !== -1 ||
    //              // Allow: Ctrl+A
    //         ( e.ctrlKey === true) || ( e.metaKey === true) ||
    //              // Allow: home, end, left, right, down, up
    //             (code >= 35 && code <= 40)) {
    //                  // let it happen, don't do anything
    //                  return;
    //         }
    //         // Ensure that it is a number and stop the keypress
    //         if ((e.shiftKey || (code < 48 || code > 57)) && (code < 96 || code > 105) && (e.ctrlKey === true || code < 65 || code > 90)) {
    //             e.preventDefault();
    //         }
    //     });

    function renderSelectAll(){
        var selectedAll=$(".chk_row").length==$(".chk_row:checked").length;

        $(".select_all").prop('checked', selectedAll);
    }

    function changeSelectRow(){

        renderSelectAll();

    }

    $(document).ready(function(){

        $('#tag_list').multiselect({
            enableFiltering: true,
            enableCaseInsensitiveFiltering: true,
            includeSelectAllOption: true,
            selectAllJustVisible: false,
            buttonWidth: '100%',
            buttonClass: 'btn btn-sm btn-default',
            buttonText: function(options, select) {
                if (options.length === 0) {
                    return '<?=lang('Select Tags');?>';
                } else {
                    var labels = [];
                    options.each(function() {
                        if ($(this).attr('label') !== undefined) {
                            labels.push($(this).attr('label'));
                        }
                        else {
                            labels.push($(this).html());
                        }
                    });
                    return labels.join(', ') + '';
                }
            }
        });

        var dateFrom = $("#by_date_from").val();
        var dateTo = $("#by_date_from").val();
        // $('#search-form').submit( function(e) {
        //     e.preventDefault();
        //     dataTable.ajax.reload();
        // });
        var arrayfrom = dateFrom.split(' ');
        var arrayto = dateTo.split(' ');

        var d = new Date();
        var month = d.getMonth()+1;
        var day = d.getDate();

        var datetoday = d.getFullYear() + '-' +
        (month<10 ? '0' : '') + month + '-' +
        (day<10 ? '0' : '') + day;

        $('#btnResetFields').click(function() {
            $("#by_date_from").val(datetoday);
            $("#by_date_from").val(datetoday);
            $("#enable_date").prop('checked', true);
            $("#by_paid_flag").val("");
            $("#by_username").val("");
            $("#by_player_level").val("");
            $("#by_amount_less_than").val("");
            $("#by_amount_greater_than").val("");
            $("#by_cashback_type").val("");
            $("#search_cashback_date").val($("#by_date_from").val() +" to "+ $("#by_date_from").val());
        });


        $(".btn_calc_cashback").click(function(){
            if(confirm("<?php echo lang('Do you want manually calculate cashback?'); ?>")){
                // $("#return_url").val(window.location.href);
                $("#search-form").attr("method", "POST")
                    .attr("action", "<?php echo site_url('/vipsetting_management/calc_cashback'); ?>")
                    .submit();
            }
        });

        $(".btn_payall").click(function(){
            //confirm
            if(confirm("<?php echo lang('Do you want pay all unpaid cashback?'); ?>")){
                // $("#return_url").val(window.location.href);
                $("#search-form").attr("method", "POST")
                    .attr("action", "<?php echo site_url('/vipsetting_management/pay_all_cashback'); ?>")
                    .submit();
            }
        });

        $(".btn_paynow").click(function(){
            //pay now
            if(confirm("<?php echo lang('Do you want pay selected unpaid cashback?'); ?>")){
                var selectedIdArr=[];
                $(".chk_row:checked").each(function(){
                    selectedIdArr.push($(this).val());
                });

                $("#selected_id_value").val(selectedIdArr.join(','));
                // utils.safelog(selectedIdArr);
                $("#redirect_url").val(window.location.href);
                $("#frm_selected_all").submit();
            }
        });

        $(".select_all").change(function(){
            //
            $(".chk_row").prop("checked", $(this).prop("checked"));
        });

        $('#search-form input[type="text"],#search-form input[type="number"],#search-form input[type="email"]').keypress(function (e) {
            if (e.which == 13) {
                $('#search-form').trigger('submit');
            }
        });

        $('body').on('click', 'div.btn[data-appoint_id]', function(e){
            reportManagement.clicked_cashbackAmountDetail(e);
        });

        $("#search_reg_date").change(function() {
            if($('#search_reg_date').is(':checked')) {
                $('#search_registration_date').prop('disabled',false);
                $('#registration_date_from').prop('disabled',false);
                $('#registration_date_to').prop('disabled',false);
            }else{
                $('#search_registration_date').prop('disabled',true);
                $('#registration_date_from').prop('disabled',true);
                $('#registration_date_to').prop('disabled',true);
            }
        }).trigger('change');

        $('body').on('submit', 'form#search-form', function(){
            // Detect Checkbox for non-checked to GET param.
            if($('#search_reg_date').prop('checked') == false){
                $('#search_reg_date').val('off');
                $('#search_reg_date').prop('checked', false);
            }else{
                $('#search_reg_date').val('on');
                $('#search_reg_date').prop('checked', true);
            }
            return true;
        });

        var _dataTable = $('#report_table').DataTable({
            <?php if( ! empty($enable_freeze_top_in_list) ): ?>
            scrollY:        1000,
            scrollX:        true,
            deferRender:    true,
            scroller:       true,
            scrollCollapse: true,
            <?php endif; // EOF if( ! empty($enable_freeze_top_in_list) ):... ?>

            lengthMenu: JSON.parse('<?=json_encode($this->utils->getConfig('default_datatable_lengthMenu'))?>'),
            pageLength: <?=$this->utils->getDefaultItemsPerPage()?>,
            autoWidth: false,
            searching: false,
            dom: "<'panel-body'<'pull-right'B><'pull-right progress-container hidden-xs'>l><'dt-information-summary1 text-info pull-left' i>t<'panel-body'<'pull-right'p><'dt-information-summary2 text-info pull-left' i>>",
            "responsive": {
                details: {
                    type: 'column'
                }
            },
            buttons: [
                {
                    extend: 'colvis',
                    postfixButtons: [ 'colvisRestore' ],
                    className: 'btn-linkwater',
                }
                <?php if ($export_report_permission) {?>
                ,{
                    text: "<?php echo lang('CSV Export'); ?>",
                    className:'btn btn-sm btn-portage',
                    action: function ( e, dt, node, config ) {
                    //  var d = {'extra_search':$('#search-form').serializeArray(), 'draw':1, 'length':-1, 'start':0};
                       var d = {'extra_search': $('#search-form').serializeArray(), 'export_format': 'csv', 'export_type': export_type,
                            'draw':1, 'length':-1, 'start':0};
                        <?php if($this->utils->isEnabledFeature('export_excel_on_queue')){?>
                                    $("#_export_excel_queue_form").attr('action', site_url('/export_data/cashback_report'));
                                    $("#_export_excel_queue_form [name=json_search]").val(JSON.stringify(d));
                                    $("#_export_excel_queue_form").submit();
                        <?php }?>
                    }
                }
                <?php }
?>
            ],
            columnDefs: [
                { sortable: false, targets: [ 1 ] },
                { className: 'text-right', targets: [ 4,5,6,13,14,15,16 ] }
                // { visible: false, targets: [ 8 ] }
            ],
            "order": [ 0, 'desc' ],
            processing: true,
            serverSide: true,
            drawCallback : function( settings ) {
                <?php if( ! empty($enable_freeze_top_in_list) ): ?>
                var _min_height = $('.dataTables_scrollBody').find('.table tbody tr').height();
                _min_height = _min_height* 5; // limit min height: 5 rows
                var _scrollBodyHeight = window.innerHeight;
                _scrollBodyHeight -= $('.navbar-fixed-top').height();
                _scrollBodyHeight -= $('.dataTables_scrollHead').height();
                _scrollBodyHeight -= $('.dataTables_scrollFoot').height();
                _scrollBodyHeight -= $('.dataTables_paginate').closest('.panel-body').height();
                _scrollBodyHeight -= 44;// buffer
                if(_scrollBodyHeight > _min_height ){
                    $('.dataTables_scrollBody').css({'max-height': _scrollBodyHeight+ 'px'});
                    _dataTable._alignedInTfoot(settings);
                }
                <?php endif; // EOF if( ! empty($enable_freeze_top_in_list) ):... ?>
            },
            ajax: function (data, callback, settings) {
                data.extra_search = $('#search-form').serializeArray();
                $.post(base_url + "api/cashback_report", data, function(data) {
                    <?php if($this->utils->getConfig('enabled_display_cashback_report_total_amount')):?>
                        $('#total_amount').text(numeral(data.summary[0].total_amount).format('0.000'));
                    <?php endif;?>
                    callback(data);
                    if ( $('#report_table').DataTable().rows( { selected: true } ).indexes().length === 0 ) {
                        $('#report_table').DataTable().buttons().disable();
                    }
                    else {
                        $('#report_table').DataTable().buttons().enable();
                    }
                },'json');
            },
            // OGP-22311: build subtotal for columns 'amount' and 'paid' in footer
            footerCallback: function (tfoot, data, start, end, display) {
                var api = this.api();
                var sum = { amount: 0, paid: 0 };
                for (var i in data) {
                    var r = data[i];
                    // column 4: amount, column 13: paid amount
                    var r_amount = numeral(r[5]).value();//, r_paid = numeral(r[13]).value();

                    var amount_cell = r[5];
                    if( ! $.isNumeric(amount_cell) ){ // for parse contains the thousands separator. e.q. "2,132.34"
                        r_amount = numeral(amount_cell).value();
                    }
                    if( $(amount_cell).text() != '') { // for parse contains html tags, '<div class="btn btn-xs btn-toolbar" data-appoint_id="566596"><span class="glyphicon glyphicon-list-alt"></span></div>&nbsp;<i class="text-success">140.40</i>'
                        r_amount = numeral($(amount_cell).text()).value();
                    }
                    // console.log('amount', r_amount); // , 'paid', r_paid);
                    sum.amount += r_amount;
                }
                var tfooter = $(tfoot);
                $(tfooter).closest('tfoot').find('.total.amount' ).text(numeral(sum.amount   ).format('0.000'));
            },

        });
        _dataTable._alignedInTfoot = function(settings){

            var is_clone_style = true;
            var _th_style = []; //style

            /// work around for some columns are not aligned in tfoot.
            // To clone the columns, $('#myTable_wrapper > div.dataTable-instance > div.dataTables_scroll > div.dataTables_scrollHead > div > table > thead > tr')
            // and add the specified class, "scroll-head-cloned"
            // into the first of footer in the list.
            //
            var _responsive_in_dataTable$El = $(settings.nTable).closest('.table-responsive');
            if(is_clone_style){
                _responsive_in_dataTable$El.find('div.dataTables_scrollHead > div > table > thead > tr').find('th').each(function(index){
                    var _theTh$El = $(this);
                    _th_style[index] = _theTh$El.attr('style');
                });

                if(_responsive_in_dataTable$El.find('.dataTables_scrollBody table tbody tr[role="row"]').length > 0){
                    _responsive_in_dataTable$El.find('.dataTables_scrollBody table tbody tr[role="row"]').find('td').each(function(index){
                        var _theTd$El = $(this);
                        _th_style[index] = 'width: '+ _theTd$El.width() +'px';
                    });
                }

            } // EOF if(is_clone_style){...


            var _clonedClass = 'scroll-head-cloned'; // the class also defined in CSS
            if( _responsive_in_dataTable$El.find('.'+ _clonedClass).length > 0|| true){
                // if its already exists, destroy for refresh.
                _responsive_in_dataTable$El.find('.'+ _clonedClass).remove();
            }
            if( _responsive_in_dataTable$El.find('.'+ _clonedClass).length == 0){
                var _fields_in_scrollHead$El = _responsive_in_dataTable$El.find('div.dataTables_scrollHead > div > table > thead > tr').clone();
                _fields_in_scrollHead$El.addClass(_clonedClass)
                _fields_in_scrollHead$El.find('th[id]').removeProp('id');
                _fields_in_scrollHead$El.find('th[id]').removeAttr('id');

                if(is_clone_style){
                    _fields_in_scrollHead$El.find('th').each(function(index){
                        var _theTh$El = $(this);
                        _theTh$El.attr('style',_th_style[index]);
                        _theTh$El.prop('style',_th_style[index]);
                        _theTh$El.html('&nbsp;');
                    });
                } // EOF if(is_clone_style){...

                // renameElement('')
                // _fields_in_scrollHead$El.find('th').
                _responsive_in_dataTable$El.find('div.dataTables_scrollFoot > div > table > tfoot').prepend( _fields_in_scrollHead$El );
            }

        }; // EOF dataTable._alignedInTfoot()...
        _dataTable.on( 'column-visibility.dt', function ( e, settings, column, state ) {
            <?php if( ! empty($enable_freeze_top_in_list) ): ?>
            _dataTable._alignedInTfoot(settings);
            <?php endif; // EOF if( ! empty($enable_freeze_top_in_list) ):... ?>
        });

    });

</script>

<style>
.text-align-center {
    text-align:center;
}
.text-align-right {
    text-align:right;
}

.cashbackAmountModalBody>.container-fluid .row:nth-child(even){
  background-color: #efefef;
}


.bet-detail-row-container,.cashback-detail-row-container {
    border-style: solid;
    border-width: 1px;
    border-color: #ccc;
}


/* LOADER 1 */

.cashbackAmountModalBody .loader {
  margin: 4em auto;
  font-size: 24px;
  width: 1em;
  height: 1em;
  border-radius: 50%;
  position: relative;
  text-indent: -9999em;
  -webkit-animation: load3 1.1s infinite ease;
  animation: load3 1.1s infinite ease;
}
@-webkit-keyframes load3 {
  0%,
  100% {
    box-shadow: 0em -2.6em 0em 0em #ffffff, 1.8em -1.8em 0 0em rgba(255, 255, 255, 0.2), 2.5em 0em 0 0em rgba(255, 255, 255, 0.2), 1.75em 1.75em 0 0em rgba(255, 255, 255, 0.2), 0em 2.5em 0 0em rgba(255, 255, 255, 0.2), -1.8em 1.8em 0 0em rgba(255, 255, 255, 0.2), -2.6em 0em 0 0em rgba(255, 255, 255, 0.5), -1.8em -1.8em 0 0em rgba(255, 255, 255, 0.7);
  }
  12.5% {
    box-shadow: 0em -2.6em 0em 0em rgba(255, 255, 255, 0.7), 1.8em -1.8em 0 0em #ffffff, 2.5em 0em 0 0em rgba(255, 255, 255, 0.2), 1.75em 1.75em 0 0em rgba(255, 255, 255, 0.2), 0em 2.5em 0 0em rgba(255, 255, 255, 0.2), -1.8em 1.8em 0 0em rgba(255, 255, 255, 0.2), -2.6em 0em 0 0em rgba(255, 255, 255, 0.2), -1.8em -1.8em 0 0em rgba(255, 255, 255, 0.5);
  }
  25% {
    box-shadow: 0em -2.6em 0em 0em rgba(255, 255, 255, 0.5), 1.8em -1.8em 0 0em rgba(255, 255, 255, 0.7), 2.5em 0em 0 0em #ffffff, 1.75em 1.75em 0 0em rgba(255, 255, 255, 0.2), 0em 2.5em 0 0em rgba(255, 255, 255, 0.2), -1.8em 1.8em 0 0em rgba(255, 255, 255, 0.2), -2.6em 0em 0 0em rgba(255, 255, 255, 0.2), -1.8em -1.8em 0 0em rgba(255, 255, 255, 0.2);
  }
  37.5% {
    box-shadow: 0em -2.6em 0em 0em rgba(255, 255, 255, 0.2), 1.8em -1.8em 0 0em rgba(255, 255, 255, 0.5), 2.5em 0em 0 0em rgba(255, 255, 255, 0.7), 1.75em 1.75em 0 0em rgba(255, 255, 255, 0.2), 0em 2.5em 0 0em rgba(255, 255, 255, 0.2), -1.8em 1.8em 0 0em rgba(255, 255, 255, 0.2), -2.6em 0em 0 0em rgba(255, 255, 255, 0.2), -1.8em -1.8em 0 0em rgba(255, 255, 255, 0.2);
  }
  50% {
    box-shadow: 0em -2.6em 0em 0em rgba(255, 255, 255, 0.2), 1.8em -1.8em 0 0em rgba(255, 255, 255, 0.2), 2.5em 0em 0 0em rgba(255, 255, 255, 0.5), 1.75em 1.75em 0 0em rgba(255, 255, 255, 0.7), 0em 2.5em 0 0em #ffffff, -1.8em 1.8em 0 0em rgba(255, 255, 255, 0.2), -2.6em 0em 0 0em rgba(255, 255, 255, 0.2), -1.8em -1.8em 0 0em rgba(255, 255, 255, 0.2);
  }
  62.5% {
    box-shadow: 0em -2.6em 0em 0em rgba(255, 255, 255, 0.2), 1.8em -1.8em 0 0em rgba(255, 255, 255, 0.2), 2.5em 0em 0 0em rgba(255, 255, 255, 0.2), 1.75em 1.75em 0 0em rgba(255, 255, 255, 0.5), 0em 2.5em 0 0em rgba(255, 255, 255, 0.7), -1.8em 1.8em 0 0em #ffffff, -2.6em 0em 0 0em rgba(255, 255, 255, 0.2), -1.8em -1.8em 0 0em rgba(255, 255, 255, 0.2);
  }
  75% {
    box-shadow: 0em -2.6em 0em 0em rgba(255, 255, 255, 0.2), 1.8em -1.8em 0 0em rgba(255, 255, 255, 0.2), 2.5em 0em 0 0em rgba(255, 255, 255, 0.2), 1.75em 1.75em 0 0em rgba(255, 255, 255, 0.2), 0em 2.5em 0 0em rgba(255, 255, 255, 0.5), -1.8em 1.8em 0 0em rgba(255, 255, 255, 0.7), -2.6em 0em 0 0em #ffffff, -1.8em -1.8em 0 0em rgba(255, 255, 255, 0.2);
  }
  87.5% {
    box-shadow: 0em -2.6em 0em 0em rgba(255, 255, 255, 0.2), 1.8em -1.8em 0 0em rgba(255, 255, 255, 0.2), 2.5em 0em 0 0em rgba(255, 255, 255, 0.2), 1.75em 1.75em 0 0em rgba(255, 255, 255, 0.2), 0em 2.5em 0 0em rgba(255, 255, 255, 0.2), -1.8em 1.8em 0 0em rgba(255, 255, 255, 0.5), -2.6em 0em 0 0em rgba(255, 255, 255, 0.7), -1.8em -1.8em 0 0em #ffffff;
  }
}
@keyframes load3 {
  0%,
  100% {
    box-shadow: 0em -2.6em 0em 0em #ffffff, 1.8em -1.8em 0 0em rgba(255, 255, 255, 0.2), 2.5em 0em 0 0em rgba(255, 255, 255, 0.2), 1.75em 1.75em 0 0em rgba(255, 255, 255, 0.2), 0em 2.5em 0 0em rgba(255, 255, 255, 0.2), -1.8em 1.8em 0 0em rgba(255, 255, 255, 0.2), -2.6em 0em 0 0em rgba(255, 255, 255, 0.5), -1.8em -1.8em 0 0em rgba(255, 255, 255, 0.7);
  }
  12.5% {
    box-shadow: 0em -2.6em 0em 0em rgba(255, 255, 255, 0.7), 1.8em -1.8em 0 0em #ffffff, 2.5em 0em 0 0em rgba(255, 255, 255, 0.2), 1.75em 1.75em 0 0em rgba(255, 255, 255, 0.2), 0em 2.5em 0 0em rgba(255, 255, 255, 0.2), -1.8em 1.8em 0 0em rgba(255, 255, 255, 0.2), -2.6em 0em 0 0em rgba(255, 255, 255, 0.2), -1.8em -1.8em 0 0em rgba(255, 255, 255, 0.5);
  }
  25% {
    box-shadow: 0em -2.6em 0em 0em rgba(255, 255, 255, 0.5), 1.8em -1.8em 0 0em rgba(255, 255, 255, 0.7), 2.5em 0em 0 0em #ffffff, 1.75em 1.75em 0 0em rgba(255, 255, 255, 0.2), 0em 2.5em 0 0em rgba(255, 255, 255, 0.2), -1.8em 1.8em 0 0em rgba(255, 255, 255, 0.2), -2.6em 0em 0 0em rgba(255, 255, 255, 0.2), -1.8em -1.8em 0 0em rgba(255, 255, 255, 0.2);
  }
  37.5% {
    box-shadow: 0em -2.6em 0em 0em rgba(255, 255, 255, 0.2), 1.8em -1.8em 0 0em rgba(255, 255, 255, 0.5), 2.5em 0em 0 0em rgba(255, 255, 255, 0.7), 1.75em 1.75em 0 0em rgba(255, 255, 255, 0.2), 0em 2.5em 0 0em rgba(255, 255, 255, 0.2), -1.8em 1.8em 0 0em rgba(255, 255, 255, 0.2), -2.6em 0em 0 0em rgba(255, 255, 255, 0.2), -1.8em -1.8em 0 0em rgba(255, 255, 255, 0.2);
  }
  50% {
    box-shadow: 0em -2.6em 0em 0em rgba(255, 255, 255, 0.2), 1.8em -1.8em 0 0em rgba(255, 255, 255, 0.2), 2.5em 0em 0 0em rgba(255, 255, 255, 0.5), 1.75em 1.75em 0 0em rgba(255, 255, 255, 0.7), 0em 2.5em 0 0em #ffffff, -1.8em 1.8em 0 0em rgba(255, 255, 255, 0.2), -2.6em 0em 0 0em rgba(255, 255, 255, 0.2), -1.8em -1.8em 0 0em rgba(255, 255, 255, 0.2);
  }
  62.5% {
    box-shadow: 0em -2.6em 0em 0em rgba(255, 255, 255, 0.2), 1.8em -1.8em 0 0em rgba(255, 255, 255, 0.2), 2.5em 0em 0 0em rgba(255, 255, 255, 0.2), 1.75em 1.75em 0 0em rgba(255, 255, 255, 0.5), 0em 2.5em 0 0em rgba(255, 255, 255, 0.7), -1.8em 1.8em 0 0em #ffffff, -2.6em 0em 0 0em rgba(255, 255, 255, 0.2), -1.8em -1.8em 0 0em rgba(255, 255, 255, 0.2);
  }
  75% {
    box-shadow: 0em -2.6em 0em 0em rgba(255, 255, 255, 0.2), 1.8em -1.8em 0 0em rgba(255, 255, 255, 0.2), 2.5em 0em 0 0em rgba(255, 255, 255, 0.2), 1.75em 1.75em 0 0em rgba(255, 255, 255, 0.2), 0em 2.5em 0 0em rgba(255, 255, 255, 0.5), -1.8em 1.8em 0 0em rgba(255, 255, 255, 0.7), -2.6em 0em 0 0em #ffffff, -1.8em -1.8em 0 0em rgba(255, 255, 255, 0.2);
  }
  87.5% {
    box-shadow: 0em -2.6em 0em 0em rgba(255, 255, 255, 0.2), 1.8em -1.8em 0 0em rgba(255, 255, 255, 0.2), 2.5em 0em 0 0em rgba(255, 255, 255, 0.2), 1.75em 1.75em 0 0em rgba(255, 255, 255, 0.2), 0em 2.5em 0 0em rgba(255, 255, 255, 0.2), -1.8em 1.8em 0 0em rgba(255, 255, 255, 0.5), -2.6em 0em 0 0em rgba(255, 255, 255, 0.7), -1.8em -1.8em 0 0em #ffffff;
  }
}

/* EOF LOADER 1 */

.scroll-head-cloned:not(.dbg) > th {
    line-height: 0 !important;
    overflow: hidden;
    padding-top: 0 !important;
    padding-bottom: 0 !important;
}

</style>