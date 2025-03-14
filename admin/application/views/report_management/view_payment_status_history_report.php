<div class="panel panel-primary hidden">

    <div class="panel-heading">
        <h4 class="panel-title">
            <i class="fa fa-search"></i> <?=lang("lang.search")?>
            <span class="pull-right">
                <a data-toggle="collapse" href="#collapsePaymentReport" class="btn btn-xs <?=$this->utils->getConfig('use_new_sbe_color') ? 'btn-primary' : 'btn-info'?> <?=$this->config->item('default_open_search_panel') ? '' : 'collapsed'?>"></a>
            </span>
            <?php include __DIR__ . "/../includes/report_tools.php" ?>
        </h4>
    </div>

    <div id="collapsePaymentReport" class="panel-collapse <?=$this->config->item('default_open_search_panel') ? '' : 'collapse in'?>">
        <div class="panel-body">
            <form id="search-form" action="<?php echo site_url('/report_management/viewPaymentStatusHistoryReport'); ?>" method="get">
                <div class="row">
                    <div class="form-group col-md-3 col-lg-3">
                        <label class="control-label"><?php echo lang('Date'); ?></label>
                        <input id="search_payment_date" class="form-control input-sm dateInput user-success" data-start="#by_date_from" data-end="#by_date_to" data-time="false" autocomplete="off">
                        <input type="hidden" id="by_date_from" name="by_date_from" value="<?=$conditions['by_date_from'];?>">
                        <input type="hidden" id="by_date_to" name="by_date_to" value="<?=$conditions['by_date_to'];?>">
                    </div>
                    <div class="form-group col-md-2 col-lg-2 hidden">
                        <div>
                            <label class="control-label"><?php echo lang('Enabled date'); ?></label>
                        </div>
                        <input type="checkbox" data-off-text="<?php echo lang('off'); ?>" data-on-text="<?php echo lang('on'); ?>" name="enable_date" data-size='mini' value='true' <?php echo $conditions['enable_date'] ? 'checked="checked"' : ''; ?>>
                    </div>
                    <div class="form-group col-md-3">
                        <label for="accountname" class="control-label">
                            <?php echo lang('Similar').' '.lang('pay.payment_account'); ?>
                        </label>
                        <input type="text" name="by_accountname" id="by_accountname" value="<?php echo $conditions['by_accountname']; ?>" class="form-control input-sm">
                    </div>
                </div>
                <div class="row">
                    <div class="form-group col-md-3">
                        <label class="control-label"><?=lang('report.p66').' >='?></label>
                        <input type="number" min="0" id="by_success_rate_greater_than" name="by_success_rate_greater_than" class="form-control input-sm" placeholder="<?=lang('Enter Digi Number Only')?>" value="<?php echo $conditions['by_success_rate_greater_than']; ?>"/>
                    </div>
                    <div class="form-group col-md-3">
                        <label class="control-label"><?=lang('report.p66').' <='?></label>
                        <input type="number" min="0" id="by_success_rate_less_than" name="by_success_rate_less_than" class="form-control input-sm" placeholder="<?=lang('Enter Digi Number Only')?>" value="<?php echo $conditions['by_success_rate_less_than']; ?>"/>
                    </div>
                </div>
                <div class="row">
                    <div class="form-group col-md-3">
                        <label class="control-label"><?=lang('report.p67').' >='?></label>
                        <input type="number" min="0" id="by_failed_rate_greater_than" name="by_failed_rate_greater_than" class="form-control input-sm" placeholder="<?=lang('Enter Digi Number Only')?>" value="<?php echo $conditions['by_failed_rate_greater_than']; ?>"/>
                    </div>
                    <div class="form-group col-md-3">
                        <label class="control-label"><?=lang('report.p67').' <='?></label>
                        <input type="number" min="0" id="by_failed_rate_less_than" name="by_failed_rate_less_than" class="form-control input-sm" placeholder="<?=lang('Enter Digi Number Only')?>" value="<?php echo $conditions['by_failed_rate_less_than']; ?>"/>
                    </div>
                </div>
                <div class="row">
                    <div class="form-group col-md-6 p-t-25">
                        <button type="button" id="btnResetFields" class="btn <?=$this->utils->getConfig('use_new_sbe_color') ? 'btn-linkwater' : 'btn-default'?> btn-sm"><?=lang('lang.reset')?></button>
                        <button type="submit" class="btn btn-sm <?=$this->utils->getConfig('use_new_sbe_color') ? 'btn-portage' : 'btn-info'?>"><?=lang("lang.search")?></button>
                    </div>
                </div>

            </form>
        </div>
    </div>

</div>

