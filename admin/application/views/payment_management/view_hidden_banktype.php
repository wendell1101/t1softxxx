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

<div class="panel panel-primary">
  <div class="panel-heading custom-ph">
    <h3 class="panel-title custom-pt" > <i class="icon-list"></i> <?=lang('Hidden Banktype List');?></h3>
  </div>
  <div class="table-responsive" style="padding:20px;">
      <table class="table table-striped " style="width:100%"  id="my_table" >
        <thead>
          <tr>
            <th><?=lang('lang.action')?></th>
            <th><?=lang('column.id')?></th>
            <th><?=lang('pay.bt.bankname')?></th>
            <th><?=lang('Bank Code')?></th>
            <th><?=lang('pay.bt.payment_api_id')?></th>
            <th><?=lang('report.p07')?></th>
            <th><?=lang('report.p06')?></th>
            <th><?=lang('pay.bt.createdon')?></th>
            <th><?=lang('pay.bt.updatedon')?></th>
            <th><?=lang('pay.bt.createdby')?></th>
            <th><?=lang('pay.bt.updatedby')?></th>
            <th><?=lang('pay.bt.status')?></th>
          </tr>
        </thead>
        <tbody>
        </tbody>
      </table>
    </div>
  </div>
  <div class="panel-footer"></div>
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


var dataTable = '';

function notify(type, msg) {
  $.notify({
    message: msg
  }, {
    type: type
  });
}

function executeAction(url,params,callback) {

    $.ajax({
      method: "GET",
      url: url,
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
          notify('danger','<?php echo lang('sys.ga.erroccured') ?>' );

      });
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

function renderControls(data) {

  $('.bank_type_actions').each(function(i, e) {
    $(this).click(function(event) {
      var banktypeId = $(this).attr('banktype_id');
      var action = $(this).attr('action');

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
     };

    modalConfirm(function(confirm) {
      if (confirm) {
        executeAction("<?php echo site_url('payment_management/showBankType') ?>/"+banktypeId,{},confirmCallback);
      }
    }, "<?php echo lang('Are you sure you want to show')?>" + " Banktype ID "+banktypeId+" ?");
      return false;
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

 $(document).ready(function () {
   $('#collapseSubmenu').addClass('in');
   $('#view_payment_settings').addClass('active');
   $('#hiddenBank3rdPaymentList').addClass('active');
   dataTable = $('#my_table').DataTable({
    autoWidth: false,
    searching: true,
    dom: "<'panel-body'<'pull-right'B>f<'pull-right progress-container hidden-xs'>l><'dt-information-summary1 text-info pull-left' i>t<'panel-body'<'pull-right'p><'dt-information-summary2 text-info pull-left' i>>",
    <?php if($this->utils->isEnabledFeature('column_visibility_report')){ ?>
            stateSave: true,
    <?php } else { ?>
            stateSave: false,
    <?php } ?>
    buttons: [
      {
        extend: 'colvis',
        postfixButtons: [ 'colvisRestore' ]
      }
    ],
    columnDefs: [
      { sortable: false, targets: [0] }
    ],
   order: [[7, 'desc']],

    // SERVER-SIDE PROCESSING
    processing: true,
    serverSide: true,
    ajax: function (data, callback, settings) {
      $.post(base_url + "api/hiddenBankTypeList", data, function(data) {
        callback(data);
        renderControls(data);

      },'json');
    },

  });

  $('body').tooltip({
    selector : '[data-toggle="tooltip"]',
    placement : "bottom",
    trigger: "hover"
  });

 //prevent clicking when datatable is loading
  dataTable.on( 'preXhr.dt', function () {
     $(".bank_type_actions").css("pointer-events", "none");
  });

}); //end document ready.


</script>