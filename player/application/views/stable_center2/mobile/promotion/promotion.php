<div class="dropdown select-promo-category-dropdown">
    <label for="select_promo_category_toggle"><?=lang('cms.selectPromoCat')?>: </label>
    <button class="btn btn-primary dropdown-toggle" type="button" id="select_promo_category_toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
        <span><?=lang('cms.selectPromoCat')?></span>
        <span class="caret"></span>
    </button>

    <ul class="dropdown-menu" aria-labelledby="select_promo_category_toggle">
        <li role="presentation" value="" disabled="disabled"><a href="javascript: void(0);" role="menuitem"><?=lang('cms.selectPromoCat')?></a></li>
        <?php foreach ($promoCategoryList as $promo_data) : ?>
            <?php
                $isCategoryHasPromo = false;
                if(!empty($promo_list)) {
                    $isCategoryHasPromo = false;
                    ?>
                    <?php foreach ($promo_list as $promo_item) : ?>
                        <?php
                            $class = ($default_show_category_id === $promo_data['id']) ? 'active' : '';
                            if ($promo_item['promorule']['promoCategory'] == $promo_data['id']) {
                                if($promo_data['displayPromo'] !== 2){
                                    $isCategoryHasPromo = true;
                                }
                            }else{
                                if($promo_data['displayPromo'] == 1){
                                    $isCategoryHasPromo = true;
                                } elseif ($promo_data['displayPromo'] == 3) {
                                    #PROMO_CATEGORY_VIEW_ALL_SHOW_AVAILABLE_PROMO
                                    $isCategoryHasPromo = true;
                                }
                            }
                        ?>
                    <?php endforeach; // EOF foreach ($promo_list as $promo_item):... ?>

                <?php
                }else if ($this->utils->getConfig('enabled_get_allpromo_with_category_via_ajax') ){
                    $isCategoryHasPromo = true;
                } // EOF if(!empty($promo_list)) {...
            ?>
            <?php if($isCategoryHasPromo) :  ?>
                <li role="presentation" value="<?=$promo_data['id']?>"><a href="javascript: void(0);" role="menuitem"><?=lang($promo_data['name'])?></a></li>
            <?php endif; // EOF if($isCategoryHasPromo) :... ?>
        <?php endforeach; ?>
    </ul>
