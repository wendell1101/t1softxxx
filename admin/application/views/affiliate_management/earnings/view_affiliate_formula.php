<br>
<strong>
    <span class="label label-primary label-formula" ><?=lang('Other Fee')?></span> = <span class="label label-default"><?=lang('Bonus Fee')?></span> + <span class="label label-default"><?=lang('Cashback Fee')?></span> + <span class="label label-default"><?=lang('Transaction Fee')?></span>
<?php if ($this->utils->isEnabledFeature('enable_player_benefit_fee')):?>
    + <span class="label label-default"><?=lang("Player's Benefit Fee")?></span>
<?php endif;?>
<?php if ($this->utils->isEnabledFeature('enable_addon_affiliate_platform_fee')):?>
    + <span class="label label-default"><?=lang("Addon Platform Fee")?></span>
<?php endif;?>
    <br>
    <span class="label label-primary label-formula" ><?=lang('game.platform.percent')?></span> = <span class="label label-default"><?=lang('Game Platform Bet')?></span> / <span class="label label-default"><?=lang('Total Bet')?></span><br>
    <span class="label label-primary label-formula" ><?=lang('Game Platform Gross Revenue')?></span> = (<span class="label label-default"><?=lang('Total Loss')?></span> - <span class="label label-default"><?=lang('Total Win')?></span>) - ((<span class="label label-default"><?=lang('Total Loss')?></span> - <span class="label label-default"><?=lang('Total Win')?></span>) &times; <span class="label label-default"><?=lang('Game Platform Fee %')?></span>)<br>
    <span class="label label-primary label-formula" ><?=lang('Game Platform Admin Fee')?></span> = <span class="label label-default"><?=lang('Gross Revenue')?></span> &times; <span class="label label-default"><?=lang('admin.fee.percent')?></span><br>
    <span class="label label-primary label-formula" ><?=lang('Game Platform Other Fee')?></span> = <span class="label label-default"><?=lang('Other Fee')?></span> / <span class="label label-default"><?=lang('game.platform.percent')?></span><br>
    <span class="label label-primary label-formula" ><?=lang('Game Platform Net Revenue')?></span> = <span class="label label-default"><?=lang('Game Platform Gross Revenue')?></span> - <span class="label label-default"><?=lang('Game Platform Admin Fee')?></span> - <span class="label label-default"><?=lang('Game Platform Other Fee')?></span><br>
    <span class="label label-primary label-formula" ><?=lang('Game Platform Commission')?></span> = <span class="label label-default"><?=lang('Game Platform Net Revenue')?></span> &times; <span class="label label-default"><?=lang('Game Platform Shares %')?></span><br>
    <span class="label label-primary label-formula" ><?=lang('Total Fee')?></span> = <span class="label label-default"><?=lang('Other Fee')?></span> + <span class="label label-default"><?=lang('sum.game.platform.admin.fee')?></span><br>
</strong>
<br>