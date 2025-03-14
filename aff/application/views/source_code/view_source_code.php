<div class="container">
	<br/>

	<!-- Personal Information -->
	<div class="row">
		<div class="panel panel-primary">
			<div class="nav-head panel-heading">
				<h4 class="panel-title"><i class="glyphicon glyphicon-cog"></i> <?=lang('Affiliate Source Code');?> </h4>
				<div class="clearfix"></div>
			</div>

			<div class="panel panel-body" id="info_panel_body">
				<div class="row">
						<div class="col-md-12" style="overflow: auto;">
							<a name="aff_source_code_list">
							<a href="javascript:void(0)" class="btn btn-xs btn-info" onclick="newSourceCode()"><?php echo lang('New Affiliate Source Code');?></a>
							<table class="table table-striped">
								<thead>
									<th class="col-md-3"><?php echo lang('Affiliate Source Code');?></th>
									<th class="col-md-7"><?php echo lang('Link Example');?></th>
									<th class="col-md-2"><?php echo lang('Action');?></th>
								</thead>
								<tbody>
								<?php foreach($aff_source_code_list as $source_code){?>
									<tr>
										<td><?php echo $source_code['tracking_source_code']; ?></td>
										<td><?php echo !empty($first_domain) ? $first_domain.'/aff/'.$affiliate['trackingCode'].'/'.$source_code['tracking_source_code'] : ""; ?><br>
										<?php echo !empty($first_domain) ? $first_domain.'/aff.html?code='.$affiliate['trackingCode'].'&source='.$source_code['tracking_source_code'] : ""; ?></td>
										<td><a href="javascript:void(0)" class="btn btn-xs btn-primary" onclick="editSourceCode('<?php echo $source_code['id'];?>', '<?php echo $source_code['tracking_source_code'];?>', '<?=$source_code['remarks']?>')"><?php echo lang('Edit');?></a>
										<a href="javascript:void(0)" id="frm_remove_domain" onclick="removeSourceCode('<?php echo $source_code['id'];?>',  '<?php echo $source_code['tracking_source_code']; ?>')" class="btn btn-xs btn-danger"><?php echo lang('Delete'); ?></a>
										</td>
									</tr>
								<?php }?>
								</tbody>
							</table>
						</div>
					</div>
			</div>
		</div>
	</div>
</div>

<script type="text/javascript">
	function editSourceCode(affTrackingId, sourceCode, remarks){
		BootstrapDialog.show({
			title: '<?php echo lang('Edit'); ?>',
			message: '<form method="POST" class="frm_edit_source_code_'+affTrackingId+'" action="<?php echo site_url("/affiliate/change_source_code/".$affiliateId); ?>/'+affTrackingId+'">'+
            '<?=lang("Affiliate Source Code"); ?>: <input type="text" name="sourceCode" class="form-control" value="'+sourceCode+'">'+
            '<?php if($this->utils->isEnabledFeature('enable_tracking_remarks_field')) {?><?=lang("Remarks"); ?>: <input type="text" name="remarks" class="form-control" value="'+remarks+'"></form><?php } ?>',
			spinicon: 'fa fa-spinner fa-spin',
			buttons: [{
				icon: 'fa fa-save',
				label: '<?php echo lang('Save'); ?>',
				cssClass: 'btn-primary',
				autospin: true,
				action: function(dialogRef){
					dialogRef.enableButtons(false);
					dialogRef.setClosable(false);

					var frm=dialogRef.getModalBody().find('.frm_edit_source_code_'+affTrackingId);
					frm.submit();
				}
			}, {
				label: '<?php echo lang('Cancel'); ?>',
				action: function(dialogRef){
					dialogRef.close();
				}
			}]
		});
	}

	function newSourceCode(){
		BootstrapDialog.show({
			title: '<?php echo lang('New'); ?>',
			message: '<form method="POST" class="frm_new_source_code" action="<?php echo site_url("/affiliate/new_source_code/".$affiliateId); ?>">'+
            '<?=lang("New Affiliate Source Code"); ?>: <input type="text" name="sourceCode" class="form-control" value="">'+
            '<?php if($this->utils->isEnabledFeature('enable_tracking_remarks_field')) {?><?=lang("Remarks"); ?>: <input type="text" name="remarks" class="form-control" value=""></form><?php } ?>',
			spinicon: 'fa fa-spinner fa-spin',
			buttons: [{
				icon: 'fa fa-save',
				label: '<?php echo lang('Save'); ?>',
				cssClass: 'btn-primary',
				autospin: true,
				action: function(dialogRef){
					dialogRef.enableButtons(false);
					dialogRef.setClosable(false);

					var frm=dialogRef.getModalBody().find('.frm_new_source_code');
					console.log(dialogRef);
					frm.submit();
				}
			}, {
				label: '<?php echo lang('Cancel'); ?>',
				action: function(dialogRef){
					dialogRef.close();
				}
			}]
		});
	}

	function removeSourceCode(affTrackingId, sourceCode){
		BootstrapDialog.show({
			title: '<?php echo lang('Delete'); ?>',
			message: '<form method="POST" class="frm_remove_source_code" action="<?php echo site_url("/affiliate/remove_source_code/".$affiliateId); ?>/'+affTrackingId+'"><?php echo lang("Affiliate Source Code"); ?>: <input type="text" disabled="disabled" class="form-control" value="'+sourceCode+'"></form>',
			spinicon: 'fa fa-spinner fa-spin',
			buttons: [{
				icon: 'fa fa-save',
				label: '<?php echo lang('Delete'); ?>',
				cssClass: 'btn-danger',
				autospin: true,
				action: function(dialogRef){
					dialogRef.enableButtons(false);
					dialogRef.setClosable(false);
					// utils.safelog(dialogRef);

					var frm=dialogRef.getModalBody().find('.frm_remove_source_code');
					frm.submit();
					// dialogRef.getModalBody().html('Dialog closes in 5 seconds.');
					// setTimeout(function(){
					//     dialogRef.close();
					// }, 5000);
				}
			}, {
				label: '<?php echo lang('Cancel'); ?>',
				action: function(dialogRef){
					dialogRef.close();
				}
			}]
		});
	}
</script>