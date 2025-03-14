<div id="promotions" class="tab-pane main-content">
    <h1><?=lang("Promotions") ?></h1>
    <!-- ============= iovation blackbox ============= -->
    <input type="hidden" name="ioBlackBox" id="ioBlackBox"/>

    <div class="tab-content" style="border:none">
        <!-- ============= MY PROMO CONTENT ============= -->
        <div role="tabpanel" class="tab-pane  promotions-section" id="mypromo">
            <div class="row">
                <?php
                $cnt = 0;
                if (!empty($mypromo)) {
                    foreach ($mypromo as $key) {
                        if ($key['promoCmsTitle'] != "_SYSTEM_MANUAL") {
                            $cnt++;
                            ?>
                            <div class="col-sm-6">
                                <div class="promotion-content">
                                    <div class="promotion-header">
                                        <h1 class="title-name"><?=ucwords($key['promoCmsTitle']) ?>
                                            <?php if ($key['tag_as_new_flag']) : ?>
                                                <span class="badge-new"><?=lang("lang.new") ?></span>
                                            <?php endif ?>
                                        </h1>
                                        <img width="377" height="199" src="<?= $this->utils->getPromoThumbnailsUrl($key['promoThumbnail'], false) ?>"/>
                                    </div>
                                    <div class="promotion-body clearfix">
                                        <div class="col-xs-8">
                                            <p>
                                                <span><?=lang("Current Betting Amount") ?>:</span> <?= $currency['symbol'] ?> <?php echo $key['currentBet'] ?> <br />
                                                <span><?=lang("Required Betting Amount") ?>:</span> <?= $currency['symbol'] ?> <?php echo $key['withdrawConditionAmount'] ?> <br />
                                                <span><?=lang("Remaining Betting Amount") ?>:</span> <?= $currency['symbol'] ?> <?php echo $key['withdrawConditionAmount'] - $key['currentBet'] ?>
                                            </p>
                                        </div>
                                        <div class="col-xs-4 text-right">
                                            <a href="#" class="btn viewPromoDetailsMyPromoItem" onclick="Promotions.viewMyPromoDetails(this, <?=$playerId?>);" id="<?=$key['promoCmsSettingId'] ?>"  data-toggle="modal" data-target="#promodetails_modal"><?=lang("View Details") ?></a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php }
                        }
                    }
                    if (!$cnt) {
                        echo "<div style='margin:20px'>" . lang("notify.103") . "</div>";
                    }

                    ?>
                </div>
            </div>

        <!-- ============= ALL PROMO CONTENT ============= -->
        <div role="tabpanel" class="tab-pane active promotions-section" id="allpromo">
            <!-- Nav tabs -->
            <ul class="nav nav-pills" role="tablist">
                <?php $ctr=0; ?>
                <?php foreach ($promoCategoryList as $row) : ?>
                    <?php
                        if(!empty($promo_list) || $this->utils->getConfig('enabled_get_allpromo_with_category_via_ajax') ) {
                            $isCategoryHasPromo = false;
                            ?>
                            <?php foreach ($promo_list as $key) : ?>
                                <?php
                                    $class = ($default_show_category_id === $row['id']) ? 'active' : '';
                                    if ($key['promorule']['promoCategory'] == $row['id']) {
                                        if($row['displayPromo'] !== 2){
                                            #PROMO_CATEGORY_HIDE_WHEN_NO_AVAILABLE_PROMO
                                            $isCategoryHasPromo = true;
                                        }
                                    }else{
                                        if($row['displayPromo'] == 1){
                                            #PROMO_CATEGORY_FORCE_SHOW
                                            $isCategoryHasPromo = true;
                                        }else if($row['displayPromo'] == 3){
                                            #PROMO_CATEGORY_VIEW_ALL_SHOW_AVAILABLE_PROMO
                                            $isCategoryHasPromo = true;
                                        }
                                    }
                                ?>
                            <?php endforeach; ?>

                            <?php
                            if ($this->utils->getConfig('enabled_get_allpromo_with_category_via_ajax') ):
                                $isCategoryHasPromo = true;
                                $class = '';
                            endif; // EOF if ($this->utils->getConfig('enabled_get_allpromo_with_category_via_ajax') )... ?>

                            <?php if($isCategoryHasPromo) :  ?>
                                <li role="presentation" class="<?=$class?>">
                                    <a href="#tab<?=$row['id']?>" data-category_id="<?=$row['id']?>" aria-controls="home" role="tab" data-toggle="tab" class="hi-icon-effect-5" aria-expanded="true">
                                        <?php if (!empty($row['icon'])) : ?>
                                            <img src="<?= $this->utils->getPromoCategoryIcon($row['icon']) ?>">
                                        <?php endif; ?>
                                        <span><?=lang($row['name'])?></span>
                                    </a>
                                </li>
                            <?php endif; ?>
                        <?php
                        }
                    ?>
                <?php $ctr++; ?>
                <?php endforeach; ?>
            </ul>

            <!-- Tab panes -->
            <div class="tab-content">
                <div class="loader loader_promotions hide">
                    <div class="loader_vertical_helper display_promotions ">
                        <div class="loader_content">
                            <div class="loader_animation"></div>
                            <div class="loader_text"><?=lang('Loading Data') ?></div>
                        </div>
                    </div> <!-- EOF .display_promotions -->
                </div> <!-- EOF .loader_promotions -->
                <?php $ctr=0; ?>
                <?php foreach ($promoCategoryList as $row) : ?>
                    <?php
                        $class = ($default_show_category_id === $row['id']) ? 'active in' : '';
                    ?>
                    <div role="tabpanel" class="tab-pane <?=$class?>" id="tab<?=$row['id']?>">
                        <div class="row">
                            <?php
                            if (!empty($promo_list)) {
                                $isCategoryHasPromo = false;
                                foreach ($promo_list as $key) {
                                    if (empty($row['id']) || $key['promorule']['promoCategory'] == $row['id']) {
                                        $isCategoryHasPromo = true;
                                        ?>
                                        <div class="col-sm-6">
                                            <div class="promotion-content">
                                                <?php if(!$this->utils->isEnabledFeature('hidden_player_center_promotion_page_title_and_img')) :  ?>
                                                    <div class="promotion-header">
                                                        <h1 class="title-name">
                                                            <?=ucwords($key['promoName']) ?>
                                                            <?php if ($this->utils->getConfig('enabled_multiple_type_tags_in_promotions')) : ?>
                                                                <?php if ($key['tag_as_new_flag'] == '1') : ?>
                                                                    <span class="badge-new multiple-tag-new"><?=lang("lang.new") ?></span>
                                                                <?php elseif ($key['tag_as_new_flag'] == '2')  : ?>
                                                                    <span class="badge-new multiple-tag-favourite"><?=lang("Favourite") ?></span>
                                                                <?php elseif ($key['tag_as_new_flag'] == '3')  : ?>
                                                                    <span class="badge-new multiple-tag-endsoon"><?=lang("End Soon") ?></span>
                                                                <?php endif ?>
                                                            <?php else : ?>
                                                                <?php if ($key['tag_as_new_flag']) : ?>
                                                                    <span class="badge-new"><?=lang("lang.new") ?></span>
                                                                <?php endif ?>
                                                            <?php endif ?>
                                                        </h1>
                                                        <img width="377" height="199" src="<?= $this->utils->getPromoThumbnailsUrl($key['promoThumbnail'], false) ?>"/>
                                                    </div>
                                                <?php endif; ?>
                                                <div class="promotion-body clearfix">
                                                    <div class="col-xs-8">
                                                        <p>
                                                            <?=$key['promoDescription']; ?>
                                                        </p>
                                                    </div>
                                                    <div class="col-xs-4 text-right">
                                                        <?php if(!$this->utils->isEnabledFeature('disabled_show_promo_detail_on_list')) :  ?>
                                                            <a href="javascript: void(0);" class="btn viewPromoDetailsAllPromoItem" id="<?=$key['promoCmsSettingId'] ?>" data-playerid="<?=$playerId?>"><?=lang("View Details") ?></a>
                                                        <?php endif; ?>
                                                        <?php if($this->utils->isEnabledFeature('enabled_request_promo_now_on_list')) :  ?>
                                                            <?php if(!!$key['display_apply_btn_in_promo_page']) :  ?>
                                                                <a href="javascript: void(0);" class="btn requestPromoNowItem"
                                                                    <?php if ($this->utils->getConfig('promo_auto_redirect_to_deposit_page')) : ?>
                                                                        onclick="checkPromo('<?= $key['promoCmsSettingId'] ?>', '<?= $playerId ?>');"
                                                                    <?php else : ?>
                                                                        onclick="requestPromoNow('<?=$key['promoCmsSettingId']?>');"
                                                                    <?php endif; ?>
                                                                >
                                                                 <?=lang('Claim Now');?></a>
                                                             <?php endif; ?>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <?php
                                    }
                                }

                                if (!$isCategoryHasPromo) {
                                    echo '<br/><br/><br/><center><p>' . lang('cat.no.promo') . '</p></center>';
                                }
                            }
                            ?>
                        </div>
                    </div>
                    <?php $ctr++; ?>
                <?php endforeach; ?>

            </div>
        </div>

    </div>
