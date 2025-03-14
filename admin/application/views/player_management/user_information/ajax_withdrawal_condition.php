<div role="tabpanel" class="tab-pane active" id="withdrawalCondition">
    <div class="table-responsive" id="withrawal_panel_body">
        <ul class="nav nav-tabs">
            <li class="active condition-table-tab"><a data-toggle="tab" href="#withdrawal-condition-table-tab"><?=lang('Wagering Requirements')?></a></li>
            <li class="condition-table-tab"><a data-toggle="tab" href="#deposit-condition-table-tab"><?=lang('Minimum Deposit Requirements')?></a>
        </ul>

        <div class="tab-content">
            <div id="withdrawal-condition-table-tab" class="tab-pane panel panel-default fade in active">
                <div class="panel-body">
                    <?php if ($this->permissions->checkPermissions('cancel_member_withdraw_condition')): ?>
                        <button type="button" title="<?=lang('sys.ga.conf.cancel.selected')?>" id="cancel-wc-items"  class="btn btn-danger btn-sm">
                            <i class="glyphicon glyphicon-remove-circle" style="color:white;" data-placement="bottom"></i>
                            <?=lang('lang.cancel');?>
                        </button>
                    <?php endif; ?>
                    <table class="table table-hover table-bordered table-condensed" id="withdrawal-condition-table">
                        <thead>
                            <tr>
                                <th><?=lang('sys.pay.action');?></th>
                                <th><?=lang('pay.transactionType')?></th>
                                <th><?=lang('Sub-wallet')?></th>
                                <th><?=lang('pay.promoName')?></th>
                                <th><?=lang('cms.promocode')?></th>
                                <th><?=lang('cashier.53')?></th>
                                <th><?=lang('Bonus')?></th>
                                <th><?=lang('pay.startedAt')?></th>
                                <th><?=lang('pay.withdrawalAmountCondition')?></th>
                                <th><?=lang('Note')?></th>
                                <?php if ($enabled_show_withdraw_condition_detail_betting): ?>
                                    <th><?=lang('Betting Amount')?></th>
                                    <th><?=lang('lang.status')?></th>
                                <?php endif;?>
                                <?php if ($use_accumulate_deduction_when_calculate_cashback): ?>
                                    <th><?=lang('Cashback deduct betting amount')?></th>
                                <?php endif;?>
                            </tr>
                        </thead>
                    </table>

                    <div id="withdraw_condition_actions" class="col-md-2">
                        <button type="button" class="btn btn-scooter btn-xs" id="check_withdraw_condition"><?=lang('Manually Check'); ?></button>
                    </div>
                    <script>
                        $(function (){
                            $("#check_withdraw_condition").click(function(e){
                                if(confirm("<?=lang('Do you want check withdraw condition? it will take a while.'); ?>")){
                                    //to check withdraw condition
                                    window.location.href="/player_management/check_withdraw_condition/<?=$player['playerId'];?>";
                                }
                                e.preventDefault();
                            });
                        });
                    </script>
                    <div id="summary-condition-container" class="col-md-3"></div>

                    <div style="margin:10px;height:30px;">
                        <a href="#" id="refresh-withdrawal-condition" data-toggle="tooltip" title="<?=lang('lang.refresh')?>" class="btn btn-sm btn-default " >
                            <i class="glyphicon glyphicon-refresh"></i>
                        </a>
                        <img id="withdawal-condition-loader"src="<?=$this->utils->imageUrl('ajax-loader.gif')?>" />
                    </div>
                </div>
            </div>

            <div id="deposit-condition-table-tab" class="tab-pane panel panel-default fade">
                <div class="panel-body">
                    <?php if ($this->permissions->checkPermissions('cancel_member_withdraw_condition')) {?>
                        <button type="button" value="" title="<?=lang('sys.ga.conf.cancel.selected')?> " id="cancel-dc-items"  class="btn btn-danger btn-sm">
                            <i class="glyphicon glyphicon-remove-circle" style="color:white;"  data-placement="bottom" ></i>
                            <?=lang('lang.cancel');?>
                        </button>
                    <?php }
                    ?>
                    <table class="table table-hover table-bordered table-condensed" id="deposit-condition-table">
                        <thead>
                            <tr>
                                <th><?=lang('sys.pay.action');?></th>
                                <th><?=lang('pay.transactionType')?></th>
                                <th><?=lang('Sub-wallet')?></th>
                                <th><?=lang('pay.promoName')?></th>
                                <th><?=lang('cms.promocode')?></th>
                                <th><?=lang('cashier.53')?></th>
                                <th><?=lang('Bonus')?></th>
                                <th><?=lang('pay.startedAt')?></th>
                                <th><?=lang('pay.mindepamt').' '.lang('Conditions')?></th>
                                <th><?=lang('Note')?></th>
                                <?php if ($enabled_show_withdraw_condition_detail_betting): ?>
                                    <th><?=lang('Betting Amount')?></th>
                                    <th><?=lang('lang.status')?></th>
                                <?php endif;?>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>


