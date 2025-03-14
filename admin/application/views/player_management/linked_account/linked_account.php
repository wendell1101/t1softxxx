<!--main-->
<div class="panel panel-primary hidden">
    <div class="panel-heading">
        <h4 class="panel-title">
            <i class="fa fa-search"></i> <?=lang("lang.search")?>
            <span class="pull-right">
                <a data-toggle="collapse" href="#collapseTaggedList" class="btn btn-info btn-xs <?=$this->config->item('default_open_search_panel') ? '' : 'collapsed'?>"></a>
            </span>
        </h4>
    </div>

    <div id="collapseTaggedList" class="panel-collapse <?=$this->config->item('default_open_search_panel') ? '' : 'collapse in'?>">
        <div class="panel-body">
			<form id="search_linked_account_form">
				<div class="row">
					<div class="col-md-3">
						<div class="form-group">
							<label for="tag" class="control-label" style="padding-bottom: 6px;"><?=lang('Linked Date');?></label>
							<br/>
                            <div class="input-group">
                                <input id="search_linked_date" class="form-control input-sm dateInput" data-time="true" data-start="#linked_date_from" data-end="#linked_date_to"/>
                                <span class="input-group-addon input-sm">
                                    <input type="checkbox"
                                           id="search_enable_date"
                                           data-off-text="<?=lang('off'); ?>"
                                           data-on-text="<?=lang('on'); ?>"
                                           data-size='mini'
                                           value='<?=$conditions['enable_date']?>'
                                           <?=empty($conditions['enable_date']) ? '' : 'checked="checked"'; ?>
                                    />
                                    <input type="hidden" name="enable_date" value='<?php echo $conditions['enable_date']?>'>
                                </span>
                            </div>
                            <input type="hidden" id="linked_date_from" name="linked_date_from" value="<?=$conditions['linked_date_from'];?>"/>
                            <input type="hidden" id="linked_date_to" name="linked_date_to" value="<?=$conditions['linked_date_to'];?>"/>
						</div>
					</div>
					<div class="col-md-3">
						<div class="form-group" style="margin-right: 30px;">
							<label class="control-label"><?=lang('Username');?>:</label>
							<input type="radio" <?php echo $conditions['search_type'] == Linked_account_model::SEARCH_TYPE_EXACT_USERNAME ? "checked" : "" ?> name="search_type" id="search_type_exact" value="<?=Linked_account_model::SEARCH_TYPE_EXACT_USERNAME ?>" />
							<label class="control-label"><?=lang('Exact');?></label>
							<input type="radio" <?php echo $conditions['search_type'] == Linked_account_model::SEARCH_TYPE_SIMILAR_USERNAME ? "checked" : "" ?> name="search_type" id="search_type_similar" value="<?=Linked_account_model::SEARCH_TYPE_SIMILAR_USERNAME ?>" />
							<label class="control-label"><?=lang('Similar');?></label>
			            	<div class="clearfix"></div>
			               	<input type="text" name="username" class="form-control" value="<?=$conditions['username']?>"/>
						</div>
					</div>
				</div>
                <div class="row">
                    <div class="col-md-3">
                        <label class="control-label text-danger">*<?=lang('Searching must with at least one condition.');?></label>
                    </div>
                </div>
				<div class="row">
                    <div class="col-md-12 text-right">
						<input type="button" value="<?=lang('lang.reset');?>" class="btn btn-linkwater btn-sm" id="btn-reset">
						<input type="submit" value="<?=lang('lang.search');?>" class="btn btn-portage btn-sm" id="btn-search">
                    </div>
				</div>
			</form>
		</div>
	</div>
</div>
<!--end of main-->

