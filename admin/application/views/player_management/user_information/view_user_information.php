<div class="col-md-12 player-infomations">
    <h3 class="player-title" data-username_case_insensitive="<?=$username_case_insensitive?>" data-username_on_register="<?=$username_on_register?>"><i class="fa fa-user"></i> <?= empty($username_case_insensitive)? $username_on_register: $player['username']?> <?=$player_is_deleted? '<span style="color:#D1374A">('.lang('Deleted').')</span>':''?></h3>

    <!-- Action -->
    <div class="row" style="margin:6px;">
        <fieldset style="padding:6px;">
            <legend>
                <h4><b><?=lang('player.ap01');?></b></h4>
            </legend>
            <div class="col-md-5 actions-left">
                <?php if ($this->permissions->checkPermissions('lock_player')) {?>
                    <?php $playerStatus = $this->utils->getPlayerStatus($player['playerId']); ?>
                    <?php if ($playerStatus == 0) {?>
                        <a href="javascript:void(0);"
                            data-player_id="<?=$player['playerId']?>"
                            data-toggle="tooltip"
                            data-original-title="<?=lang('role.25')?>"
                            class="btn btn-sm btn-danger block blockAction"
                        >
                            <i class="fa fa-lock"></i>
                            <?=lang('tool.pm08')?>
                        </a>

                        <div class="modal fade in" id="block_player" tabindex="-1" role="dialog" aria-labelledby="label_block_player">
                            <div class="modal-dialog" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                        <h4 class="modal-title" id="label_block_player"></h4>
                                    </div>
                                    <div class="modal-body"></div>
                                    <div class="modal-footer"></div>
                                </div>
                            </div>
                        </div> <!-- EOF #block_player -->

                    <?php } elseif ($playerStatus == Player_model::BLOCK_STATUS) {?>
                        <a href="/player_management/unblockPlayer/<?=$player['playerId']?>"
                            class="btn btn-scooter btn-sm unblock blockAction"
                            onclick="return confirm('<?=$unblockPlayerPrompt?>');"
                        >
                            <i class="fa fa-unlock-alt"></i>
                            <?=lang('tool.pm09')?>
                        </a>
                    <?php } elseif ($playerStatus == Player_model::SUSPENDED_STATUS) { ?>
                        <a href="/player_management/unblockPlayer/<?=$player['playerId']?>"
                            class="btn btn-scooter btn-sm unblock blockAction"
                            onclick="return confirm('<?=$unblockPlayerPrompt?>');"
                        >
                            <i class="fa fa-unlock-alt"></i>
                            <?=lang('tool.unsuspended')?>
                        </a>
                    <?php } elseif ($playerStatus == Player_model::STATUS_BLOCKED_FAILED_LOGIN_ATTEMPT) { ?>
                        <a href="/player_management/unblockPlayer/<?=$player['playerId']?>"
                            class="btn btn-scooter btn-sm unblock blockAction"
                            onclick="return confirm('<?=$unblockPlayerPrompt?>');"
                        >
                            <i class="fa fa-unlock-alt"></i>
                            <?=lang('Reset Login Attempt')?>
                        </a>
                    <?php } ?>
                <?php }?>

                <?php if ($this->permissions->checkPermissions('disable_cashback')) {
                    if ($player['disabled_cashback'] == 1) {?>
                        <a href="<?=site_url('player_management/set_cashback_status/' . $player['playerId'] . '/enable')?>" class="btn btn-scooter btn-sm">
                            <i class="fa fa-thumbs-up"></i> <?=lang('Enable Cashback')?>
                        </a>
                    <?php } else {?>
                        <a href="<?=site_url('player_management/set_cashback_status/' . $player['playerId'] . '/disable')?>" class="btn btn-danger btn-sm">
                            <i class="fa fa-thumbs-down"></i> <?=lang('Disable Cashback')?>
                        </a>
                    <?php
                    }
                } ?>

                <?php if ($this->permissions->checkPermissions('disable_promotion')) {
                    if ($player['disabled_promotion'] == 1) {?>
                        <a href="<?=site_url('player_management/set_promotion_status/' . $player['playerId'] . '/enable')?>" class="btn btn-scooter btn-sm">
                            <i class="fa fa-thumbs-up"></i> <?=lang('Enable Promotion')?>
                        </a>
                    <?php } else {?>
                        <a href="<?=site_url('player_management/set_promotion_status/' . $player['playerId'] . '/disable')?>" class="btn btn-danger btn-sm">
                            <i class="fa fa-thumbs-down"></i> <?=lang('Disable Promotion')?>
                        </a>
                    <?php
                    }
                } ?>

                <?php if ($this->permissions->checkPermissions('disable_player_withdrawal')) {
                    if ($player['enabled_withdrawal'] == 1) { ?>
                        <a href="javascript:void(0);" class="btn btn-danger btn-sm" onclick="setPlayerWithdrawalStatus(<?=$player['playerId']?>, 'disable_player_withdrawal', '<?=lang('Disable Withdraw')?>', 'disable', 'manual')">
                            <i class="fa fa-thumbs-down"></i>
                            <?=lang('Disable Player Withdrawal')?>
                        </a>

                        <div class="modal fade in" id="disable_player_withdrawal" tabindex="-1" role="dialog" aria-labelledby="label_disable_player_withdrawal">
                            <div class="modal-dialog" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                        <h4 class="modal-title" id="label_disable_player_withdrawal"></h4>
                                    </div>
                                    <div class="modal-body"></div>
                                    <div class="modal-footer"></div>
                                </div>
                            </div>
                        </div>
                    <?php } else { ?>
                        <a href="javascript:void(0);" class="btn btn-scooter btn-sm" onclick="setPlayerWithdrawalStatus(<?=$player['playerId']?>, 'enable_player_withdrawal', '<?=lang('Enable Withdraw')?>', 'enable', 'manual')">
                        <i class="fa fa-thumbs-up"></i>
                            <?=lang('Enable Player Withdrawal')?>
                        </a>

                        <div class="modal fade in" id="enable_player_withdrawal" tabindex="-1" role="dialog" aria-labelledby="label_enable_player_withdrawal">
                            <div class="modal-dialog" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                        <h4 class="modal-title" id="label_enable_player_withdrawal"></h4>
                                    </div>
                                    <div class="modal-body"></div>
                                    <div class="modal-footer"></div>
                                </div>
                            </div>
                        </div>
                    <?php
                    }
                } ?>

                <?php if ($this->permissions->checkPermissions('telesales_call') && $this->utils->getConfig('show_telesales_call_on_user_info')) { ?>
                    <a href="javascript:void(0);" class="btn btn-danger btn-sm" class="btn btn-primary btn-sm" onclick='makeTeleCall("<?=site_url('/api/call_player_tele/' . $player['playerId'])?>")'>
                        <i class="fa fa-phone"></i> <span class="hidden-xs"><?=lang('Telesales Call')?></span>
                    </a>
                <?php } ?>

                <?php if ($this->utils->getConfig('enabled_priority_player_features')) {
                    if ( empty($is_priority) ) {?>
                        <a href="<?=site_url('player_management/set_is_priority/' . $player['playerId'] . '/enable')?>" class="btn btn-scooter btn-sm">
                            <i class="fa fa-thumbs-up"></i> <?=lang('Enable Priority')?>
                        </a>
                    <?php } else {?>
                        <a href="<?=site_url('player_management/set_is_priority/' . $player['playerId'] . '/disable')?>" class="btn btn-danger btn-sm">
                            <i class="fa fa-thumbs-down"></i> <?=lang('Disable Priority')?>
                        </a>
                    <?php
                    }
                } ?>

            </div>
            <div class="col-md-7">
                <?php if ($this->utils->isEnabledFeature('show_c6_authentication') && $this->utils->isEnabledFeature('enable_c6_acuris_api_authentication') && $this->permissions->checkPermissions('show_c6_authentication')) {?>
                    <a href="javascript:void(0)" onclick="modal('/player_management/player_c6_authentication/<?=$player['playerId']?>','<?=lang('C6 Authentication')?>')" class="btn btn-portage btn-sm">
                        <i class="fa fa-star"></i> <span class="hidden-xs"><?=lang('C6 Authentication')?></span>
                    </a>
                <?php }?>

                <?php if ($this->utils->isEnabledFeature('show_pep_authentication') && $this->utils->isEnabledFeature('enable_pep_gbg_api_authentication') && $this->permissions->checkPermissions('show_pep_authentication')) {?>
                    <a href="javascript:void(0)" onclick="modal('/player_management/player_pep_authentication/<?=$player['playerId']?>','<?=lang('PEP Authentication')?>')" class="btn btn-portage btn-sm">
                        <i class="fa fa-star"></i> <span class="hidden-xs"><?=lang('PEP Authentication')?></span>
                    </a>
                <?php }?>

