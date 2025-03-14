<style type="text/css">
	.panel-body .row{
		margin: 4px;
	}
</style>

<form action="<?=site_url('system_management/post_clear_game_logs_md5_sum/false'); ?>" method="POST">
    <div class="panel panel-primary panel_main">
        <div class="panel-heading">
            <h4 class="panel-title">
                <?=lang('Clear Game Logs Md5 Sum')?>
            </h4>
        </div>
        <div id="manual_clear_game_logs_md5_sum" class="panel-collapse collapse in ">
            <div class="panel-body">
                <div class="row">
                    <div class="col-md-2">
                        <?=lang('Game');?>
                    </div>
                    <div class="col-md-6">
                        <select class="form-control input-sm" name="game_platform_id" id="clear_game_logs_md5_sum_game_platform_id" required>
                            <option value=""><?=lang('Select Game API');?></option>
                            <?php foreach ($this->utils->getGameSystemMap() as $key => $value) { ?>
                                <option value="<?=$key;?>"><?=$value;?></option>
                            <?php } ?>
                        </select>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-2">
                        <?=lang('External Unique Ids');?>
                    </div>
                    <div class="col-md-6">
                        <textarea class="form-control input-sm" rows="10" name="external_unique_ids" placeholder='["sample1", "sample2", "sample3"]' required ></textarea>
                    </div>
                </div>
            </div>
            <div class="panel-footer">
                <input type="submit" class="btn btn-portage" value="<?=lang('Submit'); ?>">
            </div>
        </div>
    </div>
</form>