</div>

    <!-- Modal -->
    <div class="modal fade promo-modal" id="promodetails_modal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <img id="promoItemPreviewImg" src="<?=$this->utils->imageUrl($this->utils->getConfig('default_promo_cms_banner_url'))?>"/>
                    <h4 class="modal-title" id="myModalLabel"><span id="promoCmsTitleModal" style="text-transform:uppercase"></span> <span class="badge-new" id="badgeNew"><?=lang("lang.new") ?></span></h4>
                </div> <!-- /.modal-header -->
                <div class="modal-body">
                    <div class="row">
                        <div class="col-xs-12">
                            <div class="promo-countdown hide" >
                                <div class="pr-t-first">
                                    <h3>Waktu yang tersisa</h3>
                                    <div class="pr-time-remaining">
                                        <div class="pr-day">
                                            <div class="pr-t-num">
                                                <span id = "countdown_day"></span>
                                            </div>
                                            <div class="pr-t-label"><?= lang('promo_countdown.Day') ?></div>
                                        </div>
                                        <div class="pr-time">
                                            <div>
                                                <div class="pr-t-num">
                                                    <span id = "countdown_hour"></span>
                                                </div>
                                                <div class="pr-t-label"><?= lang('promo_countdown.Hour') ?></div>
                                            </div><div class="pr-t-separator">
                                                <div class="pr-t-num">:</div>
                                            </div>
                                            <div>
                                                <div class="pr-t-num">
                                                    <span id = "countdown_min"></span>
                                                </div>
                                                <div class="pr-t-label"><?= lang('promo_countdown.Min') ?></div>
                                            </div><div class="pr-t-separator">
                                                <div class="pr-t-num">:</div>
                                            </div>
                                            <div>
                                                <div class="pr-t-num">
                                                    <span id = "countdown_sec"></span>
                                                </div>
                                                <div class="pr-t-label"><?= lang('promo_countdown.Sec') ?></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- <div class="pr-t-last">
                                    <div><h3>Promo Tersisa</h3><div class="pr-t-separator">:</div>
                                    <div class="pr-remaining">300</div></div>
                                </div> -->
                            </div>

                            <?php if(!$this->utils->getConfig('hide_player_center_promo_date_applied')):?>
                                <div id="dateApplied">
                                    <p><span><?=lang("Date Applied") ?>:</span> <p id="dateAppliedTxt"></p></p>
                                </div>
                            <?php endif;?>
                            <?php if(!$this->utils->getConfig('hide_player_center_promo_type')):?>
                                <p><span><?=lang("Promo Type") ?>:</span> <p id="promoCmsPromoTypeModal"></p></p>
                            <?php endif;?>
                            <?php if($this->utils->getConfig('enabled_promorules_remaining_available')):?>
                                <div id="remainingAvailable" class="hide">
                                    <p><span><?=lang("promorules.total_approved_limit") ?>:</span> <p id="remainingAvailableTxt"></p></p>
                                </div>
                            <?php endif;?>
                            <!-- enabled_promo_period_countdown -->
                            <div id="promo_period_countdown" class="hide">
                                <p><span><?=lang("promo_countdown.Remaining") ?>:</span> <span id="promo_period_countdown_txt"></span></p>
                            </div>
                            <h4><?=lang("promo.description") ?></h4>
                            <p id="promoCmsPromoDetailsModal"></p>
                        </div>
                    </div>
                </div> <!-- /.modal-body -->
                <div class="modal-footer">
                    <div class="row" id="promoMsgSec">
                        <center>
                            <p id="promoMsg" style="color: #222;"></p>
                        </center>
                    </div>
                    <div class="row" id="informRow" class="hide">
                        <center>
                            <p id="informMsg"></p>
                        </center>
                    </div>
                    <div class="applyBtn">
                        <input type="hidden" id="itemDetailsId">
                        <a href="javascript:void(0)" onclick="requestPromoNow();" class="requestPromoBtn btn btn-default submit-btn">
                            <?=lang('Claim Now');?>
                            <span class="custom-mesg"></span>
                        </a>
                    </div>
                    <div class="claimLinkBtn">
                        <a href="javascript:void(0)" class="btn btn-default claim-link-btn"><?=lang('Claim Now');?></a>
                    </div>
                    <div class="apply-mesg" style="text-align: center; font-weight: bold; color: #222; display: none;">
                    </div>
                    <div class="reject-mesg" style="text-align: center; font-weight: bold; color: #222; display: none;">
                    </div>
                    <!-- <div class="reject-mesg-2" style="text-align: center; font-weight: bold; color: #222; display: none;">
                    </div> -->
                    <button type="button" class="btn btn-default submit-btn" id="closeModal" data-dismiss="modal" data-stdmesg="<?= lang('Close') ?>"><?=lang('Close');?></button>
                </div><!-- /.modal-footer -->
            </div><!-- /.modal-content -->
        </div><!-- /.modal-dialog -->
    </div><!-- /#promodetails_modal -->


