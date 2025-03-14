<?php
    $uploadUri=$this->utils->getPromoThumbnailRelativePath();

    $view_template = $this->utils->getPlayerCenterTemplate();
    if ($view_template == 'iframe') {
        $show_promo_uri = 'iframe_module/show_promo/';
        $preapplication_promo_uri = 'iframe_module/preapplication/';
        $addto_promo_uri = 'iframe_module/addtopromo/';
    } else {
        $show_promo_uri = 'player_center/show_promo/';
        $preapplication_promo_uri = 'player_center/preapplication/';
        $addto_promo_uri = 'player_center/addtopromo/';
        $view_promo_link = 'player_center2/promotion/';
    }
?>

<style type="text/css">
    /*page loader*/
    .loader {
      z-index: 99999;
      display: inline-block;
      position: fixed;
      top: 50%;
      left: 50%;
      border: 16px solid #f3f3f3;
      border-radius: 50%;
      border-top: 16px solid #3498db;
      border-bottom: 16px solid #3498db;
      width: 120px;
      height: 120px;
      -webkit-animation: spin 2s linear infinite; /* Safari */
      animation: spin 2s linear infinite;
    }

    /* Safari */
    @-webkit-keyframes spin {
      0% { -webkit-transform: rotate(0deg); }
      100% { -webkit-transform: rotate(360deg); }
    }

    @keyframes spin {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }
    .form-control.forread {
       background: linear-gradient(#FFF, #EBEBEB);
    }

    select.editPromoLink option:disabled {
        color: #B7B4B6;
    }


    /** The Field Loading */
    .glyphicon-refresh-animate {
        -animation: spin .7s infinite linear;
        -webkit-animation: spin2 .7s infinite linear;
    }

    @-webkit-keyframes spin2 {
        from { -webkit-transform: rotate(0deg);}
        to { -webkit-transform: rotate(360deg);}
    }

    @keyframes spin {
        from { transform: scale(1) rotate(0deg);}
        to { transform: scale(1) rotate(360deg);}
    }
</style>

<div class="loader"></div>
<div class="panel panel-primary">
    <div class="panel-heading">
        <h4 class="panel-title">
            <i class="fa fa-search"></i> <?= lang('lang.search')?><span class="pull-right">
                <a data-toggle="collapse" href="#collapseViewUsers" class="btn btn-info btn-xs" aria-expanded="false"></a>
            </span>
        </h4>
    </div>
    <div id="collapseViewUsers" class="panel-collapse collapse in" aria-expanded="false">
        <div class="panel-body">
            <form class="form-horizontal" method="get" id="search_form" autocomplete="off" role="form">
                <div class="form-group">
                    <div class="col-md-3">
                        <label class="control-label"><?= lang('cms.promoCat'); ?></label>
                        <select name="category" id="category" class="form-control input-sm">
                            <option value="all" <?php echo (!isset($search['promotype.promotypeId']) || $search['promotype.promotypeId'] == 'all') ?  'selected="selected"' : '' ?>><?= lang('lang.all') ?></option>
                            <?php foreach ($promoCategoryList as $promoCategory) : ?>
                            <option value="<?= $promoCategory['id'] ?>" <?php echo (isset($search['promotype.promotypeId']) && $search['promotype.promotypeId'] == $promoCategory['id']) ?  'selected="selected"' : '' ?>><?= lang($promoCategory['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="control-label"><?= lang('lang.status'); ?></label>
                        <select name="status" id="status" class="form-control input-sm">
                            <option value="all" <?php echo (!isset($search['promocmssetting.status']) || $search['promocmssetting.status'] == 'all') ?  'selected="selected"' : '' ?>><?= lang('lang.all') ?></option>
                            <option value="active" <?php echo (isset($search['promocmssetting.status']) && $search['promocmssetting.status'] == 'active') ?  'selected="selected"' : '' ?>>active</option>
                            <option value="inactive" <?php echo (isset($search['promocmssetting.status']) && $search['promocmssetting.status'] == 'inactive') ?  'selected="selected"' : '' ?>>inactive</option>
                        </select>
                    </div>
                </div>
            </form>
        </div>

        <div class="panel-footer">
            <div class="text-center">
                <button class="btn btn-sm btn-linkwater" type="reset" form="search_form">Reset</button>
                <button class="btn btn-sm btn-portage" type="submit" form="search_form"><i class="fa fa-search"></i> Search</button>
            </div>
        </div>
    </div>
</div>
<div id="promo_manager_container" class="panel panel-primary">
    <div class="panel-heading custom-ph">
        <h4 class="panel-title custom-pt">
            <i class="icon-bullhorn"></i> <?=lang('cms.06');?>
            <button class="btn pull-right btn-xs btn-info" id="add_new_promo_setting">
                <i class="fa fa-plus-circle"></i> <?=lang('cms.addNewPromo2');?> </span>
            </button>
            <div class="clearfix"></div>
        </h4>
    </div>
    <div class="panel-body" id="details-panel-body">
        <input type="hidden" id="promoCmsID" class="form-control" readonly>
        <div class="row edit_promocms_sec">
            <div class="col-md-12">
                <div class="well" style="overflow: auto;">
                    <form action="<?=site_url('marketing_management/addNewCmsPromo')?>" method="post" role="form" id="form-editcmspromo"  accept-charset="utf-8" enctype="multipart/form-data">
                        <div class="row">
                            <?=$double_submit_hidden_field?>
                            <div class="col-md-5 form-group required">
                                <label for="promoCmsCategory"><?=lang('cms.promoCmsCategory');?>: </label>
                                <span class="text-danger error-editPromoCmsCategoryId" hidden><?=sprintf(lang('gen.error.required'), lang('cms.promoCmsCategory'))?></span>
                                <select name="promoCmsCategoryId" id="editPromoCmsCategoryId" class="form-control" required>
                                    <option value=""><?=lang('cms.selectPromoCat');?></option>
                                     <?php foreach ($promoCategoryList as $value) : ?>
                                        <option value="<?=$value['id']?>"><?=lang($value['name'])?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-5 form-group required">
                                <label for="promoLink"><?=lang('cms.promoRuleSettings');?>: </label>
                                <span class="text-danger error-editPromoLink" hidden><?=sprintf(lang('gen.error.required'), lang('cms.promoRuleSettings'))?></span>
                                <select name="promoLink" id="editPromoLink" class="form-control editPromoLink" required>
                                    <option value="" <?=$this->session->userdata('paymentReportSortByPlayerLevel') == '' ? 'selected' : ''?>>-- <?=lang('cms.selpromolink');?> --</option>
                                    <?php foreach ($promorules as $key => $value) {?>
                                        <option value="<?=$value['promorulesId']?>" <?=($value['used'])?'disabled="disabled"':'';?>><?=$value['promoName']?></option>
                                    <?php } ?>
                               </select>
                            </div>
                            <div class="col-md-2">
                                <label><?=lang('cms.showPromoRulesType');?></label>
                                <div class="form-control input-sm forread">
                                    <span id="" class="showPromoRulesType"></span>
                                </div>
                            </div>
                        </div>
                        <br/>
                        <div class="row">
                            <div class="col-md-5 form-group required">
                                <label for="promoName"><?=lang('cms.promotitle');?>: </label>
                                <span class="text-danger error-editpromoName" hidden><?=sprintf(lang('gen.error.required'), lang('cms.promotitle'))?></span>
                                <input type="hidden" id="editPromocmsId" name="promocmsId" class="form-control input-sm" required>
                                <input type="text" maxlength="100" id="editPromoName" name="promoName" class="form-control input-sm editPromoTitleTxt" required>
                            </div>
                            <div class="col-md-5 form-group required">
                                <label for="promoDescription"><?=lang('cms.promodesc');?>: </label>
                                <span class="text-danger error-editPromoDescription" hidden><?=sprintf(lang('gen.error.required'), lang('cms.promodesc'))?></span>
                                <input type="text" maxlength="100" name="promoDescription" id="editPromoDescription" class="form-control input-sm editPromoDescTxt" required>
                            </div>

                            <?php if ($this->utils->getConfig('enabled_promorulesorder')) {
                            ?>

                            <div class="col-md-2">
                                <label><?=lang('Promo Order');?></label>
                                <input type="number" min="0" onkeydown="if(event.key==='.'){event.preventDefault();}"  oninput="event.target.value = event.target.value.replace(/[^0-9]*/g,'');" id="editPromoOrder" name="promoOrder" class="form-control input-sm editPromoTitleTxt" required>
                            </div>

                            <?php } ?>

                        </div>
                        <br/>
                        <div class="row">
                            <div class="col-md-12 form-group required" >
                                <label for="promoDetails"><?=lang('cms.promodetail');?>: </label>
                                <span class="text-danger error-editPromoDetails" hidden><?=sprintf(lang('gen.error.required'), lang('cms.promodetail'))?></span>
                                <div style="background-color:#fff;">
                                    <input name="promoDetails" type="hidden" class="promoDetails" required/>
                                    <div class="summernote" id="editPromoDetails"></div>
                                    <input type="hidden" name="promoDetailsLength" id="promoDetailsLength">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <?php if (!$this->utils->getConfig('enabled_multiple_type_tags_in_promotions')) {?>
                                    <input type="checkbox" name="tagAsNewFlag" id="editTagAsNewFlag">
                                    <?=lang("Tag as New") ?>
                                <?php } else {?>
                                    <label for="tagAsNewFlag"><font class="text-red panel-title"><?=lang("deposit_page_event_tag") ?></font></label>
                                    <?php $multiple_type_tags_in_promotions=$this->utils->getConfig('multiple_type_tags_in_promotions');
                                    foreach ($multiple_type_tags_in_promotions as $value) {
                                        switch ($value) {
                                            case 'New':
                                                echo '<input type="radio" name="tagAsNewFlag" id="tagAsNew" value="1">';
                                                echo '<span class="label-text"><font class="text-red">'.lang("NEW").'</font></spand>';
                                                break;
                                            case 'Favourite':
                                                echo '<input type="radio" name="tagAsNewFlag" id="tagAsFavourite" value="2">';
                                                echo '<span class="label-text">'.lang("Favourite").'</span>';
                                                break;
                                            case 'EndSoon':
                                                echo '<input type="radio" name="tagAsNewFlag" id="tagAsEndSoon" value="3">';
                                                echo '<span class="label-text">'.lang("End Soon").'</span>';
                                                break;
                                            case 'Hot':
                                                echo '<input type="radio" name="tagAsNewFlag" id="tagAsHot" value="4">';
                                                echo '<span class="label-text"><font class="text-success">'.lang("HOT").'</font></span>';
                                                break;
                                            case 'NoTag':
                                                    echo '<input type="radio" name="tagAsNewFlag" id="tagAsNoTag" value="0" checked>';
                                                    echo '<span class="label-text" >'.lang("No Tag").'</span>';
                                                break;    
                                            default:
                                                break;
                                        }
                                    }
                                    ?>
                                <?php } ?>
                            </div>
                        </div>
                        <br/>
                        <div class="row">
                            <div class="col-md-5">
                                <label><?=lang('cms.promocode');?> <span class="hide promocode-loading glyphicon glyphicon-refresh glyphicon-refresh-animate"></span> </label>
                                <div class="input-group">
                                    <input type="text" id="editPromoCode" name="promoCode" class="form-control input-sm editPromoCode" maxlength="15" required>
                                    <div class="input-group-btn">
                                        <a href="javascript:void(0)" class="btn btn-default btn-scooter btn-sm random-code" onclick="randomPromoCodeWithAjax('');" style="border-radius: 0 !important;">
                                        <i class="glyphicon glyphicon-chevron-left"></i><?=lang('aff.ai38') ?></a>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="requestPromoLink"><?=lang('Promo Link');?></label>
                                <div class="form-control input-sm forread">
                                    <?=$this->utils->getSystemUrl('player') . '/' . $addto_promo_uri; ?><span id="requestPromoLink" class="editPromoCMSCode"></span>
                                    <a href="javascript:void(0);" class="btn-copy-by-text btn btn-scooter btn-xs pull-right" style="margin-right: 4px;" data-clipboard-text="<?=$this->utils->getSystemUrl('player') . '/' . $view_promo_link; ?>">
                                        <i class="fa fa-clipboard" alt="<?=lang('Copy link'); ?>"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <br/>
                        <div class="row">
                            <div class="col-md-5">
                                <label><?=lang('The promotion display in');?></label>
                                <div class="well">
                                    <div class="row">
                                        <div class="col-md-5">
                                            <label class="checkbox-inline">
                                                <input type="checkbox" id="show_on_player_promotion" name="show_on_player[]" class="show_on_player" value="<?=Promorules::SHOW_ON_PLAYER_PROMOTION?>">
                                                <?=lang('promo.manager.show_on_player.promotion_page')?>
                                            </label>
                                        </div>
                                        <div class="col-md-7">
                                        <label class="checkbox-inline">
                                            <input type="checkbox" id="show_on_player_deposit" name="show_on_player[]" class="show_on_player" value="<?=Promorules::SHOW_ON_PLAYER_DEPOSIT?>">
                                                <?=lang('promo.manager.show_on_player.deposit_page')?>
                                            <br><?=lang('promo.manager.show_on_player.deposit_page_hint')?>
                                        </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col col-md-5">
                                <label class="control-label"><?=lang('promo.allow_claim_promo_in_promo_page');?></label>
                                    &nbsp;&nbsp;
                                    <label class="radio-inline">
                                        <input type="radio" class="input-control" name="allow_claim_promo_in_promo_page" value="1" checked="checked">
                                        <span class="label-text"><?=lang('lang.yes')?></span>
                                    </label>
                                    &nbsp;&nbsp;&nbsp;&nbsp;
                                    <label class="radio-inline">
                                        <input type="radio" class="input-control" name="allow_claim_promo_in_promo_page" value="0">
                                        <span class="label-text"><?=lang('lang.no')?></span>
                                    </label>
                                <div class="row">
                                    <div class="col col-md-5">
                                        <label class="control-label" for="claim_button_link"><?=lang('promo.claim_promo_btn_link');?></label>
                                        <select type="text" class="form-control" id="claim_button_link" name="claim_button_link">
                                            <option value="deposit"><?=lang('Deposit')?></option>
                                            <option value="referral"><?=lang('Refer a Friend')?></option>
                                            <?php if($this->utils->getConfig('enable_promo_custom_claim_button_url')):?>
                                                <option value="custom"><?=lang('lang.custom')?></option>
                                            <?php endif;?>
                                        </select>
                                    </div>
                                    <div class="col col-md-7">
                                        <label class="control-label" for="claim_button_name"><?=lang('promo.claim_promo_btn_name');?></label>
                                        <input type="text" class="form-control" id="claim_button_name" name="claim_button_name" value="">
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col col-md-5"></div>
                                    <div class="col col-md-7"><br/>
                                        <label class="control-label" for="claim_button_url"><?=lang('mod.url');?></label>
                                        <input type="text" class="form-control" id="claim_button_url" name="claim_button_url" value="">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-5">
                                <label><?=lang('promo.display_apply_btn_in_promo_page');?></label>
                                <div class="well">

                                    <label class="radio-inline">
                                        <input type="radio" class="input-control" name="display_apply_btn_in_promo_page" value="1" checked="checked">
                                        <span class="label-text"><?=lang('lang.yes')?></span>
                                    </label>
                                    &nbsp;&nbsp;&nbsp;&nbsp;
                                    <label class="radio-inline">
                                        <input type="radio" class="input-control" name="display_apply_btn_in_promo_page" value="0">
                                        <span class="label-text"><?=lang('lang.no')?></span>
                                    </label>
                                </div>
                            </div>
                            <?php if ($this->utils->getConfig('enabled_promo_previewlink')) { ?>
                            <div class="col-md-6">
                                <label for="viewPromoLink"><?=lang('View Promo');?></label>
                                <div class="form-control input-sm forread">
                                    <?=$this->utils->getSystemUrl('player') . '/' . $view_promo_link; ?><span id="viewPromoLink" class="editPromoCMSCode"></span>

                                    <a href="javascript:void(0);" class="btn-copy-by-text btn btn-scooter btn-xs pull-right" style="margin-right: 4px;" data-clipboard-text="<?=$this->utils->getSystemUrl('player') . '/' . $view_promo_link; ?>">
                                        <i class="fa fa-clipboard" alt="<?=lang('Copy link'); ?>"></i>
                                    </a>
                                </div>
                            </div>
                            <?php } ?>
                        </div>
                        <br/>
                        <?php if ($this->utils->isEnabledFeature('enable_multi_lang_promo_manager')) { ?>
                            <div class="row">
                                <div class="col-md-5">
                                    <div class="col-md-5">
                                        <label for=""><?=lang('Select default language');?>
                                            <select name="promo_item_default_lang" id="edit_promo_item_default_lang" class="form-control" style="width: 185%;">
                                                <?php foreach ($systemLanguages as $lang) { ?>
                                                    <option value="<?=$lang['short_code']?>"><?=$lang['word']?></option>
                                                <?php } ?>
                                            </select>
                                        </label>
                                    </div>
                                    <div class="col-md-7" style="padding-top: 16px;">
                                        <div class="btn btn-md btn-primary" data-toggle="modal" onclick="setPromoItemDefaultLanguage()">
                                            <span><?=lang("Setup Multi Language") ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <br/>
                        <?php } ?>

                        <!-- Edit Banner Upload Module Start -->
                        <div class="row">
                            <div class="col-md-12">
                                <div class="promo_banner_sec">
                                    <h6><label for="promoThumbnail"><?=lang('Banner')?>: </label></h6>
                                    <div class="banner_container">
                                        <img id="edit_promo_cms_banner_600x300" class="promo_cms_banner_600x300" style="width: 600px; height: 300px;"/>
                                        <div class='upload_req_txt' id="edit_upload_req_txt">
                                            <?php
                                                echo "600px x 300px<br/>";
                                                echo "JPEG,PNG,GIF, WEBP<br/>";
                                                echo lang("File must not exceed 2MB.");
                                            ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="promo_banner_sec upload_btn_sec">
                                    <div class="">
                                        &nbsp;&nbsp;&nbsp;<input type="checkbox" name="set_default_banner" id="edit_set_default_banner"> <label for="edit_set_default_banner"><?=lang("Use default banner") ?></label>
                                    </div>
                                    <div class="presetBannerType">
                                        <img class="presetBannerImg btn" id="default_promo_cms_1" onclick="setBannerImg(this,'edit_promo_cms_banner_600x300')" src="<?=$this->utils->imageUrl('promothumbnails/default_promo_cms_1.jpg')?>" width="130px">
                                        <img class="presetBannerImg btn" id="default_promo_cms_2" onclick="setBannerImg(this,'edit_promo_cms_banner_600x300')" src="<?=$this->utils->imageUrl('promothumbnails/default_promo_cms_2.jpg')?>" width="130px">
                                        <img class="presetBannerImg btn" id="default_promo_cms_3" onclick="setBannerImg(this,'edit_promo_cms_banner_600x300')" src="<?=$this->utils->imageUrl('promothumbnails/default_promo_cms_3.jpg')?>" width="130px">
                                        <img class="presetBannerImg btn" id="default_promo_cms_4" onclick="setBannerImg(this,'edit_promo_cms_banner_600x300')" src="<?=$this->utils->imageUrl('promothumbnails/default_promo_cms_4.jpg')?>" width="130px">
                                        <br/>
                                        <img class="presetBannerImg btn" id="default_promo_cms_5" onclick="setBannerImg(this,'edit_promo_cms_banner_600x300')" src="<?=$this->utils->imageUrl('promothumbnails/default_promo_cms_5.jpg')?>" width="130px">
                                        <img class="presetBannerImg btn" id="default_promo_cms_6" onclick="setBannerImg(this,'edit_promo_cms_banner_600x300')" src="<?=$this->utils->imageUrl('promothumbnails/default_promo_cms_6.jpg')?>" width="130px">
                                        <img class="presetBannerImg btn" id="default_promo_cms_7" onclick="setBannerImg(this,'edit_promo_cms_banner_600x300')" src="<?=$this->utils->imageUrl('promothumbnails/default_promo_cms_7.jpg')?>" width="130px">
                                        <img class="presetBannerImg btn" id="default_promo_cms_8" onclick="setBannerImg(this,'edit_promo_cms_banner_600x300')" src="<?=$this->utils->imageUrl('promothumbnails/default_promo_cms_8.jpg')?>" width="130px">
                                    </div>

                                    <div class="fileUpload btn btn-md btn-info">
                                        <span><?=lang("Upload") ?></span>
                                        <input type="file" name="userfile[]" class="upload" id="userfile" onchange="uploadImage(this,'edit_promo_cms_banner_600x300');">
                                    </div>
                                    <div class="previewBtn btn btn-md btn-scooter" onclick="showEditBannerPreview()" data-toggle="modal" data-target=".bannerPreview">
                                        <span><?=lang("Preview") ?></span>
                                    </div>
                                </div>
                                <input type="hidden" name="banner_url" id="editBannerUrl" class="form-control" readonly>
                                <input type="hidden" name="editPromoThumbnail" id="editPromoThumbnail" >
                                <input type="hidden" name="is_default_banner_flag" id="isEditDefaultBannerFlag" class="form-control" readonly>
                            </div>
                        </div>
                        <br/>
                        <center>
                            <br/>
                            <span class="btn btn-sm btn-scooter" onclick="showEditPromoCmsPreview()" data-toggle="modal" data-target=".promoCmsPreview"><?=lang("Preview") ?></span>
                            <span class="btn btn-sm editcmspromo-cancel-btn custom-btn-size btn-linkwater" data-toggle="modal"><?=lang('lang.cancel');?></span>
                            <input type="button" value="<?=lang('lang.save');?>" class="btn btn-sm review-btn custom-btn-size btn-portage" data-toggle="modal" id="edit_promo_submit" />
                        </center>
                    </form>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div id="tag_table" class="table-responsive">
                    <table class="table table-bordered table-hover dataTable" id="promo_list_table" style="width:100%;">
                        <hr class="hr_between_table"/>

                        <thead>
                            <tr>
                                <th ><?=lang('cms.promotitle');?></th>
                                <th ><?=lang('cms.promoCat');?></th>
                                <th ><?=lang('Short Description');?></th>
                                <th ><?=lang('cms.promothumb');?></th>
                                <th ><?=lang('cms.promolink');?></th>
                                <th ><?=lang('Tag as New');?></th>
                                <th ><?=lang('cms.createdon');?></th>
                                <th ><?=lang('cms.createdby');?></th>
                                <th ><?=lang('cms.updatedon');?></th>
                                <th ><?=lang('cms.updatedby');?></th>

                                <?php if ($this->utils->getConfig('enabled_promorulesorder')) { ?>
                                    <th ><?=lang('Promo Order');?></th>
                                <?php } ?>

                                <th ><?=lang('lang.status');?></th>
                                <th ><?=lang('lang.action');?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($promoList)) {
                                foreach ($promoList as $data) { ?>
                                    <tr <?=$data['status'] == 'inactive' ? 'class="danger"' : ''; ?> >
                                        <td>
                                            <?=$data['promoName'] == '' ?
                                                '<i class="help-block">' . lang("lang.norecord") . '<i/>' :
                                                '<span data-toggle="tooltip" title="' . lang('tool.cms05') . '" data-placement="top">
                                                <a class="edit_pormo_details" href="javascript:void(0)" onclick="getPromoCmsDetails(' . $data['promoCmsSettingId'] . ', false, null, ' . $data['promorulesId'] . ')">' . $data['promoName'] . '</a></span>'?>
                                        </td>
                                        <td>
                                            <?=lang($data['promoTypeName'])?>
                                        </td>
                                        <td><?=$data['promoDescription'] == '' ? '<i class="help-block">' . lang("lang.norecord") . '<i/>' : $data['promoDescription']?></td>
                                        <?php
                                            if(file_exists($this->utils->getPromoThumbnails().$data['promoThumbnail']) && !empty($data['promoThumbnail'])) {
                                                $promoThumbnail = $uploadUri . $data['promoThumbnail'];
                                            } else {
                                                if(!empty($data['promoThumbnail'])){
                                                    $promoThumbnail = $this->utils->imageUrl('promothumbnails/'.$data['promoThumbnail']);
                                                } else {
                                                    $promoThumbnail = $this->utils->imageUrl('promothumbnails/default_promo_cms_1.jpg');
                                                }
                                            }
                                        ?>
                                        <td><img id="banner_name" src="<?=$promoThumbnail?>" width=100></td>
                                        <td>
                                            <?php if ($data['promoRuleName'] == '') {?>
                                                <i class="help-block"><?=lang("lang.norecord")?></i>
                                            <?php } else {
                                                ?>
                                                <a href="/marketing_management/editPromoRule/<?=$data['promorulesId']?>" target="_blank"><?=$data['promoRuleName']; // createPromoDetailButton($data['promorulesId'], $data['promoName']); ?></a>
                                            <?php } ?>
                                        </td>
                                        <td><?=$data['tag_as_new_flag'] == '' ? '<i class="help-block">' . lang("lang.norecord") . '<i/>' : $data['tag_as_new_flag'] ? lang('Yes') : lang('No')?></td>
                                        <td><?=$data['createdOn'] == '' ? '<i class="help-block">' . lang("lang.norecord") . '<i/>' : $data['createdOn']?></td>
                                        <td><?=$data['createdBy'] == '' ? '<i class="help-block">' . lang("lang.norecord") . '<i/>' : $data['createdBy']?></td>
                                        <td><?=$data['updatedOn'] == '' ? '<i class="help-block">' . lang("lang.norecord") . '<i/>' : $data['updatedOn']?></td>
                                        <td><?=$data['updatedBy'] == '' ? '<i class="help-block">' . lang("lang.norecord") . '<i/>' : $data['updatedBy']?></td>
                                         <?php if ($this->utils->getConfig('enabled_promorulesorder')) { ?>
                                            <td><?=$data['promoOrder'] == '' ? '<i class="help-block">' . lang("lang.norecord") . '<i/>' : $data['promoOrder']?></td>
                                        <?php } ?>

                                        <td><?=$data['status'] == '' ? '<i class="help-block">' . lang("cms.nodailymaxwithdrawal") . '<i/>' : $data['status']?></td>
                                        <td>

                                           <!--  activatePromo
                                        deactivatePromo -->
                                            <div class="actionPromoCMS" align="center">
                                                <!-- <a href="/marketing_management/dryrun_promo/<?=$data['promoCmsSettingId'];?>"><?=lang('Dry Run');?></a> -->
                                                <?php if ($data['status'] == 'active') {?>
                                                    <!-- <a href="<?=site_url('marketing_management/activatePromoCms/' . $data['promoCmsSettingId'] . '/' . 'inactive')?>">
                                                        <span data-toggle="tooltip" title="<?=lang('lang.deactivate');?>" class="glyphicon glyphicon-ok-sign" data-placement="top"></span>
                                                    </a> -->
                                                    <span data-toggle="tooltip"
                                                          title="<?= lang('Deactivate'); ?>"
                                                          class="fa fa-toggle-on btn btn-xs btn-chestnutrose"
                                                          style="font-size: 15px;margin-top: 5px;"
                                                          data-placement="right"
                                                          onclick="deactivatePromo('<?=site_url('marketing_management/activatePromoCms/' . $data['promoCmsSettingId'] . '/' . 'inactive')?>');"
                                                    >
                                                    </span>
                                                <?php } else {?>
                                                    <!-- <a href="<?=site_url('marketing_management/activatePromoCms/' . $data['promoCmsSettingId'] . '/' . 'active')?>">
                                                        <span data-toggle="tooltip" title="<?=lang('lang.activate');?>" class="glyphicon glyphicon-remove-circle" data-placement="top"></span>
                                                    </a> -->
                                                    <span data-toggle="tooltip"
                                                          onclick="activatePromo('<?=site_url('marketing_management/activatePromoCms/' . $data['promoCmsSettingId'] . '/' . 'active')?>');"
                                                          title="<?= lang('Activate'); ?>"
                                                          class="fa fa-toggle-off btn btn-xs btn-info"
                                                          style="font-size: 15px;margin-top: 5px;"
                                                          data-placement="right"
                                                    >
                                                    </span>
                                                <?php } ?>
                                                <br>
                                                <a href="/marketing_management/dryrun_promo/<?=$data['promoCmsSettingId'];?>"><?=lang('Dry Run');?></a>
                                                <br>
                                                <span class="glyphicon glyphicon-edit editCmsPromoBtn" data-toggle="tooltip" title="<?=lang('lang.edit');?>" onclick="getPromoCmsDetails(<?=$data['promoCmsSettingId']?>, false, null, <?=$data['promorulesId']?>)" data-placement="top">
                                                </span>
                                                <a data-toggle="modal" data-target="#deletePromoCmsItemModal" class="deletePromoCmsItem" data-id="<?=$data['promoCmsSettingId']?>">
                                                    <span data-toggle="tooltip" title="<?=lang('lang.delete');?>" class="glyphicon glyphicon-trash" data-placement="top"></span>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php }
                            }?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="panel-footer"></div>
</div>

<!-- Delete Promo Cms Item Modal Start -->
<div class="modal fade" id="deletePromoCmsItemModal" tabindex="-1" role="dialog" aria-labelledby="deletePromoCmsItemModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="deletePromoCmsItemModalLabel"><?=lang('cms.deletePromoManager')?></h4>
            </div>
            <div class="modal-body">
                <?=($this->utils->isEnabledFeature('enabled_transfer_condition'))?lang('cms.deletePromoManagerMsgWithTransferCondition'):lang('cms.deletePromoManagerMsg');?>
            </div>
            <div class="modal-footer">
                <input type="hidden" class="deletePromoCmsId">
                <input type="hidden" class="deleteSelectedPromoCmsId">
                <button type="button" class="btn btn-primary delete-func" onclick="deletePromoCmsItem()"><?=lang('Confirm')?></button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal"><?=lang('lang.cancel')?></button>
            </div>
        </div>
    </div>
</div>
<!-- Delete Promo Cms Item Modal End -->

<!-- Banner Preview Modal Start -->
<div class="modal fade bannerPreview" tabindex="-1" role="dialog" aria-labelledby="bannerPreview" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <b><?=lang("Preview") ?></b>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="bannerPreviewContainer">
                        <center>
                            <img id="preview_promo_cms_banner_600x300" class="preview_promo_cms_banner_600x300" src="">
                        </center>

                            <div class="promoTitlePreview promoText"></div>
                            <div class="shortDescPreview promoText"></div>
                        <div class="pull-right btn btn-xs btn-danger viewPromoDetailsTxt"><span><?=lang('View Promo Details') ?></span></div>
                        <div class="clearfix"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal"><?=lang("Close") ?></button>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Banner Preview Modal End -->

<!-- Multi lang Modal Start -->
<div class="modal fade multiLangWindow" tabindex="-1" role="dialog" aria-labelledby="multiLangWindow" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <b><?=lang("Setup Multi Language") ?></b>
                    <button type="button" class="close close-multiLang-modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="multiLangWindowContainer">
                        <div class="panel-group" id="accordion">
                            <?php
                                $lang_list = [
                                    'en' => ['name' => lang("English")], //ucfirst
                                    'ch' => ['name' => lang("Chinese")],
                                    'kr' => ['name' => lang("Korean")],
                                    'id' => ['name' => lang("Indonesian")],
                                    'vn' => ['name' => lang("Vietnamese")],
                                    'th' => ['name' => lang("Thai")],
                                    'in' => ['name' => lang("India")],
                                    'pt' => ['name' => lang("Portuguese")],
                                    'es' => ['name' => lang("Spanish")],
                                    'kk' => ['name' => lang("Kazakh")],
                                    'ja' => ['name' => lang("Japaneze")],
                                    'hk' => ['name' => lang("Chinese Tranditional")],
                                    'ph' => ['name' => lang("Filipino")],
                                ];
                            ?>
                            <?php foreach ($lang_list as $lang_key => $lang) :?>
                                <div class="panel panel-default" id="panel_<?=$lang_key?>">
                                    <div class="panel-heading">
                                        <h4 class="panel-title">
                                            <a data-toggle="collapse" href="#collapse<?=ucfirst($lang_key)?>"><?=$lang['name']; ?></a>
                                        </h4>
                                    </div>
                                    <div id="collapse<?=ucfirst($lang_key)?>" class="panel-collapse collapse in">
                                        <div class="panel-body">
                                            <?=lang("cms.promotitle") ?>
                                            <input type="text" id="promo_title_<?=$lang_key?>" class="form-control"/>
                                            <br/>
                                            <?=lang("Short Description") ?>
                                            <textarea id="short_desc_<?=$lang_key?>" class="form-control"></textarea>
                                            <br/>
                                            <?=lang('cms.promodetail')?>
                                            <div style="background-color:#fff;">
                                                <div id="details_<?=$lang_key?>"></div>
                                            </div>
                                            <br/>
                                            <?=lang("Upload Banner")?>:
                                            <form id="upload_imgbnr_form_<?=$lang_key?>" enctype="multipart/form-data" method="post">
                                                <input type="file" name="userfile[]" id="upload_imgbnr_btn_<?=$lang_key?>" data-image-upload="uploadimg_<?=$lang_key?>" />
                                                <input type="hidden" name="banner_<?=$lang_key?>" id="banner_<?=$lang_key?>" />
                                                <br/>
                                                <img id="uploadimg_<?=$lang_key?>" width="600" height="300">
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach;?>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" id="saveMultiLangBtn" class="btn btn-primary" data-dismiss="modal"><?=lang("Save") ?></button>
                    <button type="button" class="btn btn-danger close-multiLang-modal"><?=lang("Close") ?></button>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Banner Preview Modal End -->

<!-- Promo CMS Preview Modal Start -->
<div class="modal fade promoCmsPreview" tabindex="-1" role="dialog" aria-labelledby="promoCmsPreview" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <b><?=lang("Preview") ?></b>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="bannerPreviewContainer">
                        <center>
                            <img id="preview_promo_cms_banner_600x300_big" class="preview_promo_cms_banner_600x300_big" src="">
                        </center>
                        <span class="promoTitlePreview floatingTxt"></span>
                        <div class="row promoDetailsSec">
                            <div class="col-md-12">
                                <div class="col-md-8">
                                    <div class="badge tagAsNewBadge">
                                        <strong><?=strtoupper(lang("New")) ?></strong>
                                    </div>
                                    <br/>
                                    <strong><?=lang("Promo Type") ?></strong>:<span id="promoTypeTxt"></span>
                                </div>
                                <div class="col-md-4">
                                    <span class="promoDetailsSecTxt"><?=lang("Bonus Amount") ?>:</span>
                                    <div class="bonusAmountSec">
                                        $200
                                    </div>
                                </div>

                                <div class="col-md-12">
                                    <div class="addPromoDetailsSec">
                                        <span class="promoDetailsTxt"></span>
                                    </div>
                                </div>

                            </div>
                            <div class="col-md-12">
                                <br/>
                                <div class="claimNowSec btn">
                                    <?=strtoupper(lang("Claim Now")) ?>
                                </div>
                                <br/><br/>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal"><?=lang("Close") ?></button>
                </div>
            </div>
        </div>
    </div>
</div>
<br/>
<!-- Promo CMS Preview Modal End -->

<script type="text/javascript">
    var enabled_multiple_type_tags_in_promotions = "<?=$this->utils->getConfig('enabled_multiple_type_tags_in_promotions')?>";
    $(document).ready(function(){

        // Clipboard
        var clipboard = new Clipboard('.btn-copy-by-text',{
            text: function(trigger) {
                return $(trigger).closest('div').text().trim();
            }
        });
        clipboard.on('success', function(e) {
            flashTooltip(e.trigger, 'Copied!');
        });

        clipboard.on('error', function(e) {
            flashTooltip(e.trigger, 'Failed!');
        });

        function flashTooltip(btn, message) {
            var deferred = $.Deferred();

            $(btn).tooltip('hide')
                .attr('data-original-title', message)
                .tooltip('show');

            setTimeout(function() {
                $(btn).tooltip('hide').attr('data-original-title', '');
                deferred.resolve({});
            }, 1000); // EOF setTimeout()

            return deferred.promise();
        } // EOF flashTooltip


        //OGP-25827
        if (enabled_multiple_type_tags_in_promotions) {
            $('.multiple_type_tags').change(function(){
                var getValue = $(this).val();
                switch (getValue) {
                    case '<?=Marketing_management::TAG_AS_NOTAG?>' :
                        $('.multiple_type_tags').removeAttr('checked');
                        $('#tagAsNoTag').prop('checked', true);
                        $('#tagAsNoTag').attr('checked', 'checked');
                        break;
                    case '<?=Marketing_management::TAG_AS_NEW?>' :
                        $('.multiple_type_tags').removeAttr('checked');
                        $('#tagAsNew').prop('checked', true);
                        $('#tagAsNew').attr('checked', 'checked');
                        break;
                    case '<?=Marketing_management::TAG_AS_FAVOURITE?>' :
                        $('.multiple_type_tags').removeAttr('checked');
                        $('#tagAsFavourite').prop('checked', true);
                        $('#tagAsFavourite').attr('checked', 'checked');
                        break;
                    case '<?=Marketing_management::TAG_AS_ENDSOON?>' :
                        $('.multiple_type_tags').removeAttr('checked');
                        $('#tagAsEndSoon').prop('checked', true);
                        $('#tagAsEndSoon').attr('checked', 'checked');
                        break;
                    default :
                }
            });
        }

        $('button[type="reset"][form="search_form"]').click(function(event) {
            event.preventDefault();
            $('#category').val('all');
            $('#status').val('all');
        });

        // $('#details_en,#details_ch,#details_kr,#details_id,#details_vn,#details_th,#details_in, #details_pt')
        $('[id^="details_"]')
        .summernote({
            lang: '<?= $current_lang ?>',
            enterHtml : '<p></br><p>'
        });

        //remove upload image button
        $('.note-editor .note-toolbar .note-insert button[data-event~="showImageDialog"]').remove();

        var dataTable = $('#promo_list_table').DataTable({
            lengthMenu: JSON.parse('<?=json_encode($this->utils->getConfig('default_datatable_lengthMenu'))?>'),
            autoWidth: false,

            <?php if ($this->utils->isEnabledFeature('column_visibility_report')){ ?>
                stateSave: true,
            <?php } else { ?>
                stateSave: false,
            <?php } ?>

            dom: "<'panel-body' <'pull-right'B><'pull-right'f><'pull-right progress-container'>l><'dt-information-summary1 text-info pull-left' i>t<'panel-body'<'pull-right'p><'dt-information-summary2 text-info pull-left' i>>",
            buttons: [
                {
                    extend: 'colvis',
                    postfixButtons: [ 'colvisRestore' ],
                    className:'btn btn-sm btn-linkwater'
                }
            ],
            columnDefs: [ {orderable: false,targets: [0] } ],
            order: [[ 6, 'desc' ]],
            drawCallback: function () {
                if ( dataTable.rows( { selected: true } ).indexes().length === 0 ) {
                    dataTable.buttons().disable();
                }
                else {
                    dataTable.buttons().enable();
                }
            }
        });

        $('#editPromoCode').on("blur", function(){
            //if it's not empty
            if($(this).val()!=''){
                $('.editPromoCMSCode').text($(this).val());
            }
        });

        $(".close-multiLang-modal").click (function () {
            if(confirm("Unsaved changes will disregard. Are you sure you want to close this?")){
                $(".multiLangWindow").modal('toggle');
                getPromoCmsDetails($('#promoCmsID').val(), true);
            }
        });

        $('.deletePromoCmsItem').on('click', function(){
            var val = $(this).attr('data-id');
            $('.deletePromoCmsId').val(val);
        });

        $(".edit_pormo_details").on('click', function(){
            $('html,body').animate({
                scrollTop: $("#promo_manager_container").offset().top - 100},
            'slow');
        });
    });

    function uploadImage(input,id) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function (e) {
                $('.upload_req_txt').hide();
                $('#'+id).attr('src', e.target.result).width(600).height(300);
                $('#banner_url').val(input.files[0].name);
                $('#editBannerUrl').val(input.files[0].name);
            };
            reader.readAsDataURL(input.files[0]);
        }
    }

    $("#edit_set_default_banner").on('change', function() {
        if ($(this).is(':checked')) {
            $(this).attr('value', 'true');
            $('.upload_req_txt').hide();
            $('.fileUpload').hide();
            $('#edit_upload_req_txt').hide();
            //show preset banner list
            $('.presetBannerType').show();

            $('#isEditDefaultBannerFlag').val(true);

            default_promo_cms_banner = "<?=$this->utils->imageUrl($this->utils->getConfig('default_promo_cms_banner_url')); ?>";
            $('#edit_promo_cms_banner_600x300').attr('src', default_promo_cms_banner).width(600).height(300);
        } else {
            $(this).attr('value', 'false');
            $('.upload_req_txt').show();
            $('.fileUpload').show();
            $('#edit_promo_cms_banner_600x300').attr('src','').width(600).height(300);

            //hide preset banner list
            $('.presetBannerType').hide();

            $('#isDefaultBannerFlag').val(false);
        }
    });

    //upload
    $("#userfile").on('change', function() {
        $('.upload_req_txt').hide();
    });

    $('[name="allow_claim_promo_in_promo_page"]').change(function(){
        if(!!parseInt($(this).val())){
            $('#claim_button_name').parent().hide();
            $('#claim_button_link').parent().hide();
        }else{
            $('#claim_button_name').parent().show();
            $('#claim_button_link').parent().show();
        }
        $('[name="claim_button_link"]').trigger('change');
    });
    $('[name="allow_claim_promo_in_promo_page"]:checked').trigger('change');

    $('[name="claim_button_link"]').change(function(){
        $('#claim_button_url').parent().hide();
        if(!parseInt($('[name="allow_claim_promo_in_promo_page"]:checked').val())){
            if( $("#claim_button_link option:selected").val() == 'custom' ){
                $('#claim_button_url').parent().show();
            }
        }
    });

    function showEditBannerPreview(){
        var bannerPreviewUrl = $('#edit_promo_cms_banner_600x300').attr('src');
        $('#preview_promo_cms_banner_600x300').attr('src',bannerPreviewUrl).width(600).height(300);
        $('.promoTitlePreview').text($('.editPromoTitleTxt').val().toUpperCase());
        $('.shortDescPreview').text($('.editPromoDescTxt').val());
    }

    function showEditPromoCmsPreview(){
        var bannerPreviewUrl = $('#edit_promo_cms_banner_600x300').attr('src');
        $('#preview_promo_cms_banner_600x300_big').attr('src',bannerPreviewUrl).width(600).height(300);
        $('.promoTitlePreview').text($('.editPromoTitleTxt').val().toUpperCase());
        $('.promoDetailsTxt').html($("#editPromoDetails").code());

        var promo_type_name = $("#editPromoCmsCategoryId option:selected").text();
        $('#promoTypeTxt').text(promo_type_name);

        if (enabled_multiple_type_tags_in_promotions) {
            $('.tagAsNewBadge').text('<?=strtoupper(lang("New")) ?>');
            switch ($('input[name=tagAsNewFlag][checked]').val()) {
                case '<?=Marketing_management::TAG_AS_NOTAG?>' :
                    $('.tagAsNewBadge').hide();
                    break;
                case '<?=Marketing_management::TAG_AS_NEW?>' :
                    $('.tagAsNewBadge').show().text('<?=strtoupper(lang("New")) ?>');
                    break;
                case '<?=Marketing_management::TAG_AS_FAVOURITE?>' :
                    $('.tagAsNewBadge').show();
                    $('.tagAsNewBadge').text('<?=strtoupper(lang('Favourite'))?>');
                    break;
                case '<?=Marketing_management::TAG_AS_ENDSOON?>' :
                    $('.tagAsNewBadge').show();
                    $('.tagAsNewBadge').text('<?=strtoupper(lang('End Soon'))?>');
                    break;
                default :
            }
        }else{
             if ($('#editTagAsNewFlag').is(':checked')) {
                $('.tagAsNewBadge').show();
            }else{
                $('.tagAsNewBadge').hide();
            }
        }
    }

    $('.editCmsPromoBtn').click(function(){
        $('#edit_upload_req_txt').hide();
    });

    $('#editPromoCmsCategoryId').change(function(){
        var promoCategoryId = $('#editPromoCmsCategoryId').val();
        var promorule = <?=json_encode($promorules)?>;
        $('#editPromoLink').val('');
        $('.showPromoRulesType').empty();
        $("#editPromoLink").empty();
        $("#editPromoLink").append($('<option>').text("-- <?=lang('cms.selpromolink');?> --"));

        $.each(promorule ,function (k,v) {
            if(v.promoCategory == promoCategoryId){
                if(v.used){
                    $("#editPromoLink").append($('<option>').val(v.promorulesId).text(v.promoName).attr('disabled','disabled'));
                } else {
                    $("#editPromoLink").append($('<option>').val(v.promorulesId).text(v.promoName));
                }
            } else {
                $("#editPromoLink option[value="+v.promorulesId+"]").remove();
            }
        });
    });

    $('#editPromoLink').change(function(){
        var promorulesId = $('#editPromoLink').val();

        if(promorulesId == ''){
            $('.showPromoRulesType').empty();
        }

        var promorule = <?=json_encode($promorules)?>;
        $.each(promorule ,function (k,v) {
            if(v.promorulesId == promorulesId){
                if(v.promoType == '<?=Promorules::PROMO_TYPE_DEPOSIT?>'){
                    $('.showPromoRulesType').text("<?=lang("cms.depPromo")?>");
                    $('#show_on_player_deposit').removeAttr("disabled");
                }else{
                    $('.showPromoRulesType').text("<?=lang("cms.nonDepPromo")?>");
                    $('#show_on_player_deposit').attr("disabled", true);
                }
            }
        });
    });

    var isEditPromoFlag = false;

    function resetPromoruleSettings(promorulId){
        var promorule = <?=json_encode($promorules)?>;
        $.each(promorule ,function (k,v) {
            if(v.promorulesId != promorulId){
                $("#editPromoLink option[value="+v.promorulesId+"]").addClass('hide');
            }
        });
    }

    function getPromoCmsDetails(promocmsId, multiLangOnly = false, selectedLang = null, promorulId = null) {
        is_editPanelVisible = true;
        flag_true = '1';

        if(promorulId){
            resetPromoruleSettings(promorulId);
            $('#editPromoLink>option[value="' + promorulId +'"]').removeAttr('disabled');
        }

        $('.edit_promocms_sec').show();
        $('#promoCmsID').val(promocmsId);
        targetUrl = _site_url + 'marketing_management/getPromoCmsItemDetails/' + promocmsId;

        $.ajax({
            'url': targetUrl,
            'type': 'GET',
            'dataType': "json",
            'success': function(data){
                if(multiLangOnly == false && selectedLang == null) {
                    $('#editPromoCode').val(data.promo_code);
                    $('#editPromocmsId').val(promocmsId);
                    $('#editPromoName').val(data.promoName);
                    <?php if ($this->utils->getConfig('enabled_promorulesorder')) { ?>
                        $('#editPromoOrder').val(data.promoOrder);
                    <?php } ?>

                    $('#u_visit_limit').val(parseInt(data.visit_limit));
                    $('#editPromoThumbnail').val(data.promoThumbnail);
                    $('#editLanguage').val(data.language);
                    $('.editPromoCMSCode').text(data.promo_code);

                    $("#editPromoCmsCategoryId").val(data.promo_category);

                    if(data.promoDepositType == '<?=Promorules::PROMO_TYPE_DEPOSIT?>'){
                        $('.showPromoRulesType').text("<?=lang("cms.depPromo")?>");
                        $('#show_on_player_deposit').removeAttr("disabled");
                    }else{
                        $('.showPromoRulesType').text("<?=lang("cms.nonDepPromo")?>");
                        $('#show_on_player_deposit').attr("disabled", true);
                    }

                    var show_on_player = parseInt(data.hide_on_player);
                    if (show_on_player == '<?=Promorules::SHOW_ON_PLAYER_PROMOTION?>') {
                        $('#show_on_player_promotion').prop('checked', true);
                    }else if(show_on_player == '<?=Promorules::SHOW_ON_PLAYER_DEPOSIT?>'){
                        $('#show_on_player_deposit').prop('checked', true);
                    }else if(show_on_player == '<?=Promorules::SHOW_ON_PLAYER_PROMOTION_AND_DEPOSIT?>'){
                        $('#show_on_player_promotion').prop('checked', true);
                        $('#show_on_player_deposit').prop('checked', true);
                    }else{
                        $('#show_on_player_promotion').prop('checked', false);
                        $('#show_on_player_deposit').prop('checked', false);
                    }

                    if (data.promo_code == null) {
                        $('.editPromoCodeLinkSec').hide();
                    } else {
                        $('.editPromoCodeLinkSec').show();
                    }

                    $('#editPromoDescription').val(data.promoDescription);
                    $("#editPromoDetails").next('.note-editor').find('.note-editable').html(_pubutils.decodeHtmlEntities(data.promoDetails, data.default_lang));
                    $('#editPromoLink').val(data.promoId);

                    if (data.promoThumbnail != null) {
                        if (data.is_default_banner_flag == flag_true) {
                            $('#edit_promo_cms_banner_600x300').attr('src', (_site_url + 'resources/images/promothumbnails/' + data.promoThumbnail));
                            $('#isEditDefaultBannerFlag').val('true');
                        } else {
                            $('#edit_promo_cms_banner_600x300').attr('src', ("<?=$uploadUri?>" + data.promoThumbnail));
                        }
                        $('#editBannerUrl').val(data.promoThumbnail);
                        $('#edit_upload_req_txt').hide();
                    } else {
                        $('#edit_promo_cms_banner_600x300').attr('src', (_site_url + 'resources/images/promothumbnails/default_promo_cms_1.jpg'));
                    }

                    if (data.is_default_banner_flag == flag_true) {

                        $('#edit_set_default_banner').prop('checked', true);
                        $('.fileUpload').hide();

                        //show preset banner list
                        $('.presetBannerType').show();
                    }

                    if (enabled_multiple_type_tags_in_promotions) {
                        switch (data.tag_as_new_flag) {
                            case '<?=Marketing_management::TAG_AS_NOTAG?>' :
                                $('#tagAsNoTag').prop('checked', true);
                                $('#tagAsNoTag').attr('checked', 'checked');
                                break;
                            case '<?=Marketing_management::TAG_AS_NEW?>' :
                                $('#tagAsNew').prop('checked', true);
                                $('#tagAsNew').attr('checked', 'checked');
                                break;
                            case '<?=Marketing_management::TAG_AS_FAVOURITE?>' :
                                $('#tagAsFavourite').prop('checked', true);
                                $('#tagAsFavourite').attr('checked', 'checked');
                                break;
                            case '<?=Marketing_management::TAG_AS_ENDSOON?>' :
                                $('#tagAsEndSoon').prop('checked', true);
                                $('#tagAsEndSoon').attr('checked', 'checked');
                                break;
                            case '<?=Marketing_management::TAG_AS_HOT?>' :
                                $('#tagAsHot').prop('checked', true);
                                $('#tagAsHot').attr('checked', 'checked');
                                break;
                            default :
                                $('#tagAsNew').prop('checked', true);
                                $('#tagAsNew').attr('checked', 'checked');
                        }
                    }else{
                        //set edit tag_as_new_flag
                        if (data.tag_as_new_flag == flag_true) {
                            $('#editTagAsNewFlag').prop('checked', true);
                            $('#isDefaultBannerFlag').val(true);
                        } else {
                            $('#editTagAsNewFlag').prop('checked', false);
                            $('#isDefaultBannerFlag').val(false);
                        }
                    }
                    if (!!parseInt(data.allow_claim_promo_in_promo_page)) {
                        $('[name="allow_claim_promo_in_promo_page"][value="1"]').prop('checked', true).trigger('change');
                    } else {
                        $('[name="allow_claim_promo_in_promo_page"][value="0"]').prop('checked', true).trigger('change');
                    }

                    if (!!parseInt(data.display_apply_btn_in_promo_page)) {
                        $('[name="display_apply_btn_in_promo_page"][value="1"]').prop('checked', true).trigger('change');
                    } else {
                        $('[name="display_apply_btn_in_promo_page"][value="0"]').prop('checked', true).trigger('change');
                    }

                    $("#claim_button_link").val(data.claim_button_link).trigger('change');
                    $("#claim_button_name").val(data.claim_button_name);
                    $("#claim_button_url").val(data.claim_button_url);
                }
                // OGP-5225
                var isEnabledMultiLangPromoMgr = "<?=$this->utils->isEnabledFeature('enable_multi_lang_promo_manager')?>";
                if(isEnabledMultiLangPromoMgr){
                    var promoMultiLangData = JSON.parse(data.promo_multi_lang);
                    if(promoMultiLangData){
                        $.each(promoMultiLangData, function(index, value){
                            $.each(value, function(index2, value2){
                                var promo_title_key = "promo_title_" + index2;
                                var short_desc_key = "short_desc_" + index2;
                                var details_key = "details_" + index2;
                                var banner_key = "banner_"+index2;

                                $("#" + promo_title_key).val(value2[promo_title_key]);
                                $("#" + short_desc_key).val(value2[short_desc_key]);
                                $("#" + details_key).next('.note-editor').find('.note-editable').html(_pubutils.decodeHtmlEntities(value2[details_key], index2));
                                $("#"+banner_key).val(value2[banner_key]);
                                var bannerSrcPath = "<?=$uploadUri?>";
                                if(value2[banner_key]){

                                    $('img[id^="uploadimg_'+index2+'"]').attr("src",bannerSrcPath+value2[banner_key]).show();
                                } else {
                                     $('img[id^="uploadimg_'+index2+'"]').attr("src", "").hide();
                                }

                                if(index2 == selectedLang){
                                    $('#editPromoName').val(value2[promo_title_key]);
                                    $('#editPromoDescription').val(value2[short_desc_key]);
                                    $("#editPromoDetails").next('.note-editor').find('.note-editable').html(_pubutils.decodeHtmlEntities(value2[details_key], selectedLang));

                                    if( value2[banner_key] != null ) {
                                        $('#edit_promo_cms_banner_600x300').attr('src',bannerSrcPath+value2[banner_key]);
                                        $('#editBannerUrl').val(value2[banner_key]);
                                    } else {
                                        $('#edit_promo_cms_banner_600x300').attr('src', ("<?=$uploadUri?>" + value2[banner_key]));
                                    }
                                }
                            });
                        });
                    } else{
                        clearMultiLangPromo();
                    }

                    if(multiLangOnly == false && selectedLang == null) {
                        $('#edit_promo_item_default_lang option[value=' + data.default_lang + ']').prop('selected', true);
                    }
                    isEditPromoFlag = true;

                    // set multi lang data to form from db
                    saveMultiLangData(false);
                }
            }
        }, 'json');
        return false;
    }

    //for ranking level edit form
    var is_editPanelVisible = false;
    $('.edit_promocms_sec').hide();

    //show hide add promo cms panel
    $("#add_new_promo_setting").click(function(){
        // resetPromoruleSettings();
        resetAddNewPromoCmsView();
        clearMultiLangPromo();

        if(!is_editPanelVisible){
            is_editPanelVisible = true;
            $('.edit_promocms_sec').show();
        }else{
            is_editPanelVisible = false;
            $('.edit_promocms_sec').hide();
        }
    });

    //cancel edit promo
    $(".editcmspromo-cancel-btn").click(function () {
        is_editPanelVisible = false;
        $('.edit_promocms_sec').hide();
    });

    $('.random-code').click(function(){
        randomPromoCodeWithAjax('',function(promoCode){
            $('.editPromoCode').val(promoCode);
            $('.editPromoCMSCode').text(promoCode);
        })
    });

    /**
     * random generated promo code from server
     * @param string promoCode The promo code, if empty than generated by server else confirm unique for return.
     * @param script|function doneCB The script on ajax.done().
     */
    function randomPromoCodeWithAjax(promoCode, doneCB){

        if( typeof(doneCB) === 'undefined'){
            doneCB = function(){};
        }
        targetUrl = _site_url + 'marketing_management/getNewPromoCode/' + promoCode;

        $('.promocode-loading').removeClass('hide');

        var ajax = $.ajax({
            'url': targetUrl,
            'type': 'GET',
            'dataType': "json",
            'success': function(data){
                if(data.status == 'ok'){
                    doneCB(data.newPromoCode);
                }
            }
        }).always(function(){
            $('.promocode-loading').addClass('hide');
        });
        return ajax;
    }// EOF randomPromoCodeWithAjax

    function resetAddNewPromoCmsView(){
        //hide the preset banner initially
        $('#editPromocmsId').val('');
        $('.showPromoRulesType').empty();
        $('#editPromoCmsCategoryId').val('');
        $('#editPromoLink').val('');

        randomPromoCodeWithAjax('',function(promoCode){
            $('.editPromoCode').val(promoCode);
            $('.editPromoCMSCode').text(promoCode);
        })
        // randomCode('8', 'true');

        $('#show_on_player_promotion').trigger('click');

        $('#editPromoName').val('');
        $('#editPromoDescription').val('');
        $('#editPromoDetails').code('');
        <?php if ($this->utils->getConfig('enabled_promorulesorder')) { ?>
            $('#editPromoOrder').val('');
        <?php } ?>

        $('#edit_set_default_banner').prop('checked', true).trigger('change');

        if (enabled_multiple_type_tags_in_promotions) {
            $('#tagAsNew').prop('checked', true);
        }else{
            $('#editTagAsNewFlag').prop('checked', true);
        }

        $('#show_on_player_promotion').prop('checked', true);
        $('#show_on_player_deposit').prop('checked', false);

        $('[name="allow_claim_promo_in_promo_page"][value="1"]').prop('checked', true).trigger('change');
        $('[name="display_apply_btn_in_promo_page"][value="1"]').prop('checked', true);
    }

    //hide the preset banner initially
    $('.presetBannerType').hide();

    function setBannerImg(item,bannerId){
        bannerType = item.id;
        if(bannerId == 'promo_cms_banner_600x300'){
            $('#isDefaultBannerFlag').val(true);
            $('#banner_url').val(bannerType+".jpg");
        }else{
            $('#isEditDefaultBannerFlag').val(true);
            $('#editBannerUrl').val(bannerType+".jpg");
            $('#edit_upload_req_txt').hide();
        }

        $('#'+bannerId).attr('src',(_site_url+'resources/images/promo_cms/'+bannerType+'.jpg'));
    }

    $('#edit_promo_item_default_lang').change(function(){
        getPromoCmsDetails($('#promoCmsID').val(),false, $(this).val());
    });

    $("#edit_promo_submit").click(function () {
        var promoLink = $('#editPromoLink').val();
        var promoName = $('.editPromoTitleTxt').val();
        var promoDescription = $('.editPromoDescTxt').val();
        var promoCategory =$('#editPromoCmsCategoryId').val();
        var notValidate = false;

        var promoDetails = $("#editPromoDetails").code();
        var encodePromoDetails = encode64(encodeURIComponent(promoDetails));
        var promoDetailsLength = encodePromoDetails.length;
        $("#editPromoDetails").code(encodePromoDetails);
        $("#promoDetailsLength").val(promoDetailsLength);

        $(".error-promoLink").hide();
        $(".error-promoName").hide();
        $(".error-promoDescription").hide();
        $(".error-promoDetails").hide();
        $('.error-editPromoCmsCategoryId').hide();

        if(isNaN(parseInt(promoLink))) {
            $(".error-editPromoLink").show();
            notValidate = true;
        }

        if(promoName.length == 0) {
            $(".error-editpromoName").show();
            notValidate = true;
        }

        if(promoDescription.length == 0) {
            $(".error-editPromoDescription").show();
            notValidate = true;
        }

        if(promoDetails.length == 0) {
            $(".error-editPromoDetails").show();
            notValidate = true;
        }

        if(promoCategory.length == 0) {
            $(".error-editPromoCmsCategoryId").show();
            notValidate = true;
        }

        if(notValidate){
            // OGP-22312: restore uncoded text to editPromoDetails if validation fails
            var promoDetailsDecoded = decode64(encodePromoDetails);
            $('#editPromoDetails').code(promoDetailsDecoded);

            alert("<?=lang('con.d02')?>");

        } else {

            $("#edit_promo_submit").attr('disabled','disabled');
            submitMultiLangBannerUpload();
            saveMultiLangData(true);

        }

        return false;
    });

    function clearMultiLangPromo(){
        $("[id^='promo_title_']").val("");
        $("[id^='short_desc_']").val("");
        $("[id^='details_']").code("");
        $("input[id^='banner_']").val("");
        $("img[id^='uploadimg_']").attr("src", "").hide();
    }

    // OGP-5225
    $("#saveMultiLangBtn").click(function () {
        saveMultiLangData(false);
    });

    function saveMultiLangData(isSubmit){
        var promoTitleEn = $('#promo_title_en').val();
        var shortDescEn = $('#short_desc_en').val();
        var detailsEn = $('#details_en').code();
        detailsEn = encode64(encodeURIComponent(detailsEn));
        var bannerEn = $('#banner_en').val();

        var promoTitleCh = $('#promo_title_ch').val();
        var shortDescCh = $('#short_desc_ch').val();
        var detailsCh = $('#details_ch').code();
        detailsCh = encode64(encodeURIComponent(detailsCh));
        var bannerCh = $('#banner_ch').val();

        var promoTitleKr = $('#promo_title_kr').val();
        var shortDescKr = $('#short_desc_kr').val();
        var detailsKr = $('#details_kr').code();
        detailsKr = encode64(encodeURIComponent(detailsKr));
        var bannerKr = $('#banner_kr').val();

        var promoTitleId = $('#promo_title_id').val();
        var shortDescId = $('#short_desc_id').val();
        var detailsId = $('#details_id').code();
        detailsId = encode64(encodeURIComponent(detailsId));
        var bannerId = $('#banner_id').val();

        var promoTitleVn = $('#promo_title_vn').val();
        var shortDescVn = $('#short_desc_vn').val();
        var detailsVn = $('#details_vn').code();
        detailsVn = encode64(encodeURIComponent(detailsVn));
        var bannerVn = $('#banner_vn').val();

        var promoTitleTh = $('#promo_title_th').val();
        var shortDescTh = $('#short_desc_th').val();
        var detailsTh = $('#details_th').code();
        detailsTh = encode64(encodeURIComponent(detailsTh));
        var bannerTh = $('#banner_th').val();

        var promoTitleIn = $('#promo_title_in').val();
        var shortDescIn = $('#short_desc_in').val();
        var detailsIn = $('#details_in').code();
        detailsIn = encode64(encodeURIComponent(detailsIn));
        var bannerIn = $('#banner_in').val();

        var promoTitlePt = $('#promo_title_pt').val();
        var shortDescPt = $('#short_desc_pt').val();
        var detailsPt = $('#details_pt').code();
        detailsPt = encode64(encodeURIComponent(detailsPt));
        var bannerPt = $('#banner_pt').val();

        var promoTitleEs = $('#promo_title_es').val();
        var shortDescEs = $('#short_desc_es').val();
        var detailsEs = $('#details_es').code();
        detailsEs = encode64(encodeURIComponent(detailsEs));
        var bannerEs = $('#banner_es').val();

        var promoTitleKk = $('#promo_title_kk').val();
        var shortDescKk = $('#short_desc_kk').val();
        var detailsKk = $('#details_kk').code();
        detailsKk = encode64(encodeURIComponent(detailsKk));
        var bannerKk = $('#banner_kk').val();


        var promoTitleJa= $('#promo_title_ja').val();
        var shortDescJa = $('#short_desc_ja').val();
        var detailsJa = $('#details_ja').code();
        detailsJa = encode64(encodeURIComponent(detailsJa));
        var bannerJa = $('#banner_ja').val();

        var promoTitleHk = $('#promo_title_hk').val();
        var shortDescHk = $('#short_desc_hk').val();
        var detailsHk = $('#details_hk').code();
        detailsHk = encode64(encodeURIComponent(detailsHk));
        var bannerHk = $('#banner_hk').val();

        var promoTitlePh = $('#promo_title_ph').val();
        var shortDescPh = $('#short_desc_ph').val();
        var detailsPh = $('#details_ph').code();
        detailsPh = encode64(encodeURIComponent(detailsPh));
        var bannerPh = $('#banner_ph').val();

        
        var postUrl = "<?=site_url('marketing_management/savePromoCmsMultiLang')?>";
        var postData = {
            promo_title_en : promoTitleEn,
            short_desc_en : shortDescEn,
            details_en : detailsEn,
            banner_en : bannerEn,

            promo_title_ch : promoTitleCh,
            short_desc_ch : shortDescCh,
            details_ch : detailsCh,
            banner_ch : bannerCh,

            promo_title_kr : promoTitleKr,
            short_desc_kr : shortDescKr,
            details_kr : detailsKr,
            banner_kr : bannerKr,

            promo_title_id : promoTitleId,
            short_desc_id : shortDescId,
            details_id : detailsId,
            banner_id : bannerId,

            promo_title_vn : promoTitleVn,
            short_desc_vn : shortDescVn,
            details_vn : detailsVn,
            banner_vn : bannerVn,

            promo_title_th : promoTitleTh,
            short_desc_th : shortDescTh,
            details_th : detailsTh,
            banner_th : bannerTh,

            promo_title_in : promoTitleIn,
            short_desc_in : shortDescIn,
            details_in : detailsIn,
            banner_in : bannerIn,

            promo_title_pt : promoTitlePt,
            short_desc_pt : shortDescPt,
            details_pt : detailsPt,
            banner_pt : bannerPt,

            promo_title_es : promoTitleEs,
            short_desc_es : shortDescEs,
            details_es : detailsEs,
            banner_es : bannerEs,

            promo_title_kk : promoTitleKk,
            short_desc_kk : shortDescKk,
            details_kk : detailsKk,
            banner_kk : bannerKk,

            promo_title_ja: promoTitleJa,
            short_desc_ja : shortDescJa,
            details_ja : detailsJa,
            banner_ja : bannerJa,

            promo_title_hk : promoTitleHk,
            short_desc_hk : shortDescHk,
            details_hk : detailsHk,
            banner_hk : bannerHk,

            promo_title_ph : promoTitlePh,
            short_desc_ph : shortDescPh,
            details_ph : detailsPh,
            banner_ph : bannerPh,

        };

        if(isSubmit == true) {
            $.post(postUrl, postData, function(data){
                $("#form-editcmspromo").submit();
            });
        } else {
            $.post(postUrl, postData, function(data){});
        }
    }

    // OGP-5225
    function setPromoItemDefaultLanguage(){
        // $("#panel_en,#panel_ch,#panel_kr,#panel_id,#panel_vn,#panel_th,#panel_in,#panel_pt").show();
        $("div[id^='panel_']").show();
        var selected_default_lang;
        if(typeof (isEditPromoFlag) !== 'undefined'){
            selected_default_lang = $('#edit_promo_item_default_lang option:selected').val();
        }else{
            selected_default_lang = $('#promo_item_default_lang option:selected').val();
        }
        $("div[id^='panel_"+selected_default_lang+"']").hide();
        $('.multiLangWindow').modal({backdrop: 'static', keyboard: false});
    }

    function readURL() {
        var targetImg = "#"+this.getAttribute("data-image-upload");
        //  rehide the image and remove its current "src",
        //  this way if the new image doesn't load,
        //  then the image element is "gone" for now
        $(targetImg).attr('src', '').hide();
        if (this.files && this.files[0]) {
            var reader = new FileReader();
            $(reader).load(function(e) {
                $(targetImg)
                    //  first we set the attribute of "src" thus changing the image link
                    .attr('src', e.target.result)   //  this will now call the load event on the image

                var targetLang = targetImg.substr(targetImg.indexOf('_') + 1);
                var uploadedFileName = $("#upload_imgbnr_btn_"+targetLang).val().replace(/C:\\fakepath\\/i, '');
                var uploadedFileNameExt = uploadedFileName.substr(uploadedFileName.indexOf('.') + 1);
                var fileName = "";

                var uploadedBannerName = $("input[id='banner_"+targetLang+"']").val();
                if(uploadedBannerName == ""){
                    fileName = "promomgr_item_"+Math.random().toString(36).substring(7);
                }else{
                    fileName = uploadedBannerName.substr(0, uploadedBannerName.indexOf('.'));
                }
                $("input[id='banner_"+targetLang+"']").val(fileName+"."+uploadedFileNameExt);
            });
            reader.readAsDataURL(this.files[0]);

        }
    }

    // $('#uploadimg_ch,#uploadimg_en,#uploadimg_id,#uploadimg_kr,#uploadimg_vn,#uploadimg_th,#uploadimg_in, #uploadimg_pt')
    $('img[id^="uploadimg_"]').load(function(e) {
            $(this).css('height', '300px') .show();
        }).hide();

    // $("#upload_imgbnr_btn_ch,#upload_imgbnr_btn_en,#upload_imgbnr_btn_id,#upload_imgbnr_btn_kr,#upload_imgbnr_btn_vn,#upload_imgbnr_btn_th,#upload_imgbnr_btn_in, #upload_imgbnr_btn_pt").change(readURL);
    $('[id^="upload_imgbnr_btn_"]').change(readURL);

    function submitMultiLangBannerUpload(){
        $(".loader").show();
        var uploadImgbnrForm = $('form[id^="upload_imgbnr_form_"]');
        if(uploadImgbnrForm.length){
            uploadImgbnrForm.each(function(key, form){
                var bannerName = $(form).children('input[id^=banner_]').val();
                var bannerObj = $(form).children('input[id^=banner_]');
                var upload_imgbnr_btn = $(form).children('input[id^=upload_imgbnr_btn_]');
                if(bannerName != "" && upload_imgbnr_btn[0].files.length > 0){
                    var formData = new FormData(form);
                    formData.append('file', userfile);
                    uploadMultiLangBanner(bannerName,formData, bannerObj);
                }
            });
        }
    }

    // multi upload loader
    $('.loader').hide();
    function uploadMultiLangBanner(name,formData, obj){
        $.ajax({
            url: "<?=site_url('marketing_management/uploadBannerMultiLang')?>",
            data: formData,
            cache: false,
            contentType: false,
            processData: false,
            type: 'POST',
            async: false,
            success: function(data){
                var result = JSON.parse(data);
                $(obj).val(result.filename);
                $(".loader").show();
            },
            beforeSend: function(){
                $(".loader").show();
            }
        });
    }

    //single select id
    function deletePromoCmsItem(){
        var del_id = $('.deletePromoCmsId').val();
        var url = "<?=site_url('marketing_management/deletePromoCmsItem')?>" + "/" + del_id;
        $.post(url, {del_id}).done(function(data){
            if(data && data.success){
                window.location.reload();
            }
        }).fail(function(){
            alert('Delete Promo Cms Failed');
        });

    }

    var keyStr = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=";
    //turn string to base64 encode string
    function encode64(input) {
        var output = "";
        var chr1, chr2, chr3 = "";
        var enc1, enc2, enc3, enc4 = "";
        var i = 0;

        do {
            chr1 = input.charCodeAt(i++);
            chr2 = input.charCodeAt(i++);
            chr3 = input.charCodeAt(i++);

            enc1 = chr1 >> 2;
            enc2 = ((chr1 & 3) << 4) | (chr2 >> 4);
            enc3 = ((chr2 & 15) << 2) | (chr3 >> 6);
            enc4 = chr3 & 63;

            if (isNaN(chr2)) {
                enc3 = enc4 = 64;
            } else if (isNaN(chr3)) {
                enc4 = 64;
            }

            output = output +
                keyStr.charAt(enc1) +
                keyStr.charAt(enc2) +
                keyStr.charAt(enc3) +
                keyStr.charAt(enc4);
            chr1 = chr2 = chr3 = "";
            enc1 = enc2 = enc3 = enc4 = "";
        } while (i < input.length);

        return output;
    }

    /**
     * Decodes base64 string to UTF-8 string, compatibility wrapper
     * Will use _decode64_modern() if atob() is available; otherwise _decode64_legacy() is used, OGP-22312
     * @param   string  data    base64 string
     * @return  string  UTF-8 compatible string
     */
    function decode64(data) {
        if (typeof(atob) == 'function') {
            return _decode64_modern(data);
        }

        return _decode64_legacy(data);
    }

    /**
     * Decodes base64 string to UTF-8 string, modern version
     * Do not use directly; use decode64() instead, OGP-22312
     * Uses atob() function built in most modern browsers
     * @param   string  data    base64 string
     * @return  string  UTF-8 compatible string
     */
    function _decode64_modern(data) {
        var decoded = decodeURIComponent(atob(data));
        return decoded;
    }

    /**
     * Decodes base64 string to utf-8 string, legacy version
     * Do not use directly; use decode64() instead, OGP-22312
     * Adapted from https://simplycalc.com/base64-source.php
     * @param   string  data    base64 string
     * @return  string  UTF-8 compatible string
     */
    function _decode64_legacy(data) {
        var b64pad = '=';
        var b64u = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-_";

        function base64_charIndex(c) {
            if (c == "+") return 62;
            if (c == "/") return 63;
            return b64u.indexOf(c);
        }

        var dst = "";
        var i, a, b, c, d, z;

        for (i = 0; i < data.length - 3; i += 4) {
            a = base64_charIndex(data.charAt(i+0));
            b = base64_charIndex(data.charAt(i+1));
            c = base64_charIndex(data.charAt(i+2));
            d = base64_charIndex(data.charAt(i+3));

            dst += String.fromCharCode((a << 2) | (b >>> 4));
            if (data.charAt(i+2) != b64pad) {
                dst += String.fromCharCode(((b << 4) & 0xF0) | ((c >>> 2) & 0x0F));
            }
            if (data.charAt(i+3) != b64pad) {
                dst += String.fromCharCode(((c << 6) & 0xC0) | d);
            }
        }

        // dst = decodeURIComponent(escape(dst));
        dst = decodeURIComponent(dst);
        return dst;
    }

    function activatePromo(url) {
        if (confirm('<?php echo lang('Do you want activate this promo?'); ?>')) {
            window.location.href = url;
        }
    }

    function deactivatePromo(url) {
        if (confirm('<?php echo lang('Do you want deactivate this promo?'); ?>')) {
            window.location.href = url;
        }
    }
</script>
