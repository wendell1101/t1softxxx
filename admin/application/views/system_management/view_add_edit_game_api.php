
<form   method="post" id="add-update-game-form"role="form" >

  <?php if(!empty($gameApi)):?>  
    <!--<input type="hidden"  id="status" name="status" value="<?php echo $gameApi->status ?>"/>-->
  <?php endif; ?>

  <div class="row">    
    <div class="col-md-6"> 
      <div class="form-group">
       <div class="form-group inline" style="padding-bottom:32px;">
        <label for="live_mode"><?=lang('sys.ga.livemode');?></label>
        <?php if(!empty($gameApi)):?>
          <input type="checkbox"  id="live_mode"  name="live_mode"  value="1"   <?php echo ($gameApi->live_mode == '1') ? 'checked' : '' ?>  class="checkbox-inline" />
          <?php else: ?>
            <input type="checkbox"  id="live_mode"  name="live_mode"  value="1"  class="checkbox-inline" />
          <?php endif; ?>
           <label for="seamless"><?=lang('sys.ga.seamless');?></label>
          <?php if(!empty($gameApi)):?>
             <input type="checkbox"  id="seamless"  name="seamless" <?php echo ($gameApi->seamless == '1') ? 'checked' : ''?> value="1"  class="checkbox-inline" />
          <?php else: 
            $force_enable_seamless_api = $this->utils->getConfig('force_enable_seamless_api');
            $seamless_main_wallet_reference_enabled = $this->utils->getConfig('seamless_main_wallet_reference_enabled');
          ?>
             <input type="checkbox"  id="seamless"  name="seamless" <?php echo ($force_enable_seamless_api || $seamless_main_wallet_reference_enabled) ? 'checked' : ''?>  value="1" class="checkbox-inline" />
          <?php endif; ?>

          <label for="flag_show_in_site"><?=lang('sys.ga.flag_show_in_site');?></label>
          <?php if(!empty($gameApi)):?>
             <input type="checkbox"  id="flag_show_in_site"  name="flag_show_in_site" <?php echo ($gameApi->flag_show_in_site == '1') ? 'checked' : ''?> value="1"  class="checkbox-inline" />
          <?php else: ?>
             <input type="checkbox"  id="flag_show_in_site"  name="flag_show_in_site"  value="1" class="checkbox-inline" checked/>
          <?php endif; ?>

        </div>
        <?php if(!empty($gameApi)):?>
         <input type="hidden" id="status" name="status"   value="<?php echo ($gameApi->status)?>" />
         <?php else: ?>
           <input type="hidden" id="status" name="status"  value="1" />
         <?php endif; ?> 
        <label for="newId">ID:</label>
        <select class="form-control" id="new_id" name="new_id">
         <?php foreach($game_apis_arr  as  $key =>  $value): ?>
           <?php if(!empty($gameApi)):?>  
            <option <?php  echo $gameApi->id == $value ? 'selected' : "" ?> value="<?php echo $value ?>"> <?php echo $value.'-'. $key ?> </option>
            <?php else: ?>
             <option value="<?php echo $value ?>"> <?php echo $value.'-'. $key ?> </option>
           <?php endif; ?>
         <?php endforeach; ?>
       </select>
     </div>
     <div class="form-group">
      <label for="last_sync_datetime"><?=lang('sys.ga.lastsyncdt')?>:</label>
      <?php if(!empty($gameApi)):?>  
        <input type="text" class="form-control" name="last_sync_datetime" value="<?php echo $gameApi->last_sync_datetime ?>" id="last_sync_datetime">
        <?php else: ?>
         <input type="text" class="form-control" name="last_sync_datetime" value="" id="last_sync_datetime">
       <?php endif; ?>
     </div>

     <div class="form-group">
      <label for="note"><?=lang('sys.ga.note')?>:</label>
      <?php if(!empty($gameApi)):?>  
        <textarea class="form-control" rows="3" name="note" id="note"><?php echo $gameApi->note ?></textarea>
        <?php else: ?>
          <textarea class="form-control" rows="3" name="note" id="note"></textarea>
        <?php endif; ?>
      </div>
      <div class="form-group">
        <label for="system_type"><?php echo lang('sys.ga.systemtype') ?>:</label>
        <select class="form-control" name="system_type" id="system_type">
          <?php if(!empty($gameApi)):?> 
           <option value="<?php echo SYSTEM_GAME_API ?>" <?php ($gameApi->system_type == SYSTEM_GAME_API) ? 'selected' : ""?>  ><?php echo lang('sys.game.api'); ?> </option>
           <option value="<?php echo SYSTEM_PAYMENT ?>" <?php ($gameApi->system_type == SYSTEM_PAYMENT) ? 'selected' : ""?>  ><?php echo lang('sys.payment.api'); ?> </option>
           <?php else: ?>
            <option value="<?php echo SYSTEM_GAME_API ?>" selected ><?php echo lang('sys.game.api'); ?> </option>
            <option value="<?php echo SYSTEM_PAYMENT ?>" ><?php echo lang('sys.payment.api'); ?> </option>
          <?php endif; ?>
        </select>
      </div>
      <div class="form-group">
        <label for="class_name"><?=lang('sys.ga.classname');?></label>
        <?php if(!empty($gameApi)):?> 
          <input type="text"  id="class_name"  name="class_name"   value="<?php echo $gameApi->class_name ?>" class="form-control input-sm" />
          <?php else: ?>
           <input type="text"  id="class_name"  name="class_name"   value="" class="form-control input-sm" />
         <?php endif; ?>
       </div>
       <div class="form-group">
        <label for="manager"><?=lang('sys.ga.manager');?></label>
        <?php if(!empty($gameApi)):?> 
         <input type="text"  id="manager"  name="manager"   value="<?php echo $gameApi->manager ?>"   class="form-control input-sm" />
         <?php else: ?>
          <input type="text"  id="manager"  name="manager"   value=""   class="form-control input-sm" />
        <?php endif; ?>
      </div>
      <div class="form-group">
        <label for="live_url"><?=lang('sys.ga.liveurl');?></label>
        <?php if(!empty($gameApi)):?> 
          <input type="text"  id="live_url"  name="live_url"   value="<?php echo $gameApi->live_url ?>"   class="form-control input-sm" />
          <?php else: ?>
            <input type="text"  id="live_url"  name="live_url"   value=""   class="form-control input-sm" />
          <?php endif; ?>
      </div>
      <div class="form-group">
        <label for="live_key"><?=lang('sys.ga.livekey');?></label>
        <?php if(!empty($gameApi)):?> 
          <textarea class="form-control" id="live_key"  name="live_key" rows="3"><?php echo $gameApi->live_key ?></textarea>
          <?php else: ?>
            <textarea class="form-control" id="live_key"  name="live_key" rows="3"></textarea>
          <?php endif; ?>
      </div>
      <div class="form-group">
      <label for="live_secret"><?=lang('sys.ga.livesecret');?></label>
        <?php if(!empty($gameApi)):?> 
        <textarea class="form-control" id="live_secret"  name="live_secret"  rows="3"><?php echo $gameApi->live_secret ?></textarea>
        <?php else: ?>
          <textarea class="form-control" id="live_secret"  name="live_secret"  rows="3"></textarea>
        <?php endif; ?>
      </div>
      <div class="form-group">
        <label for="system_code"><?=lang('sys.ga.systemcode');?></label>
        <?php if(!empty($gameApi)):?>
          <input type="text"  id="system_code"  name="system_code"   value="<?php echo $gameApi->system_code ?>"   class="form-control input-sm" />
        <?php else: ?>
            <input type="text"  id="system_code"  name="system_code"   value=""   class="form-control input-sm" />
        <?php endif; ?>
      </div>
      <div class="form-group">
        <label for="live_account"><?=lang('sys.ga.liveacct');?></label>
        <?php if(!empty($gameApi)):?>
          <input type="text"  id="live_account"  name="live_account"   value="<?php echo $gameApi->live_account ?>"   class="form-control input-sm" />
        <?php else: ?>
          <input type="text"  id="live_account"  name="live_account"   value=""   class="form-control input-sm" />
        <?php endif; ?>
      </div>
      </div><!-- col-md-6 -->

    <div class="col-md-6"> 

     <div class="form-group">
      <label for="amount_float"><?=lang('sys.ga.amount_float');?></label>
      <select id="amount_float"  name="amount_float" class="form-control input-sm">
        <?php
        $amount_float_vals=[              
          '0'=> '0(Integer)',
          '1'=> '.1', 
          '2'=> '.01',                      
        ];

        if(!empty($gameApi)){
          foreach ($amount_float_vals as $key => $value) {
            if($gameApi->amount_float == $key){
              echo  '<option value="'.$key.'" selected >'.$value.'</option>';
            }else{
              echo  '<option value="'.$key.'">'.$value.'</option>';
            }                  
          }

        }else{

         foreach ($amount_float_vals as $key => $value) {
          echo  '<option value="'.$key.'">'.$value.'</option>';                 
        }
      }
      ?>
    </select>
  </div>

  <div class="form-group">
    <label for="system_name"><?=lang('sys.ga.systemname');?></label>
     <?php if(!empty($gameApi)):?>
      <input type="text" value="<?php echo $gameApi->system_name ?>" class="form-control" id="system_name" name="system_name" required>
      <?php else: ?>
        <input type="text" value="" class="form-control" id="system_name" name="system_name" required>
      <?php endif; ?>
    </div>
    <div class="form-group">
      <label for="last_sync_id"><?=lang('sys.ga.lastsyncid');?></label>
      <?php if(!empty($gameApi)):?>
        <input type="text" id="last_sync_id" name="last_sync_id"    value="<?php echo $gameApi->last_sync_id ?>"   class="form-control" />
      <?php else: ?>
         <input type="text" id="last_sync_id" name="last_sync_id"    value=""   class="form-control" />
       <?php endif; ?>
    </div>
    <div class="form-group">
      <label for="last_sync_details"><?=lang('sys.ga.lastsyncdet');?></label>
      <?php if(!empty($gameApi)):?>
        <textarea class="form-control" id="last_sync_details" name="last_sync_details"rows="3"><?php echo $gameApi->last_sync_details ?></textarea>
      <?php else: ?>
          <textarea class="form-control" id="last_sync_details" name="last_sync_details"rows="3"></textarea>
      <?php endif; ?>
    </div>
    <div class="form-group">
      <label for="category"><?php echo lang('sys.ga.category')?>:</label>
       <?php if(!empty($gameApi)):?>
        <input type="text" class="form-control"  value="<?php echo $gameApi->category ?>" >
        <?php else: ?>
         <input type="text" class="form-control"  value="" >
       <?php endif; ?>
    </div>
    <div class="form-group">
      <label for="local_path"><?=lang('sys.ga.localpath');?></label>
      <?php if(!empty($gameApi)):?>
        <input type="text"  id="local_path"  name="local_path"   value="<?php echo $gameApi->local_path ?>"   class="form-control input-sm" />
      <?php else: ?>
         <input type="text"  id="local_path"  name="local_path"   value=""   class="form-control input-sm" />
      <?php endif; ?>
    </div>
    <div class="form-group">
      <label for="game_platform_rate"><?=lang('sys.gd7') . " " . lang('sys.rate');?></label>
        <?php if(!empty($gameApi)):?>
        <input type="text"  id="game_platform_rate"  name="game_platform_rate"   value="<?php echo $gameApi->game_platform_rate ?>"  class="form-control input-sm" />
        <?php else: ?>
          <input type="text"  id="game_platform_rate"  name="game_platform_rate"   value=""  class="form-control input-sm" />
        <?php endif; ?>
    </div>
    <div class="form-group">
      <label for="sandbox_url"><?=lang('sys.ga.sandboxurl');?></label>
      <?php if(!empty($gameApi)):?>
        <input type="text"  id="sandbox_url"  name="sandbox_url"    value="<?php echo $gameApi->sandbox_url ?>"   class="form-control input-sm" />
        <?php else: ?>
          <input type="text"  id="sandbox_url"  name="sandbox_url"    value=""   class="form-control input-sm" />
        <?php endif; ?>
      </div>
      <div class="form-group">
        <label for="sandbox_key"><?=lang('sys.ga.sandboxkey');?></label>
        <?php if(!empty($gameApi)):?>
          <textarea class="form-control" id="sandbox_key"  name="sandbox_key"  rows="3"><?php echo $gameApi->sandbox_key ?></textarea>
          <?php else: ?>
            <textarea class="form-control" id="sandbox_key"  name="sandbox_key"  rows="3"></textarea>
          <?php endif; ?>
        </div>
        <div class="form-group">
          <label for="sandbox_secret"><?=lang('sys.ga.sandboxsecret');?></label>
          <?php if(!empty($gameApi)):?>
            <textarea class="form-control" id="sandbox_secret"  name="sandbox_secret" rows="3" ><?php echo $gameApi->sandbox_secret ?></textarea>
            <?php else: ?>
              <textarea class="form-control" id="sandbox_secret"  name="sandbox_secret" rows="3" ></textarea>
            <?php endif; ?>
        </div>
        <div class="form-group">
          <label for="second_url"><?=lang('sys.ga.secondurl');?></label>
          <?php if(!empty($gameApi)):?>
            <input type="text"  id="second_url"  name="second_url"   value="<?php echo $gameApi->second_url ?>"   class="form-control input-sm" />
            <?php else: ?>
             <input type="text"  id="second_url"  name="second_url"   value=""   class="form-control input-sm" />
           <?php endif; ?>
         </div>
           <div class="form-group">
            <label for="sandbox_account"><?=lang('sys.ga.sandboxacct');?></label>
            <?php if(!empty($gameApi)):?>
              <input type="text"  id="sandbox_account"  name="sandbox_account"   value="<?php echo $gameApi->sandbox_account ?>"   class="form-control input-sm"  />
              <?php else: ?>
               <input type="text"  id="sandbox_account"  name="sandbox_account"   value=""   class="form-control input-sm"  />
             <?php endif; ?>
           </div>
           <div class="form-group">
            <label for="game_platform_order">Game Platform Order</label>
            <?php if(!empty($gameApi)):?>
              <input  type="number" id="game_platform_order"  name="game_platform_order"   value="<?php echo $gameApi->game_platform_order ?>"   class="form-control input-sm"  />
              <?php else: ?>
               <input  type="number" id="game_platform_order"  name="game_platform_order"   value=""   class="form-control input-sm"  />
             <?php endif; ?>
           </div>
          </div><!-- col-md-6 -->
      </div><!-- row1 -->
       <div class="row" >
         <div class="col-md-12"> 
          <div class="form-group">
            <label for="extra_info"><?=lang('sys.ga.extrainfo');?></label>
            <?php if(!empty($gameApi)):?>
              <pre class="form-control" id="extra_info" name="extra_info" style="height:200px;overflow:auto;"><?php echo $gameApi->extra_info ?></pre>
              <?php else: ?>
                <pre class="form-control" id="extra_info" name="extra_info" style="height:200px;overflow:auto;"></pre>
              <?php endif; ?>
            </div>
            <div class="form-group">
              <label for="sandbox_extra_info"><?=lang('sys.ga.sandboxextrainfo');?></label>
              <?php if(!empty($gameApi)):?>
                <pre class="form-control" id="sandbox_extra_info" name="sandbox_extra_info" style="height:200px;overflow:auto;"><?php echo $gameApi->sandbox_extra_info ?></pre>
                <?php else: ?>
                  <pre class="form-control" id="sandbox_extra_info" name="sandbox_extra_info" style="height:200px;overflow:auto;"></pre>
                <?php endif; ?>
              </div>
            </div>
          </div>
          <div class="modal-footer" style="position:relative;height:50px">
            <div class="alert alert-danger alert-dismissible text-left" id="add-update-error-msg" style="width:70%;visibility:hidden;position:absolute;"></div>
            <button type="button" id="close-add-update-modal"  class="btn btn-default" data-dismiss="modal"><?php  echo lang('lang.cancel')?></button>
            <button id="add-update-button"  type="submit" class="btn btn-info"><?php echo lang('lang.submit')?></button>
        </div>
      </form>

 <script>
