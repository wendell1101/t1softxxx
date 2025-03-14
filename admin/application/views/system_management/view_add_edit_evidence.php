
<form   method="post" id="add-update-evidence"role="form" >

    <div class="row">    
        <div class="col-sm-12"> 

            <div class="form-group">       
                <label for="newId"><?=lang('iovation_evidence.applied_to_type');?></label>
                <?php if(!empty($evidence)):?>
                <input class="form-control" id="evidence_appliedto_type" name="evidence_appliedto_type" value="<?php echo $evidence_appliedto ?>" readonly>
                <?php else: ?>
                <select class="form-control" id="evidence_appliedto_type" name="evidence_appliedto_type">                
                    <?php foreach($appliedto_type_list  as  $key =>  $value): ?>
                    <?php if(!empty($evidence)):?>  
                    <option <?php  echo $evidence_appliedto == $value ? 'selected' : "" ?> value="<?php echo $key ?>"> <?=lang($value);?> </option>
                    <?php else: ?>
                    <option value="<?php echo $key ?>"> <?=lang($value)?> </option>
                    <?php endif; ?>
                    <?php endforeach; ?>
                </select>
                <?php endif; ?>             
                <label><i>account</i> evidence will be flagged to an account<br>
                <i>device</i> evidence will be flagged to the device</label>
            </div>

            <?php if(empty($evidence)):?> 
                <div class="form-group">
                    <label for="evidence_appliedto_type_value"></label>
                    <input type="text"  id="evidence_appliedto_type_value"  name="evidence_appliedto_type_value"   value=""   class="form-control input-sm"  placeholder="<?php echo lang('Device ID, Player Username') ?>"/>  
                    <label>Use player/affiliate username if using <i>account</i> evidence type</label>
                </div> 
                <div class="form-group"> 
                    <label for="user_type"><?=lang('iovation_evidence.user_type');?></label> 
                    <select class="form-control" id="user_type" name="user_type">
                        <?php foreach($user_type_list  as  $key =>  $value): ?>
                        <option value="<?php echo $key ?>"> <?php echo $value ?> </option>
                        <?php endforeach; ?>
                    </select>
                </div>                
            <?php else: ?>
                <div class="form-group">
                    <label for="evidence_appliedto_type_value"></label>
                    <input type="text"  id="evidence_appliedto_type_value"  name="evidence_appliedto_type_value"   value="<?php echo $evidence_appliedto_type_value ?>"   class="form-control input-sm"  placeholder="<?php echo lang('Device ID, Username') ?>" readonly/>  
                </div> 
                <div class="form-group">
                    <label for="user_type"></label>
                    <input type="text"  id="user_type"  name="user_type"   value="<?php echo $user_type ?>"   class="form-control input-sm"  placeholder="<?php echo lang('Player/Affiliate') ?>" readonly/>  
                </div> 
                <input type="hidden"  id="evidence_id"  name="evidence_id"   value="<?php echo $evidence_id ?>"   class="form-control input-sm" placeholder="<?php echo lang('Device ID, Player Username') ?>"/>                
            <?php endif; ?>

            <div class="form-group">       
                <label for="newId"><?=lang('iovation_evidence.evidence_type');?></label>
                <?php if(!empty($evidence)):?>
                    <input type="hidden"  id="evidence_type"  name="evidence_type"   value="<?php echo $evidence->evidence_type ?>"   class="form-control input-sm"/>
                    <input type="text"  id="evidence_type_text"  name="evidence_type_text"   value="<?php echo $evidence_type_text ?>"   class="form-control input-sm" readonly/>
                <?php else: ?>
                    <select class="form-control" id="evidence_type" name="evidence_type" <?php  echo !empty($evidence) ? 'readonly' : '' ?>>
                        <?php foreach($evidence_type_list  as  $key =>  $value): ?>
                        <option value="<?php echo $key ?>"> <?php echo $value ?> </option>
                        <?php endforeach; ?>
                    </select>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="live_key"><?=lang('iovation_evidence.comment');?></label>
                <?php if(!empty($evidence)):?> 
                <textarea class="form-control" id="comment"  name="comment" rows="3"><?php echo $evidence->comment ?></textarea>
                <?php else: ?>
                <textarea class="form-control" id="comment"  name="comment" rows="3"></textarea>
                <?php endif; ?>
            </div>     
        </div><!-- col-sm-12 -->
    </div><!-- row1 -->

    <div class="row" >
        <div class="col-md-12">

            <div class="modal-footer" style="position:relative;height:50px">
            <div class="alert alert-danger alert-dismissible text-left" id="add-update-error-msg" style="width:70%;visibility:hidden;position:absolute;"></div>
            <button type="button" id="close-add-update-modal"  class="btn btn-default" data-dismiss="modal"><?php  echo lang('lang.cancel')?></button>
            <button id="add-update-button"  type="submit" class="btn btn-info"><?php echo lang('lang.submit')?></button>
        </div>
    </div><!-- row1 -->
    </form>

 <script>


$(document).ready(function(){ 


    $('#add-update-evidence').submit(function(event){
    event.preventDefault();

    $('#add-update-button').attr('disabled', 'disabled').html("<?php echo lang('Please wait') ?>...");

    $('#close-add-update-modal').attr('disabled', 'disabled');

    var url = '<?php echo $form_add_edit_url;?>';
    var params = $(this).serializeArray(); 
    var type='POST';

    //call ajax
    $.ajax({
        method: type,
        url: url,
        data: params,
        dataType: "json"
    })
    .done(function(data){
        if(data.status=='success'){
            notify('success',data.msg );  
            if($('#form-modal').is(':visible')){
                $('#form-modal').modal('hide');
            } 
            //dataTable.ajax.reload();
        }else{
            notify('danger',data.msg);          
        }
                 
    }).fail(function(data) {
        notify('danger','<?php echo lang('sys.ga.erroccured') ?>' ); 
                   
    })
    .complete(function(data) {        
      $('#add-update-button').removeAttr('disabled').html("<?php echo lang('lang.submit')?>");
      $('#close-add-update-modal').removeAttr('disabled').html("<?php echo lang('lang.cancel')?>");
    });
    
    return false;
  });



});



</script>