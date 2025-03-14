<div data-file-info="ajax_ui_shoppingpointhistory.php" data-datatable-selector="#shopping-history">
    <div class="form-inline">        
        <input type="text" id="reportrange" class="form-control input-sm dateInput inline" data-start="#dateRangeValueStart" data-end="#dateRangeValueEnd" data-time="true"/>
        <input type="hidden" id="shoppingPointhistoryPlayerId" value="<?=$player_id ?>">    
        <input type="hidden" id="dateRangeValueStart" name="dateRangeValueStart"/>
        <input type="hidden" id="dateRangeValueEnd" name="dateRangeValueEnd"/>
        <input type="button" class="btn btn-portage btn-sm" id="btn-shop-point-search" value="<?=lang('lang.search');?>" onclick="shoppingPointHistory();"/>
    </div>
    <hr>
    <div class="clearfix">
        <table id="shopping-history" class="table table-bordered">
            <thead>
                <tr>
                    <th><?=lang('Date') ?></th>
                    <th><?=lang('Point') ?></th>
                    <th><?=lang('Before Balance') ?></th>
                    <th><?=lang('After Balance') ?></th>
                    <th><?=lang('Transaction type') ?></th>
                    <th><?=lang('Calculated Points') ?></th>
                    <th><?=lang('Forfeited Points') ?></th>
                    <th><?=lang('Points Limit') ?></th>
                    <th><?=lang('Points Limit Type') ?></th>
                    <th><?=lang('Date Covered') ?></th>                    
                    <th><?=lang('Remarks')?></th>
                </tr>
            </thead>
        </table>
    </div>
</div>

<template class="review_haba_api_results_btn_tpl">
    <div class="btn btn-default btn-xs review_haba_api_results_btn" data-results_counter="${results_counter}" data-playerpromoid="${playerpromo_id}" data-toggle="tooltip" title="<?=lang('HabaApiResultsList')?>">
        <span class="glyphicon glyphicon glyphicon-list"></span>&nbsp;
    </div>
</template><!---->

<script type="text/javascript">
    function shoppingPointHistory(player_id) {
        var shopPointHistoryDataTable = $('#shopping-history').DataTable({
            dom: "<'row'<'col-md-12'<'pull-right'f><'pull-right progress-container'>l<'dt-information-summary2 text-info pull-left' i>>><'table-responsive't><'row'<'col-md-12'<'pull-right'p><'dt-information-summary2 text-info pull-left' i>>>",
            order: [[0, 'desc']],
            autoWidth: false,
            columnDefs: [
                { visible: false, targets: [] }
            ],
            processing: true,
            serverSide: true,
            ajax: function (data, callback, settings) {
                data.extra_search = [
                    {
                        'name':'shopping_point_date_from',
                        'value':$('#dateRangeValueStart').val()
                    },
                    {
                        'name': 'shopping_point_date_to',
                        'value': $('#dateRangeValueEnd').val()
                    },
                    {
                        'name': 'player_id',
                        'value': $('#shoppingPointhistoryPlayerId').val()
                    }
                ];

                var _ajax = $.post(base_url + 'api/shoppingPointHistory/' + player_id, data, function(data){
                    callback(data);
                },'json');
                _ajax.done(function(data, textStatus, jqXHR){
                    appendHabaResults.appendBtnBtn()
                });

            }
        });

        $('#btn-shop-point-search').click( function() {
            shopPointHistoryDataTable.ajax.reload();
        });

        ATTACH_DATATABLE_BAR_LOADER.init('shopping-history');
    }
</script>
