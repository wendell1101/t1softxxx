<div class="mmenu">
    <div class="mm_header">
        <ul>
            <li class="current"><?=lang('lang.select')?></li>
        </ul>
        <div class="mm_exit"></div>
    </div>
    <div class="mm_main">
        <ul class="acmenu nav" style="display: none;">
            <?php include __DIR__ . '/../../report/reports/tabs.php';?>
        </ul>
        <ul class="range" style="display: none;">
            <input type="text" class="form-control sdate" name="sdate" id="sdate" value="<?=date('Y-m-d 00:00:00', time())?>" data-format="yyyy-MM-dd HH:mm:ss" data-startend="start">
            <input type="text" class="form-control edate" name="edate" id="edate" value="<?=date('Y-m-d 23:59:59', time())?>" data-format="yyyy-MM-dd HH:mm:ss" data-startend="end">
            <input type="hidden" class="form-control sdatetime" name="time1" value="<?=date('Y-m-d 00:00:00', time())?>" data-format="yyyy-MM-dd HH:mm:ss" data-startend="start">
            <input type="hidden" class="form-control edatetime" name="time2" value="<?=date('Y-m-d 23:59:59', time())?>" data-format="yyyy-MM-dd HH:mm:ss" data-startend="end">
            <div class="text-center">
                <div class="col-xs-11 col-sm-11">
                    <a class="btn btn-default btn-datetime-setup"><?=lang('Confirm')?></a>
                </div>
            </div>
        </ul>
    </div>
</div>
<div class="report panel">
    <div class="row">
        <div class="report_nav jl_nav">
            <div id="range" class="col pull-left xlmenu">
                <ul>
                    <li id="rangetext">
                        <?=lang('Date Range')?>
                    </li>
                </ul>

            </div>
            <div id="acmenu" class="col pull-right xlmenu">
                <ul>
                    <li id="jltext"><?=lang('deposit.records')?></li>
                </ul>
            </div>
        </div>
        <div class="clearfix"></div>
        <div class="text-center">
            <span class="datetime-show"></span>
        </div>
    </div>
    <!-- Main Content -->
    <div class="report-filter">
        <div class="filter-game input">
            <div class="row">
                <label for="pageLength"><?=lang('datatable.page_length')?>:</label>
                <select id="pageLength" name="pageLength">
                    <option value="5" selected="selected">5</option>
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
            </div>
            <div class="row">
                <label for="game_platform_id"><?=lang('sys.gd7')?>:</label>
                <select id="game_platform_id">
                    <option value=""><?=lang('system.word57')?></option>
                    <?php foreach ($game_platforms as $key => $value):?>
                        <?php
                            if(isset($game_wallet_settings[$key]['enabled_on_mobile']) && !$game_wallet_settings[$key]['enabled_on_mobile']){
                                continue;
                            }
                        ?>
                        <option value="<?=$key?>"><?=$value?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php if($this->utils->getConfig('eanble_display_player_total_bet_amount_in_game_history')) : ?>
                <div class="row total-bet">
                    <span class="player-total-bet-amt"><?=lang('Total Bet Amt') . '：' . $totalBettingAmount;?></span>
                    <br/>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <div class="filter-seach-btn input">
        <button id="search_btn" class="btn-submit ahsearch_btn input_01" onclick="run();"><?= lang('lang.search'); ?></button>
    </div>
    <div class="tab-content">
        <div class="clearfix"></div>
        <?php include __DIR__ . '/../../report/reports/cashback.php' ?>
        <?php if($this->utils->getConfig('enabledCreditMode') && $player_credit_mode) : ?>
        <?php include __DIR__ . '/../../report/reports/creditmode.php' ?>
        <?php endif; ?>
        <?php include __DIR__ . '/../../report/reports/deposit.php' ?>
        <?php include __DIR__ . '/../../report/reports/withdrawal.php' ?>
        <?php if(!$this->utils->getConfig('seamless_main_wallet_reference_enabled')) : ?>
        <?php include __DIR__ . '/../../report/reports/transfer.php' ?>
        <?php endif; ?>
        <?php include __DIR__ . '/../../report/reports/promotion.php' ?>
        <?php include __DIR__ . '/../../report/reports/game.php' ?>
        <?php if($this->utils->getConfig('enable_account_history_unsettled_game')) : ?>
            <?php include __DIR__ . '/../../report/reports/unsettled_game.php' ?>
        <?php endif; ?>
        <?php
        if ($this->utils->getConfig('enable_transaction_report_in_player_center')) {
            include __DIR__ . '/../../report/reports/transaction.php';
        }
        ?>
        <?php include __DIR__ . '/../../report/reports/referral.php' ?>
        <?php if($this->utils->getConfig('enable_account_history_shop_history')) : ?>
        <?php include __DIR__ . '/../../report/reports/shop_history.php' ?>
        <?php endif; ?>
    </div>
