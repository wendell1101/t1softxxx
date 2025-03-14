<style type="text/css">
    .onoffswitch {
        position: relative;
        width: 120px;
        -webkit-user-select: none;
        -moz-user-select: none;
        -ms-user-select: none;
    }

    td .onoffswitch {
        margin: 0 auto;
    }

    .control-label {
        font-size: inherit;
    }

    .onoffswitch-checkbox {
        display: none;
    }

    .onoffswitch-label {
        display: block;
        overflow: hidden;
        cursor: pointer;
        border: 1px solid #999999;
        border-radius: 20px;
    }

    .onoffswitch-inner {
        display: block;
        width: 200%;
        margin-left: -100%;
        -moz-transition: margin 0.3s ease-in 0s;
        -webkit-transition: margin 0.3s ease-in 0s;
        -o-transition: margin 0.3s ease-in 0s;
        transition: margin 0.3s ease-in 0s;
    }

    .onoffswitch-inner:before,
    .onoffswitch-inner:after {
        display: block;
        float: left;
        width: 50%;
        height: 20px;
        padding: 0;
        line-height: 20px;
        font-size: 10px;
        color: white;
        font-family: Trebuchet, Arial, sans-serif;
        font-weight: bold;
        -moz-box-sizing: border-box;
        -webkit-box-sizing: border-box;
        box-sizing: border-box;
    }

    .onoffswitch-inner:before {
        content: "<?= lang('ON') ?>";
        padding-left: 10px;
        background-color: #43ac6a;
        color: #FFFFFF;
    }

    .onoffswitch-inner:after {
        content: "<?= lang('OFF') ?>";
        padding-right: 10px;
        background-color: #EEEEEE;
        color: #999999;
        text-align: right;
    }

    .onoffswitch-default:after {
        content: "<?= lang('DEFAULT') ?>";
        padding-right: 10px;
        background-color: #EEEEEE;
        color: #999999;
        text-align: right;
    }

    .visible .onoffswitch-inner:before {
        content: "<?= lang('SHOW') ?>";
        padding-left: 10px;
        background-color: #43ac6a;
        color: #FFFFFF;
    }

    .visible .onoffswitch-inner:after {
        content: "<?= lang('HIDE') ?>";
        padding-right: 10px;
        background-color: #EEEEEE;
        color: #999999;
        text-align: right;
    }

    .required .onoffswitch-inner:before {
        content: "<?= lang('Required') ?>";
        padding-left: 10px;
        background-color: #43ac6a;
        color: #FFFFFF;
    }

    .required .onoffswitch-inner:after {
        content: "<?= lang('Unrequired') ?>";
        padding-right: 10px;
        background-color: #EEEEEE;
        color: #999999;
        text-align: right;
    }

    .edit .onoffswitch-inner:before {
        content: "<?= lang('Enable') ?>";
        padding-left: 10px;
        background-color: #43ac6a;
        color: #FFFFFF;
    }

    .edit .onoffswitch-inner:after {
        content: "<?= lang('Disable') ?>";
        padding-right: 10px;
        background-color: #EEEEEE;
        color: #999999;
        text-align: right;
    }

    .onoffswitch-switch {
        display: block;
        width: 18px;
        margin: 6px;
        background: #FFFFFF;
        border: 1px solid #999999;
        border-radius: 20px;
        position: absolute;
        top: 0;
        bottom: 0;
        right: 90px;
        -moz-transition: all 0.3s ease-in 0s;
        -webkit-transition: all 0.3s ease-in 0s;
        -o-transition: all 0.3s ease-in 0s;
        transition: all 0.3s ease-in 0s;
    }

    .onoffswitch-checkbox:checked+.onoffswitch-label .onoffswitch-inner {
        margin-left: 0;
    }

    .onoffswitch-checkbox:checked+.onoffswitch-label .onoffswitch-switch {
        right: 0px;
    }

    .onoffswitch-checkbox:disabled+.onoffswitch-label {
        background-color: #ffffff;
        cursor: not-allowed;
    }

    .note-tooltip+.tooltip .tooltip-inner {
        max-width: 400px;
    }
    .autolock-label{
        display: flex;
        align-items: center;
    }
    .onoffswitch-box{
        margin-left: 5px;
        margin-bottom: -6px;
    }
    .player_login_failed_attempt_set_locktime{
        display: flex;
        align-items: center;
    }
    .warning_message{
        color: red;
        font-size: 12px;
    }
    .hide_warning_message{
        display: none;
    }

    .loading-spinner{
      width:30px;
      height:30px;
      border:2px solid indigo;
      border-radius:50%;
      border-top-color:#0001;
      display:inline-block;
      animation:loadingspinner .7s linear infinite;
    }
    @keyframes loadingspinner{
      0%{
        transform:rotate(0deg)
      }
      100%{
        transform:rotate(360deg)
      }
    }
    .sorting_drag {
        border: 3px #5697d1 solid !important;
    }
    .sorting_tr:hover {
        border: 3px #5697d1 solid !important;
    }
