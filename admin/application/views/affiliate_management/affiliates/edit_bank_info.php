<?php
	$bankNameType = '';
	if( is_numeric($payment['bankName']) ){
		/// When bankName is a integer, F.K. of banktype.bankTypeId.
		// like as 1, 2, 3, ...
		$bankNameType = 'fk';
	} else if( substr($payment['bankName'], 0,6) == '_json:') {
		/// When bankName is contains the multi-language, json-like string.
		// like as "_json:{"1":"Thai Military Bank (TMB)","6":"ธนาคารทหารไทย..."
		$bankNameType = 'langs';
	} else {
		// When bankName is a single language string.
		// like as "中国银行", "Kasikorn Bank"
		$bankNameType = 'string';
	}

	$isExistsInOptions = null;
	switch( $bankNameType ){
		case 'fk':
			$isExistsInOptions = in_array( $payment['bankName'], array_keys($payment_types));
			// Between the patched to the execute the command,
			// The admin user still may edit the option during the above moments.
			// For prevent mis-archives, its still keep the original string.
			$isExistsInOptions = false;
			break;

		/// for handle old data type
		case 'string':
		case 'langs':
			$isExistsInOptions = empty($payment['banktype_id'])? false: true;
			break;
	}
?>
<div class="row">
	<div class="col-md-12" id="toggleView">
		<div class="panel panel-primary">
			<div class="panel-heading">
				<h4 class="panel-title">
					<i class="icon-info"></i> <strong><?= lang('aff.ai22'); ?></strong>
					<a href="<?= BASEURL . 'affiliate_management/userInformation/' . $payment['affiliateId'] ?>" class="btn btn-default btn-sm pull-right" id="view_affiliate"><span class="glyphicon glyphicon-remove"></span></a>
				</h4>
			</div>

			<div class="panel-body" id="affiliate_info">
				<form id="editPaymentForm" action="<?= BASEURL . 'affiliate_management/verifyEditPayment/' . $payment['affiliatePaymentId'] ?>" method="POST">
					<input type="hidden" name="affiliate_payment_id" value="<?= $payment['affiliatePaymentId']; ?>">
					<input type="hidden" name="affiliate_id" id="affiliate_id" class="form-control" value="<?= $payment['affiliateId'] ?>">

					<div class="row">
						<div class="col-md-6 col-md-offset-0">
							<label for="banktype_id"><?= lang('Financial Institution'); ?>: </label>
						</div>

						<div class="col-md-6 col-md-offset-0">
							<label for="account_name"><?= lang('Acc Holder'); ?> : </label>
						</div>
						<div class="col-md-5 col-md-offset-0">
							<select class="form-control input-sm select-status" id="banktype_id" name="banktype_id">
								<?php if( ! $isExistsInOptions ) : ?>
									<option value ="<?php echo lang($payment['bankName'])?>" selected>
                                        <?php echo lang($payment['bankName'])?>
                                    </option>
                                <?php endif; ?>
                                <?php foreach($payment_types as $bankId => $bankNames) : ?>
                                    <option data-bankid="<?= $bankId?>" value ="<?= $bankId?>" <?= $payment['banktype_id'] == $bankId ? 'selected' : '' ?> ><?= $bankNames?>
                                	</option>
                                <?php endforeach; ?>
                            </select>
							<label style="color: red; font-size: 12px;"><?php echo form_error('banktype_id'); ?></label>
							<div style="color: red; font-size: 12px;" class="banktype_id_tip"></div>
						</div>

						<div class="col-md-5 col-md-offset-1">
							<input type="hidden" name="accountName" value="<?= $payment['accountName'] ?>"/>
							<input type="text" name="account_name" id="account_name" class="form-control" value="<?= (set_value('account_name') == null) ? $payment['accountName']:set_value('account_name'); ?>">
							<label style="color: red; font-size: 12px;"><?php echo form_error('account_name'); ?></label>
						</div>
					</div>

					<div class="row">
						<div class="col-md-6 col-md-offset-0">
							<label for="account_info"><?= lang('aff.ai24'); ?>: </label>
						</div>

						<div class="col-md-6 col-md-offset-0">
							<label for="account_number"><?= lang('aff.ai25'); ?>: </label>
						</div>

						<div class="col-md-5 col-md-offset-0">
							<input type="text" name="account_info" id="account_info" class="form-control" value="<?= (set_value('account_info') == null) ? $payment['accountInfo']:set_value('account_info'); ?>">
							<label style="color: red; font-size: 12px;"><?php echo form_error('account_info'); ?></label>
						</div>

						<div class="col-md-5 col-md-offset-1">
							<input type="text" name="account_number" id="account_number" class="form-control" value="<?= (set_value('account_number') == null) ? $payment['accountNumber']:set_value('account_number'); ?>">
							<label style="color: red; font-size: 12px;"><?php echo form_error('account_number'); ?></label>
						</div>
					</div>

					<br/>

					<div class="row">
						<center>
							<input type="submit" class="btn btn-scooter btn-sm" value="<?= lang('lang.save'); ?>"/>
							<a href="<?= BASEURL . 'affiliate_management/userInformation/' . $payment['affiliateId'] ?>" class="btn btn-linkwater btn-sm" id="view_affiliate"><?= lang('lang.cancel'); ?></a>
						</center>
					</div>
				</form>
				<!-- End of Personal Info -->
			</div>
		</div>
	</div>
</div>
<script type="text/javascript">

	var editPaymentForm = editPaymentForm||{};
	editPaymentForm.initialize = function(){
		var _this = this;
		_this.langs = {};
		_this.langs.banktype_id_tip_bankid_not_exist = 'The selected one does not exist, please select another.';

		return _this;
	}
	editPaymentForm.onReady = function(){
		var _this = this;
		$('body').on('submit', '#editPaymentForm', function(e){
			var _isValid = _this.isValid();
			if( _isValid != true){
				e.preventDefault(); //stop form from submitting
			}
		});
		$('body').on('change', 'select[name="banktype_id"]', function(e){
			_this.changed_bank_name_option(e);
		});

		$('select[name="banktype_id"]').trigger('change');
	}
	editPaymentForm.changed_bank_name_option = function(e){
		var _this = this;
		var _submit$El = $('#editPaymentForm').find('[type="submit"]');
		var _banktype_id_tip$El = $('.banktype_id_tip');
		var _bankid_of_banktype_id = $('select[name="banktype_id"] option:selected').data('bankid');
		_banktype_id_tip$El.html('');
		if( typeof( _bankid_of_banktype_id ) === 'undefined' ){
			// the selected one does not have the attribute, data-bankid.
			_banktype_id_tip$El.html(_this.langs.banktype_id_tip_bankid_not_exist);
			_submit$El.prop('disabled', true);
			_submit$El.attr('disabled','disabled');
		}else{
			_banktype_id_tip$El.html(''); // clear
			_submit$El.removeProp('disabled');
			_submit$El.removeAttr('disabled');
		}
	}; // EOF changed_bank_name_option
	editPaymentForm.isValid = function(){
		var _this = this;
		var _isValid = false;
		var _bankid_of_banktype_id = $('select[name="banktype_id"] option:selected').data('bankid');
		if( typeof( _bankid_of_banktype_id ) !== 'undefined' ){
			_isValid = true;
		}

		return _isValid;
	};  // EOF isValid

	$(document).ready(function() {
		var _editPaymentForm = editPaymentForm.initialize();
		_editPaymentForm.onReady();
	});
</script>