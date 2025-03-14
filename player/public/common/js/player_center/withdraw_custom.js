// var currency_conversion_rate = 0;
// var custCryptoInputDecimalPlaceSetting = 0;
var formId = '#fm-withdrawal-custom';
// var ENABLE_CRYPTO_CURRENCY = $(formId + ' #crypto').length > 0 ? true : false;
// Load bank info into display
function loadPlayerWithdrawalBankCustom(bank) {
    // if(bank.isCrypto) {
    //     bank.accName = '';
    //     bank.province = '';
    //     bank.city = '';
    //     bank.address = '';
    //     bank.branch = '';
    //     bank.mobileNum = '';
    // }

    var data = [
        // display element, form field element, data
        [null, $(formId + ' #activeBankTypeIdField'), bank.bankTypeId],
        [null, $(formId + ' #activeBankDetailsIdField'), bank.bankDetailsId],
        [$(formId + ' #activeAccNum'), $(formId + ' #activeAccNumField'), bank.accNum],
        [$(formId + ' #activeBankName'), null, '&nbsp;'+bank.bankName],
        [null, $(formId + ' #activeBankCodeField'), bank.bankCode],
        [$(formId + ' #activeAccName'), $(formId + ' #activeAccNameField'), bank.accName],
        [$(formId + ' #activeProvince'), $(formId + ' #activeProvinceField'), bank.province],
        [$(formId + ' #activeCity'), $(formId + ' #activeCityField'), bank.city],
        [$(formId + ' #activeBankAddress'), $(formId + ' #activeBankAddressField'), bank.address],
        [$(formId + ' #activeBranch'), $(formId + ' #activeBranchField'), bank.branch],
        [$(formId + ' #activeMobileNum'), $(formId + ' #activeMobileNumField'), bank.mobileNum],
    ];

    // if (ENABLE_CRYPTO_CURRENCY){
    //     var ENABLE_CUST_CRYPTO_RATE = false;
    //     if(bank.isCrypto){
    //         var bankCode = bank.bankCode.toUpperCase();
    //         var CUST_CRYPTO_UPDATE_TIMING = 1800000;
    //         for (var key in withdraw_cust_crypto_data){
    //             if( bankCode.indexOf(key)  >= 0){
    //                 $(formId + ' #cryptoQty').attr("step", withdraw_cust_crypto_data[key]['custCryptoInputDecimalPlaceSetting']);
    //                 custCryptoInputDecimalPlaceSetting = withdraw_cust_crypto_data[key]['custCryptoInputDecimalPlaceSetting'];
    //                 $(formId + ' #cryptoQty').attr("data-step-error", withdraw_cust_crypto_data[key]['data_step_error_lang']);
    //                 $(formId + ' #cryptoQty').attr("placeholder", withdraw_cust_crypto_data[key]['cryptocurrency']);
    //                 $(formId + ' #crypto_rate_conversion_msg').html('<b>1 ' + withdraw_cust_crypto_data[key]['cryptocurrency'] +' &asymp; <a id="crypto_rate" style="color:red">'+ withdraw_cust_crypto_data[key]['rate']+'</a> '+  withdraw_cust_crypto_data[key]['defaultCurrency']+'</b>');
    //                 $(formId + ' .crypto-withdrawal-label').text(withdraw_cust_crypto_data[key]['converter_crypto_withdrawal_amount_lang']);
    //                 CRYPTO_CURRENCY_CONVERSION_RATE = withdraw_cust_crypto_data[key]['rate'];
    //                 ENABLE_CUST_CRYPTO_RATE = true;
    //                 CUST_CRYPTO_UPDATE_TIMING = withdraw_cust_crypto_data[key]['custCryptoUpdateTiming'];
    //                 CUST_FIX_RATE = withdraw_cust_crypto_data[key]['cryptoToCurrecnyExchangeRate'];
    //                 var displayCryptoQty = $("#displayCryptoQty");
    //                 if (displayCryptoQty.length != 0) {
    //                     displayCryptoQty.attr("step", withdraw_cust_crypto_data[key]['custCryptoInputDecimalPlaceSetting']);
    //                     custCryptoInputDecimalPlaceSetting = withdraw_cust_crypto_data[key]['custCryptoInputDecimalPlaceSetting'];
    //                     displayCryptoQty.attr("data-step-error", withdraw_cust_crypto_data[key]['data_step_error_lang']);
    //                     displayCryptoQty.attr("placeholder", withdraw_cust_crypto_data[key]['cryptocurrency']);
    //                 }
    //             }
    //         }
    //     }
    //     if(ENABLE_CUST_CRYPTO_RATE){
    //         enableDisplayCrypto('withdrawal');
    //         var getCryptoCurrencyRate = function(){
    //             $.ajax({
    //             "url": '/api/getCryptoCurrencyRate/' + bankCode + '/withdrawal'
    //             }).done(function (data) {
    //                 Loader.hide();
    //                 if (data['success']) {
    //                     $(formId + ' #crypto_rate').text(_export_sbe_t1t.utils.displayInThousands(data['rate']));
    //                 }else{
    //                     $(formId + ' #crypto_rate_conversion_msg').html('<b>Network Error</b>');
    //                 }
    //                 if(CRYPTO_CURRENCY_CONVERSION_RATE != data['rate']){
    //                     CRYPTO_CURRENCY_CONVERSION_RATE = data['rate'];
    //                     crypto_converter_current_currency();
    //                 }
    //             }).fail(function(response) {
    //                 CRYPTO_CURRENCY_CONVERSION_RATE = 0;
    //                 $('#crypto_rate_conversion_msg').html('<b>Network Error</b>');
    //                 console.log(response);
    //             });
    //         };
    //         getCryptoCurrencyRate();
    //         CUST_API_TIME_ID = setInterval(getCryptoCurrencyRate, CUST_CRYPTO_UPDATE_TIMING * 1000);
    //     }else{
    //         disableDisplayCrypto('withdrawal');
    //     }
    // }

    //OGP-20534
    // if (enabled_withdrawal_crypto) {
    //     var bankCode = bank.bankCode.toUpperCase();
    //     if (bank.isCrypto && $.inArray(bankCode,withdraw_cryptocurrencies)) {
    //         displayCrypto();
    //         withdrawal_crypto_currency_conversion_rate(bankCode);
    //     }else{
    //         hideCrypto();
    //     }
    // }

    //OGP-25088
    if (enable_withdrawl_bank_fee) {
        var bankCode = bank?.bankCode?.toUpperCase();
        console.log('bankCode is ' + bankCode);
        if ((typeof enable_withdrawl_bank_fee[bankCode]) != 'undefined' ) {
            $(formId + ' .custom_withdrawal_fee').removeClass('hide');
        }else{
            $(formId + ' .custom_withdrawal_fee').addClass('hide');
        }
    }

    // Load new data
    for (var i = 0, len = data.length; i < len; i++) {
        var d = data[i];
        if(d[2]) {
            d[0] && d[0].html(d[2]);
            d[0] && d[0].parent().removeClass('hide');
            d[1] && d[1].val(d[2]);
        } else {
            d[0] && d[0].html('');
            d[0] && d[0].parent().addClass('hide');
            d[1] && d[1].val('');
        }
    }

    // Remove all bank icon classes (OGP-2513)
    $(formId + ' #activeBankName').removeClass(function(index, name) {
        return (name.match(/b-icon|b-icon-custom|bank_[0-9]+/g) || []).join(' ');
    });
    // Remove custom bank icon image
    $(formId + ' #activeBankName').find('img').remove();

    // Add custom bank icon image (if present) or bank icon class back
    if (bank.icon_url) {
        $(formId + ' #activeBankName').addClass('b-icon-custom').prepend($('<img />').attr('src', bank.icon_url));
    }
    else {
        // Remove previous bank icon class
        $(formId + ' .dispBankInfo').removeClass('hide');

        // Bank icon and name
        $(formId + ' #activeBankName').addClass('b-icon').addClass('bank_' + bank.bankTypeId);
    }

    // Trigger re-evaluation of submit button disable status
    $(formId + ' #amount').trigger('change');
}

