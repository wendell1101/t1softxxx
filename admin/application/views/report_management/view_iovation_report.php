<div class="panel panel-primary hidden">
    <div class="panel-heading">
        <h4 class="panel-title">
            <i class="fa fa-search"></i> <?=lang("lang.search")?>
            <span class="pull-right">
                <a data-toggle="collapse" href="#collapsePaymentReport" class="btn btn-xs <?=$this->utils->getConfig('use_new_sbe_color') ? 'btn-primary' : 'btn-info'?> <?=$this->config->item('default_open_search_panel') ? '' : 'collapsed'?>"></a>
            </span>
        </h4>
    </div>
    <div id="collapsePaymentReport" class="panel-collapse <?=$this->config->item('default_open_search_panel') ? '' : 'collapse in'?>">
        <div class="panel-body">
            <form id="search-form" action="<?= site_url('/report_management/viewIovationReport'); ?>" method="get">
                <div class="row">
                    <!-- Date -->
                    <div class="form-group col-md-2 col-lg-2">
                        <label class="control-label">
                            <?= lang('report.regdate'); ?>:
                        </label>
                        <input id="search_payment_date" class="form-control input-sm dateInput user-success" data-start="#by_date_from" data-end="#by_date_to" data-time="false" autocomplete="off" />
                        <input type="hidden" id="by_date_from" name="by_date_from" value="<?=$conditions['by_date_from'];?>" />
                        <input type="hidden" id="by_date_to" name="by_date_to" value="<?=$conditions['by_date_to'];?>" />
                    </div>
                    <!-- username -->
                    <div class="form-group col-md-2 col-lg-2">
                    <label class="control-label">
                            <?= lang('report.username'); ?>
                        </label>
                        <input type="text" name="by_username" id="by_username" value="<?= $conditions['by_username']; ?>" class="form-control input-sm group-reset" />
                    </div>

                   <!-- user_type -->
                   <div class="form-group col-md-2 col-lg-2">
                       <label for="by_user_type" class="control-label"><?=lang('User Type');?> </label>
                       <?=form_dropdown('by_user_type', $user_type_list, $conditions['by_user_type'], 'class="form-control input-sm iovation_report_status group-reset"'); ?>
                   </div>

                    <!-- deviceid -->
                    <div class="form-group col-md-2 col-lg-2">
                    <label class="control-label">
                            <?= lang('report.device_id'); ?>
                        </label>
                        <input type="text" name="by_device_id" id="by_device_id" value="<?= $conditions['by_device_id']; ?>" class="form-control input-sm group-reset" />
                    </div>

                   <!-- status -->
                   <div class="form-group col-md-2 col-lg-2">
                       <label for="status" class="control-label"><?=lang('API Response');?> </label>
                       <?=form_dropdown('by_status', $status_list, $conditions['by_status'], 'class="form-control input-sm iovation_report_status group-reset"'); ?>
                   </div>

                    <!-- result -->
                    <div class="form-group col-md-2 col-lg-2">
                        <label for="result" class="control-label"><?=lang('Player Verified Status');?> </label>
                        <?=form_dropdown('by_result', $result_list, $conditions['by_result'], 'class="form-control input-sm iovation_report_result group-reset"'); ?>
                    </div>

                   <!-- type -->
                   <div class="form-group col-md-2 col-lg-2">
                       <label for="by_type" class="control-label"><?=lang('Record Type');?> </label>
                       <?=form_dropdown('by_type', $type_list, $conditions['by_type'], 'class="form-control input-sm iovation_report_status group-reset"'); ?>
                   </div>


                </div>

                <div class="row">
                    <div class="form-group col-md-2 col-md-offset-10">
                        <div class="pull-right">
                            <input type="button" id="btnResetFields" value="<?=lang('lang.clear'); ?>" class="btn btn-sm <?=$this->utils->getConfig('use_new_sbe_color') ? 'btn-linkwater' : 'btn-danger'?>">
                            <button type="submit" class="btn btn-sm <?=$this->utils->getConfig('use_new_sbe_color') ? 'btn-portage' : 'btn-info'?>"><?=lang("lang.search")?></button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="panel panel-primary">
    <div class="panel-heading">
        <h4 class="panel-title">
            <i class="icon-newspaper"></i>
            <?=lang("report.iovation_report")?>
        </h4>
    </div>
    <div class="panel-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover table-condensed" id="result_table">
                <thead>
                    <tr>
                        <th><?=lang("report.regdate")?></th>
                        <th><?=lang("UserCode")?></th>
                        <th><?=lang("Username")?></th>
                        <th><?=lang("User Type")?></th>
                        <th><?=lang("Device ID")?></th>
                        <th><?=lang("API Response")?></th>
                        <th><?=lang("Player Verified Status")?></th>
                        <th><?=lang("Registered IP")?></th>
                        <th><?=lang("Contact Number")?></th>
                        <th><?=lang("report.iovationfullname")?></th>
                        <th><?=lang("Record Type")?></th>
                        <th><?=lang("Last Update Time")?></th>
                        <th><?=lang("Action")?></th>
                    </tr>
                </thead>
                <tbody></tbody>
                <tfoot>
                    <tr></tr>
                </tfoot>
            </table>
        </div>
    </div>
    <div class="panel-footer"></div>
</div>

