<?php
	// setup defaults
	$downMaintainUnit = Group_level::DOWN_MAINTAIN_TIME_UNIT_DAY; // not any defined
	if( ! empty($data['period_down']['downMaintainUnit']) ){
		$downMaintainUnit = $data['period_down']['downMaintainUnit'];
	}

	$downMaintainTimeLength = 0; // default
	if( ! empty($data['period_down']['downMaintainTimeLength']) ){
		$downMaintainTimeLength = $data['period_down']['downMaintainTimeLength'];
	}

	$downMaintainConditionDepositAmount = 0;
	if( ! empty($data['period_down']['downMaintainConditionDepositAmount']) ){
		$downMaintainConditionDepositAmount = $data['period_down']['downMaintainConditionDepositAmount'];
	}

	$downMaintainConditionBetAmount = 0;
	if( ! empty($data['period_down']['downMaintainConditionBetAmount']) ){
		$downMaintainConditionBetAmount = $data['period_down']['downMaintainConditionBetAmount'];
	}

	$vip_setting_form_ver = $this->utils->getConfig('vip_setting_form_ver');
	$enable_separate_accumulation_in_setting = $this->utils->getConfig('enable_separate_accumulation_in_setting');
	$enable_accumulation_reset_ui = $this->utils->getConfig('enable_accumulation_reset_ui');
	if( $vip_setting_form_ver == 1 && $enable_separate_accumulation_in_setting && $enable_accumulation_reset_ui){
		$switch_to_accumulation_reset_ui = true;
	}else{
		$switch_to_accumulation_reset_ui = false;
	}

?><style type="text/css">

	/* center modal  add by jerbey  05-14-2017*/
	.content-hide {
	  display: none;
	}

	.percentage_source_0 {
	  color: brown;
	}

	.percentage_source_1 {
	  color: blue;
	}

	.percentage_source_2 {
	  color: green;
	}

	.percentage_source_3 {
	  color: red;
	}
	#container_game_tree .disabled{
		color: #cccccc;
		pointer-events: none;
	}

	label.control-label[for] {
		cursor: pointer;
	}

	.dcp-tooltip + .tooltip .tooltip-inner {
		text-align: left;
	}
	.group-fieldset-content {
		margin-left:15px !important;
		margin-right:15px !important;
	}

	.fn-btns {
		-ms-flex-align: center;
		align-items: center;
		display: -ms-flexbox;
		display: flex;
		height: 5em;
	}


	.fn-btns button {
		margin-left: 4px;
		margin-right: 4px;
	}

	.height39px {
		height: 39px;
	}
	.margin-top-15px {
		margin-top: 15px;
	}
	.margin-top-5px {
		margin-top: 5px;
	}
	.margin-top-0px {
		margin-top: 0px;
	}

	.margin-top--40px {
		margin-top: -40px;
	}
	.margin0px {
		margin: 0;
	}
	.padding-left0px {
		padding-left: 0;
	}
	.padding-right0px {
		padding-right: 0;
	}
	.top7px {
		top: 7px;
	}
	.padding20px {
		padding:20px;
	}
	.padding-bottom-8px {
		padding-bottom: 8px;
	}

</style>

<style type="text/css">
	/*// ref. to https://proto.io/freebies/onoff/ */
	.onoffswitch {
		position: relative; width: 70px;
		-webkit-user-select:none; -moz-user-select:none; -ms-user-select: none;
	}
	.onoffswitch-checkbox {
		position: absolute;
		opacity: 0;
		pointer-events: none;
	}
	.onoffswitch-label {
		display: block; overflow: hidden; cursor: pointer;
		border: 2px solid #999999; border-radius: 20px;
	}
	.onoffswitch-inner {
		display: block; width: 200%; margin-left: -100%;
		transition: margin 0.3s ease-in 0s;
	}
	.onoffswitch-inner:before, .onoffswitch-inner:after {
		display: block; float: left; width: 50%; height: 20px; padding: 0; line-height: 20px;
		font-size: 14px; color: white; font-family: Trebuchet, Arial, sans-serif; font-weight: bold;
		box-sizing: border-box;
	}
	.onoffswitch-inner:before {
		content: "ON";
		padding-left: 10px;
		background-color: #43AC6A; color: #FFFFFF;
	}
	.onoffswitch-inner:after {
		content: "OFF";
		padding-right: 10px;
		background-color: #EEEEEE; color: #999999;
		text-align: right;
	}
	.onoffswitch-switch {
		display: block;
		width: 14px;
		height: 8px;
		margin: 5px;
		background: #FFFFFF;
		position: absolute;
		top: 3px;
		bottom: 0;
		right: 46px;
		border: 2px solid #999999;
		border-radius: 20px;
		transition: all 0.3s ease-in 0s;
	}
	.onoffswitch-checkbox:checked + .onoffswitch-label .onoffswitch-inner {
		margin-left: 0;
	}
	.onoffswitch-checkbox:checked + .onoffswitch-label .onoffswitch-switch {
		right: 0px;
	}