</div>
<div class="clearfix"></div>
<div class="promotions">
    <input type="hidden" name="ioBlackBox" id="ioBlackBox"/>

    <div class="loader loader_promotions hide">
        <div class="loader_vertical_helper display_promotions">
            <div class="loader_content">
                <div class="loader_animation"></div>
                <div class="loader_text"><?=lang('Loading Data')?></div>
            </div>
        </div> <!-- EOF .display_promotions -->
    </div> <!-- EOF .loader_promotions -->

    <!-- Main Content -->
    <?php foreach ($promoCategoryList as $row) : ?>
    <div id="promo_category_<?=$row['id']?>" class="promotions-category-list hide" data-promo_category_id="<?=$row['id']?>">
        <?php
        $promo_item_count = 0;
        foreach($promo_list as $promo_item){
            if($row['id'] !== 0 && $row['id'] != $promo_item['promorule']['promoCategory']){
                continue;
            }
            $promo_item_count++;
        ?>
        <div class="pr_show threepage cpt5" data-promo_item_anchor="promo_item_<?=$promo_item["promoCmsSettingId"]?>">
            <?php if(!$this->utils->isEnabledFeature('hidden_player_center_promotion_page_title_and_img')) :  ?>
            <div class="primage">
                <?php
                if(file_exists($this->utils->getPromoThumbnails() . $promo_item['promoThumbnail']) && !empty($promo_item['promoThumbnail'])){
                    $promoThumbnail = $this->utils->getPromoThumbnailRelativePath(FALSE) . $promo_item['promoThumbnail'];
                }else{
                    if(!empty($promo_item['promoThumbnail'])){
                        $promoThumbnail = $this->utils->imageUrl('promothumbnails/' . $promo_item['promoThumbnail']);
                    }else{
                        $promoThumbnail = $this->utils->imageUrl('promothumbnails/default_promo_cms_1.jpg');
                    }
                }
                ?>
                <img src="<?=$promoThumbnail;?>?v=<?=PRODUCTION_VERSION?>">
            </div>
            <div class="title">
                【<?php echo $promo_item['promoName'] ?>】
            </div>
            <?php endif; ?>
            <div class="description"><?php echo $promo_item['promoDescription'] ?></div>
            <div class="actions">
                <?php if(!$this->utils->isEnabledFeature('disabled_show_promo_detail_on_list')) :  ?>
                    <a href="javascript: void(0);" class="btn btn-sm btn-info btn-details-<?= $promo_item['promoCmsSettingId'] ?>" data-toggle="collapse"
                    onclick="Promotions.viewPromoDetailsAll(this, <?=$playerId?>);" id="<?=$promo_item['promoCmsSettingId'] ?>"
                    data-target="#promo_<?=$row['id']?>_item_<?=$promo_item["promoCmsSettingId"]?>_detail"><?=lang('lang.details')?></a>
                <?php endif; ?>
                <?php if($this->utils->isEnabledFeature('enabled_request_promo_now_on_list')) :  ?>
                    <?php if(!!$promo_item['display_apply_btn_in_promo_page']) :  ?>
                        <?php if($promo_item['allow_claim_promo_in_promo_page']): ?>
                            <?php if (!$this->utils->getConfig('promo_auto_redirect_to_deposit_page')) : ?>
                                <a href="javascript: void(0);" class="btn btn-sm btn-info " data-promo-cms-setting-id="<?=$promo_item['promoCmsSettingId']?>" onclick="requestPromoNow('<?=$promo_item['promoCmsSettingId']?>');"><?=lang('Claim Now');?></a>
                            <?php else : ?>
                                <a href="javascript: void(0);" class="btn btn-sm btn-info " data-promo-cms-setting-id="<?=$promo_item['promoCmsSettingId']?>" onclick="checkPromo('<?= $promo_item['promoCmsSettingId'] ?>', '<?= $playerId ?>');"><?=lang('Claim Now');?></a>
                            <?php endif; ?>
                        <?php else: ?>
                            <?php if (!$this->utils->getConfig('promo_auto_redirect_to_deposit_page')) : ?>
                                <a href="<?=$promo_item['claim_button_url']?>" class="btn btn-sm btn-info "><?= !empty($promo_item['claim_button_name']) ? $promo_item['claim_button_name'] : lang('Claim Now')?></a>
                            <?php else : ?>
                                <a href="javascript: void(0);" class="btn btn-sm btn-info " data-promo-cms-setting-id="<?=$promo_item['promoCmsSettingId']?>" onclick="checkPromo('<?= $promo_item['promoCmsSettingId'] ?>', '<?= $playerId ?>', '<?= $promo_item['claim_button_url'] ?>');"><?=lang('Claim Now');?></a>
                            <?php endif; ?>
                        <?php endif ?>
                    <?php endif ?>
                <?php endif; ?>
            </div>
        </div>
        <div id="promo_<?=$row['id']?>_item_<?=$promo_item["promoCmsSettingId"]?>_detail" class="collapse">
            <div class="panel-body">
                <!-- enabled_promo_period_countdown -->
                <div class="promo_period_countdown hide">
                    <p><span><?=lang("promo_countdown.Remaining") ?>:</span> <span class="promo_period_countdown_txt"></span></p>
                </div>
                <div class="lestCo">
                    <?=html_entity_decode($promo_item['promoDetails'], ENT_QUOTES, 'UTF-8')?>
                </div>
                <div class="action">
                    <?php if($promo_item['allow_claim_promo_in_promo_page']): ?>
                        <?php if (!$this->utils->getConfig('promo_auto_redirect_to_deposit_page')) : ?>
                            <a href="<?=site_url("iframe_module/request_promo/" . @$promo_item['promoCmsSettingId']);?>" data-promo-cms-setting-id="<?=$promo_item['promoCmsSettingId']?>" class="btn btn-sm btn-info claim-promo btn-claim-<?= $promo_item['promoCmsSettingId'] ?>"><?=lang('Claim Now');?></a>
                        <?php else : ?>
                            <a href="javascript: void(0);" class="btn btn-sm btn-info " data-promo-cms-setting-id="<?=$promo_item['promoCmsSettingId']?>" onclick="checkPromo('<?= $promo_item['promoCmsSettingId'] ?>', '<?= $playerId ?>');"><?=lang('Claim Now');?></a>
                        <?php endif; ?>
                    <?php else: ?>
                        <?php if (!$this->utils->getConfig('promo_auto_redirect_to_deposit_page')) : ?>
                        <a href="<?=$promo_item['claim_button_url']?>" data-promo-cms-setting-id="<?=$promo_item['promoCmsSettingId']?>" class="btn btn-sm btn-info btn-claim-<?= $promo_item['promoCmsSettingId'] ?>"><?=!empty($promo_item['claim_button_name']) ? $promo_item['claim_button_name'] : lang('Claim Now')?></a>
                        <?php else : ?>
                            <a href="javascript: void(0);" class="btn btn-sm btn-info " data-promo-cms-setting-id="<?=$promo_item['promoCmsSettingId']?>" onclick="checkPromo('<?= $promo_item['promoCmsSettingId'] ?>', '<?= $playerId ?>', '<?= $promo_item['claim_button_url'] ?>');"><?=lang('Claim Now');?></a>
                        <?php endif; ?>
                    <?php endif ?>
                </div>
            </div> <!-- EOF .panel-body -->
        </div> <!-- EOF #promo_<?=$row['id']?>_item_<?=$promo_item["promoCmsSettingId"]?>_detail -->
        <?php } // EOF foreach($promo_list as $promo_item){... ?>

        <?php if ($this->utils->getConfig('enabled_get_allpromo_with_category_via_ajax') ):
            // load data from ajax
        elseif($promo_item_count === 0): ?>
        <div class="no_data">
            <center><?=lang('lang.norec')?></center>
        </div>
        <?php endif; // EOF if ($this->utils->getConfig('enabled_get_allpromo_with_category_via_ajax') ):... ?>
    </div>
    <?php endforeach; // EOF foreach ($promoCategoryList as $row) :... ?>

    <?php if($this->utils->isEnabledFeature('enable_mobile_copyright_footer')): ?>
        <?=$this->load->view($this->utils->getPlayerCenterTemplate(FALSE) . '/mobile/includes/template_footer');?>
    <?php endif; ?>
