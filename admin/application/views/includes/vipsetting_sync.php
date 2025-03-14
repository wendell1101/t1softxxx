<!-- Check Vip Group Levels In Each Other Currency Database Modal Start -->

<!-- /// checkVipGroupLevelsInEOCD aka. checkVipGroupLevelsInEach Other Currency Database -->
<div class="modal fade" id="checkVipGroupLevelsInEOCDModal" tabindex="-1" role="dialog" aria-labelledby="deletePromoruleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="checkVipGroupLevelsInEOCDModalLabel"><?=lang('Check VIP Group Levels in each other currency database')?></h4>
            </div>
            <div class="modal-body checkVipGroupLevelsInEOCDModalBody">


                <div class="loading">
                <img class="loading-image" src="<?=$this->utils->imageUrl('ajax-loader.gif')?>" alt="<?=lang('Loading...')?>">
                </div>


                <div class="container-fluid checkVipGroupLevelsInEOCDReport">
                        <div class="row">
                            <div class="col-md-12 report_intro">intro</div>
                        </div>
                        <div class="row">
                            <div class="col-md-12 report_intro_details"> group levels of currency </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12 report_result">result intro</div>
                        </div>
                </div>
            </div>
            <div class="modal-footer">
                <input type="hidden" class="vipsettingid">
                <input type="hidden" class="dryrun" value="1">
                <input type="hidden" class="dryrun4continue" value="1">
                <input type="hidden" class="_data">
                <input type="hidden" class="triggerFrom" value="">
                <button type="button" class="btn btn-danger btn_continue_in_ng" id="continue_in_ng" ><?=lang('Ignore the warning and Continue')?></button>
                <button type="button" class="btn btn-warning btn_continue_in_ok" id="continue_in_ok"><?=lang('Ok and Continue')?></button>
                <button type="button" class="btn btn-default btn_dismiss"><?=lang('Cancel')?></button>
                <button type="button" class="btn btn-default btn_close"><?=lang('Close')?></button>
                <button type="button" class="btn btn-success btn_refresh"><?=lang('Refresh Page')?></button>
            </div>
        </div>
    </div>
</div>
<!-- Check Vip Group Levels In Each Other Currency Database Modal End -->


<script type="text/template" id="tpl-currency_database">
    <!-- tpl-currency_database params,
        currency_key
    -->

    <div class="container-fluid currency_database" data-currency_key="${currency_key}">
        <div class="row currency_header">
            <div class="col-md-2">
                <?=lang('Currency')?>:
            </div>
            <div class="col-md-10">
                ${currency_key}
            </div>
        </div> <!-- EOF .currency_header -->

        <!-- #tpl-group_levels_container will applied here -->

    </div> <!-- EOF .currency_database -->

</script>

<script type="text/template" id="tpl-empty_group_container">
    <!-- tpl-empty_group_container params,
    -->
    <div class="row empty_group_container">
        <div class="col-md-4 group_name_header">
            <?=lang('empty')?>
        </div> <!-- EOF .group_name_header -->
        <div class="col-md-4 level_list_container" >
            <?=lang('empty')?>
        </div> <!-- EOF .level_list_container -->
        <div class="col-md-4 group_result_intro">
            ${group_result_intro}
        </div> <!-- EOF .group_result_intro -->
    </div> <!-- EOF .empty_group_container -->
</script>

<script type="text/template" id="tpl-group_levels_container">

    <!-- tpl-group_levels_container params,
        group_name
        vipSettingId
        group_result_intro
    -->
    <div class="row group_levels_container" data-vipsettingid="${vipSettingId}">

        <div class="col-md-4 group_name_header">
            ${group_name}
        </div> <!-- EOF .group_name_header -->


        <div class="col-md-4 level_list_container" >
            <!--
                todo,  #tpl-level_list_row OR #tpl-empty_level_row will applied here
            -->
        </div> <!-- EOF .level_list_container -->

        <div class="col-md-4 group_result_intro">
            ${group_result_intro}
        </div> <!-- EOF .group_result_intro -->

    </div> <!-- EOF .group_levels_container -->