</style>
<div class="panel panel-primary">
	<div class="panel-heading">
		<h4 class="panel-title pull-left"><i class="icon-diamond"></i> <?=lang('player.editvipgrplvlset');?></h4>
		<a href="<?=site_url('vipsetting_management/viewVipGroupRules/' . $data['vipSettingId'])?>" class="btn btn-sm pull-right btn-primary" id="add_news"><span class="glyphicon glyphicon-remove"></span></a>
		<div class="clearfix"></div>
	</div>
	<!-- Start Upload badge Modal -->
	<div class="modal fade " id="uploadModalBadge" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
		<div class="modal-dialog" role="document" style="width: 30%">
			<div class="modal-content">
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
					<h4 class="modal-title" id="myModalLabel"><?php echo lang('Upload Badge'); ?></h4>
				</div>
				<div class="modal-body">
					<form action="<?=site_url('vipsetting_management/uploadVipLevelbadge')?>" method="post" role="form" class="form-inline" enctype="multipart/form-data">
						<div class="row">
							<div class="col-md-6 text-center">
								<div class="presetIconType">
								  <img class="presetIconImg btn" id="default_icon_show" src="<?=$this->utils->imageUrl('vip_badge/vip-badge.png')?>" width="150px;" height="150px">
								</div>
								<div class="">
								  <input type="checkbox" name="set_default_badge" data-type="" id="set_default_badge"><?php echo lang("Use default badge") ?>
								</div>
							</div>
							<div class="col-md-6">
								<div class="presetIconType">
								  <img class="presetIconImg btn" id="select_badge" src="<?=$this->utils->imageUrl('og-login-logo.png')?>" width="150px;" height="150px">
								</div>
								<button style="width: 150px;" type="button" class="btn btn-default btn-xs btn_upload_badge" data-type=""><?php echo lang("Browse") ?></button>
							</div>
						</div>
						<div class="form-group text-center" style="padding-top: 30px;">
							<input type="hidden" name="vipLevelId" id="vipLevelId" value="<?=$vipgrouplevelId?>">
							<input type="file" name="vipbadge[]" id="vipbadge" style="display:none;">
							<input type="submit"  value="<?php echo lang('aff.ai53') ?>" class="btn btn-default btn-sm btn_submit_badge" disabled/>
							<input type="button"  value="<?=lang('lang.close');?>" class="btn btn-default btn-sm btn_cancel_modal" data-dismiss="modal"/>
						</div>
					</form>
				</div>
			</div>
		</div>
	</div>
	<div class="modal fade " id="viewBadge" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
		<div class="modal-dialog" role="document" style="width: 10%">
			<div class="presetIconType" style="text-align:center">
			   <?php
				  if(file_exists($this->utils->getVipBadgePath().$data['badge'])){
					$badge = $this->utils->getVipBadgeUri().'/'.$data['badge'];
				  } else {
					$badge = $this->utils->imageUrl('vip_badge/' ."vip-icon.png");
				  }
			   ?>
			  <img class="presetIconImg btn" id="default_icon_show" src="<?= $badge ?>" width="150px;" height="150px">
			</div>
		</div>
	</div>
	<script type="text/javascript">
		$(document).on("click",".btn_upload_badge",function(){
			$("#vipbadge").click();
		});
		$(document).on("change","#vipbadge",function(){
			readURL(this);
			$(".btn_submit_badge").prop('disabled',false);
		});
		$(document).on("change","#set_default_badge",function(){
			var check = $(this).is(':checked');
			if(check){
				$(".btn_submit_badge").prop('disabled',false);
			}else{
				$(".btn_submit_badge").prop('disabled',true);
			}
		});
		$('#uploadModalBadge').on('hidden.bs.modal', function(){
			$("#vipbadge").val("");
			$(".btn_submit_badge").prop('disabled',true);
			$('#select_badge').attr('src', "<?=$this->utils->imageUrl('og-login-logo.png')?>");
		});

		function readURL(input) {
			if (input.files && input.files[0]) {
				var reader = new FileReader();
				reader.onload = function (e) {
					$('#select_badge').attr('src', e.target.result);
				}
				reader.readAsDataURL(input.files[0]);
			}
		}
	</script>
	<!-- End Upload badge Modal -->
	<div class="panel panel-body" id="details_panel_body">
		<form id="vipsetting_form" class="form-horizontal" action="<?=site_url('vipsetting_management/editVipGroupLevelItemSetting/')?>" method="post" role="form">
			<input type="hidden" name="enabled_edit_game_tree" id="enabled_edit_game_tree" value="false">
			<?php if (!empty($data)) { ?>
                <div class="col-md-12 text-danger">
                    <?=lang('Please ONLY use Chrome Browser, version should be more than 69, otherwise settings will be lost.')?>
                </div>
			<fieldset class="groupName-fieldset">
				<legend><?=lang($data['groupName']);?></legend>
				<div class="form-group group-fieldset-content" >
					<div class="row">
						<div class="col col-md-1">
							<label class="control-label"><?=lang('player.vipgrplvl');?></label>
							<input type="text" name="vipLevel" class="form-control input-sm" readonly value="<?=$data['vipLevel'] == '' ? '<i class="help-block"><?= lang("lang.norecyet"); ?><i/>' : $data['vipLevel']?>">
							<input type="hidden" name="vipSettingId" class="form-control" readonly value="<?=$data['vipSettingId'] == '' ? '<i class="help-block"><?= lang("lang.norecyet"); ?><i/>' : $data['vipSettingId']?>">
							<input type="hidden" name="vipsettingcashbackruleId" class="form-control" readonly value="<?=$data['vipsettingcashbackruleId'] == '' ? '<i class="help-block"><?= lang("lang.norecyet"); ?><i/>' : $data['vipsettingcashbackruleId']?>">
						</div>
						<div class="col col-md-2">
							<label class="control-label"><?=lang('player.lvlname');?></label>
							<input type="text" id="vipLevelNameView" class="form-control input-sm" required value="<?=$data['vipLevelName'] == '' ? '<i class="help-block"><?= lang("lang.norecyet"); ?><i/>' : lang($data['vipLevelName'])?>">
							<input type="hidden" id="vipLevelName" name="vipLevelName" required value='<?= !empty($data["vipLevelName"]) ? $data["vipLevelName"] : ""?>'>
						</div>
						<div class="col col-md-2">
							<label class="control-label"><?=lang('Min deposit');?></label>
							<input type="text" required="required" name="minDeposit" id="minDeposit" class="form-control amount_only" value="<?=$data['minDeposit'] == '' ? '<i class="help-block"><?= lang("lang.norecyet"); ?><i/>' : $data['minDeposit']?>">
							<?php echo form_error('minDeposit', '<span class="help-block" style="color:#ff6666;">', '</span>'); ?>
						</div>
						<div class="col col-md-2">
							<label class="control-label"><?=lang('Max deposit per transaction');?></label>
							<input type="text" required="required" name="maxDeposit" id="maxDeposit"  class="form-control amount_only" value="<?=$data['maxDeposit'] == '' ? '<i class="help-block"><?= lang("player.nomindep"); ?><i/>' : $data['maxDeposit']?>">
							<?php echo form_error('maxDeposit', '<span class="help-block" style="color:#ff6666;">', '</span>'); ?>
						</div>
						<div class="col col-md-2">
							<label class="control-label"><?=lang('Min withdraw per transaction');?></label>
							<input type="text" required="required" name="min_withdrawal_per_transaction" id="min_withdrawal_per_transaction" class="form-control amount_only" value="<?=$data['min_withdrawal_per_transaction'] == '' ? '<i class="help-block"><?= lang("lang.norecyet"); ?><i/>' : $data['min_withdrawal_per_transaction']?>">
							<?php echo form_error('min_withdrawal_per_transaction', '<span class="help-block" style="color:#ff6666;">', '</span>'); ?>
						</div>
						<div class="col col-md-2">
							<label class="control-label"><?=lang('Max withdraw per transaction');?></label>
							<input type="text" required="required" name="max_withdraw_per_transaction" id="max_withdraw_per_transaction"
								class="form-control amount_only" value="<?=$data['max_withdraw_per_transaction'] == '' ? '<i class="help-block"><?= lang("lang.norecyet"); ?><i/>' : $data['max_withdraw_per_transaction']?>">
							<?php echo form_error('max_withdraw_per_transaction', '<span class="help-block" style="color:#ff6666;">', '</span>'); ?>
						</div>
					</div> <!-- EOF .row -->
					<div class="row">
						<div class="col col-md-2 col-md-offset-1">
							<label class="control-label"><?=lang('player.maxdailywith');?></label>
							<input type="text" required="required" name="dailyMaxWithdrawal" id="dailyMaxWithdrawal" class="form-control amount_only" value="<?=$data['dailyMaxWithdrawal'] == '' ? '<i class="help-block"><?= lang("player.nopointreq"); ?><i/>' : $data['dailyMaxWithdrawal']?>">
							<?php echo form_error('dailyMaxWithdrawal', '<span class="help-block" style="color:#ff6666;">', '</span>'); ?>
						</div>
						<div class="col col-md-2">
							<label class="control-label"><?=lang('pay.maxwithtimes')?></label>
							<input type="text" required="required" name="withdraw_times_limit" id="withdraw_times_limit" class="form-control amount_only" value="<?=$data['withdraw_times_limit'] == '' ? '<i class="help-block"><?= lang("player.nopointreq"); ?><i/>' : $data['withdraw_times_limit']?>">
							<?php echo form_error('withdraw_times_limit', '<span class="help-block" style="color:#ff6666;">', '</span>'); ?>
						</div>
						<div class="col col-md-2">
							<label class="control-label"><?=lang('Max withdrawal amount for non-deposit player')?></label>
							<input type="text" required="required" name="max_withdrawal_non_deposit_player" id="max_withdrawal_non_deposit_player" class="form-control amount_only" value="<?=$data['max_withdrawal_non_deposit_player'] == '' ? '<i class="help-block"><?= lang("Max withdrawal amount for non-deposit player"); ?><i/>' : $data['max_withdrawal_non_deposit_player']?>">
							<?php echo form_error('max_withdrawal_non_deposit_player', '<span class="help-block" style="color:#ff6666;">', '</span>'); ?>
						</div>
						<div class="col col-md-2">
							<label class="control-label"><?=lang('max_monthly_withdrawal')?>
								&nbsp;<i class="glyphicon glyphicon-info-sign dcp-tooltip" data-toggle="tooltip" data-placement="auto" data-html="true" data-original-title="<?=lang('max_monthly_withdrawal_hint');?>"></i>
							</label>
							<input type="text" required="required" name="max_monthly_withdrawal" id="max_monthly_withdrawal" class="form-control amount_only" value="<?=$data['max_monthly_withdrawal'] == '' ? '<i class="help-block"><?= lang("maxMonthlyWithdrawal"); ?><i/>' : $data['max_monthly_withdrawal']?>">
							<?php echo form_error('max_monthly_withdrawal', '<span class="help-block" style="color:#ff6666;">', '</span>'); ?>
						</div>
						<div class="col col-md-2">
							<div class="fn-btns">
								<button type="button" class="form-control btn btn-xs btn-default" data-toggle="modal" data-target="#uploadModalBadge"><?php echo lang('aff.ai53') ?></button>
								<?php echo form_error('vipLevelName', '<span class="help-block" style="color:#ff6666;">', '</span>'); ?>
								<button type="button" class="form-control btn btn-xs btn-scooter" data-toggle="modal" data-target="#viewBadge"><?php echo lang('Preview') ?></button>
							</div>
						</div>
					</div>  <!-- EOF .row -->
					<div class="row">
						<div class="col col-md-2 col-md-offset-1">
							<label class="control-label" for="can_cashback">
								<input type="checkbox" name="can_cashback" id="can_cashback" value="true"
								<?php echo $data['can_cashback'] == 'true' ? "checked='checked'" : ""; ?> >
								<?php echo lang('Can Cashback'); ?>
							</label>
							<?php echo form_error('can_cashback', '<span class="help-block" style="color:#ff6666;">', '</span>'); ?>
						</div>
						<div class="col col-md-2">
							<label class="control-label" for="one_withdraw_only">
								<input type="checkbox" name="one_withdraw_only" id="one_withdraw_only" value="true"
								<?php echo $data['one_withdraw_only'] == 1 ? "checked='checked'" : ""; ?> >
								<?php echo lang('Don\'t allow withdraw request until last withdraw request is done'); ?>
							</label>
							<?php echo form_error('one_withdraw_only', '<span class="help-block" style="color:#ff6666;">', '</span>'); ?>
						</div>

					</div> <!-- EOF .row -->
				</div>	<!-- EOF .group-fieldset-content -->
					<script>
						$(document).ready(function(){

							// initialized for assign consts.
							var theOptions4PMP = {}; // PMP = PlayerManagementProcess
							theOptions4PMP.enable_separate_accumulation_in_setting = '<?=$enable_separate_accumulation_in_setting?>';
							theOptions4PMP.ACCUMULATION_MODE_DISABLE = '<?=Group_level::ACCUMULATION_MODE_DISABLE ?>';
							theOptions4PMP.ACCUMULATION_MODE_FROM_REGISTRATION = '<?=Group_level::ACCUMULATION_MODE_FROM_REGISTRATION ?>';
							theOptions4PMP.ACCUMULATION_MODE_LAST_UPGEADE = '<?=Group_level::ACCUMULATION_MODE_LAST_UPGEADE ?>';
							theOptions4PMP.ACCUMULATION_MODE_LAST_DOWNGRADE = '<?=Group_level::ACCUMULATION_MODE_LAST_DOWNGRADE ?>';
							theOptions4PMP.ACCUMULATION_MODE_LAST_CHANGED_GEADE = '<?=Group_level::ACCUMULATION_MODE_LAST_CHANGED_GEADE ?>';
							theOptions4PMP.ACCUMULATION_MODE_LAST_CHANGED_GEADE_RESET_ALWAYS = '<?= Group_level::ACCUMULATION_MODE_LAST_CHANGED_GEADE_RESET_IF_MET // Group_level::ACCUMULATION_MODE_LAST_CHANGED_GEADE_RESET_ALWAYS ?>';
							theOptions4PMP.ACCUMULATION_MODE_LAST_CHANGED_GEADE_RESET_IF_MET = '<?=Group_level::ACCUMULATION_MODE_LAST_CHANGED_GEADE_RESET_IF_MET ?>';
							theOptions4PMP.UPGRADE_ONLY = '<?=Group_level::UPGRADE_ONLY ?>';
							theOptions4PMP.UPGRADE_DOWNGRADE = '<?=Group_level::UPGRADE_DOWNGRADE ?>';
							theOptions4PMP.DOWNGRADE_ONLY = '<?=Group_level::DOWNGRADE_ONLY ?>';
							PlayerManagementProcess.apply_options(theOptions4PMP);

							var theLangs = {}; // The followings, disabledTip4XXX will be cleared,

							PlayerManagementProcess.apply_lang(theLangs);

							var highestGroupLevelsId ='<?=$highestGroupLevelsId?>'
							function getLevelCopySample(vipLevel){
								var data = {
									vipLevel :vipLevel,
									highestGroupLevelsId:highestGroupLevelsId
								};
								$.ajax({
									url :  '<?php echo site_url('vipsetting_management/getLevelCopySample') ?>',
									type : 'POST',
									data : data,
									dataType : "json",
									cache : false,
								}).done(function (data) {
									// Game Tree
									// Reset All Game Tree
									$('input[type="checkbox"]').prop('checked', false);
									$.each(data.cashbackAllowedGame, function(key, value){
										var g = value.game;
										var gc = value.game_name;
										$('input[g="' + g +'"]').prop('checked', 'checked');
										$('input[gc="' + gc +'"]').prop('checked', 'checked');
									});
								   var vipGroupLevelSetting = data.vipGroupLevelSetting;
								   $('#minDeposit').val(vipGroupLevelSetting.minDeposit);
								   $('#maxDeposit').val(vipGroupLevelSetting.maxDeposit);
								   $('#dailyMaxWithdrawal').val(vipGroupLevelSetting.dailyMaxWithdrawal);
								   $('#withdraw_times_limit').val(vipGroupLevelSetting.withdraw_times_limit);
								   if(vipGroupLevelSetting.bonus_mode_cashback){
										$('#cashbackBonusPercentage').val(vipGroupLevelSetting.cashback_percentage);
										$('#maxCashbackBonus').val(vipGroupLevelSetting.cashback_maxbonus);
										$('#maxDailyCashbackBonus').val(vipGroupLevelSetting.cashback_daily_maxbonus);
										$('#cashbackBonusPercentage').prop("disabled",false);
										$('#maxCashbackBonus').prop("disabled",false);
										$('#maxDailyCashbackBonus').prop("disabled",false);
										$('#bonusModeCashback').prop("checked",true);
									}else{
										$('#cashbackBonusPercentage').val('');
										$('#maxCashbackBonus').val('');
										$('#maxDailyCashbackBonus').val('');
										$('#cashbackBonusPercentage').prop("disabled",true);
										$('#maxCashbackBonus').prop("disabled",true);
										$('#maxDailyCashbackBonus').prop("disabled",true);
										$('#bonusModeCashback').prop("checked",false);
									}
									if(vipGroupLevelSetting.firsttime_dep_withdraw_condition){
										$('#firstTimeDepositWithdrawCondition').val(vipGroupLevelSetting.firsttime_dep_withdraw_condition);
									}else{
										$('#firstTimeDepositWithdrawCondition').val(0);
									}
									if(vipGroupLevelSetting.succeeding_dep_withdraw_condition){
										$('#succeedingDepositWithdrawCondition').val(vipGroupLevelSetting.succeeding_dep_withdraw_condition);
									}else{
										$('#succeedingDepositWithdrawCondition').val(0);
									}
									if(vipGroupLevelSetting.downgradeAmount){
										$('#downgradeAmount').val(vipGroupLevelSetting.downgradeAmount);
									}else{
										$('#succeedingDepositWithdrawCondition').val(0);
									}
									if(vipGroupLevelSetting.upgradeAmount){
										$('#upgradeAmount').val(vipGroupLevelSetting.upgradeAmount);
									}else{
										$('#upgradeAmount').val(0);
									}
									if(vipGroupLevelSetting.bonus_mode_deposit){
										$('#bonusModeDeposit').prop("checked",true);
										if(vipGroupLevelSetting.firsttime_dep_type){
											//ist time (fix bonus amount) checkbox1
											$('#firstTimeDepositBonusOption1').prop("checked",true);
											// 1st time (Amount/Percentage) input1
											$('#firstTimeDepositBonus').prop('disabled',false);
											$('#firstTimeDepositBonus').val(vipGroupLevelSetting.firsttime_dep_bonus);
											// 1st time (of deposit amount) input2
											$('#firstTimeDepositBonusUpTo').prop('disabled',false);
											//ist time (fix bonus amount) checkbox1
											$('#firstTimeDepositBonusOption1').prop("checked",true);
											$('#firstTimeDepositBonusOption1').prop("disabled",false);
											// ist time  (of deposit amount) checkbox2
											$('#firstTimeDepositBonusOption2').prop("disabled",false);
										}else{//alert(1)
											// 1st time (Amount/Percentage) input1
											$('#firstTimeDepositBonus').prop('disabled',false);
											// ist time  (of deposit amount) checkbox2
											$('#firstTimeDepositBonusOption2').prop("checked",true).prop('disabled',false);
											// 1st time (Amount/Percentage) checkbox1
											$('#firstTimeDepositBonusOption1').prop('disabled',false);
											// 1st time (of deposit amount) input2
											$('#firstTimeDepositBonusUpToSec').show();//container
											$('#firstTimeDepositBonusUpTo').show().val(vipGroupLevelSetting.firsttime_dep_percentage_upto);
											$('#firstTimeDepositBonusUpTo').prop('disabled',false);
											// 1st time (Amount/Percentage) input1
											$('#firstTimeDepositBonus').val(vipGroupLevelSetting.firsttime_dep_bonus);
										}
										if(vipGroupLevelSetting.succeeding_dep_type){
											// succeeding- (Amount/Percentage) input1
											$('#succeedingDepositBonus').prop('disabled',false);
											$('#succeedingDepositBonus').val(vipGroupLevelSetting.succeeding_dep_bonus);
											// succeeding- (of deposit amount) input2
											$('#succeedingDepositBonusUpTo').prop('disabled',false);
											// succeeding-  (fix bonus amount) checkbox1
											$('#succeedingDepositBonusOption1').prop("checked",true);
											$('#succeedingDepositBonusOption1').prop("disabled",false);
											// succeeding-(of deposit amount) checkbox2
											$('#succeedingDepositBonusOption2').prop("disabled",false);
										}else{//alert(2)
											// succeeding- (Amount/Percentage) input1
											$('#succeedingDepositBonus').prop('disabled',false);
											// succeeding-   (of deposit amount) checkbox2
											$('#succeedingDepositBonusOption2').prop("checked",true).prop('disabled',false);
											// succeeding-  (Amount/Percentage) checkbox1
											$('#succeedingDepositBonusOption1').prop('disabled',false);
											// succeeding-  (of deposit amount) input2
											$('#succeedingDepositBonusUpToSec').show();//container
											$('#succeedingDepositBonusUpTo').show().val(vipGroupLevelSetting.succeeding_dep_percentage_upto);
											$('#succeedingDepositBonusUpTo').prop('disabled',false);
											// succeeding- (Amount/Percentage) input1
											$('#succeedingDepositBonus').val(vipGroupLevelSetting.succeeding_dep_bonus);
										}
									}else{
										//Deposit Bonus checkbox
										$('#bonusModeDeposit').prop("checked",false);
										//ist time (fix bonus amount) checkbox1
										$('#firstTimeDepositBonusOption1').prop("checked",false);
										$('#firstTimeDepositBonusOption1').prop("disabled",true);
										// 1st time (Amount/Percentage) input1
										$('#firstTimeDepositBonus').prop('disabled',true);
										$('#firstTimeDepositBonus').val('')
										// 1st time-  (of deposit amount) checkbox2
										$('#firstTimeDepositBonusOption2').prop("disabled",true);
										$('#firstTimeDepositBonusOption2').prop("checked",false);
										// 1st time-  (of deposit amount) input2
										$('#firstTimeDepositBonusUpTo').hide()
										///----------------------------------------------------
										// succeeding-  (fix bonus amount) checkbox1
										$('#succeedingDepositBonusOption1').prop("checked",false);
										$('#succeedingDepositBonusOption1').prop("disabled",true);
										// succeeding- (Amount/Percentage) input1
										$('#succeedingDepositBonus').prop('disabled',true);
										$('#succeedingDepositBonus').val('');
										// succeeding-  (of deposit amount) checkbox2
										$('#succeedingDepositBonusOption2').prop("disabled",true);
										$('#succeedingDepositBonusOption2').prop("checked",false);
										// succeeding-  (of deposit amount) input2
										$('#succeedingDepositBonusUpTo').hide()
									}
								}).fail(function (jqXHR, textStatus) {
									// window.location.href = REFRESH_PAGE_URL;
								});
							}
						});// doc ready end
					</script>
			</fieldset> <!-- EOF .groupName-fieldset -->
			<!-- <div class="form-group" style="margin-left:5px;margin-right:5px;margin-top:5px;"> -->
			<div class="form-group" style="margin: 5px 5px 0 5px;
				<?= $this->utils->isEnabledFeature('hide_point_setting_in_vip_level') ? 'display: none;' : '' ; ?>">
				<fieldset>
					<legend><h4><?php echo lang('vip.point.setting'); ?></h4></legend>

					<!-- OGP-21105 -->
					<div class="form-group">
						<div class="col-md-3">
							<label class="control-label">Point Limit Amount</label>
							<input type="number" placeholder="" name="vipSettingPointLimit" id="vipSettingPointLimit" class="form-control" >
						</div>
						<div class="col-xs-3 ">
							<label class="control-label">Point Limit Cycle&nbsp;<i class="glyphicon glyphicon-info-sign dcp-tooltip"  data-toggle="tooltip" data-placement="auto" data-html="true" data-original-title="• if set to daily, point limit will reset every day at 00:00:00.<br>
								• if set to weekly, point limit will reset every Monday at 00:00:00.<br>
								• if set to monthly, point limit will reset every 1st day of the month at 00:00:00.<br>"></i></label>
							<select class="form-control" name="vipSettingPointLimitType" id="vipSettingPointLimitType">
								<option value=""></option>
								<option value="daily">Daily</option>
								<option value="weekly">Weekly</option>
								<option value="monthly">Monthly</option>
							</select>
						</div>
						<div class="clearfix"></div>
					</div>
					<hr>

					<div class="form-group" style="margin-left:15px;margin-right:15px;">
						<div class="col-md-3 form-inline">
							<input type="checkbox" name="vipPointSettingDeposit" id="vipPointSettingDeposit" value="1">
							<strong><label class="control-label" for="vipPointSettingDeposit"><?=lang('vip.setting.depositAmountToPoint');?></label></strong>
							<br/><br/>
							<input type="number" style="width:30%" name="depositAmountConvertionRate" id="depositAmountConvertionRate" class="form-control" step=".01">
							<?php echo lang('vip.setting.percentageConvertRate'); ?>
						</div>
						<?php if($this->utils->getConfig('enable_beting_amount_to_point')):?>
						<div class="col-md-3 form-inline">
							<input type="checkbox" name="vipPointSettingBet" id="vipPointSettingBet" value="1">
							<label class="control-label" for="vipPointSettingBet"><?=lang('vip.setting.bettingAmountToPoint');?></label>
							<br/><br/>
							<input type="number" style="width:30%" name="betAmountConvertionRate" id="betAmountConvertionRate" class="form-control" step=".01">
							<?php echo lang('vip.setting.percentageConvertRate'); ?>
						</div>
						<!--<div class="col-md-3 form-inline">
							<input type="checkbox" name="vipPointSettingWinning" id="vipPointSettingWinning" value="1">
							<label class="control-label" for="vipPointSettingWinning"><?=lang('vip.setting.winningAmountToPoint');?></label>
							<br/><br/>
							<input type="number" style="width:30%" name="winningAmountConvertionRate" id="winningAmountConvertionRate" class="form-control" step=".01">
							<?php echo lang('vip.setting.percentageConvertRate'); ?>
						</div>
						<div class="col-md-3 form-inline">
							<input type="checkbox" name="vipPointSettingLosing" id="vipPointSettingLosing" value="1">
							<label class="control-label" for="vipPointSettingLosing"><?=lang('vip.setting.losingAmountToPoint');?></label>
							<br/><br/>
							<input type="number" style="width:30%" name="losingAmountConvertionRate" id="losingAmountConvertionRate" class="form-control" step=".01">
							<?php echo lang('vip.setting.percentageConvertRate'); ?>
						</div>-->
						<?php endif;?>
						<div class="clearfix"></div>
					</div>
				</fieldset>
			</div>
			<div class="form-group" style="margin-left:5px;margin-right:5px;margin-top:5px;">

				<fieldset>
					<legend><h4><?=lang('player.bonusMode');?></h4></legend>

                    <?php if(!$this->utils->isEnabledFeature('close_cashback')): ?>
					<br/>
					<div class="col-md-2">
						<input type="checkbox" name="bonusModeCashback" id="bonusModeCashback" value="1">&nbsp;
						<strong><label class="control-label" for="bonusModeCashback"><?=lang('player.cashbckbonus');?></label></strong>
					</div>
					<div class="col-md-2">
						<input type="checkbox" name="bonusModeDeposit" id="bonusModeDeposit" value="1">&nbsp;
						<label class="control-label" for="bonusModeDeposit"><?=lang('player.depositBonus');?></label>
					</div>

						<?php if ($this->utils->isEnabledFeature('enabled_vipsetting_birthday_bonus')) {?>
							<div class="col-md-2">
								<input type="checkbox" name="bonusModeBirthday" id="bonusModeBirthday" value="1">&nbsp;
								<label class="control-label" for="bonusModeBirthday"><?=lang('Birthday');?></label>
							</div>
						<?php }?>

					<div class="clearfix"></div>
					<hr/>
					<?php endif ?>

					<?php if(!$this->utils->isEnabledFeature('close_cashback')): ?>
					<div class="row">
						<div class="col-md-9">
							<div class="form-group" id="cashbackModeSec" style="margin-left:5px;margin-right:5px;">
								<fieldset style="padding:20px">
									<legend><h5><strong><?=lang('player.cashbckbonus');?></strong></h5></legend>
										<div class="row">
											<div class="col-md-12">
												<span class="">
													<?=lang('Cashback Target');?>
												</span>
												&nbsp;&nbsp;
												<span class="cashbackTarget_by_options hide">
													<label class="radio-inline">
														<input type="radio"  name="cashbackTarget" id="cashbackTarget" value="1" checked>
														&nbsp;<?=lang('Player');?>
													</label>
												</span>
												<span class="cashbackTarget_by_options hide">
													<label class="radio-inline">
														<input type="radio"  name="cashbackTarget" id="cashbackTarget" value="2">
														&nbsp;<?=lang('Affiliate');?>
													</label>
												</span>
												<span class="cashbackTarget_by_config hide">
													<label class="">
														<input type="hidden"  name="enforce_cashback_target" id="enforce_cashback_target">
														&nbsp;<span class="enforce_cashback_target_hint"></span>
													</label>
												</span>
											</div>
										</div> <!-- EOF .row -->
										<div class="row">
											<div class="col-md-4">
												<label class="control-label"><?=lang('Default Cashback Percentage');?>&nbsp;<i class="glyphicon glyphicon-info-sign dcp-tooltip" data-toggle="tooltip" data-placement="auto" data-html="true" data-original-title="<?=lang('dcp-tooltip');?>"></i></label>
												<input type="number" placeholder="<?=lang('pay.percentageNumber');?>" name="cashbackBonusPercentage" id="cashbackBonusPercentage" step='0.001' class="form-control amount_only" value="<?=$data['cashback_percentage']?>">
												<?php echo form_error('cashbackBonusPercentage', '<span class="help-block" style="color:#ff6666;">', '</span>'); ?>
												<br/>
											</div>
											<div class="col-md-4">
												<label class="control-label"><?=lang('player.maxcashbckbonus');?></label>
												<input type="number" name="maxCashbackBonus" id="maxCashbackBonus" step='any' class="form-control amount_only" value="<?=$data['cashback_maxbonus']?>">
												<?php echo form_error('maxCashbackBonus', '<span class="help-block" style="color:#ff6666;">', '</span>'); ?>
												<br/>
											</div>
											<div class="col-md-4">
												<label class="control-label"><?=lang('Daily Max Cashback Bonus');?></label>
												<input type="number" name="maxDailyCashbackBonus" id="maxDailyCashbackBonus" step='any' class="form-control amount_only" value="<?=$data['cashback_daily_maxbonus']?>">
												<?php echo form_error('maxDailyCashbackBonus', '<span class="help-block" style="color:#ff6666;">', '</span>'); ?>
												<br/>
											</div>
										</div> <!-- EOF .row -->
								</fieldset>
							</div>
						</div>
					</div>
					<?php endif ?>

					<?php if($this->utils->isEnabledFeature('enabled_cashback_period_in_vip') && !$this->utils->isEnabledFeature('close_cashback')) : ?>
						<div class="clearfix"></div>
						<div class="row">
							<div class="col-md-9">
								<div class="form-group" id="cashbackPeriod" style="margin-left:5px;margin-right:5px;">
									<fieldset style="padding:20px">
										<legend><h5><strong><?=lang('Cashback Period');?></strong></h5></legend>
										<div class="col-md-9">
											<div class="well">
												<label class="radio-inline" style="margin-left: 20px !important;padding-bottom: 25px !important;"><input type="radio" name="period" value="0"><?= lang('lang.daily') ?></label>
												<label class="radio-inline" style="padding-bottom: 25px !important;"><input type="radio" name="period" value="1"><?= lang('lang.weekly') ?></label>
											</div>
										</div>
										<input type="hidden" value="<?= $data["cashback_period"]; ?>" name="cashback_period">
										<div class="col-md-9" id="weekDays" style="margin-left: 20px;">
											<label class="radio-inline"><input type="radio" name="cb_weekly" value="1"><?= lang('Monday') ?></label>
											<label class="radio-inline"><input type="radio" name="cb_weekly" value="2"><?= lang('Tuesday') ?></label>
											<label class="radio-inline"><input type="radio" name="cb_weekly" value="3"><?= lang('Wednesday') ?></label>
											<label class="radio-inline"><input type="radio" name="cb_weekly" value="4"><?= lang('Thursday') ?></label>
											<label class="radio-inline"><input type="radio" name="cb_weekly" value="5"><?= lang('Friday') ?></label>
											<label class="radio-inline"><input type="radio" name="cb_weekly" value="6"><?= lang('Saturday') ?></label>
											<label class="radio-inline"><input type="radio" name="cb_weekly" value="7"><?= lang('Sunday') ?></label>
										</div>
									</fieldset>
								</div>
							</div>
						</div>
						<script>
							var cashbackPeriod = '<?= $data["cashback_period"]; ?>';
							var $weekDays = $('#weekDays');

							$(document).ready(function(){
								cashbackSetting(cashbackPeriod);

								$('input[name="period"]').on('click', function(){
									cashbackSetting($(this).val());
								});

								$('input[name="cb_weekly"]').on('click', function(){
									$('input[name="cashback_period"]').val($(this).val());
								});
							});
							function cashbackSetting(cashbackPeriod) {
								cashbackPeriod == 0 ? $weekDays.hide() : $weekDays.show();

								var periodVal = cashbackPeriod == 0 ? cashbackPeriod : 1;
								$("input[name=period][value="+periodVal+"]").prop('checked', true);
								if(periodVal != 0 ) {
									$("input[name=cb_weekly][value="+cashbackPeriod+"]").prop('checked', true);
								}
								$('input[name="cashback_period"]').val(cashbackPeriod);
							}
						</script>
					<?php endif; ?>

					<?php if(!$this->utils->isEnabledFeature('close_cashback')): ?>
	                <div class="col-md-12 text-danger">
	                    <?=lang('Please ONLY use Chrome Browser, version should be more than 69, otherwise settings will be lost.')?>
	                </div>
					<div class="form-group" id="container_game_tree" style="margin-left:5px;margin-right:5px;">
						<input type="hidden" name="selected_game_tree" value="">
	                    <input type="hidden" name="selected_game_tree_count" value="">
						<fieldset style="padding:20px">
							<legend><h4><?=lang('Allowed Cashback GameList');?></h4></legend>
							<div class="col-md-12" style="padding:0px 0px 10px 15px;">
								<input type="checkbox" name="auto_tick_new_games_in_game_type" id="auto_tick_new_games_in_game_type" value="true">
								<label class="control-label" for="auto_tick_new_games_in_game_type"><strong><?php echo lang('Auto tick new games in cashback tree'); ?></strong></label>
							</div>
							<?php if($this->utils->isEnabledFeature('enable_isolated_vip_game_tree_view')) { ?>
								<div class="legend2 upgrade">
									<button type="button" id="edit_cashback_game_list_btn" class="btn btn-portage">
										<i class="fa fa-plus-circle" aria-hidden="true"></i> <?=lang('Edit Setting');?>
									</button>
								</div>
								<div id="allowed-cashback-game-list-table"></div>
							<?php } else { ?>
								<div class="row" style="padding-bottom:15px;">
									<div class="col-md-2" style="padding-left:20px;">
										<input type="button" id="btn_edit_game_tree" disabled="disabled" class="disabled form-control input-sm btn btn-primary" value="<?=lang('Edit')?>">
									</div>
									<div class="col-md-2" style="padding-left:20px;">
										<input type="text" id="searchTree" class="form-control input-sm" disabled="disabled" placeholder="<?=lang('Search Game List')?>">
									</div>
								</div>
								<div class="row" style="padding-bottom:15px;">
									<div class="col-md-8" style="padding-left:20px;">
										<?=lang('Click Edit button to modify Cashback GameList')?>
									</div>
								</div>
								<div id="gameTree" class="disabled col-md-12"></div>
							<?php } ?>
						</fieldset>
					</div>
					<hr/>
					<?php endif; ?>

					<?php if (!$this->utils->isEnabledFeature('hide_bonus_withdraw_condition_in_vip')) {?>

					<div class="form-group" id="depositModeSec" style="margin-left:5px;margin-right:5px;">
						<div class="row">
							<div class="col-md-6">
								<fieldset style="padding:20px">
									<legend><h5><strong><?=lang('player.firstTimeDepositBonus');?></strong></h5></legend>
									<div class="row">
										<div class="col-md-6">
											<label class="control-label"><input type="radio" name="firstTimeDepositBonusOption" id="firstTimeDepositBonusOption1" value="1" onclick="choosefirstTimeDepositBonusOption('1')">&nbsp;<?=lang('player.fixBonusAmt');?></label>
										</div>
										<div class="col-md-6">
											<label class="control-label"><input type="radio" name="firstTimeDepositBonusOption" id="firstTimeDepositBonusOption2" value="2" onclick="choosefirstTimeDepositBonusOption('2')">&nbsp;<?=lang('player.percentageOfDeposit');?></label>
										</div>
									</div>
									<div class="row">
										<div class="col-md-4">
											<br/><label class="pull-left control-label"><?=lang('player.amountOrPercentage');?></label>
											<input type="number" name="firstTimeDepositBonus" min='0' step='any' id="firstTimeDepositBonus" class="form-control amount_only" value="">
										</div>
										<div class="col-md-4" id="firstTimeDepositBonusUpToSec">
											<br/><label class="pull-left control-label"><?=lang('player.upTo');?></label><br/>
											<input type="number" name="firstTimeDepositBonusUpTo" min='0' step='any' id="firstTimeDepositBonusUpTo" class="form-control amount_only" value="">
										</div>
									</div>
								</fieldset>
							</div>
							<div class="col-md-6">
								<fieldset style="padding:20px">
									<legend><h5><strong><?=lang('player.firstTimeDepositWithdrawCondition');?></strong></h5></legend>
									<div class="col-md-5">
										<input type="number" min='0' step='any' name="firstTimeDepositWithdrawCondition" id="firstTimeDepositWithdrawCondition" class="form-control amount_only" value="0">
									</div>
									<div class="col-md-7">
										<label class="control-label"><?=lang('player.firsttimeDepWithdrawConditionMsg');?></label>
									</div>
								</fieldset>
							</div>
						</div>
						<div class="row">
							<div class="col-md-6">
								<fieldset style="padding:20px">
									<legend><h5><strong><?=lang('player.succeedingDepositBonus');?></strong></h5></legend>
									<div class="row">
										<div class="col-md-6">
											<label class="control-label"><input type="radio" name="succeedingDepositBonusOption" id="succeedingDepositBonusOption1" value="1" onclick="chooseSucceedingDepositBonusOption('1')">&nbsp;<?=lang('player.fixBonusAmt');?></label>
										</div>
										<div class="col-md-6">
											<label class="control-label"><input type="radio" name="succeedingDepositBonusOption" id="succeedingDepositBonusOption2" value="2" onclick="chooseSucceedingDepositBonusOption('2')">&nbsp;<?=lang('player.percentageOfDeposit');?></label>
										</div>
									</div>
									<div class="row">
										<div class="col-md-4">
											<br/><label class="pull-left control-label"><?=lang('player.amountOrPercentage');?></label>
											<input type="number" name="succeedingDepositBonus" min='0' step='any' id="succeedingDepositBonus" class="form-control amount_only" value="">
										</div>
										<div class="col-md-4" id="succeedingDepositBonusUpToSec">
											<br/><label class="pull-left control-label"><?=lang('player.upTo');?></label><br/>
											<input type="number" id="succeedingDepositBonusUpTo" min='0' step='any' name="succeedingDepositBonusUpTo" class="form-control amount_only" value="">
										</div>
									</div>
								</fieldset>
							</div>
							<div class="col-md-6">
								<fieldset style="padding:20px">
									<legend><h5><strong><?=lang('player.succeedingDepositWithdrawCondition');?></strong></h5></legend>
									<div class="col-md-5">
										<input type="number" min='0' step='any' name="succeedingDepositWithdrawCondition" id="succeedingDepositWithdrawCondition" class="form-control amount_only" value="0">
									</div>
									<div class="col-md-7">
										<label class="control-label"><?=lang('player.succeedingDepWithdrawConditionMsg');?></label>
									</div>
								</fieldset>
							</div>
						</div>
					</div>
					<?php }?>
					<?php if ($this->utils->isEnabledFeature('enabled_vipsetting_birthday_bonus')) {?>
					<hr/>
					<div class="form-group" id="bonusModeBirthdaySec" style="margin-left:5px;margin-right:5px;">
						<div class="row">
							<div class="col-md-6">
								<fieldset style="padding:20px">
									<legend><h5><strong><?=lang('Birthday Bonus');?></strong></h5></legend>
									<div class="row">
										<div class="col-md-4">
											<label class="pull-left control-label"><?=lang('Bonus Amount');?></label>
											<input type="number" name="birthdayBonusAmount" min='0' step='any' id="birthdayBonusAmount" class="form-control amount_only">
										</div>
									</div>
								</fieldset>
							</div>
							<div class="col-md-6">
								<fieldset style="padding:20px">
									<legend><h5><strong><?=lang('player.birthdayBonusWithdrawCondition');?></strong></h5></legend>
									<div class="col-md-5">
										<input type="number" min='0' step='any' name="birthdayBonusWithdrawCondition" id="birthdayBonusWithdrawCondition" class="form-control amount_only" value="1">
									</div>
									<div class="col-md-7">
										<label class="control-label"><?=lang('player.birthdayBonusWithdrawConditionMsg');?></label>
									</div>
								</fieldset>
							</div>
						</div>
					</div>
					<?php }?>
				</fieldset>
			</div>
			<?php //if ($this->utils->getConfig('use_new_vip_upgrade_feature')): ?>
				<?php //include APPPATH . "/views/vip_setting/new_vip_upgrade_downgrade.php";?>
			<?php //else: ?>

			<?php if(!$this->utils->isEnabledFeature('close_level_upgrade_downgrade')): ?>
			<div class="form-group" style="margin-left:5px;margin-right:5px;">
				<fieldset style="padding:20px">
					<legend><h4><?=lang('player.levelUpgrade');?></h4></legend>
					<style>
						fieldset {
							position: relative;
						}
						.legend2 {
							position: absolute;
							background: #fff;
							top: -2.2em;
							right: 20px;
							line-height:1.2em;
						}
						@-moz-document url-prefix() {
							.legend2 { top: -3.8ex; }
						}

						.level_upgrade {
							font-weight: bold;
						}
					</style>
					<div class="row">
                        <div class="row">

                            <div class="col-md-5 col-xs-12"> <!-- Level Up Down Setting-->
                                <fieldset class="padding20px" style="height: 215px;">
                                    <legend>
                                        <h5><strong><?=lang('Level Up Down Setting');?></strong></h5>
                                    </legend>
                                    <div class="legend2 upgrade">
                                        <button type="button" id="addSettingsBtn_upgrade" class="btn btn-xs btn-portage" style="border-radius:2px;">
                                            <i class="fa fa-plus-circle" aria-hidden="true"></i> <?=lang('Add Setting');?>
                                        </button>
                                    </div>
                                    <div class="row upgrade">
                                        <div class="col-xs-12 level-upgrade"  data-id="<?=Vipsetting_Management::UPGRADE_SETTING['upgrade_only'];?>" data-upgrade="1" style="margin-bottom:10px;" >
                                            <div class="col-xs-1 control-label check-period"><i class="fa fa-square-o fa-lg" aria-hidden="true"></i> </div>
                                            <div class="col-xs-4 control-label upgrade-label"><?=lang('Upgrade Only');?></div>
                                            <div class="col-xs-6 ">
                                                <select class="form-control" id="upgradeOnly" name="upgradeOnly"></select>
                                            </div>
                                        </div>
                                        <input type="hidden" name="upgradeDowngrade" value="">
                                        <input type="hidden" name="upgradeSetting" id="upgradeSetting" value="<?php echo isset($data['upgrade_setting']) ? $data['upgrade_setting'] : "" ?>">
                                        <br>
                                        <div class="col-xs-12">
                                            <p><span style="font-style:italic;font-size:12px;color:#919191;" id="up_down_notes"><?=lang('Upgrade Only Notes');?></span></p>
                                        </div>
                                    </div>
                                </fieldset>
                            </div>
                            <div class="col-md-7 col-xs-12"> <!-- Period Up Down -->
                                <fieldset style="padding:29px;">
                                    <legend>
                                        <h5><strong><?=lang('Period Up Down');?></strong></h5>
                                    </legend>
                                    <div class="row schedule">
                                        <div class="col-xs-12 period-sched" data-id="<?=Vipsetting_Management::UPGRADE_SCHEDULE['daily'];?>" data-sched="1">
                                            <div class="col-xs-1 control-label check-period"><i class="fa fa-square-o fa-lg" aria-hidden="true"></i> </div>
                                            <div class="col-xs-2 control-label period-label"><?=lang('lang.daily');?></div>
                                            <div class="col-xs-5 d-inline">
                                                <input type="text" value="00:00:00 - 23:59:59" class="form-control" readonly style="background-color:#ffffff;">
                                            </div>
                                            <div class="col-xs-5 hide"><input type="text" value="00:00:00 - 23:59:59" class="form-control" name="daily"></div>
                                        </div>
                                        <div class="col-xs-12 period-sched" data-id="<?=Vipsetting_Management::UPGRADE_SCHEDULE['weekly'];?>" data-sched="2" style="padding-bottom: 8px;">
                                            <div class="col-xs-1 control-label check-period"> <i class="fa fa-square-o fa-lg" aria-hidden="true"></i></div>
                                            <div class="col-xs-2 control-label period-label"><?=lang('lang.weekly');?></div>
                                            <div class="col-xs-9 d-inline">
                                                <label class="radio-inline"> <input type="radio" name="weekly" value="1"><?=lang('Monday');?></label>
                                                <label class="radio-inline"> <input type="radio" name="weekly" value="2"><?=lang('Tuesday');?></label>
                                                <label class="radio-inline"> <input type="radio" name="weekly" value="3"><?=lang('Wednesday');?></label>
                                                <label class="radio-inline"> <input type="radio" name="weekly" value="4"><?=lang('Thursday');?></label>
                                                <label class="radio-inline"> <input type="radio" name="weekly" value="5"><?=lang('Friday');?></label>
                                                <label class="radio-inline"> <input type="radio" name="weekly" value="6"><?=lang('Saturday');?></label>
                                                <label class="radio-inline"> <input type="radio" name="weekly" value="7"><?=lang('Sunday');?></label>
                                            </div>
                                        </div>
                                        <div class="col-xs-12 period-sched" data-id="<?=Vipsetting_Management::UPGRADE_SCHEDULE['monthly'];?>" data-sched="3" style="padding-bottom: 8px;">
                                            <div class="col-xs-1 control-label check-period"><i class="fa fa-square-o fa-lg" aria-hidden="true"></i> </div>
                                            <div class="col-xs-2 control-label period-label"><?=lang('lang.monthly');?></div>
                                            <div class="col-xs-5 d-inline">
                                                <select class="form-control" id="monthly" name="monthly">
                                                    <?php for ($i = 1; $i <= 31; $i++) {?>
                                                        <option value="<?php echo $i; ?>"><?=$i?></option>
                                                    <?php }?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
    
                                    <input type="hidden" name="upgradeSched" id="upgradeSched" value="">
                                </fieldset>
                            </div>
                        </div>
                        <div class="row">

                            <div class="col-md-5 col-xs-12">
                                <fieldset class="padding20px" style="height: 130px;">
                                    <legend>
                                        <h5><strong><?=lang('Upgrade Bonus');?></strong></h5>
                                    </legend>
                                    <div class="row ">
                                        <div class="col-md-7 col-xs-7" style="margin-left:10px;margin-bottom:10px;" >
                                            <label for="promoManager"><?=lang('Select Promo');?></label>
                                            <div id="promoManager" >
                                                <select class="form-control" name="promo_cms_id" id="promoCmsId">
                                                    <option value="">-----<?php echo lang('N/A'); ?>-----</option>
                                                    <?php if (!empty($promoCms)) {
                                                        foreach ($promoCms as $v): ?>
                                                    <option value="<?php echo $v['promoCmsSettingId']; ?>"><?php echo $v['promoName'] ?></option>
                                                    <?php endforeach; }?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </fieldset>
                            </div>
                            <div class="col-md-7 col-xs-12">
                                <fieldset class="padding20px" style="height: 130px;">
                                    <legend>
                                        <h5><strong><?=lang('Hourly Check Upgrade');?></strong></h5>
                                    </legend>
                                    <div class="row ">
                                        <div class="col-xs-5">
                                            <div class="col-xs-12 period-sched">
                                                <div class="col-xs-1 control-label">
                                                    <input type="checkbox" name="hourlyCheckUpgrade" id="hourlyCheckUpgrade" value="1" class="user-success">
                                                </div>
                                                <div class="col-xs-10 control-label upgrade-label level_upgrade"><?=lang('Hourly Check Upgrade');?></div>
                                            </div>
                                        </div>
                                    </div>
                                </fieldset>
                            </div>
                        </div>                                
					</div>

					<script>
						var baseUrl = '<?php echo base_url(); ?>';
						var vipUpgradeId = '<?php echo isset($data['vip_upgrade_id']) ? $data['vip_upgrade_id'] : "" ?>';
						var upgradeSetting = '<?php echo isset($data['upgrade_setting']) ? $data['upgrade_setting'] : "" ?>';
						var upgradeSched = '<?php echo !empty($data['period_up_down_2']) ? json_encode($data['period_up_down_2']) : "{}" ?>';
						var vip_setting_form_ver = '<?=$this->utils->getConfig('vip_setting_form_ver');?>';

						var UPGRADE_ONLY = '<?=Group_level::UPGRADE_ONLY ?>';
						var UPGRADE_DOWNGRADE = '<?=Group_level::UPGRADE_DOWNGRADE ?>';
						var DOWNGRADE_ONLY = '<?=Group_level::DOWNGRADE_ONLY ?>';

						$(document).ready(function(){
							$('#daily').mask('00:00:00');

							var $schedule = $('.schedule').find('i');
							var $upgrade = $('.upgrade').find('i');
							var $label = $('.upgrade').find('.upgrade-label');
							var $periodLabel = $('.schedule').find('.period-label');

							loadUpDownGradeSetting();

							// on load level upgrade setting
							if(vipUpgradeId && upgradeSetting) {
								var onLoadUpgrade =  $("[data-upgrade='" + upgradeSetting + "']");
								levelUpgrade($upgrade, $label, onLoadUpgrade.find('i'), onLoadUpgrade.find('.upgrade-label'));
							}

							displayNotes(upgradeSetting);

							// on load period schedule
							if(upgradeSched !== null) {
								var schedule = $.parseJSON(upgradeSched);
								var num = 0;
								if(schedule.daily) {
									num = 1;
									$('#daily').val(schedule.daily);
								} else if(schedule.weekly) {
									num = 2;
									$("input[name=weekly][value=" + schedule.weekly + "]").prop('checked', true);
								} else if(schedule.monthly) {
									num = 3;
									$("#monthly option[value='"+schedule.monthly+"']").attr('selected', 'selected');
								} else if(schedule.yearly) {
									num = 4;
									$("#yearly option[value='"+schedule.yearly+"']").attr('selected', 'selected');
								}

								if(schedule.hasOwnProperty('hourly')) {
									if(schedule.hourly == true) $('#hourlyCheckUpgrade').prop('checked', true);
								}
								var onLoadSched =  $("[data-sched='" + num + "']");
								$('#upgradeSched').val(num);
								levelUpgrade($schedule, $periodLabel, onLoadSched.find('i'), onLoadSched.find('.upgrade-label'));
							}

							$('#vipsetting_form').on('click', '.upgrade #addSettingsBtn_upgrade, .downgrade #addSettingsBtn_downgrade', function(){
								if(vip_setting_form_ver == 2){
									$('#levelUpModal_v2').modal('show');
								}else{
									$('#levelUpModal').modal('show');
								}
                            });

							if(vip_setting_form_ver == 2){
								var options = {};
								options.baseUrl = '<?=base_url()?>';
								options.defaultItemsPerPage = '<?=$this->utils->getDefaultItemsPerPage() ?>';
								options.use_new_sbe_color = '<?=$this->utils->getConfig('use_new_sbe_color')?>';
								options.enable_separate_accumulation_in_setting = '<?=$enable_separate_accumulation_in_setting?>';
								options.enable_accumulation_computation = 1; // keep 1
								options._betAmountSettings = bet_amount_settings;
								options.ACCUMULATION_MODE_DISABLE = '<?=Group_level::ACCUMULATION_MODE_DISABLE ?>';
								options.ACCUMULATION_MODE_FROM_REGISTRATION = '<?=Group_level::ACCUMULATION_MODE_FROM_REGISTRATION ?>';
								options.ACCUMULATION_MODE_LAST_UPGEADE = '<?=Group_level::ACCUMULATION_MODE_LAST_UPGEADE ?>';
								options.ACCUMULATION_MODE_LAST_DOWNGRADE = '<?=Group_level::ACCUMULATION_MODE_LAST_DOWNGRADE ?>';
								options.ACCUMULATION_MODE_LAST_CHANGED_GEADE = '<?=Group_level::ACCUMULATION_MODE_LAST_CHANGED_GEADE ?>';
								options.ACCUMULATION_MODE_LAST_CHANGED_GEADE_RESET_ALWAYS = '<?=Group_level::ACCUMULATION_MODE_LAST_CHANGED_GEADE_RESET_IF_MET // Group_level::ACCUMULATION_MODE_LAST_CHANGED_GEADE_RESET_ALWAYS ?>';
								options.ACCUMULATION_MODE_LAST_CHANGED_GEADE_RESET_IF_MET = '<?=Group_level::ACCUMULATION_MODE_LAST_CHANGED_GEADE_RESET_IF_MET ?>';
								options.UPGRADE_ONLY = '<?=Group_level::UPGRADE_ONLY ?>';
								options.UPGRADE_DOWNGRADE = '<?=Group_level::UPGRADE_DOWNGRADE ?>';
								options.DOWNGRADE_ONLY = '<?=Group_level::DOWNGRADE_ONLY ?>';
								level_upgrade.initialize(options);

								var theLang = {};
								theLang.UpgradeSettingDeleted = '<?=lang('Upgrade Setting Deleted')?>';
								theLang.SuccessfullyUpdateSetting = '<?=lang('Successfully Update Setting')?>'; // lang('Successfully Update Setting')
								theLang.SuccessfullySaveSetting = '<?=lang('Successfully Save Setting')?>'; // lang('Successfully Save Setting')
								theLang.player_mp14 = '<?=lang('player.mp14')?>';// lang('player.mp14')
								theLang.SuccessfullyDisableSetting = '<?=lang('Successfully disable setting')?>'; // lang('Successfully disable setting')
								theLang.SuccessfullyEnableSetting = '<?=lang('Successfully enable setting')?>'; // lang('Successfully enable setting')
								theLang.Accumulation = '<?=lang('cms.accumulation');?>'; // '<?=lang('cms.accumulation');?>
								theLang.ACFRD = '<?=lang('Accumulation Computation From Registration Date');?>'; // 'Accumulation Computation From Registration Date.';
								theLang.ACFLUP = '<?=lang('Accumulation Computation From Last Upgeade Period');?>'; // 'Accumulation Computation From Last Upgeade Period.';
								theLang.ACFLDP = '<?=lang('Accumulation Computation From Last Downgrade Period');?>'; // 'Accumulation Computation From Last Downgrade Period.';
								theLang.ACFLCP = '<?=lang('Accumulation Computation From Last Changed Period');?>'; // 'Accumulation Computation From Last Changed Period.';
								theLang.UpgradeOnly = '<?=lang('Upgrade Only')?>';
								theLang.UpgradeAndDowngrade = '<?=lang('Upgrade and Downgrade')?>';
								theLang.DowngradeOnly = '<?=lang('Downgrade Only')?>';
								theLang.Preview = '<?=lang('Preview')?>';
								theLang.Active = '<?=lang('lang.active')?>';
								theLang.Inactive = '<?=lang('lang.inactive')?>';
								theLang.BetAmount = AMOUNT_MSG.BET; //  AMOUNT_MSG.BET
								theLang.DepositAmount = AMOUNT_MSG.DEPOSIT; // AMOUNT_MSG.DEPOSIT
								theLang.LossAmount = AMOUNT_MSG.LOSS; // AMOUNT_MSG.LOSS
								theLang.WinAmount = AMOUNT_MSG.WIN; // AMOUNT_MSG.WIN
								theLang.AND_OR = LANG.AND_OR;
								theLang.textLoading = '<?=lang('text.loading')?>';
								theLang.gameTree = '<?=lang('Game tree')?>';
								theLang.defaultBetAmount = '<?=lang('Default bet amount')?>';
								theLang.totalBetAmount = '<?=lang('Total bet amount')?>';
								theLang.areRequired = '<?=lang('are required')?>';
								level_upgrade.applyLang(theLang);

								level_upgrade.onReady();

								bet_amount_settings.onReady();
							} // EOF if(vip_setting_form_ver == 2){...


							$('.period-sched').on('click', function(){
								var checkPeriod = $(this).find('i');
								var data = $(this).attr('data-id');
								var label = $(this).find('.period-label');

								levelUpgrade($schedule,$periodLabel,checkPeriod,label);
								$('#upgradeSched').val(data);
							});

							$('.level-upgrade').on('click', function() {
								var checkUpgrade = $(this).find('i');
								var data = $(this).attr('data-id');
								var label = $(this).find('.upgrade-label');

								displayNotes(data);

								// levelUpgrade($upgrade,$label,checkUpgrade,label);
								$('#upgradeSetting').val(data);
							});

						});

						function displayNotes(settingId) {
							if(settingId == 1) {
								$('#up_down_notes').html('<?=lang("Upgrade Only Notes");?>');
							} else if (settingId == 2) {
								$('#up_down_notes').html('<?=lang("Upgrade Only Notes");?>');
							}
						}

						function loadUpDownGradeSetting() {
							var optionUp = '', optionUpDown = '', optionDown = '';
							var upgrade$El = $('#upgradeOnly');
							var upgradeDowngrade$El = $('#upgradeDowngrade'); // for "new_vip_upgrade_downgrade.php", but OGP-2735 not used.
							var downgrade$El = $('.downgrade #downgradeOnly'); // for loading into "Downgrade Only" of "Level Down Setting" in "Edit VIP Group Level Setting".
							var hasUp = false;
							var hasUpdown = false;
							var hasdown = false;
							var $selectEmpty = '<option value="">-----<?php echo lang('N/A'); ?>-----</option>';
							var _ajax = $.post( base_url + 'vipsetting_management/upDownTemplateList', function(data){
								if(data) {
									optionUp += $selectEmpty;
									optionUpDown += $selectEmpty;
									optionDown += $selectEmpty;
									for(var i in data) {
										// currently selected
										var selected = '';
										if(data[i].level_upgrade == PlayerManagementProcess.UPGRADE_ONLY) {
											if(vipUpgradeId == data[i].upgrade_id) {
												selected = 'selected';
											}
										} else if (data[i].level_upgrade == PlayerManagementProcess.UPGRADE_DOWNGRADE) {
											if(vipUpgradeId == data[i].upgrade_id) {
												selected = 'selected';
											}
										} else if (data[i].level_upgrade == PlayerManagementProcess.DOWNGRADE_ONLY) {
											if (vipDowngradeId == data[i].upgrade_id) {
                                                selected = 'selected';
                                            }
										}

										var _separate_accumulation_settings = data[i].separate_accumulation_settings;
										var _accumulation = data[i].accumulation;
										var _formula = data[i].formula;
										var _bet_amount_settings = data[i].bet_amount_settings;
										var _optionHTML = '<option value="'+data[i].upgrade_id+'"  '+selected+'> '+data[i].setting_name+'</option>'
										// append data-xxx into option
										// jQuery: outer html(), Ref.to https://stackoverflow.com/a/5744246
										_optionHTML = $('<div>').append($(_optionHTML).clone()
																						.prop('data-formula', _formula)
																						.attr('data-formula', _formula)
																						.prop('data-accumulation', _accumulation)
																						.attr('data-accumulation', _accumulation)
																						.prop('data-separate_accumulation_settings', _separate_accumulation_settings)
																						.attr('data-separate_accumulation_settings', _separate_accumulation_settings)
																						.prop('data-bet_amount_settings', _bet_amount_settings)
																						.attr('data-bet_amount_settings', _bet_amount_settings) ).html();

										if(data[i].level_upgrade == PlayerManagementProcess.UPGRADE_ONLY) {
											optionUp += _optionHTML;
											hasUp = true;
										} else if (data[i].level_upgrade == PlayerManagementProcess.UPGRADE_DOWNGRADE) {
											optionUpDown += _optionHTML;
											hasUpdown = true;
										} else if (data[i].level_upgrade == PlayerManagementProcess.DOWNGRADE_ONLY) {
                                            optionDown += _optionHTML;
                                            hasdown = true;
                                        }
									} // EOF for(var i in data) {...
								}
								if(hasUp) {
									upgrade$El.html(optionUp);
									// upgrade$El.trigger('change');
								} else {
									upgrade$El.html($selectEmpty)
								}
								if(hasUpdown){
									upgradeDowngrade$El.html(optionUpDown);
									// upgradeDowngrade$El.trigger('change');
								} else {
									upgradeDowngrade$El.html($selectEmpty);
								}
								if (hasdown) {
                                    downgrade$El.html(optionDown);
									// downgrade$El.trigger('change');
                                } else {
                                    downgrade$El.html($selectEmpty);
                                }

							},"json");

							_ajax.done(function (data, textStatus, jqXHR) {
								$('#upgradeOnly').trigger('change'); // will call PlayerManagementProcess.changed_upgradeOnly() for Accumulation Reset
							});

							return _ajax;
						} // EOF loadUpDownGradeSetting

						function loadUpDownGradeSettingOLD() {
							var optionUp = '', optionUpDown = '', optionDown = '';
							var upgrade = $('#upgradeOnly');
							var upgradeDowngrade = $('#upgradeDowngrade'); // for "new_vip_upgrade_downgrade.php", but OGP-2735 not used.
							var downgrade = $('.downgrade #downgradeOnly'); // for loading into "Downgrade Only" of "Level Down Setting" in "Edit VIP Group Level Setting".
							var hasUp = false;
							var hasUpdown = false;
							var hasdown = false;
							var $selectEmpty = '<option value="">-----<?php echo lang('N/A'); ?>-----</option>';
							$.post( base_url + 'vipsetting_management/upDownTemplateList', function(data){
								if(data) {
									optionUp += $selectEmpty;
									optionUpDown += $selectEmpty;
									optionDown += $selectEmpty;
									for(var i in data) {
										var selected = '';
										if(data[i].level_upgrade == 1) {
											if(vipUpgradeId == data[i].upgrade_id) {
												selected = 'selected';
											}
											optionUp += '<option value="'+data[i].upgrade_id+'" '+selected+'> '+data[i].setting_name+'</option>';
											hasUp = true;
										} else if (data[i].level_upgrade == 2) {
											if(vipUpgradeId == data[i].upgrade_id) {
												selected = 'selected';
											}
											optionUpDown += '<option value="'+data[i].upgrade_id+'"  '+selected+'> '+data[i].setting_name+'</option>';
											hasUpdown = true;
										} else if (data[i].level_upgrade == 3) {
											if (vipDowngradeId == data[i].upgrade_id) {
                                                selected = 'selected';
                                            }
                                            optionDown += '<option value="'+data[i].upgrade_id+'"  '+selected+'> '+data[i].setting_name+'</option>';
                                            hasdown = true;
                                        }
									}
								}
								if(hasUp) {
									upgrade.html(optionUp);
								} else {
									upgrade.html($selectEmpty)
								}
								if(hasUpdown){
									upgradeDowngrade.html(optionUpDown);
								} else {
									upgradeDowngrade.html($selectEmpty);
								}
								if (hasdown) {
                                    downgrade.html(optionDown);
                                } else {
                                    downgrade.html($selectEmpty);
                                }
							},"json");
						} // EOF loadUpDownGradeSettingOLD

						function levelUpgrade($upgrade, $label, checkUpgrade, thisLabel) {
							$upgrade.removeClass('fa-check-square').addClass('fa-square-o');
							$label.removeClass('level_upgrade');
							if(checkUpgrade.hasClass('fa-check-square')) {
								checkUpgrade.removeClass('fa-check-square').addClass('fa-square-o');
							} else {
								checkUpgrade.removeClass('fa-square-o').addClass('fa-check-square');
								thisLabel.addClass('level_upgrade');
							}
						}
					</script>
				</fieldset>
			</div>

			<div class="form-group form-group-level-downgrade" style="margin-left:5px;margin-right:5px;">
				<fieldset style="padding:20px" class="container-fluid">
					<legend><h4><?=lang('player.levelDowngrade');?></h4></legend>
					<div class="row">
                        <div class="row">

                            <div class="col-md-5 col-xs-12">
                                <fieldset class="padding20px" style="height: 145px;">
                                    <legend>
                                    <?php if ( !empty($this->utils->getConfig('enable_vip_downgrade_switch')) ): ?>
                                        <div class="row">
                                            <div class="col-sm-8 margin0px padding-right0px">
                                                <h5><strong><?= lang('Level Down Setting'); ?></strong></h5>
                                            </div>
                                            <div class="col-sm-4 margin0px padding-left0px top7px">
                                                <div class="onoffswitch">
                                                    <input type="checkbox" name="enableLevelDown" class="onoffswitch-checkbox" id="myonoffswitch4enableLevelDown" tabindex="0" <?=empty($data['enable_vip_downgrade'])?'':'checked';?> >
                                                    <label class="onoffswitch-label" for="myonoffswitch4enableLevelDown">
                                                        <span class="onoffswitch-inner"></span>
                                                        <span class="onoffswitch-switch"></span>
                                                    </label>
                                                </div> <!-- / .onoffswitch -->
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <h5><strong><?= lang('Level Down Setting'); ?></strong></h5>
                                    <?php endif; // EOF if ($this->utils->getConfig('enable_vip_downgrade_switch')): ?>
                                    </legend>
                                    <div class="legend2 downgrade">
                                        <button type="button" id="addSettingsBtn_downgrade" class="btn btn-xs btn-portage" style="border-radius:2px;">
                                            <i class="fa fa-plus-circle" aria-hidden="true"></i> <?= lang('Add Setting'); ?>
                                        </button>
                                    </div>
                                    <div class="row downgrade">
                                        <div class="col-xs-12 level-downgrade" data-id="<?=Vipsetting_Management::DOWNGRADE_SETTING['downgrade_only']; ?>" data-downgrade="3" style="margin-bottom:10px;" >
                                            <div class="col-xs-1 control-label check-period"><i class="fa fa-square-o fa-lg" aria-hidden="true"></i> </div>
                                            <div class="col-xs-4 control-label downgrade-label"><?= lang('Downgrade Only'); ?></div>
                                            <div class="col-xs-6 ">
                                                <select class="form-control" id="downgradeOnly" name="downgradeOnly"></select>
                                            </div>
                                        </div>
                                        <input type="hidden" name="downgradeSetting" id="downgradeSetting" value="<?php echo isset($data['downgrade_setting']) ? $data['downgrade_setting'] : "" ?>">
                                        <div class="col-xs-12">
                                            <p><span style="font-style:italic;font-size:12px;color:#919191;" id="down_notes"><?= lang('Downgrade Only Notes'); ?></span></p>
                                        </div>
                                    </div>
                                </fieldset>
                            </div>
                            <div class="col-md-7 col-xs-12">
                                <fieldset class="padding20px" >
                                    <legend>
                                        <h5><strong><?= lang('Period Down'); ?></strong></h5>
                                    </legend>
                                    <div class="row down_schedule">
                                        <div class="col-xs-12 down-period-sched" data-id="<?=Vipsetting_Management::DOWNGRADE_SCHEDULE['daily'];?>" data-sched="1">
                                            <div class="col-xs-1 d-inline control-label check-period"><i class="fa fa-square-o fa-lg" aria-hidden="true"></i> </div>
                                            <div class="col-xs-2 d-inline control-label period-label"><?= lang('lang.daily'); ?></div>
                                            <div class="col-xs-5 d-inline"><input type="text" class="form-control" id="down_daily" name="down_daily" placeholder="hh:mm:ss"></div>
                                        </div>
                                        <div class="col-xs-12 down-period-sched" data-id="<?=Vipsetting_Management::DOWNGRADE_SCHEDULE['weekly'];?>" data-sched="2" style="padding-bottom: 8px;">
                                            <div class="col-xs-1 d-inline control-label check-period"> <i class="fa fa-square-o fa-lg" aria-hidden="true"></i></i></div>
                                            <div class="col-xs-2 d-inline control-label period-label"><?= lang('lang.weekly'); ?></div>
                                            <div class="col-xs-9 d-inline">
                                                <label class="radio-inline"> <input type="radio" name="down_weekly" value="1"><?= lang('Monday'); ?></label>
                                                <label class="radio-inline"> <input type="radio" name="down_weekly" value="2"><?= lang('Tuesday'); ?></label>
                                                <label class="radio-inline"> <input type="radio" name="down_weekly" value="3"><?= lang('Wednesday'); ?></label>
                                                <label class="radio-inline"> <input type="radio" name="down_weekly" value="4"><?= lang('Thursday'); ?></label>
                                                <label class="radio-inline"> <input type="radio" name="down_weekly" value="5"><?= lang('Friday'); ?></label>
                                                <label class="radio-inline"> <input type="radio" name="down_weekly" value="6"><?= lang('Saturday'); ?></label>
                                                <label class="radio-inline"> <input type="radio" name="down_weekly" value="7"><?= lang('Sunday'); ?></label>
                                            </div>
                                        </div>
                                        <div class="col-xs-12 down-period-sched" data-id="<?=Vipsetting_Management::DOWNGRADE_SCHEDULE['monthly'];?>" data-sched="3" style="padding-bottom: 8px;">
                                            <div class="col-xs-1 d-inline control-label check-period"><i class="fa fa-square-o fa-lg" aria-hidden="true"></i> </div>
                                            <div class="col-xs-2 d-inline control-label period-label"><?= lang('lang.monthly'); ?></div>
                                            <div class="col-xs-5 d-inline">
                                                <select class="form-control" id="down_monthly" name="down_monthly">
                                                    <?php for($i=1; $i<=31; $i++) {?>
                                                        <option value="<?php echo $i; ?>"><?= $i ?></option>
                                                    <?php } ?>
                                                </select>
                                            </div>
                                        </div>
                                        <input type="hidden" name="downgradeSched" id="downgradeSched" value="">
                                    </div>
                                </fieldset>
                            </div>
                        </div>
					<?php if(  empty( $this->utils->isEnabledFeature('vip_level_maintain_settings') ) ): ?>
						<div class="col-md-5 col-xs-12">
							<fieldset class="padding20px" style="height: 230px;">
								<legend>
									<h5><strong><?=lang('Downgrade Guaranteed Setting');?></strong></h5>
								</legend>
								<div class="row">
									<div class="col-xs-12">
										<label for="guaranteed_downgrade_period_number"><?=lang('Downgrade Guaranteed Period Number');?></label>
										<div>
											<input type="number" min="1" step="1" id="guaranteed_downgrade_period_number" name="guaranteed_downgrade_period_number" class="form-control"  value="<?=$data['guaranteed_downgrade_period_number']?$data['guaranteed_downgrade_period_number']:1;?>">
										</div>
									</div>
								</div>
								<div class="row">
									<div class="col-xs-12">
										<label for="guaranteed_downgrade_period_total_deposit"><?=lang('Downgrade Guaranteed Period Total Deposit');?></label>
										<div>
											<input type="number" min="0" step="0.01" id="guaranteed_downgrade_period_total_deposit" name="guaranteed_downgrade_period_total_deposit" class="form-control"  value="<?=$data['guaranteed_downgrade_period_total_deposit']?$data['guaranteed_downgrade_period_total_deposit']:0;?>">
										</div>
									</div>
								</div>
							</fieldset>
						</div>
					<?php else: // else for if( ! empty( $this->utils->isEnabledFeature('vip_level_maintain_settings') ) ) ?>
						<div class="col-md-5 col-xs-12">
							<fieldset class="padding20px">
								<legend>
									<div class="row">
										<div class="col-sm-8 margin0px padding-right0px">
											<h5><strong><?=lang('Level Maintain');?></strong></h5>
										</div>
										<div class="col-sm-4 margin0px padding-left0px top7px">
											<div class="onoffswitch">
												<input type="checkbox" name="enableDownMaintain" class="onoffswitch-checkbox" id="myonoffswitch" tabindex="0" <?=empty($data['period_down']['enableDownMaintain'])?'':'checked';?>>
												<label class="onoffswitch-label" for="myonoffswitch">
													<span class="onoffswitch-inner"></span>
													<span class="onoffswitch-switch"></span>
												</label>
											</div> <!-- / .onoffswitch -->
										</div>
									</div>
								</legend>
								<div class="row">
									<div class="col-xs-12">
									<fieldset class="padding20px">
										<legend>
											<h5>
												<span><?=lang('Guaranteed Level Maintain Time');?></span>
												<span class="text-danger">( <?=lang('Since player get into this level');?> )</span>
											</h5>
										</legend>
										<div class="row padding-bottom-8px">
											<div class="col-sm-2">
												<?=lang('Period:');?>
											</div>
											<div class="col-sm-2">
												<label class="radio-inline"> <!-- @todo default and validation -->
													<input type="radio" name="downMaintainTimeUnit" value="<?=Group_level::DOWN_MAINTAIN_TIME_UNIT_DAY?>" <?=( $downMaintainUnit == Group_level::DOWN_MAINTAIN_TIME_UNIT_DAY)?'checked':''?> ><?=lang('day');?>
												</label>
											</div>
											<div class="col-sm-2">
												<label class="radio-inline">
													<input type="radio" name="downMaintainTimeUnit" value="<?=Group_level::DOWN_MAINTAIN_TIME_UNIT_WEEK?>" <?=( $downMaintainUnit == Group_level::DOWN_MAINTAIN_TIME_UNIT_WEEK)?'checked':''?> ><?=lang('week');?>
												</label>
											</div>
											<div class="col-sm-2">
												<label class="radio-inline">
													<input type="radio" name="downMaintainTimeUnit" value="<?=Group_level::DOWN_MAINTAIN_TIME_UNIT_MONTH?>" <?=( $downMaintainUnit == Group_level::DOWN_MAINTAIN_TIME_UNIT_MONTH)?'checked':''?> ><?=lang('month');?>
												</label>
											</div>
										</div>
										<div class="row">
											<div class="col-sm-2">
												<?=lang('Length:');?>
											</div>
											<div class="col-sm-4">
												<input type="number" class="form-control" name="downMaintainTimeLength" value="<?=$downMaintainTimeLength?>">
											</div>
										</div>
									</fieldset>
									</div>
								</div>
								<div class="row">
									<div class="col-xs-12">
									<fieldset class="padding20px">
										<legend>
											<h5>
												<span><?=lang('Level Maintain Condition');?></span>
											</h5>
										</legend>
										<div class="row margin-top--40px">
											<div class="col-sm-12 control-label">
												<h6 class="text-danger margin-top-opx">
													<?=lang('Player will be downgraded after the guaranteed time if not finish the condition below.');?>
												</h6>
											</div>
										</div>
										<div class="row">
											<div class="col-sm-6">
												<div class="row maintain-condition-deposit-amount-row">
													<div class="col-sm-6 control-label">
														<?=lang('Deposit Amount');?> &#8805;
													</div>
													<div class="col-sm-6">
														<input type="number" class="form-control" name="downMaintainConditionDepositAmount" value="<?=$downMaintainConditionDepositAmount?>">
													</div>
												</div>
											</div>
											<div class="col-sm-6">
												<div class="row maintain-condition-bet-amount-row">
													<div class="col-sm-6 control-label">
														<?=lang('Bet Amount');?> &#8805;
													</div>
													<div class="col-sm-6">
														<input type="number" class="form-control" name="downMaintainConditionBetAmount" value="<?=$downMaintainConditionBetAmount?>">
													</div>
												</div>
											</div>
										</div>
									</fieldset>
									</div>
								</div>

							</fieldset>
						</div>
					<?php endif; // EOF if( ! empty( $this->utils->isEnabledFeature('vip_level_maintain_settings') ) ) ?>

						<div class="col-md-7 col-xs-12">
							<fieldset class="padding20px" style="height: 130px;">
								<legend>
									<h5><strong><?=lang('Downgrade Bonus');?></strong></h5>
								</legend>
								<div class="row ">
									<div class="col-md-7 col-xs-7" style="margin-left:10px;margin-bottom:10px;" >
										<label for="promoManager"><?=lang('Select Promo');?></label>
										<div id="promoManager" >
											<select class="form-control" name="downgrade_promo_cms_id" id="downgrade_promoCmsId">
												<option value="">-----<?php echo lang('N/A');?>-----</option>
												<?php if(!empty($promoCms)){
													foreach ($promoCms as $v): ?>
														<option value="<?php echo $v['promoCmsSettingId']; ?>"><?php echo $v['promoName'] ?></option>
													<?php endforeach;
												} ?>
											</select>
										</div>
									</div>
								</div>
							</fieldset>
						</div>
					</div>
					<script>
						var vipDowngradeId = '<?php echo isset($data['vip_downgrade_id']) ? $data['vip_downgrade_id'] : "" ?>';
						var downgradeSetting = '<?php echo isset($data['downgrade_setting']) ? $data['downgrade_setting'] : "" ?>';
						var downgradeSched = '<?php echo !empty($data['period_down']) ?  json_encode($data['period_down']) : "{}" ?>';
						$(document).ready(function(){
							// loadUpDownGradeSetting(); // double called.
							var down_schedule = $.parseJSON(downgradeSched);

							var $downgradeSchedule = $('.down_schedule').find('i');
							var $downgrade = $('.downgrade').find('i');
							var $downgradelabel = $('.downgrade').find('.downgrade-label');
							var $downgrade_periodLabel = $('.down_schedule').find('.period-label');
						    // on load level upgrade setting
							if(vipDowngradeId && downgradeSetting) {
								var onLoadDowngrade =  $(".downgrade [data-downgrade='" + downgradeSetting + "']");
								levelDowngrade($downgrade, $downgradelabel, onLoadDowngrade.find('i'), onLoadDowngrade.find('.downgrade-label'));
							}
							// displayDowngradeNotes(downgradeSetting);
							// on load downgrade period schedule
							if($downgradeSchedule !== null) {

								var num = 0;
								if(down_schedule.daily) {
									num = 1;
									$('.down_schedule #down_daily').val(down_schedule.daily);
								} else if(down_schedule.weekly) {
									num = 2;
									$(".down_schedule input[name=down_weekly][value=" + down_schedule.weekly + "]").prop('checked', true);
								} else if(down_schedule.monthly) {
									num = 3;
									$(".down_schedule #down_monthly option[value='"+down_schedule.monthly+"']").attr('selected', 'selected');
								} else if(down_schedule.yearly) {
									num = 4;
									$(".down_schedule #down_yearly option[value='"+down_schedule.yearly+"']").attr('selected', 'selected');
								}
								var onLoadDowngrade =  $(".down_schedule [data-sched='" + num + "']");
								$('.down_schedule #downgradeSched').val(num);
								levelDowngrade($downgradeSchedule, $downgrade_periodLabel, onLoadDowngrade.find('i'), onLoadDowngrade.find('.downgrade-label'));
							}
							$('.down_schedule .down-period-sched').on('click', function(){
								var checkPeriod = $(this).find('i');
								var data = $(this).attr('data-id');
								var label = $(this).find('.period-label');
								levelDowngrade($downgradeSchedule,$downgrade_periodLabel,checkPeriod,label);
								$('.down_schedule #downgradeSched').val(data);
							});
							$('.downgrade .level-downgrade').on('click', function() {
								var checkUpgrade = $(this).find('i');
								var data = $(this).attr('data-downgrade');
								var label = $(this).find('.downgrade-label');
								displayDownNotes(data);
								// levelDowngrade($downgrade,$downgradelabel,checkUpgrade,label);
								$('.downgrade #downgradeSetting').val(data);
							});

							$('#downgradeOnly').on('change', function() {
								if(this.value ){
									$('.downgrade .level-downgrade').find('i').removeClass('fa-square-o').addClass('fa-check-square');
									$('.downgrade .level-downgrade').find('.upgrade-label').addClass('level_upgrade');
								}
								else {
									$('.downgrade .level-downgrade').find('i').removeClass('fa-check-square').addClass('fa-square-o');
									$('.downgrade .level-downgrade').find('.upgrade-label').removeClass('level_upgrade');
								}
							});
						});
						function displayDownNotes(settingId) {
							if(settingId == 1) {
								$('.downgrade #down_notes').html('<?= lang("Downgrade Only Notes"); ?>');
							}
						}
						function levelDowngrade($downgrade, $label, checkDowngrade, thisLabel) {
							$downgrade.removeClass('fa-check-square').addClass('fa-square-o');
							$label.removeClass('level_upgrade');
							if(checkDowngrade.hasClass('fa-check-square')) {
								checkDowngrade.removeClass('fa-check-square').addClass('fa-square-o');
							} else {
								checkDowngrade.removeClass('fa-square-o').addClass('fa-check-square');
								thisLabel.addClass('level_upgrade');
							}
						}


                        var enable_vip_downgrade_switch = <?= !empty($this->utils->getConfig('enable_vip_downgrade_switch'))? 1: 0  ?>;
                        var allow_both_vip_downgrade_and_maintain_switch_on = <?= !empty($this->utils->getConfig('allow_both_vip_downgrade_and_maintain_switch_on'))? 1: 0  ?>;

                        var _options = {};
                        _options.enable_vip_downgrade_switch = enable_vip_downgrade_switch;
                        _options.allow_both_vip_downgrade_and_maintain_switch_on = allow_both_vip_downgrade_and_maintain_switch_on;
                        _options.enableLevelDown$El = $('[name="enableLevelDown"]:checkbox');
                        _options.enableDownMaintain$El = $('[name="enableDownMaintain"]:checkbox');
                        PlayerManagementProcess.apply_options(_options);

					</script>
				</fieldset>
			</div>
	     	<?php  endif;?>

			<?php //endif;?>
            <div class="col-md-12 text-danger">
                <?=lang('Please ONLY use Chrome Browser, version should be more than 69, otherwise settings will be lost.')?>
            </div>
			<div class="col-md-12">
				<hr/>
				<div style="text-align:center;">

					<button class="btn btn-sm btn-linkwater"><?=lang('player.saveset');?></button>
					<a href="<?=site_url('vipsetting_management/viewVipGroupRules/' . $data['vipSettingId'])?>" class="btn btn-sm btn-scooter"><?=lang('lang.cancel');?></a>
				</div>
			</div>
			<?php }?>
		</form>
	</div>
