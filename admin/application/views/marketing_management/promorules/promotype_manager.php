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

/*start css OGP-17805*/
#add_promo_category .row,
#edit_promo_category .row{
  margin: 0 -15px;
}
#add_promo_category .row [class^='col-'],
#edit_promo_category .row [class^='col-']{
  padding: 0 15px;
}
#add_promo_category .upload-promo-img,
#edit_promo_category .upload-promo-img{
  border: 2px dashed #CCCCCC;
  width: 100%;
  height: 170px;
  padding: 14px;
}
#add_promo_category .filPromoCatIcon,
#edit_promo_category .filEditPromoCatIcon{
  cursor: pointer;
  position: relative;
  display: block;
  padding: 8px 0;
}
#add_promo_category .upload-promo-img-btn p,
#edit_promo_category .upload-promo-img-btn p{
  margin: 15px 0;
  font-size: 16px;
  color: #BABABA;
  line-height: 2;
}
#add_promo_category .upload-promo-img #imgPromoCatIcon,
#edit_promo_category .upload-promo-img #editImgPromoCatIcon{
  max-width: 100%;
  max-height: 135px;
  position: absolute;
  top: 0;
  left: 50%;
  transform: translateX(-50%);
}
#add_promo_category .clearPromoCatIcon,
#edit_promo_category .clearPromoCatIcon{
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
/*end css OGP-17805*/

</style>
<!--Add Promo Category-->
<form id="add_category_form">
    <div id="add_promo_category" class="modal fade" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                    </button>
                    <h5 class="modal-title"><?=lang('cms.addNewPromoType');?></h5>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-9">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="promoTypeName" class="control-label"><?=lang('cms.promoTypeOrder');?><span class="text-danger"></span></label>
                                        <input type="number" name="promoTypeOrderId" class="form-control input-sm"  step="1" min="1" id="promoTypeOrderId" onkeyup="value=value.match(/^[0-9]\d*$/)">
                                        <span class="text-danger help-block m-b-0" id="addOrderOnlyAllowDigits" hide></span>
                                        <span class="text-danger help-block m-b-0" id="addOrderMaxCharacters" hide>*<?=lang('cms.Maximum 3 characters');?></span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group required">
                                        <label for="promoTypeNameView" class="control-label"><?=lang('cms.promoTypeName');?><span class="text-danger"></span></label>
                                        <input type="text" name="promoTypeNameView" class="form-control input-sm" id="promoTypeNameView">
                                        <input type="hidden" id="promoTypeName" name="promoTypeName" class="form-control input-sm">
                                        <span class="text-danger help-block m-b-0" id="addNameViewRequired" hide>*<?=lang('cms.Column is required');?></span>
                                        <span class="text-danger help-block m-b-0" id="addNameViewMaxCharacters" hide>*<?=lang('cms.Maximum 24 characters');?></span>
                                    </div>
                                </div>
                            </div>
                            <hr class="hr_between_table">
                            <div>
                                <label><?=lang('cms.Display');?>:</label>
                                <br>
                                <div class="display-css">
                                <input type="radio" name="useToPromoManager" class="input-control" value="2" checked="checked" style="margin-right: 5;"><?=lang('cms.Hide When No Available Promo');?>
                                <input type="radio" name="useToPromoManager" class="input-control" value="1" style="margin-right: 5;"><?=lang('cms.Force Show');?>
                                <input type="radio" name="useToPromoManager" class="input-control" value="0" style="margin-right: 5;"><?=lang('cms.Force Hide');?>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <!-- <label for="filPromoCatIcon" class="control-label"><?=lang('cms.Promo Category Icon');?></label><br> -->
                            <div class="text-center upload-promo-img">
                                <label class="filPromoCatIcon">
                                    <input type="file" name="filPromoCatIcon[]" id="filPromoCatIcon" style="display: none;">
                                    <div class="upload-promo-img-btn">
                                        <p><i class="fa fa-plus-circle"></i><br><?=lang('Upload Image');?></p>
                                        <span class="text-danger text-left help-block"><?=lang('cms.upload_promo_Category_image_note');?></span>
                                    </div>
                                    <img src="/resources/images/no.png" id="imgPromoCatIcon" alt="" class="img-thumbnail" style="display: block;">
                                </label>
                                <button class="close clearPromoCatIcon" onclick="return clearPromoCatIcon();" style="display: none;"><i class="fa fa-times-circle"></i></button>
                            </div>
                        </div>
                        <div class="col-md-12 m-t-25">
                            <div class="form-group">
                                <label for="promoTypeDesc" class="control-label"><?=lang('cms.Remark');?>:</label><br>
                                <textarea name="promoTypeDesc" id="promoTypeDesc" class="form-control input-sm" style="width:100%" rows="10" required="" aria-invalid="true"></textarea>
                                <span class="text-danger help-block" id="addDescMaxCharacters" hide>*<?=lang('cms.Maximum 60 characters');?></span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-linkwater" data-dismiss="modal"><?=lang('lang.cancel');?></button>
                    <button type="button" class="btn btn-scooter" id="saveAddPromoCategory"><?=lang('lang.save');?></button>
                </div>
            </div>
        </div>
    </div>
