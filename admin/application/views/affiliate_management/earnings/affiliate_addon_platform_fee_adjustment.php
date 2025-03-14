<div class="panel panel-default">
    <div class="panel-body">
        <div class="row">
            <div class="col-lg-12">

                <div class="form-group">
                    <label><?php echo lang("Addon Platform Fee");?></label>
                    <input id="addon_platform_fee" type="text" class="form-control"
                        placeholder="<?php echo lang("Addon Platform Fee");?>"
                        value="<?php echo $this->utils->formatCurrencyNoSym($addon_platform_fee, 2);?>"
                        disabled>
                </div>
            </div>
            <div class="col-lg-12">
                <form id="formAddonPlatformFee" method="POST"
                    action="<?=site_url('affiliate_management/updatePlatformFeeForOne/' . $affiliate_commission_id)?>"
                    autocomplete="off">
                    <div class="form-group">
                        <label><?php echo lang('New Addon Platform Fee');?></label>
                        <input type="hidden" name="lang" id="lang"
                            value="<?php echo lang("Are you sure you want to update this Platform Fee?");?>">
                        <input id="AFFMID" type="hidden" class="form-control"
                            value="<?php echo $affiliate_commission_id;?>">
                        <input id="new_addon_platform_fee" type="text" name="new_addon_platform_fee"
                            class="form-control"
                            placeholder="<?php echo lang("Enter Platform Fee");?>"
                            maxlength="10">
                        <button type="submit" style="display: none;"></button>
                    </div>
                </form>
            </div>
        </div>

        <button type="button" class="btn btn-primary btn_platform_fee_update" style="float: right;" disabled><?php echo lang('lang.submit')?></button>

    </div>
</div>