function isJsonString(str) {
  if (str == '') return true;
  try {
    JSON.parse(str);
  } catch (e) {
    return false;
  }
  return true;
}

$(document).ready(function(){

  // Init syntax highlight for JSON string in extra_info
  hljs.initHighlightingOnLoad();
  // Init ACE editor for JSON
  var extraInfoEditor = ace.edit("extra_info");
  extraInfoEditor.setTheme("ace/theme/tomorrow");
  extraInfoEditor.session.setMode("ace/mode/json");
  var sandboxExtraInfoEditor = ace.edit("sandbox_extra_info");
  sandboxExtraInfoEditor.setTheme("ace/theme/tomorrow");
  sandboxExtraInfoEditor.session.setMode("ace/mode/json");

  if(targetEditField != null){

    if(targetEditField == 'extra_info' ){ 
      $('#form-modal .modal-body').animate({scrollTop: $('#extra_info').offset().top}, 'fast');
       extraInfoEditor.focus();
    }else if(targetEditField == 'sandbox_extra_info'){
    $('#form-modal .modal-body').animate({scrollTop: $('#sandbox_extra_info').offset().top}, 'fast');
      sandboxExtraInfoEditor.focus();
     $("#extrainfo_scrollto" ).focus();
    }else{
      $('#'+targetEditField).focus();
    }
    targetEditField = null;
  }else{
    $('#live_mode').focus();
  }


    //dropdown game api selection
    var preloadApiDetail = function(){ 

      $.ajax({
        url : '<?php echo site_url('game_api/getApiDetail') ?>/' + $(this).val(),
        type : 'GET',
        dataType : "json",
        cache : false
      }).done(function (data) { console.log(data)
        $("#system_name").val(data.system_name);
        $("#note").val(data.note);
        $("#last_sync_datetime").val(data.last_sync_datetime);
        $("#last_sync_id").val(data.last_sync_id);
        $("#last_sync_details").val(data.last_sync_details);
        $("#system_type").val(data.system_type);
        $('#category').val(data.category);
        $('#amount_float').val('1');
        $("#live_url").val(data.live_url);
        $("#sandbox_url").val(data.sandbox_url);
        $("#live_key").val(data.live_key);
        $("#live_secret").val(data.live_secret);
        $("#sandbox_key").val(data.sandbox_key);
        $("#sandbox_secret").val(data.sandbox_secret);
        $("#live_mode").prop('checked', data.live_mode ? 1 : 0);
        $("#second_url").val(data.second_url);
        $("#sandbox_account").val(data.sandbox_account);
        $("#live_account").val(data.live_account);
        $("#system_code").val(data.system_code);
        $("#status").val(data.status);
        $("#class_name").val(data.class_name);
        $("#local_path").val(data.local_path);
        $("#manager").val(data.manager);
        $("#game_platform_rate").val(data.game_platform_rate);
        $("#created_on").val(data.created_on);
        var extraInfoEditor = ace.edit("extra_info");
        var sandboxExtraInfoEditor = ace.edit("sandbox_extra_info");

        extraInfoEditor.setValue(JSON.stringify(JSON.parse(data.extra_info), null, 4));
        sandboxExtraInfoEditor.setValue(JSON.stringify(JSON.parse(data.sandbox_extra_info), null, 4));

        if(extraInfoEditor.getValue() == 'null') {
          extraInfoEditor.setValue('');
        }
        if(sandboxExtraInfoEditor.getValue() == 'null') {
          sandboxExtraInfoEditor.setValue('');
        }
      });
    };

    $("#new_id").change(preloadApiDetail);

    $('#add-update-game-form').submit(function(event){

     $('#add-update-button').attr('disabled', 'disabled').html("<?php echo lang('Please wait') ?>...");
     $('#close-add-update-modal').attr('disabled', 'disabled');
     var url = '<?php echo $form_add_edit_url;?>"';
     var params = $(this).serializeArray(); 

     var sandboxExtraInfo = sandboxExtraInfoEditor.getValue();
     var extraInfo =  extraInfoEditor.getValue();


     if ( ! isJsonString(extraInfoEditor.getValue()) || ! isJsonString(sandboxExtraInfoEditor.getValue())) {
      $('#add-update-button').removeAttr('disabled').html("Submit");
      $('#close-add-update-modal').removeAttr('disabled')
      $('#add-update-error-msg').css("visibility","visible").html('Invalid JSON');
      return false;
    }


    params.push({name: "extra_info", value: extraInfo});
    params.push({name: "sandbox_extra_info", value: sandboxExtraInfo});


    if(event.keyCode == 13) {
      alert(event.keyCode)
      event.preventDefault();
      return false;
    }

var type='POST';
var callback = function(data){
  if (data.status == 'failed') {
             //notify('danger', data.msg);
             $('#add-update-error-msg').css("visibility","visible").html(data.msg);
             $('#add-update-button').removeAttr('disabled').html("<?php echo lang('lang.submit')?>");
             $('#close-add-update-modal').removeAttr('disabled').html("<?php echo lang('lang.cancel')?>");
           } else {
            if($('#form-modal').is(':visible')){
             $('#form-modal').modal('hide');
           }
           notify('success', data.msg);
           <?php if(empty($gameApi)):?>
            getExistingGameApis();
          <?php endif; ?>
          dataTable.ajax.reload();
        }
      }
      executeAction(url, type, params,callback);
      return false;
    });



  });



</script>