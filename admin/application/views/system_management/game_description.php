<style>
[class^='select2'] {
    border-radius: 0px !important;
}

.select2-selection {
 padding-left: 8px;
 padding-right: 8px;
}


.select2-container--default .select2-selection--multiple:before {
    content: ' ';
    display: block;
    position: absolute;
    border-color: #888 transparent transparent transparent;
    border-style: solid;
    border-width: 5px 4px 0 4px;
    height: 0;
    right: 6px;
    margin-left: -4px;
    margin-top: -2px;top: 50%;
    width: 0;cursor: pointer
}

.select2-container--open .select2-selection--multiple:before {
    content: ' ';
    display: block;
    position: absolute;
    border-color: transparent transparent #888 transparent;
    border-width: 0 4px 5px 4px;
    height: 0;
    right: 6px;
    margin-left: -4px;
    margin-top: -2px;top: 50%;
    width: 0;cursor: pointer
}
</style>
<div class="panel panel-primary hidden">

    <div class="panel-heading">
        <h4 class="panel-title">
            <i class="fa fa-search"></i> <?=lang("lang.search")?>
            <span class="pull-right">
                <a data-toggle="collapse" href="#collapseGameDescription" class="btn btn-xs btn-info <?= $this->config->item('default_open_search_panel') ? '' : 'collapsed'?>"></a>
            </span>
        </h4>
    </div>

    <div id="collapseGameDescription" class="panel-collapse collapse <?= $this->config->item('default_open_search_panel') ? 'in' : ''?>">
	    <input type="hidden" id="gameTypeIdHide" name="gameTypeIdHide" value="<?=$conditions['gameType']?>">
        <form id="form-filter" class="form-horizontal" method="get">
	        <div class="panel-body">
	            <div class="row">
                    <div class="col-md-2">
                        <label class="control-label" for="gamePlatformId"><?=lang('sys.gd7')?>:</label>
                        <select name="gamePlatform" id="gamePlatform" class="form-control clearField">
                            <option value="N/A"><?=lang('Select Game Platform')?></option>
                            <?php foreach ($gameapis as $gameApi) { ?>
                                <option value="<?=($gameApi['id'])?>" <?=($gameApi['id']==$conditions['gamePlatform'])?'selected':''?>><?=$gameApi['system_code']  . " [" . $gameApi['id'] . "]"?></option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="control-label" for="gameType"><?=lang('sys.gd6')?>:</label>
                        <select name="gameType" id="gameType" class="form-control clearField" disabled>
                            <option value=""><?= lang('Select Game Type'); ?></option>
                        </select>
                    </div>
	                <div class="col-md-2">
	                    <label class="control-label" for="gameName"><?=lang('sys.gd8')?>:</label>
	                    <input type="text" name="gameName" id="gameName" value="<?=($conditions['gameName']) ? $conditions['gameName']:''?>" class="form-control number_only clearField"/>
	                </div>
	                <div class="col-md-6">
	                    <label class="control-label" for="gameCode[]"><?=lang('sys.gd9')?>:</label>
	                    <?php if ($this->utils->getConfig('show_non_active_game_api_game_list')): ?>
	                    	<input type="text" name="gameCode[]" value="<?=(empty($conditions['gameCode']))?null:$conditions['gameCode']?>" class="form-control clearField">
						<?php else: ?>
                       	<select id="selectGameCode" class="multi-select-filter form-control clearField" name="gameCode[]" multiple="multiple" style="width:100%;">
                       		<?php foreach ($gameCodes as $key => $gameCode): ?>
                       			<option value="<?=$gameCode?>"><?=lang($gameCode)?></option>
                       		<?php endforeach ?>
						</select>
	                    <?php endif ?>
	                </div>
	            </div>

	            <div class="row">
	                <div class="col-md-4">
	                    <label class="control-label" for="filters[]"><?=lang('Game Attributes');?>:</label>
                       	<select id="filters" class="multi-select-filter form-control clearField" name="filters[]" multiple="multiple" style="width:100%;">
                       		<?php foreach ($filters as $key => $filter): ?>
                       			<option value="<?=$filter?>"><?=lang($key)?></option>
                       		<?php endforeach ?>
						</select>
	                </div>
                    <div class="col-md-2">
                        <label class="control-label" for="gameStatus"><?=lang('sys.gd35')?>:</label>
                        <select name="gameStatus" id="gameStatus" class="form-control clearField">
                        	<?php
                        		if($conditions['gameStatus'] === 'All' || $conditions['gameStatus'] === false || $conditions['gameStatus'] === '') {
                			?>
		                            <option value = 'All' selected=""><?=lang('Select All')?></option>
			                        <option value = '1'><?=lang('Enabled')?></option>
		                            <option value = '0'><?=lang('Disabled')?></option>
                            <?php
                        		} else if($conditions['gameStatus'] === '1') {
                			?>
		                            <option value = 'All'><?=lang('Select All')?></option>
			                        <option value = '1' selected=""><?=lang('Enabled')?></option>
		                            <option value = '0'><?=lang('Disabled')?></option>
                        	?>
                            <?php
                        		} else {
                			?>
		                            <option value = 'All'><?=lang('Select All')?></option>
			                        <option value = '1'><?=lang('Enabled')?></option>
		                            <option value = '0' selected=""><?=lang('Disabled')?></option>
                            <?php
		                        }
	                        ?>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="control-label" for="gameFlagShow"><?=lang('sys.gd36')?>:</label>
                        <select name="gameFlagShow" id="gameFlagShow" class="form-control clearField">
                        	<?php
                        		if($conditions['gameFlagShow'] === 'All' || $conditions['gameFlagShow'] === false || $conditions['gameFlagShow'] === '') {
                			?>
		                            <option value = 'All' selected=""><?=lang('Select All')?></option>
			                        <option value = '1'><?=lang('Enabled')?></option>
		                            <option value = '0'><?=lang('Disabled')?></option>
                            <?php
                        		} else if($conditions['gameFlagShow'] === '1') {
                			?>
		                            <option value = 'All'><?=lang('Select All')?></option>
			                        <option value = '1' selected=""><?=lang('Enabled')?></option>
		                            <option value = '0'><?=lang('Disabled')?></option>
                        	?>
                            <?php
                        		} else {
                			?>
		                            <option value = 'All'><?=lang('Select All')?></option>
			                        <option value = '1'><?=lang('Enabled')?></option>
		                            <option value = '0' selected=""><?=lang('Disabled')?></option>
                            <?php
		                        }
	                        ?>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="control-label" for="gameId"><?=lang('lang.external.game.id')?>:</label>
                        <input type="text" name="gameId" id="gameId" value="<?=($conditions['gameId']) ? $conditions['gameId']:''?>" class="form-control number_only clearField"/>
                    </div>

                    <!-- <div class="col-md-2">
                        <label class="control-label" for="gameTag"><?=lang('Game Tag')?>:</label>
                        <input type="text" name="gameTag" id="gameTag" value="<?=($conditions['gameTag']) ? $conditions['gameTag']:''?>" class="form-control clearField"/>
                    </div> -->
                    <!-- <div class="col-md-2">
                        <label class="control-label" for="agentName"><?=lang('Agent Name')?>:</label>
                        <input type="text" name="agentName" id="agentName" value="<?=($conditions['agentName']) ? $conditions['agentName']:''?>" class="form-control number_only clearField"/>
                    </div> -->
                    
                    <div class="col-md-2">
                        <label class="control-label" for="gameTag"><?=lang('Game Tag')?>:</label>
                        <select class="select form-control clearField" id="gameTag" name="gameTag[]" multiple="multiple">
                            <?php foreach($game_tags as $game_tag) { ?>
                                <option title="<?= lang($game_tag['tag_name']) ?>" value="<?= $game_tag['tag_code'] ?>" key="<?= $game_tag['id'] ?>"><?= $game_tag['tag_code'] ?></option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="control-label" for="gd_agents"><?=lang('Agent Name');?>:</label>
                        <select id="gd_agents" class="select form-control clearField" name="agentName">
                            <?php foreach ($agents as $key => $agent): ?>
                                <option value=""></option>
                                <option value="<?=$agent['agent_name']?>"><?=$agent['agent_name']?></option>
                            <?php endforeach ?>
                        </select>
                    </div>

	            </div>
                <div class="row">
                    <div class="col-md-12 text-right" style="margin-top: 20px">
                        <input type="reset" class="btn btn-sm btn-linkwater" id="resetFields" value="<?=lang('lang.clear');?>">
                        <input type="submit" value="<?=lang('lang.search')?>" id="search_main" class="btn btn-sm btn-portage">
                    </div>
                </div>
	        </div>
        </form>
    </div>
</div>
<div class="row" id="user-container">
	<div class="col-md-12" id="toggleView">
		<div class="panel panel-primary">
            <div class="panel-heading custom-ph">
                <h3 class="panel-title custom-pt" >
                    <i class="icon-list"></i>
                    <?=lang('sys.gd0');?>
					<?php if ($this->permissions->checkPermissions('game_description_history')): ?>
	                    <a href="<?=base_url('game_description/viewGameDescriptionHistory')?>" class="goto btn btn-primary pull-right btn-xs" style="margin-left:4px;margin-top:0px">
	                        <i class="glyphicon glyphicon-list-alt" data-placement="bottom"></i>
	                        <?=lang('Game List History');?>
	                    </a>
                    <?php endif ?>

                    <button type="button" value="" id="addGameDesc" name="btnSubmit" class="btn btn-primary pull-right btn-xs" style="margin-left:4px;margin-top:0px">
                        <i class="glyphicon glyphicon-plus" data-placement="bottom"></i>
                        <?=lang('sys.gd22');?>
                    </button>
                    <?php //if ($this->utils->getConfig('allow_batch_game_list_update')): ?>
                    <button type="button" id="batchAddUpdateGameDesc" data-toggle="modal" data-target="#batchAddUpdateModal" class="btn btn-primary pull-right btn-xs" style="margin-top:0px">
                        <i class="glyphicon glyphicon-plus" data-placement="bottom"></i>
                        <?=lang('Batch Add/Update Game Description');?>
                    </button>
                	<?php //endif; ?>

                    <button type="button" id="batchUpdateGameDescFields" data-toggle="modal" data-target="#batchUpdateGameDesFieldsModal" class="btn btn-primary pull-right btn-xs" style="margin-top:0px;margin-left:4px;">
                        <i class="glyphicon glyphicon-pencil" data-placement="bottom"></i>
                        <?=lang('Batch Update Game Description');?>
                    </button>
                    <button type="button" id="batchTagGames" data-toggle="modal" data-target="#batchTagGamesModal" class="btn btn-primary pull-right btn-xs" style="margin-top:0px;">
                        <i class="glyphicon glyphicon-tag" data-placement="bottom"></i>
                        <?=lang('Batch Tag Games');?>
                    </button>
                </h3>
                </h3>
            </div>

			<div class="panel-body" id="list_panel_body">
				<div class="table-responsive">
				<div class="clearfix"></div>
					<table class="table table-bordered table-hover dataTable" id="my_table" >
						<thead>
							<tr>
                                <?php
                                    $game_list_column_order = isset($this->utils->getConfig('game_list_column_order')['custom_order']) ? $this->utils->getConfig('game_list_column_order')['custom_order'] : $this->utils->getConfig('game_list_column_order')['default_order'];

                                    foreach($game_list_column_order as $alias_order){
                                        switch($alias_order) {
                                            case 'action':
                                                echo '<th>'.lang('sys.gd33').'</th>';
                                                break;
                                            case 'english_name':
                                                echo '<th>'.lang('lang.english.name').'</th>';
                                                break;
                                            case 'game_type':
                                                echo '<th>'.lang('sys.gd6').'</th>';
                                                break;
                                            case 'system_code':
                                                echo '<th>'.lang('sys.gd7').'</th>';
                                                break;
                                            case 'game_name':
                                                echo '<th>'.lang('sys.gd8').'</th>';
                                                break;
                                            case 'game_code':
                                                echo '<th>'.lang('sys.gd9').'</th>';
                                                break;
                                            case 'external_game_id':
                                                echo '<th>'.lang('External Game ID').'</th>';
                                                break;
                                            case 'rtp':
                                                echo '<th>'.lang('sys.gd42').'</th>';
                                                break;
                                            case 'attributes':
                                                echo '<th>'.lang('Game Attributes').'</th>';
                                                break;
                                            case 'progressive':
                                                echo '<th>'.lang('sys.gd10').'</th>';
                                                break;
                                            case 'mobile_enabled':
                                                echo '<th>'.lang('Mobile').'</th>';
                                                break;
                                            case 'flash_enabled':
                                                echo '<th>'.lang('Flash').'</th>';
                                                break;
                                            case 'html_five_enabled':
                                                echo '<th>'.lang('HTML5').'</th>';
                                                break;
                                            case 'enabled_on_ios':
                                                echo '<th>'.lang('IOS').'</th>';
                                                break;
                                            case 'enabled_on_android':
                                                echo '<th>'.lang('Android').'</th>';
                                                break;
                                            case 'dlc_enabled':
                                                echo '<th>'.lang('DLC').'</th>';
                                                break;
                                            case 'desktop_enabled':
                                                echo '<th>'.lang('Desktop').'</th>';
                                                break;
                                            case 'offline_enabled':
                                                echo '<th>'.lang('Available Offline').'</th>';
                                                break;
                                            case 'flag_hot_game':
                                                echo '<th>'.lang('sys.gd40').'</th>';
                                                break;
                                            case 'flag_new_game':
                                                echo '<th>'.lang('New Game').'</th>';
                                                break;
                                            case 'note':
                                                echo '<th>'.lang('sys.gd11').'</th>';
                                                break;
                                            case 'no_cash_back':
                                                if(!$this->utils->isEnabledFeature('close_cashback')) {
                                                    echo '<th>'.lang('sys.gd18').'</th>';
                                                }
                                                break;
                                            case 'void_bet':
                                                echo '<th>'.lang('sys.gd19').'</th>';
                                                break;
                                            case 'status':
                                                echo '<th>'.lang('sys.gd16').'</th>';
                                                break;
                                            case 'flag_show_in_site':
                                                echo '<th>'.lang('sys.gd17').'</th>';
                                                break;
                                            case 'game_order':
                                                echo '<th>'.lang('sys.gd20').'</th>';
                                                break;
                                            case 'tag_game_order':
                                                echo '<th>'.lang('Tag Game Order').'</th>';
                                                break;
                                            case 'release_date':
                                                echo '<th>'.lang('sys.gd41').'</th>';
                                                break;
                                            case 'created_on':
                                                echo '<th>'.lang('Created At').'</th>';
                                                break;
                                            case 'updated_at':
                                                echo '<th>'.lang('Last Update').'</th>';
                                                break;
                                            case 'deleted_at':
                                                echo '<th>'.lang('Delete Time').'</th>';
                                                break;
                                            case 'locked_flag':
                                                echo '<th>'.lang('Locked Flag').'</th>';
                                                break;
                                            case 'demo_link':
                                                echo '<th>'.lang('Demo Supported').'</th>';
                                                break;
                                        }
                                    }
                                ?>
							</tr>
						</thead>
						<tbody>
						</tbody>
					</table>
				</div>
			</div>

			<div class="panel-footer"></div>

		</div>
	</div>

     <!-- The Modal -->
    <div class="modal" id="batchAddUpdateModal">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <!-- Modal Header -->
                <div class="modal-header">
                    <h4 class="modal-title"><?=lang('Batch Add/Update of Game List')?>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </h4>
                </div>
                    <!-- Modal body -->
                <div class="modal-body">
                    <form id="game-settings-form" class="form-horizontal" action="<?=base_url('/game_description/postBatchInsertUpdateGameList')?>" method="post" accept-charset="utf-8" enctype="multipart/form-data">
                        <p class="text-danger"><?=lang('Note: Chinese characters should be utf-8 encoded before addin the batch or else it will ignore the chinese chars')?></p>
                        <div class="form-group">
                            <label class="col-md-3"><?=lang('CSV File')?></label>
                            <div class="col-md-8">
                                <input name="games" class="user-error" aria-invalid="true" type="file" accept=".csv">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-md-3"><?=lang('Game Platform')?></label>
                            <div class="col-md-8">
                                <select name="game_platform_id" id="game_platform_id_select">
                                    <?php foreach ($gameapis as $key => $gameApi): ?>
                                        <option value="<?=$gameApi['id']?>"><?=$gameApi['system_code']." [".$gameApi['id']."]"?></option>
                                    <?php endforeach ?>
                                </select>
                                <input class="form-check-input" type="checkbox" name="game_platform_checkbox" id="game_platform_checkbox">
                                <input type="number" name="game_platform_id_num" id="game_platform_id_num" disabled>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-md-3"><?=lang('Sample CSV Download')?></label>
                            <div class="col-md-8">
                                <a id='download_csv_add_update_game_des_field' class='btn btn-info btn-sm' onclick='download_csv_add_update_game_des_field()'><span class='glyphicon glyphicon-download-alt' aria-hidden='true'></span> Sample Batch Add/Update Game CSV</a>
                            </div>
                        </div>
                    </form>
                </div>
                    <!-- Modal footer -->
                <div class="modal-footer">
                    <button type="button" id="batchAddUpdateModalSubmit" class="btn btn-info"><?=lang("Submit")?></button>
                    <button type="button" class="btn btn-danger" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>


     <!-- The Modal for BATCH UPDATE OF GAMES -->
    <div class="modal" id="batchUpdateGameDesFieldsModal">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content">
                <!-- Modal Header -->
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title">
                        <?=lang('Batch Update Options')?>
                    </h4>
                </div>
                    <!-- Modal body -->
                <div class="modal-body">
                    <div class="row">
                        <div class="col-lg-6">
                            <form id="game-update-active-form" class="form-horizontal" action="<?=base_url('/game_description/postBatchUpdateActiveGameList')?>" method="post" accept-charset="utf-8" enctype="multipart/form-data">
                                <div class="form-group">
                                    <label class="col-lg-12"><h3><strong><?=lang('Update Active Games')?></strong></h3></label>
                                </div>
                                <div class="form-group">
                                    <label class="col-lg-3"><?=lang('Game Platform')?></label>
                                    <div class="col-lg-8">
                                        <select class="form-control" name="game_platform_id" id="game_platform_id_select">
                                            <?php foreach ($gameapis as $key => $gameApi): ?>
                                                <option value="<?=$gameApi['id']?>"><?=$gameApi['system_code']." [".$gameApi['id']."]"?></option>
                                            <?php endforeach ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-lg-3"><?=lang('CSV File')?></label>
                                    <div class="col-lg-8">
                                        <input class="form-control" name="games" class="user-error" aria-invalid="true" type="file" accept=".csv">

                                    </div>
                                </div>
                                <div class="form-group">
                                    <div class="col-lg-8 col-lg-offset-3">
                                        <a id='download_csv' class='btn btn-info btn-sm' onclick='download_csv()'><span class='glyphicon glyphicon-download-alt' aria-hidden='true'></span> Sample Update Active Games CSV</a>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <div class="col-lg-8 col-lg-offset-3">
                                        <button type="button" id="batchUpdateModalSubmit" class="btn btn-primary"><?=lang("Update Active Games")?></button>
                                    </div>
                                </div>
                                <hr>
                                <p class="text-danger"><?=lang('note.batch_update_active_games')?></p>

                            </form>
                        </div>
                        <?php if ($this->permissions->checkPermissions('batch_update_game_description_fields')): ?>
                        <div class="col-lg-6">
                            <form id="batchUpdateGameDesFieldsModal-form" class="form-horizontal" action="<?=base_url('/game_description/postBatchUpdateGameList')?>" method="post" accept-charset="utf-8" enctype="multipart/form-data">
                                <div class="form-group">
                                    <label class="col-lg-12"><h3><strong><?=lang('Update Game Descriptions')?></strong></h3></label>
                                </div>
                                <div class="form-group">
                                    <label class="col-lg-3"><?=lang('Game Platform')?></label>
                                    <div class="col-lg-8">
                                        <select class="form-control" name="game_platform_id" id="game_platform_id_select">
                                            <?php foreach ($gameapis as $key => $gameApi): ?>
                                                <option value="<?=$gameApi['id']?>"><?=$gameApi['system_code']." [".$gameApi['id']."]"?></option>
                                            <?php endforeach ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-lg-3"><?=lang('CSV File')?></label>
                                    <div class="col-lg-8">
                                        <input class="form-control" name="games" class="user-error" aria-invalid="true" type="file" accept=".csv">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <div class="col-lg-8 col-lg-offset-3">
                                        <a id="download_csv_update_game_des_field" class="btn btn-info btn-sm" onclick="download_csv_update_game_des_field()"><span class="glyphicon glyphicon-download-alt" aria-hidden="true"></span> Sample Update Game Descriptions CSV</a>
                                        <a class="btn btn-default" href="#columnSelectionTable" data-toggle="collapse" title="Hide/Unhide column selection options">
                                            <span class="glyphicon glyphicon-cog"></span>
                                        </a>

                                    </div>
                                </div>

                                <div class="form-group collapse" id="columnSelectionTable">
                                    <div class="col-lg-12">
                                        <p><?=lang("note.batch_update_game_description_download_sample_file")?></p>
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th></th>
                                                    <th>Column Name</th>
                                                    <th>Options</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td>
                                                        <div class="checkbox">
                                                            <label>
                                                                <input type="checkbox" checked name="sample_csv_columns[status]" value="status"></input>
                                                            </label>
                                                        </div>
                                                    </td>
                                                    <td>Status</td>
                                                    <td>1 = Active <br> 0 = Inactive</td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <div class="checkbox">
                                                            <label>
                                                                <input type="checkbox" checked name="sample_csv_columns[flag_show_in_site]" value="flag_show_in_site"></input>
                                                            </label>
                                                        </div>
                                                    </td>
                                                    <td>Flag Show in Site</td>
                                                    <td>1 = Shown <br> 0 = Hidden</td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <div class="checkbox">
                                                            <label>
                                                                <input type="checkbox" checked name="sample_csv_columns[locked_flag]" value="locked_flag">  </input>
                                                            </label>
                                                        </div>
                                                    </td>
                                                    <td>Locked flag</td>
                                                    <td>1 = Locked <br> 0 = Unlocked</td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <div class="checkbox">
                                                            <label>
                                                                <input type="checkbox" checked name="sample_csv_columns[game_order]" value="game_order">  </input>
                                                            </label>
                                                        </div>
                                                    </td>
                                                    <td>Game Order</td>
                                                    <td>Any numerical value</td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <div class="checkbox">
                                                            <label>
                                                                <input type="checkbox" name="sample_csv_columns[mobile_enabled]" value="mobile_enabled">  </input>
                                                            </label>
                                                        </div>
                                                    </td>
                                                    <td>Mobile Enabled</td>
                                                    <td>1 = Enabled <br> 0 = Disabled</td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <div class="checkbox">
                                                            <label>
                                                                <input type="checkbox" name="sample_csv_columns[note]" value="note">  </input>
                                                            </label>
                                                        </div>
                                                    </td>
                                                    <td>Note</td>
                                                    <td>Any text</td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <div class="checkbox">
                                                            <label>
                                                                <input type="checkbox" name="sample_csv_columns[attributes]" value="attributes">  </input>
                                                            </label>
                                                        </div>
                                                    </td>
                                                    <td>Attributes</td>
                                                    <td>Any JSON formatted attribute</td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <div class="checkbox">
                                                            <label>
                                                                <input type="checkbox" name="sample_csv_columns[html_five_enabled]" value="html_five_enabled">  </input>
                                                            </label>
                                                        </div>
                                                    </td>
                                                    <td>HTML 5 Enabled</td>
                                                    <td>1 = Enabled <br> 0 = Disabled</td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <div class="checkbox">
                                                            <label>
                                                                <input type="checkbox" name="sample_csv_columns[english_name]" value="english_name">  </input>
                                                            </label>
                                                        </div>
                                                    </td>
                                                    <td>English Name</td>
                                                    <td>Any text</td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <div class="checkbox">
                                                            <label>
                                                                <input type="checkbox" name="sample_csv_columns[sub_game_provider]" value="sub_game_provider"> </input>
                                                            </label>
                                                        </div>
                                                    </td>
                                                    <td>Sub Game Provider</td>
                                                    <td>Any text</td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <div class="checkbox">
                                                            <label>
                                                                <input type="checkbox" name="sample_csv_columns[enabled_on_android]" value="enabled_on_android">  </input>
                                                            </label>
                                                        </div>
                                                    </td>
                                                    <td>Enabled on Android</td>
                                                    <td>1 = Enabled <br> 0 = Disabled</td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <div class="checkbox">
                                                            <label>
                                                                <input type="checkbox" name="sample_csv_columns[enabled_on_ios]" value="enabled_on_ios"> </input>
                                                            </label>
                                                        </div>
                                                    </td>
                                                    <td> Enabled on IOS</td>
                                                    <td>1 = Enabled <br> 0 = Disabled</td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <div class="checkbox">
                                                            <label>
                                                                <input type="checkbox" name="sample_csv_columns[flag_new_game]" value="flag_new_game">  </input>
                                                            </label>
                                                        </div>
                                                    </td>
                                                    <td>Flag New Game</td>
                                                    <td>1 = Flagged as New <br> 0 = Not Flagged</td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <div class="checkbox">
                                                            <label>
                                                                <input type="checkbox" name="sample_csv_columns[flag_hot_game]" value="flag_hot_game">  </input>
                                                            </label>
                                                        </div>
                                                    </td>
                                                    <td>Flag Hot Game</td>
                                                    <td>1 = Flagged as Hot <br> 0 = Not Flagged</td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <div class="checkbox">
                                                            <label>
                                                                <input type="checkbox" name="sample_csv_columns[rtp]" value="rtp">  </input>
                                                            </label>
                                                        </div>
                                                    </td>
                                                    <td>RTP</td>
                                                    <td>Percentage %</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <div class="col-lg-8 col-lg-offset-3">
                                        <button type="button" id="batchUpdateGameDesFieldsModalSubmit" class="btn btn-primary"><?=lang("Update Game Descriptions")?></button>
                                    </div>
                                </div>

                                <hr>

                                <p class="text-danger"><?=lang("note.batch_update_game_description")?></p>

                            </form>
                        </div>
                        <?php endif; ?>
                    </div>

                </div>
            </div>
        </div>
    </div>


<!-- The Modal for BATCH UPDATE OF GAMES -->
<div class="modal" id="batchTagGamesModal">
   <div class="modal-dialog modal-dialog-centered modal-xl">
       <div class="modal-content">
           <!-- Modal Header -->
           <div class="modal-header">
               <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
               <h4 class="modal-title">
                   <?=lang('Insert/Update/Remove Game Tags')?>
               </h4>
           </div>
               <!-- Modal body -->
           <div class="modal-body">
               <div class="row">
                   <div class="col-lg-6">
                       <form id="game-update-active-form" class="form-horizontal" action="<?=base_url('/game_description/postBatchTagGames')?>" method="post" accept-charset="utf-8" enctype="multipart/form-data">
                           <div class="form-group">
                               <label class="col-lg-3"><?=lang('CSV File')?></label>
                               <div class="col-lg-8">
                                   <input class="form-control" name="tags" class="user-error" aria-invalid="true" type="file" accept=".csv"/>
                               </div>
                           </div>
                           <div class="form-group">            
                                <div class="col-lg-8 col-lg-offset-3">
                                    <a id='download_batch_tag_csv' class='btn btn-info btn-sm' onclick='download_batch_tag_csv()'><span class='glyphicon glyphicon-download-alt' aria-hidden='true'></span> Download Template</a>
                                </div>
                           </div>
                           <div class="form-group">
                               <div class="col-lg-8 col-lg-offset-3">
                                   <button type="submit" id="batchTagGameModalSubmit" class="btn btn-primary"><?=lang("Submit")?></button>
                               </div>    
                           </div>
                       </form>
                   </div>
                   
               </div>

           </div>
       </div>
   </div>
</div>


	<!----------------EDIT-FORM Game Details start---------------->
	<div class="col-md-5" id="edit_game_description_details">

		<div class="panel panel-info panel-primary">
			<div class="panel-heading custom-ph">
				<h4 class="panel-title">
					<i class="icon-pencil"></i> <span id="add-edit-panel-title"></span>
					<a href="#close" class="btn pull-right panel-button btn-info btn-xs" id="closeDetails" ><span class="glyphicon glyphicon-remove"></span></a>
				</h4>

				<div class="clearfix"></div>
			</div>
			<div class="panel panel-body" id="details_panel_body">

				<form method="post" role="form" id="game-description-form">
					<div class="form-group">
						<input type="hidden" id="gd_id" name="gd_id" >
						<label for="game"><?=lang('sys.gd5');?></label>
						<select name="game_platform_id" id="game_platform_id" class="form-control input-sm"></select>
					</div>
					<div class="form-group">
						<label for="game"><?=lang('sys.gd6');?></label>
						<select name="game_type_id" id="game_type_id" class="form-control input-sm" disabled></select>
					</div>
                    <div class="form-group " id="game_name_label">
                        <label for="game_name"><?=lang('sys.gd8');?></label>
                        <input type="checkbox" id="game_name_use_json" name="game_name_use_json" value="1" /> <label for="game_name_use_json"><?=lang('Use JSON');?></label>
                        <input type="text" value="" class="form-control" id="game_name" name="game_name">
                        <pre class="form-control" id="game_name_editor" name="game_name_editor"></pre>
                        <!-- <select name="game_name" id="game_name" class="form-control input-sm"></select> -->
                    </div>
					<div class="form-group " id="game_attributes_label">
						<label for="game_attributes" ><?=lang('Attributes');?></label>
						<input type="checkbox" id="game_attributes_use_json" name="game_attributes_use_json" value="1" /> <label for="game_attributes_use_json"><?=lang('Use JSON');?></label>
						<input type="text" value="" class="form-control" id="game_attributes" name="game_attributes">
						<pre class="form-control" id="game_attributes_editor" name="game_attributes_editor"></pre>
						<!-- <select name="game_attributes" id="game_attributes" class="form-control input-sm"></select> -->
					</div>

					<div class="form-group">
						<label for="game_code"><?=lang('sys.gd9');?></label>
						<input type="text"  value="" class="form-control"  id="game_code" name="game_code">
					</div>
                    <div class="form-group">
						<label for="rtp"><?=lang('sys.gd42');?></label>
						<input type="text" value="" class="form-control percent" maxlength="6" id="rtp" name="rtp">
					</div>
                    <div class="form-group">
                        <label for="game_tag"><?= lang('Game Tags'); ?></label>
                        <select class="select form-control" id="game_tags" name="game_tags[]" multiple="multiple">
                            <?php foreach($game_tags as $game_tag) { ?>
                                <!-- <option title="<?= $game_tag['tag_code'] ?>" value="<?= $game_tag['id'] ?>"><?= lang($game_tag['tag_name']) ?></option> -->
                                <option title="<?= lang($game_tag['tag_name']) ?>" value="<?= $game_tag['id'] ?>"><?= $game_tag['tag_code'] ?></option>
                            <?php } ?>
                        </select>
					</div>
					<div class="form-group">
						<label for="external_game_id"><?=lang('lang.external.game.id');?></label>
						<input type="text" value="" class="form-control" id="external_game_id" name="external_game_id">
					</div>
					<div class="form-group">
						<label for="english_name"><?=lang('lang.english.name');?></label>
						<input type="text" value="" class="form-control" id="english_name" name="english_name">
					</div>
					<div class="form-group">
						<label for="note"><?=lang('sys.gd11');?></label>
						<textarea class="form-control"id="note"  maxlength="1000" name="note"rows="5"></textarea>
					</div>
					<div class="form-group">
						<label for="no_cash_back"><?=lang('sys.gd18');?></label>
						<input type="text" value="" maxlength="1" class="form-control" id="no_cash_back" name="no_cash_back">
					</div>
					<div class="form-group">
						<label for="void_bet"><?=lang('sys.gd19');?></label>
						<input type="text" value="" class="form-control" id="void_bet" name="void_bet">
					</div>
					<div class="form-group">
						<label for="game_order"><?=lang('sys.gd20');?></label>
						<input type="number" value="" class="form-control" id="game_order" name="game_order">
					</div>
                    <div class="form-group">
                        <label for="release_date" class="control-label"><?=lang('sys.gd41');?></label>
                        <input  type="text" value="" class="form-control datepicker" id="release_date" name="release_date">
                    </div>

					<div class="form-group">
	                    <li class="list-group-item" >
	                        <div class="label-list"><?=lang('sys.gd10');?></div>
	                        <div class="info-tooltip-wrapper">
	                        	<span class="glyphicon glyphicon-info-sign"></span>
	                        	<div class="tooltip-info">
	                        		<p><?=lang('Atrribute of the game if have Jackpot')?></p>
	                        	</div>
	                        </div>
	                        <div class="material-switch pull-right">
	                            <input id="progressive" name="progressive" type="checkbox"/>
	                            <label for="progressive" class="label-success"></label>
	                        </div>
	                    </li>
	                    <li class="list-group-item">
	                        <div class="label-list"><?=lang('Android');?></div>
	                        <div class="info-tooltip-wrapper">
	                        	<span class="glyphicon glyphicon-info-sign"></span>
	                        	<div class="tooltip-info"><p><?=lang('Game attribute if the game is available on android')?></p></div>
	                        </div>
	                        <div class="material-switch pull-right">
	                            <input id="enabled_on_android" name="enabled_on_android" type="checkbox"/>
	                            <label for="enabled_on_android" class="label-success"></label>
	                        </div>
	                    </li>
	                    <li class="list-group-item">
	                        <div class="label-list"><?=lang('IOS');?></div>
	                        <div class="info-tooltip-wrapper">
	                        	<span class="glyphicon glyphicon-info-sign"></span>
	                        	<div class="tooltip-info"><p><?=lang('Game attribute if the game is available on IOS')?></p></div>
	                        </div>
	                        <div class="material-switch pull-right">
	                            <input id="enabled_on_ios" name="enabled_on_ios" type="checkbox"/>
	                            <label for="enabled_on_ios" class="label-success"></label>
	                        </div>
	                    </li>
	                    <li class="list-group-item">
	                        <div class="label-list"><?=lang('sys.gd12');?></div>
	                        <div class="info-tooltip-wrapper">
	                        	<span class="glyphicon glyphicon-info-sign"></span>
	                        	<div class="tooltip-info"><p><?=lang('Game attribute if the game is available on desktop app')?></p></div>
	                        </div>
	                        <div class="material-switch pull-right">
	                            <input id="dlc_enabled" name="dlc_enabled" type="checkbox"/>
	                            <label for="dlc_enabled" class="label-success"></label>
	                        </div>
	                    </li>
	                    <li class="list-group-item">
	                        <div class="label-list"><?=lang('sys.gd13');?></div>
	                        <div class="info-tooltip-wrapper">
	                        	<span class="glyphicon glyphicon-info-sign"></span>
	                        	<div class="tooltip-info"><p><?=lang('Game attribute if the game is available on Web')?></p></div>
	                        </div>
	                        <div class="material-switch pull-right">
	                            <input id="flash_enabled" name="flash_enabled" type="checkbox"/>
	                            <label for="flash_enabled" class="label-success"></label>
	                        </div>
	                    </li>
	                    <li class="list-group-item">
	                        <div class="label-list"><?=lang('HTML5');?></div>
	                        <div class="info-tooltip-wrapper">
	                        	<span class="glyphicon glyphicon-info-sign"></span>
	                        	<div class="tooltip-info"><p><?=lang('Game attribute if the game is available on Mobile and Web')?></p></div>
	                        </div>
	                        <div class="material-switch pull-right">
	                            <input id="html_five_enabled" name="html_five_enabled" type="checkbox"/>
	                            <label for="html_five_enabled" class="label-success"></label>
	                        </div>
	                    </li>
	                    <li class="list-group-item">
	                        <div class="label-list"><?=lang('sys.gd14');?></div>
	                        <div class="info-tooltip-wrapper">
	                        	<span class="glyphicon glyphicon-info-sign"></span>
	                        	<div class="tooltip-info"><p><?=lang('Game attribute if the game is available offline')?></p></div>
	                        </div>
	                        <div class="material-switch pull-right">
	                            <input id="offline_enabled" name="offline_enabled" type="checkbox"/>
	                            <label for="offline_enabled" class="label-success"></label>
	                        </div>
	                    </li>
						<li class="list-group-item">
	                        <div class="label-list"><?=lang('sys.gd15');?></div>
	                        <div class="info-tooltip-wrapper">
	                        	<span class="glyphicon glyphicon-info-sign"></span>
	                        	<div class="tooltip-info"><p><?=lang('Game attribute if the game is available on Mobile')?></p></div>
	                        </div>
	                        <div class="material-switch pull-right">
	                            <input id="mobile_enabled" name="mobile_enabled" type="checkbox"/>
	                            <label for="mobile_enabled" class="label-success"></label>
	                        </div>
	                    </li>
						<li class="list-group-item">
	                        <div class="label-list"><?=lang('sys.gd43');?></div>
	                        <div class="info-tooltip-wrapper">
	                        	<span class="glyphicon glyphicon-info-sign"></span>
	                        	<div class="tooltip-info"><p><?=lang('Game attribute if the game is available on Desktop')?></p></div>
	                        </div>
	                        <div class="material-switch pull-right">
	                            <input id="desktop_enabled" name="desktop_enabled" type="checkbox"/>
	                            <label for="desktop_enabled" class="label-success"></label>
	                        </div>
	                    </li>
                        <li class="list-group-item">
                            <div class="label-list"><?=lang('sys.gd39');?></div>
                            <div class="info-tooltip-wrapper">
                                <span class="glyphicon glyphicon-info-sign"></span>
                                <div class="tooltip-info"><p><?=lang('Game attribute if the game is locked')?></p></div>
                            </div>
                            <div class="material-switch pull-right">
                                <input id="locked_flag" name="locked_flag" type="checkbox"/>
                                <label for="locked_flag" class="label-success"></label>
                            </div>
                        </li>
                        <li class="list-group-item">
                            <div class="label-list"><?=lang('sys.gd40');?></div>
                            <div class="info-tooltip-wrapper">
                                <span class="glyphicon glyphicon-info-sign"></span>
                                <div class="tooltip-info"><p><?=lang('Game attribute if the game is Hot')?></p></div>
                            </div>
                            <div class="material-switch pull-right">
                                <input id="flag_hot_game" name="flag_hot_game" type="checkbox"/>
                                <label for="flag_hot_game" class="label-success"></label>
                            </div>
                        </li>
                        <li class="list-group-item">
                            <div class="label-list"><?=lang('Demo supported');?></div>
                            <div class="info-tooltip-wrapper">
                                <span class="glyphicon glyphicon-info-sign"></span>
                                <div class="tooltip-info"><p><?=lang('Game attribute if the demo link is supported')?></p></div>
                            </div>
                            <div class="material-switch pull-right">
                                <input id="demo_link" name="demo_link" type="checkbox"/>
                                <label for="demo_link" class="label-success"></label>
                            </div>
                        </li>
                        <!-- (Deprecated) Separate updates for these fields -->
						<!-- <li class="list-group-item">
	                        <div class="label-list"><?=lang('New Game');?></div>
	                        <div class="info-tooltip-wrapper">
	                        	<span class="glyphicon glyphicon-info-sign"></span>
	                        	<div class="tooltip-info"><p><?=lang('Game attribute if the game is new')?></p></div>
	                        </div>
	                        <div class="material-switch pull-right">
	                            <input id="flag_new_game" name="flag_new_game" type="checkbox"/>
	                            <label for="flag_new_game" class="label-success"></label>
	                        </div>
	                    </li>
	                    <li class="list-group-item">
	                        <div class="label-list"><?=lang('sys.gd16');?></div>
	                        <div class="info-tooltip-wrapper">
	                        	<span class="glyphicon glyphicon-info-sign"></span>
	                        	<div class="tooltip-info"><p><?=lang('Status of the game if disabled or not')?></p></div>
	                        </div>
	                        <div class="material-switch pull-right">
	                            <input id="status" name="status" type="checkbox"/>
	                            <label for="status" class="label-success"></label>
	                        </div>
	                    </li>
	                    <li class="list-group-item">
	                        <div class="label-list"><?=lang('sys.gd17');?></div>
	                        <div class="info-tooltip-wrapper">
	                        	<span class="glyphicon glyphicon-info-sign"></span>
	                        	<div class="tooltip-info"><p><?=lang('Game attribute if the game is available on site')?></p></div>
	                        </div>
	                        <div class="material-switch pull-right">
	                            <input id="flag_show_in_site" name="flag_show_in_site" type="checkbox"/>
	                            <label for="flag_show_in_site" class="label-success"></label>
	                        </div>
	                    </li> -->
					</div>
					<!----------NOTE: BUTTON TITLE WILL BE UPDATE THROUG JAVASCTRIPT---------------->
					<button id="add-update-button"  type="submit" class="btn btn-scooter"></button>

				</form>


			</div>
		</div>


	</div>
	<!---------------EDIT FORM Game Details end---------------->

<div id="gameDescriptionHistory"  class="modal fade "  data-backdrop="static"
	data-keyboard="false"  tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-xl">
		<div class="modal-content" >

			<div class="modal-header panel-heading">
				<h4 id=""><?=lang('Game History')?>
					<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
				</h4>
			</div>
			<div class="modal-body">
				<div class="table-responsive">
					<table id="gameDescriptionHistoryTable" class="table table-striped">
						<thead>
							<tr>
								<th id="id"><?=lang('id')?></th>
								<th id="game_description_id"><?=lang('Game Description Id')?></th>
								<th id="game_platform_id"><?=lang('Game Platform Id')?></th>
								<th id="action"><?=lang('Action')?></th>
								<th id="game_type_id"><?=lang('Game Type Id')?></th>
								<th id="game_name"><?=lang('Game Name')?></th>
								<th id="game_code"><?=lang('Game Code')?></th>
								<th id="external_game_id"><?=lang('External Game Id')?></th>
								<th id="rtp"><?=lang('sys.gd42')?></th>
								<th id="attributes"><?=lang('Attributes')?></th>
								<th id="note"><?=lang('Note')?></th>
								<th id="english_name"><?=lang('English Name')?></th>
								<th id="external_game_id"><?=lang('External Game Id')?></th>
								<th id="clientid"><?=lang('Clientid')?></th>
								<th id="moduleid"><?=lang('Moduleid')?></th>
								<th id="sub_game_provider"><?=lang('Sub Game Provider')?></th>
								<th id="flash_enabled"><?=lang('Flash Enabled')?></th>
								<th id="status"><?=lang('Status')?></th>
								<th id="flag_show_in_site"><?=lang('Flag Show In Site')?></th>
								<th id="no_cash_back"><?=lang('No Cash Back')?></th>
								<th id="void_bet"><?=lang('Void Bet')?></th>
								<th id="game_order"><?=lang('Game Order')?></th>
								<th id="related_game_desc_id"><?=lang('Related Game Desc Id')?></th>
								<th id="dlc_enabled"><?=lang('Dlc Enabled')?></th>
								<th id="progressive"><?=lang('Progressive')?></th>
								<th id="enabled_freespin"><?=lang('Enabled Freespin')?></th>
								<th id="offline_enabled"><?=lang('Offline Enabled')?></th>
								<th id="mobile_enabled"><?=lang('Mobile Enabled')?></th>
								<th id="desktop_enabled"><?=lang('Desktop Enabled')?></th>
								<th id="enabled_on_android"><?=lang('Android')?></th>
								<th id="enabled_on_ios"><?=lang('Ios')?></th>
								<th id="flag_new_game"><?=lang('Flag New Game')?></th>
								<th id="html_five_enabled"><?=lang('Html Five Enabled')?></th>
								<th id="demo_link"><?=lang('Demo Link')?></th>
								<th id="md5_fields"><?=lang('Md5 Fields')?></th>
								<th id="deleted_at"><?=lang('Deleted At')?></th>
								<th id="created_on"><?=lang('Created On')?></th>
								<th id="updated_at"><?=lang('Updated At')?></th>
                                <th id="updated_by"><?=lang('sys.updatedby')?></th>
                                <th id="user_ip_address"><?=lang('sys.ip_address')?></th>
							</tr>
						</thead>
						<tbody>

						</tbody>
					</table>
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-linkwater" id="cancel-delete"data-dismiss="modal"><?=lang('Close')?></button>
			</div>
		</div>
	</div>
</div>

<script type="text/javascript">
$("#collapseSubmenuGameDescription").addClass("in");
$("a#view_game_description").addClass("active");
// $("a#viewGameListSettings").addClass("active");
var gameNameEditor;
var gameAttributesEditor;

$(document).ready(function(){

    /* $('.datepicker').datepicker({
            format: 'yyyy-mm-dd',
            language: '<?=$this->language_function->convertToDatePickerLang($this->language_function->getCurrentLanguage())?>',
            startView : 'year',
        }); */

    $(".datepicker").datetimepicker({
        format:'Y-m-d H:i',
        lang: '<?=$this->language_function->convertToDatePickerLang($this->language_function->getCurrentLanguage())?>'
    });

    function getFormattedDate(date) {
        var day = date.getDate();
        var month = date.getMonth() + 1;
        var year = date.getFullYear().toString().slice(2);
        return day + '-' + month + '-' + year;
    }

    $('#resetFields').click(function(){
        $('.select2-selection__choice').remove();
        $('.select2-selection__clear').remove();
        $('.clearField').attr('value', '');
        $('.clearField option:selected').removeAttr('selected');
    });

	// Init syntax highlight for JSON string in extra_info
	hljs.initHighlightingOnLoad();

	// Init ACE editor for JSON
    gameAttributesEditor = ace.edit("game_attributes_editor");
    gameAttributesEditor.setTheme("ace/theme/tomorrow");
    gameAttributesEditor.session.setMode("ace/mode/json");

    // Hide the ACE editor for JSON
    $('#game_attributes_editor').hide();

    // Implement switch between text field and rich editor
    $('#game_attributes_use_json').click(function(){
        if($(this).prop('checked')) {
            $('#game_attributes').hide();
            $('#game_attributes_editor').show();
            gameAttributesEditor.resize(); // so that the content refreshes
        } else {
            $('#game_attributes').show();
            $('#game_attributes_editor').hide();
        }
    });

    // Init ACE editor for JSON
	gameNameEditor = ace.edit("game_name_editor");
	gameNameEditor.setTheme("ace/theme/tomorrow");
	gameNameEditor.session.setMode("ace/mode/json");

	// Hide the ACE editor for JSON
	$('#game_name_editor').hide();

	// Implement switch between text field and rich editor
	$('#game_name_use_json').click(function(){
		if($(this).prop('checked')) {
			$('#game_name').hide();
			$('#game_name_editor').show();
			gameNameEditor.resize(); // so that the content refreshes
		} else {
			$('#game_name').show();
			$('#game_name_editor').hide();
		}
	});

	$('body').tooltip({
		selector: '[data-toggle="tooltip"]',
		placement: "bottom"
	});

    var hiddenColumns = [];
    var gameListColumnOrder = JSON.parse('<?=json_encode($this->utils->getConfig('game_list_column_order'));?>');
    var gameListColumnDefs = JSON.parse('<?=json_encode($this->utils->getConfig('game_list_columnDefs'));?>');

    if(gameListColumnDefs['not_visible_columns']){
        var columnOrder = gameListColumnOrder['custom_order'] ? gameListColumnOrder['custom_order'] : gameListColumnOrder['default_order'];
        var columnHidden = gameListColumnDefs['custom_not_visible_columns'] ? gameListColumnDefs['custom_not_visible_columns'] : gameListColumnDefs['not_visible_columns'];

        $.each(columnHidden, function(index, column){
            columnIndex = columnOrder.indexOf(column);
            hiddenColumns.push(columnIndex);
        });
    }

	var dataTable = $('#my_table').DataTable({

		autoWidth: false,
		searching: false,
		dom: "<'panel-body'<'pull-right'B><'pull-right progress-container hidden-xs'>l><'dt-information-summary1 text-info pull-left' i>t<'panel-body'<'pull-right'p><'dt-information-summary2 text-info pull-left' i>>",
		<?php if($this->utils->isEnabledFeature('column_visibility_report')){ ?>
            stateSave: true,
        <?php } else { ?>
            stateSave: false,
        <?php } ?>
		buttons: [
			{
                extend: 'colvis',
                className: 'btn-linkwater',
                postfixButtons: [
                    { extend: 'colvisRestore', text: '<?=lang('showDef')?>' },
                    { extend: 'colvisGroup', text: '<?=lang('showAll')?>', show: ':hidden'}
                ]
            },
				<?php

                    if( $this->permissions->checkPermissions('export_game_description') ){

                ?>
			 {
                text: "<?php echo lang('CSV Export'); ?>",
                className:'btn btn-sm btn-portage export_excel',
                action: function ( e, dt, node, config ) {
                    visible_columns = get_visible_columns();
                    var d = {'extra_search':$('#form-filter').serializeArray(), 'draw':1, 'length':-1, 'start':0, 'visible_columns': visible_columns};
                    if(visible_columns.length === 0){
                        alert("<?=lang('Please add a column before the export')?>")
                        return false;
                    }

                    if(visible_columns.length === 1 && visible_columns.includes("<?=lang('sys.gd33')?>")){
                        alert("<?=lang('Please add a column before the export, other than the action')?>")
                        return false;
                    }
                    // utils.safelog(d);
                    $.post(site_url('/export_data/gameDescriptionList'), d, function(data){
                        // utils.safelog(data);

                        //create iframe and set link
                        if(data && data.success){
                            $('body').append('<iframe src="'+data.link+'" frameborder="0" scrolling="no" style="border:0px;width:0px;height:0px"></iframe>');
                        }else{
                            alert('export failed');
                        }
                    });
                }
            }

            <?php
            	}
            ?>
		],
		columnDefs: [
            {
                "targets" : hiddenColumns,
                "visible" : false
            },
            {
                "targets": 0,
                "orderable": false
            }
        ],
		order: [[1, 'asc']],

		// SERVER-SIDE PROCESSING
		processing: true,
		serverSide: true,
		ajax: function (data, callback, settings) {
			data.extra_search = $('#form-filter').serializeArray();
			data.extra_search[data.extra_search.length] = {
					'name' : 'gameTypeIdHide',
					'value' : $('#gameTypeIdHide').val()
			}
			// console.log(data.extra_search);

			$.post(base_url + "api/gameDescriptionList", data, function(data) {
                // console.log(data);
				callback(data);
				if ( dataTable.rows( { selected: true } ).indexes().length === 0 ) {
				    dataTable.buttons().disable();
				}
				else {
					dataTable.buttons().enable();
				}
			},'json');
		},

	});

    function get_visible_columns() {
        let visible_columns = [];
        console.log("columns", dataTable.columns());
        dataTable.columns().every( function () {
            if(this.visible()){
                // console.log("column index", this.index() );
                console.log("column header", this.header() );
                visible_columns.push(this.header().textContent);
            }
        } );
        console.log("visible columns", visible_columns);
        return visible_columns;
    }

   $('#search_main').click( function(e) {
       e.preventDefault()
     dataTable.ajax.reload();
    });

    $("#game_tags").select2({
        width: '100%',
        placeholder: "<?= lang('Select Game Tags'); ?>",
        allowClear: true
    });

    $("#gameTag").select2({
        width: '100%',
        placeholder: "<?= lang('Select Game Tags'); ?>",
        allowClear: true
    });

    $("#gd_agents").select2({
        width: '100%',
        placeholder: "<?= lang('Select Agents'); ?>",
        allowClear: true
    });
});//end document ready.

/*Set Global Variables */
var  GET_GAMES_AND_GET_TYPES_URL ='<?php echo site_url('game_description/getGamesAndGameTypes') ?>/',
GET_GAME_DESCRIPTION_URL ='<?php echo site_url('game_description/editGameDescription') ?>/',
ADD_GAME_DESCRIPTION_URL ='<?php echo site_url('game_description/addGameDescription') ?>/',
UPDATE_GAME_DESCRIPTION_URL ='<?php echo site_url('game_description/updateGameDescription') ?>/',
DELETE_GAME_DESCRIPTION_URL ='<?php echo site_url('game_description/deleteGameDescription') ?>/',
UPDATE_GAME_DESCRIPTION_STATUS_URL ='<?php echo site_url('game_description/updateGameDescriptionStatus') ?>/',
REFRESH_PAGE_URL = '<?php echo site_url('game_description/viewGameDescription') ?>',
GET_GAME_DESCRIPTION_HISTORY = '<?php echo site_url('game_description/getGameDescriptionHistory') ?>';
var game_description_url = '<?php echo site_url('game_description/postBatchInsertUpdateGameList')?>';
var baseUrl = "<?=base_url()?>";
var message = {
    gameType        : '<?= lang('Select Game Type'); ?>'
};
var gameTypeData = "<?=$conditions['gameType']?>",
	gamePlatformId = "<?=$conditions['gamePlatform']?>",
	gameTypeId = '<?=isset($conditions['gameType']) ?$conditions['gameType']:""?>';

var LANG ={
	ADD_PANEL_TITLE: "<?=lang('sys.gd38');?>",
	EDIT_PANEL_TITLE:"<?=lang('sys.gd2');?>",
	ADD_BUTTON_TITLE:"<i class='fa fa-check'></i> <?=lang('Add new game');?>",
	UPDATE_BUTTON_TITLE:"<i class='fa fa-check'></i> <?=lang('sys.gd3');?>",
	DELETE_CONFIRM_MESSAGE:"<?=lang('sys.gd4');?>",
	EDIT:"<?=lang('sys.gd23');?>",
	EDIT_COLUMN:"<?=lang('sys.gd24');?>",
	DELETE_ITEMS:"<?=lang('sys.gd21');?>",
	ADD_GAME_DESC:"<?=lang('sys.gd38');?>"
};

<?php if ($this->utils->getConfig('show_non_active_game_api_game_list')) : ?>
var gameCodes = <?=($conditions['gameCode']) ?"'".$conditions['gameCode']."'":0?>;
<?php else: ?>
var gameCodes = <?=($conditions['gameCode']) ?$conditions['gameCode']:0?>;
<?php endif; ?>

var filters = <?php echo $conditions['filters'];?>;
var show_non_active_game_api_game_list = <?=($this->utils->getConfig('show_non_active_game_api_game_list'))?true:0?>;

function log_game_type_id(id){
// console.log(id);
}

document.querySelector('.percent').addEventListener('input', function(e) {
    let int = 0;

    if (e.target.value.length > 1) {
        int = e.target.value.slice(0, e.target.value.length - 1);
    } else {
        int = e.target.value.slice(0, e.target.value.length);
    }

    if (/[a-zA-Z]/.test(int)) {
        int = int.replace(/[a-zA-Z]/g, '');
    }

    if (int.slice(0, 2) == '00') {
         int = '01' + int.slice(2, 3);
    }

    if (int.includes('%') || int === '') {
            e.target.value = '';
    } else if (int.length >= 3 && int.length <= 4 && !int.includes('.')) {
        e.target.value = int.slice(0, 2) + '.' + int.slice(2, 3) + '%';
        e.target.setSelectionRange(4, 4);
    } else {
        if (int !== 0) {
            e.target.value = int + '%';
            e.target.setSelectionRange(e.target.value.length - 1, e.target.value.length - 1);
        } else {
            e.target.value = '';
        }
    }

    // console.log('For robots: ' + getInt(e.target.value));
});

function getInt(val) {
    let v = parseFloat(val);

    if (v % 1 === 0) {
        return v;
    } else {
        let n = v.toString().split('.').join('');
        return parseInt(n);
    }
}

</script>