<script type="text/javascript">
    var gameSystemMap = <?=$this->utils->encodeJson($this->utils->getGameSystemMap()); ?>;
    var forCancelIds = Array();

    function showConfirmation(){
        $('#conf-modal').modal('show');
        var items = (forCancelIds.length > 1) ? "<?=lang('sys.dasItems')?>" : "<?=lang('sys.dasItem')?>";
        $('#conf-msg-ask').html("<?=lang('sys.ga.conf.cancel')?> "+forCancelIds.length+" "+items+" ?");
        $('#conf-msg-reason').html("<?=lang('pay.reason')?>");
        return;
    }

    function cancelWithdrawalCondition(){
        if($('#reason-to-cancel').val() != ''){
            $('#conf-yes-action').attr("disabled", true);
            var data = {
                forCancelIds  : forCancelIds,
                reasonToCancel : $('#reason-to-cancel').val(),
                playerId :  playerId,
                cancelManualStatus : "<?= Withdraw_condition::DETAIL_STATUS_CANCELLED_MANUALLY ?>"
            };
            $.ajax({
                url : '/player_management/cancelWithdrawalCondition',
                type : 'POST',
                data : data,
                dataType : "json"
            }).done(function (data) {
                if (data.status == "success") {
                    $('#conf-yes-action').attr("disabled", false);
                    forCancelIds = Array();
                    $('#reason-to-cancel').next('div.help-block').html('');
                    $('#reason-to-cancel').val('');
                    $('#conf-modal').modal('hide');
                    WITHDRAWAL_CONDITION.refresh();
                }
            }).fail(function (jqXHR, textStatus) {
                $('#conf-yes-action').attr("disabled", false);
                throw textStatus;
            });
        }else{
            $('#reason-to-cancel').next('div.help-block').html('<?=lang("pay.reason")?><?=lang("is_required")?>');
        }
    }

    function getDepositCondtion(promoName,depositCondition,bonusReleaseRule,withdrawRequirement){
        if(promoName){
            var row = "<?=lang('cms.depCon')?> <br/>";
            row += depositCondition+"<br/>";
            row += "(<?=lang('cms.bonus')?>)<br/>"
            row += bonusReleaseRule+"<br/>";
            row += "(<?=lang('promo.betCondition')?>)<br/>";
            row += withdrawRequirement+"<br/>";
            return row;
        }else{
            var row = "<i><?=lang('pay.noPromo')?></i>";
            return row;
        }
    }

    var withdrawalCondTable = $('#withdrawal-condition-table').DataTable({
        searching: true,
        autoWidth: false,
        dom:"<'panel-body'<'pull-right'f><'pull-right progress-container'>l><'dt-information-summary1 text-info pull-left' i>t<'panel-body'<'pull-right'p><'dt-information-summary2 text-info pull-left' i>>",
        <?php if($this->utils->isEnabledFeature('column_visibility_report')){ ?>
            stateSave: true,
        <?php } else { ?>
            stateSave: false,
        <?php } ?>

        buttons: [
            {
                extend: 'colvis',
                postfixButtons: [ 'colvisRestore' ],
                className: ['btn-linkwater']
            }
        ],
        columnDefs: [
            { sortable: false, targets: [0] },
            <?php if (!$this->permissions->checkPermissions('cancel_member_withdraw_condition')) {?>
                { "targets": [ 0 ], className: "noVis hidden" },
            <?php } ?>
        ],
        order: [[7, 'desc']]
    }).draw(false);

    var depositCondTable = $('#deposit-condition-table').DataTable({
        searching: true,
        autoWidth: false,
        dom:"<'panel-body'<'pull-right'f><'pull-right progress-container'>l><'dt-information-summary1 text-info pull-left' i>t<'panel-body'<'pull-right'p><'dt-information-summary2 text-info pull-left' i>>",
        <?php if($this->utils->isEnabledFeature('column_visibility_report')){ ?>
            stateSave: true,
        <?php } else { ?>
            stateSave: false,
        <?php } ?>
        buttons: [
            {
                extend: 'colvis',
                postfixButtons: [ 'colvisRestore' ],
                className: ['btn-linkwater']
            }
        ],
        columnDefs: [
            { sortable: false, targets: [0] },
            <?php if (!$this->permissions->checkPermissions('cancel_member_withdraw_condition')): ?>
                { "targets": [ 0 ], className: "noVis hidden" },
            <?php endif; ?>
        ],
        order: [[7, 'desc']]
    }).draw(false);

    var repeatedLoad        = false;
    var withdrawCondLoader  = $("#withdawal-condition-loader");
    var summaryWithCondCont = $("#summary-condition-container");
    var refreshWithConBtn   = $("#refresh-withdrawal-condition");
    var hasRows             = false;

    var forCancelIds = Array(),reasonToCancel = '';

    function getWithdrawalCondition(){
        if(repeatedLoad){
            if(hasRows){
                withdrawCondLoader.show();
            }
        }

        $.ajax({
            url : '/player_management/getWithdrawalCondition/' + playerId,
            type : 'GET',
            dataType : "json"
        }).done(function (obj) {
            var arr = obj.withdrawalCondition;
            var totalPlayerBet = obj.totalPlayerBet;
            var totalRequiredBet = obj.totalRequiredBet;

            if(arr){
                hasRows = true;
                refreshWithConBtn.show();

                /*Clear the table rows first to prevent appending rows when refresh*/
                withdrawalCondTable.clear().draw();
                depositCondTable.clear().draw();

                for (var i = 0; i < arr.length; i++) {
                    var transactions,
                        promoCode,
                        depositCondition,
                        nonfixedDepositAmtCondition,
                        bonusReleaseRule,
                        currentBet,
                        withdrawRequirement,
                        unfinished_status,
                        obj=arr[i],
                        cashback_deduted_flag;

                    currentBet = (obj.currentBet != null && Number(obj.currentBet)) ? Number(obj.currentBet) : 0;

                    transactions = "<?=lang('lang.norecyet')?>";
                    var promoName = obj.promoName || obj.promoTypeName;

                    if(obj.source_type == '<?=Withdraw_condition::SOURCE_DEPOSIT?>'){
                        transactions = "<?=lang('withdraw_conditions.source_type.' . Withdraw_condition::SOURCE_DEPOSIT)?>";
                    }else if(obj.source_type == '<?=Withdraw_condition::SOURCE_BONUS?>'){
                        transactions = "<?=lang('withdraw_conditions.source_type.' . Withdraw_condition::SOURCE_BONUS)?>";
                    }else if(obj.source_type == '<?=Withdraw_condition::SOURCE_CASHBACK?>'){
                        transactions = "<?=lang('withdraw_conditions.source_type.' . Withdraw_condition::SOURCE_CASHBACK)?>";
                    }else if(obj.source_type == '<?=Withdraw_condition::SOURCE_NON_DEPOSIT?>'){
                        transactions = "<?=lang('Non-deposit')?>";
                    }

                    cashback_deduted_flag = "<?=lang('N/A')?>";
                    if(!!obj.disable_cashback_if_not_finish_withdraw_condition){
                        if(obj.cashback_deduted_flag == '<?=Withdraw_condition::NOT_DEDUCTED_FROM_CALC_CASHBACK?>'){
                            cashback_deduted_flag = "<?=lang('Not Deduct')?>";
                        }else if(obj.cashback_deduted_flag == '<?=Withdraw_condition::IS_DEDUCTED_FROM_CALC_CASHBACK?>'){
                            cashback_deduted_flag = "<?=lang('Is Deduct')?>";
                        }else if(obj.cashback_deduted_flag == '<?=Withdraw_condition::IS_ACCUMULATING_DEDUCTION_OF_WC_FROM_CALCULATE_CASHBACK?>'){
                            cashback_deduted_flag = "<?=lang('Accumulating Decution')?>";
                        }
                    }

                    promoName = promoName || "<?=lang('pay.noPromo')?>";

                    promoCode=(obj.promoCode) ? obj.promoCode : "<i><?=lang('pay.noPromo')?></i>";
                    promoCode=(obj.promoCode) ? obj.promoCode : "<i><?=lang('pay.noPromo')?></i>";

                    var wallet_name=gameSystemMap[obj.wallet_type];
                    if(!wallet_name){
                        wallet_name='';
                    }

                    var bonusAmount = 0;
                    var conditionAmount = 0;
                    var deposit_min_limit = 0;

                    if(obj.withdraw_condition_type == '<?=Withdraw_condition::WITHDRAW_CONDITION_TYPE_BETTING?>'){
                        unfinished_status =( numeral(obj.is_finished).format() < 1 ) ? "<?=lang('player.ub13')?>" : "<?=lang('player.ub14')?>";
                        conditionAmount = (obj.conditionAmount) ? obj.conditionAmount : 0;
                        bonusAmount = (numeral(obj.trigger_amount).format() == 0.0 ? numeral(obj.bonusAmount).format() : numeral(obj.trigger_amount).format());
                        <?php if ($enabled_show_withdraw_condition_detail_betting) {?>
                            var withdraw_condition_row=[
                                '<input type="checkbox" class="withdraw-cancel-checkbox" title="<?=lang("sys.ga.conf.select.to.cancel")?>" value="'+obj.withdrawConditionId+'" > &nbsp;&nbsp;<i class="fa fa-times-circle withdraw-cancel-icon" style="color:#D32A0E;cursor:pointer;" title="<?=lang("sys.ga.conf.cancel.this")?>"  value="'+obj.withdrawConditionId+'" data-placement="bottom" ></i>',
                                transactions,
                                wallet_name,
                                obj['promoBtn'],
                                promoCode,
                                numeral(obj.walletDepositAmount).format(),
                                bonusAmount,
                                obj.started_at,
                                numeral(conditionAmount).format(),
                                (!obj.note) ? obj.pp_note : obj.note,
                                numeral(currentBet).format(),
                                unfinished_status
                            ];
                        <?php } else {?>
                            var withdraw_condition_row=[
                                '<input type="checkbox" class="withdraw-cancel-checkbox" title="<?=lang("sys.ga.conf.select.to.cancel")?>" value="'+obj.withdrawConditionId+'" > &nbsp;&nbsp;<i class="fa fa-times-circle withdraw-cancel-icon" style="color:#D32A0E;cursor:pointer;" title="<?=lang("sys.ga.conf.cancel.this")?>"  value="'+obj.withdrawConditionId+'" data-placement="bottom" ></i>',
                                transactions,
                                wallet_name,
                                obj['promoBtn'],
                                promoCode,
                                numeral(obj.walletDepositAmount).format(),
                                bonusAmount,
                                obj.started_at,
                                numeral(conditionAmount).format(),
                                (!obj.note) ? obj.pp_note : obj.note
                            ];
                        <?php }?>

                        <?php if($enabled_show_withdraw_condition_detail_betting):?>
                            withdraw_condition_row.push(cashback_deduted_flag);
                        <?php endif;?>

                        withdrawalCondTable.row.add( withdraw_condition_row ).draw( false );
                    }

                    if(obj.withdraw_condition_type == '<?=Withdraw_condition::WITHDRAW_CONDITION_TYPE_DEPOSIT?>'){
                        unfinished_status =( numeral(obj.is_finished_deposit).format() > 0 &&  (numeral(obj.currentDeposit).format() >= numeral(obj.conditionDepositAmount).format()) ) ? "<?=lang('player.ub14')?>" : "<?=lang('player.ub13')?>";
                        deposit_min_limit = (obj.conditionDepositAmount) ? obj.conditionDepositAmount : 0;
                        bonusAmount = (numeral(obj.trigger_amount).format() == 0.0 ? numeral(obj.bonusAmount).format() : numeral(obj.trigger_amount).format());
                        <?php if ($enabled_show_withdraw_condition_detail_betting) {?>
                            var deposit_condition_row=[
                                '<input type="checkbox" class="deposit-cancel-checkbox" title="<?=lang("sys.ga.conf.select.to.cancel")?>" value="'+obj.withdrawConditionId+'" > &nbsp;&nbsp;<i class="fa fa-times-circle deposit-cancel-icon" style="color:#D32A0E;cursor:pointer;" title="<?=lang("sys.ga.conf.cancel.this")?>"  value="'+obj.withdrawConditionId+'" data-placement="bottom" ></i>',
                                transactions,
                                wallet_name,
                                obj['promoBtn'],
                                promoCode,
                                numeral(obj.walletDepositAmount).format(),
                                bonusAmount,
                                obj.started_at,
                                numeral(deposit_min_limit).format(),
                                (!obj.note) ? obj.pp_note : obj.note,
                                numeral(currentBet).format(),
                                unfinished_status
                            ];
                        <?php } else {?>
                            var deposit_condition_row=[
                                '<input type="checkbox" class="deposit-cancel-checkbox" title="<?=lang("sys.ga.conf.select.to.cancel")?>" value="'+obj.withdrawConditionId+'" > &nbsp;&nbsp;<i class="fa fa-times-circle deposit-cancel-icon" style="color:#D32A0E;cursor:pointer;" title="<?=lang("sys.ga.conf.cancel.this")?>" value="'+obj.withdrawConditionId+'" data-placement="bottom" ></i>',
                                transactions,
                                wallet_name,
                                obj['promoBtn'],
                                promoCode,
                                numeral(obj.walletDepositAmount).format(),
                                bonusAmount,
                                obj.started_at,
                                numeral(deposit_min_limit).format(),
                                (!obj.note) ? obj.pp_note : obj.note
                            ];
                        <?php }?>
                        depositCondTable.row.add( deposit_condition_row ).draw( false );
                    }
                }//loop end

                var un_finished = parseFloat(totalRequiredBet) - parseFloat(totalPlayerBet),
                    summary     = "<table class='table table-hover table-bordered'>";

                if(un_finished < 0) un_finished = 0;

                summary += "<tr><th class='active col-md-8'><b><?=lang('pay.totalRequiredBet')?>:</b></th><td align='right'>"+totalRequiredBet.format(2)+"</td></tr>";
                summary += "<tr><th class='active col-md-8'><b><?=lang('pay.currTotalBet')?>:</b></th><td align='right'> "+totalPlayerBet.format(2)+"</td></tr>";
                summary += "<tr><th class='active col-md-8'><b><?=lang('mark.unfinished')?>:</b></th><td align='right'> "+un_finished.format(2)+" </td><tr>";
                summary += "</table>";

                summaryWithCondCont.html(summary);


            }else{
                withdrawalCondTable.clear().draw();
                depositCondTable.clear().draw();
                refreshWithConBtn.hide();
            }
            resetCancelSelect();
            withdrawCondLoader.hide();
            repeatedLoad = true;
        }).fail(function (jqXHR, textStatus) {
            if(jqXHR.status<300 || jqXHR.status>500){
                alert(textStatus);
            }
        });
    }

    function attachEventsListener(){

        $('#conf-modal #conf-yes-action').off('click').on('click', function(){
            WITHDRAWAL_CONDITION.cancel();
        });


        $('#withdrawal-condition-table-tab').on('click', '#refresh-withdrawal-condition', function(){
            WITHDRAWAL_CONDITION.refresh();
            return false;
        });

        //For paging use delegate
        $('#withdrawal-condition-table').delegate(".withdraw-cancel-checkbox", "click", function(){

            var id =$(this).val();
            if($(this).prop('checked')){
                forCancelIds.push(id);
            }else{
                var i =  forCancelIds.indexOf(id);
                forCancelIds.splice(i, 1);
            }
        });

        $('#withdrawal-condition-table').delegate(".withdraw-cancel-icon", "click", function(){
            var id =$(this).attr('value');
            if(jQuery.inArray(id, forCancelIds) == -1){
                forCancelIds.push(id);
            }
            $(this).prev('input:checkbox').prop('checked', true);
            showConfirmation();
        });

        //tooltips
        $('#withdrawal-condition-table').delegate(".withdraw-cancel-checkbox", "mouseover", function(){
            $('.withdraw-cancel-checkbox').tooltip({placement : "right"});
        });

        $('#withdrawal-condition-table').delegate("td", "mouseover", function(){
            $('.withdraw-cancel-checkbox').tooltip({placement : "right"});
            $('.withdraw-cancel-icon').tooltip({placement : "right"});
        });

        //For paging use delegate
        $('#deposit-condition-table').delegate(".deposit-cancel-checkbox", "click", function(){
            var id =$(this).val();
            if($(this).prop('checked')){
                forCancelIds.push(id);
            }else{
                var i =  forCancelIds.indexOf(id);
                forCancelIds.splice(i, 1);
            }
        });

        $('#deposit-condition-table').delegate(".deposit-cancel-icon", "click", function(){
            var id =$(this).attr('value');
            if(jQuery.inArray(id, forCancelIds) == -1){
                forCancelIds.push(id);
            }
            $(this).prev('input:checkbox').prop('checked', true);
            showConfirmation();
        });

        //tooltips
        $('#deposit-condition-table').delegate(".deposit-cancel-checkbox", "mouseover", function(){
            $('.withdraw-cancel-checkbox').tooltip({placement : "right"});
        });

        $('#deposit-condition-table').delegate("td", "mouseover", function(){
            $('.withdraw-cancel-checkbox').tooltip({placement : "right"});
            $('.withdraw-cancel-icon').tooltip({placement : "right"});
        });


        $('#cancel-wc-items, #cancel-dc-items').tooltip({placement : "top"});
        $('#cancel-wc-items, #cancel-dc-items').click(function(){
            if(!forCancelIds.length){
                alert("<?=lang('cancel_deposit')?>");
                return;
            }
            showConfirmation();
        });

        $('#conf-cancel-action').click(function(){
            $('#reason-to-cancel').next('div.help-block').html('');
        });
    } // EOF attachEventsListener

    function resetCancelSelect(){
        forCancelIds = Array();
        $('input[class*="-cancel-checkbox"]').prop("checked", false);
    }

    $('.nav-tabs > .condition-table-tab').click(function(){
        resetCancelSelect();
    });

    var WITHDRAWAL_CONDITION = WITHDRAWAL_CONDITION||{
        refresh:function() {
            getWithdrawalCondition();
        },
        cancel:function(){
            cancelWithdrawalCondition();
        }
    };


    $(document).ready(function(){

        /**
        * Number.prototype.format(n, x)
        *
        * @param integer n: length of decimal
        * @param integer x: length of sections
        */
        Number.prototype.format = function(n, x) {
            var re = '\\d(?=(\\d{' + (x || 3) + '})+' + (n > 0 ? '\\.' : '$') + ')';
            return this.toFixed(Math.max(0, ~~n)).replace(new RegExp(re, 'g'), '$&,');
        };


        attachEventsListener();

        (function() {WITHDRAWAL_CONDITION})();


        WITHDRAWAL_CONDITION.refresh();
    });//END READY
</script>
