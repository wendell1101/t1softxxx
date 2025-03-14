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
#add_quest_manager .row,
#edit_quest_manager .row{
  margin: 15px -15px;
}
#add_quest_manager .row [class^='col-'],
#edit_quest_manager .row [class^='col-']{
  padding: 0 15px;
}
#add_quest_manager .upload-quest-img,
#edit_quest_manager .upload-quest-img{
  border: 2px dashed #CCCCCC;
  /* width: 100%; */
  height: 170px;
  padding: 14px;
}
/* #add_quest_manager .filQuestCatIcon,
#edit_quest_manager .filEditQuestCatIcon{
  cursor: pointer;
  position: relative;
  display: block;
  padding: 8px 0;
} */
#add_quest_manager .upload-quest-img-btn p,
#edit_quest_manager .upload-quest-img-btn p{
  margin: 15px 0;
  font-size: 16px;
  color: #BABABA;
  line-height: 2;
}
#add_quest_manager .clearQuestCatIcon,
#edit_quest_manager .clearQuestCatIcon{
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
     margin: 0;
     padding: 0;
     font-size: 20px;
     cursor: pointer;
     opacity: 0;
     filter: alpha(opacity=0);
}
.quest_icon_80x80, .quest_banner_80x80{
    align: left; 
    valign: middle; 
    width: 80px; 
    height: 80px; 
    margin: 0 1px 0 0;
    display:block;
    border: 1px black solid;
}
.add, .del{
    display: inline-block;
    width: 25px;
    height: 25px;
    color: #ccc;
    border: 2px solid;
    border-radius: 50%;
    transition: color .25s;
    position: relative;
    overflow: hidden;
}

.add:hover, .del:hover{
    color: #34538b;
}
.add:before, .add:after
, .del:before, .del:after{
    content: "";
    position: absolute;
    top: 50%;
    left: 50%;
}
.add::before, .del::before{
    width: 20px;
    border-top: 4px solid;
    margin: -2px 0 0 -10px;
}
.add:after{
    height: 20px;
    border-left: 4px solid;
    margin: -10px 0 0 -2px;
}

/*end css OGP-17805*/

