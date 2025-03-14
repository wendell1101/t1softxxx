<div class="panel panel-primary">
    <div class="panel-heading">
        <h4 class="panel-title">
            <i class="icon-drawer"></i> <?=lang('Adjustment Category');?>
            <?php if($this->permissions->checkPermissions('modified_adjustment_category')) : ?>
                <a href="javascript:void(0);" class="btn btn-primary btn-sm pull-right panel-button" onclick="return CommonCategory.addNewEntry()" style="margin-right: 0; margin-top: -5px;" >
                    <i class="glyphicon glyphicon-plus" data-placement="top"  data-toggle='tooltip'></i>&nbsp;<?=lang('Add Adjustment Type');?>
                </a>
            <?php endif; ?>
        </h4>
    </div>
    <div class="panel-body" style="overflow-x: scroll;">
        <form id="search-form">
            <div class="form-group col-md-2">
                <input type="hidden" name="category_type" value="adjustment">
            </div>
        </form>
        <table class="table table-hover table-condensed table-bordered" id="myTable">
            <thead>
                <tr>
                    <?php if($this->permissions->checkPermissions('modified_adjustment_category')) : ?>
                        <th><?=lang('sys.action');?></th>
                    <?php endif; ?>
                    <th><?=lang('sys.pay.systemid');?></th>
                    <th><?=lang('Adjustment Category');?></th>
                    <th><?=lang('report.p18');?></th>
                    <th><?=lang('sys.createdon');?></th>
                    <th><?=lang('sys.updatedon'); ?></th>
                    <th><?=lang('sys.createdby');?></th>
                    <th><?=lang('sys.updatedby');?></th>
                    <th><?=lang('lang.status');?></th>
                </tr>
            </thead>
        </table>
    </div>
</div>
<div class="modal fade in" id="mainModal" tabindex="-1" role="dialog" aria-labelledby="mainModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form action="<?=site_url('system_management/add_update_category')?>" method="post" role="form" id="form_category">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title" id="mainModalLabel"></h4>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12">
                            <input type="hidden" class="clear-fields" name="category_id" id="category_id" value="">
                            <input type="hidden" name="category_type" id="category_type" value="adjustment">
                            <div class="form-group">
                                <label for="category_name"><?=lang("lang.english.name")?> </label>
                                <input type="text" class="form-control clear-fields" id="category_name_english" name="category_name[1]">
                                <span class="help-block" style="color:#F04124"></span>
                            </div>
                            <div class="form-group">
                                <label for="category_name"><?=lang("lang.chinese.name")?> </label>
                                <input type="text" class="form-control clear-fields" id="category_name_chinese" name="category_name[2]">
                                <span class="help-block" style="color:#F04124"></span>
                            </div>
                            <div class="form-group">
                                <label for="category_name"><?=lang("lang.indonesian.name")?> </label>
                                <input type="text" class="form-control clear-fields" id="category_name_indonesian" name="category_name[3]">
                                <span class="help-block" style="color:#F04124"></span>
                            </div>
                            <div class="form-group">
                                <label for="category_name"><?=lang("lang.vietnamese.name")?> </label>
                                <input type="text" class="form-control clear-fields" id="category_name_vietnamese" name="category_name[4]">
                                <span class="help-block" style="color:#F04124"></span>
                            </div>
                            <div class="form-group">
                                <label for="category_name"><?=lang("lang.korean.name")?> </label>
                                <input type="text" class="form-control clear-fields" id="category_name_korean" name="category_name[5]">
                                <span class="help-block" style="color:#F04124"></span>
                            </div>
                            <div class="form-group">
                                <label for="category_name"><?=lang("lang.portuguese.name")?> </label>
                                <input type="text" class="form-control clear-fields" id="category_name_portuguese" name="category_name[8]">
                                <span class="help-block" style="color:#F04124"></span>
                            </div>
                            <div class="form-group">
                                <label for="category_name"><?=lang("lang.spanish.name")?> </label>
                                <input type="text" class="form-control clear-fields" id="category_name_spanish" name="category_name[9]">
                                <span class="help-block" style="color:#F04124"></span>
                            </div>
                            <div class="form-group">
                                <label for="category_name"><?=lang("lang.kazakh.name")?> </label>
                                <input type="text" class="form-control clear-fields" id="category_name_kazakh" name="category_name[10]">
                                <span class="help-block" style="color:#F04124"></span>
                            </div>
                            <div class="form-group">
                                <label for="order_by"><?=lang("report.p18")?> </label>
                                <input type="number" class="form-control clear-fields" onkeyup="value=value.match(/^[0-9]\d*$/)" id="order_by" name="order_by">
                                <span class="help-block" style="color:#F04124"></span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer" >
                    <div style="height:70px;position:relative;">
                        <button type="button" class="btn btn-default" data-dismiss="modal"><?=lang('lang.close');?></button>
                        <button type="button" class="btn btn-primary"  onclick="return CommonCategory.submitEntry();"><i class="glyphicon glyphicon-floppy-disk"></i> <?=lang('Save')?></button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function () {
        $('#view_adjustment_category').addClass('active');

        CommonCategory.msgSubmitConfirmation = "<?= lang('sys.ga.conf.add.msg')?>";
        CommonCategory.msgDeleteConfirmation = "<?= lang('confirm.delete') ?>";
        CommonCategory.addModalTitle = "<?= lang('Add Adjustment Type') ?>";
        CommonCategory.editModalTitle = "<?= lang('Edit Adjustment Type') ?>";
        CommonCategory.msgActiveConfirmation = "<?= lang('Are you sure you want to active this category?') ?>";
        CommonCategory.msgInactiveConfirmation = "<?= lang('Are you sure you want to inactive this category?') ?>";
    });
</script>