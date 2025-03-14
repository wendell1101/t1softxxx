<style type="text/css">
	table {
		table-layout: fixed;
	}

	table td {
		word-wrap: break-word;
	}

	.setVisibleBtn.disabled{
		background: linear-gradient( 180deg, #222 0%, #666 100%);
    	border: 1px solid #777;
		cursor: not-allowed;
		pointer-events: auto !important;

	}
	.banner-conainer{
		max-width: 100px;
		max-height: 100px;
	}
	.banner-background{
		width: 100px;
		height: 50px;
		border: 1px #ccc solid;
	}
</style>

<form class="form-horizontal" method="get" role="form" action="/cms_management/viewPopupManager">
	<div class="panel panel-primary hidden">
		<div class="panel-heading">
			<h4 class="panel-title">
				<i class="fa fa-search"></i> <?=lang("lang.search")?>
				<span class="pull-right">
					<a data-toggle="collapse" href="#collapseNewsSearch" class="btn btn-info btn-xs"></a>
				</span>
			</h4>
		</div>
		<div id="collapseNewsSearch" class="panel-collapse">
			<div class="panel-body">
				<div class="row">
					<div class="col-md-3">
						<label class="control-label" for="flag"><?=lang('cms.categoryname');?></label>
						<select class="form-control input-sm" name="categoryId">
							<option value=""><?= lang('All'); ?>
							</option>
							<?php foreach ($newsCategoryList as $list) : ?>
							<option
								value="<?= $list['id'] ?>"
								<?php echo (isset($condition['categoryId']) && $condition['categoryId'] == $list['id']) ? "selected" : "" ;?>><?= $list['name'] ?>
							</option>
							<?php endforeach; ?>
						</select>
					</div>
				</div>
				<div class="row">
					<div class="col-md-offset-9 col-md-3 text-right" style="padding-top: 25px">
						<input type="submit" class="btn btn-sm btn-portage" id="btn-submit"
							value="<?php echo lang('Search'); ?>">
					</div>
				</div>
			</div>
		</div>
	</div>

</form>

<div class="row">
	<div class="col-md-12" id="toggleView">
		<div class="panel panel-primary">
			<div class="panel-heading custom-ph">
				<h4 class="panel-title custom-pt pull-left">
					<i class="icon-newspaper"></i> <?=lang('Pop-up');?>
				</h4>
				<a href="<?=BASEURL . 'cms_management/addPopup'?>"
					class="btn pull-right btn-xs btn-info" id="add_popup">
					<i class="fa fa-plus-circle"></i> <?=lang('Add Pop-up');?>
				</a>
				<div class="clearfix"></div>
			</div>

			<div class="panel panel-body" id="inUseList">
				<div class="row">
					<div class="col-md-12">
						<h3><?=lang("In Use")?>
						</h3>
						<div class="table-responsive">
							<table class="table table-striped">
								<tr>
									<th width="5%"><?=lang("id")?>
									</th>
									<th width="15%"><?=lang('cms.title');?>
									</th>
									<th width="15%"><?=lang('cms.categoryname');?>
									</th>
									<th width="15%"><?=lang("Banner")?>
									</th>
									<th width="10%"><?=lang('cms.creator');?>
									</th>
									<th width="10%"><?=lang('lang.date');?>
									</th>
									<th width="10%"><?=lang('lang.action');?>
									</th>
								</tr>
								<?php if (count($popup_visible) > 0) : ?>
								<?php foreach ($popup_visible as $key => $value) {
									$popup_id = isset($value['id']) ? $value['id'] : $value['popup_id'];
								?>
								<tr>
									<td><?=$popup_id?>
									</td>
									<td>
										<?=$value['title']?>
									</td>
									<td><?=$value['name']?>
									</td>
									<td>
										<div class="banner-box">
											<?php if ($value['is_default_banner'] == 1):?>
											<div class="banner-conainer">
												<div class="banner-background" style="background-color: <?=$value['banner_url']?>;"></div>
												</div>
											<?php else:?>
											<div class="banner-conainer"><img
													src="<?=$value['banner_url']?>"
													alt=""></div>
											<?php endif;?>
										</div>
									</td>
									<td width="10%"><?=$value['username']?>
									</td>
									<td width="20%"><?=$value['created_at']?>
									</td>
									<td width="10%">
										<a href="<?=BASEURL . 'cms_management/editPopup/' . $popup_id?>"
											data-toggle="tooltip"
											title="<?=lang('Edit');?>"
											class="blue"><span class="glyphicon glyphicon-pencil"></span></a>

										<a href="#" data-toggle="tooltip"
											title="<?=lang('Delete');?>"
											class="blue"
											onclick="deletePopup(<?=$popup_id?>)"><span
												class="glyphicon glyphicon-trash"></span></a>

										<?php if ($value['set_visible']==1):?>
										<a href="<?=BASEURL . 'cms_management/setPopupToVisible/' . $popup_id?>"
											class="pull-right btn btn-xs m-b-5 setVisibleBtn btn-danger"> <?=lang("Cancel Visible");?></a>
										<?php else:?>
										<a href="<?=BASEURL . 'cms_management/setPopupToVisible/' . $popup_id?>"
											class="pull-right btn btn-portage btn-xs m-b-5 setVisibleBtn "> <?=lang("Set Visible");?></a>
										<?php endif;?>
									</td>
								</tr>
								<?php } ?>
								<?php else : ?>
								<tr>
									<td colspan="6" align="center"><?= lang('lang.norecord'); ?>
									</td>
								</tr>
								<?php endif; ?>
							</table>
						</div>
					</div>
				</div>
				<hr>
				<div class="row">
					<div class="col-md-12">
						<div class="table-responsive">
							<table class="table table-striped">
								<tr>
									<th width="5%"><?=lang("id")?>
									</th>
									<th width="15%"><?=lang('cms.title');?>
									</th>
									<th width="15%"><?=lang('cms.categoryname');?>
									</th>
									<th width="15%"><?=lang("Banner")?>
									</th>

									<th width="10%"><?=lang('cms.creator');?>
									</th>
									<th width="10%"><?=lang('lang.date');?>
									</th>
									<th width="10%"><?=lang('lang.action');?>
									</th>
								</tr>
								<?php if (count($popup) > 0) : ?>
								<?php foreach ($popup as $key => $value) {
									$popup_id = isset($value['id']) ? $value['id'] : $value['popup_id'];
									?>
								<tr>
									<td><?=$popup_id?>
									</td>
									<td>
										<?=$value['title']?>
									</td>
									<td><?=$value['name']?>
									</td>

									<td>
										<div class="banner-box">
											<?php if ($value['is_default_banner'] == 1):?>
											<div class="banner-conainer">
												<div class="banner-background" style="background-color: <?=$value['banner_url']?>;"></div>
												</div>
											<?php else:?>
											<div class="banner-conainer"><img
													src="<?=$value['banner_url']?>"
													alt=""></div>
											<?php endif;?>
										</div>
									</td>
									<td width="10%"><?=$value['username']?>
									</td>
									<td width="20%"><?=$value['created_at']?>
									</td>
									<td width="10%">
										<a href="<?=BASEURL . 'cms_management/editPopup/' . $popup_id?>"
											data-toggle="tooltip"
											title="<?=lang('Edit');?>"
											class="blue"><span class="glyphicon glyphicon-pencil"></span></a>

										<a href="#" data-toggle="tooltip"
											title="<?=lang('Delete');?>"
											class="blue"
											onclick="deletePopup(<?=$popup_id?>)"><span
												class="glyphicon glyphicon-trash"></span></a>

										<?php if ($value['set_visible']==1):?>
										<a
											class="pull-right btn btn-portage btn-xs m-b-5 setVisibleBtn disabled"> <?=lang("Set Visible");?></a>
										<?php else:?>
										<a href="<?=BASEURL . 'cms_management/setPopupToVisible/' . $popup_id?>"
											class="pull-right btn btn-portage btn-xs m-b-5 setVisibleBtn "> <?=lang("Set Visible");?></a>
										<?php endif;?>
									</td>
								</tr>
								<?php } ?>
								<?php else : ?>
								<tr>
									<td colspan="6" align="center"><?= lang('lang.norecord'); ?>
									</td>
								</tr>
								<?php endif; ?>
							</table>
							<br>

							<div class="row">
								<div class="col-md-12">
									<ul class="pagination pagination-sm" style="margin: 0; padding: 0;"> <?php echo $this->pagination->create_links(); ?>
									</ul>
								</div>
							</div>
						</div>
					</div>
				</div>

			</div>
		</div>
	</div>
</div>

<script type="text/javascript">
	$(document).ready(function() {
		popup = <?=json_encode($popup)?> ;
		// console.log(popup);
		// $("[name='my-checkbox']").bootstrapSwitch();
	});
</script>