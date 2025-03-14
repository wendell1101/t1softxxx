<?php
	$disable_cashback_on_register = $this->utils->getConfig('disable_cashback_on_register');
	$disable_promotion_on_register = $this->utils->getConfig('disable_promotion_on_register');
	$hide_dispatch_account_level_on_registering_in_aff = $this->utils->getConfig('hide_dispatch_account_level_on_registering_in_aff');
	$edit_dispatch_account_level_id_on_registering = $this->permissions->checkPermissions('edit_dispatch_account_level_id_on_registering');

?><div class="row">
	<div class="col-md-12" id="toggleView">
		<div class="panel panel-primary">
			<div class="panel-heading">
				<h4 class="panel-title">
					<i class="icon-info"></i> <strong><?=lang('aff.ai01');?></strong>
					<a href="<?=site_url('affiliate_management/userInformation/' . $affiliates['affiliateId'])?>" class="btn btn-danger btn-xs pull-right" id="view_affiliate"><span class="glyphicon glyphicon-remove"></span></a>
				</h4>
			</div>

			<div class="panel-body" id="affiliate_info">
				<!-- Personal Info -->
				<form method="POST" action="<?=site_url('affiliate_management/verifyEditAffiliate/' . $affiliates['affiliateId'] . '/' . $affiliates['affiliatePayoutId'])?>" accept-charset="utf-8">
		            <input type="hidden" name="affiliateId" class="form-control input-sm" value="<?=$affiliates['affiliateId'];?>">
					<div class="row">
	                    <div class="col-md-12">
	                        <div class="table-responsive overflow-visible">
	                            <table class="table table-hover table-bordered">
	                            	<tr>
	                            		<th class="active col-md-2"><?=lang('aff.ap03');?></th>
	                            		<td class="col-md-4"><?=$affiliates['username']?></td>
	                            		<th class="active col-md-2"><?=lang('aff.ai11');?></th>
	                            		<td class="col-md-4">
	                            			<input type="text" name="zip" id="zip" class="form-control input-sm letters_numbers_only" value="<?=(set_value('zip') != null) ? set_value('zip') : $affiliates['zip'];?>">
											<span class="text-danger"><?php echo form_error('zip'); ?></span>
	                            		</td>
	                            	</tr>
	                            	<tr>
	                            		<th class="active col-md-2"><?=lang('aff.ai02');?></th>
	                            		<td class="col-md-4">
	                            			<input type="text" name="firstname" id="firstname" class="form-control input-sm" value="<?=(set_value('firstname') != null) ? set_value('firstname') : $affiliates['firstname'];?>">
	                            			<span class="text-danger"><?php echo form_error('firstname'); ?></span>
	                            		</td>
	                            		<th class="active col-md-2"><?=lang('aff.ai08');?></th>
	                            		<td class="col-md-4">
	                            			<input type="hidden" name="email_db" id="email_db" class="form-control input-sm" value="<?=(set_value('email') != null) ? set_value('email') : $affiliates['email'];?>">
											<input type="text" name="email" id="email" class="form-control input-sm" value="<?=(set_value('email') != null) ? set_value('email') : $affiliates['email'];?>">
											<span class="text-danger"><?php echo form_error('email'); ?>
	                            		</td>
	                            	</tr>
	                            	<tr>
	                            		<th class="active col-md-2"><?=lang('aff.ai03');?></th>
	                            		<td class="col-md-4">
	                            			<input type="text" name="lastname" id="lastname" class="form-control input-sm" value="<?=(set_value('lastname') != null) ? set_value('lastname') : $affiliates['lastname'];?>">
											<span class="text-danger"><?php echo form_error('lastname'); ?></span>
	                            		</td>
	                            		<th class="active col-md-2"><?=lang('aff.ai07');?></th>
	                            		<td class="col-md-4">
	                            			<input type="text" name="occupation" id="occupation" class="form-control input-sm" value="<?=(set_value('occupation') != null) ? set_value('occupation') : $affiliates['occupation'];?>">
											<span class="text-danger"><?php echo form_error('occupation'); ?></span>
	                            		</td>
	                            	</tr>
	                            	<tr>
	                            		<th class="active col-md-2"><?=lang('aff.ai04');?></th>
	                            		<td class="col-md-4">
	                            			<input type="text" name="birthday" id="birthday" class="form-control input-sm datepicker" value="<?=(set_value('birthday') != null) ? set_value('birthday') : date("Y-m-d", strtotime($affiliates['birthday']));?>">
											<span class="text-danger"><?php echo form_error('birthday'); ?></span>
	                            		</td>
	                            		<th class="active col-md-2"><?=lang('aff.ai06');?></th>
	                            		<td class="col-md-4">
	                            			<input type="text" name="company" id="company" class="form-control input-sm" value="<?=(set_value('company') != null) ? set_value('company') : $affiliates['company'];?>">
											<span class="text-danger"><?php echo form_error('company'); ?></span>
	                            		</td>
	                            	</tr>
	                            	<tr>
	                            		<th class="active col-md-2"><?=lang('aff.ai05');?></th>
	                            		<td class="col-md-4">
	                            			<select name="gender" id="gender" class="form-control input-sm">
												<option value="Male" <?=($affiliates['gender'] == 'Male') ? 'selected' : ''?> ><?=lang('aff.ai76');?></option>
												<option value="Female" <?=($affiliates['gender'] == 'Female') ? 'selected' : ''?> ><?=lang('aff.ai77');?></option>
											</select>
											<span class="text-danger"><?php echo form_error('gender'); ?></span>
	                            		</td>
	                            		<th class="active col-md-2"><?=lang('aff.ai16');?></th>
	                            		<td class="col-md-4">
	                            			<select name="imtype1" id="imtype1" class="form-control input-sm" onchange="imCheck(this.value, '1');">
												<option value=""><?=lang('aff.ai81');?></option>
												<?php if($this->config->item('IM_list')):
												    foreach($this->config->item('IM_list') as $im){
												        echo '<option value=\''.lang($im).'\' '. ($affiliates['imType1'] == $im ? 'selected' : '') .'>'.lang($im).'</option>';
												    }
												endif;?>
											</select>
											<span class="text-danger"><?php echo form_error('imType1'); ?></span>
	                            		</td>
	                            	</tr>
	                            	<tr>
	                            		<th class="active col-md-2"><?=lang('aff.ai14');?></th>
	                            		<td class="col-md-4">
                                            <div class="mobile_dialing_code">
                                                <select id="mobile_dialing_code" class="form-control selectpicker diling-code-field" name="mobile_dialing_code">
                                                    <option title="<?=lang('reg.77')?>" country="" value=""><?=lang('reg.77')?></option>
                                                    <?php if (! empty($frequentlyUsedCountryNumList)): ?>
                                                        <optgroup label="<?=lang('lang.frequentlyUsed')?>">
                                                            <?php foreach ($frequentlyUsedCountryNumList as $country => $nums) : ?>
                                                                <?php if (is_array($nums)) : ?>
                                                                    <?php foreach ($nums as $_nums) : ?>
                                                                        <option title="(+<?=$_nums?>)" country="<?=$country?>" value="<?=$_nums?>" <?= ($mobile_dailing_num == $_nums) ? 'selected' : '' ; ?>><?= sprintf("%s (+%s)", lang('country.'.$country), $_nums);?></option>
                                                                    <?php endforeach ; ?>
                                                                <?php else : ?>
                                                                    <option title="(+<?=$nums?>)" country="<?=$country?>" value="<?=$nums?>" <?= ($mobile_dailing_num == $nums) ? 'selected' : '' ; ?>><?= sprintf("%s (+%s)", lang('country.'.$country), $nums); ?></option>
                                                                <?php endif ; ?>
                                                            <?php endforeach ; ?>
                                                        </optgroup>
                                                    <?php endif ?>

                                                    <?php foreach ($countryNumList as $country => $nums) : ?>
                                                        <?php if (is_array($nums)) : ?>
                                                            <?php foreach ($nums as $_nums) : ?>
                                                                <option title="(+<?=$_nums?>)" country="<?=$country?>" value="<?=$_nums?>" <?= ($mobile_dailing_num == $_nums) ? 'selected' : '' ; ?>><?= sprintf("%s (+%s)", lang('country.'.$country), $_nums);?></option>
                                                            <?php endforeach ; ?>
                                                        <?php else : ?>
                                                            <option title="(+<?=$nums?>)" country="<?=$country?>" value="<?=$nums?>" <?= ($mobile_dailing_num == $nums) ? 'selected' : '' ; ?>><?= sprintf("%s (+%s)", lang('country.'.$country), $nums); ?></option>
                                                        <?php endif ; ?>
                                                    <?php endforeach ; ?>
                                                </select>
                                            </div>
                                            <div class="mobile_num">
    	                            			<input type="text" name="mobile" id="mobile" class="form-control input-sm number_only" value="<?=(set_value('mobile') != null) ? set_value('mobile') : $mobile_num;?>">
                                            </div>
                                            <div class="clearfix"></div>
											<span class="text-danger"><?php echo form_error('mobile'); ?></span>
	                            		</td>
	                            		<th class="active col-md-2"><?=lang('aff.ai17');?></th>
	                            		<td class="col-md-4">
	                            			<input type="text" name="im1" id="im1" class="form-control input-sm" value="<?=(set_value('im1') != null) ? set_value('im1') : $affiliates['im1'];?>" <?=($affiliates['imType1'] == null && $affiliates['im1'] == null) ? 'readonly' : ''?> >
											<span class="text-danger"><?php echo form_error('im1'); ?></span>
	                            		</td>
	                            	</tr>

	                            	<tr>
	                            		<th class="active col-md-2"><?=lang('aff.ai15');?></th>
	                            		<td class="col-md-4">
                                            <div class="phone_dialing_code">
                                                <select id="phone_dialing_code" class="form-control selectpicker diling-code-field" name="phone_dialing_code">
                                                    <option title="<?=lang('reg.77')?>" country="" value=""><?=lang('reg.77')?></option>
                                                    <?php if (! empty($frequentlyUsedCountryNumList)): ?>
                                                        <optgroup label="<?=lang('lang.frequentlyUsed')?>">
                                                            <?php foreach ($frequentlyUsedCountryNumList as $country => $nums) : ?>
                                                                <?php if (is_array($nums)) : ?>
                                                                    <?php foreach ($nums as $_nums) : ?>
                                                                        <option title="(+<?=$_nums?>)" country="<?=$country?>" value="<?=$_nums?>" <?= ($phone_dailing_num == $_nums) ? 'selected' : '' ; ?>><?= sprintf("%s (+%s)", lang('country.'.$country), $_nums);?></option>
                                                                    <?php endforeach ; ?>
                                                                <?php else : ?>
                                                                    <option title="(+<?=$nums?>)" country="<?=$country?>" value="<?=$nums?>" <?= ($phone_dailing_num == $nums) ? 'selected' : '' ; ?>><?= sprintf("%s (+%s)", lang('country.'.$country), $nums); ?></option>
                                                                <?php endif ; ?>
                                                            <?php endforeach ; ?>
                                                        </optgroup>
                                                    <?php endif ?>

                                                    <?php foreach ($countryNumList as $country => $nums) : ?>
                                                        <?php if (is_array($nums)) : ?>
                                                            <?php foreach ($nums as $_nums) : ?>
                                                                <option title="(+<?=$_nums?>)" country="<?=$country?>" value="<?=$_nums?>" <?= ($phone_dailing_num == $_nums) ? 'selected' : '' ; ?>><?= sprintf("%s (+%s)", lang('country.'.$country), $_nums);?></option>
                                                            <?php endforeach ; ?>
                                                        <?php else : ?>
                                                            <option title="(+<?=$nums?>)" country="<?=$country?>" value="<?=$nums?>" <?= ($phone_dailing_num == $nums) ? 'selected' : '' ; ?>><?= sprintf("%s (+%s)", lang('country.'.$country), $nums); ?></option>
                                                        <?php endif ; ?>
                                                    <?php endforeach ; ?>
                                                </select>
                                            </div>
                                            <div class="phone_num">
	                            			    <input type="text" name="phone" id="phone" class="form-control input-sm number_only" value="<?=(set_value('phone') != null) ? set_value('phone') : $phone_num;?>">
                                            </div>
                                            <div class="clearfix"></div>
											<span class="text-danger"><?php echo form_error('phone'); ?></span>
                                        </td>
	                            		<th class="active col-md-2"><?=lang('aff.ai18');?></th>
	                            		<td class="col-md-4">
	                            			<select name="imtype2" id="imtype2" class="form-control input-sm" onchange="imCheck(this.value, '2');">
												<option value=""><?=lang('aff.ai82');?></option>
												<?php if($this->config->item('IM_list')):
												    foreach($this->config->item('IM_list') as $im){
												        echo '<option value=\''.lang($im).'\' '. ($affiliates['imType2'] == $im ? 'selected' : '') .'>'.lang($im).'</option>';
												    }
												endif;?>
											</select>
											<span class="text-danger"><?php echo form_error('imType2'); ?></span>
	                            		</td>
	                            	</tr>
	                            	<tr>
	                            		<th class="active col-md-2"><?=lang('aff.ai10');?></th>
	                            		<td class="col-md-4">
	                            			<input type="text" name="address" id="address" class="form-control input-sm" value="<?=(set_value('address') != null) ? set_value('address') : $affiliates['address'];?>">
											<span class="text-danger"><?php echo form_error('address'); ?></span>
	                            		</td>
	                            		<th class="active col-md-2"><?=lang('aff.ai19');?></th>
	                            		<td class="col-md-4">
	                            			<input type="text" name="im2" id="im2" class="form-control input-sm" value="<?=(set_value('im2') != null) ? set_value('im2') : $affiliates['im2'];?>" <?=($affiliates['imType2'] == null && $affiliates['im2'] == null) ? 'readonly' : ''?> >
											<span class="text-danger"><?php echo form_error('im2'); ?></span>
	                            		</td>
	                            	</tr>
	                            	<tr>
	                            		<th class="active col-md-2"><?=lang('aff.ai09');?></th>
	                            		<td class="col-md-4">
	                            			<input type="text" name="city" id="city" class="form-control input-sm" value="<?=(set_value('city') != null) ? set_value('city') : $affiliates['city'];?>">
											<span class="text-danger"><?php echo form_error('city'); ?></span>
	                            		</td>
	                            		<th class="active col-md-2"><?=lang('aff.ai21');?></th>
	                            		<td class="col-md-4">
	                            			<input type="text" name="website" id="website" class="form-control input-sm" value="<?=(set_value('website') != null) ? set_value('website') : $affiliates['website'];?>">
											<span class="text-danger"><?php echo form_error('website'); ?></span>
	                            		</td>
	                            	</tr>
	                            	<tr>
	                            		<th class="active col-md-2"><?=lang('aff.ai13');?></th>
	                            		<td class="col-md-4">
	                            			<select name="country" id="country" class="form-control input-sm">
												<option value="">Select Country</option>
												<?php foreach (unserialize(COUNTRY_LIST) as $key) :?>
								                    <option value="<?=$key?>" <?=($affiliates['country'] == $key) ? 'selected' : ''?>><?=lang('country.' . $key)?></option>
								                <?php endforeach;?>
											</select>
											<span class="text-danger"><?php echo form_error('country'); ?></span>
	                            		</td>
	                            		<th class="active col-md-2"><?=lang('Parent Affiliate');?></th>
	                            		<td class="col-md-4">
                                            <?php if (!empty($affiliates_parent)): ?>
                                                <?=$affiliates_parent['username'];?>
                                            <?php endif; ?>
                                        <!--
                                        <select class="form-control" name="parent_id">
	                            			<?php if (!isset($is_parent)) {?>
	                            				<option value="0"><?php echo lang('Set as parent affiliate') ?></option>
	                            			<?php }?>
	                            			<?php if( ! empty($affiliates_parents) ):
												foreach ($affiliates_parents as $key) {?>
	                            				<option value="<?php echo $key['affiliateId']; ?>" <?php echo $isCheked = $parent_id == $key['affiliateId'] ? 'selected' : ''; ?>><?php echo $key['username']; ?></option>
	                            			<?php }
												endif;
											?>
	                            			</select>
											<span class="text-danger"><?php echo form_error('parent_username'); ?></span>
                                        -->
	                            		</td>
	                            	</tr>
	                            	<tr>
	                            		<th class="active col-md-2"><?=lang('aff.ai12');?></th>
	                            		<td class="col-md-4">
	                            			<input type="text" name="state" id="state" class="form-control input-sm" value="<?=(set_value('state') != null) ? set_value('state') : $affiliates['state'];?>">
											<span class="text-danger"><?php echo form_error('state'); ?></span>
	                            		</td>

										<th class="active col-md-2"><?php echo lang('Disable Promotion on Registering players'); ?></th>
	                            		<td class="col-md-4">
											<?php if( empty($disable_promotion_on_register) ): ?>
												<label>
													<input type="radio" name="disable_promotion_on_registering" value="1" <?=empty($affiliates['disable_promotion_on_registering'])? '': 'checked="checked"'?> >
													<?= lang('Yes')?>
												</label>
												&nbsp;&nbsp;
												<label>
													<input type="radio" name="disable_promotion_on_registering" value="0" <?=empty($affiliates['disable_promotion_on_registering'])? 'checked="checked"': ''?>>
													<?= lang('No')?>
												</label>
											<?php else: ?>
												<?= lang('Yes')?>
											<?php endif; ?>
										</td>
	                            	</tr>
	                            	<tr>
	                            		<th class="active col-md-2"><?=lang('Prefix of player');?></th>
	                            		<td class="col-md-4">
	                            			<input type="text" name="prefix_of_player" id="prefix_of_player" class="form-control input-sm" value="<?=(set_value('prefix_of_player') != null) ? set_value('prefix_of_player') : $affiliates['prefix_of_player'];?>">
											<span class="text-danger"><?php echo form_error('checkPrefixOfPlayer'); ?></span>
	                            		</td>
	                            		<th class="active col-md-2"><?php echo lang('Disable Cashback on Registering players'); ?></th>
	                            		<td class="col-md-4">
											<?php if( empty($disable_cashback_on_register) ): ?>
												<label>
													<input type="radio" name="disable_cashback_on_registering" value="1" <?=empty($affiliates['disable_cashback_on_registering'])? '': 'checked="checked"'?>>
													<?= lang('Yes')?>
												</label>
												&nbsp;&nbsp;
												<label>
													<input type="radio" name="disable_cashback_on_registering" value="0" <?=empty($affiliates['disable_cashback_on_registering'])? 'checked="checked"': ''?>>
													<?= lang('No')?>
												</label>
											<?php else: ?>
												<?= lang('Yes')?>
											<?php endif; ?>
	                            		</td>
	                            	</tr>
                                    <tr>
                                        <th class="active col-md-2"><?=lang('Affiliate Link Redirection');?></th>
                                        <td class="col-md-4">
                                            <select name="redirect" id="redirect" class="form-control input-sm">
                                                <?php foreach (Affiliatemodel::REDIRECT_DESCRIPTION as $key => $value) :?>
                                                    <option value="<?=$key?>" <?=($affiliates['redirect'] == $key) ? 'selected' : ''?>><?=lang($value)?></option>
                                                <?php endforeach;?>
                                            </select>
                                        </td>
                                        <th class="active col-md-2"><?=lang('Auto Add Selected Tags to Registering Players')?></th>
                                        <td class="col-md-4">
											<div class="input-group">
												<div class="form-control playerTag" id="player_tags">
													<?php if(empty($player_tags)): ?>
														<?=lang('Select Tag')?>
													<?php else: ?>
														<div style="margin-top: 4px;"><?=aff_newly_player_tagged_list($affiliates['affiliateId'])?></div>
													<?php endif; ?>
												</div>
												<?php $ableEditTags = $this->permissions->checkPermissions('edit_player_tag');?>
												<?php if ($ableEditTags || true) : ?>
													<!--        after click "Edit"          -->
														<select name="tag_list[]" id="tag-list" multiple="multiple" class="form-control input-md" style="display: none;">
															<?php foreach($all_tag_list as $tag):?>
																<?php if(is_array($player_tags) && in_array($tag['tagId'], $player_tags)): ?>
																	<option value="<?=$tag['tagId']?>" data-color="<?=$tag['tagColor']?>" selected="selected">
																		<?=$tag['tagName']?>
																	</option>
																<?php else: ?>
																	<option value="<?=$tag['tagId']?>" data-color="<?=$tag['tagColor']?>">
																		<?=$tag['tagName']?>
																	</option>
																<?php endif;?>
															<?php endforeach;?>
														</select>
													<!--        after click "Edit" end         -->
													<a class="input-group-addon playerTag toEditingTag">
														<i class="fa fa-edit"></i>
													</a>
													<!--        after click "Edit"          -->
														<a class="input-group-addon confirmedEditTag" style="display: none;">
															<i class="fa fa-times"></i>
														</a>
														<div class="input-group-btn tagSaveBtn" style="display: none;">
															<button type="button" class="btn btn-sm btn-scooter" id="tag-save_btn" >
																<?=lang("lang.save")?>
															</button>
														</div>
													<!--        after click "Edit" end         -->
												<?php endif;?>
											</div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th class="active col-md-2"><?=lang('Default Player VIP Level');?></th>
                                        <td>
                                            <select name="vip_level" id="vip_level" class="form-control">
                                                <?php foreach ($vip_levels as $vip_level): ?>
                                                    <optgroup label="<?=lang($vip_level['name'])?>">
                                                        <?php foreach ($vip_level['levels'] as $level): ?>
                                                            <option value="<?=$level['id']?>" <?=$affiliates['vip_level_id'] == $level['id'] ? 'selected="selected"':''?>><?=$level['name']?></option>
                                                        <?php endforeach ?>
                                                    </optgroup>
                                                <?php endforeach ?>
                                            </select>
                                        </td>
										<?php if( empty($hide_dispatch_account_level_on_registering_in_aff) ): ?>
										<th class="active col-md-2"><?=lang('Default Player Dispatch Account Level');?></th>
                                        <td class="col-md-4">
											<?php if( ! empty($edit_dispatch_account_level_id_on_registering) ): ?>
												<?=form_dropdown('dispatch_account_level_id_on_registering', is_array($all_dispatch_levels) ? $all_dispatch_levels : [], $dispatch_account_level_id, '  class="form-control input-sm chosen-select dispatch_account_levels" data-disable_search="1" id="dispatch_account_levels" ')?>
											<?php else: ?>
												<? if( ! empty($dispatchAccountLevelDetails) ): ?>
													<?=lang($dispatchAccountLevelDetails['group_name'])?> - <?=lang($dispatchAccountLevelDetails['level_name'])?>
													<input type="hidden" name="dispatch_account_level_id_on_registering" value="<?=$dispatch_account_level_id?>">
												<? else: ?>
													<?=lang($dispatchAccountLevelDetails['N/A'])?>
												<? endif;  // EOF if( ! empty($dispatchAccountLevelDetails) ):... ?>
											<?php endif; // EOF if($edit_dispatch_account_level_id_on_registering)...?>
                                        </td>
										<?php endif; // EOF if( ! empty($hide_dispatch_account_level_on_registering_in_aff) )... ?>
                                    </tr>
	                            </table>
	                        </div>
	                    </div>
	             	</div>
					<div class="row">
						<center>
							<input type="submit" class="btn btn-sm <?=$this->utils->getConfig('use_new_sbe_color') ? 'btn-scooter' : 'btn-info'?>" value="<?=lang('lang.save');?>"/>
							<a href="<?=site_url('affiliate_management/userInformation/' . $affiliates['affiliateId'])?>" class="btn btn-sm <?=$this->utils->getConfig('use_new_sbe_color') ? 'btn-linkwater' : 'btn-danger'?>" id="view_affiliate"><?=lang('lang.cancel');?></a>
						</center>
					</div>
				</form>
				<!-- End of Personal Info -->
			</div>
		</div>
	</div>
</div>

<script type="text/javascript">
	$(document).ready(function(){
	    $('.datepicker').datepicker({
	    	format: 'yyyy-mm-dd',
	        language: '<?=$this->language_function->convertToDatePickerLang($this->language_function->getCurrentLanguage())?>',
	        startView : 'year',
	        startDate : '-120y',
	        endDate : '+0y'
	    });
	});

</script>
<style>
	.overflow-visible {
		overflow: visible;
	}

	/**
	 * For make-up and cloned form player_information.css
	 * .player-infomations .info-tab .multiselect-container.dropdown-menu .multiselect-clear-filter
	 */
	.multiselect-clear-filter {
		padding: 11px;
		border-radius: 0 !important;
		margin-left: 3px !important;
	}
	a.playerTag {
		cursor: pointer;
	}
	#player_tags {
		overflow: hidden;
	}

</style>