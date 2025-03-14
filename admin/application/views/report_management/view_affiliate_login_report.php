<div class="panel panel-primary hidden">
    <div class="panel-heading">
        <h4 class="panel-title">
            <i class="fa fa-search"></i> <?=lang("lang.search")?>
            <span class="pull-right">
                <a data-toggle="collapse" href="#viewAffiliateLoginReport" class="btn btn-xs btn-primary <?=$this->config->item('default_open_search_panel') ? '' : 'collapsed'?>"></a>
            </span>
        </h4>
    </div>
    <div id="viewAffiliateLoginReport" class="panel-collapse <?=$this->config->item('default_open_search_panel') ? '' : 'collapse in'?>">
        <div class="panel-body">
            <form id="search-form" action="<?= site_url('/affiliate_management/viewAffiliateLoginReport'); ?>" method="get">
                <div class="row">
                    <!-- Date/Time -->
                    <div class="form-group col-md-3 col-lg-3">
                        <label class="control-label">
                            <?= lang('Date'); ?>:
                        </label>
                        <input id="search_payment_date" class="form-control input-sm dateInput user-success" data-start="#by_date_from" data-end="#by_date_to" data-time="true" autocomplete="off" />
                        <input type="hidden" id="by_date_from" name="by_date_from" value="<?=$conditions['by_date_from'];?>" />
                        <input type="hidden" id="by_date_to" name="by_date_to" value="<?=$conditions['by_date_to'];?>" />
                    </div>
                    <!-- Username -->
                    <div class="form-group col-md-3 col-lg-3">
                        <label class="control-label">
                            <?=lang('Username'); ?>
                        </label>
                        <input type="text" name="by_username" id="by_username" value="<?= $conditions['by_username']; ?>" class="form-control input-sm group-reset" />
                    </div>
                    <!-- Login IP -->
                    <div class="form-group col-md-3 col-lg-3">
                        <label class="control-label"><?=lang('player_login_report.login_ip');?></label>
                        <input type="text" name="login_ip" id="login_ip" class="form-control input-sm" value="<?php echo $conditions['login_ip']; ?>">
                        <?php echo form_error('login_ip', '<span class="help-block" style="color:#ff6666;">', '</span>'); ?>
                    </div>
                </div>

                <div class="row">
                    <div class="form-group col-md-2 col-md-offset-10">
                        <div class="pull-right">
                            <input type="button" id="btnResetFields" value="<?=lang('lang.clear'); ?>" class="btn btn-sm btn-linkwater">
                            <button type="submit" class="btn btn-sm btn-portage"><?=lang("lang.search")?></button>
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
            <?=lang("Affiliate Login Report")?>
        </h4>
    </div>
    <div class="panel-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover table-condensed" id="result_table">
                <thead>
                    <tr>
                        <th style="min-width:110px;"><?=lang("Date")?></th>
                        <th><?=lang("Affiliate Username")?></th>
                        <th><?=lang("player_login_report.login_ip")?></th>
                        <th><?=lang("player_login_report.referrer")?></th>
                    </tr>
                </thead>
                <tbody></tbody>
                <tfoot>
                    <tr></tr>
                </tfoot>
            </table>
        </div>
    </div>
    <div class="panel-footer"></div>
</div>

<script type="text/javascript">
    $(document).ready(function(){
        var dataTable = $('#result_table').DataTable({
            // ...existing code...
            dom: "<'panel-body'<'pull-right'B><'pull-right progress-container hidden-xs'>l><'dt-information-summary1 text-info pull-left' i>t<'panel-body'<'pull-right'p><'dt-information-summary2 text-info pull-left' i>>",
            autoWidth: false,
            searching: false,
            buttons: [
                {
                    extend: 'colvis',
                    postfixButtons: [ 'colvisRestore' ],
                    className: 'btn-linkwater',
                }
                // ...existing code...
            ],
            columnDefs: [
                { visible: false, targets: [] },
            ],
            order: [ 0, 'desc' ],
            processing: true,
            serverSide: true,
            ajax: function (data, callback, settings) {
                data.extra_search = $('#search-form').serializeArray();
                $.post(base_url + "api/affiliateLoginReport", data, function(data) {
                    callback(data);
                    if ( dataTable.rows( { selected: true } ).indexes().length === 0 ) {
                        dataTable.buttons().disable();
                    }
                    else {
                        dataTable.buttons().enable();
                    }
                }, 'json');
            }
        });

        dataTable.on( 'draw', function (e, settings) {
            // ...existing code...
        });

        $('#btnResetFields').click(function() {
            $('.group-reset').val('');
            $('.dateInput').data('daterangepicker').setStartDate(moment().startOf('day').format('Y-MM-DD HH:mm:ss'));
            $('.dateInput').data('daterangepicker').setEndDate(moment().endOf('day').format('Y-MM-DD HH:mm:ss'));
            dateInputAssignToStartAndEnd($('#search_withdrawal_date'));
        });
    });
</script>
