<div class="panel panel-primary">
    <div class="panel-heading">
        <h4 class="panel-title">
            <i class="fa fa-search"></i> <?= lang('lang.search'); ?> <span class="pull-right">
                <a data-toggle="collapse" href="#collapseTaggedList" class="btn btn-info btn-xs"></a>
            </span>
        </h4>
    </div>
 <div id="collapseTaggedList" class="panel-collapse ">
        <form class="form-horizontal" id="search-form" method="get" role="form">
            <div class="panel-body">
                <div class="row">
                    <div class="col-md-6">
                        <!-- <div class="form-group"> -->
                            <label class="control-label col-xs-4" for=""><?= lang('Currency'); ?></label>
                            <div class="col-xs-7">
                                <!-- <label class="checkbox-inline">
                                    <input type="checkbox" id="currency-all" value="all"> All
                                </label> -->
                                <?php foreach ($currency_list as $value) :?>
                                    <label class="checkbox-inline">
                                        <input type="checkbox" class="currency-selectors"  currency="<?=($value) ?>" value="<?= $value ?>"> <?= strtoupper($value) ?>
                                    </label>
                                <?php endforeach; ?> 
                                <div class="" style="">
                            <i class="text-danger"  id="no-chosen-currency-msg"style="font-size:12px;visibility: hidden;"><?=lang('con.usm35')?></i>
                        <!-- </div>        -->
                          <input type="hidden" name="chosen_currencies"  id="chosen_currencies" value="<?= $conditions['chosen_currencies']; ?>" >
                            </div>
                        </div>
                        <!-- <div class="form-group">
                            <label for="reportDate" class="control-label col-xs-4"><?= lang('aff.ap07'); ?></label>
                            <div class="col-xs-7">
                                <input id="reportDate" name="" class="form-control input-sm dateInput" data-start="#date_from" data-end="#date_to" data-time="true" autocomplete="off">
                                <input type="hidden" id="date_from" name="date_from"  value="<?=$conditions['date_from'];?>" >
                                <input type="hidden" id="date_to" name="date_to" value="<?=$conditions['date_to'];?>">
                            </div>
                        </div> -->
                    </div>
                    <!-- <div class="col-md-5">
                        <div class="form-group">
                            <label for="reportDate" class="control-label col-xs-4"><?= lang('aff.as19'); ?></label>
                            <div class="col-xs-7">
                                <input id="username" name="username" class="form-control input-sm" value="<?=$conditions['username'];?>">
                            </div>
                        </div>
                    </div> -->
                </div>
                <div class="row">
                    <div class="col-md-4">
                        <label for="reportDate" class="control-label"><?= lang('aff.ap07'); ?></label>
                        <input id="reportDate" name="" class="form-control input-sm dateInput" data-start="#date_from" data-end="#date_to" data-time="true" autocomplete="off">
                                <input type="hidden" id="date_from" name="date_from"  value="<?=$conditions['date_from'];?>" >
                                <input type="hidden" id="date_to" name="date_to" value="<?=$conditions['date_to'];?>">
                    </div>
                    <div class="col-md-2">
                        <label class="control-label" for="username"><?= lang('aff.as19'); ?> </label>
                        <input type="text" name="username" id="username" class="form-control input-sm"
                        value='<?=$conditions['username'];?>'/>
                    </div>
                    <!-- Group By -->
                    <div class="col-md-2">
                        <label class="control-label" for="group_by"><?=lang('report.g14')?> </label>
                        <select name="group_by" id="group_by" class="form-control input-sm">
                            <option value="game_platform_id" ><?php echo lang('Game Platform'); ?></option>
                            <option value="game_type_id" <?php echo $conditions["group_by"] == 'game_type_id' ? "selected=selected" : ''; ?>><?php echo lang('Game Type Code'); ?></option>
                            <!-- <option value="game_description_id" <?php echo $conditions["group_by"] == 'game_description_id' ? "selected=selected" : ''; ?>><?php echo lang('Game External Unique Id'); ?></option> -->
                            <option value="player_id" <?php echo $conditions["group_by"] == 'player_id' ? "selected=selected" : ''; ?> ><?php echo lang('Player'); ?></option>
                            <option value="game" <?php echo $conditions["group_by"] == 'game' ? "selected=selected" : ''; ?> ><?php echo lang('Game'); ?></option>

                            <!-- <option value="game_platform_and_player" <?php echo $conditions["group_by"] == 'game_platform_and_player' ? "selected=selected" : ''; ?> ><?php echo lang('Player And Game Platform'); ?></option>
                            <option value="game_type_and_player" <?php echo $conditions["group_by"] == 'game_type_and_player' ? "selected=selected" : ''; ?> ><?php echo lang('Player And Game Type'); ?></option>
                            <option value="game_description_and_player" <?php echo $conditions["group_by"] == 'game_description_and_player' ? "selected=selected" : ''; ?> ><?php echo lang('Player And Game'); ?></option>

                            <option value="aff_id" <?php echo $conditions["group_by"] == 'aff_id' ? "selected=selected" : ''; ?> ><?php echo lang('Affiliate'); ?></option>
                            <option value="agent_id" <?php echo $conditions["group_by"] == 'agent_id' ? "selected=selected" : ''; ?> ><?php echo lang('Agency'); ?></option>

                            <option value="aff_and_game_platform" <?php echo $conditions["group_by"] == 'aff_and_game_platform' ? "selected=selected" : ''; ?> ><?php echo lang('Affiliate and Game Platform'); ?></option> -->
                        </select>
                    </div>
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
            </div>
            <div class="panel-footer text-center">
                <input type="button" value="<?= lang('Reset') ?>" class="btn btn-default btn-sm" id="reset">
                <button type="submit" name="submit" class="btn btn-primary btn-sm"><i class="fa"></i> <?= lang('Search'); ?></button>
            </div>
        </form>
    </div>
