<?php $col = $this->utils->isEnabledFeature('hide_sub_affiliates_on_affiliate') ? '4' : '3' ?>
<style type="text/css">
    .dashboard_stat_div_main {
        display: flex;
        justify-content: flex-start;
        align-items: center;
    }

    .dashboard_stat_div {
        flex-grow: 1;
        margin: 0 -13px;
    }

    .dashboard-stat.total-affiliate-player-deposit {
        background-color: #16a085

    }

    .dashboard-stat.total-affiliate-commission {
        background-color: #00c036
    }

    .dashboard-stat-num span {
        font-size: 2.4rem;
    }

    .dashboard-stat .details {
        right: 5px;
    }
</style>
<div class="dashboard_stat_div_main">
    <div class="col-lg-<?=$col?> col-md-<?=$col?> col-sm-6 col-xs-12 dashboard_stat_div" >
        <div class="dashboard-stat blue">
            <div class="visual">
                <i class="fa fa-users"></i>
            </div>
            <div class="details">
                <div class="number dashboard-stat-num">
                    <span><?=$total_players;?></span>
                </div>
                <div class="desc"> <?=lang('lang.countplayers');?> </div>
            </div>
            <span class="more"> <?=lang('traffic.today');?>: <strong><?=$today_players_today;?></strong></span>
        </div>
    </div>
    <div class="col-lg-<?=$col?> col-md-<?=$col?> col-sm-6 col-xs-12 dashboard_stat_div">
        <div class="dashboard-stat total-affiliate-player-deposit">
            <div class="visual">
                <i class="fa fa-money"></i>
            </div>
            <div class="details">
                <div class="number dashboard-stat-num">
                    <span><?php echo $total_count_affiliate_player_deposit; ?></span>
                </div>
                <div class="desc"> <?=lang('aff.countdepositplayer');?> </div>
            </div>
            <span class="more"> <?=lang('traffic.today');?>: <strong><?=$today_count_affiliate_player_deposit; ?></strong> </span>
        </div>
    </div>
    <div class="col-lg-<?=$col?> col-md-<?=$col?> col-sm-6 col-xs-12 dashboard_stat_div">
        <div class="dashboard-stat red">
            <div class="visual">
                <i class="fa fa-credit-card"></i>
            </div>
            <div class="details">
                <div class="number dashboard-stat-num">
                    <span><?php echo $total_deposit; ?></span>
                </div>
                <div class="desc"> <?=lang('lang.totaldeposit');?> </div>
            </div>
            <span class="more"> <?=lang('traffic.today');?>: <strong><?=$today_deposit; ?></strong> </span>
        </div>
    </div>
    <div class="col-lg-<?=$col?> col-md-<?=$col?> col-sm-6 col-xs-12 dashboard_stat_div">
        <div class="dashboard-stat green">
            <div class="visual">
                <i class="fa fa-money"></i>
            </div>
            <div class="details">
                <div class="number dashboard-stat-num">
                    <span><?php echo $total_withdraw; ?></span>
                </div>
                <div class="desc"> <?=lang('lang.totalwithdraw');?> </div>
            </div>
            <span class="more"> <?=lang('traffic.today');?>: <strong><?=$today_withdraw; ?></strong> </span>
        </div>
    </div>
    <?php if ( ! $this->utils->isEnabledFeature('hide_sub_affiliates_on_affiliate')): ?>
        <div class="col-lg-<?=$col?> col-md-<?=$col?> col-sm-6 col-xs-12 dashboard_stat_div">
            <div class="dashboard-stat purple">
                <div class="visual">
                    <i class="fa fa-user-secret"></i>
                </div>
                <div class="details">
                    <div class="number dashboard-stat-num">
                        <span><?=$total_subaffiliates;?></span>
                    </div>
                    <div class="desc"> <?=lang('lang.countsubaffiliates');?> </div>
                </div>
                <span class="more"> <?=lang('traffic.today');?>: <strong><?=$today_subaffiliates_today;?></strong> </span>
            </div>
        </div>
    <?php endif ?>
    <div class="col-lg-<?=$col?> col-md-<?=$col?> col-sm-6 col-xs-12 dashboard_stat_div">
        <div class="dashboard-stat total-affiliate-commission">
            <div class="visual">
                <i class="fa fa-money"></i>
            </div>
            <div class="details">
                <div class="number dashboard-stat-num">
                    <span><?php echo $total_affiliate_commission; ?></span>
                </div>
                <div class="desc"> <?=lang('aff.totalcommission');?> </div>
            </div>
            <span class="more"> <?=lang('traffic.today');?>: <strong><?=$today_affiliate_commission; ?></strong> </span>
        </div>
    </div>
</div>
