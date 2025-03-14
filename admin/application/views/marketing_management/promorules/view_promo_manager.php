<?php include APPPATH . "/views/includes/popup_promorules_info.php";?>

<div class="panel panel-primary">
    <div class="panel-heading">
        <h4 class="panel-title">
            <i class="fa fa-search"></i> <?= lang('lang.search')?><span class="pull-right">
                <a data-toggle="collapse" href="#collapseViewUsers" class="btn btn-info btn-xs" aria-expanded="false"></a>
            </span>
        </h4>
    </div>
    <div id="collapseViewUsers" class="panel-collapse collapse in" aria-expanded="false">
        <div class="panel-body">
            <form class="form-horizontal" method="get" id="search_form" autocomplete="off" role="form">
                <div class="form-group">
                    <div class="col-md-3">
                        <label class="control-label"><?= lang('cms.promoCat'); ?></label>
                        <select name="category" id="category" class="form-control input-sm">
                            <option value="all" <?php echo (!isset($search['promorules.promoCategory']) || $search['promorules.promoCategory'] == 'all') ?  'selected="selected"' : '' ?>><?= lang('lang.all') ?></option>
                            <?php foreach ($promoCategoryList as $promoCategory) : ?>
                            <option value="<?= $promoCategory['id'] ?>" <?php echo (isset($search['promorules.promoCategory']) && $search['promorules.promoCategory'] == $promoCategory['id']) ?  'selected="selected"' : '' ?>><?= lang($promoCategory['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="control-label"><?= lang('lang.status'); ?></label>
                        <select name="status" id="status" class="form-control input-sm">
                            <option value="all" <?php echo (!isset($search['promorules.status']) || $search['promorules.status'] == 'all') ?  'selected="selected"' : '' ?>><?= lang('lang.all') ?></option>
                            <option value="0" <?php echo (isset($search['promorules.status']) && $search['promorules.status'] == 0) ?  'selected="selected"' : '' ?>><?=lang("pay.active") ?></option>
                            <option value="1" <?php echo (isset($search['promorules.status']) && $search['promorules.status'] == 1) ?  'selected="selected"' : '' ?>><?=lang("pay.inactive") ?></option>
                        </select>
                    </div>
                </div>
            </form>
        </div>

        <div class="panel-footer">
            <div class="text-center">
                <button class="btn btn-sm btn-linkwater" type="reset" form="search_form">Reset</button>
                <button class="btn btn-sm btn-portage" type="submit" form="search_form"><i class="fa fa-search"></i> Search</button>
            </div>
        </div>
    </div>
</div>

<div class="panel panel-primary">
    <div class="panel-heading custom-ph">
        <h4 class="panel-title custom-pt">
            <i class="icon-bullhorn"></i> <?=lang('cms.promoRuleSettings');?>
            <div class="clearfix"></div>
        </h4>
    </div>
    <div class="panel-body" id="details_panel_body">
        <div class="row">
            <div class="col-md-12">
            <a href="<?=site_url('marketing_management/add_new_promo_by_template')?>" class="btn btn-sm btn-scooter" data-toggle="tooltip" data-placement="left">
                <i class="fa fa-plus"></i> <?=lang('Add New Promo By Template')?>
            </a>
            <a href="<?=site_url('marketing_management/addNewPromo')?>" class="btn btn-sm btn-portage" data-toggle="tooltip" title="<?=lang('cms.addNewPromo');?>" data-placement="left">
                <span id="addDepositPromoGlyhicon" class="glyphicon glyphicon-plus-sign"></span> <?=lang('cms.addNewPromo')?>
            </a>

                <hr class="hr_between_table"/>
                <div id="promorule_table" class="table-responsive" style="overflow: hidden;">
                    <table class="table table-bordered table-hover dataTable" id="myTable" style="width:100%;">

                        <thead>
                            <tr>
                                <th></th>
                                <th class="tableHeaderFont"><?=lang('cms.promoname');?></th>
                                <th class="tableHeaderFont"><?=lang('cms.promoCat');?></th>
                                <th class="tableHeaderFont"><?=lang('cms.promoType');?></th>
                                <th class="tableHeaderFont"><?=lang('cms.createdon');?></th>
                                <th class="tableHeaderFont"><?=lang('cms.createdby');?></th>
                                <th class="tableHeaderFont"><?=lang('cms.updatedon');?></th>
                                <th class="tableHeaderFont"><?=lang('cms.updatedby');?></th>
                                <th class="tableHeaderFont"><?=lang('promorules.bonus_release');?></th>
                                <th class="tableHeaderFont"><?=lang('Expire Date');?></th>
                                <th class="tableHeaderFont"><?=lang('lang.status');?></th>
                                <?php if(!$this->utils->getConfig('disabled_promo_bonus_release_count')) : ?>
                                    <th class="tableHeaderFont"><?=lang('promorules.bonus_release_count')?></th>
                                <?php endif; ?>
                                <th class="tableHeaderFont"><?=lang('promorules.Auto tick new games in game type')?></th>
                                <th class="tableHeaderFont"><?=lang('lang.action');?></th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php
//var_dump($promorules);exit();
if (!empty($promorules)) {
	$cnt = 1;
    $disabled_promo_bonus_release_count = $this->utils->getConfig('disabled_promo_bonus_release_count');
    $use_cronjob_for_promo_bonus_release_count = $this->utils->getConfig('use_cronjob_for_promo_bonus_release_count');
	foreach ($promorules as $row) {
		?>
                                            <tr class="<?php echo $row['hide_date']<$this->utils->getNowForMysql() || $row['status'] != 0 ? "danger" : "" ; ?>" >
                                                <td></td>
                                                <td class='tableContent'><?=$row['promoName'] == '' ? '<i class="help-block">' . lang("lang.norecyet") . '<i/>' : '<a href="'.site_url('marketing_management/editPromoRule/'.$row['promorulesId']).'">'.$row['promoName'].'</a>';?></td>
                                                <td class='tableContent'><?=$row['promoTypeName'] == '' ? '<i class="help-block">' . lang("lang.norecyet") . '<i/>' : lang($row['promoTypeName'])?></td>
                                                <td class='tableContent'><?=$row['promoType'] == '' ? '<i class="help-block">' . lang("lang.norecyet") . '<i/>' : $row['promoType'] == '0' ? 'Deposit' : 'Non-Deposit'?></td>
                                                <td class='tableContent'><?=$row['createdOn'] == '' ? '<i class="help-block">' . lang("lang.norecyet") . '<i/>' : $row['createdOn']?></td>
                                                <td class='tableContent'><?=$row['createdBy'] == '' ? '<i class="help-block">' . lang("lang.norecyet") . '<i/>' : $row['createdBy']?></td>
                                                <td class='tableContent'><?=$row['updatedOn'] == null ? '<i class="help-block">' . lang("lang.norecyet") . '<i/>' : $row['updatedOn']?></td>
                                                <td class='tableContent'><?=$row['updatedBy'] == '' ? '<i class="help-block">' . lang("lang.norecyet") . '<i/>' : $row['updatedBy']?></td>
                                                <td class='tableContent <?php echo $row['bonusReleaseToPlayer'] == 0 ? "success" : "" ;?>'><?=$row['bonusReleaseToPlayer'] == 0 ? lang("Automatic") : lang("Manual");?></td>
                                                <td class='tableContent'><?=$row['hide_date']?></td>
                                                <td class='tableContent'><?=$row['status'] == 0 ? lang("pay.active") : lang("pay.inactive");?></td>
                                                <?php if(!$disabled_promo_bonus_release_count) : ?>
                                                    <?php if($use_cronjob_for_promo_bonus_release_count):?>
                                                        <td class='tableContent'><?= !empty($row['bonusReleaseCount']) ? $row['bonusReleaseCount'] : 0 ?></td>
                                                    <?php else:?>
                                                        <td class='tableContent'><?= !empty($row['bonusCount']) ? $row['bonusCount'] : 0 ?></td>
                                                    <?php endif;?>
                                                <?php endif; ?>
                                                <td class='tableContent'><?= $row['auto_tick_new_game_in_cashback_tree'] ? lang('lang.yes') : lang('lang.no') ?></td>
                                                <td class='tableContent'>
                                                    <div class="actionDepositPromoGroup" align="center">
                                                        <?php if ($row['status'] == 0 ) :?>
                                                            <span data-toggle="tooltip" title="<?=lang('lang.deactivate');?>" class="glyphicon glyphicon-ok-sign deactive_promorule" data-rule-id="<?=$row['promorulesId']?>" data-placement="top"></span>
                                                        <?php elseif($row['status'] == 1) :?>
                                                            <a href="<?=site_url('marketing_management/activatePromoRule/' . $row['promorulesId'] . '/0')?>">
                                                                <span data-toggle="tooltip" title="<?=lang('lang.activate');?>" class="glyphicon glyphicon-remove-circle active_promorule" data-rule-id="<?=$row['promorulesId']?>" data-placement="top"></span>
                                                            </a>
                                                        <?php endif;?>

                                                        <?php if ($this->permissions->checkPermissions('allow_to_enable_edit_promo_rules')) : ?>
                                                            <?php if ($row['enable_edit'] == 1  ) :?>
                                                                <a href="<?=site_url('marketing_management/enableEditPromoRule/' . $row['promorulesId'] . '/0')?>">
                                                                    <span data-toggle="tooltip" title="<?=lang('promorules.lock');?>" class="fa fa-unlock" data-placement="top">
                                                                    </span>
                                                                </a>
                                                                <a href="javascript:void(0)" class="deletePromoruleItem" data-id="<?=$row['promorulesId']?>">
                                                                    <span data-toggle="tooltip" title="<?=lang('lang.delete');?>" class="glyphicon glyphicon-trash" data-placement="top">
                                                                    </span>
                                                                </a>
                                                            <?php elseif($row['enable_edit'] == 0) :?>
                                                                 <a href="<?=site_url('marketing_management/enableEditPromoRule/' . $row['promorulesId'] . '/1')?>">
                                                                    <span data-toggle="tooltip" title="<?=lang('system.word56');?>" class="fa fa-lock" data-placement="top">
                                                                    </span>
                                                                </a>
                                                            <?php endif;?>
                                                        <?php endif;?>
                                                        <!-- <a href="<?=site_url('marketing_management/editPromoRule/' . $row['promorulesId'])?>">
                                                            <span data-toggle="tooltip" title="<?=lang('lang.edit');?>" class="glyphicon glyphicon-edit" data-placement="top">
                                                            </span>
                                                        </a> -->
                                                    </div>
                                                </td>
                                            </tr>
                            <?php
$cnt++;
	}
}
?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div><div class="panel-footer"></div>
</div>

<!-- Delete Promorule Modal Start -->
<div class="modal fade" id="deletePromoruleModal" tabindex="-1" role="dialog" aria-labelledby="deletePromoruleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="deletePromoruleModalLabel"><?=lang('cms.deletePromoRule')?></h4>
            </div>
            <div class="modal-body deletePromoruleModalBody">
                <?=lang('cms.deletePromoruleMsg')?>
            </div>
            <div class="modal-footer">
                <input type="hidden" class="deleteSelectedPromoruleId">
                <button type="button" class="btn btn-primary delete-func" data-dismiss="modal"><?=lang('Confirm')?></button>
            </div>
        </div>
    </div>
</div>
<!-- Delete Promorule Modal End -->

<!-- Deactive Promorule Modal Start -->
<div class="modal fade" id="deactivePromoruleModal" tabindex="-1" role="dialog" aria-labelledby="deactivePromoruleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="deactivePromoruleModalLabel"><?=lang('cms.deactivatePromoRule')?></h4>
            </div>
            <div class="modal-body deactivePromoruleModalBody">
                <?=lang('cms.deactivePromoruleMsg')?>
            </div>
            <div class="modal-footer">
                <input type="hidden" class="deactiveSelectedPromoruleId">
                <button type="button" class="btn btn-primary deactive-func"><?=lang('Yes')?></button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal"><?=lang('No')?></button>
            </div>
        </div>
    </div>
</div>
<!-- Deactive Promorule Modal End -->

<script type="text/javascript">
    $(document).ready(function() {
        $('#myTable').DataTable( {
            dom: "<'panel-body' <'pull-right'f><'pull-right progress-container'>l><'dt-information-summary1 text-info pull-left' i>t<'panel-body'<'pull-right'p><'dt-information-summary2 text-info pull-left' i>>",
            "responsive": {
                details: {
                    type: 'column'
                }
            },
            "columnDefs": [ {
                className: 'control',
                targets:   0
            },{
                orderable: false,
                targets:   1
            } ],
            "order": [ 4, 'desc' ]
        } );

        //check delete successful or not, then prompt different msg
        $('.deletePromoruleItem').on('click', function(){
            var del_id = $(this).attr('data-id');
            var url = "<?=site_url('marketing_management/deletePromorule')?>" + "/" + del_id;
            $.post(url).done(function(data){
                if(data && data.success){
                    $('.deletePromoruleModalBody').html('<?=lang('cms.deletePromoruleSuccMsg');?>');
                    $('#deletePromoruleModal button').hide();
                    $('#deletePromoruleModal').modal('show');
                    window.location.reload();
                }else{
                    $('.deletePromoruleModalBody').html('<?=lang('cms.deletePromoruleFailMsg');?>');
                    $('#deletePromoruleModal').modal('show');
                }
            }).fail(function(){
                alert('Delete failed');
            });

        });
        $('button[type="reset"][form="search_form"]').click(function(event) {
            event.preventDefault();
            $('#category').val('all');
            $('#status').val('all');
        });

        $('.deactive_promorule').click(function(){
            var status_inactive = '1';
            var rule_id = $(this).data('rule-id');
            var url = "<?=site_url('marketing_management/existActivePromoCms')?>" + "/" + rule_id;
            $.post(url).done(function(data){
                if(data.success){
                    $('.deactiveSelectedPromoruleId').val('');
                    $('.deactiveSelectedPromoruleId').val(rule_id);
                    $('#deactivePromoruleModal').modal('show');
                }else{
                    window.location.href = "<?=site_url('marketing_management/activatePromoRule')?>" + "/" + rule_id + "/" + status_inactive;
                }
            }).fail(function(){
                alert('Deactive failed');
            });
        });

        $('#deactivePromoruleModal .deactive-func').click(function(){
            var status_inactive = '1';
            var rule_id = $('.deactiveSelectedPromoruleId').val();
            var url = "<?=site_url('marketing_management/activatePromoRule')?>" + "/" + rule_id + "/" + status_inactive;
            $('#deactivePromoruleModal .deactive-func').prop('disabled', true);
            window.location.href = url;
        });

    } );
</script>