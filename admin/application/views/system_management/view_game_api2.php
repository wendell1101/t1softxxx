  <style>
    .table > tbody > tr > td, .table > tfoot > tr > td {
      white-space: nowrap;
      vertical-align: middle;
    }
    .game_api_hint{
      margin-top:-11px;
      visibility:hidden;
      font-size:xsmall;
    }
    th.width-201 {min-width:250px;}
    th.width-150 {min-width:150px;}
  </style>

<div class="panel panel-primary hidden">
  <div class="panel-heading">
    <h4 class="panel-title">
      <i class="fa fa-search"></i> <?=lang("lang.search")?>
      <span class="pull-right">
        <a data-toggle="collapse" href="#collapseGameApiHistory" class="btn btn-default btn-xs <?= $this->config->item('default_open_search_panel') ? '' : 'collapsed'?>"></a>
      </span>
    </h4>
  </div>
  <div id="collapseGameApiHistory" class="panel-collapse collapse <?= $this->config->item('default_open_search_panel') ? 'in' : ''?>">
    <form id="form-filter" class="form-horizontal" method="get">
      <div class="panel-body">
        <div class="row">
          <div class="col-md-8"> 
            <label class="radio-inline"><input class="form-filter-element" type="radio" name="game_api_statuses"  checked><?php echo lang('All'); ?></label>
            <label class="radio-inline"><input type="radio" class="form-filter-element"  name="game_api_statuses" value="1" ><?php echo lang('Active'); ?></label>
            <label class="radio-inline"><input type="radio" class="form-filter-element"  name="game_api_statuses" value="2"><?php echo lang('Blocked'); ?></label>
            <label class="radio-inline"><input type="radio" class="form-filter-element"  name="game_api_statuses" value="3"><?php echo lang('Under Maintenance'); ?></label>
            <label class="radio-inline"><input type="radio" class="form-filter-element"  name="game_api_statuses" value="4"><?php echo lang('Paused Sync'); ?></label>
          </div>

          <div class="col-md-3">
          
        </div>
        <div class="col-md-4">
        </div> 
      </div>
      <div class="row">
      <div class="col-md-12">
       <br>
          <fieldset>
            <legend>
              <label class="form-check-label">
              Game APIs                              
            </label>
             <a class="btn btn-primary btn-xs" name="show_multiselect_filter" id="show-multiselect-filter" style="text-decoration:none; border-radius:2px;"><span class="fa fa-minus-circle"> <?=lang("Collapse All")?></span></a>
             </legend>
             <div id="apis" style="padding-bottom:20px;">    
            </div>
          </fieldset>

        </div>
       <!--  <div class="col-md-2">
        </div> -->
      </div>
    </div>
 
 </form>