</style>
<!--Add Quest Manager-->
<form id="add_manager_form">
    <div id="add_quest_manager" class="modal fade" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document" style = "width: 80%;">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                    </button>
                    <h5 class="modal-title"><?=lang('cms.addNewQuestActivity');?></h5>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-5">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group required">
                                        <label for="levelType" class="col-sm-3 control-label label-css"><?=lang('cms.attribute')?>:<span class="text-danger"></span></label>
                                        <div class="col-sm-9">
                                            <input type="radio" name="levelType" class="input-control" value="1" style="margin: 10px; margin-left: 0px;" onclick = "changeLevelType('', event)" checked><?=lang('cms.singleQuest')?>
                                            <input type="radio" name="levelType" class="input-control" value="2" style="margin: 10px; margin-left: 0px;" onclick = "changeLevelType('', event)" ><?=lang('cms.multipleQuest')?>
                                            <i class="glyphicon glyphicon-exclamation-sign" data-toggle="tooltip" data-placement="auto" data-original-title="<?=lang('cms.quest must be completed sequentially')?>"></i>                                            
                                            <select  name="questCategoryId" class="form-control input-sm" id="questCategoryId">
                                                <option value=""><?=lang('cms.mainMissionCategory')?></option>
                                                <?php if(!empty($questCategory)):
                                                    foreach ($questCategory as $row) :?>
                                                        <option value="<?=$row['questCategoryId']?>"><?=lang($row['title'])?></option>
                                                        <?php endforeach; ?>
                                                <?php endif; ?>
                                            </select>                                            
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div id ="forMultipleSetting" style = "background-color:#BABABA;padding: 1px;">
                                <div class = "row">
                                    <div class="col-md-12">
                                        <div class="form-group required">
                                            <label for="displayPanel" class="col-sm-3 control-label label-css"><?=lang('cms.displayPanel')?>:</label>
                                            <div class="col-sm-9">
                                            <select name="displayPanel" class="form-control input-sm" id="displayPanel">
                                                <option value="0"><?=lang('cms.default')?></option>
                                                <?php
                                                if(!empty($questDisplayPanel)):
                                                    foreach ($questDisplayPanel as $panelKey => $panelValue) :?>
                                                        <option value="<?=$panelKey?>"><?=lang($panelValue)?></option>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>                                
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label for="allowSameIpReceive" class="col-sm-5 control-label label-css"><?=lang('cms.allowSameIpReceive')?>:</label>
                                            <div class="col-sm-6">
                                                <input type="radio" name="allowSameIpReceive" class="input-control" value="1" style="margin-right: 5;"><?=lang('Yes')?>
                                                <input type="radio" name="allowSameIpReceive" class="input-control" value="0" checked style="margin-right: 5;"><?=lang('No')?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group required">
                                            <label for="cms.showOneClick" class="col-sm-3 control-label label-css"><?=lang('cms.showOneClick')?>:<span class="text-danger"></span></label>
                                            <div class="col-sm-8">
                                                <input type="radio" name="cms.showOneClick" class="input-control" value="1" style="margin-right: 5;"><?=lang('Yes')?>
                                                <input type="radio" name="cms.showOneClick" class="input-control" value="0" checked style="margin-right: 5;"><?=lang('No')?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group required" hidden>
                                            <label for="showTimer" class="col-sm-3 control-label label-css"><?=lang('cms.showTimer')?>:<span class="text-danger"></span></label>
                                            <div class="col-sm-9">
                                                <input type="radio" name="showTimer" class="input-control" value="1" onclick = "changeShowTimer('')" style="margin-right: 5;"><?=lang('cms.Open')?>
                                                <input type="radio" name="showTimer" class="input-control" value="0" onclick = "changeShowTimer('')" checked style="margin-right: 5;"><?=lang('cms.Close')?>
                                                <div id = "showTimerSetting">
                                                    <div style = "display: flex;align-items: center;">
                                                        <label for="startDate" class="col-sm-5 control-label label-css" style = "padding: 18px 0;"><?=lang('cms.startTime')?>:</label>
                                                        <input type = "date"  name="startDate" class="form-control input-sm" id="startDate">
                                                    </div>
                                                    <div style = "display: flex;align-items: center;">
                                                        <input type = "time"  name="startTime" class="form-control input-sm" id="startTime">
                                                    </div>
                                                    <div style = "display: flex;align-items: center;">
                                                        <label for="endDate" class="col-sm-5 control-label label-css" style = "padding: 18px 0;"><?=lang('cms.endTime')?>:</label>
                                                        <input type = "date"  name="endDate" class="form-control input-sm" id="endDate">
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
                                
                            </div>
                            <div style = "background-color:#BABABA; padding:0 1px;">
                                <div class = "row">
                                    <div class="col-md-12">
                                        <div style = "margin: 15 15 15 10;font-size: 20px;">
                                            <b><?=lang('cms.General Settings')?></b>
                                        </div>
                                        <div class="form-group required">
                                            <label for="questManagerType" class="col-sm-3 control-label label-css"><?=lang('cms.Event Type')?>:<span class="text-danger"></span></label>
                                            <div class="col-sm-9">
                                                <select  name="questManagerType" class="form-control input-sm" id="questManagerType">
                                                    <option value="1"><?=lang('cms.Deposit')?></option>
                                                    <option value="2"><?=lang('cms.Wagering amount')?></option>
                                                    <option value="3"><?=lang('cms.Invite friends')?></option>
                                                    <option value="4"><?=lang('cms.Other')?></option>
                                                    <option value="5"><?=lang('cms.Game')?></option>
                                                    <option value="6"><?=lang('cms.Other links')?>:</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group required" hidden>
                                            <label for="period" class="col-sm-3 control-label label-css"><?=lang('cms.period')?>:</label>
                                            <div class="col-sm-8">
                                                <input type="radio" name="period" class="input-control" value="999" style="margin-right: 5;" checked><?=lang('cms.None');?>
                                                <input type="radio" name="period" class="input-control" value="1" style="margin-right: 5;"><?=lang('cms.everyday')?>
                                                <input type="radio" name="period" class="input-control" value="2" style="margin-right: 5;"><?=lang('cms.everyweek')?>
                                                <input type="radio" name="period" class="input-control" value="3" style="margin-right: 5;"><?=lang('cms.everymonth')?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group required">
                                            <label class="col-sm-6 control-label label-css">Icon:<span class="text-danger"></span><p>(size:80*80)</p></label>
                                            <div class="col-md-6">
                                                <div class="quest_icon_sec upload_btn_sec">
                                                    <div class="fileUpload btn btn-md btn-info">
                                                        <span><?=lang("Upload") ?></span>
                                                        <input type="file" name="userfile[]" class="upload col-md-6" id="userfile" onchange="uploadImage(this,'quest_icon_80x80');">
                                                    </div>
                                                </div>
                                                <div class="quest_icon_sec">
                                                    <h6></h6>
                                                    <div class="banner_container">
                                                        <img id="quest_icon_80x80" class="quest_icon_80x80"/>
                                                    </div>
                                                </div>
                                                <input type="hidden" name="icon_url" id="IconUrl" class="form-control" readonly>
                                                <input type="hidden" name="QuestThumbnail" id="QuestThumbnail" >
                                                <input type="hidden" name="is_default_icon_flag" id="isEditDefaultBannerFlag" class="form-control" readonly>
                                                <span class="text-danger help-block m-b-0" id="addIconViewRequired" hide>*Icon is required.</span>
                                                <span>(jpeg, jpg, png)</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="col-sm-6 control-label label-css">Banner:<span class="text-danger"></span><p>(size:832*120)</p></label>
                                            <div class="col-md-6">
                                                <div class="quest_icon_sec upload_btn_sec">
                                                    <div class="fileUpload btn btn-md btn-info">
                                                        <span><?=lang("Upload") ?></span>
                                                        <input type="file" name="userfile_banner[]" class="upload col-md-6" id="userfile_banner" onchange="uploadImage(this,'quest_banner_80x80');">
                                                    </div>
                                                </div>
                                                <div class="quest_icon_sec">
                                                    <h6></h6>
                                                    <div class="banner_container">
                                                        <img id="quest_banner_80x80" class="quest_banner_80x80"/>
                                                    </div>
                                                </div>
                                                <input type="hidden" name="banner_url" id="BannerUrl" class="form-control" readonly>
                                                <input type="hidden" name="QuestBannerThumbnail" id="QuestBannerThumbnail" >
                                                <input type="hidden" name="is_default_banner_flag" id="isEditDefaultBannerFlag" class="form-control" readonly>
                                                <span>(jpeg, jpg, png)</span>
                                            </div>
                                        </div>
                                    </div>                                    
                                </div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label for="questManagerDesc" class="col-sm-2 control-label label-css"><?=lang("cms.Note") ?>:</label>
                                            <div class="col-sm-10">
                                                <textarea name="questManagerDesc" id="questManagerDesc" class="form-control input-sm" style="width:100%" rows="10" required="" aria-invalid="true"></textarea>
                                            </div>
                                            <!-- <span class="text-danger help-block" id="addDescMaxCharacters" hide>*<?=lang('cms.Maximum 60 characters');?></span> -->
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div style = "margin: 15 15 15 10;font-size: 20px;">
                                            <b><?=lang('cms.allowedGameType')?></b>
                                        </div>
                                        <span id="game_tree_by_quest" style="color:red; font-size:10px;"></span>
                                        <div>
                                            <div class="form-group col-md-12">
                                                    <input type="checkbox" name="auto_tick_new_games_in_game_type" id="auto_tick_new_games_in_game_type" value="true">
                                                    <label class="control-label" for="auto_tick_new_games_in_game_type"><strong><?php echo lang('promorules.Auto tick new games in game type'); ?></strong></label>
                                            </div>
                                            <div class="form-group col-md-12">
                                                    <input type="checkbox" name="auto_tick_all_games_in_game_type" id="auto_tick_all_games_in_game_type" value="true">
                                                    <label class="control-label" for="auto_tick_all_games_in_game_type"><strong><?php echo lang('Select All'); ?></strong></label>
                                            </div>

                                            <div class="form-group col-md-12" id='treeAGT_sec'>
                                                <div class="form-group col-md-12" style="border:1px solid #ddd; margin-bottom:15px; padding:10px 0px 10px; 0px; overflow-y:scroll; overflow-x:scroll;">
                                                    <?php include APPPATH . "/views/marketing_management/quest/quest_game_list.php"; ?>
                                                    <?php //include APPPATH . "/views/includes/game_tree.php";?>
                                                    <input type="hidden" name="selected_game_tree" value="">
                                                    <div id="allowedGameTypeTree"></div>
                                                    <div id="allowed-promo-game-list-table" class="col-md-12"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>                            
                        </div>
                        <div class="col-md-7">
                            <div class="row">
                                <div class="col-md-5">
                                    <div class="form-group required">
                                        <label for="questConditionType" class="control-label label-css"><?=lang('cms.Mission task condition settings');?>:<span class="text-danger"></span></label>
                                        <select class="form-control input-sm questConditionType" id = "questConditionType">
                                        </select>
                                    </div>
                                    <span class="text-danger help-block m-b-0" id="addRuleViewRequired" hide>*Rule is required.</span>
                                </div>
                                <div class="col-md-5">
                                    <div class="form-group">
                                        <input type="text" id = "questTitle" class="form-control input-control questTitle" placeholder="<?=lang('cms.Please input mission task title');?>*" style="margin-top: 25px;">
                                    </div>
                                </div>                               
                            </div>
                            <div class="row">
                                <div id="questRuleTag" class="table-responsive col-md-12">
                                    <table class="table table-striped table-hover" id="questRuleTable" style="margin: 0px 0 0 0; width: 100%;" >
                                        <thead>
                                            <tr>
                                                <td style = "width: 20px;"></td>
                                                <th style = "width: 10%;"><?=lang('cms.Title');?></th>
                                                <th style = "width: 25%;"><?=lang('cms.Condition');?></th>
                                                <th style = "width: 30%;"><?=lang('cms.Rewards');?></th>
                                                <th style = "width: 35%;"><?=lang('cms.Withdrawal request');?></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        </tbody>
                                    </table>
                                </div>
                                <div class = "col-md-2" style = "margin-top: 10px;">
                                    <div class="add" onclick = "addCol('');"></div>
                                    <div class="del" onclick = "delCol('');"></div>
                                </div>
                            </div>                            
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-linkwater" data-dismiss="modal"><?=lang('lang.cancel');?></button>
                    <input type="submit" class="btn btn-scooter" id="saveAddQuestManager"  <?php if ( ! $this->permissions->checkPermissions('quest_manager_add')) echo 'disabled="disabled"' ?> value = '<?=lang('lang.save');?>'></button>
                </div>
            </div>
        </div>
    </div>
