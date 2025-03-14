<style type="text/css">
textarea {
    resize: none;
}
/* The switch - the box around the slider */

.switch {
  position: relative;
  display: inline-block;
  height: 34px;
  top: 19px;
  left: 0;
}

/* Hide default HTML checkbox */
.switch input {display:none;}

/* The slider */
.slider {
  position: absolute;
  cursor: pointer;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: #ccc;
  -webkit-transition: .4s;
  transition: .4s;
  width: 60px;
}

.slider:before {
  position: absolute;
  content: "";
  height: 26px;
  width: 26px;
  left: 4px;
  bottom: 4px;
  background-color: white;
  -webkit-transition: .4s;
  transition: .4s;
}

input:checked + .slider {
  background-color: #2196F3;
}

input:focus + .slider {
  box-shadow: 0 0 1px #2196F3;
}

input:checked + .slider:before {
  -webkit-transform: translateX(26px);
  -ms-transform: translateX(26px);
  transform: translateX(26px);
}

/* Rounded sliders */
.slider.round {
  border-radius: 34px;
}

.slider.round:before {
  border-radius: 50%;
}

.btn-radius {
    border-radius: 5px;
}
.switch-btn{
    display: inline-block;
    width: 10%;
}
.promo-text{
    display: inline-block;
}
.action-item {
    margin-bottom: 0.5rem;
}

/*start css OGP-17805*/
#add_quest_category .row,
#edit_quest_category .row{
  margin: 15px -15px;
}
#add_quest_category .row [class^='col-'],
#edit_quest_category .row [class^='col-']{
  padding: 0 15px;
}
#add_quest_category .upload-quest-img,
#edit_quest_category .upload-quest-img{
  border: 2px dashed #CCCCCC;
  /* width: 100%; */
  height: 170px;
  padding: 14px;
}
/* #add_quest_category .filQuestCatIcon,
#edit_quest_category .filEditQuestCatIcon{
  cursor: pointer;
  position: relative;
  display: block;
  padding: 8px 0;
} */
#add_quest_category .upload-quest-img-btn p,
#edit_quest_category .upload-quest-img-btn p{
  margin: 15px 0;
  font-size: 16px;
  color: #BABABA;
  line-height: 2;
}
#add_quest_category .clearQuestCatIcon,
#edit_quest_category .clearQuestCatIcon{
  position: absolute;
  right: 25px;
  top: 25px;
  z-index: 9;
  opacity: 0.7;
  background: #fff;
  border-radius: 50px;
  line-height: 20px;
  height: 20px;
}
.display-css{
  font-size: 13px;
}
.label-css{
    font-size: 15px;
}
.btn-css {
    width:100%;
    padding:5px;
    height: 23px;
    line-height: 12px;
    overflow: hidden;
}

.fileUpload input.upload {
     position: absolute;
     top: 0;
     right: 0;
     margin: 0;
     padding: 0;
     font-size: 20px;
     cursor: pointer;
     opacity: 0;
     filter: alpha(opacity=0);
}
.quest_icon_80x80{
        align: left; valign: middle; width: 80px; height: 80px; margin: 0 1px 0 0; display:block;
    }
/*end css OGP-17805*/

