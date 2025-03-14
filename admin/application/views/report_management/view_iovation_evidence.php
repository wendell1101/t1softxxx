<div class="panel panel-primary hidden">
    <div class="panel-heading">
        <h4 class="panel-title">
            <i class="fa fa-search"></i> <?=lang("lang.search")?>
            <span class="pull-right">
                <a data-toggle="collapse" href="#collapsePaymentReport" class="btn btn-xs <?=$this->utils->getConfig('use_new_sbe_color') ? 'btn-primary' : 'btn-info'?> <?=$this->config->item('default_open_search_panel') ? '' : 'collapsed'?>"></a>
            </span>
        </h4>
    </div>
    <div id="collapsePaymentReport" class="panel-collapse <?=$this->config->item('default_open_search_panel') ? '' : 'collapse in'?>">
        <div class="panel-body">
            <form id="search-form" action="<?= site_url('/report_management/viewIovationEvidence'); ?>" method="get">
                <div class="row">
                    <!-- Date -->
                    <div class="form-group col-md-2 col-lg-2">
                        <label class="control-label">
                            <?= lang('report.regdate'); ?>:
                        </label>
                        <input id="search_payment_date" class="form-control input-sm dateInput user-success" data-start="#by_date_from" data-end="#by_date_to" data-time="false" autocomplete="off" />
                        <input type="hidden" id="by_date_from" name="by_date_from" value="<?=$conditions['by_date_from'];?>" />
                        <input type="hidden" id="by_date_to" name="by_date_to" value="<?=$conditions['by_date_to'];?>" />
                    </div>
                    <!-- username -->
                    <div class="form-group col-md-2 col-lg-2">
                    <label class="control-label">
                            <?= lang('report.username'); ?>
                        </label>
                        <input type="text" name="by_username" id="by_username" value="<?= $conditions['by_username']; ?>" class="form-control input-sm group-reset" />
                    </div>

                    <!-- user type -->
                    <div class="form-group col-md-2 col-lg-2">
                        <label for="result" class="control-label"><?=lang('User Type');?> </label>
                        <?=form_dropdown('by_user_type', $user_type_list, $conditions['by_user_type'], 'class="form-control input-sm iovation_report_result group-reset"'); ?>
                    </div>

                    <!-- deviceid -->
                    <div class="form-group col-md-2 col-lg-2">
                    <label class="control-label">
                            <?= lang('report.device_id'); ?>
                        </label>
                        <input type="text" name="by_device_id" id="by_device_id" value="<?= $conditions['by_device_id']; ?>" class="form-control input-sm group-reset" />
                    </div>

                   <!-- status -->
                   <div class="form-group col-md-2 col-lg-2">
                       <label for="status" class="control-label"><?=lang('API Response');?> </label>
                       <?=form_dropdown('by_status', $status_list, $conditions['by_status'], 'class="form-control input-sm iovation_report_status group-reset"'); ?>
                   </div>

                    <!-- result -->
                    <div class="form-group col-md-2 col-lg-2">
                        <label for="result" class="control-label"><?=lang('Evidence Type');?> </label>
                        <?=form_dropdown('by_evidence_type', $evidence_type_list, $conditions['by_evidence_type'], 'class="form-control input-sm iovation_report_result group-reset"'); ?>
                    </div>

                </div>

                <div class="row">
                    <div class="form-group col-md-2 col-md-offset-10">
                        <div class="pull-right">
                            <input type="button" id="btnResetFields" value="<?=lang('lang.clear'); ?>" class="btn btn-sm <?=$this->utils->getConfig('use_new_sbe_color') ? 'btn-linkwater' : 'btn-danger'?>">
                            <button type="submit" class="btn btn-sm <?=$this->utils->getConfig('use_new_sbe_color') ? 'btn-portage' : 'btn-info'?>"><?=lang("lang.search")?></button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="panel panel-primary">
    <div class="panel-heading">
        <h4 class="panel-title">
            <i class="icon-newspaper"></i>
            <?=lang("report.iovation_evidence")?>
        </h4>
    </div>
    <div class="panel-body">
        <form action="<?=site_url('report_management/iovationEvidenceBatchAction')?>" id="evidencelist" method="post" role="form">
					
            <div class="table-responsive">

                <button type="button" value="" id="add-evidence" name="btnSubmit" class="btn btn-primary btn-sm">
                    <i class="glyphicon glyphicon-plus" style="color:white;"  data-placement="bottom" ></i>
                    <?=lang('sys.ga.add.button');?>
                </button>
                
                <button type="button" value="" id="batch-add-evidence-modal" name="btnBatchAddModal" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#batchAddEvidenceModal">
                    <i class="glyphicon glyphicon-upload" style="color:white;"  data-placement="bottom" ></i>
                    <?=lang('Batch Add Evidence');?>
                </button>
                
                <button type="button" value="" id="batch-retract-evidence" name="btnBatchRetractEvidenceSubmit" class="btn btn-danger btn-sm">
                    <i class="glyphicon glyphicon-minus" style="color:white;"  data-placement="bottom" ></i>
                    <?=lang('Batch Retract Evidence');?>
                </button>

                <table class="table table-striped table-hover table-condensed" id="result_table">
                    <thead>
                        <tr>
                            <th style="padding:8px"><input type="checkbox" id="checkWhite" onclick="checkAll(this.id)"/></th>
                            <th><?=lang('iovation_evidence.date_created')?></th>
                            <th><?=lang("Username")?></th>
                            <th><?=lang("User Type")?></th>
                            <th><?=lang("Fullname")?></th>
                            <th><?=lang("Evidence Type")?></th>
                            <th><?=lang("Applied To")?></th>
                            <th><?=lang("Account Code")?></th>
                            <th><?=lang("Device ID")?></th>
                            <th><?=lang('iovation_evidence.applied_to')?></th>
                            <th><?=lang('iovation_evidence.comment')?></th>
                            <th><?=lang("Status")?></th>
                            <th><?=lang('iovation_evidence.action_taken')?></th>
                            <th><?=lang("Last Update Time")?></th>
                            <th><?=lang("Action")?></th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                    <tfoot>
                        <tr></tr>
                    </tfoot>
                </table>
            </div>
        </form>
    </div>
    <div class="panel-footer"></div>
