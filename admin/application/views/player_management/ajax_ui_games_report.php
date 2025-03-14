<div data-file-info="ajax_ui_games_report.php" data-datatable-selector="#gamesreport-table">
    <form class="form-inline" id="search-form">
        <input type="text" id="reportrange" class="form-control input-sm dateInput inline" data-start="#date_from" data-end="#date_to" data-time="true" />
        <input type="hidden" id="date_from" name="datetime_from"/>
        <input type="hidden" id="date_to" name="datetime_to"/>
        <?php
            $default_timezone = $this->utils->getTimezoneOffset(new DateTime());
            $timezone_offsets = $this->utils->getConfig('timezone_offsets');
            $timezone_location = $this->utils->getConfig('current_php_timezone');
            $force_default_timezone = $this->utils->getConfig('force_default_timezone_option');
        ?>
        <select id="timezone" name="timezone"  class="form-control input-sm">
        <?php if(!$force_default_timezone): ?>
             <?php for($i = 12;  $i >= -12; $i--): ?>
                <option value="<?php echo $i > 0 ? "+{$i}" : $i ;?>" <?php echo ($i==$default_timezone) ? 'selected' : ''?>> <?php echo $i >= 0 ? "+{$i}" : $i ;?></option>
            <?php endfor;?>
        <?php else: ?>
            <option value="<?=$force_default_timezone;?>" selected> <?= $force_default_timezone;?></option>
        <?php endif;?>
        </select>
        <select name="group_by" id="group_by" class="form-control input-sm">
            <option value="game_platform_and_player" selected=selected ><?php echo lang('Group By Game Platform'); ?></option>
            <option value="game_type_and_player" ><?php echo lang('Group By Game Type'); ?></option>
            <option value="game_description_and_player" ><?php echo lang('Group By Game'); ?></option>
        </select>
        <input type="button" class="btn btn-portage btn-sm" id="btn-submit" value="<?=lang('lang.search');?>"/>

        <input type="hidden" name="search_by" value="2">
        <input type="hidden" name="player_id" value="<?= $player_id ?>">
        <input type="hidden" name="request_type" value="">
        <input type="hidden" name="request_grade" value="">
        <input type="hidden" name="level_from" value="">
        <input type="hidden" name="level_to" value="">
    </form>
    <hr />
    <div class="clearfix">
        <table id="gamesreport-table" class="table table-bordered">
            <thead>
                <tr>
                    <th><?=lang('Game Platform')?></th>
                    <th><?=lang('Game Type')?></th>
                    <th><?=lang('Game')?></th>
                    <th><?=lang('Player Id')?></th>
                    <th><?=lang('Player Username')?></th>
                    <th><?=lang('Player Tag')?></th>
                    <th><?=lang('Player Level')?></th>
                    <th><?=lang('aff.as03')?></th>
                    <th><?=lang('Affiliate Tag')?></th>
                    <th><?=lang('aff.as24')?></th>
                    <th><?=lang('report.g09')?></th>
                    <th><?=lang('Agency Payout')?> <i class="fa fa-exclamation-circle" data-toggle="tooltip" title="<?=lang("games_report_payout_formula")?>" data-container="body"></i></th>
                    <!-- <th><?=lang('Average Bet')?></th> -->
                    <th><?=lang('report.g10')?></th>
                    <th><?=lang('report.g11')?></th>
                    <th><?=lang('Game Revenue')?> <i class="fa fa-exclamation-circle" data-toggle="tooltip" title="<?=lang("games_report_revenue_formula")?>" data-container="body"></i></th>
                    <th><?=lang('Game Revenue %')?></th>
                </tr>
            </thead>

            <tfoot>
                <tr>
                    <th class="text-primary"><?=lang('Total')?></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th class="text-right text-primary"><span class="total-player">0</span></th>
                    <th class="text-right text-primary"><span class="total-bet">0.00</span></th>
                    <th class="text-right text-primary"><span class="total-payout">0.00</span></th>
                    <!-- <th class="text-right text-primary"><span class="total-ave-bet">0</span></th> -->
                    <th class="text-right text-primary"><span class="total-win">0.00</span></th>
                    <th class="text-right text-primary"><span class="total-loss">0.00</span></th>
                    <th class="text-right text-primary"><span class="total-revenue">0.00</span></th>
                    <th class="text-left text-primary"><span class="total-revenue-percent">0.00%</span></th>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<script type="text/javascript">

    var message = {
        gameType         : '<?= lang('Select Game Type'); ?>',
        distinctPlayers  : '<?= lang('Distinct Players'); ?>',
        totalBets       : '<?=lang('report.g09')?>',
        totalWins       : '<?=lang('report.g10')?>',
        totalLosses       : '<?=lang('report.g11')?>',
        totalPayouts     : '<?=lang('Payout')?>',
        totalRevenue     : '<?=lang('Game Revenue')?>',
        totalRevenuePercent     : '<?=lang('Game Revenue %')?>',
        Revenue     : '<?=lang('Game Revenue')?>',
        RevenuePercent     : '<?=lang('Game Revenue %')?>',
        Wins     : '<?=lang('player.ui27')?>',
        Loss    : '<?=lang('player.ui28')?>',
        Payout    : '<?=lang('Payout')?>',
        playerId         : '<?=lang('Player Id')?>',
        playerUsername   : '<?=lang('Player Username')?>',
        playerTag   : '<?=lang('Player Tag')?>',
        All  : '<?=lang('lang.all')?>' ,
        total: '<?=lang('This Total')?>',
        subTotal: '<?=lang('Subtotal')?>',
        count: '<?=lang('Count')?>',
    };

    function addCommas(nStr){
        nStr += '';
        var x = nStr.split('.');
        var x1 = x[0];
        var x2 = x.length > 1 ? '.' + x[1] : '';
        var rgx = /(\d+)(\d{3})/;
        while (rgx.test(x1)) {
            x1 = x1.replace(rgx, '$1' + ',' + '$2');
        }
        return x1 + x2;
    }
    function gamesReport() {
        let player_id = <?= $player_id ?>;
        var dataTable = $('#gamesreport-table').DataTable({
            scrollX: true,
            buttons: [
                {
                    extend: 'colvis',
                    postfixButtons: [ 'colvisRestore' ],
                    className: '<?=$this->utils->getConfig('use_new_sbe_color') ? 'btn-linkwater' : ''?>',
                }
            ],
            columnDefs: [
                { className: 'text-right', targets: [6,7,8,9,10,11] },
                { visible: false, targets: [ <?php echo implode(',', $this->config->item('game_reports_hidden_cols')); ?>  ] }
            ],
            "order": [ 0, 'asc' ],

            // SERVER-SIDE PROCESSING
            processing: true,
            serverSide: true,
            ajax: function (data, callback, settings) {

                var formData = $('#search-form').serializeArray();
                // console.log("form data ==>", formData);
               // formData.push({name: 'game_type', value: loadParams.gameTypeId});
         
                // formData.push({
                //     'name' : 'group_by',
                //     'value' : 'game_platform_and_player',
                // });
                // formData.push({
                //     'name' : 'timezone',
                //     'value' : '+8',
                // });
                data.extra_search = formData;
                $.post(base_url + "api/gameReports/" + player_id , data, function(data) {
                    // console.log("data ==>", data);
                 if(data.recordsTotal > 0){
                    $('.total-player').html('<i class="text-success" style="font-size:9;padding-right:10px;">('+message.distinctPlayers+')</i>'+data.summary[0].total_player);
                    $('.total-bet').text(addCommas(parseFloat(data.summary[0].total_bet).toFixed(2)));
                    $('.total-payout').text(addCommas(parseFloat(data.summary[0].total_payout).toFixed(2)));
                    $('.total-ave-bet').html(addCommas(parseFloat(data.summary[0].total_ave_bet).toFixed(2)) +'<br><i class="text-success" style="font-size:10px;">('+message.count+': '+data.summary[0].total_ave_count+')</i>' );
                    $('.total-win').text(addCommas(parseFloat(data.summary[0].total_win).toFixed(2)));
                    $('.total-loss').text(addCommas(parseFloat(data.summary[0].total_loss).toFixed(2)));
                    $('.total-revenue').text(addCommas(parseFloat(data.summary[0].total_revenue).toFixed(2)));
                    $('.total-revenue-percent').text(data.summary[0].total_revenue_percent + '%');

     

                }else{
                    if ( $.fn.DataTable.isDataTable('#player-bets-per-game') ) {
                       $('#player-bets-per-game').DataTable().destroy();
                    }
                    $('#player-bets-per-game-container').hide();
                 }
                    callback(data);
                    if ( dataTable.rows( { selected: true } ).indexes().length === 0 ) {
                        dataTable.buttons().disable();
                    }
                    else {
                        dataTable.buttons().enable();
                    }
                }, 'json');
           }
           ,
           drawCallback : function( settings ) {
               console.log('drawCallback.arguments', arguments);
               <?php if( ! empty($enable_freeze_top_in_list) ): ?>
                var _scrollBodyHeight = window.innerHeight;
                _scrollBodyHeight -= $('.navbar-fixed-top').height();
                _scrollBodyHeight -= $('.dataTables_scrollHead').height();
                _scrollBodyHeight -= $('.dataTables_scrollFoot').height();
                _scrollBodyHeight -= $('#myTable_paginate').closest('.panel-body').height();
                _scrollBodyHeight -= 44;// buffer
                $('.dataTables_scrollBody').css({'max-height': _scrollBodyHeight+ 'px'});
            <?php endif; // EOF if( ! empty($enable_freeze_top_in_list) ):... ?>
           },
           "rowCallback": function( row, data, index ) {
           },
        });

        $('#search-form #btn-submit').click( function(e) {
            e.preventDefault();
            dataTable.ajax.reload();
        });

        // $('.export_excel').click(function(){
        //     var d = {'extra_search':$('#form-filter').serializeArray(), 'draw':1, 'length':-1, 'start':0};
        //     $.post(site_url('/export_data/player_reports'), d, function(data){
        //         if(data && data.success){
        //             $('body').append('<iframe src="'+data.link+'" frameborder="0" scrolling="no" style="border:0px;width:0px;height:0px"></iframe>');
        //         }else{
        //             alert('export failed');
        //         }
        //     });
        // });

        ATTACH_DATATABLE_BAR_LOADER.init('gamesreport-table');
    }
</script>