</div>
<div class="panel panel-primary">
    <div class="panel-heading custom-ph">
        <h4 class="panel-title">
            <i class="icon-pie-chart"></i>
            <?=lang('report.s07')?>
        </h4>
    </div>
    <?php if (!empty($currency_list)): ?>
        <?php foreach ($currency_list as $value) :?>

        <?php
         // Dont display datatable not in chosen currencies
         $chosen_currencies = explode(",", $conditions['chosen_currencies']);

         if(!in_array($value, $chosen_currencies)){
            continue;
         }
        ?>            
            <div id="currency_table_<?=$value?>" class="panel-body">
            <h4><span class="label label-info"><?= strtoupper($value) ?></span></h4>
            <div class="table-responsive">
                <table class="table table-condensed table-bordered table-hover report-table" id="reportTable_<?=$value?>" data-searching="false" data-ordering="true" data-page-length="10">
                    <thead>
                        <tr>
                            <th><?= lang('Currency'); ?></th>
                            <th><?=lang('Player Username')?></th>
                            <th style="min-width:100px;" ><?= lang('Date'); ?></th>
                            <!-- <th><?= lang('Game Platform Id'); ?></th> -->
                            <th style="min-width:200px;"><?= lang('Game Provider'); ?></th>
                            <th style="min-width:100px;"><?= lang('Game Type Code'); ?></th>
                            <th style="min-width:100px;"><?= lang('Game'); ?></th>
                            <th style="min-width:100px;"><?= lang('Total Players'); ?></th>
                            <!-- <th style="min-width:100px;"><?= lang('Game External Unique Id'); ?></th> -->
                            <th><?=lang('Total Bets')?></th>
                            <th><?=lang('Agency Payout')?> <i class="fa fa-exclamation-circle" data-toggle="tooltip" title="<?=lang("games_report_payout_formula")?>" data-container="body"></i></th>
                            <th><?=lang('Total Win')?></th>
                            <th><?=lang('Total Loss')?></th>
                            <th><?=lang('Game Revenue')?> <i class="fa fa-exclamation-circle" data-toggle="tooltip" title="<?=lang("games_report_revenue_formula")?>" data-container="body"></i></th>
                            <th><?=lang('Game Revenue %')?></th>
                        </tr>
                    </thead>
                    <tfoot>
                        <tr>
                            <th></th>
                            <th  class="text-primary"><span><?php echo lang('Total') ?></span></th>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th class="text-right text-primary"><span id="total_player_<?=$value?>">0</span></th>
                            <th class="text-right text-primary"><span id="betting_amount_<?=$value?>">0</span></th>
                            <th class="text-right text-primary"><span id="payout_amount_<?=$value?>">0</span></th>
                            <th class="text-right text-primary"><span id="win_amount_<?=$value?>">0</span></th>
                            <th class="text-right text-primary"><span id="loss_amount_<?=$value?>">0.00</span></th>
                            <th class="text-right text-primary"><span id="result_amount_<?=$value?>">0.00</span></th>
                            <th class="text-right text-primary"><span id="game_revenue_percentage_<?=$value?>">0.00%</span></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
    <div class="panel-footer"></div>
