<?php
$sharingURL = $this->utils->getSystemUrl('player') . '/player_center/iframe_register?referralcode=' . $player['invitationCode'];
?>
<div id="referral" class="panel friend-referral">
    <div class="panel-heading">
        <h1 class="hidden-xs hidden-sm"><?= lang("Refer a Friend") ?></h1>
    </div>
    <div class="panel-body">
        <?php if ($enableFriendRefExtraInfo) : ?>
            <div class="clearfix referral-extrainfo">
                <div id="referral-extrainfo-title">
                    <?= $extraInfoTitle ?>
                </div>
                <div class="col-md-12" id="referral-extrainfo-content">
                </div>
            </div>
        <?php endif; ?>
        <div class="clearfix refferal-url refferal-code-item" id="refferal-link-block">
            <div class="col-md-12" id="referral-link-title">
                <p><strong><?= lang("Referral URL") ?> :</strong></p>
            </div>
            <div class="col-md-10">
                <p id="referral-link" class="referral-code-content"><?= $sharingURL ?></p>
            </div>
            <?php if (!$enableFriendRefMobileShare) : ?>
                <div class="col-md-2">
                    <a href="javascript:void(0)" class="btn btn-info btn-sm" onclick="return PlayerReferral.pcCopyToclipboard('referral-link');"><?= lang('Copy') ?></a>
                </div>
            <?php endif; ?>
        </div>
        <?php if (!$this->utils->isEnabledFeature('hidden_referFriend_referralcode')) : ?>
            <div class="clearfix refferal-code refferal-code-item" id="refferal-code-block">
                <div class="col-md-12" id="refferal-code-title">
                    <p><strong><?= lang("Referral Code") ?> :</strong></p>
                </div>
                <div class="col-md-10">
                    <p id="referral-code" class="referral-code-content"><?= $player['invitationCode'] ?></p>
                </div>
                <?php if (!$enableFriendRefMobileShare) : ?>
                    <div class="col-md-2">
                        <a href="javascript:void(0)" class="btn btn-info btn-sm" data-target="test" onclick="return PlayerReferral.pcCopyToclipboard('referral-code');"><?= lang('Copy') ?></a>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        <?php if ($this->utils->getConfig('show_referFriend_bonus')) : ?>
            <div class="clearfix refferal-code-item" id="refferal-bonus-block">
                <div class="col-md-12" id="refferal-bonus-title">
                    <p><strong><?= lang("Bonus Per Referral") ?> :</strong></p>
                </div>
                <div class="col-md-10">
                    <p id="referral-bonus-content" class="referral-code-content"><?= sprintf(lang('referral-bonus-content'), $this->utils->safeGetArray($friend_referral_settings, 'bonusAmount', '0')) ?></p>
                </div>
            </div>
        <?php endif; ?>
        <?php if ($enableFriendRefMobileShare) : ?>
            <div class="clearfix refferal-code-item" id="refferal-mobile-sharing-block">
                <div class="refferal-mobile-sharing-share">
                    <a href="javascript:void(0)" class="btn btn-share btn-sm" onclick="return PlayerReferral.mobileDeviceShare('referral-link');"><i class="fa fa-share-square"></i></a>
                    <p><?= lang('Share') ?></p>
                </div>
                <div class="refferal-mobile-sharing-copy">
                    <a href="javascript:void(0)" class="btn btn-copy btn-sm" data-target="test" onclick="return PlayerReferral.pcCopyToclipboard('referral-link');"><i class="fa fa-copy"></i></a>
                    <p><?= lang('Copy') ?></p>
                </div>
            </div>
        <?php endif; ?>
        <?php if ($this->utils->isEnabledFeature('enable_edit_upload_referral_detail') && !empty($friend_referral_settings['referralDetails'])) : ?>
            <div class="clearfix refferal-code-item" id="refferal-detail-block">
                <div class="col-md-12" id="referral-detail-title">
                    <p><strong><?= lang("mark.referraldetails") ?> :</strong></p>
                </div>
                <div class="col-md-12">
                    <p id="referral-detail-content" class="referral-code-content"></p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
<script type="text/javascript" src="<?= $this->utils->getPlayerCmsUrl('/common/js/player_center/player-referral.js') ?>"></script>
<script type="text/javascript">
    $(function() {
        var referralDetails = `<?= $this->utils->safeGetArray($friend_referral_settings, 'referralDetails', '') ?>`;
        $("#referral-detail-content").html(_export_sbe_t1t.utils.decodeHtmlEntities(referralDetails, 'default'));

        PlayerReferral.msg_success_copy = '<?= lang('Successfully copied to clipboard') ?>';

        var enableFriendRefExtraInfo = parseInt('<?= $enableFriendRefExtraInfo ?>');
        var enableFriendRefMobileShare = parseInt('<?= $enableFriendRefMobileShare ?>');
        var friendRefExtraInfo = `<?php echo (empty($friendRefExtraInfo) ? json_encode(array()) : $friendRefExtraInfo) ?>`;
        if ((enableFriendRefExtraInfo == 1) && !!friendRefExtraInfo) {
            friendRefExtraInfo = JSON.parse(friendRefExtraInfo);
            PlayerReferral.generateExtraInfo(friendRefExtraInfo);
        } else {
            $('.referral-extrainfo').hide();
        }

        if (enableFriendRefMobileShare == 1) {
            PlayerReferral.sharingTitle = `<?= $friendRefMobileSharingTitle ?>`;
            PlayerReferral.sharingText = `<?= $friendRefMobileSharingText ?>`;
            PlayerReferral.sharingURL = '<?= $sharingURL ?>';
            PlayerReferral.sharingCode = '<?= $player['invitationCode'] ?>';
            PlayerReferral.generateShareString();
        }
    });
</script>