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
        <h4 class="panel-title"><i class="icon-users"></i> <?=lang('player_additional_report')?> </h4>
    </div>

    <div class="panel-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover" id="myTable">
                <thead>
                    <tr>
                        <th id="th-username" ><?=lang('report.pr01')?></th>
                        <th id="th-tag" style="min-width:150px;" ><?=lang('player.41')?></th>
                        <th id="th-player-level" style="min-width:150px;" ><?=lang('report.pr03')?></th>
                        <!--TURN OFF THIS FIELDS WHEN USING SBE LOTTERY-->
                        <?php if( !$this->utils->isEnabledFeature('close_aff_and_agent')): ?>
                            <th id="th-affiliate" ><?=lang('Affiliate')?></th>
                        <?php endif?>
                        <?php if(!$this->utils->isEnabledFeature('close_aff_and_agent')): ?>
                            <th id="th-agent" ><?=lang("Agent")?></th>
                        <?php endif?>
                        <?php if($this->utils->getConfig('display_last_deposit_col')): ?>
                            <th id="th-total-last-deposit" ><?=lang('player.105')?></th>
                        <?php endif?>
                        <!--TURN OFF THIS FIELDS WHEN USING SBE LOTTERY-->
                        <th id="th-total-cashback-bonus" ><?=lang('report.sum15')?></th>
                        <th id="th-total-bonus"><?=lang('report.pr18')?> &nbsp;<i class="fa fa-info-circle"></i></th>
                        <th id="th-total-first-deposit" ><?=lang('player.75')?></th>
                        <th id="th-total-deposit"><?=lang('report.pr21')?></th>
                        <th id="th-deposit-times"><?=lang('yuanbao.deposit.times')?></th>
                        <th id="th-total-withdrawal" ><?=lang('report.pr22')?></th>
                        <th id="th-deposit-minus-withdrawal" ><?=lang('Net Deposit')?> &nbsp;<i class="fa fa-info-circle"></i></th>
                        <th id="th-total-bets" ><?=lang('cms.totalbets')?></th>
                        <th id="th-total-revenue"><?=lang('Game Revenue')?> &nbsp;<i class="fa fa-info-circle"></i></th>
                        <?php if($this->utils->getConfig('display_net_loss_col')): ?>
                            <th id="th-net-loss"><?=lang('report.pr33')?> &nbsp;<i class="fa fa-info-circle"></i></th>
                        <?php endif?>
                        <th id="th-total-total-nofrozen" ><?=lang("Total Balance")?></th>
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
                    if($td.attr("id") == 'th-deposit-minus-withdrawal'){
                        $td.attr('title', '<?=lang("nd_formula")?>');
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

                            $("#_export_excel_queue_form").attr('action', site_url('/export_data/playerAdditionalReports'));
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
                $.post(base_url + "api/playerAdditionalReports", data, function(data) {
                    //add to datatable property
                    dataTable.init().colsNamesAliases = data.cols_names_aliases;
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