<!--                 <?php if ($this->utils->isEnabledFeature('enable_show_trigger_XinyanApi_validation_btn')) { ?>
                    <a href="<?=site_url('player_management/triggerRegisterEventForXinyanApi/' . $player['playerId'])?>"  class="btn btn-portage btn-sm" id="xinyan_api_btn">
                        <i class="fa fa-caret-square-o-up"></i> <span class="hidden-xs"><?=lang('Trigger Xinyan Validation')?></span>
                    </a>
                    <script> //get XinyanApi status and enable or disabled btn
                        $(document).ready(function() {
                            $.post('/player_management/getXinyanApiStatus/'+playerId, function(data){
                                if(data.success) {
                                    if(data.status == '1' || data.status == '4'){
                                        $("#xinyan_api_btn").attr("disabled", true);
                                    }else{
                                        $("#xinyan_api_btn").attr("disabled", false);
                                    }
                                } else {
                                    $("#xinyan_api_btn").attr("disabled", false);
                                }
                            },"json");
                        }); //end
                    </script>
                <?php } ?> -->

                <?php if ($this->permissions->checkPermissions('player_notes')) {?>
                    <button type="button" class="btn btn-portage btn-sm" onclick="player_notes(<?=$player['playerId']?>)">
                        <i class="fa fa-sticky-note"></i> <?=lang('Player Remarks')?>
                    </button>

                    <div class="modal fade in" id="player_notes" tabindex="-1" role="dialog" aria-labelledby="label_player_notes">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                    <h4 class="modal-title" id="label_player_notes"></h4>
                                </div>
                                <div class="modal-body"></div>
                            </div>
                        </div>
                    </div>
                    <script>
                        function player_notes(player_id) {
                            var dst_url = "/player_management/player_notes/" + player_id;
                            open_modal('player_notes', dst_url, "<?=lang('Player Remarks'); ?>");
                        }
                    </script>
                <?php }?>

                <?php if ($this->utils->isEnabledFeature('show_upload_documents') && $this->permissions->checkPermissions('kyc_attached_documents')) {?>
                    <a href="javascript:void(0)" onclick="modal('/player_management/player_attach_document/<?=$player['playerId']?>','<?=lang('Attached Document')?>')" class="btn btn-portage btn-sm">
                        <i class="fa fa-folder-open"></i> <span class="hidden-xs"><?=lang('user_info.kyc_attachment')?></span>
                    </a>
                <?php }?>

                <?php if ($this->utils->isEnabledFeature('show_player_deposit_withdrawal_achieve_threshold') && $this->permissions->checkPermissions('show_player_deposit_withdrawal_achieve_threshold')) {?>
                    <a href="javascript:void(0)" onclick="modal('/player_management/view_dw_achieve_threshold/<?=$player['playerId']?>','<?=lang('D/W Achieve Threshold')?>')" class="btn btn-portage btn-sm">
                        <i class="fa fa-star cc_pointer"></i> <span class="hidden-xs"><?=lang('sys.achieve.threshold.title')?></span>
                    </a>
                <?php }?>

                <?php if ($this->utils->isEnabledMDB() && $this->utils->getConfig('enable_userInformation_sync_player_to_mdb')) { ?>
                    <a href="<?=site_url('/player_management/sync_player_to_mdb/' . $player['playerId'])?>" class="btn btn-portage btn-sm">
                        <i class="fa fa-refresh"></i> <?=lang('Sync To Currency')?>
                    </a>
                <?php } ?>
            </div>
            <div class="col-md-12 actions-bottom">
                <?php if ($this->permissions->checkPermissions('new_deposit')): ?>
                    <a href="<?=site_url('payment_management/newDeposit')?>" class="btn btn-scooter btn-sm">
                        <i class="fa fa-plus"></i> <?=lang('lang.newDeposit')?>
                    </a>
                <?php endif; ?>
                <?php if ($this->permissions->checkPermissions('new_withdrawal')): ?>
                    <a href="<?=site_url('payment_management/newWithdrawal')?>" class="btn btn-scooter btn-sm">
                        <i class="fa fa-plus"></i> <?=lang('lang.newWithdrawal')?>
                    </a>
                <?php endif; ?>
                <?php if ($this->permissions->checkPermissions('manually_add_bonus')): ?>
                    <a href="/marketing_management/manually_add_bonus/<?=$player['playerId']?>" class="btn btn-scooter btn-sm">
                        <i class="fa fa-star"></i> <?=lang('transaction.transaction.type.' . Transactions::ADD_BONUS)?>
                    </a>
                <?php endif; ?>
            </div>
        </fieldset>
        <br/>
    </div>

    <!-- Status -->
    <div class="row player-status" style="margin:6px;">
        <fieldset style="padding:6px;">
            <legend>
                <h4><b><?=lang('Status');?></b></h4>
            </legend>

            <div class='col-md-2'>
                <p><?=lang('viewuser.03');?> :
                    <?php if ($player['online']):?>
                        <?=lang('icon.online')?>
                    <?php else:?>
                        <?=lang('icon.offline')?>
                    <?php endif;?>
                </p>
            </div>

            <div class='col-md-2'>
                <?php
                    switch( $this->utils->getPlayerStatus($player['playerId'])){
                        case 0:
                            $statusTag = '<span class="text-success">' .lang('status.normal').'</span>';
                            break;
                        case Player_model::BLOCK_STATUS:
                            $statusTag = '<span class="text-danger" data-blocked_until="'. (empty($player['_blockedUntilDate'])? $player['blockedUntil']: $player['_blockedUntilDate']). '">' .lang('Blocked').'</span>';
                            break;
                        case Player_model::SUSPENDED_STATUS:
                            $statusTag = '<span class="text-danger">' .lang('Suspended').'</span>';
                            break;
                        case Player_model::SELFEXCLUSION_STATUS:
                            $statusTag = '<span class="text-muted">' .lang('Self Exclusion').'</span>';
                            break;
                        case Player_model::STATUS_BLOCKED_FAILED_LOGIN_ATTEMPT:
                            $statusTag = '<span class="text-danger">' .lang('Failed Login Attempt').'</span>';
                            break;
                    }
                ?>
                <p><?=lang('lang.accountstatus');?> : <?=$statusTag?> </p>
            </div>

            <div class='col-md-2'>
                <p><?=lang('Cashback'); ?>:
                    <?php echo $player['disabled_cashback'] == 0 ? '<span class="text-success">' . lang('Enabled') . '</span>' : '<span class="text-danger">' . lang('Disabled') . '</span>'; ?>
                </p>
            </div>

            <div class='col-md-2'>
                <p><?=lang('Promotion'); ?>:
                    <?php echo $player['disabled_promotion'] == 0 ? '<span class="text-success">' . lang('Enabled') . '</span>' : '<span class="text-danger">' . lang('Disabled') . '</span>'; ?>
                </p>
            </div>

            <?php if (!empty($player['agent_id']) && $player['credit_mode'] && $this->utils->isEnabledFeature('agent_player_cannot_use_deposit_withdraw') && $this->utils->getConfig('enabledCreditMode')): ?>
            <div class='col-md-2'>
                <p><?=lang('Credit Mode'); ?>:
                    <?php echo $player['credit_mode'] != 0 ? '<span class="text-success">' . lang('On') . '</span>' : '<span class="text-danger">' . lang('Off') . '</span>'; ?>
                </p>
            </div>
            <?php else:?>
            <div class='col-md-2'>
                <p><?=lang('Withdrawal Status'); ?>:
                    <?php echo $player['enabled_withdrawal'] != 0 ? '<span class="text-success">' . lang('Enabled') . '</span>' : '<span class="text-danger">' . lang('Disabled') . '</span>'; ?>
                </p>
            </div>
            <?php endif;?>

            <?php if ($this->utils->isEnabledFeature('show_pep_status') && $this->utils->isEnabledFeature('show_risk_score')) {?>
                <div class='col-md-2'>
                    <p><?=lang('PEP'); ?>:
                        <a href="javascript:void(0);" onclick="modal('/player_management/player_pep/<?=$player['playerId']?>','<?=lang('player PEP')?>');">
                            <span class="text-success" id="pep_status"><?=lang(str_replace('%20', ' ', $pep_status))?></span>
                        </a>
                    </p>
                </div>
            <?php }?>

            <?php if ($this->utils->isEnabledFeature('show_c6_status') && $this->utils->isEnabledFeature('show_risk_score')) {?>
                <div class='col-md-2'>
                    <p><?=lang('C6'); ?>:
                        <a href="javascript:void(0);" onclick="modal('/player_management/player_c6/<?=$player['playerId']?>','<?=lang('Player C6')?>');">
                            <span class="text-success" id="c6_status"><?=lang(str_replace('%20', ' ', $c6_status))?></span>
                        </a>
                    </p>
                </div>
            <?php }?>

            <?php if ($this->utils->isEnabledFeature('show_risk_score')) {?>
                <div class='col-md-2'>
                    <p><?=lang('risk score'); ?>:
                        <a href="javascript:void(0);" onclick="modal('/player_management/player_risk_score/<?=$player['playerId']?>','<?=lang('risk score')?>');">
                            <span class="text-success" id="risk_score"><?=$risk_level?> / <?=$risk_score?></span>
                        </a>
                    </p>
                </div>
            <?php }?>

            <?php if ($this->utils->isEnabledFeature('show_kyc_status')) {?>
                <div class='col-md-2'>
                    <p><?=lang('KYC Score'); ?>:
                        <a href="javascript:void(0);" onclick="modal('/player_management/player_kyc/<?=$player['playerId']?>','<?=lang('player kyc')?>');">
                            <span class="text-success" id="kyc_status"><?=lang($kyc_level)?> / <?=lang($kyc_status)?></span>
                        </a>
                    </p>
                </div>
            <?php }?>

            <?php if ($this->utils->isEnabledFeature('show_allowed_withdrawal_status') && $this->utils->isEnabledFeature('show_risk_score') && $this->utils->isEnabledFeature('show_kyc_status')) {?>
                <div class='col-md-2'>
                    <p><?=lang('KYC Withdrawal Status'); ?>:
                        <a href="javascript:void(0);" onclick="modal('/player_management/player_allowed_withdrawal_status/<?=$player['playerId']?>','<?=lang('KYC Withdrawal Status')?>');">
                            <span class="text-success" id="allowed_withdrawal_status"><?=$allowed_withdrawal_status?></span>
                        </a>
                    </p>
                </div>
            <?php }?>

            <?php if ( $this->utils->getConfig('enabled_priority_player_features') ) {?>
                <div class='col-md-2'>
                    <p><?=lang('Priority Status'); ?>:
                        <?php echo !empty($is_priority)? '<span class="text-success">' . lang('Enabled') . '</span>' : '<span class="text-danger">' . lang('Disabled') . '</span>'; ?>
                    </p>
                </div>
            <?php } ?>

        </fieldset>
    </div>

    <div class="info-tab">
        <ul class="nav nav-tabs" role="tablist">
            <li id="infotab_1"> <!-- signup_info -->
                <a href="#" onclick="changeUserInfoTab(1);" data-toggle="tab"><?=lang('userinfo.tab01');?></a>
            </li>
            <?php if ($this->permissions->checkPermissions('player_basic_info')): ?>
                <li id="infotab_2"> <!-- basic_info -->
                    <a href="#" onclick="changeUserInfoTab(2);" data-toggle="tab"><?=lang('userinfo.tab02');?></a>
                </li>
            <?php endif;?>
            <?php if(false && $this->utils->isEnabledFeature('show_upload_documents') && $this->permissions->checkPermissions('kyc_attached_documents')) :?>
                <li id="infotab_3"> <!-- kyc_attachment -->
                    <a href="#" onclick="changeUserInfoTab(3);" data-toggle="tab"><?=lang('userinfo.tab03');?></a>
                </li>
            <?php endif;?>
            <?php if($this->utils->isEnabledFeature('responsible_gaming') && $this->permissions->checkPermissions('responsible_gaming_info')) :?>
                <li id="infotab_4"> <!-- responsible_gaming -->
                    <a href="#" onclick="changeUserInfoTab(4);" data-toggle="tab"><?=lang('userinfo.tab04');?></a>
                </li>
            <?php endif;?>
            <?php if($this->utils->getConfig('use_financial_acc_info_show_permission')):?>
                <?php if($this->permissions->checkPermissions('financial_acc_info_show')==false):?>
                    <!-- hide financial_acc_info_show' -->
                <?php else:?>
                    <li id="infotab_5"> <!-- fin_info -->
                        <a href="#" onclick="changeUserInfoTab(5);" data-toggle="tab"><?=lang('userinfo.tab05');?></a>
                    </li>
                <?php endif?>
            <?php else:?>
                <li id="infotab_5"> <!-- fin_info -->
                    <a href="#" onclick="changeUserInfoTab(5);" data-toggle="tab"><?=lang('userinfo.tab05');?></a>
                </li>
            <?php endif;?>
            <li id="infotab_6"> <!-- withdrawal_condition -->
                <a href="#" onclick="changeUserInfoTab(6);" data-toggle="tab"><?=lang('userinfo.tab06');?></a>
            </li>
            <?php if ($this->utils->isEnabledFeature('enabled_transfer_condition')): ?>
                <li id="infotab_7"> <!-- transfer_condition -->
                    <a href="#" onclick="changeUserInfoTab(7);" data-toggle="tab"><?=lang('userinfo.tab07');?></a>
                </li>
            <?php endif;?>
            <li id="infotab_8"> <!-- account_info -->
                <a href="#" onclick="changeUserInfoTab(8);" data-toggle="tab"><?=lang('userinfo.tab08');?></a>
            </li>
            <li id="infotab_9"> <!-- game_info -->
                <a href="#" onclick="changeUserInfoTab(9);" data-toggle="tab"><?=lang('userinfo.tab09');?></a>
            </li>
            <?php if($this->utils->getConfig('enabled_crypto_currency_wallet')) :?>
                <li id="infotab_11"> <!-- crypto_wallet_info -->
                    <a href="#" onclick="changeUserInfoTab(11);" data-toggle="tab"><?=lang('userinfo.tab11');?></a>
                </li>
            <?php endif; ?>
        </ul>
        <div class="tab-content" id="nav_content"></div>
        <fieldset class="tab-content" id="playersLog1">
            <legend>
                <h4><b><?=lang('userinfo.tab10');?></b></h4>
            </legend>
            <?php include VIEWPATH . '/player_management/user_information/ajax_player_log.php'; ?>
        </fieldset>
    </div>