</script>


<script type="text/template" id="tpl-level_list_row">
    <!-- tpl-level_list_row params,
        vipLevelName
        vipSettingId
        vipsettingcashbackruleId
        deleted
        deleted_in_other
        caseStr
    -->
        <div class="row" data-vipsettingid="${vipSettingId}">
            <div class="col-md-12"
                data-vipsettingcashbackruleid="${vipsettingcashbackruleId}"
                data-deleted="${deleted}"
                data-deleted_in_other="${deleted_in_other}"
                data-casestr="${caseStr}" >
                ${vipLevelName}
            </div>
        </div>

</script>

<script type="text/template" id="tpl-empty_level_row">
    <!-- tpl-empty_level_row params,
        vipSettingId
    -->
    <div class="row" data-vipsettingid="${vipSettingId}">
        <div class="col-md-12">
            <?=lang('empty')?>
        </div>
    </div>

</script>
<style type="text/css">

.checkVipGroupLevelsInEOCDModalBody .loading {
  width: 100%;
  height: 100%;
  top: 0;
  left: 0;
  position: fixed;
  display: block;
  opacity: 0.7;
  background-color: #fff;
  z-index: 99;
  text-align: center;
}
.checkVipGroupLevelsInEOCDModalBody .loading-image {
  position: absolute;
  top: 100px;
  /* left: 240px; */
  z-index: 100;
}

.container-fluid.currency_database {
    border-top-style: groove;
    border-bottom-style: groove;
    border-top-width: medium;
    border-bottom-width: medium;
}
.row.currency_header {
    background-color: #ddd;
    border-bottom: gray;
    border-bottom-style: dashed;
    border-bottom-width: thin;
    padding-bottom: 4px;
    padding-top: 4px;
}
.row.group_levels_container {
    background-color: #eee;
    border-bottom: gray;
    /* border-bottom-style: dashed; */
    border-bottom-width: thin;
    padding-bottom: 4px;
    padding-top: 4px;
}
.group_result_intro li[data-is_warning="1"] {
    text-decoration-line: underline;
    text-decoration-color: #e99;
    text-decoration-style: double;
}
/** dryrun function */
[data-deleted_in_other="1"] {
    color: #aaa;
}
[data-casestr="softDeleteVipLevel"] {
    text-decoration-line: line-through;
}

[data-casestr="moveVipLevel"] {
    font-style:italic;
}
</style>

<script type="text/javascript">
var theLangList4vipsetting_sync = {};
theLangList4vipsetting_sync['warning4SoftDeleteVipLevelEvent'] = '<?=lang('The level, "${affected_level_name}" will be deleted.')?>';
theLangList4vipsetting_sync['warning4OverrideVipGroupEvent'] = '<?=lang('The Group,"${affected_group_name}" will be overrided.')?>';
theLangList4vipsetting_sync['warning4AdjustPlayerlevel'] = '<?=lang('The ${playerIds_count} player(s)  will updated from the level,"${affected_level_name}" to default level.')?>';
theLangList4vipsetting_sync['warning4RefreshLevelCountOfVipGroupEvent'] = '<?=lang('The Group,"${affected_group_name}" levels amount will be refresh.')?>';
theLangList4vipsetting_sync['warning4NewVipGroupEvent'] = '<?=lang('The Group,"${affected_group_name}" will be new.')?>';
theLangList4vipsetting_sync['warning4IncreaseVipGroupLevelEvent'] = '<?=lang('The Group,"${affected_group_name}" levels will be increased one, "${affected_level_name}".')?>';
theLangList4vipsetting_sync['warning4OverrideVipLevelEvent'] = '<?=lang('The VIP Level, "${affected_level_name}" will be overrided.')?>';
theLangList4vipsetting_sync['warning4MoveVipLevelEvent'] = '<?=lang('The VIP Level, "${from_level_name}" will be moved to "${to_level_name}" of the VIP group, "${to_group_name}".')?>';

