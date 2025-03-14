<?php $playerId = $player['playerId']; ?>
<?php if($this->utils->isEnabledFeature('add_suspended_status')):?>
    <div class="panel panel-primary">
        <div class="panel-footer">
            <form name="blockPlayerForm" action="#" method="post">
                <center>
                    <div class="form-group">
                        <p><?=$lang_block_prompt ?></p>
                    </div>
                    <div class="form-group">
                        <p>
                            <?= lang('system.word99') ?> :
                            <select name="optBlockingReason" id="optBlockingReason" data-flg="x" required>
                                <option value=""><?=lang('Select block reason')?></option>
                                <?php if(!empty($tags)) : ?>
                                    <?php foreach ($tags as $key => $value) : ?>
                                        <option value="<?=$value['tagId']?>" data-description="<?=$value['tagDescription']?>" ><?=$value['tagName']?></option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </p>
                        <h4><strong><span class="glyphicon glyphicon-info-sign"></span> <span id="spnTagDescription"></span></strong></h4>
                    </div>
                    <div class="form-group block_player_account_until-group hide">
                        <div class="flex flex-col text-left">
                            <div class="flex text-center justify-start">
                                <input type="radio" id="unlimited_disable" name="block_player_account" value="unlimited_disable" checked>
                                <label class="space-x-4" for="unlimited_disable"><?= lang('Unlimited Disable') ?></label>
                            </div>
                            <div class="flex text-center justify-start">
                                <input type="radio" id="disable_until" name="block_player_account" value="block_until">
                                <label class="space-x-4 no-wrap" for="disable_until">
                                    <?= lang('Disable Until') ?>
                                </label>
                                <input type="text" class="form-control-del input-sm dateInput inline space-x-4" id="disable_block_until_datetime" name="disable_block_until_datetime" value="" disabled/>
                            </div>
                        </div>
                    </div>
                    <br>

                    <button class="btn btn-danger" type="button" id="iBlock">
                        <span class="glyphicon glyphicon-lock"></span> <?=lang('Block')?>
                    </button>
                    <button class="btn btn-danger" type="button" id="iSuspend">
                        <span class="glyphicon glyphicon-lock"></span> <?=lang('Suspended')?>
                    </button>
                    <div class="clearfix"></div>
                </center>
            </form>
        </div>
    </div>
<?php else: // for if($this->utils->isEnabledFeature('add_suspended_status'))... ?>
    <div class="panel panel-primary">
        <div class="panel-footer">
            <form name="blockPlayerForm" action="/player_management/blockPlayer/<?=$playerId?>" method="post">
                <center>
                    <div class="form-group">
                        <p><?=$lang_block_prompt ?></p>
                    </div>
                    <div class="form-group">
                        <?php if(!empty($tags)) : ?>
                            <p>
                                <?= lang('system.word99') ?> :
                                <select name="optBlockingReason" id="optBlockingReason" data-flg="x" required>
                                    <option value=""><?=lang('Select block reason')?></option>
                                    <?php foreach ($tags as $key => $value) : ?>
                                        <option value="<?=$value['tagId']?>" data-description="<?=$value['tagDescription']?>" ><?=$value['tagName']?></option>
                                    <?php endforeach; ?>
                                </select>
                            </p>
                            <h4><strong><span class="glyphicon glyphicon-info-sign"></span> <span id="spnTagDescription"></span></strong></h4>
                        <?php endif; ?>
                    </div>

                    <div class="form-group block_player_account_until-group hide">
                        <div class="flex flex-col text-left">
                            <div class="flex text-center justify-start">
                                <input type="radio" id="unlimited_disable" name="block_player_account" value="unlimited_block" checked>
                                <label class="space-x-4" for="unlimited_disable"><?= lang('Unlimited Disable') ?></label>
                            </div>
                            <div class="flex text-center justify-start">
                                <input type="radio" id="disable_until" name="block_player_account" value="block_until">
                                <label class="space-x-4 no-wrap" for="disable_until">
                                    <?= lang('Disable Until') ?>
                                </label>
                                <input type="text" class="form-control-del input-sm dateInput inline space-x-4" id="disable_block_until_datetime" name="disable_block_until_datetime" value="" disabled/>
                            </div>
                        </div>
                    </div>
                    <br>

                    <button class="btn btn-danger" id="iBlock" type="submit">
                        <span class="glyphicon glyphicon-lock"></span> <?=lang('Block')?>
                    </button>
                    <div class="clearfix"></div>
                </center>
            </form>
        </div>
    </div>
