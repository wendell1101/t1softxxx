<style>
   	.avoid-clicks {
   		pointer-events: none;
   	}
	.notes-textarea {
		resize: none;
		height: 160px !important;
	}
</style>
<div class="row">
	<div class="col-md-offset-3 col-md-6">
		<div class="panel panel-primary">
			<div class="panel-heading">
				<h3 class="panel-title">
					<i class="fa fa-minus"></i> <?=lang('lang.newWithdrawal')?>
                </h3>
			</div>
			<div class="panel-body">
				<?php if ($errors = validation_errors()): ?>
					<div class="alert alert-danger"><?=$errors?></div>
				<?php endif?>
				<form id="form" method="post" autocomplete="off">
					<?=$double_submit_hidden_field?>
					<div class="form-group required col-md-6">
						<label for="date" class="control-label"><?=lang('mess.06')?></label>
						<input name="date" id="date" class="form-control dateInput" type="text" value="<?=set_value('date', $this->utils->getNowForMysql())?>" data-time="true" required="required"/>
					</div>
					<div class="form-group required col-md-6">
						<label for="username" class="control-label"><?=lang('aff.as19')?> </label>
						<input name="username" id="username" class="form-control" type="text" value="<?=set_value('username', $username)?>" required="required"/>
						<span class="help-block"><?=lang('con.pb')?>: <strong id="balance">0.00</strong></span>
					</div>
					<div id="existing-bank">
						<div class="form-group required col-md-6">
							<label for="account" class="control-label"><?=lang('lang.bank')?></label>
							<select name="bank" id="bank" class="form-control" value="<?=set_value('bank')?>" required="required">
								<option value=""><?=lang('player.ui59')?></option>
							</select>
						</div>
					</div>
					<div class="form-group required col-md-6">
						<label for="amount" class="control-label"><?=lang('cashier.09')?></label>
						<input name="amount" id="amount" class="form-control" type="number" value="<?=set_value('amount', $this->utils->formatCurrencyNoSym($min_amount))?>" required="required" min="<?=$min_amount?>" step="any"/>
						<?php if ($this->utils->getConfig('enable_withdrawl_fee_from_player')) : ?>
		                    <p class="help-block withdraw-fee"><?= lang('fee.withdraw') ?>&nbsp;:&nbsp;<b id="withdraw_fee">0</b>&emsp;</p>
		                    <span class="help-block fee_hint"><?= lang('fee.withdraw.hint') ?></span>
		                <?php endif; ?>
						<span class="help-block"><?=lang('lang.minimumAmount')?>: <b id="min_amount"><?=$this->utils->formatCurrencyNoSym($min_amount)?></b></span>
						<span class="help-block"><?=lang('pay.dailymaxwithdrawal')?>: <b id="daily_max_withdrawal">0</b></span>
						<span class="help-block"><?=lang('Total Withdraw Today')?>: <b id="total_withdraw_today">0</b></span>
					</div>

					<input type="radio" name="type" class="hidden" value="1" checked="checked"/>
					<div class="form-group required col-md-6">
						<label for="internal_note" class="control-label"><?=lang('Internal Note')?></label>
						<textarea name="internal_note" id="internal_note" class="form-control notes-textarea" maxlength="500"><?=set_value('internal_note')?></textarea>
					</div>
					<div class="form-group col-md-6">
						<label for="external_note" class="control-label"><?=lang('External Note')?></label>
						<textarea name="external_note" id="external_note" class="form-control notes-textarea" maxlength="500"><?=set_value('external_note')?></textarea>
					</div>

					<div class="text-right col-md-12">
						<span class="help-block"><b id="submitBtnMsg"></b></span>
					</div>
					<div class="col-md-12" style="text-align: right;">
						<div class="form-group required text-right" style="padding-top: 15px; padding-right: 30px; display: inline-block;">
							<label class="control-label"><?=lang('lang.status')?></label>
							<label class="radio-inline"><input type="radio" name="status" value="<?php echo Wallet_model::REQUEST_STATUS; ?>" <?php echo $this->utils->getConfig('default_manually_withdraw_status') == 'pending' ? 'checked="checked"' : ''; ?> required="required"> <?php echo lang('sale_orders.status.3'); ?></label>
						</div>
						<div class="text-right" style="display: inline-block;">
							<button type="submit" form="form" class="btn btn-portage btn_submit"><i class="fa fa-plus"></i> <?=lang('lang.addNewWithdrawal')?></button>
						</div>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>