</div>

<?=$this->load->view('resources/third_party/mdDateTimePicker');?>
<?=$this->load->view('resources/third_party/DataTable');?>
<script type="text/javascript">
var xlmenu = ".mmenu";
var xlmovie = "mmenu_movie";

var report_type = "<?=isset($report_type) ? $report_type : $this->utils->getConfig('default_player_center_account_history_tab'); ?>";
var enabled_player_cancel_pending_withdraw = '<?=$this->utils->getConfig('enabled_player_cancel_pending_withdraw')?>';
$(document).ready(function () {
    $(xlmenu).hide();
    $(".xlmenu").click(function () {
        $(xlmenu).show();

        $('.mm_main').find('ul').hide();

        $('.' + $(this).attr('id')).show();

        $(xlmenu).addClass(xlmovie);

        $(".mm_exit").click(function () {
            $(xlmenu).hide();
            $(xlmenu).removeClass(xlmovie);
        });
    });

    $(".mm_main .acmenu li a").off("click").click(function () {
        $(".mm_main .acmenu li .border").removeClass("border");
        $(this).addClass("border");

        $(".mm_exit").trigger('click');

        mmenu = $(this).attr("id");

        report_type = $(this).data('report_type');
        $(this).tab('show');

        var title = $(this).text();
        $('#jltext').html(title);
        $('.header_text').html(title);

        if (enabled_player_cancel_pending_withdraw) {
            $('#sdate').val("<?=date('Y-m-d 00:00:00', time())?>");
            $('#edate').val("<?=date('Y-m-d 23:59:59', time())?>");
            if (report_type == "withdrawal") {
                $('#sdate').val("<?=$datelimit_start->format('Y-m-d 00:00:00')?>");
            }
        }

        run();
    });

    $('.filter-select-game #game_platform_id').off('change').on('change', function(){
        run();
    });

    $('#pageLength').on('change', function(){
        run();
    });

    $(".mm_main li a[data-report_type=" + report_type + "]").trigger('click');

    var STRING_OK = "<?= lang('OK') ?>";
    var STRING_CANCEL = "<?= lang('CANCEL') ?>";

    var sdate = new mdDateTimePicker.default({
        type: 'date',
        past: moment().add(-30, 'days'),
        future: moment(),
        ok: STRING_OK,
        cancel: STRING_CANCEL
    });
    var edate = new mdDateTimePicker.default({
        type: 'date',
        past: moment().add(-30, 'days'),
        future: moment(),
        ok: STRING_OK,
        cancel: STRING_CANCEL
    });

    var sdatetime = new mdDateTimePicker.default({
        type: 'time',
        ok: STRING_OK,
        cancel: STRING_CANCEL
    });

    var edatetime = new mdDateTimePicker.default({
        type: 'time',
        ok: STRING_OK,
        cancel: STRING_CANCEL
    });

    $(".sdate").click(function(){
        $('body').append($('<div class="md-datepicker-backdrop">'));
        $(".sdate").blur();
        sdate.toggle();
    });

    $(".edate").click(function(){
        $('body').append($('<div class="md-datepicker-backdrop">'));
        $(".edate").blur();
        edate.toggle();
    });

    sdate.trigger = $(".sdate")[0];
    edate.trigger = $(".edate")[0];
    sdatetime.trigger = $(".sdatetime")[0];
    edatetime.trigger = $(".edatetime")[0];

    $(".sdate").on('onOk', function () {
        var _sdate = moment(sdate.time.toString()).format('YYYY-MM-DD');
        var _sdatetime = _sdate + ' ' + moment(sdatetime.time.toString()).format('HH:mm:ss');
        this.value = _sdatetime;
        sdatetime.toggle();
    });

    $(".edate").on('onOk', function () {
        var _edate = moment(edate.time.toString()).format('YYYY-MM-DD');
        var _edatetime = _edate + ' ' + moment(edatetime.time.toString()).format('HH:mm:ss');
        this.value = _edatetime;
        edatetime.toggle();
    });

    $(".sdatetime").on('onOk', function () {
        var _sdate = moment(sdate.time.toString()).format('YYYY-MM-DD');
        var _sdatetime = _sdate + ' ' + moment(sdatetime.time.toString()).format('HH:mm:ss');
        $(".sdate").val(_sdatetime);
        $('body').find('.md-datepicker-backdrop').remove();
    });

    $(".edatetime").on('onOk', function () {
        var _edate = moment(edate.time.toString()).format('YYYY-MM-DD');
        var _edatetime = _edate + ' ' + moment(edatetime.time.toString()).format('HH:mm:ss');
        $(".edate").val(_edatetime);
        $('body').find('.md-datepicker-backdrop').remove();
    });

    $(".sdate").on('onCancel', function() {
        $('body').find('.md-datepicker-backdrop').remove();
    });

    $(".edate").on('onCancel', function() {
        $('body').find('.md-datepicker-backdrop').remove();
    });

    $(".sdatetime").on('onCancel', function() {
        $('body').find('.md-datepicker-backdrop').remove();
    });

    $(".edatetime").on('onCancel', function() {
        $('body').find('.md-datepicker-backdrop').remove();
    });

    $('.btn-datetime-setup').off('click').on('click', function(){
        // $(".mm_exit").trigger('click');
        // run();
        if(run() != false) {

            $(".mm_exit").trigger('click');

        }
    });
});

