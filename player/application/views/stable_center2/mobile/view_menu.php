<?php
if(!empty($this->utils->getConfig('use_custom_player_info_mobile'))){
    include __DIR__ . "/includes/custom_player_info/".$this->utils->getConfig('use_custom_player_info_mobile')."/player_info.php";
} else {
    include __DIR__ . "/includes/player_info.php";
} ?>

<?php if($this->utils->isEnabledFeature('show_game_lobby_in_player_center')){ ?>

    <?php include "includes/game_lobby.php" ?>

<?php } ?>

<?php if($this->utils->getConfig('enabled_player_cancel_pending_withdraw')) : ?>
    <div class="pending_withdrawal">
        <p><?=lang("cashier.pendingBalance");?></p>
        <p id="pending_withdraw_balance" class="balance"><?=$this->utils->displayCurrency($big_wallet['total_frozen'])?></p>
        <a href="<?=$this->utils->getSystemUrl('player')?>/player_center2/report/index/withdrawal" class="go">
            <img src='data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABYAAAArCAYAAAB8UHhIAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAyNpVFh0WE1MOmNvbS5hZG9iZS54bXAAAAAAADw/eHBhY2tldCBiZWdpbj0i77u/IiBpZD0iVzVNME1wQ2VoaUh6cmVTek5UY3prYzlkIj8+IDx4OnhtcG1ldGEgeG1sbnM6eD0iYWRvYmU6bnM6bWV0YS8iIHg6eG1wdGs9IkFkb2JlIFhNUCBDb3JlIDYuMC1jMDAyIDc5LjE2NDQ2MCwgMjAyMC8wNS8xMi0xNjowNDoxNyAgICAgICAgIj4gPHJkZjpSREYgeG1sbnM6cmRmPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5LzAyLzIyLXJkZi1zeW50YXgtbnMjIj4gPHJkZjpEZXNjcmlwdGlvbiByZGY6YWJvdXQ9IiIgeG1sbnM6eG1wPSJodHRwOi8vbnMuYWRvYmUuY29tL3hhcC8xLjAvIiB4bWxuczp4bXBNTT0iaHR0cDovL25zLmFkb2JlLmNvbS94YXAvMS4wL21tLyIgeG1sbnM6c3RSZWY9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC9zVHlwZS9SZXNvdXJjZVJlZiMiIHhtcDpDcmVhdG9yVG9vbD0iQWRvYmUgUGhvdG9zaG9wIDIxLjIgKFdpbmRvd3MpIiB4bXBNTTpJbnN0YW5jZUlEPSJ4bXAuaWlkOjcyMEQxQUFGREU1NTExRUI4MkE4OUM4RDBCNjczRUY3IiB4bXBNTTpEb2N1bWVudElEPSJ4bXAuZGlkOjcyMEQxQUIwREU1NTExRUI4MkE4OUM4RDBCNjczRUY3Ij4gPHhtcE1NOkRlcml2ZWRGcm9tIHN0UmVmOmluc3RhbmNlSUQ9InhtcC5paWQ6NzIwRDFBQURERTU1MTFFQjgyQTg5QzhEMEI2NzNFRjciIHN0UmVmOmRvY3VtZW50SUQ9InhtcC5kaWQ6NzIwRDFBQUVERTU1MTFFQjgyQTg5QzhEMEI2NzNFRjciLz4gPC9yZGY6RGVzY3JpcHRpb24+IDwvcmRmOlJERj4gPC94OnhtcG1ldGE+IDw/eHBhY2tldCBlbmQ9InIiPz6Iaja4AAAB60lEQVR42rTXP0jDQBQG8DQVcezg4CBCpeBipwqKoPhnKHRRLCIODl0cHARxEEEHRXBRF3ERXEQRBFFwLkbERUoFHaRQ0KGICLooDkKJ38knHLE1NPc8uL60gV+P1+u9l5DrutZ/jDr1Ui4u3PN9OhxbyUvANuMZZgnzCF8Sk4BDP6kAeMHPmjE7sfJnEbgC3gb80zQV3wNQDy9VWgoSOa6E57Qf1RzW8CYT3K52Q8OtILjtc7+X+a4Z/xPGqtWW6dPwgtSKFV5GGCReD/xaBCau9nOSeAT4uQhM/AMhRbwFeFYEJv6GMEy8FfipCEz8BWGMeDvwAxGY+CPCBPEu4DsiMPEHhAzxAeBbIjDxIsIk8RTwdRGY+B3CNPER4Eu/zmOTATChDi1MlaKkbcmNW+06YgutNoyQ5WrzSM+V1IodlrMS0LRIjlknFfoONC6yK4g+cXfERfaxhnYAjYr88/zQQDnWe49qaM0r9jY0IuexB034dUl2ALQf6KtxaQJ6rKFDPC7NiinQfYRGoqNAb4zLP9BdVTSJZoDmjBsWoGsIUaIzQB3jFgvorGq8iS4CPTFuCoGOs7wrdBXonnEbC1Q1I1NEN4FuGzfeQLsR5okeAt2Qemq6JOoAnRN7zsNYxmyQQkWrtHd8CTAANwrZ4c/H7/kAAAAASUVORK5CYII='/>
        </a>
    </div>
