<div id="now" class="show">
    <?php foreach($playerpromo as $key) { ?>
    <?php if ($key['promoCmsTitle'] != "_SYSTEM_MANUAL") { ?>
    <div class="pr_show threepage cpt5" data-toggle="collapse" data-target="#promo_<?=$key["playerpromoId"]?>" style="display: block;cursor:pointer; padding: 10px; margin-bottom: 0">
        <div class="icon"></div>
        <div class="primage" style="width: 25%">
            <?php
            if(file_exists($this->utils->getPromoThumbnails().$key['promoThumbnail']) && !empty($key['promoThumbnail'])){
                $promoThumbnail = $this->utils->getPromoThumbnailRelativePath() . $key['promoThumbnail'];
            } else {
                if(!empty($key['promoThumbnail'])){
                    $promoThumbnail = $this->utils->imageUrl('promothumbnails/'.$key['promoThumbnail']);
                } else {
                    $promoThumbnail = $this->utils->imageUrl('promothumbnails/default_promo_cms_1.jpg');
                }
            }
            ?>
            <img src="<?=$promoThumbnail?>?v=<?=PRODUCTION_VERSION?>" style="width: 71px; min-height: 53px;">
        </div>
         <div class="title" style="width: 48%; display: block; -webkit-margin-start: 0px; -webkit-margin-end: 0px; font-weight: bold; font-size: 16px;">【<?php echo $key['promoCmsTitle'] ?>】</div>
         <div class="title" style="width: 48%; color: #e50000; font-size: 14px;"><?php echo $key['promoDescription'] ?></div>
         <div class="pr_time"><?=$key['dateApply']?></div>
         <div class="lookover">
            <div class="lookover_text"><?=lang('lang.details')?></div>
            <div class="lookover_icon"></div>
        </div>
    </div>
    <div id="promo_<?=$key["playerpromoId"]?>" class="collapse" style="background-color: #fff;">
        <div class="panel-body" style="border-top: 1px solid #ddd;">
            <div class="lestCo">
                <div class="row">
                    <div class="col-xs-7">
                        <div id="dateApplied">
                                <p><span><?php echo lang("Date Applied") ?>:</span> <p id="dateAppliedTxt"><?=$key['dateApply']?></p></p>
                        </div>
                        <p><span><?php echo lang("Promo Type") ?>:</span> <p id="promoCmsPromoTypeModal"><?=$key['promoTypeName']?></p></p>
                        <h4><?php echo lang("sys.description") ?>:</h4>
                        <p id="promoCmsPromoDetailsModal"><?=$key['promoDescription']?></p>
                    </div>
                    <div class="col-xs-5 amount">
                        <h4><?php echo lang("Bonus Amount") ?>:</h4>
                        <span id="promoCmsBonusAmountModal"><?=$key['bonusAmount']?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php   }?>
    <?php } ?>
</div>
<?=$this->load->view($this->utils->getPlayerCenterTemplate(FALSE) . '/mobile/includes/template_footer');?>

<script>
    $(document).ready(function () {
        $('#ht').html('<?=lang("header.promotions")?>');
    });
</script>