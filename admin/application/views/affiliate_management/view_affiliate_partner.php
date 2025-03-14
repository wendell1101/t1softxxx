<div class="panel panel-primary">
    <div class="panel-heading">
        <h4 class="panel-title"><i class="fa fa-search"></i> <?=lang("lang.search")?> </h4>
    </div>
    <div class="panel-body">
        <form class="col-md-12" id="search-form">

            <div class="form-group col-md-3">
                <label class="control-label"><?=lang('Date Range')?></label>
                <div class="input-group">
                    <span class="input-group-addon input">
                        <i class="icon-calendar"></i>
                    </span>
                    <input id="search_date" class="form-control input-sm dateInput" data-start="#date_from" data-end="#date_to" data-time="true"/>

                </div>
                <input type="hidden" name="date_from" id="date_from"/>
                <input type="hidden" name="date_to" id="date_to"/>
            </div>

            <div class="form-group col-md-2">
                <label class="control-label"><?=lang('Affiliate Username');?></label>
                <input type="text" name="username" value="" class="form-control input-sm" required />
            </div>
            <div class="clearfix"></div>
            <div class="col-md-12">
                <div class="form-group pull-left">
                    <button type="submit" class="btn pull-right btn-portage" id="btn-submit"><i class="fa fa-search"></i> <?=lang('lang.search');?></button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="panel panel-primary" >
    <div class="panel-heading">
        <h4 class="panel-title"><i class="icon-profile"></i><?=lang('Affiliate Partners');?> </h4>
    </div>
    <div class="panel-body" >
        <div class="table-responsive">
            <table class="table table-hover table-condensed table-bordered" id="aff_partner_table" style="width: 100%;">
                <thead>
                    <tr>
                        <th><?=lang('Username');?></th>
    					<th><?=lang('Registration time');?></th>
                        <th><?=lang('Registration method');?></th>
                        <th><?=lang('Registration IP');?></th>
    					<th><?=lang('Referrer');?></th>
                        <th><?=lang('Parent Affiliate');?></th>
                        <th><?=lang('Affiliate');?></th>
    					<th><?=lang('Total Deposit');?></th>
                        <th><?=lang('Total Withdrawal');?></th>
                        <th><?=lang('Total Bet');?></th>
                        <th><?=lang('Total Win');?></th>
                        <th><?=lang('Total Loss');?></th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
    <div class="panel-footer"></div>
</div>

<?php if($this->utils->isEnabledFeature('export_excel_on_queue')){?>
    <form id="_export_excel_queue_form" class="hidden" method="POST" target="_blank">
        <input name='json_search' type="hidden">
    </form>
<?php }?>

<script type="text/javascript">
    $(document).ready( function () {

        var aff_table = $('#aff_partner_table').DataTable({
            order: [],
            searching: true,

            stateSave: true,
            dom: "<'panel-body' <'pull-right'B><'pull-right progress-container'>l><'dt-information-summary1 text-info pull-left' i>t<'panel-body'<'pull-right'p><'dt-information-summary2 text-info pull-left' i>>",
            buttons: [
                {
                    extend: 'colvis',
                    className:'btn-linkwater',
                    postfixButtons: [ 'colvisRestore' ]
                },
                {
                    text: "<?= lang('CSV Export'); ?>",
                    className:'btn btn-sm btn-portage',
                    action: function ( e, dt, node, config ) {
                        // OGP-25799
                        let dateFrom = new Date($('#date_from').val());
                        let dateTo = new Date($('#date_to').val());
                        let confirmed = true;

                        // for getting time
                        const msBetweenDates = Math.abs(dateFrom.getTime() - dateTo.getTime());

                        // convert MS to hours
                        const hoursBetweenDates = Math.floor(msBetweenDates / (60 * 60 * 1000));

                        var form_params=$('#search-form').serializeArray();
                        var d = {'extra_search': form_params, 'export_format': 'csv', 'export_type': 'queue',
                            'draw':1, 'length':-1, 'start':0};

                        if (hoursBetweenDates > 24){
                            confirmed = confirm('<?=lang('aff.partner.export')?>');
                        }
                        if(confirmed) {
                            $("#_export_excel_queue_form").attr('action', site_url('/export_data/affiliatePartners'));
                            $("#_export_excel_queue_form [name=json_search]").val(JSON.stringify(d));
                            $("#_export_excel_queue_form").submit();
                        }
                    }
                }
            ],

            // SERVER-SIDE PROCESSING
            processing: true,
            serverSide: true,
            ajax: function (data, callback, settings) {
                data.extra_search = $('#search-form').serializeArray();
                $.post(base_url + "api/affiliate_partners", data, function(data) {
                    callback(data);
                }, 'json');
            }
        });

        $('#btn-submit').click( function(e) {
            e.preventDefault();
            aff_table.ajax.reload();
        });
    } );

</script>