function setupSelectPlayerWithdrawalBankCustom(bank_data){
    var load_bank_data;
    // var ENABLE_CRYPTO_CURRENCY = $(formId + ' #crypto').length > 0 ? true : false;
    console.log('---------------------bank_data',bank_data);
    if(bank_data === undefined){
        var $chosenBank = $('#currentBankListCustom li.active');
        // Hack (append to last event.) - for waiting other jquery events.
        // $(function(){
            // Check the player withdrawal bank is empty.
            // if($chosenBank.length <= 0){
            //     MessageBox.info(lang('cashier.withdrawal.force_setup_player_withdrawal_bank_hint'), lang('cashier.withdrawal.player_center_withdrawal_bank_messagebox_title_info'), function(){
            //         document.location.href="/player_center2/bank_account#bank_account_withdrawal";
            //         // $('#add-bank-acc').data('backdrop', 'static').data('keyboard', false);
            //         // $('#add-bank-acc .close-add-bank-account').hide();
            //         // $('#add-bank-acc .close').hide();
            //         // $('.add-bank-account[data-bank-type="withdrawal"]').trigger('click');
            //     }, [
            //         {
            //             'attr': {
            //                 'class': 'btn btn-primary'
            //             },
            //             'text': lang('pay.reg')
            //         }
            //     ]);
            // }
        // });

        // if($chosenBank.length <= 0){
        //     return;
        // }
        load_bank_data = {
            'bankTypeId' : $chosenBank.data('bank-type-id'),
            'bankDetailsId' : $chosenBank.data('id'),
            'bankName' : $('span', $chosenBank).html(),
            'bankCode' : $chosenBank.data('bank-code'),
            'isCrypto' : $chosenBank.data('is-crypto'),
            'branch' : $chosenBank.data('branch'),
            'province' : $chosenBank.data('province'),
            'city' : $chosenBank.data('city'),
            'accNum' : $chosenBank.data('acc-num'),
            'accName' : $chosenBank.data('acc-name'),
            'mobileNum' : $chosenBank.data('mobile-num')
        };

        if ($chosenBank.length <= 0) {
            $.ajax({"url": '/api/automaticWithdrawalBank'}).done(function (data) {
                if (data['success']) {
                    var automaticBank = data['automaticBank'];

                    load_bank_data = {
                        'bankTypeId' : automaticBank['bankTypeId'],
                        'bankDetailsId' : automaticBank['playerBankDetailsId'],
                        'bankName' : automaticBank['bankName'],
                        'bankCode' : automaticBank['bank_code'],
                        'isCrypto' : automaticBank['is_crypto'],
                        'branch' : automaticBank['branch'],
                        'province' : automaticBank['province'],
                        'city' : automaticBank['city'],
                        'accNum' : automaticBank['bankAccountNumber'],
                        'accName' : automaticBank['bankAccountFullName'],
                        'mobileNum' : automaticBank['phone']
                    };

                    loadPlayerWithdrawalBankCustom(load_bank_data);
                }
            }).fail(function(response) {
                console.log(response);
            });
        }
    }else{
        load_bank_data = {
            'bankTypeId' : bank_data['bankTypeId'],
            'bankDetailsId' : bank_data['bankDetailsId'],
            'bankName' : bank_data['bankName'],
            'bankCode' : bank_data['bank_code'],
            'isCrypto' : bank_data['isCrypto'],
            'branch' : bank_data['branch'],
            'province' : bank_data['province'],
            'city' : bank_data['city'],
            'accNum' : bank_data['bankAccountNumber'],
            'accName' : bank_data['bankAccountFullName'],
            'mobileNum' : bank_data['phone']
        };
    }
    // Get chosen bank info from data fields
    loadPlayerWithdrawalBankCustom(load_bank_data);

    $('#player-withdrawal-banks-custom').modal('hide');
}

