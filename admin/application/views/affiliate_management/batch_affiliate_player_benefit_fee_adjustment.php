<style>
    .selected_aff-list{
        width: 100%;
        border: 1px solid;
        padding: 10px 1rem;
    }
    .selected_aff-item{
        background: #ccc;
        margin: 3px;
        padding: 4px;
        display: inline-block;
    }
    .box{
        margin-bottom: 2rem;
    }
    .for_each_aff.hidden .for_all_aff.hidden{
        display: none;
    }
    .for_each_aff.active .for_all_aff.active{
        display: block;
    }
</style>
<div class="panel panel-default">
    <div class="panel-body">
        <div class="row">
            <div class="col-lg-12 box">
                <div class="form-group">
                    <label><?php echo lang("Affiliates");?></label>
                    <div class="selected_aff-list">
                        <?php
                            if(!empty($aff_editable_list)){
                                foreach ($aff_editable_list as $key => $aff) {
                                    echo '<span class="selected_aff-item"> '.$aff['username'].' </span>';
                                }
                            }
                        ?>
                    </div>
                </div>
            </div>
            <form id="form_update_fee" method="POST" action="<?=site_url('affiliate_management/batchUpdatePlayerBenefitFee/')?>">
                <div class="col-lg-12 box">
                    <div class="form-group">
    
                        <div class="col-md-8">
                            <div>
                                <input type="radio" id="same_amount" name="benefit_fee_update_type" value="ALL" checked>
                                <label for="same_amount"><?php echo lang('Set up the same amount for all the selected affiliates.');?></label>
                            </div>
                            <div>
                                <input type="radio" id="different_amount" name="benefit_fee_update_type" value="EACH">
                                <label for="different_amount"><?php echo lang('Set up different amount for each selected affiliate.');?></label>
                            </div>
                            <input type="hidden" name="yearmonth" value="<?=$yearmonth?>">
                        </div>
                        <div class="col-md-4">
                            <button type="button" class="btn btn-primary btn_benefit_fee_batch_update" style="float: right;"><?php echo lang('lang.submit')?></button>
                        </div>
                    </div>
                </div>
                <div class="col-lg-12 box">
                    <div class="col-md-12 form-group for_all_aff active">
                        <label><?=lang("Player's Benefit Fee")?></label>
                        <input id="set_for_all" type='number' name='set_for_all' class='form-control' placeholder='<?php echo lang("Enter Benefit Fee");?>' maxlength='10'>
                    </div>
                    <div class="for_each_aff hidden">
                        <div class="from-group">
                            <div class="row">
                                <div class='col-md-4'> <label><?=lang('Affiliate Username')?> </label></div>
                                <div class='col-md-8'> <label><?=lang("Player's Benefit Fee")?> </label></div>
                            </div>
                            <?php
                                if (!empty($aff_editable_list)) {
                                    foreach ($aff_editable_list as $key => $aff) {

                                        $aff_id = $aff['affiliateId'];
                                        $username = $aff['username'];
                                        $commission_id = $aff['earningid'];
                                        $player_benefit_fee = $aff['player_benefit_fee'];
                                        // $input_id = $commission_id .'_'. $aff_id .'_'. $username;
                                        $input_id = $username;
                                        $lang_of_placeholder = lang("Enter Benefit Fee");

                                        echo "<div class='update_item form-group'>
                                                <div class='row'>
                                                    <div class='col-md-4'>$username</div>
                                                    <div class='col-md-8 input_item'>
                                                        <input type='number' name='$input_id' class='form-control' placeholder='$lang_of_placeholder' maxlength='10' value='$player_benefit_fee' required>
                                                    </div>
                                                </div>
                                            </div>";

                                    }
                                }
                            ?>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>