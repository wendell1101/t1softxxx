<?php
$upload_image_max_size =$this->utils->getMaxUploadSizeByte();
?>
<style>
	.notes-textarea {
		resize: none;
		height: 160px !important;
	}

	.btn-file input[type=file] {
      position: absolute;
      top: 0;
      right: 0;
      min-width: 100%;
      min-height: 100%;
      font-size: 100px;
      text-align: right;
      filter: alpha(opacity=0);
      opacity: 0;
      outline: none;
      background: white;
      cursor: inherit;
      display: block;
  }

  .form-control.upload-depo {
    position: relative;
    z-index: 2;
    float: left;
    width: 60%;
    margin-bottom: 0;
}
.account_item >a>label>input[type="radio"]{
	visibility: hidden;
}
.btn-group, .btn-group-vertical {
    display: block;
}
</style>

<div class="row">
	<div class="col-md-offset-3 col-md-6">
		<div class="panel panel-primary">
			<div class="panel-heading">
				<h3 class="panel-title">
					<i class="fa fa-plus"></i> <?=lang('lang.newDeposit')?>
				</h3>
			</div>
			<div class="panel-body" style="padding: 30px;">
				<div class="row">
					<?php if ($errors = validation_errors()): ?>
					<div class="alert alert-danger"><?=$errors?></div>
					<?php endif?>
					<form id="form" method="post" autocomplete="off" enctype="multipart/form-data">
						<?=$double_submit_hidden_field?>
						<div class="form-group required col-md-6">
							<label for="date" class="control-label"><?=lang('mess.06')?></label>
							<input name="date" id="date" class="form-control dateInput" type="text" value="<?=set_value('date', $this->utils->getNowForMysql())?>" data-time="true" required="required"/>
						</div>
						<div class="form-group required col-md-6">
							<label for="username" class="control-label"><?=lang('aff.as19')?></label>
							<input name="username" id="username" class="form-control" type="text" value="<?=set_value('username')?>" required="required"/>
						</div>
						<div class="form-group required col-md-6">
							<label for="amount" class="control-label"><?=lang('deposit_amount')?></label>
							<input name="amount" id="amount" class="form-control" type="number" value="<?=set_value('amount')?>" required="required" min="1" step="any"/>
							<span class="help-block"><?=lang('lang.memberDepLimit')?>: <b id="min_amount"></b> - <b id="max_amount"></b></span>

						</div>
						<div class="form-group required col-md-6">
							<label for="account" class="control-label"><?=lang('con.plm35')?></label>
							<select name="account" id="account" class="form-control" value="<?=set_value('account')?>" required="required">
								<option value=""><?=lang('cashier.74')?></option>
								<?php $previous_account = null?>
								<?php if(is_array($account_list)):?>
								<?php foreach ($account_list as $account_item): ?>
									<?php if ($previous_account != $account_item->payment_type): ?>
										<?php if ($previous_account !== null): ?>
											</optgroup>
										<?php endif?>
										<optgroup label="<?=lang($account_item->payment_type)?>">
									<?php endif?>
									<?php $previous_account = $account_item->payment_type?>
									<option class="payment_account_status" data-status="<?=($account_item->status == '2')?'in':'';?>active" value="<?=$account_item->id?>" <?=set_select('account', $account_item->id)?>><?=$account_item->payment_account_name?><?=!empty($account_item->payment_account_number)? ' - '.'******'.substr($account_item->payment_account_number, -4) : ''?></option>
								<?php endforeach?>
								<?php endif;?>
							</select>
							<input type="checkbox" class="hide_inactive_payment_account" id="hide_inactive_payment_account">
	                        <label for="hide_inactive_payment_account" class="control-label"><?=lang('payment.new_deposit.hide_inactive_payment_account')?></label>
						</div>

						<!-- OGP-2930 add the money transfer person name in add new deposit page (start) -->
						<div class="form-group col-md-12">
							<label for="bank_account_owner_name" class="control-label"><?=lang('Bank Account Owner Name')?></label>
							<input name="bank_account_owner_name" class="form-control" type="text" value="<?=set_value('bank_account_owner_name')?>" maxlength="60"/>
						</div>
						<!-- OGP-2930 add the money transfer person name in add new deposit page (end) -->

						<div class="form-group col-md-6">
							<label for="subwallet" class="control-label"><?=lang('player.ut08')?></label>
							<select name="subwallet" id="subwallet" class="form-control" value="<?=set_value('subwallet')?>">
								<option value=""><?=lang('select.empty.line')?></option>
								<?php foreach ($subwallets as $walletId => $walletName) {?>
									<option value="<?=$walletId?>" <?=set_select('subwallet', $walletId)?>><?=$walletName?></option>
								<?php } ?>
							</select>
						</div>
						<div class="form-group col-md-6">
							<label for="promo_cms_id" class="control-label"><?=lang('Promotion')?></label>
	                        <?php echo form_dropdown('promo_cms_id', $avail_promocms_list, null, 'class="avail_promocms_list form-control input-sm" style="height: 39px;"'); ?>
						</div>

						<div class="form-group col-md-6 required">
							<label for="internal_note" class="control-label"><?=lang('Internal Note')?></label>
							<textarea name="internal_note" id="internal_note" class="form-control notes-textarea" maxlength="500" required><?=set_value('internal_note')?></textarea>
						</div>
						<div class="form-group col-md-6">
							<label for="external_note" class="control-label"><?=lang('External Note')?></label>
							<textarea name="external_note" id="external_note" class="form-control notes-textarea" maxlength="500"><?=set_value('external_note')?></textarea>
						</div>

						<?php if($this->utils->getConfig('enable_newdeposit_upload_documents')) : ?>
						<div class="form-group col-md-12 upload-browse">
							<p class="control-label"><?=lang('Upload Attachment')?></p>
							<p id="errfm_txtImage" class="text-danger hide"></p>
		                    <span type="text" class="form-control upload-depo" maxlength="100"><?=lang('File 1')?>:</span>
		                    <label class="btn btn-default btn-file">
		                        <?= lang('Browse') ?>
		                        <input type="file" class="txtImage" id="file1" name="file1[]" title="<?=lang('upload_no_file_tooltip')?>" onchange="getFileData(this)" hidden />
		                    </label>
		                </div>

		                <?php if(!$this->config->item('disable_deposit_upload_file_2')) :?>
		                <div class="form-group col-md-12 upload-browse">
		                    <span type="text" class="form-control upload-depo" maxlength="100"><?=lang('File 2')?>:</span>
		                    <label class="btn btn-default btn-file">
		                        <?= lang('Browse') ?>
		                        <input type="file" class="txtImage" id="file2" name="file2[]" title="<?=lang('upload_no_file_tooltip')?>" onchange="getFileData(this)" hidden />
		                    </label>
		                </div>
		                <?php endif; ?>
		                 <?php endif; ?>
						<div class="col-md-12" style="text-align: right;">
							<div class="form-group required text-right" style="padding-top: 15px; padding-right: 30px; display: inline-block;">
								<label class="control-label"><?=lang('lang.status')?></label>
								<label class="radio-inline">
									<input type="radio" name="status" value="<?php echo Sale_order::STATUS_PROCESSING; ?>" <?php echo $this->utils->getConfig('default_manually_deposit_status') == 'pending' ? 'checked="checked"' : ''; ?> required="required">
									<?php echo lang('sale_orders.status.3'); ?>
								</label>
								<?php if ($this->permissions->checkPermissions('set_settled_on_new_deposit')) {?>
									<label class="radio-inline">
										<input type="radio" name="status" value="<?php echo Sale_order::STATUS_SETTLED; ?>" <?php echo $this->utils->getConfig('default_manually_deposit_status') == 'settled' ? 'checked="checked"' : ''; ?> required="required">
										<?php echo lang('sale_orders.status.5'); ?>
									</label>
								<?php }?>
							</div>
							<div class="text-right" style="display: inline-block;">
								<span class="help-block"><b id="submitBtnMsg"></b></span>
								<button type="submit" form="form" class="btn btn-portage btn_submit"><i class="fa fa-plus"></i> <?=lang('lang.addNewDeposit')?></button>
							</div>
						</div>
					</form>
				</div>
			</div>
		</div>
	</div>
