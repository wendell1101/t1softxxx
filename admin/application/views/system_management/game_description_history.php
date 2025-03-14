<div class="panel panel-primary hidden">

    <div class="panel-heading">
        <h4 class="panel-title">
            <i class="fa fa-search"></i> <?=lang("lang.search")?>
            <span class="pull-right">
                <a data-toggle="collapse" href="#collapseGameDescription" class="btn btn-xs <?=$this->utils->getConfig('use_new_sbe_color') ? 'btn-info' : 'btn-default'?> <?= $this->config->item('default_open_search_panel') ? '' : 'collapsed'?>"></a>
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
                                <option value="<?=($gameApi['id'])?>" <?=($gameApi['id']==$conditions['gamePlatform'])?'selected':''?>><?=$gameApi['system_code']?></option>
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
	                <div class="col-md-2">
	                    <label class="control-label" for="gameCode[]"><?=lang('sys.gd9')?>:</label>
	                    <?php if ($this->utils->getConfig('show_non_active_game_api_game_list')): ?>
	                    	<input type="text" name="gameCode[]" value="<?=($conditions['gameCode'] === 0|| empty($conditions['gameCode']))?null:$conditions['gameCode']?>" class="form-control clearField">
						<?php else: ?>
                       	<select id="selectGameCode" class="multi-select-filter form-control clearField" name="gameCode[]" multiple="multiple">
                       		<?php foreach ($gameCodes as $key => $gameCode): ?>
                       			<option value="<?=$gameCode?>"><?=lang($gameCode)?></option>
                       		<?php endforeach ?>
						</select>
	                    <?php endif ?>
	                </div>
	                <div class="col-md-2">
	                    <label class="control-label" for="filters[]"><?=lang('Game Attributes');?>:</label>
                       	<select id="filters" class="multi-select-filter form-control clearField" name="filters[]" multiple="multiple">
                       		<?php foreach ($filters as $key => $filter): ?>
                       			<option value="<?=$filter?>"><?=lang($key)?></option>
                       		<?php endforeach ?>
						</select>
	                </div>
	                <div class="col-md-2">
						<label class="control-label" for="type"><?=lang('Action Type')?></label>
						<select class="form-control input-sm clearField" name="action" id="action">
							<option value=""><?=lang('Select Action Type')?></option>
							<?php foreach ($actions as $action): ?>
								<option value="<?=$action?>" <?=$action==$conditions['action'] ? 'selected':null?>><?=$action?></option>
							<?php endforeach ?>
						</select>
					</div>
	            </div>
	        </div>
	        <div class="panel-footer text-right">
                <input type="reset" class="btn btn-sm btn-linkwater" id="resetFields" value="<?=lang('lang.clear');?>">
	            <input type="submit" value="<?=lang('lang.search')?>" id="search_main" class="btn btn-sm <?=$this->utils->getConfig('use_new_sbe_color') ? 'btn-portage' : 'btn-info'?>">
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
                    <?=lang('Game List History');?>
                    <a href="<?=base_url('game_description/viewGameDescription')?>" class="goto btn btn-primary pull-right <?=$this->utils->getConfig('use_new_sbe_color') ? 'btn-xs' : 'btn-sm'?>" <?=$this->utils->getConfig('use_new_sbe_color') ? 'style="margin-top:0px;"' : ''?>>
                        <i class="glyphicon glyphicon-list-alt" data-placement="bottom"></i>
                        <?=lang('sys.gd0');?>
                    </a>
                </h3>
            </div>

			<div class="panel-body" id="list_panel_body">
				<div class="table-responsive">
				<div class="clearfix"></div>
				<table class="table table-bordered table-hover dataTable" id="my_table" >
					<thead>
						<tr>
							<th><?=lang('sys.gd33');?></th>
							<th><?=lang('lang.english.name');?></th>
							<th><?=lang("Action");?></th>
							<th><?=lang("sys.gd6");?></th>
							<th><?=lang("sys.gd7");?></th>
							<th><?=lang("sys.gd8");?></th>
							<th><?=lang("sys.gd9");?></th>
							<th><?=lang("sys.gd42");?></th>
							<th><?=lang("lang.external.game.id");?></th>
							<th><?=lang("Game Attributes");?></th>
							<th><?=lang("sys.gd10");?></th>
							<th><?=lang("Mobile");?></th>
							<th><?=lang("Flash");?></th>
							<th><?=lang("HTML5");?></th>
							<th><?=lang("IOS");?></th>
							<th><?=lang("Android");?></th>
							<th><?=lang("Desktop");?></th>
							<th><?=lang("Available Offline");?></th>
							<th><?=lang("New Game");?></th>
							<th><?=lang("sys.gd11");?></th>
							<?php if(!$this->utils->isEnabledFeature('close_cashback')): ?>
								<th><?=lang("sys.gd18");?></th>
							<?php endif; ?>
							<th><?=lang("sys.gd19");?></th>
							<th><?=lang("sys.gd16");?></th>
							<th><?=lang("sys.gd17");?></th>
							<th><?=lang("sys.gd20");?></th>
							<th><?=lang("Created At");?></th>
							<th><?=lang("Last Update");?></th>
							<th><?=lang("Delete Time");?></th>
                            <th><?=lang('Updated By')?></th>
                            <th><?=lang('IP Address')?></th>
                            <th><?=lang("Demo supported");?></th>
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

