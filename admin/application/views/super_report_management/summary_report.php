<style>
    .dataTables_wrapper { overflow-y: hidden; overflow-x: hidden; }
    .monthly-report-box{
        line-height: 70px;
        height: 64px;
    }
    .dt-button-collection{
        max-height: 450px !important; 
        overflow-y: auto !important;
    }
</style>

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
                        <div class="form-group">
                            <label class="control-label col-xs-4" for=""><?= lang('Currency'); ?></label>
                            <div class="col-xs-7">
                                <label class="checkbox-inline">
                                    <input type="checkbox" id="currency-all" value="all"> All
                                </label>
                                <?php foreach ($currency_list as $value) :?>
                                    <?php if($value == 'super'):?>
                                        <?php continue; ?>
                                    <?php else: ?>
                                        <label class="checkbox-inline">
                                            <input type="checkbox" class="currency-selectors"  currency="<?=($value) ?>" value="<?= $value ?>"> <?= strtoupper($value) ?>
                                        </label>
                                    <?php endif; ?>
                                <?php endforeach; ?> 
                                <div class="" style="">
                            <i class="text-danger"  id="no-chosen-currency-msg"style="font-size:12px;visibility: hidden;"><?=lang('con.usm35')?></i>
                        </div>       
                          <input type="hidden" name="chosen_currencies"  id="chosen_currencies" value="<?= $conditions['chosen_currencies']; ?>" >
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="reportDate" class="control-label col-xs-4"><?= lang('aff.ap07'); ?></label>
                            <div class="col-xs-7">
                                <input id="reportDate" name="" class="form-control input-sm dateInput" data-start="#date_from" data-end="#date_to" data-time="true" autocomplete="off">
                                <input type="hidden" id="date_from" name="date_from"  value="<?=$conditions['date_from'];?>" >
                                <input type="hidden" id="date_to" name="date_to" value="<?=$conditions['date_to'];?>">
                            </div>
                        </div>
                    </div>
                    <div class="col-md-5">
                        <div class="form-group">
                            <div class="col-xs-7">
                               <label class="control-label"><?=lang('Month Only')?> : </label>
                               <input type="checkbox" name="month_only" id="month_only"  <?php echo $conditions["month_only"] ? 'checked="checked"' : ''; ?> />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="panel-footer text-center">
                <input type="button" value="<?= lang('Reset') ?>" class="btn btn-default btn-sm" id="reset">
                <button type="submit" name="submit" class="btn btn-primary btn-sm"><i class="fa"></i> <?= lang('lang.search'); ?></button>
            </div>
        </form>
    </div>
</div>