</div>
</div>
<div class="panel panel-primary">
  <div class="panel-heading custom-ph">
    <h3 class="panel-title custom-pt" > <i class="icon-list"></i> <?=lang('sys.ga.paneltitle');?></h3>
  </div>
  <div class="table-responsive" style="padding:20px;">
       <button type="button" value="" id="add-row" name="btnSubmit" class="btn btn-primary btn-sm">
    <i class="glyphicon glyphicon-plus" style="color:white;"  data-placement="bottom" ></i>
    <?=lang('sys.ga.add.button');?>
  </button>
      <table class="table table-striped " style="width:100%"  id="my_table" >
        <thead>
          <tr>
            <th><?=lang('lang.action')?></th> 
            <th><?=lang('Game Platform')?></th>
            <th><?=lang('sys.ga.livemode')?></th>
            <th><?=lang('sys.ga.seamless')?></th>
            <th><?=lang('sys.ga.status')?></th>
            <th><?=lang('sys.ga.systemcode')?></th>
            <th><?=lang('sys.ga.systemname')?></th>
            <th><?=lang('sys.ga.systemtype')?></th> 
            <th><?=lang('Maintenance Mode')?></th>
            <th><?=lang('Pause Sync')?></th>
            <th class='width-201'><?=lang('sys.ga.lastsyncdt')?></th>
            <th class='width-201'><?=lang('sys.ga.lastsyncid')?></th>
            <th class='width-201'><?=lang('sys.ga.lastsyncdet')?></th>
            <th class='width-201'><?=lang('sys.ga.liveurl')?></th>
            <th class='width-201'><?=lang('sys.ga.secondurl')?></th>
            <th class='width-201'><?=lang('sys.ga.sandboxurl')?></th>  
            <th class='width-201'><?=lang('sys.ga.extrainfo')?></th>
            <th class='width-201'><?=lang('sys.ga.sandboxextrainfo')?></th>
            <th class='width-201'><?=lang('sys.ga.livekey')?></th>
            <th class='width-201'><?=lang('sys.ga.sandboxkey')?></th>
            <th class='width-201'><?=lang('sys.ga.livesecret')?></th>
            <th class='width-201'><?=lang('sys.ga.sandboxsecret')?></th>
            <th class='width-201'><?=lang('sys.ga.liveacct')?></th> 
            <th class='width-201'><?=lang('sys.ga.sandboxacct')?></th>               
            <th class='width-201'><?=lang('sys.ga.classname')?></th>
            <th class='width-201'><?=lang('sys.ga.localpath')?></th>
            <th class='width-201'><?=lang('sys.ga.manager')?></th>
            <th class='width-150'><?=lang('sys.gd7') . " " . lang('sys.rate');?></th>
            <th class='width-201'><?=lang('sys.ga.note')?></th>  
            <th><?=lang('sys.createdon')?></th>
          </tr>
        </thead>
        <tbody>
        </tbody>
      </table>
    </div>
  </div>
  <div class="panel-footer"></div>
</div>
<?php if($this->utils->isEnabledFeature('export_excel_on_queue')){?>
<form id="_export_excel_queue_form" class="hidden" method="POST" target="_blank">
<input name='json_search' type="hidden">
</form>
<form id="_export_csv_form" class="hidden" method="POST" target="_blank">
<input name='json_search' id = "json_csv_search" type="hidden">
</form>
<?php }?>

 <!-- Modal -->
  <div  id="form-modal" class="modal fade" id="myModal" role="dialog" data-backdrop="static">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal">&times;</button>
          <h4 class="modal-title" id="modal-panel-title"></h4>
        </div>
        <div class="modal-body" style="max-height:600px;overflow: auto;">
     


        </div> <!-- modalbody -->
    <!--     <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
        </div> -->
      </div>
    </div>
  </div>
</div>

<div id="conf-modal"  class="modal fade bs-example-modal-md"  data-backdrop="static"
data-keyboard="false"  tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true">
<div class="modal-dialog modal-md">
    <div class="modal-content">
        <div class="modal-header panel-heading">
            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
            <h3 id="myModalLabel" ><?=lang('sys.ga.conf.title');?></h3>
        </div>
        <div class="modal-body">
            <div class="row">
                <div class="col-md-12">
                    <div class="help-block" id="conf-msg">

                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-default" id="cancel-action" data-dismiss="modal"><?=lang('pay.bt.cancel');?></button>
            <button type="button" id="confirm-action" class="btn btn-primary"><?=lang('pay.bt.yes');?></button>
        </div>
    </div>
</div>
</div>

