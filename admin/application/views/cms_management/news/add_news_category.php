<div class="row">
	<div class="col-md-12" id="toggleView">
		<div class="panel panel-primary">
			<div class="panel-heading custom-ph">
				<h4 class="panel-title custom-pt pull-left">
					<i class="icon-newspaper"></i> <?=lang('cms.addnewscategory');?>
				</h4>
				<a href="<?=BASEURL . 'cms_management/viewNewsCategory'?>" class="btn pull-right btn-info btn-xs">
					<span class="glyphicon glyphicon-remove"></span>
				</a>
				<div class="clearfix"></div>
			</div>

			<div class="panel-body" id="affiliate_panel_body">
				<form class="form-horizontal" action="<?=BASEURL . 'cms_management/verifyAddNewsCategory'?>" method="POST">
					<div class="form-group">
						<div class="col-md-6 col-md-offset-3">
							<label for="name"><?=lang('cms.categoryname');?>:</label>
							<input type="text" name="name" maxlength="100" id="name" class="form-control" value="<?=set_value('name')?>"/>
							<span style="color: red;"><?=form_error('name')?></span>
						</div>
						<div class="col-md-6 col-md-offset-3" style="padding-top:10px;">
							<label for="language"><?=lang('player.62');?>:</label>
							<select name="language" id="language" class="form-control">
							   <option value="" ><?=lang('system.word3');?></option>
								<option value="en">English</option>
								<option value="ch">Chinese (中文)</option>
								<option value="id">Indonesian</option>
								<option value="vn">Vietnamese</option>
								<option value="kr">Korean</option>
								<option value="th">Thai</option>
								<option value="<?=Language_function::PROMO_SHORT_LANG_INDIA?>"><?=lang(Language_function::PLAYER_LANG_INDIA)?></option>
								<option value="<?=Language_function::PROMO_SHORT_LANG_PORTUGUESE?>"><?=Language_function::PLAYER_LANG_PORTUGUESE?></option>
								<option value="<?=Language_function::PROMO_SHORT_LANG_JAPANESE?>"><?=Language_function::PLAYER_LANG_JAPANESE?></option>
								<option value="<?=Language_function::PROMO_SHORT_LANG_CHINESE_TRADITIONAL?>"><?=Language_function::PLAYER_LANG_CHINESE_TRADITIONAL?></option>
								<option value="<?=Language_function::PROMO_SHORT_LANG_FILIPINO?>"><?=Language_function::PLAYER_LANG_FILIPINO?></option>
							</select>
							<span style="color: red;"><?=form_error('language')?></span>
						</div>
					</div>
					<center>
						<input type="submit" class="btn btn-portage" value="<?=lang('lang.add');?>" />
					</center>
				</form>
			</div>

			<div class="panel-footer"></div>
		</div>
	</div>
</div>