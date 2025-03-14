<style type="text/css">
    #collapseCashbackReport input{
        font-weight:bold;
    }
</style>

<form class="form-horizontal" id="search-form" method="get" role="form">
    <div class="panel panel-primary hidden">
        <div class="panel-heading">
            <h4 class="panel-title">
                <i class="fa fa-search"></i> <?=$title; ?>
                <span class="pull-right">
                <a data-toggle="collapse" href="#collapseCashbackReport" class="btn btn-info btn-xs <?=$this->config->item('default_open_search_panel') ? '' : 'collapsed'?>"></a>
            </span>
                <?php include __DIR__ . "/../includes/report_tools.php" ?>
            </h4>
        </div>

        <div id="collapseCashbackReport" class="panel-collapse <?=$this->utils->getConfig('default_open_search_panel') ? '' : 'collapse in' ?>">
            <div class="panel-body">
                <div class="row">
                    <div class="col-md-3 col-lg-3">
                        <label class="control-label" for="period"><?=lang('aff.ap07');?>:</label>
                        <input type="text" class="form-control input-sm dateInput" id = "filterDate" data-start="#dateRangeValueStart" data-end="#dateRangeValueEnd" data-time="false"/>
                        <input type="hidden" id="dateRangeValueStart" name="by_date_from" value="<?=$conditions['by_date_from']; ?>" />
                        <input type="hidden" id="dateRangeValueEnd" name="by_date_to" value="<?=$conditions['by_date_to']; ?>" />
                    </div>
                    <div class="col-md-3 col-lg-3">
                        <label class="control-label"><?=lang('Enabled date'); ?></label><br>
                        <input type="checkbox" id="enable_date" data-off-text="<?=lang('off'); ?>" data-on-text="<?=lang('on'); ?>" name="enable_date" data-size='mini' value='true' <?=$conditions['enable_date'] ? 'checked="checked"' : ''; ?>>
                    </div>
                    <div class="col-md-3 col-lg-3">                        
                    </div>
                </div>

                <div class="row">     
                    <div class="col-md-3 col-lg-3">
                        <label class="control-label"><?=lang('Affiliate Username'); ?></label>
                        <input type="text" name="by_affiliate_username" class="form-control input-sm" placeholder='<?=lang('Enter Username'); ?>'
                               value="<?=$conditions['by_affiliate_username']; ?>"/>
                    </div> 
                    <div class="col-md-3 col-lg-3">
                        <label for="by_banner_name" class="control-label"><?php echo lang('Banner'); ?>:</label>
                        <input type="text" name="by_banner_name" id="by_banner_name" class="form-control input-sm" value="<?php echo $conditions['by_banner_name']; ?>"/>
                    </div>
                    <div class="col-md-3 col-lg-3">
                        <label for="by_tracking_code" class="control-label"><?php echo lang('Tracking Code'); ?>:</label>
                        <input type="text" name="by_tracking_code" id="by_tracking_code" class="form-control input-sm" value="<?php echo $conditions['by_tracking_code']; ?>"/>
                    </div>
                    <div class="col-md-3 col-lg-3">
                        <label for="by_tracking_source_code" class="control-label"><?php echo lang('Source Code'); ?>:</label>
                        <input type="text" name="by_tracking_source_code" id="by_tracking_source_code" class="form-control input-sm" value="<?php echo $conditions['by_tracking_source_code']; ?>"/>
                    </div>
                </div>

                <div class="row">  
                    <div class="col-md-3 col-lg-3">
                        <label for="by_type" class="control-label"><?php echo lang('Type'); ?>:</label>
                        <select name="by_type" id="by_type" class="form-control input-sm"> 
                            <option value=""><?php echo lang('Select All')?></option>
                            <option  <?php echo ($conditions['by_type'] == Http_request::TYPE_AFFILIATE_BANNER) ? 'selected' : ''  ?>  value="<?php echo Http_request::TYPE_AFFILIATE_BANNER ?>"><?php echo lang('con.aff29')?></option>
                            <option  <?php echo ($conditions['by_type'] == Http_request::TYPE_AFFILIATE_SOURCE_CODE )? 'selected' : '' ?> value="<?php echo Http_request::TYPE_AFFILIATE_SOURCE_CODE ?>"><?php echo lang('Affiliate Source Code');?></option>
                        </select>
                    </div>
                    <div class="col-md-3 col-lg-3">
                        <label for="registrationWebsite" class="control-label"><?php echo lang('Registration Website'); ?>:</label>
                        <input type="text" name="registrationWebsite" id="registrationWebsite" class="form-control input-sm" value="<?php echo $conditions['registrationWebsite']; ?>"/>
                    </div>
                    <?php if( $this->utils->isEnabledFeature('enable_tracking_remarks_field') ){?>
                    <div class="col-md-3 col-lg-3">
                        <label for="remarks" class="control-label"><?php echo lang('Remarks'); ?>:</label>
                        <input type="text" name="remarks" id="remarks" class="form-control input-sm" value="<?php echo $conditions['remarks']; ?>"/>
                    </div>
                    <?php } ?>
                </div>

                <div class="row">
                    <div class="col-md-3 col-lg-3" style="padding: 10px;">
                        <input type="button" value="<?=lang('Reset'); ?>" class="btn btn-danger btn-sm" onclick="resetForm()">
                        <input class="btn btn-sm btn-primary" type="submit" value="<?=lang('Search'); ?>" />
                    </div>
                </div>
            </div>
        </div>

    </div>
