<?php if ($this->utils->getConfig('enable_refresh_all_player_balance_in_specific_game_provider')): ?>
<form action="<?=site_url('system_management/post_refresh_all_player_balance_in_specific_game_provider'); ?>" method="POST">
	<div class="panel panel-primary panel_main">
		<div class="panel-heading">
			<h4 class="panel-title"><?=lang('Refresh all player balance'); ?>
			</h4>
		</div>

		<div id="cashback_panel" class="panel-collapse collapse in ">
			<div class="panel-body">
				<div class="row">
					<div class="col-md-2">
						<?=lang('Game');?>
					</div>
					<div class="col-md-4">
						<select class="form-control input-sm" name="game_platform_id" id="game_platform_id">
							<option value=""><?=lang('Please select game');?></option>
							<?php foreach ($this->utils->getNonSeamlessGameSystemMap() as $key => $value) { ?>
								<option value="<?=$key;?>"><?=$value;?></option>
							<?php } ?>
						</select>
					</div>
				</div>
				<br>
				<div class="row">
					<div class="col-md-2">
						<!--  -->
					</div>
					<div class="col-md-2">
						<input class="" type="radio" id="isRegistered" name="is_only_registered" value="1" checked />
						<label for="isRegistered"><?=lang('Registered players');?></label>
					</div>
					<div class="col-md-2">
						<input class="" type="radio" id="isNotRegistered" name="is_only_registered" value="0"/>
						<label for="isNotRegistered"><?=lang('Unregistered players');?></label>
					</div>
				</div>
			</div>
			<div class="panel-footer">
				<input type="submit" class="btn btn-portage" value="<?=lang('Submit'); ?>">
			</div>
		</div>
	</div>
</form>

<?php endif; ?>
