<style>
.table .glyphicon-eye-open, .table .glyphicon-picture {
    color: #3F61B4;
}
</style>
<div class="row">
    <div class="col-md-12">
        <div class="panel panel-primary">
            <div class="panel-heading custom-ph">
                <h4 class="panel-title custom-pt pull-left"><i class="icon-settings"></i> <?= lang('cms.13') ?></h4>
                <div class="pull-right">
                    <a href="/cms_management/refreshNavigationGameType/" title="View Providers" class="btn btn-info btn-xs">
                        <span class="glyphicon glyphicon-refresh"></span> &nbsp; <?= lang('cms.navigation.gameType.refresh') ?>
                    </a>
                </div>
                <div class="clearfix"></div>
            </div>
            
            <div class="panel-body m-t-20">
                <div class="col-md-12">
                    <table class="table table-condensed table-bordered no-footer table-striped" id="DataTables_Table_0" role="grid">
                        <colgroup>
                        </colgroup>
                        <thead>
                        <tr role="row">
                            <th class="sorting_disabled" rowspan="1" colspan="1"><?= lang('cms.navigation.englishName') ?></th>
                            <th class="sorting_disabled" rowspan="1" colspan="1"><?= lang('cms.navigation.chineseName') ?></th>
                            <th class="sorting_disabled" rowspan="1" colspan="1"><?= lang('cms.navigation.indonesianName') ?></th>
                            <th class="sorting_disabled" rowspan="1" colspan="1"><?= lang('cms.navigation.vietnameseName') ?></th>
                            <th class="sorting_disabled" rowspan="1" colspan="1"><?= lang('cms.navigation.koreanName') ?></th>
                            <th class="sorting_disabled" rowspan="1" colspan="1"><?= lang('cms.navigation.thailandName') ?></th>
                            <th class="sorting_disabled" rowspan="1" colspan="1"><?= lang('cms.navigation.gameType') ?></th>
                            <th class="sorting_disabled" rowspan="1" colspan="1"><?= lang('cms.navigation.order') ?></th>
                            <th class="sorting_disabled" rowspan="1" colspan="1"><?= lang('cms.navigation.status') ?></th>
                            <th class="sorting_disabled" rowspan="1" colspan="1"><?= lang('cms.navigation.action') ?></th>
                        </tr>
                        </thead>
                        <tbody>
                            
                            <?php if(!empty($game_types)) : ?>
                                <?php foreach($game_types as $key => $game_type) : ?>
                                <tr role="row" class="odd">
                                    <td><?= $game_type['game_type_lang']['en'] ?></td>
                                    <td><?= $game_type['game_type_lang']['cn'] ?></td>
                                    <td><?= $game_type['game_type_lang']['id'] ?></td>
                                    <td><?= $game_type['game_type_lang']['vt'] ?></td>
                                    <td><?= $game_type['game_type_lang']['kr'] ?></td>
                                    <td><?= !empty($game_type['game_type_lang']['th']) ? $game_type['game_type_lang']['th'] : $game_type['game_type_lang']['en'] ?></td>
                                    <td><?= $game_type['game_type_code'] ?></td>
                                    <td><?= $game_type['order'] ?></td>
                                    <td>
                                        <?php if($game_type['status'] != 0) : ?>
                                        <span class="label label-success"><?= lang('cms.navigation.active') ?></span>
                                        <?php else : ?>
                                        <span class="label label-info"><?= lang('cms.navigation.inactive') ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="javascript:void(0)" title="<?= lang('cms.navigation.edit') ?>" class="modify-game-type" data-id='<?= $game_type['id'] ?>'>
                                            <span class="glyphicon glyphicon-edit"></span>
                                        </a>
                                        <a href="/cms_management/viewNavigationGamePlatform/<?= $game_type['id'] ?>" title="<?= lang('cms.navigation.providers.view') ?>">
                                            <span class="glyphicon glyphicon-eye-open"></span>
                                        </a>
                                        <?php if(!empty($game_type['icon'])) : ?>
                                        <a href="javascript:void(0)" title="<?= lang('cms.navigation.icon.view') ?>" data-icon="<?= site_url('resources/images/cms_game_types/' . $game_type['icon']) ?>" class="view-game-platform_icon">
                                            <span class="glyphicon glyphicon-picture"></span>
                                        </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php else : ?>
                                <td colspan="10" class="text-center"><?= lang('cms.navigation.noGameTypeAdded') ?></td>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modify-game-type-modal" tabindex="-1" role="dialog">
    <div class="vertical-alignment-helper">
        <div class="modal-dialog vertical-align-center" role="document">
            <div class="modal-content">
                <form id="modify-game-type" action="/cms_management/postModifyGameType" method="POST" enctype="multipart/form-data">
                    <div class="modal-header modal-header-info">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title m-5"><?= lang('cms.navigation.editGameType') ?></h4>
                    </div>
                    <div class="modal-body">

                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary"><?=lang('Save') ?></button>
                        <button type="button" class="btn btn-danger" data-dismiss="modal"><?=lang('Cancel') ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="icon-modal" tabindex="-1" role="dialog">
    <div class="vertical-alignment-helper">
        <div class="modal-dialog vertical-align-center" role="document">
            <div class="modal-content">
                <form id="modify-game-type" action="/cms_management/postModifyGamePlatform" method="POST" enctype="multipart/form-data">
                    <div class="modal-header modal-header-info">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title m-5"><?= lang('cms.navigation.icon.view') ?></h4>
                    </div>
                    <div class="modal-body">
                        <img style="width: 100%" id="game_platform_icon">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-danger" data-dismiss="modal"><?=lang('Close') ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    $( document ).ready(function() {

        function showFormModal(id) {

            var url = "/cms_management/viewModifyGameType/" + id;
            var main_selector = "#modify-game-type-modal"
            var body_selector = main_selector + ' .modal-body';
            var submit_button_selector = main_selector + " .modal-footer button[type='submit']";
            var target = $(body_selector);
            $(submit_button_selector).attr("disabled", true);
            target.html('<center><img style="width: auto;" src="' + imgloader + '"></center>')
            .delay(1000)
            .load(url, function() {
                $(submit_button_selector).removeAttr("disabled");
            });
            $(main_selector).modal('show');
        }

        $(".modify-game-type").click(function() {
            var id = $(this).data('id');
            showFormModal(id);
        });

        $('.view-game-platform_icon').click(function() {
            var icon = $(this).data('icon');
            $('#game_platform_icon').attr('src', icon);
            $('#icon-modal').modal('show');
        });

    })
</script>