</style>
<!--Add Quest Category-->
<form id="add_category_form">
    <div id="add_quest_category" class="modal fade" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                    </button>
                    <h5 class="modal-title"><?=lang('cms.addNewQuestCategory');?></h5>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group required">
                                        <label for="questCatecoryTitleView" class="col-sm-3 control-label label-css"><?=lang('cms.Name');?>:<span class="text-danger"></span></label>
                                        <div class="col-sm-9">
                                            <input type="text" name="questCatecoryTitleView" class="form-control input-sm" id="questCatecoryTitleView">
                                        </div>
                                        <input type="hidden" id="questCatecoryTitle" name="questCatecoryTitle" class="form-control input-sm">
                                        <span class="text-danger help-block m-b-0" id="addNameViewRequired" hide>*<?=lang('cms.Column is required');?></span>
                                        <span class="text-danger help-block m-b-0" id="addNameViewMaxCharacters" hide>*<?=lang('cms.Maximum 24 characters');?></span>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="questCategoryOrderId" class="col-sm-3 control-label label-css"><?=lang('cms.Sort');?>:</label>
                                        <div class="col-sm-9">
                                            <input type="text" name="questCategoryOrderId" class="form-control input-sm" id="questCategoryOrderId">
                                        </div>
                                        <span class="text-danger help-block m-b-0" id="addOrderOnlyAllowDigits" hide></span>
                                        <span class="text-danger help-block m-b-0" id="addOrderMaxCharacters" hide>*<?=lang('cms.Maximum 3 characters');?></span>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group required">
                                        <label for="questCategoryStatus" class="col-sm-3 control-label label-css"><?=lang('cms.Status')?>:<span class="text-danger"></span></label>
                                        <div class="col-sm-9">
                                            <input type="radio" name="questCategoryStatus" class="input-control" value="1" style="margin-right: 5;"><?=lang('cms.active')?>
                                            <input type="radio" name="questCategoryStatus" class="input-control" value="0" checked="checked" style="margin-right: 5;"><?=lang('cms.inactive')?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group required">
                                        <label for="showTimer" class="col-sm-3 control-label label-css"><?=lang('cms.showTimer')?>:<span class="text-danger"></span></label>
                                        <div class="col-sm-9">
                                            <input type="radio" name="showTimer" class="input-control" value="1" onclick = "changeShowTimer('')" style="margin-right: 5;"><?=lang('Yes')?>
                                            <input type="radio" name="showTimer" class="input-control" value="0" onclick = "changeShowTimer('')" checked style="margin-right: 5;"><?=lang('No')?>
                                            <div id = "showTimerSetting">
                                                <div style = "display: flex;align-items: center;">
                                                    <label for="startDate" class="col-sm-5 control-label label-css" style = "padding: 18px 0;"><?=lang('cms.startTime')?>:</label>
                                                    <input type = "text"  name="startDate" class="form-control input-sm dateInput" id="startDate">
                                                </div>
                                                <div style = "display: flex;align-items: center;">
                                                    <input type = "time"  name="startTime" class="form-control input-sm" id="startTime">
                                                </div>
                                                <div style = "display: flex;align-items: center;">
                                                    <label for="endDate" class="col-sm-5 control-label label-css" style = "padding: 18px 0;"><?=lang('cms.endTime')?>:</label>
                                                    <input type = "text"  name="endDate" class="form-control input-sm dateInput" id="endDate">
                                                </div>
                                                <div style = "display: flex;align-items: center;">
                                                    <input type = "time"  name="endTime" class="form-control input-sm" id="endTime">
                                                </div>
                                                <span class="text-danger help-block" id="addDate" hide>* 日期輸入錯誤</span>
                                                <span class="text-danger help-block m-b-0" id="addDateViewRequired" hide>*Date is required.</span>
                                            </div>
                                        </div>
                                        
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group required">
                                        <label for="coverQuestTime" class="col-sm-6 control-label label-css"><?=lang('cms.Need to cover Mutiple quest')?>:<span class="text-danger"></span></label>
                                        <div class="col-sm-6">
                                            <input type="radio" name="coverQuestTime" class="input-control" value="1" checked="checked" style="margin-right: 5;"><?=lang('Yes')?>
                                            <input type="radio" name="coverQuestTime" class="input-control" value="0" <?=$this->config->item('disable_quest_category_override_mnanger_countdown') ? 'disabled' : ''?> style="margin-right: 5;"><?=lang('No')?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group required">
                                        <label for="period" class="col-sm-3 control-label label-css"><?=lang('cms.period')?>:</label>
                                        <div class="col-sm-8">
                                            <input type="radio" name="period" class="input-control" value="999" style="margin-right: 5;" checked><?=lang('cms.None')?>
                                            <input type="radio" name="period" class="input-control" value="1" style="margin-right: 5;"><?=lang('cms.everyday')?>
                                            <input type="radio" name="period" class="input-control" value="2" style="margin-right: 5;"><?=lang('cms.everyweek')?>
                                            <input type="radio" name="period" class="input-control" value="3" style="margin-right: 5;"><?=lang('cms.everymonth')?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group required">
                                        <label class="col-sm-3 control-label label-css">icon:<span class="text-danger"></span><p>(size:80*80)</p></label>
                                        <div class="col-md-9">
                                            <div class="quest_icon_sec upload_btn_sec">
                                                <div class="fileUpload btn btn-md btn-info">
                                                    <span><?=lang("Upload") ?></span>
                                                    <input type="file" name="userfile[]" class="upload" id="userfile" onchange="uploadImage(this,'quest_icon_80x80');">
                                                </div>
                                            </div>
                                            <div class="quest_icon_sec">
                                                <h6></h6>
                                                <div class="banner_container">
                                                    <img id="quest_icon_80x80" class="quest_icon_80x80"/>
                                                </div>
                                            </div>
                                            <span>(jpeg, jpg, png)</span> 
                                            <input type="hidden" name="icon_url" id="IconUrl" class="form-control" readonly>
                                            <input type="hidden" name="editQuestThumbnail" id="editQuestThumbnail" >
                                            <input type="hidden" name="is_default_banner_flag" id="isEditDefaultBannerFlag" class="form-control" readonly>
                                            <span class="text-danger help-block m-b-0" id="addImgViewRequired" hide>*Icon is required.</span>
                                        </div>                                        
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="questCategoryDesc" class="control-label"><?=lang('cms.Quest Remark');?>:</label><br>
                                <textarea name="questCategoryDesc" id="questCategoryDesc" class="form-control input-sm" style="width:100%" rows="10" required="" aria-invalid="true"></textarea>
                                <!-- <span class="text-danger help-block" id="addDescMaxCharacters" hide>*<?=lang('cms.Maximum 60 characters');?></span> -->
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-linkwater" data-dismiss="modal"><?=lang('lang.cancel');?></button>
                    <button type="button" class="btn btn-scooter" id="saveAddQuestCategory"><?=lang('lang.save');?></button>
                </div>
            </div>
        </div>
    </div>
