<div class="row">
	<div class="col-md-12" id="toggleView">
		<div class="panel panel-primary">
			<div class="panel-heading custom-ph">
				<h4 class="panel-title custom-pt pull-left">
					<i class="icon-newspaper"></i> <?=$form_title;?>
				</h4>
				<a href="<?=BASEURL . 'cms_management/viewPopupManager'?>"
					id="closeEditForm" class="btn pull-right btn-info btn-xs">
					<span class="glyphicon glyphicon-remove"></span>
				</a>
				<div class="clearfix"></div>
			</div>

			<div class="panel-body" id="affiliate_panel_body">
				<form class="form-horizontal" id="editPopUpForm"
					action="<?=BASEURL . $form_action?>"
					enctype="multipart/form-data" method="POST">
					<div class="form-group">
						<div class="col-md-6 col-md-offset-3">
							<label for="categoryId"><?=lang('cms.categoryname');?>:</label>
							<select name="categoryId" id="categoryId" class="form-control">
								<option value=""><?=lang('cms.categoryoption');?>
								</option>
								<?php foreach ($newsCategoryList as $list) : ?>
								<option
									value="<?= $list['id'] ?>">
									<?= $list['name'] ?>
								</option>
								<?php endforeach; ?>
							</select>
							<span style="color: red;"><?=form_error('categoryId')?></span>
						</div>
						<div class="col-md-6 col-md-offset-3 pt2rem">
							<label for="displayIn"><?=lang('The pop-up display in');?></label>
							<div class="checkboxGroup withBroder">
								<div class="displayIn-item">
									<input type="checkbox" name="displayIn-op[]" id="displayIn-op1" group='displayIn'
										value='1'>
									<label for="displayIn-op1"><?=lang('Player Center (Desktop)');?></label>
								</div>
								<div class="displayIn-item">
									<input type="checkbox" name="displayIn-op[]" id="displayIn-op2" group='displayIn'
										value='2'>
									<label for="displayIn-op2"><?=lang('Player Center (Mobile)');?></label>
								</div>
								<div class="displayIn-item">
									<input type="checkbox" name="displayIn-op[]" id="displayIn-op3" group='displayIn'
										value='3'>
									<label for="displayIn-op3"><?=lang('Website (Desktop)');?></label>
								</div>
								<div class="displayIn-item">
									<input type="checkbox" name="displayIn-op[]" id="displayIn-op4" group='displayIn'
										value='4'>
									<label for="displayIn-op4"><?=lang('Website (Mobile)');?></label>
								</div>
							</div>
						</div>
						<!-- <div class="col-md-6 col-md-offset-3 pt2rem">
							<label for="displayFreq"><?=lang('Display Frequency');?></label>
							<div class="checkboxGroup withBroder">
								<div class="displayFreq-item">
									<input type="checkbox" name="displayFreq-op[]" id="displayFreq-op1"
										group='displayFreq' value='1'>
									<label for="displayFreq-op1"><?=lang("On player's first login only (after registration)");?></label>
								</div>
								<div class="displayFreq-item">
									<input type="checkbox" name="displayFreq-op[]" id="displayFreq-op2"
										group='displayFreq' value='2'>
									<label for="displayFreq-op2"><?=lang('On every player login');?></label>
								</div>
							</div>
						</div> -->
						<div class="col-md-6 col-md-offset-3 pt2rem">
							<label for="redirectionSetting"><?=lang('Allow redirection to other page?');?>
								<span class="redirectTo-radio"> <input type="radio" name="redirectTo"
										id="redirectTo-disable" value="disable"> <label for="redirectTo-disable"><?=lang('No');?></label>
								</span>
								<span class="redirectTo-radio"> <input type="radio" name="redirectTo"
										id="redirectTo-enable" value="enable"> <label for="redirectTo-enable"><?=lang('Yes');?></label>
								</span>
							</label>
							<div class="redirectionSetting-content">
								<div class="redirectionSetting-item option">
									<label for="button_link"><?=lang('Redirect button link');?>:</label>
									<select name="button_link" id="button_link" class="form-control">
										<option value="1"><?=lang('Deposit');?>
										</option>
										<option value="2"><?=lang('Refer a Friend');?>
										</option>
										<option value="3"><?=lang('Promotions');?>
										</option>
									</select>
								</div>
								<div class="redirectionSetting-item btnName">
									<label for="redirectBtnName" style="display: block;"><?=lang('Display button name');?></label>
									<input type="text" name="redirectBtnName" id="redirectBtnName" class="form-control">
								</div>
							</div>
						</div>

						<div class="col-md-6 col-md-offset-3 pt2rem">
							<label><?=lang('cms.date');?>:</label><br>
							<input type="checkbox" name="is_daterange" value="1"> <label><?=lang('cms.setdaterange');?></label>
							<input type="text" id="datetime_range" name="datetime" class="form-control dateInput"
								value="<?=set_value('datetime')?>"
								data-start="#start_date" data-end="#end_date" data-time="true" data-empty="true"
								data-future="TRUE" />
							<input type="hidden" id="start_date" name="start_date" value="">
							<input type="hidden" id="end_date" name="end_date" value="">
							<span style="color: red;"><?=form_error('datetime')?></span>
						</div>
						<div class="col-md-6 col-md-offset-3 pt2rem">
							<label for="title"><?=lang('cms.title');?>:</label>
							<input type="text" name="title" maxlength="100" id="title" class="form-control"
								value="<?=set_value('title')?>" />
							<span style="color: red;"><?=form_error('title')?></span>
						</div>
						<div class="col-md-6 col-md-offset-3 pt2rem">
							<label for="content"><?=lang('cms.content');?>:</label>
							<div class="">
								<div id="summernote-editor" class="">
									<textarea class="summernote" id="contentInput"></textarea>
								</div>
								<input type="hidden" name="summernoteDetailsLength" id="summernoteDetailsLength">
								<input type="hidden" name="summernoteDetails" id="summernoteDetails">
							</div>
							<span style="color: red;"><?=form_error('Content')?></span>
						</div>
						<div class="col-md-6 col-md-offset-3 pt2rem">
							<input type="hidden" name="banner_url" id="editBannerUrl" readonly>
							<input type="hidden" name="upload_banner_url" id="uploadBannerUrl" readonly>
							<input type="hidden" name="editPromoThumbnail" id="editPromoThumbnail" readonly>
							<input type="hidden" name="is_default_banner_flag" id="isEditDefaultBannerFlag" readonly>

							<label for="banner"><?=lang('Banner');?>:</label>
							<div class="banner_container">
								<div class="bannerImgPreview">
									<div class="bannerImgPreview-content">
										<img id="edit_promo_cms_banner_600x300" class="promo_cms_banner_600x300"
											style="width: 600px; height: 300px;" />
										<div class='upload_req_txt' id="edit_upload_req_txt">
											<div class="txt-content">
												<p>600px x 300px</p>
												<p>JPEG,PNG,GIF</p>
												<p><?=lang("File must not exceed 2MB.");?>
												</p>
											</div>
										</div>
									</div>
								</div>
								<center>
									<div class="fileUpload btn btn-md btn-info">
										<span><?=lang("Upload") ?></span>
										<input type="file" name="userfile" class="upload" id="userfile"
											onchange="uploadImage(this,'edit_promo_cms_banner_600x300');">
									</div>
									<div class="previewBtn btn btn-md btn-scooter" onclick="showEditBannerPreview()"
										data-toggle="modal" data-target=".bannerPreview">
										<span><?=lang("Preview") ?></span>
									</div>
								</center>
								<div class="bannerImgOption">
									<div class="promo_banner_sec upload_btn_sec">
										<div class="">
											<input type="checkbox" name="set_default_banner" value="true"
												class="user-success" id="edit_set_default_banner">
											<label for="edit_set_default_banner"><?=lang("Use default banner") ?></label>
										</div>
										<?php if (0):?>
										<div class="presetBannerType">
											<div class="presetBanner-item"><img class="presetBannerImg btn"
													id="default_promo_cms_1"
													onclick="setBannerImg(this,'edit_promo_cms_banner_600x300')"
													src="<?=$this->utils->imageUrl('promothumbnails/default_promo_cms_1.jpg')?>"
													width="130px"></div>
											<div class="presetBanner-item"><img class="presetBannerImg btn"
													id="default_promo_cms_2"
													onclick="setBannerImg(this,'edit_promo_cms_banner_600x300')"
													src="<?=$this->utils->imageUrl('promothumbnails/default_promo_cms_2.jpg')?>"
													width="130px"></div>
											<div class="presetBanner-item"><img class="presetBannerImg btn"
													id="default_promo_cms_3"
													onclick="setBannerImg(this,'edit_promo_cms_banner_600x300')"
													src="<?=$this->utils->imageUrl('promothumbnails/default_promo_cms_3.jpg')?>"
													width="130px"></div>
											<div class="presetBanner-item"><img class="presetBannerImg btn"
													id="default_promo_cms_4"
													onclick="setBannerImg(this,'edit_promo_cms_banner_600x300')"
													src="<?=$this->utils->imageUrl('promothumbnails/default_promo_cms_4.jpg')?>"
													width="130px"></div>
											<div class="presetBanner-item"><img class="presetBannerImg btn"
													id="default_promo_cms_5"
													onclick="setBannerImg(this,'edit_promo_cms_banner_600x300')"
													src="<?=$this->utils->imageUrl('promothumbnails/default_promo_cms_5.jpg')?>"
													width="130px"></div>
											<div class="presetBanner-item"><img class="presetBannerImg btn"
													id="default_promo_cms_6"
													onclick="setBannerImg(this,'edit_promo_cms_banner_600x300')"
													src="<?=$this->utils->imageUrl('promothumbnails/default_promo_cms_6.jpg')?>"
													width="130px"></div>
											<div class="presetBanner-item"><img class="presetBannerImg btn"
													id="default_promo_cms_7"
													onclick="setBannerImg(this,'edit_promo_cms_banner_600x300')"
													src="<?=$this->utils->imageUrl('promothumbnails/default_promo_cms_7.jpg')?>"
													width="130px"></div>
											<div class="presetBanner-item"><img class="presetBannerImg btn"
													id="default_promo_cms_8"
													onclick="setBannerImg(this,'edit_promo_cms_banner_600x300')"
													src="<?=$this->utils->imageUrl('promothumbnails/default_promo_cms_8.jpg')?>"
													width="130px"></div>
										</div>
										<?php endif;?>
										<?php if (1):?>
										<div class="presetBannerType">
											<div class="presetBanner-item">
												<img class="presetBannerImg single_color_banner" id="single_color_01"
													style="background-color: #ffffff;"
													onclick="setBannerImg(this,'edit_promo_cms_banner_600x300')">
											</div>
											<div class="presetBanner-item">
												<img class="presetBannerImg single_color_banner" id="single_color_02"
													style="background-color: #262626;"
													onclick="setBannerImg(this,'edit_promo_cms_banner_600x300')">
											</div>
											<div class="presetBanner-item">
												<img class="presetBannerImg single_color_banner" id="single_color_03"
													style="background-color: #808080;"
													onclick="setBannerImg(this,'edit_promo_cms_banner_600x300')">
											</div>
											<div class="presetBanner-item">
												<img class="presetBannerImg single_color_banner" id="single_color_04"
													style="background-color: #d3d3d3;"
													onclick="setBannerImg(this,'edit_promo_cms_banner_600x300')">
											</div>
											<div class="presetBanner-item">
												<img class="presetBannerImg single_color_banner" id="single_color_05"
													style="background-color: #ff4b4b;"
													onclick="setBannerImg(this,'edit_promo_cms_banner_600x300')">
											</div>
											<div class="presetBanner-item">
												<img class="presetBannerImg single_color_banner" id="single_color_06"
													style="background-color: #ffff58;"
													onclick="setBannerImg(this,'edit_promo_cms_banner_600x300')">
											</div>
											<div class="presetBanner-item">
												<img class="presetBannerImg single_color_banner" id="single_color_07"
													style="background-color: #1ab31a;"
													onclick="setBannerImg(this,'edit_promo_cms_banner_600x300')">
											</div>
											<div class="presetBanner-item">
												<img class="presetBannerImg single_color_banner" id="single_color_08"
													style="background-color: #5a5aff;"
													onclick="setBannerImg(this,'edit_promo_cms_banner_600x300')">
											</div>
										</div>
										<?php endif;?>
									</div>
								</div>
							</div>
						</div>
					</div>
					<center>
						<span class="btn btn-sm btn-scooter" onclick="showEditPromoCmsPreview()" data-toggle="modal"
							data-target=".promoCmsPreview"><?=lang("Preview") ?></span>
						<button type="button" class="btn btn-secondary" id="cancelEditPopup">
							<?=lang("Cancel") ?>
						</button>
						<input type="submit" class="btn btn-portage" id="addNewPopupBtn"
							value="<?=$submit_text;?>" />
					</center>
				</form>
			</div>

			<div class="panel-footer"></div>
		</div>
	</div>
