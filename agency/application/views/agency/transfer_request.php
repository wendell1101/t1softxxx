<?php
/**
 *   filename:   credit_transactions.php
 *   date:       2016-05-02
 *   @brief:     view credit transactions for agency sub-system
 */

// set display according to configurations
$panelOpenOrNot = $this->config->item('default_open_search_panel') ? '' : 'collapsed';
$panelDisplayMode = $this->config->item('default_open_search_panel') ? '' : 'collapse in';
if (isset($_GET['search_on_date'])) {
	$search_on_date = $_GET['search_on_date'];
} else {
	$search_on_date = true;
}
?>
<div class="content-container">
    <!-- search form {{{1 -->
    <form class="form-horizontal" id="search-form">
        <input type="hidden" name="parent_id" value="<?php echo $parent_id?>"/>
        <input type="hidden" name="agent_id" value="<?php echo $agent_id?>"/>
        <input type="hidden" name="parent_name" value="<?php echo $parent_name?>"/>
        <div class="panel panel-primary">
            <!-- panel heading {{{2 -->
            <div class="panel-heading">
                <h4 class="panel-title">
                    <i class="fa fa-search"></i>
                    <?=lang("lang.search")?>
                    <span class="pull-right">
                        <a data-toggle="collapse" href="#collapseAgentList"
                            class="btn btn-info btn-xs <?=$panelOpenOrNot?>">
                        </a>
                    </span>
                </h4>
            </div>
            <!-- panel heading }}}2 -->

            <div id="collapseAgentList" class="panel-collapse <?=$panelDisplayMode?>">
                <!-- panel body {{{2 -->
                <div class="panel-body">
                    <div class="row">

                        <div class="col-md-4">
                            <label class="control-label"><?=lang('report.sum02')?></label>
                            <div class="input-group">
                                <input class="form-control input-sm dateInput" id="search-date" data-start="#date_from" data-end="#date_to" data-time="true"/>
                                <input type="hidden" id="date_from" name="date_from" value="<?=@$date_from?>"/>
                                <input type="hidden" id="date_to" name="date_to" value="<?=@$date_to?>"/>
                                <span class="input-group-addon input-sm">
                                    <input type="checkbox" name="search_reg_date" id="search_reg_date" value="1"
                                    <?php if ($conditions['search_reg_date']) {echo 'checked';} ?>/>
                                </span>
                            </div>
                        </div>


                        <div class="col-md-2">
                            <label class="control-label" for="status"><?=lang('Status')?></label>
                            <select id="status" name="status"  class="form-control input-sm">
                                <option value=""><?=lang('All');?></option>
                                <option value="<?php echo Wallet_model::STATUS_TRANSFER_SUCCESS;?>" <?php echo ($conditions['status']==Wallet_model::STATUS_TRANSFER_SUCCESS) ? 'selected' : ''?>><?=lang('Successful');?> </option>
                                <option value="<?php echo Wallet_model::STATUS_TRANSFER_FAILED;?>" <?php echo ($conditions['status']==Wallet_model::STATUS_TRANSFER_FAILED) ? 'selected' : ''?>><?=lang('Failed');?> </option>
                                <option value="<?php echo Wallet_model::STATUS_TRANSFER_REQUEST;?>" <?php echo ($conditions['status']==Wallet_model::STATUS_TRANSFER_REQUEST) ? 'selected' : ''?>><?=lang('Request');?> </option>
                            </select>
                        </div>

                        <div class="col-md-2">
                            <label class="control-label"><?=lang('Player Username');?></label>
                            <input type="text" name="player_username" class="form-control input-sm"
                            placeholder=' <?=lang('Enter Player Username');?>' value="<?=@$player_username?>"/>
                        </div>

                        <div class="col-md-2">
                            <label class="control-label" for="timezone"><?=lang('Timezone')?></label>
                            <select id="timezone" name="timezone"  class="form-control input-sm">
                                <?php for($i = 12;  $i >= -12; $i--): ?>
                                    <option value="<?php echo $i > 0 ? "+{$i}" : $i ;?>" <?php echo ($i == $conditions['timezone']) ? 'selected' : ''?>> <?php echo $i > 0 ? "+{$i}" : $i ;?>:00</option>
                                <?php endfor;?>
                            </select>
                        </div>

                        <div class="col-md-2">
                            <label class="control-label"><?=lang('Agent Username');?></label>
                            <input type="text" name="agent_name" class="form-control input-sm"
                            placeholder=' <?=lang('Enter Agent Username');?>' value="<?=@$agent_username?>"/>
                        </div>

                        <div class="col-md-2">
                            <label class="control-label"><?=lang('Amount')?> >=</label>
                            <input type="number" name="amount_from" class="form-control input-sm user-success" min="0" placeholder=" Enter Minimum Transfer Amount" value="">
                        </div>

                        <div class="col-md-2">
                            <label class="control-label"><?=lang('Amount')?> <=</label>
                            <input type="number" name="amount_to" class="form-control input-sm user-success" min="0" placeholder=" Enter Maximum Transfer Amount" value="">
                        </div>

                        <div class="col-md-2">
                            <label class="control-label" for="by_game_platform_id"><?=lang('Game Platform')?></label>
                            <select id="by_game_platform_id" name="by_game_platform_id"  class="form-control input-sm">
                                <option value="" ><?=lang('All');?></option>
                                <?php foreach ($game_platforms as $game_platform) {?>
                                    <option value="<?=$game_platform['id']?>" <?php echo $conditions['by_game_platform_id']==$game_platform['id'] ? 'selected="selected"' : '' ; ?>><?=$game_platform['system_code'];?></option>
                                <?php }?>
                            </select>
                        </div>

                        <div class="col-md-2">
                            <label class="control-label" for="secure_id"><?=lang('ID')?></label>
                            <input id="secure_id" type="text" name="secure_id" class="form-control input-sm" value="<?=$conditions['secure_id']?>"/>
                        </div>
                        <div class="col-md-2">
                            <label class="control-label" for="result_id"><?=lang('Response Result ID')?></label>
                            <input id="result_id" type="text" name="result_id" class="form-control input-sm" value="<?=$conditions['result_id']?>"/>
                        </div>
                        <div class="col-md-2">
                            <label class="control-label" for="suspicious_trans"><?=lang('Suspicious Trans')?>
                            </label>
                            <select id="suspicious_trans" name="suspicious_trans" class="form-control input-sm">
                                <option value=""><?=lang('None');?></option>
                                <option value="<?=Wallet_model::SUSPICIOUS_TRANSFER_IN_ONLY?>"><?=lang('Transfer In Only');?></option>
                                <option value="<?=Wallet_model::SUSPICIOUS_TRANSFER_OUT_ONLY?>"><?=lang('Transfer Out Only');?></option>
                                <option value="<?=Wallet_model::SUSPICIOUS_ALL?>"><?=lang('All');?></option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="control-label" for="transfer_type"><?=lang('Transfer Type')?>
                            </label>
                            <select id="transfer_type" name="transfer_type" class="form-control input-sm">
                                <option value=""><?=lang('All');?></option>
                                <option value="<?=Wallet_model::TRANSFER_TYPE_IN?>"><?=lang('Transfer In');?></option>
                                <option value="<?=Wallet_model::TRANSFER_TYPE_OUT?>"><?=lang('Transfer Out');?></option>
                            </select>
                        </div>


                    </div><!-- button row }}}3 -->






                    <div class="row">
                        <div class="col-md-offset-2 col-md-8" style="margin-top: 10px;">
                            <div class="pull-right">
                                <input type="button" class="btn btn-sm btn-info btn_today" value="<?php echo lang('Today');?>">
                                <input type="button" class="btn btn-sm btn-info btn_yesterday" value="<?php echo lang('Yesterday');?>">
                                <input type="button" class="btn btn-sm btn-info btn_this_week" value="<?php echo lang('This Week');?>">
                                <input type="button" class="btn btn-sm btn-info btn_last_week" value="<?php echo lang('Last Week');?>">
                                <input type="button" class="btn btn-sm btn-info btn_this_month" value="<?php echo lang('This Month');?>">
                                <input type="button" class="btn btn-sm btn-info btn_last_month" value="<?php echo lang('Last Month');?>">
                                <input type="button" class="btn btn-sm btn-info btn_this_year" value="<?php echo lang('This Year');?>">
                            </div>

                            <input type="button" value="<?=lang('lang.reset');?>" class="btn btn-default btn-sm" onclick="window.location.href='<?php echo site_url('agency/transfer_request'); ?>'">
                            <input class="btn btn-sm btn-primary" type="submit" value="<?=lang('lang.search');?>" />
                        </div>
                    </div> <!-- button row }}}3 -->


                </div>
                <!-- panel body }}}2 -->
            </div>
        </div>
    </form> <!-- end of search form }}}1 -->

    <!-- panel for Credit Transactions table {{{1 -->
    <div class="panel panel-primary">
        <div class="panel-heading">
            <h4 class="panel-title"><i class="fa fa-money"></i> <?=lang('Transfer Request');?></h4>
        </div>
        <div class="panel-body">
            <!-- credit transactions table {{{2 -->
            <div class="table-responsive">
                <table class="table table-striped table-hover table-condensed" id="transfer_request_table">
                    <thead>
                        <tr>
                            <th><?=lang('ID');?></th>
                            <th><?=lang('Player Username');?></th>
                            <th><?=lang('Transfer');?></th>
                            <th><?=lang('Admin Username');?></th>
                            <th><?=lang('Amount');?></th>
                            <th><?=lang('Status');?></th>
                            <th><?=lang('Created At');?></th>
                            <th><?=lang('Updated At');?></th>
                            <th><?=lang('External ID');?></th>
                            <th><?=lang('API ID');?></th>
                            <th><?=lang('Reason');?></th>
                            <th><?=lang('Query Status');?></th>
                            <th><?=lang('Exec Time');?></th>
                            <th><?=lang('Fix Flag');?></th>
                        </tr>
                    </thead>
                </table>
            </div>
            <!--end of credit transactions table }}}2 -->
        </div>
        <div class="panel-footer"></div>
    </div>
    <!-- panel for credit transactions table }}}1 -->
