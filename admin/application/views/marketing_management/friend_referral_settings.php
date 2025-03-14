
<style type="text/css">
	#saveFriendReferralSettings input[type="text"],
	#saveFriendReferralSettings .form-inline .form-control{
		min-width: 205px;
		text-align: right;
	}
</style>
<div class="row">
	<div class="col-md-8">
		<div class="panel panel-primary">

			<div class="panel-heading">
				<h4 class="panel-title">
					<i class="fa fa-user-plus"></i> <?=lang('mark.friendrefset')?>
				</h4>
			</div>

			<div class="panel-body">
				<form class="form-horizontal" id="saveFriendReferralSettings" method="post" autocomplete="off">
					<table class="table">
						<thead>
							<tr>
								<th colspan="2"><?=lang('con.referrerBonus')?></th>
							</tr>
						</thead>
						<tbody>
							<?php if($this->utils->getConfig('disabled_same_ips_with_inviter')):?>
							<tr>
								<td nowrap="nowrap" style="border-top: none;">
									<?=lang('con.restrictInvited.sameIp')?>
									&nbsp;<i class="glyphicon glyphicon-info-sign dcp-tooltip" data-toggle="tooltip" data-placement="auto" data-html="true" data-original-title="<?=lang('con.restrictInvited.sameIpHint')?>"></i>
								</td>
								<td align="right" class="form-inline" style="border-top: none;"><input type="checkbox" name="disabled_same_ips_with_inviter" value="1" <?php if($this->utils->safeGetArray($friend_referral_settings, 'disabled_same_ips_with_inviter', '0')=='1') echo 'checked'; ?>></td>
							</tr>
							<?php endif;?>

							<tr>
								<td nowrap="nowrap"><?=lang('mark.bonusamt')?> <span class="text-danger">*</span></td>
								<td align="right" class="form-inline">
									<input type="text" class="form-control text-right number_only" name="bonus_amount" id="bonus_amount" required="required" value="<?=$this->utils->safeGetArray($friend_referral_settings, 'bonusAmount', '0.00')?>">
								</td>
							</tr>

							<?php if($this->utils->getConfig('enabled_referrer_bonus_rate')):?>
							<tr>
								<td nowrap="nowrap" style="border-top: none;"><?=lang('mark.bonusrate_in_referrer')?> <span class="text-danger">*</span>
								&nbsp;<i class="glyphicon glyphicon-info-sign dcp-tooltip" data-toggle="tooltip" data-placement="auto" data-html="true" data-original-title="<?=lang('bonus_rate_title_friend_referral')?>"></i>
							</td>
								<td align="right" class="form-inline">
									<input type="text" class="form-control text-right number_only" name="bonus_rate_in_referrer" id="bonus_rate_in_referrer" required="required" value="<?=$this->utils->safeGetArray($friend_referral_settings, 'bonusRateInReferrer', '0.00')?>">
								</td>
							</tr>
							<?php endif; ?>

							<?php if($this->utils->getConfig('enabled_referred_bonus')):?>
							<tr>
								<td nowrap="nowrap" style="border-top: none;"><?=lang('mark.bonusamt_in_referred')?> <span class="text-danger">*</span></td>
								<td align="right" class="form-inline">
									<input type="text" class="form-control text-right number_only" name="bonus_amount_in_referred" id="bonus_amount_in_referred" required="required" value="<?=$this->utils->safeGetArray($friend_referral_settings, 'bonusAmountInReferred', '0.00')?>">
								</td>
							</tr>
							<?php endif; ?>

							<?php if($this->utils->isEnabledFeature('enable_friend_referral_cashback') && false){?>
								<tr>
									<td nowrap="nowrap"><?=lang('Cashback Rate')?> (%) <span class="text-danger">*</span></td>
									<td align="right" class="form-inline">
										<input type="text" class="form-control text-right number_only" name="cashback_rate" id="cashback_rate" required="required" value="<?=$this->utils->safeGetArray($friend_referral_settings, 'cashback_rate', '0.00')?>">
									</td>
								</tr>
							<?php }?>

						</tbody>
					</table>
					<table class="table">
						<thead>
							<tr>
								<th colspan="2"><?=lang('con.referredReq')?>
									<?php if($this->utils->getConfig('enabled_friend_referral_referred_single_choice')):?>
										&nbsp;
										<input type="checkbox" name="enabled_referred_single_choice" value="1" <?php if($this->utils->safeGetArray($friend_referral_settings, 'enabled_referred_single_choice', '0')=='1') echo 'checked'; ?>>
										<i class="glyphicon glyphicon-info-sign dcp-tooltip" data-toggle="tooltip" data-placement="auto" data-html="true" data-original-title="<?=lang('con.referredReq.singleChoiceHint')?>"></i>
									<?php endif;?>
								</th>
							</tr>
						</thead>
						<tbody>
							<tr>
								<td nowrap="nowrap"><?=lang('con.minBet')?> <span class="text-danger">*</span></td>
								<td align="right" class="form-inline">
									<input type="text" class="form-control text-right number_only" name="bet" id="bet" required="required" value="<?=$this->utils->safeGetArray($friend_referral_settings, 'ruleInBet', '0.00')?>">
								</td>
							</tr>
							<tr>
								<td nowrap="nowrap" style="border-top: none;"><?=lang('con.minDep')?> <span class="text-danger">*</span></td>
								<td align="right" class="form-inline" style="border-top: none;">
									<input type="text" class="form-control text-right number_only" name="deposit" id="deposit" required="required" value="<?=$this->utils->safeGetArray($friend_referral_settings, 'ruleInDeposit', '0.00')?>">
								</td>
							</tr>
							<?php if($this->utils->getConfig('enable_registration_date_on_friend_referraL_setting')):?>
							<tr>
								<td nowrap="nowrap" style="border-top: none;"><?=lang('Sign Up')?> <?=lang('Date')?>
								<!-- <span class="badge" data-toggle="tooltip"
            						data-placement="top" title="" data-original-title="<?=lang('sign_up_title_friend_referral')?>">ℹ</span> -->
            					&nbsp;<i class="glyphicon glyphicon-info-sign dcp-tooltip" data-toggle="tooltip" data-placement="auto" data-html="true" data-original-title="<?=lang('sign_up_title_friend_referral')?>"></i>
            					</td>
								<td align="right" class="form-inline" style="border-top: none;">
			                        <input class="form-control dateInput user-success"  id="datetime_range" data-start="#registered_from" data-end="#registered_to" data-time="false" data-future="true" required="required" />
			                        <input type="hidden" id="registered_from" name="registered_from" value="<?=$this->utils->safeGetArray($friend_referral_settings, 'registered_from', $default_registered_from)?>"/>
			                        <input type="hidden" id="registered_to" name="registered_to" value="<?=$this->utils->safeGetArray($friend_referral_settings, 'registered_to', $default_registered_to)?>"/>
								</td>
							</tr>
							<?php endif;?>
							<?php if($this->utils->getConfig('enable_friend_referral_referred_deposit_count')):?>
                                <tr>
                                    <td nowrap="nowrap" style="border-top: none;"><?=lang('con.referredReq.minDepCnt')?> <span class="text-danger">*</span></td>
                                    <td align="right" class="form-inline">
                                        <input type="text" class="form-control text-right number_only" name="referredDepositCount" id="referredDepositCount" required="required" value="<?=$this->utils->safeGetArray($friend_referral_settings, 'referredDepositCount', '0')?>">
                                    </td>
                                </tr>
                            <?php endif;?>
						</tbody>
					</table>
					<table class="table">
						<thead>
							<tr>
								<th colspan="2"><?=lang('con.referrerLimit')?></th>
							</tr>
						</thead>
						<tbody>
							<tr>
                                <?php if($this->utils->getConfig('enable_change_referral_limit_to_monthly')):?>
                                    <td nowrap="nowrap"><?= lang('con.enableReferralLimit.monthly') ?></td>
                                    <td align="right" class="form-inline"><input type="checkbox" name="enabled_referral_limit_monthly" value="1" <?php if($this->utils->safeGetArray($friend_referral_settings, 'enabled_referral_limit_monthly', '0')=='1') echo 'checked'; ?>></td>
                                <?php else:?>
                                    <td nowrap="nowrap"><?= lang('con.enableReferralLimit') ?></td>
                                    <td align="right" class="form-inline"><input type="checkbox" name="enabled_referral_limit" value="1" <?php if($this->utils->safeGetArray($friend_referral_settings, 'enabled_referral_limit', '0')=='1') echo 'checked'; ?>></td>
                                <?php endif;?>
							</tr>
							<input type="hidden" name="max_referral_count" value="<?=$this->utils->safeGetArray($friend_referral_settings, 'max_referral_count', '0')?>">
							<tr>
								<td nowrap="nowrap" style="border-top: none;"><?=lang('con.maxReleasedBonusCount')?> <span class="text-danger">*</span></td>
								<td align="right" class="form-inline" style="border-top: none;">
									<input type="text" class="form-control text-right number_only" name="max_referral_released" id="max_referral_released" required="required" value="<?=$this->utils->safeGetArray($friend_referral_settings, 'max_referral_released', '0')?>">
								</td>
							</tr>

						</tbody>
					</table>
					<table class="table">
						<thead>
							<tr>
								<th colspan="2"><?=lang('con.referrerReq')?></th>
							</tr>
						</thead>
						<tbody>
							<tr>
								<td nowrap="nowrap"><?=lang('con.minBetWithdraw')?></td>
								<td align="right" class="form-inline">
									<input type="text" class="form-control text-right number_only" name="withdraw_condition" id="withdraw_condition" required="required" value="<?=$this->utils->safeGetArray($friend_referral_settings, 'withdrawalCondition', '0.00')?>">
									<div class="help-block"><?=lang('con.sinceRef')?></div>
								</td>
							</tr>
                            <?php if($this->utils->getConfig('enable_friend_referral_referrer_deposit_count')):?>
                                <tr>
                                    <td nowrap="nowrap"><?=lang('con.referrerReq.minDepCnt')?> <span class="text-danger">*</span></td>
                                    <td align="right" class="form-inline">
                                        <input type="text" class="form-control text-right number_only" name="referrerDepositCount" id="referrerDepositCount" required="required" value="<?=$this->utils->safeGetArray($friend_referral_settings, 'referrerDepositCount', '0')?>">
										<?php if($this->utils->getConfig('calculate_referrer_deposit_bet_by_signup_date')):?>
											<div class="help-block"><?=lang('con.sinceReferrerBetCountSignupDate')?></div>
										<?php endif;?>
                                    </td>
                                </tr>
                            <?php endif;?>
                            <?php if($this->utils->getConfig('enable_friend_referral_referrer_bet')):?>
                                <tr>
                                    <td nowrap="nowrap" style="border-top: none;"><?=lang('con.referrerReq.minBet')?> <span class="text-danger">*</span></td>
                                    <td align="right" class="form-inline" style="border-top: none;">
                                        <input type="text" class="form-control text-right number_only" name="referrerBet" id="referrerBet" required="required" value="<?=$this->utils->safeGetArray($friend_referral_settings, 'referrerBet', '0.00')?>">
										<?php if($this->utils->getConfig('calculate_referrer_deposit_bet_by_signup_date')):?>
											<div class="help-block"><?=lang('con.sinceReferrerBetSignupDate')?></div>
										<?php endif;?>
                                    </td>
                                </tr>
                            <?php endif;?>
                            <?php if($this->utils->getConfig('enable_friend_referral_referrer_deposit')):?>
                                <tr>
                                    <td nowrap="nowrap" style="border-top: none;"><?=lang('con.referrerReq.minDep')?> <span class="text-danger">*</span></td>
                                    <td align="right" class="form-inline" style="border-top: none;">
                                        <input type="text" class="form-control text-right number_only" name="referrerDeposit" id="referrerDeposit" required="required" value="<?=$this->utils->safeGetArray($friend_referral_settings, 'referrerDeposit', '0.00')?>">
										<?php if($this->utils->getConfig('calculate_referrer_deposit_bet_by_signup_date')):?>
											<div class="help-block"><?=lang('con.sinceReferrerDepositSignupDate')?></div>
										<?php endif;?>
                                    </td>
                                </tr>
                            <?php endif;?>

						</tbody>
					</table>

                    <?php if(!$this->utils->getConfig('hide_friend_referral_setting_bind_promo')):?>
					<table class="table">
						<thead>
							<tr>
								<th colspan="2"><?=lang('Bind Promo CMS')?></th>
							</tr>
						</thead>
						<tbody>
							<tr>
								<td nowrap="nowrap"><?=lang('Select Promo')?></td>
								<td align="right">
                                    <select class="form-control" name="promo_cms_id" id="promoCmsId" style="width:205px" >
                                        <option value="">-----<?php echo lang('N/A'); ?>-----</option>
                                        <?php
											if (!empty($promoCms)) {
												foreach ($promoCms as $v): ?>
                                                <option value="<?php echo $v['promoCmsSettingId']; ?>" <?php echo $v['promoCmsSettingId'] == $this->utils->safeGetArray($friend_referral_settings, 'promo_id') ? "selected" : "" ?>><?php echo $v['promoName'] ?></option>
                                            <?php endforeach;
										}?>
                                    </select>
								</td>
							</tr>
						</tbody>
					</table>
                    <?php endif;?>

                    <?php if($this->utils->isEnabledFeature('enable_edit_upload_referral_detail')):?>
					<table class="table">
						<tbody>
							<tr>
	                            <div class="col-md-12 form-group required" >
	                                <label for="referralDetails" style="font-weight:bold;"><?=lang('mark.referraldetails');?>: </label>
	                                <span class="text-danger error-editReferralDetails" hidden><?=sprintf(lang('gen.error.required'), lang('mark.referraldetails'))?></span>
	                                <div style="background-color:#fff;">
	                                    <input name="referralDetails" type="hidden" class="referralDetails" required/>
	                                    <div class="summernote" id="editReferralDetails"></div>
	                                    <input type="hidden" name="referralDetailsLength" id="referralDetailsLength">
	                                </div>
	                            </div>
							</tr>
						</tbody>
					</table>
                    <?php endif;?>
				</form> <!-- /#saveFriendReferralSettings -->
			</div>

			<div class="panel-footer text-right">
				<span class="btn btn-sm btn-scooter preview_btn" style="display: none;" onclick="showEditReferralPreview()" data-toggle="modal" data-target=".referralCmsPreview"><?=lang("Preview") ?></span>
				<button type="submit" form="saveFriendReferralSettings" class="btn btn-portage" id="edit_referral_submit"><?=lang('lang.save')?></button>
			</div>

		</div>
	</div>