<div id="conf-modal"  class="modal fade bs-example-modal-md"  data-backdrop="static"
data-keyboard="false"  tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true">
<div class="modal-dialog modal-md">
    <div class="modal-content">
        <div class="modal-header panel-heading">
            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
            <h3 id="myModalLabel" ><?=lang('sys.ga.conf.title');?></h3>
        </div>
        <div class="modal-body">
            <div class="row">
                <div class="col-md-12">
                    <div class="help-block" id="conf-msg">

                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-default" id="cancel-action" data-dismiss="modal"><?=lang('pay.bt.cancel');?></button>
            <button type="button" id="confirm-action" class="btn btn-primary"><?=lang('pay.bt.yes');?></button>
        </div>
    </div>
</div>
</div>

<?php if($this->utils->isEnabledFeature('export_excel_on_queue')){?>
    <form id="_export_excel_queue_form" class="hidden" method="POST" target="_blank">
        <input name='json_search' type="hidden">
    </form>
    <form id="_export_csv_form" class="hidden" method="POST" target="_blank">
        <input name='json_search' id = "json_csv_search" type="hidden">
    </form>
<?php }?>

<script type="text/javascript">
    var LANG = {
        RESEND_CONFIRM_MESSAGE : "<?=lang('report.iovation.conf.resend.msg');?>",
    };

    function notify(type, msg) {
        $.notify({
            message: msg
        }, {
            type: type
        });
    }

    function resendIovation(id){
        var r = confirm(LANG.RESEND_CONFIRM_MESSAGE);
        if (r == true) {
            var type = 'POST';
            var params = {
                id: id,
            };
            var url='<?php echo site_url('iovation/resend') ?>';
            executeAction(url, type, params);
        } else {

        }
    }

    function executeAction(url, type, params) {

        if (type == 'POST') {

            $.ajax({
                method: type,
                url: url,
                data: params,
                dataType: "json"
            })
            .done(function(data) {
                notify('success',data.msg );
            }).fail(function() {
                notify('danger','<?php echo lang('sys.ga.erroccured') ?>' );
            });


        } else {

            $.ajax({
                method: type,
                url: url,
                dataType: "json"
            })
            .done(function(data) {
                notify('success',data.msg );
            }).fail(function() {
                notify('danger','<?php echo lang('sys.ga.erroccured') ?>' );
            });
        }


    }//end executeAction

    $(document).ready(function(){
        var hide_targets=<?=json_encode($hide_cols); ?>;

        var dataTable = $('#result_table').DataTable({
            <?php if( ! empty($enable_freeze_top_in_list) ): ?>
                scrollY:        1000,
                scrollX:        true,
                deferRender:    true,
                scroller:       true,
                scrollCollapse: true,
            <?php endif; // EOF if( ! empty($enable_freeze_top_in_list) ):... ?>

            stateSave: true,
            dom: "<'panel-body'<'pull-right'B><'pull-right progress-container hidden-xs'>l><'dt-information-summary1 text-info pull-left' i>t<'panel-body'<'pull-right'p><'dt-information-summary2 text-info pull-left' i>>",
            autoWidth: false,
            searching: false,
            buttons: [
                {
                    extend: 'colvis',
                    postfixButtons: [ 'colvisRestore' ],
                    className: '<?=$this->utils->getConfig('use_new_sbe_color') ? 'btn-linkwater' : ''?>',
                }
                ,{
                    text: "<?= lang('CSV Export'); ?>",
                    className:'btn btn-sm <?=$this->utils->getConfig('use_new_sbe_color') ? 'btn-portage' : 'btn-primary'?>',
                    action: function ( e, dt, node, config ) {
                        var form_params=$('#search-form').serializeArray();
                       var d = {'extra_search': form_params, 'export_format': 'csv', 'export_type': 'queue',
                            'draw':1, 'length':-1, 'start':0};
                            $("#_export_excel_queue_form").attr('action', site_url('/export_data/iovationReport'));
                            $("#_export_excel_queue_form [name=json_search]").val(JSON.stringify(d));
                            $("#_export_excel_queue_form").submit();
                    }
                }
            ],
            columnDefs: [
                { className: 'text-right', targets: [] },
                { className: 'text-center', targets: [2,3,7] },
                { "visible": false, "targets": hide_targets }
            ],
            order: [ 0, 'desc' ],

            // SERVER-SIDE PROCESSING
            processing: true,
            serverSide: true,
            ajax: function (data, callback, settings) {
                data.extra_search = $('#search-form').serializeArray();
                $.post(base_url + "api/iovationReport", data, function(data) {
                    $.each(data.data, function(i, v){
                        /*sub = v[10].replace(/<(?:.|\n|)*?>/gm, '');
                        convertedSub = sub.replace(',', '');
                        if(Number.parseFloat(convertedSub)){
                            subTotal+= Number.parseFloat(convertedSub);
                        }*/
                    });
                    callback(data);
                    if ( dataTable.rows( { selected: true } ).indexes().length === 0 ) {
                        dataTable.buttons().disable();
                    }
                    else {
                        dataTable.buttons().enable();
                    }
                }, 'json');
            }
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
        });


        var date_today = new moment().format('YYYY-MM-DD');

        $('#btnResetFields').click(function() {
            $('.group-reset').val('');
            $('#include_all_downlines').prop('checked', false);
            $("#search_payment_date").val(date_today + " to " + date_today);
        });


    });
</script>