</div>

<form id="_export_excel_queue_form" class="hidden" method="POST" target="_blank">
    <input name='json_search' type="hidden">
</form>

<script>

    var baseUrl = '<?php echo base_url(); ?>';

    $(document).ready(function(){

    <?php foreach ($currency_list as $value):?> 

    <?php
         // Dont display datatable not in chosen currencies
         $chosen_currencies = explode(",", $conditions['chosen_currencies']);

         if(!in_array($value, $chosen_currencies)){
            continue;
         }
    ?>

    var message = {
        distinctPlayers  : '<?= lang('Distinct Players'); ?>',
    };
    var dataTable_<?php echo $value?> = $('#reportTable_<?php echo $value; ?>').DataTable({
            autoWidth: false,
            searching: false,
             <?php if ($this->utils->isEnabledFeature('column_visibility_report')){ ?>
                stateSave: true,
            <?php } else { ?>
                stateSave: false,
            <?php } ?>
            bLengthChange: false,
            dom: "<'panel-body'<'pull-right'B><'pull-right progress-container hidden-xs'>l><'dt-information-summary1 text-info ' i>f t<'panel-body'<'pull-right'p>>",
                      buttons: [
                {
                    extend: 'colvis',
                    postfixButtons: [ 'colvisRestore' ],
                    className: 'btn btn-sm <?=$this->utils->getConfig('use_new_sbe_color') ? 'btn-linkwater' : ''?>',
                }
                <?php if ($export_report_permission) {?>
                ,{
                    text: "<?php echo lang('CSV Export'); ?>",
                    className:'btn btn-sm disabled <?=$this->utils->getConfig('use_new_sbe_color') ? 'btn-portage' : 'btn-primary'?>',
                    action: function ( e, dt, node, config ) {
                        
                         var d = {'extra_search': $('#search-form').serializeArray(), 'export_format': 'csv', 'export_type': export_type, 'draw':1, 'length':-1, 'start':0};
                          d.extra_search.push({"name":"currency", "value":"<?php echo $value?>".toUpperCase()});
               
                            $("#_export_excel_queue_form").attr('action', site_url('/export_data/export_super_game_report'));
                            $("#_export_excel_queue_form [name=json_search]").val(JSON.stringify(d));
                            $("#_export_excel_queue_form").submit();         
                    }
                }
                <?php } ?>
            ],
            columnDefs: [
                { visible: false, targets:[0] },
                { className: 'text-right', targets: [  7, 8, 9, 10, 11, 12,] },
                // { className: 'text-right', targets: [  5, 6, 7, 8] },
                { className:'text-center', targets: [ 0, 3, 4, 5, 6] }
            ],

            // SERVER-SIDE PROCESSING
            processing: true,
            serverSide: true,
           // destroy:true,
            ajax: function (data, callback, settings) {

                data.extra_search = $('#search-form').serializeArray(); 
                data.extra_search.push({"name":"currency", "value":"<?php echo $value?>".toUpperCase()});

                dataTable_<?php echo $value?> .buttons().disable();
                $('#search-form,.report-table,.btn').css("pointer-events", "none");
              
                $.post(base_url + "api/super_game_report", data, function(data) {
                        // $('#betting_amount_<?=$value?>').text(addCommas(parseFloat(data.sub_summary.total_bet_amount).toFixed(2)));
                        // $('#win_amount_<?=$value?>').text(addCommas(parseFloat(data.sub_summary.total_win_amount).toFixed(2)));
                        // $('#loss_amount_<?=$value?>').text(addCommas(parseFloat(data.sub_summary.total_loss_amount).toFixed(2)));
                        // $('#result_amount_<?=$value?>').text(addCommas(parseFloat(data.sub_summary.result_amount).toFixed(2)));
                        // $('#payout_amount_<?=$value?>').text(addCommas(parseFloat(data.sub_summary.total_payout).toFixed(2)));
                        $('#game_revenue_percentage_<?=$value?>').text(data.sub_summary.total_game_revenue + '%');
                        $('#total_player_<?=$value?>').html('<i class="text-success" style="font-size:7;padding-right:2px;">('+message.distinctPlayers+')</i>'+data.sub_summary.total_player);
                        // $('#total_player_<?=$value?>').text(data.sub_summary.total_player);
                        $('#betting_amount_<?=$value?>').text(data.sub_summary.total_bet_amount);
                        $('#win_amount_<?=$value?>').text(data.sub_summary.total_win_amount);;
                        $('#loss_amount_<?=$value?>').text(data.sub_summary.total_loss_amount);
                        $('#result_amount_<?=$value?>').text(data.sub_summary.result_amount);
                        $('#payout_amount_<?=$value?>').text(data.sub_summary.total_payout);
                        // $('#game_revenue_percentage_<?=$value?>').text(data.sub_summary.total_game_revenue);

                    callback(data);
                    if ( dataTable_<?php echo $value?> .rows( { selected: true } ).indexes().length === 0 ) {
                        dataTable_<?php echo $value?> .buttons().disable();
                    }
                    else {
                        dataTable_<?php echo $value?> .buttons().enable();
                    }
                    $('#search-form,.report-table,.btn').css("pointer-events", "auto");
                }, 'json');
            },
         
     });

    <?php endforeach;?>

   var chosenCurrencies =  "<?= $conditions['chosen_currencies']; ?>".split(",");

    $('#search-form').submit( function(e) {

        if (!Array.isArray(chosenCurrencies) || !chosenCurrencies.length) {

            $('#no-chosen-currency-msg').css({"visibility":"visible"});

            return false;
     
        }
        $(this).find(':submit').attr('disabled', 'disabled');
        $('#search-form,.report-table,.btn').css("pointer-events", "none");
    });
         
    $(".currency-selectors").each(function(){
        var currencyAttr = $(this).attr('currency');
        if (chosenCurrencies.indexOf(currencyAttr) > -1){
            $(this).prop( "checked",true );
        }
    });

    $('#currency-all').change(function(){
        if($(this).is(":checked")){
            $(".currency-selectors").each(function(){
                if($(this).is(":not(:checked)")){
                    $(this).trigger('click');
                }
            });
        }
    });

    $(".currency-selectors").each(function(){
        $(this).change(function(){
            if($(this).is(":checked")){
               chosenCurrencies.push($(this).val());
               $('#chosen_currencies').val(chosenCurrencies.join())
            }else{
                var currency_index = chosenCurrencies.indexOf($(this).val());
                $('#currency-all').prop( "checked",false );

             if (chosenCurrencies.indexOf($(this).val()) > -1) {
                 chosenCurrencies.splice(currency_index, 1);
                 $('#chosen_currencies').val(chosenCurrencies.join())
             }
             }
        });

    });

    var d = new Date();
    var month = d.getMonth()+1;
    var day = d.getDate();

    var dateToday = d.getFullYear() + '-' +
    (month<10 ? '0' : '') + month + '-' +
    (day<10 ? '0' : '') + day;

    var dateFrom = dateToday+' 00:00:00';
    var dateTo = dateToday+' 23:59:59';;

    $('#reset').click(function(){
        $('#username').val("");
        $(".currency-selectors").each(function(){
                if($(this).is(":not(:checked)")){
                    $(this).trigger('click');
                }
        });

         $("#reportDate").val(dateFrom +" to "+ dateTo);
         $('#date_from').val(dateFrom);
         $('#date_to').val(dateTo);

    });

});//document ready

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
</script>