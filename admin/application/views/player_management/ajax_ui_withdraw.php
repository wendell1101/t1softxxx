<div id="withdrawal_list_panel" data-file-info="ajax_ui_withdraw.php" data-datatable-selector="#withdraw-table">
    <div class="form-inline">
        <?php if ($this->permissions->checkPermissions('new_withdrawal')): ?>
            <a href="/payment_management/newWithdrawal" class="btn btn-scooter btn-sm btn-new_withdrawal" target="_blank">
                <i class="fa fa-plus"></i><span class="hidden-xs"><?=lang('lang.newWithdrawal') ?></span>
            </a>
        <?php endif; ?>

        <?php
            if(is_array($this->config->item('cryptocurrencies'))){
                $enabled_crypto = true;
            }else{
                $enabled_crypto = false;
            }

            if($this->config->item('enable_cpf_number')){
                $enable_cpf_number = true;
            }else{
                $enable_cpf_number = false;
            }
        ?>
        <select id="withdraw-type" class="form-control input-sm">
            <option value="allStatus" selected><?=lang("All") ?></option>
            <?php if ($this->permissions->checkPermissions('view_pending_stage')): ?>
                <option value="request"><?=lang('st.pending'); ?></option>
            <?php endif;?>
            <?php if ($this->utils->isEnabledFeature('enable_withdrawal_pending_review') && $this->permissions->checkPermissions('view_pending_review_stage')): ?>
                <option value="pending_review"><?=lang('st.pendingreview'); ?></option>
            <?php endif; ?>
            <?php if (!empty($pendingCustom) && $this->utils->getConfig('enable_pending_review_custom') && $this->permissions->checkPermissions('view_pending_custom_stage')): ?>
                <option value="pending_review_custom"><?=lang($pendingCustom); ?></option>
            <?php endif;?>
            <?php if (!empty($customStage) && is_array($customStage)): ?>
                <?php foreach ($customStage as $key => $value):?>
                    <?php if ($this->permissions->checkPermissions('view_withdraw_custom_stage_'.$key)): ?>
                        <option value="<?=lang($key); ?>"><?=lang($value); ?></option>
                    <?php endif;?>
                <?php endforeach; ?>
            <?php endif;?>
            <?php if ($this->permissions->checkPermissions('view_payment_processing_stage')): ?>
                <option value="payProc"><?=lang('st.processing'); ?></option>
            <?php endif;?>
            <option value="paid"><?=lang('st.paid'); ?></option>
            <option value="declined"><?=lang('st.declined'); ?></option>
            <?php if ($this->permissions->checkPermissions('view_locked_3rd_party_request')): ?>
                <option value="lock_api_unknown"><?=lang('st.lockedapirequest'); ?></option>
            <?php endif;?>
        </select>
        <input type="text" id="reportrange" class="form-control input-sm dateInput inline" data-start="#dateRangeValueStart" data-end="#dateRangeValueEnd" data-time="true" />
        <input type="hidden" id="dateRangeValueStart" name="dateRangeValueStart" />
        <input type="hidden" id="dateRangeValueEnd" name="dateRangeValueEnd" />
        <input type="button" class="btn btn-portage btn-sm" id="btn-submit" value="<?=lang('lang.search'); ?>" />
    </div>
    <hr />
    <div class="clearfix">
        <table id="withdraw-table" class="table table-bordered">
            <thead>
                <tr>
                    <th><?=lang('lang.status') ?></th>
                    <th><?=lang("Withdraw Code") ?></th>
                    <th><?=lang('Locked Status') ?></th>
                    <th><?=lang('Risk Check Status')?></th>
                    <th><?=lang("pay.username") ?></th>
                    <?php if(!empty($this->utils->getConfig('enable_crypto_details_in_crypto_bank_account'))) : ?>
                        <th><?=lang("financial_account.cryptousername.list")?></th>
                        <th><?=lang("financial_account.cryptoemail.list")?></th>
                    <?php endif; ?>
                    <?php if ($this->utils->getConfig('enable_split_player_username_and_affiliate')) { ?>
                        <th><?=lang("Affiliate")?></th> <!-- 4 -->
                    <?php } ?>
                    <th id="default_sort_reqtime" style="min-width:45px;"><?=lang("pay.reqtime") ?></th>
                    <th style="min-width:45px;"><?=lang("pay.proctime") ?></th>
                    <?php if($this->utils->getConfig('enable_processed_on_custom_stage_time')) : ?>
                    <th style="min-width:45px;"><?=lang("pay.procstagetmie")?></th>
                    <?php endif; ?>
                    <th style="min-width:45px;"><?=lang("pay.paidtime") ?></th>
                    <th style="min-width:45px;"><?=lang("pay.spenttime") ?></th>
                    <th><?=lang("pay.realname") ?></th>
                    <th><?=lang('pay.playerlev') ?></th>
                    <th><?=lang("Tag") ?></th>
                    <th><?=lang('pay.withamt') ?></th>
                    <?php if($this->utils->getConfig('enable_withdrawl_fee_from_player')) :?>
                            <th><?=lang('transaction.transaction.type.43')?></th>
                        <?php endif;?>
                    <?php if($enabled_crypto) :?>
                        <th><?=lang('Transfered crypto')?></th>
                    <?php endif;?>
                    <th><?=lang('pay.bankname') ?></th>
                    <th><?=lang('pay.acctname') ?></th>
                    <th><?=lang('pay.acctnumber') ?></th>
                    <?php if($enable_cpf_number) :?>
                        <th><?=lang('financial_account.CPF_number');?></th>
                    <?php endif; ?>
                    <th><?=lang('pay.payment_account_flag') ?></th>
                    <th><?= $this->utils->getConfig('use_branch_as_ifsc_in_withdrawal_accounts') ? lang('financial_account.ifsc') : lang('pay.acctbranch') ?></th>
                    <th><?=lang('Withdrawal Declined Category') ?></th>
                    <th><?=lang('Province') ?></th>
                    <th><?=lang('City') ?></th>
                    <th><?=lang('pay.withip') ?></th>
                    <?php if( empty( $this->utils->getConfig('hide_iptaglist') ) ) :?>
                        <th data-field_id="ip_tags"><?=lang('Ip Tags')?></th> <!-- #30.1 Ip Tags -->
                    <?php endif;?>
                    <?php if($this->utils->getConfig('enable_total_player_withdrawal_requests')) :?>
                        <th><?=lang('pay.countPlayerWithdrawalRequests')?></th>
                    <?php endif;?>
                    <?php if($this->utils->getConfig('enable_total_ip_withdrawal_requests')) :?>
                        <th><?=lang('pay.countIpWithdrawalRequests')?></th>
                    <?php endif;?>
                    <th><?=lang('pay.withlocation') ?></th>
                    <th><?=lang('pay.procssby') ?></th>
                    <th><?=lang('pay.updatedon') ?></th>
                    <th><?=lang("pay.withdrawalId") ?></th>
                    <th style="min-width:400px;"><?=lang('External Note'); ?></th>
                    <th style="min-width:400px;"><?=lang('Internal Note'); ?></th>
                    <th style="min-width:600px;"><?=lang('Action Log'); ?></th>
                    <th style="min-width:300px;"><?=lang('pay.timelog') ?></th>
                    <th><?=lang('pay.curr') ?></th>
                    <th><?=lang('sys.ga.systemcode') ?></th>
                    <th><?=lang('lang.withdrawal_payment_api') ?></th>
                    <th><?=lang('Paybus ID')?></th>
                    <th><?=lang('External ID')?></th>
                </tr>
            </thead>
        </table>
    </div>
