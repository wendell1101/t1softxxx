<?php
//$config['default_player_center_account_history_tab_order'] = ['rebate', 'deposit', 'withdrawal', 'transfer_request', 'promoHistory', 'game', 'referralFriend'];

    $enabledCreditMode = $this->utils->getConfig('enabledCreditMode') && $player_credit_mode && $this->utils->isEnabledFeature('agent_player_cannot_use_deposit_withdraw');
    $enableTransactionReport = $this->utils->getConfig('enable_transaction_report_in_player_center');
    // $tabs_order = $this->utils->getConfig('custom_player_center_account_history_tab_order') ?: $this->utils->getConfig('default_player_center_account_history_tab_order');
    $tabs_order = !empty($this->utils->getConfig('custom_player_center_account_history_tab_order')) ? $this->utils->getConfig('custom_player_center_account_history_tab_order') : $this->utils->getConfig('default_player_center_account_history_tab_order');
    foreach ($tabs_order as $tab): ?>
    <?php switch ($tab):
        case 'transaction': ?>
            <?php if ($enableTransactionReport) : ?>
                <li><a href="#ah-transaction" role="tab" data-toggle="tab" data-report_type="transaction"><?= lang('Transaction History') ?></a></li>
            <?php endif; ?>
        <? break;
        case 'rebate': ?>
            <li><a href="#ah-rebate" role="tab" data-toggle="tab" data-report_type="rebate"><?=lang('player.ui45')?></a></li>
        <?php break;?>
        <?php case 'credit_mode': ?>
            <?php if($enabledCreditMode) : ?>
                <li><a href="#ah-credit_mode" role="tab" data-toggle="tab" data-report_type="credit_mode"><?=lang('Credit History')?></a></li>
            <?php endif; ?>
        <?php break;?>
        <?php case 'deposit': ?>
            <?php if(!$enabledCreditMode) : ?>
                <li><a href="#ah-deposit" role="tab" data-toggle="tab" data-report_type="deposit"><?=lang('Deposit History')?></a></li>
            <?php endif; ?>
        <?php break;?>
        <?php case 'withdrawal': ?>
            <?php if(!$enabledCreditMode) : ?>
                <li><a href="#ah-withdrawal" role="tab" data-toggle="tab" data-report_type="withdrawal"><?=lang('Withdrawal History')?></a></li>
            <?php endif; ?>
        <?php break;?>
        <?php case 'transfer_request': ?>
            <?php if(!$this->utils->getConfig('seamless_main_wallet_reference_enabled')) : ?>
                <li><a href="#ah-transfer" role="tab" data-toggle="tab" data-report_type="transfer_request"><?=lang('Transfer History')?></a></li>
            <?php endif; ?>
        <?php break;?>
        <?php case 'promoHistory': ?>
            <li> <a href="#ah-promotion" role="tab" data-toggle="tab" data-report_type="promoHistory"> <?=lang('player.ui49')?> </a> </li>
        <?php break;?>
        <?php case 'game': ?>
            <li> <a href="#ah-game" role="tab" data-toggle="tab" data-report_type="game"> <?=lang('Game History')?> </a> </li>
        <?php break;?>
        <?php case 'unsettledGame': ?>
            <?php if($this->utils->getConfig('enable_account_history_unsettled_game')) : ?>
                <li> <a href="#ah-unsettled-game" role="tab" data-toggle="tab" data-report_type="unsettledGame"> <?=lang('Unsettled Game History')?> </a> </li>
            <?php endif; ?>
        <?php break;?>
        <?php case 'shop': ?>
            <?php if($this->utils->getConfig('enable_account_history_shop_history')) : ?>
                <li> <a href="#ah-shop-history" role="tab" data-toggle="tab" data-report_type="shop"> <?=lang('Shop History')?> </a> </li>
            <?php endif; ?>
        <?php break;?>
        <?php case 'referralFriend': ?>
            <?php if (!$this->utils->isEnabledFeature('hidden_accounthistory_friend_referral_status')): ?>
            <li> <a href="#ah-referral" role="tab" data-toggle="tab" data-report_type="referralFriend"> <?=lang('player.friendReferralStatus')?> </a> </li>
            <?php endif ?>
        <?php break;?>
    <?php endswitch ?>
    <?php endforeach; ?>