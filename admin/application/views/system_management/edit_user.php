<div id="container">
	<div class="row">
		<div class="col-md-6">
			<div class="panel panel-primary">
				<div class="panel-heading">
					<h4 class="panel-title"><i class="icon-user"></i> <?= lang('viewuser.07'); ?>: </h4>
				</div>
				<div class="panel panel-body" id="email_panel_body">
					<form class="form-horizontal" method="post" action="<?= BASEURL . 'user_management/postEditUser/' . $user['userId'] ?>" id="my_form" autocomplete="off">
						<div class="form-group">
							<label for="username" class="control-label col-md-3"><?= lang('system.word38'); ?>:</label>
							<div class="col-md-7">
								<input type="text" name="username" disabled="disabled" id="username" value="<?= $user['username'] ?>" class="form-control">
								<?php echo form_error('username', '<span class="help-block" style="color:#ff6666;">', '</span>'); ?>
							</div>
						</div>
						<div class="form-group">
							<label for="realname" class="control-label col-md-3"><?= lang('system.word39'); ?>:</label>
							<div class="col-md-7">
								<input type="text" name="realname" id="realname" value="<?= $user['realname'] ?>" class="form-control">
								<?php echo form_error('realname', '<span class="help-block" style="color:#ff6666;">', '</span>'); ?>
							</div>
						</div>
						<div class="form-group">
							<label for="department" class="control-label col-md-3"><?= lang('system.word40'); ?></label>
							<div class="col-md-7">
								<input type="text" name="department" id="department" value="<?= $user['department'] ?>" class="form-control">
								<?php echo form_error('department', '<span class="help-block" style="color:#ff6666;">', '</span>'); ?>
							</div>
						</div>
						<div class="form-group">
							<label for="position" class="control-label col-md-3"><?= lang('system.word41'); ?></label>
							<div class="col-md-7">
								<input type="text" name="position" id="position" value="<?= $user['position'] ?>" class="form-control">
								<?php echo form_error('position', '<span class="help-block" style="color:#ff6666;">', '</span>'); ?>
							</div>
						</div>
						<div class="form-group">
							<label for="email" class="control-label col-md-3"><?= lang('system.word42'); ?></label>
							<div class="col-md-7">
								<input type="email" name="email" id="email" value="<?= $user['email'] ?>" class="form-control">
								<?php echo form_error('email', '<span class="help-block" style="color:#ff6666;">', '</span>'); ?>
							</div>
						</div>

						<?php if($this->rolesfunctions->checkRole($user['roleId']) == true) { ?>
							<div class="form-group">
								<label for="wid_amt" class="control-label col-md-5"><?= lang('system.word51'); ?></label>
								<div class="col-md-5">
									<input type="hidden" name="role_id" value="<?= $user['roleId'] ?>"/>
									<input type="text" name="wid_amt" value="<?= $user['maxWidAmt'] ?>" class="form-control input-sm number_only">
									<?php echo form_error('wid_amt', '<span class="help-block" style="color:#ff6666;">', '</span>'); ?>
								</div>
							</div>
						<?php } ?>

						<?php if($this->rolesfunctions->checkRole($user['roleId']) == true) { ?>
							<div class="form-group">
								<label for="wid_amt" class="control-label col-md-5"><?= lang('Single Withdrawal'); ?></label>
								<div class="col-md-5">
									<input type="hidden" name="role_id" value="<?= $user['roleId'] ?>"/>
									<input type="text" name="single_amt" value="<?= $user['singleWidAmt'] ?>" class="form-control input-sm number_only">
									<?php echo form_error('single_amt', '<span class="help-block" style="color:#ff6666;">', '</span>'); ?>
								</div>
							</div>
						<?php } ?>

						<?php if ($this->utils->getConfig('enabled_adminusers_withdrawal_cs_stage_setting')) {?>
							<?php for($i = 0; $i < CUSTOM_WITHDRAWAL_PROCESSING_STAGES; $i++) : ?>
								<?php if(!$setting[$i]['enabled']) continue; ?>
								<?php if($this->rolesfunctions->checkRole($user['roleId']) == true) { ?>
									<div class="form-group">
										<label for="cs<?=$i?>_wid_amt" class="control-label col-md-5"><?=lang($setting[$i]['name']) .' '.  lang('system.word51'); ?></label>
										<div class="col-md-5">
											<input type="hidden" name="role_id" value="<?= $user['roleId'] ?>"/>
											<input type="text" name="cs<?=$i?>_wid_amt" value="<?= $user['cs'.$i.'maxWidAmt'] ?>" class="form-control input-sm number_only">
											<?php echo form_error('cs<?=$i?>_wid_amt', '<span class="help-block" style="color:#ff6666;">', '</span>'); ?>
										</div>
									</div>
								<?php } ?>

								<?php if($this->rolesfunctions->checkRole($user['roleId']) == true) { ?>
									<div class="form-group">
										<label for="cs<?=$i?>_single_amt" class="control-label col-md-5"><?=lang($setting[$i]['name']) .' '.  lang('Single Withdrawal'); ?></label>
										<div class="col-md-5">
											<input type="hidden" name="role_id" value="<?= $user['roleId'] ?>"/>
											<input type="text" name="cs<?=$i?>_single_amt" value="<?= $user['cs'.$i.'singleWidAmt'] ?>" class="form-control input-sm number_only">
											<?php echo form_error('cs<?=$i?>_single_amt', '<span class="help-block" style="color:#ff6666;">', '</span>'); ?>
										</div>
									</div>
								<?php } ?>
							<?php endfor; ?>
						<?php } ?>

						<div class="form-group ">
			                <label for="role" class="control-label col-md-3"><?=lang('report.log04');?></label>
			                <div class="col-md-7">
				                <select name="userRole" class="form-control input-sm" required>
				                    <option value=""><?=lang('lang.selectall')?></option>
				                    <?php foreach ($roles as $role): ?>

				                        <option value="<?=$role['roleId']?>" <?php echo $user['roleId']==$role['roleId']?"selected":""; ?>><?=$role['roleName']?></option>
				                    <?php endforeach?>
				                </select>
			           		 </div>
			            </div>
						<?php $telephone_api_list=$this->utils->getConfig('telephone_api')?>
			            <?php if (!empty($telephone_api_list)) :?>
								<?php if(!$this->utils->getConfig('use_adminuser_telesale')):?>
				            <div class="form-group">
								<label for="tele_id" class="control-label col-md-5"><?= lang('Telemarketing ID A'); ?></label>
								<div class="col-md-5">
									<input type="text" name="tele_id" value="<?= $user['tele_id'] ?>" class="form-control input-sm" />
									<?php echo form_error('tele_id', '<span class="help-block" style="color:#ff6666;">', '</span>'); ?>
								</div>
							</div>
							<div class="form-group">
								<label for="tele_id_2" class="control-label col-md-5"><?= lang('Telemarketing ID B'); ?></label>
								<div class="col-md-5">
									<input type="text" name="tele_id_2" value="<?= $user['tele_id_2'] ?>" class="form-control input-sm" />
									<?php echo form_error('tele_id_2', '<span class="help-block" style="color:#ff6666;">', '</span>'); ?>
								</div>
							</div>
							<div class="form-group">
								<label for="tele_id_3" class="control-label col-md-5"><?= lang('Telemarketing ID C'); ?></label>
								<div class="col-md-5">
									<input type="text" name="tele_id_3" value="<?= $user['tele_id_3'] ?>" class="form-control input-sm" />
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
											<label for=<?=$displaysystemCode?> class="control-label col-md-3"><?=$systemName;?></label>
											<div class="col-md-7">
												<input type="text" name="teleArray[<?=$displaysystemCode?>]" id=<?=$displaysystemCode?> value="<?=$tele_id?>" class="form-control" >
												<?php echo form_error($displaysystemCode, '<span class="help-block" style="color:#ff6666;">', '</span>'); ?>
											</div>
									</div>
								<?php
								endforeach;?>							
							<?php endif;?>
						<?php else :?>
							<div class="form-group">
								<label for="tele_id" class="control-label col-md-5"><?= lang('Telemarketing ID'); ?></label>
								<div class="col-md-5">
									<input type="text" name="tele_id" value="<?= $user['tele_id'] ?>" class="form-control input-sm" />
									<?php echo form_error('tele_id', '<span class="help-block" style="color:#ff6666;">', '</span>'); ?>
								</div>
							</div>
						<?php endif ?>
						<div class="col-md-offset-3" style="padding-left:7px;">
							<br/>
							<input type="button" value=" <?= lang('lang.cancel'); ?>" class="btn btn-sm <?=$this->utils->getConfig('use_new_sbe_color') ? 'btn-linkwater' : 'btn-default'?> " onclick="history.back();" />
							<input type="submit" value=" <?= lang('lang.update'); ?>" class="btn btn-sm <?=$this->utils->getConfig('use_new_sbe_color') ? 'btn-scooter' : 'btn-info'?> ">
						</div>
					</form>
				</div>
			</div>
		</div>
	</div>
</div>