</div>

<script type="text/javascript">
    function withdrawHistory() {
        <?php if (!empty($this->utils->getConfig('withdrawal_list_columnDefs'))): ?>
            <?php if (!empty($this->utils->getConfig('withdrawal_list_columnDefs')['not_visible_player_information'])): ?>
                var visible_target = JSON.parse("<?= json_encode($this->utils->getConfig('withdrawal_list_columnDefs')['not_visible_player_information'], true) ?>");
            <?php else : ?>
                var visible_target = [2, 3, 8, 9, 10, 18, 19, 20, 21, 24, 29, 30, 31];
            <?php endif; ?>
            <?php if (!empty($this->utils->getConfig('withdrawal_list_columnDefs')['className_text-right_player_information'])): ?>
                var text_right = JSON.parse("<?= json_encode($this->utils->getConfig('withdrawal_list_columnDefs')['className_text-right_player_information'], true) ?>");
            <?php else : ?>
                var text_right = [7, 11, 28];
            <?php endif; ?>
        <?php else : ?>
            var visible_target = [6, 10, 11, 13, 14, 18, 21, 23, 24];
            var text_right = [5];
        <?php endif; ?>

        var amtColSummary = 0,
            totalPerPage = 0,
            desc = $("#default_sort_reqtime").index();

        var dataTable = $('#withdraw-table').DataTable({
            dom: "<'row'<'col-md-12'<'pull-right'B><'pull-right progress-container'>l<'dt-information-summary2 text-info pull-left' i>>><'table-responsive't><'row'<'col-md-12'<'pull-right'p><'dt-information-summary2 text-info pull-left' i>>>",
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
                    postfixButtons: ['colvisRestore'],
                    className: ['btn-linkwater']
                }
            ],
            columnDefs: [
                {
                    sortable: false,
                    targets: [0]
                },
                {
                    visible: false,
                    targets: visible_target
                },
                {
                    className: 'text-right',
                    targets: text_right
                }
            ],
            order: [
                [desc, 'desc']
            ],
            processing: true,
            serverSide: true,
            ajax: function(data, callback, settings) {
                data.extra_search = [
                    {
                        'name': 'withdrawal_date_from',
                        'value': $('#withdrawal_list_panel #dateRangeValueStart').val(),
                    },
                    {
                        'name': 'withdrawal_date_to',
                        'value': $('#withdrawal_list_panel #dateRangeValueEnd').val(),
                    },
                    {
                        'name': 'dwStatus',
                        'value': $('#withdrawal_list_panel #withdraw-type').val(),
                    },
                    {
                        'name': 'enable_date',
                        'value': true,
                    },
                ];

                $.post('/api/withdrawList/' + playerId + '/false', data, function(data) {
                    callback(data);
                }, 'json');
            },
        });

        $('#changeable_table #btn-submit').click(function() {
            dataTable.ajax.reload();
        });

        ATTACH_DATATABLE_BAR_LOADER.init('withdraw-table');
    }

    function showDetialNotes(walletAccountId, noteType) {
        $.ajax({
            url: '/payment_management/getWithdrawalDetialNotes/' + walletAccountId + '/' + noteType,
            type: 'POST',
            dataType: 'json',
            success: function(data) {
                if (data.success) {
                    var title   = 'NO.' + data.transactionCode,
                        content = '<div>' + data.noteSubTitle + '</div><br>' +
                                  '<textarea class="form-control" rows="15" readonly style="resize: none;">' + data.formatNotes.trim() + '</textarea>',
                        button  = '<center><button class="btn btn-sm btn-scooter" data-dismiss="modal" aria-label="Close"><?=lang('Close')?></button></center>';

                    confirm_modal(title, content, button);
                } else {
                    alert('<?=lang("Something is wrong, show notes detail failed"); ?>');
                }
            },
        });
    }
</script>