</form>
<!--end of Sort Information-->


<div class="panel panel-primary">
    <div class="panel-heading custom-ph">
        <h4 class="panel-title custom-pt"><i class="icon-bullhorn"></i> <?=lang('Report Result'); ?></h4>
    </div>
    <div class="panel-body">
        <!-- result table -->
        <div id="logList" class="table-responsive">
            <table class="table table-striped table-hover table-condensed" id="report_table" style="width:100%;">
                <thead>
                    <tr>
                        <th><?=lang('Date'); ?></th>
                        <th><?=lang('Affiliate Username'); ?></th>
                        <th><?=lang('Registration Website'); ?></th>
                        <th><?=lang('Banner'); ?></th>
                        <th><?=lang('Tracking Code'); ?></th>
                        <th><?=lang('Source Code'); ?></th>
                        <th><?=lang('No of Clicks'); ?></th>
                        <th><?=lang('Sign Up'); ?></th>
                        <th><?=lang('First Time Deposit'); ?></th>
                        <th><?=lang('First Time Deposit Amount'); ?></th>
                        <th><?=lang('Total Deposit Amount'); ?></th>
                        <?php if( $this->utils->isEnabledFeature('enable_tracking_remarks_field') ){?>
                        <th><?=lang('Remarks'); ?></th>
                        <?php } ?>
                    </tr>               
                </thead>
                <tbody></tbody>
                <tfoot>
                <tr>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th id="first-time-deposit-amount"></th>
                    <th id="total-deposit-amount"></th>
                    <?php if( $this->utils->isEnabledFeature('enable_tracking_remarks_field') ){?>
                    <th></th>
                    <?php } ?>
                </tr>
                </tfoot>
            </table>
        </div>
    </div>
    <!--end of result table -->
    <div class="panel-footer"></div>
</div>

