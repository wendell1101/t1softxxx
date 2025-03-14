<?php include __DIR__.'/../includes/big_wallet_details.php'; ?>
<?php include __DIR__.'/../includes/popup_promorules_info.php'; ?>

<style type="text/css">
    input::-webkit-outer-spin-button,
    input::-webkit-inner-spin-button {
      -webkit-appearance: none;
      margin: 0;
    }

    /* Firefox */
    input[type=number] {
      -moz-appearance: textfield;
    }
</style>

<div class="container-fluid" id="iframe_transaction_list" >
    <!-- Sort Option -->
    <form class="form-horizontal" id="form-filter" method="post">
        <div class="row">
            <div class="col-md-12">
                <div class="panel panel-primary hidden">

                    <div class="panel-heading">
                        <h4 class="panel-title">
                            <i class="fa fa-search"></i> <?=lang("lang.search")?>
                            <span class="pull-right">
                                <a data-toggle="collapse" href="#collapseTransactionList" class="btn btn-xs btn-primary <?=$this->config->item('default_open_search_panel') ? '' : 'collapsed'?>"></a>
                            </span>
                        </h4>
                    </div>
                    <div id="collapseTransactionList" class="panel-collapse <?=$this->config->item('default_open_search_panel') ? '' : 'collapse in'?>">
                        <div class="panel-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <label class="control-label"><?=lang('pay.transperd')?></label>
                                    <input id="reportrange" class="form-control input-sm dateInput" data-time="true" data-start="#dateRangeValueStart" data-end="#dateRangeValueEnd" autocomplete="off"/>
                                    <input type="hidden" id="dateRangeValueStart" name="dateRangeValueStart" value="<?=$this->session->userdata('dateRangeValueEnd')?>"/>
                                    <input type="hidden" id="dateRangeValueEnd" name="dateRangeValueEnd" value="<?=$this->session->userdata('dateRangeValueEnd')?>"/>
                                </div>
                                <div class="col-md-2">
                                    <label for="username" class="control-label">
                                        <?=lang('Username'); ?>:
                                        <input type="radio" name="search_by" value="1" checked="checked"/> <?=lang('Similar');?>
                                        <input type="radio" name="search_by" value="2"/> <?=lang('Exact'); ?>
                                    </label>
                                    <input type="text" name="memberUsername"  id="memberUsername"  class="form-control input-sm" placeholder="<?=lang('member.username'); ?>"/>
                                    <?php if(!$this->utils->isEnabledFeature('close_aff_and_agent')): ?>
                                        <label>
                                            <input type="checkbox" name="no_affiliate" value="true" onchange="$('#belongAff').prop('disabled', this.checked);" />
                                            <?=lang('affiliate.no.affiliate.only')?>
                                        </label>
                                        <label>
                                            <input type="checkbox" name="no_agent" value="true"/>
                                            <?=lang('No agent only')?>
                                        </label>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-2">
                                    <label class="control-label"><?=lang('pay.transid'); ?></label>
                                    <input type="number" name="transaction_id"  id="transaction_id"  class="form-control input-sm" placeholder="<?=lang('pay.transid'); ?>" value="<?=$transaction_id?>"/>
                                </div>
                                <div class="col-md-2">
                                    <label class="control-label"><?=lang('cms.promoCat')?></label>
                                    <select class="form-control input-sm" name="promo_category">
                                        <option value=""><?=lang('lang.selectall')?></option>
                                        <?php foreach ($promo_category_list as $promo_category): ?>
                                            <option value="<?=$promo_category['promotypeId']?>" <?=set_select('promo_category', $promo_category['promotypeId'], isset($input['promo_category']) && $input['promo_category'] == $promo_category['promotypeId'])?>><?=lang($promo_category['promoTypeName'])?></option>
                                        <?php endforeach?>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="control-label"><?=lang('Promo Rule')?></label>
                                    <select class="form-control input-sm" name="promo_rule">
                                        <option value=""><?=lang('lang.selectall')?></option>
                                        <?php foreach($promo_rules_list as $promo_rule){ ?>
                                            <option value="<?= $promo_rule['promorulesId']; ?>" <?= set_select('promorulesId', $promo_rule['promorulesId'], isset($input['promo_rule']) && $input['promo_rule'] == $promo_rule['promorulesId']) ?>><?= lang($promo_rule['promoName']); ?></option>
                                        <?php } ?>
                                    </select>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-2">
                                    <label class="control-label"><?=lang('player.80')?></label>
                                    <div>
                                        <select class="form-control input-sm" name="from_type" id="from_type">
                                            <option value=""><?=lang('lang.selectall')?></option>
                                            <option value="1" <?=set_select('from_type', 1, isset($input['from_type']) && $input['from_type'] == 1)?>><?=lang('transaction.from.to.type.1')?></option>
                                            <option value="2" <?=set_select('from_type', 2, isset($input['from_type']) && $input['from_type'] == 2)?>><?=lang('transaction.from.to.type.2')?></option>
                                            <option value="3" <?=set_select('from_type', 3, isset($input['from_type']) && $input['from_type'] == 3)?>><?=lang('transaction.from.to.type.3')?></option>
                                        </select>
                                        <br>
                                        <input type="text" name="fromUsername" style="display:none;" id="fromUsername"  class="form-control input-sm" placeholder="<?=lang('reg.03')?>"/>
                                        <span class="help-block" ></span>
                                        <input type="hidden" name="fromUsernameId"  id="fromUsernameId" />
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <label class="control-label"><?=lang('player.81')?></label>
                                    <div>
                                        <select class="form-control input-sm" name="to_type" id="to_type">
                                            <option value=""><?=lang('lang.selectall')?></option>
                                            <option value="1" <?=set_select('from_type', 1, isset($input['from_type']) && $input['from_type'] == 1)?>><?=lang('transaction.from.to.type.1')?></option>
                                            <option value="2" <?=set_select('from_type', 2, isset($input['from_type']) && $input['from_type'] == 2)?>><?=lang('transaction.from.to.type.2')?></option>
                                            <option value="3" <?=set_select('from_type', 3, isset($input['from_type']) && $input['from_type'] == 3)?>><?=lang('transaction.from.to.type.3')?></option>
                                        </select>
                                        <br>
                                       <input type="text" name="toUsername" style="display:none;"  id="toUsername"  class="form-control input-sm" placeholder="<?=lang('reg.03')?>"/>
                                       <span class="help-block" ></span>
                                       <input type="hidden" name="toUsernameId" id="toUsernameId" />
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <label class="control-label"><?=lang('Transaction Amount')?> >= </label>
                                    <input type="number" class="form-control input-sm" name="amountStart" min="0" maxlength="10" oninput="maxLengthCheck(this)" id="amountStart" value="<?=isset($input['amountStart']) ? $input['amountStart'] : '';?>"/>
                                </div>
                                <div class="col-md-2">
                                    <label class="control-label"><?=lang('Transaction Amount')?> <= </label>
                                    <input type="number" class="form-control input-sm" name="amountEnd" id="amountEnd" min="0" maxlength="10"  oninput="maxLengthCheck(this)" value="<?=isset($input['amountEnd']) ? $input['amountEnd'] : '';?>"/>
                                    <span id="from-to-amount-range" class="help-block" style="color:#F04124;"></span>
                                </div>
                                <div class="col-md-2">
                                    <label class="control-label"><?=lang('player.ut10')?></label>
                                    <select class="form-control input-sm" name="flag">
                                        <option value=""><?=lang('lang.selectall')?></option>
                                        <option value="1" <?=set_select('flag', 1, isset($input['flag']) && $input['flag'] == 1)?>><?=lang('transaction.flag.1')?></option>
                                        <option value="2" <?=set_select('flag', 2, isset($input['flag']) && $input['flag'] == 2)?>><?=lang('transaction.flag.2')?></option>
                                    </select>
                                </div>
                                <?php if ($this->permissions->checkPermissions('friend_referral_player')): ?>
                                    <div class="col-md-2">
                                        <label class="control-label" for="referrer"><?=lang('pay.referrer')?></label>
                                        <input id="referrer" type="text" name="referrer"  value="<?=$referrer?>"  class="form-control input-sm"/>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class='row'>
                                <?php if(!$this->utils->isEnabledFeature('close_aff_and_agent')): ?>
                                    <div class="col-md-2">
                                        <label class="control-label"><?=lang('Belongs To Affiliate'); ?></label>
                                        <input type="text" name="belongAff"  id="belongAff"  class="form-control input-sm"/>
                                        <label>
                                            <input type="checkbox" name="aff_include_all_downlines" value="true"/>
                                            <?=lang('Include All Downlines Affiliate')?>
                                        </label>
                                    </div>
                                <?php endif; ?>
                                <?php if ($this->utils->isEnabledFeature('enable_adjustment_category')): ?>
                                    <div class="col-md-2">
                                        <label class="control-label"><?=lang('Adjustment Category');?></label>
                                        <div class="">
                                            <select class="form-control" name="adjustment_category_id"style="height: 36px; font-size: 12px;">
                                            <option value=""><?=lang("None");?></option>
                                            <?php if(!empty($adjustment_category_list)): ?>
                                                <?php foreach ($adjustment_category_list as $key => $value): ?>
                                                    <option value="<?=$value['id'];?>" <?=set_select('category_name', $value['category_name'], isset($input['category_name']) && $input['id'] == $value['category_name'])?>><?=lang($value['category_name'])?></option>
                                                <?php endforeach; ?>
                                            <?php endif;?>
                                            </select>
                                        </div>
                                    </div>
                                <?php endif;?>
                                <?php if ($this->utils->isEnabledFeature('enable_tag_column_on_transaction')): ?>
                                    <div class="col-md-2">
                                        <label class="control-label" class="control-label" style="font-size:12px;"><?=lang('con.plm72');?></label>
                                        <select class="form-control input-sm" name="tag_list">
                                            <option value=""><?=lang('lang.selectall')?></option>
                                            <?php foreach ($tag_list as $tag): ?>
                                                <option value="<?=$tag['tagId']?>" ><?=lang($tag['tagName'])?></option>
                                            <?php endforeach?>
                                        </select>
                                    </div>
                                <?php endif; ?>

                                <?php if ($this->utils->getConfig('enabled_viplevel_filter_in_transactions')): ?>
                                    <div class="col-md-2">
                                        <label class="control-label"><?=lang('player_list.fields.vip_level')?>:</label>
                                        <select name="player_level[]" id="player_level" multiple="multiple" class="form-control input-sm">
                                            <?php if (!empty($levels)): ?>
                                                <?php foreach ($levels as $levelId => $levelName): ?>
                                                    <option value="<?=$levelId?>" <?=
                                                    isset($input['player_level']) ? is_array($input['player_level']) && in_array($levelId, $input['player_level']) ? "selected" : "" : ""
                                                    ?> ><?=$levelName?></option>
                                                <?php endforeach ?>
                                            <?php endif ?>
                                        </select>
                                    </div>
                                <?php endif; ?>


                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <fieldset style="padding:0px 10px 10px 15px;">
                                        <legend>
                                            <label class="control-label"><?=lang('player.ut02')?></label>
                                        </legend>
                                        <div class="col-md-1 col-md-offset-0">
                                            <div class="checkbox checkbox-info checkbox-circle">
                                                <input id="checkall" name="transaction_type_all" value="checkall" class="checkall" type="checkbox" onclick="checkAll(this.id)" checked>
                                                <label for="checkall">
                                                    <?=lang('player.ui02')?>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="checkbox checkbox-info checkbox-circle">
                                                <input id="transaction_type_1" name="transaction_type" value="1" class="checkall trans-check" type="checkbox" checked="checked">
                                                <label for="transaction_type_1">
                                                    <?=lang('transaction.transaction.type.1')?>
                                                </label>
                                                <br/>
                                                <input id="transaction_type_2" name="transaction_type" value="2" class="checkall trans-check" type="checkbox" checked="checked">
                                                <label for="transaction_type_2">
                                                    <?=lang('transaction.transaction.type.2')?>
                                                </label>
                                                <br/>
                                                <input id="transaction_type_3" name="transaction_type" value="3" class="checkall trans-check" type="checkbox" checked="checked">
                                                <label for="transaction_type_3">
                                                    <?=lang('transaction.transaction.type.3')?>
                                                </label>
                                                <br/>
                                                <input id="transaction_type_4" name="transaction_type" value="4" class="checkall trans-check" type="checkbox" checked="checked">
                                                <label for="transaction_type_4">
                                                    <?=lang('transaction.transaction.type.4')?>
                                                </label>
                                                <br/>
                                                <input id="transaction_type_5" name="transaction_type" value="5" class="checkall trans-check" type="checkbox" checked="checked">
                                                <label for="transaction_type_5">
                                                    <?=lang('transaction.transaction.type.5')?>
                                                </label>
                                                <br/>
                                                <input id="transaction_type_6" name="transaction_type" value="6" class="checkall trans-check" type="checkbox" checked="checked">
                                                <label for="transaction_type_6">
                                                    <?=lang('transaction.transaction.type.6')?>
                                                </label>
                                                <br/>
                                                <input id="transaction_type_7" name="transaction_type" value="7" class="checkall trans-check" type="checkbox" checked="checked">
                                                <label for="transaction_type_7">
                                                    <?=lang('transaction.transaction.type.7')?>
                                                </label>
                                                <br/>
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="checkbox checkbox-info checkbox-circle">
                                                <input id="transaction_type_8" name="transaction_type" value="8" class="checkall trans-check" type="checkbox" checked="checked">
                                                <label for="transaction_type_8">
                                                    <?=lang('transaction.transaction.type.8')?>
                                                </label>
                                                <br/>
                                                <input id="transaction_type_9" name="transaction_type" value="9" class="checkall trans-check" type="checkbox" checked="checked">
                                                <label for="transaction_type_9">
                                                    <?=lang('transaction.transaction.type.9')?>
                                                </label>
                                                <br/>
                                                <input id="transaction_type_10" name="transaction_type" value="10" class="checkall trans-check" type="checkbox" checked="checked">
                                                <label for="transaction_type_10">
                                                    <?=lang('transaction.transaction.type.10')?>
                                                </label>
                                                <br/>
                                                <!-- <input id="transaction_type_11" name="transaction_type" value="11" class="checkall trans-check" type="checkbox" checked="checked">
                                                <label for="transaction_type_11">
                                                    <?=lang('transaction.transaction.type.11')?>
                                                </label>
                                                <br/>
                                                <input id="transaction_type_12" name="transaction_type" value="12" class="checkall trans-check" type="checkbox" checked="checked">
                                                <label for="transaction_type_12">
                                                    <?=lang('transaction.transaction.type.12')?>
                                                </label>
                                                <br/> -->
                                                <?php if(!$this->utils->isEnabledFeature('close_cashback')): ?>
                                                    <input id="transaction_type_13" name="transaction_type" value="13" class="checkall trans-check" type="checkbox" checked="checked">
                                                    <label for="transaction_type_13">
                                                        <?=lang('transaction.transaction.type.13')?>
                                                    </label>
                                                    <br/>
                                                <?php endif; ?>
                                                <input id="transaction_type_14" name="transaction_type" value="14" class="checkall trans-check" type="checkbox" checked="checked">
                                                <label for="transaction_type_14">
                                                    <?=lang('transaction.transaction.type.14')?>
                                                </label>
                                                <br/>
                                                <input id="transaction_type_46" name="transaction_type" value="46" class="checkall trans-check" type="checkbox" checked="checked">
                                                <label for="transaction_type_46">
                                                    <?=lang('transaction.transaction.type.46')?>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="checkbox checkbox-info checkbox-circle">
                                            <input id="transaction_type_15" name="transaction_type" value="15" class="checkall trans-check" type="checkbox" checked="checked">
                                            <label for="transaction_type_15">
                                                <?=lang('transaction.transaction.type.15')?>
                                            </label>
                                            <br>
                                            <?php if ($this->utils->getConfig('enabled_referred_bonus')): ?>
                                            <input id="transaction_type_49" name="transaction_type" value="49" class="checkall trans-check" type="checkbox" checked="checked">
                                            <label for="transaction_type_49">
                                                <?=lang('transaction.transaction.type.49')?>
                                            </label>
                                            <br>
                                            <?php endif; // EOF if ($this->utils->getConfig('enabled_referred_bonus')): ?>
                                            <input id="transaction_type_17" name="transaction_type" value="17" class="checkall trans-check" type="checkbox" checked="checked">
                                            <label for="transaction_type_17">
                                                <?=lang('transaction.transaction.type.17')?>
                                            </label>
                                            <br/>
                                            <input id="transaction_type_18" name="transaction_type" value="18" class="checkall trans-check" type="checkbox" checked="checked">
                                            <label for="transaction_type_18">
                                                <?=lang('transaction.transaction.type.18')?>
                                            </label>
                                            <br/>
                                            <input id="transaction_type_19" name="transaction_type" value="19" class="checkall trans-check" type="checkbox" checked="checked">
                                            <label for="transaction_type_19">
                                                <?=lang('transaction.transaction.type.19')?>
                                            </label>
                                            <br/>
                                            <input id="transaction_type_20" name="transaction_type" value="20" class="checkall trans-check" type="checkbox" checked="checked">
                                            <label for="transaction_type_20">
                                                <?=lang('transaction.transaction.type.20')?>
                                            </label>
                                            <br/>
                                            <input id="transaction_type_21" name="transaction_type" value="21" class="checkall trans-check" type="checkbox" checked="checked">
                                            <label for="transaction_type_21">
                                                <?=lang('transaction.transaction.type.21')?>
                                            </label>
                                            <br>
                                            <input id="transaction_type_22" name="transaction_type" value="22" class="checkall trans-check" type="checkbox" checked="checked">
                                            <label for="transaction_type_22">
                                                <?=lang('transaction.transaction.type.22')?>
                                            </label>
                                            <br/>
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="checkbox checkbox-info checkbox-circle">
                                            <input id="transaction_type_23" name="transaction_type" value="23" class="checkall trans-check" type="checkbox" checked="checked">
                                            <label for="transaction_type_23">
                                                <?=lang('transaction.transaction.type.23')?>
                                            </label>
                                            <br/>
                                            <?php if(!$this->utils->isEnabledFeature('close_cashback')): ?>
                                                <input id="transaction_type_24" name="transaction_type" value="24" class="checkall trans-check" type="checkbox" checked="checked">
                                                <label for="transaction_type_24">
                                                    <?=lang('transaction.transaction.type.24')?>
                                                </label>
                                                <br/>
                                                <input id="transaction_type_25" name="transaction_type" value="25" class="checkall trans-check" type="checkbox" checked="checked">
                                                <label for="transaction_type_25">
                                                    <?=lang('transaction.transaction.type.25')?>
                                                </label>
                                                <br/>
                                                <input id="transaction_type_26" name="transaction_type" value="26" class="checkall trans-check" type="checkbox" checked="checked">
                                                <label for="transaction_type_26">
                                                    <?=lang('transaction.transaction.type.26')?>
                                                </label>
                                                <br>
                                                <input id="transaction_type_27" name="transaction_type" value="27" class="checkall trans-check" type="checkbox" checked="checked">
                                                <label for="transaction_type_27">
                                                    <?=lang('transaction.transaction.type.27')?>
                                                </label>
                                                <br/>
                                                <input id="transaction_type_28" name="transaction_type" value="28" class="checkall trans-check" type="checkbox" checked="checked">
                                                <label for="transaction_type_28">
                                                    <?=lang('transaction.transaction.type.28')?>
                                                </label>
                                                <br/>
                                                <input id="transaction_type_29" name="transaction_type" value="29" class="checkall trans-check" type="checkbox" checked="checked">
                                                <label for="transaction_type_29">
                                                    <?=lang('transaction.transaction.type.29')?>
                                                </label>
                                                <br/>
                                            <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="checkbox checkbox-info checkbox-circle">
                                            <?php if(!$this->utils->isEnabledFeature('close_aff_and_agent')): ?>
                                                <input id="transaction_type_30" name="transaction_type" value="30" class="checkall trans-check" type="checkbox" checked="checked">
                                                <label for="transaction_type_30">
                                                    <?=lang('transaction.transaction.type.30')?>
                                                </label>
                                                <br/>
                                            <?php endif; ?>
                                            <input id="transaction_type_31" name="transaction_type" value="31" class="checkall trans-check" type="checkbox" checked="checked">
                                            <label for="transaction_type_31">
                                                <?=lang('transaction.transaction.type.31')?>
                                            </label>
                                            <br>
                                            <input id="transaction_type_32" name="transaction_type" value="32" class="checkall trans-check" type="checkbox" checked="checked">
                                            <label for="transaction_type_32">
                                                <?=lang('transaction.transaction.type.32')?>
                                            </label>
                                            <br/>
                                            <input id="transaction_type_33" name="transaction_type" value="33" class="checkall trans-check" type="checkbox" checked="checked">
                                            <label for="transaction_type_33">
                                                <?=lang('transaction.transaction.type.33')?>
                                            </label>
                                            <br>
                                            <?php if($this->utils->getConfig('enable_withdrawl_fee_from_player')): ?>
                                            <input id="transaction_type_43" name="transaction_type" value="43" class="checkall trans-check" type="checkbox" checked="checked">
                                            <label for="transaction_type_43">
                                                <?=lang('transaction.transaction.type.43')?>
                                            </label>
                                            <br>
                                            <input id="transaction_type_44" name="transaction_type" value="44" class="checkall trans-check" type="checkbox" checked="checked">
                                            <label for="transaction_type_44">
                                                <?=lang('transaction.transaction.type.44')?>
                                            </label>
                                            <br>
                                            <input id="transaction_type_45" name="transaction_type" value="45" class="checkall trans-check" type="checkbox" checked="checked">
                                            <label for="transaction_type_45">
                                                <?=lang('transaction.transaction.type.45')?>
                                            </label>
                                            <?php endif; ?>
                                            <br>
                                            <?php if($this->utils->getConfig('enable_withdrawl_bank_fee')): ?>
                                            <input id="transaction_type_47" name="transaction_type" value="47" class="checkall trans-check" type="checkbox" checked="checked">
                                            <label for="transaction_type_47">
                                                <?=lang('transaction.transaction.type.47')?>
                                            </label>
                                            <br>
                                            <?php endif; ?>
                                            <?php if($this->utils->getConfig('enabled_roulette_transactions')): ?>
                                            <input id="transaction_type_48" name="transaction_type" value="48" class="checkall trans-check" type="checkbox" checked="checked">
                                            <label for="transaction_type_48">
                                                <?=lang('transaction.transaction.type.48')?>
                                            </label>
                                            <br>
                                            <?php endif; ?>
                                            <?php if($this->utils->getConfig('enabled_quest')): ?>
                                            <input id="transaction_type_50" name="transaction_type" value="50" class="checkall trans-check" type="checkbox" checked="checked">
                                            <label for="transaction_type_50">
                                                <?=lang('transaction.transaction.type.50')?>
                                            </label>
                                            <br>
                                            <?php endif; ?>
                                            </div>
                                        </div>
                                    </fieldset>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <?php if(is_array($payment_account_list)): ?>
                                    <fieldset style="padding-bottom: 8px">
                                        <legend>
                                            <label class="control-label"><?=lang('pay.collection_account_name');?></label>
                                            <a id="payment_account_toggle_btn" style="text-decoration:none; border-radius:2px;" class="btn btn-xs btn-scooter"><span class="fa fa-plus-circle"> <?=lang("Expand All")?></span></a>
                                        </legend>
                                        <div class="col-md-3">
                                            <div class="checkbox">
                                                <label>
                                                    <input id="payment_account_id" name="payment_account_all" value="true" type="checkbox" onclick="checkAll(this.id)" checked="checked"> <?=lang('lang.selectall');?>
                                                </label>
                                            </div>
                                            <div class="col-md-3">
                                                <label>
                                                    <input class="btn btn-sm btn-portage" type="button" onclick="showPaymentCategoryModal(this.id)" value="<?=lang("Bank/Payment Gateway Type");?>" />
                                                </label>
                                            </div>
                                            <div class="modal fade in" id="payment_category_modal" tabindex="-1" role="dialog" aria-labelledby="label_payment_category_modal">
                                                <div class="modal-dialog" role="document">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                                <span aria-hidden="true">&times;</span>
                                                            </button>
                                                            <h4 class="modal-title" id="label_payment_category_modal"></h4>
                                                        </div>
                                                        <div class="modal-body"></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div id="payment_account_toggle">
                                            <?php foreach (($payment_account_chunks = array_chunk($payment_account_list, ceil(count($payment_account_list) / 3))) as $i => $payment_account_chunk): ?>
                                                <div class="col-md-3">
                                                <?php foreach ($payment_account_chunk as $payment_account): ?>
                                                    <div class="checkbox">
                                                        <label>
                                                            <input name="payment_account_id" value="<?=$payment_account->payment_account_id?>" data-flag="<?=$payment_account->second_category_flag?>" class="payment_account_id" type="checkbox" checked="checked"/>
                                                            <?=lang($payment_account->payment_type) . ' - ' . $payment_account->payment_account_name?>
                                                        </label>
                                                    </div>
                                                <?php endforeach?>
                                                <?php if ($payment_account_chunk == end($payment_account_chunks)): ?>
                                                    <div class="checkbox">
                                                        <label>
                                                            <input name="payment_account_id_null" value="1" class="payment_account_id" type="checkbox" checked="checked"> <?=lang('mg_others')?>
                                                        </label>
                                                    </div>
                                                <?php endif?>
                                              </div>
                                            <?php endforeach?>
                                        </div>
                                    </fieldset>
                                    <?php else:?>
                                    <fieldset style="padding-bottom: 8px">
                                        <legend>
                                            <label class="control-label"><?=lang('pay.collection_account_name');?></label>
                                        </legend>
                                    </fieldset>
                                    <?php endif;?>
                                </div>
                            </div>
                        </div>
                        <div class="panel-footer text-right">
                            <input class="btn btn-sm btn-linkwater" id="btn-reset" type="reset" value="<?=lang('lang.reset');?>">
                            <input class="btn btn-sm btn-portage" type="submit" value="<?=lang("lang.search");?>" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
    <!--end of Sort Information-->

    <div class="panel panel-primary">
        <div class="panel-heading">
            <h4 class="panel-title"><i class="fa fa-credit-card"></i> <?=lang('pay.transactions')?></h4>
        </div>
        <div class="table-responsive" style="padding:10px;">
            <table id="transaction-table" class="table table-bordered table-hover" cellspacing="0" width="100%" >
                <thead>
                    <tr>
                        <th><?=lang('player.ut01');?></th>
                        <th><?=lang('player.ut02');?></th>
                        <th><?=lang('player.ut03');?></th>
                        <th><?=lang('player.ut04');?></th>
                        <?php  if ($this->utils->getConfig('enabled_viplevel_filter_in_transactions')):  ?>
                            <th><?=lang('player_list.fields.vip_level')?></th>
                        <?php  endif;  ?>
                        <?php if ($this->utils->isEnabledFeature('enable_tag_column_on_transaction')): ?>
                            <th><?=lang("player.41")?></th>
                        <?php endif; ?>
                        <th><?=lang('Transaction Amount');?></th>
                        <th><?=lang('player.ut06');?></th>
                        <th><?=lang('player.ut07');?></th>
                        <th><?=lang('player.ut08');?></th>
                        <th><?=lang('cms.promoCat')?></th>
                        <?php  if ($this->utils->isEnabledFeature('enable_adjustment_category')):  ?>
                            <th><?=lang('Adjustment Category')?></th>
                        <?php  endif;  ?>
                        <th><?= lang('cms.promotitle') ?></th>
                        <th><?= lang('Promo Rule')?></th>
                        <th><?= lang('Promo Request ID') ?></th>
                        <th><?=lang('Changed Balance')?></th>
                        <th><?=lang('player.ut10');?></th>
                        <th><?=lang('Request ID');?></th>
                        <th><?=lang('pay.transid');?></th>
                        <th><?=lang('player.ut11');?></th>
                        <th><?=lang('player.ut13');?></th>
                        <th><?=lang('Remarks');?></th>
                    </tr>
                </thead>
                <tfoot>
                    <tr>
                        <th id="transaction-ft-col" style="text-align:right;">
                            <div class="row" id="summary"></div>
                            <div id="bank-summary" style="display: none;">
                                <h4 class="page-header"><?=lang('role.80')?></h4>
                                <div class="row"></div>
                            </div>
                        </th>
                    </tr>
                </tfoot>
            </table>
        </div>
        <div class="panel-body"></div>
        <div class="panel-footer"></div>
    </div>
