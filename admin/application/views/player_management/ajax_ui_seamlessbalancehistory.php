<div data-file-info="ajax_ui_seamlessbalancehistory.php" data-datatable-selector="#seamless-balance-history">
    <div class="form-inline">
        <input type="text" id="reportrange" class="form-control input-sm dateInput inline" data-start="#dateRangeValueStart" data-end="#dateRangeValueEnd" data-time="true"/>
        <input type="hidden" id="seamlessBalanceHistoryPlayerId" value="<?=$player_id ?>">
        <input type="hidden" id="dateRangeValueStart" name="dateRangeValueStart"/>
        <input type="hidden" id="dateRangeValueEnd" name="dateRangeValueEnd"/>
        <select name="by_game_platform_id" id="seamlessBalanceHistoryGamePlatformId" class="form-control input-sm">
            <option value=""><?=lang('lang.all') . ' ' . lang('cms.gameprovider')?></option>
            <?php foreach ($game_platforms as $game_platform): ?>
                <option value="<?=$game_platform['id']?>"><?=$game_platform['system_code']?></option>
            <?php endforeach?>
        </select>
        <input type="button" class="btn btn-portage btn-sm" id="btn-seamless-balance-search" value="<?=lang('lang.search');?>" onclick="seamlessBalanceHistory();"/>
    </div>
    <hr>
    <div class="clearfix">
        <table id="seamless-balance-history" class="table table-bordered">
            <thead>
                <tr>
                    <th><?=lang('Date') ?></th>
                    <th><?=lang('ID') ?></th>
                    <th><?=lang('Amount') ?></th>
                    <th><?=lang('Before Balance') ?></th>
                    <th><?=lang('After Balance') ?></th>
                    <th><?=lang('Transaction Type') ?></th>
                    <th><?=lang('Game Platform') ?></th>
                    <th><?=lang('Round') ?></th>
                    <th><?=lang('External Unique ID') ?></th>
                    <th><?=lang('Details') ?></th>
                </tr>
            </thead>
        </table>
    </div>
</div>

<!--<template class="review_haba_api_results_btn_tpl">
    <div class="btn btn-default btn-xs review_haba_api_results_btn" data-results_counter="${results_counter}" data-playerpromoid="${playerpromo_id}" data-toggle="tooltip" title="<?=lang('HabaApiResultsList')?>">
        <span class="glyphicon glyphicon glyphicon-list"></span>&nbsp;
    </div>
</template>-->
<?php if ($this->utils->isEnabledFeature('export_excel_on_queue')) {?>
    <form id="_export_excel_queue_form" class="hidden" method="POST" target="_blank">
        <input name='json_search' type="hidden">
    </form>
<?php }?>