function display_thousands_separator_custom_withdrawal() {
    var float_amount = $(formId + ' #thousands_separator_amount').val().replace(/,/g, "");
    var housands_separator_amount = _export_sbe_t1t.utils.displayInThousands(float_amount);

    $(formId + ' #thousands_separator_amount').val(housands_separator_amount);
    $(formId + ' #amount').val(parseFloat(float_amount)).change();
    // crypto_converter_current_currency();
}

// function crypto_converter_current_currency() {
//     if (ENABLE_CRYPTO_CURRENCY && $('#currentBankListCustom li.active').data('is-crypto')){
//         var cryptoQty = $('#cryptoQty').val();
//         var amount = $('#amount').val();
//         var rateAmount = (amount * CUST_FIX_RATE / CRYPTO_CURRENCY_CONVERSION_RATE);
//         var num = rateAmount.toFixed(custCryptoInputDecimalPlaceSetting.toString().split(".")[1].length);
//         if(amount && cryptoQty != num && $('#cryptoQty').hasClass('active')){
//             var displayCryptoQty = _export_sbe_t1t.utils.displayInThousands(num)
//             $('#displayCryptoQty').val(displayCryptoQty).trigger('change');
//             $('#cryptoQty').val(num).trigger('change');
//         }else if(amount.length <= 0 && $('#cryptoQty').hasClass('active')){
//             $('#cryptoQty').val('');
//             $('#displayCryptoQty').val('').trigger('change');
//         }
//     }else{
//         return;
//     }
// }

