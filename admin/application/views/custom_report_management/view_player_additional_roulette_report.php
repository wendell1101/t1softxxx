<style>
    .font-bold{
        font-weight: bold;
    }
    .margin-bottom-n20px {
        margin-bottom: -20px;
    }
    .padding-top-6px {
        padding-top: 6px !important;
    }

    /*
    fixedColumns
    Ref. to  https://datatables.net/extensions/scroller/examples/initialisation/fixedColumns.html
    */
    /* .dtfc-fixed-left {
        background-color:#FEFEFE;
        z-index: 1;
    } */

    #myTable tbody tr[role="row"] td table {
        border-top: 1px solid #808080 !important;
    }
    #myTable tbody tr[role="row"] td table tbody th,
    #myTable tbody tr[role="row"] td table tbody td {
        border-bottom-width: 1px;
    }

</style>
<div class="panel panel-primary hidden">
    <div class="panel-heading">
        <h4 class="panel-title">
            <i class="fa fa-search"></i> <?=lang("lang.search")?>
            <span class="pull-right">
                <a data-toggle="collapse" href="#collapsePlayerReport" class="btn btn-xs btn-primary <?= $this->config->item('default_open_search_panel') ? '' : 'collapsed'?>"></a>
            </span>
        </h4>
    </div>

    <div id="collapsePlayerReport" class="panel-collapse <?= $this->config->item('default_open_search_panel') ? '' : 'collapse in'?>">
        <div class="panel-body">
            <form id="form-filter" class="form-horizontal" method="GET" onsubmit="return validateForm();">
                <div class="row">
                    <div class="col-md-4">
                        <label class="control-label"><?=lang('report.sum02')?></label>
                        <input class="form-control dateInput input-sm" id="datetime_range" data-start="#date_from" data-end="#date_to" data-time="true"/>
                        <input type="hidden" id="date_from" name="date_from" value="<?=$conditions['date_from'];?>"/>
                        <input type="hidden" id="date_to" name="date_to" value="<?=$conditions['date_to'];?>"/>
                    </div>

                    <?php if($enable_timezone_query): ?>
                        <!-- Timezone( + - ) hr -->
                        <div class="col-md-2 col-lg-2">
                            <label class="control-label padding-top-6px" for="group_by"><?=lang('Timezone')?></label>
                            <!-- <input type="number" id="timezone" name="timezone" class="form-control input-sm " value="<?=$conditions['timezone'];?>" min="-12" max="12"/> -->
                            <?php
                            $default_timezone = $this->utils->getTimezoneOffset(new DateTime());
                            $timezone_offsets = $this->utils->getConfig('timezone_offsets');
                            $timezone_location = $this->utils->getConfig('current_php_timezone');
                            ?>
                            <select id="timezone" name="timezone"  class="form-control input-sm">
                            <?php for($i = 12;  $i >= -12; $i--): ?>
                                <?php if($conditions['timezone'] || $conditions['timezone'] == '0' ): ?>
                                    <option value="<?php echo $i > 0 ? "+{$i}" : $i ;?>" <?php echo ($i == $conditions['timezone']) ? 'selected' : ''?>> <?php echo $i > 0 ? "+{$i}" : $i ;?>:00</option>
                                <?php else: ?>
                                    <option value="<?php echo $i > 0 ? "+{$i}" : $i ;?>" <?php echo ($i==$default_timezone) ? 'selected' : ''?>> <?php echo $i >= 0 ? "+{$i}" : $i ;?></option>
                                <?php endif;?>
                            <?php endfor;?>
                            </select>
                            <div class="margin-bottom-n20px" >
                                <i class="text-info" style="font-size:10px;"><?php echo lang('System Timezone') ?>: (GMT <?php echo ( $default_timezone >= 0) ? '+'. $default_timezone  : $default_timezone; ?>) <?php echo $timezone_location ;?></i>
                            </div>
                        </div>
                    <?php endif; // EOF if($enable_timezone_query): ?>

                    <div class="col-md-3">
                        <label for="username" class="control-label">
                            <?=lang('Username'); ?>
                        </label>
                        <input type="text" name="username" id="username" class="form-control input-sm" value="<?= $conditions['username'];?>" />
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-2 col-md-offset-10" style="padding-top: 20px">
                        <input type="submit" value="<?=lang('lang.search')?>" id="search_main" class="btn btn-portage btn-sm pull-right">
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
<div class="panel panel-primary">
    <div class="panel-heading">
        <h4 class="panel-title"><i class="icon-users"></i> <?=lang('player_additional_roulette_report')?> </h4>
    </div>

    <div class="panel-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover" id="myTable">
                <thead>
                    <tr>
                        <th id="th-username" ><?=lang('report.pr01')?></th>
                        <th id="th-reg-time" ><?=lang('player.38')?></th>
                        <th id="th-bonus" ><?=lang('report.pr18')?></th>
                        <th id="th-total-deposit" ><?=lang('report.pr21')?></th>
                        <th id="th-total-withdrawal" ><?=lang('Total Withdrawal')?></th>
                        <th id="th-total-bet" ><?=lang('cms.totalbets')?></th>
                        <th id="th-total-win" ><?=lang('Win')?></th>
                        <th id="th-total-loss" ><?=lang('Loss')?></th>
                        <th id="th-total-revenue" ><?=lang('Game Revenue')?></th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
    <div class="panel-footer"></div>