theLangList4vipsetting_sync['resultIntro4AdjustPlayerlevel'] = '<?=lang('The player${plural} will be moved to default level.')?>';
theLangList4vipsetting_sync['resultIntro4OverrideVipLevel'] = '<?=lang('The VIP Level${plural} will be overrided.')?>';
theLangList4vipsetting_sync['resultIntro4SoftDeleteVipLevel'] = '<?=lang('Some level${plural} will be removed.')?>';
theLangList4vipsetting_sync['resultIntro4OverrideVipGroup'] = '<?=lang('Some Group${plural} will be overrided.')?>';
theLangList4vipsetting_sync['resultIntro4MoveVipLevel'] = '<?=lang('The VIP Level will be moved to another VIP group. Please check the original group and levels.')?>';

theLangList4vipsetting_sync['inVipGroupLevels'] = '<?=lang('In VIP Group Levels')?>';
theLangList4vipsetting_sync['con.pym01'] = '<?=lang('con.pym01')?>';
theLangList4vipsetting_sync['con.vsm12'] = '<?=lang('con.vsm12')?>';


theLangList4vipsetting_sync['playerInLevel'] = '<?=lang('There is a player in the VIP level.')?>';
theLangList4vipsetting_sync['playerInLevelWithPluralNumber'] = '<?=lang('There are %s players in the VIP level.')?>';


var gDRY_RUN_MODE_LIST = {};
<?php if( class_exists('Multiple_db_model', false)): ?>
    gDRY_RUN_MODE_LIST.IN_DISABLED = <?= Multiple_db_model::DRY_RUN_MODE_IN_DISABLED?>;
    gDRY_RUN_MODE_LIST.IN_NORMAL = <?= Multiple_db_model::DRY_RUN_MODE_IN_NORMAL?>;
    gDRY_RUN_MODE_LIST.IN_INCREASED_LEVELS = <?= Multiple_db_model::DRY_RUN_MODE_IN_INCREASED_LEVELS?>;
    gDRY_RUN_MODE_LIST.IN_DECREASED_LEVELS = <?= Multiple_db_model::DRY_RUN_MODE_IN_DECREASED_LEVELS?>;
    gDRY_RUN_MODE_LIST.IN_ADD_GROUP = <?= Multiple_db_model::DRY_RUN_MODE_IN_ADD_GROUP?>;

    <?php // the below items mean it will be execute sync( with P.K.id ) ?>
    gDRY_RUN_MODE_LIST.IN_DISABLED_NORMAL = <?= Multiple_db_model::DRY_RUN_MODE_IN_DISABLED_NORMAL?>;
    gDRY_RUN_MODE_LIST.IN_DISABLED_INCREASED_LEVELS = <?= Multiple_db_model::DRY_RUN_MODE_IN_DISABLED_INCREASED_LEVELS?>;
    gDRY_RUN_MODE_LIST.IN_DISABLED_DECREASED_LEVELS = <?= Multiple_db_model::DRY_RUN_MODE_IN_DISABLED_DECREASED_LEVELS?>;
    gDRY_RUN_MODE_LIST.IN_DISABLED_ADD_GROUP = <?= Multiple_db_model::DRY_RUN_MODE_IN_DISABLED_ADD_GROUP?>;