</form>
<!--Edit Quest Category-->
<form id="edit_category_form">
    <div id="edit_quest_category" class="modal fade" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                    </button>
                    <h5 class="modal-title" id = "edit_from_title"><?=lang('cms.editQuestCategory');?></h5>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group required">
                                        <label for="editquestCategoryTitleView" class="col-sm-3 control-label label-css"><?=lang('cms.Name');?>:<span class="text-danger"></span></label>
                                        <div class="col-sm-9">                                        
                                            <input type="text" name="editquestCategoryTitleView" class="form-control input-sm" id="editquestCategoryTitleView">
                                        </div>
                                        <input type="hidden" name="questCategoryId" class="form-control" id="questCategoryId">
                                        <input type="hidden" id="editquestCategoryTitle" name="editquestCategoryTitle" class="form-control input-sm">
                                        <span class="text-danger help-block m-b-0" id="editNameViewRequired" hide>*<?=lang('cms.Column is required');?></span>
                                        <span class="text-danger help-block m-b-0" id="editNameViewMaxCharacters" hide>*<?=lang('cms.Maximum 24 characters');?></span>
                                    </div>
                                </div>
                            </div>
                            <div class = "row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="editquestCategoryOrderId" class="col-sm-3 control-label label-css"><?=lang('cms.Sort');?>:<span class="text-danger"></span></label>
                                        <div class="col-sm-9">
                                            <input type="text" name="editquestCategoryOrderId" class="form-control input-sm" id="editquestCategoryOrderId" onkeyup="value=value.match(/^[0-9]\d*$/)">
                                        </div>
                                        <span class="text-danger help-block m-b-0" id="editOrderOnlyAllowDigits" hide></span>
                                        <span class="text-danger help-block m-b-0" id="editOrderMaxCharacters" hide>*<?=lang('cms.Maximum 3 characters');?></span>
                                    </div>
                                </div>                                
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group required">
                                        <label for="editquestCategoryStatus" class="col-sm-3 control-label label-css"><?=lang('cms.Status');?>:<span class="text-danger"></span></label>
                                        <div class="col-sm-9">
                                            <input type="radio" name="editquestCategoryStatus" class="input-control" value="1" style="margin-right: 5;"><?=lang('cms.active');?>
                                            <input type="radio" name="editquestCategoryStatus" class="input-control" value="0" checked="checked" style="margin-right: 5;"><?=lang('cms.inactive');?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <div class = "questCategoryContent col-sm-12 hide">
                                            <p><?=lang($questCategoryContent)?></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group required">
                                        <label for="editshowTimer" class="col-sm-3 control-label label-css"><?=lang('cms.showTimer')?>:<span class="text-danger"></span></label>
                                        <div class="col-sm-9">
                                            <input type="radio" name="editshowTimer" class="input-control" value="1" onclick = "changeShowTimer('edit')" style="margin-right: 5;"><?=lang('Yes')?>
                                            <input type="radio" name="editshowTimer" class="input-control" value="0" onclick = "changeShowTimer('edit')" checked style="margin-right: 5;"><?=lang('No')?>
                                            <div id = "editshowTimerSetting">
                                                <div style = "display: flex;align-items: center;">
                                                    <label for="editstartDate" class="col-sm-5 control-label label-css" style = "padding: 18px 0;"><?=lang('cms.startTime')?>:</label>
                                                    <input type = "text"  name="editstartDate" class="form-control input-sm" id="editstartDate">
                                                </div>
                                                <div style = "display: flex;align-items: center;">
                                                    <input type = "time"  name="editstartTime" class="form-control input-sm" id="editstartTime">
                                                </div>
                                                <div style = "display: flex;align-items: center;">
                                                    <label for="editendDate" class="col-sm-5 control-label label-css" style = "padding: 18px 0;"><?=lang('cms.endTime')?>:</label>
                                                    <input type = "text"  name="editendDate" class="form-control input-sm" id="editendDate">
                                                </div>
                                                <div style = "display: flex;align-items: center;">
                                                    <input type = "time"  name="editendTime" class="form-control input-sm" id="editendTime">
                                                </div>
                                                <span class="text-danger help-block" id="editDate" hide>* 日期輸入錯誤</span>
                                                <span class="text-danger help-block m-b-0" id="editDateViewRequired" hide>*Date is required.</span>
                                            </div>
                                        </div>                                        
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group required">
                                        <label for="editperiod" class="col-sm-3 control-label label-css"><?=lang('cms.period')?>:<span class="text-danger"></span></label>
                                        <div class="col-sm-8">
                                            <input type="radio" name="editperiod" class="input-control" value="999" style="margin-right: 5;" checked><?=lang('cms.None')?>
                                            <input type="radio" name="editperiod" class="input-control" value="1" style="margin-right: 5;"><?=lang('cms.everyday')?>
                                            <input type="radio" name="editperiod" class="input-control" value="2" style="margin-right: 5;"><?=lang('cms.everyweek')?>
                                            <input type="radio" name="editperiod" class="input-control" value="3" style="margin-right: 5;"><?=lang('cms.everymonth')?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group required">
                                        <label for="editcoverQuestTime" class="col-sm-6 control-label label-css"><?=lang('cms.Need to cover Mutiple quest')?>:<span class="text-danger"></span></label>
                                        <div class="col-sm-6">
                                            <input type="radio" name="editcoverQuestTime" class="input-control" value="1" checked="checked" style="margin-right: 5;"><?=lang('Yes');?>
                                            <input type="radio" name="editcoverQuestTime" class="input-control" value="0" <?=$this->config->item('disable_quest_category_override_mnanger_countdown') ? 'disabled' : ''?>  style="margin-right: 5;"><?=lang('No');?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group required">
                                        <label class="col-sm-3 control-label label-css">icon:<span class="text-danger"></span><p>(size:80*80)</p></label>
                                        <div class="col-md-9">
                                            <div class="quest_icon_sec upload_btn_sec">
                                                <div class="fileUpload btn btn-md btn-info">
                                                    <span><?=lang("Upload") ?></span>
                                                    <input type="file" name="edit_userfile[]" class="upload" id="edit_userfile" onchange="uploadImage(this,'edit_quest_icon_80x80');">
                                                </div>
                                            </div>
                                            <div class="quest_icon_sec">
                                                <h6></h6>
                                                <div class="banner_container">
                                                    <img id="edit_quest_icon_80x80" class="quest_icon_80x80"/>
                                                </div>
                                            </div>
                                            <span>(jpeg, jpg, png)</span> 
                                            <input type="hidden" name="icon_url" id="editIconUrl" class="form-control" readonly>
                                            <input type="hidden" name="editQuestThumbnail" id="editQuestThumbnail" >
                                            <input type="hidden" name="is_default_banner_flag" id="isEditDefaultBannerFlag" class="form-control" readonly>
                                            <span class="text-danger help-block m-b-0" id="editImgViewRequired" hide>*Icon is required.</span>
                                        </div>                                        
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="editquestCategoryDesc" class="control-label"><?=lang('cms.Quest Remark');?>:</label><br>
                                <textarea name="editquestCategoryDesc" id="editquestCategoryDesc" class="form-control input-sm" style="width:100%" rows="10" required="" aria-invalid="true"></textarea>
                                <span class="text-danger help-block" id="addDescMaxCharacters" hide>*<?=lang('cms.Maximum 60 characters');?></span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-linkwater" data-dismiss="modal"><?=lang('lang.cancel');?></button>
                    <button type="button" class="btn btn-scooter" id="saveEditQuestCategory"><?=lang('lang.save');?></button>
                </div>
            </div>
        </div>
    </div>
