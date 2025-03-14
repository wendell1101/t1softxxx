<?php
// cloned from player/application/views/stable_center2/includes/dashboard/smash/shop.php
$shoppingList = $this->utils->getAvailableShoppingList();
?>

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

    img#shopItemPreviewImg { /* for fit the goods image size in the popup at onestop-STG and smash-STG2 */
        max-width: 100%;
        padding: 8px;
    }
</style>



    <div id="shop" class="tab-pane main-content">

        <div class="row content shop">

            <h4><?php echo lang("Shop");?></h4>
            <p><?php echo lang("Shop now using your points.");?></p>

            <div class="clearfix"></div>
        </div>
        <div class="row">

        <?php if (!empty($shoppingList)) {
            foreach ($shoppingList as $key) {
                $file_path = $this->utils->getShopThumbnailsPath();
                if (array_key_exists('promoThumbnail', $key)) {
                    $file_path = $file_path . $key['promoThumbnail'];
                }

                if (file_exists($file_path) &&!empty($key['banner_url'])) {
                  if ($key['is_default_banner_flag']) {
                    $shopBannerUrl = $this->utils->imageUrl('shopping_banner/' . $key['banner_url']);
                  } else {
                    if ($this->utils->isEnabledMDB()) {
                        $activeDB = $this->utils->getActiveTargetDB();
                        $shopBannerUrl = base_url().'upload/'.$activeDB.'/shopthumbnails/'.$key['banner_url'];
                    } else {
                        $shopBannerUrl = base_url().'upload/shopthumbnails/'.$key['banner_url'];
                    }
                  }

                } else {
                  $shopBannerUrl = $this->utils->imageUrl('shopping_banner/shop_banner_temp1.jpg');
                }
                ?>
            <div class="col-md-2 col-xs-6">
                <div class="shop-wrapper">
                    <figure>
                        <div class="img-thumb-wrap">
                            <img src="<?php echo $shopBannerUrl?>">
                        </div>
                        <div class="shop-name-wrap">
                            <?php echo ucwords($key['title']) ?>
                            <?php if ($key['tag_as_new']) {?>
                                <span class="new-tag"><?php echo lang("New"); ?></span>
                            <?php }?>
                            <!-- <span class="new-tag">NEW</span> -->
                        </div>
                        <figcaption>
                            <div>
                                <a class="viewShopItemDetails" href="#!" onclick="Shop.displayShoppingDetails(this, <?= $this->authentication->getPlayerId() ?>);" id="<?php echo $key['id'] ?>" data-toggle="modal" data-target="#shop-modal"><?php echo lang("Avail"); ?></a>
                            </div>
                        </figcaption>
                    </figure>
                </div>
            </div>
        <?php }}?>

        </div>
    </div>


<!-- Modal -->
<div class="modal fade shop-modal" id="shop-modal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <img id="shopItemPreviewImg" src="<?= $this->utils->imageUrl('shopping_banner/shop_banner_temp1.jpg') ?>" alt="Shop">
        <h4 class="modal-title" id="myModalLabel"><span id="shopItemTitle"></span></h4>
      </div>
      <div class="modal-body">
        <div class="row">
          <div class="col-xs-7">
            <h4><?php echo lang("Details"); ?>:</h4>
            <p id="shopItemDesc">
            </p>
          </div>
          <div class="col-xs-5 amount">
            <h4><?php echo lang("Required Points"); ?>:</h4>
            <span id="shopItemRequirePoints"></span>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <div class="row" id="shopMsgSec">
          <center>
          <p id="shopMsg"></p>
          </center>
        </div>
        <div id="claimBtnSec">

        </div>
        <!-- <button type="button" id="claimShoppingItemBtn" onclick="claimShoppingItem(this)" class="btn btn-default submit-btn claimShoppingItemBtn"><span id="claimNowBtnTxt"><?php echo lang("Claim Now"); ?></span></button> -->
      </div>
    </div>
  </div>
</div>

<script type="text/javascript" src="<?=$this->utils->getPlayerCmsUrl('/common/js/player_center/player-shop.js') ?>"></script>
<script type="text/javascript">
$(function(){
    /** initialize default */
    Shop.default_banner_img_src = "<?= $this->utils->imageUrl('shopping_banner/shop_banner_temp1.jpg') ?>";
    Shop.claim_now_msg = "<?= lang('Claim Now'); ?>";
    Shop.pending_msg = "<?php echo lang('Pending'); ?>";
});
</script>