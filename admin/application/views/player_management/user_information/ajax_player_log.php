<div role="tabpanel" class="tab-pane active" id="playersLog">
    <div class="form-inline">
        <input type="hidden" value="<?=$player['playerId']?>" id="player_id">
        <input type="hidden" value="<?=$player['username']?>" id="username">
        <select id="player_info_dropdown" class="form-control input-sm">
            <?php if ($this->utils->isEnabledFeature('deposit_withdraw_transfer_list_on_player_info')): ?>
                <option value="depositHistory" data-load="/player_management/depositHistory/" data-callback="depositHistory" data-dt-selector="#deposit-table" data-params='{"player_id":<?=$player['playerId']?>}'><?=lang('Deposit List'); ?></option>
                <option value="withdrawHistory" data-load="/player_management/withdrawHistory/" data-callback="withdrawHistory" data-dt-selector="#withdraw-table" data-params='{"player_id":<?=$player['playerId']?>}'><?=lang('Withdraw List'); ?></option>
                <?php if(!$this->CI->utils->getConfig('seamless_main_wallet_reference_enabled') || $this->utils->getConfig('still_enabled_transfer_list_on_seamless_wallet')): ?>
                <option value="transferHistory" data-load="/player_management/transferHistory/<?=$player['playerId']?>" data-callback="transferHistory" data-dt-selector="#transfer-table" data-params='{"player_id":<?=$player['playerId']?>}'><?=lang('Transfer List'); ?></option>
                <?php endif ?>
            <?php endif ?>
            <option value="balanceHistory" data-load="/player_management/balance_history/" data-callback="balance_history" data-params='{"player_id":<?=$player['playerId']?>}'><?=lang('Balance History'); ?></option>
            <?php if (!$this->utils->isEnabledFeature('hide_old_adjustment_history')): ?>
                <option value="adjustmentHistory" data-load="/player_management/adjustment_history_tab/" data-callback="adjustmentHistory" data-dt-selector="#adjustment_history" data-params='{"player_id":<?=$player['playerId']?>}'><?=lang('Old Adjustment History')?></option>
            <?php endif ?>
            <option value="adjustmentHistory2" data-load="/player_management/adjustment_history_tab_v2/" data-callback="adjustmentHistoryV2" data-dt-selector="#adjustment_history_tab" data-params='{"player_id":<?=$player['playerId']?>}'><?=lang('pay.adjustHistory')?></option>
            <?php if ($this->permissions->checkPermissions('transaction_report')): ?>
                <option value="transactionHistory" data-load="/player_management/transactionHistory/" data-callback="transactionHistory" data-params='{"player_id":<?=$player['playerId']?>}' data-dt-selector="#transaction-table"><?=lang('pay.transact')?></option>
            <?php endif ?>
            <?php if ($this->permissions->checkPermissions('balance_transaction_report')): ?>
                <option value="balanceTransactionHistory" data-load="/player_management/balanceTransactionHistory/" data-callback="balanceTransactionHistory" data-params='{"player_id":<?=$player['playerId']?>}' data-dt-selector="#transaction-table"><?=lang('pay.balance_transactions_abr')?></option>
            <?php endif ?>
            <?php if ($this->permissions->checkPermissions('view_player_update_history')): ?>
                <option value="personalHistory" data-load="/player_management/personalHistory/<?=$player['playerId']?>" data-callback="personalHistory" data-params='{"player_id":<?=$player['playerId']?>}' ><?=lang('member.playerUpdateHistory')?></option>
            <?php endif ?>
            <option value="bankHistory" data-load="/player_management/bankHistory/" data-callback="bankHistory" data-params='{"player_id":<?=$player['playerId']?>}' data-dt-selector="#bank-table" ><?=lang('player.ui41')?></option>
            <option value="promoStatus" data-load="/player_management/promoStatus/" data-callback="promoStatus" data-params='{"player_id":<?=$player['playerId']?>}' data-dt-selector="#promo-table"><?=lang('player.ui49')?></option>
            <?php if ($this->permissions->checkPermissions('gamelogs')): ?>
                <option value="gamesHistory" data-load="/player_management/gamesHistory" data-callback="gamesHistory" data-params='{"player_id":<?=$player['playerId']?>}' data-dt-selector="#gamehistory-table"><?=lang('player.ui48')?></option>
                <option value="unsettlegamesHistory" data-load="/player_management/gamesHistory/2" data-callback="gamesHistory" data-params='{"player_id":<?=$player['playerId']?>}' data-dt-selector="#gamehistory-table"><?=lang('Unsettle Game History'); ?></option>
            <?php endif; ?>
            <option value="friendReferralStatus" data-load="/player_management/friendReferralStatus/" data-callback="friendReferralStatus" data-params='{"player_id":<?=$player['playerId']?>}' data-dt-selector="#friendreferral-table"><?=lang('player.ui46')?></option>
            <option value="chatHistory" data-load="/player_management/chatHistory/" data-callback="chatHistory" data-dt-selector="#chatHistory-table" data-params='{"player_id":<?=$player['playerId']?>}'><?=lang('cs.messagehistory')?></option>
            <option value="ipHistory" data-load="/player_management/ipHistory/" data-callback="ipHistory" data-dt-selector="#ip-table" data-params='{"player_id":<?=$player['playerId']?>}'><?=lang('player.ui75')?></option>
            <option value="dupAccounts" data-load="/player_management/dupAccounts/" data-callback="dupAccounts" data-params='{"player_id":<?=$player['playerId']?>}' data-dt-selector="#dup-table"><?=lang('pay.duplicateAccountList')?></option>
            <?php if ($this->utils->isEnabledFeature('linked_account') && $this->permissions->checkPermissions('linked_account')): ?>
                <option value="linkedAccount" data-load="/player_management/linked_account/<?=$player['playerId']?>" data-callback="linkedAccount" data-params='{"player_id":<?=$player['playerId']?>}'><?=lang('Linked Account')?></option>
            <?php endif ?>
            <option value="cancelledWithdrawalCondition" data-load="/player_management/cancelled_withdrawal/" data-callback="cancelledWithdrawalCondition" data-params='{"player_id":<?=$player['playerId']?>}' data-dt-selector="#cancelledwithdrawal-table"><?=lang('Withdrawal Condition History')?></option>
            <?php if($this->utils->isEnabledFeature('enabled_transfer_condition')):?>
                <option value="cancelledTransferCondition" data-load="/player_management/cancelledTransferCondition/" data-callback="cancelledTransferCondition" data-params='{"player_id":<?=$player['playerId']?>}'><?=lang('Transfer Condition History')?></option>
            <?php endif;?>
            <?php if ($this->utils->isEnabledFeature('show_kyc_status') && $this->permissions->checkPermissions('view_kyc_history')):?>
                <option value="kycHistory" data-load="/player_management/kyc_history/" data-callback="kycHistory" data-params='{"player_id":<?=$player['playerId']?>}' data-dt-selector="#kycHistory-table"><?=lang('KYC History')?></option>
            <?php endif ?>
            <?php if ($this->utils->isEnabledFeature('show_risk_score') && $this->permissions->checkPermissions('view_risk_score_history')):?>
                <option value="riskScoreHistory" data-load="/player_management/risk_score_history/" data-callback="riskScoreHistory" data-params='{"player_id":<?=$player['playerId']?>}'><?=lang('Risk Score History')?></option>
            <?php endif ?>
            <option value="rgHistory" data-load="/player_management/rgHistory/" data-callback="rgHistory" data-params='{"player_id":<?=$player['playerId']?>}' data-dt-selector="#responsible-gaming-table"><?=lang('Responsible Gaming History Log')?></option>
            <?php if ($this->permissions->checkPermissions('player_communication_preference') && $this->utils->isEnabledFeature('enable_communication_preferences')): ?>
                <option value="communicationPreferenceHistory" data-load="/player_management/communicationPreferenceHistory/" data-callback="communicationPreferenceHistory" data-params='{"player_id":<?=$player['playerId']?>}'><?=lang('Communication Preference History')?></option>
            <?php endif ?>
            <?php if ($this->utils->isEnabledFeature('enable_shop')): ?>
                <option value="shoppingPointHistory" data-load="/player_management/shoppingPointHistory/<?=$player['playerId']?>" data-callback="shoppingPointHistory" data-params='{"player_id":<?=$player['playerId']?>}'><?=lang('Shopping Point History')?></option>
            <?php endif ?>
            <?php if ($this->permissions->checkPermissions('view_player_login_report') && $this->utils->getConfig('enable_player_login_report')): ?>
                <option value="playerLoginHistory" data-load="/player_management/playerLoginHistory/<?=$player['playerId']?>" data-callback="playerLoginHistory" data-params='{"player_id":<?=$player['playerId']?>}'><?=lang('Player Login History')?></option>
            <?php endif ?>
            <?php if ($this->permissions->checkPermissions('view_roulette_report') && $this->utils->getConfig('enabled_roulette_report')): ?>
                <option value="playerRouletteHistory" data-load="/player_management/playerRouletteHistory/<?=$player['playerId']?>" data-callback="playerRouletteHistory" data-params='{"player_id":<?=$player['playerId']?>}'><?=lang('Player Roulette History')?></option>
            <?php endif ?>
            <option value="shoppingPointHistory" data-load="/player_management/shoppingPointHistory/<?=$player['playerId']?>" data-callback="shoppingPointHistory" data-params='{"player_id":<?=$player['playerId']?>}'><?=lang('Shopping Point History')?></option>
            <option value="seamlessBalanceHistory" data-load="/player_management/seamlessBalanceHistory/<?=$player['playerId']?>" data-callback="seamlessBalanceHistory" data-params='{"player_id":<?=$player['playerId']?>}'><?=lang('Seamless Balance History')?></option>
            <?php if ($this->permissions->checkPermissions('grade_report')) : ?>
                <option value="playerGradeReport" data-load="/player_management/playerGradeReport/<?=$player['username']?>" data-callback="gradeHistory" data-dt-selector="#grade-table" data-params='{"player_id":<?=$player['playerId']?>}'><?=lang('Grade History')?></option>
            <?php endif; ?>
            <?php if ($this->utils->getConfig('enabled_remote_seamless_wallet_balance_history')) : ?>
                <option value="remoteWalletBalanceHistory" data-load="/player_management/remoteWalletBalanceHistory/<?=$player['playerId']?>" data-callback="remoteWalletBalanceHistory" data-params='{"player_id":<?=$player['playerId']?>}'><?=lang('Remote Wallet Balance History')?></option>
            <?php endif; ?>
            <option value="playerGamesReport" data-load="/player_management/playerGamesReport/<?=$player['playerId']?>" data-callback="gamesReport"  data-dt-selector="#gamesreport-table" data-params='{"player_id":<?=$player['playerId']?>}'><?=lang('Games Report')?></option>
            <?php if ($this->permissions->checkPermissions('quest_report')) : ?>
                <option value="playerQuestReport" data-load="/player_management/playerQuestReport/<?=$player['playerId']?>" data-callback="questReport"  data-dt-selector="#questreport-table" data-params='{"player_id":<?=$player['playerId']?>}'><?=lang('Quest Report')?></option>
            <?php endif; ?>
        </select>
    </div>
    <hr>

    <div id="changeable_table"></div>