</form>
<!--Edit Quesst Manager-->
<form id="edit_manager_form">
    <div id="edit_quest_manager" class="modal fade" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document" style = "width: 80%;">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                    </button>
                    <h5 class="modal-title" id = "edit_from_title"><?=lang('cms.editQuestManager');?></h5>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-5">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group required">
                                        <label for="editlevelType" class="col-sm-3 control-label label-css"><?=lang('cms.attribute')?>:<span class="text-danger"></span></label>
                                        <div class="col-sm-9">
                                            <input type="radio" name="editlevelType" class="input-control" value="1" style="margin: 10px; margin-left: 0px;" onclick = "changeLevelType('edit', event)"><?=lang('cms.singleQuest')?>
                                            <input type="radio" name="editlevelType" class="input-control" value="2" style="margin: 10px; margin-left: 0px;" onclick = "changeLevelType('edit', event)"><?=lang('cms.multipleQuest')?>
                                            <i class="glyphicon glyphicon-exclamation-sign" data-toggle="tooltip" data-placement="auto" data-original-title="<?=lang('cms.quest must be completed sequentially')?>"></i>                                            
                                            <select  name="editquestCategoryId" class="form-control input-sm" id="editquestCategoryId">
                                                <option value=""><?=lang('cms.mainMissionCategory')?></option>
                                                <?php if(!empty($questCategory)):
                                                    foreach ($questCategory as $row) :?>
                                                        <option value="<?=$row['questCategoryId']?>"><?=lang($row['title'])?></option>
                                                    <?php endforeach ?>
                                                <?php endif ?>
                                            </select>
                                            <input type = "hidden" name = "editquestManagerId">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div id ="editforMultipleSetting" style = "background-color:#BABABA;padding: 1px;">
                                <div class = "row">
                                    <div class="col-md-12">
                                        <div class="form-group required">
                                            <label for="editdisplayPanel" class="col-sm-3 control-label label-css"><?=lang('cms.displayPanel')?>:</label>
                                            <div class="col-sm-9">
                                                <select  name="editdisplayPanel" class="form-control input-sm" id="editdisplayPanel">
                                                    <option value="0"><?=lang('cms.default')?></option>
                                                    <?php
                                                    if(!empty($questDisplayPanel)):
                                                        foreach ($questDisplayPanel as $panelKey => $panelValue) :?>
                                                            <option value="<?=$panelKey?>"><?=lang($panelValue)?></option>
                                                        <?php endforeach; ?>
                                                    <?php endif; ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>                                
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label for="editallowSameIpReceive" class="col-sm-5 control-label label-css"><?=lang('cms.allowSameIpReceive')?>:</label>
                                            <div class="col-sm-6">
                                                <input type="radio" name="editallowSameIpReceive" class="input-control" value="1" style="margin-right: 5;"><?=lang('Yes')?>
                                                <input type="radio" name="editallowSameIpReceive" class="input-control" value="0" checked style="margin-right: 5;"><?=lang('No')?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group required">
                                            <label for="editshowOneClick" class="col-sm-3 control-label label-css"><?=lang('cms.showOneClick')?>:<span class="text-danger"></span></label>
                                            <div class="col-sm-8">
                                                <input type="radio" name="editshowOneClick" class="input-control" value="1" style="margin-right: 5;"><?=lang('Yes')?>
                                                <input type="radio" name="editshowOneClick" class="input-control" value="0" checked style="margin-right: 5;"><?=lang('No')?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group required" hidden>
                                            <label for="editshowTimer" class="col-sm-3 control-label label-css"><?=lang('cms.showTimer')?>:<span class="text-danger"></span></label>
                                            <div class="col-sm-9">
                                                <input type="radio" name="editshowTimer" class="input-control" value="1" onclick = "changeShowTimer('edit')" style="margin-right: 5;"><?=lang('cms.Open')?>
                                                <input type="radio" name="editshowTimer" class="input-control" value="0" onclick = "changeShowTimer('edit')" checked style="margin-right: 5;"><?=lang('cms.Close')?>
                                                <div id = "editshowTimerSetting">
                                                    <div style = "display: flex;align-items: center;">
                                                        <label for="editstartDate" class="col-sm-5 control-label label-css" style = "padding: 18px 0;"><?=lang('cms.startTime')?>:</label>
                                                        <input type = "date"  name="editstartDate" class="form-control input-sm" id="editstartDate">
                                                    </div>
                                                    <div style = "display: flex;align-items: center;">
                                                        <input type = "time"  name="editstartTime" class="form-control input-sm" id="editstartTime">
                                                    </div>
                                                    <div style = "display: flex;align-items: center;">
                                                        <label for="editendDate" class="col-sm-5 control-label label-css" style = "padding: 18px 0;"><?=lang('cms.endTime')?>:</label>
                                                        <input type = "date"  name="editendDate" class="form-control input-sm" id="editendDate">
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
                                
                            </div>
                            <div style = "background-color:#BABABA; padding:0 1px;">
                                <div class = "row">
                                    <div class="col-md-12">
                                        <div style = "margin: 15 15 15 10;font-size: 20px;">
                                            <b><?=lang('cms.General Settings')?></b>
                                        </div>
                                        <div class="form-group required">
                                            <label for="editquestManagerType" class="col-sm-3 control-label label-css"><?=lang('cms.Event Type')?>:<span class="text-danger"></span></label>
                                            <div class="col-sm-9">
                                                <select  name="editquestManagerType" class="form-control input-sm" id="editquestManagerType">
                                                    <option value="1"><?=lang('cms.Deposit')?></option>
                                                    <option value="2"><?=lang('cms.Wagering amount')?></option>
                                                    <option value="3"><?=lang('cms.Invite friends')?></option>
                                                    <option value="4"><?=lang('cms.Other')?></option>
                                                    <option value="5"><?=lang('cms.Game')?></option>
                                                    <option value="6"><?=lang('cms.Other links')?>:</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group required" hidden>
                                            <label for="editperiod" class="col-sm-3 control-label label-css"><?=lang('cms.period')?>:<span class="text-danger"></span></label>
                                            <div class="col-sm-8">
                                                <input type="radio" name="editperiod" class="input-control" value="999" style="margin-right: 5;" checked><?=lang('cms.None');?>
                                                <input type="radio" name="editperiod" class="input-control" value="1" style="margin-right: 5;"><?=lang('cms.everyday')?>
                                                <input type="radio" name="editperiod" class="input-control" value="2" style="margin-right: 5;"><?=lang('cms.everyweek')?>
                                                <input type="radio" name="editperiod" class="input-control" value="3" style="margin-right: 5;"><?=lang('cms.everymonth')?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group required">
                                            <label class="col-sm-6 control-label label-css">Icon:<span class="text-danger"></span><p>(size:80*80)</p></label>
                                            <div class="col-md-6">
                                                <div class="quest_icon_sec upload_btn_sec">
                                                    <div class="fileUpload btn btn-md btn-info">
                                                        <span><?=lang("Upload") ?></span>
                                                        <input type="file" name="edit_userfile[]" class="upload col-md-6" id="userfile" onchange="uploadImage(this,'edit_quest_icon_80x80');">
                                                    </div>
                                                </div>
                                                <div class="quest_icon_sec">
                                                    <h6></h6>
                                                    <div class="banner_container">
                                                        <img id="edit_quest_icon_80x80" class="quest_icon_80x80"/>
                                                    </div>
                                                </div>
                                                <input type="hidden" name="edit_icon_url" id="editIconUrl" class="form-control" readonly>
                                                <input type="hidden" name="QuestThumbnail" id="QuestThumbnail" >
                                                <input type="hidden" name="is_default_icon_flag" id="isEditDefaultBannerFlag" class="form-control" readonly>
                                                <span class="text-danger help-block m-b-0" id="editIconViewRequired" hide>*Icon is required.</span>
                                                <span>(jpeg, jpg, png)</span> 
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="col-sm-6 control-label label-css">Banner:<span class="text-danger"></span><p>(size:832*120)</p></label>
                                            <div class="col-md-6">
                                                <div class="quest_icon_sec upload_btn_sec">
                                                    <div class="fileUpload btn btn-md btn-info">
                                                        <span><?=lang("Upload") ?></span>
                                                        <input type="file" name="edit_userfile_banner[]" class="upload col-md-6" id="userfile_banner" onchange="uploadImage(this,'edit_quest_banner_80x80');">
                                                    </div>
                                                </div>
                                                <div class="quest_icon_sec">
                                                    <h6></h6>
                                                    <div class="banner_container">
                                                        <img id="edit_quest_banner_80x80" class="quest_banner_80x80"/>
                                                    </div>
                                                </div>
                                                <input type="hidden" name="edit_banner_url" id="editBannerUrl" class="form-control" readonly>
                                                <input type="hidden" name="QuestBannerThumbnail" id="QuestBannerThumbnail" >
                                                <input type="hidden" name="is_default_banner_flag" id="isEditDefaultBannerFlag" class="form-control" readonly>
                                                <span>(jpeg, jpg, png)</span>
                                            </div>
                                        </div>
                                    </div>                                    
                                </div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label for="editquestManagerDesc" class="col-sm-2 control-label label-css"><?=lang("cms.Note") ?>:</label>
                                            <div class="col-sm-10">
                                                <textarea name="editquestManagerDesc" id="editquestManagerDesc" class="form-control input-sm" style="width:100%" rows="10" required="" aria-invalid="true"></textarea>
                                            </div>
                                            <!-- <span class="text-danger help-block" id="addDescMaxCharacters" hide>*<?=lang('cms.Maximum 60 characters');?></span> -->
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div style = "margin: 15 15 15 10;font-size: 20px;">
                                            <b><?=lang('cms.allowedGameType')?></b>
                                        </div>
                                        <span id="editgame_tree_by_quest" style="color:red; font-size:10px;"></span>
                                        <div>
                                            <div class="form-group col-md-12">
                                                    <input type="checkbox" name="edit_auto_tick_new_games_in_game_type" id="edit_auto_tick_new_games_in_game_type" value="true">
                                                    <label class="control-label" for="edit_auto_tick_new_games_in_game_type"><strong><?php echo lang('promorules.Auto tick new games in game type'); ?></strong></label>
                                            </div>
                                            <div class="form-group col-md-12">
                                                    <input type="checkbox" name="edit_auto_tick_all_games_in_game_type" id="edit_auto_tick_all_games_in_game_type" value="true">
                                                    <label class="control-label" for="edit_auto_tick_all_games_in_game_type"><strong><?php echo lang('Select All'); ?></strong></label>
                                            </div>

                                            <div class="form-group col-md-12" id='treeAGT_sec'>
                                                <div class="form-group col-md-12" style="border:1px solid #ddd; margin-bottom:15px; padding:10px 0px 10px; 0px; overflow-y:scroll; overflow-x:scroll;">
                                                    <?php include APPPATH . "/views/marketing_management/quest/quest_game_list.php"; ?>
                                                    <?php //include APPPATH . "/views/includes/game_tree.php";?>
                                                    <input type="hidden" name="editselected_game_tree" value="">
                                                    <div id="editallowedGameTypeTree"></div>
                                                    <div id="allowed-promo-game-list-table" class="col-md-12"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>                            
                        </div>
                        <div class="col-md-7">
                            <div class="row">
                                <div class="col-md-5">
                                    <div class="form-group required">
                                        <label for="editquestConditionType" class="control-label label-css"><?=lang('cms.Mission task condition settings');?>:</label>
                                        <select class="form-control input-sm questConditionType" id = "editquestConditionType">
                                        </select>
                                    </div>
                                    <span class="text-danger help-block m-b-0" id="editRuleViewRequired" hide>*Rule is required.</span>
                                </div>
                                <div class="col-md-5">
                                    <div class="form-group">
                                        <input type="text" id = "editquestTitle" class="form-control input-control questTitle" placeholder="<?=lang('cms.Please input mission task title');?>*" style="margin-top: 25px;">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <div class = "questManagerContent hide" style="color: red;">
                                            <p><?=lang($questManagerContent)?></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div id="editquestRuleTag" class="table-responsive col-md-12">
                                    <table class="table table-striped table-hover" id="editquestRuleTable" style="margin: 0px 0 0 0; width: 100%;" >
                                        <thead>
                                            <tr>
                                                <td style = "width: 20px;"></td>
                                                <th style = "width: 10%;"><?=lang('cms.Title');?></th>
                                                <th style = "width: 25%;"><?=lang('cms.Condition');?></th>
                                                <th style = "width: 30%;"><?=lang('cms.Rewards');?></th>
                                                <th style = "width: 35%;"><?=lang('cms.Withdrawal request');?></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        </tbody>
                                    </table>
                                </div>
                                <div class = "col-md-2" style = "margin-top: 10px;" id = "add_condition">
                                    <div class="add" onclick = "addCol('edit');"></div>
                                    <div class="del" onclick = "delCol('edit');"></div>
                                    <input type = "hidden" name = "deleteRuleId">
                                </div>
                            </div>                            
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-linkwater" data-dismiss="modal"><?=lang('lang.cancel');?></button>
                    <input type="submit" class="btn btn-scooter" id="saveEditQuestManager"  <?php if ( ! $this->permissions->checkPermissions('quest_manager_edit')) echo 'disabled="disabled"' ?> value = '<?=lang('lang.save');?>'></button>
                </div>
            </div>
        </div>
    </div>
