<script>
var modalHtml =
    '<div class="modal fade" id="sms-verify-modal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">' +
    '	<div class="modal-dialog modal-sm" role="document">' +
    '		<form id="smsVerifyForm">' +
    '			<div class="modal-content">' +
    '				<div class="modal-header">' +
    '					<span class="modal-title" id="exampleModalLabel"><?php echo lang('Please validate sms captcha') ?></span>' +
    '					<button type="button" class="close pull-right" data-dismiss="modal" aria-label="Close">' +
    '						<span aria-hidden="true">&times;</span>' +
    '					</button>' +
    '				</div>' +
    '				<div class="modal-body">' +
    '					<div class="row">' +
    '						<div class="col-md-12">' +
    '							<div class="form-group form-inline">' +
    '								<input id="sms_captcha" name="sms_captcha" type="text" class="form-control registration-field fcrecaptcha" placeholder="<?php echo lang('label.captcha'); ?>" style="width: 60%" required oninvalid="this.setCustomValidity(\'<?=lang('default_html5_required_error_message')?>\')" oninput="setCustomValidity(\'\')" >' +
    '								<img id="image_sms_captcha" src="<?php echo site_url("/iframe/auth/smsCaptcha/120/40?" . random_string('alnum')); ?>" onclick="refreshSMSCaptcha();" />' +
    '							</div>' +
    '						</div>' +
    '					</div>' +
    '				</div>' +
    '				<div class="modal-footer">' +
    ' 					<button type="submit" class="btn btn-primary"> <?php echo lang('Confirm submit') ?> </button>' +
    '				</div>' +
    '			</div>' +
    '		</form>' +
    '	</div>' +
    '</div>';

function refreshSMSCaptcha(){
	$('#image_sms_captcha').attr('src','<?php echo site_url('/iframe/auth/smsCaptcha/120/40'); ?>?'+ Math.random());
}

function SMS_SendVerify(callback) {
    var disabled_sms_capcha = '<?= $this->utils->isEnabledFeature('disable_captcha_before_sms_send') ? true : false ?>';
    if(disabled_sms_capcha){
        console.log('disable',disabled_sms_capcha);
        $("#sms_captcha").val("");
        $("#sms-verify-modal").modal('hide');
        callback($("#sms_captcha").val());
        return;
    }
    // Init sms-verify-model
    if ($("#sms-verify-modal").length <= 0) {
        $("body").append(modalHtml);
    } else {
        $("#sms_captcha").val("");
    }

    refreshSMSCaptcha();

    $("#sms-verify-modal").modal();

    $("#smsVerifyForm").off('submit').on('submit', function() {
        _submitEvent.submitForm(callback);
        $("#sms-verify-modal").modal('hide');
    });
}

// submitEvent
var _submitEvent = new function() {
    this.submitForm = function(callback) {
        event.preventDefault();
        callback($("#sms_captcha").val());
    }
};

</script>