</div>

<!-- Start levelName Modal -->
<div class="modal fade" id="levelNameModal" tabindex="-1" role="dialog" aria-labelledby="mainModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form role="form" id="form_level_name">
                <div class="modal-header">
                    <h4 class="modal-title" id="levelNameLabel"><?=lang('player.grpname');?></h4>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="group_name_english"><?=lang("lang.english.name")?> </label>
                                <input type="text" class="form-control clear-fields" id="level_name_english" name="level_name[]">
                                <span class="help-block hidden" style="color:#F04124"><?= sprintf(lang('formvalidation.required'),lang("lang.english.name"))?></span>
                            </div>
                            <div class="form-group">
                                <label for="group_name_chinese"><?=lang("lang.chinese.name")?> </label>
                                <input type="text" class="form-control clear-fields" id="level_name_chinese" name="level_name[]">
                                <span class="help-block hidden" style="color:#F04124"><?= sprintf(lang('formvalidation.required'),lang("lang.chinese.name"))?></span>
                            </div>
                            <div class="form-group">
                                <label for="group_name_indonesian"><?=lang("lang.indonesian.name")?> </label>
                                <input type="text" class="form-control clear-fields" id="level_name_indonesian" name="level_name[]">
                                <span class="help-block hidden" style="color:#F04124"><?= sprintf(lang('formvalidation.required'),lang("lang.indonesian.name"))?></span>
                            </div>
                            <div class="form-group">
                                <label for="group_name_vietnamese"><?=lang("lang.vietnamese.name")?> </label>
                                <input type="text" class="form-control clear-fields" id="level_name_vietnamese" name="level_name[]">
                                <span class="help-block hidden" style="color:#F04124"><?= sprintf(lang('formvalidation.required'),lang("lang.vietnamese.name"))?></span>
                            </div>
                            <div class="form-group">
                                <label for="group_name_korean"><?=lang("lang.korean.name")?> </label>
                                <input type="text" class="form-control clear-fields" id="level_name_korean" name="level_name[]">
                                <span class="help-block hidden" style="color:#F04124"><?= sprintf(lang('formvalidation.required'),lang("lang.korean.name"))?></span>
                            </div>
                            <div class="form-group">
                                <label for="group_name_thai"><?=lang("lang.thai.name")?> </label>
                                <input type="text" class="form-control clear-fields" id="level_name_thai" name="level_name[]">
                                <span class="help-block hidden" style="color:#F04124"><?= sprintf(lang('formvalidation.required'),lang("lang.thai.name"))?></span>
                            </div>
                            <div class="form-group">
                                <label for="group_name_india"><?=lang("lang.india.name")?> </label>
                                <input type="text" class="form-control clear-fields" id="level_name_india" name="level_name[]">
                                <span class="help-block hidden" style="color:#F04124"><?= sprintf(lang('formvalidation.required'),lang("lang.india.name"))?></span>
                            </div>
                            <div class="form-group">
                                <label for="group_name_portuguese"><?=lang("lang.portuguese.name")?> </label>
                                <input type="text" class="form-control clear-fields" id="level_name_portuguese" name="level_name[]">
                                <span class="help-block hidden" style="color:#F04124"><?= sprintf(lang('formvalidation.required'),lang("lang.portuguese.name"))?></span>
                            </div>
                            <div class="form-group">
                                <label for="group_name_spanish"><?=lang("lang.spanish.name")?> </label>
                                <input type="text" class="form-control clear-fields" id="level_name_spanish" name="level_name[]">
                                <span class="help-block hidden" style="color:#F04124"><?= sprintf(lang('formvalidation.required'),lang("lang.spanish.name"))?></span>
                            </div>
                            <div class="form-group">
                                <label for="group_name_kazakh"><?=lang("lang.kazakh.name")?> </label>
                                <input type="text" class="form-control clear-fields" id="level_name_kazakh" name="level_name[]">
                                <span class="help-block hidden" style="color:#F04124"><?= sprintf(lang('formvalidation.required'),lang("lang.kazakh.name"))?></span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer" >
                    <div style="height:70px;position:relative;">
                        <button type="button" class="btn btn-default" data-dismiss="modal"><?=lang('lang.close');?></button>
                        <button type="button" class="btn btn-primary"  onclick="return validateGroupName();"><?=lang('Done')?></button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- End levelName Modal -->
