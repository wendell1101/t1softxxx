<div class="row content shop">

    <h4><?php echo lang("Shop");?></h4>
    <p><?php echo lang("Shop now using your points.");?></p>

    <div class="clearfix"></div>
    <div class="promotions">
        <?php if (!empty($shoppingList)){?>

        <div class="promotions-category-list">

        <?php foreach ($shoppingList as $key=>$item){?>
            <div class="pr_show threepage cpt5" data-promo_item_anchor="promo_item_01">
                <div class="primage">
                    <img src="<?=$item['shopBannerUrl']?>" />
                </div>
                <div class="title">
                    <?php echo ucwords($item['title']);?>
                </div>
                <div class="description"><?=$item['short_description']?></div>
                <div class="actions">
                    <a  href="javascript: void(0);"
                        class="btn btn-sm btn-info btn-details-<?=$key?> collapsed"
                        data-toggle="collapse"
                        data-target="#shopping_item_<?=$key?>"
                        aria-expanded="true"
                        id="<?=$item['id']?>"
                        onclick="Shop.displayShoppingDetailsMobileVer(this, <?=$playerId?>);"
                        >
                        <?php echo lang("Avail"); ?>
                    </a>
                </div>
            </div>
            <div id="shopping_item_<?=$key?>" class="collapse" aria-expanded="true" style="">
                <div class="panel-body">
                    <p class="s-title"><?php echo lang("Details");?>:</p>
                    <p><?=$item['details']?></p>
                    <div class="item-points">
                        <p class="s-title"><?php echo lang("Required Points"); ?>:</p>
                        <span class="shopItemRequirePoints"><i class="fa fa-cubes" aria-hidden="true"></i><?=$item['required_points']?></span>
                    </div>
                </div>
                <div class="modal-footer">
                    <div class="row" id="shopMsgSec_item_<?=$item['id']?>">
                        <center> <p id="shopMsg_item_<?=$item['id']?>"></p> </center>
                    </div>
                    <div id="claimBtnSec_item_<?=$item['id']?>"></div>
                </div>
            </div>
        <?php } ?>
        </div>
        <?php }?>
    </div>

</div>
<script>
    $(function(){
        /** initialize default */
        Shop.default_banner_img_src = "<?= $this->utils->imageUrl('shopping_banner/shop_banner_temp1.jpg') ?>";
        Shop.claim_now_msg = "<?= lang('Claim Now'); ?>";
        Shop.pending_msg = "<?php echo lang('Pending'); ?>";
    });
</script>
<style>
  .shop>h4 {
    text-align: center;
    padding-top: 15px;
    margin-top: 0;
  }
  .shop>p {
    text-align: center;
  }
  .shop .pr_show .description {
    font-size: 12px;
    text-overflow: ellipsis;
    padding-right: 110px;
  }
  .shop .s-title {
    color: #9a9a9a;
    font-size: 12px;
  }
  .shop .collapse .panel-body, .promotions .collapsing .panel-body {
    text-align: left;
    padding: 15px;
  }
  span.shopItemRequirePoints {
    background: #fed981 !important;
    background: -moz-linear-gradient(left, #3e2a06 0%, #fed981 100%) !important;
    background: -webkit-linear-gradient(left, #3e2a06 0%,#fed981 100%) !important;
    background: linear-gradient(to right, #3e2a06 0%,#fed981 100%) !important;
    filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='#3e2a06', endColorstr='#fed981',GradientType=1 ) !important;
    height: 35px !important;
    display: flex;
    justify-content: center;
    align-items: center;
    width: 95px;
    border-radius: 150px !important;
  }
  .shop i.fa.fa-cubes {
    font-size: 20px !important;
    margin-right: 10px;
  }
</style>