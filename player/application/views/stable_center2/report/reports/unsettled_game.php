<!-- ============ Game History ================== -->
<div id="ah-unsettled-game" role="tabpanel" class="tab-pane active">
    <div id="unsettled-game-box" class="report table-responsive">
        <table id="unsettledGameResultTable" width="100%" class="table table-striped table-hover dt-responsive display nowrap"></table>
    </div>
</div>
<script type="text/javascript">
    var unsettledGameTb;

    $('.filter-select-game #game_platform_id').off('change').on('change', function(){
        $('#accountHistory').trigger($.Event('draw.t1t.player-center.report'));
    });

    function unsettledGameHistory() {
        console.log("unsettledGameHistory");
        var div = $("#unsettled-game-box");
        var table_container = $('#unsettledGameResultTable');

        if(unsettledGameTb !== undefined){
            unsettledGameTb.page.len($('#pageLength').val());
            unsettledGameTb.ajax.reload();
            return false;
        }

        var render_log_summary = function(div){
            var log_summary;

            <?php if(!$this->utils->isEnabledFeature('disabled_display_sub_total_row_in_player_center_game_history_report')):?>
            if($('#log-summary').length <= 0){
                log_summary = $('<table>').attr({'id': 'log-summary', 'class': 'table table-striped table-hover dt-responsive display nowrap'});
                log_summary.appendTo(div);

                // log_summary.append($('<div>').append($('<span>').html('<?=lang('cms.totalBetAmount');?>: ')).append($('<span>').addClass('bet-total').html('0.00')));
                // log_summary.append($('<div>').append($('<span>').html('<?=lang('cms.totalResultAmount');?>: ')).append($('<span>').addClass('result-total').html('0.00')));
                // log_summary.append($('<div>').append($('<span>').html('<?=lang('Total Bet + Result Amount');?>: ')).append($('<span>').addClass('bet-result-total').html('0.00')));
                // log_summary.append($('<div>').append($('<span>').html('<?=lang('Total Win');?>: ')).append($('<span>').addClass('win-total').html('0.00')));
                // log_summary.append($('<div>').append($('<span>').html('<?=lang('Total Loss');?>: ')).append($('<span>').addClass('loss-total').html('0.00')));
            }else{
                log_summary = $('#log-summary').empty();
            }
            log_summary.css('text-align', 'right');

            var thead = $('<thead>');
            thead.appendTo(log_summary);

            var tr = $('<tr>');
            $('<th class ="lscss">').append($('<span>').html('<?=lang('sys.gt7');?>')).appendTo(tr);
            $('<th>').append($('<span>').html('<?=lang('mark.bet');?>')).appendTo(tr);
            $('<th>').append($('<span>').html('<?=lang('Result');?>')).appendTo(tr);
            $('<th>').append($('<span>').html('<?=lang('Wins');?>')).appendTo(tr);
            $('<th>').append($('<span>').html('<?=lang('player.ui28');?>')).appendTo(tr);
            $('<th>').append($('<span>').html('<?=lang('lang.bet.plus.result');?>')).appendTo(tr);
            tr.appendTo(thead);

            var tbody = $('<tbody>');
            tbody.appendTo(log_summary);
            <?php endif;?>

            return log_summary;
        };

        render_log_summary(div);

        //$("p").css({"background-color":"yellow","font-size":"200%"});
        //log_summary.css('border', '1px solid #dddddd');
        // var platform_summary;
        // if($('#platform-summary').length <= 0){
        //     platform_summary = $('<div>').attr('id', 'platform-summary');
        //     platform_summary.appendTo(div);
        // }else{
        //     platform_summary = $('#platform-summary');
        // }
        // platform_summary.css('text-align', 'right');

        var columns = [];
        columns.push({
            "name": "game_logs_bet_date",
            "title": "<?=lang('game_logs_bet_date');?>",
            "data": 26,
            "visible": true,
            "orderable": true,
        });
        columns.push({
            "name": "game_log_end_date",
            "title": "<?=lang('Payout Date');?>",
            "data": 0,
            "visible": true,
            "orderable": false,
            "className": "none" // for expandable
        });
        columns.push({
            "name": "game_provider",
            "title": "<?=lang('cms.gameprovider');?>",
            "data": 5,
            "visible": true,
            "orderable": false,
        });
        columns.push({
            "name": "game_name",
            "title": "<?=lang('cms.gamename');?>",
            "data": 7,
            "visible": true,
            "orderable": false,
        });

        columns.push({
            "name": "real_bet_amount",
            "title": "<?=lang('Real Bet');?>",
            "data": 8,
            "visible": true,
            "orderable": false,
        });


        columns.push({
            "name": "bet_amount",
            "title": "<?=lang('Valid Bet');?>",
            "data": 9,
            "visible": true,
            "orderable": false,
        });

        columns.push({
            "name": "result_amount",
            "title": "<?=lang('mark.resultAmount');?>",
            "data": 10,
            "visible": true,
            "orderable": false,
        });

        columns.push({
            "name": "bet_plus_result_amount",
            "title": "<?=lang('lang.bet.plus.result');?>",
            "data": 11,
            "visible": true,
            "orderable": false,
        });

        columns.push({
            "name": "win_amount",
            "title": "<?=lang('Win'); ?>",
            "data": 12,
            "visible": true,
            "orderable": false,
            "className": "none" // for expandable
        });

        columns.push({
            "name": "loss_amount",
            "title": "<?=lang('Loss'); ?>",
            "data": 13,
            "visible": true,
            "orderable": false,
            "className": "none" // for expandable
        });

        columns.push({
            "name": "after_balance",
            "title": "<?=lang('mark.afterBalance');?>",
            "data": 14,
            "visible": true,
            "orderable": false,
            "className": "none" // for expandable
        });

        columns.push({
            "name": "trans_amount",
            "title": "<?=lang('pay.transamount');?>",
            "data": 15,
            "visible": true,
            "orderable": false,
            "className": "none" // for expandable
        });


        columns.push({
            "name": "roundno",
            "title": "<?php echo lang('Round No'); ?>",
            "data": 16,
            "visible": true,
            "orderable": false,
            "className": "none" // for expandable
        });

        columns.push({
            "name": "betDetails",
            "title": "<?=lang('Bet Detail');?>",
            "data": 1,
            "visible": true,
            "orderable": false,
            "className": "none" // for expandable
        });

        columns.push({
            "name": "updated_at",
            "title": "<?=lang('Updated Date');?>",
            "data": 27,
            "visible": true,
            "orderable": false,
            "className": "none" // for expandable
        });

        columns.push({
            "name": "external_uniqueid",
            "title": "<?=lang('External Unique ID');?>",
            "data": 28,
            "visible": true,
            "orderable": false,
            "className": "none" // for expandable
        });

        columns.push({ // for the responsive extention to display control row button
            "title": "&nbsp",
            "data": 1,
            "visible": true,
            "orderable": false,
            "responsivePriority": 1,
            "render": function(){
                return '&nbsp';
            }
        });

        unsettledGameTb = table_container.DataTable($.extend({}, dataTable_options, {
            "responsive": true,
            "pageLength": $('#pageLength').val(),
            "columns": columns,
            columnDefs: [ {  // for the responsive extention to display control row button
                className: 'control',
                orderable: false,
                targets:   -1
            }],
            order: [[0, 'desc']],
            ajax: function(data, callback, settings){
                $.ajax({
                    //url: '/api/player_games_history',
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
                    success: function(json){
                        var log_summary = render_log_summary($("#unsettled-game-box"));
                        var tbody = $('tbody', log_summary);
                        tbody.empty();

                        $.each(json.sub_summary, function(i,v) {
                            var tr = $('<tr>');
                            $('<td>').append($('<span>').html(v.system_code)).appendTo(tr);
                            $('<td>').append($('<span>').html(parseFloat(v.total_bet).toFixed(2))).appendTo(tr);
                            $('<td>').append($('<span>').html(parseFloat(v.total_result).toFixed(2))).appendTo(tr);
                            $('<td>').append($('<span>').html(parseFloat(v.total_win).toFixed(2))).appendTo(tr);
                            $('<td>').append($('<span>').html(parseFloat(v.total_loss).toFixed(2))).appendTo(tr);
                            $('<td>').append($('<span>').html(parseFloat(v.total_bet_result).toFixed(2))).appendTo(tr);
                            tr.appendTo(tbody);
                        });

                        var tr = $('<tr>');
                        $('<td>').append($('<span>').html('<?=lang('cs.total');?>')).appendTo(tr);
                        $('<td>').append($('<span>').addClass('bet-total').html(parseFloat(json.summary[0].total_bet).toFixed(2))).appendTo(tr);
                        $('<td>').append($('<span>').addClass('result-total').html(parseFloat(json.summary[0].total_result).toFixed(2))).appendTo(tr);
                        $('<td>').append($('<span>').addClass('win-total').html(parseFloat(json.summary[0].total_win).toFixed(2))).appendTo(tr);
                        $('<td>').append($('<span>').addClass('loss-total').html(parseFloat(json.summary[0].total_loss).toFixed(2))).appendTo(tr);
                        $('<td>').append($('<span>').addClass('bet-result-total').html(parseFloat(json.summary[0].total_bet_result).toFixed(2))).appendTo(tr);
                        tr.appendTo(tbody);

                        // platform_summary.empty();
                        // $.each(json.sub_summary, function(i,v) {
                        //     platform_summary.append(v.system_code + ' <?=lang("mark.bet"); ?>: ' + parseFloat(v.total_bet).toFixed(2) + '<br>');
                        //     platform_summary.append(v.system_code + ' <?=lang("Result"); ?>: ' + parseFloat(v.total_result).toFixed(2) + '<br>');
                        //     platform_summary.append(v.system_code + ' <?=lang("lang.bet.plus.result"); ?>: ' + parseFloat(v.total_bet_result).toFixed(2) + '<br>');
                        //     platform_summary.append(v.system_code + ' <?=lang("Wins"); ?>: ' + parseFloat(v.total_win).toFixed(2) + '<br>');
                        //     platform_summary.append(v.system_code + ' <?=lang("player.ui28"); ?>: ' + parseFloat(v.total_loss).toFixed(2) + '<br>');
                        // });

                        callback(json);
                    }
                })

            }
            <?php if ($this->utils->getConfig('hide_player_center_history_list_controls_when_no_data')) : ?>
            // OGP-21311: drawCallback not working, use fnDrawCallback instead
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
            <?php endif; ?>
        }));
    }
</script>