<form class="form-horizontal" id="search-form" method="get" role="form">
<div class="panel panel-primary">

    <div class="panel-heading">
        <h4 class="panel-title">
            <i class="fa fa-search"></i> <?php echo lang("report.transactions_daily_summary_report"); ?>
            <span class="pull-right">
                <a data-toggle="collapse" href="#collapseTransactionsDailySummaryReport" class="btn btn-xs <?=$this->utils->getConfig('use_new_sbe_color') ? 'btn-primary' : 'btn-info'?> <?=$this->config->item('default_open_search_panel') ? '' : 'collapsed'?>"></a>
            </span>
        </h4>
    </div>

    <div id="collapseTransactionsDailySummaryReport" class="panel-collapse <?=$this->config->item('default_open_search_panel') ? '' : 'collapse in'?>">
        <div class="panel-body">
            <div class="row">
                <!-- Date -->
                <div class="col-md-3 col-lg-3">
                    <label class="control-label"><?php echo lang('Date'); ?></label>
                    <input id="by_transaction_date" type="date" name="by_transaction_date" class="form-control" value="<?=$conditions['by_transaction_date']?>" />
                </div>
                <!-- Player Username -->
                <div class="col-md-3 col-lg-3">
                    <label class="control-label"><?php echo lang('report.username'); ?></label>
                    <input type="text" name="by_username" id="by_username" class="form-control input-sm" placeholder='<?php echo lang('Enter Username'); ?>'
                    value="<?php echo $conditions['by_username']; ?>"/>
                </div>
                <!-- Balance Validation -->
                <div class="col-md-3 col-lg-3">
                    <label class="control-label"><?php echo lang('report.latest_balance_validation'); ?></label>
                    <select name="by_balance_validation" id="by_balance_validation" class="form-control input-sm">
                        <option value="" <?=empty($conditions['by_balance_validation']) ? 'selected' : ''?>>--  <?php echo lang('All'); ?> --</option>
                        <option value="Tallied" <?php echo ($conditions['by_balance_validation'] === 'Tallied') ? 'selected' : ''; ?>><?php echo lang('report.tallied'); ?></option>
                        <option value="Not Tallied" <?php echo ($conditions['by_balance_validation'] === 'Not Tallied') ? 'selected' : ''; ?> ><?php echo lang('report.not_tallied'); ?></option>
                    </select>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-12 col-md-12 p-t-15 text-right">
                    <input type="button" id="btnResetFields" value="<?php echo lang('Reset'); ?>" class="btn btn-sm <?=$this->utils->getConfig('use_new_sbe_color') ? 'btn-linkwater' : 'btn-danger'?>">
                    <input class="btn btn-sm btn-primary" type="submit" value="<?php echo lang('Search'); ?>" />
                </div>
            </div>
        </div>
    </div>
</div>
</form>

        <!--end of Sort Information-->
        <div class="panel panel-primary">
            <div class="panel-heading custom-ph">
                <h4 class="panel-title custom-pt"><i class="icon-bullhorn"></i> <?php echo lang('Report Result'); ?></h4>
            </div>
            <div class="panel-body">
                <!-- result table -->
                <div id="logList" class="table-responsive">
                    <table class="table table-striped table-hover table-condensed" id="report_table" style="width:100%;">
                        <thead>
                            <tr>
                                <th><?php echo lang('report.date'); ?></th>
                                <th><?php echo lang('report.username'); ?></th>
                                <th><?php echo lang('a_header.affiliate'); ?></th>
                                <th><?php echo lang('report.initial_balance'); ?></th>
                                <th><?php echo lang('report.total_deposit'); ?></th>
                                <th><?php echo lang('report.total_add_bonus'); ?></th>
                                <th><?php echo lang('report.total_add_cashback'); ?></th>
                                <th><?php echo lang('report.total_referral_bonus'); ?></th>
                                <th><?php echo lang('report.total_vip_bonus'); ?></th>
                                <th><?php echo lang('report.total_manual_add_balance'); ?></th>
                                <th><?php echo lang('report.total_withdrawal'); ?></th>
                                <th><?php echo lang('report.total_subtract_bonus'); ?></th>
                                <th><?php echo lang('report.total_subtract_balance'); ?></th>
                                <th><?php echo lang('report.total_win'); ?></th>
                                <th><?php echo lang('report.total_loss'); ?></th>
                                <th><?php echo lang('report.end_balance'); ?></th>
                                <th><?php echo lang('report.latest_balance_record'); ?></th>
                                <th><?php echo lang('report.latest_balance_validation'); ?></th>
                            </tr>
                        </thead>
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
<style type="text/css">
    .dt-body-right{
        text-align:right;
    }
</style>