<div class="panel panel-primary">
    <div class="panel-heading custom-ph">
        <h4 class="panel-title">
            <i class="icon-pie-chart"></i>
            <?=lang('report.s08')?>
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
        <?php if($value == 'super') :?>
                <h4><span class="label label-info"><?= lang('All')." ( $master_currency )" ?></span></h4>
            <?php else: ?>
                <h4><span class="label label-info"><?= strtoupper($value) ?></span></h4>
            <?php endif; ?>
            <div class="table-responsive">
                <table class="table table-condensed table-bordered table-hover report-table" id="reportTable_<?=$value?>" data-searching="false" data-ordering="true" data-page-length="10">
                    <thead>
                    <tr>
                        <th><?= lang('Currency'); ?></th><!---currency_key----->
                        <th style="min-width:50px;"><?= lang('report.sum02'); ?></th><!---summary_date----->
                        <th><?= lang('report.sum05'); ?></th><!---count_new_player----->
                        <th><?= lang('report.sum06'); ?></th><!---count_all_players----->
                        <th><?= lang('report.sum07'); ?></th><!---count_first_deposit----->
                        <th><?= lang('report.sum08'); ?></th><!---count_second_deposit----->
                        <th><?= lang('report.sum22')?></th><!---count_total_deposit_players----->
                        <th><?= lang('report.sum09'); ?></th><!---total_deposit----->
                        <th><?= lang('report.sum10'); ?></th><!---total_withdrawal----->
                        <th><?= lang('report.sum14'); ?></th><!---total_bonus----->
                        <th><?= lang('report.sum15'); ?></th><!---total_cashback----->
                        <th id ="percentage_of_bonus_cashback_bet" ><?= lang('report.percentage_of_bonus_cashback_bet')?>&nbsp;<i class="fa fa-info-circle"></i></th>
                        <th><?= lang('report.sum21')?></th><!----total_player_fee---->
                        <th><?= lang('Withdraw Fee from player')?></th><!----total_withdrawal_fee_from_player---->
                        <th><?= lang('report.sum16'); ?></th><!----total_fee---->
                        <th><?= lang('report.sum18'); ?></th><!---total_bank_cash_amount----->
                        <th><?= lang('Total Bet'); ?></th><!---total_bet----->
                        <th><?= lang('Total Win'); ?></th><!---total_win----->
                        <th><?= lang('Total Loss'); ?></th><!---total_loss----->
                        <th><?= lang('report.Payout'); ?></th><!---gross_payout----->
                        <th><?= lang('Deposit Member'); ?></th><!---count_deposit_member----->
                        <th><?= lang('Active Member'); ?></th><!---count_active_member----->
                        <th id="retention" title="<?=lang("report.retention_formula")?>"><?= lang('Retention'); ?><i class="fa fa-exclamation-circle"></i></th>
                        <th id="ret_dp" title="<?=lang("report.ret_dp_formula")?>"><?= lang('ret_dp'); ?><i class="fa fa-exclamation-circle"></i></th>
                    </tr>
                    </thead>
                    <tfoot>
                        <tr>
                            <th></th>
                            <th ><span><?= lang('Total') ?></span></th>
                            <th class="text-right text-primary"><span id="count_new_player_<?=$value?>"></span></th>
                            <th class="text-right text-primary"><span id="count_all_players_<?=$value?>"></span></th>
                            <th class="text-right text-primary"><span id="count_first_deposit_<?=$value?>">0.00</span></th>
                            <th class="text-right text-primary"><span id="count_second_deposit_<?=$value?>">0.00</span></th>
                            <th class="text-right text-primary"><span id="count_total_deposit_players_<?=$value?>">0.00</span></th>
                            <th class="text-right text-primary"><span id="total_deposit_<?=$value?>">0.00</span></th>
                            <th class="text-right text-primary"><span id="total_withdrawal_<?=$value?>">0.00</span></th>
                            <th class="text-right text-primary"><span id="total_bonus_<?=$value?>">0.00</span></th>
                            <th class="text-left text-primary"><span id="total_cashback_<?=$value?>">0.00</span></th>
                            <th class="text-left text-primary"><span id="total_percentage_of_bonus_cashback_bet_<?=$value?>">0.00%</span></th>
                            <th class="text-left text-primary"><span id="total_player_fee_<?=$value?>">0.00</span></th>
                            <th class="text-left text-primary"><span id="total_withdrawal_fee_from_player_<?=$value?>">0.00</span></th>
                            <th class="text-left text-primary"><span id="total_fee_<?=$value?>">0.00</span></th>
                            <th class="text-left text-primary"><span id="total_bank_cash_amount_<?=$value?>">0.00</span></th>
                            <th class="text-left text-primary"><span id="total_bet_<?=$value?>">0.00</span></th>
                            <th class="text-left text-primary"><span id="total_win_<?=$value?>">0.00</span></th>
                            <th class="text-left text-primary"><span id="total_loss_<?=$value?>">0.00</span></th>
                            <th class="text-left text-primary"><span id="gross_payout_<?=$value?>">0.00</span></th>
                            <th class="text-left text-primary"><span id="deposit_member_<?=$value?>">0.00</span></th>
                            <th class="text-left text-primary"><span id="active_member_<?=$value?>">0.00</span></th>      
                            <th class="text-left text-primary"><span id="total_retention_<?=$value?>">0.00%</span></th>
                            <th class="text-left text-primary"><span id="total_ret_dp_<?=$value?>">0.00%</span></th>                            
                        </tr>
                </tfoot>
                </table>
            </div>
        </div>
        <div class="modal fade" id="myModal<?=$value?>" tabindex="-1" role="document" aria-labelledby="myModalLabel">
            <div class="modal-dialog" role="document" style="max-width:300px;margin: 30px auto;">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title" id="myModalLabel<?=$value?>"><?php echo lang('Export Specific Columns') ?></h4>
                    </div>
                    <div class="modal-body" id="checkboxes-export-selected-columns-<?=$value?>">
                    ...
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-primary export-all-columns" data-currency="<?=$value?>" id="export-selected-columns-<?=$value?>" ><?php echo lang('CSV Export'); ?></button>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
    <div class="panel-footer"></div>
