<style>
	.title-css {
		margin-top: 0;
		padding-bottom: 10px;
		font-size: 16px
	}
    .dashboard-stat .details .desc{
        font-size: 12px
    }
    .dashboard-stat .details .number {
        font-size: 28px
    }
    .promoCmsSettingItem >a>label>input[type="radio"]{
        visibility: hidden;
    }
</style>
<?=$this->load->view("includes/popup_promorules_info")?>
<?php
$promotion_rules=$this->utils->getConfig('promotion_rules');

$disable_pre_application_on_release_bonus_first=$promotion_rules['disable_pre_application_on_release_bonus_first'];
?>
<form class="promo_req_list" id="search-form">
    <h4 class="title-css"><?php echo lang('cms.promoReqAppListQuickFilter'); ?></h4>
    <div class="row">
        <div class="col-md-3">
            <label style="display: block; cursor: pointer; margin-bottom: 0;">
                <input type="radio" name="status" value="<?php echo Player_promo::TRANS_STATUS_REQUEST; ?>" class="dwStatus hidden"
                    <?php echo $conditions['status'] == Player_promo::TRANS_STATUS_REQUEST ? 'checked="checked"' : '' ?> />
                <div class="dashboard-stat curiousblue panel_<?php echo Player_promo::TRANS_STATUS_REQUEST; ?> <?php echo $conditions['status'] == Player_promo::TRANS_STATUS_REQUEST ? 'checked' : '' ?>">
                    <div class="visual">
                        <i class="fa fa-square-o"></i>
                    </div>
                    <div class="details">
                        <div class="number">
                            <span class="badge_number <?=is_null($countAllStatus[Player_promo::TRANS_STATUS_REQUEST])?'hide': ''?>">
                                <?php echo number_format($countAllStatus[Player_promo::TRANS_STATUS_REQUEST]); ?>
                            </span>
                            <span class="badge_loading <?=is_null($countAllStatus[Player_promo::TRANS_STATUS_REQUEST])?'': 'hide'?>">
                                <i class="fa fa-spinner fa-pulse fa-fw"></i> <!--?=lang('Loading')?-->
                            </span>
                        </div>
                        <div class="desc"> <?=lang('promo.request_list.status.pending')?> </div>
                    </div>
                </div>
            </label>
        </div>
        <div class="col-md-3">
            <label style="display: block; cursor: pointer; margin-bottom: 0;">
                <input type="radio" name="status" value="<?php echo Player_promo::TRANS_STATUS_APPROVED; ?>" class="dwStatus hidden"
                    <?php echo $conditions['status'] == Player_promo::TRANS_STATUS_APPROVED ? 'checked="checked"' : ''?>  />
                <div class="dashboard-stat bermuda panel_<?php echo Player_promo::TRANS_STATUS_APPROVED; ?> <?php echo $conditions['status'] == Player_promo::TRANS_STATUS_APPROVED ? 'checked' : '' ?>">
                    <div class="visual">
                        <i class="fa fa-check-square-o"></i>
                    </div>
                    <div class="details">
                        <div class="number">
                            <span class="badge_number <?=is_null($countAllStatus[Player_promo::TRANS_STATUS_APPROVED])?'hide': ''?>">
                                <?php echo number_format($countAllStatus[Player_promo::TRANS_STATUS_APPROVED]); ?>
                            </span>
                            <span class="badge_loading <?=is_null($countAllStatus[Player_promo::TRANS_STATUS_APPROVED])?'': 'hide'?>">
                                <i class="fa fa-spinner fa-pulse fa-fw"></i> <!--?=lang('Loading')?-->
                            </span>
                        </div>
                        <div class="desc"> <?=lang('promo.request_list.status.release')?> </div>
                    </div>
                </div>
            </label>
        </div>
        <div class="col-md-3">
            <label style="display: block; cursor: pointer; margin-bottom: 0;">
                <input type="radio" name="status" value="<?php echo Player_promo::TRANS_STATUS_FINISHED_WITHDRAW_CONDITION; ?>" class="dwStatus hidden"
                    <?php echo $conditions['status'] == Player_promo::TRANS_STATUS_FINISHED_WITHDRAW_CONDITION ? 'checked="checked"' : '' ?> />
                <div class="dashboard-stat curiousblue panel_<?php echo Player_promo::TRANS_STATUS_FINISHED_WITHDRAW_CONDITION; ?> <?php echo $conditions['status'] == Player_promo::TRANS_STATUS_FINISHED_WITHDRAW_CONDITION ? 'checked' : '' ?>">
                    <div class="visual">
                        <i class="fa fa-check-square-o"></i>
                    </div>
                    <div class="details">
                        <div class="number">
                            <span class="badge_number <?=is_null($countAllStatus[Player_promo::TRANS_STATUS_FINISHED_WITHDRAW_CONDITION])?'hide': ''?>">
                                <?php echo number_format($countAllStatus[Player_promo::TRANS_STATUS_FINISHED_WITHDRAW_CONDITION]); ?>
                            </span>
                            <span class="badge_loading <?=is_null($countAllStatus[Player_promo::TRANS_STATUS_FINISHED_WITHDRAW_CONDITION])?'': 'hide'?>">
                                <i class="fa fa-spinner fa-pulse fa-fw"></i> <!--?=lang('Loading')?-->
                            </span>
                        </div>
                        <div class="desc"> <?php echo lang('promo.request_list.status.finished'); ?> </div>
                    </div>
                </div>
            </label> 
        </div>
        <div class="col-md-3">
            <label style="display: block; cursor: pointer; margin-bottom: 0;">            
                <input type="radio" name="status" value="<?php echo Player_promo::TRANS_STATUS_DECLINED; ?>" class="dwStatus hidden"
                    <?php echo $conditions['status'] == Player_promo::TRANS_STATUS_DECLINED ? 'checked="checked"' : '' ?> />
                <div class="dashboard-stat charm panel_<?php echo Player_promo::TRANS_STATUS_DECLINED; ?> <?php echo $conditions['status'] == Player_promo::TRANS_STATUS_DECLINED ? 'checked' : '' ?>">
                    <div class="visual">
                        <i class="fa fa-minus-square-o"></i>
                    </div>
                    <div class="details">
                        <div class="number">
                            <span class="badge_number <?=is_null($countAllStatus[Player_promo::TRANS_STATUS_DECLINED])?'hide': ''?>">
                                <?php echo number_format($countAllStatus[Player_promo::TRANS_STATUS_DECLINED]); ?>
                            </span>
                            <span class="badge_loading <?=is_null($countAllStatus[Player_promo::TRANS_STATUS_DECLINED])?'': 'hide'?>">
                                <i class="fa fa-spinner fa-pulse fa-fw"></i> <!--?=lang('Loading')?-->
                            </span>
                        </div>
                        <div class="desc"> <?=lang('promo.request_list.status.declined')?> </div>
                    </div>
                </div>
            </label>
        </div>
    </div><!-- row -->

    <div class="panel panel-primary">
        <div class="panel-heading">
            <h4 class="panel-title">
                <i class="fa fa-search"></i> <?=lang("lang.search")?>
            </h4>
        </div>
        <div class="panel-body">
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="control-label" for="search_transaction_date"><?=lang('Transaction Date')?>：</label>
                        <label class="radio-inline" for="transRequestTime"><input type="radio" id="transRequestTime" name="transactionDateType" value="0" <?=(!$conditions['transactionDateType'])?'checked="true"':''?>><?=lang('cms.dateApplyRequest')?></label>
                        <label class="radio-inline" for="transProcessTime"><input type="radio" id="transProcessTime" name="transactionDateType" value="1" <?=($conditions['transactionDateType'])?'checked="true"':''?>><?=lang('cms.dateProcessed')?></label>
                        <div class="input-group">
                            <input id="search_transaction_date" class="form-control input-sm dateInput" data-time="true" data-start="#request_date_from" data-end="#request_date_to"/>
                            <span class="input-group-addon input-sm">
                                <input type="checkbox" id="search_by_date" value="<?=$conditions['search_by_date']?>" <?=empty($conditions['search_by_date'])?'':'checked="true"';?>>
                                <input type="hidden" name="search_by_date" value="<?=$conditions['search_by_date']?>">
                            </span>
                            <input type="hidden" id="request_date_from" name="request_date_from" value="<?=$conditions['request_date_from'];?>" />
                            <input type="hidden" id="request_date_to" name="request_date_to" value="<?=$conditions['request_date_to'];?>" />
                        </div>


                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label class="control-label" for="player_promo_status"><?=lang('Status')?></label>
                        <select name="player_promo_status" id="player_promo_status" class="form-control input-sm">
                            <?php foreach ($allPlayerPromoStatus as $k => $v) :?>
                                <?php if($conditions['player_promo_status'] === $k): ?>
                                    <option class="select_option" selected value="<?=$k?>"><?=$v?></option>
                                <?php else:?>
                                    <option value="<?=$k?>"><?=$v?></option>
                                <?php endif; ?>
                            <?php endforeach;?>
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label class="control-label" for="username"><?=lang('pay.username')?></label>
                        <input id="username" type="text" name="username" class="form-control input-sm" value="<?=$conditions['username'];?>" />
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="promoCmsSettingId" class="control-label"><?=lang('cms.promotitle')?></label>
                        <select name="promoCmsSettingId" id="promoCmsSettingId" class="form-control input-sm">
                            <option value=""><?=lang('All')?></option>
                            <?php foreach ($promoList as $promo) :?>
                                <?php if($conditions['promoCmsSettingId'] == $promo['promoCmsSettingId']): ?>
                                    <option selected value="<?=$promo['promoCmsSettingId']?>"><?=$promo['promoName']?></option>
                                <?php else:?>
                                    <option value="<?=$promo['promoCmsSettingId']?>"><?=$promo['promoName']?></option>
                                <?php endif; ?>
                            <?php endforeach;?>
                        </select>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-2">
                    <div class="form-group">
                        <label for="vipsettingcashbackruleId" class="control-label"><?=lang('player.07')?></label>
                        <select name="vipsettingcashbackruleId" id="vipsettingcashbackruleId" class="form-control input-sm">
                            <option value=""><?=lang('player.08')?></option>
                            <?php foreach ($allLevels as $level) :?>
                                <?php if($conditions['vipsettingcashbackruleId'] == $level['vipsettingcashbackruleId']): ?>
                                    <option selected value="<?=$level['vipsettingcashbackruleId']?>"><?=lang($level['groupName']) . ' - ' . lang($level['vipLevelName'])?></option>
                                <?php else:?>
                                    <option value="<?=$level['vipsettingcashbackruleId']?>"><?=lang($level['groupName']) . ' - ' . lang($level['vipLevelName'])?></option>
                                <?php endif; ?>
                            <?php endforeach;?>
                        </select>
                    </div>
                </div>
                <div class="col-md-6"></div>
                <?php if ($this->utils->getConfig('only_show_active_promotion')) : ?>
                    <div class="col-md-4">
                        <label for="only_show_active_promotion">
                            <input type="hidden" name="only_show_active_promotion" value="<?=$conditions['only_show_active_promotion']?>">
                            <input class="checkbox_align promotion_management_status" type="checkbox" id="only_show_active_promotion"
                            <?= $conditions['only_show_active_promotion'] == 'active' ? 'checked' : '' ?> />
                            <?=lang('Only Show Active Promo Manager')?>
                        </label>
                    </div>
                <?php endif; ?>
                <?php //if($conditions['status'] != '0' ): ?>
