<div class="modal fade security-modal" id="security-withdrawal-forgot-password-bysms" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title"><?= lang('Security') ?><small> - <?=lang('Find Withdrawal password by SMS')?></small></h4>
            </div>
            <form>
                <div class="panel-body">
                    <div id="div_forgot_withdrawal_pass_bysms_msg"></div>
                    	<label><?= lang('Please enter verification code:') ?></label>
						<input type="text" id="reset_code" name="reset_code" class="form-control" placeholder="<?=lang('Verification Code')?>" length="6" style="margin-bottom: 5px" autocomplete="one-time-code"/>

						<?php // NEW PASSWORD ?>
						<label><?= lang('New Password') ?></label>
						<span class="pull-right"><?= lang('Please type in 4-12 digits of numbers or letters') ?></span>
						<input type="password" id="new_w_password" name="new_w_password" placeholder="<?=lang('New Password')?>" class="form-control" style="margin-bottom: 5px">
                        <div class="mb20 hide" id="new_w_password-note-field">
						</div>

						<?php // CONFIRM NEW PASSWORD ?>
						<label><?= lang('Confirm Password:') ?></label>
						<span class="pull-right"><?= lang('Please type in 4-12 digits of numbers or letters') ?></span>
                        <input type="password" id="cfnew_password" name="cfnew_password" placeholder="<?=lang('Confirm New Password')?>" class="form-control" style="margin-bottom: 5px">
						<div class="mb20 hide" id="cfnew_password-note-field">
                            <p class="pl15 mb0"><i id="password_confirm" class="icon-warning red f16 mr5"></i><?=lang('validation.retypeNewPassword')?></p>
                        </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn mc-btn form-control submit-btn" id="bysmsbtn" onclick="return PlayerSecurity.changeWithdrawalPasswordBySms();"><?= lang('lang.submit') ?></button>
                    <button type="button" class="btn mc-btn form-control" data-dismiss="modal"><?= lang('lang.cancel') ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<script type="text/javascript">
	function checkVerifyphone(){
		var isPhoneVerified = <?= $isPhoneVerified ?'1':'0'; ?>;

		if (!isPhoneVerified) {
			alert('<?= lang('Please verify your mobile number first') ?>');
			// $('#security-withdrawal-forgot-password-bysms').hide();
			return false;
		}else{
			PlayerSecurity.sendMobileVerificationFindWithdrawalPassword('<?=$player['contactNumber']?>', '<?= sprintf(lang('msg.send.verification'),$player['contactNumber']) ?>', '<?= lang('mod.mobilePhoneAsContact') ?>' ,'<?=$player['dialing_code']?>');
			//$('#security-withdrawal-forgot-password-bysms').modal('show');
		}
	}
</script>