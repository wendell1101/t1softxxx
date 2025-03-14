<style type="text/css">
#timezone, input, select{
    font-weight: bold;
}
#timezone{
    height: 36px;
}

.select2-container--default .select2-selection--single{
    padding-top: 2px;
    height: 35px;
    font-size: 1.2em;
    position: relative;
    border-radius: 0;
    font-size:12px;;
}
.removeLink {
    text-decoration: none;
    color : #222222;
}
.removeLink:hover {
    text-decoration:none;
    color : #222222;
    cursor:text;
}
</style>
<div class="panel panel-primary hidden">

    <div class="panel-heading">
        <h4 class="panel-title">
            <i class="fa fa-search"></i> <?=lang("lang.search")?>
            <span class="pull-right">
                <a data-toggle="collapse" href="#collapseGamesReport" class="btn btn-xs <?=$this->utils->getConfig('use_new_sbe_color') ? 'btn-primary' : 'btn-info'?> <?=$this->config->item('default_open_search_panel') ? '' : 'collapsed'?>"></a>
            </span>
            <?php include __DIR__ . "/../../includes/report_tools.php"?>
        </h4>
    </div>

    <div id="collapseGamesReport" class="panel-collapse <?=$this->config->item('default_open_search_panel') ? '' : 'collapse in'?>">
        <div class="panel-body">
            <form id="form-filter" class="form-horizontal" method="GET" onsubmit="return validateForm();">
                <div class="row">

                    <div class="col-md-4 col-lg-4">
                       <label class="control-label" for="group_by"><?=lang('Date')?> </label>
                        <input class="form-control dateInput normal-font-weight " id="datetime_range" data-start="#datetime_from" data-end="#datetime_to" data-time="true"/>
                        <input type="hidden" id="datetime_from" name="datetime_from" value="<?=$conditions['datetime_from'];?>"/>
                        <input type="hidden" id="datetime_to" name="datetime_to" value="<?=$conditions['datetime_to'];?>"/>
                     </div>
                    <div class="col-md-3 col-lg-3" style="display: none">
                        <label class="control-label" for="group_by"><?=lang('Timezone')?></label>
                        <!-- <input type="number" id="timezone" name="timezone" class="form-control input-sm " value="<?=$conditions['timezone'];?>" min="-12" max="12"/> -->
                        <?php
                        $default_timezone = $this->utils->getTimezoneOffset(new DateTime());
                        $timezone_offsets = $this->utils->getConfig('timezone_offsets');
                        $timezone_location = $this->utils->getConfig('current_php_timezone');
                        ?>
                        <select id="timezone" name="timezone"  class="form-control input-sm">
                        <?php for($i = 12;  $i >= -12; $i--): ?>
                        <?php if($conditions['timezone'] || $conditions['timezone'] == '0' ): ?>
                        <option value="<?php echo $i > 0 ? "+{$i}" : $i ;?>" <?php echo ($i == $conditions['timezone']) ? 'selected' : ''?>> <?php echo $i > 0 ? "+{$i}" : $i ;?>:00</option>
                        <?php else: ?>
                        <option value="<?php echo $i > 0 ? "+{$i}" : $i ;?>" <?php echo ($i==$default_timezone) ? 'selected' : ''?>> <?php echo $i >= 0 ? "+{$i}" : $i ;?></option>
                        <?php endif;?>
                        <?php endfor;?>
                        </select>
                        <i class="text-info" style="font-size:10px;font-weight: bold;"><?php echo lang('System Timezone') ?>: (GMT <?php echo ( $default_timezone >= 0) ? '+'. $default_timezone  : $default_timezone; ?>) <?php echo $timezone_location ;?></i>
                        </div>
                    <div class="col-md-2">
                        <label class="control-label" for="username"><?=lang('Player Username')?> </label>
                        <input type="text" name="username" id="username" class="form-control input-sm normal-font-weight"
                            value='<?php echo $conditions["username"]; ?>'/>
                    </div>
                </div>


                <div class="row">
                    <div class="col-md-1" style="text-align:center;padding-top:24px;">
                        <input type="submit" value="<?=lang('lang.search')?>" id="search_main" class="btn col-md-12 btn-sm <?=$this->utils->getConfig('use_new_sbe_color') ? 'btn-portage' : 'btn-info'?>">
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="panel panel-primary" >
    <div class="panel-heading">
        <h4 class="panel-title"><i class="icon-dice"></i><?=lang('Tournament Winners')?> </h4>
    </div>
    <div class="panel-body" >
        <table class="table table-bordered table-hover " id="my_table">
            <thead>
                <tr>
                    <th><?=lang('Game Platform')?></th>
                    <th><?=lang('Tournament Name')?></th>
                    <th><?=lang('Tournament Id')?></th>
                    <th><?=lang('Player Username')?></th>
                    <th><?=lang('Position')?></th>
                    <th><?=lang('Score')?></th>
                    <th><?=lang('Price Amount')?></th>
                    <th><?=lang('Currency')?></th>
                    <th><?=lang('End at')?></th>
                    <th><?=lang('Start at')?></th>`
                </tr>
            </thead>
        </table>
    </div>
    <div class="panel-footer"></div>
</div>

<?php if($this->utils->isEnabledFeature('export_excel_on_queue')){?>
    <form id="_export_excel_queue_form" class="hidden" method="POST" target="_blank">
        <input name='json_search' type="hidden">
    </form>
    <form id="_export_csv_form" class="hidden" method="POST" target="_blank">
        <input name='json_search' id = "json_csv_search" type="hidden">
    </form>
<?php }?>

<script type="text/javascript">
    var baseUrl = '<?php echo base_url(); ?>';
    $(document).ready(function(){
        $('#view_game_tags').addClass('active');
        $("#collapseSubmenuGameDescription").addClass("in");
        $("a#view_game_description").addClass("active");
        // Initialize DataTable jQuery plugin on the main table
        var dataTable = $('#my_table').DataTable({
            dom: "<'panel-body' <'pull-right'B><'pull-right'f><'pull-right progress-container'>l><'dt-information-summary1 text-info pull-left' i>t<'panel-body'<'pull-right'p><'dt-information-summary2 text-info pull-left' i>>",
            autoWidth: false,

            <?php if ($this->utils->isEnabledFeature('column_visibility_report')){ ?>
                stateSave: true,
            <?php } else { ?>
                stateSave: false,
            <?php } ?>

            buttons: [
                { extend: 'colvis', postfixButtons: [ 'colvisRestore' ], className: '<?=$this->utils->getConfig('use_new_sbe_color') ? 'btn-linkwater' : ''?>'},
                <?php

                    if( $this->permissions->checkPermissions('game_report') ){

                ?>
                        {

                            text: "<?php echo lang('CSV Export'); ?>",
                            className:'btn btn-sm <?=$this->utils->getConfig('use_new_sbe_color') ? 'btn-portage' : 'btn-primary'?>',
                            action: function ( e, dt, node, config ) {
                                var d = {'extra_search':$('#form-filter').serializeArray(), 'draw':1, 'length':-1, 'start':0};
                                // utils.safelog(d);

                                $.post(site_url('/export_data/tournamentWinnerReports'), d, function(data){
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
            "order": [[ 9, 'desc' ]],
            processing: true,
            serverSide: true,
            ajax: function (data, callback, settings) {
                data.extra_search = $('#form-filter').serializeArray();
                $.post(base_url + "api/tournamentWinnerReports", data, function(data) {
                        callback(data);
                        if ( dataTable.rows( { selected: true } ).indexes().length === 0 ) {
                            dataTable.buttons().disable();
                        }
                        else {
                            dataTable.buttons().enable();
                        }
                }, 'json');
            },
        });
    });
    </script>