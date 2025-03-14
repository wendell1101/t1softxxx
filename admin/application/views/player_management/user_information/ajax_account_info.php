<div role="tabpanel" class="tab-pane active" id="accountInfo">
    <div class="row">
        <div class="col-md-12">
            <h5 class="currency-title pull-right"><?=lang("player.currency");?>: <span class="player-currency"><?=$this->utils->getCurrentCurrency()['currency_code'] ?></span></h5>
        </div>
    </div>
    <div class="row table-responsive">
        <div class="col-md-12">
            <table class="table table-bordered" id="wallet-info">
                <thead>
                    <tr>
                        <th colspan="6">
                            <center>
                                <?=lang('Wallet Balance'); ?>
                                <div class="pull-right">
                                    <a class="btn btn-portage btn-xs show_bigwallet_details" data-playerid="<?=$player['playerId']; ?>">
                                        <?=lang('Details'); ?>
                                    </a>
                                    <a href="/player_management/resetbalance/<?=$player['playerId'];?>" class="btn btn-scooter btn-xs" onclick="return confirm('<?=lang("confirm.refresh.balance")?>');">
                                        <i class="fa fa-refresh"></i>
                                    </a>
                                    <?php if ($this->permissions->checkPermissions('payment_player_adjustbalance')) {?>
                                        <a href="/payment_management/adjust_balance/<?=$player['playerId']?>" class="btn btn-scooter btn-xs" target="_blank">
                                            <i class="icon-equalizer"></i>
                                        </a>
                                    <?php }?>
                                    <?php if ($this->permissions->checkPermissions('transfer_all_back_to_main_wallet')) {?>
                                        <button id="transferAllToMainWallet" class="btn btn-scooter btn-xs">
                                            <i class="fa fa-exchange"></i>
                                        </button>
                                    <?php }?>
                                </div>
                            </center>
                        </th>
                    </tr>
                    <tr>
                        <th class="active"><center><?=lang("Wallet Name")?></center></th>
                        <th class="active"><center><?=lang("Balance")?></center></th>
                        <th class="active"><center><?=lang("Wallet Name")?></center></th>
                        <th class="active"><center><?=lang("Balance")?></center></th>
                        <th class="active"><center><?=lang("Wallet Name")?></center></th>
                        <th class="active"><center><?=lang("Balance")?></center></th>
                    </tr>
                </thead>
                <tbody id="wallet-list">
                    <tr id="wallet-list-first">
                        <th><?=lang('player.ui20')?></th>
                        <td align="right"><span id="main-wallet-total-bal-amt"></span></td>
                        <th><?=lang('cashier.pendingBalance')?></th>
                        <td align="right"><span id="player-frozen"></span></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    <div class="row table-responsive">
        <div class="col-md-4">
            <table class="table table-bordered">
                <tbody>
                    <tr>
                        <th colspan="2">
                          <center><?=lang('player.74')?></center>
                        </th>
                    </tr>
                    <tr>
                        <th class="active"><?=lang('player.ui14')?></th>
                        <td><span id="total-no-deposit"></span></td>
                    </tr>
                    <tr>
                        <th class="active"><?=lang('player.ui15')?></th>
                        <td align="right"><span id="total-deposit"></span></td>
                    </tr>
                    <tr>
                        <th class="active"><?=lang('player.ui16')?></th>
                        <td align="right"><span id="average-deposits"></span></td>
                    </tr>
                    <tr>
                        <th class="active"><?=lang('player.firstDepositDateTime')?></th>
                        <td><span id="first-last-deposit-first"></span></td>
                    </tr>
                    <tr>
                        <th class="active"><?=lang('player.lastDepositDateTime')?></th>
                        <td><span id="first-last-deposit-last"></span></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="col-md-4">
            <table class="table table-bordered">
                <tbody>
                    <tr>
                        <th colspan="2">
                          <center><?=lang('pay.withinfo')?></center>
                        </th>
                    </tr>
                    <tr>
                        <th class="active"><?=lang('player.ui17')?></th>
                        <td><span id="total-no-widthdrawal"></span></td>
                    </tr>
                    <tr>
                        <th class="active"><?=lang('player.ui18')?></th>
                        <td align="right"><span id="total-withdrawal"></span></td>
                    </tr>
                    <tr>
                        <th class="active"><?=lang('player.ui19')?></th>
                        <td align="right"><span id="average-withdrawals"></span></td>
                    </tr>
                    <tr>
                        <th class="active"><?=lang('player.firstWithdrawDateTime')?></th>
                        <td><span id="first-last-withdraw-first"></span></td>
                    </tr>
                    <tr>
                        <th class="active"><?=lang('player.lastWithdrawDateTime')?></th>
                        <td><span id="first-last-withdraw-last"></span></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="col-md-4">
            <table class="table table-bordered">
                <tbody>
                    <tr>
                        <th colspan="2">
                          <center><?=lang('pay.bonusinfo')?></center>
                        </th>
                    </tr>
                    <tr>
                        <th class="active"><?=lang('player.totalPlayerGroupDepositBonus')?></th>
                        <td align="right"><span id="total-dep-bonus"></span></td>
                    </tr>
                    <tr>
                        <th class="active"><?=lang('player.ui23')?></th>
                        <td align="right"><span id="total-cashback-bonus"></span></td>
                    </tr>
                    <tr>
                        <th class="active"><?=lang('player.ui24')?></th>
                        <td align="right"><span id="total-referal-bonus"></span></td>
                    </tr>
                    <tr>
                        <th class="active"><?=lang('player.totalPromoBonus')?></th>
                        <td align="right"><span id="total-promo-bonus"></span></td>
                    </tr>
                    <tr>
                        <th class="active"><?=lang('player.totalBonusReceived')?></th>
                        <td align="right"><span id="total-bonus-received"></span></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    <div class="row table-responsive">
        <div class="col-md-12">
            <table class="table table-bordered">
                <?php if ($display_game_only_bet_wallet): ?>
                <tr>
                    <th class="col-md-2 active"><?=lang('Game Only Bet Wallet')?></th>
                    <td><span id="game-only-bet-points"><?=$game_only_bet_wallet?></span></td>
                </tr>
                <?php endif; ?>
                <?php if ($enabled_locked_wallet): ?>
                <tr>
                    <th class="col-md-2 active"><?=lang('Locked Wallet')?></th>
                    <td><span id="frozen-points"><?=$locked_wallet?></span></td>
                </tr>
                <?php endif; ?>
            </table>
            <?php if (!$this->utils->isEnabledFeature('hide_point_setting_in_vip_level')): ?>
                <table class="table table-bordered">
                    <tr>
                        <th class="col-md-2 active"><?=lang('Available Points')?></th>
                        <td><span id="available-points"><?=$available_points?></span></td>
                    </tr>
                    <tr>
                        <th class="col-md-2 active"><?=lang('Frozen Points')?></th>
                        <td><span id="frozen-points"><?=$frozen_points?></span></td>
                    </tr>
                </table>
            <?php endif; ?>

            <?php if ($this->permissions->checkPermissions('available_payment_account_for_player')): ?>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th colspan="6">
                                <center><?=lang('con.plm73')?></center>
                            </th>
                        </tr>
                        <tr>
                            <th class="active"><?=lang('ID')?></th>
                            <th class="active"><?=lang('con.plm74')?></th>
                            <th class="active"><?=lang('pay.bankname')?></th>
                            <th class="active"><?=lang('pay.acctname')?></th>
                            <th class="active"><?=lang('cashier.69')?></th>
                            <th class="active"><?= $this->utils->getConfig('use_branch_as_ifsc_in_withdrawal_accounts') ? lang('financial_account.ifsc') : lang('con.plm75') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(!empty($paymentaccounts)): ?>
                            <?php foreach ($paymentaccounts as $paymentaccount): ?>
                                <tr>
                                    <td><?=$paymentaccount->id?></td>
                                    <td><?=lang($paymentaccount->payment_flag)?></td>
                                    <td><?=lang($paymentaccount->payment_type)?></td>
                                    <td><?=$paymentaccount->payment_account_name ?: '<i class="text-muted">' . lang('lang.norecyet') . '</td>'?></td>
                                    <td><?=$paymentaccount->payment_account_number ?: '<i class="text-muted">' . lang('lang.norecyet') . '</td>'?></td>
                                    <td><?=$paymentaccount->payment_branch_name ?: '<i class="text-muted">' . lang('lang.norecyet') . '</td>'?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function(){
        _pubutils.attchBigWalletButton();
        var detectSmallestNegativeBalanceAndNotifyIntoMM =<?= json_encode($this->utils->getConfig('detectSmallestNegativeBalanceAndNotifyIntoMM'))?>;
        $("#transferAllToMainWallet").on('click', function(){
            if (confirm("<?=lang('transfer.all.main')?>")) {

                smButtonLoadStart($(this));
                setTimeout(function(){
                    $.post('/api/retrieveAllSubWalletBalanceToMainBallance/' + playerId, function(data){
                        var status = data.status == 'success' ? 'success' : 'danger';
                        var icon = 'check';
                        if(status != 'success'){
                            $('#transferAllToMainWallet').addClass("disabled");
                            icon = 'warning'
                        }

                        ACCOUNT_INFORMATION.refresh();

                        $.notify({
                            message: data.msg
                        },{
                            type: status,
                            mouse_over: 'pause',
                            template:   '<div id="'+status+'_message_prompt" data-notify="container" class="col-xs-11 col-sm-4 alert alert-{0}" role="alert">' +
                            '<button type="button" aria-hidden="true" class="close" data-notify="dismiss">×</button>' +
                            '<span data-notify="icon"><i class="fa fa-'+icon+'" aria-hidden="true"></i></span> ' +
                            '<span data-notify="title">{1}</span> ' +
                            '<span id="'+status+'_message_text" data-notify="message">{2}</span>' +
                            '</div>'
                        });

                        buttonLoadEnd($("#transferAllToMainWallet"));
                    });
                },500);
            }
        });

        function smButtonLoadStart(button) {
            button.html('<i class="fa fa-spinner fa-spin fa-1x fa-fw"></i> ');
        }

        function buttonLoadEnd(button) {
            button.html('<i class="fa fa-exchange"></i> ');
        }

        var ACCOUNT_INFORMATION = (function() {
            var walletInfo     = $("#wallet-info"),
                repeatedLoad   = false,
                acctInfoLoader = $(".account-info-loader");

            /*Intial Settings*/
            acctInfoLoader.hide()

            function getAccountInformation(){
                if(repeatedLoad){
                    acctInfoLoader.show()
                }

                $.ajax({
                    url : '/player_management/getAccountInformation/' + playerId,
                    type : 'GET',
                    dataType : "json"
                }).done(function (obj) {
                    console.log(<?=$currency_decimals?>);
                    $("#player-frozen").html(Number(obj.frozen).format(<?=$currency_decimals?>));
                    $("#main-wallet-total-bal-amt").html(Number(obj.mainWallet.totalBalanceAmount).format(<?=$currency_decimals?>));
                    makeSubwalletRows(obj.subWallet,obj.playerAccount);
                    $("#total-no-deposit").html(Number(obj.totalDeposits.totalNumberOfDeposit).format(0));
                    $("#total-deposit").html(Number(obj.totalDeposits.totalDeposit).format(<?=$currency_decimals?>));
                    $("#total-no-widthdrawal").html(Number(obj.totalWithdrawal.totalNumberOfWithdrawal).format(0)) ;
                    $("#total-withdrawal").html(Number(obj.totalWithdrawal.totalWithdrawal).format(<?=$currency_decimals?>));
                    $("#total-dep-bonus").html(Number(obj.totalDepositBonus).format(<?=$currency_decimals?>));
                    $("#total-cashback-bonus").html(Number(obj.totalCashbackBonus).format(<?=$currency_decimals?>));
                    $("#average-deposits").html(Number(obj.averageDeposits).format(<?=$currency_decimals?>));
                    $("#average-withdrawals").html(Number(obj.averageWithdrawals).format(<?=$currency_decimals?>));
                    $("#total-referal-bonus").html(Number(obj.totalReferralBonus).format(<?=$currency_decimals?>));
                    $("#first-last-deposit-first").html((obj.firstLastDeposit.first) ? obj.firstLastDeposit.first : "<?=lang('lang.norecord')?>" );
                    $("#first-last-withdraw-first").html((obj.firstLastWithdraw.first) ? obj.firstLastWithdraw.first : "<?=lang('lang.norecord')?>" );
                    $("#total-promo-bonus").html(Number(obj.totalPromoBonus).format(<?=$currency_decimals?>));
                    $("#first-last-deposit-last").html((obj.firstLastDeposit.last) ? obj.firstLastDeposit.last : "<?=lang('lang.norecord')?>" );
                    $("#first-last-withdraw-last").html((obj.firstLastWithdraw.last) ? obj.firstLastWithdraw.last : "<?=lang('lang.norecord')?>" );
                    $("#total-bonus-received").html(Number(obj.totalBonusReceived).format(<?=$currency_decimals?>));

                    repeatedLoad = true;
                    acctInfoLoader.hide();
                }).fail(function (jqXHR, textStatus) {
                    if(jqXHR.status<300 || jqXHR.status>500){
                        alert(textStatus);
                    }
                });
            }

            function makeSubwalletRows(subWallet,playerAccount){
                if(repeatedLoad){
                    walletInfo.find(".sub-wallet").remove();
                }

                var subWalletLength =  subWallet.length;
                var subwallets;

                if(subWalletLength == 0) {
                    walletInfo.find("thead tr:last-child th:last-child").remove();
                    walletInfo.find("thead tr:last-child th:last-child").remove();
                    return;
                }

                var firstSubWallet;
                firstSubWallet += '<th class="sub-wallet">'+subWallet[0].game+' <?=lang("player.uw06")?></th>';
                firstSubWallet += '<td class="sub-wallet" align="right">'+Number(subWallet[0].totalBalanceAmount).format(2)+'</td>';
                $("#wallet-list-first").append(firstSubWallet);

                for(var i=1; i < subWalletLength; i++ ){
                    if(i%3 === 1){
                        subwallets += '<tr class="sub-wallet">';
                    }
                    subwallets += '<th>'+subWallet[i].game+' <?=lang("player.uw06")?></th>';

                    var formated_totalBalanceAmount = Number(subWallet[i].totalBalanceAmount).format(2);
                    if(formated_totalBalanceAmount == detectSmallestNegativeBalanceAndNotifyIntoMM.warningAmount){
                        subwallets +='<td align="right">'
                        + '<span data-total_balance_amount="'+ formated_totalBalanceAmount+'">'
                        + Number(0).format(2)
                        + '</span>'
                        + '</td>';

                    }else{
                        subwallets += '<td align="right">'+Number(subWallet[i].totalBalanceAmount).format(2)+'</td>';
                    }


                    if(i%3 === 0){
                        subwallets += '</tr>';
                    }
                }

                $('#wallet-list').append(subwallets);
                repeatedLoad = true;
            }

            function resetPlayerSubwalletBalance(){
                if(repeatedLoad){
                    acctInfoLoader.show()
                }
                var refresh_enabled = '<?=$refresh_enabled?>';
                if(refresh_enabled){
                    var gamePlatformObj = <?=json_encode($game_platforms)?>;
                    getSubWalletData(gamePlatformObj);

                }
            }

            function getSubWalletData(gamePlatformList){
                if(gamePlatformList.length <= 0){
                    return false;
                }
                var game_platform_id = gamePlatformList[0]['id'];
                $.ajax({
                    url : '/player_management/player_query_balance_by_id/' + playerId + '/'+ game_platform_id,
                    type : 'GET',
                    dataType : "json"
                }).done(function (obj) {
                    gamePlatformList.shift();
                    if(obj.success == true && obj.featureEnabled == true && obj.isUpdated == true){
                        /* Update subwallet only if features is enabled,success and have changes on amount*/
                        $("#main-wallet-total-bal-amt").html(Number(obj.mainWallet.totalBalanceAmount).format(2));
                        makeSubwalletRows(obj.subWallet,obj.playerAccount);
                        // console.log(obj);
                    }
                    if(gamePlatformList.length > 0){
                        getSubWalletData(gamePlatformList);
                    }
                    repeatedLoad = true;
                    acctInfoLoader.hide();
                }).fail(function (jqXHR, textStatus) {
                    if(jqXHR.status<300 || jqXHR.status>500){
                        alert(textStatus);
                    }
                });
                // console.log(game_platform_id);
            }
            /**
             * Number.prototype.format(n, x)
             *
             * @param integer n: length of decimal
             * @param integer x: length of sections
             */
            Number.prototype.format = function(n, x) {
                var re = '\\d(?=(\\d{' + (x || 3) + '})+' + (n > 0 ? '\\.' : '$') + ')';
                return this.toFixed(Math.max(0, ~~n)).replace(new RegExp(re, 'g'), '$&,');
            };

            return {
                refresh:function() {
                    getAccountInformation();
                },
                reset:function() {
                    resetPlayerSubwalletBalance();
                }
            }
        }());

        ACCOUNT_INFORMATION.refresh();
        ACCOUNT_INFORMATION.reset();
    });
</script>