</style>
<div class="row" id="user-container">
    <form id="game_tag-form_filter" style="display: none;">
        <input type="text" id="gt_flag_show_in_site" name="flag_show_in_site" value="all">
        <input type="text" id="gt_tag_code" name="tag_code" value="">
    </form>
    <div class="col-md-6" id="toggleView">
        <div class="panel panel-primary">
            <div class="panel-heading custom-ph">
                <h3 class="panel-title custom-pt" >
                    <i class="glyphicon glyphicon-globe"></i>
                    <?=lang('Casino Navigation');?>
                </h3>
            </div>

            <div class="panel-body" id="list_panel_body">
                <form class="form-horizontal" action="/action_page.php">
                  <div class="form-group">
                    <label class="control-label col-sm-2" for="lfc"><?=lang("Landing of Casino");?></label>
                    <div class="col-sm-5">
                      <select class="form-control" id="lfc">
                        <option  value="" <?= count($game_tags) == 0 ? "selected" : "";?> class="<?= count($game_tags) == 0 ? "prev-sel" : "";?>"><?= lang("No Landing Page") ?></option>
                        <?php 
                            if(!empty($game_tags)){ 
                                foreach ($game_tags as $key => $game_tag) { ;
                        ?> 
                                    <option id="<?= $game_tag['tag'] ?>" value="<?= $game_tag['tag'] ?>" <?= $game_tag['tag'] == $landing_page ? "selected" : ""  ?> class="<?= $game_tag['tag'] == $landing_page ? "prev-sel" : ""  ?>"><?= $game_tag['tag'] ?></option>
                        <?php 
                                } 
                            }
                        ?>
                      </select>
                    </div>
                  </div>
                  <div class="form-group">
                    <label class="control-label col-sm-2" for="cn_rg"><?=lang("Recent games");?></label>
                    <div class="col-sm-10">
                      <div class="" style="">
                            <div class="" title="">
                                <div class="onoffswitch">
                                    <input type="checkbox" name="cn_rg" class="onoffswitch-checkbox" id="cn_rg" value="false" <?php  echo $recent_enabled == "true" ? "checked": "" ?>>
                                    <label class="onoffswitch-label" for="cn_rg">
                                        <span class="onoffswitch-inner"></span>
                                        <span class="onoffswitch-switch"></span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                  </div>
                  <div class="form-group">
                    <label class="control-label col-sm-2" for="cn_fg"><?=lang("Favorites games");?></label>
                    <div class="col-sm-10">
                      <div class="" style="">
                            <div class="" title="">
                                <div class="onoffswitch">
                                    <input type="checkbox" name="cn_fg" class="onoffswitch-checkbox" id="cn_fg" value="false" <?php  echo $favorite_enabled == "true" ? "checked": "" ?>>
                                    <label class="onoffswitch-label" for="cn_fg">
                                        <span class="onoffswitch-inner"></span>
                                        <span class="onoffswitch-switch"></span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                  </div>
                </form>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover dataTable" style="width:100%;" id="gameTagTable" >
                        <thead>
                            <tr>
                                <th style="width:50%;"><?=lang("Tag Name");?></th> <!-- Game Platform -->
                                <th style="width:20%;"><?=lang('Display at Navigation');?></th> <!-- Action -->
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3" id="toggleView">
        <div class="panel panel-primary">
            <div class="panel-heading custom-ph">
                <h3 class="panel-title custom-pt" >
                    <i class="icon-list"></i>
                    <?=lang('Tag Sorting');?>
                </h3>
            </div>
            <div class="panel-body" id="list_panel_body">
                <!-- <strong>NOTE: </strong> Sort Value = 0 will be on last on API. -->
                <div class="table-responsive">
                    <table class="table table-bordered table-hover" style="width:100%;" id="sortTable" >
                        <thead>
                            <tr>
                                <th style="width:50%;"><?=lang("Tag Name");?></th> <!-- Game Platform -->
                                <th style="width:20%;"><?=lang('Action');?></th> <!-- Action -->
                            </tr>
                        </thead>
                        <tbody>
                        <?php 
                            if(!empty($game_tags)){ 
                                foreach ($game_tags as $key => $game_tag) { ;
                        ?> 
                                    <tr id="sort_<?= $game_tag['tag'] ?>" data-row-tag="<?= $game_tag['tag'] ?>" class="sorting_tr">
                                        <td><?= $game_tag['tag'] ?></td>
                                        <td>
                                            <button class='up'><span class="glyphicon glyphicon-arrow-up"></span></button>
                                            <button class='down'><span class="glyphicon glyphicon-arrow-down"></span></button>
                                        </td>
                                    </tr>
                        <?php 
                                } 
                            }
                        ?>
                        </tbody>
                    </table>
                </div>
                <button type="buttin" class="btn btn-sm btn-primary" id="tagSortButton"><?=lang("Save");?></button>
            </div>
        </div>
    </div>