<script type="text/javascript">

function checkPromo(promoCmsSettingId, player_id) {
    // self.scriptShowLoading();
    show_loading();
    var is_mobile = $('.ismobile').length > 0;

    var ajax = $.ajax({
        'url' : "/player_center/getPromoCmsItemDetailsByPlayerId/promojoint/" + player_id + "/" + promoCmsSettingId,
        'type' : 'GET',
        'dataType' : "json",
        'success' : function (data) {
            var fpromo = data.promo_list[0];

            if (fpromo.player_promo != undefined ) {
                bonusAmount = fpromo.player_promo.bonusAmount;
            }
            else if(fpromo.promorule.bonusAmount == 0){
                var depositPercentage = fpromo.promorule.depositPercentage;
                var nonfixedDepositMinAmount = fpromo.promorule.nonfixedDepositMinAmount;
                bonusAmount = nonfixedDepositMinAmount * (depositPercentage/100);
            }else{
                bonusAmount = fpromo.promorule.bonusAmount;
            }

            if (parseInt(fpromo.allow_claim_promo_in_promo_page) > 0 && fpromo.player_allowed_for_promo == false && typeof(fpromo.redirect_to_deposit) != 'undefined') {
                var go_deposit = confirm(fpromo.redirect_to_deposit.mesg);
                if (go_deposit) {
                    window.location.href = fpromo.redirect_to_deposit.url;
                }
                return;
            }
            requestPromoNow(promoCmsSettingId);
        }
    })
    .always(function() {
        stop_loading();
    });
}