<script type="text/javascript">

  var LANG = {
    ADD_PANEL_TITLE : "<?=lang('sys.ga.add.paneltitle');?>",
    EDIT_PANEL_TITLE : "<?=lang('sys.ga.edit.paneltitle');?>",
    ADD_BUTTON_TITLE : "<i class='fa fa-check'></i> <?=lang('sys.ga.add.button');?>",
    UPDATE_BUTTON_TITLE : "<i class='fa fa-check'></i> <?=lang('sys.ga.update.button');?>",
    DELETE_CONFIRM_MESSAGE : "<?=lang('sys.ga.conf.del.msg');?>",
    UPDATE_CONFIRM_MESSAGE :"<?=lang('sys.ga.conf.update.msg');?>",
    ADD_CONFIRM_MESSAGE :"<?=lang('sys.ga.conf.add.msg');?>",
    DISABLE_CONFIRM_MESSAGE :"<?=lang('sys.ga.conf.disable.msg');?>",
    ABLE_CONFIRM_MESSAGE :"<?=lang('sys.ga.conf.able.msg');?>",
    EDIT : "<?=lang('sys.ga.edit.buttontitle');?>",
    EDIT_COLUMN : "<?=lang('pay.bt.edit.column');?>",
    DELETE_ITEMS : "<?=lang('sys.ga.delete.items');?>",
    ADD_GAME_DESC : "<?=lang('sys.ga.add.paneltitle');?>",
    GAME_MAINTENANCE : "<?=lang('sys.game.maintenance');?>",
    FINISH_GAME_MAINTENANCE : "<?=lang('Are you sure you want to Finish Maintenance');?>",
    GAME_PAUSE_SYNCING : "<?=lang('sys.pause.syncing');?>",
    GAME_REVERT_SYNCING : "<?=lang('sys.revert.syncing');?>"
};

var targetEditField = null;
var currentMode = null;
var dataTable = '';
var imgloader = "/resources/images/ajax-loader.gif";


function notify(type, msg) {
  $.notify({
    message: msg
  }, {
    type: type
  });
}

function executeAction(url, type, params,callback) {

  if (type == 'POST') { 

    $.ajax({
      method: type,
      url: url,
      data: params,
      dataType: "json"
     })
      .done(function(data) {
        callback(data);
      }).fail(function() {
         if($('#conf-modal').is(':visible')){
          $("#cancel-action").removeAttr('disabled')
          $("#confirm-action").removeAttr('disabled').html('<?=lang('pay.bt.yes');?>');
          $('#conf-modal').modal('hide');
         }
        if($('#form-modal').is(':visible')){
         $('#add-update-button').removeAttr('disabled').html("<?php echo lang('lang.submit')?>");
         $('#close-add-update-modal').removeAttr('disabled').html("<?php echo lang('lang.cancel')?>");
         $('#form-modal').modal('hide');
         }
          notify('danger','<?php echo lang('sys.ga.erroccured') ?>' );
         
      });


  } else {

    $.ajax({
      method: type,
      url: url,
      dataType: "json"
     })
      .done(function(data) {
        callback(data);
      }).fail(function() {
           notify('danger','<?php echo lang('sys.ga.erroccured') ?>' );
      });
  }

}


function getExistingGameApis(){

  $('#apis').html('<center><img src="' + imgloader + '"></center>');

  var callback = function(data){
    
    var rows = data.gameApis;
    var checkboxes = "";
     for (var i=0; i < rows.length; i++) {
      var row = rows[i];
      checkboxes += '<label class="checkbox-inline form-filter-element"><input type="checkbox" name="game_api_ids[]" value="'+row.id+'"><b>'+row.id+'</b>-<span class="text-primary">'+row.system_name+'</span></label>';
     }
     $('#apis').html(checkboxes);

      $(".form-filter-element").change(function() {
      dataTable.ajax.reload();
      });
     
 }

  var type = 'GET';
  var params = null;
  var url='<?php echo site_url('game_api/getExistingGameApis') ?>/';
  executeAction(url, type, params,callback) 

  }

function hideFormModal() {
  $("html, body").animate({
    scrollTop: 0
  }, "slow");
  $('#form-modal').modal('hide');
}

function showFormModal(gameApi) {

  var dst_url = "/game_api/add_edit_game_api";
  var panelTitle = "<?=lang('sys.ga.add.paneltitle');?>";

  if(gameApi !== null){
    dst_url = "/game_api/add_edit_game_api/" + gameApi;
    panelTitle = "<?=lang('sys.ga.edit.paneltitle');?>";
  }
  $('#modal-panel-title').html(panelTitle);
  var main_selector = "#form-modal"
  var body_selector = main_selector + ' .modal-body';
  var target = $(body_selector);
  target.html('<center><img src="' + imgloader + '"></center>').delay(1000).load(dst_url);
  $(main_selector).modal('show');
}