<?php if($this->utils->isEnabledFeature('export_excel_on_queue')){?>
    <form id="_export_excel_queue_form" class="hidden" method="POST" target="_blank">
        <input name='json_search' type="hidden">
    </form>
<?php }?>
<script type="text/javascript">
    $(document).ready(function(){
        $("#affiliate_traffic_statistics").addClass('active');
    });
    function getCurrentDate(d){
        var month = d.getMonth()+1;
        var day = d.getDate();
        var output = d.getFullYear() + '-' +
            ((''+month).length<2 ? '0' : '') + month + '-' +
            ((''+day).length<2 ? '0' : '') + day;
        return output;
    }

    function resetForm(){
        //get current date
        var date_from = new Date();
        //get 7 days before
        date_from.setDate(date_from.getDate() - 7);
        var from = getCurrentDate(date_from);
        var to = getCurrentDate(new Date());
        //set default value
        $('#dateRangeValueStart').val(from + " 00:00:00");
        $('#dateRangeValueEnd').val(to + " 23:59:59");
        $("#filterDate").data('daterangepicker').setStartDate(from);
        $("#filterDate").data('daterangepicker').setEndDate(to);
        $("input[name='by_banner_name']").val("");
        $("input[name='by_tracking_code']").val("");
        $("input[name='by_tracking_source_code']").val("");
        $("select option").removeAttr('selected');
        $("input[name='registrationWebsite']").val("");
        $("input[name='remarks']").val("");
        $("input[name='by_affiliate_username']").val("");               
        $("#enable_date").bootstrapSwitch('state', true);        
        
    }

    $(document).ready(function(){
        $("input[type='checkbox']").bootstrapSwitch();

        $('.bookmark-this').click(_pubutils.addBookmark);

        $('#search-form input[type="text"],#search-form input[type="number"],#search-form input[type="email"]').keypress(function (e) {
            if (e.which == 13) {
                $('#search-form').trigger('submit');
            }
        });

        $('#report_table').DataTable({
            autoWidth: false,
            searching: false,
            <?php if($this->utils->isEnabledFeature('column_visibility_report')){ ?>
            stateSave: true,
            <?php } else { ?>
            stateSave: false,
            <?php } ?>
            dom: "<'panel-body'<'pull-right'B><'pull-right progress-container hidden-xs'>l><'dt-information-summary1 text-info pull-left' i>t<'panel-body'<'pull-right'p><'dt-information-summary2 text-info pull-left' i>>",
            pageLength: <?=$this->utils->getDefaultItemsPerPage()?>,
            buttons: [
                {
                    extend: 'colvis',
                    postfixButtons: [ 'colvisRestore' ]
                }
                <?php if ($export_report_permission) {?>
                ,{
                    text: '<?=lang("Export CSV"); ?>',
                    className:'btn btn-sm btn-primary',
                    action: function ( e, dt, node, config ) {

                        var form_params=$('#search-form').serializeArray();
                        console.log('form_params');
                        console.log(form_params);
                        var d = {'extra_search': form_params, 'export_format': 'csv', 'export_type': export_type,
                            'draw':1, 'length':-1, 'start':0};

                        $("#_export_excel_queue_form").attr('action', site_url('/export_data/export_affiliate_traffic_statistics'));
                        $("#_export_excel_queue_form [name=json_search]").val(JSON.stringify(d));
                        $("#_export_excel_queue_form").submit();

                    }
                }
                <?php } ?>
            ],
            columnDefs: [
                <?php if(!$this->utils->isEnabledFeature('enable_sortable_columns_affiliate_statistic')) { ?>
                { sortable: false, targets: [ 4,5,6,7,8,9,10,11 ] },
                <?php } ?>
                { className: 'text-right', targets: [ 1,2,3,4,5,6,7,8,9,10,11 ] },
                { visible: false, targets: [ 2,3 ] }
            ],
            "order": [ 0, 'asc' ],
            processing: true,
            serverSide: true,
            ajax: function (data, callback, settings) {
                data.extra_search = $('#search-form').serializeArray();
                $.post(base_url + "api/affiliate_traffic_statistics", data, function(data) {
                    $('#first-time-deposit-amount').text(data.total.first_time_deposit_amount);
                    $('#total-deposit-amount').text(data.total.deposit_amount);
                    callback(data);
                    if ($('#report_table').DataTable().rows( { selected: true } ).indexes().length === 0 ) {
                        $('#report_table').DataTable().buttons().disable();
                    }
                    else {
                        $('#report_table').DataTable().buttons().enable();
                    }
                },'json');
            }
        });
    });
</script>
