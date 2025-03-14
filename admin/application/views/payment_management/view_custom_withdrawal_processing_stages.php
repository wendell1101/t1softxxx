<div class="panel panel-primary">
    <div class="panel-heading">
        <h4 class="panel-title pull-left">
            <i class="glyphicon glyphicon-list-alt"></i> <?=lang('Withdrawal Processing Stages Setting');?>
        </h4>
        <div class="clearfix"></div>
    </div>
    <div class="panel-body" id="details_panel_body">
        <div class="row">
            <div class="col-md-6" id="previewPaymentAcctSettingSec">
                <!-- Display -->
                <form action="<?=site_url('payment_management/saveCustomWithdrawalProcessingStageSetting'); ?>" method="post" role="form">
                    <table class="table table-bordered">
                        <tr>
                            <th style="width: 30%"><?=lang('Enable')?></th>
                            <th><?=lang('Stage Name')?></th>
                        </tr>
                        <tr>
                            <td colspan="2">
                                <input type="checkbox" checked disabled />
                                <label><?=lang('pay.penreq')?></label>
                            </td>
                        </tr>
                        <?php if($this->utils->isEnabledFeature('enable_withdrawal_pending_review')){ ?>
                            <tr>
                                <?php $this->load->view('payment_management/withdraw_flow/pending_review'); ?>
                            </tr>
                        <?php } ?>
                        <?php if($this->utils->getConfig('enable_pending_review_custom')){ ?>
                            <tr>
                                <?php $this->load->view('payment_management/withdraw_flow/pending_review_vip'); ?>
                            </tr>
                        <?php } ?>
                        <?php for($i = 0; $i < CUSTOM_WITHDRAWAL_PROCESSING_STAGES; $i++) : ?>
                            <tr>
                                <td>
                                    <input id="cb<?=$i?>" type="checkbox" data-index="<?=$i?>" name="enabled_<?=$i?>" value="1" <?=$setting[$i]['enabled'] ? 'checked' : ''?> />
                                    <label for="cb<?=$i?>"><?= lang('Custom Stage').' '.($i+1) ?></label>
                                </td>
                                <td>
                                    <input id="name<?=$i?>" type="text" class="form-control" name="name_<?=$i?>" value="<?=$setting[$i]['name'] ?: ''?>" maxlength="20" />
                                </td>
                            </tr>
                        <?php endfor; ?>
                        <tr>
                            <td colspan="2">
                                <input id="payProc" type="checkbox" name="enabled_payProc" value="1"
                                    <?=$setting['payProc']['enabled'] ? 'checked' : ''?>
                                    <?=array_key_exists('mustEnable', $setting['payProc']) && $setting['payProc']['mustEnable'] ? "disabled" : ""?> />
                                <label for="payProc"><?=lang('pay.processing')?></label>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2">
                                <input type="checkbox" checked disabled />
                                <label><?=lang('pay.paid')?></label>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2">
                                <input type="checkbox" checked disabled />
                                <label><?=lang('pay.decreq')?></label>
                            </td>
                        </tr>
                    </table>
                    <input type="submit" value="<?=lang('player.saveset')?>" class="btn btn-sm btn-scooter">
                </form>
            </div>
        </div>
    </div>
</div>

<div class="panel panel-primary">
    <div class="panel-heading">
        <h4 class="panel-title pull-left">
            <i class="glyphicon glyphicon-list-alt"></i> <?= lang('pay.minwithsetting'); ?>
        </h4>
        <div class="clearfix"></div>
    </div>
    <div class="panel-body">
        <form action="<?= site_url('payment_management/saveMinWithdrawSetting/') ?>" method="post" class="form-inline">
            <div class="row">
                <div class="col-md-6">
                    <input class="form-control" type="number" min="0" max="9999" name="min_withdraw" value="<?= $min_withdraw[0]['value'] ?>" required/>
                </div>
            </div>
            <input type="submit" value="<?=lang('lang.save')?>" class="btn btn-scooter" style="margin-top: 15px;"/>
        </form>
    </div>
</div>

<div class="panel panel-primary">
    <div class="panel-heading">
        <h4 class="panel-title pull-left">
            <i class="glyphicon glyphicon-list-alt"></i> <?= lang('Withdrawal Preset Amount'); ?>
        </h4>
        <div class="clearfix"></div>
    </div>
    <div class="panel-body">
        <form action="<?= site_url('payment_management/saveWithdrawalPresetAmountSetting/') ?>" method="post" class="form-inline">
            <div class="row">
                <div class="col-md-6">
                    <label class="control-label"><?= lang('pay.preset_amount_buttons'); ?>: </label><br>
                    <input type="text" name="withdrawal_preset_amount" id="withdrawal_preset_amount" class="form-control" onkeyup="this.value=this.value.replace(/[^\d\|]/g,'')" value="<?= $withdrawal_preset_amount[0]['value'] ?>">
                        <?php echo form_error('withdrawal_preset_amount', '<span class="help-block" style="color:#ff6666;">', '</span>'); ?>
                </div>
            </div>
            <input type="submit" value="<?=lang('lang.save')?>" class="btn btn-scooter" style="margin-top: 15px;"/>
        </form>
    </div>
</div>

<script type="text/javascript">
    var numCustomStages = <?=CUSTOM_WITHDRAWAL_PROCESSING_STAGES?>;
    $(function(){
        var fillDefaultName = function() {
            var index = $(this).data('index');
            var checked = $('#cb'+index).prop('checked');
            var name = $('#name'+index).val();
            if(checked && name == '') {
                $('#name'+index).val($('label[for="cb'+index+'"]').text());
            }
        }

        for(i = 0; i <= numCustomStages; i++) {
            $('#cb' + i).click(fillDefaultName);
        }

        // -- control sidebar --
        $('#collapseSubmenu').addClass('in');
        $('#view_payment_settings').addClass('active');
        $('#withdrawalProcessingStagesSetting').addClass('active');
    });
</script>
