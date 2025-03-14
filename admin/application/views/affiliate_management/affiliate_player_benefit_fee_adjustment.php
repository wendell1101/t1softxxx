<div class="panel panel-default">
    <div class="panel-body">
        <div class="row">
            <div class="col-lg-12">

                <div class="form-group">
                    <label><?php echo lang("Player's Benefit Fee");?></label>
                    <input id="benefit_fee" type="text" class="form-control" placeholder="<?php echo lang("Player's Benefit Fee");?>" value="<?php echo $this->utils->formatCurrencyNoSym($player_benefit_fee,2);?>" disabled>
                </div>
            </div>
            <div class="col-lg-12">
                <form id="formUPBF" method="POST" target="_blank" action="<?=site_url('affiliate_management/updatePlayerBenefitFeeForOne/' . $affiliate_commission_id)?>" autocomplete="off" onkeydown="return event.key != 'Enter';">
                    <div class="form-group">
                        <label><?php echo lang('New Player\'s Benefit Fee');?></label>
                        <input type="hidden" name="lang" id="lang" value="<?php echo lang("Are you sure you want to update this Player's Benefit Fee?");?>">
                        <input id="AFFMID" type="hidden" class="form-control" value="<?php echo $affiliate_commission_id;?>">
                        <input id="player_benefit_fee" type="text" name="player_benefit_fee" class="form-control" placeholder="<?php echo lang("Enter Benefit Fee");?>" maxlength="10">
                    </div>
                </form>
            </div>
        </div>

        <button type="button" class="btn btn-primary btn_benefit_fee_update" style="float: right;" disabled><?php echo lang('lang.submit')?></button>

    </div>
</div>