</form>
<!--Edit Promo Category-->
<form id="edit_category_form">
    <div id="edit_promo_category" class="modal fade" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                    </button>
                    <h5 class="modal-title"><?=lang('cms.editPromoType');?></h5>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-9">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="promoTypeName" class="control-label"><?=lang('cms.promoTypeOrder');?><span class="text-danger"></span></label>
                                        <input type="text" name="editpromoTypeOrderId" class="form-control input-sm" id="editpromoTypeOrderId" onkeyup="value=value.match(/^[0-9]\d*$/)">
                                        <span class="text-danger help-block m-b-0" id="editOrderOnlyAllowDigits" hide></span>
                                        <span class="text-danger help-block m-b-0" id="editOrderMaxCharacters" hide>*<?=lang('cms.Maximum 3 characters');?></span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="editpromoTypeNameView" class="control-label"><?=lang('cms.promoTypeName');?><span class="text-danger"></span></label>
                                        <input type="text" name="editpromoTypeNameView" class="form-control input-sm" id="editpromoTypeNameView">
                                        <input type="hidden" name="promoTypeId" class="form-control" id="promoTypeId">
                                        <input type="hidden" id="editpromoTypeName" name="editpromoTypeName" class="form-control input-sm">
                                        <span class="text-danger help-block m-b-0" id="editNameViewRequired" hide>*<?=lang('cms.Column is required');?></span>
                                        <span class="text-danger help-block m-b-0" id="editNameViewMaxCharacters" hide>*<?=lang('cms.Maximum 24 characters');?></span>
                                    </div>
                                </div>
                            </div>
                            <hr class="hr_between_table">
                            <div>
                                <label><?=lang('cms.Display');?>:</label>
                                <br>
                                <div class="display-css">
                                <input type="radio" name="editUseToPromoManager" value="2" class="input-control" checked="checked" style="margin-right: 5;"><?=lang('cms.Hide When No Available Promo');?>
                                <input type="radio" name="editUseToPromoManager" value="1" class="input-control" style="margin-right: 5;"><?=lang('cms.Force Show');?>
                                <input type="radio" name="editUseToPromoManager" value="0" class="input-control" style="margin-right: 5;"><?=lang('cms.Force Hide');?>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <!-- <label for="filEditPromoCatIcon" class="control-label"><?=lang('cms.Promo Category Icon');?></label><br> -->
                            <div class="text-center upload-promo-img">
                                <label class="filEditPromoCatIcon">
                                    <input type="file" name="filEditPromoCatIcon[]" id="filEditPromoCatIcon" style="display: none;">
                                    <div class="upload-promo-img-btn">
                                        <p><i class="fa fa-plus-circle"></i><br><?=lang('Upload Image');?></p>
                                        <span class="text-danger text-left help-block"><?=lang('cms.upload_promo_Category_image_note');?></span>
                                    </div>
                                    <img src="/resources/images/no.png" id="editImgPromoCatIcon" alt="" class="img-thumbnail" style="display: block;">
                                    <input type="hidden" id="promoIconFileName">
                                </label>
                                <button class="close clearPromoCatIcon" onclick="return clearPromoCatIcon();" style="display: none;"><i class="fa fa-times-circle"></i></button>
                            </div>
                        </div>
                        <div class="col-md-12 m-t-25">
                            <div>
                                <button class="btn btn-danger btn-sm btn-radius removePromoCatIcon" onclick="return removePromoCatIcon();" style="display: none;float: right; margin-bottom: 5px;"><?= lang('cms.deleteIcon') ?></button>
                            </div>
                            <div class="form-group">
                                <label for="editpromoTypeDesc" class="control-label"><?=lang('cms.Remark');?>:</label>
                                <textarea name="editpromoTypeDesc" id="editpromoTypeDesc" class="form-control input-sm" style="width:100%" rows="10" required="" aria-invalid="true"></textarea>
                                <span class="text-danger help-block" id="editDescMaxCharacters" hide>*<?=lang('cms.Maximum 60 characters');?></span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-linkwater" data-dismiss="modal"><?=lang('lang.cancel');?></button>
                    <button type="button" class="btn btn-scooter" id="saveEditPromoCategory"><?=lang('lang.save');?></button>
                </div>
            </div>
        </div>
    </div>
