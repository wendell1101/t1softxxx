<style>
.col_content .input-group {
    width: 80%;
}

.col_sub_title {
    text-align: right;
}

#createandapplybonusmultiModal .row {
    margin-bottom: 4px;
    margin-top: 4px;
}

div.habaneroGameTree {
    min-height: 100px;
    max-height: 200px;
    width: 100%;
    overflow-y: auto;
    background-color: #8080801f;
}

#createandapplybonusmultiModal .form-group {
    width: 100%;
}

.font-bold {
  font-weight: bold;
}

.col_title.control-label {
    margin-bottom: 8px;
}
</style>

<!-- Level Upgrade Setting -->
<div id="createandapplybonusmultiModal" class="modal fade " role="dialog">

    <div class="modal-dialog">
        <!-- Modal content-->
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title"><?= lang('Update CreateAndApplyBonusMulti Settings'); ?></h4>
            </div>
            <div class="modal-body custom-height-modal">
                <form class="form-inline" id="createandapplybonusmulti_form" action="<?=site_url('marketing_management/preparePromo')?>" method="post" role="form" onsubmit="return valthis();" enctype="multipart/form-data">
                    <div class="container-fluid">
                        <fieldset>
                            <legend>
                                <div class="h5 font-bold">
                                    <?=lang('Description')?>
                                </div>
                            </legend>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="col_note">
                                        <?=lang('The Release Bonus Must Be Automatic.')?>
                                    </div>
                                </div>
                            </div>
                        </fieldset>

                        <fieldset>
                            <legend>
                                <div class="h5 font-bold">
                                    <?=lang('The available date time')?>
                                </div>
                            </legend>
                            <div class="row row-dtstartendutc">
                                <div class="col-md-12">
                                    <div class="col_title control-label">
                                        <?=lang('The coupon available date time range')?>
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="col_content">
                                        <div class="input-group">
                                            <input name="DtStartEndUTC" class="form-control input-sm dateInput" data-time="true" data-start=".DtStartUTC" data-end=".DtEndUTC" data-extra-attr="getExtraAttr4field_DtStartEndUTC()" data-future="TRUE">
                                            <input type="hidden" class="DtStartUTC" name="DtStartUTC" value="2020-09-01 00:00:00">
                                            <input type="hidden" class="DtEndUTC" name="DtEndUTC" value="2020-12-31 23:59:59">
                                        </div>
                                    </div>
                                    <div class="col_content col_tip text-danger hide">the tip message</div>
                                </div>
                            </div> <!-- EOF .row-dtstartendutc -->
                        </fieldset>

                        <fieldset>
                            <legend>
                                <div class="h5 font-bold">
                                    <?=lang('Expire after days')?>
                                </div>
                            </legend>
                            <div class="row row-expireafterdays">
                                <div class="col-md-12">
                                    <div class="col_title control-label">
                                        <?=lang('Number of days to expire the BonusBalance of the player once the coupon has been applied.')?>
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="col_content">
                                        <input type="text" name="ExpireAfterDays" class="" value="3">
                                    </div>
                                    <div class="col_content col_tip text-danger hide">the tip message</div>
                                </div>
                            </div> <!-- EOF .row-expireafterdays -->
                        </fieldset>

                        <fieldset>
                            <legend>
                                <div class="h5 font-bold">
                                    <?=lang('Number Of Free Spins')?>
                                </div>
                            </legend>
                            <div class="row row-numberoffreespins">
                                <div class="col-md-12">
                                    <div class="col_title control-label">
                                        <?=lang('Number of free spins to award.')?>
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="col_content">
                                        <input type="text" name="NumberOfFreeSpins" class="" value="10">
                                    </div>
                                    <div class="col_content col_tip text-danger hide">the tip message</div>
                                </div>
                            </div> <!-- EOF .row-numberoffreespins -->
                        </fieldset>

                        <fieldset>
                            <legend>
                                <div class="h5 font-bold">
                                    <?=lang('Game Key Names')?>
                                </div>
                            </legend>
                            <div class="row row-habanerogametree">
                                <div class="col-md-12">
                                    <div class="col_title control-label">
                                        <?=lang('The list of Games to enable the free spins on')?>
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="col_content">
                                        <div class="form-group">
                                            <div class="habaneroGameTree"></div>
                                            <input type="hidden" name="_GameKeyNames" value="10">
                                        </div>
                                    </div>
                                    <div class="col_content col_tip text-danger hide">the tip message</div>
                                </div>
                            </div><!-- EOF .row-habanerogametree -->
                        </fieldset>

                        <fieldset>
                            <legend>
                                <div class="h5 font-bold">
                                    <?=lang('Coupon Currency Data')?>
                                </div>
                            </legend>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="col_title control-label">
                                        <?=lang('A position in game bet config for the currency')?>
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="row row-currencycode">
                                        <div class="col-md-3">
                                            <div class="col_sub_title control-label">
                                                <?=lang('CurrencyCode')?>
                                            </div>
                                        </div>
                                        <div class="col-md-9">
                                            <div class="col_sub_content">
                                                <input type="text" name="CurrencyCode" class="" value="THB.">
                                            </div>
                                            <div class="col_sub_content col_tip text-danger hide">the tip message</div>
                                        </div>
                                    </div><!-- EOF .row-currencycode -->
                                    <div class="row row-coinposition">
                                        <div class="col-md-3">
                                            <div class="col_sub_title control-label">
                                                <?=lang('CoinPosition')?>
                                            </div>
                                        </div>
                                        <div class="col-md-9">
                                            <div class="col_sub_content">
                                                <input type="text" name="CoinPosition" class="" value="01">
                                            </div>
                                            <div class="col_sub_content col_tip text-danger hide">the tip message</div>
                                        </div>
                                    </div><!-- EOF .row-coinposition -->
                                </div>
                            </div>
                        </fieldset>
                    </div>
                </form>
            </div> <!-- EOF .modal-body.custom-height-modal -->
            <div class="modal-footer">
                <div class="row row-footer">
                    <div class="col-md-offset-5 col-md-4" style="text-align: center;">
                        <button type="button" class="btn btn-success btn_append"><?= lang('Update the Customize(JS)'); ?></button>
                    </div>
                    <div class="col-md-3" style="text-align: center;">
                        <button type="button" class="btn btn-default" data-dismiss="modal"><?= lang('lang.cancelEdit'); ?></button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">

    function getExtraAttr4field_DtStartEndUTC(_ASCAABM){
        var extraAttr = [];
        extraAttr['ranges'] = [];
        extraAttr['ranges']['<?=lang('Until the end of this Week')?>'] = [moment().startOf('day'), moment().endOf('isoWeek')];
        extraAttr['ranges']['<?=lang('Until the end of this Month')?>'] = [moment().startOf('day'), moment().endOf('month')];
        extraAttr['ranges']['<?=lang('Until the end of this Year')?>'] = [moment().startOf('day'), moment().endOf('year')];
        return extraAttr;
    }
</script>