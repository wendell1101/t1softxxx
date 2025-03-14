<div class="panel panel-primary">
    <div class="panel-heading">
        <h3 class="panel-title">
            <i class="icon-diamond"></i><b> <?= lang($data[0]['groupName']); ?> <?= lang('player.lvl'); ?></b>
            <div class="pull-right">

            <?php if ($this->utils->isEnabledMDB() && $this->utils->_getSyncVipGroup2othersWithMethod('SyncMDBSubscriber::syncMDB') ) : ?>
                <label class="control-label" for="sync_vip_group_to_others" >
                    <?=lang('Sync To Currency');?>
                    <!-- readonly in checkbox, ref. to https://stackoverflow.com/a/12267350 -->
                    <input type="checkbox"
                        name="sync_vip_group_to_others"
                        id="sync_vip_group_to_others"
                        value="sync_vip_group_to_others"
                        checked="checked"
                        onclick="return false;" onkeydown="e = e || window.event; if(e.keyCode !== 9) return false;" />
                </label>

                <a href="javascript:void(0)" data-vipsettingid="<?=$vipSettingId?>" class="btn  btn-xs btn-primary btn_incgrplvlcnt">
                    <span class="glyphicon glyphicon-plus"></span> <?= lang('player.incgrplvlcnt'); ?>
                </a>

                <a href="javascript:void(0)" data-vipsettingid="<?=$vipSettingId?>" class="btn btn-xs btn-primary btn_decgrplvlcnt" data-loading-text="<?=lang('Processing...')?>" >
                    <span class="glyphicon glyphicon-minus"></span> <?= lang('player.decgrplvlcnt'); ?>
                </a>
            <?php else:  // Not under utils::isEnabledMDB() = true ?>
                <a href="<?=BASEURL . 'vipsetting_management/increaseVipGroupLevel/' . $vipSettingId?>" class="btn btn-default btn-sm">
                    <span class="glyphicon glyphicon-plus"></span> <?=lang('player.incgrplvlcnt');?>
                </a>
                <a href="javascript:void(0)" data-href="<?=BASEURL . 'vipsetting_management/decreaseVipGroupLevelWithDetectPlayerExists/' . $vipSettingId?>" class="btn btn-default btn-sm btn_decgrplvlcnt_prompt_exists_players">
                    <span class="glyphicon glyphicon-minus"></span> <?=lang('player.decgrplvlcnt');?>
                </a>
            <?php endif; ?>

                <!-- Back to vipGroupSettingList -->
                <a href="<?= BASEURL . 'vipsetting_management/vipGroupSettingList' ?>" class="btn btn-sm btn-primary" id="add_news">
                    <span class="glyphicon glyphicon-remove"></span>
                </a>
            </div>
        </h3>

        <div class="clearfix"></div>
    </div>
    <div class="panel panel-body" id="details_panel_body">
        <div class="row">
            <div class="col-md-12">
                <div id="tag_table">
                    <table class="table table-striped table-hover" id="my_table" style="margin: 0px 0 0 0; width: 100%;">
                        <thead>
                            <tr>
                                <th></th>
                                <th><?= lang('player.viplvl'); ?></th>
                                <th><?= lang('player.lvlname'); ?></th>
                                <th><?= lang('player.mindep'); ?></th>
                                <th><?= lang('player.maxdep'); ?></th>
                                <th><?= lang('player.maxdailywith'); ?></th>
                                <th><?= lang('Auto tick new games in cashback tree'); ?></th>
                                <th><?= lang('Total number of players'); ?></th>
                                <th><?= lang('lang.action'); ?></th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php
                            if (!empty($data)) {
                                foreach ($data as $data) {
                                    $cnt = 0;
                            ?>
                                    <tr>
                                        <td></td>
                                        <td><?= $data['vipLevel'] == '' ? '<i class="help-block"><?= lang("lang.norecyet"); ?><i/>' : $data['vipLevel'] ?></td>
                                        <td><?= $data['vipLevelName'] == '' ? '<i class="help-block"><?= lang("lang.norecyet"); ?><i/>' : lang($data['vipLevelName']) ?></td>
                                        <td><?= $data['minDeposit'] == '' ? '<i class="help-block"><?= lang("lang.norecyet"); ?><i/>' : $data['minDeposit'] ?></td>
                                        <td><?= $data['maxDeposit'] == '' ? '<i class="help-block"><?= lang("player.nomindep"); ?><i/>' : $data['maxDeposit'] ?></td>
                                        <td><?= $data['dailyMaxWithdrawal'] == '' ? '<i class="help-block"><?= lang("lang.nopointreq"); ?><i/>' : $data['dailyMaxWithdrawal'] ?></td>
                                        <td><?= $data['auto_tick_new_game_in_cashback_tree']  ? lang('lang.yes') : lang('lang.no') ?></td>
                                        <td>
                                            <span class="get_player_count_by_levelid" data-level_id="<?=$data['vipsettingcashbackruleId']?>"><?=lang('pay.procssng')?></span>
                                        </td>

                                        <td>
                                            <div class="actionVipGroup">
                                                <a href="<?= BASEURL . 'vipsetting_management/editVipGroupLevel/' . $data['vipsettingcashbackruleId'] ?>">
                                                    <button class="btn btn-sm btn-scooter"><?= lang('player.editset'); ?></button>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php $cnt++;
                                }
                            } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
    // include admin/application/views/includes/vipsetting_sync.php
    include __DIR__ . '/../../includes/vipsetting_sync.php';