// function withdrawal_crypto_currency_conversion_rate(bankCode){
//     var timeid = 0;
//     var getPaymentCryptoRate = function(){
//         $.ajax({
//         "url": '/api/getPaymentCryptoRate/' + bankCode
//         }).done(function (data) {
//             Loader.hide();
//             if (data['success']) {
//                 $('#coin_rate').text(data['rateData'].exchangeRate);
//                 $('.default_amount').text(data['rateData'].amount);
//                 $('#currency_rate').text(data['rateData'].reverseRate);
//                 $('.crypto-currency').text(data['cryptoCurrency']);
//                 $('.default-currency').text(data['currency']);
//                 $('#withdrawal_crypto_amt').attr('placeholder',data['cryptoCurrency']);
//                 currency_conversion_rate = data['rateData'].exchangeRate;
//             }else{
//                 currency_conversion_rate = 0;
//                 $('.default_amount').html('<b>Network Error</b>');
//                 $('#crypto_rate_conversion_msg').html('<b>Network Error</b>');
//                 $('#currency_conversion_rate_msg').html('<b>Network Error</b>');
//             }
//         }).fail(function(response) {
//             currency_conversion_rate = 0;
//             $('.default_amount').html('<b>Network Error</b>');
//             $('#crypto_rate_conversion_msg').html('<b>Network Error</b>');
//             $('#currency_conversion_rate_msg').html('<b>Network Error</b>');
//             console.log(response);
//         });
//     };
//     getPaymentCryptoRate();
//     timeid = setInterval(getPaymentCryptoRate, 600000);
// }

// function currency_conversion_to_crypto() {
//     var cryptoQty = $('#withdrawal_crypto_amt').val();
//     var amount = $(formId + ' input[name="amount"]').val();
//     var rateAmount = (amount / currency_conversion_rate);
//     var num = rateAmount.toFixed(8);

//     if(amount.length > 0 && withdrawal_crypto_amt != num){
//         $('#withdrawal_crypto_amt').val(num).trigger('change');
//     }else if(amount.length <= 0 && $(formId + ' input[name="amount"]').hasClass('active')){
//         $('#withdrawal_crypto_amt').val('');
//     }
// }

