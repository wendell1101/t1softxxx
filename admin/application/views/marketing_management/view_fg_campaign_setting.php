<style>
    .freespin .panel-body {
        box-shadow: none;
    }

    .freespin .button-group {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding-left: 0;
        padding-top: 20px;
    }

    .freespin .button-group .btn {
        flex-grow: 1;
        margin-right: 5px;
    }

    .freespin-camplist .search-entries {
        display: flex;
        justify-content: space-between;
        margin: 20px 0;
    }

    .freespin-camplist .search-entries .entries label {
        white-space: nowrap;
    }

    .freespin-camplist .search-entries .entries label select {
        display: inline-block;
        max-width: 70px;
    }

    .freespin-camplist .search-entries .table-filter {
        display: flex;
        align-items: center;
    }

    .freespin-camplist .search-entries .table-filter .search {
        display: flex;
        justify-content: center;
        align-items: center;
        margin-right: 10px;
    }

    .freespin-camplist .search-entries .table-filter .search label {
        margin-bottom: 0;
    }

    .freespin-camplist .search-entries .table-filter .search input {
        min-width: 200px;
        margin-left: 10px;
    }

    .freespin-camplist .page-entries {
        margin: 20px 0;
    }
    #freeGames .modal-dialog {
        max-width: 900px;
        width: 100%;
    }
    .add-game {
        margin: 10px auto;
        display: block;
    }
    form.freegames {
        -webkit-box-shadow: 0 1px 1px rgba(0,0,0,.05);
        box-shadow: 0 1px 1px rgba(0,0,0,.05);

    }
    #freeGames .table-data {
        margin-top: 30px;
    }
    #freeGames .table-data table td, #freeGames .table-data table th{
        text-align: center;
    }
    #freeGames .table-data table td input {
        height: auto;
        box-shadow: none;
    }
    #freeGames .modal-footer {
        display: flex;
        justify-content: center;
        align-items: center;
    }
    .scope-player, .allowed-players {
        margin-top: 30px;
    }
    .allowed-players p {
        margin-bottom: 5px;
    }
    .allowed-players p.note {
        font-size: 12px;
        margin-top: 5px;
    }

    .loadingoverlay {
        position: fixed;
        top: 0;
        z-index: 100;
        width: 100%;
        height: 100%;
        display: none;
        background: rgba(0,0,0,0.6);
    }

    .cv-spinner {
        height: 100%;
        display: flex;
        justify-content: center;
        align-items: center;
    }

    .spinner {
        width: 40px;
        height: 40px;
        border: 4px #ddd solid;
        border-top: 4px #2e93e6 solid;
        border-radius: 50%;
        animation: sp-anime 0.8s infinite linear;
    }

    @keyframes sp-anime {
        0% {
            transform: rotate(0deg);
        }

        100% {
            transform: rotate(359deg);
        }
    }
