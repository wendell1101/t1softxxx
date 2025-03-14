<style type="text/css">
</style>

<form action="<?php echo site_url('payment_management/unusualNotificationRequestsList'); ?>" id="search-form" method="GET">
<div class="panel panel-primary panel_main">

    <div class="panel-heading">
        <h4 class="panel-title"><i class="fa fa-exclamation-circle"></i> &nbsp;<?php echo $title; ?>
        <a href="#main_panel" data-toggle="collapse" class="pull-right"><i class="fa fa-caret-down"></i></a>
        </h4>
    </div>

    <div id="main_panel" class="panel-collapse collapse in ">

    <div class="panel-body">
        <div class="row">
            <div class="col-md-12">
            <p>
                <?php echo lang('Only Support 3rd party: Richpay');?>
            </p>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 col-lg-6">
                <label class="control-label"><?php echo lang('Date'); ?></label>
                <input id="search_cashback_date" class="form-control input-sm dateInput user-success" data-start="#by_date_from" data-end="#by_date_to" data-time="true" autocomplete="off">
                <input type="hidden" id="by_date_from" name="by_date_from" value="<?=$conditions['by_date_from'];?>">
                <input type="hidden" id="by_date_to" name="by_date_to" value="<?=$conditions['by_date_to'];?>">
            </div>
            <div class="col-md-3 col-lg-3">
                <label class="control-label"><?php echo lang('Richpay Transaction ID'); ?></label>
                <input type="text" name="data_transaction_id" class="form-control input-sm"
                value="<?php echo $conditions['data_transaction_id']; ?>"/>
            </div>
        </div>
        <div class="row">
            <div style="padding: 10px;">
                <input class="btn btn-sm <?=$this->utils->getConfig('use_new_sbe_color') ? 'btn-portage' : 'btn-primary'?> pull-right" type="submit" value="<?php echo lang('Search'); ?>" />
            </div>
            <div class="col-md-6 col-lg-6" style="padding: 10px;">
            </div>
        </div>
    </div>

    </div>

</div>
</form>

<div class="panel panel-primary panel_list">

    <div class="panel-heading">
        <h4 class="panel-title"><i class="fa fa-exclamation-circle"></i> &nbsp;<?php echo $title; ?>
        </h4>
    </div>

    <div class="panel-collapse collapse in ">

    <div class="panel-body">
        <div id="logList" class="table-responsive">
            <table class="table table-striped table-hover table-condensed" id="report_table" style="width:100%;">
                <thead>
                    <tr>
                        <th><?=lang('ID'); ?></th>
                        <th><?=lang('Date'); ?></th>
                        <th><?=lang('unusual_notification_requests_status_code');?></th>
                        <th><?=lang('unusual_notification_requests_status_type');?></th>
                        <th><?=lang('unusual_notification_requests_data_transaction_id');?></th>
                        <th><?=lang('unusual_notification_requests_data_payer_bank');?></th>
                        <th><?=lang('unusual_notification_requests_data_payer_account');?></th>
                        <th><?=lang('unusual_notification_requests_data_payee_bank');?></th>
                        <th><?=lang('unusual_notification_requests_data_payee_account');?></th>
                        <th><?=lang('Amount');?></th>
                    </tr>
                </thead>
                <tfoot>
                </tfoot>
            </table>
        </div>
    </div>

    </div>

</div>

<script type="text/javascript">
    $(document).ready(function(){

        $('#search-form input[type="text"],#search-form input[type="number"],#search-form input[type="email"]').keypress(function (e) {
            if (e.which == 13) {
                $('#search-form').trigger('submit');
            }
        });

        $('#report_table').DataTable({
            autoWidth: false,
            searching: false,
            dom: "<'panel-body'<'pull-right'B><'pull-right progress-container hidden-xs'>l><'dt-information-summary1 text-info pull-left' i>t<'panel-body'<'pull-right'p><'dt-information-summary2 text-info pull-left' i>>",
            pageLength: <?=$this->utils->getDefaultItemsPerPage()?>,
            "responsive": {
                details: {
                    type: 'column'
                }
            },
            buttons: [
                {
                    extend: 'colvis',
                    postfixButtons: [ 'colvisRestore' ],
                    className: '<?=$this->utils->getConfig('use_new_sbe_color') ? 'btn-linkwater' : ''?>'
                }
            ],
            columnDefs: [
            ],
            "order": [ 1, 'desc' ],
            processing: true,
            serverSide: true,
            ajax: function (data, callback, settings) {
                data.extra_search = $('#search-form').serializeArray();
                $.post(base_url + "api/unusual_notification_requests_list", data, function(data) {
                    callback(data);
                    if ( $('#report_table').DataTable().rows( { selected: true } ).indexes().length === 0 ) {
                        $('#report_table').DataTable().buttons().disable();
                    }
                    else {
                        $('#report_table').DataTable().buttons().enable();
                    }
                    // $('#total_amount').text(data.summary[0].total_amount);
                },'json');
            },
        });
    });

</script>