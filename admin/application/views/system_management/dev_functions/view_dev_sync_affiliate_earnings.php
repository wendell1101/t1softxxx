<?php if(!$this->utils->isEnabledFeature('close_aff_and_agent')): ?>
	<form action="<?=site_url('system_management/post_calculate_aff_earnings'); ?>" method="POST">
		<div class="panel panel-primary panel_main">
			<div class="panel-heading">
				<h4 class="panel-title"><?=lang('Generate Affiliate Earnings'); ?>
				</h4>
			</div>
			<div id="aff_earnings" class="panel-collapse collapse in ">
				<div class="panel-body">
					<div class="row">
						<div class="col-md-2">
							<?=lang('Affiliate Username');?>
						</div>
						<div class="col-md-4">
			                <input class="form-control input-sm" name="username"/>
						</div>
					</div>
					<br>
					<div class="row">
						<div class="col-md-2">
							<?=lang('Date');?>
						</div>
						<div class="col-md-4">
			                <input id="aff_earnings_date_picker" class="form-control input-sm dateInput" data-start="#startDate" data-end="#endDate" data-time="false"/>
			                <input type="hidden" id="startDate" name="startDate" value="<?=date('Y-m-d', strtotime('-1 day'));?>" />
			                <input type="hidden" id="endDate" name="endDate" value="<?=date('Y-m-d', strtotime('-1 day'))?>" />
						</div>
					</div>
					<br>
					<div class="row">
						<div class="col-md-2">
							<?=lang('Dry Run');?>
						</div>
						<div class="col-md-4">
							<input type="checkbox" name="dry_run" value="true">
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