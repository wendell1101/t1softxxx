<style>
    .action-item {
        margin-bottom: 0.5rem;
    }
</style>
<div class="panel panel-primary">
    <div class="panel-heading">
        <h4 class="panel-title">
            <i class="icon-note"></i> <?= lang('redemptionCode.redemptionCodeCategoryList'); ?>
            <?php if ($manage_redemption_code_category) : ?>
                <a href="javascript:void(0);" class="btn  pull-right btn-xs btn-info" id="addCategoryBtn">
                    <i class="fa fa-plus-circle"></i> <?= lang('redemptionCode.addNewCategory'); ?>
                </a>
            <?php endif; ?>
        </h4>
    </div>
    <div class="panel-body">
        <div class="table-responsive">
            <table class="table table-hover table-bordered" id="myTable">
                <thead>
                    <tr>
                        <th><?= lang('redemptionCode.id'); ?></th>
                        <th><?= lang('redemptionCode.categoryName'); ?></th>
                        <th><?= lang('redemptionCode.quantity'); ?></th>
                        <!-- <th><?= lang('redemptionCode.used_quantity'); ?></th> -->
                        <!-- <th><?= lang('redemptionCode.left_quantity'); ?></th> -->
                        <th><?= lang('redemptionCode.bonus'); ?></th>
                        <th><?= lang('redemptionCode.applyLimit'); ?></th>
                        <th><?= lang('redemptionCode.withdraw_condition'); ?></th>
                        <th><?= lang('redemptionCode.create_at'); ?></th>
                        <th><?= lang('redemptionCode.apply_expire_time'); ?></th>
                        <!-- <th><?= lang('redemptionCode.allow_duplicate_apply'); ?></th> -->
                        <th><?= lang('redemptionCode.status'); ?></th>
                        <th><?= lang('redemptionCode.note'); ?></th>
                        <!-- <th><?= lang('redemptionCode.action_logs'); ?></th> -->
                        <?php if ($manage_redemption_code_category) : ?>
                            <th><?= lang('redemptionCode.actions'); ?></th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>
    </div>
    <div class="panel-footer"></div>
</div>

<?php include('include/redemptioncode_modals.php'); ?>

<?php if ($this->utils->isEnabledFeature('export_excel_on_queue')) { ?>
    <form id="_export_excel_queue_form" class="hidden" method="POST" target="_blank">
        <input name='json_search' type="hidden">
    </form>
<?php } ?>