$("#conf-modal").on("hide.bs.modal", function () {
   $("#cancel-action").removeAttr('disabled');
   $("#confirm-action").removeAttr('disabled').html('<?=lang('pay.bt.yes');?>');
});


var modalConfirm = function(callback, msg) {

  $('#conf-modal').modal('show');
  $('#conf-msg').html(msg);


   var timeout = null;
    $('#conf-modal').keydown(function (event) {
          var key=event.which || event.charCode;
          if(key == 13)  // the enter key code
          {
            event.preventDefault();
            if(timeout != null){
                clearTimeout(timeout);
                timeout = null;
            }
            timeout = setTimeout(function(){
               $('#confirm-action').trigger('click');
            }, 10);
          }
    });

  $("#confirm-action").one("click", function() {
    callback(true);
    $(this).attr('disabled', 'disabled').html("<?php echo lang('Please wait') ?>...");
    $("#cancel-action").attr('disabled', 'disabled');
  })

  $("#cancel-action").on("click", function() {
    callback(false);
  });



  return false;
};


var openPreCode = [];

function collapsePrecodeList(){
  var len = openPreCode.length;
  setTimeout(function(){
   for(var i=0; i<len; i++){
     var id = '#'+openPreCode[i];
        $(id).fadeIn(2000);
        $(id).prev('span').toggleClass("glyphicon-chevron-down glyphicon-chevron-up");
     }
  },100)
 
}

