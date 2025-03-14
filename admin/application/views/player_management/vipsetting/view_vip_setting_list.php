<div class="panel panel-primary">
    <div class="panel-heading custom-ph">
        <h4 class="panel-title custom-pt">
            <i class="icon-diamond"></i> <?=lang('player.sd02');?>
            <a href="javascript:void(0)" class="btn pull-right btn-xs btn-info" id="add_vip_group">
                <span id="add_vip_group_glyhicon" class="glyphicon glyphicon-plus-sign"></span> <?=lang('con.vsm16');?>
            </a>
            <div class="clearfix"></div>
        </h4>
    </div>
    <div class="panel-body" id="vip_add_edit_panel">
        <!-- add vip group -->
        <div class="row add_vip_group_sec">
            <div class="col-md-12">
                <div class="well" style="overflow: auto">
                    <form id="add_vip_group_form" action="<?=site_url('vipsetting_management/addVipGroup')?>" class="form-horizontal" method="post" role="form" class="form-inline" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-3 i_required">
                                <label class="control-label" for="groupName"><?=lang('player.grpname');?>: </label>
                                <input type="text" id="groupNameView" class="form-control input-sm"/>
                                <input type="hidden" id="groupName" name="groupName" class="form-control input-sm"/>
                            </div>
                            <div class="col-md-2 i_required">
                                <label class="control-label" for="groupLevelCount"><?=lang('player.grplvlcnt');?>: </label>
                                <input type="text" id="groupLevelCount" name="groupLevelCount" class="form-control input-sm" maxlength="2"/>
                            </div>
                            <div class="col-md-4 i_required">
                                <label class="control-label" for="groupDescription"><?=lang('pay.description');?>: </label>
                                <input type="text" id="groupDescription" name="groupDescription" class="form-control input-sm"/>
                            </div>
                            <div class="col-md-3"></div>
                            <div class="col-md-12">
                                <label class="control-label">
                                    <input type="checkbox" name="can_be_self_join_in" value="true"/> <?=lang('Player can choose to join group')?>
                                </label>
                                <input class="file_input" type="file" name="vip_cover" id="vip_cover" style="display: none;"/>
                                <input type="checkbox" name="vip_default_cover" id="vip_default_cover" hidden/>
                            </div>
                        </div>
                        <div class="pull-right">
                            <?php if ($this->utils->isEnabledMDB() && $this->utils->_getSyncVipGroup2othersWithMethod('SyncMDBSubscriber::syncMDB') ) : ?>
                                <label class="control-label" for="sync_vip_group_to_others" >
                                    <?=lang('Sync To Currency');?>
                                    <!-- readonly in checkbox, ref. to https://stackoverflow.com/a/12267350 -->
                                    <input type="checkbox"
                                            name="sync_vip_group_to_others"
                                            id="sync_vip_group_to_others"
                                            value="sync_vip_group_to_others"
                                            checked="checked"
                                            onclick="return false;" onkeydown="e = e || window.event; if(e.keyCode !== 9) return false;" />
                                </label>
                                &nbsp;
                                <input type="button" value="<?=lang('lang.add');?>" class="btn btn-sm btn-linkwater review-btn btn_add_vip_group"/>
                            <?php else: ?>
                                <input type="submit" value="<?=lang('lang.add');?>" class="btn btn-sm btn-linkwater review-btn" data-toggle="modal" />
                            <?php endif; ?>
                            &nbsp;
                            <span class="btn btn-sm addvip-cancel-btn btn-scooter" data-toggle="modal" /><?=lang('lang.cancel');?></span>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- edit vip group -->
        <div class="row edit_vip_group_sec">
            <div class="col-md-12">
                <div class="well" style="overflow: auto">
                    <form id="edit_vip_group_form" action="<?=site_url('vipsetting_management/addVipGroup')?>" class="form-horizontal" method="post" role="form" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-3 i_required">
                                <label class="control-label" for="groupName"><?=lang('player.grpname');?>: </label>
                                <input type="text" id="editGroupNameView" class="form-control input-sm">
                                <input type="hidden" id="editGroupName" name="groupName" class="form-control input-sm">
                                <input type="hidden" readonly id="editVipGroupId" name="vipGroupId" class="form-control input-sm">
                                <input type="hidden" id="editGroupLevelCount" name="groupLevelCount" class="form-control input-sm">
                                <input type="hidden" id="editImage" name="image_name" class="form-control input-sm">
                            </div>
                            <div class="col-md-4 i_required">
                                <label class="control-label" for="editGroupDescription"><?=lang('pay.description');?>: </label>
                                <input type="text" id="editGroupDescription" name="groupDescription" class="form-control input-sm"/>
                            </div>
                            <div class="col-md-5"></div>
                            <div class="col-md-12">
                                <label class="control-label">
                                    <input type="checkbox" name="can_be_self_join_in" id="can_be_self_join_in" value="true"/> <?=lang('Player can choose to join group')?>
                                </label>
                                <input class="file_input" type="file" name="vip_cover[]" id="vip_edit_cover" style="display: none;">
                                <input type="checkbox" name="vip_default_cover" id="edit_vip_default_cover" hidden>
                            </div>
                        </div>
                        <div class="pull-right">
                            <input type="submit" value="<?=lang('lang.edit');?>" class="btn btn-sm btn-linkwater review-btn" data-toggle="modal" />
                            <span class="btn btn-sm editvip-cancel-btn btn-scooter" data-toggle="modal" /><?=lang('lang.cancel');?></span>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="panel panel-primary">
    <div class="panel-heading custom-ph">
        <h4 class="panel-title custom-pt">
            <i class="icon-diamond"></i> <?=lang('player.sd12');?>
        </h4>
    </div>
    <div class="panel-body" id="details_panel_body">
        <div class="row">
            <div class="col-md-12">
                <div class="table-responsive"  >
                    <form action="<?=site_url('vipsetting_management/deleteSelectedVip')?>" method="post" role="form">
                        <div id="tag_table">
                            <div class="clearfix"></div>
                            <table class="table table-bordered table-hover" id="my_table">
                                <thead>
                                    <tr>
                                        <th><?=lang('player.grpname');?></th>
                                        <th><?=lang('player.grplvlcnt');?></th>
                                        <th><?=lang('Player can choose to join group')?></th>
                                        <th><?=lang('pay.description');?></th>
                                        <th><?=lang('cms.createdon');?></th>
                                        <th><?=lang('cms.createdby');?></th>
                                        <th><?=lang('cms.updatedon');?></th>
                                        <th><?=lang('cms.updatedby');?></th>
                                        <th><?=lang('lang.display_status');?></th>
                                        <th><?=lang('lang.action');?></th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <?php
                                    if (!empty($data)) {
                                        foreach ($data as $datai) { ?>
                                        <tr>
                                            <td><?=$datai['groupName'] == '' ? '<i class="text-muted">' . lang("lang.norecord") . '<i/>' : anchor(site_url('vipsetting_management/viewVipGroupRules/' . $datai['vipSettingId']), lang($datai['groupName']));?></td>
                                            <td><?=$datai['groupLevelCount'] == '' ? '<i class="text-muted">' . lang("lang.norecord") . '<i/>' : $datai['groupLevelCount']?></td>
                                            <td><?=$datai['can_be_self_join_in'] ? lang('lang.yes') : lang('lang.no') ?></td>
                                            <td><?=$datai['groupDescription'] == '' ? '<i class="text-muted">' . lang("lang.norecord") . '<i/>' : $datai['groupDescription']?></td>
                                            <td><?=$datai['createdOn'] == '' ? '<i class="text-muted">' . lang("lang.norecord") . '<i/>' : $datai['createdOn']?></td>
                                            <td><?=$datai['createdBy'] == '' ? '<i class="text-muted">' . lang("lang.norecord") . '<i/>' : $datai['createdBy']?></td>
                                            <td><?=$datai['updatedOn'] == '' ? '<i class="text-muted">' . lang("lang.norecord") . '<i/>' : $datai['updatedOn']?></td>
                                            <td><?=$datai['updatedBy'] == '' ? '<i class="text-muted">' . lang("lang.norecord") . '<i/>' : $datai['updatedBy']?></td>
                                            <td><?=$datai['status'] == '' ? '<i class="text-muted">' . lang("cms.nodailymaxwithdrawal") . '<i/>' : $datai['status']?></td>
                                            <td>
                                                <div class="actionVipGroup">
                                                    <?php if ($datai['status'] == 'active') {?>
                                                        <a href="<?=site_url('vipsetting_management/activateVIPGroup/' . $datai['vipSettingId'] . '/' . 'inactive')?>">
                                                            <span data-toggle="tooltip" title="<?=lang('lang.deactivate');?>" class="glyphicon glyphicon-ok-sign" data-placement="top">
                                                            </span>
                                                        </a>
                                                    <?php } else {?>
                                                        <a href="<?=site_url('vipsetting_management/activateVIPGroup/' . $datai['vipSettingId'] . '/' . 'active')?>">
                                                            <span data-toggle="tooltip" title="<?=lang('lang.activate');?>" class="glyphicon glyphicon-remove-circle" data-placement="top">
                                                            </span>
                                                        </a>
                                                    <?php } ?>

                                                    <span class="glyphicon glyphicon-edit editVipGroupSettingBtn" data-toggle="tooltip" title="<?=lang('lang.edit');?>" onclick="PlayerManagementProcess.getVIPGroupDetails(<?=$datai['vipSettingId']?>,<?= $this->language_function->getCurrentLanguage(); ?>)" data-placement="top">
                                                    </span>
                                                </div>
                                            </td>
                                        </tr>
                                            <?php
                                        }
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <div class="panel-footer"></div>
</div>


<!-- Start Upload Icon Modal -->
    <div class="modal fade " id="uploadVipBanner" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
        <div class="modal-dialog" role="document" style="width: 30%">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title" id="myModalLabel"><?php echo lang('Upload cover');?></h4>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 text-center">
                            <div class="presetIconType">
                              <img class="presetIconImg btn" id="default_icon_show" src="<?=$this->utils->imageUrl('vip_cover/default_vip_cover.jpeg')?>" width="150px;" height="150px">
                            </div>
                            <div class="">
                              <input type="checkbox" name="set_default_image" data-type="" id="set_default_image"><?php echo lang("Use default cover") ?>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="presetIconType">
                              <img class="presetIconImg btn" id="select_cover" src="<?=$this->utils->imageUrl('og-login-logo.png')?>" width="150px;" height="150px">
                            </div>
                            <button style="width: 150px;" type="button" class="btn btn-default btn-xs btn_upload_cover" data-type=""><?php echo lang('Upload cover');?></button>
                            <br><span style="margin-top: 2px;">80px x 80px</span><br>
                            <span>JPEG,PNG,GIF</span><br>
                            <span><?php echo lang("File must not exceed 2MB.");?></span>
                        </div>
                    </div>
                    <div class="form-group text-center" style="padding-top: 30px;">
                        <input type="button"  value="<?=lang('lang.close');?>" class="btn btn-default btn-sm btn_cancel_modal" data-dismiss="modal"/>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php $defaul_image = $this->utils->imageUrl('tutorial/default_tutorial_icon-img.png');
    ?>
<!-- End Upload Icon Modal -->

<!-- Start groupName Modal -->
<div class="modal fade" id="groupNameModal" tabindex="-1" role="dialog" aria-labelledby="mainModalLabel">
    <div class="modal-dialog" role="document">
        <input type="hidden" id="groupNameModalTarget">
        <div class="modal-content">
            <form role="form" id="form_group_name">
                <div class="modal-header">
                    <h4 class="modal-title" id="groupNameLabel"><?=lang('player.grpname');?></h4>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12">
                            <?php $group_name_language_fields = $this->config->item('group_name_language_fields'); ?>
                            <?php if(!empty($group_name_language_fields)) : ?>
                                <?php foreach ($group_name_language_fields as $key => $value) : ?>
                                    <div class="form-group">
                                        <label for="group_name_<?=$value?>"><?=lang("lang.".$value.".name")?> </label>
                                        <input type="text" class="form-control clear-fields" id="group_name_<?=$value?>" name="group_name[]">
                                        <span class="help-block hidden" style="color:#F04124"><?= sprintf(lang('formvalidation.required'),lang("lang.".$value.".name"))?></span>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="form-group">
                                    <label for="group_name_english"><?=lang("lang.english.name")?> </label>
                                    <input type="text" class="form-control clear-fields" id="group_name_english" name="group_name[]">
                                    <span class="help-block hidden" style="color:#F04124"><?= sprintf(lang('formvalidation.required'),lang("lang.english.name"))?></span>
                                </div>
                                <div class="form-group">
                                    <label for="group_name_chinese"><?=lang("lang.chinese.name")?> </label>
                                    <input type="text" class="form-control clear-fields" id="group_name_chinese" name="group_name[]">
                                    <span class="help-block hidden" style="color:#F04124"><?= sprintf(lang('formvalidation.required'),lang("lang.chinese.name"))?></span>
                                </div>
                                <div class="form-group">
                                    <label for="group_name_indonesian"><?=lang("lang.indonesian.name")?> </label>
                                    <input type="text" class="form-control clear-fields" id="group_name_indonesian" name="group_name[]">
                                    <span class="help-block hidden" style="color:#F04124"><?= sprintf(lang('formvalidation.required'),lang("lang.indonesian.name"))?></span>
                                </div>
                                <div class="form-group">
                                    <label for="group_name_vietnamese"><?=lang("lang.vietnamese.name")?> </label>
                                    <input type="text" class="form-control clear-fields" id="group_name_vietnamese" name="group_name[]">
                                    <span class="help-block hidden" style="color:#F04124"><?= sprintf(lang('formvalidation.required'),lang("lang.vietnamese.name"))?></span>
                                </div>
                                <div class="form-group">
                                    <label for="group_name_korean"><?=lang("lang.korean.name")?> </label>
                                    <input type="text" class="form-control clear-fields" id="group_name_korean" name="group_name[]">
                                    <span class="help-block hidden" style="color:#F04124"><?= sprintf(lang('formvalidation.required'),lang("lang.korean.name"))?></span>
                                </div>
                                <div class="form-group">
                                    <label for="group_name_thai"><?=lang("lang.thai.name")?> </label>
                                    <input type="text" class="form-control clear-fields" id="group_name_thai" name="group_name[]">
                                    <span class="help-block hidden" style="color:#F04124"><?= sprintf(lang('formvalidation.required'),lang("lang.thai.name"))?></span>
                                </div>
                                <div class="form-group">
                                    <label for="group_name_india"><?=lang("lang.india.name")?> </label>
                                    <input type="text" class="form-control clear-fields" id="group_name_india" name="group_name[]">
                                    <span class="help-block hidden" style="color:#F04124"><?= sprintf(lang('formvalidation.required'),lang("lang.india.name"))?></span>
                                </div>
                                <div class="form-group">
                                    <label for="group_name_portuguese"><?=lang("lang.portuguese.name")?> </label>
                                    <input type="text" class="form-control clear-fields" id="group_name_portuguese" name="group_name[]">
                                    <span class="help-block hidden" style="color:#F04124"><?= sprintf(lang('formvalidation.required'),lang("lang.portuguese.name"))?></span>
                                </div>
                                <div class="form-group">
                                    <label for="group_name_spanish"><?=lang("lang.spanish.name")?> </label>
                                    <input type="text" class="form-control clear-fields" id="group_name_spanish" name="group_name[]">
                                    <span class="help-block hidden" style="color:#F04124"><?= sprintf(lang('formvalidation.required'),lang("lang.spanish.name"))?></span>
                                </div>
                                <div class="form-group">
                                    <label for="group_name_kazakh"><?=lang("lang.kazakh.name")?> </label>
                                    <input type="text" class="form-control clear-fields" id="group_name_kazakh" name="group_name[]">
                                    <span class="help-block hidden" style="color:#F04124"><?= sprintf(lang('formvalidation.required'),lang("lang.kazakh.name"))?></span>
                                </div>
                        <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="modal-footer" >
                    <div style="height:70px;position:relative;">
                        <button type="button" class="btn btn-linkwater" data-dismiss="modal"><?=lang('lang.close');?></button>
                        <button type="button" class="btn btn-scooter"  onclick="return validateGroupName();"><?=lang('Done')?></button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- End groupName Modal -->


<?php
    // Moved to admin/application/views/includes/vipsetting_sync.php
    include __DIR__ . '/../../includes/vipsetting_sync.php';
?>

<?php if($this->utils->isEnabledFeature('export_excel_on_queue')){?>
    <form id="_export_excel_queue_form" class="hidden" method="POST" target="_blank">
        <input name='json_search' type="hidden">
    </form>
<?php }?>

<script type="text/javascript">
    var group_name_language_fields_length = "<?=(!empty($group_name_language_fields) ? count($group_name_language_fields) : 10)?>";
    function validateGroupName(){
        var form = $("#form_group_name");
        var inputNames = form.find('input[name^="group_name"]');
        var groupNames = {1:"",2:"",3:"",4:"",5:"",6:"",7:"",8:"",9:"",10:""};

        form.find('.hidden').length
        form.find('span').addClass("hidden");

        if ( $("#group_name_english").val().length == 0 ) {
            $("#group_name_english").next().removeClass("hidden")
        }

        if( form.find('.hidden').length == group_name_language_fields_length ) {
            inputNames.each(function(index) {
                switch($(this).attr('id')) {
                    case "group_name_english":
                        groupNames[1] = $(this).val();
                        break;
                    case "group_name_chinese":
                        groupNames[2] = $(this).val();
                        break;
                    case "group_name_indonesian":
                        groupNames[3] = $(this).val();
                        break;
                    case "group_name_vietnamese":
                        groupNames[4] = $(this).val();
                        break;
                    case "group_name_korean":
                        groupNames[5] = $(this).val();
                        break;
                    case "group_name_thai":
                        groupNames[6] = $(this).val();
                        break;
                    case "group_name_india":
                        groupNames[7] = $(this).val();
                        break;
                    case "group_name_portuguese":
                        groupNames[8] = $(this).val();
                        break;
                    case "group_name_spanish":
                        groupNames[9] = $(this).val();
                        break;
                    case "group_name_kazakh":
                        groupNames[10] = $(this).val();
                        break;
                }
            });

            $.each( groupNames, function( key, value ) {
              if(value == ""){
                groupNames[key] = groupNames[1];
              }
            });

            var jsonPretty = '_json:'+JSON.stringify(groupNames);
            var init = jsonPretty;
            var currentLang = "<?= $this->language_function->getCurrentLanguage(); ?>";
            var target =  $("#groupNameModalTarget").val();

            if( target === "groupName" ){
                $("#groupNameView").val(groupNames[currentLang]);
                $("#groupName").val(jsonPretty);
            } else if( target === "editGroupName" ) {
                $("#editGroupNameView").val(groupNames[currentLang]);
                $("#editGroupName").val(jsonPretty);
            }

            $('#groupNameModal').modal('hide');
        }
    }

    $(document).on("click","#editGroupNameView",function(){
        $("#form_group_name").find('span').addClass("hidden");
        $("#form_group_name").find('span').text('');

        var inputNames = $('#editGroupName').val();
        if( inputNames.indexOf("_json:") >= 0 ) {
        var langConvert = jQuery.parseJSON(inputNames.substring(6));
            $("#form_group_name input[type=text]").each(function(index){
                $(this).val(langConvert[index+1]);
            });
        } else {
            $("#form_group_name input[type=text]").val(inputNames);
        }
        $("#groupNameModalTarget").val('editGroupName');
        $('#groupNameModal').modal('show');
    });

    $(document).on("click","#groupNameView",function(){
        $("#form_group_name").find('span').addClass("hidden");
        $("#groupNameModalTarget").val('groupName');
        $('#groupNameModal').modal('show');
    });

    $(document).on("click",".btn_pencil",function(){
        $eText = "<?=lang('lang.edit');?>";
        $uText = "<?=lang('sys.ga.update.button');?>";
        $check = $(this).hasClass('edit_vip_welcome');
        if($check){
            $(this).closest('.form-group').find('input').prop('disabled',false);
            $(this).addClass('update_vip_welcome').removeClass('edit_vip_welcome');
            $(this).find('i').removeClass('fa-pencil').addClass('fa-pencil-square-o');

        }else{
            $(this).closest('.form-group').find('input').prop('disabled',true);
            $(this).addClass('edit_vip_welcome').removeClass('update_vip_welcome');
            $(this).find('i').removeClass('fa-pencil-square-o').addClass('fa-pencil');
        }
    });
    $(document).on("click",".update_vip_welcome",function(){
        $main           = $("#main_text").val();
        $sub            = $("#sub_text").val();
        $vip_button1    = $("#vip_button1").val();
        $vip_button2    = $("#vip_button2").val();
        $vip_button3    = $("#vip_button3").val();
        $vip_button4    = $("#vip_button4").val();
        $url = "<?php echo "http://" . $this->utils->getSystemHost('admin') . "/vipsetting_management/updateVipWelcomeText"?>";

        $.post( $url, { main : $main, sub: $sub, b1 : $vip_button1, b2 : $vip_button2, b3 : $vip_button3, b4 : $vip_button4} ,function( data ) {
            console.log(data);
        });
    });
    $(document).on("click",".btn_uCover",function(){
        $type = $(this).data('type');
        $('.btn_upload_cover').data('type', $type);
        $('#set_default_image').data('type', $type);
    });
    $(document).on("click",".btn_upload_cover",function(){
        $type = $(this).data('type');
        if($type == "add"){
            $("#vip_cover").click();
        }
        else{
            $("#vip_edit_cover").click();
        }
    });
    $(document).on("click","#set_default_image",function(){
        $type = ($(this).data('type') == "add") ? "vip_default_cover" : "edit_vip_default_cover" ;
        var check = $(this).is(':checked');
        if(check){
            $('#'+$type).prop('checked', true);
        }else{
            $('#'+$type).prop('checked', false);
        }
    });
    $(document).on("change","#vip_cover,#vip_edit_cover",function(){
        readURL(this);
        $(".btn_uCover").text("View selected cover");
    });

    function readURL(input) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();

            reader.onload = function (e) {
                $('#select_cover').attr('src', e.target.result);
            }

            reader.readAsDataURL(input.files[0]);
        }
    }

    <?php echo $this->utils->generateLangArray(array('con.vsm16')); ?>

    $(document).ready(function(){
        var dataTable = $('#my_table').DataTable({
            searching: true,
            autoWidth: false,

            <?php if($this->utils->isEnabledFeature('column_visibility_report')){ ?>
                stateSave: true,
            <?php } else { ?>
                stateSave: false,
            <?php } ?>
            dom: "<'panel-body' <'pull-right'B><'pull-right'f><'pull-right progress-container'>l><'dt-information-summary1 text-info pull-left' i>t<'text-center'r><'panel-body'<'pull-right'p><'dt-information-summary2 text-info pull-left' i>>",
            buttons: [
                {
                    extend: 'colvis',
                    className:'btn-linkwater',
                    postfixButtons: [ 'colvisRestore' ]
                },
                <?php if( $this->permissions->checkPermissions('export_vip_group_manager') ){ ?>
                {
                    text: "<?php echo lang('CSV Export'); ?>",
                    className:'btn btn-sm btn-portage',
                    action: function ( e, dt, node, config ) {
                        var d = {};
                        $.post(site_url('/export_data/export_vip_setting_list'), d, function(data){
                            if(data && data.success){
                                $('body').append('<iframe src="'+data.link+'" frameborder="0" scrolling="no" style="border:0px;width:0px;height:0px"></iframe>');
                            }else{
                                alert('export failed');
                            }
                        });
                    }
                }
                <?php } ?>
            ],
            columnDefs: [
                { sortable: false, targets: [ 0 ] },
            ],
            order: [[1, 'asc']],
            drawCallback: function () {
                if ( dataTable.rows( { selected: true } ).indexes().length === 0 ) {
                    dataTable.buttons().disable();
                }
                else {
                    dataTable.buttons().enable();
                }
            }
        });
    });