</div>
<div class="modal fade" id="check_mg_modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle"
        aria-hidden="true" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <form class="" action="<?=base_url('/player_management/post_check_mg_livedealer_data')?>" method="post" accept-charset="utf-8" enctype="multipart/form-data">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title" id="myModalLabel"><?=lang('Check MG Live Dealer Data');?></h4>
                </div>
                <div class="modal-body">
                    <div class="form-inline">
                        <input type="text" id="mg_search_range" class="form-control input-sm dateInput inline" data-start="#mg_dateRangeValueStart" data-end="#mg_dateRangeValueEnd" data-time="true"/>
                        <input type="hidden" id="mg_dateRangeValueStart" name="mg_dateRangeValueStart" value="<?php echo date('Y-m-d 00:00:00'); ?>" />
                        <input type="hidden" id="mg_dateRangeValueEnd" name="mg_dateRangeValueEnd" value="<?php echo date('Y-m-d 23:59:59'); ?>" />
                        <input type="hidden" id="mg_playerid" name="mg_playerid" value="<?=$player['playerId']?>" />
                        <input type="submit" class="btn btn-portage btn-sm" id="btn-submit" value="<?=lang('Submit');?>"/>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
<?php include VIEWPATH . '/marketing_management/promorules/review_haba_api_results_list.php'; ?>
<?php include VIEWPATH . '/player_management/user_information/modals.php'; ?>
<script type="text/javascript">
    var playerId = '<?=$player['playerId']?>';
    var currentDate = moment('<?=$current_php_datetime?>');
    var disabledPlayerWithdrawlUntil = moment('<?=$player_preference['disabled_withdrawal_until']?>');
    var disabledPlayerWithdrawlUntil_milliseconds = Math.max(Math.round(((disabledPlayerWithdrawlUntil - currentDate)/1000)) * 1000, 0);

    if(isNaN(disabledPlayerWithdrawlUntil)) {
        console.log('Player Withdrawal Enabled');
    }else{
        console.log('disabled_withdrawal_until', '<?=$player_preference['disabled_withdrawal_until']?>');
        console.log(disabledPlayerWithdrawlUntil_milliseconds/1000, 'seconds');
    }

    timeout = setTimeout(function() { location.reload(true); }, disabledPlayerWithdrawlUntil_milliseconds);

    if(disabledPlayerWithdrawlUntil_milliseconds == '' || isNaN(disabledPlayerWithdrawlUntil_milliseconds)) {
        clearTimeout(timeout);
    }

    $(document).ready(function() {
        changeUserInfoTab();

        var theOption = {};
        theOption.defaultItemsPerPage = <?=$this->utils->getDefaultItemsPerPage()?>;
        theOption.uri4checkHabaResultsByPlayerPromoIds = "<?=lang('/api/checkHabaResultsByPlayerPromoIds')?>";
        appendHabaResults.initEvents(theOption);

        var options = {};
        options.playerId = playerId;
        options.uri = {};
        options.uri.kickPlayer = '<?=site_url('player_management/kickPlayer/'. $player['playerId'])?>';
        options.uri.manuallyDowngradeLevel = '<?=site_url('player_management/ajaxManuallyDowngradeLevel/'. $player['playerId'])?>';
        options.uri.manuallyUpgradeLevel = '<?=site_url('player_management/ajaxManuallyUpgradeLevel/'. $player['playerId'])?>';
        options.langs = {};
        options.langs.loading = '<?=lang('Loading')?>';
        options.langs.enabled = '<?=lang('enabled')?>';
        options.langs.disabled = '<?=lang('disabled')?>';
        options.langs.daily = '<?=lang('Daily')?>';
        options.langs.weekly = '<?=lang('Weekly')?>';
        options.langs.monthly = '<?=lang('Monthly')?>';
        options.langs.monday = '<?=lang('Monday')?>';
        options.langs.tuesday = '<?=lang('Tuesday')?>';
        options.langs.wednesday = '<?=lang('Wednesday')?>';
        options.langs.thursday = '<?=lang('Thursday')?>';
        options.langs.friday = '<?=lang('Friday')?>';
        options.langs.saturday = '<?=lang('Saturday')?>';
        options.langs.sunday = '<?=lang('Sunday')?>';
        options.langs.day = '<?=lang('day')?>';
        options.langs.week = '<?=lang('week')?>';
        options.langs.month = '<?=lang('month')?>';
        options.langs.na = '<?=lang('N/A')?>';
        options.langs.accumulation_prefix = '<?=lang('Total')?>';
        options.langs.bet_amount = '<?=lang('Bet Amount')?>';
        options.langs.deposit_amount = '<?=lang('Deposit Amount')?>';
        options.langs.win_amount = '<?=lang('Win Amount')?>';
        options.langs.loss_amount = '<?=lang('Loss Amount')?>';
        options.langs.upgrade_needs4prefix = '<?=lang('Upgrade Needs')?>';
        options.langs.current_vip4prefix = '<?=lang('Current VIP')?>';
        options.langs.skipped_level = '<?=lang('Skipped Level')?>';
        options.langs.upgrade_condition_met = '<?=lang('Upgrade Condition met')?>';
        options.langs.upgrade_condition_not_met = '<?=lang('Upgrade Condition not met')?>';
        // options.langs.upgrade_needs_bets_amount = '<?=lang('Upgrade Needs Bets Amount')?>';
        options.featurelist = {};
        options.featurelist.vip_level_maintain_settings = '<?=$this->utils->isEnabledFeature('vip_level_maintain_settings')?1:0?>';
        options.featurelist.disable_player_multiple_upgrade = '<?=$this->utils->isEnabledFeature('disable_player_multiple_upgrade')?1:0?>';
        options.configlist = {};
        options.configlist.enable_separate_accumulation_in_setting = '<?=
            // 0 = common accumulation
            // 1 = separate accumulation.
            $this->utils->getConfig('enable_separate_accumulation_in_setting')
        ?>';
        options.configlist.vip_setting_form_ver = '<?=
            // 1 = common(total) bet amount
            // 2 = separate bet amount by game tree.
            $this->utils->getConfig('vip_setting_form_ver')
        ?>';
        options.settings = {};
        options.settings.DOWN_MAINTAIN_TIME_UNIT_DAY = <?=Group_level::DOWN_MAINTAIN_TIME_UNIT_DAY?>;
        options.settings.DOWN_MAINTAIN_TIME_UNIT_WEEK = <?=Group_level::DOWN_MAINTAIN_TIME_UNIT_WEEK?>;
        options.settings.DOWN_MAINTAIN_TIME_UNIT_MONTH = <?=Group_level::DOWN_MAINTAIN_TIME_UNIT_MONTH?>;
        var playerManagement = PlayerManagement.initial(options);

        // for confirm in TEST IN STG.
        $('.player-status legend').attr('notification_token', '<?=$notification_token?>');

        playerManagement.onReady();

    });

    function changeUserInfoTab(clickOn) {
        var tab = '1';
        var hash = window.location.hash.replace("#", "");
        if(clickOn) {
            tab = clickOn;
            window.location.hash = clickOn;
        } else if ($.isNumeric(hash)){
            tab = hash;
        }
        $('.info-tab li').removeClass('active');
        $('#infotab_' + tab).addClass('active');
        if(document.getElementsByClassName("disableMask").length == 0){

            $(".info-tab>.nav-tabs>li[id*='infotab_']" ).append('<div class="disableMask"></div>');
        }
        var url = "/player_management/changeUserInfoTab/"+ playerId + "/"+ tab;
        $('#nav_content').html('<center><img src="' + imgloader + '"></center>').load(url, null, function () {
                $('.disableMask').remove();
        });
    }

    function setPlayerWithdrawalStatus(playerId, modal_id, title, status, transmission) {
        var dst_url = "/player_management/set_withdrawal_status_by_options/" + playerId + "/load_player_withdrawal_view/" + status + "/" + transmission;
        open_modal(modal_id, dst_url, title);
    }
</script>
