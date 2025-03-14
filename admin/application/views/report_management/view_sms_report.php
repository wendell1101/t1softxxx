<style type="text/css">
    .select2-container--default .select2-selection--single{
        padding-top: 2px;
        height: 35px;
        font-size: 1.2em;
        position: relative;
        border-radius: 0;
        font-size:12px;;
    }
</style>
<div class="panel panel-primary hidden">
    <div class="panel-heading">
        <h4 class="panel-title">
            <i class="fa fa-search"></i> <?=lang("lang.search")?>
            <span class="pull-right">
                <a data-toggle="collapse" href="#collapseSMSReport" class="btn btn-xs btn-primary <?= $this->config->item('default_open_search_panel') ? '' : 'collapsed'?>"></a>
            </span>
        </h4>
    </div>
    <div id="collapseSMSReport" class="panel-collapse <?= $this->config->item('default_open_search_panel') ? '' : 'collapse'?>">
        <div class="panel-body ">
            <form id="form-filter" class="form-horizontal" method="post">
                <div class="row">
                    <div class="col-md-6">
                        <label class="control-label">
                            <?=lang('Sent Date/Time')?>
                        </label>

                        <input id="search_sms_date" class="form-control input-sm dateInput"
                            data-start="#date_from"
                            data-end="#date_to"
                            data-time="true"
                            data-restrict-max-range="1"
                            data-restrict-max-range-second-condition="180"
                            data-restrict-range-label="<?=lang("sms_report.date_range_hint");?>"
                            data-override-on-apply="true"
                            autocomplete="off"
                        />
                        <input type="hidden" id="date_from" name="date_from"/>
                        <input type="hidden" id="date_to" name="date_to"/>
                    </div>
                    <div class="col-md-3">
                        <label class="control-label" for="username">
                            <?=lang('Username');?>
                        </label>
                        <input type="text" name="username" id="username" class="form-control"/>
                    </div>
                    <div class="col-md-3">
                        <label class="control-label" for="mobileNumber">
                            <?=lang('Contact Number')?>
                        </label>
                        <input type="text" name="mobileNumber" id="mobileNumber" class="form-control"/>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-1" style="padding-top: 20px">
                        <input type="submit" value="<?=lang('lang.search')?>" id="search_main" class="btn btn-sm btn-portage" />
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="panel panel-primary">
    <div class="panel-heading">
        <h4 class="panel-title">
            <i class="icon-mobile"></i> <?=lang('SMS Verification Code')?>
        </h4>
    </div>
    <div class="panel-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover" id="sms_report_table">
                <thead>
                    <tr>
                        <th><?=lang('Username')?></th>
                        <th><?=lang('Session')?></th>
                        <th><?=lang('Mobile Number')?></th>
                        <th><?=lang('Verification Code')?></th>
                        <th><?=lang('Verified')?></th>
                        <th><?=lang('Created')?></th>
                        <th><?=lang('Timeout')?></th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
    <div class="panel-footer"></div>
