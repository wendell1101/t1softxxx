<div class="panel panel-primary">
    <div class="panel-heading custom-ph">
        <h4 class="panel-title">
            <i class="icon-pie-chart"></i>
            <?=lang('Search')?>
        </h4>
    </div>
    <div class="panel-body">
        <form id='form-filter'>
            <div class="row form-group">
                <div class="col-md-2">
                    <input type="text" id="vpgts_player_username" name="vpgts_player_username" class="form-control" required>
                </div>
                <div class="col-md-2">
                    <input type="button" value="<?=lang('lang.search')?>" id="loadData" class="btn btn-portage btn-sm">
                </div>
            </div>
        </form>
    </div>
</div>

<div class="panel panel-primary" >
    <div class="panel-heading">
        <h4 class="panel-title"><i class="icon-dice"></i> <?=lang('Player Life Time Data')?> </h4>
    </div>
    <div class="panel-body" >
        <div class="table-responsive">
            <table class="table table-bordered table-hover " id="myTable">
                <thead>
                    <tr>
                        <th><?=lang('Player Username')?></th>
                        <th><?=lang('Total Deposit')?></th>
                        <th><?=lang('Total Withdraw')?></th>
                        <th><?=lang('Total Bonus')?></th>
                        <th><?=lang('Total Bet')?></th>
                        <th><?=lang('Total Net Loss')?></th>
                    </tr>
                </thead>
            </table>
        </div>
       <div style="min-height:400px;">
            <div id="player-bets-per-game-container" style="overflow-x: auto;">
                <table class="table table-hover table-bordered table-condensed " id="player-bets-per-game" >
                   <thead>
                    <tr ></tr>
                   </thead>
                   <tbody>
                   </tbody>
                    <tfoot id="player-bets-per-game-totals" >
                    </tfoot>
              </table>
            </div>
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

<script>
    $(document).ready(function(){

        var dataTable = $('#myTable').DataTable({
            <?php if( ! empty($enable_freeze_top_in_list) ): ?>
            scrollY:        1000,
            scrollX:        true,
            deferRender:    true,
            scroller:       true,
            scrollCollapse: true,
            <?php endif; // EOF if( ! empty($enable_freeze_top_in_list) ):... ?>
            dom: "<'panel-body'<'pull-right'B><'pull-right progress-container hidden-xs'>l><'dt-information-summary1 text-info pull-left' i>t<'panel-body'<'pull-right'p><'dt-information-summary2 text-info pull-left' i>>",
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
                    className: '<?=$this->utils->getConfig('use_new_sbe_color') ? 'btn-linkwater' : ''?>',
                }
                <?php if ($export_report_permission) {?>
                ,{
                    text: "<?php echo lang('CSV Export'); ?>",
                    className:'btn btn-sm <?=$this->utils->getConfig('use_new_sbe_color') ? 'btn-portage' : 'btn-primary'?>',
                    action: function ( e, dt, node, config ) {
                        //var d = {'extra_search':$('#form-filter').serializeArray(), 'draw':1, 'length':-1, 'start':0};
                        // utils.safelog(d);
                         var d = {'extra_search': $('#form-filter').serializeArray(), 'export_format': 'csv', 'export_type': export_type,
                            'draw':1, 'length':-1, 'start':0};
                        <?php if($this->utils->isEnabledFeature('export_excel_on_queue')){?>
                                    $("#_export_excel_queue_form").attr('action', site_url('/export_data/player_game_and_transaction_summary_report'));
                                    $("#_export_excel_queue_form [name=json_search]").val(JSON.stringify(d));
                                    $("#_export_excel_queue_form").submit();
                        <?php }else{?>

                        $.post(site_url('/export_data/player_game_and_transaction_summary_report'), d, function(data){
                            // utils.safelog(data);

                            //create iframe and set link
                            if(data && data.success){
                                $('body').append('<iframe src="'+data.link+'" frameborder="0" scrolling="no" style="border:0px;width:0px;height:0px"></iframe>');
                            }else{
                                alert('export failed');
                            }
                        }).fail(function(){
                            alert('export failed');
                        });

                        <?php }?>
                    }
                }
                <?php } ?>
            ],
            columnDefs: [
                { className: 'text-right', targets: [1] },
            ],
            "order": [ 0, 'asc' ],

            // SERVER-SIDE PROCESSING
            processing: true,
            serverSide: true,
            ajax: function (data, callback, settings) {
                formData = $('#form-filter').serializeArray();
                data.extra_search = formData;
                console.log('ajax post here ....................');
                console.log('extra_search', data.extra_search);
                $.post(base_url + "api/playerGameAndTransactionSummaryReport", data, function(data) {
                    console.log(data);

                    callback(data);
                    if ( dataTable.rows( { selected: true } ).indexes().length === 0 ) {
                        dataTable.buttons().disable();
                    }
                    else {
                        dataTable.buttons().enable();
                    }
                }, 'json');
           },

        });

        $('#loadData').on('click', function () {
            dataTable.ajax.reload(); // Reload the DataTable with new data from the AJAX call
        });

    });
</script>
