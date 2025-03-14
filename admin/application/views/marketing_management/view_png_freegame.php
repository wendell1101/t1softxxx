<style type="text/css">
#timezone, input, select{
    font-weight: bold;
}
#timezone{
    height: 36px;
}

.select2-container--default .select2-selection--single{
    padding-top: 2px;
    height: 35px;
    font-size: 1.2em;
    position: relative;
    border-radius: 0;
    font-size:12px;
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
</style>
<div class="panel panel-primary hidden">

    <div class="panel-heading">
        <h4 class="panel-title">
            <i class="fa fa-search"></i> <?=lang("lang.search")?>
            <span class="pull-right">
                <a data-toggle="collapse" href="#collapseGamesReport" class="btn btn-info btn-xs <?=$this->config->item('default_open_search_panel') ? '' : 'collapsed'?>"></a>
            </span>
            <?php include __DIR__ . "/../includes/report_tools.php"?>
        </h4>
    </div>

         <!-- The Modal for ADD FREE GAME OFFER -->
    <div class="modal" id="addFreeGameOfferModal">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <!-- Modal Header -->
                <div class="modal-header">
                    <h4 class="modal-title"><?=lang('Add Free Game Offer')?>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </h4>
                </div>
                    <!-- Modal body -->
                <div class="modal-body">
                    <form id="game-update-active-form" class="form-horizontal" action="<?=base_url('/marketing_management/postAddFreeGameOffer')?>" method="post" accept-charset="utf-8">
                        <p class="text-danger"><?=lang('Note: Some of fields like <b>Lines and Coins</b> depends on game settings like some of them have fixed Lines/ Coins')?></p class="text-danger">
                        
                        <p class="text-danger"><?=lang('reg.02')?></p>

                        <div class="form-group">
                            <label class="col-md-3"><?=lang('Player Name')?> <span class="text-danger">*</span></label>
                            <div class="col-md-8">

                        <select id="selectPlayer" class="multi-select-filter form-control" name="pngPlayers" style="width:100%;" required>
                                    <?php foreach ($png_players as $player): ?>
                                        <option value="<?=$player['game_username']?>"><?=$player['player_username']?></option>
                                    <?php endforeach ?>
                        </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-md-3"><?=lang('Games')?> <span class="text-danger">*</span></label>
                            <div class="col-md-8">
                        <select id="selectGameCode" class="multi-select-filter form-control" name="pngGames[]" multiple="multiple" style="width:100%;" required="">
                                    <?php foreach ($listOfGames as $game): ?>
                                        <option value="<?=$game['game_code']?>"><?=$game['english_name']?></option>
                                    <?php endforeach ?>
                        </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-md-3"><?=lang('Rounds')?> <span class="text-danger">*</span></label>
                            <div class="col-md-8">
                            <input type="text" name="rounds" id="rounds" value="<?=($conditions['rounds']) ? $conditions['rounds']:''?>" class="form-control number_only" required="" placeholder="Number of rounds in the offer."/>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-md-3"><?=lang('Lines')?></label>
                            <div class="col-md-8">
                            <input type="text" name="lines" id="lines" value="<?=($conditions['lines']) ? $conditions['lines']:''?>" class="form-control number_only" placeholder="Number of lines in the offer"/>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-md-3"><?=lang('Coins')?></label>
                            <div class="col-md-8">
                            <input type="text" name="coins" id="coins" value="<?=($conditions['coins']) ? $conditions['coins']:''?>" class="form-control number_only" placeholder="Number of coins in the offer."/>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-md-3"><?=lang('Denomination')?></label>
                            <div class="col-md-8">
                            <input type="text" name="denomination" id="denomination" value="<?=($conditions['denomination']) ? $conditions['denomination']:''?>" class="form-control number_only" placeholder="The denomination (coin value) of the offer."/>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-md-3"><?=lang('Expire Time')?> <span class="text-danger">*</span></label>
                            <div class="col-md-8">
                            <input type="datetime-local" name="expire_time" id="expire_time" value="<?=($conditions['expire_time']) ? $conditions['expire_time']:''?>" step="1" required class="form-control number_only"/>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-md-3"><?=lang('Turnover')?></label>
                            <div class="col-md-8">
                            <input type="text" name="turnover" id="turnover" value="<?=($conditions['turnover']) ? $conditions['turnover']:''?>" class="form-control number_only" placeholder="Turnover of the offer"/>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-md-3"><?=lang('Request ID ( Auto Generated )')?></label>
                            <div class="col-md-8">
                            <input type="text" name="request_id" id="request_id" value="<?=$request_id?>" class="form-control" disabled/>
                            <input type="hidden" name="request_id_hidden" id="request_id_hidden" value="<?=$request_id?>" class="form-control"/>
                            </div>
                        </div>
                </div>
                    <!-- Modal footer -->
                <div class="modal-footer">
                    <button type="button" class="btn btn-linkwater" data-dismiss="modal">Close</button>
                    <button type="submit" id="batchUpdateModalSubmit" class="btn btn-scooter"><?=lang("Submit")?></button>
                </div>
            </form>
            </div>
        </div>
    </div>

    <div id="collapseGamesReport" class="panel-collapse <?=$this->config->item('default_open_search_panel') ? '' : 'collapse in'?>">
        <div class="panel-body">
            <form id="form-filter" class="form-horizontal" method="GET" onsubmit="return validateForm();">
                <div class="row">

                    <div class="col-md-4 col-lg-4">
                       <label class="control-label" for="group_by"><?=lang('Date Creation')?> </label>
                        <input class="form-control dateInput" id="datetime_range" data-start="#datetime_from" data-end="#datetime_to" data-time="false"/>
                        <input type="hidden" id="datetime_from" name="datetime_from" value="<?=$conditions['datetime_from'];?>"/>
                        <input type="hidden" id="datetime_to" name="datetime_to" value="<?=$conditions['datetime_to'];?>"/>
                     </div>

                    <div class="col-md-2">
                        <label class="control-label" for="gamesSearch"><?=lang('Games')?> </label>

                        <select id="gamesSearch" class="multi-select-filter form-control" name="gamesSearch" style="width:100%;">
                                    <option value = "false">-- Default --</option>
                                    <?php foreach ($gamesSearch as $game): ?>
                                        <option value="<?=$game['game_code']?>"><?=$game['english_name']?></option>
                                    <?php endforeach ?>
                        </select>
                    </div>
                </div>


                <div class="row">
                    <div class="col-md-1" style="text-align:center;padding-top:24px;">
                        <input type="submit" value="<?=lang('lang.search')?>" id="search_main" class="btn col-md-12 btn-info btn-sm">
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="panel panel-primary" >
    <div class="panel-heading">
        <h4 class="panel-title"><i class="icon-dice"></i> <?=lang('PNG Free Game')?>
            <button type="button" id="addFreeGameOffer" data-toggle="modal" data-target="#addFreeGameOfferModal" class="btn btn-primary pull-right btn-xs" style="margin-top:0px">
                <i class="glyphicon glyphicon-plus" data-placement="bottom"></i>
                <?=lang('sys.gd22');?>
            </button>
        </h4>
    </div>
    <div class="panel-body" >
        <table class="table table-bordered table-hover " id="myTable">
            <thead>
                <tr>
                    <th><?=lang('Action')?></th>
                    <th><?=lang('Request ID')?></th>
                    <th><?=lang('Player Username')?></th>
                    <th><?=lang('Games')?></th>
                    <th><?=lang('Lines')?></th>
                    <th><?=lang('Coins')?></th>
                    <th><?=lang('Denomination')?></th>
                    <th><?=lang('Rounds')?></th>
                    <th><?=lang('Turnover')?></th>
                    <th><?=lang('Expiration Time')?></th>
                    <th><?=lang('Created At')?></th>
                </tr>
            </thead>
        </table>
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

    $("body").on('click', '.delete-png', function () {
        if (confirm("Are you sure you want to delete this offer?")) {
            return true;
        }
        return false;
    });

    $('.multi-select-filter').select2();

    var baseUrl =  '<?= base_url(); ?>';
    var loadParams = {
        platformId      : '<?= $conditions["external_system"] ?  $conditions["external_system"] : 0 ?>',
        gameTypeId      : '<?= $conditions["game_type"] ? $conditions["game_type"] : 0 ?>',
        gameTypeIdMultiple      : turnToArrayGametype('<?= $conditions["game_type_multiple"] ? $conditions["game_type_multiple"] : 0 ?>'),
    };
    var gamePlatformId;

    var gamesSearch = <?=($conditions['gamesSearch']) ?$conditions['gamesSearch']:0?>;
    var message = {
        gameType         : '<?= lang('Select Game Type'); ?>',
        distinctPlayers  : '<?= lang('Distinct Players'); ?>',
        totalBets       : '<?=lang('report.g09')?>',
        totalWins       : '<?=lang('report.g10')?>',
        totalLosses       : '<?=lang('report.g11')?>',
        totalPayouts     : '<?=lang('Payout')?>',
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

    function processFilters(filters,$id){
        $('#'+$id+' option').prop('selected', false);
        if ($.isArray(filters)) {
            $.each(filters,function(key,filter){
                $('#'+$id+' option[value="'+filter+'"]').prop('selected', true);
            });
        }else{
            $('#'+$id+' option[value="'+filters+'"]').prop('selected', true);
        }
        $('#'+$id+'').trigger('chosen:updated');
        $('#'+$id+'').trigger('change');
    }

    function validateForm(){
        // $('#form-filter')

        // utils.safelog($('#datetime_from').val());
        // utils.safelog($('#datetime_to').val());

        // utils.safelog($('#datetime_from').val().substr(14,5));
        // utils.safelog($('#datetime_to').val().substr(14,5));

        // if($('#datetime_from').val().substr(14,5)!='00:00'){
        //     alert('<?php echo lang("Please donot change minute and second, minimum level is hour")?>');
        //     $('#datetime_range').focus();
        //     return false;
        // }

        // if($('#datetime_to').val().substr(14,5)!='59:59'){
        //     alert('<?php echo lang("Please donot change minute and second, minimum level is hour")?>');
        //     $('#datetime_range').focus();
        //     return false;
        // }

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
        tableHeaders =  data.game_platform_header_map,
         th = "<th>"+message.playerId+"</th><th>"+message.playerUsername+"</th>",
         rowHeaders=[];

         rowHeaders.push(message.playerId);
         rowHeaders.push(message.playerUsername);

        if ( $.fn.DataTable.isDataTable('#player-bets-per-game') ) {
           $('#player-bets-per-game').DataTable().destroy();
        }

       existTableHeaders = [];

       for (var key in tableHeaders) {

           rowHeaders.push(gameApisMapObj[key]);
           rowHeaders.push(message.Wins+gameApisMapObj[key]);
           rowHeaders.push(message.Loss+gameApisMapObj[key]);
           rowHeaders.push(message.Payout+gameApisMapObj[key]);

           th +=  '<th>'+gameApisMapObj[key]+'</th><th class="text-success">'+message.Wins+'<i style="font-size:9px">('+gameApisMapObj[key]+')</i></th><th class="text-danger">'+message.Loss+'<i style="font-size:9px">('+gameApisMapObj[key]+')</i></th><th class="text-warning">'+message.Payout+'<i style="font-size:9px">('+gameApisMapObj[key]+')</i></th>';
           existTableHeaders.push(key);
       }

       rowHeaders.push(message.totalBets)
       rowHeaders.push(message.totalWins);
       rowHeaders.push(message.totalLosses);
       rowHeaders.push(message.totalPayouts);

       th += "<th>"+message.totalBets+"</th><th class='text-success'>"+message.totalWins+"</th><th class='text-danger'>"+message.totalLosses+"</th><th class='text-warning' >"+message.totalPayouts+"</th>";
       $('#player-bets-per-game tr:first').html("");
       $('#player-bets-per-game tr:first').append(th);

       //return;

       var tbody = '';

        //use the fastest way https://jsperf.com/fastest-way-to-iterate-object
        var keys = Object.keys(playerTotalBetsPerGame);
        var len = keys.length;


        for (var i = 0; i < len; i++) {
            var rowObj = {};
            var tr = '<tr>', tds = '', playerRow = playerTotalBetsPerGame[keys[i]], //rows of game
            betDetails = playerRow.bet_details,
            hlen = existTableHeaders.length,
            bdArrkeys = Object.keys(betDetails);


            rowObj['playerId']= playerRow.player_id;
            rowObj['username'] = playerRow.username;

            tds +='<td>'+playerRow.player_id+'</td>';
            tds +='<td>'+playerRow.username+'</td>';

               for (var n=0; n < hlen;  n++) {
                    if(bdArrkeys.indexOf(existTableHeaders[n]) > -1){
                   //  console.log(betDetails[existTableHeaders[n]])

                        rowObj['total_bet'+n] = betDetails[existTableHeaders[n]].total_bet.replace( /<.*?>/g, '' );
                        rowObj['total_win'+n]= betDetails[existTableHeaders[n]].total_win.replace( /<.*?>/g, '' );
                        rowObj['total_loss'+n] = betDetails[existTableHeaders[n]].total_loss.replace( /<.*?>/g, '' );
                        rowObj['total_payout'+n] = betDetails[existTableHeaders[n]].total_payout.replace( /<.*?>/g, '' );

                        tds += '<td class ="text-right" >' +betDetails[existTableHeaders[n]].total_bet+ '</td><td class ="text-right text-success" >' +betDetails[existTableHeaders[n]].total_win+ '</td><td class ="text-right text-danger" >' +betDetails[existTableHeaders[n]].total_loss+ '</td><td class ="text-right text-warning" >' +betDetails[existTableHeaders[n]].total_payout+ '</td>'
                    }else{

                        rowObj['total_bet'+n] = '0.00';
                        rowObj['total_win'+n] = '0.00';
                        rowObj['total_loss'+n] = '0.00';
                        rowObj['total_payout'+n] = '0.00';

                        tds += '<td class ="text-right text-muted" >0.00</td><td class ="text-right text-muted" >0.00</td><td class ="text-right text-muted" >0.00</td><td class ="text-right text-muted" >0.00</td>';
                    }
                }

            rowObj['sum_total_bets'] = playerRow.sum_total_bets.replace( /<.*?>/g, '' );
            rowObj['sum_total_wins'] = playerRow.sum_total_wins.replace( /<.*?>/g, '' );
            rowObj['sum_total_loss'] = playerRow.sum_total_loss.replace( /<.*?>/g, '' );
            rowObj['sum_total_payout'] = playerRow.sum_total_payout.replace( /<.*?>/g, '' );

            tds +='<td class ="text-right text-primary">'+playerRow.sum_total_bets+'</td><td class ="text-right text-primary">'+playerRow.sum_total_wins+'</td><td class ="text-right text-primary">'+playerRow.sum_total_loss+'</td><td class ="text-right text-primary">'+playerRow.sum_total_payout+'</td>';
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
        var footerCount = jsonObjForExportToCSV.header_data.length ;

            var  footerTotals = '';
            for(var i=1; i<=footerCount; i++){
              if(i==1){
                 footerTotals += '<th class="text-right "><span style="font-weight:bold;">'+message.subTotal+'</span><br><span style="font-weight:bold;" class="text-primary">'+message.total+'</span></th>';
              }else{
                 footerTotals += '<th class="text-right "></th>';
              }
            }
              footerTotals = '<tr >'+footerTotals+'</tr>';

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
        processFilters(gamesSearch,'gamesSearch');
        $('#png_free_game_offer').addClass('active');
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

//---------------------Multi select search start----------------------------



   if(loadParams.showMultiSelectFilter == 'true'){
         $('#show-multiselect-filter').val('true');
         $('#show-multiselect-filter').attr("checked", true);
         $('#gamePlatform').attr('disabled', 'disabled');
         $('#gameType').attr('disabled', 'disabled');
         $('#gameTypeCheckBoxes').show();

     }else{
      $('#game-type-multiple').val("");
      $('#show-multiselect-filter').val('false');
      $('#show-multiselect-filter').attr("checked", false);
      $('#gameTypeCheckBoxes').hide();
      // $('#gamePlatform').removeAttr('disabled');
      // $('#gameType').removeAttr('disabled');
    }

  $('#show-multiselect-filter').click(function(){
      if(this.checked) {
         $(this).val('true');
         $(this).attr("checked", true);
         // $('#gamePlatform').attr('disabled', 'disabled');
         // $('#gameType').attr('disabled', 'disabled');
         $('#gameTypeCheckBoxes').show();
     }else{
      $('.game-types-all').attr("checked", false);
      $(this).val('false');
      $(this).attr("checked", false);
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
     //---------------------Multi select search  end----------------------------

       var existTableHeaders = [];

            // Initialize DataTable jQuery plugin on the main table
        var dataTable = $('#myTable').DataTable({
            dom: "<'panel-body' <'pull-right'B><'pull-right'f><'pull-right progress-container'>l><'dt-information-summary1 text-info pull-left' i>t<'panel-body'<'pull-right'p><'dt-information-summary2 text-info pull-left' i>>",
            autoWidth: false,

            <?php if ($this->utils->isEnabledFeature('column_visibility_report')){ ?>
                stateSave: true,
            <?php } else { ?>
                stateSave: false,
            <?php } ?>

            buttons: [
                { extend: 'colvis', postfixButtons: [ 'colvisRestore' ], className: '<?=$this->utils->getConfig('use_new_sbe_color') ? 'btn-linkwater' : ''?>'},
                    {

                        text: "<?php echo lang('CSV Export'); ?>",
                        className:'btn btn-sm <?=$this->utils->getConfig('use_new_sbe_color') ? 'btn-portage' : 'btn-primary'?>',
                        action: function ( e, dt, node, config ) {
                            var d = {'extra_search':$('#search-form').serializeArray(), 'draw':1, 'length':-1, 'start':0};
                            // utils.safelog(d);

                            $.post(site_url('/export_data/export_png_freegame'), d, function(data){
                                // utils.safelog(data);

                                //create iframe and set link
                                if(data && data.success){
                                    $('body').append('<iframe src="'+data.link+'" frameborder="0" scrolling="no" style="border:0px;width:0px;height:0px"></iframe>');
                                }else{
                                    alert('export failed');
                                }
                            });
                        }
                    }
            ],
            columnDefs: [
                { sortable: false, targets: [ 0 ] },
            ],
            "order": [[ 1, 'asc' ]],
            processing: true,
            serverSide: true,
            ajax: function (data, callback, settings) {
                var formData = $('#form-filter').serializeArray();
                data.extra_search = formData;
                $.post(base_url + "api/pngFreeGameOfferReport", data, function(data) {
                    console.table(data);
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

       // var dataTable = $('#myTable').DataTable({
       //      dom: "<'panel-body'<'pull-right'B><'pull-right progress-container hidden-xs'>l><'dt-information-summary1 text-info pull-left' i>t<'panel-body'<'pull-right'p><'dt-information-summary2 text-info pull-left' i>>",
       //      autoWidth: false,
       //      searching: false,

       //      <?php if ($this->utils->isEnabledFeature('column_visibility_report')){ ?>
       //          stateSave: true,
       //      <?php } else { ?>
       //          stateSave: false,
       //      <?php } ?>

       //      //dom: "<'panel-body'l>t<'text-center'r><'panel-body'<'pull-right'p>i>",
       //      buttons: [
       //          {
       //              extend: 'colvis',
       //              postfixButtons: [ 'colvisRestore' ]
       //          }
       //          <?php if ($export_report_permission) {?>
       //          ,{
       //              text: "<?php echo lang('CSV Export'); ?>",
       //              className:'btn btn-sm btn-primary',
       //              action: function ( e, dt, node, config ) {
       //                  var d = {'extra_search':$('#form-filter').serializeArray(), 'draw':1, 'length':-1, 'start':0};
       //                  // utils.safelog(d);

       //                  $.post(site_url('/export_data/export_png_freegame'), d, function(data){
       //                      // utils.safelog(data);

       //                      //create iframe and set link
       //                      if(data && data.success){
       //                          $('body').append('<iframe src="'+data.link+'" frameborder="0" scrolling="no" style="border:0px;width:0px;height:0px"></iframe>');
       //                      }else{
       //                          alert('export failed');
       //                      }
       //                  }).fail(function(){
       //                      alert('export failed');
       //                  });
       //              }
       //          }
       //          <?php } ?>
       //      ],
       //      columnDefs: [
       //          { className: 'text-right', targets: [6,7,8,9,10] },
       //          { visible: false, targets: [ <?php echo implode(',', $this->config->item('game_reports_hidden_cols')); ?>  ] }
       //      ],
       //      "order": [ 0, 'asc' ],

       //      // SERVER-SIDE PROCESSING
       //      processing: true,
       //      serverSide: true,
       //      ajax: function (data, callback, settings) {

       //          var formData = $('#form-filter').serializeArray();
       //          data.extra_search = formData;
       //          $.post(base_url + "api/pngFreeGameOfferReport", data, function(data) {
       //              console.table(data);
       //              callback(data);
       //              if ( dataTable.rows( { selected: true } ).indexes().length === 0 ) {
       //                  dataTable.buttons().disable();
       //              }
       //              else {
       //                  dataTable.buttons().enable();
       //              }

       //              callback(data);
       //              if ( dataTable.rows( { selected: true } ).indexes().length === 0 ) {
       //                  dataTable.buttons().enable();
       //              }
       //              else {
       //                  dataTable.buttons().enable();
       //              }
       //          }, 'json');
       //     }
       //     ,
       //     drawCallback : function(data, type, row) {
       //     },
       //     "rowCallback": function( row, data, index ) {
       //     },
       //  });
    });

</script>
