<style>
    .withdrawal-modal-body .row{
        margin: 0 -15px;
    }

    .withdrawal-modal-body .row .col-md-6 input[type="text"]{
        height: 40px;
    }

    .withdrawal-modal-body .row .col-md-6 button{
        margin-top: 30px;
    }

    .mc-content .sub-wallet-container .inner-content {
        height: 48px;
    }

    .mc-content .sub-wallet-container .inner-content.gray-out {
        border-color: #ddd;
        background-color: #fff;
        color: #ddd;
    }

    .mc-content .sub-wallet-container .inner-content.gray-out .under-maintenance {
        width: 100%;
        text-align: center;
    }
</style>
<div id="memberCenter" class="tab-pane main-content fade in active">
    <h1><?php
            // $memberCenterName = $this->utils->getConfig('playercenter.memberCenterName');
            echo lang("Title Cashier Center");
        ?>
    </h1>
    <div class="row cashier-center-header">
        <div class="col-sm-4 col-md-4">
            <div class="inner-content">
                <p><?=lang("Main Wallet Total")?></p>
                <h2 id="total_mainwallet_balance" class="d-yen main-total"><?=$this->utils->displayCurrency($total_main_wallet_balance)?></h2>
                <div class="text-right">
                <?php if($this->utils->isEnabledFeature('cashier_multiple_refresh_btn')) : ?>
                    <a href="javascript:void(0)" onclick="return PlayerCashier.manuallyRefreshBalance();" class="btn quick-refresh"><i class="glyphicon glyphicon-refresh"></i></a>
                <?php else : ?>
                    <a id="refresh_balance" href="javascript:void(0)" onclick="return PlayerCashier.manuallyRefreshBalance();" class="refreshBalanceButton btn" style="display: inline-block;"><i class="glyphicon glyphicon-refresh"></i><?=lang('lang.refreshbalance')?></a>
                <?php endif; ?>
                </div>
            </div>
        </div>
        <?php if (!$this->utils->getConfig('seamless_main_wallet_reference_enabled')) : ?>
        <div class="col-sm-4 col-md-4">
            <div class="inner-content">
                <p><?=lang("Game Wallet Total")?></p>
                <h2 id="total_subwallet_balance" class="game-total"><?=$this->utils->displayCurrency($total_subwallet_balance)?></h2>
                <?php if($playerStatus==5):?>

                <?php else: ?>
                    <div class="clearfix text-right">
                        <a href="javascript:void(0)" class="btn d-btn <?= empty($total_subwallet_balance) ? 'disabled' : '' ?> " id="transferAllToMainBtn"><?=lang("Transfer Back All")?></a>
                    <?php if($this->utils->isEnabledFeature('cashier_multiple_refresh_btn')) : ?>
                        <a href="javascript:void(0)" onclick="return PlayerCashier.manuallyRefreshBalance();" class="btn quick-refresh"><i class="glyphicon glyphicon-refresh"></i></a>
                    <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <div class="col-sm-4 col-md-4">
            <div class="inner-content">
                <p><?=lang("Total Balance")?></p>
                <h2 id="total_blance" class="total-balance"><?=$this->utils->displayCurrency($total_no_frozen)?></h2>
                <div class="text-right">
                <?php if($this->utils->isEnabledFeature('cashier_multiple_refresh_btn')) : ?>
                    <a href="javascript:void(0)" onclick="return PlayerCashier.manuallyRefreshBalance();" class="btn quick-refresh"><i class="glyphicon glyphicon-refresh"></i></a>
                <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <div class="row cashier-center-withdrawal mt20">
        <?php if(!$this->utils->isEnabledFeature('hidden_player_center_pending_withdraw_balance_tab')) :  ?>
        <div class="col-sm-4 cashier_center_pending_withdraw_balance">
            <div class="clearfix inner-content">
                <div class="col-xs-7 text-label"><?=lang("cashier.pendingBalance");?></div>
                <div id="pending_withdraw_balance" class="col-xs-5 text-right pending-total"><?=$this->utils->displayCurrency($total_frozen)?></div>
                <?php if($this->utils->getConfig('enabled_player_cancel_pending_withdraw')) : ?>
                    <a href="<?=$system_hosts['player']?>/player_center2/report/index/withdrawal" class="btn quick-refresh pending_withdrawal"><?=lang("View");?></a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
        <?php if(!$this->utils->isEnabledFeature('hidden_player_center_total_deposit_amount_tab')) :  ?>
        <div class="col-sm-4 cashier_center_total_deposit_amount">
            <div class="clearfix inner-content">
                <div class="col-xs-7 text-label"><?=lang("player.ui15");?></div>
                <div id="total_deposit_amount" class="col-xs-5 text-right"><?=$this->utils->displayCurrency($playerBalance[0])?></div>
            </div>
        </div>
        <?php endif; ?>
        <?php if(!$this->utils->isEnabledFeature('hidden_player_center_total_withdraw_amount_tab')) :  ?>
        <div class="col-sm-4 cashier_center_total_withdraw_amount">
            <div class="clearfix inner-content">
                <div class="col-xs-7 text-label"><?=lang("player.ui18");?></div>
                <div id="total_withdraw_amount" class="col-xs-5 text-right"><?=$this->utils->displayCurrency($playerBalance[1])?></div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <?php if($playerStatus==5):?>
        <div class="row">
            <a href="#" class="btn btn-xs btn-warning"><?=lang("Your Status is Suspend. You cannot use  deposit , withdrawal , transfer functions")?></a>
        </div>

    <?php else: ?>

    <ul class="fm-ul row cashier-center-tabs">
    <?php $hide_transfer_tab_in_player_center = $this->utils->getConfig('hide_transfer_tab_in_player_center');
        if( ! $hide_transfer_tab_in_player_center ):
    ?>
        <li id="transfer-panel-page" class="active col-xs-3">
            <a href="#fm-transfer" data-toggle="tab"><i class="fa fa-credit-card" aria-hidden="true"></i><?= lang('Transfer') ?></a>
        </li>
    <?php endif; /// EOF if( ! $hide_transfer_tab_in_player_center ):...
    ?>
        <?php if(!$this->utils->isEnabledFeature('agent_player_cannot_use_deposit_withdraw') || $this->utils->isEnabledFeature('agent_player_cannot_use_deposit_withdraw') && !$player['credit_mode']) :  ?>
        <li id="deposit-panel-page" class="col-xs-3">
            <a href="<?=$this->utils->getSystemUrl('player')?>/player_center2/deposit" id="quick_deposit_change_account"><i class="fa fa-credit-card-alt" aria-hidden="true"></i><?= lang('Deposit') ?></a>
        </li>
        <li id="withdrawal-panel-page" class="col-xs-3">
            <a href="<?=$this->utils->getSystemUrl('player')?>/player_center2/withdraw"><i class="fa fa-credit-card" aria-hidden="true"></i><?= lang('Withdrawal') ?></a>
        </li>
        <li id="bank-info-panel-page" class="col-xs-3">
            <a href="<?=$this->utils->getSystemUrl('player')?>/player_center2/bank_account"><i class="fa fa-university" aria-hidden="true"></i><?= lang('cashier.16') ?></a>
        </li>
        <?php if($this->utils->enableRedemptionCodeInPlayerCenter()) {?>
        <li id="redemption-code-panel-page" class="col-xs-3">
            <a href="<?=$system_hosts['player']?>/player_center2/redemption_code"><span class="redemption_code_icon"></span><?= lang('redemptionCode.redemptionCode') ?></a>
        </li>
        <?php } ?>
        <?php endif; ?>
    </ul>
    <div class="tab-content fm-content">
        <!--- ===========================================================
        Transfer
        =========================================================== -->
        <div id="fm-transfer" class="tab-pane active">
            <div class="row sub-wallet-container mt20 wallet-ui-modal">
                <?php
                if(!empty($subwallet)) : ?>
                    <?php foreach($subwallet as $key => $value) : ?>
                        <div class="col-sm-6 subwallet" data-toggle="tooltip"
                             title="<?=
                                in_array($value['typeId'], $this->utils->getConfig('api_not_allowed_cents')) ? lang('Cents transfer are not allowed') : '';
                             ?>"
                             data-typeid="<?= lang($value['typeId']); ?>">
                            <div class="clearfix inner-content <?= $value['maintenance_mode'] == 1 ? 'gray-out' : '' ?>">
                                <?php ?>
                                <div class="col-xs-4 game"><?=lang($value['game'])?> <?= isset($game_daily_currency_rate[$value['typeId']]) ?  $this->utils->getConfig('default_currency_symbol')."1"." <-> $".$game_daily_currency_rate[$value['typeId']] : "" ?></div>
                                <div class="col-xs-4 amount"><?=$this->utils->displayCurrency($value['totalBalanceAmount']);?></div>
                                <div class="col-xs-4 button-transfer">
                                    <?php if ($value['maintenance_mode'] == 1) : ?>
                                        <div class="under-maintenance">
                                            <?= lang('Under Maintenance') ?>
                                        </div>
                                    <?php else : ?>
                                        <a href="javascript:void(0)" data-sub-wallet-id="<?=$value['typeId']?>" class="btn transfer-fund-btn">
                                            <?= lang('Transfer') ?>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <?php if($this->utils->isEnabledFeature('cashier_multiple_refresh_btn')) : ?>
                <div class="col-xs-4"><a href="javascript:void(0)" onclick="return PlayerCashier.manuallyRefreshBalance();" class="refreshBalanceButton btn" style="
                display: inline-block;"><i class="glyphicon glyphicon-refresh"></i><?=lang('lang.refreshbalance')?></a></div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

</div>
<script type="text/javascript" src="<?=$this->utils->getPlayerCmsUrl('/common/js/player_center/player-cashier.js')?>"></script>