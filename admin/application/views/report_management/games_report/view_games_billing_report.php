<div class="panel panel-primary">
    <div class="panel-heading custom-ph">
        <h4 class="panel-title">
            <i class="icon-pie-chart"></i>
            <?=lang('Game Billing Report')?>
        </h4>
    </div>
    <div class="panel-body">
        <form id='form-filter'>
            <div class="row form-group">
                <div class="col-md-2">
                    <label for="month" class="form-label">Choose a month:</label>
                    <input type="month" id="month" name="month" class="form-control" required>
                </div>
                <div class="col-md-2">
                    <br>
                    <input type="button" value="<?=lang('lang.search')?>" id="loadData" class="btn btn-portage btn-sm">
                </div>
            </div>
            <div class="row form-group">
                <!-- Show Multiselect Game Filter -->
                <div class="col-md-12">
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
        </form>
    </div>
</div>

<div class="panel panel-primary" >
    <div class="panel-heading">
        <h4 class="panel-title"><i class="icon-dice"></i> <?=lang('report.s07')?> </h4>
    </div>
    <div class="panel-body" >
        <div class="table-responsive">
            <table class="table table-bordered table-hover " id="myTable">
                <thead>
                    <tr>
                        <th><?=lang('Game Platform')?></th>
                        <th><?=lang('Game Type')?></th>
                        <th><?=lang('Timezone')?></th>
                        <th><?=lang('Start Of Billing')?></th>
                        <th><?=lang('Game Fee')?></th>
                        <th><?=lang('aff.as24')?></th>
                        <th><?=lang('report.g09')?></th>
                        <th><?=lang('Agency Payout')?> <i class="fa fa-exclamation-circle" data-toggle="tooltip" title="<?=lang("games_report_payout_formula")?>" data-container="body"></i></th>
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
                        <th class="text-right text-primary"><span class="total-player">0</span></th>
                        <th class="text-right text-primary"><span class="total-bet">0.00</span></th>
                        <th class="text-right text-primary"><span class="total-payout">0.00</span></th>
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

<script>
    const currentDate = new Date();
    currentDate.setDate(1);
    const previousMonth = currentDate.toISOString().split('T')[0].substring(0, 7);
    document.getElementById('month').setAttribute('max', previousMonth);
    document.getElementById('month').value = previousMonth;

    var loadParams = {
        platformId      : '<?= $conditions["external_system"] ?  $conditions["external_system"] : 0 ?>',
        gameTypeId      : '<?= $conditions["game_type"] ? $conditions["game_type"] : 0 ?>',
        gameTypeIdMultiple      : turnToArrayGametype('<?= $conditions["game_type_multiple"] ? $conditions["game_type_multiple"] : 0 ?>'),
        showMultiSelectFilter      : '<?= $conditions["show_multiselect_filter"] ?>',
    };

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

    var gameTypeParam = [];

    function turnToArrayGametype(gameTypeString){

        if (typeof gameTypeString === undefined || !gameTypeString) {
            return 0;
        }
        return gameTypeString.split('+');

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
                                    $("#_export_excel_queue_form").attr('action', site_url('/export_data/game_billing_report'));
                                    $("#_export_excel_queue_form [name=json_search]").val(JSON.stringify(d));
                                    $("#_export_excel_queue_form").submit();
                        <?php }else{?>

                        $.post(site_url('/export_data/game_billing_report'), d, function(data){
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
                { className: 'text-right', targets: [5,6,7,8,9,10,11] },
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
                $.post(base_url + "api/gameBillingReports", data, function(data) {
                    console.log(data);
                    $('.total-player').html('<i class="text-success" style="font-size:9;padding-right:10px;">('+message.distinctPlayers+')</i>'+data.summary[0].total_player);
                    $('.total-bet').text(addCommas(parseFloat(data.summary[0].total_bet).toFixed(2)));
                    $('.total-payout').text(addCommas(parseFloat(data.summary[0].total_payout).toFixed(2)));
                    $('.total-ave-bet').html(addCommas(parseFloat(data.summary[0].total_ave_bet).toFixed(2)) +'<br><i class="text-success" style="font-size:10px;">('+message.count+': '+data.summary[0].total_ave_count+')</i>' );
                    $('.total-win').text(addCommas(parseFloat(data.summary[0].total_win).toFixed(2)));
                    $('.total-loss').text(addCommas(parseFloat(data.summary[0].total_loss).toFixed(2)));
                    $('.total-revenue').text(addCommas(parseFloat(data.summary[0].total_revenue).toFixed(2)));
                    $('.total-revenue-percent').text(data.summary[0].total_revenue_percent + '%');

                    callback(data);
                    if ( dataTable.rows( { selected: true } ).indexes().length === 0 ) {
                        dataTable.buttons().disable();
                    }
                    else {
                        dataTable.buttons().enable();
                    }
                }, 'json');
           },
           drawCallback : function( settings ) {
            <?php if( ! empty($enable_freeze_top_in_list) ): ?>
                var _scrollBodyHeight = window.innerHeight;
                _scrollBodyHeight -= $('.navbar-fixed-top').height();
                _scrollBodyHeight -= $('.dataTables_scrollHead').height();
                _scrollBodyHeight -= $('.dataTables_scrollFoot').height();
                _scrollBodyHeight -= $('#myTable_paginate').closest('.panel-body').height();
                _scrollBodyHeight -= 44;// buffer
                $('.dataTables_scrollBody').css({'max-height': _scrollBodyHeight+ 'px'});
            <?php endif;?>
           },

        });

        $('#loadData').on('click', function () {
            dataTable.ajax.reload(); // Reload the DataTable with new data from the AJAX call
        });

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
            // $('#gamePlatform').removeAttr('disabled');
            // $('#gameType').removeAttr('disabled');
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
                // $('#gamePlatform').removeAttr('disabled');
                // $('#gameType').removeAttr('disabled');
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
    });
</script>
