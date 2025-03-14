<style type="text/css">
#timezone{
    height: 36px;
}

.select2-container--default .select2-selection--single{
    padding-top: 2px;
    height: 35px;
    font-size: 1.2em;
    position: relative;
    border-radius: 0;
    font-size:12px;;
}
.removeLink {
    text-decoration: none;
    color : #222222;
}
.removeLink:hover {
    text-decoration:none;
    color : #222222;
    cursor:text;
}
.tooltip-inner {
    white-space: nowrap;
    min-width: 400px;
}

</style>
<div class="panel panel-primary hidden">

    <div class="panel-heading">
        <h4 class="panel-title">
            <i class="fa fa-search"></i> <?=lang("lang.search")?>
            <span class="pull-right">
                <a data-toggle="collapse" href="#collapseGamesReport" class="btn btn-xs <?=$this->utils->getConfig('use_new_sbe_color') ? 'btn-primary' : 'btn-info'?> <?=$this->config->item('default_open_search_panel') ? '' : 'collapsed'?>"></a>
            </span>
        </h4>
    </div>

    <div id="collapseGamesReport" class="panel-collapse <?=$this->config->item('default_open_search_panel') ? '' : 'collapse in'?>">
        <div class="panel-body">
            <form id="form-filter" class="form-horizontal" method="GET" onsubmit="return validateForm();">
                <div class="row">
                    <!-- Date -->
                    <div class="col-md-4 col-lg-4">
                        <label class="control-label" for="group_by"><?=lang('Date')?> </label>
                        <input class="form-control dateInput input-sm" id="datetime_range" data-start="#datetime_from" data-end="#datetime_to" data-time="false"/>
                        <input type="hidden" id="datetime_from" name="datetime_from" value="<?=$conditions['datetime_from'];?>"/>
                        <input type="hidden" id="datetime_to" name="datetime_to" value="<?=$conditions['datetime_to'];?>"/>
                    </div>
                     <!-- Timezone( + - ) hr -->
                    <div class="col-md-2 col-lg-2">
                        <label class="control-label" for="group_by"><?=lang('Timezone of the game')?></label>
                        <!-- <input type="number" id="timezone" name="timezone" class="form-control input-sm " value="<?=$conditions['timezone'];?>" min="-12" max="12"/> -->
                        <?php
                        $default_timezone = $this->utils->getTimezoneOffset(new DateTime());
                        $timezone_offsets = $this->utils->getConfig('timezone_offsets');
                        $timezone_location = $this->utils->getConfig('current_php_timezone');
                        ?>
                        <select id="timezone" name="timezone"  class="form-control input-sm">
                         <?php for($i = 12;  $i >= -12; $i--): ?>
                             <?php if($conditions['timezone'] || $conditions['timezone'] == '0' ): ?>
                                 <option value="<?php echo $i > 0 ? "+{$i}" : $i ;?>" <?php echo ($i == $conditions['timezone']) ? 'selected' : ''?>> <?php echo $i > 0 ? "+{$i}" : $i ;?>:00</option>
                             <?php else: ?>
                                <option value="<?php echo $i > 0 ? "+{$i}" : $i ;?>" <?php echo ($i==$default_timezone) ? 'selected' : ''?>> <?php echo $i >= 0 ? "+{$i}" : $i ;?></option>
                            <?php endif;?>
                        <?php endfor;?>
                        </select>
                        <!-- <div class="" style="">
                            <i class="text-info" style="font-size:10px;"><?php echo lang('System Timezone') ?>: (GMT <?php echo ( $default_timezone >= 0) ? '+'. $default_timezone  : $default_timezone; ?>) <?php echo $timezone_location ;?></i>
                        </div> -->
                    </div>
                    <!-- Player Username -->
                    <div class="col-md-2">
                        <label class="control-label" for="username"><?=lang('Player Username')?> </label>
                        <input type="text" name="username" id="username" class="form-control input-sm"
                            value='<?php echo $conditions["username"]; ?>'/>
                    </div>
                    <!-- Group By -->
                    <div class="col-md-2">
                        <label class="control-label" for="group_by"><?=lang('report.g14')?> </label>
                        <select name="group_by" id="group_by" class="form-control input-sm">
                            <option value="game_platform_id" <?php echo $conditions["group_by"] == 'game_platform_id' ? "selected=selected" : ''; ?> ><?php echo lang('Game Platform'); ?></option>
                            <option value="game_type_id" <?php echo $conditions["group_by"] == 'game_type_id' ? "selected=selected" : ''; ?>><?php echo lang('Game Type'); ?></option>
                            <option value="game_description_id" <?php echo $conditions["group_by"] == 'game_description_id' ? "selected=selected" : ''; ?>><?php echo lang('Game'); ?></option>
                            <option value="player_id" <?php echo $conditions["group_by"] == 'player_id' ? "selected=selected" : ''; ?> ><?php echo lang('Player'); ?></option>

                            <option value="game_platform_and_player" <?php echo $conditions["group_by"] == 'game_platform_and_player' ? "selected=selected" : ''; ?> ><?php echo lang('Player And Game Platform'); ?></option>
                            <option value="game_type_and_player" <?php echo $conditions["group_by"] == 'game_type_and_player' ? "selected=selected" : ''; ?> ><?php echo lang('Player And Game Type'); ?></option>
                            <option value="game_description_and_player" <?php echo $conditions["group_by"] == 'game_description_and_player' ? "selected=selected" : ''; ?> ><?php echo lang('Player And Game'); ?></option>

                            <option value="aff_id" <?php echo $conditions["group_by"] == 'aff_id' ? "selected=selected" : ''; ?> ><?php echo lang('Affiliate'); ?></option>
                            <option value="agent_id" <?php echo $conditions["group_by"] == 'agent_id' ? "selected=selected" : ''; ?> ><?php echo lang('Agency'); ?></option>
                        </select>
                    </div>
                    <!-- Search Unsettle Game -->
                    <div class="col-md-2" style="display: none">
                        <label class="checkbox-inline">
                            <input type="checkbox" name="search_unsettle_game" id="search_unsettle_game"
                                   value='true' <?php echo $conditions["search_unsettle_game"] ? 'checked="checked"' : ''; ?> >
                            <?php echo lang('Search Unsettle Game'); ?>
                        </label>
                    </div>
                </div>
                <div class="row">
                    <!-- Agent Username -->
                    <div class="col-md-4">
                        <label class="control-label" for="agent_name"><?=lang('Agent Username')?> </label>
                        <div class="input-group">
                            <input type="text" name="agent_name" id="agent_name" class="form-control input-sm" value='<?php echo $conditions["agent_name"]; ?>'/>
                            <span class="input-group-addon input-sm">
                                <input type="checkbox" name="include_all_downlines"  <?php echo $conditions['include_all_downlines']  == 'on' ? 'checked="checked"' : '' ?>/>
                                <?=lang('Include Downlines')?>
                            </span>
                        </div>
                    </div>
                    <!-- Affiliate Username -->
                    <div class="col-md-4">
                        <label class="control-label" for="affiliate_username"><?=lang('Affiliate Username')?> </label>
                        <!-- <input type="text" name="affiliate_username" id="affiliate_username" class="form-control input-sm"
                        value='<?php echo $conditions["affiliate_username"]; ?>'/> -->
                        <div class="input-group">
                            <input type="text" name="affiliate_username" id="affiliate_username" class="form-control input-sm"
                        value='<?php echo $conditions["affiliate_username"]; ?>'/>
                            <span class="input-group-addon input-sm">
                                <input type="checkbox" name="include_all_downlines_aff"  <?php echo $conditions['include_all_downlines_aff']  == 'on' ? 'checked="checked"' : '' ?>/>
                                <?=lang('Include All Downline Affiliates')?>
                            </span>
                        </div>
                    </div>
                    <!-- Referrer -->
                    <div class="col-md-2">
                        <label for="referrer" class="control-label"><?=lang('Referrer')?></label>
                        <input type="text" name="referrer" id="referrer" class="form-control input-sm" value="<?php echo $conditions['referrer']; ?>"/>
                    </div>
                    <!-- Under Affiliate/Agent Status -->
                    <div class="col-md-2">
                        <label for="affiliate_agent" class="control-label"><?=lang('Under Affiliate/Agent Status')?></label>
                        <select name="affiliate_agent" id="affiliate_agent" class="form-control input-sm">
                            <option value=""><?=lang('lang.selectall')?></option>
                            <option value="2" <?php if($conditions['affiliate_agent'] == 2) echo 'selected'; ?>><?=lang('Under Affiliate Only')?></option>
                            <option value="3" <?php if($conditions['affiliate_agent'] == 3) echo 'selected'; ?>><?=lang('Under Agent Only')?></option>
                            <option value="4" <?php if($conditions['affiliate_agent'] == 4) echo 'selected'; ?>><?=lang('Under Affiliate or Agent')?></option>
                            <option value="1" <?php if($conditions['affiliate_agent'] == 1) echo 'selected'; ?>><?=lang('Not under any Affiliate or Agent')?></option>
                          </select>
                    </div>
                </div>
                <div class="row">
                    <!-- Total Bets >= -->
                    <div class="col-md-2">
                        <label class="control-label" for="total_bet_from"><?=lang('report.g09') . " >= "?> </label>
                        <input type="text" name="total_bet_from" id="total_bet_from" class="form-control input-sm number_only"
                        value='<?php echo $conditions["total_bet_from"]; ?>'/>
                    </div>
                    <!-- Total Bets <= -->
                    <div class="col-md-2">
                        <label class="control-label" for="total_bet_to"><?=lang('report.g09') . " <= "?> </label>
                        <input type="text" name="total_bet_to" id="total_bet_to" class="form-control input-sm number_only"
                        value='<?php echo $conditions["total_bet_to"]; ?>'/>
                    </div>
                    <!-- Total Wins >= -->
                    <div class="col-md-2">
                        <label class="control-label" for="total_gain_from"><?=lang('report.g10') . " >= "?> </label>
                        <input type="text" name="total_gain_from" id="total_gain_from" class="form-control input-sm number_only"
                        value='<?php echo $conditions["total_gain_from"]; ?>'/>
                    </div>
                    <!-- Total Wins <= -->
                    <div class="col-md-2">
                        <label class="control-label" for="total_gain_to"><?=lang('report.g10') . " <= "?> </label>
                        <input type="text" name="total_gain_to" id="total_gain_to" class="form-control input-sm number_only"
                        value='<?php echo $conditions["total_gain_to"]; ?>'/>
                    </div>
                    <!-- Total Loss >= -->
                    <div class="col-md-2">
                        <label class="control-label" for="total_loss_from"><?=lang('report.g11') . " >= "?> </label>
                        <input type="text" name="total_loss_from" id="total_loss_from" class="form-control input-sm number_only"
                        value='<?php echo $conditions["total_loss_from"]; ?>'/>
                    </div>
                    <!-- Total Loss <= -->
                    <div class="col-md-2">
                        <label class="control-label" for="total_loss_to"><?=lang('report.g11') . " <= "?> </label>
                        <input type="text" name="total_loss_to" id="total_loss_to" class="form-control input-sm number_only"
                        value='<?php echo $conditions["total_loss_to"]; ?>'/>
                    </div>
                </div>
                <div class="row">
                    <!-- Show Multiselect Game Filter -->
                    <div class="col-md-12">
                        <br>
                        <fieldset>
                            <legend>
                                <label class="form-check-label">
                                    <?php echo lang('Show Multiselect Game Filter')?>
                                </label>
                                <a class="btn btn-xs <?=$this->utils->getConfig('use_new_sbe_color') ? 'btn-scooter' : 'btn-primary'?>" name="show_multiselect_filter" id="show-multiselect-filter" style="text-decoration:none; border-radius:2px;"><span class="fa fa-plus-circle"> <?=lang("Expand All")?></span></a>
                            </legend>
                            <div id="gameTypeCheckBoxes" style="display:none;padding-bottom:20px;">
                                <?php foreach($mulitple_select_game_map as $k => $gt ): ?>
                                  <fieldset style="padding-bottom: 8px">
                                    <legend>
                                        <label class="control-label"><?php echo $game_apis_map[$k]  ?></label>
                                    </legend>
                                    <label class="checkbox-inline">
                                       <input class="game-types-all" id="game-types-all-<?php echo $k;?>" platform_id_class="<?php echo $k;?>"  type="checkbox" value="<?php ?>"><?php echo lang('lang.all')?></label>
                                       <?php foreach($gt as $gt_row): ?>
                                          <label class="checkbox-inline">
                                              <input class="game-types  game-platfom-class-<?php echo $k;?>"  platform_id="<?php echo $k;?>" id="game-type-multiple-<?php echo $gt_row['id']   ?>"  type="checkbox" value="<?php echo $gt_row['id']   ?>"><?php echo lang($gt_row['game_type']) ?></label>
                                          <?php endforeach;?>
                                      </fieldset>
                                <?php endforeach;?>
                                <input type="hidden" name="game_type_multiple" value="<?= $conditions["game_type_multiple"] ?>" id="game-type-multiple" />
                            </div>
                        </fieldset>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-1 pull-right" style="text-align:center;padding-top:24px;">
                        <input type="submit" value="<?=lang('lang.search')?>" id="search_main" class="btn col-md-12 btn-sm <?=$this->utils->getConfig('use_new_sbe_color') ? 'btn-portage' : 'btn-info'?>">
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="panel panel-primary" >
    <div class="panel-heading">
        <h4 class="panel-title"><i class="icon-dice"></i> <?=lang('Games Report Timezone')?> </h4>
    </div>
    <div class="panel-body" >
        <div class="table-responsive">
            <table class="table table-bordered table-hover " id="myTable">
                <thead>
                    <tr>
                        <th><?=lang('Game Platform')?></th>
                        <th><?=lang('Game Type')?></th>
                        <th><?=lang('Game')?></th>
                        <th><?=lang('Player Username')?></th>
                        <th><?=lang('Player Level')?></th>
                        <th><?=lang('aff.as03')?></th>
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


