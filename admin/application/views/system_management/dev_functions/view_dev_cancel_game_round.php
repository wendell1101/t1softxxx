<style type="text/css">
	.panel-body .row{
		margin: 4px;
	}
</style>

<form action="<?=site_url('system_management/post_cancel_game_round'); ?>" method="POST">
    <div class="panel panel-primary panel_main">
        <div class="panel-heading">
            <h4 class="panel-title">
                <?=lang('Cancel Game Round')?>
            </h4>
        </div>
        <div id="manual_cancel_game_round" class="panel-collapse collapse in ">
            <div class="panel-body">
                <div class="row">
                    <div class="col-md-2">
                        <?=lang('Game API');?>
                    </div>
                    <div class="col-md-6">
                        <select class="form-control input-sm" id="cgr_game_platform_id" name="game_platform_id" required>
                            <option value="">Select Game API</option>
                            <?php foreach ($cancel_round_game_apis as $game_api) { ?>
                                <option value="<?=$game_api['game_platform_id'];?>"><?php echo "[{$game_api['game_platform_id']}] {$game_api['game_platform_name']}";?></option>
                            <?php } ?>
                        </select>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-2">
                        <?=lang('Game Username');?>
                    </div>
                    <div class="col-md-6">
                        <input type="text" class="form-control input-sm" id="cgr_game_username" name="game_username" placeholder="<?=lang('Game Username');?>" required />
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-2">
                        <?=lang('Round ID');?>
                    </div>
                    <div class="col-md-6">
                        <input type="text" class="form-control input-sm" id="cgr_round_id" name="round_id" placeholder="<?=lang('Round ID');?>" required />
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-2">
                        <?=lang('Game Code');?>
                    </div>
                    <div class="col-md-6">
                        <input type="text" class="form-control input-sm" id="cgr_game_code" name="game_code" placeholder="<?=lang('Game Code');?>" required />
                    </div>
                </div>
            </div>
            <div class="panel-footer">
                <input type="submit" class="btn btn-portage" value="<?=lang('Submit'); ?>">
            </div>
        </div>
    </div>
</form>