function requestPromoNow(promoCmsSettingId){

    var use_confirm = "<?= $this->utils->getConfig('use_confirm_on_get_promo') ?>";

    if (use_confirm == true || use_confirm == 'true') {
        MessageBox.confirm("<?=lang("confirm.promo") ?>", '',
        function(){
            processPromo(promoCmsSettingId);
        }, function(){
            return false;
        });
    } else {
        processPromo(promoCmsSettingId);
    }

}// EOF requestPromoNow

function processPromo(promoCmsSettingId){
    var promoCmsSettingId = ( typeof(promoCmsSettingId) === 'undefined') ? $("#itemDetailsId").val() : promoCmsSettingId;
    var custom_promo_sucess_msg = JSON.parse('<?=json_encode($this->utils->getConfig('custom_promo_sucess_msg'))?>');
    var enabled_fixed_promo_err_msg = "<?= $this->utils->getConfig('enabled_fixed_promo_err_msg') ? '1' : '0' ?>";

    // register to iovation when joined promotion
    var params = {};
    params.ioBlackBox = $("#ioBlackBox").val();
    Promotions.requestPromoNow(promoCmsSettingId, params, function(data){
        if( data.status === 'success' ){
            if (custom_promo_sucess_msg && (typeof custom_promo_sucess_msg[promoCmsSettingId] != 'undefined' )) {
                data.msg = lang(custom_promo_sucess_msg[promoCmsSettingId]);
            }
            // TEST CASE, 從列表中申請憂患，會重新整理。
            MessageBox.success(data.msg, null, function(){
                if( ! Promotions.embedMode ){
                    show_loading();
                    window.location.reload(true);
                }
            }, undefined, function(e){ // shownCB
                Promotions.scriptMessageBoxShownCB(e);
            });
        } else {
            if (enabled_fixed_promo_err_msg == '1') {
                console.log('original_err_msg:',data.msg);
                data.msg = "<?= lang('enabled_fixed_promo_err_msg') ?>";
            }
            // TEST CASE, 從列表中申請優惠失敗，會重新整理。
            MessageBox.danger(data.msg, null, function(){
                /// another promo, not preload.
                // Promotions.viewPromoDetailWithPreloadPromo(); // @todo doing
            }, undefined, function(e){ // shownCB
                Promotions.scriptMessageBoxShownCB(e);
            });
        }
    });
}

