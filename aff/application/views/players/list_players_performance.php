<div class="container">
	<br/>

	<div class="row">
		<div class="col-md-12" id="toggleView">
		    <div class="panel panel-primary">
		        <div class="panel-heading">
		            <h4 class="panel-title">
		                <i class="fa fa-search"></i> <?=lang("lang.search")?>
		                <span class="pull-right">
		                    <a data-toggle="collapse" href="#collapsePlayerReport" class="btn btn-default btn-xs"></a>
		                </span>
		            </h4>
		        </div>
		        <div id="collapsePlayerReport" class="panel-collapse">
		            <div class="panel-body">
		                <form id="form-filter" class="form-inline" method="get">
							<div class="row">
								<div class="col-md-6">
									<label class="control-label"><?=lang('traffic.regdate')?></label><br>
										<div class="input-group">
											<input class="form-control dateInput" id="date-range" data-start="#date_from" data-end="#date_to" data-time="true" style="width: 500px;" />
											<input type="hidden" id="date_from" name="date_from" value="<?= $date_from ?>"/>
											<input type="hidden" id="date_to" name="date_to" value="<?= $date_to ?>"/>
											<span class="input-group-addon input-sm">
												<input type="checkbox" name="by_date" id="by_date" value="1"
												<?= empty($by_date) ? '' : ' checked="1" ' ?>
											/>
											</span>
										</div>
									</div>
								<div class="col-md-6">
									<label class="control-label"><?=lang('Performance Date')?></label><br>
										<div class="input-group">
											<input class="form-control dateInput" id="p_date-range" data-start="#p_date_from" data-end="#p_date_to" data-time="true" style="width: 500px;" />
											<input type="hidden" id="p_date_from" name="p_date_from" value="<?= $p_date_from ?>"/>
											<input type="hidden" id="p_date_to" name="p_date_to" value="<?= $p_date_to ?>"/>
											<span class="input-group-addon input-sm">
												<input type="checkbox" name="p_by_date" id="p_by_date" value="1"
												<?= empty($p_by_date) ? '' : ' checked="1" ' ?>
											/>
											</span>
										</div>
								</div>
							</div>
							<div class="col-md-12 text-right" style="padding-top:10px;margin-right:10px">
									<input type="submit" value="<?=lang('lang.search')?>" id="search_main" class="btn btn-primary">
								</div>
		                </form>
		            </div>
		        </div>
		    </div>

			<div class="panel panel-primary">
				<div class="nav-head panel-heading">
					<h4 class="panel-title pull-left">
						<?=lang('traffic.playerlist');?>
					</h4>
					<div class="clearfix"></div>
				</div>

				<div class="panel panel-body table-responsive" id="affiliate_panel_body">

						<div class="col-md-12" id="view_payments" style="margin: 30px 0 0 0;">
							<table class="table table-striped table-hover" id="paymentTable" style="width: 100%">
								<thead>
									<tr>
										<th></th>
										<th class="input-sm"><?=lang('performance.playerusername');?></th>
										<?php if ($this->utils->isEnabledFeature('aff_show_real_name_on_reports')): ?>
											<th class="input-sm"><?=lang('Real Name');?></th>
										<?php endif ?>
										<th class="input-sm"><?=lang('traffic.regdate');?></th>
										<th class="input-sm"><?=lang('performance.lastLogInDate');?></th>
										<th class="input-sm"><?=lang('performance.firstDepositDate');?></th>
										<th class="input-sm"><?=lang('First Deposit Amount');?></th>
										<th class="input-sm"><?=lang('Total Deposit Times');?></th>
										<th class="input-sm"><?=lang('performance.totalDepositAmount');?></th>
										<th class="input-sm"><?=lang('Total Withdraw');?></th>
										<th class="input-sm"><?=lang('Total Win');?></th>
										<th class="input-sm"><?=lang('performance.totalLoss');?></th>
										<th class="input-sm"><?=lang('Total Cashback');?></th>
										<th class="input-sm"><?=lang('Total Bonus');?></th>
										<th class="input-sm"><?=lang('aff.al11');?></th>
										<th class="input-sm"><?=lang('aff.al16');?></th>
										<?php if ($this->utils->getConfig('enable_3rd_party_affiliate')) {?>
										<th class="input-sm"><?=lang('Affiliate Network Source');?></th>
										<?php }?>
										<th class="input-sm"><?=lang('performance.player.if_online');?></th>
										<th class="input-sm"><?=lang('Last Deposit Time');?></th>
										<!-- <th class="input-sm"><?=lang('yuanbao.deposit.times');?></th> -->
									</tr>
								</thead>

								<tbody>
									<?php if (!empty($players)) {
	?>
										<?php foreach ($players as $value) {
		?>
											<tr>
												<td></td>
												<td class="input-sm">
												<?php $mask = $this->utils->keepOnlyString($value['username'], 4); ?>
												<?php if($this->utils->isHidePlayerInfoOnAff()){ ?>
													<?=  $this->utils->isEnabledFeature('masked_player_username_on_affiliate')  ? $mask : $value['username']?>
												<?php }else{?>
													<a href="<?php echo site_url('affiliate/viewPlayerById/' . $value['playerId']); ?>">
														<?=  $this->utils->isEnabledFeature('masked_player_username_on_affiliate')  ? $mask : $value['username']?>
													</a>
												<?php }?>
												</td>
												<?php if ($this->utils->isEnabledFeature('aff_show_real_name_on_reports')): ?>
													<td class="input-sm"><?=$value['realName'] ? : lang('lang.norecyet')?></td>
												<?php endif ?>
												<td class="input-sm"><?php echo $value['createdOn']; ?></td>
												<td class="input-sm"><?php echo $value['last_login']; ?></td>
												<td class="input-sm"><?php echo $value['first_deposit_date'] ? : lang('lang.norecyet') ?></td>
												<td class="input-sm"><?php echo $value['first_deposit']? : lang('lang.norecyet') ?></td>
												<td class="input-sm"><?php echo $value['deposit_count']? : "0"; ; ?></td>
												<td class="input-sm"><?php echo $value['totalDepositAmount']? : "0";  ?></td>
												<td class="input-sm"><?php echo $value['approvedWithdrawAmount']; ?></td>
												<td class="input-sm"><?php echo $value['sum_total_win']? : "0"; ?></td>
												<td class="input-sm"><?php echo $value['sum_total_loss']? : "0"; ?></td>
												<td class="input-sm"><?php echo $value['sum_total_cashback']? : "0"; ?></td>
												<td class="input-sm"><?php echo $value['sum_total_bonus']? : "0"; ?></td>
												<!-- <td class="input-sm"><?php echo  $value['add_balance'] ? : "0";?></td> -->
												<!-- <td class="input-sm"><?php echo  $value['subtract_balance'] ? : "0";?></td> -->

												<td class="input-sm"><?php echo $this->utils->isHidePlayerContactOnAff() ? '******' : $value['email']; ?></td>
												<td class="input-sm"><?php echo $value['status']=='0' ? lang('Active') : lang('Inactive') ; ?></td>
												<?php if ($this->utils->getConfig('enable_3rd_party_affiliate')) {?>
												<td class="input-sm"><?php echo $value['aff_source']; ?></td>
												<?php }?>
												<td class="input-sm"><?php echo $value['online'] ? '<i class="text-muted">' . lang('icon.online') . '</i>' : '<i class="text-muted">' . lang('icon.offline') . '</i>' ?></td>
												<td class="input-sm"><?php echo $value['last_deposit'] ? : lang('lang.norecyet'); ?></td>
												<!-- <td class="input-sm"><?php echo $value['deposit_count']; ?></td> -->
											</tr>
										<?php
}
}
?>
								</tbody>
								<tfoot>
									<tr>
										<th></th>
										<th></th>
										<?php if ($this->utils->isEnabledFeature('aff_show_real_name_on_reports')): ?>
											<th></th>
										<?php endif ?>
										<th></th>
										<th></th>
										<?php if ($this->utils->getConfig('enable_3rd_party_affiliate')) {?>
										<th></th>
										<?php }?>
										<th></th>
										<th></th>
										<th></th>
										<th></th>
										<th><?=lang('Total Players Online')?>: <?=$online_count?></th>
									</tr>
								</tfoot>
							</table>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<script type="text/javascript">
	var Columnvisibility = "<?=lang('Column visibility')?>";
    $(document).ready(function() {
		let not_visible_cols = <?= json_encode($this->utils->getConfig('not_visible_aff_playerslist') ?: []) ?>;
        var table = $('#paymentTable').DataTable( {
            // "responsive": {
            //     details: {
            //         type: 'column'
            //     }
            // },
			
			dom: "<'panel-body' <'pull-right'f><'pull-right'B><'pull-right progress-container'>l><'dt-information-summary1 text-info pull-left' i>t<'panel-body'<'pull-right'p><'dt-information-summary2 text-info pull-left' i>>",
        	buttons: [{
            extend: 'colvis',
            postfixButtons: [ 'colvisRestore' ],
			text: Columnvisibility
        	}],
            "columnDefs": [
				{
                className: 'control',
                orderable: false,
                targets:   0
				},
				{ visible: false, targets: not_visible_cols },
			],
            "order": [ 4, 'desc' ]
        } );
    } );
</script>