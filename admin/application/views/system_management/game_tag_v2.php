
<div class="panel panel-primary hidden">
    <div class="panel-heading">
        <h4 class="panel-title">
            <i class="fa fa-search"></i> <?=lang("lang.search")?>
            <span class="pull-right">
                <a data-toggle="collapse" href="#collapseGameTag" class="btn btn-xs btn-info <?= $this->config->item('default_open_search_panel') ? '' : 'collapsed'?>"></a>
            </span>
        </h4>
    </div>

    <div id="collapseGameTag" class="panel-collapse collapse <?= $this->config->item('default_open_search_panel') ? 'in' : ''?>">
	    <!-- <input type="hidden" id="gameTypeIdHide" name="gameTypeIdHide"> -->
        <form id="form-filter" class="form-horizontal" method="get">
	        <div class="panel-body">
	            <div class="row">
                    <div class="col-md-2">
                        <label class="control-label" for="gamePlatform"><?=lang('sys.gd7')?>:</label>
                        <select name="gamePlatform" id="gamePlatform" class="form-control clearField">
                            <option value=""><?=lang('Select Game Platform')?></option>
                            <?php foreach ($game_platforms as $gameApi) { ?>
                                <option value="<?=$gameApi['id']?>"> <?=$gameApi['system_code']  . " [" . $gameApi['id'] . "]"?> </option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="control-label" for="gameType"><?=lang('sys.gd6')?>:</label>
                        <select name="gameType" id="gameType" class="form-control clearField" disabled>
                            <option value=""><?= lang('Select Game Type'); ?></option>
                        </select>
                    </div>
                    

                    <div class="col-md-2 pt-10 mt-10">
                        <label class="control-label" for="gameType">Select Game Tag:</label>
                        <select name="gameTagToApply" id="gameTagToApply" class="form-control">
                            <option value=""><?=lang('Select Game Tag');?></option>
                            <?php foreach ($game_tags as $gameTag) { ?>
                                <option value="<?=$gameTag['id']?>"> <?=$gameTag['translation']  . " [" . $gameTag['tag_code'] . "]"?> </option>
                            <?php } ?>
                        </select>
                    </div>

                    <div class="col-md-2 pt-10 mt-10">
                        <label class="control-label" for="batchTagGames"><br></label>
                        <input type="button" id="batchTagGames" class="btn btn-sm btn-danger form-control" value="Tag Selected Games">
                    </div>
                    <div class="col-md-2 pt-10 mt-10">
                    <label class="control-label" for="batchTagGamesClear"><br></label>
                        <input type="reset" name="batchTagGamesClear" class="btn btn-sm btn-linkwater form-control" value="<?=lang('lang.clear');?>">
                    </div>
                    <div class="col-md-2 pt-10 mt-10">
                        <label class="control-label" for="syncToOtherCurrency"><br></label>
                        <input type="button" id="syncToOtherCurrency" class="btn btn-success btn-sm form-control" value="<?=lang('Sync To Other Currency')?>">
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

                    
                </h3>
            </div>

			<div class="panel-body" id="list_panel_body">
				<div class="table-responsive">
				<div class="clearfix"></div>
					<table class="table table-bordered table-hover dataTable" id="my_table" >
						<thead>
							<tr>
                            <!-- action -->
                                <th style="width: 134px"><input type="checkbox" id="toggleSelectAll"> Select / Unselect</th>
                            <!-- game_name -->
                                <th><?=lang('sys.gd8');?></th>
                            <!-- english_name -->
                                <th><?=lang('lang.english.name');?></th>
                            <!-- game_code -->
                                <th><?=lang('sys.gd9');?></th>
                            <!-- attributes -->
                                <th><?=lang('Game Attributes');?></th>
                            <!-- game_order -->
                                <th><?=lang('sys.gd20');?></th>
                            <!-- tag_game_order -->
                                <th><?=lang('Tag Game Order');?></th>
                            <!-- tags -->
                                <th><?=lang('Game Tags');?></th>
							</tr>
						</thead>
						<tbody id="table_body">
						</tbody>
					</table>
				</div>
			</div>

			<div class="panel-footer"></div>

		</div>
	</div>
<form id='form_sync_other' style="display:none" action="<?=site_url('game_tag/remote_sync_game_tag_from_one_to_other_mdb'); ?>" method="POST">
    <label class="control-label" for="game_ids"><br></label>
        <input type="hidden" name="game_ids" id="game_ids" class="btn btn-sm btn-linkwater form-control">
    </div>
</form>
<script type="text/javascript">
	var baseUrl = '<?php echo base_url(); ?>';
	$(document).ready(function(){
		$('#view_game_tag').addClass('active');
		$("#collapseSubmenuGameDescription").addClass("in");
    	$("a#view_game_description").addClass("active");

        $('#gamePlatform').change( function() {
            var selectedValue = $(this).val();
            var url = baseUrl + "game_type/getGameTypeByPlatformId/" + selectedValue;

            fetch(url)
                .then(function(response) {
                    if (response.ok) {
                        return response.json();
                    }else{
                        throw Error(response.statusText);
                    }
                })
                .then(function(data){
                    var gameType = document.getElementById("gameType");
                    gameType.innerHTML = "";
                    var option = document.createElement("option");
                    option.text = "<?= lang('Select Game Type'); ?>";
                    option.value = "";
                    gameType.add(option);
                    for (var i = 0; i < data.length; i++) {
                        var option = document.createElement("option");
                        option.text = data[i].game_type;
                        option.value = data[i].id;
                        gameType.add(option);
                    }
                    gameType.disabled = false;
                })
	        });

        $('#gameType').change( function(e) {
            e.preventDefault();
            updateGameDescription();
        });

        $('#toggleSelectAll').click( function(e) {
            const isChecked = $(this).prop('checked');
            const checkboxes = $(this).closest('table').find('tbody input[type="checkbox"]');

            checkboxes.prop('checked', isChecked);
        });

        $('#syncToOtherCurrency').click( function(e) {
            const checkedCheckboxes = $('#table_body input[type="checkbox"]:checked');
            const checkedValues = checkedCheckboxes.map(function() {
                return $(this).val();
            }).get();

            $("#game_ids").val(checkedValues);
            console.log("check-data", checkedValues);
            //check if there are checked checkboxes
            if(checkedCheckboxes.length == 0){
                alert("Please select at least one game to sync tag");
            } else {
                $('form#form_sync_other').submit();
            }
        });

        $('#batchTagGames').click( function(e) {
            var gameTagToApply = $('#gameTagToApply').val();
            const checkedCheckboxes = $('#table_body input[type="checkbox"]:checked');
            const checkedValues = checkedCheckboxes.map(function() {
                return $(this).val();
            }).get();

            //check if gameTagToApply is empty
            if(gameTagToApply == ""){
                alert("Please select a game tag to apply");
            }

            //check if there are checked checkboxes
            if(checkedCheckboxes.length == 0){
                alert("Please select at least one game to tag");
            }
            console.log(checkedValues);

            var url = baseUrl + "game_tag/batchTagGames/" + gameTagToApply;
            var postData = JSON.stringify(checkedValues);

            $.post(url, { data: postData }, function(data) {
                if (data.status == "success") {
                    updateGameDescription();
                } else {
                    alert('Tagging failed');
                    location.reload();
                }
            }, 'json');
        });
        });

        function updateGameDescription() {
            var gamePlatform = $('#gamePlatform').val();
            var gameType = $('#gameType').val();
            var url = baseUrl + "game_tag/searchGamesByGameTypeId/" + gameType;

            fetch(url)
                .then(function(response) {
                    if (response.ok) {
                        return response.json();
                    }else{
                        throw Error(response.statusText);
                    }
                })
                .then(function(data){
                    $('#table_body').empty();
                    data.forEach(function(game){
                        var row = '<tr>' +
                            '<td><input type="checkbox" value="' + game.id + '" /></td>' +
                            '<td>' + game.game_name + '</td>' +
                            '<td>' + game.english_name + '</td>' +
                            '<td>' + game.game_code + '</td>' +
                            '<td>' + (game.attributes !== null ? game.attributes : '') + '</td>' +
                            '<td>' + game.game_order + '</td>' +
                            '<td>' + game.tag_game_order + '</td>' +
                            '<td>' + game.tags + '</td>' +
                        '</tr>';

                    $('#table_body').append(row);
                    $('#toggleSelectAll').prop('checked', false);
                })
            });
        }

</script>