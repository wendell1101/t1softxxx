<style>
    .action-item {
        margin-bottom: 0.5rem;
    }
</style>
<div class="panel panel-primary">
    <div class="panel-heading">
        <h4 class="panel-title">
            <i class="fa fa-search"></i> <?= lang('lang.search')?><span class="pull-right">
                <a data-toggle="collapse" href="#collapseViewUsers" class="btn btn-info btn-xs" aria-expanded="false"></a>
            </span>
        </h4>
    </div>
    <div id="collapseViewUsers" class="panel-collapse collapse in" aria-expanded="false">
        <div class="panel-body">
            <form class="redemptionCodeCategoryManagerSearch" method="get" id="search_form" autocomplete="off" role="form">
                    <div class="form-group col-md-3 col-lg-3">
                        <label class="control-label">
                            <?= lang('Date'); ?>
                        </label>
                        <input id="search_payment_date" class="form-control input-sm dateInput user-success" data-start="#by_date_from" data-end="#by_date_to" data-time="false" autocomplete="off" />
                        <input type="hidden" id="by_date_from" name="by_date_from" value="<?=$conditions['by_date_from'];?>" />
                        <input type="hidden" id="by_date_to" name="by_date_to" value="<?=$conditions['by_date_to'];?>" />
                    </div>
                    <div class="form-group col-md-3 col-lg-3">
                        <label for="" class="control-lable">
                            <?=lang('redemptionCode.redemptionCode');?>
                        </label>
                        <input type="text" class="form-control input-sm" name="redemption_code" value="<?=$conditions['redemption_code']?>" />
                    </div>    
                    <div class="form-group col-md-3 col-lg-3">
                        <label for="" class="control-lable">
                            <?=lang('redemptionCode.status');?>
                        </label>
                        <select name="codeStatus" id="codeStatus" class="form-control input-sm user-success">
                                <?php foreach ($codeStatus_options as $option_value => $option_lang) : ?>
                                    <option value="<?= $option_value ?>" <?= $conditions['codeStatus'] == $option_value ? 'selected' : '' ?>><?= $option_lang ?></option>
                                <?php endforeach; ?>
                            </select>
                    </div>  
            </form>
        </div>

        <div class="panel-footer">
            <div class="text-center">
                <!-- <button class="btn btn-sm btn-linkwater" type="reset" form="search_form">Reset</button> -->
                <button class="btn btn-sm btn-portage" type="submit" form="search_form"><i class="fa fa-search"></i> Search</button>
            </div>
        </div>
    </div>
</div>
<div class="panel panel-primary">
    <div class="panel-heading">
        <h4 class="panel-title">
            <i class="icon-note"></i> <?= lang('redemptionCode.static.redemptionCodeCategoryList'); ?>
            <?php if ($manage_static_redemption_code_category) : ?>
                <a href="javascript:void(0);" class="btn  pull-right btn-xs btn-info" id="addCategoryBtn">
                    <i class="fa fa-plus-circle"></i> <?= lang('redemptionCode.generateRedemptionCode'); ?>
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
                        <th><?= lang('redemptionCode.redemptionCode'); ?></th>
                        <th><?= lang('redemptionCode.totalRedeemable'); ?></th>
                        <!-- <th><?= lang('redemptionCode.used_quantity'); ?></th> -->
                        <!-- <th><?= lang('redemptionCode.left_quantity'); ?></th> -->
                        <th><?= lang('redemptionCode.bonusRule'); ?></th>
                        <th><?= lang('redemptionCode.applyLimit'); ?></th>
                        <th><?= lang('redemptionCode.withdraw_condition'); ?></th>
                        <th><?= lang('redemptionCode.create_at'); ?></th>
                        <th><?= lang('redemptionCode.apply_expire_time'); ?></th>
                        <!-- <th><?= lang('redemptionCode.allow_duplicate_apply'); ?></th> -->
                        <th><?= lang('redemptionCode.status'); ?></th>
                        <th><?= lang('redemptionCode.note'); ?></th>
                        <!-- <th><?= lang('redemptionCode.action_logs'); ?></th> -->
                        <?php if ($manage_static_redemption_code_category) : ?>
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

<?php include('include/static_redemptioncode_modals.php'); ?>

<?php if ($this->utils->isEnabledFeature('export_excel_on_queue')) { ?>
    <form id="_export_excel_queue_form" class="hidden" method="POST" target="_blank">
        <input name='json_search' type="hidden">
    </form>
<?php } ?>