<?php //if ($this->utils->getConfig('use_new_vip_upgrade_feature')): ?>
	<?php //include APPPATH . "/views/vip_setting/new_level_upgrade.php";?>
<?php //else: ?>

	<?php
	if ($this->utils->getConfig('vip_setting_form_ver') == 2) {
		include APPPATH . "/views/vip_setting/level_upgrade.v2.php";
		include APPPATH . "/views/vip_setting/bet_amount_settings.php";
	}else{
		include APPPATH . "/views/vip_setting/level_upgrade.php";
	}
	?>

	<?php
		if($this->utils->isEnabledFeature('enable_isolated_vip_game_tree_view')) {
			include APPPATH . "/views/vip_setting/cashback_game_list.php";
		}
	?>
    <?php
		if( !empty($this->utils->getConfig('enable_vip_downgrade_switch')) ) {
			include APPPATH . "/views/vip_setting/vip_downgrade_and_maintain_switch_modal.php";
		}
	?>
<?php //endif;?>


<script type="text/javascript">
	var ENABLE_ISOLATED_VIP_GAME_TREE_VIEW = "<?=$this->utils->isEnabledFeature('enable_isolated_vip_game_tree_view')?>";
	function validateGroupName(){
        var form = $("#form_level_name");
        var inputNames = form.find('input[name^="level_name"]');
        var groupNames = {};

        form.find('.hidden').length
        form.find('span').addClass("hidden");
        $("#form_level_name input[type=text]").each(function(){
            if ( $(this).val().length == 0 ) {
                $(this).next().removeClass("hidden")
            }
        });

        if( form.find('.hidden').length == 10 ) {
            inputNames.each(function(index) {
                var key = index + 1;
                groupNames[key] = $(this).val();

            });

            var jsonPretty = '_json:'+JSON.stringify(groupNames);
            var init = jsonPretty;
            var currentLang = "<?= $this->language_function->getCurrentLanguage(); ?>";

            $("#vipLevelNameView").val(groupNames[currentLang]);
            $("#vipLevelName").val(jsonPretty);

            $('#levelNameModal').modal('hide');
        }
    }

	$(document).on("click","#vipLevelNameView",function(){
        $("#form_level_name").find('span').addClass("hidden");
        $("#form_level_name").find('span').text('');

        var inputNames = $('#vipLevelName').val();
        console.log(inputNames);
        if( inputNames.indexOf("_json:") >= 0 ) {
        var langConvert = jQuery.parseJSON(inputNames.substring(6));
            $("#form_level_name input[type=text]").each(function(index){
                $(this).val(langConvert[index+1]);
            });
        } else {
            $("#form_level_name input[type=text]").val(inputNames);
        }
        $('#levelNameModal').modal('show');
    });

	$( document ).ready( function( ) {

		var periodUpDownValue = '<?=$data['period_up_down'];?>';
		var promoCmsId = '<?=$data['promo_cms_id'];?>';
		var downgrade_promo_cms_id = '<?=$data['downgrade_promo_cms_id'];?>';
		var auto_tick_new_game_in_cashback_tree = '<?= $data['auto_tick_new_game_in_cashback_tree'] ?>';

		// for auto tick new game check box
		$('#auto_tick_new_games_in_game_type').prop('checked',  auto_tick_new_game_in_cashback_tree == '1');

		if(promoCmsId) {
			$("#promoCmsId").val(promoCmsId);
		}

		if(downgrade_promo_cms_id) {
			$("#downgrade_promoCmsId").val(downgrade_promo_cms_id);
		}

		$('#allowedGameList').show();
		$('#editGameList').hide();
		$('#cancelEdit_btn').hide();

		$("#editAllowedGame_btn").click( function( ) {
			$('#allowedGameList').hide();
			$('#cancelEdit_btn').show();
			$('#editAllowedGame_btn').hide();
			$('#editGameList').show();
		});
		$("#cancelEdit_btn").click( function( ) {
			$('#allowedGameList').show();
			$('#cancelEdit_btn').hide();
			$('#editAllowedGame_btn').show();
			$('#editGameList').hide();
		});

		//bonus mode
		var bonusModeCashback = "<?=$data['bonus_mode_cashback'] == 1 ? 'true' : 'false'?>";
		var bonusModeDeposit = "<?=$data['bonus_mode_deposit'] == 1 ? 'true' : 'false'?>";
		var bonusModeBirthday = "<?=$data['bonus_mode_birthday'] == 1 ? 'true' : 'false'?>";

		bonusModeOptionCashback(bonusModeCashback);
		bonusModeOptionDeposit(bonusModeDeposit);
		bonusModeOptionBirthday(bonusModeBirthday);

		var cashbackTarget = "<?=$data['cashback_target']?>";
		var enforce_cashback_target = <?= empty($this->utils->getConfig('enforce_cashback_target'))? 0: $this->utils->getConfig('enforce_cashback_target')?>;
		if(enforce_cashback_target != 0){ // assist from config

			var enforce_cashback_target_player_hint = "<?=lang('enforce_cashback_target_player_hint')?>";
			var enforce_cashback_target_affiliate_hint = "<?=lang('enforce_cashback_target_affiliate_hint')?>";

			if(enforce_cashback_target == <?=Group_level::CASHBACK_TARGET_PLAYER?>){
				$('.enforce_cashback_target_hint').text(enforce_cashback_target_player_hint);
			}else if(enforce_cashback_target == <?=Group_level::CASHBACK_TARGET_AFFILIATE?>){
				$('.enforce_cashback_target_hint').text(enforce_cashback_target_affiliate_hint);
			}

			setCashbackTarget(enforce_cashback_target);
			$('.cashbackTarget_by_config').removeClass('hide'); // show
			$('.cashbackTarget_by_options').addClass('hide'); // hide

		}else{ // assist from vip
			setCashbackTarget(cashbackTarget);
			$('.cashbackTarget_by_config').addClass('hide'); // hide
			$('.cashbackTarget_by_options').removeClass('hide'); // show
		}

		var depositRateFlag = "<?=$data['deposit_convert_rate'] != '' ? 'true' : 'false'?>";
		var betRateFlag = "<?=$data['bet_convert_rate'] != '' ? 'true' : 'false'?>";
		var winningRateFlag = "<?=$data['winning_convert_rate'] != '' ? 'true' : 'false'?>";
		var losingRateFlag = "<?=$data['losing_convert_rate'] != '' ? 'true' : 'false'?>";

		vipPointSettingDepositRateFlag(depositRateFlag);
		vipPointSettingBetRateFlag(betRateFlag);
		vipPointSettingWinningRateFlag(winningRateFlag);
		vipPointSettingLosingRateFlag(losingRateFlag);

		var vipSettingPointLimit = "<?=$data['points_limit'] != '' ? $data['points_limit'] : ''?>";
		var vipSettingPointLimitType = "<?=$data['points_limit_type'] != '' ? $data['points_limit_type'] : ''?>";
		$("#vipSettingPointLimit").val(vipSettingPointLimit);
		$("#vipSettingPointLimitType").val(vipSettingPointLimitType);

		if(ENABLE_ISOLATED_VIP_GAME_TREE_VIEW) {
			loadJstreeTable(
				tree_dom_id = '#gameTree',
				outer_tale_id = '#allowed-cashback-game-list-table',
				summarize_table_id = '#summarize-table',
				get_data_url = "<?php echo site_url('/api/get_game_tree_by_level/' . $vipgrouplevelId); ?>",
				input_number_form_sel = '#settingForm',
				default_num_value = "<?php echo isset($data['cashback_percentage']) ? $data['cashback_percentage'] : 0; ?>",
				generate_filter_column = {
					'Download Enabled': 'dlc_enabled',
					'Mobile Enabled': 	'mobile_enabled',
					'progressive': 		'progressive',
					'Android Enabled': 	'enabled_on_android',
					'IOS Enabled': 		'enabled_on_ios',
					'Flash Enabled': 	'flash_enabled',
					'HTML5 Enabled': 	'html_five_enabled'
				},
				filter_col_id = '#filter_col',
				filter_trigger_id = '#filterTree'
			);
		}
		else {

			$('#btn_edit_game_tree').click(function(){
				//load again

				//remove disabled and set flag
				$('#gameTree').removeAttr('disabled').removeClass('disabled');
				$('#gameTree input').removeAttr('disabled').removeClass('disabled');
				$('#searchTree').removeAttr('disabled').removeClass('disabled');
				$('#enabled_edit_game_tree').val('true');
			});

			$('#gameTree').on('ready.jstree', function(ev, data){
				//enable edit button
				$('#btn_edit_game_tree').removeAttr('disabled').removeClass('disabled');
				$('#gameTree input').attr('disabled', 'disabled').addClass('disabled');
			}).jstree({
			  'core' : {
				'data' : {
				  "url" : "<?php echo site_url('/api/get_game_tree_by_level/' . $vipgrouplevelId); ?>",
				  "dataType" : "json" // needed only if you do not supply JSON headers
				}
			  },
			  "input_number":{
				"form_sel": '#vipsetting_form'
			  },
			  "checkbox":{
				"tie_selection": false
			  },
			  "plugins":[
				"search","checkbox","input_number"
			  ]
			});
		}


		var to = false;
		$("#searchTree").keyup(function() {
			if(to) { clearTimeout(to); }
			to = setTimeout(function () {
				var v = $('#searchTree').val();
				$('#gameTree').jstree(true).search(v);
			}, 250);
		});

		<?php if(!$this->utils->isEnabledFeature('close_cashback')): ?>
		$('#vipsetting_form').submit(function(e){
            if(!isChrome()){
                alert("<?=lang('Sorry, cannot use other browser to save settings.')?> <?=lang('Please ONLY use Chrome Browser, version should be more than 69, otherwise settings will be lost.')?>");
                e.preventDefault();
                return false;
            }
			if($("#enabled_edit_game_tree").val()=='true'){
				var selected_game=$('#gameTree').jstree('get_checked');
				if(selected_game.length>0){
                    $("#vipsetting_form input[name=selected_game_tree_count]").val(selected_game.length);
					$('#vipsetting_form input[name=selected_game_tree]').val(selected_game.join());
					$('#gameTree').jstree('generate_number_fields');
				}else{
					BootstrapDialog.alert("<?php echo lang('Please choose one game at least'); ?>");
					e.preventDefault();
					return false;
				}
			}
		});
		<?php endif; ?>

		// Always run, not affected by sys feature close_cashback
		$('#vipsetting_form').submit(function(e){

			var wd_per_tx = parseFloat($('input#max_withdraw_per_transaction').val());
			var wd_per_day = parseFloat($('input#dailyMaxWithdrawal').val());
			if (wd_per_tx > wd_per_day) {
				BootstrapDialog.alert({
					message: '<?= lang('vipedit.error.wd_per_tx_over_wd_per_day') ?>',
					onhidden: function() {
						$('input#max_withdraw_per_transaction').focus();
					}
				});
				return false;
			}


		});

        $('#edit_cashback_game_list_btn').on('click', function(){
            $('#cashbackGameListModal').modal('show');
        });
	});

	var fixBonusAmt = 1;
	var byPercentage = 2;
	var flag_true = 'true';
	var flag_false = 'false';

	function vipPointSettingDepositRateFlag(flag){
	  if(flag == flag_true){
		$('#vipPointSettingDeposit').prop('checked',true);
		$('#depositAmountConvertionRate').prop('disabled',false);
		$('#depositAmountConvertionRate').val("<?=$data['deposit_convert_rate']?>");
	  }else{
		$('#vipPointSettingDeposit').prop('checked',false);
		$('#depositAmountConvertionRate').prop('disabled',true);
		$('#depositAmountConvertionRate').val("");
	  }
	}

	function vipPointSettingBetRateFlag(flag){
	  if(flag == flag_true){
		$('#vipPointSettingBet').prop('checked',true);
		$('#betAmountConvertionRate').prop('disabled',false);
		$('#betAmountConvertionRate').val("<?=$data['bet_convert_rate']?>");
	  }else{
		$('#vipPointSettingBet').prop('checked',false);
		$('#betAmountConvertionRate').prop('disabled',true);
		$('#betAmountConvertionRate').val("");
	  }
	}

	function vipPointSettingWinningRateFlag(flag){
	  if(flag == flag_true){
		$('#vipPointSettingWinning').prop('checked',true);
		$('#winningAmountConvertionRate').prop('disabled',false);
		$('#winningAmountConvertionRate').val("<?=$data['winning_convert_rate']?>");
	  }else{
		$('#vipPointSettingWinning').prop('checked',false);
		$('#winningAmountConvertionRate').prop('disabled',true);
		$('#winningAmountConvertionRate').val("");
	  }
	}

	function vipPointSettingLosingRateFlag(flag){
	  if(flag == flag_true){
		$('#vipPointSettingLosing').prop('checked',true);
		$('#losingAmountConvertionRate').prop('disabled',false);
		$('#losingAmountConvertionRate').val("<?=$data['losing_convert_rate']?>");
	  }else{
		$('#vipPointSettingLosing').prop('checked',false);
		$('#losingAmountConvertionRate').prop('disabled',true);
		$('#losingAmountConvertionRate').val("");
	  }
	}


	$("#vipPointSettingBet").click(function() {
	   checked = $('#vipPointSettingBet').is(':checked');
	   if(checked){
			vipPointSettingBetRateFlag(flag_true);
	   }else{
			vipPointSettingBetRateFlag(flag_false);
	   }
	});

	$("#vipPointSettingDeposit").click(function() {
	   checked = $('#vipPointSettingDeposit').is(':checked');
	   if(checked){
			vipPointSettingDepositRateFlag(flag_true);
	   }else{
			vipPointSettingDepositRateFlag(flag_false);
	   }
	});

	$("#vipPointSettingWinning").click(function() {
	   checked = $('#vipPointSettingWinning').is(':checked');
	   if(checked){
			vipPointSettingWinningRateFlag(flag_true);
	   }else{
			vipPointSettingWinningRateFlag(flag_false);
	   }
	});

	$("#vipPointSettingLosing").click(function() {
	   checked = $('#vipPointSettingLosing').is(':checked');
	   if(checked){
			vipPointSettingLosingRateFlag(flag_true);
	   }else{
			vipPointSettingLosingRateFlag(flag_false);
	   }
	});

	$("#bonusModeCashback").click(function() {
	   checked = $('#bonusModeCashback').is(':checked');
	   if(checked){
			bonusModeOptionCashback(flag_true);
	   }else{
			bonusModeOptionCashback(flag_false);
	   }
	});
	$("#bonusModeDeposit").click(function() {
	   checked = $('#bonusModeDeposit').is(':checked');
	   if(checked){
			bonusModeOptionDeposit(flag_true);
	   }else{
			bonusModeOptionDeposit(flag_false);
	   }
	});

	$("#bonusModeBirthday").click(function() {
	   checked = $('#bonusModeBirthday').is(':checked');
	   if(checked){
			bonusModeOptionBirthday(flag_true);
	   }else{
			bonusModeOptionBirthday(flag_false);
	   }
	});

	function bonusModeOptionCashback(flag){
		if(flag == flag_true){
			$('#bonusModeCashback').prop('checked',true);
			$('#cashbackBonusPercentage').val("<?=$data['cashback_percentage']?>");
			$('#maxCashbackBonus').val("<?=$data['cashback_maxbonus']?>");
			$('#maxDailyCashbackBonus').val("<?=$data['cashback_daily_maxbonus']?>");
			$('#cashbackBonusPercentage').prop("disabled",false);
			$('#maxCashbackBonus').prop("disabled",false);
			$('#maxDailyCashbackBonus').prop("disabled",false);
		}else{
			$('#bonusModeCashback').prop('checked',false);
			$('#cashbackBonusPercentage').val("");
			$('#maxCashbackBonus').val("");
			$('#maxDailyCashbackBonus').val("");
			$('#cashbackBonusPercentage').prop("disabled",true);
			$('#maxCashbackBonus').prop("disabled",true);
			$('#maxDailyCashbackBonus').prop("disabled",true);
		}
	}

	function bonusModeOptionBirthday(flag){
		if(flag == flag_true){
		  $('#bonusModeBirthday').prop('checked',true);
		  $('#birthdayBonusAmount').prop('disabled',false);
		  $('#birthdayBonusAmount').val("<?=$data['birthday_bonus_amount']?>");
		  $('#bonusExpirationPeriod').prop('disabled',false);
		  $('#bonusExpirationPeriod').val("<?=$data['birthday_bonus_expiration_datetime']?>");
		  $('#birthdayBonusWithdrawCondition').prop('disabled',false);
		  $('#birthdayBonusWithdrawCondition').val("<?=$data['birthday_bonus_withdraw_condition']?>");
		}else{
		  $('#bonusModeBirthday').prop('checked',false);
		  $('#birthdayBonusAmount').prop('disabled',true);
		  $('#birthdayBonusAmount').val("");
		  $('#bonusExpirationPeriod').prop('disabled',true);
		  $('#bonusExpirationPeriod').val("");
		  $('#birthdayBonusWithdrawCondition').prop('disabled',true);
		  $('#birthdayBonusWithdrawCondition').val("");
		}
	  }

	function setCashbackTarget(theCashbackTarget){

		$('input:radio[name="cashbackTarget"]').prop('checked',false);
		if(theCashbackTarget == "<?=Group_level::CASHBACK_TARGET_PLAYER?>"){
			$('input:radio[name="cashbackTarget"][value="<?=Group_level::CASHBACK_TARGET_PLAYER?>"]').prop('checked',true);
		}else if(theCashbackTarget == "<?=Group_level::CASHBACK_TARGET_AFFILIATE?>"){
			$('input:radio[name="cashbackTarget"][value="<?=Group_level::CASHBACK_TARGET_AFFILIATE?>"]').prop('checked',true);
		}

	}

	function bonusModeOptionDeposit(flag){
		if(flag == flag_true){
			$('#bonusModeDeposit').prop('checked',true);

			var firstTimeDepositBonusOptionType = "<?=$data['firsttime_dep_type']?>";
			var succeedingDepositBonusOptionType = "<?=$data['succeeding_dep_type']?>";

			//1st time deposit bonus
			if(firstTimeDepositBonusOptionType == fixBonusAmt){
				$('#firstTimeDepositBonusUpToSec').hide();
				$('#firstTimeDepositBonusOption1').prop('checked',true);
				$('#firstTimeDepositBonusOption1').prop('disabled',false);
				$('#firstTimeDepositBonusOption2').prop('checked',false);
				$('#firstTimeDepositBonusOption2').prop('disabled',false);
				$('#firstTimeDepositBonus').val("<?=$data['firsttime_dep_bonus']?>");
				$('#firstTimeDepositBonus').prop('disabled',false);
				$('#firstTimeDepositBonusUpTo').prop('disabled',false);
			}
			else if(firstTimeDepositBonusOptionType == byPercentage){
				$('#firstTimeDepositBonusOption1').prop('checked',false);
				$('#firstTimeDepositBonusOption1').prop('disabled',false);
				$('#firstTimeDepositBonusOption2').prop('checked',true);
				$('#firstTimeDepositBonusOption2').prop('disabled',false);
				$('#firstTimeDepositBonusUpToSec').show();
				$('#firstTimeDepositBonus').val("<?=$data['firsttime_dep_bonus']?>");
				$('#firstTimeDepositBonus').prop('disabled',false);
				$('#firstTimeDepositBonusUpTo').val("<?=$data['firsttime_dep_percentage_upto']?>");
				$('#firstTimeDepositBonusUpTo').prop('disabled',false);
			}else{
				$('#firstTimeDepositBonusUpToSec').hide();
				$('#firstTimeDepositBonusOption1').prop('checked',true);
				$('#firstTimeDepositBonusOption1').prop('disabled',false);
				$('#firstTimeDepositBonusOption2').prop('disabled',false);
				$('#firstTimeDepositBonusOption2').prop('checked',false);
				$('#firstTimeDepositBonus').val("");
				$('#firstTimeDepositBonus').prop('disabled',false);
				$('#firstTimeDepositBonusUpTo').prop('disabled',false);
			}

			//succeeding deposit bonus
			if(succeedingDepositBonusOptionType == fixBonusAmt){
				$('#succeedingDepositBonusUpToSec').hide();
				$('#succeedingDepositBonusOption1').prop('checked',true);
				$('#succeedingDepositBonusOption1').prop('disabled',false);
				$('#succeedingDepositBonusOption2').prop('checked',false);
				$('#succeedingDepositBonusOption2').prop('disabled',false);
				$('#succeedingDepositBonus').val("<?=$data['succeeding_dep_bonus']?>");
				$('#succeedingDepositBonus').prop('disabled',false);
				$('#succeedingDepositBonusUpTo').prop('disabled',false);
			}
			else if(succeedingDepositBonusOptionType == byPercentage){
				$('#succeedingDepositBonusOption1').prop('checked',false);
				$('#succeedingDepositBonusOption1').prop('disabled',false);
				$('#succeedingDepositBonusOption2').prop('checked',true);
				$('#succeedingDepositBonusOption2').prop('disabled',false);
				$('#succeedingDepositBonusUpToSec').show();
				$('#succeedingDepositBonus').val("<?=$data['succeeding_dep_bonus']?>");
				$('#succeedingDepositBonusUpTo').val("<?=$data['succeeding_dep_percentage_upto']?>");
				$('#succeedingDepositBonus').prop('disabled',false);
				$('#succeedingDepositBonusUpTo').prop('disabled',false);
			}else{
				$('#succeedingDepositBonusUpToSec').hide();
				$('#succeedingDepositBonusOption1').prop('checked',true);
				$('#succeedingDepositBonusOption1').prop('disabled',false);
				$('#succeedingDepositBonusOption2').prop('checked',false);
				$('#succeedingDepositBonusOption2').prop('disabled',false);
				$('#succeedingDepositBonus').val("");
				$('#succeedingDepositBonus').prop('disabled',false);
				$('#succeedingDepositBonusUpTo').prop('disabled',false);
			}

			//1st time withdraw condition
			$('#firstTimeDepositWithdrawCondition').val("<?=$data['firsttime_dep_withdraw_condition'] ? $data['firsttime_dep_withdraw_condition'] : 0?>");
			$('#succeedingDepositWithdrawCondition').val("<?=$data['succeeding_dep_withdraw_condition'] ? $data['succeeding_dep_withdraw_condition'] : 0?>");
			$('#firstTimeDepositWithdrawCondition').prop('disabled',false);
			$('#succeedingDepositWithdrawCondition').prop('disabled',false);
		}else{
			$('#bonusModeDeposit').prop('checked',false);

			//1st time deposit
			$('#firstTimeDepositBonusUpToSec').hide();
			$('#firstTimeDepositBonusOption1').prop('checked',false);
			$('#firstTimeDepositBonusOption1').prop('disabled',true);
			$('#firstTimeDepositBonusOption2').prop('checked',false);
			$('#firstTimeDepositBonusOption2').prop('disabled',true);
			$('#firstTimeDepositBonus').val("");
			$('#firstTimeDepositBonus').prop('disabled',true);
			$('#firstTimeDepositBonusUpTo').prop('disabled',true);
			$('#firstTimeDepositBonusUpTo').val("");
			//1st time withdrawal condition
			$('#firstTimeDepositWithdrawCondition').prop('disabled',true);
			$('#firstTimeDepositWithdrawCondition').val("");
			//succeeding deposit
			$('#succeedingDepositBonusUpToSec').hide();
			$('#succeedingDepositBonusOption1').prop('checked',false);
			$('#succeedingDepositBonusOption1').prop('disabled',true);
			$('#succeedingDepositBonusOption2').prop('checked',false);
			$('#succeedingDepositBonusOption2').prop('disabled',true);
			$('#succeedingDepositBonus').val("");
			$('#succeedingDepositBonus').prop('disabled',true);
			$('#succeedingDepositBonusUpTo').prop('disabled',true);
			$('#succeedingDepositBonusUpTo').val("");
			//succeeding withdrawal condition
			$('#succeedingDepositWithdrawCondition').prop('disabled',true);
			$('#succeedingDepositWithdrawCondition').val("");
		}

	}

	function choosefirstTimeDepositBonusOption(type){
		if(type == fixBonusAmt){
			$('#firstTimeDepositBonusUpToSec').hide();
		}else{
			$('#firstTimeDepositBonusUpToSec').show();
			$('#firstTimeDepositBonusUpTo').val("");
			$('#firstTimeDepositBonusUpTo').show()
		}
	}

	function chooseSucceedingDepositBonusOption(type){
		if(type == fixBonusAmt){
			$('#succeedingDepositBonusUpToSec').hide();
		}else{
			$('#succeedingDepositBonusUpToSec').show();
			$('#succeedingDepositBonusUpTo').show()
			$('#succeedingDepositBonusUpTo').val("");
		}
	}

	function resizeGameOptionWindow(windowSize){
		if(windowSize == 'small'){
			$("#gameOptionWindow").css("height","99px");

			//toggle treeview
			$( '.tree li' ).each( function() {
				$( this ).toggleClass( 'active' );
				$( this ).children( 'ul' ).slideToggle( 'fast' );
			});
		}else if(windowSize == 'medium'){
			$("#gameOptionWindow").css("height","500px");
		}else if(windowSize == 'large'){
			$("#gameOptionWindow").css("height","1000px");
		}
		else{
			//toggle treeview
			$("#gameOptionWindow").css("height","100%");

			$( '.tree li' ).each( function() {
				$( this ).toggleClass( 'active' );
				$( this ).children( 'ul' ).slideToggle( 'fast' );
			});
		}
	}


</script>