<?php endif; // EOF if( class_exists('Multiple_db_model', false)): ?>

    var gCODE_DECREASEVIPGROUPLEVEL = {};
    gCODE_DECREASEVIPGROUPLEVEL.CODE_DECREASEVIPGROUPLEVEL_IN_LEVEL_EXIST_PLAYER = "<?=Group_level::CODE_DECREASEVIPGROUPLEVEL_IN_LEVEL_EXIST_PLAYER?>";
    gCODE_DECREASEVIPGROUPLEVEL.CODE_DECREASEVIPGROUPLEVEL_IN_DECREASE_COMPLETED = "<?=Group_level::CODE_DECREASEVIPGROUPLEVEL_IN_DECREASE_COMPLETED?>";
    gCODE_DECREASEVIPGROUPLEVEL.CODE_DECREASEVIPGROUPLEVEL_IN_DECREASE_NO_GOOD = "<?=Group_level::CODE_DECREASEVIPGROUPLEVEL_IN_DECREASE_NO_GOOD?>";
</script>
<!-- Check Vip Group Levels In Each Other Currency Database Modal End -->


<!-- /// promptVipGroupLevelHasPlayersModal -->
<div class="modal fade" id="promptVipGroupLevelHasPlayersModal" tabindex="-1" role="dialog" aria-labelledby="promptPromoruleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="promptVipGroupLevelHasPlayersModalLabel"><?=lang('Detect players exist in the VIP level')?></h4>
            </div>
            <div class="modal-body promptVipGroupLevelHasPlayersModalBody">

                <div class="loading">
                    <img class="loading-image" src="<?=$this->utils->imageUrl('ajax-loader.gif')?>" alt="<?=lang('Loading...')?>">
                </div>

                <div class="container-fluid promptVipGroupLevelHasPlayers">
                        <div class="row">
                            <div class="col-md-12 msg4promptVipGroupLevelHasPlayersModal" data-default_msg="<?=lang('Progressive')?>">

                            </div>
                        </div>
                </div>
            </div>
            <div class="modal-footer">
                <input type="hidden" class="ajax-uri">
                <button type="button" class="btn btn-success btn_next"><?=lang('Next')?></button>
                <button type="button" class="btn btn-default btn_close"><?=lang('Close')?></button>
            </div>
        </div>
    </div>
</div>
<!-- promptVipGroupLevelHasPlayersModal End -->
<style type="text/css">

.promptVipGroupLevelHasPlayersModalBody .loading {
  width: 100%;
  height: 100%;
  top: 0;
  left: 0;
  position: fixed;
  display: block;
  opacity: 0.7;
  background-color: #fff;
  z-index: 99;
  text-align: center;
}
.promptVipGroupLevelHasPlayersModalBody .loading-image {
  position: absolute;
  top: 50%;
  /* left: 240px; */
  z-index: 100;
}
</style>


<!-- /// promptVipGroupLevelOfMDBHasPlayersModal -->
<div class="modal fade" id="promptVipGroupLevelOfMDBHasHasPlayersModal" tabindex="-1" role="dialog" aria-labelledby="promptVipGroupLevelOfMDBHasHasPlayersModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="promptVipGroupLevelOfMDBHasHasPlayersModalLabel"><?=lang('Detect players exist in the VIP level')?></h4>
            </div>
            <div class="modal-body promptVipGroupLevelOfMDBHasHasPlayersModalBody">

                <div class="loading">
                    <img class="loading-image" src="<?=$this->utils->imageUrl('ajax-loader.gif')?>" alt="<?=lang('Loading...')?>">
                </div>

                <div class="container-fluid promptVipGroupLevelOfMDBHasHasPlayers">
                        <div class="row">
                            <div class="col-md-12 msg4promptVipGroupLevelOfMDBHasPlayersModal" data-default_msg="<?=lang('Progressive')?>">

                            </div>
                        </div>
                </div>
            </div>
            <div class="modal-footer">
                <input type="hidden" class="ajax-uri">
                <button type="button" class="btn btn-success btn_next"><?=lang('Next')?></button>
                <button type="button" class="btn btn-default btn_close"><?=lang('Close')?></button>
            </div>
        </div>
    </div>
</div>
<!-- promptVipGroupLevelOfMDBHasPlayersModal End -->