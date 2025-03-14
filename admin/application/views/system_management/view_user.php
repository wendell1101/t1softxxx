<div id="container">
	<div class="row">
		<div class="col-md-6">
			<div class="panel panel-primary">
				<div class="panel-heading">
					<h4 class="panel-title"><i class="icon-user"></i> <?=lang('viewuser.01');?> </h4>
				</div>
				<div class="panel panel-body" id="email_panel_body">
					<form class="form-horizontal" method="post" action="<?=site_url('user_management/postEditUser/' . $user['userId'])?>" id="my_form" autocomplete="off" class="form-inline">
						<div class="form-group">
							<label for="username" class="control-label col-md-3"><?=lang('system.word38');?>:</label>
							<div class="col-md-7">
								<input type="text" name="username" id="username" value="<?=$user['username']?>" class="form-control" disabled="disabled">
								<?php echo form_error('username', '<span class="help-block" style="color:#ff6666;">', '</span>'); ?>
							</div>
						</div>
						<div class="form-group">
							<label for="realname" class="control-label col-md-3"><?=lang('system.word39');?>:</label>
							<div class="col-md-7">
								<input type="text" name="realname" id="realname" value="<?=$user['realname']?>" class="form-control" disabled="disabled">
								<?php echo form_error('realname', '<span class="help-block" style="color:#ff6666;">', '</span>'); ?>
							</div>
						</div>
						<div class="form-group">
							<label for="department" class="control-label col-md-3"><?=lang('system.word40');?></label>
							<div class="col-md-7">
								<input type="text" name="department" id="department" value="<?=$user['department']?>" class="form-control" disabled="disabled">
								<?php echo form_error('department', '<span class="help-block" style="color:#ff6666;">', '</span>'); ?>
							</div>
						</div>
						<div class="form-group">
							<label for="position" class="control-label col-md-3"><?=lang('system.word41');?></label>
							<div class="col-md-7">
								<input type="text" name="position" id="position" value="<?=$user['position']?>" class="form-control" disabled="disabled">
								<?php echo form_error('position', '<span class="help-block" style="color:#ff6666;">', '</span>'); ?>
							</div>
						</div>
						<div class="form-group">
							<label for="email" class="control-label col-md-3"><?=lang('system.word42');?></label>
							<div class="col-md-7">
								<input type="email" name="email" id="email" value="<?=$user['email']?>" class="form-control" disabled="disabled">
								<?php echo form_error('email', '<span class="help-block" style="color:#ff6666;">', '</span>'); ?>
							</div>
						</div>

						<?php if ($this->rolesfunctions->checkRole($user['roleId']) == true) {?>
							<div class="form-group">
								<label for="wid_amt" class="control-label col-md-3"><?=lang('system.word51');?>:</label>
								<div class="col-md-7">
									<input type="wid_amt" name="wid_amt" id="wid_amt" value="<?=$user['maxWidAmt']?>" class="form-control" disabled="disabled">
									<?php echo form_error('wid_amt', '<span class="help-block" style="color:#ff6666;">', '</span>'); ?>
								</div>
							</div>
						<?php } ?>

						<?php if ($this->rolesfunctions->checkRole($user['roleId']) == true) {?>
							<div class="form-group">
								<label for="wid_amt" class="control-label col-md-3"><?=lang('Single Withdrawal');?>:</label>
								<div class="col-md-7">
									<input type="wid_amt" name="single_amt" id="single_amt" value="<?=$user['singleWidAmt']?>" class="form-control" disabled="disabled">
									<?php echo form_error('single_amt', '<span class="help-block" style="color:#ff6666;">', '</span>'); ?>
								</div>
							</div>
						<?php } ?>

						<!-- enabled_adminusers_withdrawal_cs_stage_setting -->

						<?php if ($this->utils->getConfig('enabled_adminusers_withdrawal_cs_stage_setting')) {?>
							<?php for($i = 0; $i < CUSTOM_WITHDRAWAL_PROCESSING_STAGES; $i++) : ?>
								<?php if(!$setting[$i]['enabled']) continue; ?>
								<?php if ($this->rolesfunctions->checkRole($user['roleId']) == true) {?>
									<div class="form-group">
										<label for="cs<?=$i?>_wid_amt" class="control-label col-md-3"><?=lang($setting[$i]['name']) .' '. lang('system.word51');?>:</label>
										<div class="col-md-7">
											<input type="text" name="cs<?=$i?>_wid_amt" id="cs<?=$i?>_wid_amt" value="<?=$user['cs'.$i.'maxWidAmt']?>" class="form-control" disabled="disabled">
											<?php echo form_error('cs$i_wid_amt', '<span class="help-block" style="color:#ff6666;">', '</span>'); ?>
										</div>
									</div>
								<?php } ?>

								<?php if ($this->rolesfunctions->checkRole($user['roleId']) == true) {?>
									<div class="form-group">
										<label for="cs<?=$i?>_single_amt" class="control-label col-md-3"><?=lang($setting[$i]['name']) .' '. lang('Single Withdrawal');?>:</label>
										<div class="col-md-7">
											<input type="text" name="cs<?=$i?>_single_amt" id="cs<?=$i?>_single_amt" value="<?=$user['cs'.$i.'singleWidAmt']?>" class="form-control" disabled="disabled">
											<?php echo form_error('cs$i_single_amt', '<span class="help-block" style="color:#ff6666;">', '</span>'); ?>
										</div>
									</div>
								<?php } ?>
							<?php endfor; ?>
						<?php } ?>

						<div class="form-group">
							<label for="role_id" class="control-label col-md-3"><?=lang('sys.vu04');?></label>
							<div class="col-md-7">
								<input type="role" name="role_id" id="role" value="<?=$user['roleName']?>" class="form-control" disabled="disabled">
								<?php echo form_error('role', '<span class="help-block" style="color:#ff6666;">', '</span>'); ?>
							</div>
						</div>
						<?php $telephone_api_list=$this->utils->getConfig('telephone_api')?>
						<?php if (!empty($telephone_api_list)) :?>
							<?php if(!$this->utils->getConfig('use_adminuser_telesale')):?>
								<div class="form-group">
									<label for="tele_id" class="control-label col-md-3"><?=lang('Telemarketing ID A');?></label>
									<div class="col-md-7">
										<input type="text" name="tele_id" id="tele_id" value="<?=$user['tele_id']?>" class="form-control" disabled="disabled">
										<?php echo form_error('tele_id', '<span class="help-block" style="color:#ff6666;">', '</span>'); ?>
									</div>
								</div>
								<div class="form-group">
									<label for="tele_id_2" class="control-label col-md-3"><?=lang('Telemarketing ID B');?></label>
									<div class="col-md-7">
										<input type="text" name="tele_id_2" id="tele_id_2" value="<?=$user['tele_id_2']?>" class="form-control" disabled="disabled">
										<?php echo form_error('tele_id_2', '<span class="help-block" style="color:#ff6666;">', '</span>'); ?>
									</div>
								</div>
								<div class="form-group">
									<label for="tele_id_3" class="control-label col-md-3"><?=lang('Telemarketing ID C');?></label>
									<div class="col-md-7">
										<input type="text" name="tele_id_3" id="tele_id_3" value="<?=$user['tele_id_3']?>" class="form-control" disabled="disabled">
										<?php echo form_error('tele_id_3', '<span class="help-block" style="color:#ff6666;">', '</span>'); ?>
									</div>
								</div>
							<?php else:?>
							<?php 
								foreach($check_tele_list as $systemCode):
									$displaysystemCode = !empty($tele_lists[$systemCode['id']]['systemCode'])?$tele_lists[$systemCode['id']]['systemCode']:$systemCode['id'];
									$systemName=!empty($tele_lists[$systemCode['id']]['system_name'])? $tele_lists[$systemCode['id']]['system_name']:$systemCode['system_name'];
									?>
									<?php $tele_id=!empty($tele_lists[$systemCode['id']]['tele_id'])?$tele_lists[$systemCode['id']]['tele_id']:""?>
										<div class="form-group">
											<label for=<?=$systemCode['id']?> class="control-label col-md-3"><?=$systemName;?></label>
											<div class="col-md-7">
												<input type="text" name="<?=$displaysystemCode?>" id="<?=$displaysystemCode?>" value="<?=$tele_id?>" class="form-control" disabled="disabled">
												<?php echo form_error($displaysystemCode, '<span class="help-block" style="color:#ff6666;">', '</span>'); ?>
											</div>
									</div>
								<?php endforeach;?>							
							<?php endif;?>
						<?php else :?>
							<div class="form-group">
								<label for="tele_id" class="control-label col-md-3"><?=lang('Telemarketing ID');?></label>
								<div class="col-md-7">
									<input type="text" name="tele_id" id="tele_id" value="<?=$user['tele_id']?>" class="form-control" disabled="disabled">
									<?php echo form_error('tele_id', '<span class="help-block" style="color:#ff6666;">', '</span>'); ?>
								</div>
							</div>
						<?php endif ?>
						<div class="col-md-12" style="padding-left:7px;">
							<br/>
                            <?php if ($this->permissions->checkPermissions('admin_manage_user_roles') && $this->users->isT1Admin($this->authentication->getUsername())) { ?>
							<a href="<?=site_url('/user_management/login_as_user/' . $user['userId'])?>" class="btn btn-sm btn-scooter"> <?=lang('Login as User');?></a>
                            <?php } ?>
                            <?php if ($this->utils->isEnabledMDB()) { ?>
                                <a href="<?=site_url('/user_management/sync_user_to_mdb/'.$user['userId'])?>" class="btn btn-sm btn-portage">
                                    <i class="fa fa-refresh"></i> <?=lang('Sync To Currency')?>
                                </a>
                            <?php } ?>

							<?php if ($this->utils->isEnabledFeature('enabled_backendapi') && $this->permissions->checkPermissions('view_backendapi_keys')) {?>
							<a href="<?=site_url('/system_management/view_user_backendapi/'.$user['userId'])?>" class="btn btn-scooter btn-sm"><?=lang('Backend API');?></a>
                            <?php } ?>

							<a href="<?=site_url('user_management/viewEditUser/' . $user['userId'])?>" class="btn btn-sm btn-scooter"> <?=lang('lang.edit');?></a>
							<a href="<?php echo site_url("/user_management/viewUsers") ?>" class="btn btn-sm btn-linkwater" ><?=lang('lang.cancel');?></a>
						</div>
					</form>
				</div>
			</div>
		</div>

		<div class="col-md-6">
			<div class="panel panel-primary">
				<div class="panel-heading">
					<h4 class="panel-title pull-left"><i class="icon-profile"></i>  <?=lang('viewuser.02');?>: </h4>
					<div class="clearfix"></div>
				</div>
				<div class="panel-body">
					<table class="table table-hover table-bordered">
						<tr>
							<td class="active"><b> <?=lang('system.word31');?>: </b></td>
							<td><?=$user['roleName']?></td>
						</tr>

						<tr>
							<td class="active"><b> <?=lang('viewuser.03');?>: </b></td>
							<td><?=!empty($this->users->searchAdminSession($user['userId'])) ? lang('icon.online') : lang('icon.offline')?>
							<input type="button" onclick="kickoutAdminuser()" class="btn btn-sm btn-chestnutrose" value="<?php echo lang('player.ol03'); ?>" >
							</td>
						</tr>

						<tr>
							<td class="active"><b> <?=lang('sys.vu44');?>: </b></td>
							<td><?=$user['lastLoginIp'] ?: '<i class="text-muted">' . lang('lang.norec') . '</i>'?></td>
						</tr>

						<tr>
							<td class="active"><b> <?=lang('sys.vu24');?>: </b></td>
							<td><?=$user['lastLoginTime']?></td>
						</tr>

						<tr>
							<td class="active"><b> <?=lang('sys.vu25');?>: </b></td>
							<td><?=$user['lastLogoutTime']?></td>
						</tr>

						<tr>
							<td class="active"><b> <?=lang('sys.vu66');?>: </b></td>
							<td><?=$user['createTime']?></td>
						</tr>

						<tr>
							<td class="active"><b> <?=lang('sys.vu67');?>: </b></td>
							<td><?=$user['creator']?></td>
						</tr>

						<tr>
							<td class="active"><b> <?=lang('lang.status');?>: </b></td>
							<td>
								<?php switch ($user['status']) {
case 2:
	echo lang('viewuser.04');
	break;

case 1:
	echo lang('viewuser.05');
	break;

default:
	echo lang('viewuser.06');
	break;
}
?>
							</td>
						</tr>
					</table>
				</div>
			</div>
		</div>
	</div>
</div>

<script type="text/javascript">
function kickoutAdminuser(){
	if(confirm("<?php echo lang('sys.sure'); ?>")){
		window.location.href="<?php echo site_url('/user_management/kickout/' . $user['userId']) ?>";
	}
}

</script>