<?php endif; ?>

<?php include __DIR__ . "/includes/mobile_main_menu.php" ?>

<div class="blank"></div>
<!--bottom-->
<?php if($this->utils->isEnabledFeature('enable_mobile_copyright_footer')): ?>
    <?=$this->load->view($this->utils->getPlayerCenterTemplate(FALSE) . '/mobile/includes/template_footer');?>
<?php endif; ?>
<div class="modal fade" id="progress-modal" role='dialog'>
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-body">
                <img class="loading" src="/<?=$this->utils->getPlayerCenterTemplate(FALSE)?>/images/loading.gif">
            </div>
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div><!-- /.modal -->

<?php if ($this->utils->isEnabledFeature('enable_registered_show_success_popup') && !$is_registered_popup_success_done) : ?>

<?php $customFileExist = false;
if ($this->utils->getConfig('custom_registered_popup') !== false) : ?>
    <?php $filepath = VIEWPATH . '/resources/includes/custom_popup_register/' . $this->utils->getConfig('custom_registered_popup') . '.php';
    if ($customFileExist = file_exists($filepath)) {
        include $filepath;
    }?>
<?php endif ?>
<?php if ($this->utils->getConfig('custom_registered_popup') === false || $customFileExist == false) : ?>
    <div class="modal fade " id="registered-modal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <?php if ($this->operatorglobalsettings->getSettingJson('registered_success_popup') == 1) : ?>
                    <div class="modal-body">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <div class="modal-title text-center">
                            <div class="title">
                                <h4><?= lang('Congratulations! You have successfully registered'); ?></h4>
                            </div>
                                <?php foreach ($player_first_login_page_button_setting as $btn_type => $settings) : ?>
                                    <?php if ($btn_type == 'home_btn') : ?>
                                        <div class="redirect-link">
                                            <span><a href="<?= $this->utils->getSystemUrl('m'); ?>"><?= lang($settings['lang_key']); ?></a></span>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($btn_type == 'deposit_btn') : ?>
                                        <div class="deposit-info">
                                            <?php if (isset($settings['account'])) : ?>
                                                <span><?= lang('Account'); ?>：<?= $this->authentication->getUsername(); ?></span>
                                            <?php endif; ?>
                                            <?php if (isset($settings['account_balance'])) : ?>
                                                <span><?= lang('Account balance'); ?>：<?= $this->utils->displayCurrency($total_no_frozen); ?></span>
                                            <?php endif; ?>
                                            <span><a href="/player_center2/deposit"><?= lang($settings['lang_key']); ?></a></span>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($btn_type == 'promo_btn') : ?>
                                        <?php if ($this->utils->isEnabledFeature('switch_to_player_center_promo_on_first_popup_after_register')) : ?>
                                            <div class="player-center-promo">
                                                <span><a href="/player_center2/promotion"><?= lang($settings['lang_key']); ?></a></span>
                                            </div>
                                        <?php else : ?>
                                            <div class="promo-list">
                                                <?php $promoList = $this->utils->getPlayerPromo('firstLogin', $player['playerId']); ?>
                                                <?php foreach ($promoList as $_list) : ?>
                                                    <span> <?= $_list['promoDescription']; ?> </span>
                                                    <span><a href="<?= $this->utils->getSystemUrl('player') ?>/player_center2/promotion"><?= lang($settings['lang_key']); ?></a></span><br />
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif ?>
                                    <?php endif; ?>
                                    <?php if ($btn_type == 'bank_account_btn') : ?>
                                        <div class="bankacc-link">
                                            <span><a href="/player_center2/bank_account"><?= lang($settings['lang_key']); ?></a></span>
                                        </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
                <?php if ($this->operatorglobalsettings->getSettingJson('registered_success_popup') == 2) : ?>
                    <div class="modal-body">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <div class="modal-title text-center">
                            <a href="javascript:void(0)" onclick=" $('#registered-modal').modal('hide'); ">
                                <img src="<?= $this->utils->getSystemUrl('www') . "/" . $this->utils->getConfig('registered_image_poup_path') ?>">
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
                <div class="modal-footer"></div>
            </div>
        </div>
    </div>