function processPromo4progressionBtn(promoCmsSettingId){
    var promoCmsSettingId = ( typeof(promoCmsSettingId) === 'undefined') ? $("#progression-itemDetailsId").val() : promoCmsSettingId;
    var enabled_progression_btn = JSON.parse('<?=json_encode($this->utils->getConfig('enabled_progression_btn'))?>');
    var params = {};
    if (enabled_progression_btn && (typeof enabled_progression_btn[promoCmsSettingId] != 'undefined' ) ) {
        var progression_val = $('#progression-itemDetailsId').val();
        if (progression_val != '') {
            params.is_dryurn = progression_val;
        }
    }
    Promotions.requestPromoNow(promoCmsSettingId, params, function(data){
        MessageBox.success(data.msg, null, function(){
            $('.applyBtn').show();
        }, undefined, function(e){ // shownCB
            Promotions.scriptMessageBoxShownCB(e);
        });
    });
}

function createProgressionBtn(category_id, cms_id){
    $('.progressionBtn').remove();
    var progressionBtn ='<div class="progressionBtn hide">' +
                            '<input type="hidden" id="progression-itemDetailsId" value="' + cms_id +'">' +
                            '<a href="javascript:void(0)" id="progressionBtn" class="requestPromoBtn btn btn-default submit-btn" onclick="processPromo4progressionBtn('+cms_id+')"><?=lang('Check progression');?></a>' +
                        '</div>';

    $('.applyBtn').after(progressionBtn);
}

$(function(){

    /** initialize default */
    Promotions.currency_symbol = "<?= $this->utils->getCurrentCurrency()['symbol'] ?>";

    var enable_sms_verified_phone_in_promotion = "<?= $this->utils->getConfig('enable_sms_verified_phone_in_promotion') ? '1' : '0' ?>";
    var player_verified_phone = "<?= $player_verified_phone ? '1' : '0' ?>";
    if (enable_sms_verified_phone_in_promotion == '1') {
        if (player_verified_phone == '0') {
            MessageBox.danger("<?=lang('promo.msg3')?>", undefined, function(){
                show_loading();
                window.location.href = '/player_center2/security#promotion';
            },
            [
                {
                    'text': '<?=lang('lang.settings')?>',
                    'attr':{
                        'class':'btn btn-primary',
                        'data-dismiss':"modal"
                    }
                }
            ]);
        }
    }
});