</div>

<form id="_export_excel_queue_form" class="hidden" method="POST" target="_blank">
    <input name='json_search' type="hidden">
</form>

<script type="text/javascript" src="<?=site_url().'resources/datatables/dataTables.buttons.min.js'?>"></script>
<script type="text/javascript" src="<?=site_url().'resources/datatables/jszip.min.js'?>"></script>
<script type="text/javascript" src="<?=site_url().'resources/datatables/buttons.html5.min.js'?>"></script>
<script>
    var currentColumns = [];
    var exportSelectedColumns = 'exportSelectedColumns';
	var selectedColumns = [];
    var PER_COLUMN_CSV_EXPORTER = (function() {
		var table;
		var prefix = 0;
        var currentCurrency = '';

        function render(){
            var len = currentColumns.length,len2 =selectedColumns.length, checkboxes='';
            if (typeof currentCurrency !==  'undefined' || currentCurrency !== null || currentCurrency !== '') {
                exportSelectCheckbox = 'export-select-checkbox-' + currentCurrency;
            }
            for(var i=0; i<len; i++){
                checkboxes += '<div class="checkbox">';

                if(len2 > 0){
                    if (selectedColumns.indexOf(currentColumns[i].alias) > -1 ) {
                        checkboxes += '<label><input type="checkbox" class="' + exportSelectCheckbox + '" checked value="'+currentColumns[i].alias+'">'+currentColumns[i].name+'</label>';
                    } else {
                        checkboxes += '<label><input type="checkbox" class="' + exportSelectCheckbox + '" value="'+currentColumns[i].alias+'">'+currentColumns[i].name+'</label>';
                    }
                } else {
                    checkboxes += '<label><input type="checkbox" class="' + exportSelectCheckbox + '" value="'+currentColumns[i].alias+'">'+currentColumns[i].name+'</label>';
                }
                checkboxes += '</div>';
            }
            $('#checkboxes-export-selected-columns-' + currentCurrency).html(checkboxes);
        }

        function attachExportCheckboxesEvent(){
            if (typeof currentCurrency !==  'undefined' || currentCurrency !== null || currentCurrency !== '') {
                exportSelectedColumns = 'exportSelectedColumns' + currentCurrency;
            }
			var cols = [];
			var reportTh = document.querySelectorAll('#reportTable_<?=$value?> thead tr th');
			reportTh.forEach(function(item,val){
				cols.push(item.dataset.data);
			});
            $('.'+ exportSelectCheckbox).each(function(index, value) {
                $(this).click(function(){
                    if (selectedColumns.indexOf($(this).val()) > -1) {
                        var index = selectedColumns.indexOf($(this).val());
                        selectedColumns.splice(index, 1);
                    }else{
                        selectedColumns.push($(this).val());
                    }
                })
            });

            $("#" + exportSelectedColumns).remove();
            $("#search-form").append("<input type='hidden' id='" + exportSelectedColumns + "' name='" + exportSelectedColumns + "'/>");
            $(this).removeAttr("disabled");
		}
        
        $('.export-all-columns').click(function(){
            let curr = $(this).data('currency');

            $(this).attr('disabled', 'disabled');
            $("#" + exportSelectedColumns).val(selectedColumns.join(","));
            $('.export-all-columns-' + curr).trigger('click');
            //IMPORTANT REMOVE THE THIS ELEMENT AND APPEND NEW TO PREVENT BUG AFTER EXPORT: not fully understand
            $("#" + exportSelectedColumns).remove();
            $("#search-form").append("<input type='hidden' id='" + exportSelectedColumns + "' name='" + exportSelectedColumns + "'/>");
            $(this).removeAttr("disabled");
        });
        return {
            openModal:function(columns,selected,currency) {
                selectedColumns =  selected;
                currentColumns = columns;
                currentCurrency = currency;

				table = $('#reportTable_'+currency).DataTable();
                $('#myModal'+currency).modal('show');
                render();
                attachExportCheckboxesEvent();
			}
        }
	}());
    $(document).ready(function(){
    <?php $d = new DateTime(); ?>
    var filenames = '<?=lang('Summary Report').' '. $d->format('Y_m_d_H_i_s') . '_' . rand(1, 9999)?>';
    var columns = []
    var reportTh = document.querySelectorAll('#reportTable_<?=$value?> thead tr th');
    reportTh.forEach(function(item,val){
        columns.push({alias: item.dataset.data, name: item.innerText});
    });

    <?php foreach ($currency_list as $value):?>
         <?php
         // Dont display datatable not in chosen currencies
         $chosen_currencies = explode(",", $conditions['chosen_currencies']);
        
         if(!in_array($value, $chosen_currencies)){
            continue;
         }
        ?>
    var dataTable_<?php echo $value?> = $('#reportTable_<?php echo $value; ?>').DataTable({
            autoWidth: false,
            searching: false,
            bLengthChange: false,
            <?php if($this->utils->isEnabledFeature('column_visibility_report')):?>
                stateSave: true,
            <?php else : ?>
                stateSave: false,
            <?php endif; ?>            
            dom: "<'panel-body'<'pull-right'B><'pull-right progress-container hidden-xs'>l><'dt-information-summary1 text-info ' i>f t<'panel-body'<'pull-right'p>>",
                buttons: [
                    {
                        extend: 'colvis',
                        postfixButtons: [ 'colvisRestore' ],
                        collectionLayout: 'dropdown',
                        className: 'btn btn-sm btn-linkwater',
                    }
                    <?php if ($export_report_permission) {?>
                    ,{
                        text: "<?php echo lang('Export Specific Columns') ?>",
                        className:"btn btn-sm btn-scooter",
                        action: function ( e, dt, node, config ) {
                            var columns = dataTable_<?php echo $value?>.init().colsNamesAliases;

                            currency = '<?php echo $value?>';
                            var selected = [];
                            PER_COLUMN_CSV_EXPORTER.openModal(columns, selected, currency);

                            }
                        }
                    ,{
                        text: "<?php echo lang('CSV Export'); ?>",
                        className:'btn btn-sm  disabled btn-portage export-all-columns-<?php echo $value?>',
                        action: function ( e, dt, node, config ) {
                            var d = {'extra_search': $('#search-form').serializeArray(), 'export_format': 'csv', 'export_type': export_type, 'draw':1, 'length':-1, 'start':0};
                                d.extra_search.push({"name":"currency", "value":"<?php echo $value?>".toUpperCase()});
                                $("#_export_excel_queue_form").attr('action', site_url('/export_data/export_super_summary_report'));
                                $("#_export_excel_queue_form [name=json_search]").val(JSON.stringify(d));
                                $("#_export_excel_queue_form").submit();         
                        }
                    }
                    <?php } ?>
                ],
            columnDefs: [
                { visible: false, targets:[0] },
                { className: 'text-right', targets: [ 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23] },
                { className: 'text-center', targets: [0] }
            ],
            order: [[1,'asc'] ],
            "initComplete": function(settings){
                $("#reportTable_<?=$value?> thead th").each(function () {
                    var $td = $(this);
                    if($td.attr("id") == 'percentage_of_bonus_cashback_bet'){
                        $td.attr('title', '<?=lang("report.percentage_of_bonus_cashback_bet.desc")?>');
                    }
				});

                /* Apply the tooltips */
                $('#reportTable_<?php echo $value; ?> thead th[title]').tooltip({
                    "container": 'body'
                });
            },

            // SERVER-SIDE PROCESSING
            processing: true,
            serverSide: true,
           // destroy:true,
            ajax: function (data, callback, settings) {

                data.extra_search = $('#search-form').serializeArray(); 
                data.extra_search.push({"name":"currency", "value":"<?php echo $value?>".toUpperCase()});
                dataTable_<?php echo $value?> .buttons().disable();
                $('#search-form,.report-table,.btn').css("pointer-events", "none");
                $.post(base_url + "api/super_summary_report", data, function(data) {
                    dataTable_<?php echo $value?>.init().colsNamesAliases = data.cols_names_aliases;
                    $('#count_new_player_<?=$value?>').text(data.sub_summary.count_new_player);
                    // $('#count_all_players_<?=$value?>').text(data.sub_summary.count_all_players);
                    $('#count_first_deposit_<?=$value?>').text(data.sub_summary.count_first_deposit);
                    $('#count_second_deposit_<?=$value?>').text(data.sub_summary.count_second_deposit);
                    $('#count_total_deposit_players_<?=$value?>').text(data.sub_summary.count_deposit_member);
                    $('#total_deposit_<?=$value?>').text(data.sub_summary.total_deposit);
                    $('#total_withdrawal_<?=$value?>').text(data.sub_summary.total_withdrawal);
                    $('#total_bonus_<?=$value?>').text(data.sub_summary.total_bonus);
                    $('#total_cashback_<?=$value?>').text(data.sub_summary.total_cashback);
                    $('#total_percentage_of_bonus_cashback_bet_<?=$value?>').text(data.sub_summary.percentage_of_bonus_cashback_bet+'%');
                    $('#total_player_fee_<?=$value?>').text(data.sub_summary.total_fee);
                    $('#total_withdrawal_fee_from_player_<?=$value?>').text(data.sub_summary.total_withdrawal_fee_from_player);
                    $('#total_fee_<?=$value?>').text(data.sub_summary.total_fee);
                    $('#total_bank_cash_amount_<?=$value?>').text(data.sub_summary.total_bank_cash_amount);
                    $('#total_bet_<?=$value?>').text(data.sub_summary.total_bet);
                    $('#total_win_<?=$value?>').text(data.sub_summary.total_win);
                    $('#total_loss_<?=$value?>').text(data.sub_summary.total_loss);
                    $('#gross_payout_<?=$value?>').text(data.sub_summary.total_payout);
                    $('#deposit_member_<?=$value?>').text(data.sub_summary.count_deposit_member);
                    $('#active_member_<?=$value?>').text(data.sub_summary.count_active_member);
                    $('#total_retention_<?=$value?>').text(data.sub_summary.total_retention+'%');
                    $('#total_ret_dp_<?=$value?>').text(data.sub_summary.total_ret_dp+'%');
                    var isCheck_month = $("#month_only").is(":checked");
                    if (isCheck_month) {
                        $("#retention").attr('title', "<?=lang("report.retention_formula.month")?>");
                        $("#ret_dp").attr('title', "<?=lang("report.ret_dp_formula.month")?>");
                    }
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
        $('#month_only').prop( "checked",false);
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

 });

</script>