?>

<script type="text/javascript">
    $(document).ready(function() {
        var vipsetting_sync =  VIPSETTING_SYNC.init({
            DRY_RUN_MODE_LIST: gDRY_RUN_MODE_LIST
            , CODE_DECREASEVIPGROUPLEVEL: gCODE_DECREASEVIPGROUPLEVEL
        });
        vipsetting_sync.assignLangList2Options(theLangList4vipsetting_sync);
        vipsetting_sync.onReadyInView('<?=pathinfo(basename(__FILE__), PATHINFO_FILENAME); // aka. view_vip_setting_rules ?>');
    });

    $(document).ready(function() {
        var dataTable = $('#my_table').DataTable({
            "responsive": {
                details: {
                    type: 'column'
                }
            },
            "dom": "<'panel-body' <'pull-right'f><'pull-right progress-container'>l>t<'panel-body'<'pull-right'p>i>",
            buttons: [{
                extend: 'colvis',
                postfixButtons: ['colvisRestore']
            }],
            "columnDefs": [{
                className: 'control',
                orderable: false,
                targets: 0
            }],
            "order": [1, 'asc']
        });

        dataTable.on( 'draw.dt', function (e, settings) {

            $('.get_player_count_by_levelid').each(function(indexNumber, currEl){
                var curr$El = $(currEl);
                get_player_count_by_levelid(curr$El.data('level_id'));
            });

        });// EOF dataTable.on( 'draw.dt', function (e, settings) {

        function get_player_count_by_levelid(levelId){
            var _ajax = $.ajax({
                        'url' : base_url+ "api/get_player_count_by_levelid/"+ levelId,
                        'type' : 'GET',
                        'dataType' : "json",
                        'data': {},
                        'success' : function(data){
                            if(data['levelId']){
                                $('.get_player_count_by_levelid[data-level_id="'+ data['levelId']+ '"]').html(data['count']);
                            }
                        }
                    });
            return _ajax;
        } // EOF get_player_count_by_levelid()
    });

    function do_increaseVipGroupLevel(levelId){
        var uri = "<?= BASEURL . 'vipsetting_management/increaseVipGroupLevel/'?>";
        uri += levelId;
        uri += "/";
        if( $('input[name="sync_vip_group_to_others"]:checked').length > 0){
            uri += "sync_vip_group_to_others";
        }
        window.location.href  = uri;
        // console.log('do_increaseVipGroupLevel.uri:', uri);
    }
    function do_decreaseVipGroupLevel(levelId){
        var uri = "<?= BASEURL . 'vipsetting_management/decreaseVipGroupLevel/'?>";
        uri += levelId;
        uri += "/";
        if( $('input[name="sync_vip_group_to_others"]:checked').length > 0){
            uri += "sync_vip_group_to_others";
        }
        window.location.href  = uri;
        // console.log('do_decreaseVipGroupLevel.uri:', uri);
    }
</script>