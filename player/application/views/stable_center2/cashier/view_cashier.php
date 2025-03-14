<div class="container dashboar-container hidden" data-view="stable_center2/cashier/view_cashier">
    <?php $this->utils->startEvent('Load overview'); ?>
    <?php include __DIR__ . '/../includes/overview.php'; ?>
    <?php $this->utils->endEvent('Load overview'); ?>

    <div class="member-center row">
        <div class="col-md-3 mc-ul navigation-menu">
            <?php include VIEWPATH . '/resources/common/components/player_center_navigation.php'; ?>
        </div>
        <div class="col-md-9 tab-content mc-content">
            <?php include __DIR__ . '/../includes/dashboard/member_center.php'; ?>
            <?php include __DIR__ . '/../includes/dashboard/account_information.php'; ?>
            <?php
                if ($this->utils->getConfig('custom_shop_ui') == 'smash') {
                    include __DIR__ . '/../includes/dashboard/smash/shop.php';
                } else {
                    include __DIR__ . '/../includes/dashboard/shop.php';
                }

            ?>
            <?php include __DIR__ . '/../includes/dashboard/vip_rewards.php'; ?>
            <?php if($this->utils->isEnabledFeature('enabled_favorites_and_rencently_played_games')) : ?>
                <?php include __DIR__ . '/../includes/dashboard/favorite_games.php'; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if (!$this->utils->isEnabledFeature('hidden_player_first_login_welcome_popup')) :?>
<!-- VIP Group Modal -->
<?php if(count($vipList) > 0) : ?>
    <div class="modal fade " id="vip-group-modal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
        <div class="modal-dialog  modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="modal-title">
                        <h4><?php echo $operator_setting->main ?></h4>
                        <p><?php echo $operator_setting->sub ?></p>
                    </div>
                    <div class="row">
                        <?php
                        $x = 1;
                        foreach($vipList as $key => $vip):
                            $imageUrl = empty(trim($vip['image'])) ? $this->utils->imageUrl("vip_cover/default_vip_cover.jpeg") : $this->utils->imageUrl("vip_cover/".$vip['image']);
                            if($key == 4) break;
                            $btn_val = "b".$x;
                            $x++;
                            ?>
                            <div class="col-xs-12 col-sm-6 col-md-3">
                                <a href="javascript:void(0)" class="img_join <?= $vip['is_player_choose_vip'] ? '' : 'disabled'?>"
                                   data-id="<?php echo $vip['vipSettingId']?>"
                                   data-level="<?php echo $vip['level'] ?>"
                                   data-gname="<?php echo $vip['groupName'] ?>"
                                   data-lname="<?php echo $vip['levelName'] ?>">
                                    <h4><?php echo $vip['groupName'] ?></h4>
                                    <img style="height: 100px;width: 400px;" src="<?php echo $imageUrl; ?>" alt=""/>
                                    <div class="tip-bottom">
                                        <?php echo ($vip['groupDescription']); ?>
                                    </div>
                                </a>
                                <a href="javascript:void(0)" class="btn btn_join <?= $vip['is_player_choose_vip'] ? '' : 'disabled'?>"
                                   data-id="<?php echo $vip['vipSettingId']?>"
                                   data-level="<?php echo $vip['level'] ?>"
                                   data-gname="<?php echo $vip['groupName'] ?>"
                                   data-lname="<?php echo $vip['levelName'] ?>"><?= $vip['groupName'] ?></a>
                            </div>
                        <?php endforeach?>
                    </div>
                </div>
                <div class="modal-footer text-left">
                    <button type="button" class="btn btn-default submit-btn" data-dismiss="modal"><?= lang('reg.53') ?></button>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>
<?php endif; ?>