<?php endif; // EOF if($this->utils->isEnabledFeature('add_suspended_status'))... ?>
<script type="text/javascript">

    $('#optBlockingReason').change(function(){
        var description = $(this).find(":selected").data("description");
        $('#spnTagDescription').text(description);
    });

    <?php if($this->utils->isEnabledFeature('add_suspended_status') && false): // move to blockPlayerForm.clicked_iBlock() ?>
        var playerId =<?=$playerId?>;

        $('#iBlock').click(function(){
            var voptBlockingReason = $('#optBlockingReason').val();
            if(voptBlockingReason == ""){
                alert("<?=lang('Block Reason is required')?>");
                return;
            }

            var _data = {};
            _data.optBlockingReason = voptBlockingReason;
            _data.playerStatus = "Block";
            if(_this.enabled_block_player_account_with_until == 1){
                _data.block_player_account = $('[name="block_player_account"]:checked').val();
                _data.disable_block_until_datetime = $('[name="disable_block_until_datetime"]').val();
            }
            $.ajax({
                'url' : '/player_management/changePlayserStatus/'+playerId+'/true',
                'data': _data,
                'type' : 'POST',
                'cache' : false,
                'dataType' : "json"
            }).done(
                function(data){
                    location.reload();
                }
            );
        });

        $('#iSuspend').click(function(){
            var voptBlockingReason = $('#optBlockingReason').val();
            if(voptBlockingReason == ""){
                alert("<?=lang('Block Reason is required')?>");
                return;
            }

            var _data = {};
            _data.optBlockingReason = voptBlockingReason;
            _data.playerStatus = "Suspended";
            if(_this.enabled_block_player_account_with_until == 1){
                _data.block_player_account = $('[name="block_player_account"]:checked').val();
                _data.disable_block_until_datetime = $('[name="disable_block_until_datetime"]').val();
            }
            $.ajax({
                'url' : '/player_management/changePlayserStatus/'+playerId+'/true',
                'data': _data,
                'type' : 'POST',
                'cache' : false,
                'dataType' : "json"
            }).done(
                function(data){
                    location.reload();
                }
            );
        });
    <?php endif;?>


    var blockPlayerForm = blockPlayerForm||{};

    blockPlayerForm.destruct = function(){
        var _this = this;
    }
    blockPlayerForm.initialize = function (options) {
        var _this = this;

        _this.dbg = false; // work in querystring has "dbg=1".

        // detect dbg for console.log
        var query = window.location.search.substring(1);
        var qs = _this.parse_query_string(query);
        if ('dbg' in qs
            && typeof (qs.dbg) !== 'undefined'
            && qs.dbg
        ) {
            _this.dbg = true;
        }

        _this.enabled_block_player_account_with_until = <?= empty($this->utils->getConfig('enabled_block_player_account_with_until'))? 0:1 ?>;
        _this.add_suspended_status = <?= empty($this->utils->isEnabledFeature('add_suspended_status'))? 0:1 ?>;

        _this.playerId = <?=$playerId?>;

        _this.changePlayserStatusURI = '/player_management/changePlayserStatus/'+ _this.playerId+ '/true';


        return _this;
    }; // EOF initialize()

    blockPlayerForm.onReady = function(){
        var _this = this;

console.log('blockPlayerForm in onReady');
        $('[name="blockPlayerForm"]').removeAttr('action').removeProp('action');


        if(_this.enabled_block_player_account_with_until == 1){
            $('.block_player_account_until-group').removeClass('hide');
        }else{
            $('.block_player_account_until-group').addClass('hide');
        }

        _this.initialDaterangepicker();

        var root$El = $('form[name="blockPlayerForm"]'); // $('body') will be double define events

        root$El.on('change', 'input[name="block_player_account"]',function(e){
            _this.radioHandler(e);
        });

        if(_this.add_suspended_status){
            root$El.on('click', '[id="iBlock"],[id="iSuspend"]',function(e){
                _this.clicked_iBlock(e);
            });
        }else{
            root$El.on('click', '[id="iBlock"]',function(e){
                _this.clicked_iBlock(e);
            });
        }

    }; // EOF onReady()

    blockPlayerForm.initialDaterangepicker = function(){
        var _this = this;
        let startDate = moment().add(24, 'hours');
        if(_this.dbg){
            startDate = moment(); // Test
        }

        var disableUntilDatetime = $('#disable_block_until_datetime'); // disable_until_datetime
        _this.disableUntilDatetime = disableUntilDatetime;

        disableUntilDatetime.daterangepicker({
            "autoApply": true,
            "singleDatePicker": true,
            "showDropdowns": true,
            "alwaysShowCalendars": true,
            "timePicker": true,
            "timePicker24Hour": true,
            "timePickerSeconds": true,
            "startDate": startDate,
            "minDate": moment(),
            "applyClass": "btn-primary",
            "ranges": {
                "1 <?= lang('Day') ?>": [moment().add(24, 'hours'), moment().add(24, 'hours')],
                "7 <?= lang('Days') ?>": [moment().add(7*24, 'hours'), moment().add(7*24, 'hours')],
                "30 <?= lang('Days') ?>": [moment().add(30*24, 'hours'), moment().add(30*24, 'hours')],
                "365 <?= lang('Days') ?>": [moment().add(365*24, 'hours'), moment().add(365*24, 'hours')],
            },
            "opens": "left",
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
            _this.applied_daterangepicker(ev, picker);
        });

    }; // EOF initialDaterangepicker()

    blockPlayerForm.radioHandler = function(e){
        var _this = this;

        var disableUntilDatetime = _this.disableUntilDatetime;

        let unlimitedDisable = 'unlimited_disable';

        let submitButton = $('[id="iBlock"],[id="iSuspend"]');

        var theTarget$El = $(e.target);
        var action = theTarget$El.val();

        if(action == unlimitedDisable) {
            disableUntilDatetime.prop('disabled', true);
            submitButton.removeAttr('disabled');
            // $('div.daterangepicker.dropdown-menu.ltr.single.show-calendar.opensright').removeClass('flex-row-reverse');
        }else{
            disableUntilDatetime.removeAttr('disabled');
            disableUntilDatetime.data('daterangepicker').show();
            // $('div.daterangepicker.dropdown-menu.ltr.single.show-calendar.opensright').addClass('flex-row-reverse');
            // $('div.daterangepicker.dropdown-menu.ltr.single.show-calendar.opensright').css('display','flex');

            if(moment(disableUntilDatetime.val()) <= moment()) {
                submitButton.prop('disabled', true);
            }
        }
    }; // EOF radioHandler()

    blockPlayerForm.applied_daterangepicker = function(e, picker){
        let submitButton = $('[id="iBlock"],[id="iSuspend"]');
        if(moment(picker.startDate.format('YYYY-MM-DD HH:mm:ss')) <= moment()) {
            alert("<?= lang('Invalid Datetime!') ?> " + picker.startDate.format('YYYY-MM-DD HH:mm:ss'));
            submitButton.prop('disabled', true);
        }else{
            submitButton.removeAttr('disabled');
        }
    } // EOF applied_daterangepicker()

    blockPlayerForm.clicked_iBlock = function(e){
        var _this = this;
        var theTarget$El = $(e.target);

        var _playerStatus = '';
        if( theTarget$El.prop('id') == 'iBlock'){
            _playerStatus = "Block";
        }else if( theTarget$El.prop('id') == 'iSuspend'){
            _playerStatus = "Suspended";
        }

        var voptBlockingReason = $('#optBlockingReason').val();
        if(voptBlockingReason == ""){
            alert("<?=lang('Block Reason is required')?>");
            return;
        }

        var _data = {};
        _data.optBlockingReason = voptBlockingReason;
        _data.playerStatus = _playerStatus;
        if(_this.enabled_block_player_account_with_until == 1){
            _data.block_player_account = $('[name="block_player_account"]:checked').val();
            _data.disable_block_until_datetime = $('[name="disable_block_until_datetime"]').val();
        }
        var _ajax = $.ajax({
            'url' : _this.changePlayserStatusURI,
            'data': _data,
            'type' : 'POST',
            'cache' : false,
            'dataType' : "json"
        }).done(
            function(data){
                location.reload();
            }
        );
        return _ajax;
    } // EOF clicked_iBlock()

    /**
     * Cloned from promotionDetails.parse_query_string()
     *
     * @param {*} query
     */
    blockPlayerForm.parse_query_string = function (query) {
        var vars = query.split("&");
        var query_string = {};
        for (var i = 0; i < vars.length; i++) {
            var pair = vars[i].split("=");
            var key = decodeURIComponent(pair[0]);
            var value = decodeURIComponent(pair[1]);
            // If first entry with this name
            if (typeof query_string[key] === "undefined") {
                query_string[key] = decodeURIComponent(value);
                // If second entry with this name
            } else if (typeof query_string[key] === "string") {
                var arr = [query_string[key], decodeURIComponent(value)];
                query_string[key] = arr;
                // If third or later entry with this name
            } else {
                query_string[key].push(decodeURIComponent(value));
            }
        }
        return query_string;
    }; // EOF parse_query_string

    $(document).ready(function() {
        /// moved to admin/public/resources/js/player_management/player_management.js
        // var oBlockPlayerForm = blockPlayerForm.initialize();
        // oBlockPlayerForm.onReady();
    });
</script>