</div>



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


<!-- QUEUE FORM -->
<form id="_batch_remove_evidence_ids_queue_form" action="<?=site_url('report_management/batchRemoveIovationEvidenceByIds'); ?>" method="POST">
	<input name='json_search' type="hidden">
</form>
<!-- /QUEUE FORM -->


<!-- BATCH ADD EVIDENCE MODAL -->
<div class="modal" id="batchAddEvidenceModal">
   <div class="modal-dialog modal-dialog-centered modal-xl">
    <form id="game-update-active-form" class="form-horizontal upload-form" action="<?=base_url('/iovation/postBatchAddEvidence')?>" method="post" accept-charset="utf-8" enctype="multipart/form-data">
                    
       <div class="modal-content">
           <!-- Modal Header -->
           <div class="modal-header">
               <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
               <h4 class="modal-title">
                   <?=lang('Batch Add evidence')?>
               </h4>
           </div>
               <!-- Modal body -->
           <div class="modal-body">
                
                    <div class="file-field">
                        <div class="btn btn-primary">
                            <span>Choose file</span>
                            <input id="csv_tag_file" type="file" accept=".csv" name="tags" required onchange="return isValidFileInCSV(this)" >
                        </div>
                        <div class="file-path-wrapper">
                            <span class="span-default" >Upload your file</span>
                        </div>
                        <a id="download_batch_add_evidence_csv"  onclick='download_batch_add_evidence_csv()' class="btn btn-primary btn-lg pull-right panel-button" title="<?=lang('Download Sample CSV File')?>" style="margin-right:1%"><img src="<?=$this->utils->imageUrl('csv.png')?>"/></a>
                    </div>
                
                    


               <!--<div class="row">
                   <div class="col-lg-6">
                       <form id="game-update-active-form" class="form-horizontal" action="<?=base_url('/iovation/postBatchAddEvidence')?>" method="post" accept-charset="utf-8" enctype="multipart/form-data">
                           <div class="form-group">
                               <label class="col-lg-3"><?=lang('CSV File')?></label>
                               <div class="col-lg-8">
                                   <input class="form-control" name="tags" class="user-error" aria-invalid="true" type="file" accept=".csv"/>
                               </div>
                           </div>
                           <div class="form-group">            
                                <div class="col-lg-8 col-lg-offset-3">
                                    <a id='download_batch_add_evidence_csv' class='btn btn-info btn-sm' onclick='download_batch_add_evidence_csv()'><span class='glyphicon glyphicon-download-alt' aria-hidden='true'></span> Download Template</a>
                                </div>
                           </div>
                           <div class="form-group">
                               <div class="col-lg-8 col-lg-offset-3">
                                   <button type="submit" id="batchAddEvidenceSubmit" class="btn btn-primary"><?=lang("Submit")?></button>
                               </div>    
                           </div>
                       </form>
                   </div>
                   
               </div>-->
               <span class="help-block" style="color: red;">&nbsp;</span>
           </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary submit-file" id="batchAddEvidenceSubmit"><?=lang('lang.submit')?></button>
            </div>
       </div><!--/ modal content-->
       </form>
   </div>