<script type="text/javascript">
    $(document).ready(function() {
        $('#collapseSubmenu').addClass('in');
        $('#viewRedemptionCodeSettings').addClass('active');
        $('#redemptionCodeCategoryManager').addClass('active');
        $('#viewRedemptionCodeSettings').on('click', function() {
            $('#viewRedemptionCodeSettings').toggleClass('active');
        });

        $('#addCategoryBtn').click(function() {
            $('#add_category_modal').modal('show');
            $('#add_category_form').each(function() {
                this.reset();
            });
            resetAddCategoryForm();
            $('.addCategoryAlert').hide();
        });

        $('#add_category_modal').on('hidden.bs.modal', function() {
            $(this).data('bs.modal', null);
            resetAddCategoryForm()
        });

        $('#saveAddCategory').click(function(e) {
            e.preventDefault();
            RedemptionCodeType.processAction('add');
        });

        // updateCategory
        $('#updateCategory').click(function(e) {
            e.preventDefault();
            RedemptionCodeType.processAction('edit');
        });

        var header_length = $('#myTable>thead th').length;
        var orderable_targets = (header_length > 9) ? [1, 4, 8, 9] : [1, 4, 8]

        cateTable = $('#myTable').DataTable({
            autoWidth: false,
            searching: true,
            sort: false,

            dom: "<'panel-body' <'pull-right'B><'pull-right'f><'pull-right progress-container'>l><'dt-information-summary1 text-info pull-left' i>t<'panel-body'<'pull-right'p><'dt-information-summary2 text-info pull-left' i>>",
            ordering: true,
            columnDefs: [{
                orderable: false,
                targets: orderable_targets
            }],
            buttons: [{
                text: `<i class="glyphicon glyphicon-refresh"></i>`,
                className: 'btn btn-sm btn-portage',
                action: function(e, dt, node, config) {
                    // dt.clear().draw();
                    dt.ajax.reload();
                }
            }],
            processing: true,
            serverSide: true,
            ajax: function(data, callback, settings) {
                data.extra_search = $('#form-filter').serializeArray();
                $.post(base_url + "api/getRedemptionCodeCategoryList", data, function(data) {
                    // console.log(data);
                    callback(data);
                    initActionsEvent();
                }, 'json');
            },
        });

        var initActionsEvent = function() {
            $(".switch_checkbox").bootstrapSwitch({
                onSwitchChange: function(e, bool) {
                    let data = $(this).data(),
                        isEnable = (bool) ? 1 : 0,
                        category_id = data.category_id;
                    // templateName = data.template_name;
                    let postData = {
                        "is_enable": isEnable,
                        "category_id": category_id
                    }
                    // console.log(postData);

                    let activeMsg = `${!isEnable ? '<?= lang("redemptionCode.categoryDeactive") ?>' : '<?= lang("redemptionCode.categoryActive") ?>'}<?= lang("redemptionCode.redemptionCode") ?>?`;
                    if (confirm(activeMsg) == true) {
                        $.ajax({
                            url: '/marketing_management/updateRedemptionCodeCategoryStatus',
                            method: "POST",
                            data: postData,
                            success: function(data) {
                                // console.log(data);
                                cateTable.ajax.reload();
                            }
                        });
                    } else {
                        return false;
                    }
                }
            });

            $(".editCategoryBtn").on('click', function() {
                let data = $(this).data();
                RedemptionCodeType.initEditTypeForm(data);
            });
            $(".generateCodeBtn").on('click', function() {
                // console.log($(this).closest('tr[role="row"]').find("a.type_name").text());
                let data = $(this).data();
                let category_id = data.category_id;
                let type_name = $(this).closest('tr[role="row"]').find("a.type_name").text();
                $('#generateCategoryId').val(category_id);
                $('#generateCategoryIdByMsg').val(category_id);
                $("#generate_code_modal .code_type_name").text(type_name);
                $('#generate_code_modal').modal('show');
                resetGenerateModal();
            });
            $('.clearCodeBtn').on('click', () => {
                let activeMsg = `<?= lang("redemptionCode.clearCode") ?>?`;
                if (confirm(activeMsg) != true) {
                    return false;
                }
            });
            $('.deleteTypeBtn').on('click', () => {
                let activeMsg = `<?= lang("redemptionCode.deleteType") ?>?`;
                if (confirm(activeMsg) != true) {
                    return false;
                }
            });
        }
        $("#submitGenerateCodeForm").on('click', function(e) {
            e.preventDefault();
            let category_id = $('#generateCategoryId').val();
            let quantity = $('#generateQuantity').val();
            // var form_id = document.getElementById('generate_code_form');
            // var formData = new FormData(form_id);
            showProcess();
            if (!isNaN(category_id)) {
                let postData = {
                    "category_id": category_id,
                    "quantity": quantity
                }
                $.ajax({
                    // url: '/marketing_management/generateRedemptionCode',
                    url: '/marketing_management/generateRedemptionCodeByQueue',
                    method: "POST",
                    data: postData
                }).done((res) => {
                    if (res.status == 'success') {
                        let resultContent = `${res.message}`;

                        if (res?.data?.redriectCodeReport) {

                            resultContent += `
                                            <br>
                                            <a href="${res.data.redriectCodeReport}" target="_blank"><?= lang('redemptionCode.viewCodeList') ?></a>
                                            <br>
                                            <a href="${res.data.redriectGenerateProgress}" target="_blank"><?= lang('redemptionCode.viewGenerateProgress') ?></a>
                                            `;
                        }
                        window.open(res.data.redriectGenerateProgress, '_blank')
                        $('.generate_process_content').show().empty().html(resultContent);
                        $('.generate_process_footer').show();

                    } else {
                        $('.generate_code_form_content').show();
                        $('.generate_code_footer').show();
                        $('.generate_process_footer').hide();
                        $('.generate_process_content').hide().empty();
                        $('#generateQuantityErrorMsg').html(res?.message || 'error').show();
                    }
                }).fail((error) => {
                    console.log(error);
                });
            }
        });

        $('#generate_code_modal').on('hidden.bs.modal', function() {
            cateTable.ajax.reload();
        });

        let showProcess = function() {
            $('.generate_code_form_content').hide();
            $('.generate_code_footer').hide();
            // $('.generate_process_footer').show();
            $('.generate_process_content').show().empty().append('<center id="loader"><img src="' + imgloader + '"></center>').delay(1000);
        }

        let resetGenerateModal = function() {
            $('#generateQuantity').val(1);
            $('#generateQuantityErrorMsg').hide();
            $('.generate_code_form_content').show();
            $('.generate_code_footer').show();
            $('.generate_process_footer').hide();
            $('.generate_process_content').hide().empty();
        }



        $("#player_username").select2({
            ajax: {
                url: '<?php echo site_url('player_management/getPlayerUsernames') ?>',
                dataType: 'json',
                delay: 250,
                data: function(params) {
                    var query = {
                        q: params.term,
                        page: params.page
                    }
                    // Query paramters will be ?search=[term]&page=[page]
                    return query;
                },
                allowClear: true,
                tags: true,
                processResults: function(data, params) {
                    params.page = params.page || 1;
                    return {
                        results: data.items,
                        pagination: {
                            more: (params.page * 30) < data.total_count
                        }
                    };
                },
                cache: true
            },
            escapeMarkup: function(markup) {
                return markup;
            },
            minimumInputLength: 1,
            templateResult: formatOption,
            templateSelection: formatOptionSelection
        });
        $("#clear-member-selection").click(function() {
            clearSelections();
        });

        function formatOption(opt) {
            if (opt.loading) {
                return opt.text;
            } else {
                return opt.username;
            }
        }

        function formatOptionSelection(opt) {
            return opt.username || opt.text;
        }

        function validateSelect2() {
            if (!batchPlayers.length) {
                $(".player-username-help-block").html('<?= lang("system.word38") . lang("lang.is.required") ?>');
            } else {
                $(".player-username-help-block").html('');
            }
        }

        function clearSelections() {
            batchPlayers = Array();
            $("#player_username").val("").trigger("change");
            // updateDatatableCheckbox();
        }

        $("#batch_mail_message_body").summernote({
            height: 300,
            toolbar: [
                ['style', ['style']],
                ['font', ['bold', 'italic', 'underline', 'strikethrough', 'clear']],
                ['fontname', ['fontname']],
                ['fontsize', ['fontsize']],
                ['color', ['color']],
                ['para', ['paragraph']],
                ['view', ['undo', 'redo', 'fullscreen', 'codeview', 'help']]
            ]
        });

        $("#submitGenerateCodeByMessageForm").on('click', function() {
            var summernoteContent = $("#batch_mail_message_body").code();
            var encodeDetails = _pubutils.encode64(encodeURIComponent(summernoteContent));
            var detailsLength = encodeDetails.length;
            $("#contentInput").code(encodeDetails);
            $("#summernoteDetailsLength").val(detailsLength);
            $("#summernoteDetails").val(encodeDetails);
            $("#submitGenerateCodeByMessageForm").submit();
            // return false;
        });

    });
    var cateTable;
    var addNewCodeTypeSuccess = function() {
        resetAddCategoryForm()
        cateTable.ajax.reload();
    }
    var resetAddCategoryForm = function() {
        $('#saveAddCategory').attr('disabled', false);
        $("#categoryName").val('');
        $("#bonus").val(0);
        $("#appEndDate").val(null);
        $("#hideDate").val(null);
        $("#catenote").val('');
        $('#categoryNameErrorMsg').empty().hide();
        $("#withdrawRequirementDepositConditionOption1").attr('checked', true);
        $("#withdrawRequirementBettingConditionOption3").attr('checked', true);
        $("#bonusApplicationLimitDateType").val("<?php echo Promorules::BONUS_APPLICATION_LIMIT_DATE_TYPE_NONE; ?>");
        $("#bonusConditionByApplicationNoLimit").attr('checked', true);
        $("#bonusConditionByApplicationLimitCnt").attr('disabled', true);
    }
    $('#bonusConditionByApplicationWithLimit').on('click', function() {
        $("#bonusConditionByApplicationLimitCnt").attr('disabled', false).val(1);
    });
    $('#bonusConditionByApplicationNoLimit').on('click', function() {
        $("#bonusConditionByApplicationLimitCnt").attr('disabled', true).val(0);
    });
    $('#edit-bonusConditionByApplicationWithLimit').on('click', function() {
        $("#edit-bonusConditionByApplicationLimitCnt").attr('disabled', false).val(1);
    });
    $('#edit-bonusConditionByApplicationNoLimit').on('click', function() {
        $("#edit-bonusConditionByApplicationLimitCnt").attr('disabled', true).val(0);
    });
    var RedemptionCodeType = RedemptionCodeType || {
        withdrawalConditionsDefineds: {
            WITHDRAW_CONDITION_TYPE_BONUS_TIMES: "<?php echo Promorules::WITHDRAW_CONDITION_TYPE_BONUS_TIMES; ?>",
            WITHDRAW_CONDITION_TYPE_FIXED_AMOUNT: "<?php echo Promorules::WITHDRAW_CONDITION_TYPE_FIXED_AMOUNT; ?>",
            WITHDRAW_CONDITION_TYPE_NOTHING: "<?php echo Promorules::WITHDRAW_CONDITION_TYPE_NOTHING; ?>",
            DEPOSIT_CONDITION_TYPE_MIN_LIMIT: "<?php echo Promorules::DEPOSIT_CONDITION_TYPE_MIN_LIMIT; ?>",
            DEPOSIT_CONDITION_TYPE_MIN_LIMIT_SINCE_REGISTRATION: "<?php echo Promorules::DEPOSIT_CONDITION_TYPE_MIN_LIMIT_SINCE_REGISTRATION; ?>",
            DEPOSIT_CONDITION_TYPE_NOTHING: "<?php echo Promorules::DEPOSIT_CONDITION_TYPE_NOTHING; ?>",
        },
        bonusApplicationLimitDefineds: {

            BONUS_APPLICATION_LIMIT_DATE_TYPE_NONE: <?php echo Promorules::BONUS_APPLICATION_LIMIT_DATE_TYPE_NONE; ?>,
            BONUS_APPLICATION_LIMIT_DATE_TYPE_DAILY: <?php echo Promorules::BONUS_APPLICATION_LIMIT_DATE_TYPE_DAILY; ?>,
            BONUS_APPLICATION_LIMIT_DATE_TYPE_WEEKLY: <?php echo Promorules::BONUS_APPLICATION_LIMIT_DATE_TYPE_WEEKLY; ?>,
            BONUS_APPLICATION_LIMIT_DATE_TYPE_MONTHLY: <?php echo Promorules::BONUS_APPLICATION_LIMIT_DATE_TYPE_MONTHLY; ?>,
            BONUS_APPLICATION_LIMIT_DATE_TYPE_YEARLY: <?php echo Promorules::BONUS_APPLICATION_LIMIT_DATE_TYPE_YEARLY; ?>,
        },
        initEditTypeForm: function(elementData) {
            // console.log(elementData);
            let category_id = elementData.category_id;
            let postData = {
                category_id: category_id
            }
            $('.editCategoryAlert').hide();
            if (!isNaN(category_id)) {
                $.ajax({
                    url: '/marketing_management/getRedemptionCodeCategoryDetailByCategoryId',
                    method: "POST",
                    data: postData,
                    success: function(data) {
                        // console.log(data);
                        // "data": {
                        //     "currentCategory": {
                        //         "id": "4",
                        //         "code_name": "y1",
                        //         "withdrawal_rules": {
                        //             "withdrawReqBetAmount": "",
                        //             "withdrawReqBonusTimes": "",
                        //             "withdrawReqDepMinLimit": "",
                        //             "withdrawReqDepMinLimitSinceRegistration": "",
                        //             "withdrawRequirementBettingConditionOption": "2",
                        //             "withdrawRequirementDepositConditionOption": "0"
                        //         },
                        //         "bonus": "8989",
                        //         "created_by": "superadmin",
                        //         "created_at": "2022-05-26 20:06:27",
                        //         "updated_at": "2022-05-30 14:38:59",
                        //         "updated_by": "superadmin",
                        //         "expires_at": "2022-05-26 20:06:27",
                        //         "status": "1",
                        //         "notes": "dddsss",
                        //         "action_logs": "|[2022-05-26 20:06:27]add by superadmin|<\/br>[2022-05-30 14:38:59] superadmin update Status to Active|"
                        //     }
                        // }
                        if (data.success == true) {
                            $('#edit_category_modal').modal('show');

                            let currentCategory = data?.result?.currentCategory;
                            let withdrawRequirement = currentCategory?.withdrawal_rules;

                            $("#edit-categoryName").val(currentCategory?.category_name || '');
                            $("#edit-bonus").val(currentCategory?.bonus || 0);
                            $("#edit-appEndDate").val(currentCategory?.expires_at || null);
                            $("#edit-hideDate").val(currentCategory?.expires_at || null);
                            $("#edit-catenote").val(currentCategory?.notes || '');
                            $("#edit-categoryId").val(currentCategory?.id || 0);

                            if (currentCategory.valid_forever == 1) {
                                $("#edit-isValidForever").prop("checked", true);
                            }

                            if (withdrawRequirement != undefined) {
                                // console.log('found withdrawRequirement');

                                // withdrawal conditions for bet
                                switch (withdrawRequirement?.withdrawRequirementBettingConditionOption) {
                                    case RedemptionCodeType.withdrawalConditionsDefineds.WITHDRAW_CONDITION_TYPE_BONUS_TIMES:
                                        $("#edit-withdrawRequirementBettingConditionOption5").attr('checked', true);
                                        $("#edit-withdrawReqBonusTimes").val(withdrawRequirement?.withdrawReqBonusTimes || 0);
                                        break;

                                    case RedemptionCodeType.withdrawalConditionsDefineds.WITHDRAW_CONDITION_TYPE_FIXED_AMOUNT:
                                        $("#edit-withdrawRequirementBettingConditionOption1").attr('checked', true);
                                        $("#edit-withdrawReqBetAmount").val(withdrawRequirement?.withdrawReqBetAmount || 0);
                                        break;

                                    case RedemptionCodeType.withdrawalConditionsDefineds.WITHDRAW_CONDITION_TYPE_NOTHING:
                                    default:
                                        $("#edit-withdrawRequirementBettingConditionOption3").attr('checked', true);
                                        break;

                                }

                                // withdrawal conditions for deposit
                                switch (withdrawRequirement?.withdrawRequirementBettingConditionOption) {
                                    case RedemptionCodeType.withdrawalConditionsDefineds.DEPOSIT_CONDITION_TYPE_MIN_LIMIT:
                                        $("#edit-withdrawRequirementDepositConditionOption2").attr('checked', true);
                                        $("#edit-withdrawReqDepMinLimit").val(withdrawRequirement?.withdrawReqDepMinLimit || 0);
                                        break;

                                    case RedemptionCodeType.withdrawalConditionsDefineds.DEPOSIT_CONDITION_TYPE_MIN_LIMIT_SINCE_REGISTRATION:
                                        $("#edit-withdrawRequirementDepositConditionOption3").attr('checked', true);
                                        $("#edit-withdrawReqDepMinLimitSinceRegistration").val(withdrawRequirement?.withdrawReqDepMinLimitSinceRegistration || 0);
                                        break;

                                    case RedemptionCodeType.withdrawalConditionsDefineds.DEPOSIT_CONDITION_TYPE_NOTHING:
                                    default:
                                        $("#edit-withdrawRequirementDepositConditionOption1").attr('checked', true);
                                        break;

                                }
                            }
                            if (currentCategory?.application_limit_setting) {
                                let applicationLimitSetting = currentCategory?.application_limit_setting;
                                $("#edit-bonusApplicationLimitDateType").val(applicationLimitSetting.bonusApplicationLimitDateType);
                                (applicationLimitSetting.bonusReleaseTypeOptionByNonSuccessionLimitOption == 0) ?
                                function() {
                                    $("#edit-bonusConditionByApplicationNoLimit").attr('checked', true);
                                    $("#edit-bonusConditionByApplicationLimitCnt").attr('disabled', true).val('');
                                }() :
                                function() {
                                    $("#edit-bonusConditionByApplicationWithLimit").attr('checked', true);
                                    $("#edit-bonusConditionByApplicationLimitCnt").attr('disabled', false).val(applicationLimitSetting.limitCnt);
                                }()
                            }
                            $('#updateCategory').attr('disabled', false);

                        }
                    }
                });
            }
        },
        actionForm: function(_action) {
            $('.addCategoryAlert, .editCategoryAlert').hide();

            this.actionPrefix = (_action == 'edit') ? 'edit-' : '';
            this.currentModal = (_action == 'edit') ? $('#edit_category_modal') : $('#add_category_modal');
            // this.currentModal = $('#manage_type_modal');
            this.formId = (_action == 'edit') ? 'edit_category_form' : 'add_category_form';
            this.currentForm = document.getElementById(this.formId);
            this.formData = new FormData(this.currentForm);
            this.notValidate = false;
            this.categoryNameInput = $(`#${this.actionPrefix}categoryName`);
            this.categoryName = this.categoryNameInput.val();
            this.bonus = $(`#${this.actionPrefix}bonus`).val();
            this.appEndDate = $(`#${this.actionPrefix}hideDate`).val();
            this.isValidForever = $(`#${this.actionPrefix}isValidForever`).prop("checked");
            this.categoryNameAlert = $(`#${this.actionPrefix}categoryNameAlert`);
            this.categoryNameErrorMsg = $(`#${this.actionPrefix}categoryNameAlert`);
            this.bonusAlert = $(`#${this.actionPrefix}bonusAlert`);
            this.appEndDateAlert = $(`#${this.actionPrefix}appEndDateAlert`);
            this.bonusApplicationLimitDateType = $(`#${this.actionPrefix}bonusApplicationLimitDateType`);
            this.bonusConditionByApplicationWithLimit = $(`#${this.actionPrefix}bonusConditionByApplicationWithLimit`);
            this.bonusConditionByApplicationNoLimit = $(`#${this.actionPrefix}bonusConditionByApplicationNoLimit`);
            this.bonusConditionByApplicationLimitCnt = $(`#${this.actionPrefix}bonusConditionByApplicationLimitCnt`);
            this.submitBtn = (_action == 'edit') ? $('#updateCategory') : $('#saveAddCategory');
            this.actionURL = (_action == 'edit') ? 'marketing_management/updateRedemptionCodeCategory' : 'marketing_management/addRedemptionCodeCategory';
        },
        processAction: function(_action) {
            var submitFrom = new RedemptionCodeType.actionForm(_action);
            window.__submitFrom = submitFrom;
            console.log(submitFrom);
            if (!submitFrom.categoryName.trim()) {
                submitFrom.categoryNameAlert.show().html(`*<?= lang('Required'); ?>`);
                submitFrom.notValidate = true;
            }

            if (submitFrom.categoryName.trim().length > 50) {
                submitFrom.categoryNameAlert.show().html(`*<?= lang('Maximum 50 characters (including spaces).'); ?>`);
                submitFrom.notValidate = true;
            }
            if (submitFrom.bonus <= 0) {
                submitFrom.bonusAlert.show();
                submitFrom.notValidate = true;
            }
            if (submitFrom.appEndDate == '' && submitFrom.isValidForever == false) {
                submitFrom.appEndDateAlert.show();
                submitFrom.notValidate = true;
            }

            if (submitFrom.bonusConditionByApplicationWithLimit[0].checked && 0 >= submitFrom.bonusConditionByApplicationLimitCnt.val()) {
                $('.limitCntAlert').show();
                submitFrom.notValidate = true;
            }

            switch (submitFrom.formData.get('withdrawRequirementBettingConditionOption')) {
                case RedemptionCodeType.withdrawalConditionsDefineds.WITHDRAW_CONDITION_TYPE_BONUS_TIMES:
                    if (0 >= $(`#${submitFrom.actionPrefix}withdrawReqBonusTimes`).val()) {
                        $('.withdrawReqBonusTimes').show();
                        submitFrom.notValidate = true;
                    }
                    break;

                case RedemptionCodeType.withdrawalConditionsDefineds.WITHDRAW_CONDITION_TYPE_FIXED_AMOUNT:
                    if (0 >= $(`#${submitFrom.actionPrefix}withdrawReqBetAmount`).val()) {
                        $('.withdrawReqBetAmount').show();
                        submitFrom.notValidate = true;
                    }
                    break;
            }

            if (submitFrom.notValidate) {
                return false;
            } else {
                submitFrom.submitBtn.attr('disabled', true);
                submitFrom.categoryNameInput.val(submitFrom.categoryName.trim());
                $.ajax({
                    'url': base_url + submitFrom.actionURL,
                    'type': 'POST',
                    'dataType': "json",
                    'data': submitFrom.formData,
                    'cache': false,
                    'contentType': false,
                    'processData': false,
                    'success': function(data) {
                        if (data.success) {
                            $('#process-type-modal').modal('show');
                            $('#successMsg').text(data.successMsg);
                            submitFrom.currentModal.modal('hide');
                        } else {
                            submitFrom.submitBtn.attr('disabled', false);
                            switch (data.noteType) {
                                case 'name':
                                    submitFrom.categoryNameErrorMsg.text(data.errorMsg).show();
                                    break;
                            }
                        }
                    }
                });
            }
        }
    }
</script>