function renderControls(data) {
  $('.pre-code').each(function(i, e) {
    hljs.highlightBlock(e)
  });

  $('.pre-code-container').each(function(i, e) {
    $(this).hide();
  })

  $('.code-holder').each(function(i, e) {
    $(this).prepend('<span class="show-code glyphicon glyphicon-chevron-down" style="padding-left: 50%;font-size:15px;cursor:pointer;" aria-hidden="true"></span>');
  });

  $('.show-code').each(function(i, e) {
    $(this).click(function() {
      $(this).toggleClass("glyphicon-chevron-down glyphicon-chevron-up");
      $(this).next('.pre-code-container').toggle();

      var id = $(this).next('.pre-code-container').attr('id');

      var codeElement = $('#'+id);
      if(codeElement.is(":visible")){
          openPreCode.push(id);
        } else{
          var index = openPreCode.indexOf(id);
          if (index > -1) {
             openPreCode.splice(index, 1);
          }
        }
    });
  });

  $('.game_api_actions').each(function(i, e) {
    $(this).click(function(event) {
      var game_platform_id = $(this).attr('game_api_id');
      var action = $(this).attr('game_api_action');

      var url = null;
      var type = null;
      var params = null;
      var  confirmCallback =function(data){ 
       if($('#conf-modal').is(':visible')){
         $('#conf-modal').modal('hide');
         $("#confirm-action").html('<?php echo lang('yes') ?>').removeAttr('disabled');
         $("#cancel-action").removeAttr('disabled');
           if (data.status == 'failed') {
             notify('danger', data.msg);
           }else{
             notify('success', data.msg);
             dataTable.ajax.reload();
             
          }
       }     
     }

      switch (action) {
        case "add":
          url = '<?php echo site_url('game_api/addGameApi') ?>/';
          type = 'POST';
          showFormModal();
          break;

        case "edit":
          targetEditField = null;
          showFormModal(game_platform_id);
          break;

        case "edit_by_field":
          targetEditField = $(this).attr('game_api_field');
          showFormModal(game_platform_id);
          break;

        case "update":
          url = '<?php echo site_url('game_api/updateGameApi') ?>/';
          type = 'POST';
          break;

        case "disable":
          url = '<?php echo site_url('game_api/disableAbleGameApi') ?>/';
          params = {
            id: game_platform_id,
            status: 0
          }
          type = 'POST';
          modalConfirm(function(confirm) {
            if (confirm) {
              executeAction(url, type, params,confirmCallback);
            }
          }, LANG.DISABLE_CONFIRM_MESSAGE);
          break;

        case "able":
          url = '<?php echo site_url('game_api/disableAbleGameApi') ?>/';
          params = {
            id: game_platform_id,
            status: 1
          }
          type = 'POST';
          modalConfirm(function(confirm) {
            if (confirm) {
              executeAction(url, type, params,confirmCallback);
            }
          }, LANG.ABLE_CONFIRM_MESSAGE);
          break;

        case "start_maintenance":
          url = '<?php echo site_url('game_api/gameMaintenanceMode') ?>/';
          params = {
            id: game_platform_id,
            maintenance_mode: 1
          }
          type = 'POST';
          modalConfirm(function(confirm) {
            if (confirm) {
              executeAction(url, type, params,confirmCallback);
            }
          }, LANG.GAME_MAINTENANCE);
          break;

        case "finish_maintenance":
          url = '<?php echo site_url('game_api/gameMaintenanceMode') ?>/';
          params = {
            id: game_platform_id,
            maintenance_mode: 0
          }
          type = 'POST';
          modalConfirm(function(confirm) {
            if (confirm) {
              executeAction(url, type, params,confirmCallback);
            }
          }, LANG.FINISH_GAME_MAINTENANCE);
          break;

        case "pause_sync":
          url = '<?php echo site_url('game_api/gamePauseSync') ?>/';
          params = {
            id: game_platform_id,
            pause_sync: 1
          }
          type = 'POST';
          modalConfirm(function(confirm) {
            if (confirm) {
              executeAction(url, type, params,confirmCallback);
            }
          }, LANG.GAME_PAUSE_SYNCING);
          break;

        case "revert_sync":
          url = '<?php echo site_url('game_api/gamePauseSync') ?>/';
          params = {
            id: game_platform_id,
            pause_sync: 0
          }
          type = 'POST';
          modalConfirm(function(confirm) {
            if (confirm) {
              executeAction(url, type, params,confirmCallback);
            }
          }, LANG.GAME_REVERT_SYNCING);
          break;
      }
      
      return false;

    });
  });

  $('td').each(function(i, e) {
    var td = $(this);

    td.hover(function() {
      td.find(".game_api_hint").css("visibility", "visible");
      //td.css('background-color','#fcf8e3');
    },function() {
      td.find(".game_api_hint").css("visibility", "hidden");
    });

  });

  if (dataTable.rows({
      selected: true
    }).indexes().length === 0) {
    dataTable.buttons().disable();
  } else {
    dataTable.buttons().enable();
  }

}