</form>

<div id="quest_manager_success" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">×</span>
                </button>
                <h5 class="modal-title"><?=lang('Quest Manager');?></h5>
            </div>
            <div class="modal-body text-center">
                <p class="f-20" id="successMsg"></p>
                <button type="button" class="btn btn-scooter" data-dismiss="modal"><?=lang('cms.OK');?></button>
            </div>
        </div>
    </div>
</div>

<!-- main manager -->
<div class="panel panel-primary">
    <div class="panel-heading custom-ph">
        <h4 class="panel-title custom-pt">
            <i class="icon-note"></i> <?=lang('cms.questManagerSetting');?>
            <!-- <span class="pull-right" style = 'margin: 0 4px;'>
                <a data-toggle="collapse" href="#details_panel_body" class="btn btn-info btn-xs" aria-expanded="false"></a>
            </span> -->

            <a href="javascript:void(0);" class="btn  pull-right btn-xs btn-info" id="addQuestManagerBtn"
                <?php if ( ! $this->permissions->checkPermissions('quest_manager_add')) echo 'style="display: none;"' ?> >
                <i class="fa fa-plus-circle"></i> <?=lang('cms.addNewQuestActivity');?>
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
                                    <th>Manager ID</th>
                                    <th>Rule ID</th>
                                    <th><?=lang('cms.mainMission');?></th>
                                    <th><?=lang('cms.title');?></th>
                                    <th><?=lang('cms.attribute');?></th>
                                    <th><?=lang('cms.startTime');?></th>
                                    <th><?=lang('cms.endTime');?></th>
                                    <th><?=lang('cms.createTime');?></th>
                                    <th><?=lang('cms.createBy');?></th>
                                    <th><?=lang('cms.status');?></th>
                                    <th><?=lang('cms.operate');?></th>
                                </tr>
                            </thead>

                            <tbody>
                            <?php if (!empty($questManager)) {
                                // var_dump($questManager);
                                foreach ($questManager as $row) { ?>
                                    <tr>
                                        <td></td>
                                        <td><?=$row['questManagerId']?></td>
                                        <td><?=$row['questRuleId']?></td>
                                        <th><?=lang($row['questCategoryTitle'])?></th>
                                        <td><?=($row['questTitle'] == null) ? $row['title'] : $row['questTitle']?></td>
                                        <th><?=($row['levelType']==1) ? lang("cms.singleQuest") : lang("cms.multipleQuest")?></th>
                                        <th><?=$row['startAt']?></th>
                                        <th><?=$row['endAt']?></th>
                                        <th><?=$row['createdAt']?></th>
                                        <th><?=$row['createdBy']?></th>
                                        <!-- <th><?=($row['status'] == 0) ? lang('cms.inactive') : lang('cms.active') ?></th> -->
                                        <th><?= $row['status'] == 0 ? '<p class="text-danger"><i class="glyphicon glyphicon-ban-circle"></i>' . lang('cms.inactive') . '</p>' : '<p class="text-success"><i class="glyphicon glyphicon-ok-circle"></i>' . lang('cms.active') . '</p>';?></th>
                                        <th>
                                            <div class="panel-heading custom-ph">
                                                <h4 class="panel-title custom-pt">
                                                    <a href="javascript:void(0);" class="btn btn-css btn-scooter" id="addQuestManagerBtn" onclick="getQuestManagerDetails(<?=$row['questManagerId']?>,<?= $this->language_function->getCurrentLanguage(); ?>, 'view')"
                                                       <?php 
                                                            if ($this->permissions->checkPermissions('quest_manager_edit') && $row['status'] != 1) echo 'style="display: none;"';?> >
                                                       <?=lang('cms.view');?>
                                                    </a>
                                                </h4>
                                                <h4 class="panel-title custom-pt">
                                                    <a href="javascript:void(0);" class="btn btn-css btn-scooter" id="addQuestManagerBtn" onclick="getQuestManagerDetails(<?=$row['questManagerId']?>,<?= $this->language_function->getCurrentLanguage(); ?>)"
                                                       <?php if ( ! $this->permissions->checkPermissions('quest_manager_edit')) echo 'style="display: none;"'; if($row['status'] != 0)  echo 'style="display: none;"';?> >
                                                       <?=lang('cms.edit');?>
                                                    </a>
                                                </h4>
                                                <h4 class="panel-title custom-pt">
                                                    <a href="javascript:void(0);" class="btn btn-css btn-linkwater" id="activeQuestManagerBtn" onclick = "changeStatus(<?=$row['questManagerId']?>, <?=$row['questCategoryId']?>, <?=$row['status']?>, <?=($row['questTitle'] == null) ? '\'' . $row['title'] . '\'' : '\'' . $row['questTitle'] . '\''?>)"
                                                        <?if($row['status'] == 0){
                                                            if ( ! $this->permissions->checkPermissions('quest_manager_enable')) echo 'style="display: none;"' ;
                                                        } else {
                                                            if ( ! $this->permissions->checkPermissions('quest_manager_disable')) echo 'style="display: none;"' ;
                                                        }?>>
                                                       <?=($row['status'] == 0) ? lang('cms.active') :  lang('cms.deactivate')?>
                                                    </a>
                                                </h4>
                                                <h4 class="panel-title custom-pt">
                                                    <a href="javascript:void(0);" class="btn pull-left btn-css btn-danger" id="deleteQuestManagerBtn" onclick = "deletedManager(<?=$row['questManagerId']?>, <?=$row['questCategoryId']?>, <?=($row['questTitle'] == null) ? '\'' . $row['title'] . '\'' : '\'' . $row['questTitle'] . '\''?>)"
                                                        <?php if ( ! $this->permissions->checkPermissions('quest_manager_delete')) echo 'style="display: none;"'; 
                                                              if($row['status'] != 0)  echo 'style="display: none;"';?>>
                                                       <?=lang('cms.delete');?>
                                                    </a>
                                                </h4>
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

        $('#forMultipleSetting input, #forMultipleSetting select').prop('disabled', true)
        $('#addDate').hide();
        $('#editDate').hide();
        $('#addDateViewRequired').hide();
        $('#editDateViewRequired').hide();
        $('#addIconViewRequired').hide();
        $('#editIconViewRequired').hide();
        $('#addRuleViewRequired').hide();
        $('#editRuleViewRequired').hide();
        optionMapping(1);

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
            "order": [ 6, 'desc' ]
        });        

        $('#add_quest_manager').on('hidden.bs.modal', function () {
           resetAddQuestCategoryInput();
        });

        $('#saveAddQuestManager').click(function(e){
            e.preventDefault();
            var notValidate = false;

            var selected_game=$('#allowedGameTypeTree').jstree('get_checked');
            // if(ENABLE_ISOLATED_PROMO_GAME_TREE_VIEW) {
            //     selected_game=$('#gameTree').jstree('get_checked'); //element #gameTree comes from promo_game_list.php
            // }
            console.log(selected_game);
            if(selected_game.length>0){
                $('input[name=selected_game_tree]').val(selected_game.join());
            }else{
                $('input[name=selected_game_tree]').val("");
                $('#game_tree_by_quest').text("<?php echo lang('Please choose one game at least') ?>");
                e.preventDefault();
                notValidate = true;
            }
            console.log($('input[name=selected_game_tree]').val());


            var form_id = document.getElementById('add_manager_form');
            var formData = new FormData(form_id);
            // formData.append('file', filQuestCatIcon);

            // var orderIdVal = $('#questCategoryOrderId').val();
            // var nameVal = $('#questCatecoryTitleView').val();
            var descVal = $('#questManagerDesc').val();
            var questCategoryId = $('#questCategoryId').val()
            var iconVal = $('#IconUrl').val();
            var showTimer = $('input[name="showTimer"]:checked').val();
            var line = $("#questRuleTag").find('tbody').find('tr').length;
            var withdrawalConditionType = $('select[name="withdrawalConditionType[]"]').val();
            var withdrawalValue = $('input[name="withdrawalValue[]"]').val();

            $('#addOrderMaxCharacters').hide();
            $('#addNameViewRequired').hide();
            $('#addNameViewMaxCharacters').hide();
            $('#addDescMaxCharacters').hide();
            $('#addDate').hide();
            $('#addDateViewRequired').hide();
            $('#addIconViewRequired').hide();
            $('#addRuleViewRequired').hide();


            // if(orderIdVal.length > '<?=Promo_type::PROMO_CATEGORY_ORDER_MAX_CHARACTERS?>'){
            //     $('#addOrderMaxCharacters').show();
            //     notValidate = true;
            // }
            // if(nameVal.length == 0){
            //     $('#addNameViewRequired').show();
            //     notValidate = true;
            // }
            // if(nameVal.length > '<?=Promo_type::PROMO_CATEGORY_NAME_MAX_CHARACTERS?>'){
            //     $('#addNameViewMaxCharacters').show();
            //     notValidate = true;
            // }
            if(descVal.length > '<?=Promo_type::PROMO_CATEGORY_INTERNAL_REMARK_MAX_CHARACTERS?>'){
                $('#addDescMaxCharacters').show();
                notValidate = true;
            }

            if(questCategoryId == ""){
                alert("請選擇主任務");
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
                $('#addIconViewRequired').show();
                notValidate = true;
            }

            if(line == 0){
                $('#addRuleViewRequired').show();
                notValidate = true;
            }

            $("#questRuleTag").find('tbody').find('tr').find('th').each(function() {
                let th = $(this);
                let input = th.find('input[name="questConditionValue[]"]');
                if (input.length > 0) {
                    let value = input.val();
                    if (value.trim() === '') {
                        alert("<?= lang('cms.questConditionValue') ?>");
                        notValidate = true;
                        return false;
                    }
                }
            });

            if(withdrawalConditionType != 0 && withdrawalValue < 1){
                alert("<?= lang('cms.withdrawalValueError') ?>");
                notValidate = true;
            }

            if(notValidate){
                return false;
            }else{
                $.ajax({
                    'url' : base_url + 'marketing_management/addQuestManager',
                    'type' : 'POST',
                    'dataType' : "json",
                    'data':formData,
                    'cache': false,
                    'contentType': false,
                    'processData': false,
                    'success' : function(data) {
                        if (data.success){
                            $('#quest_manager_success').modal('show');
                            $('#successMsg').text('<?=lang('cms.Quest Manager added');?>');
                            $('#add_quest_manager').modal('hide');
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

        $('#auto_tick_all_games_in_game_type').on('change', function() {
            if (this.checked) {
                $('#allowedGameTypeTree').jstree('check_all');
            } else {
                $('#allowedGameTypeTree').jstree('uncheck_all');
            }
        });

        $('#edit_auto_tick_all_games_in_game_type').on('change', function() {
            if (this.checked) {
                $('#editallowedGameTypeTree').jstree('check_all');
            } else {
                $('#editallowedGameTypeTree').jstree('uncheck_all');
            }
        });

        $('#saveEditQuestManager').click(function(e){
            e.preventDefault();
            var notValidate = false;

            var selected_game=$('#editallowedGameTypeTree').jstree('get_checked');
            // if(ENABLE_ISOLATED_PROMO_GAME_TREE_VIEW) {
            //     selected_game=$('#gameTree').jstree('get_checked'); //element #gameTree comes from promo_game_list.php
            // }
            console.log(selected_game);
            if(selected_game.length>0){
                $('input[name=editselected_game_tree]').val(selected_game.join());
            }else{
                $('input[name=editselected_game_tree]').val("");
                $('#editgame_tree_by_quest').text("<?php echo lang('Please choose one game at least') ?>");
                e.preventDefault();
                notValidate = true;
            }
            console.log($('input[name=editselected_game_tree]').val());

            var form_id = document.getElementById('edit_manager_form');
            var formData = new FormData(form_id);
            // formData.append('file', filEditQuestCatIcon);

            var descVal = $('#editquestManagerDesc').val();
            var questCategoryId = $('#editquestCategoryId').val()
            var iconVal = $('#editIconUrl').val();
            var showTimer = $('input[name="editshowTimer"]:checked').val();
            var line = $("#editquestRuleTag").find('tbody').find('tr').length;
            var withdrawalConditionType = $('select[name="editwithdrawalConditionType[]"]').val();
            var withdrawalValue = $('input[name="editwithdrawalValue[]"]').val();
            
            $('#editOrderMaxCharacters').hide();
            $('#editNameViewRequired').hide();
            $('#editNameViewMaxCharacters').hide();
            $('#editDescMaxCharacters').hide();
            $('#editDateViewRequired').hide();
            $('#editIconViewRequired').hide();
            $('#editRuleViewRequired').hide();

            if(descVal.length > '<?=Promo_type::PROMO_CATEGORY_INTERNAL_REMARK_MAX_CHARACTERS?>'){
                $('#editDescMaxCharacters').show();
                notValidate = true;
            }

            if(questCategoryId == ""){
                alert("請選擇主任務");
                notValidate = true;
            }
            
            if(showTimer == 1){
                $('#editshowTimerSetting input').each(function(){
                    if($(this).val() == ""){
                        $('#addDateViewRequired').show();
                        notValidate = true;
                    }
                });
            }

            if(iconVal.length == ""){
                $('#addIconViewRequired').show();
                notValidate = true;
            }

            if(line == 0){
                $('#addRuleViewRequired').show();
                notValidate = true;
            }

            $("#editquestRuleTag").find('tbody').find('tr').find('th').each(function() {
                let th = $(this);
                let input = th.find('input[name="editquestConditionValue[]"]');
                if (input.length > 0) {
                    let value = input.val();
                    if (value.trim() === '') {
                        alert("<?= lang('cms.questConditionValue') ?>");
                        notValidate = true;
                        return false;
                    }
                }
            });

            if(withdrawalConditionType != 0 && withdrawalValue < 1){
                alert("<?= lang('cms.withdrawalValueError') ?>");
                notValidate = true;
            }

            if(notValidate){
                return false;
            }else{
                $.ajax({
                    'url' : base_url + 'marketing_management/editQuestManager',
                    'type' : 'POST',
                    'dataType' : "json",
                    'data':formData,
                    'cache': false,
                    'contentType': false,
                    'processData': false,
                    'success' : function(data) {
                        if (data.success){
                            $('#quest_manager_success').modal('show');
                            $('#successMsg').text('<?=lang('cms.Quest Manager saved');?>');
                            $('#edit_quest_manager').modal('hide');
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

         // add game type
         game_type_tree();
        // end add game type

    });

    // $('.note-editor .note-toolbar .note-insert button[data-event~="showImageDialog"]').remove();

    
    $("#userfile").on('change', function() {
        $('.upload_req_txt').hide();
    });

    $("#edit_userfile").on('change', function() {
        $('.upload_req_txt').hide();
    });

    function changeLevelType(input, event){
        var line = $("#"+input+"questRuleTag").find('tbody').find('tr').length;
        if(line > 0){
            alert('請先刪除任務條件');
            event.preventDefault()
            return false;
        }
        levelType = $('input[name="'+input+'levelType"]:checked').val();
        showTimer = $('input[name="'+input+'showTimer"]:checked').val();

        var ele = "";
        
        ele = optionMapping(levelType, input);
        // $("#"+input+"questConditionType").append(ele);

        if(levelType == 1){
            $('#'+input+'forMultipleSetting input, #'+input+'forMultipleSetting select').prop('disabled', true)
        }else{
            $('#'+input+'forMultipleSetting input, #'+input+'forMultipleSetting select').prop('disabled', false)
        }

        if(showTimer == 1){
            $('#showTimerSetting input').prop('disabled', false)
        }else{
            $('#showTimerSetting input').prop('disabled', true)
        }
    };

    function changeShowTimer(input){
        showTimer = $('input[name="'+input+'showTimer"]:checked').val();
        if(showTimer == 1){
            $('#'+input+'showTimerSetting input').prop('disabled', false)
        }else{
            $('#'+input+'showTimerSetting input').prop('disabled', true)
        }
    };

    function addCol(input){
            
        var questConditionType = $("#"+input+"questConditionType").val();
        var questTitle = $("#"+input+"questTitle").val();
        let lineLimit = <?= $ladderQuestLimit ?>;

        if(questConditionType == 0 || questTitle == ""){
            alert("<?= lang('cms.questManagerConditionTitle') ?>");
            return false;
        }

        var line = $("#"+input+"questRuleTag").find('tbody').find('tr').length;

        if(line >= lineLimit){
            alert("<?= sprintf(lang('cms.questManagerLimit'), $ladderQuestLimit) ?>");
            return false;
        }
        
        ele = ruleTableMapping(input, questConditionType, line, questTitle);
        if(line==0){
            $("#"+input+"questRuleTag").find('tbody').append(ele);
        }else{
            if($('input[name="'+input+'levelType"]:checked').val()==1){
                alert('只能新增一筆任務條件');
                return false;
            }
            $("#"+input+"questRuleTag").find('tbody').find('tr:last').after(ele);
        }
    };

    function delCol(input){
        var line = $("#"+input+"questRuleTag").find('tbody').find('tr').length;
        var delRuleId = $("#"+input+"questRuleTag").find('tbody').find('tr:last').find('input[name="editquestRuleId[]"]').val();
        console.log(delRuleId);
        $("#"+input+"questRuleTag").find('tbody').find('tr:last').remove();
        $('input[name="'+input+'levelType"]').prop('disabled', false)
        var currectValue = $('input[name="deleteRuleId"]').val();
        if(delRuleId != ""){
            if(currectValue == ""){
                $('input[name="deleteRuleId"]').val(delRuleId);
            }else{
                $('input[name="deleteRuleId"]').val(currectValue + ',' + delRuleId);
            }
        }
    };

    function uploadImage(input,id) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function (e) {
                $('.upload_req_txt').hide();
                $('#'+id).attr('src', e.target.result).width(80).height(80);
                if(id == 'quest_icon_80x80' || id == 'edit_quest_icon_80x80'){
                    $('#IconUrl').val(input.files[0].name);
                    $('#editIconUrl').val(input.files[0].name);
                }
                if(id == 'quest_banner_80x80' || id == 'edit_quest_banner_80x80'){
                    $('#BannerUrl').val(input.files[0].name);
                    $('#editBannerUrl').val(input.files[0].name);
                }
            };
            reader.readAsDataURL(input.files[0]);
        }
    }

    $('#addQuestManagerBtn').click(function() {
        $('#add_quest_manager').modal('show');
        // $('.filQuestCatIcon').show();
        // $('#form_quest_name' ).each(function(){
        //     this.reset();
        // });
    });

    $('#quest_manager_success').on('hidden.bs.modal', function (e) {
        location.reload();
    });

    function getQuestManagerDetails(questCategoryId,currentLang,type=null){
        $('#edit_quest_manager').modal('show');
        $("#editquestRuleTable").find('tbody').find('tr').remove();
        $("#editstartDate").val("");
        $("#editstartTime").val("");
        $("#editendDate").val("");
        $("#editendTime").val("");
        $('input[name="deleteRuleId"]').val("");
        $('select[name="editdisplayPanel"]').val(0);
        $.ajax({
            'url' : base_url + 'marketing_management/getQuestManagerDetails/' + questCategoryId,
            'type' : 'GET',
            'dataType' : "json",
            'success' : function(data) {
                
                $("input[name*='editlevelType'][value='" + data[0].levelType +"']").prop("checked", true);
                $('select[name="editquestCategoryId"]').val(data[0].questCategoryId);
                $("input[name='editquestManagerId'").val(data[0].questManagerId);

                if(data[0].levelType == 2){
                    optionMapping(data[0].levelType, 'edit');

                    $('#editforMultipleSetting input, #editforMultipleSetting select').prop('disabled', false)
                    $('select[name="editdisplayPanel"]').val(data[0].displayPanel);
                    $("input[name*='editallowSameIpReceive'][value='" + data[0].allowSameIPBonusReceipt +"']").prop("checked", true);
                    $("input[name*='editshowOneClick'][value='" + data[0].showOneClick +"']").prop("checked", true);
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
                }else{
                    optionMapping(data[0].levelType, 'edit');
                    $('#editforMultipleSetting input, #editforMultipleSetting select').prop('disabled', true)
                }

                $('select[name="editquestManagerType"]').val(data[0].questManagerType);
                $("input[name*='editperiod'][value='" + data[0].period +"']").prop("checked", true);
                $('#edit_quest_icon_80x80').attr('src', data[0].icon_path);
                $('#edit_quest_banner_80x80').attr('src', data[0].banner_path);                
                $('#editBannerUrl').val(data[0].bannerPath);        
                $('#editIconUrl').val(data[0].iconPath);
                
                $("#editquestManagerDesc").val(data[0].description);

                if(data[0].auto_tick_new_game_in_cashback_tree=='1'){
                    $('#edit_auto_tick_new_games_in_game_type').prop('checked', true);
                }else{
                    $('#edit_auto_tick_new_games_in_game_type').prop('checked', false);
                }

                for(i = 0; i < data[1].length; i++){
                    title = (data[0].levelType == 1) ? data[0].title : data[1][i].title;
                    ele = ruleTableMapping('edit', data[1][i].questConditionType, i, title, data[1]);

                    if(i == 0){
                        $("#editquestRuleTable").find('tbody').append(ele);
                    }else{                    
                        $("#editquestRuleTable").find('tbody').find('tr:last').after(ele);
                    }

                    game_type_tree(data[0].questManagerId, 'edit');

                    if(data[1][i].isApply === "1"){
                        $('.questManagerContent').removeClass('hide');
                        $("#editquestRuleTable tbody tr:last input").prop('readonly', true);
                        let selectEle = $("#editquestRuleTable tbody tr:last select");
                        selectEle.prop('disabled', true);
                        selectEle.each(function() {
                            let selectValue = $(this).val();
                            let inputName = $(this).attr('name');
                            $(this).after('<input type="hidden" name="' + inputName + '" value="' + selectValue + '">');
                        });
                    }
                }

                if(type == 'view'){
                    $('#saveEditQuestManager').hide();
                    $('#edit_from_title').text("<?=lang('cms.viewQuestManager');?>")
                    $('#edit_quest_manager input').prop('disabled', true)
                    $('#edit_quest_manager select').prop('disabled', true)
                    $('#edit_quest_manager textarea').prop('disabled', true)
                    $('#add_condition').hide();
                }else{
                    $('#saveEditQuestManager').show();
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

    function changeStatus(id, categoryid, status, title){
        if(status == 0){
            check = confirm("<?=lang('cms.questActiveWithTitle')?> : " + title);
        }else{
            check = confirm("<?=lang('cms.questInactiveWithTitle')?> : " + title);
        }
        if(check){
            $.ajax({
                'url' : base_url + 'marketing_management/changeQuestManagerStatus',
                'type' : 'POST',
                'dataType' : "json",
                'data': {
                    'questManagerId' : id,
                    'questCategoryId' : categoryid,
                    'status' : status
                },
                'success' : function(res) {
                    let resultContent = res?.message || '';
                    let isJob = res.data?.isJob || false;

                    if (res.status == 'success' && isJob && res.data?.success) {
                        console.log('res.data', res.data);
                        window.open(res.data.redriectGenerateProgress, '_blank');
                    }

                    $('#quest_manager_success').modal('show');
                    $('#successMsg').text(resultContent);
                }
            });
        }
    }

    function deletedManager(id , categoryid){
        check = confirm("<?=lang('cms.questDelete')?>");
        if(check){
            $.ajax({
                'url' : base_url + 'marketing_management/deleteQuestManager',
                'type' : 'POST',
                'dataType' : "json",
                'data': {
                    'questManagerId' : id,
                    'questCategoryId' : categoryid
                },
                'success' : function(data) {
                    if (data.success){
                        var url="/marketing_management/quest_manager";
                        window.location.href=url;
                    } else {
                        alert('Delete Failed')
                    }
                }
            });
        }
    }

    function optionMapping(type, input=''){
        var key = [];
        let singleType = <?=json_encode($singleConditionType)?>;
        let multipleType = <?=json_encode($multipleConditionType)?>;

        if(type == 1){
            $key = singleType;
        }else{
            $key = multipleType;
        }
        $("#"+input+"questConditionType").find('option').remove();
        $('#'+input+'questConditionType').append('<option value="0"><?=lang('cms.Mission task condition type');?></option>');
        for (var i = 0; i < $key.length; i++) {
            $('#'+input+'questConditionType').append('<option value="'+$key[i]+'">'+optionNameMapping($key[i])+'</option>');
        }
    }

    function optionNameMapping(value){        
        switch(value){
            case 1: return "<?=lang('cms.Single deposit');?>"; break;
            case 2: return "<?=lang('cms.Accumlated Deposit');?>"; break;
            case 3: return "<?=lang('cms.Single bet');?>"; break;
            case 4: return "<?=lang('cms.Accumlated Bet');?>"; break;
            case 5: return "<?=lang('cms.Invite friends');?>"; break;
            case 6: return "<?=lang('cms.Registration');?>"; break;
            case 7: return "<?=lang('cms.Fill in personel information');?>"; break;
            case 8: return "<?=lang('cms.Login with app');?>"; break;
            case 9: return "<?=lang('cms.Follow channel');?>"; break;
            case 10: return "<?=lang('cms.Add community ID');?>"; break;
            case 11: return "<?=lang('cms.Bind and verify bank card');?>"; break;
            case 12: return "<?=lang('cms.Share social media');?>"; break;
            case 13: return "<?=lang('cms.Other');?>"; break;
        }
    }

    function ruleTableMapping(input, type, line, title, data = null){
        var questConditionType = "";
        var questConditionTypeEle = "";
        var withdrawalValueEle = "";
        var bonusConditionValueEle = "";
        var questConditionValueEle = "";
        var withdrawReqBetAmount = "";

        if(data != null){
            questConditionValueEle = `<input name = '` + input + `questConditionValue[]' type="text" class="form-control input-sm" style="width: 50%;display: inline;" value = `+ data[line].questConditionValue +`>`
            bonusConditionValueEle = `<input name = '` + input + `bonusConditionValue[]' type="text" class="form-control" style="width: 35%;" value = `+ data[line].bonusConditionValue +`>`;
            if(data[line].withdrawalConditionType == 1){
                withdrawalValueEle = ` <input name = '` + input + `withdrawalValue[]' type="number" class="form-control" min = 1 style="width: 35%;" value = `+ data[line].withdrawReqBetAmount +`>`;
            }else if(data[line].withdrawalConditionType == 2){
                withdrawalValueEle = ` <input name = '` + input + `withdrawalValue[]' type="number" class="form-control" min = 1 style="width: 35%;" value = `+ data[line].withdrawReqBettingTimes +`>`;
            }else if(data[line].withdrawalConditionType == 3){
                withdrawalValueEle = ` <input name = '` + input + `withdrawalValue[]' type="number" class="form-control" min = 1 style="width: 35%;" value = `+ data[line].withdrawReqBonusTimes +`>`;
            }else{
                withdrawalValueEle = ` <input name = '` + input + `withdrawalValue[]' type="number" class="form-control" min = 1 style="width: 35%;" disabled>`;
            }
        }else{
            questConditionValueEle = `<input name = '` + input + `questConditionValue[]' type="text" class="form-control input-sm" style="width: 50%;display: inline;">`
            bonusConditionValueEle = `<input name = '` + input + `bonusConditionValue[]' type="text" class="form-control" style="width: 35%;">`;
            withdrawalValueEle = ` <input step='any' name = '` + input + `withdrawalValue[]' type="number" class="form-control" min = 1 style="width: 35%;" disabled>`;
        }

        switch(type){
            case "1":
                questConditionType = "<?=lang('cms.Single deposit');?> >= ";
                questConditionTypeEle = `
                    <span>`+ questConditionType +` </span>
                    <input name = '` + input + `questConditionType[]' type="hidden" value = `+ type +`>`
                    questConditionTypeEle += questConditionValueEle;
                withdrawReqBetAmount = `<option value = "2"` + (data != null && data[line].withdrawalConditionType === '2' ? 'selected' : '') + `><?=lang('cms.(Deposit+Bonus)x');?></option>`
                break;
            case "2":
                questConditionType = "<?=lang('cms.Accumlated Deposit');?> >= ";
                questConditionTypeEle = `
                    <span>`+ questConditionType +` </span>
                    <input name = '` + input + `questConditionType[]' type="hidden" value = `+ type +`>`
                    questConditionTypeEle += questConditionValueEle;
                withdrawReqBetAmount = `<option value = "2"` + (data != null && data[line].withdrawalConditionType === '2' ? 'selected' : '') + `><?=lang('cms.(Deposit+Bonus)x');?></option>`
                break;
            case "3":
                questConditionType = "<?=lang('cms.Single bet');?> >= ";
                questConditionTypeEle = `
                    <span>`+ questConditionType +` </span>
                    <input name = '` + input + `questConditionType[]' type="hidden" value = `+ type +`>`
                    questConditionTypeEle += questConditionValueEle;
                break;
            case "4":
                questConditionType = "<?=lang('cms.Accumlated Bet');?> >= ";
                questConditionTypeEle = `
                    <span>`+ questConditionType +` </span>
                    <input name = '` + input + `questConditionType[]' type="hidden" value = `+ type +`>`
                    questConditionTypeEle += questConditionValueEle;
                break;
            case "5":
                questConditionType = "<?=lang('cms.Invite friends');?> >= ";
                questConditionTypeEle = `
                    <span>`+ questConditionType +` </span>
                    <input name = '` + input + `questConditionType[]' type="hidden" value = `+ type +`>`
                    questConditionTypeEle += questConditionValueEle;
                break;
            case "6":
                questConditionType = "<?=lang('cms.Registration completed');?>";
                questConditionTypeEle = `
                    <span>`+ questConditionType +` </span>
                    <input name = '` + input + `questConditionType[]' type="hidden" value = `+ type +`>`
                break;
            case "7":
                questConditionType = "<?=lang('cms.Fill in personel information option');?>";
                questConditionTypeEle = `
                    <input name = '` + input + `questConditionType[]' type="hidden" value = `+ type +`>
                    <select class = "form-control " name = "` + input + `personalInfoType" style = "float: left;width: 50%;">
                        <option value = "999" ` + (data != null && data[line].personalInfoType === '999' ? 'selected' : '') + `>`+ questConditionType +`</option>
                        <option value = "1" ` + (data != null && data[line].personalInfoType === '1' ? 'selected' : '') + `>First name + last name</option>
                        <option value = "2" ` + (data != null && data[line].personalInfoType === '2' ? 'selected' : '') + `><?=lang('cms.Phone number');?></option>
                        <option value = "3" ` + (data != null && data[line].personalInfoType === '3' ? 'selected' : '') + `>Email</option>
                        <option value = "4" ` + (data != null && data[line].personalInfoType === '4' ? 'selected' : '') + `>CPF</option>
                    </select>`
                break;
            case "8":
                questConditionType = "<?=lang('cms.Login with app successfully');?> ";
                questConditionTypeEle = `
                    <span>`+ questConditionType +` </span>
                    <input name = '` + input + `questConditionType[]' type="hidden" value = `+ type +`>`
                break;
            case "9":
                questConditionType = "<?=lang('cms.Follow channel completed');?>";
                questConditionTypeEle = `
                    <span>`+ questConditionType +` </span>
                    <input name = '` + input + `questConditionType[]' type="hidden" value = `+ type +`>`
                break;
            case "10":
                questConditionType = "<?=lang('cms.Community choices');?>";
                questConditionTypeEle = `
                    <input name = '` + input + `questConditionType[]' type="hidden" value = `+ type +`>
                    <select class = "form-control " name = "` + input + `communityOptions" style = "float: left;width: 50%;">
                        <option value = "999" ` + (data!= null && data[line].communityOptions === '999' ? 'selected' : '') + `>`+ questConditionType +`</option>
                        <option value = "1"` + (data!= null && data[line].communityOptions === '1' ? 'selected' : '') + `>Telegram</option>
                        <option value = "2"` + (data!= null && data[line].communityOptions === '2' ? 'selected' : '') + `>Skype</option>
                    </select>`
                break;
            case "11":
                questConditionType = "<?=lang('cms.Bind and verify bank card completed');?>";
                questConditionTypeEle = `
                    <span>`+ questConditionType +` </span>
                    <input name = '` + input + `questConditionType[]' type="hidden" value = `+ type +`>`
                break;
            case "12":
                questConditionType = "<?=lang('cms.Share social media');?>";
                questConditionTypeEle = `
                    <span>`+ questConditionType +` </span>
                    <input name = '` + input + `questConditionType[]' type="hidden" value = `+ type +`>`
                break;
            case "13":
                questConditionType = "<?=lang('cms.Others');?>";
                questConditionTypeEle = `
                    <span>`+ questConditionType +` </span>
                    <input name = '` + input + `questConditionType[]' type="hidden" value = `+ type +`>`
                break;
            default:
                break;
        }

        ele = `<tr>
                    <td>`+(line+1)+`
                        <input type = "hidden" name = "`+ input +`questRuleId[]" value = "`+ (data != null ? data[line].questRuleId : '') +`">
                        <input type = "hidden" name = "`+ input +`questJobId[]" value = "`+ (data != null ? data[line].questJobId : '') +`">
                    </td>
                    <th>`+ title +`
                        <input name = '` + input + `questTitle[]' type="hidden" value='` + title + `'>
                    </th>
                    <th>`+ questConditionTypeEle + `
                    </th>
                    <th>
                        <select class = "form-control " name = "`+ input +`bonusConditionType[]" style = "float: left;width: 50%;">
                            <option value = "0"` + (data != null && data[line].bonusConditionType === '0' ? 'selected' : '') + ` disabled = "disabled"><?=lang('cms.None');?></option>
                            <option value = "1"` + (data != null && data[line].bonusConditionType === '1' ? 'selected' : '') + `><?=lang('cms.Fixed bonus amount');?></option>
                        </select>
                        ` + bonusConditionValueEle + `
                    </th>
                    <th>
                        <select class = "form-control " name = "`+ input +`withdrawalConditionType[]" style = "float: left;width: 50%;" onchange = "checkValue(this)">
                            <option value = "0"` + (data != null && data[line].withdrawalConditionType === '0' ? 'selected' : '') + ` ><?=lang('cms.None');?></option>
                            <option value = "1" ` + (data != null && data[line].withdrawalConditionType === '1' ? 'selected' : '') + `><?=lang('cms.Bet amount');?></option>
                            `+ withdrawReqBetAmount +                            
                            `<option value = "3"` + (data != null && data[line].withdrawalConditionType === '3' ? 'selected' : '') + `><?=lang('cms.Bonus x');?></option>
                        </select>
                        ` + withdrawalValueEle + `
                    </th>
                </tr>`

        return ele;
    }
    function game_type_tree(managerId = "", input=""){
        var ENABLE_ISOLATED_PROMO_GAME_TREE_VIEW = "<?=$this->utils->isEnabledFeature('enable_isolated_promo_game_tree_view')?>";
        if(ENABLE_ISOLATED_PROMO_GAME_TREE_VIEW) {
            loadJstreeTable(
                tree_dom_id = '#gameTree',
                outer_tale_id = '#allowed-promo-game-list-table',
                summarize_table_id = '#summarize-table',
                get_data_url = "<?php echo site_url('/api/get_game_tree_by_quest/'); ?>" + "/" + managerId,
                input_number_form_sel = '#settingForm',
                default_num_value = "0",
                generate_filter_column = {
                    'Download Enabled': 'dlc_enabled',
                    'Mobile Enabled':   'mobile_enabled',
                    'progressive':      'progressive',
                    'Android Enabled':  'enabled_on_android',
                    'IOS Enabled':      'enabled_on_ios',
                    'Flash Enabled':    'flash_enabled',
                    'HTML5 Enabled':    'html_five_enabled'
                },
                filter_col_id = '#filter_col',
                filter_trigger_id = '#filterTree',
                use_input_number = false
            );
        }else {
            $('#'+input+'allowedGameTypeTree').jstree({
                'core' : {
                    'data' : {
                        "url" : "<?php echo site_url('/api/get_game_tree_by_quest/'); ?>" + "/" + managerId,
                        "dataType" : "json"
                    }
                },
                "input_number":{
                    "form_sel": '#promoform'
                },
                "checkbox":{
                    "tie_selection": false,
                },
                "plugins":[
                    "search","checkbox"
                ]
            });
        }
    }

    function checkValue(ele){
        console.log(ele)
        check = ele.value;
        console.log( ele.nextElementSibling.value);
        if(check == 0){
            ele.nextElementSibling.value = "";
            ele.nextElementSibling.disabled = true;
        }else{
            ele.nextElementSibling.disabled = false;
        }
    }

</script>