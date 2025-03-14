<div class="row">
	<div class="col-md-12" id="toggleView">
		<div class="panel panel-primary">
			<div class="panel-heading">
				<h4 class="panel-title">
					<i class="icon-info"></i> <strong><?= lang('aff.ai22'); ?></strong>
					<a href="<?= BASEURL . 'affiliate_management/userInformation/' . $affiliate_id ?>" class="btn btn-default btn-sm pull-right" id="view_affiliate"><span class="glyphicon glyphicon-remove"></span></a>
				</h4>
			</div>

			<div class="panel-body" id="affiliate_info">
				<!-- Personal Info -->
				<form method="POST" action="<?= BASEURL . 'affiliate_management/verifyaddNewAccount' ?>" accept-charset="utf-8">
					<input type="hidden" name="affiliate_id" id="affiliate_id" class="form-control" value="<?= $affiliate_id ?>">

					<div class="row">
						<div class="col-md-6 col-md-offset-0">
							<label for="banktype_id"><?= lang('Financial Institution'); ?>: </label>
						</div>

						<div class="col-md-6 col-md-offset-0">
							<label for="account_name"><?= lang('Acc Holder'); ?> : </label>
						</div>

						<div class="col-md-5 col-md-offset-0">
							<p>
                                <select name="banktype_id" id="banktype_id" class="form-control input-sm" required>
                                    <?php foreach ($payment_types as $key => $value) {?>
                                        <option value="<?=$key?>"><?=$value?></option>
                                    <?php } ?>
                                </select>
                                <?php echo form_error('banktype_id', '<span class="help-block" style="color:#ff6666;">', '</span>'); ?>
                            </p>
						</div>

						<div class="col-md-5 col-md-offset-1">
							<input type="text" name="account_name" id="account_name" class="form-control" value="<?= set_value('account_name'); ?>">
							<label style="color: red; font-size: 12px;"><?php echo form_error('account_name'); ?></label>
						</div>
					</div>

					<div class="row">
						<div class="col-md-6 col-md-offset-0">
							<label for="account_info"><?= lang('aff.ai24'); ?>: </label>
						</div>

						<div class="col-md-6 col-md-offset-0">
							<label for="account_number"><?= lang('cashier.69'); ?>: </label>
						</div>

						<div class="col-md-5 col-md-offset-0">
							<input type="text" name="account_info" id="account_info" class="form-control" value="<?= set_value('account_info'); ?>">
							<label style="color: red; font-size: 12px;"><?php echo form_error('account_info'); ?></label>
						</div>

						<div class="col-md-5 col-md-offset-1">
							<input type="text" name="account_number" id="account_number" class="form-control number_only" value="<?= set_value('account_number'); ?>">
							<label style="color: red; font-size: 12px;"><?php echo form_error('account_number'); ?></label>
						</div>
					</div>

					<br/>

					<div class="row">
						<center>
							<input type="submit" class="btn btn-sm btn-scooter" value="<?= lang('lang.save'); ?>"/>
							<a href="<?= BASEURL . 'affiliate_management/userInformation/' . $affiliate_id ?>" class="btn btn-sm btn-linkwater" id="view_affiliate"><?= lang('lang.cancel'); ?></a>
						</center>
					</div>
				</form>
				<!-- End of Personal Info -->
			</div>
		</div>
	</div>
</div>

<script type="text/javascript">
	$(".letters_only").keydown(function (e) {
            var code = e.keyCode || e.which;
            // Allow: backspace, delete, tab, escape, enter and .
            if ($.inArray(code, [46, 8, 9, 27, 32, 13, 110, 190]) !== -1 ||
                 // Allow: Ctrl+A
            	( e.ctrlKey === true) || ( e.metaKey === true) ||
                 // Allow: home, end, left, right, down, up
                (code >= 35 && code <= 40)) {
                     // let it happen, don't do anything
                     return;
            }
            // Ensure that it is a number and stop the keypress
            if (e.ctrlKey === true || code < 65 || code > 90) {
                e.preventDefault();
            }
        });

	$(".number_only").keydown(function (e) {
        var code = e.keyCode || e.which;
        // Allow: backspace, delete, tab, escape, enter and .
        if ($.inArray(code, [46, 8, 9, 27, 13, 110]) !== -1 ||
            // Allow: Ctrl+A
            ( e.ctrlKey === true) || ( e.metaKey === true) ||
            // Allow: home, end, left, right, down, up
            (code >= 35 && code <= 40)) {
                // let it happen, don't do anything
                return;
            }
        // Ensure that it is a number and stop the keypress
        if ((e.shiftKey || (code < 48 || code > 57)) && (code < 96 || code > 105)) {
            e.preventDefault();
        }
    });
</script>