function run(){
    var from = $('#sdate').val();
    var to = $('#edate').val();
    $('.datetime-show').html(from + ' <?=lang('To')?> ' + to);

    $('.report-filter>div').hide();
    $('.report-filter .filter-' + report_type).show();

    switch(report_type){
        case "rebate":
            rebateHistory();
            break;
        case "credit_mode":
            creditmodeRequestHistory();
            break;
        case "deposit":
            depositHistory();
            break;
        case "withdrawal":
            withdrawalHistory();
            break;
        case "transfer_request":
            transferRequestHistory();
            break;
        case "promoHistory":
            promoHistory();
            break;
        case "unsettledGame":
            <?php if ($this->utils->getConfig('player_game_history_date_range_restriction')): ?>
                var dateInput = $('#cdate.dateInput');

                var $restricted_range = "<?=$this->utils->getConfig('player_game_history_date_range_restriction')?>";

                if ($restricted_range == '' && !$.isNumeric($restricted_range) && !isRange)
                    return false;

                var a_day = 86400000;
                var restriction = a_day * $restricted_range;
                var start_date = new Date($('#sdate').val());
                var end_date = new Date($('#edate').val());
                var restrict_range_label = "<?=sprintf(lang("restrict_date_range_label"), $this->utils->getConfig('player_game_history_date_range_restriction'))?>";

                if(((end_date - start_date) >= restriction)){

                    if(restrict_range_label && $.trim(restrict_range_label) !== ""){
                        alert(restrict_range_label);
                    }else{
                        var day_label = 'day';

                        if($restricted_range > 1) day_label = 'days'

                        alert('Please choose a date range not greater than '+ $restricted_range +' '+ day_label);
                    }
                }
                else{
                    unsettledGameHistory();
                }
            <?php else:?>
                unsettledGameHistory();
            <?php endif;?>
            break;
        case "game":
            <?php if ($this->utils->getConfig('player_game_history_date_range_restriction')): ?>
                var $restricted_range = "<?=$this->utils->getConfig('player_game_history_date_range_restriction')?>";
                if ($restricted_range == '' && !$.isNumeric($restricted_range) && !isRange)
                    return false;

                var a_day = 86400000;
                var restriction = a_day * $restricted_range;
                var start_date = new Date($('#sdate').val());
                var end_date = new Date($('#edate').val());
                var restrict_range_label = "<?=sprintf(lang("restrict_date_range_label"), $this->utils->getConfig('player_game_history_date_range_restriction'))?>";

                if(((end_date - start_date) >= restriction)){

                    if(restrict_range_label && $.trim(restrict_range_label) !== ""){
                        alert(restrict_range_label);
                    }else{
                        var day_label = 'day';

                        if($restricted_range > 1) day_label = 'days'

                        alert('Please choose a date range not greater than '+ $restricted_range +' '+ day_label);
                    }
                    return false;
                }
                else{
                    gameHistory();
                }
            <?php else:?>
                gameHistory();
            <?php endif;?>
            break;
        case "transaction":
            transactionHistory();
            break;
        case "referralFriend":
            referralFriend();
            break;
        case "shop":
            shopHistory();
            break;


        default:
            depositHistory();
            break;
    }
}
</script>