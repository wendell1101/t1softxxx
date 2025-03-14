<div class="panel panel-primary">
    <div class="panel-heading custom-ph">
        <h4 class="panel-title custom-pt"><i class="glyphicon glyphicon-picture"></i> <?=lang('cms.03');?>
            <a href="#" class="btn  pull-right btn-xs <?=$this->utils->getConfig('use_new_sbe_color') ? 'btn-info' : 'btn-default'?>" id="add_cmsbanner_sec">
                <span id="addBannerCmsGlyhicon"> <i class="fa fa-plus-circle"></i> <?=lang('Add New Banner');?> </span>
            </a>
        </h4>
    </div>

    <div class="panel-body" id="banner_panel_body">

        <!-- edit cms banner -->
        <div class="edit_cmsbanner_sec m-15">
            <form class="form-horizontal" action="<?=BASEURL . 'cmsbanner_management/addBannerCms'?>" method="post" role="form" id="form-editcmspromo" accept-charset="utf-8" enctype="multipart/form-data">
                <input type="hidden" id="editBannercmsId" name="bannercmsId" class="form-control input-sm" required>

                <div class="form-group">
                    <div class="col-md-12 text-center">
                        <img id="editBannerCmsImg" src="" style="align: left; valign= middle; height: 150px; width: auto; margin: 0 auto; display: none;"/>
                        <input type="hidden" name="editBannerCms" id="editBannerCms">
                        <div id="cmsImgDim" class="cms-sub-mesg" style="font-style: italic;"></div>
                    </div>
                </div>
                <div class="form-group">
                    <div class="col-md-4">
                        <label for="userfile" class="control-label"><?=lang('cms.uploadBanner');?>: </label>
                        <input type="file" name="userfile" id="userfile" class="form-control input-sm" onchange="setURLEditBannerCms(this,this.value);">
                    </div>
                    <!--
                    <div class="col-md-4">
                        <label for="editCategory" class="control-label"><?=lang('cms.category');?>: </label>
                        <select id="editCategory" name="category" class="form-control input-sm" required>
                            <option value="" disabled="disabled">-- <?= lang('cms.selecttype'); ?> --</option>
                            <?php foreach($this->cmsbanner_library->getCategories() as $key => $value): ?>
                                <option value="<?=$key?>"><?=$value?> (<?=lang('cms.banner.category.hint.' . $key)?>)</option>
                            <?php endforeach; ?>
                        </select>
                        <span class='uploadNote' style="color:#888;"><?=lang('cms.uploadNote');?></span>
                    </div>
                    -->
                    <input type="hidden" id="editCategory" name="category" value="1" />
                    <?php /*
                    <div class="col-md-4">
                        <label for="editlanguage" class="control-label"><?=lang('player.62');?>: </label>
                        <select id="editlanguage" name="language" class="form-control input-sm" required>
                            <option value="" disabled="disabled"><?=lang('system.word3');?></option>
                            <?php foreach($this->cmsbanner_library->getLanguages() as $key => $value): ?>
                                <option value="<?=$key?>"><?=$value?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    */ ?>
                   <input type="hidden" id="editlanguage" name="language" value="1" />
                </div>
                <div class="form-group">
                    <div class="col-md-4">
                        <label for="cmsbanner_title" class="control-label"><?=lang('cms.title');?>: </label>
                        <input type="text" name="title" id="cmsbanner_title" class="form-control input-sm" value="<?=set_value('title');?>">
                    </div>
                    <div class="col-md-8">
                        <label for="cmsbanner_summary" class="control-label"><?=lang('cms.description');?>: </label>
                        <input type="text" name="summary" id="cmsbanner_summary" class="form-control input-sm" value="<?=set_value('summary');?>">
                    </div>
                </div>
                <div class="form-group">
                    <div class="col-md-6">
                        <label for="cmsbanner_link" class="control-label"><?=lang('cms.link');?>: </label>
                        <input type="text" name="link" id="cmsbanner_link" class="form-control input-sm" value="<?=set_value('link');?>">
                    </div>
                    <div class="col-md-2">
                        <label for="cmsbanner_order" class="control-label"><?=lang('cms.order');?>: </label>
                        <input type="text" name="order" id="cmsbanner_order" class="form-control input-sm" value="<?=set_value('sort_order');?>">
                    </div>
                    <div class="col-md-4">
                        <label for="cmsbanner_link_target" class="control-label"><?=lang('Link Target');?>: </label>
                        <select id="cmsbanner_link_target" name="link_target" class="form-control input-sm" required>
                            <option value="" disabled="disabled">-- <?= lang('cms.selecttype'); ?> --</option>
                            <option value="_self"><?=lang('Current Window');?></option>
                            <option value="_blank"><?=lang('New Window');?></option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <div class="col-md-6">
                        <input id="game_goto_lobby" name="game_goto_lobby" type="checkbox" class="" value="1" />
                        <label for="game_goto_lobby" class="control-label"><?= lang('Go to Game Lobby') ?> (<?= lang('Player Center API Only') ?>)</label>
                    </div>
                </div>
                <div class="form-group">
                    <div class="col-md-2">
                        <label for="game_platform_id" class="control-label"><?= lang('Game Platform') ?></label>
                        <select id="game_platform_id" name="game_platform_id" class="form-control input-sm cms-game">
                            <option value="">---</option>
                            <?php foreach ($game_apis as $ga) : ?>
                                <option value="<?= $ga['id'] ?>">
                                    <?= $ga['system_code'] ?> (<?= $ga['id'] ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="game_gametype" class="control-label"><?= lang('gametype String') ?></label>
                        <input id="game_gametype" name="game_gametype" type="text"  class="form-control input-sm cms-game" />
                        <span class='uploadNote cms-sub-mesg'><?=lang('cms.notes_game_type_string');?></span>
                    </div>
                    <div class="col-md-4">
                        <div style="text-align:right;">
                            <br/>
                            <button type="button" class="btn btn-sm editbannercms-cancel-btn <?=$this->utils->getConfig('use_new_sbe_color') ? 'btn-linkwater' : 'btn-default'?>"><?=lang('lang.cancel');?></button>
                            <input type="submit" value="<?=lang('lang.save');?>" class="btn btn-sm custom-btn-size <?=$this->utils->getConfig('use_new_sbe_color') ? 'btn-scooter m-l-5' : 'btn-info'?>"/>
                        </div>
                    </div>
                </div>

            </form>
        </div>

        <div id="bannerList" class="table-responsive">
            <form action="<?=BASEURL . 'cmsbanner_management/deleteSelectedBannerCms'?>" method="post" role="form" onsubmit="CMSBannerManagementProcess.deleteSelected(this); return false;">
                <table class="table table-striped table-hover" id="my_table" style="width:100%;">
                    <button type="submit" class="btn btn-danger btn-sm btn-action" data-toggle="tooltip" data-placement="top" title="<?=lang('cms.deletesel');?>">
                        <i class="glyphicon glyphicon-trash" style="color:white;"></i>
                    </button>
                    <thead>
                    <tr>
                        <th></th>
                        <th><input type="checkbox" id="checkWhite" onclick="checkAll(this.id)"/></th>
                        <th><?=lang('cms.order');?></th>
                        <th><?=lang('cms.title') ?></th>
                        <!-- <th><?=lang('cms.category') ?></th -->
                        <th><?= lang('cms.image') ?></th>
                        <th><?= lang('Link Target') ?></th>
                        <!-- <th><?=lang('player.62') ?></th> -->
                        <!-- <th><?=lang('cms.createdon') ?></th> -->
                        <!-- <th><?=lang('cms.createdby') ?></th> -->
                        <th><?= lang('lang.status') ?></th>
                        <th><?= lang('Go to Game Lobby') ?></th>
                        <th><?= lang('Game Platform') ?></th>
                        <th><?= lang('gametype String') ?></th>
                        <th><?= lang('Last Update') ?></th>
                        <th><?= lang('cms.updatedby') ?></th>
                        <th><?= lang('lang.action') ?></th>
                    </tr>
                    </thead>

                    <tbody>
                    <?php if(!empty($banner)){ ?>
                        <?php foreach($banner as $value){ ?>
                            <tr <?php if ($value['status'] == 'inactive') : ?>class="cms-disabled"<?php endif; ?> >
                                <td></td>
                                <td>
                                    <input type="checkbox" class="checkWhite" id="<?=$value['bannerId']?>" name="bannercms[]" value="<?=$value['bannerId']?>" onclick="uncheckAll(this.id)"/>
                                </td>
                                <td><?=$value['sort_order']?></td>
                                <td title="<?= lang('cms.title') ?> : <?= $value['title'] ?>&#013;<?= lang('cms.description') ?> : <?= $value['summary'] ?>">
                                    <?=$value['title'] == '' ? '<i class="help-block">' . lang('lang.norecord') . '<i/>' : $value['title']?>

                                </td>
                                <!-- <td><?=empty($value['category_name']) ? '<i class="help-block">' . lang('lang.norecord') . '<i/>' : $value['category_name'] ?></td> -->
                                <td><?=empty($value['banner_img_url']) ? '<i class="help-block">' . lang('lang.norecord') . '<i/>' : '<img id="banner_name" src="' . $value['banner_img_url'] . '" >'?></td>
                                <td title="<?= lang('cms.link') ?> : <?= $value['link'] ?>&#013;<?= lang('Link Target') ?> : <?= $value['link_target'] ?>">
                                    <?= $value['link'] ?>
                                </td>
                                <!-- <td><?=$value['language_name'] == '' ? '<i class="help-block">' . lang('lang.norecord') . '<i/>' : $value['language_name']?></td> -->
                                <!-- <td><?=$value['createdOn']?></td> -->
                                <!-- <td><?=$value['createdBy']?></td> -->
                                <td>
                                    <?php if ($value['status'] == 'active') : ?>
                                        <span class="fa fa-check fa-2x cms-text-green"></span>
                                    <?php else : ?>
                                        <span class="fa fa-times fa-2x cms-text-red"></span>
                                    <?php endif;?>
                                    <br />
                                    <?= lang("lang.{$value['status']}") ?>
                                </td>
                                <td><?= lang(empty($value['game_goto_lobby']) ? 'No' : 'Yes') ?></td>
                                <td>
                                    <?= empty($value['game_platform_system_code']) ? '' : "{$value['game_platform_system_code']} ({$value['game_platform_id']})" ?>

                                </td>
                                <td><?= $value['game_gametype'] ?></td>
                                <td><?=$value['updatedOn'] == '' ? '<i class="help-block">' . lang('lang.norecord') . '<i/>' : $value['updatedOn']?></td>
                                <td><?=$value['updatedBy'] == '' ? '<i class="help-block">' . lang('lang.norecord') . '<i/>' : $value['updatedBy']?></td>
                                <td>
                                    <div class="actionCmsBannerGroup">
                                        <?php if($value['status'] == 'active'){ ?>
                                            <a href="<?=BASEURL . 'cmsbanner_management/activateBannerCms/' . $value['bannerId'] . '/' . 'inactive'?>">
                                                <span data-toggle="tooltip" title="<?=lang('tool.cms01');?>" class="glyphicon glyphicon-remove-circle" data-placement="top"></span>
                                            </a>
                                        <?php }else{ ?>
                                            <a href="<?=BASEURL . 'cmsbanner_management/activateBannerCms/' . $value['bannerId'] . '/' . 'active'?>">

                                                <span data-toggle="tooltip" title="<?=lang('tool.cms02');?>" class="glyphicon glyphicon-ok-sign" data-placement="top"></span>
                                            </a>
                                        <?php } ?>

                                        <a href="javascript: CMSBannerManagementProcess.getBannerCmsDetail(<?=$value['bannerId']?>);">
                                            <span class="glyphicon glyphicon-edit editBannerCmsBtn" data-toggle="tooltip" title="<?=lang('lang.edit');?>" data-placement="top"></span>
                                        </a>

                                        <a href="javascript: CMSBannerManagementProcess.delBannerCmsDetail(<?=$value['bannerId']?>);">
                                            <span data-toggle="tooltip" title="<?=lang('lang.delete');?>" class="glyphicon glyphicon-trash" data-placement="top"></span>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php } ?>
                    <?php }else{ ?>
                    <?php } ?>
                    </tbody>
                </table>
            </form>
        </div>
    </div>
</div>

<div class="modal del_cms_banner_modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?=lang('Are you sure you want to delete')?></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger"><?=lang('confirm')?></button>
                <button type="button" class="btn btn-close" data-dismiss="modal"><?=lang('close')?></button>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function(){
        $('#my_table').DataTable({
            "responsive": {
                details: {
                    type: 'column'
                }
            },
            "columnDefs": [{
                className: 'control',
                orderable: false,
                targets: 0
            },{
                orderable: false,
                // targets: [1, 2, 4]
                targets: [1, 4]
            }],
            "order": [2, 'asc'],
            "dom": '<"top"fl>rt<"bottom"ip>',
            "fnDrawCallback": function(oSettings){
                $('.btn-action').prependTo($('.top'));
            }
        });
    });

    // Identifier for old/new view, do not change
    var cmsbanner_view_mode = 2;
    var lang = {
        "preview": '<?= lang('Preview') ?>'
    };
</script>