// Custom javascripts used by withdraw page
$(function(){
	// Simulates the click on first bank upon page load
    $('#currentBankListCustom li').each(function(){
        if($(this).data('default')){
            $('a', $(this)).trigger('click');
        }
    });

    if($('#currentBankListCustom li.active').length <= 0){
        $('#currentBankListCustom li:first a').trigger('click');
    }

    setupSelectPlayerWithdrawalBankCustom();

    // $('#cryptoQty').change(function() {
    //     crypto_converter_current_currency();
    // });

    // if (enabled_withdrawal_crypto) {
    //     $(formId + ' input[name="amount"]').on("change paste keyup", function() {
    //         currency_conversion_to_crypto();
    //     });
    // }

	// Controls bank list expand and collapse
	$('.show-btn').click(function(){
		$('#allBankList').css( { height: 'auto' } );
		$('.show-btn').hide();
		$('.hide-btn').show();
	});
	$('.hide-btn').click(function(){
		$('#allBankList').animate( { height: $('#allBankList').data('height')+'px' }, { duration:300 });
		$('.show-btn').show();
		$('.hide-btn').hide();
	});

    // Selected withdrawal bank account
	$('#saveChosenBankNew').click(function(){
        // if (ENABLE_CRYPTO_CURRENCY){
        //     setValueOnChangeAccountCrypto('withdrawal');

        // }
		setupSelectPlayerWithdrawalBankCustom();
	});


	// Submit form
	$(formId + ' #submitBtn').click(function(){
        // if (ENABLE_CRYPTO_CURRENCY){
        //     if (!validateCryptoQuantity()){
        //          MessageBox.danger(lang('Please select a bank account with virtual currency type'));
        //         $(formId + ' #submitBtn').removeClass('disabled').removeAttr('disabled').prop('disabled', false);
        //         return false;
        //     }
        //     $('#activeRate').val(CRYPTO_CURRENCY_CONVERSION_RATE);
        // }
        $(formId + ' #submitBtn').addClass('disabled').attr('disabled', 'disabled').prop('disabled', true);
        $("form[name='withdrawFormCustom']").submit();
	});

    // Form validation
    var form = $('#fm-withdrawal-custom form');
    $('[required]', form).closest('.form-group').find('.control-label').each(function(){
        $(this).append($('<em style="color:red">*</em>'));
    });

	// Disable submit btn when form is submitting
    var submit_timer = null;
	form.on('submit', function() {
        form.validator('validate', function(e, validator){
            if(!validator.isIncomplete() && !validator.hasErrors()){
                Loader.show();
                $(formId + ' #submitBtn').addClass('disabled');
                var inputAmt = $(formId + ' input[name="amount"]').val();
                if (_withdraw.checkReachAutomaticTransferConditions(inputAmt)) {
                    _withdraw.autoTransferAll();
                }

                if(submit_timer !== null){
                    clearTimeout(submit_timer);
                }
                submit_timer = setTimeout(function(){
                    form.off('submit').submit();
                }, 300);
            }else{
                if(enable_thousands_separator_in_the_withdraw_amount){
                    display_thousands_separator_custom_withdrawal();
                }
                $(formId + ' #submitBtn').removeClass('disabled').removeAttr('disabled').prop('disabled', false);
            }
        });

        return false;
	});

    form.validator({
        disable: false
    });
});

var _withdraw = function () {

    var wallInfo = {},
        isAutoTransferAll = false;

    autoTransferAll = function()
    {
        if (wallInfo) {
            $.ajax({
                type: "post",
                url: "/api/retrieveAllSubWalletBalanceToMainBallance",
                async: false,
            });
        }
    }

    checkReachAutomaticTransferConditions = function (withdrawAmt)
    {
        initCurrentWalletInfo();
        if (!_withdraw.isAutoTransferAll) { return false }
        if (checkMainWalletAmtCanWithdrawal(withdrawAmt)) { return false; }
        if (!checkTotalBalanceCanWithdrawal(withdrawAmt)) { return false; }
        return true;
    }

    checkMainWalletAmtCanWithdrawal = function (amt)
    {
        return parseFloat(amt) <= parseFloat(wallInfo.main_balance);
    }

    checkTotalBalanceCanWithdrawal = function(amt)
    {
        return parseFloat(amt) <= parseFloat(wallInfo.total_balance);
    }

    initCurrentWalletInfo = function ()
    {
        var _wallinfo = _export_sbe_t1t.variables.walletInfo;

        if (!_wallinfo) {return false}
        wallInfo.main_balance  = _wallinfo.main_wallet.balance
        wallInfo.total_balance = _wallinfo.total_balance.balance
    }

    return {
        isAutoTransferAll: isAutoTransferAll,
        autoTransferAll  : function () { return autoTransferAll(); },
        checkReachAutomaticTransferConditions : function (withdrawAmt) { return checkReachAutomaticTransferConditions(withdrawAmt); }
    };

}()