<script type="text/javascript" src="<?php echo $this->utils->thirdpartyUrl('jquery-validate/jquery.validate.min.js') ?>"></script>
<?php include __DIR__ . "/../includes/jquery_validate_lang.php"?>
<script type="text/javascript">
	var min_amount = <?=$min_amount?>;
	var daily_max_withdrawal = 0;
	var total_withdraw_today = 0;
	$( function() {
		var validator = $('#form').validate({
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
							checkExistPlayer : true
						}
					}
				},
				accountNumber: {
					remote: {
						url: '/api/bankAccountNumber',
						type: 'post',
						data: {
							accountNumber: function() {
								return $('#accountNumber').val();
							},
						}
					}
				},
			},
	        messages: {
	            username: {
	                remote: jQuery.validator.format("Member Username <b>{0}</b> does not exist.")
	            },
	            accountNumber: {
	                remote: jQuery.validator.format("Bank Account Number <b>{0}</b> is already registered.")
	            }
	        },
	        onkeyup: false,
		});

        $('.btn_submit').hide();

		$( '#username' ).change( function() {
			var data = { username: $(this).val() };

			$.post('/api/withdrawalBankList', data, function(response) {
				var selected = '<?=set_value('bank', '')?>';
				$('#bank').val(selected);
				$('#bank option:not(:first-child), #bank optgroup').remove();
				if (response.list) {
					$.each(response.list, function(i,v) {
						var optgroup = $('#banktype-' + v.bankTypeId);
						if (optgroup.length == 0) {
							optgroup = $('<optgroup>').attr('id', 'banktype-' + v.bankTypeId).attr('label', v.bankName);
							$('#bank').append(optgroup);
						}
						var option = $('<option>').val(v.playerBankDetailsId).text(v.bankAccountFullName + ' - ' + v.bankAccountNumber);
						optgroup.append(option);
					});
					$('#bank').val(selected);
				}
			}, 'json');

			$.post('/api/playerMainWalletBalance', data, function(response) {
				if (response.balance) {
					var balance = parseFloat(response.balance.replace(/,/g, ''));
					$('#amount').prop('max',balance);
					$('#balance').text(response.balance);
				} else {
					$('#amount').removeAttr('max');
					$('#balance').text('0.00');
				}
			}, 'json');

			$.post('/api/playerWithdrawLimit', data, function(response) {
				if (response) {
					min_amount = response.min_withdraw_per_transaction;
					$('#min_amount').text(min_amount);

					daily_max_withdrawal = response.dailyMaxWithdrawal;
					$('#daily_max_withdrawal').text(daily_max_withdrawal);

					total_withdraw_today = response.playerTotalWithdrawForToday;
					$('#total_withdraw_today').text(total_withdraw_today);

					$('#amount').val(min_amount).trigger('change');
					$('#amount').prop('min', min_amount);
				} else {
					$('#daily_max_withdrawal').text('0.00');
					$('#total_withdraw_today').text('0.00');
				}
			}, 'json');

			$.post('/api/playerName', data, function(response) {
				if (response.playerName !== false) {
					$('#accountName').val(response.name);
				}
				$('#new-bank input, #new-bank select').val('');
			}, 'json');
		}).trigger('change');

		$('input[name="type"]').click( function() {
			var type = $(this).val();
			$('input[name="type"]').closest('label').removeClass('btn-primary').addClass('btn-default');
			$(this).closest('label').removeClass('btn-default').addClass('btn-primary');
			if (type == 1) {
				$('#existing-bank').show();
				$('#new-bank').hide();
			} else {
				$('#existing-bank').hide();
				$('#new-bank').show();
			}
		});

		$('#bank, #amount').change( function() {
 	    	var withdrawamount = parseFloat($('#amount').val());
        	var withdrawtotal = Number(total_withdraw_today) + Number(withdrawamount);

            if(isNaN(withdrawtotal)){
                $('.btn_submit').prop('disabled', true).hide();
                $('#submitBtnMsg').text("");
            }else if(withdrawtotal > parseFloat(daily_max_withdrawal)){
                $('.btn_submit').prop('disabled', true).hide();
                $('#submitBtnMsg').text("<?=lang('Daily Maximum Withdrawal Reached') ?>");
            }else if(withdrawamount < parseFloat(min_amount)){
            	$('.btn_submit').prop('disabled', true).hide();
             	$('#submitBtnMsg').text("<?=lang('notify.119') ?>");
            }else{
                $('.btn_submit').prop('disabled', false).show();
                $('#submitBtnMsg').text("");
            }
        });
	});

	var enable_withdrawl_fee_from_player = '<?=$this->utils->getConfig('enable_withdrawl_fee_from_player')?>';

    if (enable_withdrawl_fee_from_player) {
        $(document).on("change", "#amount" , function() {
            // withdraw_fee
            var username =$("#username").val();
            var amount = $(this).val();

            $.ajax({
                'url' : '/api/getWithdrawFee/',
                'type' : 'POST',
                'dataType' : "json",
                'data': {'username' :username, 'amount' :amount},
                'success' : function(data){
                    if(data['success']){
                        $('#withdraw_fee').text(data.amount);
                    }
                }
            });
        });
    }
</script>