<!--                    <div class="col-md-2">-->
<!--                        <div class="form-group">-->
<!--                            <label class="control-label" for="processed_by">--><?//=lang('pay.procssby')?><!--</label>-->
<!--                            <select class="form-control input-sm" name="processed_by" id="processed_by">-->
<!--                                <option value =""  >--><?//=lang("lang.selectall")?><!-- </option>-->
<!--                                --><?php //foreach($users as $u): ?>
<!--                                    <option value ="--><?php //echo $u['userId']?><!--" --><?php //echo $conditions['processed_by'] == $u['userId'] ? 'selected' : '' ?><!-- >--><?php //echo $u['username']?><!-- </option>-->
<!--                                --><?php //endforeach; ?>
<!--                            </select>-->
<!--                        </div>-->
<!--                    </div>-->
                <?php //endif; ?>
            </div>
            <div class="row">

                <div class="col-md-12 text-right">
                    <span class="pull-right">
                        <button type="submit" class="btn btn-sm btn-portage" id="submit-btn"><?=lang('lang.search')?></button>
                        <input type="hidden" class="isClickSearch" name="isClickSearch" value="<?=$conditions['isClickSearch']?>">
                        <input class="btn btn-default btn-sm" id="btn-clear" type="button" value="<?=lang('lang.clear');?>">
                    </span>
                </div>
            </div>
        </div>
        <div class="panel-footer"></div>
    </div>
