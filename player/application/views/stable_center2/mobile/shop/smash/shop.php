<style>
	.shop-wrapper {
		border: 1px #5e1cd9 solid;
		border-radius: 20px;
		overflow: hidden;
		position: relative;
	}
	.shop-wrapper figure .img-thumb-wrap img {
		max-width: 100%;
    	height: auto;
	}
	.shop-wrapper figure .shop-name-wrap {
		background: #5419c3;
		text-align: center;
		color: #fff;
		padding: 10px 0;

	}
	.shop-wrapper figure .shop-name-wrap span.new-tag {
		background: #ff0002;
	    display: inline-block;
	    padding: 0 3px;
	    font-size: 12px;
	    height: 20px;
	    line-height: 20px;
	    margin-left: 3px;
	}
	.shop-wrapper figure figcaption {
		opacity: 0;
	    visibility: hidden;
	    position: absolute;
	    top: 0;
	    left: 0;
	    width: 100%;
	    height: 100%;
	    background: rgb(28 28 28 / 66%);
	    display: flex;
	    justify-content: center;
	    align-items: center;
	    transition: all .6s;
	    -webkit-transition: .6s;
	    -moz-transition: .6s;
	    -o-transition: .6s;
	    -ms-transition: all .6s;
	}
	.shop-wrapper figure:hover figcaption {
		opacity: 1;
		visibility: visible;
	}
	.shop-wrapper figure figcaption div {

	}
	.shop-wrapper figure figcaption div a {
		background: #5e1cd9;
	    color: #fff;
	    text-decoration: none;
	    padding: 10px;
	    border-radius: 10px;
	}
</style>

<div class="row content shop">

    <h4><?php echo lang("Shop");?></h4>
    <p><?php echo lang("Shop now using your points.");?></p>

    <div class="clearfix"></div>
</div>
<div class="container">
<?php if (!empty($shoppingList)){?>
    <div id="shop" class="tab-pane main-content">

        <div class="row">

        <?php foreach ($shoppingList as $key=>$item){?>

            <div class="col-md-2 col-xs-6">
                <div class="shop-wrapper">
                    <figure>
                        <div class="img-thumb-wrap">
                            <img src="<?=$item['shopBannerUrl']?>">
                        </div>
                        <div class="shop-name-wrap">
                            <?php echo ucwords($item['title']);?>
                            <?php if ($key['tag_as_new']) {?>
                                <span class="new-tag"><?php echo lang("New"); ?></span>
                            <?php }?>
                        </div>
                        <figcaption>
                            <div>
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
                        </figcaption>
                    </figure>
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
            </div>
        <?php } ?>

        </div>





    </div>
<?php } ?>

</div>

<script>
    $(function(){
        /** initialize default */
        Shop.default_banner_img_src = "<?= $this->utils->imageUrl('shopping_banner/shop_banner_temp1.jpg') ?>";
        Shop.claim_now_msg = "<?= lang('Claim Now'); ?>";
        Shop.pending_msg = "<?php echo lang('Pending'); ?>";
    });
</script>