</div>
<?php if($this->utils->isEnabledFeature('export_excel_on_queue')): ?>
        <form id="_export_excel_queue_form" class="hidden" method="POST" target="_blank">
            <input name='json_search' type="hidden">
        </form>
<?php endif; ?>

<script type="text/javascript">
    $(document).ready(function(){

        <?php if ($this->utils->getConfig('enabled_viplevel_filter_in_transactions')): ?>
        $('#player_level').multiselect({
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
        <?php endif; ?>

        //check if all checked on filter transactions
        $('.trans-check').on('click',function(){
            if($('.trans-check:checked').length != $('.trans-check').length){
                $('#checkall').removeAttr('checked');
            }else{
                $('#checkall').click();
            }
        });

        <?php $col_config = $this->utils->getConfig('transactions_columnDefs'); ?>
            var hidden_cols = [];
        <?php if(!empty($col_config['not_visible_transactions_report'])) : ?>
            var not_visible_cols = JSON.parse("<?= json_encode($col_config['not_visible_transactions_report']) ?>" ) ;
        <?php else: ?>
            var not_visible_cols = [10, 11, 13, 15];
        <?php endif; ?>

        <?php if(!empty($col_config['className_text-transactions_report'])) : ?>
            var text_right_cols = JSON.parse("<?= json_encode($col_config['className_text-transactions_report']) ?>" ) ;
        <?php else: ?>
            var text_right_cols = [ 4,5,6 ];
        <?php endif; ?>

        var amtColSummary = 0, totalPerPage=0;
        var dataTable = $('#transaction-table').DataTable({

            <?php if( ! empty($enable_freeze_top_in_list) ): ?>
            scrollY:        1000,
            scrollX:        true,
            deferRender:    true,
            scroller:       true,
            scrollCollapse: true,
            <?php endif; // EOF if( ! empty($enable_freeze_top_in_list) ):... ?>

            autoWidth: false,
            searching: false,
            <?php if($this->utils->isEnabledFeature('column_visibility_report')){ ?>
                stateSave: true,
            <?php } else { ?>
                stateSave: false,
            <?php } ?>
            dom: "<'panel-body'<'pull-right'B><'pull-right progress-container'>l><'dt-information-summary1 text-info pull-left' i>t<'panel-body'<'pull-right'p><'dt-information-summary2 text-info pull-left' i>>",
            buttons: [
                {
                    extend: 'colvis',
                    postfixButtons: [ 'colvisRestore' ],
                    className: 'btn-linkwater',
                }
                <?php
                    $funcCode = ( $from == "report" ) ? 'export_report_transactions' : 'export_payment_transactions';
                    if( $this->permissions->checkPermissions($funcCode) ) :
                ?>
                ,{
                    text: '<?=lang("CSV Export"); ?>',
                    className:'btn btn-sm btn-portage',
                    action: function ( e, dt, node, config ) {

                        var form_params=$('#form-filter').serializeArray();

                        var d = {'extra_search': form_params, 'export_format': 'csv', 'export_type': export_type,
                          'draw':1, 'length':-1, 'start':0};

                        $("#_export_excel_queue_form").attr('action', site_url('/export_data/transaction_details'));
                        $("#_export_excel_queue_form [name=json_search]").val(JSON.stringify(d));
                        $("#_export_excel_queue_form").submit();

                    }
                }
                <?php endif; ?>
            ],
            columnDefs: [
                { className: 'text-right', targets: text_right_cols },
                { visible: false, targets: not_visible_cols }
            ],
            order: [[0, 'desc']],

            // SERVER-SIDE PROCESSING
            processing: true,
            serverSide: true,
            ajax: function (data, callback, settings) {
                var formData = $('#form-filter').serializeArray();
                data.extra_search = formData;

                $.post(base_url + "api/transactionHistory", data, function(data) {
                    if (data.summary) {
                        $('#summary').html('');

                        var rows =data.summary, len = data.summary.length,footer='';
                        for(var i=0; i<len; i++){
                          footer += '<div class="col-xs-11" ><?=lang('system.word66')?> '+rows[i].transaction_name+':</div><div class="col-xs-1"><a  href="transaction_type_'+rows[i].transaction_type+'" class="link-to-trans" >'+rows[i].amount+'</a></div>'
                        }

                        $('#summary').append(footer);
                        //attach event listener
                        $('.link-to-trans').on('click', function(){
                            var id = $(this).attr('href');
                            $('input:checkbox.checkall').each(function() {
                                $(this).prop("checked", false);
                            });

                            $('#'+id).prop("checked", true);
                            dataTable.ajax.reload();
                            return false;
                        });
                    }

                    if (data.bank_summary) {
                        $('#bank-summary .row').html('');
                        $.each(data.bank_summary, function(key, value) {
                            if (key == ' - ') {
                                key = '<?=lang('lang.norecyet')?>';
                            }
                            $('#bank-summary .row').append('<div class="col-xs-11">'+key+':</div><div class="col-xs-1">'+value+'</div>');
                        });
                        $('#bank-summary').show();
                    } else {
                        $('#bank-summary').hide();
                    }

                    callback(data);
                    if ( dataTable.rows( { selected: true } ).indexes().length === 0 ) {
                        dataTable.buttons().disable();
                    }
                    else {
                      dataTable.buttons().enable();
                    }
                },'json');

            },
            "initComplete": function() {
                var colspanNum = $('#transaction-table thead tr th').length;
                columnVisibilityChange(colspanNum);
            },
            drawCallback : function( settings ) {
                <?php if( ! empty($enable_freeze_top_in_list) ): ?>

                var _min_height = $('.dataTables_scrollBody').find('.table tbody tr').height();
                _min_height = _min_height* 5; // limit min height: 5 rows
                var _scrollBodyHeight = window.innerHeight;
                _scrollBodyHeight -= $('.navbar-fixed-top').height();
                _scrollBodyHeight -= $('.dataTables_scrollHead').height();
                _scrollBodyHeight -= $('.dataTables_scrollFoot').height();
                _scrollBodyHeight -= $('.dataTables_paginate').closest('.panel-body').height();
                _scrollBodyHeight -= 44;// buffer
                if(_scrollBodyHeight > _min_height ){
                    $('.dataTables_scrollBody').css({'max-height': _scrollBodyHeight+ 'px'});
                }
                <?php endif; // EOF if( ! empty($enable_freeze_top_in_list) ):... ?>
            }
        });

        //Send event to iframe parent so that container height will be adjusted
        dataTable.on( 'draw.dt', function () {
            window.parent.$('body').trigger('datatable_drawn');
        });

        $('#hide_main').click(function(){
            setTimeout(function(){
                window.parent.$('body').trigger('datatable_drawn');
            },300);
        });

      	var dateInput = $('#reportrange');
	    var isTime = dateInput.data('time');

	    dateInput.keypress(function(e){
        	e.preventDefault();
        	return false;
        });

	    $('.daterangepicker .cancelBtn ').text('Reset');//.css('display','none');

	    // -- Use reset to current day upon cancel/reset in daterange instead of emptying the value
	    dateInput.on('cancel.daterangepicker', function(ev, picker) {
	        // -- if start date was empty, add a default one
	        if($.trim($(dateInput.data('start')).val()) == ''){
	            var startEl = $(dateInput.data('start'));
	                start = startEl.val();
	                start = start ? moment(start, 'YYYY-MM-DD HH:mm:ss') : moment().startOf('day');
	                startEl.val(isTime ? start.format('YYYY-MM-DD HH:mm:ss') : start.startOf('day').format('YYYY-MM-DD HH:mm:ss'));

	            dateInput.data('daterangepicker').setStartDate(start);
	        }

	        // -- if end date was empty, add a default one
	        if($.trim($(dateInput.data('end')).val()) == ''){
	            var endEl = $(dateInput.data('end'));
	                end = endEl.val();
	                end = end ? moment(end, 'YYYY-MM-DD HH:mm:ss') : moment().endOf('day');
	                endEl.val(isTime ? end.format('YYYY-MM-DD HH:mm:ss') : end.endOf('day').format('YYYY-MM-DD HH:mm:ss'));

	            dateInput.data('daterangepicker').setEndDate(end);
	        }

	        dateInput.val($(dateInput.data('start')).val() + ' to ' + $(dateInput.data('end')).val());
	    });

	    $('#form-filter').submit( function(e) {
	    	e.preventDefault();

	    	// -- Check if date is empty
	    	if($.trim($('#reportrange').val()) == ''){
	    		alert('<?=lang("require_date_range_label")?>');
	    		return;
	    	}

	    	dataTable.ajax.reload();
	    });

        $('#form-filter input[type="text"],#form-filter input[type="number"],#form-filter input[type="email"]').keypress(function (e) {
            if (e.which == 13) {
                $('#form-filter').trigger('submit');
            }
        });

        $('#btn-reset').on('click', function(e) {

            document.getElementById('form-filter').reset();
            $('#belongAff').prop('disabled', false);

            dateInput.trigger('cancel.daterangepicker');

            $('#form-filter').trigger('submit');

            $('.help-block').html('');
            asDirty=false; aeDirty=false;
            $('#toUsername, #fromUsername').hide();
            $('#fromUsernameId,#toUsernameId').val('');

            e.preventDefault();
        });

        var amountStart =0, amountEnd = 0,asDirty=false, aeDirty=false;


        $('#amountStart').blur(function(){
            amountStart = Number($(this).val());
            asDirty =true;
            if(asDirty && aeDirty){
                if(amountStart > amountEnd){
                    $('#from-to-amount-range').html("<?=lang('player.uab12')?>");
                }else{
                    $('#from-to-amount-range').html('');
              }
            }
        });

        $('#amountEnd').blur(function(){
            amountEnd = Number($(this).val());
            aeDirty=true;
            if(asDirty && aeDirty){
                if(amountEnd < amountStart){
                    $('#from-to-amount-range').html("<?=lang('player.uab11')?>");
                }else{
                    $('#from-to-amount-range').html('');
                }
            }
        });

        var currentUsernameType ={from_type:'',fromUsername:'', to_type: '',toUsername:''};

        $('#from_type').change(function(){
            var self =  $(this);

            if(self.val()) {
                $('#fromUsername').show();
                 // if merong value
                if($('#fromUsername').val()){
                    //validate if previous values is differrent to new from_type value
                    if( currentUsernameType.from_type != getUsergroup(self.val())){
                        validateUsername('fromUsername',$('#fromUsername').val(),self.val());
                    }
                }
            }else{
               $('#fromUsername').val('');
               $('#fromUsernameId').val('');
               $('#fromUsername').hide();
               $('#fromUsername').next('span').html('');
            }
        });

        $('#to_type').change(function(){
            var self =  $(this);

            if(self.val()) {
                $('#toUsername').show();
                // if merong value
                if($('#toUsername').val()){
                   //validate if previous values is differrent to new to_type value
                    if( currentUsernameType.to_type != getUsergroup(self.val())){
                        validateUsername('toUsername',$('#toUsername').val(),self.val());
                    }
                }
            }else{
                $('#toUsername').val('');
                $('#toUsernameId').val('');
                $('#toUsername').hide();
                $('#toUsername').next('span').html('');
            }
        });

        $('#fromUsername').blur(function(){
            var self =  $(this);
            if(self.val()){
               //Prevent repeat checking
                if( (currentUsernameType.fromUsername != self.val()) && (currentUsernameType.from_type != $('#from_type').val()) ){
                    validateUsername('fromUsername', self.val(), $('#from_type').val());
                }
            }else{
                self.next('span').html('');
            }
        });

        $('#toUsername').blur(function(){
            var self =  $(this);
            if(self.val()){
                //Prevent repeat checking
                if( (currentUsernameType.toUsername != self.val()) && (currentUsernameType.to_type != $('#to_type').val()) ){
                    validateUsername('toUsername',self.val(), $('#to_type').val());
                }
            }else{
              self.next('span').html('');
            }
        });

        // payment account list toggle
        $('#payment_account_toggle').hide();
        $('#payment_account_toggle_btn').click(function(){
            $('#payment_account_toggle').toggle();
            if($('#payment_account_toggle_btn span').attr('class') == 'fa fa-plus-circle'){
                $('#payment_account_toggle_btn span').attr('class', 'fa fa-minus-circle');
                $('#payment_account_toggle_btn span').html(' <?=lang("Collapse All")?>');
            }
            else{
                $('#payment_account_toggle_btn span').attr('class', 'fa fa-plus-circle');
                $('#payment_account_toggle_btn span').html(' <?=lang("Expand All")?>');
            }
        });

        function getUsergroup(type){
            if(type == '1'){
                return 'adminusers';
            }
            if(type == '2'){
                return 'player';
            }
            if(type == '3'){
                return 'afilliates';
            }
        }

        function validateUsername(field_id,username,userGroup){

            $('#'+field_id).next('span').html('<i>Checking...</i>').css({color:'#008CBA'});

            var  userType ='' ;
            if(userGroup == '1') {userGroup = 'adminusers'; userType = "<?=lang('transaction.from.to.type.1')?>" }
            if(userGroup == '2') {userGroup = 'player'; userType = "<?=lang('transaction.from.to.type.2')?>" }
            if(userGroup == '3') {userGroup = 'affiliates'; userType = "<?=lang('transaction.from.to.type.3')?>" }

            var data = {username:username, userGroup:userGroup};

            $.ajax({
                url : "<?php echo site_url('payment_management/checkUsernames') ?>",
                type : 'POST',
                data : data,
                dataType : "json",
                cache : false,
            }).done(function (data) {
                if (data.status == "success") {
                    var userId = data.userdata[0].id;
                    $('#'+field_id).next('span').html(data.msg+' &nbsp;&nbsp; (<?=lang("player.uab08")?>: <b>'+userId+'</b> )' ).css({color:'#43AC6A'});

                    $('#'+field_id+'Id').val(userId);//username id
                      //Update currentUsernameType obj for preventing repeat checking
                    if(field_id == 'fromUsername'){
                        currentUsernameType.fromUsername = data.userdata[0].username;
                        currentUsernameType.from_type = userGroup;
                    }else{
                        currentUsernameType.toUsername = data.userdata[0].username;
                        currentUsernameType.to_type = userGroup;
                    }
                }
                if (data.status == "notfound") {
                    $('#'+field_id).next('span').html(data.msg).css({color:'#F04124'});
                    $('#'+field_id+'Id').val('');
                    if(field_id == 'fromUsername'){
                        currentUsernameType.fromUsername = '';
                        currentUsernameType.from_type = '';
                    }else{
                        currentUsernameType.toUsername = '';
                        currentUsernameType.to_type = '';
                    }
                }
            }).fail(function (jqXHR, textStatus) {
                throw textStatus;
            });
        }
    });//doc end


    function maxLengthCheck(object) {
        if (object.value.length > object.maxLength)
            object.value = object.value.slice(0, object.maxLength)
    }

    function clearSelect(id){
        $("#"+id).val(null).trigger("change");
    }

    function checkAll(id) {
        var list = document.getElementsByClassName(id);
        var all = document.getElementById(id);

        if (all.checked) {
            for (i = 0; i < list.length; i++) {
                list[i].checked = 1;
            }
        } else {
            all.checked;

            for (i = 0; i < list.length; i++) {
                list[i].checked = 0;
            }
        }
    }

    function showPaymentCategoryModal(id){
        var dst_url = "/player_management/payment_category_modal/";
        open_modal('payment_category_modal', dst_url, "<?php echo lang('Bank/Payment Gateway Type'); ?>");
    }

    function open_modal(name, dst_url, title) {
        var main_selector = '#' + name;

        var label_selector = '#label_' + name;
        $(label_selector).html(title);

        var body_selector = main_selector + ' .modal-body';
        var target = $(body_selector);
        target.html('<center><img src="' + imgloader + '"></center>').delay(1000).load(dst_url);

        $(main_selector).modal('show');
    }

    function filter_payment_category(payment_category) {
        if (payment_category.length > 0) {
            $('input[name="payment_account_all"]').prop('checked', false);
            $('input[name="payment_account_id"]').prop('checked', false);
            $('input[name="payment_account_id_null"]').prop('checked', false);
            
            $.each(payment_category, function(index, value) {
                $('input[name="payment_account_id"][data-flag="' + value + '"]').prop('checked', true);
            });
        }
        close_modal('payment_category_modal');
    }

    function close_modal(name) {
        var selector = '#' + name;
        $(selector).modal('hide');
    }

    //jquery choosen
    $(".chosen-select").chosen({
        disable_search: true,
    });

    var selectedList = [];
    selectedList.push('.buttons-columnVisibility');
    selectedList.push('.buttons-colvisRestore');
    selectedList.push('.buttons-colvisGroup');
    $(document).on("click",selectedList.join(','),function(){
        var colspanNum = $('#transaction-table thead tr th').length;
        columnVisibilityChange(colspanNum);
    });

    function columnVisibilityChange(colspanNum) {
        colspanNum = colspanNum+1;
        $("#transaction-ft-col").attr('colspan',colspanNum);
    }
</script>