</div>
<script type="text/javascript">
    $(document).ready(function(){
        var dataTable = $('#sms_report_table').DataTable({
            lengthMenu: JSON.parse('<?=json_encode($this->utils->getConfig('default_datatable_lengthMenu'))?>'),
            pageLength: <?=$this->utils->getDefaultItemsPerPage()?>,
            order: [[ 5, "desc" ]],
            dom: "<'panel-body'<'pull-right'B><'pull-right progress-container hidden-xs'>l><'dt-information-summary1 text-info pull-left' i>t<'panel-body'<'pull-right'p><'dt-information-summary2 text-info pull-left' i>>",
            autoWidth: false,
            searching: false,
            buttons: [
                {
                    extend: 'colvis',
                    postfixButtons: [ 'colvisRestore' ],
                    className: 'btn-linkwater',
                },
                <?php if($this->permissions->checkPermissions('sms_verify_report') ){ ?>
                    {
                        text: '<?php echo lang("lang.export_excel"); ?>',
                        className:'btn btn-sm btn-portage',
                        action: function ( e, dt, node, config ) {
                            var d = {'extra_search':$('#form-filter').serializeArray(), 'draw':1, 'length':-1, 'start':0};
                            $.post(site_url('/export_data/smsVerificationCodeReport'), d, function(data){
                                //create iframe and set link
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
            <?php if( ! empty($enable_freeze_top_in_list) ): ?>
                scrollY:        1000,
                scrollX:        true,
                deferRender:    true,
                scroller:       true,
                scrollCollapse: true,
            <?php endif; // EOF if( ! empty($enable_freeze_top_in_list) ):... ?>
            drawCallback : function( settings ) {
                <?php if( ! empty($enable_freeze_top_in_list) ): ?>
                var _min_height = $('.dataTables_scrollBody').find('.table tbody tr').height();
                _min_height = _min_height* 5; // limit min height: 5 rows
                var _scrollBodyHeight = window.innerHeight;
                _scrollBodyHeight -= $('.navbar-fixed-top').height();
                _scrollBodyHeight -= $('.dataTables_scrollHead').height();
                _scrollBodyHeight -= $('.dataTables_scrollFoot').height();
                _scrollBodyHeight -= $('.dataTables_paginate').closest('.panel-body').height();
                _scrollBodyHeight -= 44;// buffer
                if(_scrollBodyHeight > _min_height ){
                    $('.dataTables_scrollBody').css({'max-height': _scrollBodyHeight+ 'px'});
                }
                <?php endif; // EOF if( ! empty($enable_freeze_top_in_list) ):... ?>
            },
            processing: true,
            serverSide: true,
            ajax: function (data, callback, settings) {
                data.extra_search = $('#form-filter').serializeArray();
                $.post(base_url + "api/smsVerificationCodeReport", data, function(data) {
                    callback(data);
                    if ( dataTable.rows( { selected: true } ).indexes().length === 0 ) {
                        dataTable.buttons().disable();
                    }
                    else {
                        dataTable.buttons().enable();
                    }
                }, 'json');
            },
        });

        function checkDateRangeRestrictionWithUsername(){
            var dateInput = $('#search_sms_date.dateInput');
            var isRange = (dateInput.data('start') && dateInput.data('end'));

            var restricted_range = dateInput.data('restrict-max-range');
            var second_restricted_range = dateInput.data('restrict-max-range-second-condition');

            if($.trim(second_restricted_range) != '' && $.isNumeric(second_restricted_range) && isRange){
                if($.trim($('#username').val()) != '' || $.trim($('#mobileNumber').val()) != ''){
                    dateInput.data('restrict-max-range', second_restricted_range);
                    dateInput.data('restrict-range-label','<?=lang("sms_report.date_range_second_restricted_range")?>');
                    restricted_range = second_restricted_range;
                }
                else{
                    dateInput.data('restrict-max-range','1');
                    dateInput.data('restrict-range-label','<?=lang("sms_report.date_range_hint")?>');
                    restricted_range = dateInput.data('restrict-max-range');
                }
            }

            return restricted_range;
        }

        var dateInput = $('#search_sms_date.dateInput');
        var isRange = (dateInput.data('start') && dateInput.data('end'));
        var isTime = dateInput.data('time');

        dateInput.keypress(function(e){
            e.preventDefault();
            return false;
        });

        // -- Use reset to current day upon cancel/reset in daterange instead of emptying the value
        dateInput.on('cancel.daterangepicker', function(ev, picker) {
            // -- if start date was empty, add a default one
            if($.trim($(dateInput.data('start')).val()) == ''){
                var startEl = $(dateInput.data('start'));
                    start = startEl.val();
                    start = start ? moment(start, 'YYYY-MM-DD HH:mm:ss') : moment().startOf('day');
                    startEl.val(isTime ? start.format('YYYY-MM-DD HH:mm:ss') : start.startOf('day').format('YYYY-MM-DD HH:mm:ss'));

                dateInput.data('daterangepicker').setStartDate(start);
            }

            // -- if end date was empty, add a default one
            if($.trim($(dateInput.data('end')).val()) == ''){
                var endEl = $(dateInput.data('end'));
                    end = endEl.val();
                    end = end ? moment(end, 'YYYY-MM-DD HH:mm:ss') : moment().endOf('day');
                    endEl.val(isTime ? end.format('YYYY-MM-DD HH:mm:ss') : end.endOf('day').format('YYYY-MM-DD HH:mm:ss'));

                dateInput.data('daterangepicker').setEndDate(end);
            }

            dateInput.val($(dateInput.data('start')).val() + ' to ' + $(dateInput.data('end')).val());
        });

        dateInput.on('apply.daterangepicker, hide.daterangepicker', function(ev, picker) {

            var restricted_range = checkDateRangeRestrictionWithUsername();

            if (restricted_range == '' && !$.isNumeric(restricted_range) && !isRange)
                return false;

            var a_day = 86400000; // -- one day
            var restriction = a_day * restricted_range;
            var start_date = new Date(picker.startDate._d);
            var end_date = new Date(picker.endDate._d);

            // -- if start date was empty, add a default one
            if($.trim($(dateInput.data('start')).val()) == ''){
                var startEl = $(dateInput.data('start'));
                    start = startEl.val();
                    start = start ? moment(start, 'YYYY-MM-DD HH:mm:ss') : moment().startOf('day');
                    startEl.val(isTime ? start.format('YYYY-MM-DD HH:mm:ss') : start.startOf('day').format('YYYY-MM-DD HH:mm:ss'));

                dateInput.data('daterangepicker').setStartDate(start);
            }

            // -- if end date was empty, add a default one
            if($.trim($(dateInput.data('end')).val()) == ''){
                var endEl = $(dateInput.data('end'));
                    end = endEl.val();
                    end = end ? moment(end, 'YYYY-MM-DD HH:mm:ss') : moment().endOf('day');
                    endEl.val(isTime ? end.format('YYYY-MM-DD HH:mm:ss') : end.endOf('day').format('YYYY-MM-DD HH:mm:ss'));

                dateInput.data('daterangepicker').setEndDate(end);
            }

            dateInput.val($(dateInput.data('start')).val() + ' to ' + $(dateInput.data('end')).val());

            if((end_date - start_date) >= restriction){ // -- get timestamp result

                var second_restricted_range = dateInput.data('restrict-max-range-second-condition');
                var second_restriction = a_day * second_restricted_range;
                if((end_date - start_date) >= second_restriction){
                    alert('<?=lang("sms_report.date_range_second_restricted_range")?>');
                } else if(dateInput.data('restrict-range-label') && $.trim(dateInput.data('restrict-range-label')) !== ""){
                    alert(dateInput.data('restrict-range-label'));
                } else {
                    var day_label = 'day';

                    if(restricted_range > 1) day_label = 'days'

                    alert('Please choose a date range not greater than '+ restricted_range +' '+ day_label);
                }

                //  -- reset value
                //  -- if validation fails, do not change anything, retain the last correct values
                $(dateInput.data('start')).val('');
                $(dateInput.data('end')).val('');

                var startEl = $(dateInput.data('start'));
                    start = picker.oldStartDate;//startEl.val();
                    start = start ? moment(start, 'YYYY-MM-DD HH:mm:ss') : moment().startOf('day');

                var endEl = $(dateInput.data('end'));
                    end = picker.oldEndDate;//endEl.val();
                    end = end ? moment(end, 'YYYY-MM-DD HH:mm:ss') : moment().endOf('day');

                dateInput.data('daterangepicker').setStartDate(start);
                dateInput.data('daterangepicker').setEndDate(end);

                startEl.val(isTime ? start.format('YYYY-MM-DD HH:mm:ss') : start.startOf('day').format('YYYY-MM-DD HH:mm:ss'));
                endEl.val(isTime ? end.format('YYYY-MM-DD HH:mm:ss') : end.endOf('day').format('YYYY-MM-DD HH:mm:ss'));

                dateInput.val(startEl.val() + ' to ' + endEl.val());
            }
        });

        $('#search_main').click(function(e){
            e.preventDefault();

            var dateInput = $('#search_sms_date.dateInput');

            var restricted_range = checkDateRangeRestrictionWithUsername();

            if (restricted_range == '' && !$.isNumeric(restricted_range) && !isRange)
                return false;

            var start_date = new Date($('#date_from').val());
            var end_date = new Date($('#date_to').val());
            var a_day = 86400000;
            var restriction = a_day * restricted_range;

            if($.trim(dateInput.val()) == '' || ((end_date - start_date) >= restriction)){

                var second_restricted_range = dateInput.data('restrict-max-range-second-condition');
                var second_restriction = a_day * second_restricted_range;
                if((end_date - start_date) >= second_restriction){
                    alert('<?=lang("sms_report.date_range_second_restricted_range")?>');
                } else if(dateInput.data('restrict-range-label') && $.trim(dateInput.data('restrict-range-label')) !== ""){
                    alert(dateInput.data('restrict-range-label'));
                } else {
                    var day_label = 'day';

                    if(restricted_range > 1) day_label = 'days'

                    alert('Please choose a date range not greater than '+ restricted_range +' '+ day_label);
                }
            }
            else{
                $('#form-filter').submit();
            }
        });

        $('#form-filter').submit( function(e) {
            e.preventDefault();
            dataTable.ajax.reload();
        });
    });
</script>