<script type="text/javascript">
    $(document).ready(function(){
        var d = new Date();
        var month = d.getMonth()+1;
        var day = d.getDate();

        var datetoday = d.getFullYear() + '-' +
        (month<10 ? '0' : '') + month + '-' +
        (day<10 ? '0' : '') + day;

        var byTransactionDate = $("#by_transaction_date").val();

        $('#btnResetFields').click(function() {
            $("#by_transaction_date").val(datetoday);
            $("#by_username").val("");
            $("#by_balance_validation").val("");
        });

        $('#search-form input[type="text"],#search-form input[type="number"],#search-form input[type="email"]').keypress(function (e) {
            if (e.which == 13) {
                $('#search-form').trigger('submit');
            }
        });

        var dataTable = $('#report_table').DataTable({
            <?php if( ! empty($enable_freeze_top_in_list) ): ?>
                scrollY:        1000,
                scrollX:        true,
                deferRender:    true,
                scroller:       true,
                scrollCollapse: true,
            <?php endif; // EOF if( ! empty($enable_freeze_top_in_list) ):... ?>

            <?php if($this->utils->isEnabledFeature('column_visibility_report')){ ?>
                stateSave: true,
            <?php } else { ?>
                stateSave: false,
            <?php } ?>
            lengthMenu: JSON.parse('<?=json_encode($this->utils->getConfig('default_datatable_lengthMenu'))?>'),
            pageLength: 50,
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
                    className: '<?=$this->utils->getConfig('use_new_sbe_color') ? 'btn-linkwater' : ''?>',
                }
                <?php if ($export_report_permission) {?>
                ,{
                    text: "<?php echo lang('CSV Export'); ?>",
                    className:'btn btn-sm <?=$this->utils->getConfig('use_new_sbe_color') ? 'btn-portage' : 'btn-primary'?>',
                    action: function ( e, dt, node, config ) {
                    //  var d = {'extra_search':$('#search-form').serializeArray(), 'draw':1, 'length':-1, 'start':0};
                       var d = {'extra_search': $('#search-form').serializeArray(), 'export_format': 'csv', 'export_type': export_type,
                            'draw':1, 'length':-1, 'start':0};
                        <?php if($this->utils->isEnabledFeature('export_excel_on_queue')){?>
                                    $("#_export_excel_queue_form").attr('action', site_url('/export_data/transactions_daily_summary_report'));
                                    $("#_export_excel_queue_form [name=json_search]").val(JSON.stringify(d));
                                    $("#_export_excel_queue_form").submit();
                        <?php }?>
                    }
                }
                <?php }
?>
            ],
            columnDefs: [
                { targets: '_all', visible: true, className: 'dt-body-right' }
            ],
            processing: true,
            serverSide: true,
            ajax: function (data, callback, settings) {
                data.extra_search = $('#search-form').serializeArray();
                $.post(base_url + "api/transactions_daily_summary_report", data, function(data) {
                    callback(data);
                    // $('#total_amount').text(data.summary[0].total_amount);
                    if ( $('#report_table').DataTable().rows( { selected: true } ).indexes().length === 0 ) {
                        $('#report_table').DataTable().buttons().disable();
                    }
                    else {
                        $('#report_table').DataTable().buttons().enable();
                    }
                },'json');
            },
        });

        dataTable.on( 'draw', function (e, settings) {

        <?php if( ! empty($enable_freeze_top_in_list) ): ?>
            var _dataTableIdstr = settings.sTableId; // for multi-dataTable in a page.
            _dataTableIdstr += '_wrapper'; // append the suffix, "_wrapper".
            var _min_height = $('#'+ _dataTableIdstr).find('.dataTables_scrollBody').find('.table tbody tr').height();
            _min_height = _min_height* 5; // limit min height: 5 rows

            var _scrollBodyHeight = window.innerHeight;
            _scrollBodyHeight -= $('.navbar-fixed-top').height();
            _scrollBodyHeight -= $('#'+ _dataTableIdstr).find('.dataTables_scrollHead').height();
            _scrollBodyHeight -= $('#'+ _dataTableIdstr).find('.dataTables_scrollFoot').height();
            _scrollBodyHeight -= $('#'+ _dataTableIdstr).find('.dataTables_paginate').closest('.panel-body').height();
            _scrollBodyHeight -= 44;// buffer
            if(_scrollBodyHeight < _min_height ){
                _scrollBodyHeight = _min_height;
            }
            $('#'+ _dataTableIdstr).find('.dataTables_scrollBody').css({'max-height': _scrollBodyHeight+ 'px'});

        <?php endif; // EOF if( ! empty($enable_freeze_top_in_list) ):... ?>
        }); // EOF _dataTable.on( 'draw', function (e, settings) {...

    });
</script>