</form>

<div class="panel panel-primary">
     <div class="panel-heading">
        <h4 class="panel-title">
            <i class="fa fa-hand-paper-o"></i> <?php echo lang('cms.promoReqAppList'); ?>
        </h4>
    </div>
    <div class="panel-body">
        <div class="table-responsive">
            <?php if ($this->utils->isEnabledFeature('batch_release_promo') && $this->permissions->checkPermissions('promocancel_list') && $conditions['status'] == 0) : ?>
                <span class="pull-left">
                    <button type="button" id="btnBatchReleasePromo" onclick="fnBatchProcessPromo('release')" class="btn btn-success btn-sm" data-toggle="tooltip" data-placement="top" title="<?= lang('batch_release_promo'); ?>">
                        <i class="fa fa-thumbs-up"></i> <?= lang('batch_release_promo'); ?>
                    </button>
                </span>
                <br/><br/>
                <hr class="m-b-5 m-t-10">
                <br/>
            <?php endif; ?>
            <?php if ($this->utils->isEnabledFeature('batch_decline_promo') && $this->permissions->checkPermissions('promocancel_list') && $conditions['status'] == 0) : ?>
                <span class="pull-left">
                    <button type="button" id="btnBatchDeclinePromo" onclick="fnBatchProcessPromo('decline')" class="btn btn-danger btn-sm" data-toggle="tooltip" data-placement="top" title="<?= lang('batch_decline_promo'); ?>">
                        <i class="fa fa-thumbs-down"></i> <?= lang('batch_decline_promo'); ?>
                    </button>
                </span>
                <br/><br/>
                <hr class="m-b-5 m-t-10">
                <br/>
            <?php endif; ?>
            <?php if (!$this->utils->isEnabledFeature('batch_finish_promo') && $conditions['status'] == 1) : ?>
                <span class="pull-left">
                    <button type="button" id="btnBatchFinishPromo" onclick="fnBatchProcessPromo('finish')" class="btn btn-primary btn-sm" data-toggle="tooltip" data-placement="top" title="<?= lang('batch_decline_promo'); ?>">
                        <i class="glyphicon glyphicon-ok"></i> <?= lang('batch_finish_promo'); ?>
                    </button>
                </span>
                <br/><br/>
                <hr class="m-b-5 m-t-10">
                <br/>
            <?php endif; ?>
            <table class="table table-bordered table-hover dataTable" id="promo-application-table" style="margin: 0px 0 0 0; width: 100%;">
                <thead>
                <tr>
                    <th>
                        <?php if ($this->utils->isEnabledFeature('batch_decline_promo') || $this->utils->isEnabledFeature('batch_finish_promo') || $this->utils->isEnabledFeature('batch_release_promo')) : ?>
                        <div class="clearfix">
                            <div class="col-md-3" style="padding:0 1px 0 2px"><input type="checkbox" name="chkAll" id="chkAll"></div>
                            <!-- OGP - 11385 Remove Details button and Action column -->
                            <?=lang('system.word85');?></div>
                        </div>
                        <!-- OGP - 11385 Remove Details button and Action column -->
                        <?php else : ?>
                            <?=lang('system.word85');?>
                        <?php endif; ?>
                    </th>
                    <th><?=lang('player.01');?></th>
                    <?php if ($this->utils->getConfig('enable_split_player_username_and_affiliate')) { ?>
                        <th><?=lang("Affiliate")?></th>
                    <?php } ?>
                    <th><?=lang('player.07');?></th>
                    <th><?=lang('cms.promotitle');?></th>
                    <th><?=lang('Deposit');?></th>
                    <th><?=lang('cms.bonusAmount');?></th>
                    <th><?=lang('Withdraw Condition');?></th>
                    <th><?=lang('cms.dateApplyRequest');?></th>
                    <th><?=lang('cms.requestBy');?></th>
                    <th><?=lang('promo.request_list.order_generated_by');?></th>
                    <th><?=lang('promo.request_list.player_request_ip');?></th>
                    <th><?=lang('cms.dateProcessed');?></th>
                    <th><?=lang('pay.procssby');?></th>
                    <th><?=lang('Status');?></th>
                    <th><?=lang('cms.bonusRelease');?></th>
                    <?php if ($this->utils->getConfig('enabled_promorules_remaining_available')) { ?>
                    <th><?=lang('promorules.total_approved_limit');?></th>
                    <?php } ?>
                    <th><?=lang('lang.action');?></th>
                    <th><?=lang('Note');?></th>
                </tr>
                </thead>
            </table>
        </div>
    </div>
</div>


<!-- edit and release -->
<div class="modal fade bs-example-modal-md" id="promoEditAndRelease" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="myModalLabel" style="margin: 0 10px;"><?=lang('Edit And Release Bonus');?></h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12">
                        <form method="post" action="<?=site_url('marketing_management/release_promo')?>">
                            <?=$double_submit_hidden_field?>
                            <input type="hidden" name="releasePlayerPromoId" id="edit_release_playerpromoid" class="form-control">
                            <input type="hidden" name="actionStatus" id="release_edit_action_status" class="form-control">
                            <input type="hidden" name="allow_zero_bonus" id="allow_zero_bonus" value="0">
                            <?=lang('Please input bonus amount');?>
                            <input type="number"  step="0.01" name="bonusAmount" id="bonusAmount" class="form-control" required>
                            <br/>
                            <?=lang('cms.remarks');?>
                            <textarea name="reasonToRelease" class="form-control reason_text" rows="7" required></textarea>
                            <br/>
                            <center>
                            <input type="reset" class="btn btn-primary" style="width:20%" value="<?=lang('lang.reset');?>">
                            <button class="btn btn-primary" style="width:20%" id="edit_release_submit"><?=lang('lang.submit');?></button>
                            </center>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- end edit and release -->

