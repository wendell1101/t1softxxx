<div data-file-info="ajax_ui_playerroulettehistory.php" data-datatable-selector="#playerroulette-history">
    <div class="form-inline">
        <input type="text" id="reportrange" class="form-control input-sm dateInput inline" data-start="#dateRangeValueStart" data-end="#dateRangeValueEnd" data-time="true"/>
        <input type="hidden" id="playerroulettehistoryplayerid" value="<?=$player_id ?>">   
        <input type="hidden" id="dateRangeValueStart" name="dateRangeValueStart"/>
        <input type="hidden" id="dateRangeValueEnd" name="dateRangeValueEnd"/>
        <input type="button" class="btn btn-portage btn-sm" id="btn-submit" value="<?=lang('lang.search');?>"/>
    </div>
    <hr/>
    <div class="clearfix">
        <table id="playerroulette-history" class="table table-bordered">
            <thead>
                <th style="min-width:110px;"><?=lang("roulette_report.datetime")?></th>
                <th><?=lang("Username")?></th>
                <th><?=lang("roulette_report.deposit_amount")?></th>
                <th><?=lang("Promo Rule")?></th>
                <th style="min-width:110px;"><?=lang("roulette_report.prize_release_time")?></th>
                <th><?=lang("roulette_report.prize")?></th>
                <th><?=lang("roulette_report.spin_limit")?></th>
                <th><?=lang("roulette_report.spin_count")?></th>
                <th><?=lang('Withdraw Condition');?></th>
                <th style="min-width:300px;"><?=lang("roulette_report.note")?></th>
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
    function playerRouletteHistory(player_id) {
        console.log('player id  : ' + player_id);
        var dataTable = $('#playerroulette-history').DataTable({
            dom: "<'row'<'col-md-12'<'pull-right'f><'pull-right progress-container'>l<'dt-information-summary2 text-info pull-left' i>>><'table-responsive't><'row'<'col-md-12'<'pull-right'p><'dt-information-summary2 text-info pull-left' i>>>",
            autoWidth: false,
            searching: false,
            buttons: [
                {
                    extend: 'colvis',
                    postfixButtons: [ 'colvisRestore' ],
                    className: 'btn-linkwater',
                }
            ],
            columnDefs: [
                //hide content
                { visible: false, targets: [8] },
            ],
            order: [ 0, 'desc' ],

            ajax: function (data, callback, settings) {
                data.extra_search = [
                    {
                        'name':'dateRangeValueStart',
                        'value':$('#dateRangeValueStart').val()
                    },
                    {
                        'name':'dateRangeValueEnd',
                        'value':$('#dateRangeValueEnd').val()
                    },
                    {
                        'name': 'player_id',
                        'value': $('#playerroulettehistoryplayerid').val()
                    }
                ];

                $.post(base_url + 'api/playerRouletteHistory/' + playerId, data, function(data) {
                    callback(data);
                },'json');
            }
        });

        $('#changeable_table #btn-submit').click( function() {
            dataTable.ajax.reload();
        });

        ATTACH_DATATABLE_BAR_LOADER.init('playerroulette-history');
    }
</script>