</form>

<div id="promo_category" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">×</span>
                </button>
                <h5 class="modal-title"><?=lang('Promotion Category');?></h5>
            </div>
            <div class="modal-body text-center">
                <p class="f-20" id="successMsg"></p>
                <button type="button" class="btn btn-scooter" data-dismiss="modal" onclick="addNewPromoTypeSuccess()"><?=lang('cms.OK');?></button>
            </div>
        </div>
    </div>
</div>

<div class="panel panel-primary">
    <div class="panel-heading custom-ph">
        <h4 class="panel-title custom-pt">
            <i class="icon-note"></i> <?=lang('cms.promoTypeList');?>

            <a href="javascript:void(0);" class="btn  pull-right btn-xs btn-info" id="addPromoTypeBtn">
                <i class="fa fa-plus-circle"></i> <?=lang('cms.addNewPromoType');?>
            </a>
        </h4>
    </div>
    <div class="panel-body" id="details_panel_body">
        <div class="row">
            <div class="col-md-12">
                <form action="<?=BASEURL . 'marketing_management/deleteSelectedPromoType'?>" method="post" role="form">

                    <button type="submit" class="btn btn-danger btn-sm" data-toggle="tooltip" data-placement="top" title="<?=lang('cms.deletesel');?>">
                        <i class="glyphicon glyphicon-trash" style="color:white;"></i>
                    </button>
                    <hr class="hr_between_table"/>

                    <div id="tag_table" class="table-responsive">
                        <table class="table table-striped table-hover" id="my_table" style="margin: 0px 0 0 0; width: 100%;">
                            <thead>
                                <tr>
                                    <th></th>
                                    <th style="padding: 8px;"><input type="checkbox" id="checkWhite" onclick="checkAll(this.id)"/></th>
                                    <th id="default_sort_paymentTyperOrder"><?=lang('cms.promoTypeOrder');?></th>
                                    <th><?=lang('cms.promoTypeName');?></th>
                                    <th><?=lang('cms.promoTypeDesc');?></th>
                                    <th><?=lang('cms.createdby');?></th>
                                    <th><?=lang('cms.updatedby');?></th>
                                    <th><?=lang('pay.createdon');?></th>
                                    <th><?=lang('pay.updatedon');?></th>
                                    <th><?=lang('lang.action');?></th>
                                </tr>
                            </thead>

                            <tbody>
                            <?php if (!empty($promoType)) {
                                foreach ($promoType as $row) { ?>
                                    <tr>
                                        <td></td>
                                        <td style="padding: 8px;"><input type="checkbox" class="checkWhite" id="<?=$row['promotypeId']?>" name="promoType[]" value="<?=$row['promotypeId']?>" onclick="uncheckAll(this.id)"/></td>
                                        <td><?=$row['promotypeOrder']?></td>
                                        <td><?=lang($row['promoTypeName'])?></td>
                                        <td><?=$row['promoTypeDesc'] ? $row['promoTypeDesc'] : lang('player.tm06')?></td>
                                        <td><?=$row['createdBy']?></td>
                                        <td><?=$row['updatedBy'] ? $row['updatedBy'] : lang('lang.norecord')?></td>
                                        <td><?=$row['createdOn']?></td>
                                        <td><?=$row['updatedOn']?></td>
                                        <td>
                                                <a href="javascript:void(0);" id="editPromoType">
                                                    <span class="glyphicon glyphicon-edit" data-toggle="tooltip" title="<?=lang('cms.editPromoType');?>"  data-placement="top" onclick="getPromotypeDetails(<?=$row['promotypeId']?>,<?= $this->language_function->getCurrentLanguage(); ?>)">
                                                    </span>
                                                </a>
                                                <a href="<?=BASEURL . 'marketing_management/fakeDeletePromoType/' . $row['promotypeId']?>">
                                                    <span class="glyphicon glyphicon-trash delete-promo" data-toggle="tooltip" title="<?=lang('cms.deletePromoType');?>"  data-placement="top">
                                                    </span>
                                                </a>
                                        </td>
                                    </tr>
                                <?php }
                            } ?>
                            </tbody>
                        </table>
                        <!-- <div class="col-md-12 col-offset-0">
                            <ul class="pagination pagination-sm" style="margin: 0; padding: 0;"> <?php echo $this->pagination->create_links(); ?> </ul>
                        </div> -->
                    </div>
                </form>

            </div>
        </div>
    </div>
    <div class="panel-footer"></div>