</div>

<script type="text/javascript">
    $(document).ready(function() {
        $('#player_info_dropdown').multiselect({
            enableFiltering: true,
            filterBehavior: 'text',
            enableCaseInsensitiveFiltering: true,
            buttonClass: 'form-control',
            maxHeight: 300,
            buttonWidth: '300px'
        });

        var isEnable_column_visibility_report = false;
        <?php if($this->utils->isEnabledFeature('column_visibility_report')){ ?>
            isEnable_column_visibility_report = true;
        <?php } ?>

        var theOptions = {};
        theOptions.isEnable_column_visibility_report = isEnable_column_visibility_report;
        changeableTable.initial(theOptions);
        changeableTable.onReady();
        $('#playersLog select:has(option[data-load])').trigger('change');
        generate_shortcut();
    });

    function generate_shortcut() {
        var shortcut_list = JSON.parse('<?=$shortcut_list;?>');
        shortcut_list.forEach( function(item, key){
            var selector = $('#playersLog option[value="' + item + '"]');
            if(selector.length){
                var text = selector.text();
                var shortcut = '<button class="btn btn-zircon" data-target="' +item+ '" onclick="triggerChange(this);">' +text+ '</button>';
                $('#playersLog .form-inline').append(shortcut);
            }
        });
    }

    function triggerChange(self) {
        var target = $(self).data('target');
        $('#player_info_dropdown').multiselect('select', [target], true)
        $('#player_info_dropdown').multiselect('refresh');
        $('#playersLog select:has(option[data-load])').trigger('change');
    }
</script>
