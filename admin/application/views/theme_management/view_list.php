<style type="text/css">
	.text-right {
		text-align: right !important;
	}
	.col-xs-5ths,
	.col-sm-5ths,
	.col-md-5ths,
	.col-lg-5ths {
	    position: relative;
	    min-height: 1px;
	    padding-right: 15px;
	    padding-left: 15px;
	}

	.col-xs-5ths {
	    width: 20%;
	    float: left;
	}

	@media (min-width: 768px) {
	    .col-sm-5ths {
	        width: 20%;
	        float: left;
	    }
	}

	@media (min-width: 992px) {
	    .col-md-5ths {
	        width: 20%;
	        float: left;
	    }
	}

	@media (min-width: 1200px) {
	    .col-lg-5ths {
	        width: 20%;
	        float: left;
	    }
	}
	.btn.active {
		border: 3px solid #ff0000;
	}

    .style-in-iframe {
        position: absolute;
        left: 0;
        width: 100%;
        top: -30px;
        overflow-y: scroll;
        height: 1050px;
    }
    .style-in-iframe>.panel-primary{
        border: none;
    }
    /*.custom-container{
        display: none;
    }*/
	.error_msg{
		color: red;
    	font-size: 12px;
	}

</style>
<div id="theme_main_content">
    <div class="panel panel-primary">
<h1 class="page-header p-l-15 p-r-15"><span id="spnpPageTitle"><?=lang('Theme')?></span>
	<?php if ($this->permissions->checkPermissions('add_theme')) :?>
		<a type="button" id="btn-addTheme" name="btn-addTheme" class="btn btn-sm pull-right btn-portage" data-original-title="" title=""><i class="glyphicon glyphicon-plus" style="color:white;" data-placement="bottom"></i><?= lang('Add Theme') ?></a>
	<?php endif; ?>
</h1>

        <div class="panel-body" id="player_panel_body">
<form id="form-theme" action="/theme_management/save" method="POST">
<div class="row">
	<?php foreach ($themes as $theme): ?>
		<div class="col-md-5ths">
				<label class="btn btn-block btn-default <?=$selected_theme == $theme['name'] ? 'active' : ''?>" style="height: 283px; margin-bottom: 30px;">
				<input type="radio" name="theme" value="<?=$theme['name']?>" autocomplete="off" <?=$selected_theme == $theme['name'] ? 'checked' : ''?> style="display: none;">
				<img src="<?= $theme['img_path'] ?>" alt="<?=ucfirst($theme['name'])?>" height="230" width="100%" style="display: block; margin-bottom: 10px;"/>
				<a href="/theme_management/downloadThemes/<?= urlencode($theme['name']) ?>" class="pull-right" style="position: relative; font-size: 18px;">
					<i class="fa fa-download" id="icon"></i>
				</a>
				<?=ucfirst($theme['name'])?>
			</label>

		</div>
	<?php endforeach ?>
</div>
</form>

<div class="well text-center">
	<button type="button" id="btn-preview" class="btn btn-scooter"><?=lang('Preview')?></button>
	<button type="button" id="btn-reset" class="btn btn-linkwater"><?=lang('Reset')?></button>
	<button type="submit" id="btn-save" form="form-theme" class="btn btn-portage"><?=lang('Save')?></button>
</div>
        </div>
    </div>