</div>
<script type="text/javascript" src="<?php echo $this->utils->thirdpartyUrl('jquery-validate/jquery.validate.min.js') ?>"></script>
<?php include __DIR__ . "/../includes/jquery_validate_lang.php"?>
<script type="text/javascript">
	var ALLOWED_UPLOAD_FILE = "<?= $this->config->item('allowed_upload_file') ?>";
	var newdeposit_upload_documents = "<?= $this->utils->getConfig('enable_newdeposit_upload_documents') ? '1' : '0' ?>";
	var disable_deposit_upload_file_2 = <?= $this->config->item('disable_deposit_upload_file_2') ? 'true' : 'false' ?>;
	var required_deposit_upload_file_1 = "<?= $this->utils->isEnabledFeature('required_deposit_upload_file_1') ? 'true' : 'false' ?>";
	var LANG_UPLOAD_FILE_REQUIRED_ERRMSG = "<?=lang('Please upload at least one file when using ATM/Cashier payment account.')?>";
	var LANG_UPLOAD_IMAGE_MAX_SIZE = "<?= $upload_image_max_size ?>";
	var LANG_UPLOAD_FILE_ERRMSG = "<?= sprintf(lang('upload image limit and format'),$upload_image_max_size/1000000,$this->config->item('allowed_upload_file')) ?>";
	$( function() {
		$('#form').validate({
			errorElement: 'span',
			errorClass: 'help-block',
		    highlight: function (element, errorClass, validClass) {
	            $(element).closest('.form-group').removeClass('has-success').addClass('has-error');
		    },
		    unhighlight: function (element, errorClass, validClass) {
	            $(element).closest('.form-group').removeClass('has-error').addClass('has-success');
		    },
			submitHandler: function(form) {
			    if ($(form).valid()) {
			    	if (confirm('<?=lang('sys.sure')?>')) {
			    		$('.btn_submit').prop('disabled', true);
				        form.submit();
				    }
			    }
			},



			rules: {
				username: {
					remote: {
						url: '/api/playerUsernameExist',
						type: 'post',
						data: {
							username: function() {
								return $('#username').val();
							},
                            newdeposit : 1,
							checkExistPlayer : true
						}
					}
				}
			},
	        messages: {
	            username: {
	                remote: jQuery.validator.format("<?=lang('aff.as19')?> <b>{0}</b> <?=lang('player.uab10')?>.")
	            }
	        },
	        onkeyup: false
		});

		$( '#username,#amount' ).change( function() {
			var username = username = { username: $('#username').val() };
			$.post('/api/playerDepositLimit', username, function(response) {
				if (response.minDeposit && response.maxDeposit) {
					depositamount = Number($('#amount').val());

					$('#min_amount').text(response.minDeposit);
					$('#max_amount').text(response.maxDeposit);
					if(depositamount < Number(response.minDeposit)){
						$('.btn_submit').prop('disabled', true);
						$('#submitBtnMsg').text("<?php echo lang('Minimum Deposit Amount Reached') ?>");
                        addValidationColor("error");
					}else if(depositamount > Number(response.maxDeposit)){
						$('.btn_submit').prop('disabled', true);
						$('#submitBtnMsg').text("<?php echo lang('Maximum Deposit Amount Reached') ?>");
                        addValidationColor("error");
					}
					else{
						$('.btn_submit').prop('disabled', false);
						$('#submitBtnMsg').text("");
					}
				} else {
					if($(this).prop('id') == 'username'){
						$('#min_amount').text('0.00');
						$('#max_amount').text('0.00');
					}
                    // #43ac6a
				}
			}, 'json');
		}).trigger('change');

		$('.hide_inactive_payment_account').click(function(){
            var hide_inactive_payment_account = $('.hide_inactive_payment_account').is(':checked');
            if(hide_inactive_payment_account){
                $('#account .payment_account_status[data-status="inactive"]').addClass('hide');
            }else{
                $('#account .payment_account_status[data-status="inactive"]').removeClass('hide');
            }
            $('#account optgroup').each(function(){
                if($('option', this).length <= $('option.hide', this).length){
                    $(this).hide();
                }else{
                    $(this).show();
                }
            });

        });

		if (newdeposit_upload_documents == '1') {
			var deposit_form = $('#form');
			$('button.btn-portage', deposit_form).on('click', function () {
				if (!validateFiles()) {
					$('#errfm_txtImage').removeClass('hide');
					return false;
				}
			});
		}
	});

    function addValidationColor(status){
        if (status == "error") {
            $("#amount").closest("div").addClass("has-error");
        }
    }

    function getFileData(myFile) {
	    var file = myFile.files[0];
	    var filename = file.name;
	    if (myFile.id == 'file1') {
	        var add_remove_btn = '<button type="button" id="remove_file_btn1" class="filedata remove_btn" onclick="removeImage(this)"><i class="glyphicon glyphicon-remove-sign"></i></button>';
	    } else {
	        var add_remove_btn = '<button type="button" id="remove_file_btn2" class="filedata remove_btn" onclick="removeImage(this)"><i class="glyphicon glyphicon-remove-sign"></i></button>';
	    }
	    $(myFile).parent().parent().find("span").html(filename + add_remove_btn);
	}

	function removeImage(mybutton) {
	    if (mybutton.id == 'remove_file_btn1') {
	        $('#file1').val('')
	        $(mybutton).parent().parent().find("span").html('File 1:');
	    } else {
	        if(!disable_deposit_upload_file_2){
	            $('#file2').val('')
	            $(mybutton).parent().parent().find("span").html('File 2:');
	        }
	    }
	}

	function validateAttachedFile(file) {
	    var fp = $(file);
	    var lg = fp[0].files.length; // get length

	    if (lg != 0) {
	        var allowedUploadFile = ALLOWED_UPLOAD_FILE.split("|");
	        for (var i = 0; i < allowedUploadFile.length; i++) {
	            allowedUploadFile[i] = 'image/' + allowedUploadFile[i];
	        }

	        var fileErrMsg = LANG_UPLOAD_FILE_ERRMSG;

	        var items = fp[0].files;
	        if (lg > 0) {
	            for (var i = 0; i < lg; i++) {

	                var fileSize = items[i].size; // get file size
	                var fileType = items[i].type; // get file type
	            }
	        }

	        var limitSize = LANG_UPLOAD_IMAGE_MAX_SIZE;

	        if (fileSize <= limitSize) {
	            if (allowedUploadFile.indexOf(fileType) === -1) {
	                flg = 0;
	                $('#errfm_txtImage').text(fileErrMsg);
	                return false;
	            }

	        } else {
	            flg = 0;
	            $('#errfm_txtImage').text(fileErrMsg);
	            return false;
	        }
	    }

	    return true;
	}

	function alreadySelectFile(file) {
	    var fp = $(file);
	    var lg = fp[0].files.length; // get length

	    if (lg != 0) {
	        return true;
	    } else {
	        return false;
	    }
	}

	function validateFiles() {
	    if (typeof newdeposit_upload_documents === 'undefined') {
	        return true;
	    }
	    if (newdeposit_upload_documents === '1') {
	        if(!disable_deposit_upload_file_2){
	            var file_1 = $('#file1');
	            var file_2 = $('#file2');
	            success = (validateAttachedFile(file_1) && validateAttachedFile(file_2));
	        }else{
	            var file_1 = $('#file1');
	            success = validateAttachedFile(file_1);
	        }

	        // if (success) {//&& required_deposit_upload_file_1
	        //     should check required
	        //     success = alreadySelectFile(file_1);
	        //     if (!success) {
	        //         $('#errfm_txtImage').text(LANG_UPLOAD_FILE_REQUIRED_ERRMSG);
	        //     }
	        // }
	        return success;
	    } else {
	        return true;
	    }
	}
	$('#account').multiselect({
		enableFiltering: true,
		includeSelectAllOption: true,
		selectAllJustVisible: false,
		buttonClass: 'form-control',
		enableCaseInsensitiveFiltering: true,
		optionClass: function(element){
			return 'account_item';
		},
	});
</script>