</div>

<div class="modal fade" id="myModal" tabindex="-1" role="document" aria-labelledby="myModalLabel">
    <div class="modal-dialog" role="document" style="max-width:300px;margin: 30px auto;">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="myModalLabel"><?php echo lang('Export Specific Columns') ?></h4>
            </div>
            <div class="modal-body" id="checkboxes-export-selected-columns">
            ...
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="export-selected-columns" ><?php echo lang('CSV Export'); ?></button>
            </div>
        </div>
    </div>
</div>

<?php if($this->utils->isEnabledFeature('export_excel_on_queue')){?>
    <form id="_export_excel_queue_form" class="hidden" method="POST" target="_blank">
        <input name='json_search' type="hidden">
    </form>
<?php }?>

<script type="text/javascript">

    $(document).ready(function(){
        $('#tag_list').multiselect({
            enableFiltering: true,
            includeSelectAllOption: true,
            selectAllJustVisible: false,
            buttonWidth: '100%',
            buttonText: function(options, select) {
                if (options.length === 0) {
                    return '<?=lang('Select Tags');?>';
                }
                else {
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

        $('#tag_list_included').multiselect({
            enableFiltering: true,
            includeSelectAllOption: true,
            selectAllJustVisible: false,
            buttonWidth: '100%',
            buttonText: function(options, select) {
                if (options.length === 0) {
                    return '<?=lang('Select Tags');?>';
                }
                else {
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

        $('#tag_list , #tag_list_included').on('change',function(){
            // var optionInSelect2 = $('#tag_list_included').find('option[value="'+$(this).val()+'"]');
            // if(optionInSelect2.length) {
            //     alert("same "+ $(this).val())
            //     optionInSelect2.attr('disabled','disabled');
            // }

            var bk1 = document.getElementById("tag_list").value;
            var bk2 = document.getElementById("tag_list_included").value;

            if(bk1.length > 0 && bk2.length > 0){
                var test = [bk1, bk2];
                var res = true;
                for(var i = 0; i < test.length; i++) {
                if (test.indexOf(test[i], i + 1) >= 0) {
                    res = false;
                    break;
                }
                }

                if(res){
                    console.log("on same tags between tag_list and tag_list_included")
                }else{
                    $(this).multiselect("deselectAll", false).multiselect("refresh");
                    alert('<?php echo lang("Selected the same tag on both Include Players with Selected Tags and Exclude Players with Selected Tags are not allow.")?>');

                }
            }

        });

        var PLAYER_REPORT_DT_CONFIG   =  <?php echo json_encode($this->config->item('player_report_dt_config'))?>,
            tableColumns              = [],
            textRightTargets          = PLAYER_REPORT_DT_CONFIG.text_right_targets,
            hiddenColsTargets         = PLAYER_REPORT_DT_CONFIG.hidden_cols_targets,
            disableColsTargets        = PLAYER_REPORT_DT_CONFIG.disable_cols_order_target,
            defaultExportCols         = PLAYER_REPORT_DT_CONFIG.default_export_cols,
            textRightTargetsIndexes   = [],
            hiddenColsTargetsIndexes  = [],
            disableColsTargetsIndexes = [],
            j = 0;

        $( "#myTable" ).find('th').each(function( index ) {
            if ($(this).attr('id') !== undefined){
                var id = $(this).attr('id').replace('th-', "");
                tableColumns[id] = index;
            }
        });

        Object.keys(tableColumns).forEach(function(key, index) {
            if (textRightTargets.indexOf(key) > -1) {
                textRightTargetsIndexes.push(tableColumns[key]);
            }
            if (hiddenColsTargets.indexOf(key) > -1) {
                hiddenColsTargetsIndexes.push(tableColumns[key]);
            }
            if (disableColsTargets.indexOf(key) > -1) {
                disableColsTargetsIndexes .push(tableColumns[key]);
            }
            j++
        }, tableColumns);

        /* Apply the tooltips */
        var initTooltipInTh = function(thSelectorInEach){
            if( typeof(thSelectorInEach) === 'undefined' ){ // default
                thSelectorInEach = '#myTable thead th';
            }
            $(thSelectorInEach).each(function () {
                    var $td = $(this);
                    if($td.attr("id") == 'th-total-bonus'){
                        $td.attr('title', '<?=lang("tb_formula")?>');
                    }
                    if($td.attr("id") == 'th-deposit-and-bonus'){
                        $td.attr('title', '<?=lang("dnb_formula")?>');
                    }
                    if($td.attr("id") == 'th-bonus-over-deposit'){
                        $td.attr('title', '<?=lang("bod_formula")?>');
                    }
                    if($td.attr("id") == 'th-deposit-minus-withdrawal'){
                        $td.attr('title', '<?=lang("nd_formula")?>');
                    }
                    if($td.attr("id") == 'th-withdrawal-over-deposit'){
                        $td.attr('title', '<?=lang("wod_formula")?>');
                    }
                    if($td.attr("id") == 'th-turn-around-time'){
                        $td.attr('title', '<?=lang("tat_formula")?>');
                    }
                    if($td.attr("id") == 'th-payout'){
                        $td.attr('title', '<?=lang("games_report_payout_formula")?>');
                    }
                    if($td.attr("id") == 'th-payout-rate'){
                        $td.attr('title', '<?=lang("payout_rate_formula")?>');
                    }
                    if($td.attr("id") == 'th-total-revenue'){
                        $td.attr('title', '<?=lang("games_report_revenue_formula")?>');
                    }
                    if($td.attr("id") == 'th-net-loss'){
                        $td.attr('title', '<?=lang("games_report_net_loss_formula")?>');
                    }
                });

                /* Apply the tooltips */
                $(thSelectorInEach).filter("[title]").tooltip({
                    "container": 'body'
                });

        };

        var dataTable = $('#myTable').DataTable({
            <?php if( ! empty($enable_freeze_top_in_list) ): ?>
            scrollY:        1000,
            scrollX:        true,
            deferRender:    true,
            scroller:       true,
            scrollCollapse: true,
            <?php endif; // EOF if( ! empty($enable_freeze_top_in_list) ):... ?>
            lengthMenu: JSON.parse('<?=json_encode($this->utils->getConfig('default_datatable_lengthMenu'))?>'),
            pageLength: 50,
            autoWidth: false,
            searching: false,
            dom: "<'panel-body' <'pull-right'B><'#export_select_columns.pull-left'><'pull-right progress-container'>l><'dt-information-summary1 text-info pull-left' i><'dataTable-instance't><'panel-body'<'pull-right'p><'dt-information-summary2 text-info pull-left' i>>",
            columnDefs: [
                { className: 'text-right font-bold', targets: textRightTargetsIndexes },
                { visible: false, targets:  hiddenColsTargetsIndexes },
                { orderable: false, targets: disableColsTargetsIndexes }
            ],
            colsNamesAliases:[],
            buttons: [
                {
                    extend: 'colvis',
                    postfixButtons: [ 'colvisRestore' ],
                    className: "btn-linkwater"
                },
                <?php if ($export_report_permission) {?>
                    {
                        text: "<?php echo lang('CSV Export'); ?>",
                        className:"btn btn-sm btn-portage export-all-columns",
                        action: function ( e, dt, node, config ) {
                            var form_params=$('#form-filter').serializeArray();
                            var d = {'extra_search': form_params, 'export_format': 'csv', 'export_type': export_type,'draw':1, 'length':-1, 'start':0};
                            utils.safelog(d);

                            $("#_export_excel_queue_form").attr('action', site_url('/export_data/playerAdditionalRouletteReports'));
                            $("#_export_excel_queue_form [name=json_search]").val(JSON.stringify(d));
                            $("#_export_excel_queue_form").submit();
                        }
                    }
                <?php } ?>
            ],
            // SERVER-SIDE PROCESSING
            processing: true,
            serverSide: true,
            "initComplete": function(settings){

                if( ! $.isEmptyObject($.fn.dataTable.FixedColumns) ) {
                    // new FixedColumns( dataTable ); // https://legacy.datatables.net/release-datatables/extras/FixedColumns/server-side-processing.html
                    new $.fn.dataTable.FixedColumns( dataTable );

                    // The workaround for fixed Columns in the multi-rows of the table foot.
                    var _fixedLeft_Class = $.fn.dataTable.FixedColumns.classes.fixedLeft; // dtfc-fixed-left
                    var dtfc_fixed_left$El = $('tfoot th.'+ _fixedLeft_Class).eq(0);
                    dtfc_fixed_left$El.closest('tfoot').find('tr th:first-child').each(function(){
                        var curr$El = $(this);
                        if( ! curr$El.hasClass(_fixedLeft_Class) ){
                            curr$El.css({
                                'left': '0px',
                                'position': 'sticky'
                            })
                            .addClass(_fixedLeft_Class);
                        }
                    });
                } // EOF if( ! $.isEmptyObject($.fn.dataTable.FixedColumns) ) {...


            }, // EOF "initComplete": function(settings){...
            ajax: function (data, callback, settings) {
                data.extra_search = $('#form-filter').serializeArray();
                $.post(base_url + "api/playerAdditionalRouletteReports", data, function(data) {

                    //add to datatable property
                    dataTable.init().colsNamesAliases = data.cols_names_aliases;

                    <?php //if (false && $this->utils->isEnabledFeature('show_total_for_player_report')) {?>
                    <?php if (false) {?>
                        $('.sub-total-cashback-bonus').text(addCommas(parseFloat(data.subtotals.subtotals_cashback_bonus).toFixed(2)));
                        $('.sub-total-deposit-bonus').text(addCommas(parseFloat(data.subtotals.subtotals_deposit_bonus).toFixed(2)));
                        $('.sub-total-referral-bonus').text(addCommas(parseFloat(data.subtotals.subtotals_referral_bonus).toFixed(2)));
                        $('.sub-total-manual-bonus').text(addCommas(parseFloat(data.subtotals.subtotals_manual_bonus).toFixed(2)));
                        $('.sub-total-subtract-bonus').text(addCommas(parseFloat(data.subtotals.subtotals_subtract_bonus).toFixed(2)));
                        $('.sub-total-bonus-add').text(addCommas(parseFloat(data.subtotals.subtotals_total_bonus).toFixed(2)));
                        $('.sub-total-firstdeposits').text(addCommas(parseFloat(data.subtotals.subtotals_first_deposit).toFixed(2)));
                        $('.sub-total-deposit-add').text(addCommas(parseFloat(data.subtotals.subtotals_total_deposit).toFixed(2)));
                        $('.sub-total-deposit-times-add').text(addCommas(parseFloat(data.subtotals.subtotals_total_deposit_times)));
                        $('.sub-total-dnb').text(addCommas(parseFloat(data.subtotals.subtotals_dnb).toFixed(2)));
                        $('.sub-total-bod').text(data.subtotals.subtotals_bod);
                        $('.sub-total-withdrawal-add').text(addCommas(parseFloat(data.subtotals.subtotals_total_withdrawal).toFixed(2)));
                        $('.sub-total-dw-add').text(addCommas(parseFloat(data.subtotals.subtotals_total_dw).toFixed(2)));
                        $('.sub-total-wod').text(addCommas(parseFloat(data.subtotals.subtotals_wod).toFixed(2))+'%');
                        $('.sub-total-bets-add').text(addCommas(parseFloat(data.subtotals.subtotals_total_bets).toFixed(2)));
                        $('.sub-total-tat').text(addCommas(parseFloat(data.subtotals.subtotals_tat).toFixed(2)));
                        $('.sub-total-win').text(addCommas(parseFloat(data.subtotals.subtotals_total_win).toFixed(2)));
                        $('.sub-total-loss').text(addCommas(parseFloat(data.subtotals.subtotals_total_loss).toFixed(2)));
                        $('.sub-total-payout-add').text(addCommas(parseFloat(data.subtotals.subtotals_total_payout).toFixed(2)));
                        $('.sub-total-payout-rate').text(addCommas(parseFloat(data.subtotals.subtotals_payout_rate).toFixed(2))+'%');
                        $('.sub-total-game-revenue').text(addCommas(parseFloat(data.subtotals.subtotals_game_revenue).toFixed(2)));

                        $('.total-cashback-bonus').text(data.total.total_cashback);
                        $('.total-deposit-bonus').text(data.total.total_deposit_bonus);
                        $('.total-referral-bonus').text(data.total.total_referral_bonus);
                        $('.total-manual-bonus').text(data.total.total_add_bonus);
                        $('.total-subtract-bonus').text(data.total.total_subtract_bonus);
                        $('.total-bonus-add').text(data.total.total_total_bonus);
                        $('.total-firstdeposits').text(data.total.total_first_deposit);
                        $('.total-deposit-add').text(data.total.total_deposit);
                        $('.total-deposit-times-add').text(addCommas(parseFloat(data.total.total_deposit_times)));
                        $('.total-dnb').text(data.total.total_dnb);
                        $('.total-bod').text(data.total.total_bod);
                        $('.total-withdrawal-add').text(data.total.total_withdrawal);
                        $('.total-dw').text(data.total.total_dw);
                        $('.total-wod').text(data.total.total_wod);
                        $('.total-bets-add').text(data.total.total_bets);
                        $('.total-tat').text(data.total.total_tat);
                        $('.total-win').text(data.total.total_win);
                        $('.total-loss').text(data.total.total_loss);
                        $('.total-payout-add').text(data.total.total_payout);
                        $('.total-payout-rate').text(data.total.total_payout_rate);
                        $('.total-game-revenue').text(data.total.total_game_revenue);
                        $('.total-dw-add').text(data.total.total_dw);
                    <?php }?>

                    callback(data);
                }, 'json');
            }
        });

        dataTable.on( 'draw', function () {
            $("#myTable_wrapper .dataTable-instance").floatingScroll("init");
            <?php if( ! empty($enable_freeze_top_in_list) ): ?>
                var _scrollBodyHeight = window.innerHeight;
                _scrollBodyHeight -= $('.navbar-fixed-top').height();
                _scrollBodyHeight -= $('.dataTables_scrollHead').height();
                _scrollBodyHeight -= $('.dataTables_scrollFoot').height();
                _scrollBodyHeight -= $('#myTable_paginate').closest('.panel-body').height();
                _scrollBodyHeight -= 44;// buffer
                $('.dataTables_scrollBody').css({'max-height': _scrollBodyHeight+ 'px'});

                initTooltipInTh('#myTable_wrapper thead th');
            <?php else: ?>
                initTooltipInTh();
            <?php endif; // EOF if( ! empty($enable_freeze_top_in_list) ):... ?>

        });

        $('#group_by').change(function() {
            var value = $(this).val();
            if (value != 'player_id') {
                $('#username').val('').prop('disabled', true);
            } else {
                $('#username').val('').prop('disabled', false);
            }
        });

        $('.export_excel').click(function(){
            var d = {'extra_search':$('#form-filter').serializeArray(), 'draw':1, 'length':-1, 'start':0};

            $.post(site_url('/export_data/playerAdditionalRouletteReports'), d, function(data){
                //create iframe and set link
                if(data && data.success){
                    $('body').append('<iframe src="'+data.link+'" frameborder="0" scrolling="no" style="border:0px;width:0px;height:0px"></iframe>');
                }else{
                    alert('export failed');
                }
            });
        });
    });

    function validateForm(){
        if($('#date_from').val().substr(14,5)!='00:00'){
            alert('<?php echo lang("Please donot change minute and second, minimum level is hour")?>');
            $('#datetime_range').focus();
            return false;
        }

        if($('#date_to').val().substr(14,5)!='59:59'){
            alert('<?php echo lang("Please donot change minute and second, minimum level is hour")?>');
            $('#datetime_range').focus();
            return false;
        }
    }

    function addCommas(nStr){
        nStr += '';
        var x = nStr.split('.');
        var x1 = x[0];
        var x2 = x.length > 1 ? '.' + x[1] : '';
        var rgx = /(\d+)(\d{3})/;
        while (rgx.test(x1)) {
            x1 = x1.replace(rgx, '$1' + ',' + '$2');
        }
        return x1 + x2;
    }
</script>