<script type="text/javascript">

    var export_type="<?php echo $this->utils->isEnabledFeature('export_excel_on_queue') ? 'queue' : 'direct';?>";

    var baseUrl =  '<?= base_url(); ?>';
    var loadParams = {
        platformId      : '<?= $conditions["external_system"] ?  $conditions["external_system"] : 0 ?>',
        gameTypeId      : '<?= $conditions["game_type"] ? $conditions["game_type"] : 0 ?>',
        gameTypeIdMultiple      : turnToArrayGametype('<?= $conditions["game_type_multiple"] ? $conditions["game_type_multiple"] : 0 ?>'),
        showMultiSelectFilter      : '<?= $conditions["show_multiselect_filter"] ?>',
    };
    var gamePlatformId;
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
        All  : '<?=lang('lang.all')?>' ,
        total: '<?=lang('This Total')?>',
        subTotal: '<?=lang('Subtotal')?>',
        count: '<?=lang('Count')?>',
    };


    var gameApisMapObj = <?php echo json_encode($game_apis_map) ?>;

    var gameTypeParam = [];

    var jsonObjForExportToCSV = {data:[], header_data:[]};

    function turnToArrayGametype(gameTypeString){

        if (typeof gameTypeString === undefined || !gameTypeString) {
            return 0;
        }
        return gameTypeString.split('+');

    }

    function validateForm(){
        //check user name
        var checked = true;
        $.ajax({
            async: false,
            url : '/api/checkUserName',
            type: 'POST',
            dataType: 'json',
            data: {
                username: $('#form-filter #username').val(),
                agent_name : $('#form-filter #agent_name').val(),
                affiliate_username: $('#form-filter #affiliate_username').val(),
                admin_username: '',
            },
            success: function(response) {
                var message = '';
                if (!response.username) {
                    message += '<?=lang('Username') . ' ' .lang('not found')?>' + "\r\n";
                }
                if (!response.agent_name) {
                    message += '<?=lang('Agent Username') . ' ' .lang('not found')?>' + "\r\n";
                }
                if (!response.affiliate_username) {
                    message += '<?=lang('Affiliate Username') . ' ' .lang('not found')?>' + "\r\n";
                }
                if (!response.admin_username) {
                    message += '<?=lang('Admin Username') . ' ' .lang('not found')?>' + "\r\n";
                }
                if (message.length > 0) {
                    alert(message);
                    checked = false;
                }
            }
        });
        if (!checked)
            return false;
        return true;
    }

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

    function renderGameTable(data){
        //---------------------Bets Per Game Table start----------------------------
        var playerTotalBetsPerGame = data.player_total_bets_per_game,
            tableHeaders = data.game_platform_header_map,
            th = "<th>" + message.playerId + "</th><th>" + message.playerUsername + "</th>",
            rowHeaders=[];

        rowHeaders.push(message.playerId);
        rowHeaders.push(message.playerUsername);

        if ( $.fn.DataTable.isDataTable('#player-bets-per-game') ) {
            $('#player-bets-per-game').DataTable().destroy();
        }

        existTableHeaders = [];

        for (var key in tableHeaders) {

            rowHeaders.push(gameApisMapObj[key]);
            rowHeaders.push(message.Payout + gameApisMapObj[key]);
            rowHeaders.push(message.Wins + gameApisMapObj[key]);
            rowHeaders.push(message.Loss + gameApisMapObj[key]);
            rowHeaders.push(message.Revenue + gameApisMapObj[key]);
            rowHeaders.push(message.RevenuePercent + gameApisMapObj[key]);

            th += '<th>' + gameApisMapObj[key] + '</th>';
            th += '<th class="text-warning">' + message.Payout + '<i style="font-size:9px">(' + gameApisMapObj[key] + ')</i></th>';
            th += '<th class="text-success">' + message.Wins + '<i style="font-size:9px">(' + gameApisMapObj[key] + ')</i></th>';
            th += '<th class="text-danger">' + message.Loss + '<i style="font-size:9px">(' + gameApisMapObj[key] + ')</i></th>';
            th += '<th class="text-warning">' + message.Revenue + '<i style="font-size:9px">(' + gameApisMapObj[key] + ')</i></th>';
            th += '<th class="text-warning">' + message.RevenuePercent + '<i style="font-size:9px">(' + gameApisMapObj[key] + ')</i></th>';

            existTableHeaders.push(key);

        }

        rowHeaders.push(message.totalBets)
        rowHeaders.push(message.totalPayouts);
        rowHeaders.push(message.totalWins);
        rowHeaders.push(message.totalLosses);
        rowHeaders.push(message.totalRevenue);
        rowHeaders.push(message.totalRevenuePercent);

        th += "<th>" + message.totalBets + "</th>";
        th += "<th class='text-warning' >" + message.totalPayouts + "</th>";
        th += "<th class='text-success'>" + message.totalWins + "</th>";
        th += "<th class='text-danger'>" + message.totalLosses + "</th>";
        th += "<th class='text-danger'>" + message.totalRevenue + "</th>";
        th += "<th class='text-danger'>" + message.totalRevenuePercent + "</th>";

        $('#player-bets-per-game tr:first').html("");
        $('#player-bets-per-game tr:first').append(th);

        //return;

        var tbody = '';

        //use the fastest way https://jsperf.com/fastest-way-to-iterate-object
        var keys = Object.keys(playerTotalBetsPerGame);
        var len = keys.length;

        for (var i = 0; i < len; i++) {
            var rowObj = {};
            var tr = '<tr>',
                tds = '',
                playerRow = playerTotalBetsPerGame[keys[i]], //rows of game
                betDetails = playerRow.bet_details,
                hlen = existTableHeaders.length,
                bdArrkeys = Object.keys(betDetails);

            rowObj['playerId'] = playerRow.player_id;
            rowObj['username'] = playerRow.username;

            tds += '<td>' + playerRow.player_id + '</td>';
            tds += '<td>' + playerRow.username + '</td>';

               for (var n=0; n < hlen;  n++) {

                    if (bdArrkeys.indexOf(existTableHeaders[n]) > -1) {

                        // console.log(betDetails[existTableHeaders[n]]);

                        rowObj['total_bet'+n] = betDetails[existTableHeaders[n]].total_bet.replace( /<.*?>/g, '' );
                        rowObj['total_payout'+n] = betDetails[existTableHeaders[n]].total_payout.replace( /<.*?>/g, '' );
                        rowObj['total_win'+n]= betDetails[existTableHeaders[n]].total_win.replace( /<.*?>/g, '' );
                        rowObj['total_loss'+n] = betDetails[existTableHeaders[n]].total_loss.replace( /<.*?>/g, '' );
                        rowObj['total_revenue'+n] = betDetails[existTableHeaders[n]].total_revenue.replace( /<.*?>/g, '' );
                        rowObj['total_revenue_percent'+n] = betDetails[existTableHeaders[n]].total_revenue_percent.replace( /<.*?>/g, '' );

                        tds += '<td class ="text-right">' + betDetails[existTableHeaders[n]].total_bet + '</td>';
                        tds += '<td class ="text-right">' + betDetails[existTableHeaders[n]].total_payout + '</td>';
                        tds += '<td class ="text-right text-success">' + betDetails[existTableHeaders[n]].total_win + '</td>';
                        tds += '<td class ="text-right text-danger">' + betDetails[existTableHeaders[n]].total_loss + '</td>';
                        tds += '<td class ="text-right text-warning">' + betDetails[existTableHeaders[n]].total_revenue + '</td>';
                        tds += '<td class ="text-right text-warning">' + betDetails[existTableHeaders[n]].total_revenue_percent + '</td>';

                    } else {

                        rowObj['total_bet'+n] = '0.00';
                        rowObj['total_payout'+n] = '0.00';
                        rowObj['total_win'+n] = '0.00';
                        rowObj['total_loss'+n] = '0.00';
                        rowObj['total_revenue'+n] = '0.00';
                        rowObj['total_revenue_percent'+n] = '0.00';

                        tds += '<td class ="text-right text-muted" >0.00</td>';
                        tds += '<td class ="text-right text-muted" >0.00</td>';
                        tds += '<td class ="text-right text-muted" >0.00</td>';
                        tds += '<td class ="text-right text-muted" >0.00</td>';
                        tds += '<td class ="text-right text-muted" >0.00</td>';
                        tds += '<td class ="text-right text-muted" >0.00</td>';

                    }
                }

            rowObj['sum_total_bets'] = playerRow.sum_total_bets.replace( /<.*?>/g, '' );
            rowObj['sum_total_payout'] = playerRow.sum_total_payout.replace( /<.*?>/g, '' );
            rowObj['sum_total_wins'] = playerRow.sum_total_wins.replace( /<.*?>/g, '' );
            rowObj['sum_total_loss'] = playerRow.sum_total_loss.replace( /<.*?>/g, '' );
            rowObj['sum_total_revenue'] = playerRow.sum_total_revenue.replace( /<.*?>/g, '' );
            rowObj['sum_total_revenue_percent'] = playerRow.sum_total_revenue_percent.replace( /<.*?>/g, '' );

            tds += '<td class="text-right text-primary">' + playerRow.sum_total_bets + '</td>';
            tds += '<td class="text-right text-primary">' + playerRow.sum_total_payout + '</td>';
            tds += '<td class="text-right text-primary">' + playerRow.sum_total_wins + '</td>';
            tds += '<td class="text-right text-primary">' + playerRow.sum_total_loss + '</td>';
            tds += '<td class="text-right text-primary">' + playerRow.sum_total_revenue + '</td>';
            tds += '<td class="text-right text-primary">' + playerRow.sum_total_revenue_percent + '</td>';
            tr += tds+'</tr>';
            tbody += tr;
            jsonObjForExportToCSV['data'].push(rowObj);
            jsonObjForExportToCSV['header_data'] = rowHeaders;
            $("#json_csv_search").val(JSON.stringify(jsonObjForExportToCSV));
        }
        // var myJSON = JSON.stringify(jsonObjForExportToCSV);
        // console.log(myJSON)

        $('#player-bets-per-game tbody').html(tbody);


        //Prepare footer totals
        var footerCount = jsonObjForExportToCSV.header_data.length;

            var footerTotals = '';
            for (var i = 1; i <= footerCount; i++) {
                if (i == 1) {
                    footerTotals += '<th class="text-right">';
                    footerTotals += '<span style="font-weight:bold;">' + message.subTotal + '</span><br>';
                    footerTotals += '<span style="font-weight:bold;" class="text-primary">' + message.total + '</span>';
                    footerTotals += '</th>';
                } else {
                    footerTotals += '<th class="text-right"></th>';
                }
            }
            footerTotals = '<tr>' + footerTotals + '</tr>';

           $('#player-bets-per-game-totals').html(footerTotals);

        if (data.recordsTotal == 0) {
            $('#player-bets-per-game-container').hide();
            return;
         } else {
            $('#player-bets-per-game-container').show()
         }
        $('#player-bets-per-game').DataTable({
            "pageLength": 25,
            "lengthMenu": [[25, 50, 100], [25, 50, 100]],
            searching: true,
            autoWidth: false,
            dom: "<'panel-body'<'pull-right'B><'pull-right progress-container hidden-xs'>l><'dt-information-summary1 text-info pull-left' i>f t<'panel-body'<'pull-right'p><'dt-information-summary2 text-info pull-left' i>>",

             buttons: [
            // {
            //     extend: 'colvis',
            //     postfixButtons: [ 'colvisRestore' ]
            // }
            <?php if ($export_report_permission) {?>
            //,
            {
                text: "<?php echo lang('CSV Export'); ?>",
                className:'btn btn-sm btn-primary _export_csv_btn',
                action: function ( e, dt, node, config ) {

                          $(this).attr('disabled', 'disabled');
                         $.ajax({
                            url:  site_url('/export_data/game_report_results/true'),
                            type: 'POST',
                             data: {json_search: $("#json_csv_search").val() }
                            }).done(function(data) {

                            if(data && data.success){
                               $('body').append('<iframe src="'+data.link+'" frameborder="0" scrolling="no" style="border:0px;width:0px;height:0px"></iframe>');
                                $('._export_csv_btn').removeAttr("disabled");
                            }else{
                               $('._export_csv_btn').removeAttr("disabled");
                               alert('export failed');
                            }
                            }).fail(function(){
                                $('._export_csv_btn').removeAttr("disabled");
                                alert('export failed');
                          });
                           }
            }
            <?php } ?>
            ],


            "footerCallback": function ( row, data, start, end, display ) {
                var api = this.api(), data;

                var intVal = function ( a,b ) {
                    if ( typeof a === 'string' ) {
                            a = a.replace(/[^\d.-]/g, '') * 1;
                        }
                        if ( typeof b === 'string' ) {
                            b = b.replace(/[^\d.-]/g, '') * 1;
                        }
                       return a + b;
                };

                var count_ = jsonObjForExportToCSV.header_data.length  ;

                for(var i=2; i<count_; i++){

                var  total = api.column(i).data().reduce( function( a, b ){return intVal(a,b);}, 0 ).toFixed(2);
                    $( api.column( i ).footer() ).html('<span>'+ addCommas(api.column( i, {page:'current'} ).data().sum().toFixed(2)) +'</span><br><span class="text-right text-primary ">'+addCommas(total)+'</span>');
                }
            }


         }).draw(false);
         $('#player-bets-per-game-container').hide();
         $('#player-bets-per-game-container').fadeIn(1000);
         //---------------------Bets Per Game Table end----------------------------
    }

    $(document).ready(function(){
        jQuery.fn.dataTable.Api.register( 'sum()', function ( ) {
            return this.flatten().reduce( function ( a, b ) {
                if ( typeof a === 'string' ) {
                    a = a.replace(/[^\d.-]/g, '') * 1;
                }
                if ( typeof b === 'string' ) {
                    b = b.replace(/[^\d.-]/g, '') * 1;
                }

                return a + b;
            }, 0 );
        } );

        $('#playerList').select2();

//---------------------Multi select search start----------------------------


    //OGP-14089 change checkout box to fieldset
    if(loadParams.gameTypeIdMultiple != 0){
        // $('#show-multiselect-filter').val('true');
        $('#show-multiselect-filter').attr("checked", true);
        $('#gamePlatform').attr('disabled', 'disabled');
        $('#gameType').attr('disabled', 'disabled');
        $('#gameTypeCheckBoxes').show();
        $('#show-multiselect-filter span').attr('class', 'fa fa-minus-circle');

     }else{
        $('#game-type-multiple').val("");
        // $('#show-multiselect-filter').val('false');
        $('#show-multiselect-filter').attr("checked", false);
        $('#gameTypeCheckBoxes').hide();
        $('#show-multiselect-filter span').attr('class', 'fa fa-plus-circle');
    }

    $('#show-multiselect-filter').click(function(){
        if($('#show-multiselect-filter span').attr('class') == 'fa fa-plus-circle'){
            $('#show-multiselect-filter span').attr('class', 'fa fa-minus-circle');
            $('#show-multiselect-filter span').html(' <?=lang("Collapse All")?>');
            $('#show-multiselect-filter').attr("checked", true);
            $('#gameTypeCheckBoxes').show();
        }
        else{
            $('#show-multiselect-filter span').attr('class', 'fa fa-plus-circle');
            $('#show-multiselect-filter span').html(' <?=lang("Expand All")?>');
            $('.game-types-all').attr("checked", false);
            // $(this).val('false');
            $('#show-multiselect-filter').attr("checked", false);
            $('#game-type-multiple').val("");
            $('.game-types').attr("checked", false);
            $('#gameTypeCheckBoxes').hide();
             loadParams.gameTypeIdMultiple =0;
             gameTypeParam = [];
        }
    });


    $('.game-types-all').each(function(index, value) {
        $(this).click(function(){
             var id =  $(this).attr('platform_id_class');
              if (this.checked) {
                 $('.game-platfom-class-'+id).each(function(i, v) {
                     if(!this.checked){
                          $(this).trigger('click');
                     }
                 });
              }else{
                $('.game-platfom-class-'+id).each(function(i, v) {
                       $(this).trigger('click');
                });
              }
        });
     });


   //loop through checkboxes and attach each event listener
    $('.game-types').each(function(index, value) {
          // on first load of page gameTypeId is always zero bec no url parameter
          if (loadParams.gameTypeIdMultiple != 0) {
           if (loadParams.gameTypeIdMultiple.indexOf($(this).val()) > -1) {
               $(this).attr("checked", true);
           }
       }
        //onchecked make setting and string  for game_type value as single string
        $(this).change(function() {
           if (this.checked) {
               gameTypeParam.push($(this).val());
               gameTypeString = gameTypeParam.join('+');
               $('#game-type-multiple').val(gameTypeString);
           } else {
               var gameTypeIndex = gameTypeParam.indexOf($(this).val());
               gameTypeParam.splice(gameTypeIndex, 1);
               gameTypeString = gameTypeParam.join('+');
               $('#game-type-multiple').val(gameTypeString);
               var platform_id =  $(this).attr('platform_id');
               $('#game-types-all-'+platform_id).attr("checked", false);

           }
       });
    });
     // on succeeding page load make string param for exising game_type values
     if (loadParams.gameTypeIdMultiple!= 0) {
       $('#game-type-multiple').val(loadParams.gameTypeIdMultiple.join('+'));
       gameTypeParam = loadParams.gameTypeIdMultiple;

   }
     //---------------------Multi select search  end----------------------------

       var existTableHeaders = [];

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

            //dom: "<'panel-body'l>t<'text-center'r><'panel-body'<'pull-right'p>i>",
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
                         var d = {'extra_search': $('#form-filter').serializeArray(), 'export_format': 'csv', 'export_type': export_type,
                            'draw':1, 'length':-1, 'start':0};
                        <?php if($this->utils->isEnabledFeature('export_excel_on_queue')){?>
                                    $("#_export_excel_queue_form").attr('action', site_url('/export_data/game_report_timezone/null/true'));
                                    $("#_export_excel_queue_form [name=json_search]").val(JSON.stringify(d));
                                    $("#_export_excel_queue_form").submit();
                        <?php }else{?>

                        $.post(site_url('/export_data/game_report_timezone/null/true'), d, function(data){
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
                { className: 'text-right', targets: [6,7,8,9,10,11] },
                { visible: false, targets: [ <?php echo implode(',', $this->config->item('game_reports_hidden_cols')); ?>  ] }
            ],
            "order": [ 0, 'asc' ],

            // SERVER-SIDE PROCESSING
            processing: true,
            serverSide: true,
            ajax: function (data, callback, settings) {

                var formData = $('#form-filter').serializeArray();
                data.extra_search = formData;
                $.post(base_url + "api/gameReportsTimezone", data, function(data) {// console.log(data)
                 if(data.recordsTotal > 0){
                    $('.total-player').html('<i class="text-success" style="font-size:9;padding-right:10px;">('+message.distinctPlayers+')</i>'+data.summary[0].total_player);
                    $('.total-bet').text(addCommas(parseFloat(data.summary[0].total_bet).toFixed(2)));
                    $('.total-payout').text(addCommas(parseFloat(data.summary[0].total_payout).toFixed(2)));
                    $('.total-ave-bet').html(addCommas(parseFloat(data.summary[0].total_ave_bet).toFixed(2)) +'<br><i class="text-success" style="font-size:10px;">('+message.count+': '+data.summary[0].total_ave_count+')</i>' );
                    $('.total-win').text(addCommas(parseFloat(data.summary[0].total_win).toFixed(2)));
                    $('.total-loss').text(addCommas(parseFloat(data.summary[0].total_loss).toFixed(2)));
                    $('.total-revenue').text(addCommas(parseFloat(data.summary[0].total_revenue).toFixed(2)));
                    $('.total-revenue-percent').text(data.summary[0].total_revenue_percent + '%');

                 <?php if ($this->utils->isEnabledFeature('display_player_bets_per_game')) { // OGP-17821: feature display_player_bets_per_game retired // OGP-18149: revert game report feature?>
                    renderGameTable(data);
                 <?php } ?>

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
           drawCallback : function(setting) {

            <?php if( ! empty($enable_freeze_top_in_list) ): ?>
                var _dataTableIdstr = setting.sTableId; // for multi-dataTable in a page.
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
           },
           "rowCallback": function( row, data, index ) {

           },
        });
    });

    $(document).ready(function () {
        function set_disabled_but(selector, val) {
            $(selector).find('option').each(function () {
                var v = $(this).val();
                if (val != v) { $(this).attr('disabled', 1); }
            });
        }

        function clear_disabled(selector) {
            $(selector).find('option').each(function () {
                $(this).removeAttr('disabled');
            });
        }

        function set_aff_agent_by_group_by() {
            var val_group_by = $('select#group_by').val();
            switch (val_group_by) {
                case 'aff_id' :
                    $('#affiliate_agent').val(2);   // 2 - under affiliate only
                    set_disabled_but('#affiliate_agent', 2);
                    break;
                default :
            }
        }

        // On page-load
        (function () {
            clear_disabled('#affiliate_agent');
            set_aff_agent_by_group_by();
        })();

        // On select#group_by change
        $('select#group_by').change(function () {
            clear_disabled('#affiliate_agent');
            set_aff_agent_by_group_by();
        });
    });
</script>