</form>

<div id="quest_category_success" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">×</span>
                </button>
                <h5 class="modal-title"><?=lang('Quest Category');?></h5>
            </div>
            <div class="modal-body text-center">
                <p class="f-20" id="successMsg"></p>
                <button type="button" class="btn btn-scooter" data-dismiss="modal"><?=lang('cms.OK');?></button>
            </div>
        </div>
    </div>
</div>

<!-- main category -->
<div class="panel panel-primary">
    <div class="panel-heading custom-ph">
        <h4 class="panel-title custom-pt">
            <i class="icon-note"></i> <?=lang('cms.questCategorySetting');?>
            <!-- <span class="pull-right" style = 'margin: 0 4px;'>
                <a data-toggle="collapse" href="#details_panel_body" class="btn btn-info btn-xs" aria-expanded="false"></a>
            </span> -->

            <a href="javascript:void(0);" class="btn  pull-right btn-xs btn-info" id="addQuestCategoryBtn"
                <?php if ( ! $this->permissions->checkPermissions('quest_category_add')) echo 'style="display: none;"' ?>>
                <i class="fa fa-plus-circle"></i> <?=lang('cms.addNewQuestCategory');?>
            </a>
        </h4>
    </div>
    <div class="panel-body" id="details_panel_body">
        <div class="row">
            <div class="col-md-12">
                <form action="<?=BASEURL . 'marketing_management/deleteSelectedPromoType'?>" method="post" role="form">
                    <div id="tag_table" class="table-responsive">
                        <table class="table table-striped table-hover" id="my_table" style="margin: 0px 0 0 0; width: 100%;" >
                            <thead>
                                <tr>
                                    <td></td>
                                    <th>ID</th>
                                    <th><?=lang('cms.mainMissionCategory');?></th>
                                    <th><?=lang('cms.missionIcon');?></th>
                                    <th id = "default_sort_paymentTyperOrder"><?=lang('cms.missionCategorySort');?></th>
                                    <th><?=lang('cms.createTime');?></th>
                                    <th><?=lang('cms.createBy');?></th>
                                    <!-- <th><?=lang('cms.addSubCategory');?></th> -->
                                    <th><?=lang('cms.status');?></th>
                                    <th><?=lang('cms.operate');?></th>
                                </tr>
                            </thead>

                            <tbody>
                            <?php if (!empty($questCategory)) {
                                foreach ($questCategory as $row) { ?>
                                    <tr>
                                        <td ></td>
                                        <th><?=$row['questCategoryId']?></th>
                                        <th><?=lang($row['title'])?></th>
                                        <?php
                                            $uploadUri=$this->utils->getQuestThumbnailRelativePath();
                                            if(file_exists($this->utils->getQuestThumbnails().$row['iconPath']) && !empty($row['iconPath'])) {
                                                $iconPath = $uploadUri . $row['iconPath'];
                                            } else {
                                                if(!empty($row['iconPath'])){
                                                    $iconPath = $this->utils->imageUrl('questthumbnails/'.$row['iconPath']);
                                                } else {
                                                    $iconPath = $this->utils->imageUrl('promothumbnails/default_promo_cms_1.jpg');
                                                }
                                            }
                                        ?>
                                        <td><img id="icon_name" src="<?=$iconPath?>" width=100></td>
                                        <th><?=$row['sort']?></th>
                                        <th><?=$row['createdAt']?></th>
                                        <th><?=$row['createdBy']?></th>
                                        <!-- <th>
                                            <div class="panel-heading custom-ph">
                                                <h4 class="panel-title custom-pt">
                                                    <a href="javascript:void(0);" class="btn pull-left btn-css btn-default" id="addSubCategoryBtn">
                                                       <?=lang('cms.add');?>
                                                    </a>
                                                </h4>
                                            </div>
                                        </th> -->
                                        <th><?= $row['status'] == 0 ? '<p class="text-danger"><i class="glyphicon glyphicon-ban-circle"></i>' . lang('cms.inactive') . '</p>' : '<p class="text-success"><i class="glyphicon glyphicon-ok-circle"></i>' . lang('cms.active') . '</p>';?></th>
                                        <th>
                                            <div class="panel-heading custom-ph">
                                            <div class="action-item">
                                                    <a href="javascript:void(0);" class="btn btn-css btn-scooter" id="addQuestCategoryBtn"
                                                        <?php if($this->permissions->checkPermissions('quest_category_edit') && $row['status'] != 1) echo 'style="display: none;"';?>
                                                        onclick="getQuestCategoryDetails(<?=$row['questCategoryId']?>,<?= $this->language_function->getCurrentLanguage(); ?>, 'view')">
                                                       <?=lang('cms.view');?>
                                                    </a>
                                                <div class="action-item">
                                                    <a href="javascript:void(0);" class="btn btn-css btn-scooter" id="addQuestCategoryBtn"
                                                        <?php if ( ! $this->permissions->checkPermissions('quest_category_edit')) echo 'style="display: none;"';
                                                            if($row['status'] != 0) echo 'style="display: none;"';
                                                        ?>
                                                        onclick="getQuestCategoryDetails(<?=$row['questCategoryId']?>,<?= $this->language_function->getCurrentLanguage(); ?>)">
                                                       <?=lang('cms.edit');?>
                                                    </a>
                                                </div>
                                                <div class="action-item">
                                                    <a href="javascript:void(0);" class="btn btn-css btn-linkwater" id="activeQuestCategoryBtn"
                                                        <?if($row['status'] == 0){
                                                            if ( ! $this->permissions->checkPermissions('quest_category_enable')) echo 'style="display: none;"' ;
                                                        } else {
                                                            if ( ! $this->permissions->checkPermissions('quest_category_disable')) echo 'style="display: none;"' ;
                                                        }?>
                                                        onclick = "changeStatus(<?=$row['questCategoryId']?>, <?=$row['status']?>)">
                                                        <?=($row['status'] == 0) ? lang('cms.active') :  lang('cms.deactivate')?>
                                                    </a>
                                                </div>
                                                <div class="action-item">
                                                    <a href="javascript:void(0);" class="btn btn-css btn-danger" id="deleteQuestCategoryBtn"
                                                        <?php if ( ! $this->permissions->checkPermissions('quest_category_delete')) echo 'style="display: none;"';
                                                            if($row['status'] != 0)  echo 'style="display: none;"';?>
                                                        onclick = "deletedCategory(<?=$row['questCategoryId']?>)" >
                                                    <?=lang('cms.delete');?>
                                                    </a>
                                                </div>
                                            </div>
                                        </th>
                                    </tr>
                                <?php }
                            } ?>
                            </tbody>
                        </table>
                    </div>
                </form>

            </div>
        </div>
    </div>
    <div class="panel-footer"></div>
