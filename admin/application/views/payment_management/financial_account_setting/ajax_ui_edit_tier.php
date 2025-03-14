<style>
    .proccessing_top{
        display: flex;
        position: relative;
    }
    .set_infinity_checkBox{
        position: absolute;
        top: -20px;
    }
    .proccessing{
        display: none;
    }
    .proccessing_bottom,.proccessing_top{
        margin-top: 2rem;
        align-items: center;
    }
    .errMsg{
        color: red;
        display: none;
        font-size: 12px;
    }
    #submitUpdateProcess{
        display: none;
    }

</style>
<div>
    <div id="addSettingProcess">
        <div class="alert_msg" style="color:red;">
            <?= lang('financial_account.edit_tier_hint'); ?>
        </div>

        <div class="tier_settings">
            <div>
                <table id="tierSettingsTable" class="table table-bordered table-hover">
                    <thead>
                        <th><?=lang('financial_account.min_amount')?></th>
                        <th><?=lang('financial_account.max_amount')?></th>
                        <th><?=lang('financial_account.no_of_accounts_allowed')?></th>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
        </div>
        <hr>
        <div class="proccessing row form-inline">
            <div class='proccessing_top col-md-12'>
                <input type="number" class="last_level_rangefrom form-control" value='0' disabled>
                <span class="proccessing_range_text p-10 text-nowrap">
                    <?='≦'.lang('financial_account.transaction_mount').'<';?>
                </span>
                <div class="rangeToSettingBox">
                    <div class="set_infinity_checkBox">
                        <label for="setInfinity">
                            <input type="checkbox" name="set_infinity" id="setInfinity">
                            <?=lang('financial_account.infinity_amount');?>
                        </label>
                    </div>
                    <div>
                        <input class="form-control input-sm" type="number" id="proccessingRangeTo" min="0" step="1" oninput="setToInt(this);">
                        <div id="proccessingRangeTo_error" class="errMsg"><?=lang('financial_account.edit_tier_error_hint');?></div>
                    </div>
                </div>
            </div>
            <div class="proccessing_bottom col-md-12">
                <?=lang('financial_account.num_of_accounts_allowed');?><input class="form-control input-sm m-l-5" type="number" id="numOfAccount" min="0" step="1" oninput="setToInt(this);">
                <div id="numOfAccount_error" class="errMsg"></div>
            </div>

        </div>
        <div class="pull-right">
            <center>
                <button id="cancelModalBtn" data-dismiss="modal" class="btn btn-linkwater"><?=lang('Cancel');?></button>
                <button id="saveMaximumNumberAccountSetting" class="btn btn-scooter"><?=lang('Save');?></button>
            </center>
        </div>
    </div>
    <div id="submitUpdateProcess">
        <div id="responseMsg">
        </div>
        <div>
            <center>
                <button data-dismiss="modal" class="btn btn-linkwater"><?=lang('OK');?></button>
            </center>
        </div>
    </div>

    <div class="clearfix"></div>
</div>
<script type="text/javascript">
    var bank_type = <?=$bank_type?>;
    var targetIndex = '<?=$index?>';
    $(document).ready(function () {
        var tierItem = new updateTierItem(bank_type,targetIndex);
        $('#saveMaximumNumberAccountSetting').click(function(e){
            e.preventDefault();
            if (tierItem.targetIndex == 'ADD') {
                tierItem.add();
            } else {
                tierItem.edit();
            }
        });
    });
</script>