</div>
<div class="modal" id="modal-loading" data-backdrop="static">
  <div class="modal-dialog modal-sm">
    <div class="modal-content">
      <div class="modal-body text-center">
        <div class="loading-spinner mb-2"></div>
         <div><?=lang("loading");?></div>
      </div>
    </div>
  </div>
</div>

<?php if($this->utils->isEnabledFeature('export_excel_on_queue')){?>
<form id="_export_excel_queue_form" class="hidden" method="POST" target="_blank">
<input name='json_search' type="hidden">
</form>
<?php }?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/TableDnD/0.9.1/jquery.tablednd.js" integrity="sha256-d3rtug+Hg1GZPB7Y/yTcRixO/wlI78+2m08tosoRn7A=" crossorigin="anonymous"></script>
<script type="text/javascript">
    var baseUrl = '<?php echo base_url(); ?>';
    $(document).ready(function(){
        $("#sortTable tr:even").addClass('alt');
        $("#sortTable").tableDnD({
            onDragClass: "sorting_drag",
        });
        var dataTable = $('#gameTagTable').DataTable({
            dom: "<'panel-body' <'pull-right'B><'pull-right'f><'pull-right progress-container'>l><'dt-information-summary1 text-info pull-left' i>t<'panel-body'<'pull-right'p><'dt-information-summary2 text-info pull-left' i>>",
            autoWidth: false,
            buttons: [],
            searching: false,
            orderCellsTop: true,
            fixedHeader: true,
            processing: true,
            serverSide: true,
            ajax: function (data, callback, settings) {
                data.filters = $('#game_tag-form_filter').serializeArray();
                data.isNavigation = 1;
                // console.log(data);
                $.post(baseUrl + "game_description/getAllGameTags", data, function(data) {
                        callback(data);
                }, 'json');
            },
            initComplete: function () {
                createFilter(this.api(), "gameTagTable");
            }
        });
    });

    function createFilter(dataTable, tableId) {
        var tableFilterRowId = tableId + "-filter-row";
        var tableFilterRow = $('#' + tableFilterRowId);
        if (!tableFilterRow.length) {
            tableFilterRow = $("<tr id='" + tableFilterRowId + "'role='row'>")
        }
        tableFilterRow.empty();
     
        dataTable.columns().every(function (index) {
            var column = this;
            var xid = tableId + "-" + index + "-filter";
            if (index == 0) {
                var th = $('<th>');
                th.appendTo(tableFilterRow)
                select = $('<input id="' + xid + '" class="form-control" placeholder="Input tag name here">')
                    .appendTo(th)
                    .on('input', function () {
                        $('#gt_tag_code').val($(this).val());
                        column.draw();
                    });
            } else if (index == 1) {
                var th = $('<th>');
                th.appendTo(tableFilterRow)
                select = $('<select id="' + xid + '" class="form-control">')
                    .appendTo(th)
                    .on('change', function () {
                        $('#gt_flag_show_in_site').val($(this).val());
                        column.draw();
                    });
                select.empty();
                select.append('<option value="all">---Display at Navigation---</option></select>');
                select.append('<option value="all">------------ALL------------</option></select>');
                select.append('<option value="1">------------ON-------------</option></select>');
                select.append('<option value="0">------------OFF------------</option></select>');
            } else {
                $('<th>').appendTo(tableFilterRow);
            }
        });
        tableFilterRow.appendTo('#' + tableId + ' thead');
    }

    $(document).on('click', '#gameTagTable .onoffswitch-checkbox', function(e) {
        isChecked = $(this).is(":checked");
        alert_message = "<?=lang("Are you sure you want to enable it?");?>";
        if(isChecked == false){
            alert_message = "<?=lang("Are you sure you want to disable it?");?>";
        }
        if (confirm(alert_message) == true) {
            id = $(this).attr("data-row-id");
            name = $(this).attr("data-row-name");
            tag = $(this).attr("data-row-tag");
            isChecked = $(this).is(":checked");
            $.post(baseUrl + "cms_management/update_game_tag_show_insite", {id: id, flag: isChecked}, function(data, status) {
                if(data.success == 1 ){
                    if(isChecked == true){
                        $('#lfc').append('<option value="'+tag+'">'+tag+'</option>');
                        $('#sortTable > tbody:last').append('<tr id="sort_'+tag+'" data-row-tag="'+tag+'"><td>'+tag+'</td><td><button class="up"><span class="glyphicon glyphicon-arrow-up"></span></button> <button class="down"><span class="glyphicon glyphicon-arrow-down"></span></button></td></tr>');  
                    } else {
                        
                        selected_tag = $( "#lfc option:selected" ).val();
                        $("#lfc option[value="+tag+"]").remove();
                        $("#sort_"+tag).remove();
                        if(selected_tag == tag){
                            $.get(baseUrl + "cms_management/update_casino_navigation/casino_navigation_landing_page", function(data, status){
                                console.log("Success: " + data.success + "\nStatus: " + status);
                            }, 'json');
                        }
                    }
                }
                // console.log(data);
                if(data.success != true){
                    alert("Success: " + data.success + "\nStatus: " + status);
                }
            }, 'json');
        } else {
            e.preventDefault();
            return false;
        } 
    });


    $(document).on('click', '#cn_rg', function(e) {
        isChecked = $(this).is(":checked");
        alert_message = "<?=lang("Are you sure you want to enable it?");?>";
        if(isChecked == false){
            alert_message = "<?=lang("Are you sure you want to disable it?");?>";
        }
        if (confirm(alert_message) == true) {
            // isChecked = $(this).is(":checked");
            $.get(baseUrl + "cms_management/update_casino_navigation/casino_navigation_recent_games_enabled/" + isChecked, function(data, status){
                // console.log(data);
                if(data.success != true){
                    alert("Success: " + data.success + "\nStatus: " + status);
                }
            }, 'json');
        } else {
            e.preventDefault();
            return false;
        } 
    });

    $(document).on('click', '#cn_fg', function(e) {
        isChecked = $(this).is(":checked");
        alert_message = "<?=lang("Are you sure you want to enable it?");?>";
        if(isChecked == false){
            alert_message = "<?=lang("Are you sure you want to disable it?");?>";
        }
        if (confirm(alert_message) == true) {
            isChecked = $(this).is(":checked");
            $.get(baseUrl + "cms_management/update_casino_navigation/casino_navigation_favorite_games_enabled/" + isChecked, function(data, status){
                // console.log(data);
                if(data.success != true){
                    alert("Success: " + data.success + "\nStatus: " + status);
                }
            }, 'json');
        } else {
            e.preventDefault();
            return false;
        } 
    });

    $(document).on('change', '#lfc', function(e) {
        tag = $(this).val();
        if (confirm("<?=lang("Are you sure you want to set this as landing page?");?>") == true) {
            $.get(baseUrl + "cms_management/update_casino_navigation/casino_navigation_landing_page/" + tag, function(data, status){
                // console.log(data);
                if(data.success != true){
                    alert("Success: " + data.success + "\nStatus: " + status);
                } else {
                    $('#lfc .prev-sel').removeClass('prev-sel');
                    $('#lfc option[value="'+tag+'"]').addClass('prev-sel');
                }
            }, 'json');
        } else {
            $('#lfc .prev-sel').prop('selected', true);
        } 
    });

    $(document).on('click', '#tagSortButton', function() {
        tagsOrder = [];
        $("#sortTable tbody tr").each(function(index) {
            var position = index + 1;
            var tag = $(this).attr("data-row-tag");
            tagsOrder.push([tag, position]);
        });
        // console.log(tagsOrder);
        if (confirm("<?=lang("Are you sure you want to update sorting?");?>") == true) {
            $('#modal-loading').modal('show');
            $.post(baseUrl + "cms_management/update_game_tag_sorting", {tagsOrder: tagsOrder}, function(data, status) {
                if(data.success != true){
                    alert("Success: " + data.success + "\nStatus: " + status);
                }
                
            }, 'json')
            .done(function(){
                $('#modal-loading').modal('hide');
            });
        } 
    });

    $(document).on('click', '.up,.down', function() {
        var $element = this;
        var row = $($element).parents("tr:first");

        if($(this).is('.up')){
            row.insertBefore(row.prev());
        } else {
            row.insertAfter(row.next());
        }
    });
</script>