</div>
<?php if ($this->permissions->checkPermissions('add_theme')) :?>
	<div class="modal fade in" id="mdl-addTheme" tabindex="-1" role="dialog" aria-labelledby="label_player_notes">
	  <div class="modal-dialog" role="document">
	      <div class="modal-content">
	          <div class="modal-header">
	              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
	                  <span aria-hidden="true">&times;</span>
	              </button>
	              <h4 class="modal-title"><?= lang('Add Theme') ?></h4>
	          </div>
	          <form id="form_upload_themes" action="<?php echo site_url('/theme_management/upload_new_themes/');?>" method="post" enctype="multipart/form-data">
		          <div class="modal-body">
		          	<div class="row">
			            <div class="form-group row">
					    	<label class="col-sm-3 control-label text-right">
                                <font style="color:red;">*</font> <?= lang('Theme Name') ?>
			                </label>
			                <div class="col-sm-9">
					    		<input type="text" id="txtName" name="txtName">
								<div class="error_msg theme_name">
								</div>
							</div>
					  	</div>
			            <div class="form-group row">
					    	<label class="col-sm-3 control-label text-right">
                                <font style="color:red;">*</font> <?= lang('CSS Themes') ?>
			                </label>
			                <div class="col-sm-9">
					    		<input type="file" id="file_css_theme" name="file_css_theme[]" hidden>
								<div class="error_msg file_css_theme">
								</div>
							</div>
					  	</div>
			            <div class="form-group row">
					    	<label class="col-sm-3 control-label text-right">
                                <font style="color:red;">*</font> <?= lang('Theme Preview Image') ?>
			                </label>
			                <div class="col-sm-9">
					    		<input type="file" id="file_image" name="file_image[]" hidden>
								<div class="error_msg file_image">
								</div>
							</div>
					  	</div>
		            </div>
		          </div>
				  <div class="modal-footer">
					  <div class="well text-center">
						  <button type="button" class="btn btn-linkwater" data-dismiss="modal"><?=lang('Cancel')?></button>
						  <button type="submit" id="btn-save-new-theme" class="btn btn-portage"><?=lang('Save')?></button>
					  </div>
				  </div>
				</form>
	      </div>
	  </div>
	</div>

	<!-- success modal  -->
	<div class="modal fade in" id="mdl-addTheme_success" tabindex="-1" role="dialog" aria-labelledby="label_player_notes">
	  <div class="modal-dialog" role="document">
	      <div class="modal-content">
	          <div class="modal-header">
	              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
	                  <span aria-hidden="true">&times;</span>
	              </button>
	              <h4 class="modal-title"><?= lang('Upload New Theme') ?></h4>
			  </div>
			  <div class="modal-body">
				<div class=""><?= lang('Upload success') ?></div>
			  </div>
				<div class="modal-footer">
					<div class="text-center">
						<button type="button" class="btn btn-linkwater" data-dismiss="modal" onClick="window.location.reload();"><?=lang('Confirm')?></button>
					</div>
				</div>
	      </div>
	  </div>
	</div>
<?php endif;?>
<script type="text/javascript">
	var preview_expiry;

	if ( self !== top ) {
		$('nav').remove();
		$('#sidebar-wrapper').remove();
		$('#theme_main_content').addClass('style-in-iframe');
        $('#main_content').css({ height: "1000px"});
	}

	$(document).ready(function(){
		$('#form-theme')[0].reset();
		$.get('/theme_management/reset', function() {
			$('input[name="theme"]').trigger('change');
		});
	});

	$('input[name="theme"]').change(function() {
		$('.row label').removeClass('active');
		$('input[name="theme"]:checked').parent('label').addClass('active');
	});

	$('#btn-preview').click(function() {
		var theme = $('input[name="theme"]:checked').val();
		window.open('/theme_management/preview/' + encodeURI(theme), 'preview');

		clearInterval(preview_expiry);
		var preview_expiry_countdown = 60;
		$('#btn-preview').text('<?=lang('Preview')?> (' + preview_expiry_countdown-- + ')');
		preview_expiry = setInterval(function() {
			$('#btn-preview').text('<?=lang('Preview')?> (' + preview_expiry_countdown-- + ')');
			if (preview_expiry_countdown < 0) {
				$('#btn-preview').text('<?=lang('Preview')?>');
				clearInterval(preview_expiry);
			}
		}, 1000);
	});

	$('#btn-save').click(function(e) {
		return confirm("<?=lang('sys.sure')?>");
	});

	$('#btn-reset').click(function() {
		$('#form-theme')[0].reset();
		$.get('/theme_management/reset', function() {
			$('input[name="theme"]').trigger('change');
		});
	});

	$('#btn-addTheme').click(function() {
		$('input').val('');
		/*$('#file_image').val('');
		$('#txtName').val('');*/
		$('#mdl-addTheme').modal('show');
		$('.error_msg').empty();
	});

	$('#btn-save-new-theme').click(function(e) {
		e.preventDefault();
		$('.error_msg').empty();
		var form = $('#form_upload_themes')[0];
		var formData = new FormData(form);

		$.ajax({
			url: '/theme_management/upload_new_themes',
			type: 'POST',
			data: formData,
			processData: false,
			contentType:false,
			mimeType: 'multipart/form-data',
			cache : false,
			success: function(res) {
				res = JSON.parse(res);
				console.log(res);
				if(res && res.status == 'fail') {
					$.each(res, function(index, item) {
						if (index != 'status') {
							var content = '<font>* '+ item.msg +'</font>';
							$('.error_msg.'+index).html(content);
							console.log($('.error_msg .'+index));

						}
					})
				} else if(res && res.status == 'success') {
					//show success popup mdl-addTheme_success
					$('#mdl-addTheme').modal('hide');
					$('#mdl-addTheme_success').modal('show');
				}
			}
		});
	});

</script>