</script>

<!-- Script for add/edit form validation -->
<script type="text/javascript">
    $(document).on("submit","#add_vip_group_form",function(){
        var errors = 0;
        $("#groupNameView, #groupLevelCount ,#groupDescription").map(function(){
             if( !$(this).val() ) {
                  $(this).parents('.i_required').addClass('has-error');
                  errors++;
            } else if ($(this).val()) {
                  $(this).parents('.i_required').removeClass('has-error');
            }
        });
        if(errors > 0){
             $(this).find('.container .form_alert').remove();
             $(this).find('.container').prepend('<div class="alert alert-danger fade in form_alert">\
                                              <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>\
                                              <strong><?=lang('player.mp14');?></strong>.\
                                            </div>');
            return false;
        }
    });

    $(document).on("submit","#edit_vip_group_form",function(){
        var errors = 0;
        $("#editGroupName ,#editGroupDescription").map(function(){
             if( !$(this).val() ) {
                  $(this).parents('.i_required').addClass('has-error');
                  errors++;
            } else if ($(this).val()) {
                  $(this).parents('.i_required').removeClass('has-error');
            }
        });
        if(errors > 0){
            $(this).find('.container .form_alert').remove();
            $(this).find('.container').prepend('<div class="alert alert-danger fade in form_alert">\
                                              <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>\
                                              <strong><?=lang('player.mp14');?></strong>.\
                                            </div>');;
            return false;
        }
    });

    $(document).on("keydown","#groupLevelCount",function(e){
        // Allow: backspace, delete, tab, escape, enter and .
        if ($.inArray(e.keyCode, [46, 8, 9, 27, 13, 190]) !== -1 ||
             // Allow: Ctrl+A, Command+A
            (e.keyCode === 65 && (e.ctrlKey === true || e.metaKey === true)) ||
             // Allow: home, end, left, right, down, up
            (e.keyCode >= 35 && e.keyCode <= 40)) {
                 // let it happen, don't do anything
                 return;
        }
        // Ensure that it is a number and stop the keypress
        if ((e.shiftKey || (e.keyCode < 48 || e.keyCode > 57)) && (e.keyCode < 96 || e.keyCode > 105)) {
            e.preventDefault();
        }
    });

    $(document).on("click",".editvip-cancel-btn, .addvip-cancel-btn, #add_vip_group",function(){
        $('.i_required').removeClass('has-error');
        $('.form_alert').remove();
    });

    $(document).on("click",".sync_btn .btn_vip_group",function(e){
        var target$El = $(e.target);
        if( $.isEmptyObject( target$El.data('vipsettingid') )
            && $(e.target).find('[data-vipsettingid]').length > 0
        ){
            target$El = $(e.target).find('[data-vipsettingid]');
        }
        // TODO, data-vipsettingid
        console.log('clicked.btn_vip_group.vipsettingid:', target$El.data('vipsettingid'), target$El );


        var _url = '<?=site_url("Vipsetting_Management/sync_vip_group")?>/'+ target$El.data('vipsettingid');
        var jqXHR = $.ajax({
            type: 'POST',
            url: _url,
            dataType : "json",
            cache: false,
            // data: {},
            contentType:false,          // The content type used when sending data to the server.
            cache:false,                // To unable request pages to be cached
            processData:false,          // To send DOMDocument or non processed data file it is set to false
            beforeSend: function (jqXHR, settings) {
                // targetBtn$El.button('loading');
                // beforeSendCB.apply(_this, arguments);
                // $('.btn_batch_add_via_csv').button('loading');
            },
            complete: function (jqXHR, textStatus) {
                // targetBtn$El.button('reset');
                // completeCB.apply(_this, arguments);

                // var _form_batch_player_csv$El = $('input[type=file][name="batch_player_csv"]').closest('form');
                // _form_batch_player_csv$El.trigger('reset');

                // $('.btn_batch_add_via_csv').button('reset');
            }
        });
        jqXHR.done(function (data, textStatus, jqXHR) {
            // _this.dataTable.ajax.reload(null, false); // user paging is not reset on reload
            // $('#deleteWithdrawalConditionModal').modal('hide');
            console.log('sync_vip_group.done().data', data);
        });


    }); // EOF $(document).on("click",".sync_btn .btn_vip_group",function(e){

</script>


<script type="text/javascript">
    $(document).ready(function() {
        var vipsetting_sync =  VIPSETTING_SYNC.init({
            DRY_RUN_MODE_LIST: gDRY_RUN_MODE_LIST
            , CODE_DECREASEVIPGROUPLEVEL: gCODE_DECREASEVIPGROUPLEVEL
        });
        vipsetting_sync.assignLangList2Options(theLangList4vipsetting_sync);
        // vipsetting_sync.onReady();
        vipsetting_sync.onReadyInView('<?=pathinfo(basename(__FILE__), PATHINFO_FILENAME); // aka. view_vip_setting_list ?>');
    });

</script>

<style type="text/css">
.btn_vip_group{
	cursor: pointer;
    color: #3F61B4;
}

</style>
