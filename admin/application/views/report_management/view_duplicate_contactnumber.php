<div class="panel panel-primary hidden">
    <div class="panel-heading">
        <h4 class="panel-title">
            <i class="fa fa-search"></i> <?=lang("lang.search")?>
            <span class="pull-right">
                <a data-toggle="collapse" href="#viewDuplicateContactNumberReport" class="btn btn-xs btn-primary <?=$this->config->item('default_open_search_panel') ? '' : 'collapsed'?>"></a>
            </span>
        </h4>
    </div>
    <div id="viewDuplicateContactNumberReport" class="panel-collapse <?=$this->config->item('default_open_search_panel') ? '' : 'collapse in'?>">
        <div class="panel-body">
            <form id="search-form" action="<?= site_url('/report_management/viewDuplicateContactNumberReport'); ?>" method="get">
                <div class="row">
                    <!-- Date -->
                    <div class="form-group col-md-4 col-lg-4">
                        <label class="control-label">
                            <?= lang('player.38'); ?>:
                        </label>
                        <input id="search_payment_date" class="form-control input-sm dateInput user-success" data-start="#by_date_from" data-end="#by_date_to" data-time="true" autocomplete="off" />
                        <input type="hidden" id="by_date_from" name="by_date_from" value="<?=$conditions['by_date_from'];?>" />
                        <input type="hidden" id="by_date_to" name="by_date_to" value="<?=$conditions['by_date_to'];?>" />
                    </div>
                    <!-- username -->
                    <div class="form-group col-md-4 col-lg-4">
                        <label class="control-label">
                            <input type="radio" name="search_by" value="1" checked <?=$conditions['search_by'] == '1' ? 'checked="checked"' : '' ?> /> <?=lang('Similar');?>
                            <?=lang('Username'); ?>
                            <input type="radio" name="search_by" value="2" <?=$conditions['search_by'] == '2' ? 'checked="checked"' : ''?> /> <?=lang('Exact'); ?>
                            <?=lang('Username'); ?>
                        </label>
                        <input type="text" name="by_username" id="by_username" value="<?= $conditions['by_username']; ?>" class="form-control input-sm group-reset" />
                    </div>
                    <!-- login ip -->
                    <div class="form-group col-md-4 col-lg-4">
                        <label  class="control-label"><?=lang('Signup IP');?></label>
                        <input type="text" name="login_ip" id="login_ip" class="form-control input-sm" value="<?php echo $conditions['login_ip']; ?>">
                        <?php echo form_error('login_ip', '<span class="help-block" style="color:#ff6666;">', '</span>'); ?>
                    </div>
                </div>
                <div class="row">
                    <div class="form-group col-md-2 col-md-offset-10">
                        <div class="pull-right">
                            <input type="button" id="btnResetFields" value="<?=lang('lang.clear'); ?>" class="btn btn-sm btn-linkwater">
                            <button type="submit" class="btn btn-sm btn-portage"><?=lang("lang.search")?></button>
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
            <?=lang("duplicate_contactnumber_model.2")?>
        </h4>
    </div>
    <div class="panel-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover table-condensed" id="result_table">
                <thead>
                    <tr>
                    <th><?=lang('player.01')?></th><!-- new user -->
                    <th style="min-width:110px;"><?=lang('player.38')?></th> <!--// new user reg date-->
                    <th><?=lang('Signup IP')?></th><!--// new user reg IP-->
                    <th><?=lang('player_list.fields.last_login_ip')?></th><!--// new user login ip-->
                    <th><?=lang('player.63')?></th><!--// Phone Number-->
                    <th><?=lang("duplicate_contactnumber_model.1")?></th> <!--// old Duplicate Phone Number user-->
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

<?php if($this->utils->isEnabledFeature('export_excel_on_queue')){?>
    <form id="_export_excel_queue_form" class="hidden" method="POST" target="_blank">
        <input name='json_search' type="hidden">
    </form>
    <form id="_export_csv_form" class="hidden" method="POST" target="_blank">
        <input name='json_search' id = "json_csv_search" type="hidden">
    </form>
<?php }?>

<script type="text/javascript">

    $(document).ready(function(){

        var dataTable = $('#result_table').DataTable({

        <?php if( ! empty($enable_freeze_top_in_list) ): ?>
            scrollY:        1000,
            // scrollX:        true,
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
                    className: 'btn-linkwater',
                }
                <?php if($export_report_permission){ ?>
                ,{
                    text: "<?= lang('CSV Export'); ?>",
                    className:'btn btn-sm btn-portage',
                    action: function ( e, dt, node, config ) {
                        var form_params=$('#search-form').serializeArray();
                       var d = {'extra_search': form_params, 'export_format': 'csv', 'export_type': 'queue',
                            'draw':1, 'length':-1, 'start':0};
                            $("#_export_excel_queue_form").attr('action', site_url('/export_data/playerDuplicateContactNumberReport'));
                            $("#_export_excel_queue_form [name=json_search]").val(JSON.stringify(d));
                            $("#_export_excel_queue_form").submit();
                    }
                }
                <?php } ?>
            ],
            columnDefs: [
                // { className: 'text-right', targets: [3,4] },
                // { visible: false, targets: [1] },
            ],
            order: [ 1, 'desc' ],

            // SERVER-SIDE PROCESSING
            processing: true,
            serverSide: true,
            ajax: function (data, callback, settings) {
                data.extra_search = $('#search-form').serializeArray();
                $.post(base_url + "api/playerDuplicateContactNumberReport", data, function(data) {
                    callback(data);

                    console.log('-----------------data',settings);
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
        }); //EOF dataTable.on( 'draw', function (e, settings) {...


        $('#btnResetFields').click(function() {
            $('.group-reset').val('');
            $('.dateInput').data('daterangepicker').setStartDate(moment().startOf('day').format('Y-MM-DD HH:mm:ss'));
            $('.dateInput').data('daterangepicker').setEndDate(moment().endOf('day').format('Y-MM-DD HH:mm:ss'));
            dateInputAssignToStartAndEnd($('#search_withdrawal_date'));
        });
    });
</script>