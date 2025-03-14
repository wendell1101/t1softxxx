<?php

$playerId = $this->load->get_var('playerId');

$is_registered_popup_success_done = true;
if(!empty($playerId)) {
    $is_registered_popup_success_done = $this->player_model->getPlayerInfoDetailById($playerId, null)['is_registered_popup_success_done'];
}
?>
<?php if(!$is_registered_popup_success_done){ ?>
<div class="modal fade " id="registered-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
        <?php if ($this->operatorglobalsettings->getSettingJson('registered_success_popup') == 1) { ?>
            <div class="modal-body">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            <div class="modal-title text-center">
                <div class="title">
                    <h4><?= lang('Congratulations! You have successfully registered'); ?></h4>
                    <p class="sec-title"><?=lang('Welcome to SMASH, you can enjoy the following exclusive offers for new players! Go and collect your prize!');?></p>
            <?php } ?>
                </div>
            <div class="platforms">
                    <div class="platform-item item1">
                        <div class="plat-title">
                            <p><?=lang('Single Deposit');?><span class="keypoints">≥R$100</span></p>
                        </div>
                        <div class="plat-details">
                            <p class="event-rate">50<span>%</span></p>
                            <p class="detail"><?=lang('First time only');?></p>
                        </div>
                    </div>
                    <div class="platform-item item2">
                        <div class="plat-title">
                            <p><?=lang('Single Deposit');?><span class="keypoints">≥R$50</span></p>
                        </div>
                        <div class="plat-details">
                            <p class="event-rate">6<span>BRL</span></p>
                            <p class="detail"><?=lang('First time only')?></p>
                        </div>
                    </div>
                    <div class="platform-item item3">
                        <div class="plat-title">
                            <p><?=lang('Single Deposit');?><span class="keypoints">≥R$100</span></p>
                        </div>
                        <div class="plat-details">
                            <p class="event-rate">8<span>%</span></p>
                            <p class="detail"><?=lang('3 times/day');?></p>
                        </div>
                    </div>
                    <div class="platform-item item4">
                        <div class="plat-title">
                            <p><?=lang('Invite friends to deposit');?><span class="keypoints">≥R$20</span></p>
                        </div>
                        <div class="plat-details">
                            <p class="event-rate">10<span>BRL</span></p>
                            <p class="detail"><?=lang('No cap');?></p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <a class="btn btn-default game-btn" href="<?= $this->utils->getSystemUrl('www', '/') . "#main-content"; ?>"><?=lang('Home')?></a>
                <a class="btn btn-default deposit-btn" href="/player_center2/deposit"><?=lang('Deposit');?></a>
            </div>
            <?php if ($this->operatorglobalsettings->getSettingJson('registered_success_popup') == 2) { ?>
                <div class="modal-body">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <div class="modal-title text-center">
                    <a href="javascript:void(0)" data-url="<?=$this->utils->getSystemUrl('player') . '/player_center/dashboard#accountInformation'?>" onclick="registered_popup_click(this);">
                        <img src="<?= $this->utils->getSystemUrl('www') . "/" . $this->utils->getConfig('registered_image_poup_path') ?>">
                    </a>
                </div>
            </div>
            <?php } ?>
<?php } ?>