</div>
<script>
var promotion_category_id = <?=$default_show_category_id?>;

function checkPromo(promoCmsSettingId, player_id, claim_url) {
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
            if (claim_url == undefined) {
                requestPromoNow(promoCmsSettingId);
            }
            else {
                window.location.href = claim_url;
            }
        }
    })
    .always(function() {
        stop_loading();
    });
}

// function requestPromoNow(promoCmsSettingId){
//     var params = {};
//     params.ioBlackBox = $("#ioBlackBox").val();
//     Promotions.requestPromoNow(promoCmsSettingId, params, function(data){
//         if(data.status === 'success'){
//             MessageBox.success(data.msg, null, function(){
//                 show_loading();
//                 window.location.reload(true);
//             });
//         } else {
//             MessageBox.danger(data.msg);
//         }
//     });
// }

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
            $('.claim-promo').show();
        }, undefined, function(e){ // shownCB
            Promotions.scriptMessageBoxShownCB(e);
        });
    });
}

function createProgressionBtn(category_id, cms_id){
    $('#promo_'+category_id+'_item_'+cms_id+'_detail .progressionBtn').remove();
    var progressionBtn ='<div class="progressionBtn hide">' +
                            '<input type="hidden" id="progression-itemDetailsId" value="' + cms_id +'">' +
                            '<a href="javascript:void(0)" id="progressionBtn" class="requestPromoBtn btn btn-default submit-btn" onclick="processPromo4progressionBtn('+cms_id+')"><?=lang('Check progression');?></a>' +
                        '</div>';

    $('#promo_'+category_id+'_item_'+cms_id+'_detail .claim-promo').after(progressionBtn);
}
const viewPromoDetailId = parseInt('<?= isset($promoCmsSettingId)?$promoCmsSettingId:0 ?>');
const currentPromoCategory = parseInt('<?= isset($currentPromoCategory)?$currentPromoCategory:0 ?>');

$(document).ready(function(){
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
    Promotions.onReadyMobi();
});

$(document).ready(function(){
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

    $(".select-promo-category-dropdown .dropdown-menu li").on("click", function(){
        if($(this).attr('disabled')){
            return false;
        }

        var str = $(this).text();
        var promotion_category_id = $(this).attr('value');

        $(this).parent().parent().parent().find(".dropdown-toggle span:first").html(str);
        $('#jltext').html(str);
        // $('.header_title_text').html(str);

        $('.promotions-category-list').addClass('hide');
        $('#promo_category_' + promotion_category_id).removeClass('hide');
    });

    // auto redirect to anchor
    var promoAnchorPrefix = window.location.hash.substr(1);
    var anchor_promo = (promoAnchorPrefix.indexOf("promo_item_") !== -1) ? $(".promotions div[data-promo_item_anchor=" + promoAnchorPrefix + "]") : false;
    if(anchor_promo != false && anchor_promo.length > 0){
        promotion_category_id = anchor_promo.parent().data('promo_category_id');
        $(".select-promo-category-dropdown .dropdown-menu li[value=" + promotion_category_id + "]").trigger('click');

        var top = anchor_promo.offset().top - $('.header').height();
        if(top > 0){
            $('html,body').animate({scrollTop: top}, 1000);
        }
        anchor_promo.click();
    }else{
        $(".select-promo-category-dropdown .dropdown-menu li[value=" + promotion_category_id + "]").trigger('click');
    }


    $( ".claim-promo" ).click(function(event) {
        event.preventDefault();
        var href = $(this).attr('href');
        var promoCmsSettingId = $(this).data("promo-cms-setting-id");
        console.log('request_promo promoCmsSettingId: '+promoCmsSettingId);
        // register to iovation when joined promotion
        requestPromoNow(promoCmsSettingId);

    });

    if(!!viewPromoDetailId && $('.btn-details-'+viewPromoDetailId).length > 0) {
        // $(`li[role*="presentation"][value=${currentPromoCategory}]`).trigger('click');//.closest(`.btn-details-${viewPromoDetailId}`).trigger('click');
        $('li[role*="presentation"][value="'+currentPromoCategory+'"]').trigger('click');
        $('#promo_category_'+currentPromoCategory+' .btn-details-'+viewPromoDetailId).trigger('click');
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
</style>