<script type="text/javascript">
    var hasActionPermission = <?= $manage_static_redemption_code_category ? 'true' : 'false' ?>;
    var sendRequest = 0;
    $(document).ready(function() {
        $('#collapseSubmenu').addClass('in');
        $('#viewRedemptionCodeSettings').addClass('active');
        $('#staticRedemptionCodeCategoryManager').addClass('active');
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
            if($("[name$='redemptionCode']", $('#add_category_modal')).length > 0){
                randomCodeWithAjax('',function(redeemCode){
                    $("[name$='redemptionCode']", $('#add_category_modal')).val(redeemCode);
                });
            }
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
        var orderable_targets = [];//(header_length > 9) ? [1, 5, 9, 10] : [1, 5, 9]
        var actionColumnIndex = hasActionPermission ? header_length - 1 : -1;

        cateTable = $('#myTable').DataTable({
            autoWidth: false,
            searching: true,
            sort: false,

            dom: "<'panel-body' <'pull-right'B><'pull-right'f><'pull-right progress-container'>l><'dt-information-summary1 text-info pull-left' i>t<'panel-body'<'pull-right'p><'dt-information-summary2 text-info pull-left' i>>",
            ordering: true,
            order: [[0, 'desc']],
            columns: [
                { data: "categoryId" },
                { data: "categoryName", orderable: false, },
                { data: "redemptionCode", orderable: false, },
                { data: "totalRedeemable", orderable: false, },
                { 
                    data: "bonusRule", orderable: false,
                    render: function (data, type, row, meta) {
                        let bonusRule = row['bonusRule'];//row['4'];
                        bonusRule = bonusRule.replace(/&quot;/g, '"');
                        let ruleJson = JSON.parse(bonusRule);
                        return generateBonusRuleContent(ruleJson);
                    }
                },
                { 
                    data: "applyLimit", orderable: false,
                    render: function (data, type, row, meta) {
                        let applyLimit = row['applyLimit'];//row['5'];
                        applyLimit = applyLimit.replace(/&quot;/g, '"');
                        let ruleJson = JSON.parse(applyLimit);
                        return generateApplicationLimitContent(ruleJson);
                    }
                },
                { data: "withdrawal_rules", orderable: false, },
                { data: "created_at" },
                { data: "expires_at" },
                { 
                    data: "status", orderable: false,
                    render: function (data, type, row, meta) {
                        const staus_map = {
                            "<?php echo Static_redemption_code_model::CATEGORY_STATUS_ACTIVATED; ?>": `<p class="text-success"><i class="glyphicon glyphicon-ok-circle"></i> <?=lang('redemptionCode.categoryActive')?></p>`,
                            "<?php echo Static_redemption_code_model::CATEGORY_STATUS_DEACTIVATE; ?>": `<p class="text-danger"><i class="glyphicon glyphicon-ban-circle"></i> <?=lang('redemptionCode.categoryDeactive')?></p>`,
                        };
                        return staus_map[data] || '';
                    }
                },
                { data: "notes", orderable: false, },
            ],
            columnDefs: [
                {
                    targets: actionColumnIndex
                    , render: function (data, type, row, meta) {
                        if(actionColumnIndex != -1) {
                            return generateActionContent(row['status'], row['categoryId'], row);
                        }
                        return '';
                    }
                }
            ],
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
                data.extra_search = $('#search_form').serializeArray();
                $.post(base_url + "api/getStaticRedemptionCodeCategoryList", data, function(data) {
                    // console.log(data);
                    callback(data);
                    initActionsEvent();
                }, 'json');
            },
            "fnDrawCallback": function(oSeetings) {
                let inlineCountElements = document.querySelectorAll('div.inlinecount[data-catdid]');
                inlineCountElements.forEach((element) => {
                    const catdid = element.getAttribute('data-catdid');
                    let fetchUrl = '/api/getRedemptionCount/' + catdid;
                    fetch(fetchUrl)
                        .then((response) => response.json())
                        .then((data) => {
                            let countTotal = element.querySelector('.countTotal');
                            let left = element.querySelector('.left');
                            countTotal.textContent = data.countTotal;
                            left.textContent = data.left;
                        })
                        .catch((error) => {
                        // Handle errors
                        });
                });
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

                    let activeMsg = `${!isEnable ? '<?= lang("redemptionCode.categoryDeactive") ?> ' : '<?= lang("redemptionCode.categoryActive") ?> '}<?= lang("redemptionCode.redemptionCode") ?>?`;
                    if (confirm(activeMsg) == true) {
                        $.ajax({
                            url: '/marketing_management/updateStaticRedemptionCodeCategoryStatus',
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
                    url: '/marketing_management/generateStaticRedemptionCodeByQueue',
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

        $('#generate_code_modal, #process-type-modal').on('hidden.bs.modal', function() {
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
                data: function (params) {
                    var query = {
                        q: params.term,
                        page: params.page
                    }
                    // Query paramters will be ?search=[term]&page=[page]
                    return query;
                },
                allowClear: true,
                tags: true,
                processResults: function (data, params) {
                    params.page = params.page || 1;
                    return {
                        results: data.items,
                        pagination: {
                            more: (params.page *30) < data.total_count
                        }
                    };
                },
                cache: true
            },
            escapeMarkup: function (markup) { return markup; },
            minimumInputLength: 1,
            templateResult: formatOption,
            templateSelection: formatOptionSelection
        });
        $("#clear-member-selection").click(function(){
            clearSelections();
        });
        function formatOption (opt) {
            if (opt.loading){
                return opt.text;
            } else{
                return opt.username;
            }
        }

        function formatOptionSelection (opt) {
            return opt.username || opt.text;
        }

        function validateSelect2(){
            if(!batchPlayers.length){
              $(".player-username-help-block").html('<?=lang("system.word38").lang("lang.is.required")?>');
            } else {
               $(".player-username-help-block").html('');
            }
        }

        function clearSelections(){
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

        $("#submitGenerateCodeByMessageForm").on('click', function () {
            var summernoteContent = $("#batch_mail_message_body").code();
            var encodeDetails = _pubutils.encode64(encodeURIComponent(summernoteContent));
            var detailsLength = encodeDetails.length;
            $("#contentInput").code(encodeDetails);
            $("#summernoteDetailsLength").val(detailsLength);
            $("#summernoteDetails").val(encodeDetails);
            $("#submitGenerateCodeByMessageForm").submit();
            // return false;
        });

        $("#allowed_clear_player_select").click(function() {
            $("#addPlayers").val('')
            $("#addPlayers").trigger("change");
        });

    });
    $(function() {

        $(".select2-player-list-ajax").select2({
            placeholder: '<?=lang('Select new applicable players')?>',
            ajax: {
                url: '/payment_account_management/players',
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return {
                        q: params.term,
                    };
                },
                processResults: function (data, params) {
                    return {
                        results: data.items,
                    };
                },
                cache: true
            },
            templateResult: function (option) {
                return option.text;
            },
            templateSelection: function (option) {
                return option.text;
            },
            minimumInputLength: 3,
        });

        $(".select2-affiliate-list-ajax").select2({
            placeholder: '<?=lang('Select new applicable affiliates')?>',
            ajax: {
                url: '/marketing_management/ajaxAffiliates',
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return {
                        q: params.term,
                    };
                },
                processResults: function (data, params) {
                    return {
                        results: data.items,
                    };
                },
                cache: true
            },
            templateResult: function (option) {
                return option.text;
            },
            templateSelection: function (option) {
                return option.text;
            },
            minimumInputLength: 3,
        });

        $('.select2-player-level-list-ajax').multiselect({//
            enableFiltering: true,
            enableCaseInsensitiveFiltering: true,
            includeSelectAllOption: true,
            selectAllJustVisible: false,
            buttonWidth: '100%',
            buttonClass: 'btn btn-sm btn-default',
            buttonText: function(options, select) {
                if (options.length === 0) {
                    return '<?=lang('Select Player Level');?>';
                } else {
                    var labels = [];
                    options.each(function() {
                        if ($(this).attr('label') !== undefined) {
                            labels.push($(this).attr('label'));
                        }
                        else {
                            labels.push($(this).html());
                        }
                    });
                    return labels.join(', ') + '';
                }
            }
        });
    });

    /*
    ruleJson = {
        "bonus": 0,
        "bonusCap": "20",
        "bonusReleaseTypeOption": "3",
        "nonfixedBonusMaxAmount": "10",
        "nonfixedBonusMinAmount": "1"
    }
    */ 
    const generateBonusRuleContent = function(ruleJson){
        let content = '';
        if(ruleJson){
            let bonusReleaseTypeOption =ruleJson.bonusReleaseTypeOption;
            if(bonusReleaseTypeOption == RedemptionCodeType.bonusConditionsDefineds.BONUS_CONDITION_TYPE_FIXED_AMOUNT){

                content = `<?=lang('cms.fixedBonusAmount')?>: ${ruleJson.bonus || 0}`;
            } else if(bonusReleaseTypeOption == RedemptionCodeType.bonusConditionsDefineds.BONUS_CONDITION_TYPE_NON_FIXED_AMOUNT){

                let bonusMin = ruleJson.nonfixedBonusMinAmount || '';
                let bonusMax = ruleJson.nonfixedBonusMaxAmount || '';
                let bonusCap = ruleJson.bonusCap || '';
                content = `<?=lang('redemptionCode.bonus')?>: <?=lang('Min')?>: ${bonusMin} <?=lang('to')?>: <?=lang('Max')?>: ${bonusMax} <br>` + `<?=lang('redemptionCode.bonusCap')?>: ${bonusCap}`;
            }
            let sdStr = '';
            if(ruleJson.enableSameDayDeposit === true){

                let sameDayDepositAmount = ruleJson.sameDayDepositAmount || 0;
                sdStr = `<br><?=lang('redemptionCode.sameDayDeposit')?>: >= ${sameDayDepositAmount}`;
                content += sdStr;
            }
            let pdStr = '';
            if(ruleJson.enablePastDayDeposit === true){

                let pastDayDepositDays = ruleJson.pastDayDepositDays || 0;
                let pastDayDepositAmount = ruleJson.pastDayDepositAmount || 0;
                pdStr = `<br><?=lang('redemptionCode.pastDayDeposit')?>: ${pastDayDepositDays} / <?=lang("lang.daily")?> <?=lang("Amount")?> >= ${pastDayDepositAmount}`;
                content += pdStr;
            }
            let pdtStr = '';
            if(ruleJson.enablePastDaysTotalDeposit === true){

                let pastDaysTotalDeposit = ruleJson.pastDaysTotalDeposit || 0;
                let pastDaysTotalDepositAmount = ruleJson.pastDaysTotalDepositAmount || 0;
                pdtStr = `<br><?=lang('redemptionCode.pastDaysTotalDeposit')?>: ${pastDaysTotalDeposit} / <?=lang("redemptionCode.pastDaysTotalDeposit.amount")?> >= ${pastDaysTotalDepositAmount}`;
                content += pdtStr;
            }
        }
            
        return content;
    }

    const generateApplicationLimitContent = function(ruleJson){
        let content = '';
        if(ruleJson){
            let bonusApplicationLimit = ruleJson.bonusApplicationLimit
            let bonusApplicationLimitDateType = parseInt(bonusApplicationLimit.bonusApplicationLimitDateType);
            let bonusApplicationLimitDateTypeStr = '';
            
            switch(bonusApplicationLimitDateType){
                case RedemptionCodeType.bonusApplicationLimitDefineds.BONUS_APPLICATION_LIMIT_DATE_TYPE_DAILY:
                    bonusApplicationLimitDateTypeStr = '<?=lang('Daily')?>';
                    break;
                case RedemptionCodeType.bonusApplicationLimitDefineds.BONUS_APPLICATION_LIMIT_DATE_TYPE_WEEKLY:
                    bonusApplicationLimitDateTypeStr = '<?=lang('Weekly')?>';
                    break;
                case RedemptionCodeType.bonusApplicationLimitDefineds.BONUS_APPLICATION_LIMIT_DATE_TYPE_MONTHLY:
                    bonusApplicationLimitDateTypeStr = '<?=lang('Monthly')?>';
                    break;
                case RedemptionCodeType.bonusApplicationLimitDefineds.BONUS_APPLICATION_LIMIT_DATE_TYPE_YEARLY:
                    bonusApplicationLimitDateTypeStr = '<?=lang('Yearly')?>';
                    break;
                case RedemptionCodeType.bonusApplicationLimitDefineds.BONUS_APPLICATION_LIMIT_DATE_TYPE_NONE:
                default:
                    bonusApplicationLimitDateTypeStr = '';
                    // bonusApplicationLimitDateTypeStr = '<?=lang('cms.noLimit')?>';
                    break;
            }

            content = `${bonusApplicationLimitDateTypeStr} <?=lang('cms.withLimit')?>  ${bonusApplicationLimit.limitCnt || 0}`;
        }
        return content;
    }

    const generateActionContent = function(status, categoryId, rowData){
        const isExport = false;
        let isActive = status === RedemptionCodeType.categoryStatusDefineds.CATEGORY_STATUS_ACTIVATED;
        if (!isExport) {
            let content = '';
            const switchBtnTemplate = `<div class="action-item active-btn">
                                            <input type="checkbox" class="switch_checkbox"
                                                    data-on-text="<?=lang('redemptionCode.categoryActive')?>"
                                                    data-off-text="<?=lang('redemptionCode.categoryDeactive')?>"
                                                    data-category_id="${categoryId}"
                                                    ${isActive ? 'checked' : ''}
                                            />
                                        </div>`;

            let manageBtnGroup = '';
            let isUsed = rowData?.isUsed || false;
            if(!isActive){
                let editBtnTemplate = `<div class="action-item">
                                            <a class="btn btn-scooter btn-xs editCategoryBtn" href="javascript:void(0);" data-category_id="${categoryId}"><i class="glyphicon glyphicon-cog"></i> <?=lang('Edit')?></a>
                                        </div>`;
    
                if (isUsed) {
                    editBtnTemplate = '';
                    const enableEdit = `<?=$this->utils->getConfig('enable_static_redemption_code_edit')  ? 'true' : 'false' ?>`;
                    if (enableEdit === 'true') {
                        editBtnTemplate = `<div class="action-item">
                                                <a class="btn btn-scooter btn-xs editCategoryBtn" href="javascript:void(0);" data-category_id="${categoryId}"><i class="glyphicon glyphicon-cog"></i> <?=lang('Edit')?></a>
                                            </div>`;
                    }
                }
    
                const deleteBtnTemplate = `<div class="action-item">
                                                <a class="btn btn-danger btn-xs clearCodeBtn" href="/marketing_management/ClearUnusingStaticCodeByCateId/${categoryId}" data-category_id="${categoryId}"><i class="glyphicon glyphicon-remove"></i> <?=lang('redemptionCode.clearCode')?></a>
                                            </div>`;
    
                const deleteTypeBtnTemplate = `<div class="action-item">
                                                    <a class="btn btn-danger btn-xs deleteTypeBtn" href="/marketing_management/deleteTypeAndClearUnusingStaticCode/${categoryId}" data-category_id="${categoryId}"><i class="glyphicon glyphicon-remove"></i> <?=lang('redemptionCode.deleteType')?></a>
                                                </div>`;
    
                manageBtnGroup = `<hr>${editBtnTemplate}${deleteBtnTemplate}${deleteTypeBtnTemplate}`;
            }

            const messagesToPlayers = `<div class="action-item">
                                            <a class="btn btn-linkwater btn-xs generateCodeBtn" href="javascript:void(0);" data-category_id="${categoryId}"><i class="glyphicon glyphicon-plus"></i> <?=lang('redemptionCode.messagesToPlayers')?></a>
                                        </div>`;

            content += switchBtnTemplate + messagesToPlayers + manageBtnGroup;

            return content;
        }
    }

    /**
     * random generated code with ajax.
     * @param string  redeemCode The redeem code.
     * @param function doneCB    The callback function.
     */
    function randomCodeWithAjax(redeemCode, doneCB){

        if( typeof(doneCB) === 'undefined'){
            doneCB = function(){};
        }
        targetUrl = _site_url + 'marketing_management/getNewRedeemCode/' + redeemCode;

        $('.promocode-loading').removeClass('hide');

        var ajax = $.ajax({
            'url': targetUrl,
            'type': 'GET',
            'dataType': "json",
            'success': function(data){
                if(data.status == 'ok'){
                    doneCB(data.newCode);
                }
            }
        }).always(function(){
            $('.promocode-loading').addClass('hide');
        });
        return ajax;
    }// EOF randomCodeWithAjax

    var cateTable;
    var addNewCodeTypeSuccess = function() {
        resetAddCategoryForm();
        cateTable.ajax.reload();
    }
    var resetAddCategoryForm = function() {
        $('#saveAddCategory').prop('disabled', false);
        $("#categoryName").val('');
        $("#bonus").prop('disabled', false).val(0);
        $("#appEndDate").val(null);
        $("#hideDate").val(null);
        $("#catenote").val('');
        $('#categoryNameErrorMsg').empty().hide();
        $("#withdrawRequirementDepositConditionOption1").prop('checked', true);
        $("#withdrawRequirementBettingConditionOption3").prop('checked', true);
        $("#bonusApplicationLimitDateType").val("<?php echo Promorules::BONUS_APPLICATION_LIMIT_DATE_TYPE_NONE; ?>");
        // $("#bonusConditionByApplicationNoLimit").prop('checked', true);
        $("#bonusConditionByApplicationWithLimit").prop('checked', true);
        $("#bonusConditionByApplicationLimitCnt").val(1);
        $("#bonusReleaseTypeFixedAmount").prop('checked', true);
        $("#nonfixedBonusMinAmount").prop('disabled', true);
        $("#nonfixedBonusMaxAmount").prop('disabled', true);
        $("#bonusCap").prop('disabled', true);
        $("#totalRedeemable").val(1);

        if($("[name$='enableSameDayDeposit']", $('#add_category_modal')).length > 0){
            $("[name$='enableSameDayDeposit']", $('#add_category_modal')).prop('checked', false);
            $("[name$='sameDayDepositAmount']", $('#add_category_modal')).val(0);
        }
        if($("[name$='enablePastDayDeposit']", $('#add_category_modal')).length > 0){
            $("[name$='enablePastDayDeposit']", $('#add_category_modal')).prop('checked', false);
            $("[name$='pastDayDepositDays']", $('#add_category_modal')).val(0);
            $("[name$='pastDayDepositAmount']", $('#add_category_modal')).val(0);
        }
        if($("[name$='enablePastDaysTotalDeposit']", $('#add_category_modal')).length > 0){
            $("[name$='enablePastDaysTotalDeposit']", $('#add_category_modal')).prop('checked', false);
            $("[name$='pastDaysTotalDeposit']", $('#add_category_modal')).val(0);
            $("[name$='pastDaysTotalDepositAmount']", $('#add_category_modal')).val(0);
        }

        $("#add-affiliates").val('');
        $("#add-affiliates").trigger("change");

        $("#add-players").val('');
        $("#add-players").trigger("change");

        $('#add-player-levels').multiselect('deselectAll', false);
        $('#add-player-levels').multiselect('updateButtonText');
    }
    $('#bonusConditionByApplicationWithLimit').on('click', function() {
        $("#bonusConditionByApplicationLimitCnt").prop('disabled', false).val(1);
    });
    $('#bonusConditionByApplicationNoLimit').on('click', function() {
        $("#bonusConditionByApplicationLimitCnt").prop('disabled', true).val(0);
    });
    $('#edit-bonusConditionByApplicationWithLimit').on('click', function() {
        $("#edit-bonusConditionByApplicationLimitCnt").prop('disabled', false).val(1);
    });
    $('#edit-bonusConditionByApplicationNoLimit').on('click', function() {
        $("#edit-bonusConditionByApplicationLimitCnt").prop('disabled', true).val(0);
    });

    $('#bonusReleaseTypeFixedAmount').on('click', function() {
        $("#nonfixedBonusMinAmount").prop('disabled', true).val('');
        $("#nonfixedBonusMaxAmount").prop('disabled', true).val('');
        $("#bonusCap").prop('disabled', true).val('');
        $("#bonus").prop('disabled', false).val(0);
    });

    $('#bonusReleaseTypeNonFixedAmount').on('click', function() {
        $("#nonfixedBonusMinAmount").prop('disabled', false).val('');
        $("#nonfixedBonusMaxAmount").prop('disabled', false).val('');
        $("#bonusCap").prop('disabled', false).val('');
        $("#bonus").prop('disabled', true).val(0);
    });

    $('#edit-bonusReleaseTypeFixedAmount').on('click', function() {
        $("#edit-nonfixedBonusMinAmount").prop('disabled', true).val('');
        $("#edit-nonfixedBonusMaxAmount").prop('disabled', true).val('');
        $("#edit-bonusCap").prop('disabled', true).val('');
        $("#edit-bonus").prop('disabled', false).val(0);
    });
    $('#edit-bonusReleaseTypeNonFixedAmount').on('click', function() {
        $("#edit-nonfixedBonusMinAmount").prop('disabled', false).val('');
        $("#edit-nonfixedBonusMaxAmount").prop('disabled', false).val('');
        $("#edit-bonusCap").prop('disabled', false).val('');
        $("#edit-bonus").prop('disabled', true).val(0);
    });


    $('.random-code', $('#edit_category_modal')).click(function(){
        randomCodeWithAjax('',function(redeemCode){
            $("[name$='redemptionCode']", $('#edit_category_modal')).val(redeemCode);
        });
    });


    $('.random-code', $('#add_category_modal')).click(function(){
        randomCodeWithAjax('',function(redeemCode){
            $("[name$='redemptionCode']", $('#add_category_modal')).val(redeemCode);
        });
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
        bonusConditionsDefineds: {
            BONUS_CONDITION_TYPE_FIXED_AMOUNT: "<?php echo Promorules::BONUS_RELEASE_RULE_FIXED_AMOUNT; ?>",
            BONUS_CONDITION_TYPE_NON_FIXED_AMOUNT: "<?php echo Promorules::BONUS_RELEASE_RULE_CUSTOM; ?>",
        },
        categoryStatusDefineds: {
            CATEGORY_STATUS_ACTIVATED: "<?php echo Static_redemption_code_model::CATEGORY_STATUS_ACTIVATED; ?>",
            CATEGORY_STATUS_DEACTIVATED: "<?php echo Static_redemption_code_model::CATEGORY_STATUS_DEACTIVATE; ?>",
        },
        initEditTypeForm: function(elementData) {
            sendRequest += 1;
            // console.log(elementData);
            let category_id = elementData.category_id;
            let postData = {
                category_id: category_id
            }
            $('.editCategoryAlert').hide();
            $("#edit-affiliates").val('');
            $("#edit-affiliates").trigger("change");

            $("#edit-players").val('');
            $("#edit-players").trigger("change");

            $('#edit-player-levels').multiselect('deselectAll', false);
            $('#edit-player-levels').multiselect('updateButtonText');

            $('.select2-hidden-accessible').empty();
            if (!isNaN(category_id) && sendRequest === 1) {
                $.ajax({
                    url: '/marketing_management/getStaticRedemptionCodeCategoryDetailByCategoryId',
                    method: "POST",
                    data: postData,
                    success: function(data) {
                        // console.log(data);
                        if (data.success == true) {
                            let currentCategory = data?.result?.currentCategory;
                            let withdrawRequirement = currentCategory?.withdrawal_rules;
                            let bonusCondition = currentCategory?.bonus_rules;

                            $("#edit-categoryName").val(currentCategory?.category_name || '');
                            $("#edit-bonus").val(currentCategory?.bonus || 0);
                            $("#edit-appEndDate").val(currentCategory?.expires_at || null);
                            $("#edit-hideDate").val(currentCategory?.expires_at || null);
                            $("#edit-catenote").val(currentCategory?.notes || '');
                            $("#edit-categoryId").val(currentCategory?.id || 0);
                            $("[name$='redemptionCode']", $('#edit_category_modal')).val(currentCategory?.redemption_code || '');
                            $("#edit-totalRedeemable").val(currentCategory?.total_redeemable_count || 0);

                            if (currentCategory.valid_forever == 1) {
                                $("#edit-isValidForever").prop("checked", true);
                            }

                            if (bonusCondition != undefined) {
                                switch (bonusCondition?.bonusReleaseTypeOption) {
                                    case RedemptionCodeType.bonusConditionsDefineds.BONUS_CONDITION_TYPE_NON_FIXED_AMOUNT:
                                        $("#edit-bonusReleaseTypeNonFixedAmount").prop('checked', true);
                                        $("#edit-nonfixedBonusMinAmount").val(bonusCondition?.nonfixedBonusMinAmount || 0).prop('disabled', false);
                                        $("#edit-nonfixedBonusMaxAmount").val(bonusCondition?.nonfixedBonusMaxAmount || 0).prop('disabled', false);
                                        $("#edit-bonusCap").val(bonusCondition?.bonusCap || 0).prop('disabled', false);
                                        $("#edit-bonus").prop('disabled', true).val(0);
                                        break;
                                    case RedemptionCodeType.bonusConditionsDefineds.BONUS_CONDITION_TYPE_FIXED_AMOUNT:
                                    default:
                                        $("#edit-bonusReleaseTypeFixedAmount").prop('checked', true);
                                        $("#edit-nonfixedBonusMinAmount").prop('disabled', true).val('');
                                        $("#edit-nonfixedBonusMaxAmount").prop('disabled', true).val('');
                                        $("#edit-bonusCap").prop('disabled', true).val('');
                                        $("#edit-bonus").val(bonusCondition?.bonus || 0).prop('disabled', false);
                                        break;
                                }

                                if($("[name$='enableSameDayDeposit']", $('#edit_category_modal')).length > 0 && bonusCondition.enableSameDayDeposit == true){
                                    $("[name$='enableSameDayDeposit']", $('#edit_category_modal')).prop('checked', true);
                                    $("[name$='sameDayDepositAmount']", $('#edit_category_modal')).val(bonusCondition.sameDayDepositAmount || 0);
                                }
                                if($("[name$='enablePastDayDeposit']", $('#edit_category_modal')).length > 0 && bonusCondition.enablePastDayDeposit == true){
                                    $("[name$='enablePastDayDeposit']", $('#edit_category_modal')).prop('checked', true);
                                    $("[name$='pastDayDepositDays']", $('#edit_category_modal')).val(bonusCondition.pastDayDepositDays || 0);
                                    $("[name$='pastDayDepositAmount']", $('#edit_category_modal')).val(bonusCondition.pastDayDepositAmount || 0);
                                }
                                if($("[name$='enablePastDaysTotalDeposit']", $('#edit_category_modal')).length > 0 && bonusCondition.enablePastDaysTotalDeposit == true){
                                    $("[name$='enablePastDaysTotalDeposit']", $('#edit_category_modal')).prop('checked', true);
                                    $("[name$='pastDaysTotalDeposit']", $('#edit_category_modal')).val(bonusCondition.pastDaysTotalDeposit || 0);
                                    $("[name$='pastDaysTotalDepositAmount']", $('#edit_category_modal')).val(bonusCondition.pastDaysTotalDepositAmount || 0);
                                }
                            }

                            if (withdrawRequirement != undefined) {
                                // console.log('found withdrawRequirement');
                                // withdrawal conditions for bet
                                switch (withdrawRequirement?.withdrawRequirementBettingConditionOption) {
                                    case RedemptionCodeType.withdrawalConditionsDefineds.WITHDRAW_CONDITION_TYPE_BONUS_TIMES:
                                        $("#edit-withdrawRequirementBettingConditionOption5").prop('checked', true);
                                        $("#edit-withdrawReqBonusTimes").val(withdrawRequirement?.withdrawReqBonusTimes || 0);
                                        break;

                                    case RedemptionCodeType.withdrawalConditionsDefineds.WITHDRAW_CONDITION_TYPE_FIXED_AMOUNT:
                                        $("#edit-withdrawRequirementBettingConditionOption1").prop('checked', true);
                                        $("#edit-withdrawReqBetAmount").val(withdrawRequirement?.withdrawReqBetAmount || 0);
                                        break;

                                    case RedemptionCodeType.withdrawalConditionsDefineds.WITHDRAW_CONDITION_TYPE_NOTHING:
                                    default:
                                        $("#edit-withdrawRequirementBettingConditionOption3").prop('checked', true);
                                        break;
                                }

                                // withdrawal conditions for deposit
                                switch (withdrawRequirement?.withdrawRequirementBettingConditionOption) {
                                    case RedemptionCodeType.withdrawalConditionsDefineds.DEPOSIT_CONDITION_TYPE_MIN_LIMIT:
                                        $("#edit-withdrawRequirementDepositConditionOption2").prop('checked', true);
                                        $("#edit-withdrawReqDepMinLimit").val(withdrawRequirement?.withdrawReqDepMinLimit || 0);
                                        break;

                                    case RedemptionCodeType.withdrawalConditionsDefineds.DEPOSIT_CONDITION_TYPE_MIN_LIMIT_SINCE_REGISTRATION:
                                        $("#edit-withdrawRequirementDepositConditionOption3").prop('checked', true);
                                        $("#edit-withdrawReqDepMinLimitSinceRegistration").val(withdrawRequirement?.withdrawReqDepMinLimitSinceRegistration || 0);
                                        break;

                                    case RedemptionCodeType.withdrawalConditionsDefineds.DEPOSIT_CONDITION_TYPE_NOTHING:
                                    default:
                                        $("#edit-withdrawRequirementDepositConditionOption1").prop('checked', true);
                                        break;
                                }
                            }
                            if (currentCategory?.application_limit_setting) {
                                let applicationLimitSetting = currentCategory?.application_limit_setting;
                                $("#edit-bonusApplicationLimitDateType").val(applicationLimitSetting.bonusApplicationLimitDateType);
                                (applicationLimitSetting.bonusReleaseTypeOptionByNonSuccessionLimitOption == 0) ?
                                function() {
                                    $("#edit-bonusConditionByApplicationNoLimit").prop('checked', true);
                                    $("#edit-bonusConditionByApplicationLimitCnt").prop('disabled', true).val('');
                                }() :
                                function() {
                                    $("#edit-bonusConditionByApplicationWithLimit").prop('checked', true);
                                    $("#edit-bonusConditionByApplicationLimitCnt").prop('disabled', false).val(applicationLimitSetting.limitCnt);
                                }()
                            }
                            if($('.select2-player-list-ajax#edit-players').length > 0)
                            {
                                let allowedPlayers = currentCategory?.allowedPlayers;
                                allowedPlayers?.forEach((item) => {
                                    // let option = new Option(item.username, item.playerId, true, true);
                                    //<option value="1" selected="">testaff01</option>
                                    let optionAllowedPlayers = `<option value="${item.playerId}" selected="">${item.username}</option>`;
                                    $('.select2-player-list-ajax#edit-players').append(optionAllowedPlayers).trigger('change');
                                });
                            }
                            if($('.select2-affiliate-list-ajax#edit-affiliates').length > 0)
                            {
                                let allowedAffiliates = currentCategory?.allowedAffiliates;
                                allowedAffiliates?.forEach((item) => {
                                    // let option = new Option(item.username, item.affiliateId, true, true);
                                    //<option value="1" selected="">testaff01</option>
                                    let optionAllowedAffiliates = `<option value="${item.affiliateId}" selected="">${item.username}</option>`;
                                    $('.select2-affiliate-list-ajax#edit-affiliates').append(optionAllowedAffiliates).trigger('change');
                                });
                            }
                            if($('.select2-player-level-list-ajax#edit-player-levels').length > 0)
                            {
                                let allowedPlayerLevels = currentCategory?.allowedPlayerLevels;
                                let optionAllowedPlayerLevels = $('.select2-player-level-list-ajax#edit-player-levels').find('option');
                                if(typeof(allowedPlayerLevels) !== 'undefined'){
                                    $.each(optionAllowedPlayerLevels, function(index, option){
                                        if(typeof(allowedPlayerLevels[option.value]) !== 'undefined'){
                                            // console.log('found');
                                            $('.select2-player-level-list-ajax#edit-player-levels').multiselect('select', option.value);
                                        }
                                    });
                                }
                            }
                            $('#updateCategory').prop('disabled', false);
                            sendRequest = 0;
                            $('#edit_category_modal').modal('show');

                        }else{
                            alert('Something went wrong, please try again later.')
                        }
                    }
                });
            }
        },
        actionForm: function(_action) {
            $('.addCategoryAlert, .editCategoryAlert').hide();
            switch (_action) {
                case "edit":
                    this.actionPrefix = 'edit-';
                    this.currentModal = $('#edit_category_modal');
                    this.formId = 'edit_category_form';
                    this.submitBtn = $('#updateCategory');
                    this.actionURL = 'marketing_management/updateStaticRedemptionCodeCategory';
                    break;
                default:
                    this.actionPrefix = '';
                    this.currentModal = $('#add_category_modal');
                    this.formId = 'add_category_form';
                    this.submitBtn = $('#saveAddCategory');
                    this.actionURL = 'marketing_management/addStaticRedemptionCodeCategory';
                    break;
            }

            this.currentForm = document.getElementById(this.formId);
            this.formData = new FormData(this.currentForm);
            this.notValidate = false;
            this.categoryNameInput = $(`#${this.actionPrefix}categoryName`);
            this.categoryName = this.categoryNameInput.val();
            this.redeemCodeInput = $("[name$='redemptionCode']", this.currentModal);
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
            this.totalRedeemable = $(`#${this.actionPrefix}totalRedeemable`).val();
            this.bonusReleaseTypeFixedAmount = $(`#${this.actionPrefix}bonusReleaseTypeFixedAmount`);
            this.bonusReleaseTypeNonFixedAmount = $(`#${this.actionPrefix}bonusReleaseTypeNonFixedAmount`);
            this.nonfixedBonusMinAmount = $(`#${this.actionPrefix}nonfixedBonusMinAmount`).val();
            this.nonfixedBonusMaxAmount = $(`#${this.actionPrefix}nonfixedBonusMaxAmount`).val();
            this.bonusCap = $(`#${this.actionPrefix}bonusCap`).val();
            this.totalRedeemableAlert = $(`#${this.actionPrefix}totalRedeemableAlert`);
            this.redemptionCodeErrorMsg = $(`#${this.actionPrefix}redemptionCodeErrorMsg`);
            this.nonfixedBonusMinAlert = $(`#${this.actionPrefix}nonfixedBonusMinAlert`);
            this.nonfixedBonusMaxAlert = $(`#${this.actionPrefix}nonfixedBonusMaxAlert`);
            this.maxLessThanMinAlert = $(`#${this.actionPrefix}maxLessThanMinAlert`);
            this.bonusCapAlert = $(`#${this.actionPrefix}bonusCapAlert`);
            this.defaultErrMsg = $(`#${this.actionPrefix}defaultErrMsg`);
        },
        processAction: function(_action) {
            var submitFrom = new RedemptionCodeType.actionForm(_action);
            if (!submitFrom.categoryName.trim()) {
                submitFrom.categoryNameAlert.show().html(`*<?= lang('Required'); ?>`);
                submitFrom.notValidate = true;
            }

            if (submitFrom.categoryName.trim().length > 50) {
                submitFrom.categoryNameAlert.show().html(`*<?= lang('Maximum 50 characters (including spaces).'); ?>`);
                submitFrom.notValidate = true;
            }

            if (submitFrom.appEndDate == '' && submitFrom.isValidForever == false) {
                submitFrom.appEndDateAlert.show();
                submitFrom.notValidate = true;
            }

            if (submitFrom.totalRedeemable <= 0) {
                submitFrom.totalRedeemableAlert.show();
                submitFrom.notValidate = true;
            }

            if(submitFrom.redeemCodeInput.length > 0)
            {
                if (submitFrom.redeemCodeInput.val().trim() == '') {
                    submitFrom.redemptionCodeErrorMsg.show().html(`*<?= lang('required'); ?>`);
                    submitFrom.notValidate = true;
                }
                //check redeemCodeInput val length < 20
                if (submitFrom.redeemCodeInput.val().trim().length > 20) {
                    submitFrom.redemptionCodeErrorMsg.show().html(`*<?= lang('Maximum 20 characters'); ?>`);
                    submitFrom.notValidate = true;
                }
                //check redeemCodeInput val only A-Z, a-z, 0-9, without space
                if (submitFrom.redeemCodeInput.val().trim() != '') {
                    var reg = /^[A-Za-z0-9]+$/;
                    if (!reg.test(submitFrom.redeemCodeInput.val().trim())) {
                        submitFrom.redemptionCodeErrorMsg.show().html(`*<?= sprintf(lang('form.validation.alpha_numeric'), ''); ?>`);
                        submitFrom.notValidate = true;
                    }
                }
            }

            if (submitFrom.bonusReleaseTypeNonFixedAmount.is(":checked")) {
                if (submitFrom.nonfixedBonusMinAmount <= 0) {
                    submitFrom.nonfixedBonusMinAlert.show();
                    submitFrom.notValidate = true;
                }
                if (submitFrom.nonfixedBonusMaxAmount <= 0) {
                    submitFrom.nonfixedBonusMaxAlert.show();
                    submitFrom.notValidate = true;
                }

                if (parseFloat(submitFrom.nonfixedBonusMaxAmount) < parseFloat(submitFrom.nonfixedBonusMinAmount)) {
                    submitFrom.maxLessThanMinAlert.show();
                    submitFrom.notValidate = true;
                }
                if (submitFrom.bonusCap <= 0) {
                    submitFrom.bonusCapAlert.show();
                    submitFrom.notValidate = true;
                }

                if (parseFloat(submitFrom.bonusCap) < parseFloat(submitFrom.nonfixedBonusMinAmount)) {
                    submitFrom.bonusCapAlert.show();
                    submitFrom.notValidate = true;
                }
            }else if(submitFrom.bonusReleaseTypeFixedAmount.is(":checked"))
            {
                if (submitFrom.bonus <= 0) {
                    submitFrom.bonusAlert.show();
                    submitFrom.notValidate = true;
                }
            }

            if (submitFrom.bonusConditionByApplicationWithLimit[0].checked && 0 >= submitFrom.bonusConditionByApplicationLimitCnt.val()) {
                $('.limitCntAlert').show();
                submitFrom.notValidate = true;
            }

            switch (submitFrom.formData.get('withdrawRequirementBettingConditionOption')) {
                case RedemptionCodeType.withdrawalConditionsDefineds.WITHDRAW_CONDITION_TYPE_BONUS_TIMES:
                    if(0>=$(`#${submitFrom.actionPrefix}withdrawReqBonusTimes`).val()) {
                        $('.withdrawReqBonusTimes').show();
                        submitFrom.notValidate = true;
                    }
                    break;

                case RedemptionCodeType.withdrawalConditionsDefineds.WITHDRAW_CONDITION_TYPE_FIXED_AMOUNT:
                    if(0>= $(`#${submitFrom.actionPrefix}withdrawReqBetAmount`).val()) {
                        $('.withdrawReqBetAmount').show();
                        submitFrom.notValidate = true;
                    }
                    break;
            }

            if (submitFrom.notValidate) {
                return false;
            } else {
                submitFrom.submitBtn.prop('disabled', true);
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
                        if (data?.data?.success) {
                            let res = data.data;
                            let resultContent = `${res.successMsg}`;

                            $('#process-type-modal').modal('show');
                            submitFrom.currentModal.modal('hide');

                            if (res?.redriectCodeReport) {

                                resultContent += `
                                                <br>
                                                <a href="${res.redriectCodeReport}" target="_blank"><?= lang('redemptionCode.viewCodeList') ?></a>
                                                <br>
                                                <a href="${res.redriectGenerateProgress}" target="_blank"><?= lang('redemptionCode.viewGenerateProgress') ?></a>
                                                `;
                            }
                            window.open(res.redriectGenerateProgress, '_blank')
                            $('#successMsg').html(resultContent);
                            $('.generate_process_footer').show();
                        } else {
                            submitFrom.submitBtn.prop('disabled', false);
                            switch (data?.data?.noteType) {
                                case 'name':
                                    submitFrom.categoryNameErrorMsg.text(data?.data?.errorMsg).show();
                                    break;
                                case 'quantity':
                                    submitFrom.totalRedeemableAlert.text(data?.data?.errorMsg).show();
                                    break;
                                case 'code':
                                    submitFrom.redemptionCodeErrorMsg.text(data?.data?.errorMsg).show();
                                    break;
                                case 'job':
                                default:
                                    submitFrom.defaultErrMsg.text(data?.data?.errorMsg).show();

                            }
                        }
                    }
                });
            }
        }
    }
</script>
