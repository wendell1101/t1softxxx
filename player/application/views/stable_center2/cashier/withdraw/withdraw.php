<?php
    $isBank = ('bank' == $type);
    $isAlipay = ('alipay' == $type);
    $isWechat = ('wechat' == $type);

    $withdrawal_page = $this->config->item('enabled_withdrawal_page');
    $withdrawal_custom_page = $this->config->item('enabled_withdrawal_custom_page');

    // default custom both
    $enabled_default = !empty($withdrawal_page) ? $withdrawal_page : false;
    $enabled_custom = !empty($withdrawal_custom_page) ? array_keys($withdrawal_custom_page)[0] : false;

    $custom_params = !empty($enabled_custom) ? reset($withdrawal_custom_page) : false;
    $enabled_custom_player_page = !empty($custom_params['enabled_player_page']) ? $custom_params['enabled_player_page'] : false;
    $enabled_custom_player_mobile_page = !empty($custom_params['enabled_player_mobile_page']) ? $custom_params['enabled_player_mobile_page'] : false;

    $is_mobile = $this->utils->is_mobile();
    $enabled_both =  $enabled_default && $enabled_custom ? true : false;
?>
<?php if (!empty($result)) : ?>
<div id="messageDialog" class="alert alert-<?= $result['success']? 'success':'danger'?> alert-dismissable">
    <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
    <?= $result['message'] ?>
</div>
<script>
$(function(){
    $('html, body').animate({
        scrollTop: $("#messageDialog").offset().top
    }, 1000);
});
</script>
<?php endif; ?>

<script type="text/javascript">
var EMPTY_ACCOUNT_NAME_REDIRECT_URL = '<?=(!$this->utils->is_mobile()) ? '/player_center/dashboard/index#accountInformation' : '/player_center/profile'?>';
$(function(){
    var allowed_withdrawal_status_kyc_risk_score = "<?= $allowed_withdrawal_status_kyc_risk_score ?>";

    if(allowed_withdrawal_status_kyc_risk_score != 1) {
        MessageBox.info("<?= lang('not_allowed_kyc_risk_score_message') ?>", null, function(){
            $('#submitBtn').attr({'disabled':true});
        });
    }

    _withdraw.isAutoTransferAll = <?= ($this->utils->isAllowedAutoTransferOnFeature()) ? 1 : 0 ?>;

    <?php if( $enabled_withdrawal_password && !$has_withdraw_password): ?>
    MessageBox.info("<?=lang('withdrawal.msg3')?>", '<?=lang('lang.info')?>', function(){
        show_loading();
        window.location.href = '/player_center2/security#withdrawal';
    },
    [
        {
            'text': '<?=lang('lang.close')?>',
            'attr':{
                'class':'btn btn-info',
                'data-dismiss':"modal"
            }
        }
    ]);
    <?php endif ?>

    $("#withdrawal_list_tab_nav a:first").tab("show");
});
</script>

<style type="text/css">
    .withdrawal-customize-content{
        color:#ff0000;
    }
    .withdrawal_note{
        color:#ff0000;
        font-size: 16px;
        display: inline
    }
    .fee_hint_table{
        font-size: 12px;
    }
</style>

<?php switch(TRUE) :
case $enabled_both: ?>
    <div id="bank_account" class="panel">
        <div class="panel-heading">
            <h1 class="hidden-xs hidden-sm"><?= lang('cashier.16') ?></h1>
        </div>
        <div class="panel-body withdrawal-list sub_content">
            <ul id="withdrawal_list_tab_nav" class="nav nav-tabs nav-justified fm-ul">
                <li><a href="#withdrawal_page" class="withdrawal_page" data-toggle="tab"><?=lang('withdrawal_page.default')?></a></li>
                <li><a href="#withdrawal_custom_page" class="withdrawal_custom_page" data-toggle="tab"><?=lang('withdrawal_page.custom')?></a></li>
            </ul>
            <div class="withdrawal_list_page_tab_content tab-content fm-content">
                <div id="withdrawal_page" class="tab-pane fade in">
                    <?php include __DIR__ . '/withdrawal_page.php'; ?>
                </div>
                <div id="withdrawal_custom_page" class="tab-pane fade">
                    <?php include __DIR__ . '/custom/'. $enabled_custom .'/withdrawal_page.php'; ?>
                </div>
            </div>
        </div>
    </div>
<? break;
case $enabled_custom: ?>
    <div class="withdrawal_list_page_tab_content tab-content ">
        <?php if(!empty($custom_params)):?>
            <?php if($enabled_custom_player_page && !$is_mobile):?>
                <div id="withdrawal_custom_page" class="tab-pane fade in active">
                    <?php include __DIR__ . '/custom/'. $enabled_custom .'/player/withdrawal_page.php'; ?>
                </div>
            <?php elseif ($enabled_custom_player_mobile_page && $is_mobile):?>
                <div id="withdrawal_custom_page" class="tab-pane fade in active">
                    <?php include __DIR__ . '/custom/'. $enabled_custom .'/mobile/withdrawal_page.php'; ?>
                </div>
            <?php else:?>
                <div id="withdrawal_page" class="tab-pane fade in active">
                    <?php include __DIR__ . '/withdrawal_page.php'; ?>
                </div>
            <?php endif;?>
        <?php endif;?>
    </div>
<? break;
default: ?>
    <div class="withdrawal_list_page_tab_content tab-content ">
        <div id="withdrawal_page" class="tab-pane fade in active">
            <?php include __DIR__ . '/withdrawal_page.php'; ?>
        </div>
    </div>
<?php endswitch ?>

<?php include __DIR__ . '/../../bank_account/content/modal.php';?>