function renderRow(nRow,aData,iDisplayIndex,iDisplayIndexFull) {

  var blocked = '<span class="label label-danger"><?php echo lang('Blocked'); ?></span>';
  var active = '<span class="label label-success"><?php echo lang('Active'); ?></span>';
  var blockColumn = aData[4],
    maintenanceModeColumn = aData[8],
    pauseSyncColumn = aData[9];

  if (blockColumn == '0') {

    $(nRow).css('background-color', '#f2dede');
    $('td:eq(4)', nRow).html(blocked);

    if (maintenanceModeColumn == '1') {
      $('td:eq(8)', nRow).html('<span class="label label-default"><?php echo lang('Under Maintenance'); ?></span>');
    } else {
      $('td:eq(8)', nRow).html('-');
    }

    if (pauseSyncColumn == '1') {
      $('td:eq(9)', nRow).html('<span class="label label-default"><?php echo lang('Paused Sync'); ?></span>');
    } else {
      $('td:eq(9)', nRow).html('-');
    }

  } else {

    $('td:eq(4)', nRow).html(active);
    if (maintenanceModeColumn == '1') {
      $(nRow).css('background-color', '#fcf8e3');
      $('td:eq(8)', nRow).html('<span class="label label-warning"><?php echo lang('Under Maintenance'); ?></span>');
    } else {
      $('td:eq(8)', nRow).html('-');
    }

    if (pauseSyncColumn == '1') {
      $(nRow).css('background-color', '#fcf8e3');
      $('td:eq(9)', nRow).html('<span class="label label-warning"><?php echo lang('Paused Sync'); ?></span>');
    } else {
      $('td:eq(9)', nRow).html('-');
    }

  }

}

 $(document).ready(function () { 

   dataTable = $('#my_table').DataTable({
    autoWidth: false,
    searching: true,
    dom: "<'panel-body'<'pull-right'B><'pull-right progress-container hidden-xs'>l><'dt-information-summary1 text-info pull-left' i>t<'panel-body'<'pull-right'p><'dt-information-summary2 text-info pull-left' i>>",
    <?php if($this->utils->isEnabledFeature('column_visibility_report')){ ?>
            stateSave: true,
    <?php } else { ?>
            stateSave: false,
    <?php } ?>
    buttons: [
      {
        extend: 'colvis',
        postfixButtons: [ 'colvisRestore' ]
      },
     
      //  {
      //   text: "<?php echo lang('CSV Export'); ?>",
      //   className:'btn btn-sm btn-primary export_excel',
      //   action: function ( e, dt, node, config ) {
      //      var d = {'extra_search': $('#form-filter').serializeArray(), 'export_format': 'csv', 'export_type': 'queue',
      //                       'draw':1, 'length':-1, 'start':0};              
      //     $("#_export_excel_queue_form").attr('action', site_url('/export_data/gameApiUpdateHistory'));
      //     $("#_export_excel_queue_form [name=json_search]").val(JSON.stringify(d));
      //     $("#_export_excel_queue_form").submit();                      
      //   }
      // }
    ],

    columnDefs: [
      { sortable: false, targets: [0,3,15,16,27] }
    ],
  //  order: [[0, 'desc']],

    // SERVER-SIDE PROCESSING
    processing: true,
    serverSide: true,
    ajax: function (data, callback, settings) {

      data.extra_search = $('#form-filter').serializeArray();     
       console.log(data.extra_search);

      $.post(base_url + "api/gameApi2", data, function(data) { 

        callback(data);
        renderControls(data);

      },'json');
    },

      fnRowCallback: function( nRow,aData,iDisplayIndex,iDisplayIndexFull) {
        
        renderRow(nRow,aData,iDisplayIndex,iDisplayIndexFull);      
        
      }
  }); 
  
  getExistingGameApis();

  $("#add-row").on('click', function () {
       showFormModal(null);
   });


 $('#show-multiselect-filter').click(function(){
  if($('#show-multiselect-filter span').attr('class') == 'fa fa-plus-circle'){
    $('#show-multiselect-filter span').attr('class', 'fa fa-minus-circle');
    $('#show-multiselect-filter span').html(' <?=lang("Collapse All")?>');
    $('#show-multiselect-filter').attr("checked", true);
    $('#apis').show();

  }
  else{
    $('#show-multiselect-filter span').attr('class', 'fa fa-plus-circle');
    $('#show-multiselect-filter span').html(' <?=lang("Expand All")?>');
    $('#show-multiselect-filter').attr("checked", false);
    $('#apis').hide();

  }
});

  $('body').tooltip({
    selector : '[data-toggle="tooltip"]',
    placement : "bottom",
    trigger: "hover"
  });

 //prevent cliking when datatable is loading
  dataTable.on( 'preXhr.dt', function () {
     $(".game_api_actions").css("pointer-events", "none");
  });

  //unlock clicking when datatable is loaded
  dataTable.on( 'xhr.dt', function () {
   // $(".game_api_actions").css("pointer-events", "none");
      collapsePrecodeList();
  });

}); //end document ready.


</script>