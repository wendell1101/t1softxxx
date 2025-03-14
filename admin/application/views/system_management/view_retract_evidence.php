
<form   method="post" id="add-update-evidence"role="form" >

    <div class="row">    
        <div class="col-sm-12"> 
            <input type="hidden"  id="evidence_id"  name="evidence_id"   value="<?php echo $evidence_id ?>"   class="form-control input-sm" />

            <div class="form-group">
                <label for="live_key"><?=lang('iovation_evidence.comment');?></label>
                <textarea class="form-control" id="comment"  name="comment" rows="3"></textarea>
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