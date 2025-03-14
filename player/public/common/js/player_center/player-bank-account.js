function expandBankList() {
     $("#allBankList").data("height", $("#allBankList").outerHeight());
     $("#allBankList").css({ height: "auto" });
     $(".show-btn").hide();
     $(".hide-btn").show();
}

function shrinkBankList() {
     $("#allBankList").animate({ height: $("#allBankList").data("height") + "px" }, { duration: 300 });
     $(".show-btn").show();
     $(".hide-btn").hide();
}

function disabledSmsVerificationWhenSuccess(){
     $('#send_sms_verification_code').prop('disabled', true);
     $("#submit_sms_verification").prop('disabled', true);
     $("#fm-verification-code").prop('disabled', true);
     clearInterval(countdown);
     smsValidBtn.text(smstextBtn);
}

var SMS_VERIFY_SUCCESSE = false,
     SUBMIT_PAYMENT_TYPE_FLAG = '';

var PlayerBankaccount = {
     enable_crypto_details : false,
}

$(function () {
     $.fn.validator.Constructor.DEFAULTS.errors.required = lang("This is a required field");

     $("body").on("keydown", '#inputAccNum[type="number"]', function (e) {
          // Allow: delete, backspace, tab, escape, enter
          if (
               $.inArray(e.keyCode, [46, 8, 9, 27, 13, 190]) !== -1 ||
               // Allow: Ctrl/cmd+A
               (e.keyCode == 65 && (e.ctrlKey === true || e.metaKey === true)) ||
               // Allow: Ctrl/cmd+C
               (e.keyCode == 67 && (e.ctrlKey === true || e.metaKey === true)) ||
               // Allow: Ctrl/cmd+X
               (e.keyCode == 88 && (e.ctrlKey === true || e.metaKey === true)) ||
               // Allow: home, end, left, right
               (e.keyCode >= 35 && e.keyCode <= 39)
          ) {
               // let it happen, don't do anything
               return;
          }
          // Ensure that it is a number and stop the keypress
          if ((e.shiftKey || (e.keyCode < 48 || e.keyCode > 57)) && (e.keyCode < 96 || e.keyCode > 105)) {
               e.preventDefault();
          }
     });

     function formInit(form, bank_type) {
          var bank_code = '';
          // setupDefaultBank
          if ($("#allBankList").data("setup") != true) {
               $("#allBankList li").on("click", function () {
                    $('.crypto_bank_detail').addClass('hide');
                    $("#bankTypeId").val($(this).data("bank-type-id"));
                    if(ENABLE_SWITCH_CPF_TYPE == 'true' ){
                         if($(this).data("bank-code") && ( $(this).data("bank-code").indexOf('PIX_CPF')   != -1 ||
                              $(this).data("bank-code").indexOf('PIX_PHONE') != -1 || $(this).data("bank-code").indexOf('PIX_EMAIL') != -1 )
                              ){
                              bank_code = $(this).data("bank-code");
                              if(PlayerBankaccount.switch_cpf_type_data){
                                   $.each(PlayerBankaccount.switch_cpf_type_data, function (key, value) {
                                        if(key == 'PIX_CPF' && bank_code.indexOf('PIX_CPF') != -1){
                                             $("#inputPixType").val(key);
                                             $("#inputAccNum").val(value);
                                             $('.pix_key_label').text('').text(lang('financial_account.CPF_number')+':');
                                             $("#inputPixKey").val(value);
                                        }else if(key == 'PHONE' && bank_code.indexOf('PIX_PHONE') != -1){
                                             $("#inputPixType").val(key);
                                             $("#inputAccNum").val(value);
                                             $('.pix_key_label').text('').text(lang('financial_account.phone')+':');
                                             $("#inputPixKey").val(value);
                                        }else if(key == 'EMAIL' && bank_code.indexOf('PIX_EMAIL') != -1){
                                             $("#inputPixType").val(key);
                                             $("#inputAccNum").val(value);
                                             $('.pix_key_label').text('').text(lang('lang.email')+':');
                                             $("#inputPixKey").val(value);
                                        }
                                   });
                              }
                         }
                    }
                    var bankTypeId = $("#bankTypeId").val();
                    if(PlayerBankaccount.enable_crypto_details && PlayerBankaccount.enable_crypto_details.indexOf(bankTypeId) != -1 && bank_type.toLowerCase() == "withdrawal"){
                         $('.crypto_bank_detail').removeClass('hide');
                    }

                    if($('#inputCryptoNetwork').length > 0){
                         $.ajax({
                         url: "/ajax/bank_account/CryptoNetwork/" + $(this).data("bank-type-id"),
                         }).done(function (data, status, xhr) {
                              Loader.hide();
                              $('#inputCryptoNetwork').find("option").remove();
                              $('#inputCryptoNetwork').append($("<option>").val('').text(lang('please_select')));
                              if (xhr.hasOwnProperty("responseJSON")) {
                                   $.each(data.crypto_network_options, function (key, value) {
                                        $('#inputCryptoNetwork').append($("<option>").val(value).text(value));
                                   });
                              }
                         });
                    }
                    $(".selected-bank .bank-entry").html($("a span", this)[0].outerHTML);
               });
               $("#allBankList").data("setup", true);
          }

          if ($("#allBankList li.active").length <= 0) {
               $("#allBankList li:first")
                    .addClass("active")
                    .trigger("click");
          }

          if ($("#bankDetailId").val() !== undefined && $("#bankDetailId").val().length) {
               $(".player_bank_account_modal .fmd-step1").addClass("disabled");
               $(".player_bank_account_modal #inputAccNum")
                    .addClass("disabled")
                    .prop("disabled", true);
          }

          ProvinceCity.bind();

          $("#inputAccNum").data("remote-data", function () {
               var data = {};

               data["bankType"] = bank_type.toLowerCase() !== "withdrawal" ? 0 : 1;
               data["accountNumber"] = $("#inputAccNum").val();
               data["bankTypeId"] = $("#bankTypeId").val();
               return data;
          });

          form.validator({
               focus_offset: 0,
               disable: false,
          });
          form.on("validated.bs.remote.input-acct-num", function (e, request_data, response_data, errors, deferred) {
               if (response_data.status == "error" && response_data.hasOwnProperty("message")) {
                    return response_data["message"];
               }
          });

          $("#bank_list_search_input").keyup(function () {
               var searchText = $(this).val();
               $('#allBankList>li').each(function (index) {
                    var bankName = $(this).data().bankName.toLowerCase();
                    if (bankName.indexOf(searchText.toLowerCase()) == '-1') {
                         $(this).hide()
                    } else (
                         $(this).show()
                    )
               });
          });
     }

     function checkRequireInfo(bank_type) {
          var errorCount = 0;
          defaultErrorMsg = lang("Before adding a bank account, please set your");
          if (checkAccountNameIsEmpty() && ENABLED_SET_REALNAME_WHEN_ADD_BANK_CARD !== 'true') {
               defaultErrorMsg = lang("Please update account name to add bank account");
               errorCount++;
          } else if (!checkPhoneNumberIsVerify()) {
               defaultErrorMsg = lang("Please update phone number to add bank account");
               errorCount++;
          } else if (ENABLE_CPF_NUMBER == 'true' && checkPixNumberIsEmpty()){
               defaultErrorMsg = lang("Please update CPF number to add bank account");
               errorCount++;
               if(EDIT_CPF_NUMBER_STATUS == 'not_allow_edit_cpf_number'){
                    defaultErrorMsg = lang("Please contact administrator to update your CPF Number");
               }
          }

          if (errorCount > 0) {
               return defaultErrorMsg;
          } else {
               return false;
          }
     }

     function checkAccountNameIsEmpty() {
          var element = $('[name="input-acct-name"]');
          if (element.length <= 0) {
               return false;
          }

          if (!element[0].hasAttribute("readonly")) {
               return false;
          }

          return $.isEmptyObject(element.val()) ? true : false;
     }

     function checkPixNumberIsEmpty() {
          var element = $('[name="input-pixkey"]');
          if (element.length <= 0) {
               return false;
          }

          if (!element[0].hasAttribute("readonly")) {
               return false;
          }

          return $.isEmptyObject(element.val()) ? true : false;
     }

     function checkPhoneNumberIsVerify() {
          if((SUBMIT_PAYMENT_TYPE_FLAG == 3 && ENABLE_SMS_VERIFY_IN_ADD_CRYPTO_BANK_ACCOUNT == 'true') || (SUBMIT_PAYMENT_TYPE_FLAG == 1 && ENABLE_SMS_VERIFY_IN_ADD_BANK_ACCOUNT == 'true') || (SUBMIT_PAYMENT_TYPE_FLAG == 2 && ENABLE_SMS_VERIFY_IN_ADD_EWALLET == 'true')){
               if ($('#verify_phone').val() == 1) {
                    return true;
               } else {
                    return false;
               }
          }else{
               return true;
          }
     }

     function showRequireInfoEmptyWarning(msg) {
          var callback = function () {
               Loader.show();

               window.location.href = typeof EMPTY_ACCOUNT_NAME_REDIRECT_URL === "undefined" ? "/player_center/dashboard/index#accountInformation" : EMPTY_ACCOUNT_NAME_REDIRECT_URL;
          };

          MessageBox.danger(msg, undefined, callback, [
               {
                    attr: {
                         class: "btn btn-primary",
                    },
                    text: lang("lang.settings"),
               },
          ]);
     }

     function FormValidation(form, callback) {
          form.validator("validate", function (e, validator) {
               if (!validator.isIncomplete() && !validator.hasErrors()) {
                    form.validator("destroy");

                    if (callback !== undefined && typeof callback == "function") {
                         callback(form.serialize());
                    }
               }
          });
     }

     $("#bank_account_tab_nav a:first").tab("show");

     $(".set-default-bank-account").on("click", function () {

          var err_msg = $(this).data("err-msg");
          var bank_type_status = $(this).data("bank-type-status");
          bank_type_status = bank_type_status.toLowerCase() == "active" ? true : false;
          if (!bank_type_status) {
               MessageBox.info(err_msg, lang('alert-notice'), null, null);
               return false;
          }

          Loader.show();

          $.ajax({
               url: "/ajax/bank_account/SetDefault/" + $(this).data("bank-account-id"),
          }).done(function (msg, status, xhr) {
               Loader.hide();

               MessageBox.ajax(msg, function () {
                    Loader.show();

                    window.location.href = window.location.href;
               });
          });
     });

     $("#view-bank-acc").on("hide.bs.modal", function () {
          $(".modal-body", $(this)).html("");
     });

     $("#add-bank-acc").on("hide.bs.modal", function () {
          $(".modal-body", $(this)).html("");
          $("#AddBankAccountForm").validator("destroy");

          var hashtag_str = window.location.hash.substr(1);
          if (hashtag_str == 'triggerAddBank') {
               window.location.hash = '';
          }
     });

     $("#edit-bank-acc").on("hide.bs.modal", function () {
          $(".modal-body", $(this)).html("");
          $("#EditBankAccountForm").validator("destroy");
     });

     $(".view-bank-account").on("click", function () {

          var bank_type = $(this).data("bank-type");
          bank_type = bank_type.toLowerCase() == "withdrawal" ? "withdrawal" : "deposit";
          bank_type = bank_type.charAt(0).toUpperCase() + bank_type.slice(1);

          var err_msg = $(this).data("err-msg");
          var bank_type_status = $(this).data("bank-type-status");
          bank_type_status = bank_type_status.toLowerCase() == "active" ? true : false;
          if (!bank_type_status) {
               MessageBox.info(err_msg, lang('alert-notice'), null, null);
               return false;
          }

          Loader.show();

          $.ajax({
               url: "/ajax/bank_account/" + bank_type + "Detail/" + $(this).data("bank-account-id"),
          }).done(function (msg, status, xhr) {
               Loader.hide();
               if (xhr.hasOwnProperty("responseJSON")) {
                    MessageBox.ajax(msg);
                    return false;
               }

               $("#view-bank-acc .modal-body").html(msg);

               ProvinceCity.bind();

               $("#view-bank-acc").modal("show");
          });
     });

     $(".edit-bank-account").on("click", function () {
          Loader.show();

          var bank_type = $(this).data("bank-type");
          bank_type = bank_type.toLowerCase() == "withdrawal" ? "withdrawal" : "deposit";
          bank_type = bank_type.charAt(0).toUpperCase() + bank_type.slice(1);

          $.ajax({
               url: "/ajax/bank_account/Edit" + bank_type + "/" + $(this).data("bank-account-id"),
          }).done(function (msg, status, xhr) {
               Loader.hide();
               if (xhr.hasOwnProperty("responseJSON")) {
                    MessageBox.ajax(msg);
                    return false;
               }

               $("#edit-bank-acc .modal-body").html(msg);

               $("#edit-bank-acc .modal-body .bank-list")
                    .off("mousewheel")
                    .on("mousewheel", function (e) {
                         var event = e.originalEvent;
                         var d = event.wheelDelta || -event.detail;

                         this.scrollTop += (d < 0 ? 1 : -1) * 30;
                         e.preventDefault();
                    });

               var msg = checkRequireInfo(bank_type);

               if (msg) {
                    showRequireInfoEmptyWarning(msg);
               } else {
                    formInit($("#EditBankAccountForm"), bank_type);

                    $(".submit-edit-bank-account").data("bank-type", bank_type);

                    // Append '*' automatically for required fields
                    $("#fields *[required]")
                         .closest(".form-group")
                         .find(".control-label span")
                         .after($('<em style="color:red">*</em>'));
                    $("#edit-bank-acc").modal("show");
               }
          });
     });

     $(".submit-edit-bank-account").on("click", function () {
          var bank_type = $(this).data("bank-type");
          bank_type = bank_type.toLowerCase() == "withdrawal" ? "withdrawal" : "deposit";
          bank_type = bank_type.charAt(0).toUpperCase() + bank_type.slice(1);

          FormValidation($("#EditBankAccountForm"), function (data) {
               $("#edit-bank-acc").modal("hide");

               Loader.show();

               $.ajax({
                    method: "POST",
                    url: "/ajax/bank_account/Edit" + bank_type + "/",
                    data: data,
               }).done(function (msg, status, xhr) {
                    Loader.hide();
                    if (xhr.hasOwnProperty("responseJSON")) {
                         MessageBox.ajax(msg, function () {
                              Loader.show();
                              window.location.href = window.location.href;
                         });
                         return false;
                    }
               });
          });

          return false;
     });

     $(".add-bank-account").on("click", function () {

          var bank_limit = $(this).data("bank-limit");
          if (bank_limit == undefined) {
               bank_limit = 0;
          }
          var bank_count = 0;
          if (bank_limit > 0) {
               var bank_error_message = $(this).data("bank-error-message");
               bank_count = $(this).data("bank-count");
               if (bank_count >= bank_limit) {
                    MessageBox.show(bank_error_message);
                    return false;
               }
          }
          Loader.show();
          var bank_type = $(this).data("bank-type");
          bank_type = bank_type.toLowerCase() == "withdrawal" ? "withdrawal" : "deposit";
          bank_type = bank_type.charAt(0).toUpperCase() + bank_type.slice(1);

          var callback = $(this).data("callback");
          var payment_type_flag = $(this).data("payment-type-flag");
          SUBMIT_PAYMENT_TYPE_FLAG = payment_type_flag;
          var modal_title = $(this).text();

          $.ajax({
               url: "/ajax/bank_account/Add" + bank_type + "/" + payment_type_flag,
          }).done(function (msg, status, xhr) {
               Loader.hide();
               if (xhr.hasOwnProperty("responseJSON")) {
                    MessageBox.ajax(msg);
                    return false;
               }

               $("#add-bank-acc .modal-body").html(msg);
               $("#add-bank-acc .modal-title").html(modal_title);

               $("#add-bank-acc .modal-body .bank-list")
                    .off("mousewheel")
                    .on("mousewheel", function (e) {
                         var event = e.originalEvent;
                         var d = event.wheelDelta || -event.detail;

                         this.scrollTop += (d < 0 ? 1 : -1) * 30;
                         e.preventDefault();
                    });

               msg = checkRequireInfo(bank_type);

               if (msg) {
                    showRequireInfoEmptyWarning(msg);
               } else {
                    formInit($("#AddBankAccountForm"), bank_type);

                    $(".submit-add-bank-account").data("bank-type", bank_type);
                    $(".submit-add-bank-account").data("callback", callback);

                    // Append '*' automatically for required fields
                    $("#fields *[required]")
                         .closest(".form-group")
                         .find(".control-label span")
                         .after($('<em style="color:red">*</em>'));
                    $("#add-bank-acc").modal("show");
               }
          });
     });

     $(".submit-add-bank-account").on("click", function () {
          if (!SMS_VERIFY_SUCCESSE && ((SUBMIT_PAYMENT_TYPE_FLAG == 3 && ENABLE_SMS_VERIFY_IN_ADD_CRYPTO_BANK_ACCOUNT == 'true')
               || (SUBMIT_PAYMENT_TYPE_FLAG == 1 && ENABLE_SMS_VERIFY_IN_ADD_BANK_ACCOUNT == 'true')
               || (SUBMIT_PAYMENT_TYPE_FLAG == 2 && ENABLE_SMS_VERIFY_IN_ADD_EWALLET == 'true'))) {
               MessageBox.danger(lang("Please update phone number to add bank account"));
               return false;
          }

          var bank_type = $(this).data("bank-type");
          var bank_type_object = "bank_account_" + bank_type;
          bank_type = bank_type.toLowerCase() == "withdrawal" ? "withdrawal" : "deposit";
          bank_type = bank_type.charAt(0).toUpperCase() + bank_type.slice(1);

          var callback = $(this).data("callback");

          FormValidation($("#AddBankAccountForm"), function (data) {
               $("#add-bank-acc").modal("hide");

               Loader.show();

               $.ajax({
                    method: "POST",
                    url: "/ajax/bank_account/Add" + bank_type + "/",
                    data: data,
               }).done(function (msg, status, xhr) {
                    Loader.hide();
                    if (xhr.hasOwnProperty("responseJSON")) {
                         MessageBox.ajax(msg, function () {
                              if (callback !== undefined && window.hasOwnProperty(callback) && typeof window[callback] === "function") {
                                   $(document)
                                        .find(".add-bank-account")
                                        .each(function () {
                                             if ($(this).data("bank-count") != undefined) {
                                                  var add_bank_count = parseInt($(this).data("bank-count")) + 1;
                                                  $(this).data("bank-count", add_bank_count);
                                             }
                                        });
                                   window[callback](msg.bank_detail);
                              } else {
                                   Loader.show();
                                   location.reload();
                              }
                         });
                         if(ENABLED_QUICK_ADD_ACCOUNT_BUTTON && source === 'deposit_page'){
                              document.location.href = '/player_center2/deposit/deposit_custom_view/' + source_id;
                         }
                         return false;
                    }
               });
          });

          return false;
     });

     $(".delete-bank-account").on("click", function () {
          var bank_type = $(this).data("bank-type");
          bank_type = bank_type.toLowerCase() == "withdrawal" ? "withdrawal" : "deposit";
          bank_type = bank_type.charAt(0).toUpperCase() + bank_type.slice(1);

          var bank_account_id = $(this).data("bank-account-id");

          var bank_account_info = $(this)
               .parent()
               .parent();

          var modal = $("#delete-bank-acc");

          $(".submit-btn", modal)
               .off("click")
               .on("click", function () {
                    Loader.show();
                    modal.modal("hide");

                    $.ajax({
                         url: "/ajax/bank_account/Delete" + bank_type + "/" + bank_account_id,
                    }).done(function (data, status, xhr) {
                         Loader.hide();
                         if (xhr.hasOwnProperty("responseJSON")) {
                              MessageBox.ajax(data, function () {
                                   Loader.show();
                                   window.location.href = window.location.href;
                              });

                              return false;
                         } else {
                              Loader.show();
                              window.location.href = window.location.href;
                         }
                    });
               });

          modal.on("show.bs.modal", function (e) {
               $(".modal-body", modal).html(bank_account_info.html());
               $(".modal-body .bank_account_helper", modal).remove();
          });

          modal.modal("show");
     });

     const urlParams = new URLSearchParams(window.location.search);
     var source = urlParams.get('source');
     var source_flag = urlParams.get('source_flag');
     var source_type = urlParams.get('source_type');
     var source_id = urlParams.get('source_id');

     if (source === 'deposit_page') {
          $('#bank_account_deposit .add-bank-account[data-payment-type-flag="' + source_flag + '"]').click();
     }
});

$(document).ready(function () {
     // Javascript to enable link to tab
     var url = document.location.toString();
     if (url.match('#')) {
          $('a[href="#' + url.split('#')[1] + '"]').click();
     }

     $(".invalid_bank").on("click", function () {
          var msg = { "status": "info", "msg": $(this).children(":first").html() };
          MessageBox.ajax(msg);
     });

     var hashtag_str = window.location.hash.substr(1);
     if (hashtag_str == 'triggerAddBank') {
          $(".add-bank-account").trigger('click');
     }

});