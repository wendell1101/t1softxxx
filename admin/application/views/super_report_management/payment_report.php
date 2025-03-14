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
                        <div class="form-group">
                            <div class="col-xs-4">
                                <label for="amount_greater_than" class="control-label"><?= lang('report.p16'); ?></label>
                                <input id="amount_greater_than" name="amount_greater_than" class="form-control input-sm" value="<?=$conditions['amount_greater_than'];?>">
                            </div>    
                            <div class="col-xs-4">
                                <label for="amount_less_than" class="control-label"><?= lang('report.p15'); ?></label>
                                <input id="amount_less_than" name="amount_less_than" class="form-control input-sm" value="<?=$conditions['amount_less_than'];?>">
                            </div>   
                        </div>
                    </div>
                    <div class="col-md-5">
                        <div class="form-group">
                            <label for="reportDate" class="control-label col-xs-4"><?= lang('aff.as19'); ?></label>
                            <div class="col-xs-7">
                                <input id="username" name="username" class="form-control input-sm" value="<?=$conditions['username'];?>">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="collection_account" class="control-label col-xs-4"><?= lang('Group By'); ?></label>
                            <div class="col-xs-7">
                                <?= form_dropdown('group_by', $group_by_list, $conditions['group_by'], 'id="group_by" class="form-control input-sm group-reset"'); ?>
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
            <?=lang('report.s03')?>
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
                <table class="table table-condensed table-bordered table-hover report-table" id="reportTable_<?=$value?>"  data-page-length="10">
                    <thead>
                        <tr>
                        <?php foreach ($show_cols as $col) :?>
                            <?php if($col == 'currency'): ?>
                                <th><?= lang('Currency'); ?></th>
                            <?php endif; ?>

                            <?php if($col == 'date'): ?>
                                <th ><?= lang('Date'); ?></th>
                            <?php endif; ?>

                            <?php if($col == 'userName'): ?>
                                <th ><?= lang('Player Username'); ?></th>
                            <?php endif; ?>

                            <?php if($col == 'levelName'): ?>
                                <th style="min-width:150px;" style="min-width:150px;"><?=lang("Group Level")?></th>
                            <?php endif; ?>

                            <?php if($col == 'transactionType'): ?>
                                <th><?=lang("Transaction Type")?></th>
                            <?php endif; ?>

                            <?php if($col == 'paymentAccount'): ?>
                                <th><?=lang("pay.deposit_payment_account_name")?></th>
                            <?php endif; ?>

                            <?php if($col == 'amount'): ?>
                                <th><?=lang("Amount")?></th>
                            <?php endif; ?>
                        <?php endforeach; ?> 
                     </tr>
                 </thead>
                 <tfoot>
                    <tr>
                        <th colspan="4" style="text-align:right"><?php echo lang('Sub Total') ?></th>
                        <th class="text-right text-primary"><span id="sub_total_amount_<?=$value?>">0.00</span></th>
                    </tr>
                    <tr>
                        <th colspan="4" style="text-align:right"><?php echo lang('Total') ?></th>
                        <th class="text-right text-primary"><span id="total_amount_<?=$value?>">0.00</span></th>
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
               
                            $("#_export_excel_queue_form").attr('action', site_url('/export_data/export_super_payment_report'));
                            $("#_export_excel_queue_form [name=json_search]").val(JSON.stringify(d));
                            $("#_export_excel_queue_form").submit();         
                    }
                }
                <?php } ?>
            ],
            order: [ 1, 'desc' ],
            columnDefs: [
                { visible: false, targets:[0] },
                { className:'text-center', targets: [0] }
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
              
                $.post(base_url + "api/super_payment_report", data, function(data) {
                    $('#sub_total_amount_<?=$value?>').text(data.summary_amount.sub_amount);
                    $('#total_amount_<?=$value?>').text(data.summary_amount.total_amount);
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
});// document ready


</script>