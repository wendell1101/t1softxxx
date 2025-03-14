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
                <a data-toggle="collapse" href="#collapseSearchForm" class="btn btn-xs btn-primary <?= $this->config->item('default_open_search_panel') ? '' : 'collapsed'?>" aria-expanded="<?= $this->config->item('default_open_search_panel') ? 'true' : 'false'?>"></a>
            </span>
            <?php include __DIR__ . "/../includes/report_tools.php";?>
        </h4>
    </div>
    <div id="collapseSearchForm" class="panel-collapse <?= $this->config->item('default_open_search_panel') ? 'in' : ''?>">
        <div class="panel-body ">
            <form id="form-filter" class="form-horizontal" method="post">
                <div class="row">
                    <div class="col-md-4">
                        <label class="control-label">
                            <?=lang('Date')?>
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
                        <label class="control-label" for="emailAddress">
                            <?=lang('Email Address')?>
                        </label>
                        <input type="text" name="emailAddress" id="emailAddress" class="form-control"/>
                    </div>
                    <div class="col-md-3">
                        <label class="control-label" for="emailTemplate">
                            <?=lang('email.template')?>
                        </label>
                        <select name="emailTemplate" id="emailTemplate" class="form-control input-sm user-success">
                            <option value='' selected="selected"><?=lang('All')?></option>
                            <?php foreach ($email_template_options as $option) {?>
                                <option value="<?=$option?>"><?=$option?></option>
                            <?php }?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="control-label" for="verificationCode">
                            <?=lang('Verification Code')?>
                        </label>
                        <input type="text" name="verificationCode" id="verificationCode" class="form-control"/>
                    </div>
                    <div class="col-md-2">
                        <label class="control-label" for="sendingStatus">
                            <?=lang('Status')?>
                        </label>
                        <select name="sendingStatus" id="sendingStatus" class="form-control input-sm user-success">
                            <option value='' selected="selected"><?=lang('All')?></option>
                            <option value='0'><?=lang('In Progress')?></option>
                            <option value='1'><?=lang('Success')?></option>
                            <option value='2'><?=lang('Failed')?></option>
                        </select>
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
            <i class="icon-mobile"></i> <?=lang('Email Verification Report')?>
        </h4>
    </div>
    <div class="panel-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover" id="sms_report_table">
                <thead>
                    <tr>
                        <th><?=lang('Date')?></th>
                        <th><?=lang('Player Username')?></th>
                        <th><?=lang('Email Address')?></th>
                        <th><?=lang('email.template')?></th>
                        <th><?=lang('Verification Code')?></th>
                        <th><?=lang('Status')?></th>
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
            <?php if( ! empty($enable_freeze_top_in_list) ): ?>
                scrollY:        1000,
                scrollX:        true,
                deferRender:    true,
                scroller:       true,
                scrollCollapse: true,
            <?php endif; // EOF if( ! empty($enable_freeze_top_in_list) ):... ?>
            lengthMenu: JSON.parse('<?=json_encode($this->utils->getConfig('default_datatable_lengthMenu'))?>'),
            pageLength: <?=$this->utils->getDefaultItemsPerPage()?>,
            order: [[ 0, "desc" ]],
            dom: "<'panel-body'<'pull-right'B><'pull-right progress-container hidden-xs'>l><'dt-information-summary1 text-info pull-left' i>t<'panel-body'<'pull-right'p><'dt-information-summary2 text-info pull-left' i>>",
            autoWidth: false,
            searching: false,
            buttons: [
                {
                    extend: 'colvis',
                    postfixButtons: [ 'colvisRestore' ],
                    className: 'btn-linkwater',
                },
            ],
            processing: true,
            serverSide: true,
            ajax: function (data, callback, settings) {
                data.extra_search = $('#form-filter').serializeArray();
                $.post(base_url + "api/emailVerificationReport", data, function(data) {
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
        dataTable.on( 'draw', function (e, settings) {

			<?php if( ! empty($enable_freeze_top_in_list) ): ?>
				var _min_height = $('.dataTables_scrollBody').find('.table tbody tr').height();
                _min_height = _min_height* 5; // limit min height: 5 rows

                var _scrollBodyHeight = window.innerHeight;
                _scrollBodyHeight -= $('.navbar-fixed-top').height();
                _scrollBodyHeight -= $('.dataTables_scrollHead').height();
                _scrollBodyHeight -= $('.dataTables_scrollFoot').height();
                _scrollBodyHeight -= $('.dataTables_paginate').closest('.panel-body').height();
                _scrollBodyHeight -= 44;// buffer
				if(_scrollBodyHeight < _min_height ){
					_scrollBodyHeight = _min_height;
				}
				$('.dataTables_scrollBody').css({'max-height': _scrollBodyHeight+ 'px'});

            <?php endif; // EOF if( ! empty($enable_freeze_top_in_list) ):... ?>

        });

        $('#form-filter').submit( function(e) {
            e.preventDefault();
            dataTable.ajax.reload();
        });
    });
</script>