<!-- release -->
<div class="modal fade bs-example-modal-md" id="promoRelease" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="myModalLabel" style="margin: 0 10px;"><?=lang('Release Bonus');?></h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12">
                        <form method="post" action="<?=site_url('marketing_management/release_promo')?>">
                            <?=$double_submit_hidden_field?>
                            <input type="hidden" name="releasePlayerPromoId" id="release_playerpromoid" class="form-control">
                            <input type="hidden" name="actionStatus" id="release_action_status" class="form-control">
                            <?=lang('cms.remarks');?>
                            <textarea name="reasonToRelease" class="form-control reason_text" rows="7" required></textarea>
                            <br/>
                            <center>
                            <input type="reset" class="btn btn-primary" style="width:20%" value="<?=lang('lang.reset');?>">
                            <button class="btn btn-primary" style="width:20%"><?=lang('lang.submit');?></button>
                            </center>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- end release -->
<!-- decline -->
<div class="modal fade bs-example-modal-md" id="promoCancel" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="myModalLabel" style="margin: 0 10px;"><?=lang('cms.declineApplicationPromoRequest');?></h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12">
                        <form method="post" action="<?=site_url('marketing_management/decline_player_promo')?>">
                            <?=lang('cms.remarks');?>
                            <?=$double_submit_hidden_field?>
                            <input type="hidden" name="declinePlayerPromoId" id="decline_playerpromoId" class="form-control">
                            <input type="hidden" name="actionStatus" id="decline_action_status" class="form-control">
                            <textarea name="reasonToCancel" class="form-control reason_text" rows="7" required></textarea>
                            <br/>
                            <center>
                            <input type="reset" class="btn btn-primary" style="width:20%" value="<?=lang('lang.reset');?>">
                            <button class="btn btn-primary" style="width:20%"><?=lang('lang.submit');?></button>
                            </center>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- end decline -->

<style>
    @media screen and (min-width: 992px) {
        .modal-lg {
            width: 100%; /* New width for large modal */
        }
        @-moz-document url-prefix() {
            .modal-lg {
                width: 100%; /* Firefox New width for large modal */
            }
        }
    }
</style>
<!-- Level Upgrade Setting -->
<div id="duplicateModal" class="modal fade " role="dialog">
    <div class="modal-dialog modal-lg">
        <!-- Modal content-->
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title"><?= lang('cms.promoReqAppList'); ?></h4>
            </div>
            <div class="modal-body custom-height-modal">

                <div class="row">
                    <div class="col-xs-12">
                        <div class="col-md-12">
                            <div class="row playerDuplicateAccountInfoPanel">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="batchProcessModal" style="margin-top:130px !important;">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                <h4 class="modal-title batch-process-title"><?= lang('Batch Process Summary')?></h4>
            </div>
            <div class="modal-body">
                <div class="progress">
                    <div class="progress-bar progress-bar-striped active" role="progressbar" aria-valuenow="45" aria-valuemin="0" aria-valuemax="100" style="width: 100%">
                        <span class="progressbar-text"><?= lang('Processing....') ?></span>
                    </div>
                </div>
                <table class="table table-striped" id="batchProcessTable">
                    <thead>
                        <tr>
                            <th width="30"><?= lang('lang.status') ?></th>
                            <th width="50"><?= lang('ID') ?></th>
                            <th><?= lang('Remarks') ?></th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
            <div class="modal-footer"></div>
        </div>
    </div>
</div>

<div class="modal fade bs-example-modal-md" id="modalBatchProcessReason" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="batchProcessReasonModalTitle" style="margin: 0 10px;"><?=lang('cms.declineApplicationPromoRequest');?>:</h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12">
                            <span id="batchProcessReasonModalLabel"><?=lang('cms.declinePromoAppReason');?></span>
                            <textarea name="taBatchProcessReason" id="taBatchProcessReason" class="form-control reason_text" rows="7" required></textarea>
                            <br/>
                            <center>
                            <button type="button" id="btnBatchProcessReason" onclick="fnDeclinePromo()" class="btn btn-primary" style="width:30%"><?=lang('lang.submit');?></button>
                            </center>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<template class="review_haba_api_results_btn_tpl">
    <div class="btn btn-default btn-xs review_haba_api_results_btn" data-results_counter="${results_counter}" data-playerpromoid="${playerpromo_id}" data-toggle="tooltip" title="<?=lang('HabaApiResultsList')?>">
        <span class="glyphicon glyphicon glyphicon-list"></span>&nbsp;
    </div>
</template>

<?php if($this->utils->isEnabledFeature('export_excel_on_queue')){?>
<form id="_export_excel_queue_form" class="hidden" method="POST" target="_blank">
<input name='json_search' type="hidden">
</form>
<?php }?>

<?php include APPPATH . "/views/marketing_management/promorules/review_haba_api_results_list.php"; ?>