<div class="panel panel-primary">

    <div class="panel-heading">
        <h4 class="panel-title">
            <i class="icon-credit-card"></i>
            <?=lang("report.p68")?>
        </h4>
    </div>

    <div class="panel-body table-responsive">

        <table class="table table-striped table-hover table-condensed" id="deposit_status_history">

            <thead>
            <tr>
                <th><?=lang("Date")?></th>
                <th><?=lang("pay.payment_account")?></th>
                <th><?=lang("report.p59")?></th>
                <th><?=lang("report.p60")?></th>
                <th><?=lang("report.p61")?></th>
                <th><?=lang("report.p62")?></th>
                <th><?=lang("report.p63")?></th>
                <th><?=lang("report.p64")?></th>
                <th><?=lang("report.p65")?></th>
                <th><?=lang("report.p66")?></th>
                <th><?=lang("report.p67")?></th>
            </tr>
            </thead>

            <tbody>

            </tbody>

        </table>
    </div><!-- panel-body -->

    <div class="panel-footer"></div>

</div>

<script type="text/javascript">
    $(document).ready(function(){

        $('#btnResetFields').click(function() {

            var date_today = new moment().format('YYYY-MM-DD');
            var date_lastweek = new moment().add(-7,'days').format('YYYY-MM-DD');
            $("#by_date_from").val(date_lastweek);
            $("#by_date_to").val(date_today);
            $("#enable_date").prop('checked', true);

            $("#by_accountname").val("");
            $("#by_success_rate_greater_than").val("");
            $("#by_success_rate_less_than").val("");
            $("#by_failed_rate_greater_than").val("");
            $("#by_failed_rate_less_than").val("");
            $("#search_payment_date").val($("#by_date_from").val() +" to "+ $("#by_date_to").val());

        });

        $("input[type='checkbox']").bootstrapSwitch();

        var depositStatusHistory = $('#deposit_status_history').DataTable({
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
            dom: "<'panel-body'<'pull-right'B><'pull-right progress-container hidden-xs'>l><'dt-information-summary1 text-info pull-left' i>t<'panel-body'<'pull-right'p><'dt-information-summary2 text-info pull-left' i>>",
            autoWidth: false,
            searching: false,
            buttons: [
                {
                    extend: 'colvis',
                    postfixButtons: [ 'colvisRestore' ],
                    className: '<?=$this->utils->getConfig('use_new_sbe_color') ? 'btn-linkwater' : ''?>'
                }
                <?php if ($export_report_permission) {?>
                ,{
                    text: "<?php echo lang('CSV Export'); ?>",
                    className:'btn btn-sm <?=$this->utils->getConfig('use_new_sbe_color') ? 'btn-portage' : 'btn-primary'?>',
                    action: function ( e, dt, node, config ) {
                        var form_params=$('#search-form').serializeArray();

                        var d = {'extra_search': form_params, 'export_format': 'csv', 'export_type': export_type,
                            'draw':1, 'length':-1, 'start':0};

                        $.post(site_url('/export_data/payment_status_history_report'), d, function(data){
                            if(data && data.success){
                                $('body').append('<iframe src="'+data.link+'" frameborder="0" scrolling="no" style="border:0px;width:0px;height:0px"></iframe>');
                            }else{
                                alert('export failed');
                            }
                        }).fail(function(){
                            alert('export failed');
                        });
                    }
                }
                <?php } ?>
            ],
            columnDefs: [
                { className: 'text-right', targets: [ 2,3,4,5,6,7,8,9,10 ] }
            ],
            "order": [ 0, 'desc' ],

            // SERVER-SIDE PROCESSING
            processing: true,
            serverSide: true,
            ajax: function (data, callback, settings) {
                data.extra_search = $('#search-form').serializeArray();
                $.post(base_url + "api/payment_status_history_report", data, function(data) {
                    callback(data);
                    if ( depositStatusHistory.rows( { selected: true } ).indexes().length === 0 ) {
                        depositStatusHistory.buttons().disable();
                    }
                    else {
                        depositStatusHistory.buttons().enable();
                    }
                }, 'json');
            }
        });

        depositStatusHistory.on( 'draw', function (e, settings) {

            <?php if( ! empty($enable_freeze_top_in_list) ): ?>
                var _min_height = $('.dataTables_scrollBody').find('.table tbody tr').height();
                _min_height = _min_height* 5; // limit min height: 5 rows

                var _scrollBodyHeight = window.innerHeight;
                _scrollBodyHeight -= $('.navbar-fixed-top').height();
                _scrollBodyHeight -= $('.dataTables_scrollHead').height();
                _scrollBodyHeight -= $('.dataTables_scrollFoot').height();
                _scrollBodyHeight -= $('.dataTables_paginate').closest('.panel-body').height();
                _scrollBodyHeight -= 44;// buffer
                if(_scrollBodyHeight < _min_height ){
                    _scrollBodyHeight = _min_height;
                }
                $('.dataTables_scrollBody').css({'max-height': _scrollBodyHeight+ 'px'});

            <?php endif; // EOF if( ! empty($enable_freeze_top_in_list) ):... ?>
        });

    });
</script>