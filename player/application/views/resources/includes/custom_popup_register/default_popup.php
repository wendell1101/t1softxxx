<?php
$playerId = $this->load->get_var('playerId');
$big_wallet = $this->wallet_model->getOrderBigWallet($playerId);
$player_first_login_page_button_setting = $this->utils->getConfig('player_first_login_page_button_setting');
$total_no_frozen = $this->load->vars('total_no_frozen', $big_wallet['total'] - $big_wallet['main']['frozen']);


$is_registered_popup_success_done = true;
if (!empty($playerId)) {
    $is_registered_popup_success_done = $this->player_model->getPlayerInfoDetailById($playerId, null)['is_registered_popup_success_done'];
}
?>

<?php if (!$is_registered_popup_success_done) { ?>
    <?php if ($this->utils->getConfig('custom_registered_popup') === false || $customFileExist == false) { ?>
        <div class="modal fade " id="registered-modal" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <?php if ($this->operatorglobalsettings->getSettingJson('registered_success_popup') == 1) { ?>
                        <div class="modal-body">
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                            <div class="modal-title text-center">
                                <div class="title">
                                    <h4><?= lang('Congratulations! You have successfully registered'); ?></h4>
                                </div>
                                <?php if (!empty($player_first_login_page_button_setting)) { ?>
                                    <?php foreach ($player_first_login_page_button_setting as $btn_type => $settings) : ?>
                                        <?php if ($btn_type == 'home_btn') { ?>
                                            <div class="redirect-link">
                                                <span><a href="<?= $this->utils->getSystemUrl('www', '/') . "#main-content"; ?>"><?= lang($settings['lang_key']); ?></a></span>
                                            </div>
                                        <?php } ?>
                                        <?php if ($btn_type == 'deposit_btn') { ?>
                                            <div class="deposit-info">
                                                <?php if (isset($settings['account'])) { ?>
                                                    <span><?= lang('Account'); ?>：<?= $this->authentication->getUsername(); ?></span>
                                                <?php } ?>
                                                <?php if (isset($settings['account_balance'])) { ?>
                                                    <span><?= lang('Account balance'); ?>：<?= $this->utils->displayCurrency($total_no_frozen); ?></span>
                                                <?php } ?>
                                                <span><a href="/player_center2/deposit"><?= lang($settings['lang_key']); ?></a></span>
                                            </div>
                                        <?php } ?>
                                        <?php if ($btn_type == 'promo_btn') { ?>
                                            <?php if ($this->utils->isEnabledFeature('switch_to_player_center_promo_on_first_popup_after_register')) { ?>
                                                <div class="player-center-promo">
                                                    <span><a href="/player_center2/promotion"><?= lang($settings['lang_key']); ?></a></span>
                                                </div>
                                            <?php } else { ?>
                                                <div class="promo-list">
                                                    <?php $promoList = $this->utils->getPlayerPromo('firstLogin', $player['playerId']); ?>
                                                    <?php foreach ($promoList as $_list) : ?>
                                                        <span> <?= $_list['promoDescription']; ?> </span>
                                                        <span><a href="<?= $this->utils->getSystemUrl('player') ?>/player_center2/promotion"><?= lang($settings['lang_key']); ?></a></span><br />
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php } ?>
                                        <?php } ?>
                                        <?php if ($btn_type == 'bank_account_btn') { ?>
                                            <div class="bankacc-link">
                                                <span><a href="/player_center2/bank_account"><?= lang($settings['lang_key']); ?></a></span>
                                            </div>
                                        <?php } ?>
                                    <?php endforeach; ?>
                                <?php } ?>
                            </div>
                        </div>
                    <?php } ?>
                    <?php if ($this->operatorglobalsettings->getSettingJson('registered_success_popup') == 2) { ?>
                        <div class="modal-body">
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                            <div class="modal-title text-center">
                                <a href="javascript:void(0)" data-url="<?= $this->utils->getSystemUrl('player') . '/player_center/dashboard#accountInformation' ?>" onclick="registered_popup_click(this);">
                                    <img src="<?= $this->utils->getSystemUrl('www') . "/" . $this->utils->getConfig('registered_image_poup_path') ?>">
                                </a>
                            </div>
                        </div>
                    <?php } ?>
                </div>
            </div>
        </div> <!-- EOF #registered-modal -->
    <?php } ?>
<?php } ?>