<script type="text/javascript">
    var export_type="<?php echo $this->utils->isEnabledFeature('export_excel_on_queue') ? 'queue' : 'direct';?>";
    var status = '<?= $conditions['status']; ?>';
    var player_promo_status = '<?= $conditions['player_promo_status']; ?>';
    var success_trans = 0;
    var fail_trans = 0;
    var totalTransation = 0;
    var totalCompleteTrans = 0;
    var isClickSearch = <?=$conditions['isClickSearch'];?>;
    var order_col = 7; //requestTme
    var transactionDateType = <?=$conditions['transactionDateType'];?>;

    if(transactionDateType == <?=Player_promo::TRANSACTION_DATE_TYPE_PROCESSED_TIME?>){
        order_col = 11; //processedOn
    }
    // console.log(order_col);

    var _promoApplicationList = {};
    /// CASOPA = countAllStatusOfPromoApplication
    _promoApplicationList.url4CASOPA = "<?=site_url('marketing_management/ajax_countAllStatusOfPromoApplication')?>";
    _promoApplicationList.TRANS_STATUS = {};
    _promoApplicationList.TRANS_STATUS.TRANS_STATUS_REQUEST = <?=Player_promo::TRANS_STATUS_REQUEST?>;
    _promoApplicationList.TRANS_STATUS.TRANS_STATUS_APPROVED = <?=Player_promo::TRANS_STATUS_APPROVED?>;
    _promoApplicationList.TRANS_STATUS.TRANS_STATUS_FINISHED_WITHDRAW_CONDITION = <?=Player_promo::TRANS_STATUS_FINISHED_WITHDRAW_CONDITION?>;
    _promoApplicationList.TRANS_STATUS.TRANS_STATUS_DECLINED = <?=Player_promo::TRANS_STATUS_DECLINED?>;


    var _MTMG = marketing_management.initialize({
		'promoApplicationList': _promoApplicationList
	});


    $(document).ready( function() {
        _MTMG.promoApplicationList.initEvents();

       var dataTable = $('#promo-application-table').DataTable({

            autoWidth: false,
            searching: false,
            <?php if($this->utils->isEnabledFeature('column_visibility_report')){ ?>
                stateSave: true,
            <?php } else { ?>
                stateSave: false,
            <?php } ?>
            cache: false,
            pageLength: <?=$this->utils->getDefaultItemsPerPage()?>,
            dom: "<'panel-body'<'pull-right'B><'pull-right progress-container hidden-xs'>l><'dt-information-summary1 text-info pull-left' i>t<'panel-body'<'pull-right'p><'dt-information-summary2 text-info pull-left' i>>",
           "columnDefs": [
                <?php if(($conditions['status'] == 2 || $conditions['status'] == 9)
                            || (!$this->utils->isEnabledFeature('batch_finish_promo') && $conditions['status'] == 1)
                            || (!$this->utils->isEnabledFeature('batch_decline_promo') || $conditions['status'] == 3)) : ?>
                    { "targets": [ 0 ],  "visible": false },
                <?php endif; ?>
                { sortable: false, targets: [ 0 ] }
           ],
           buttons: [
                {
                    extend: 'colvis',
                    postfixButtons: [ 'colvisRestore' ],
                    className:'btn btn-sm btn-linkwater'
                },
                <?php

                    if( $this->permissions->checkPermissions('export_promo_request_list') ){

                ?>
                // {
                //     text: '<?php echo lang("lang.export_excel"); ?>',
                //     className:'btn  btn-sm btn-primary export_excel',
                //     action: function ( e, dt, node, config ) {
                //         var d = {'extra_search':$('#search-form').serializeArray(), 'draw':1, 'length':-1, 'start':0};


                //        // utils.safelog(d);
                //         $.post(site_url('/export_data/promoApplicationList'), d, function(data){
                //             // utils.safelog(data);

                //             //create iframe and set link
                //             if(data && data.success){
                //                 $('body').append('<iframe src="'+data.link+'" frameborder="0" scrolling="no" style="border:0px;width:0px;height:0px"></iframe>');
                //             }else{
                //                 alert('export failed');
                //             }
                //         });
                //     }
                // },

                    {
                        text: "<?php echo lang('CSV Export'); ?>",
                        className:'btn btn-sm btn-portage',
                        action: function ( e, dt, node, config ) {
                            var d = {'extra_search': $('#search-form').serializeArray(), 'export_format': 'csv', 'export_type': export_type,
                            'draw':1, 'length':-1, 'start':0};


                            <?php if($this->utils->isEnabledFeature('export_excel_on_queue')){?>
                                $("#_export_excel_queue_form").attr('action', site_url('/export_data/promoApplicationList'));
                                $("#_export_excel_queue_form [name=json_search]").val(JSON.stringify(d));
                                $("#_export_excel_queue_form").submit();
                            <?php }else{?>

                            $.post(site_url('/export_data/promoApplicationList/true'), d, function(data){
                                // utils.safelog(data);

                                //create iframe and set link
                                if(data && data.success){
                                    $('body').append('<iframe src="'+data.link+'" frameborder="0" scrolling="no" style="border:0px;width:0px;height:0px"></iframe>');
                                }else{
                                    alert('export failed');
                                }
                            });
                            <?php }?>

                        }
                    }

                <?php
                   }
                ?>
            ],
            order: [[order_col, 'desc']],

            // SERVER-SIDE PROCESSING
            processing: true,
            serverSide: true,
            ajax: function (data, callback, settings) {
                var formData = $('#search-form').serializeArray();
                data.extra_search = formData;

                <?php if( ! empty($enable_go_1st_page_another_search_in_list) ): ?>
                    var _api = this.api();
                        var _container$El = $(_api.table().container());
                        var _md5 = _pubutils.NON_ENG_MD5(JSON.stringify(formData));
                    _container$El.data('md5_formdata_ajax', _md5); // assign
                <?php endif;// EOF if( ! empty($enable_go_1st_page_another_search_in_list) ):... ?>

                var _ajax = $.post("<?=site_url('api/promoApplicationList') ?>", data, function(data) {
                    // console.log(data);
                    callback(data);
                    if ( dataTable.rows( { selected: true } ).indexes().length === 0 ) {
                        dataTable.buttons().disable();
                    }
                    else {
                        dataTable.buttons().enable();
                    }
                },'json');
                _ajax.done(function(data, textStatus, jqXHR){
                    appendHabaResults.appendBtnBtn();
                });

                var _api = this.api();
                var _container$El = $(_api.table().container());
                _ajax.always(function(jqXHR, textStatus){
                    <?php if( ! empty($enable_go_1st_page_another_search_in_list) ): ?>
                        if(_container$El.data('md5_formdata_draw') != _container$El.data('md5_formdata_ajax')){
                            // goto 1st page
                            // console.log('goto 1st page');
                            _api.page('first').draw(false);
                            _api.ajax.reload();
                        }else{
                            // idle
                            // console.log('idle');
                        }
                    <?php endif;// EOF if( ! empty($enable_go_1st_page_another_search_in_list) ):... ?>
                    _container$El.data('md5_formdata_draw', _container$El.data('md5_formdata_ajax') ); // assign
                });
            },
            drawCallback: function (settings){
                // console.log('aaaa')
                var force_refresh = 0;
                if( $('input[name="isClickSearch"]').length > 0 ){
                    force_refresh = $('input[name="isClickSearch"]').val();
                }
                _MTMG.promoApplicationList.refreshDashboardStat(force_refresh);
            }
        });
        dataTable.order([order_col, 'desc']);

        $('#search-form input[type="text"],#search-form input[type="number"],#search-form input[type="email"]').keypress(function (e) {
            if (e.which == 13) {
                $('#search-form').trigger('submit');
            }
        });

        if(isClickSearch){
            $('.dashboard-stat.checked').removeClass('checked');
        }

        $('#submit-btn').click(function(){
            $('input[name="isClickSearch"]').val('1');
        });

        if(status != player_promo_status){
            //if two status are different, then remove portlet css
            $('.dashboard-stat').removeClass('checked');
            $('.dashboard-stat.panel_'+status).removeClass('checked');
            $('option[value="'+player_promo_status+'"]','#player_promo_status').attr("selected", "selected").addClass('select_option');
        }else{
            $('option[value="'+status+'"]','#player_promo_status').attr("selected", "selected").addClass('select_option');
        }

        $('input[type="radio"].dwStatus').change( function() {
            var dwStatus = $(this).val();

            //clear all checked and check current
            $('.dashboard-stat').removeClass('checked');
            $('.dashboard-stat.panel_'+dwStatus).addClass('checked');

            //default setting
            switch(dwStatus) {
                case '<?=Player_promo::TRANS_STATUS_REQUEST?>':
                    $('#transRequestTime').prop('checked', 'checked');
                    break;
                case '<?=Player_promo::TRANS_STATUS_APPROVED?>':
                    $('#transProcessTime').prop('checked', 'checked');
                    break;
                case '<?=Player_promo::TRANS_STATUS_FINISHED_WITHDRAW_CONDITION?>':
                    $('#transProcessTime').prop('checked', 'checked');
                    break;
                case '<?=Player_promo::TRANS_STATUS_DECLINED?>':
                    $('#transProcessTime').prop('checked', 'checked');
                    break;
            }
            $('input[name="search_by_date"]').val('1');
            $('#player_promo_status').val(dwStatus);
            $('#request_date_from').val('<?=$date_from?>');
            $('#request_date_to').val('<?=$date_to?>');
            $('input[name="isClickSearch"]').val('0');

            //clear other input
            $('#username').val('');
            $('#promoCmsSettingId').val('');
            $('#vipsettingcashbackruleId').val('');
            // $('#processed_by').val('');

            $('#search-form').trigger('submit');
        });

        $("#chkAll").click(function() {
            $('.chk-promo-id').not(this).prop('checked', this.checked);
        });

        $('#batchProcessModal').on('hidden.bs.modal', function () {
            window.location.reload();
        });

        $("#search_by_date").change(function() {
            if($(this).is(':checked')){
                $('#search_transaction_date').prop('disabled',false);
                $('#request_date_from').prop('disabled',false);
                $('#request_date_to').prop('disabled',false);
                $(this).attr( 'checked', true); //enabled
                $('input[name="search_by_date"]').val('1'); //true
            }else{
                $('#search_transaction_date').prop('disabled',true);
                $('#request_date_from').prop('disabled',true);
                $('#request_date_to').prop('disabled',true);
                $(this).attr( 'checked', false); //disenabled
                $('input[name="search_by_date"]').val('0'); //false
            }
        }).trigger('change');

        $('#btn-clear').on('click', function() {
            //format to today's start and end time
            $('.dateInput').data('daterangepicker').setStartDate(moment().startOf('day').format('YYYY-MM-DD HH:mm:ss'));
            $('.dateInput').data('daterangepicker').setEndDate(moment().endOf('day').format('YYYY-MM-DD HH:mm:ss'));
            dateInputAssignToStartAndEnd($('#search_transaction_date'));

            //clear select and set to default
            $('.select_option').removeAttr('selected').removeClass('select_option');
            $('#player_promo_status option:first').attr("selected", "selected").addClass('select_option');
            $('#transRequestTime').prop('checked', 'checked');
            //clear input value
            $('#username').val('');
            $('#promoCmsSettingId').val('');
            $('#vipsettingcashbackruleId').val('');
            // $('#processed_by').val('');

            //uncheck enabled date
            $('#search_by_date').removeAttr('checked');
            $('input[name="search_by_date"]').val('0');
            if($('#only_show_active_promotion')){
                $('input[name="only_show_active_promotion"]').val('');
                $('#only_show_active_promotion').removeAttr('checked');
                renderPromoSettingSelecter();
            }
            $("#search_by_date").trigger('change');

        });

        $('#player_promo_status').change(function(){
            var status = $(this).val();
            $('.select_option').removeAttr('selected').removeClass('select_option');
            $('option[value="'+status+'"]','#player_promo_status').attr("selected", "selected").addClass('select_option');
            switch(status){
                case '<?=Player_promo::TRANS_STATUS_APPROVED?>':
                case '<?=Player_promo::TRANS_STATUS_FINISHED_WITHDRAW_CONDITION?>':
                case '<?=Player_promo::TRANS_STATUS_DECLINED?>':
                    $('#transProcessTime').prop('checked', 'checked');
                    break;
                case '<?=Player_promo::TRANS_STATUS_REQUEST?>':
                default:
                    $('#transRequestTime').prop('checked', 'checked');
                    break;
            }
        });

        $('#edit_release_submit').on('click', function(e) {
            var target$El = $(e.target);
            var _form$El = target$El.closest('form');
            var allow_zero_bonus = _form$El.find('input[name="allow_zero_bonus"]').val();
            var bonus = $('#bonusAmount').val();
            if(bonus <= 0 && !(allow_zero_bonus == '1') ){
                alert("<?php echo lang('Bonus amount is invalid'); ?>");
                return false;
            }
        });

        if($('#only_show_active_promotion')){
            if($('#only_show_active_promotion').attr('checked')){
                let conditions = {onlyShowActive : true};
                renderPromoSettingSelecter(conditions);
            }else{
                renderPromoSettingSelecter();
            }
        }

        //if click input, will clear checked of cssd with ashboard-stat
        $('#username, #promoCmsSettingId, #vipsettingcashbackruleId, #search_transaction_date, #player_promo_status, #search_by_date, #transRequestTime, #transProcessTime, #only_show_active_promotion').click(function(){
            $('.dashboard-stat.checked').removeClass('checked');
        });

        var theOption = {};
        theOption.defaultItemsPerPage = <?=$this->utils->getDefaultItemsPerPage()?>;
        theOption.uri4checkHabaResultsByPlayerPromoIds = "<?=lang('/api/checkHabaResultsByPlayerPromoIds')?>";
        appendHabaResults.initEvents(theOption);

    }); // EOF $(document).ready( function() {...

    $('#promotype_sec').hide();
    $('#editpromotype_sec').hide();
    var promoTypeFlag = false;
    $('#addPromoTypeBtn').click(function() {
        if(!promoTypeFlag){
            $('#promotype_sec').show();
            $('#editpromotype_sec').hide();
            $('#addPromoTypeGlyph').removeClass('glyphicon glyphicon-plus-sign');
            $('#addPromoTypeGlyph').addClass('glyphicon glyphicon-minus-sign');
            promoTypeFlag = true;
        }else{
            $('#promotype_sec').hide();
            $('#addPromoTypeGlyph').removeClass('glyphicon glyphicon-minus-sign');
            $('#addPromoTypeGlyph').addClass('glyphicon glyphicon-plus-sign');
            promoTypeFlag = false;
        }

    });

    function getDupLicateAccountList(playerId) {
        $('#duplicateModal').modal('show');
        $.ajax({
            'url' : base_url +'payment_management/viewDuplicateAccountsDetailById/'+playerId,
            'type' : 'GET',
            'dataType' : "html",
            'success' : function(data){
                $(".playerDuplicateAccountInfoPanel").html(data);
            }
        });
    }

    function getPromotypeDetails(promotypeId){
        $('#editpromotype_sec').show();
        $('#promotype_sec').hide();
        $.ajax({
            'url' : base_url + 'marketing_management/getPromoTypeDetails/' + promotypeId,
            'type' : 'GET',
            'dataType' : "json",
            'success' : function(data){
                     $('#editpromoTypeName').val(data[0].promoTypeName);
                     $('#editpromoTypeDesc').val(data[0].promoTypeDesc);
                     $('#promoTypeId').val(data[0].promotypeId);

                 }
         });
    }

    function checkAll(id) {
        var list = document.getElementsByClassName(id);
        var all = document.getElementById(id);

        if (all.checked) {
            for (i = 0; i < list.length; i++) {
                list[i].checked = 1;
            }
        } else {
            all.checked;

            for (i = 0; i < list.length; i++) {
                list[i].checked = 0;
            }
        }
    }

    function ajaxRecordActionStatus(playerpromoId,status){
        var remarks = null;
        $.ajax({
            'url' : "<?=site_url('marketing_management/record_action_status')?>",
            'type' : 'POST',
            'data': { 'playerpromoId' : playerpromoId, 'playerPromoStatus' : status},
            'dataType' : "json",
        }).done(function(data){
            // var message=data['message'];
            if(data['success']){
                //appendToBatchProcessSummary('Success', promoId, message);
                console.log(data['message']);
                // return true;
            }else{
                console.log(data['message']);
            }
        }).fail(function(xhr, textStatus, errorThrown){
            console.log('ajaxRecordActionStatus fail');
            console.log(xhr.responseText);
        });
    }

    function viewPromoDeclineForm(playerpromoId){
        //ACTION_LOCK_STATUS_DECLINE = 3
        var status = 3;
        $('.reason_text').val('');
        $('#decline_playerpromoId').val(playerpromoId);
        $('#decline_action_status').val(status);
        ajaxRecordActionStatus(playerpromoId, status);
    }

    //function approvePromo(playerpromoId){
    //    if(confirm("<?php //echo lang('confirm.approve'); ?>//")){
    //        window.location.href="<?php //echo site_url('marketing_management/approve_player_promo') ?>///"+playerpromoId;
    //    }
    //}

    function releasePromoWithZero(playerpromoId, allow_zero_bonus){
        //ACTION_LOCK_STATUS_EDIT_RELEASE = 2
        var status = 2;
        $('#bonusAmount').val('');
        $('.reason_text').val('');
        $('#edit_release_playerpromoid').val(playerpromoId);
        $('#release_edit_action_status').val(status);
        $('#allow_zero_bonus').val(allow_zero_bonus);
        ajaxRecordActionStatus(playerpromoId, status);
    }

    function releasePromo(playerpromoId){
        //ACTION_LOCK_STATUS_RELEASE = 1
        var status = 1;
        $('.reason_text').val('');
        $('#release_playerpromoid').val(playerpromoId);
        $('#release_action_status').val(status);
        ajaxRecordActionStatus(playerpromoId, status);
    }

    function setFinished(playerpromoId){
        if(confirm("<?php echo lang('Do you want to set promotion to finished all withdraw conditions now?'); ?>?")){
            window.location.href="<?php echo site_url('marketing_management/finish_promo') ?>/"+playerpromoId;
        }
    }

    function approveManualRequestPromo(promoCmsSettingId,$playerId){
         if(confirm("<?php echo lang('confirm.approve'); ?>")){
            var check_request = 0;
            window.location.href="<?php echo site_url('marketing_management/request_promo') ?>/"+promoCmsSettingId+"/"+check_request+"/"+$playerId;
        }
    }

    $('#promoCmsSettingId').multiselect({
        enableFiltering: true,
        includeSelectAllOption: true,
        selectAllJustVisible: false,
        buttonWidth: '100%',
        buttonClass: 'form-control',
        enableCaseInsensitiveFiltering: true,
        optionClass: function(element){
            return 'promoCmsSettingItem';
        },
    });

    <?php if ($this->utils->isEnabledFeature('batch_decline_promo') || $this->utils->isEnabledFeature('batch_finish_promo') || $this->utils->isEnabledFeature('batch_release_promo')) : ?>
    function fnBatchProcessPromo(processType) {
        var confirmTypeMessage = "<?= lang('confirm_release_promo') ?>";
        var emptySelectionMessage = "<?= lang('select_promo_to_release') ?>";
        var modalTitle = "<?= lang('title_batch_release_summary') ?>";

        switch(processType) {
            case 'decline':
                confirmTypeMessage = "<?= lang('confirm_decline_promo') ?>";
                emptySelectionMessage = "<?= lang('select_promo_to_decline') ?>";
                modalTitle  = "<?= lang('title_batch_decline_summary') ?>";
                break;
            case 'finish':
                confirmTypeMessage = "<?= lang('confirm_finish_promo') ?>";
                emptySelectionMessage = "<?= lang('select_promo_to_finish') ?>";
                modalTitle = "<?= lang('title_batch_finish_summary') ?>";
                break;
        }

        if ($('.chk-promo-id').length) {
            if (!$('.chk-promo-id:checked').length) {
                alert(emptySelectionMessage);
                return false;
            }

            if(!confirm(confirmTypeMessage)){
                return false;
            }

            totalTransation = $('.chk-promo-id:checked').length;
            totalCompleteTrans = 0;

            $('.batch-process-title').text(modalTitle);


            if (processType == "decline") {
                $("#btnBatchProcessReason").attr("onclick","fnDeclinePromo('" +processType+ "')");
                $('#modalBatchProcessReason').modal('show');
                return;
            } else {
                $('#batchProcessModal').modal('show');
            }

            $('.chk-promo-id:checked').each(function(i, obj) {
                var promoId = $(this).val();

                setTimeout(
                    function () {
                        if (processType == "release") {
                            ajaxReleasePromo(promoId);
                        } else if (processType == "finish") {
                            ajaxFinishPromo(promoId);
                        } else {
                            alert('Invalid type!');
                        }
                }, 3000);
            });
        }
    }

    function ajaxReleasePromo(promoId) {
        // ------- Release promo
        var remarks = null;
        $.ajax({
            'url' : "<?=site_url('marketing_management/release_promo') ?>/"+promoId,
            'type' : 'POST',
            // 'data': {remarks: remarks},
            'cache' : false,
            'dataType' : "json",
            // "async" : false
        }).done(function(data){
            var message=data['message'];

            if(data['success']){
                appendToBatchProcessSummary('Success', promoId, message);
                return true;
            }

            if(message == ''){
                message=lang['error.default.message'];
            }

            appendToBatchProcessSummary('Failed', promoId, message);
            return false;

        }).fail(function(xhr, textStatus, errorThrown){
            console.log('fail');
            console.log(xhr.responseText);
            // appendToBatchProcessSummary('Failed', secureId, lang['error.default.message']);
            // return false;
        }); // ------- Release promo
    }

    function appendToBatchProcessSummary(status, id, remarks) {
        $('#batchProcessTable').append('<tr><td>'+status+'</td><td>'+id+'</td><td>'+remarks+'</td></tr>');

        if (status == 'Failed') {
            fail_trans++;
        } else {
            success_trans++;
        }

        totalCompleteTrans++;

        if (totalCompleteTrans == totalTransation) {
            completeProcess();
        }
    }

    function completeProcess() {
        $( ".progress-bar" ).removeClass('active');
        $( ".progress-bar" ).addClass('progress-bar-warning');
        $(".progressbar-text").text("<?= lang('Done!') ?>");
    }

    function fnDeclinePromo(declineType) {
        if(!$('#taBatchProcessReason').val()) {
            alert("<?=sprintf(lang('formvalidation.required'), 'Reason')?>");
        }

        $('#modalBatchProcessReason').modal('hide');
        $('#batchProcessModal').modal('show');
        var reason = $('#taBatchProcessReason').val();

        $('.chk-promo-id:checked').each(function(i, obj) {
            var promoId = $(this).val();

            setTimeout(
                function () {
                    if (declineType == "decline") {
                        ajaxDeclinePromo(promoId, reason);
                    }
            }, 3000);
        });
    }

    function ajaxDeclinePromo(promoId, reason) {
        $.ajax({
            'url' : "<?=site_url('marketing_management/decline_player_promo') ?>/"+promoId,
            'type' : 'POST',
            'data': {declinePlayerPromoId: promoId, reasonToCancel: reason, admin_double_submit_post: $('#admin_double_submit_post').val()},
            'cache' : false,
            'dataType' : "json",
            // "async" : false
        }).done(function(data){
            var message=data['message'];

            if(data['success']){
                appendToBatchProcessSummary('Success', promoId, message);
                return true;
            }

            if(message == ''){
                message=lang['error.default.message'];
            }

            appendToBatchProcessSummary('Failed', promoId, message);
            return false;

        }).fail(function(xhr, textStatus, errorThrown){
            console.log('fail');
            console.log(xhr.responseText);
            // appendToBatchProcessSummary('Failed', secureId, lang['error.default.message']);
            // return false;
        }); // ------- Release promo
    }

    function ajaxFinishPromo(promoId) {
        // ------- Release promo
        var remarks = null;
        $.ajax({
            'url' : "<?=site_url('marketing_management/finish_promo') ?>/"+promoId,
            'type' : 'POST',
            // 'data': {remarks: remarks},
            'cache' : false,
            'dataType' : "json",
            // "async" : false
        }).done(function(data){
            var message=data['message'];
            if(data['success']){
                appendToBatchProcessSummary('Success', promoId, message);
                return true;
            }

            if(message == ''){
                message=lang['error.default.message'];
            }

            appendToBatchProcessSummary('Failed', promoId, message);
            return false;

        }).fail(function(xhr, textStatus, errorThrown){
            console.log('fail');
            console.log(xhr.responseText);
            // appendToBatchProcessSummary('Failed', secureId, lang['error.default.message']);
            // return false;
        }); // ------- Release promo
    }
    <?php endif; ?>

    $("#only_show_active_promotion").change(function() {
        if($(this).attr('checked')){
            $('input[name="only_show_active_promotion"]').val('');
            $(this).removeAttr('checked');
            renderPromoSettingSelecter();
        }else{
            $('input[name="only_show_active_promotion"]').val('active');
            $(this).attr('checked', true);
            let conditions = {onlyShowActive : true};
            renderPromoSettingSelecter(conditions);
        }
    });

    function renderPromoSettingSelecter(conditions = {}){
        $.ajax({
            'url' : "<?=site_url('marketing_management/ajax_promo_setting_list')?>",
            'type' : 'POST',
            'data': {'conditions' : conditions},
            'dataType' : "json",
        }).done(function(data){
            if(data['status'] == 'success'){
                generateOptionsForPromoSettingSelecter(data['data']);
            }else{
                return false;
            }
        }).fail(function(xhr, textStatus, errorThrown){
            console.log('ajaxRenderPromoList fail');
            console.log(xhr.responseText);
            return false;
        });
    }

    function generateOptionsForPromoSettingSelecter(promoSettingLists){
        if(promoSettingLists){
            $('#promoCmsSettingId').val('');
            $('#promoCmsSettingId option').remove();
            $('#promoCmsSettingId').append('<option value="">' + "<?=lang('All')?>" + '</option>');
            promoSettingLists.forEach(function (item) {
                if( item.promoCmsSettingId == "<?=$conditions['promoCmsSettingId']?>" ){
                    $('#promoCmsSettingId').append('<option selected value="'+item.promoCmsSettingId+'">' + item.promoName + '</option>');
                }else{
                    $('#promoCmsSettingId').append('<option value="'+item.promoCmsSettingId+'">' + item.promoName + '</option>');
                }
            });
            $('#promoCmsSettingId').multiselect('rebuild');
        }else{
            return false;
        }
    }
</script>