<?php include VIEWPATH . '/resources/includes/custom_popup_register/join_priority_popup.php'; ?>
<?php if (!$is_registered_popup_success_done) : ?>
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
                    <?php if (!empty($player_first_login_page_button_setting)) : ?>
                        <?php foreach ($player_first_login_page_button_setting as $btn_type => $settings): ?>
                            <?php if ($btn_type == 'home_btn') : ?>
                                <div class="redirect-link">
                                    <span><a href="<?= $this->utils->getSystemUrl('www', '/') . "#main-content"; ?>"><?= lang($settings['lang_key']); ?></a></span>
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
                                <?php if($this->utils->isEnabledFeature('switch_to_player_center_promo_on_first_popup_after_register')){ ?>
                                    <div class="player-center-promo">
                                        <span><a href="/player_center2/promotion"><?= lang($settings['lang_key']); ?></a></span>
                                    </div>
                                <?php }else{?>
                                    <div class="promo-list">
                                        <?php $promoList = $this->utils->getPlayerPromo('firstLogin', $player['playerId']); ?>
                                        <?php foreach ($promoList as $_list) : ?>
                                            <span> <?= $_list['promoDescription']; ?> </span>
                                            <span><a href="<?=$this->utils->getSystemUrl('player')?>/player_center2/promotion"><?= lang($settings['lang_key']); ?></a></span><br/>
                                        <?php endforeach; ?>
                                    </div>
                                <?php }?>
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
                    <a href="javascript:void(0)" data-url="<?=$this->utils->getSystemUrl('player') . '/player_center/dashboard#accountInformation'?>" onclick="registered_popup_click(this);">
                        <img src="<?= $this->utils->getSystemUrl('www') . "/" . $this->utils->getConfig('registered_image_poup_path') ?>">
                    </a>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if (isset($enable_pop_up_verify_contact_number)&&$enable_pop_up_verify_contact_number&&isset($enable_pop_up_verify_contact_number_msg)&&!empty($enable_pop_up_verify_contact_number_msg)) :?>
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
                        <p><?= $enable_pop_up_verify_contact_number_msg ?></p>
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
<script>
    $(function () {
        $('[data-toggle="tooltip"]').tooltip()
    })

    $(function() {
        setTimeout(function(){
            $('.circle').each(function(index, el) {
                var num = $(this).find('span').text() * 3.6;
                if (num <= 180) {
                    $(this).find('.right').css('transform', "rotate(" + num + "deg)");
                } else {
                    $(this).find('.right').css('transform', "rotate(180deg)");
                    $(this).find('.left').css('transform', "rotate(" + (num - 180) + "deg)");
                };
            });
        }, 1500);
    });

    /* For VIP Rewards */
    $(".privilege-icon a").click(function(e) {
        $(".prm").removeClass("active")
        $(".mc").removeClass("active")
        $(".msg").removeClass("active")
        $(".fm").removeClass("active")
        $(".ai").removeClass("active")
        $(".scy").removeClass("active")
        $(".ah").removeClass("active")
        $(".shp").removeClass("active")
        $(".vipr").removeClass("active")
        $(".reff").removeClass("active")
        $(".favg").removeClass("active")
        $(".rsg").removeClass("active")
        $("."+$(e.target).attr("target")).addClass("active")
    });

    /* For Completion Icon */
    $(".tips a").click(function(e) {
        $(".prm").removeClass("active")
        $(".mc").removeClass("active")
        $(".msg").removeClass("active")
        $(".fm").removeClass("active")
        $(".ai").removeClass("active")
        $(".scy").addClass("active")
        $(".ah").removeClass("active")
        $(".shp").removeClass("active")
        $(".vipr").removeClass("active")
        $(".reff").removeClass("active")
        $(".favg").removeClass("active")
    });

    /* For Fund Management Section */
    $('input[name="withdrawAmount"]').change(function(){
        if ($(this).val())
            {
                $(".depositBtn").removeClass('disabled');
            }else{
               $(".depositBtn").addClass('disabled');
            }
    });

    $(".show-btn").click(
    function(){
       $("#fm-deposit .bank-list").animate( { height:"115px" }, { queue:false, duration:300 });
    },
    function(){
       $("#fm-deposit .bank-list").animate( { height:"325px" }, { queue:false, duration:300 });
       $(".show-btn").hide();
       $(".hide-btn").show();
    });

    $(".hide-btn").click(
    function(){
       $("#fm-deposit .bank-list").animate( { height:"325px" }, { queue:false, duration:300 });
    },
    function(){
       $("#fm-deposit .bank-list").animate( { height:"115px" }, { queue:false, duration:300 });
       $(".hide-btn").hide();
       $(".show-btn").show();
    });

    function registered_popup_click(e){
        var url = $(e).data('url');
        $("#registered-modal").modal('hide');
        location.href = url;
    }
</script>
<?php
    $joinVipUrl = $this->utils->getServerProtocol(). "://" . $this->utils->getSystemHost('player') . "/player_center/joinVip";
    $updateLoginInfo = $this->utils->getServerProtocol(). "://" . $this->utils->getSystemHost('player') . "/player_center/updateLoginInfo/";
?>
<script type="text/javascript">
    RegisteredAutoPlayProcedure.is_tutorial_done = <?php echo ($is_tutorial_done ); ?>;
    RegisteredAutoPlayProcedure.playerId = <?php echo $player['playerId']?>;
    RegisteredAutoPlayProcedure.joinVipUrl = <?php echo json_encode($joinVipUrl); ?>;
    RegisteredAutoPlayProcedure.vip_count = <?php echo count($vipList); ?>;
    RegisteredAutoPlayProcedure.is_vip_show_done = <?php echo ($is_vip_show_done); ?>;
    RegisteredAutoPlayProcedure.is_registered_popup_success_done = <?php echo ($is_registered_popup_success_done ); ?>;
    RegisteredAutoPlayProcedure.enable_registered_show_popup = <?= ($this->utils->isEnabledFeature('enable_registered_show_success_popup')) ? 1 : 0 ?>;
    RegisteredAutoPlayProcedure.is_join_show_done =  <?= json_encode($is_join_show_done)?>;
    RegisteredAutoPlayProcedure.hide_registered_modal = <?= empty( $this->utils->getConfig('hide_registered_modal') )? 0: 1 ?>;

    RegisteredAutoPlayProcedure.execute();

</script>
<script type="text/javascript">
    var glob = {
        subwallet_stat: <?= json_encode($this->utils->array_select_fields($subwallet, [ 'typeId', 'maintenance_mode' ])) ?> ,
        show_tag_for_unavailable_deposit_accounts: <?= $show_tag_for_unavailable_deposit_accounts ?> ,
        disable_account_transfer_when_balance_check_fails: <?= $disable_account_transfer_when_balance_check_fails ?> ,
        tag_unavailable: ' (<?= lang('Unavailable') ?>)' ,
        tag_maintenance: ' (<?= lang('Under Maintenance') ?>)'
    };
</script>