</div>
<!-- Banner Preview Modal Start -->
<div class="modal fade bannerPreview" tabindex="-1" role="dialog" aria-labelledby="bannerPreview" aria-hidden="true">
	<div class="modal-dialog modal-lg">
		<div class="modal-dialog" role="document">
			<div class="modal-content">
				<div class="modal-header">
					<b><?=lang("Preview") ?></b>
					<button type="button" class="close" data-dismiss="modal" aria-label="Close">
						<span aria-hidden="true">&times;</span>
					</button>
				</div>
				<div class="modal-body">
					<div class="bannerPreviewContainer">
						<div id="preview_promo_cms_banner_background"></div>
							<img id="preview_promo_cms_banner_600x300" class="preview_promo_cms_banner_600x300" src="">
						<div class="clearfix"></div>
					</div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-secondary" data-dismiss="modal"><?=lang("Close") ?></button>
				</div>
			</div>
		</div>
	</div>
</div>
<!-- Banner Preview Modal End -->

<!-- Promo CMS Preview Modal Start -->
<div class="modal fade promoCmsPreview" tabindex="-1" role="dialog" aria-labelledby="promoCmsPreview"
	aria-hidden="true">
	<div class="modal-dialog modal-lg">
		<div class="modal-dialog" role="document">
			<div class="modal-content">
				<div class="modal-header">
					<b><?=lang("Preview") ?></b>
					<button type="button" class="close" data-dismiss="modal" aria-label="Close">
						<span aria-hidden="true">&times;</span>
					</button>
				</div>
				<div class="modal-body">
					<div class="bannerPreviewContainer">
						<div class="previewContent">
							<div id="preview_promo_cms_banner_background_big">
							</div>
							<img id="preview_promo_cms_banner_600x300_big" class="preview_promo_cms_banner_600x300_big"
								src="">
							<div class="previewDetail">
								<div class="priviewTitle"></div>
								<div class="previewDetailsTxt"></div>
								<div class="redriectBtn hide">
									<button type="button" class="btn"></button>
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-secondary" data-dismiss="modal"><?=lang("Close") ?></button>
				</div>
			</div>
		</div>
	</div>
</div>
<!-- Promo CMS Preview Modal End -->
<script type="text/javascript" src="/resources/js/cms_management/cmspopup_management.js"></script>
<script type="text/javascript">
	$(document).ready(function() {
		popupSettings = <?=(isset($popup) ? json_encode($popup) : 0)?> ;
		// console.log(popupSettings);
		//get pop-up detail by ajax
		popupManager.initialize();
	});
</script>