const viewPromoDetailId = parseInt('<?= isset($promoCmsSettingId)?$promoCmsSettingId:0 ?>');
$(document).ready(function() {
    <?php if( ! empty($preloadPromoJson) ): ?>
    Promotions.preloadPromo = <?=$preloadPromoJson?>;
    <?php endif; ?>
    <?php if( ! empty($preloadPromoRespJoined) ): ?>
    Promotions.preloadPromoRespJoined = <?=$preloadPromoRespJoined?>;
    <?php endif; ?>
    <?php if( ! empty($lastAlertMessageJson) ): ?>
    Promotions.lastAlertMessageJson = <?=$lastAlertMessageJson?>;
    <?php endif; ?>

    Promotions.playerId = <?= $playerId ?>;
    Promotions.is_mobile = <?= empty($this->utils->is_mobile())? 'false': 'true' ?>;
    Promotions.enabled_get_allpromo_with_category_via_ajax = <?=empty( $this->utils->getConfig('enabled_get_allpromo_with_category_via_ajax') )? 'false': 'true' ?>;
    Promotions.hidden_player_center_promotion_page_title_and_img = <?=empty( $this->utils->isEnabledFeature('hidden_player_center_promotion_page_title_and_img') )? 'false': 'true' ?>;
    Promotions.enabled_multiple_type_tags_in_promotions = <?=empty( $this->utils->getConfig('enabled_multiple_type_tags_in_promotions') )? 'false': 'true' ?>;
    Promotions.disabled_show_promo_detail_on_list = <?=empty( $this->utils->isEnabledFeature('disabled_show_promo_detail_on_list') )? 'false': 'true' ?>;
    Promotions.enabled_request_promo_now_on_list = <?=empty( $this->utils->isEnabledFeature('enabled_request_promo_now_on_list') )? 'false': 'true' ?>;
    Promotions.promo_auto_redirect_to_deposit_page = <?=empty( $this->utils->getConfig('promo_auto_redirect_to_deposit_page') )? 'false': 'true' ?>;
    // langs
    Promotions.langs['lang.new'] = "<?=lang('lang.new')?>";
    Promotions.langs['Favourite'] = "<?=lang('Favourite')?>";
    Promotions.langs['End Soon'] = "<?=lang('End Soon')?>";
    Promotions.langs['Claim Now'] = "<?=lang('Claim Now')?>";
    Promotions.langs['cat.no.promo'] = "<?=lang('cat.no.promo')?>";
    Promotions.langs['View Details'] = "<?=lang('View Details')?>";
    Promotions.langs['lang.norec'] = "<?=lang('lang.norec')?>";
    Promotions.langs['lang.details'] = "<?=lang('lang.details')?>";
    Promotions.onReady();

    if(!!viewPromoDetailId) {
        $('a#'+viewPromoDetailId+'.viewPromoDetailsAllPromoItem').first().trigger('click');
    }
});
</script>
<style>

.loader_promotions {
	padding: 44px;
	background-color: rgb(10 10 10 / 60%);
}
.loader_promotions .display_promotions {
	display: block;
}
.promo-countdown {
        margin-bottom: 20px;
    }
    .promo-countdown h3 {
        font-size: 15px;
        text-transform: none;
    }
    .pr-time-remaining {
        display: flex;
        gap: 5px;
    }
    .pr-time-remaining .pr-day {
        display: flex;
        background: #f7b132;
        color: #ffffff;
        border-radius: 5px;
        padding: 3px 5px;
        align-items: center;
    }
    .pr-time > div {
        display: flex;
        padding: 3px 10px;
        align-items: center;
    }
    .pr-time {
        display: flex;
        background: linear-gradient(90deg, #f7b733, #fc4a1a) !important;
        color: #ffffff;
        border-radius: 5px;
    }
    .pr-t-label {
        margin-left: 3px;
        font-size: 10px;
    }
    .pr-t-num {
        font-size: 18px;
        font-weight: bold;
    }
    .pr-t-first {
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 10px;
    }
    .pr-t-last > div {
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(90deg, #265de7, #112e8c) !important;
        border-radius: 3px;
        color: #ffffff;
    }
    .pr-remaining {
        font-size: 18px;
        border-radius: 3px;
        font-weight: bold;
    }
    .pr-t-last {
        display: flex;
        justify-content: center;
    }
    .pr-t-last > div h3 {
        font-weight: normal;
        margin: 0;
    }
    .pr-t-first h3 {
        margin-top: 0;
        margin-right: 10px;
        margin-bottom: 0;
        font-weight: normal;
    }
    .pr-t-last > div > * {
        padding: 3px 10px;
    }
    .pr-t-last > div > .pr-t-separator {
        padding: 3px;
    }
    .pr-time > div.pr-t-separator {
        padding: 3px 5px;
    }
</style>