</div>

<?php include('promo_type_name_modal.php') ?>

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
          //submenu
        $('#collapseSubmenu').addClass('in');
        $('#promoCategorySettings').addClass('active');
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

        $('#add_promo_category').on('hidden.bs.modal', function () {
           resetAddPromoCatInput();
        });

        $('#saveAddPromoCategory').click(function(e){
            e.preventDefault();

            var form_id = document.getElementById('add_category_form');
            var formData = new FormData(form_id);
            formData.append('file', filPromoCatIcon);

            var orderIdVal = $('#promoTypeOrderId').val();
            var nameVal = $('#promoTypeNameView').val();
            var descVal = $('#promoTypeDesc').val();
            var notValidate = false;

            $('#addOrderMaxCharacters').hide();
            $('#addNameViewRequired').hide();
            $('#addNameViewMaxCharacters').hide();
            $('#addDescMaxCharacters').hide();

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

            if(notValidate){
                return false;
            }else{
                $.ajax({
                    'url' : base_url + 'marketing_management/addPromoType',
                    'type' : 'POST',
                    'dataType' : "json",
                    'data':formData,
                    'cache': false,
                    'contentType': false,
                    'processData': false,
                    'success' : function(data) {
                        if (data.success){
                            $('#promo_category').modal('show');
                            $('#successMsg').text('<?=lang('cms.Promo Category added');?>');
                            $('#add_promo_category').modal('hide');
                        } else {
                            $('#saveAddPromoCategory').attr('disabled', true);
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
                            }
                        }
                    }
                });
            }
        });

        $('#saveEditPromoCategory').click(function(e){
            e.preventDefault();
            var form_id = document.getElementById('edit_category_form');
            var formData = new FormData(form_id);
            formData.append('file', filEditPromoCatIcon);

            var orderIdVal = $('#editpromoTypeOrderId').val();
            var nameVal = $('#editpromoTypeNameView').val();
            var descVal = $('#editpromoTypeDesc').val();
            var notValidate = false;

            $('#editOrderMaxCharacters').hide();
            $('#editNameViewRequired').hide();
            $('#editNameViewMaxCharacters').hide();
            $('#editDescMaxCharacters').hide();

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

            if(notValidate){
                return false;
            }else{
                $.ajax({
                    'url' : base_url + 'marketing_management/editPromoType',
                    'type' : 'POST',
                    'dataType' : "json",
                    'data':formData,
                    'cache': false,
                    'contentType': false,
                    'processData': false,
                    'success' : function(data) {
                        if (data.success){
                            $('#promo_category').modal('show');
                            $('#successMsg').text('<?=lang('cms.Promotion Category saved');?>');
                            $('#edit_promo_category').modal('hide');
                        } else {
                            $('#saveEditPromoCategory').attr('disabled', true);
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
                            }
                        }
                    }
                });
            }
        });
    });

    $('#addPromoTypeBtn').click(function() {
        $('#add_promo_category').modal('show');
        $('.filPromoCatIcon').show();
        $('.clearPromoCatIcon, .removePromoCatIcon').hide();
        $('#imgPromoCatIcon').attr('src', '/resources/images/no.png');
        $('#form_promo_name' ).each(function(){
            this.reset();
        });
    });

    function addNewPromoTypeSuccess(){
        var url="/marketing_management/promoTypeManager";
        window.location.href=url;
    }

    function getPromotypeDetails(promotypeId,currentLang){
        $('#edit_promo_category').modal('show');
        $('.clearPromoCatIcon, .removePromoCatIcon').hide();

        $.ajax({
            'url' : base_url + 'marketing_management/getPromoTypeDetails/' + promotypeId,
            'type' : 'GET',
            'dataType' : "json",
            'success' : function(data) {
                if (data[0].promoTypeName.toLowerCase().indexOf("_json:") >= 0){
                    var langConvert = jQuery.parseJSON(data[0].promoTypeName.substring(6));
                    $('#editpromoTypeNameView').val(langConvert[currentLang]);
                    $('#editpromoTypeName').val(data[0].promoTypeName);
                } else {
                    $('#editpromoTypeNameView').val(data[0].promoTypeName);
                    $('#editpromoTypeName').val(data[0].promoTypeName);
                }
                $('#editpromoTypeDesc').val(data[0].promoTypeDesc);
                $('#promoTypeId').val(data[0].promotypeId);
                $("input[name*='editUseToPromoManager'][value='" + data[0].isUseToPromoManager +"']").prop("checked", true);
                $('#editImgPromoCatIcon').attr('src', data[0].icon_path);
                $('#editpromoTypeOrderId').val(data[0].promotypeOrder);
                $('#promoIconFileName').val(data[0].promoIcon);
                if(data[0].promoIcon){
                    $('.removePromoCatIcon').show();
                }else{
                    $('.filEditPromoCatIcon').show();
                }
            }
         });
        $("html, body").animate({ scrollTop: 0 }, "slow");
    }

    function resetAddPromoCatInput(){
        $('#promoTypeOrderId').val('');
        $('#promoTypeNameView').val('');
        $('#promoTypeDesc').val('');
        $("input[name*='useToPromoManager'][value='2']").prop("checked", true);
        clearPromoCatIcon();
    }

    function clearPromoCatIcon(){
        var promoTypeId = $('#promoTypeId').val();

        if(promoTypeId){
            $('#editImgPromoCatIcon').attr('src', '/resources/images/no.png');
            $('.clearPromoCatIcon').hide();
        }else{
            $('#imgPromoCatIcon').attr('src', '/resources/images/no.png');
            $('.clearPromoCatIcon').hide();
        }

        return false;
    }

    function removePromoCatIcon(){
        var file = $('#promoIconFileName').val();
        if(!file){
            alert('This promo category icon is not exist now!');
            return false;
        }

        var c = confirm('Are you sure you want to remove promo category icon ?');
        if(!c){
            return false;
        }

        var promoTypeId = $('#promoTypeId').val();

        $.ajax({
            'url' : base_url + 'marketing_management/removePromoCatIcon',
            'type' : 'POST',
            'dataType' : "json",
            'data' : {
                promoTypeId: promoTypeId,
            },
            'success' : function(data) {
                if (data.status){
                    alert(data.message);
                    window.location.reload();
                } else {
                    alert(data.message);
                }
            }
        });

        return false;
    }

    function checkAll(id) {
        var list = document.getElementsByClassName(id);
        var all = document.getElementById(id);

        if (all.checked) {
            for (i = 0; i < list.length; i++) {
                list[i].checked = 1;
            }
        } else {
            all.checked;

            for (i = 0; i < list.length; i++) {
                list[i].checked = 0;
            }
        }
    }

    function readURL(input, imgSelector) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();

            reader.onload = function (e) {
                $('#'+imgSelector).attr('src', e.target.result);
            };

            reader.readAsDataURL(input.files[0]);
        }
    }

    $("#filPromoCatIcon").change(function(){
        readURL(this, 'imgPromoCatIcon');
        $('.clearPromoCatIcon').show();
    });

    $("#filEditPromoCatIcon").change(function(){
        readURL(this, 'editImgPromoCatIcon');
        $('.clearPromoCatIcon').show();
    });

</script>