<script type="text/javascript">
    var optionSeamlessDatePicker = {
            "timePicker": true,
            "linkedCalendars": false,
            "timePicker24Hour": true,
            "timePickerSeconds": true,
            "locale": {"format": "YYYY-MM-DD HH:mm:ss"},
            "maxDate": moment(),
            ranges: {
                'Today': [moment(), moment()],
                'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                'Last 30 Days': [moment().subtract(29, 'days'), moment()],
                'This Month': [moment().startOf('month'), moment().endOf('month')],
                'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
            },
            "alwaysShowCalendars": true,

        };

    /*$(function() {

        $('#reportrange').daterangepicker(optionSeamlessDatePicker, function(start, end, label) {
            console.log('callback');
            //cehck if start date and end date does not belong to same month
            if(start.format('YYYY-MM') != end.format('YYYY-MM')){
                alert("Start and end date should belong in the same month.");
                var endOfMonth = moment(start.format('YYYY-MM-DD HH:mm:ss')).endOf('month').format('YYYY-MM-DD 23:59:59');
                console.log(endOfMonth);
                this.setEndDate(endOfMonth);
            }
        });

    });*/

    var cb = function(start, end, label) {
        console.log('callback');
        //cehck if start date and end date does not belong to same month
        if(start.format('YYYY-MM') != end.format('YYYY-MM')){
            alert("Start and end date should belong in the same month.");
            var endOfMonth = moment(start.format('YYYY-MM-DD HH:mm:ss')).endOf('month').format('YYYY-MM-DD 23:59:59');
            console.log(endOfMonth);
            this.setEndDate(endOfMonth);
        }
    }


    function seamlessBalanceHistory(player_id) {
        var seamlessBalanceHistoryDataTable = $('#seamless-balance-history').DataTable({
            dom: "<'row'<'col-md-12'<'pull-right'B><'pull-right'f><'pull-right progress-container'>l<'dt-information-summary2 text-info pull-left' i>>><'table-responsive't><'row'<'col-md-12'<'pull-right'p><'dt-information-summary2 text-info pull-left' i>>>",
            order: [[0, 'desc'],[1, 'desc']],
            autoWidth: false,
            columnDefs: [
                { visible: false, targets: [] },
                { className: 'text-right', targets: [2,3,4] },
            ],
            buttons: [
                {
                    text: "<?= lang('CSV Export'); ?>",
                    className:'btn btn-sm btn-portage btn-exportcsv',
                    action: function ( e, dt, node, config ) {
                        extra_search = [
                            {
                                'name':'date_from',
                                'value':$('#dateRangeValueStart').val()
                            },
                            {
                                'name': 'date_to',
                                'value': $('#dateRangeValueEnd').val()
                            },
                            {
                                'name': 'player_id',
                                'value': $('#seamlessBalanceHistoryPlayerId').val()
                            },
                            {
                                'name': 'by_game_platform_id',
                                'value': $('#seamlessBalanceHistoryGamePlatformId').val()
                            }
                        ];
                        var d = {'extra_search': extra_search, 'export_format': 'csv', 'export_type': 'queue',
                            'draw':1, 'length':-1, 'start':0};
                            $("#_export_excel_queue_form").attr('action', site_url('/export_data/seamless_balance_history/'+ player_id));
                            $("#_export_excel_queue_form [name=json_search]").val(JSON.stringify(d));
                            $("#_export_excel_queue_form").submit();
                    }
                }
            ],
            processing: true,
            serverSide: true,
            ajax: function (data, callback, settings) {
                let start_date = $('#dateRangeValueStart').val();
                let end_date = $('#dateRangeValueEnd').val();

                if(moment(start_date).format('YYYY-MM') != moment(end_date).format('YYYY-MM')){
                    alert("Start and end date should belong in the same month.");
                    var maxDate = moment(start_date).endOf('month').format('YYYY-MM-DD 23:59:59');
                    $('#dateRangeValueEnd').val(maxDate);
                    $('#reportrange').data("daterangepicker").setEndDate(maxDate);
                }

                data.extra_search = [
                    {
                        'name':'date_from',
                        'value':$('#dateRangeValueStart').val()
                    },
                    {
                        'name': 'date_to',
                        'value': $('#dateRangeValueEnd').val()
                    },
                    {
                        'name': 'player_id',
                        'value': $('#seamlessBalanceHistoryPlayerId').val()
                    },
                    {
                        'name': 'by_game_platform_id',
                        'value': $('#seamlessBalanceHistoryGamePlatformId').val()
                    }
                ];

                var _ajax = $.post(base_url + 'api/seamlessBalanceHistory/' + player_id, data, function(data){
                    if(data.recordsTotal == 0){
                        $('.btn-exportcsv').addClass('disabled');
                    } else {
                        $('.btn-exportcsv').removeClass('disabled');
                    }
                    callback(data);
                },'json');
                _ajax.done(function(data, textStatus, jqXHR){
                    appendHabaResults.appendBtnBtn()
                });

            }
        });

        $('#btn-seamless-balance-search').click( function() {
            seamlessBalanceHistoryDataTable.ajax.reload();
        });


        ATTACH_DATATABLE_BAR_LOADER.init('seamless-balance-history');
    }

</script>