<div class="row">
	<div class="col-md-12" id="toggleView">
		<div class="panel panel-primary">
			<div class="panel-heading custom-ph">
				<h4 class="panel-title custom-pt pull-left">
					<i class="glyphicon glyphicon-link"></i> <?=lang('Linked Account');?>
				</h4>

				<div class="pull-right">
					<?php if(!empty($players)){ ?>
						<input type="button" value="<?=lang('lang.exporttitle')?>" class="btn btn-info btn-xs export_excel">
					<?php } ?>
				</div>
				<span class="clearfix"></span>
			</div>

			<div class="panel-body" id="player_panel_body">
				<div class="table-responsive">
					<table class="table table-striped table-hover" id="myTable">
						<thead>
							<tr>
								<th><?=lang('From');?></th>
								<th><?=lang('Status (From)');?></th>
                                <th><?=lang('Linked Date');?></th>
								<th><?=lang('Linked Accounts Count');?></th>
								<th><?=lang('Linked Accounts');?></th>
							</tr>
						</thead>
						<?php if(!empty($players)){
							foreach ($players as $key) {
								if(count($key['linked_accounts'])){ ?>
									<tr>
										<td>
											<a target="_blank" href="<?=site_url('player_management/userInformation/'.$key['playerId'])?>">
												<?=$key['username'] ?>
											</a>
										</td>
										<td>
											<?php if($key['blocked'] == 1){?>
												<span class="text-danger"><?=lang('Blocked'); ?></span>
											<?php } elseif($key['blocked'] == 5){ ?>
												<span class="text-warning"><?=lang('Suspended'); ?></span>
											<?php } else { ?>
												<span class="text-success"><?=lang('lang.active'); ?></span>
											<?php } ?>
										</td>
                                        <td>
                                            <?=$key['link_datetime'] ?>
                                        </td>
										<td>
											<?=count($key['linked_accounts']) ?>
										</td>
										<td>
											<?php if(!empty($key['linked_accounts'])){ ?>
												<table class="table table-striped table-hover" >
													<th><?=lang('Action');?></th>
													<th><?=lang('Linked To');?></th>
													<th><?=lang('Linked Date');?></th>
													<th><?=lang('Remark');?></th>
													<th><?=lang('Status(To)');?></th>

													<?php if(!empty($key['linked_accounts'])){
														foreach ($key['linked_accounts'] as $la) { ?>
															<tr>
																<td><?php echo $la['action_edit_remarks']." ".$la['action_delete_remarks']; ?></td>
																<td><a target="_blank" href="<?php echo site_url('player_management/userInformation/'.$la['playerId'])?>"><?php echo $la['username']; ?></a></td>
																<td><?php echo $la['link_datetime']; ?></td>
																<td><?php echo $la['remarks']; ?></td>
																<td>
																	<?php if($la['blocked'] == 1){?>
																		<span class="text-danger"><?=lang('Blocked'); ?></span>
																	<?php } elseif($la['blocked'] == 5){ ?>
																		<span class="text-warning"><?=lang('Suspended'); ?></span>
																	<?php } else { ?>
																		<span class="text-success"><?=lang('lang.active'); ?></span>
																	<?php } ?>
																</td>
															</tr>
														<?php }
													}else{ ?>
														<tr>
															<td colspan="5"><?=lang("No Linked Account") ?></td>
														</tr>
													<?php } ?>
												</table>
											<?php } ?>
										</td>
									</tr>
								<?php
								}
							}
						} ?>
					</table>
				</div>
			</div>
		</div>
	</div>
</div>

<script type="text/javascript">

    $('#search_linked_account_form').submit( function(e) {
		search_linked_account_form_data = $('#search_linked_account_form').serializeArray();
        if($('input[name="enable_date"]').val() == '0' && $('input[name="username"]').val() == ''){
            alert("<?=lang('Searching must with at least one condition.');?>");
            e.preventDefault();
        }
    });


    $('#btn-reset').click(function(){
        var search_linked_date = $('#search_linked_date');
        $('#linked_date_from').val('<?=$default['linked_date_from'];?>');
        $('#linked_date_to').val('<?=$default['linked_date_to'];?>');
        dateInputAssignValue(search_linked_date , true);

        $('#search_enable_date').prop('checked', true);
        $('input[name="enable_date"]').val('1');
        handler_search_enable_date_change();

        $('[name="username"]').val('');
        $("#search_type_exact").attr('checked', true).trigger("click");;
    });

    //trigger enable_date check box
    $('#search_enable_date').change(function(){
        if($(this).is(':checked')) {
            handler_search_enable_date_change();
            $(this).prop('checked', true);
            $('input[name="enable_date"]').val('1');

        }else{
            handler_search_enable_date_change();
            $(this).prop('checked', false);
            $('input[name="enable_date"]').val('0');
        }
    }).trigger('change');

    function handler_search_enable_date_change() {
        var checked = $('#search_enable_date').is(':checked');
        $('#search_linked_date').removeAttr('disabled');
        if (!checked) {
            $('#search_linked_date').attr('disabled', true);
        }
    }
</script>

<!-- Linked Account Edit Remarks Modal Start -->
<?php $this->load->view('player_management/linked_account/linked_account_modals'); ?>
<!-- Linked Account Edit Remarks Modal End -->

<!-- Linked Account Common Script Start -->
<?php $this->load->view('player_management/linked_account/linked_account_script'); ?>
<!-- Linked Account Common Script End -->