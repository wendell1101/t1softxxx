<div data-file-info="ajax_ui_ip.php" data-datatable-selector="#ip-table">
    <div class="form-inline">
        <input type="text" id="reportrange" class="form-control input-sm dateInput inline" data-start="#dateRangeValueStart" data-end="#dateRangeValueEnd" data-time="true"/>
        <input type="hidden" id="dateRangeValueStart" name="dateRangeValueStart"/>
        <input type="hidden" id="dateRangeValueEnd" name="dateRangeValueEnd"/>
        <input type="button" class="btn btn-portage btn-sm" id="btn-submit" value="<?=lang('lang.search');?>"/>
    </div>
    <hr/>
    <div class="clearfix">
        <table id="ip-table" class="table table-bordered">
            <thead>
                <th><?=lang('traffic.playerip');?></th>
                <th><?=lang('player.sd08');?></th>
                <th><?=lang('player_login_report.referrer');?></th>
                <th><?=lang('pay.useragent');?></th>
                <th><?=lang('player.ub01');?></th>
                <th><?=lang('Client End');?></th>
            </thead>
        </table>
    </div>
</div>
<?php if ($this->utils->isEnabledFeature('export_excel_on_queue')) {?>
    <form id="_export_excel_queue_form" class="hidden" method="POST" target="_blank">
        <input name='json_search' type="hidden">
    </form>
<?php }?>

<script type="text/javascript">
    function ipHistory() {
        var dataTable = $('#ip-table').DataTable({
            dom: "<'row'<'col-md-12'<'pull-right'B><'pull-right progress-container'>l<'dt-information-summary2 text-info pull-left' i>>><'table-responsive't><'row'<'col-md-12'<'pull-right'p><'dt-information-summary2 text-info pull-left' i>>>",
            autoWidth: false,
            searching: false,
            <?php if ($this->utils->isEnabledFeature('column_visibility_report')){ ?>
                stateSave: true,
            <?php } else { ?>
                stateSave: false,
            <?php } ?>
            buttons: [
                {
                    extend: 'colvis',
                    postfixButtons: [ 'colvisRestore' ],
                    className: ['btn-linkwater']
                },
                {
                    text: "<?php echo lang('CSV Export'); ?>",
                    className:'btn btn-sm btn-portage',
                    action: function ( e, dt, node, config ) {

                        // var form_params=$('#search-form_new').serializeArray();
                        var form_params= [
                            {
                                'name':'player_id',
                                'value':playerId
                            },
                            {
                                'name':'dateRangeValueStart',
                                'value':$('#dateRangeValueStart').val()
                            },
                            {
                                'name':'dateRangeValueEnd',
                                'value':$('#dateRangeValueEnd').val()
                            }
                        ];

                        console.log(form_params);

                        var d = {'extra_search': form_params, 'export_format': 'csv', 'export_type': export_type,
                                'draw':1, 'length':-1, 'start':0};
                         // console.log(d);

                        $("#_export_excel_queue_form").attr('action', site_url('/export_data/export_ip_history'));
                        $("#_export_excel_queue_form [name=json_search]").val(JSON.stringify(d));
                        $("#_export_excel_queue_form").submit();
                    }
                }
            ],
            order: [
                [4, 'desc']
            ],
            processing: true,
            serverSide: true,
            ajax: function (data, callback, settings) {
                data.extra_search = [
                    {
                        'name':'dateRangeValueStart',
                        'value':$('#dateRangeValueStart').val()
                    },
                    {
                        'name':'dateRangeValueEnd',
                        'value':$('#dateRangeValueEnd').val()
                    }
                ];

                $.post(base_url + 'api/ip_history/' + playerId, data, function(data) {
                    callback(data);
                },'json');
            }
        });

        $('#changeable_table #btn-submit').click( function() {
            dataTable.ajax.reload();
        });

        ATTACH_DATATABLE_BAR_LOADER.init('ip-table');
    }
</script>