</div>

<!-- Referral CMS Preview Modal Start -->
<?php if($this->utils->isEnabledFeature('enable_edit_upload_referral_detail')):?>
<div class="modal fade referralCmsPreview" tabindex="-1" role="dialog" aria-labelledby="referralCmsPreview" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <b><?=lang("Preview") ?></b>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="bannerPreviewContainer">
                        <div class="row referralDetailsSec">
                            <div class="col-md-12">
                                <div class="col-md-12">
                                    <div class="addReferralDetailsSec">
                                        <span class="referralDetailsTxt"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal"><?=lang("Close") ?></button>
                </div>
            </div>
        </div>
    </div>
</div>
<br/>
<!-- referral CMS Preview Modal End -->

<script type="text/javascript">

	function showEditReferralPreview(){
        $('.referralDetailsTxt').html($("#editReferralDetails").code());
    }

    $("#edit_referral_submit").click(function () {

        var notValidate = false;
        var referralDetails = $("#editReferralDetails").code();
        var encodeReferralDetails = encode64(encodeURIComponent(referralDetails));
        var referralDetailsLength = encodeReferralDetails.length;

        $("#editReferralDetails").code(encodeReferralDetails);
        $("#referralDetailsLength").val(referralDetailsLength);
        $(".error-editReferralDetails").hide();

        if(referralDetails.length == 0) {
            $(".error-editReferralDetails").show();
            notValidate = true;
        }

        if(notValidate){
            alert("<?=lang('con.d02')?>");
        }else {
            $("#saveFriendReferralSettings").submit();
        }

        return false;

    });


    $('#saveFriendReferralSettings').submit( function(e) {
              var code = $('#editReferralDetails').code();
              // console.log('editcmspromo: '+code);
              //if(code != '<p><br></p>'){
                $('.referralDetails').val(code);
                return true;
              //}
          });

    $('#editReferralDetails').summernote({
              height: 300   //set editable area's height
            });

    var keyStr = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=";
    //turn string to base64 encode string
    function encode64(input) {
        var output = "";
        var chr1, chr2, chr3 = "";
        var enc1, enc2, enc3, enc4 = "";
        var i = 0;

        do {
            chr1 = input.charCodeAt(i++);
            chr2 = input.charCodeAt(i++);
            chr3 = input.charCodeAt(i++);

            enc1 = chr1 >> 2;
            enc2 = ((chr1 & 3) << 4) | (chr2 >> 4);
            enc3 = ((chr2 & 15) << 2) | (chr3 >> 6);
            enc4 = chr3 & 63;

            if (isNaN(chr2)) {
                enc3 = enc4 = 64;
            } else if (isNaN(chr3)) {
                enc4 = 64;
            }

            output = output +
                keyStr.charAt(enc1) +
                keyStr.charAt(enc2) +
                keyStr.charAt(enc3) +
                keyStr.charAt(enc4);
            chr1 = chr2 = chr3 = "";
            enc1 = enc2 = enc3 = enc4 = "";
        } while (i < input.length);

        return output;
    }

    $(document).ready(function () {
		var referralDetails = `<?=$this->utils->safeGetArray($friend_referral_settings, 'referralDetails', '')?>`;

		$("#editReferralDetails").next('.note-editor').find('.note-editable').html(_pubutils.decodeHtmlEntities(referralDetails,'default'));
		$('.preview_btn').show();
	});
</script>
<?php endif;?>

<script>
$(document).ready(function () {
//submenu
    $('#collapseSubmenu').addClass('in');
    $('#friendReferralSettings').addClass('active');

    var registered_from = '<?=$this->utils->safeGetArray($friend_referral_settings, 'registered_from', '')?>';
    var registered_to = '<?=$this->utils->safeGetArray($friend_referral_settings, 'registered_to', '')?>';

    if(registered_from.length == 0 || registered_to.length == 0) {
    	var dateInput = $('#datetime_range');
    	$(dateInput).val('');
    	$(dateInput.data('start')).val('');
       	$(dateInput.data('end')).val('');
    }

});
</script>