</div>
<!--/ BATCH ADD/RETRACT EVIDENCE MODAL -->

<?php if($this->utils->isEnabledFeature('export_excel_on_queue')){?>
    <form id="_export_excel_queue_form" class="hidden" method="POST" target="_blank">
        <input name='json_search' type="hidden">
    </form>
    <form id="_export_csv_form" class="hidden" method="POST" target="_blank">
        <input name='json_search' id = "json_csv_search" type="hidden">
    </form>
<?php }?>

<script type="text/javascript">
    var dataTable = '';

    var LANG = {
        RETRACT_CONFIRM_MESSAGE : "<?=lang('report.iovation.conf.retract.msg');?>",
    };

    function notify(type, msg) {
        $.notify({
            message: msg
        }, {
            type: type,
            z_index: 5000,
        });
    }

    function isValidFileInCSV(field) {

        var value = field.value;
        var res = value.split('.').pop();

        var oFile = document.getElementById(field.id).files[0];

        if( res != 'csv' ){
            $('#' + field.id).val('');
            return alert('<?=lang('Please enter valid File')?>');
        }
    }

    function retractEvidence(evidenceId){
        var dst_url = "/iovation/retract_evidence/" + evidenceId;
        var panelTitle = "<?=lang('iovation_evidence.retract_evidence');?>";

        $('#modal-panel-title').html(panelTitle);
        var main_selector = "#form-modal"
        var body_selector = main_selector + ' .modal-body';
        var target = $(body_selector);
        target.html('<center><img src="' + imgloader + '"></center>').delay(1000).load(dst_url);
        $(main_selector).modal('show');
    }

    function resendIovation(id){
        var r = confirm(LANG.RESEND_CONFIRM_MESSAGE);
        if (r == true) {
            var type = 'POST';
            var params = {
                id: id,
            };
            var url='<?php echo site_url('iovation/resend') ?>';
            executeAction(url, type, params);
        } else {

        }
    }

    function showFormModal(evidenceId) {

        var dst_url = "/iovation/add_edit_evidence";
        var panelTitle = "<?=lang('iovation_evidence.add_evidence');?>";

        if(evidenceId !== null){
            dst_url = "/iovation/add_edit_evidence/" + evidenceId;
            panelTitle = "<?=lang('iovation_evidence.edit_evidence');?>";
        }
        $('#modal-panel-title').html(panelTitle);
        var main_selector = "#form-modal"
        var body_selector = main_selector + ' .modal-body';
        var target = $(body_selector);
        target.html('<center><img src="' + imgloader + '"></center>').delay(1000).load(dst_url);
        $(main_selector).modal('show');
    }

    function executeAction(url, type, params) {

        if (type == 'POST') {

            $.ajax({
                method: type,
                url: url,
                data: params,
                dataType: "json"
            })
            .done(function(data) {
                if(data.status=='error'){
                    notify('danger',data.msg );
                }else{
                    notify('success',data.msg );
                }

            }).fail(function() {
                notify('danger','<?php echo lang('sys.ga.erroccured') ?>' );
            });


        } else {

            $.ajax({
                method: type,
                url: url,
                dataType: "json"
            })
            .done(function(data) {
                notify('success',data.msg );
            }).fail(function() {
                notify('danger','<?php echo lang('sys.ga.erroccured') ?>' );
            });
        }


    }//end executeAction



    function reloadDataTable(){
        dataTable.ajax.reload();
    }//end reloadDataTable

    function download_batch_add_evidence_csv() {
        var optionalHeaders = ['applied_to', 'device_id', 'username', 'user_type', 'evidence_type_code', 'comments'];
        var csvContentsObj = {
            headers: [],
            dummyData: []
        };
        
        optionalHeaders.forEach(function(header) {
            csvContentsObj.headers.push(header);
        });
        
        var csvContent = "";
        csvContent += csvContentsObj.headers.join(',') + "\n";
        csvContent += csvContentsObj.dummyData.join(',') + "\n";

        var hiddenElement = document.getElementById('download_batch_add_evidence_csv');
        hiddenElement.href = 'data:text/csv;charset=utf-8,' + encodeURI(csvContent);
        hiddenElement.target = '_blank';

        var fileName = 'sample_batch_add_evidence_' + $.now(); 
        var fileExtension = '.csv';
        hiddenElement.download = fileName + fileExtension;
    }

    $(document).ready(function(){
        var hide_targets=<?=json_encode($hide_cols); ?>;

        var dataTable = $('#result_table').DataTable({

            <?php if( ! empty($enable_freeze_top_in_list) ): ?>
                scrollY:        1000,
                scrollX:        true,
                deferRender:    true,
                scroller:       true,
                scrollCollapse: true,
            <?php endif; // EOF if( ! empty($enable_freeze_top_in_list) ):... ?>

            stateSave: true,
            dom: "<'panel-body'<'pull-right'B><'pull-right progress-container hidden-xs'>l><'dt-information-summary1 text-info pull-left' i>t<'panel-body'<'pull-right'p><'dt-information-summary2 text-info pull-left' i>>",
            autoWidth: false,
            searching: false,
            buttons: [
                {
                    extend: 'colvis',
                    postfixButtons: [ 'colvisRestore' ],
                    className: '<?=$this->utils->getConfig('use_new_sbe_color') ? 'btn-linkwater' : ''?>',
                }
                ,{
                    text: "<?= lang('CSV Export'); ?>",
                    className:'btn btn-sm <?=$this->utils->getConfig('use_new_sbe_color') ? 'btn-portage' : 'btn-primary'?>',
                    action: function ( e, dt, node, config ) {
                        var form_params=$('#search-form').serializeArray();
                       var d = {'extra_search': form_params, 'export_format': 'csv', 'export_type': 'queue',
                            'draw':1, 'length':-1, 'start':0};
                            $("#_export_excel_queue_form").attr('action', site_url('/export_data/iovationEvidence'));
                            $("#_export_excel_queue_form [name=json_search]").val(JSON.stringify(d));
                            $("#_export_excel_queue_form").submit();
                    }
                }
            ],
            columnDefs: [
                { className: 'text-right', targets: [] },
                { className: 'text-center', targets: [6,7] },
                { "visible": false, "targets": hide_targets },
                { sortable: false, "targets": [ 0 ] }
            ],
            order: [ 1, 'desc' ],

            // SERVER-SIDE PROCESSING
            processing: true,
            serverSide: true,
            ajax: function (data, callback, settings) {
                data.extra_search = $('#search-form').serializeArray();
                $.post(base_url + "api/iovationEvidence", data, function(data) {
                    $.each(data.data, function(i, v){
                        /*sub = v[10].replace(/<(?:.|\n|)*?>/gm, '');
                        convertedSub = sub.replace(',', '');
                        if(Number.parseFloat(convertedSub)){
                            subTotal+= Number.parseFloat(convertedSub);
                        }*/
                    });
                    callback(data);
                    if ( dataTable.rows( { selected: true } ).indexes().length === 0 ) {
                        //dataTable.buttons().disable();
                    }
                    else {
                        dataTable.buttons().enable();
                    }
                }, 'json');
            }
        });


        dataTable.on( 'draw', function (e, settings) {

        <?php if( ! empty($enable_freeze_top_in_list) ): ?>
            var _dataTableIdstr = settings.sTableId; // for multi-dataTable in a page.
            _dataTableIdstr += '_wrapper'; // append the suffix, "_wrapper".
        // console.log('_dataTableIdstr:', '#'+ _dataTableIdstr);
            var _min_height = $('#'+ _dataTableIdstr).find('.dataTables_scrollBody').find('.table tbody tr').height();
            _min_height = _min_height* 5; // limit min height: 5 rows

            var _scrollBodyHeight = window.innerHeight;
            _scrollBodyHeight -= $('.navbar-fixed-top').height();
            _scrollBodyHeight -= $('#'+ _dataTableIdstr).find('.dataTables_scrollHead').height();
            _scrollBodyHeight -= $('#'+ _dataTableIdstr).find('.dataTables_scrollFoot').height();
            _scrollBodyHeight -= $('#'+ _dataTableIdstr).find('.dataTables_paginate').closest('.panel-body').height();
            _scrollBodyHeight -= 44;// buffer
            if(_scrollBodyHeight < _min_height ){
                _scrollBodyHeight = _min_height;
            }
            $('#'+ _dataTableIdstr).find('.dataTables_scrollBody').css({'max-height': _scrollBodyHeight+ 'px'});

        <?php endif; // EOF if( ! empty($enable_freeze_top_in_list) ):... ?>
        });

        var date_today = new moment().format('YYYY-MM-DD');

        $('#btnResetFields').click(function() {
            $('.group-reset').val('');
            $('#include_all_downlines').prop('checked', false);
            $("#search_payment_date").val(date_today + " to " + date_today);
        });

        $("#add-evidence").on('click', function () {
            showFormModal(null);
        });

        $('#form-modal').on('hidden.bs.modal', function () {
            dataTable.ajax.reload();
        });

        function hideFormModal() {
            var main_selector = "#form-modal"
            $(main_selector).modal('hide');
        }


        function editIovation(){

        }

        

		var batchremoveevidence = document.getElementById('batch-retract-evidence');

        batchremoveevidence.addEventListener('click', function() {
        	var message = '<?=lang("Are you sure you want to retract selected evidence?")?>';
        	//var comment = confirm(message);
            let comments = prompt("Comment", "");
        	if (comments == null || comments == "") {
                alert('Comment is required!!');
        	}else{                
                var d = {'extra_search':$('#evidencelist').serializeArray(), comment: comments};
                $("#_batch_remove_evidence_ids_queue_form [name=json_search]").val(JSON.stringify(d));
                $('#_batch_remove_evidence_ids_queue_form').submit();
            }
        }, false);

        $('#batchAddEvidenceModal').on('hidden.bs.modal', function () {
		    $("#csv_tag_file").val(null);
		    $(".file-path-wrapper span").text('Upload your file');
		    $(".upload-form .file-field .file-path-wrapper span").removeClass("span-select").addClass("span-default");
		});

        $('#csv_tag_file').change(function(e){
            let fileName = e.target.files[0].name;
            $(".file-path-wrapper span").text(fileName);
            $(".upload-form .file-field .file-path-wrapper span").removeClass("span-default").addClass("span-select");
        });

    });
</script>