<div id="gameDescriptionHistory"  class="modal fade "  data-backdrop="static"
	data-keyboard="false"  tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-xl">
		<div class="modal-content">

			<div class="modal-header panel-heading">
				<h4 id=""><?=lang('Game History')?>
					<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
				</h4>
			</div>
			<div class="modal-body">
				<div class="row">
					<div class="col-md-12">
                        <div class="table-responsive">
                        <div class="clearfix"></div>

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
                                        <th id="rtp"><?=lang('sys.gd42')?></th>
                                        <th id="attributes"><?=lang('Game Attributes')?></th>
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
                                        <th id="enabled_on_android"><?=lang('Android')?></th>
                                        <th id="enabled_on_ios"><?=lang('Ios')?></th>
                                        <th id="flag_new_game"><?=lang('Flag New Game')?></th>
                                        <th id="html_five_enabled"><?=lang('Html Five Enabled')?></th>
                                        <th id="demo_link"><?=lang('Demo Link')?></th>
                                        <th id="md5_fields"><?=lang('Md5 Fields')?></th>
                                        <th id="deleted_at"><?=lang('Deleted At')?></th>
                                        <th id="created_on"><?=lang('Created On')?></th>
                                        <th id="updated_at"><?=lang('Updated At')?></th>
                                        <th id="username"><?=lang('sys.updatedby')?></th>
                                        <th id="user_ip_address"><?=lang('sys.ip_address')?></th>
                                        <th id="demo_link"><?=lang('Demo supported')?></th>
                                    </tr>
                                </thead>
                                <tbody>
    
                                </tbody>
                            </table>
                        </div>
					</div>
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-default" id="cancel-delete"data-dismiss="modal"><?=lang('Close')?></button>
			</div>
		</div>
	</div>
</div>

<form id="_export_excel_queue_form" class="hidden" method="POST" target="_blank">
<input name='json_search' type="hidden">
</form>


<script type="text/javascript">
$("#collapseSubmenuGameDescription").addClass("in");
$("a#view_game_description").addClass("active");
$("a#viewGameDescription").addClass("active");
// $("a#viewGameListSettings").addClass("active");

$(document).ready(function(){

	$('body').tooltip({
		selector: '[data-toggle="tooltip"]',
		placement: "bottom"
	});

    $('#resetFields').click(function(){
        $('.select2-selection__choice').remove();
        $('.clearField').attr('value', '');
        $('.clearField option:selected').removeAttr('selected');
    });

    var column_to_order = "<?= (!$this->utils->isEnabledFeature('close_cashback')) ? 26 : 25 ?>";
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
				postfixButtons: [ 'colvisRestore' ],
            	className: '<?=$this->utils->getConfig('use_new_sbe_color') ? 'btn-linkwater' : ''?>',
			},

			{
                text: "<?php echo lang('CSV Export'); ?>",
                className:'btn btn-sm btn-portage export_excel',
                action: function ( e, dt, node, config ) {
                    var d = { 'draw':1, 'length':-1, 'start':0};


					d.extra_search = $('#form-filter').serializeArray();
					d.extra_search[d.extra_search.length] = {
							'name' : 'gameTypeIdHide',
							'value' : $('#gameTypeIdHide').val()
					}


                    // utils.safelog(d);
                    $.post(site_url('/export_data/gameDescriptionHistory'), d, function(data){
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
		],

		columnDefs: [
			{ sortable: false, targets: [ 0 ] }
		],
		order: [[column_to_order, 'desc']],

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

			$.post(base_url + "api/gameDescriptionList/true", data, function(data) {
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

   $('#btn-submit').click( function() {
     dataTable.ajax.reload();
    });

});//end document ready.

/*Set Global Variables */
var  GET_GAMES_AND_GET_TYPES_URL ='<?php echo site_url('game_description/getGamesAndGameTypes') ?>/',
REFRESH_PAGE_URL = '<?php echo site_url('game_description/viewGameDescription') ?>',
GET_GAME_DESCRIPTION_HISTORY = '<?php echo site_url('game_description/getGameDescriptionHistory') ?>'
baseUrl = "<?=base_url()?>";
var message = {
    gameType        : '<?= lang('Select Game Type'); ?>'
};
var gameTypeData = "<?=$conditions['gameType']?>",
	gamePlatformId = "<?=$conditions['gamePlatform']?>"
	gameTypeId = '<?=isset($conditions['gameType']) ?$conditions['gameType']:""?>';

var LANG ={
	EDIT_COLUMN:"<?=lang('sys.gd24');?>",
	DELETE_ITEMS:"<?=lang('sys.gd21');?>",
	ADD_GAME_DESC:"<?=lang('Add new game');?>"
};
var gameCodes = <?=($conditions['gameCode']) ?$conditions['gameCode']:0?>;
var filters = <?php echo $conditions['filters'];?>;
var show_non_active_game_api_game_list = <?=($this->utils->getConfig('show_non_active_game_api_game_list'))?true:0?>;


</script>