</style>
<div class="freespin-camplist-main">
    <div class="panel panel-primary freespin-camplist">
        <div class="panel-heading custom-ph">
            <h4 class="panel-title custom-pt pull-left">
                <i class="fa fa-gamepad"></i> <?=lang('FG Free Spin Campaign list');?></h4>
            <a href="#" data-toggle="modal" data-target="#freeGames" class="btn pull-right btn-xs btn-info btn-modal-freegames" style="padding: 9px;">
                <i class="fa fa-plus-circle"></i> </a>
            <div class="clearfix"></div>
        </div>
        <div class="panel-body">
            <div class="col-md-12">
                <div class="table-data">
                    <table class="table table-bordered table-hover" id="free_spin_campaign_table">
                        <thead>
                            <tr role="row">
                                <th class="sorting" tabindex="0"><?=lang('Action');?></th>
                                <th class="sorting" tabindex="0"><?=lang('Campaign id');?></th>
                                <th class="sorting" tabindex="0"><?=lang('Campaign name');?></th>
                                <th class="sorting" tabindex="0"><?=lang('Number of games');?></th>
                                <th class="sorting" tabindex="0"><?=lang('Status');?></th>
                                <th class="sorting" tabindex="0"><?=lang('Currency');?></th>
                                <th class="sorting" tabindex="0"><?=lang('Start date & time');?></th>
                                <th class="sorting" tabindex="0"><?=lang('End date & time');?></th>
                                <th class="sorting" tabindex="0"><?=lang('For new player');?></th>
                                <th class="sorting" tabindex="0"><?=lang('Created at');?></th>
                                <th class="sorting" tabindex="0"><?=lang('Updated at');?></th>
                            </tr>
                        </thead>
                        <tbody>          
                            <?php
                                if (!empty($campaignList)) {
                                    foreach ($campaignList as $campaign) { 
                            ?>
                                        <tr class="odd">
                                            <td>
                                                <div class="actionCampaign">
                                                    <a href="javascript:void(0)" class="edit-campaign" data-campaign="<?=$campaign['campaign_id']?>">
                                                        <span class="glyphicon glyphicon-edit editCampaignBtn" data-toggle="tooltip" title="<?=lang('lang.edit');?>">
                                                        </span>
                                                    </a>
                                                </div>
                                            </td>
                                            <td><?=$campaign['campaign_id'] == '' ? '<i class="text-muted">' . lang("lang.norecord") . '<i/>' : $campaign['campaign_id']?></td>
                                            <td><?=$campaign['name'] == '' ? '<i class="text-muted">' . lang("lang.norecord") . '<i/>' : $campaign['name']?></td>
                                            <td><?=$campaign['num_of_games'] == '' ? '<i class="text-muted">' . lang("lang.norecord") . '<i/>' : $campaign['num_of_games']?></td>
                                            <td><?=$campaign['status'] == '' ? '<i class="text-muted">' . lang("lang.norecord") . '<i/>' : $campaign['status']?></td>
                                            <td><?=$campaign['currency'] == '' ? '<i class="text-muted">' . lang("lang.norecord") . '<i/>' : $campaign['currency']?></td>
                                            <td><?=$campaign['start_time'] == '' ? '<i class="text-muted">' . lang("lang.norecord") . '<i/>' : $campaign['start_time']?></td>
                                            <td><?=$campaign['end_time'] == '' ? '<i class="text-muted">' . lang("lang.norecord") . '<i/>' : $campaign['end_time']?></td>
                                            <td><?=$campaign['is_for_new_player'] ? '<i class="text-muted">' . lang("Yes") . '<i/>' : '<i class="text-muted">' . lang("No") . '<i/>'?></td>
                                            <td><?=$campaign['created_at'] == '' ? '<i class="text-muted">' . lang("lang.norecord") . '<i/>' : $campaign['created_at']?></td>
                                            <td><?=$campaign['updated_at'] == '' ? '<i class="text-muted">' . lang("lang.norecord") . '<i/>' : $campaign['updated_at']?></td>
                                        </tr>
                            <?php
                                    }
                                }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal bootstrap-dialog type-primary fade size-normal" role="dialog" aria-hidden="true" id="freeGames" data-backdrop="static" data-keyboard="false">
        <div class="loader"></div>
        <div class="modal-dialog">
            <div class="modal-content">
                <!-- Begin overlay-->
                <div id="modalloadingoverlay" class="loadingoverlay"> 
                    <div class="cv-spinner">
                        <span class="spinner"></span>
                    </div>
                </div>
                <!-- End overlay-->
                <div class="modal-header">
                    <div class="bootstrap-dialog-header">
                        <div class="bootstrap-dialog-title"><?php echo lang("FG Campaign") ?>
                        </div>
                    </div>
                </div>
                <form id="campaign_form" action="/fg_campaign_api/create_campaign" data-edit="false">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-sm-12">
                                <div class="row">
                                    <div class="col-md-4 col-sm-12">
                                        <div class="form-group">
                                            <label class="control-label"><?php echo lang("Campaign name") ?></label>
                                            <input type="text" id="campaign" class="form-control input-sm user-success" placeholder="<?php echo lang("Type Name Here") ?>" required>
                                            <input type="hidden" id="campaign-id" class="form-control input-sm user-success">
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-sm-12">
                                        <div class="form-group">
                                            <label class="control-label"><?php echo lang("Start Date & Time") ?></label>
                                            <input id="campaign_date" class="form-control input-sm dateInput" data-time="true" data-start="#from" data-end="#to" data-future="true" data-enabledmindate="true" data-disableranges="true">
                                            <input type="hidden" id="from" name="from" value="<?=$conditions['from'];?>" />
                                            <input type="hidden" id="to" name="to" value="<?=$conditions['to'];?>" />
                                        </div>
                                    </div>
                                    <div class="col-md-2 col-sm-12">
                                        <div class="form-group">
                                            <label class="control-label"><?php echo lang("Number of Spins") ?></label>
                                            <input type="text" id="freespin" class="form-control input-sm user-success" maxlength="2" required>
                                        </div>
                                    </div>
                                    <div class="col-md-2 col-sm-12">
                                        <div class="form-group">
                                            <label class="control-label"><?php echo lang("Currency") ?></label>
                                            <select class="form-control" id="currency" value="<?php echo $currency['currency_code'] ?>" disabled>
                                                <option value="THB">THB</option>
                                                <option value="CNY">CNY</option>
                                                <option value="IDR">IDR</option>
                                                <option value="USD">USD</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-1 col-sm-12">
                                        <div class="form-group">
                                            <label class="control-label"><?php echo lang("Version") ?></label>
                                            <input type="text" id="version" class="form-control input-sm user-success" value="1" disabled>
                                        </div>
                                    </div>
                                    <div class="col-md-3 col-sm-12">
                                        <div class="form-group">
                                            <label class="control-label"><?php echo lang("Status") ?></label>
                                            <select class="form-control" id="status" value="ACTIVE">
                                                <option value="ACTIVE"><?php echo lang("ACTIVE") ?></option>
                                                <option value="SUSPENDED"><?php echo lang("SUSPENDED") ?></option>
                                                <option value="CLOSED"><?php echo lang("CLOSED") ?></option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-12">
                                <div class="table-data">
                                    <div class="text-info pull-left">
                                        <p><?php echo lang("Games") ?></p>
                                    </div>
                                    <div class="col-md-12">
	                                    <div class="form-group">
	                                    	<select class="multi-select-filter-game" id="games" name="games[]" multiple="multiple" required="" style="width:100%;">
					                        </select>
	                                    </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-12">
                                <div class="scope-player">
                                    <div class="text-info">
                                        <span><?php echo lang("Vip Groups") ?></span>
                                    </div>
                                    <div class="col-md-12">
	                                    <div class="form-group">
	                                    	<select class="multi-select-filter-vip" id="vipsettings" name="vipsettings[]" multiple="multiple" style="width:100%;">
			                                    <?php foreach ($vipsettings as $vipsetting): ?>
			                                        <option value="<?=$vipsetting['vipSettingId']?>"><?=$vipsetting['groupName']?></option>
			                                    <?php endforeach ?> 
					                        </select>
	                                    </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-12">
                                <div class="scope-player">
                                    <div class="text-info">
                                        <span><?php echo lang("Players") ?></span>
                                    </div>
                                    <div class="col-md-12">
	                                    <div class="form-group">
	                                    	<select class="multi-select-filter-player" id="players" name="players[]" multiple="multiple" style="width:100%;">
					                        </select>
	                                    </div>
                                    </div>
                                    <div class="form-group">
                                        <input type="checkbox" id="check_new_player" name="check_new_player">
                                        <label for=""><?php echo lang("Checked this if the campaign is for new players only") ?></label>
                                    </div>
                                    <!-- <div class="col-md-6 col-sm-12 clearfix">
                                        <div class="form-group">
                                            <input type="file" class="form-control input-sm">
                                            <p class="note"><span class="text-danger">Note: Only CSV file format and not exceeded 2MB. </span><a href="#" class="text-info">Download Sample</a></p>
                                        </div>
                                    </div> -->
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary btn-linkwater" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-secondary btn-scooter btn-modal-save">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<script type="text/javascript">
    $(document).ready(function(){
        $('.multi-select-filter-player').select2({
            placeholder: "<?php echo lang("Search here") ?>",
            ajax:{
                url: "<?= base_url('/marketing_management/getFGPlayerDataAjaxRemote') ?>", 
                dataType: "json",
                type: "post",
                delay: 250,
                data: function(params) {
                    return {
                        search: params.term,
                        page: params.page
                    }
                },
                processResults: function(data, params) {
                    params.page = params.page || 1;
                    return {
                        results: data.items,
                        pagination: {
                            more: data.total_count
                        }
                    };
                },
                cache: true
            },
            minimumInputLength: 3,
        });

        $('.multi-select-filter-game').select2({
            placeholder: "<?php echo lang("Search here") ?>",
            ajax:{
                url: "<?= base_url('/marketing_management/getFGGameDataAjaxRemote') ?>", 
                dataType: "json",
                type: "post",
                delay: 250,
                data: function(params) {
                    return {
                        search: params.term,
                        page: params.page
                    }
                },
                processResults: function(data, params) {
                    params.page = params.page || 1;
                    return {
                        results: data.items,
                        pagination: {
                            more: data.total_count
                        }
                    };
                },
                cache: true
            },
            minimumInputLength: 3,
        });

        $('.multi-select-filter-vip').select2({
            placeholder: "<?php echo lang("Search here") ?>",
            allowClear: true
        });

        var dataTable = $('#free_spin_campaign_table').DataTable({
            searching: true,
            autoWidth: false,

            <?php if($this->utils->isEnabledFeature('column_visibility_report')){ ?>
                stateSave: true,
            <?php } else { ?>
                stateSave: false,
            <?php } ?>
            dom: "<'panel-body' <'pull-right'B><'pull-right'f><'pull-right progress-container'>l><'dt-information-summary1 text-info pull-left' i>t<'text-center'r><'panel-body'<'pull-right'p><'dt-information-summary2 text-info pull-left' i>>",
            buttons: [
                {
                    extend: 'colvis',
                    className:'btn-linkwater',
                    postfixButtons: [ 'colvisRestore' ]
                },
                <?php if( $this->permissions->checkPermissions('game_free_spin_bonus') ){ ?>
                {
                    text: "<?php echo lang('CSV Export'); ?>",
                    className:'bt;n btn-sm btn-portage',
                    action: function ( e, dt, node, config ) {
                        var d = {};
                        var gameId = <?php echo $gamePlatformId; ?>;
                        $.post(site_url('/export_data/export_free_spin_campaign_list/'+ gameId), d, function(data){
                            if(data && data.success){
                                $('body').append('<iframe src="'+data.link+'" frameborder="0" scrolling="no" style="border:0px;width:0px;height:0px"></iframe>');
                            }else{
                                alert('export failed');
                            }
                        });
                    }
                }
                <?php } ?>
            ],
            drawCallback: function () {
                if ( dataTable.rows( { selected: true } ).indexes().length === 0 ) {
                    dataTable.buttons().disable();
                }
                else {
                    dataTable.buttons().enable();
                }
            }
        });
    });

    $(document).on("submit", "#campaign_form", function(e){
        e.preventDefault();
        var from = $("#from").val();
        var to = $("#to").val();
        var edit = $(this).data("edit");

        var minutesToAdd = 15;
        var currentDate = new Date();
        var adjustedDate = new Date(currentDate.getTime() + minutesToAdd * 60000);
        var fromDate = new Date(from);
        var toDate = new Date(to);
        if(fromDate <= adjustedDate && !edit) {
            alert("Start date should be ahead of 15 mins.");
            return false;
        }
        var hours = parseInt(Math.abs(toDate - fromDate) / (1000 * 60 * 60) % 24);
        var days = parseInt((toDate - fromDate) / (1000 * 60 * 60 * 24));
        if(days == 0 ){
            if(hours < 1){
                alert("Date time range must atleast 1 hour.");
                return false;
            }
        }

        var players = [];
        $.each($("#players").select2('data'), function(index, val) { 
            players.push(val.id);
        });
        var vipsettings = [];
        $.each($("#vipsettings").select2('data'), function(index, val) { 
            vipsettings.push(val.id);
        });
        var games = [];
        $.each($("#games").select2('data'), function(index, val) { 
            games.push(val.id);
        });

        var url = $(this).attr('action');
        var data = {
            gamePlatformId:"<?php echo $gamePlatformId; ?>",
            players:players,
            vipsettings:vipsettings,
            games:games,
            campaign:$("#campaign").val(),
            from:from,
            to:to,
            currency:$("#currency").val(),
            status:$("#status").val(),
            numOfGames:$("#freespin").val(),
            newPlayerOnly:$("#check_new_player").is(":checked"),
            version:$("#version").val(),
            campaignId:$("#campaign-id").val()
        };
        $("#modalloadingoverlay").fadeIn();
        $.ajax({
            type: "POST",
            url: url,
            dataType: 'json',
            data: JSON.stringify(data),
            success: function(data){
                if(data.success == true){
                    alert("<?php echo lang('Update success!'); ?>");
                    location.reload();
                } else {
                    var errorMsg = "<?php echo lang('Try again!'); ?>";
                    if(typeof(data.result.error.message) != "undefined" && data.result.error.message !== null) {
                        errorMsg = data.result.error.message;
                    }
                    alert(errorMsg);
                    $("#modalloadingoverlay").fadeOut();
                }
            },
            error: function(XMLHttpRequest, textStatus, errorThrown) {
                alert("Failed. Something went wrong.");
                location.reload();
            }
        });
    });

    $(document).on("click",".edit-campaign",function() {
        var campaign = $(this).data("campaign");
        var url = "/fg_campaign_api/get_campaign_details";
        var data = {
            gamePlatformId:"<?php echo $gamePlatformId; ?>",
            campaignId:campaign,
            currency:"<?php echo $currency['currency_code']; ?>"
        };
        $.ajax({
            type: "POST",
            url: url,
            dataType: 'json',
            data: JSON.stringify(data),
            success: function(data){
                if(data.success == true){
                    $("#campaign_date").daterangepicker({
                        parentEl: "#freeGames  .modal",
                        timePicker: true,
                        timePickerSeconds: true,
                        timePicker24Hour: true,
                        startDate: data.result.start_time,
                        endDate: data.result.end_time,
                        autoUpdateInput: true,
                        minDate: data.result.start_time,
                        locale: {
                              format: 'YYYY-MM-DD HH:mm:ss'
                        }
                    },function(start, end, label) {
                        $('#from').val(start.format('YYYY-MM-DD HH:mm:ss'));
                        $('#to').val(end.format('YYYY-MM-DD HH:mm:ss'));
                    });
                    $("#vipsettings").val(data.result.vip_levels).trigger('change');
                    $.each(data.result.players,function(key,value){
                        $newOption = $("<option selected='selected'></option>").val(value.id).text(value.player_username);
                        $("#players").append($newOption).trigger('change');
                    });
                    $.each(data.result.games,function(key,value){
                        $newOption = $("<option selected='selected'></option>").val(value.id).text(value.name);
                        $("#games").append($newOption).trigger('change');
                    });
                    $("#freespin").val(data.result.num_of_games);
                    $("#status").val(data.result.status);
                    $("#currency").val(data.result.currency);
                    $('#campaign').val(data.result.name);
                    $("#version").val(data.result.version);
                    $("#from").val(data.result.start_time);
                    $("#to").val(data.result.end_time);
                    if(data.result.is_for_new_player == 1){
                        $("#players").prop("disabled",true);
                        $("#check_new_player").prop("checked", true);
                    }
                    $("#campaign_form").attr("action", "/fg_campaign_api/update_campaign");
                    $('#campaign_form').attr('data-edit', 'true');
                    if(data.result.running == 1){
                        $("#games").prop("disabled",true);
                    }
                    $('#campaign-id').val(campaign);
                    $('#freeGames').modal('show');
                } else {
                    var errorMsg = "<?php echo lang('Try again!'); ?>";
                    if(typeof(data.result.error.message) != "undefined" && data.result.error.message !== null) {
                        errorMsg = data.result.error.message;
                    }
                    alert(errorMsg);
                }
            }
        });
    });

    $(document).on("click", ".btn-modal-freegames", function(e){
        var date_from = "<?= $this->utils->adjustDateTimeStr($this->utils->getDatetimeNow(), '+30 minutes') ?>";
        var date_to = "<?= date("Y-m-d") . ' 23:59:59'; ?>";
        $("#campaign_form").attr("action", "/fg_campaign_api/create_campaign");
        $("#campaign_date").daterangepicker({
            parentEl: "#freeGames  .modal",
            timePicker: true,
            timePickerSeconds: true,
            timePicker24Hour: true,
            startDate: date_from,
            endDate: date_to,
            autoUpdateInput: true,
            minDate: date_from,
            locale: {
                  format: 'YYYY-MM-DD HH:mm:ss'
            }
        },function(start, end, label) {
            $('#from').val(start.format('YYYY-MM-DD HH:mm:ss'));
            $('#to').val(end.format('YYYY-MM-DD HH:mm:ss'));
        });
        $('#from').val(date_from);
        $('#to').val(date_to);
        $('#campaign_form').attr('data-edit', 'false');
    });



    $("#check_new_player").change(function(){
        if($(this).prop("checked")){
            $("#players").prop("disabled",true);
            $("#players").val(null).trigger("change"); 
        }
        else{
            $("#players").prop("disabled",false);
        }
    });

    $('#freespin').keypress(function (e) {    
        var charCode = (e.which) ? e.which : event.keyCode    
        if (String.fromCharCode(charCode).match(/[^0-9]/g))    
            return false;                        
    });    

    $('#freeGames').on('hidden.bs.modal', function(e) {
        $("#check_new_player").prop("checked", false);
        $("#players").prop("disabled",false);
        $("#games").prop("disabled",false);
        $("#players, #vipsettings, #games, #freespin, #campaign, #campaign-id").val("");
        $("#players, #vipsettings, #games").trigger("change");  
        $("#version").val("1");
        $("#status").val("ACTIVE");
    });
</script>