<?php endif; ?>


<?php if (isset($enable_pop_up_verify_contact_number)&&$enable_pop_up_verify_contact_number) :?>
<!-- enable_pop_up_verify_contact_number Modal -->
    <div class="modal fade " id="enable_pop_up_verify_contact_number-modal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
        <div class="modal-dialog  modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="modal-title">
                        <h4><?= lang('Message') ?></h4>
                    </div>
                    <div class="row">
                       <p><?= lang('Please verify your contact number to enable game launch.') ?></p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal"><?=lang('verify_account_close_button');?></button>
                </div>
            </div>
        </div>
    </div>
    <script>
        $(function () {
            $("#enable_pop_up_verify_contact_number-modal").modal('show');
        });
    </script>
<?php endif; ?>

<script type="text/javascript" src="/common/js/player_center/registered-autoplay-procedure.js?v=<?=PRODUCTION_VERSION;?>"></script>

<script type="text/javascript">
    RegisteredAutoPlayProcedure.is_registered_popup_success_done = <?php echo($is_registered_popup_success_done); ?>;
    RegisteredAutoPlayProcedure.enable_registered_show_popup = <?= ($this->utils->isEnabledFeature('enable_registered_show_success_popup')) ? 1 : 0 ?>;
    RegisteredAutoPlayProcedure.hide_registered_modal = <?= empty( $this->utils->getConfig('hide_registered_modal') )? 0: 1 ?>;
    RegisteredAutoPlayProcedure.execute();

    function createstars(n) {
    return new Array(n+1).join("*")
    }

    $(function() {
        $("#registered-modal").modal('show');
        $.post("/player_center/setIsRegisterPopUpDone");

        var modal = $('#progress-modal').modal({
            'show': false
        });

        $('#logoutxx').on('click', function(e){
            e.preventDefault();

            modal.modal('show');

            window.location = base_url + 'player_center/player_center_logout';
        });

        _export_sbe_t1t.on('updated.t1t.player_wallet', function(e, wallet_info){
            $('.main-wallet .playerTotalBalance').html(_export_sbe_t1t.utils.displayCurrency(wallet_info['main_wallet']['balance']));
            $('.game-wallet .playerTotalBalance').html(_export_sbe_t1t.utils.displayCurrency(wallet_info['game_total']));

            $('.refreshBalanceButton').removeClass('disabled').prop('disabled', false);
            $('.transferAllToMainBtn').removeClass('disabled').prop('disabled', false);
        });

        $('.refreshBalanceButton').on('click', function(){
            $('.refreshBalanceButton').addClass('disabled').prop('disabled', true);
            $('.transferAllToMainBtn').addClass('disabled').prop('disabled', true);

            _export_sbe_t1t.player_wallet.refreshPlayerBalance();
        });

        $('.transferAllToMainBtn').on('click', function(){
            $('.refreshBalanceButton').addClass('disabled').prop('disabled', true);
            $('.transferAllToMainBtn').addClass('disabled').prop('disabled', true);

            Loader.show();

            _export_sbe_t1t.player_wallet.transferAllBalance(_export_sbe_t1t.variables.main_wallet_id, function(){
                Loader.hide();

                $('.refreshBalanceButton').removeClass('disabled').prop('disabled', false);
                $('.transferAllToMainBtn').removeClass('disabled').prop('disabled', false);
            });
        });

        <?php if($this->utils->getConfig('enable_hide_show_username_player_center')) : ?>
        $( "#uname_hidden" ).click(function()
        {
            var player_uname_len = $("#player_uname").html().length;
            var player_uname = $("#player_uname").html();

            $("#player_uname").html(player_uname.substring(1,0) + createstars(player_uname_len-2) + player_uname.substring(player_uname_len-1,player_uname_len));
            $('#uname_hidden').hide();
            $('#uname_show').show();
        });

        $( "#uname_show" ).click(function()
        {
            var player_uname_len = $("#player_uname").html().length;
            var player_uname = $("#hidden_uname").html();


            $("#player_uname").html(player_uname);
            $('#uname_show').hide();
            $('#uname_hidden').show();
        });
        <?php endif; ?>
    });
</script>

