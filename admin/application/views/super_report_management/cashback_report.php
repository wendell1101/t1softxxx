<div class="panel panel-primary">
    <div class="panel-heading">
        <h4 class="panel-title">
            <i class="fa fa-search"></i> <?= lang('Search'); ?> <span class="pull-right">
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
                        <!-- <div class="form-group">
                            <label for="reportDate" class="control-label col-xs-4"><?= lang('aff.ap07'); ?></label>
                            <div class="col-xs-7">
                                <input id="reportDate" name="" class="form-control input-sm dateInput" data-start="#date_from" data-end="#date_to" data-time="true" autocomplete="off">
                                <input type="hidden" id="date_from" name="date_from"  value="<?=$conditions['date_from'];?>" >
                                <input type="hidden" id="date_to" name="date_to" value="<?=$conditions['date_to'];?>">
                            </div>
                        </div> -->
                    </div>

                    <!-- <div class="col-xs-3">
                        <div class="form-group">
                            <label for="reportDate" class="control-label col-xs-4"><?= lang('aff.as19'); ?></label>
                            <div class="col-xs-7">
                                <input id="username" name="username" class="form-control input-sm" value="<?=$conditions['username'];?>">
                            </div>
                        </div>
                    </div> -->

                </div>
            </div>
            <div class="panel-body">
                <div class="row">
                    <div class="col-md-3">
                        <label class="control-label"><?=lang('Registration Time')?>:</label>
                        <div class="input-group">
                            <input id="search_registration_date" class="form-control input-sm dateInput" data-start="#registration_date_from" data-end="#registration_date_to" data-time="true"/>
                            <span class="input-group-addon input-sm">
                                <input type="checkbox" name="search_reg_date" id="search_reg_date" data-size='mini' <?= $conditions['search_reg_date']  == 'on' ? 'checked="checked"' : '' ?> value='<?php echo $conditions['search_reg_date']?>' />
                            </span>
                            <input type="hidden" name="registration_date_from" id="registration_date_from" value="<?=$conditions['registration_date_from'];?>" />
                            <input type="hidden" name="registration_date_to" id="registration_date_to" value="<?=$conditions['registration_date_to'];?>" />
                        </div>
                    </div>
                </div>
                <div class="row">
                    <!-- Date -->
                    <div class="col-md-3 col-lg-3">
                        <label class="control-label"><?php echo lang('Date'); ?></label>
                        <input id="search_cashback_date" class="form-control input-sm dateInput user-success" data-start="#by_date_from" data-end="#by_date_to" data-time="false" autocomplete="off">
                        <input type="hidden" id="by_date_from" name="by_date_from" value="<?=$conditions['by_date_from'];?>">
                        <input type="hidden" id="by_date_to" name="by_date_to" value="<?=$conditions['by_date_to'];?>">
                    </div>
                    <div class="col-md-3 col-lg-3 hide">
                        <label class="control-label"><?php echo lang('Enabled date'); ?></label>
                        <input type="checkbox" data-off-text="<?php echo lang('off'); ?>" data-on-text="<?php echo lang('on'); ?>"  name="enable_date" id="enable_date" data-size='mini' value='true' <?php echo $conditions['enable_date'] ? 'checked="checked"' : ''; ?>>
                    </div>
                    <!-- Paid -->
                    <div class="col-md-3 col-lg-3">
                        <label class="control-label"><?php echo lang('paid.cashback'); ?></label>
                        <select name="by_paid_flag" id="by_paid_flag" class="form-control input-sm">
                            <option value="" <?=empty($conditions['by_paid_flag']) ? 'selected' : ''?>>--  <?php echo lang('None'); ?> --</option>
                            <option value="1" <?php echo ($conditions['by_paid_flag'] === '1') ? 'selected' : ''; ?>><?php echo lang('Paid'); ?></option>
                            <option value="0" <?php echo ($conditions['by_paid_flag'] === '0') ? 'selected' : ''; ?> ><?php echo lang('Not pay'); ?></option>
                        </select>
                    </div>
                    <!-- Player Username -->
                    <div class="col-md-3 col-lg-3">
                        <label class="control-label"><?php echo lang('Player Username'); ?></label>
                        <input type="text" name="by_username" id="by_username" class="form-control input-sm" placeholder='<?php echo lang('Enter Username'); ?>'
                        value="<?php echo $conditions['by_username']; ?>"/>
                    </div>
                    <!-- Player Level -->
                    <div class="col-md-3 col-lg-3">
                        <label class="control-label"><?php echo lang('VIP Level'); ?></label>
                        <?php echo form_dropdown('by_player_level', $vipgrouplist, $conditions['by_player_level'], 'id="by_player_level" class="form-control input-sm"') ?>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-3 col-lg-3">
                        <label class="control-label"><?php echo lang('Cashback Amount >='); ?></label>
                        <input type="text" name="by_amount_greater_than" id="by_amount_greater_than" value="<?=$conditions['by_amount_greater_than']?>" class="form-control input-sm number_only" placeholder='<?php echo lang('Enter Amount'); ?>'/>
                    </div>
                    <div class="col-md-3 col-lg-3">
                        <label class="control-label"><?php echo lang('Cashback Amount <='); ?></label>
                        <input type="text" name="by_amount_less_than" id="by_amount_less_than" value="<?=$conditions['by_amount_less_than']?>" class="form-control input-sm number_only" placeholder='<?php echo lang('Enter Amount'); ?>'/>
                    </div>
                    <!-- Agent Username -->
                    <div class="col-md-3 col-lg-3">
                        <label class="control-label"><?php echo lang('Agent Username'); ?></label>
                        <input type="text" name="agent_username" id="agent_username" class="form-control input-sm" placeholder='<?php echo lang('Enter Agent Username'); ?>'
                        value="<?php echo $conditions['agent_username']; ?>"/>
                    </div>
                    <!-- Player Tag -->
                    <div class="col-md-3 col-lg-3">
                        <label class="control-label"><?=lang('Player Tag')?>:</label>
                        <select name="tag_list[]" id="tag_list" multiple="multiple" class="form-control input-md">
                            <?php if (!empty($tags)) { ?>
                                <option value="notag" id="notag" <?=is_array($selected_tags) && in_array('notag', $selected_tags) ? "selected" : "" ?>><?=lang('player.tp12')?></option>
                                <?php foreach ($tags as $tag): ?>
                                    <option value="<?=$tag['tagName']?>" <?=is_array($selected_tags) && in_array($tag['tagName'], $selected_tags) ? "selected" : "" ?> ><?=$tag['tagName']?></option>
                                <?php endforeach ?>
                            <?php } ?>
                        </select>
                    </div>
                </div>
            </div>
            <div class="panel-footer text-right">
                <!-- <?php if ($this->permissions->checkPermissions('manually_pay_cashback')) {?>
                    <input class="btn btn-sm btn_payall m-r-30 btn-portage" type="button" value="<?php echo lang('Pay Today Cashback'); ?>" />
                <?php }?> -->
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
            <?=lang('Cashback Report')?>
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
            <?php if($value == 'super' && !empty($master_currency)) :?>
                <h4><span class="label label-info"><?= lang('All')." ( $master_currency )" ?></span></h4>
            <?php else: ?>
                <h4><span class="label label-info"><?= strtoupper($value) ?></span></h4>
            <?php endif; ?>
            <div class="table-responsive">
                <table class="table table-condensed table-bordered table-hover report-table" id="reportTable_<?=$value?>" data-page-length="10">
                    <thead>
                        <tr>
                             <th><?= lang('Currency'); ?></th>
                             <th style="min-width:100px;" ><?= lang('Date'); ?></th>
                             <th><?= lang('Player Username'); ?></th>
                             <th><?= lang('Agent Username'); ?></th>
                             <th style="min-width:100px;"><?= lang('Player Tag'); ?></th>
                             <th style="min-width:150px;"><?= lang('Player Level'); ?></th>
                             <th><?= lang('Amount'); ?></th>
                             <th><?= lang('Bet Amount'); ?></th>
                             <th><?= lang('Real Bet Amount'); ?></th>
                             <th style="min-width:50px;"><?= lang('Paid'); ?></th>
                             <th><?= lang('Game Platform'); ?></th>
                             <th style="min-width:200px;"><?= lang('sys.gd9'); ?></th>
                             <th style="min-width:100px;"><?= lang('Game Type'); ?></th>
                             <th style="min-width:100px;"><?= lang('Updated at'); ?></th>
                             <th style="min-width:100px;"><?= lang('Paid date'); ?></th>
                             <th style="min-width:100px;"><?= lang('Registration Time'); ?></th>
                             <th><?= lang('Paid amount'); ?></th>
                             <th><?= lang('Withdraw Condition amount'); ?></th>
                        </tr>
                    </thead>
                    <tfoot>
                        <tr>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th  class="text-primary"><span><?php echo lang('Total') ?></span></th>
                            <th class="text-right text-primary"><span id="cashback_amount_<?=$value?>">0.00</span></th>
                            <th class="text-right text-primary"><span id="betting_amount_<?=$value?>">0.00</span></th>
                            <th class="text-right text-primary"><span id="real_betting_amount_<?=$value?>">0.00</span></th>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th class="text-right text-primary"><span id="paid_amount_<?=$value?>">0.00</span></th>
                            <th class="text-right text-primary"><span id="withdraw_condition_amount_<?=$value?>">0.00</span></th>
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
            lengthMenu: JSON.parse('<?=json_encode($this->utils->getConfig('default_datatable_lengthMenu'))?>'),
            pageLength: <?=$this->utils->getDefaultItemsPerPage()?>,
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
               
                            $("#_export_excel_queue_form").attr('action', site_url('/export_data/export_super_cashback_report'));
                            $("#_export_excel_queue_form [name=json_search]").val(JSON.stringify(d));
                            $("#_export_excel_queue_form").submit();         
                    }
                }
                <?php } ?>
            ],
            columnDefs: [
               { visible: false, targets:[0] },
               // { className: 'text-right', targets: [4,5,12,13] },
               // { className:'text-center', targets: [ 0,6,7,11] },
               { className:'text-center', targets: [0] },
               { orderable: false, targets: [0]}
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
              
                $.post(base_url + "api/super_cashback_report", data, function(data) {
                    $('#cashback_amount_<?=$value?>').text(data.sub_summary.cashback_amount);
                    $('#betting_amount_<?=$value?>').text(data.sub_summary.betting_amount);
                    $('#real_betting_amount_<?=$value?>').text(data.sub_summary.original_betting_amount);
                    $('#paid_amount_<?=$value?>').text(data.sub_summary.paid_amount);
                    $('#withdraw_condition_amount_<?=$value?>').text(data.sub_summary.withdraw_condition_amount);
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

    $("#search_reg_date").change(function() {
        if($('#search_reg_date').is(':checked')) {
            $('#search_registration_date').prop('disabled',false);
            $('#registration_date_from').prop('disabled',false);
            $('#registration_date_to').prop('disabled',false);
        }else{
            $('#search_registration_date').prop('disabled',true);
            $('#registration_date_from').prop('disabled',true);
            $('#registration_date_to').prop('disabled',true);
        }
    }).trigger('change');

    $('body').on('submit', 'form#search-form', function(){
        // Detect Checkbox for non-checked to GET param.
        if($('#search_reg_date').prop('checked') == false){
            $('#search_reg_date').val('off');
            $('#search_reg_date').prop('checked', false);
        }else{
            $('#search_reg_date').val('on');
            $('#search_reg_date').prop('checked', true);
        }
        return true;
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
        $('#by_paid_flag').val("");
        $('#by_username').val("");
        $('#by_player_level').val("");
        $('#by_amount_greater_than').val("");
        $('#by_amount_less_than').val("");
        $('#agent_username').val("");
        $('#tag_list').val("");

        $(".currency-selectors").each(function(){
                if($(this).is(":not(:checked)")){
                    $(this).trigger('click');
                }
        });

        $("#search_cashback_date").val($("#by_date_from").val() +" to "+ $("#by_date_from").val());
        $('#by_date_from').val(dateToday);
        $('#by_date_to').val(dateToday);
        $("#enable_date").prop('checked', true);
    });

    $(".btn_payall").click(function() {
        // confirm
        if (confirm("<?php echo lang('Do you want pay all unpaid cashback?'); ?>")) {
            // $("#return_url").val(window.location.href);
            $("#search-form").attr("method", "POST")
                .attr("action", "<?php echo site_url('/vipsetting_management/pay_all_cashback'); ?>")
                .submit();
        }
    });

    $('#tag_list').multiselect({
        enableFiltering: true,
        enableCaseInsensitiveFiltering: true,
        includeSelectAllOption: true,
        selectAllJustVisible: false,
        buttonWidth: '100%',
        buttonClass: 'btn btn-sm btn-default',
        buttonText: function(options, select) {
            if (options.length === 0) {
                return '<?=lang('Select Tags');?>';
            } else {
                var labels = [];
                options.each(function() {
                    if ($(this).attr('label') !== undefined) {
                        labels.push($(this).attr('label'));
                    }
                    else {
                        labels.push($(this).html());
                    }
                });
                return labels.join(', ') + '';
            }
        }
    });
 });//document ready


</script>