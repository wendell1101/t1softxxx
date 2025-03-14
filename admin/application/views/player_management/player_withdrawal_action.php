<?php $playerId = $player['playerId']; ?>

<style>
.flex	{
    display: flex; 
}
.justify-start {
    justify-content: flex-start;
}
.justify-center {
    justify-content: center;
}
.flex-col {
    flex-direction: column;
}
.flex-row-reverse {
    flex-direction: row-reverse;
}
.text-left {
    text-align: left;
}
.space-x-4 {
    margin-left: 1rem; /* 16px */
}
.p-4 {
    padding: 1rem; /* 16px */
}
.no-wrap {
    white-space: nowrap;
}
</style>

<div class="panel panel-primary">
    <div class="flex text-center justify-center p-4">
        <form action="/player_management/set_withdrawal_status_by_options/<?= $playerId ?>/update_withdrawal_status/<?= $action_status ?>/<?= $transmission ?>" method="post">
            <?php if($action_status == 'disable') { ?>
                <p><?= sprintf(lang('system.message.disable_player_withdrawal'), strtoupper($player['username'])) ?></p>
                <div class="flex flex-col text-left">
                    <div class="flex text-center justify-start">
                        <input type="radio" id="unlimited_disable" name="disable_player_withdrawal" value="unlimited_disable" onclick="radioHandler('unlimited_disable')" checked>
                        <label class="space-x-4" for="unlimited_disable"><?= lang('Unlimited Disable') ?></label>
                    </div>
                    <div class="flex text-center justify-start">
                        <input type="radio" id="disable_until" name="disable_player_withdrawal" value="disable_until" onclick="radioHandler('disable_until')">
                        <label class="space-x-4 no-wrap" for="disable_until">
                            <?= lang('Disable Until') ?>
                        </label>
                        <input type="text" class="form-control input-sm dateInput inline space-x-4" id="disable_until_datetime" name="disable_until_datetime" value="" disabled/>
                    </div>
                </div>
                <br>

                <button class="btn btn-danger" id="submit-button">
                    <i class="fa fa-thumbs-down"></i> <?= lang('Disable Player Withdrawal') ?>
                </button>
            <?php }else{ ?>
                <div class="flex flex-col text-left justify-start">
                    <p>
                        <?php if(!empty($player['disabled_withdrawal_until'])) { ?>
                            <?= sprintf(lang('system.message.disable_withdraw_until'), strtoupper($player['username']), $player['disabled_withdrawal_until']) ?>
                        <?php }else{ ?>
                            <?= sprintf(lang('system.message.unlimited_disabled_withdraw'), strtoupper($player['username'])) ?>
                        <?php } ?>
                    </p>
                </div>
                <br><br>
                
                <button class="btn btn-scooter">
                    <i class="fa fa-thumbs-up"></i> <?= lang('Enable Player Withdrawal') ?>
                </button>
            <?php } ?>
            <div class="clearfix"></div>
        </form>
    </div>
</div>

<script type="text/javascript">
$(document).ready(function() {
    let playerId = '<?= $playerId ?>';
    let current_php_datetime = '<?= $current_php_datetime ?>';
    let startDate = moment(current_php_datetime).add(24, 'hours');
    let minDate = moment(current_php_datetime);
    let disableUntilDatetime = $('#disable_until_datetime');
    let submitButton = $('#submit-button');
    let unlimitedDisable = 'unlimited_disable';
    
    disableUntilDatetime.daterangepicker({
        "autoApply": true,
        "singleDatePicker": true,
        "showDropdowns": true,
        "alwaysShowCalendars": true,
        "timePicker": true,
        "timePicker24Hour": true,
        "timePickerSeconds": true,
        "startDate": startDate,
        "minDate": minDate,
        "applyClass": "btn-primary",
        "ranges": {
            "1 <?= lang('Day') ?>": [moment(current_php_datetime).add(24, 'hours'), moment(current_php_datetime).add(24, 'hours')],
            "7 <?= lang('Days') ?>": [moment(current_php_datetime).add(7*24, 'hours'), moment(current_php_datetime).add(7*24, 'hours')],
            "30 <?= lang('Days') ?>": [moment(current_php_datetime).add(30*24, 'hours'), moment(current_php_datetime).add(30*24, 'hours')],
            "365 <?= lang('Days') ?>": [moment(current_php_datetime).add(365*24, 'hours'), moment(current_php_datetime).add(365*24, 'hours')],
        },
        "locale": {
            "separator": "<?= lang('player.12') ?>",
            "applyLabel": "<?= lang('lang.apply') ?>",
            "cancelLabel": "<?= lang('lang.cancel') ?>",
            "daysOfWeek": ["<?= lang('Sun') ?>",
                            "<?= lang('Mon') ?>",
                            "<?= lang('Tue') ?>",
                            "<?= lang('Wed') ?>",
                            "<?= lang('Thu') ?>",
                            "<?= lang('Fri') ?>",
                            "<?= lang('Sat') ?>"
                        ],
            "monthNames": ["<?= lang('January') ?>",
                            "<?= lang('February') ?>",
                            "<?= lang('March') ?>",
                            "<?= lang('April') ?>",
                            "<?= lang('May') ?>",
                            "<?= lang('June') ?>",
                            "<?= lang('July') ?>",
                            "<?= lang('August') ?>",
                            "<?= lang('September') ?>",
                            "<?= lang('October') ?>",
                            "<?= lang('November') ?>",
                            "<?= lang('December') ?>"
                        ],
            "firstDay": 0,
            "format": "YYYY-MM-DD HH:mm:ss",
            "customRangeLabel": "<?= lang('lang.custom') ?>"
        },
    }, function (start, end, label) {
        console.log('New date selected: ' + start.format('YYYY-MM-DD HH:mm:ss') + ' (predefined range: ' + label + ')');
    });

    disableUntilDatetime.on('apply.daterangepicker', function(ev, picker) {
        if(moment(picker.startDate.format('YYYY-MM-DD HH:mm:ss')) <= moment(current_php_datetime)) {
            alert("<?= lang('Invalid Datetime!') ?> " + picker.startDate.format('YYYY-MM-DD HH:mm:ss'));
            submitButton.prop('disabled', true);
        }else{
            submitButton.removeAttr('disabled');
        }

        //console.log(picker.startDate.format('YYYY-MM-DD HH:mm:ss'));
    });

    radioHandler = function(action) {
        if(action == unlimitedDisable) {
            disableUntilDatetime.prop('disabled', true);
            submitButton.removeAttr('disabled');
            $('div.daterangepicker.dropdown-menu.ltr.single.show-calendar.opensright').removeClass('flex-row-reverse');
        }else{
            disableUntilDatetime.removeAttr('disabled');
            disableUntilDatetime.data('daterangepicker').show();
            $('div.daterangepicker.dropdown-menu.ltr.single.show-calendar.opensright').addClass('flex-row-reverse');
            $('div.daterangepicker.dropdown-menu.ltr.single.show-calendar.opensright').css('display','flex');

            if(moment(disableUntilDatetime.val()) <= moment(current_php_datetime)) {
                submitButton.prop('disabled', true);
            }
        }
    }
});
</script>