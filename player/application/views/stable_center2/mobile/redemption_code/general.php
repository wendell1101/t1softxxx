<div class="redemption_code" class="panel">
    <div class="panel-body redemption_code_content">
        <div id="redemption_code_content_1"></div>
        <div class="form-group">
            <form name="redemptionCodeForm" role="form" method="POST">
                <div class="row input_main">
                    <div class="form-group col-md-6 nopadding">
                        <label for="redemption_code_input" class="control-label"><?= lang('redemptionCode.redemptionCode') ?>:</label>
                        <input type="text" class="form-control" id="redemption_code_input" name="redemption_code"></span>
                    </div>
                </div>
                <hr>
                <div class="row">
                    <button type="button" class="btn btn-primary" id="redemption_code_submit"><?= lang('redemptionCode.apply') ?></button>
                </div>
            </form>
        </div>
        <div id="redemption_code_content_2"></div>
    </div>
</div>

<script type="text/javascript">
    var promoCmsSettingId = '<?= $this->utils->getConfig('redemption_code_promo_cms_id') ?>';
    $(document).ready(function() {
        $('#redemption_code_submit').on('click', function() {
            let redemption_code_input = $("#redemption_code_input").val();
            if (redemption_code_input.trim() == '') {
                MessageBox.danger('<?= lang("redemptionCode.codeEmptyAlert") ?>', null, function() {}, undefined, function(e) { // shownCB
                    Promotions.scriptMessageBoxShownCB(e);
                });
            } else {

                processPromo(promoCmsSettingId);
            }
        });
    });

    function requestPromoNow(promoCmsSettingId) {

        var use_confirm = "<?= $this->utils->getConfig('use_confirm_on_get_promo') ?>";

        if (use_confirm == true || use_confirm == 'true') {
            MessageBox.confirm("<?= lang("confirm.promo") ?>", '',
                function() {
                    processPromo(promoCmsSettingId);
                },
                function() {
                    return false;
                });
        } else {
            processPromo(promoCmsSettingId);
        }

    } // EOF requestPromoNow
    function processPromo(promoCmsSettingId) {
        var promoCmsSettingId = (typeof(promoCmsSettingId) === 'undefined') ? $("#itemDetailsId").val() : promoCmsSettingId;
        var custom_promo_sucess_msg = JSON.parse('<?= json_encode($this->utils->getConfig('custom_promo_sucess_msg')) ?>');
        // register to iovation when joined promotion
        var params = {};
        // params.ioBlackBox = $("#ioBlackBox").val();
        params.redemption_code = $("#redemption_code_input").val();
        Promotions.requestRedemptionCode(promoCmsSettingId, params, function(data) {
            if (data.status === 'success') {
                if (custom_promo_sucess_msg && (typeof custom_promo_sucess_msg[promoCmsSettingId] != 'undefined')) {
                    data.msg = lang(custom_promo_sucess_msg[promoCmsSettingId]);
                }
                // TEST CASE, 從列表中申請憂患，會重新整理。
                MessageBox.success(data.msg, null, function() {
                    if (!Promotions.embedMode) {
                        show_loading();
                        window.location.reload(true);
                    }
                }, undefined, function(e) { // shownCB
                    Promotions.scriptMessageBoxShownCB(e);
                });
            } else {
                // TEST CASE, 從列表中申請優惠失敗，會重新整理。
                MessageBox.danger(data.msg, null, function() {}, undefined, function(e) { // shownCB
                    Promotions.scriptMessageBoxShownCB(e);
                });
            }
        });
    }
</script>