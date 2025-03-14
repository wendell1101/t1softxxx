<form action="<?=site_url('system_management/post_transfer_all_players_subwallet_to_main_wallet'); ?>" method="POST">
	<div class="panel panel-primary panel_main">
		<div class="panel-heading">
			<h4 class="panel-title"><?=lang('Sync Summary Game Total Bet Daily'); ?>
			</h4>
		</div>

		<div id="cashback_panel" class="panel-collapse collapse in ">
			<div class="panel-body">
				<!-- <div class="row">
					<div class="col-md-2">
						<?=lang('Player');?>
					</div>
					<div class="col-md-4">
						<input class="form-control input-sm" type="text" name="player_name" value="" required>
					</div>
				</div> -->
				<br>
				<div class="row">
					<div class="col-md-2">
						<?=lang('Game');?>
					</div>
					<div class="col-md-4">
						<select class="form-control input-sm" name="game_id" id="game_id">
							<option value=""><?=lang('All');?></option>
							<?php foreach ($this->utils->getGameSystemMap() as $key => $value) { ?>
								<option value="<?=$key;?>"><?=$value;?></option>
							<?php } ?>
						</select>
					</div>
				</div>
				<br>
				<div class="row">
					<div class="col-md-2">
						<?=lang('Min Balance');?>
					</div>
					<div class="col-md-4">
						<input class="form-control input-sm" type="number" name="min_balance" value="0">
					</div>
				</div>
				<br>
				<div class="row">
					<div class="col-md-2">
						<?=lang('Max Balance');?>
					</div>
					<div class="col-md-4">
						<input class="form-control input-sm" type="number" name="max_balance" value="1">
					</div>
				</div>
			</div>
			<div class="panel-footer">
				<input type="submit" class="btn btn-portage" value="<?=lang('Submit'); ?>">
			</div>
		</div>
	</div>
</form>