</div>
<!-- main category end -->

<?php include('quest_category_name_modal.php') ?>

<script type="text/javascript">
    $(document).ready(function(){

        $('#addOrderMaxCharacters').hide();
        $('#addNameViewRequired').hide();
        $('#addNameViewMaxCharacters').hide();
        $('#addDescMaxCharacters').hide();
        $('#editOrderMaxCharacters').hide();
        $('#editNameViewRequired').hide();
        $('#editNameViewMaxCharacters').hide();
        $('#editDescMaxCharacters').hide();
        $('#showTimerSetting input').prop('disabled', true);
        $('#addDate').hide();
        $('#editDate').hide();
        $('#addDateViewRequired').hide();
        $('#addImgViewRequired').hide();
        $('#editDateViewRequired').hide();
        $('#editImgViewRequired').hide();

        $('#startDate').daterangepicker({
            singleDatePicker: true,
            locale: {
                format: 'YYYY-MM-DD'
            }
        })
        $('#endDate').daterangepicker({
            singleDatePicker: true,
            locale: {
                format: 'YYYY-MM-DD'
            }
        })
        $('#editstartDate').daterangepicker({
            singleDatePicker: true,
            locale: {
                format: 'YYYY-MM-DD'
            }
        })
        $('#editendDate').daterangepicker({
            singleDatePicker: true,
            locale: {
                format: 'YYYY-MM-DD'
            }
        })

          //submenu
        $('#collapseSubmenu').addClass('in');
        var desc = $("#default_sort_paymentTyperOrder").index();
        $('#my_table').DataTable({
            dom: "<'panel-body'<'pull-right'f><'pull-right progress-container'>l><'dt-information-summary1 text-info pull-left' i>t<'panel-body'<'pull-right'p><'dt-information-summary2 text-info pull-left' i>>",
            "responsive": {
                details: {
                    type: 'column'
                }
            },
            "columnDefs": [ {
                className: 'control',
                orderable: false,
                targets:   0
            },{
                orderable: false,
                targets:   1
            } ],
            "order": [ desc, 'desc' ]
        });

        $('.delete-promo').click(function(){
            if(confirm("Are you sure you want to delete this?")){
            }
            else{
                return false;
            }
        });

        $('#add_quest_category').on('hidden.bs.modal', function () {
           resetAddQuestCategoryInput();
        });

        $('#saveAddQuestCategory').click(function(e){
            e.preventDefault();

            var form_id = document.getElementById('add_category_form');
            var formData = new FormData(form_id);
            // formData.append('file', filQuestCatIcon);

            var orderIdVal = $('#questCategoryOrderId').val();
            var nameVal = $('#questCatecoryTitleView').val();
            var descVal = $('#questCategoryDesc').val();
            var iconVal = $('#IconUrl').val();
            var showTimer = $('input[name="showTimer"]:checked').val();
            var notValidate = false;

            $('#addOrderMaxCharacters').hide();
            $('#addNameViewRequired').hide();
            $('#addNameViewMaxCharacters').hide();
            $('#addDescMaxCharacters').hide();
            $('#addDateViewRequired').hide();
            $('#addImgViewRequired').hide();

            if(orderIdVal.length > '<?=Promo_type::PROMO_CATEGORY_ORDER_MAX_CHARACTERS?>'){
                $('#addOrderMaxCharacters').show();
                notValidate = true;
            }
            if(nameVal.length == 0){
                $('#addNameViewRequired').show();
                notValidate = true;
            }
            if(nameVal.length > '<?=Promo_type::PROMO_CATEGORY_NAME_MAX_CHARACTERS?>'){
                $('#addNameViewMaxCharacters').show();
                notValidate = true;
            }
            if(descVal.length > '<?=Promo_type::PROMO_CATEGORY_INTERNAL_REMARK_MAX_CHARACTERS?>'){
                $('#addDescMaxCharacters').show();
                notValidate = true;
            }

            if(showTimer == 1){
                $('#showTimerSetting input').each(function(){
                    if($(this).val() == ""){
                        $('#addDateViewRequired').show();
                        notValidate = true;
                    }
                });
            }

            if(iconVal.length == ""){
                $('#addImgViewRequired').show();
                notValidate = true;
            }

            if(notValidate){
                return false;
            }else{
                $.ajax({
                    'url' : base_url + 'marketing_management/addQuestCategory',
                    'type' : 'POST',
                    'dataType' : "json",
                    'data':formData,
                    'cache': false,
                    'contentType': false,
                    'processData': false,
                    'success' : function(data) {
                        if (data.success){
                            $('#quest_category_success').modal('show');
                            $('#successMsg').text('<?=lang('cms.Quest Category added');?>');
                            $('#add_quest_category').modal('hide');
                        } else {
                            switch (data.noteType) {
                                case 'orderMaxChar':
                                    $('#addOrderMaxCharacters').show();
                                    break;
                                case 'nameLen':
                                    $('#addNameViewMaxCharacters').show();
                                    break;
                                case 'descLen':
                                    $('#addDescMaxCharacters').show();
                                    break;
                                case 'date':
                                    $('#addDate').show();
                                    break;
                            }
                        }
                    }
                });
            }
        });

        $('#saveEditQuestCategory').click(function(e){
            e.preventDefault();
            var form_id = document.getElementById('edit_category_form');
            var formData = new FormData(form_id);
            // formData.append('file', filEditQuestCatIcon);

            var orderIdVal = $('#editquestCategoryOrderId').val();
            var nameVal = $('#editquestCategoryTitleView').val();
            var descVal = $('#editquestCategoryDesc').val();
            var iconVal = $('#editIconUrl').val();
            var showTimer = $('input[name="editshowTimer"]:checked').val();
            var notValidate = false;

            $('#editOrderMaxCharacters').hide();
            $('#editNameViewRequired').hide();
            $('#editNameViewMaxCharacters').hide();
            $('#editDescMaxCharacters').hide();
            $('#editDateViewRequired').hide();
            $('#editImgViewRequired').hide();

            if(orderIdVal.length > '<?=Promo_type::PROMO_CATEGORY_ORDER_MAX_CHARACTERS?>'){
                $('#editOrderMaxCharacters').show();
                notValidate = true;
            }
            if(nameVal.length == 0){
                $('#editNameViewRequired').show();
                notValidate = true;
            }
            if(nameVal.length > '<?=Promo_type::PROMO_CATEGORY_NAME_MAX_CHARACTERS?>'){
                $('#editNameViewMaxCharacters').show();
                notValidate = true;
            }
            if(descVal.length > '<?=Promo_type::PROMO_CATEGORY_INTERNAL_REMARK_MAX_CHARACTERS?>'){
                $('#editDescMaxCharacters').show();
                notValidate = true;
            }

            if(showTimer == 1){
                $('#editshowTimerSetting input').each(function(){
                    if($(this).val() == ""){
                        $('#editDateViewRequired').show();
                        notValidate = true;
                    }
                });
            }

            if(iconVal.length == ""){
                $('#editImgViewRequired').show();
                notValidate = true;
            }


            if(notValidate){
                return false;
            }else{
                $.ajax({
                    'url' : base_url + 'marketing_management/editQuestCategory',
                    'type' : 'POST',
                    'dataType' : "json",
                    'data':formData,
                    'cache': false,
                    'contentType': false,
                    'processData': false,
                    'success' : function(data) {
                        if (data.success){
                            $('#quest_category_success').modal('show');
                            $('#successMsg').text('<?=lang('cms.Quest Category saved');?>');
                            $('#edit_quest_category').modal('hide');
                        } else {
                            switch (data.noteType) {
                                case 'orderMaxChar':
                                    $('#editOrderMaxCharacters').show();
                                    break;
                                case 'nameLen':
                                    $('#editNameViewMaxCharacters').show();
                                    break;
                                case 'descLen':
                                    $('#editDescMaxCharacters').show();
                                    break;
                                case 'date':
                                    $('#editDate').show();
                                    break;
                            }
                        }
                    }
                });
            }
        });



    });

    $('.note-editor .note-toolbar .note-insert button[data-event~="showImageDialog"]').remove();

    
    $("#userfile").on('change', function() {
        $('.upload_req_txt').hide();
    });

    $("#edit_userfile").on('change', function() {
        $('.upload_req_txt').hide();
    });

    function changeShowTimer(input){
        showTimer = $('input[name="'+input+'showTimer"]:checked').val();
        if(showTimer == 1){
            $('#'+input+'showTimerSetting input').prop('disabled', false)
        }else{
            $('#'+input+'showTimerSetting input').prop('disabled', true)
        }
    };

    function uploadImage(input,id) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function (e) {
                $('.upload_req_txt').hide();
                $('#'+id).attr('src', e.target.result).width(80).height(80);
                $('#IconUrl').val(input.files[0].name);
                $('#editIconUrl').val(input.files[0].name);
            };
            reader.readAsDataURL(input.files[0]);
        }
    }

    $('#addQuestCategoryBtn').click(function() {
        $('#add_quest_category').modal('show');
        // $('.filQuestCatIcon').show();
        $('#form_quest_name' ).each(function(){
            this.reset();
        });
    });

    $('#quest_category_success').on('hidden.bs.modal', function (e) {
        location.reload();
    });

    function getQuestCategoryDetails(questCategoryId,currentLang,type=null){
        $('#edit_quest_category').modal('show');
        $("#editstartDate").val("");
        $("#editstartTime").val("");
        $("#editendDate").val("");
        $("#editendTime").val("");

        $.ajax({
            'url' : base_url + 'marketing_management/getQuestCategoryDetails/' + questCategoryId,
            'type' : 'GET',
            'dataType' : "json",
            'success' : function(data) {

                if (data[0].title.toLowerCase().indexOf("_json:") >= 0){
                    var langConvert = jQuery.parseJSON(data[0].title.substring(6));
                    $('#editquestCategoryTitleView').val(langConvert[currentLang]);
                    $('#editquestCategoryTitle').val(data[0].title);
                } else {
                    $('#editquestCategoryTitleView').val(data[0].title);
                    $('#editquestCategoryTitle').val(data[0].title);
                }
                $('#editquestCategoryDesc').val(data[0].description);
                $('#questCategoryId').val(data[0].questCategoryId);
                $("input[name*='editquestCategoryStatus'][value='" + data[0].status +"']").prop("checked", true);
                $('#edit_quest_icon_80x80').attr('src', data[0].icon_path);
                $('#editquestCategoryOrderId').val(data[0].sort);
                $('#editIconUrl').val(data[0].iconPath);
                $("input[name*='editshowTimer'][value='" + data[0].showTimer +"']").prop("checked", true);
                if(data[0].showTimer == 1){
                    $('#editshowTimerSetting input').prop('disabled', false)
                    $("#editstartDate").val(data[0].startDate);
                    $("#editstartTime").val(data[0].startTime);
                    $("#editendDate").val(data[0].endDate);
                    $("#editendTime").val(data[0].endTime);
                }else{
                    $('#editshowTimerSetting input').prop('disabled', true)
                }                
                $("input[name*='editcoverQuestTime'][value='" + data[0].coverQuestTime +"']").prop("checked", true);
                $("input[name*='editperiod'][value='" + data[0].period +"']").prop("checked", true);

                if(type=='view'){
                    $('#saveEditQuestCategory').hide();
                    $('#edit_from_title').text("<?=lang('cms.viewQuestCategory');?>")
                    $('#edit_quest_category input').prop('disabled', true);
                    $('#edit_quest_category textarea').prop('disabled', true);
                }else{
                    $('#saveEditQuestCategory').show();
                }

                if(data[0]?.existQuestManager){
                    $(".questCategoryContent").removeClass("hide").css("color", "red");
                    $("input[name*='editperiod']").prop("disabled", true);
                    $("input[name*='editshowTimer']").prop("disabled", true);
                    $('#editshowTimerSetting input').prop('disabled', true);

                    $('#editstartDate').after('<input type="hidden" name="editstartDate" value="'+data[0].startDate+'">');
                    $('#editstartTime').after('<input type="hidden" name="editstartTime" value="'+data[0].startTime+'">');
                    $('#editendDate').after('<input type="hidden" name="editendDate" value="'+data[0].endDate+'">');
                    $('#editendTime').after('<input type="hidden" name="editendTime" value="'+data[0].endTime+'">');
                    $("input[name*='editperiod']:checked").after('<input type="hidden" name="editperiod" value="'+data[0].period+'">');
                    $("input[name*='editshowTimer']:checked").after('<input type="hidden" name="editshowTimer" value="'+data[0].showTimer+'">');
                }
            }
         });
        $("html, body").animate({ scrollTop: 0 }, "slow");
    }

    function resetAddQuestCategoryInput(){
        $('#questCategoryOrderId').val('');
        $('#promoTypeNameView').val('');
        $('#promoTypeDesc').val('');
        $("input[name*='status'][value='0']").prop("checked", true);
        clearQuestCatIcon();
    }

    function clearQuestCatIcon(){
        var questCategoryId = $('#questCategoryId').val();
        return false;
    }

    function changeStatus(id, status){
        console.log(status);
        if(status == 0){            
            check = confirm("<?=lang('cms.questActive')?>");
        }else{
            check = confirm("<?=lang('cms.questInactive')?>");
        }
        if(check){
            $.ajax({
                'url' : base_url + 'marketing_management/changeQuestStatus',
                'type' : 'POST',
                'dataType' : "json",
                'data': {
                    'questCategoryId' : id,
                    'status' : status
                },
                'success' : function(data) {
                    if (data.success){
                        var url="/marketing_management/quest_category";
                        window.location.href=url;
                    } else {
                        alert('Status Changed Failed')
                    }
                }
            });
        }
    }

    function deletedCategory(id){
        check = confirm("<?=lang('cms.questDelete')?>");
        if(check){
            $.ajax({
                'url' : base_url + 'marketing_management/deleteQuestCategory',
                'type' : 'POST',
                'dataType' : "json",
                'data': {
                    'questCategoryId' : id
                },
                'success' : function(data) {
                    if (data.success){
                        var url="/marketing_management/quest_category";
                        window.location.href=url;
                    } else {
                        alert(data.msg)
                    }
                }
            });
        }
    }

</script>