</div>
<!-- JS code {{{1 -->
<script type="text/javascript">
$(document).ready(function(){
    <?php $agent_status = $this->session->userdata('agent_status'); ?>
    <?php if($agent_status == 'suspended') { ?>;
    set_suspended_operations();
    <?php } ?>

    $('.btn_yesterday').click(function(){
        //today
        $from=moment().subtract(1,'days').startOf('day');
        $to=moment().subtract(1,'days').endOf('day');
        submitDateRange($from, $to);
    })

    $('.btn_today').click(function(){
        //today
        $from=moment().startOf('day');
        $to=moment().endOf('day');
        submitDateRange($from, $to);
    })

    $('.btn_last_week').click(function(){
        //this week
        $from=moment().subtract(1,'weeks').startOf('isoWeek');
        $to=moment().subtract(1,'weeks').endOf('isoWeek');

        submitDateRange($from, $to);
    })

    $('.btn_this_week').click(function(){
        //this week
        $from=moment().startOf('isoWeek');
        $to=moment().endOf('day');

        submitDateRange($from, $to);
    })

    $('.btn_last_month').click(function(){
        //this month
        $from=moment().subtract(1,'months').startOf('month');
        $to=moment().subtract(1,'months').endOf('month');

        submitDateRange($from, $to);
    })

    $('.btn_this_month').click(function(){
        //this month
        $from=moment().startOf('month');
        $to=moment().endOf('day');

        submitDateRange($from, $to);
    })

    $('.btn_this_year').click(function(){
        //this year
        $from=moment().startOf('year');
        $to=moment().endOf('day');

        submitDateRange($from, $to);
    })

    $('#search-form').submit( function(e) {
        e.preventDefault();
        dataTable.ajax.reload();
        set_agent_operations();
    });

    $('#search-form input[type="text"]').keypress(function (e) {
        if (e.which == 13) {
            $('#search-form').trigger('submit');
        }
    });

    // DataTable settings {{{2
    var dataTable = $('#transfer_request_table').DataTable({
        lengthMenu: [50, 100, 250, 500, 1000],
        autoWidth: false,
        searching: false,
        dom: "<'panel-body'<'pull-right'B><'pull-right progress-container hidden-xs'>l><'dt-information-summary1 text-info pull-left' i>t<'panel-body'<'pull-right'p><'dt-information-summary2 text-info pull-left' i>>",
        buttons: [
            { text: '<?php echo lang("Column visibility"); ?>',extend: 'colvis', postfixButtons: [ 'colvisRestore' ] }
        ],
        columnDefs: [
            { className: 'text-right', targets: [ 4 ] },
        ],
        order: [ 0, 'desc' ],
        processing: true,
        serverSide: true,
        ajax: function (data, callback, settings) {
            data.extra_search = $('#search-form').serializeArray();
            $.post(base_url + "api/agency_transfer_request", data, function(data) {
                callback(data);
            },'json')
            .fail( function (jqxhr, status_text)  {
                if ( jqxhr.status >= 300 && jqxhr.status < 500 ) {
                    if (confirm('<?= lang('session.timeout') ?>')) {
                        window.location.href = '/';
                    }
                }
                else {
                    alert(status_text);
                }
            });
        },
    });
    // DataTable settings }}}2

});

function submitDateRange($from, $to){
    $('#date_from').val($from.format('YYYY-MM-DD HH:mm:ss'));
    $('#date_to').val($to.format('YYYY-MM-DD HH:mm:ss'));
    var dateInput=$('#search-date');

    dateInput.data('daterangepicker').setStartDate($from);
    dateInput.data('daterangepicker').setEndDate($to);

    $('#search-form').submit();
}
</script>
<!-- JS code }}}1 -->

<?php
// zR to open all folded lines
// vim:ft=php:fdm=marker
// end of credit_transactions.php
