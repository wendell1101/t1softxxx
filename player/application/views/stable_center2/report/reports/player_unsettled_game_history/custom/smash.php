<style>
    .unsettled-game-tfoot tr td {
        border: 1px solid #ddd;
    }
</style>

<div id="ah-unsettled-game" role="tabpanel" class="tab-pane active">
    <div id="unsettled-game-box" class="report table-responsive">
        <table id="unsettledGameResultTable" width="100%" class="table table-bordered table-striped table-hover dt-responsive display nowrap">
            <tfoot class="unsettled-game-tfoot"></tfoot>
        </table>
    </div>
</div>

<script type="text/javascript">
    var unsettledGameTb;

    $('.filter-select-game #game_platform_id').off('change').on('change', function() {
        $('#accountHistory').trigger($.Event('draw.t1t.player-center.report'));
    });

    function unsettledGameHistory() {
        var table_container = $('#unsettledGameResultTable');

        if(unsettledGameTb !== undefined) {
            unsettledGameTb.page.len($('#pageLength').val());
            unsettledGameTb.ajax.reload();
            return false;
        }

        var columns = [];
        columns.push({
            "name": "game_log_end_date",
            "title": "<?=lang('game_logs_bet_date');?>",
            "data": 0,
            "visible": true,
            "orderable": true,
            "responsivePriority": -1
        });
        columns.push({
            "name": "game_provider",
            "title": "<?=lang('cms.gameprovider');?>",
            "data": 5,
            "visible": false,
            "orderable": true,
            "responsivePriority": 4
        });
        columns.push({
            "name": "game_name",
            "title": "<?=lang('cms.gamename');?>",
            "data": 7,
            "visible": true,
            "orderable": true,
            "responsivePriority": 6
        });
        columns.push({
            "name": "real_bet_amount",
            "title": "<?=lang('Real Bet');?>",
            "data": 8,
            "visible": true,
            "orderable": true,
            "responsivePriority": 7
        });
        columns.push({
            "name": "bet_amount",
            "title": "<?=lang('Valid Bet');?>",
            "data": 9,
            "visible": true,
            "orderable": true,
            "responsivePriority": 8
        });
        columns.push({
            "name": "win_amount",
            "title": "<?=lang('Win Amount'); ?>",
            "data": 12,
            "visible": true,
            "orderable": true,
            "responsivePriority": 11
        });
        columns.push({
            "name": "loss_amount",
            "title": "<?=lang('Loss Amount'); ?>",
            "data": 13,
            "visible": true,
            "orderable": true,
            "responsivePriority": 12
        });
        columns.push({
            "name": "after_balance",
            "title": "<?=lang('mark.afterBalance');?>",
            "data": 14,
            "visible": true,
            "orderable": true,
            "responsivePriority": 13
        });
        columns.push({
            "name": "roundno",
            "title": "<?=lang('Round No'); ?>",
            "data": 16,
            "visible": true,
            "orderable": true,
            "responsivePriority": 15
        });
        columns.push({
            "name": "betDetails",
            "title": "<?=lang('Bet Detail');?>",
            "data": 18,
            "visible": true,
            "orderable": true,
            "responsivePriority": 17
        });
        columns.push({
            "name": "status",
            "title": "<?=lang('Status');?>",
            "data": 28,
            "visible": true,
            "orderable": true,
            "responsivePriority": 27
        });
        columns.push({
            "name": "player_user_name",
            "title": "<?=lang('Player Username');?>",
            "data": 2,
            "visible": false,
            "orderable": false,
            "responsivePriority": 1
        });
        columns.push({
            "name": "affiliate_username",
            "title": "<?=lang('Affiliate Username');?>",
            "data": 3,
            "visible": false,
            "orderable": false,
            "responsivePriority": 2
        });
        columns.push({
            "name": "game_type",
            "title": "<?=lang('cms.gametype');?>",
            "data": 6,
            "visible": false,
            "orderable": false,
            "responsivePriority": 5
        });
        columns.push({
            "name": "result_amount",
            "title": "<?=lang('mark.resultAmount');?>",
            "data": 10,
            "visible": false,
            "orderable": false,
            "responsivePriority": 9
        });
        columns.push({
            "name": "bet_plus_result_amount",
            "title": "<?=lang('lang.bet.plus.result');?>",
            "data": 11,
            "visible": false,
            "orderable": false,
            "responsivePriority": 10
        });
        columns.push({
            "name": "trans_amount",
            "title": "<?=lang('pay.transamount');?>",
            "data": 15,
            "visible": false,
            "orderable": false,
            "responsivePriority": 14
        });
        columns.push({
            "name": "flag",
            "title": "<?=lang('player.ut10');?>",
            "data": 19,
            "visible": false,
            "orderable": false,
            "responsivePriority": 18
        });
        columns.push({ // for the responsive extention to display control row button
            "title": "&nbsp",
            "data": 1,
            "visible": false,
            "orderable": false,
            "responsivePriority": 0,
            "render": function(){
                return '&nbsp';
            }
        });

        unsettledGameTb = table_container.DataTable($.extend({}, dataTable_options, {
            "pageLength": $('#pageLength').val(),
            "columns": columns,
            "columnDefs": [{  // for the responsive extention to display control row button
                className: 'control',
                orderable: false,
                targets: -1
            }],
            "order": [[0, 'desc']],
            ajax: function(data, callback, settings) {

                //console.log(data);

                $.ajax({
                    url: '/ajax/account_history/player_games_history',
                    type: 'post',
                    data: $.extend({}, data, {
                        extra_search: [
                            {
                                'name':'dateRangeValueStart',
                                'value': $('#sdate').val(),
                            },
                            {
                                'name':'dateRangeValueEnd',
                                'value': $('#edate').val(),
                            },
                            {
                                'name':'by_player_center_unsettled',
                                'value': true,
                            },
                            {
                                'name':'game_code',
                                'value': $('#game_code').val(),
                            },
                            {
                                'name':'by_bet_type',
                                'value': 2, // 1: Settled; 2: Unsettled
                            }
                        ]
                    }),
                    success: function(json) {
                        var tfoot = $('.unsettled-game-tfoot');
                        tfoot.empty();
                        var sub_real_bet_total = 0;
                        var sub_bet_total = 0;
                        var sub_win_total = 0;
                        var sub_loss_total = 0;
                        
                        json.data.forEach(data => {
                            var sub_real_bet_amount = data[8];
                            var sub_bet_amount = data[9];
                            var sub_win_amount = data[12];
                            var sub_loss_amount = data[13];

                            if(sub_real_bet_amount == 'N/A' || sub_real_bet_amount == '' || isNaN(sub_real_bet_amount)) {
                                sub_real_bet_amount = 0;
                            }

                            if(sub_bet_amount == 'N/A' || sub_bet_amount == '' || isNaN(sub_bet_amount)) {
                                sub_bet_amount = 0;
                            }

                            if(sub_win_amount == 'N/A' || sub_win_amount == '' || isNaN(sub_win_amount)) {
                                sub_win_amount = 0;
                            }

                            if(sub_loss_amount == 'N/A' || sub_loss_amount == '' || isNaN(sub_loss_amount)) {
                                sub_loss_amount = 0;
                            }

                            sub_real_bet_total += parseFloat(sub_real_bet_amount);
                            sub_bet_total += parseFloat(sub_bet_amount);
                            sub_win_total += parseFloat(sub_win_amount);
                            sub_loss_total += parseFloat(sub_loss_amount);
                        });

                        //console.log(json);

                        <?php if(!$this->utils->isEnabledFeature('disabled_display_sub_total_row_in_player_center_game_history_report')) { ?>
                            var tr = $('<tr>');
                            $('<td colspan="2" style="text-align: right;">').append($('<strong>').html('<?=lang('Sub Total');?>')).appendTo(tr);
                            $('<td>').append($('<strong>').addClass('bet-total').html(sub_real_bet_total.toFixed(2))).appendTo(tr);
                            $('<td>').append($('<strong>').addClass('bet-total').html(sub_bet_total.toFixed(2))).appendTo(tr);
                            $('<td>').append($('<strong>').addClass('win-total').html(sub_win_total.toFixed(2))).appendTo(tr);
                            $('<td>').append($('<strong>').addClass('loss-total').html(sub_loss_total.toFixed(2))).appendTo(tr);
                            $('<td colspan="4">').appendTo(tr);
                            tr.appendTo(tfoot);
                        <?php } ?>

                        var tr = $('<tr>');
                        $('<td colspan="2" style="text-align: right;">').append($('<strong>').html('<?=lang('Total');?>')).appendTo(tr);
                        $('<td>').append($('<strong>').addClass('bet-total').html(parseFloat(json.summary[0].real_total_bet).toFixed(2))).appendTo(tr);
                        $('<td>').append($('<strong>').addClass('bet-total').html(parseFloat(json.summary[0].total_bet).toFixed(2))).appendTo(tr);
                        $('<td>').append($('<strong>').addClass('win-total').html(parseFloat(json.summary[0].total_win).toFixed(2))).appendTo(tr);
                        $('<td>').append($('<strong>').addClass('loss-total').html(parseFloat(json.summary[0].total_loss).toFixed(2))).appendTo(tr);
                        $('<td colspan="4">').appendTo(tr);
                        tr.appendTo(tfoot);

                        callback(json);
                    }
                })
            }
            <?php if($this->utils->getConfig('hide_player_center_history_list_controls_when_no_data')) { ?>
            , fnDrawCallback: function() {
                var wrapper = $(this).parents('.dataTables_wrapper');
                var status = $(wrapper).find('.dt-row:last');
                if ($(this).find('tbody td.dataTables_empty').length > 0) {
                    $(status).hide();
                }
                else {
                    $(status).show();
                }
            }
            <?php } ?>
        }));
    }
</script>