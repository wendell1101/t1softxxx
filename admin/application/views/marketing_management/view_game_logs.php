<!--
<?php
echo json_encode($conditions,JSON_PRETTY_PRINT);
?>
-->

<style type="text/css">
	#collapseViewGameLogs .col-md-2,
	#collapseViewGameLogs .col-md-4,
	#collapseViewGameLogs .col-md-6{
		height: 70px;
	}

	#collapseViewGameLogs .col-md-2 .non-aff{
		margin-top: 33px;
	}

	.bet, .cashout{
		color: red;
	}
	.betType {
		color: blue;
	}
	.League {
		color: DodgerBlue;
	}

	#collapseViewGameLogs .col-md-4.gtree_gp {
		height: auto;
	}

	.gp_title_frame {
		position: relative;
		top: -1pt;
	}

	.gp_title {
		font-size: 11pt;
	}

	.ga_gtype {
		line-height: 16pt;
	}

	.gtype_cb {
		position: relative;
		top: 2pt;
	}

	.gtype_text {
		margin: auto 0.5em;
	}

	.btn.gp_expand, .btn.gp_collapse {
		padding: 3px 7px;
		font-size: 10px;
	}

	.scroll-head-cloned > th {
		line-height: 0 !important;
		overflow: hidden;
		padding-top: 0 !important;
		padding-bottom: 0 !important;
	}
</style>

<form class="form-horizontal" id="search-form" method="get" role="form">
	<div class="panel panel-primary hidden">

		<div class="panel-heading">
			<h4 class="panel-title">
				<i class="fa fa-search"></i> <?=lang("lang.search")?>
				<span class="pull-right">
                <a data-toggle="collapse" href="#collapseViewGameLogs" class="btn btn-xs <?=$this->utils->getConfig('use_new_sbe_color') ? 'btn-primary' : 'btn-info'?> <?=$this->config->item('default_open_search_panel') ? '' : 'collapsed'?>"></a>
            </span><!--
            <span class="pull-right m-r-10">
                <label class="checkbox-inline">
					<input type="checkbox" id="gametype" value="gametype" onclick="checkSearchGameLogs(this.value);"/> <?php echo lang('Game Type');?>
				</label>
            </span> -->

				<?php include __DIR__ . "/../includes/report_tools.php" ?>

			</h4>
		</div>

		<div id="collapseViewGameLogs" class="panel-collapse <?=$this->config->item('default_open_search_panel') ? '' : 'collapse in'?>">
			<div class="panel-body">
				<input type="hidden" id="game_description_id" name="game_description_id" value="<?php echo $conditions['game_description_id']; ?>" />
				<div class="col-md-4">
					<label class="control-label" for="search_game_date">
						<input type="radio" name="by_date_type" value="2" <?= $date_types['settled'] == $conditions['by_date_type'] ? 'checked' : ''?>/> <?php echo lang('game_logs_settled_date');?>
						<input type="radio" name="by_date_type" value="3" <?= $date_types['bet'] == $conditions['by_date_type'] ? 'checked' : ''?>/> <?php echo lang('game_logs_bet_date');?>
						<input type="radio" name="by_date_type" value="4" <?= $date_types['updated'] == $conditions['by_date_type'] ? 'checked' : ''?>/> <?php echo lang('game_logs_updated_date');?>
					</label>
					<input id="search_game_date" class="form-control input-sm dateInput" data-start="#by_date_from" data-end="#by_date_to" data-time="true"
					<?php if ($this->utils->getConfig('game_logs_report_date_range_restriction')): ?>
						data-restrict-max-range="<?=$this->utils->getConfig('game_logs_report_date_range_restriction')?>" data-restrict-range-label="<?=sprintf(lang("restrict_date_range_label"),$this->utils->getConfig('game_logs_report_date_range_restriction'))?>" data-restrict-max-range-second-condition="<?=$this->utils->getConfig('game_logs_report_with_username_date_range_restriction')?>" data-override-on-apply="true"
					<?php endif ?>
					 autocomplete="off" />
					<input type="hidden" id="by_date_from" name="by_date_from" value="<?php echo $conditions['by_date_from']; ?>" />
					<input type="hidden" id="by_date_to" name="by_date_to"  value="<?php echo $conditions['by_date_to']; ?>"/>
				</div>
                <div class="col-md-2">
                    <label class="control-label" for="group_by"><?=lang('Timezone')?></label>
                    <?php
                        $default_timezone = $this->utils->getTimezoneOffset(new DateTime());
                        $timezone_offsets = $this->utils->getConfig('timezone_offsets');
                        $timezone_location = $this->utils->getConfig('current_php_timezone');
                        $force_default_timezone = $this->utils->getConfig('force_default_timezone_option');
                    ?>

                    <select id="timezone" name="timezone"  class="form-control input-sm">
                    <?php if(!$force_default_timezone): ?>
                     <?php for($i = 12;  $i >= -12; $i--): ?>
                         <?php if($conditions['timezone'] || $conditions['timezone'] == '0' ): ?>
                             <option value="<?php echo $i > 0 ? "+{$i}" : $i ;?>" <?php echo ($i == $conditions['timezone']) ? 'selected' : ''?>> <?php echo $i > 0 ? "+{$i}" : $i ;?>:00</option>
                         <?php else: ?>
                            <option value="<?php echo $i > 0 ? "+{$i}" : $i ;?>" <?php echo ($i == $default_timezone) ? 'selected' : ''?>> <?php echo $i > 0 ? "+{$i}" : $i ;?>:00</option>
                        <?php endif;?>
                    <?php endfor;?>
                    <?php else: ?>
						<option value="<?=$force_default_timezone;?>" selected> <?= $force_default_timezone;?></option>
                    <?php endif;?>
                	</select>
                    <i class="text-info" style="font-size:10px;"><?php echo lang('System Timezone') ?>: (GMT <?php echo ( $default_timezone >= 0) ? '+'. $default_timezone  : $default_timezone; ?>) <?php echo $timezone_location ;?></i>
                </div>

				<div class="col-md-6">

					<label class="control-label" for="flag"><?=lang('player.groupLevel');?></label>
					<select class="form-control input-sm" name="by_group_level" id="by_group_level">
						<option value=""><?=lang('lang.selectall');?></option>
						<?php
						foreach ($player_levels as $pl) {
							echo "<option value='" . $pl['vipsettingcashbackruleId'] . "' ".($pl['vipsettingcashbackruleId']==$conditions['by_group_level'] ? 'selected="selected"' : '')." >" . lang($pl['groupName']) . " - " . lang($pl['vipLevelName']) . "</option>";
						}
						?>
					</select>

				</div>

				<div class="col-md-4 col-lg-4">
					<label class="control-label" for="by_username_match_mode">
						<input type="radio" name="by_username_match_mode" value="1" <?= $conditions['by_username_match_mode'] == '1' ? 'checked' : ''?>/> <?php echo lang('Similar');?>
						<input type="radio" name="by_username_match_mode" value="2" <?= $conditions['by_username_match_mode'] == '2' ? 'checked' : ''?>/> <?php echo lang('Exact');?>
					</label>
					<label class="control-label" for="by_username"><?=lang('Player Username');?></label>
					<input type="text" name="by_username" id="by_username" class="form-control input-sm disable-esp-char" value="<?php echo $conditions['by_username']; ?>" />
				</div>

				<div class="col-md-4 col-lg-2">
					<label class="control-label" for="by_affiliate"><?=lang('Affiliate Username');?> </label>
					<input type="text" name="by_affiliate" id="by_affiliate" class="form-control input-sm" value="<?php echo $conditions['by_affiliate']; ?>" />
				</div>

				<div class="col-md-2">
					<label class="control-label" for="by_game_code"><?=lang('sys.gd9');?> </label>
					<input type="text" name="by_game_code" id="by_game_code" class="form-control input-sm" value="<?php echo $conditions['by_game_code']; ?>" />
				</div>

				<div class="col-md-2">
					<label class="control-label" for="by_game_platform_id"><?=lang('player.ui29');?> </label>
					<select class="form-control input-sm" name="by_game_platform_id" id="by_game_platform_id">
						<option value="" ><?=lang('lang.selectall');?></option>
						<?php foreach ($game_platforms as $game_platform) {?>
							<option value="<?=$game_platform['id']?>" <?php echo $conditions['by_game_platform_id']==$game_platform['id'] ? 'selected="selected"' : '' ; ?>><?=$game_platform['system_code'];?></option>
						<?php }?>
					</select>
				</div>


				<div class="col-md-4 col-lg-2">
					<label class="control-label" for="round_no"><?=lang('Round No');?> </label>
					<input type="text" name="round_no" id="round_no" class="form-control input-sm" value="<?php echo $conditions['round_no']; ?>" />
				</div>

				<div class="col-md-4 col-lg-2">
					<label class="control-label" for="flag"><?=lang('player.ut10');?> </label>
					<select class="form-control input-sm" name="by_game_flag" id="by_game_flag">
						<option value=""><?=lang('lang.selectall');?></option>
						<option value="<?=Game_logs::FLAG_GAME?>" <?php echo ($conditions['by_game_flag']==Game_logs::FLAG_GAME ? 'selected="selected"' : '' ); ?> ><?=lang('sys.gd5');?></option>
						<option value="<?=Game_logs::FLAG_TRANSACTION?>" <?php echo $conditions['by_game_flag']==Game_logs::FLAG_TRANSACTION ? 'selected="selected"' : '' ; ?> ><?=lang('pay.transact');?></option>
					</select>
				</div>

				<div class="col-md-4 col-lg-2">
					<label class="control-label" for="flag"><?=lang('Type');?> </label>
					<select class="form-control input-sm" name="by_bet_type" id="by_bet_type">
						<option value="<?=Game_logs::IS_GAMELOGS?>" <?php echo ($conditions['by_bet_type']==Game_logs::IS_GAMELOGS ? 'selected="selected"' : '' ); ?> ><?=lang('Settled');?></option>
						<option value="<?=Game_logs::IS_GAMELOGS_UNSETTLE?>" <?php echo $conditions['by_bet_type']==Game_logs::IS_GAMELOGS_UNSETTLE ? 'selected="selected"' : '' ; ?> ><?=lang('cms.others');?></option>
					</select>
				</div>

				<div class="col-md-4 col-lg-2">
					<label class="control-label" for="by_amount_from"><?=lang('mark.resultAmount')?> &gt;=</label>
					<input id="by_amount_from" type="number" name="by_amount_from" class="form-control input-sm" value="<?php echo $conditions['by_amount_from'] ?>"/>
				</div>

				<div class="col-md-4 col-lg-2">
					<label class="control-label" for="by_amount_to"><?=lang('mark.resultAmount')?> &lt;=</label>
					<input id="by_amount_to" type="number" name="by_amount_to" class="form-control input-sm" value="<?php echo $conditions['by_amount_to'] ?>"/>
				</div>

				<div class="col-md-2">
					<label class="control-label" for="by_bet_amount_from"><?php echo lang('Bet Amount'); ?> &gt;=</label>
					<input id="by_bet_amount_from" type="number" name="by_bet_amount_from" class="form-control input-sm" value="<?php echo $conditions['by_bet_amount_from'] ?>"/>
				</div>

				<div class="col-md-2">
					<label class="control-label" for="by_bet_amount_to"><?php echo lang('Bet Amount'); ?> &lt;=</label>
					<input id="by_bet_amount_to" type="number" name="by_bet_amount_to" class="form-control input-sm" value="<?php echo $conditions['by_bet_amount_to'] ?>"/>
				</div>

				<?php if ($this->utils->isEnabledFeature('show_agent_name_on_game_logs')) : ?>
					<div class="col-md-2">
						<label class="control-label" for="agency_username"><?php echo lang('Agent Username'); ?> </label>
						<input id="agency_username" type="text" name="agency_username" class="form-control input-sm" value="<?php echo $conditions['agency_username'] ?>"/>
					</div>
				<?php endif; ?>

				<div class="col-md-3">
                    <div class="checkbox">
                        <label>
                            <input id="check-free-spin" type="checkbox" name="by_free_spin" value="true" <?=$conditions['by_free_spin'] ? 'checked="checked"' : '' ; ?>/>
                            <?=lang('Only Free Bet'); ?>
                        </label>
                    </div>
                    <div class="checkbox">
                        <label>
                            <input type="checkbox" name="by_no_affiliate" value="true" <?=$conditions['by_no_affiliate'] ? 'checked="checked"' : '' ; ?>/>
                            <?=lang('Only non-affiliate'); ?>
                        </label>
                    </div>
				</div>

				<?php if (!$this->utils->getConfig('game_logs_use_alt_game_type_tree_layout')) : ?>
				<div id="gametype_search" class="col-md-12 show" style="display: none;">
					<fieldset style="padding-bottom: 8px">
						<legend>
							<label class="control-label"><?=lang('sys.gd6');?></label>
						</legend>
						<div class="row">
							<div class="col-md-3">
								<div class="checkbox">
									<label>
										<!-- <input type="hidden" id="game_type_tree" name="game_type_id" value="<?php echo $conditions['game_type_id']; ?>"> -->
										<div id="gameTree" class="col-md-12">
										</div>
									</label>
								</div>
							</div>
						</div>
					</fieldset>
				</div>
				<?php endif; ?>
				<input type="hidden" id="game_type_tree" name="game_type_id" value="<?php echo $conditions['game_type_id']; ?>">
				<?php if ($this->utils->getConfig('game_logs_use_alt_game_type_tree_layout')) : ?>
				<!-- alt game type tree, game tree frame -->
				<div id="gtree_alt_frame_border" class="col-md-12 show">
					<fieldset style="padding-bottom: 8px">
						<legend>
							<label class="control-label"><?= lang('Show Multiselect Game Filter') ?></label>
							<button type="button" class="btn btn-info btn-xs gtree_collapse">
								<i class="fa fa-minus-circle"></i> <?= lang('Collapse All') ?>
							</button>
							<button type="button" class="btn btn-info btn-xs gtree_expand" style="display: none;">
								<i class="fa fa-plus-circle"></i> <?= lang('Expand All') ?>
							</button>
						</legend>
						<div class="row" id="gtree_alt_frame">
						</div>
					</fieldset>
				</div>
				<?php endif; ?>
				<!-- alt game type tree, game platform template -->
				<div class="col-md-4 gtree_gp" id="gtree_alt_gp_tmpl" style="display: none;">
					<fieldset style="padding-bottom: 8px">
						<legend class="gp_title_frame">
							<label class="control-label gp_title"></label>
							<button type="button" class="btn btn-primary btn-xs gp_collapse">
								<i class="fa fa-minus-circle"></i> <?= lang('Collapse All') ?>
							</button>
							<button type="button" class="btn btn-primary btn-xs gp_expand" style="display: none;">
								<i class="fa fa-plus-circle"></i> <?= lang('Expand All') ?>
							</button>
						</legend>
						<div class="gp_contents">
						</div>
					</fieldset>
				</div>
				<!-- alt game type tree, game type template -->
				<div class="ga_gtype" id="gtree_alt_gtype_tmpl" style="display: none;">
					<label>
						<input class="gtype_cb" type="checkbox" />
						<span class="gtype_text"></span>
					</label>
				</div>
			</div>
			<div class="panel-footer text-right">
				<input type="submit" class="btn btn-sm  <?=$this->utils->getConfig('use_new_sbe_color') ? 'btn-portage' : 'btn-primary'?>" id="btn-submit" value="<?php echo lang('Search'); ?>" >
			</div>
		</div>
	</div>

</form>

<div class="panel panel-primary">
	<div class="panel-heading">
		<h4 class="panel-title"><i class="fa fa-list"></i> <?=lang('player.ui48');?> </h4>
	</div>

	<div class="panel-body" >
		<small class="text-muted"><?php echo lang('Note') . ': ' . lang('Green: transfer, Red: free bet'); ?></small>
		<div class="table-responsive">
			<table id="myTable" class="table table-bordered">
				<thead>
				<tr>
					<?php include __DIR__.'/../includes/cols_for_game_logs.php'; ?>
				</tr>
				</thead>
				<tbody>
				</tbody>
				<tfoot>
				<?php if ($this->utils->isEnabledFeature('show_sub_total_for_game_logs_report')) {?>
				<?php include __DIR__.'/../includes/footer_sub_for_game_logs.php'; ?>
				<?php }?>
				<?php include __DIR__.'/../includes/footer_for_game_logs.php'; ?>
				</tfoot>
			</table>
		</div>
	</div>
	<div class="panel-footer"></div>
</div>
<?php if($this->utils->isEnabledFeature('export_excel_on_queue')){?>
<form id="_export_excel_queue_form" class="hidden" method="POST" target="_blank">
<input name='json_search' type="hidden">
</form>
<?php }?>
<script type="text/javascript">
	var hiddenColumns = [];
	var rightTextColumns = [];
	var export_type="<?php echo $this->utils->isEnabledFeature('export_excel_on_queue') ? 'queue' : 'direct';?>";
	var gameTypeId = '<?php echo $conditions['game_type_id']; ?>';
	gameTypeId = gameTypeId.split(',');
	// console.log(gameTypeId);

	function addCommas(nStr){
        nStr += '';
        var x = nStr.split('.');
        var x1 = x[0];
        var x2 = x.length > 1 ? '.' + x[1] : '';
        var rgx = /(\d+)(\d{3})/;
        while (rgx.test(x1)) {
            x1 = x1.replace(rgx, '$1' + ',' + '$2');
        }
        return x1 + x2;
    }

    function checkDateRangeRestrictionWithUsername(){
    	var dateInput = $('#search_game_date.dateInput');
		var isRange = (dateInput.data('start') && dateInput.data('end'));

        var $restricted_range = dateInput.data('restrict-max-range');
        var $second_restricted_range = dateInput.data('restrict-max-range-second-condition');

    	if($.trim($second_restricted_range) != '' && $.isNumeric($second_restricted_range) && isRange){
    		if($.trim($('#by_username').val()) != ''){
    			dateInput.data('restrict-max-range',$second_restricted_range);
    			dateInput.data('restrict-range-label','<?=sprintf(lang("restrict_date_range_label"),$this->utils->getConfig('game_logs_report_with_username_date_range_restriction'))?>');
    			$restricted_range = $second_restricted_range;
        	}
        	else{
        		dateInput.data('restrict-max-range','<?=$this->utils->getConfig('game_logs_report_date_range_restriction')?>');
    			dateInput.data('restrict-range-label','<?=sprintf(lang("restrict_date_range_label"),$this->utils->getConfig('game_logs_report_date_range_restriction'))?>');
        		$restricted_range = dateInput.data('restrict-max-range');
        	}
    	}

    	return $restricted_range;
    }

	$(document).ready(function(){

		<?php if ($this->utils->getConfig('game_logs_report_date_range_restriction')): ?>

		var dateInput = $('#search_game_date.dateInput');
		var isRange = (dateInput.data('start') && dateInput.data('end'));
        var isTime = dateInput.data('time');

        dateInput.keypress(function(e){
            e.preventDefault();
            return false;
        });

        $('.daterangepicker .cancelBtn ').text('Reset');//.css('display','none');

        // -- Use reset to current day upon cancel/reset in daterange instead of emptying the value
        dateInput.on('cancel.daterangepicker', function(ev, picker) {
            // -- if start date was empty, add a default one
            if($.trim($(dateInput.data('start')).val()) == ''){
                var startEl = $(dateInput.data('start'));
                    start = startEl.val();
                    start = start ? moment(start, 'YYYY-MM-DD HH:mm:ss') : moment().startOf('day');
                    startEl.val(isTime ? start.format('YYYY-MM-DD HH:mm:ss') : start.startOf('day').format('YYYY-MM-DD HH:mm:ss'));

                dateInput.data('daterangepicker').setStartDate(start);
            }

            // -- if end date was empty, add a default one
            if($.trim($(dateInput.data('end')).val()) == ''){
                var endEl = $(dateInput.data('end'));
                    end = endEl.val();
                    end = end ? moment(end, 'YYYY-MM-DD HH:mm:ss') : moment().endOf('day');
                    endEl.val(isTime ? end.format('YYYY-MM-DD HH:mm:ss') : end.endOf('day').format('YYYY-MM-DD HH:mm:ss'));

                dateInput.data('daterangepicker').setEndDate(end);
            }

            dateInput.val($(dateInput.data('start')).val() + ' to ' + $(dateInput.data('end')).val());
        });

        dateInput.on('apply.daterangepicker, hide.daterangepicker', function(ev, picker) {

        	var $restricted_range = checkDateRangeRestrictionWithUsername();

        	if ($restricted_range == '' && !$.isNumeric($restricted_range) && !isRange)
                return false;

            var a_day = 86400000; // -- one day
            var restriction = a_day * $restricted_range;
            var start_date = new Date(picker.startDate._d);
            var end_date = new Date(picker.endDate._d);

            // -- if start date was empty, add a default one
            if($.trim($(dateInput.data('start')).val()) == ''){
                var startEl = $(dateInput.data('start'));
                    start = startEl.val();
                    start = start ? moment(start, 'YYYY-MM-DD HH:mm:ss') : moment().startOf('day');
                    startEl.val(isTime ? start.format('YYYY-MM-DD HH:mm:ss') : start.startOf('day').format('YYYY-MM-DD HH:mm:ss'));

                dateInput.data('daterangepicker').setStartDate(start);
            }

            // -- if end date was empty, add a default one
            if($.trim($(dateInput.data('end')).val()) == ''){
                var endEl = $(dateInput.data('end'));
                    end = endEl.val();
                    end = end ? moment(end, 'YYYY-MM-DD HH:mm:ss') : moment().endOf('day');
                    endEl.val(isTime ? end.format('YYYY-MM-DD HH:mm:ss') : end.endOf('day').format('YYYY-MM-DD HH:mm:ss'));

                dateInput.data('daterangepicker').setEndDate(end);
            }

            dateInput.val($(dateInput.data('start')).val() + ' to ' + $(dateInput.data('end')).val());


            if((end_date - start_date) >= restriction){ // -- get timestamp result

                if(dateInput.data('restrict-range-label') && $.trim(dateInput.data('restrict-range-label')) !== "")
                    alert(dateInput.data('restrict-range-label'));
                else{
                    var day_label = 'day';

                    if($restricted_range > 1) day_label = 'days'

                    alert('Please choose a date range not greater than '+ $restricted_range +' '+ day_label);
                }

                //  -- reset value
                //  -- if validation fails, do not change anything, retain the last correct values
                $(dateInput.data('start')).val('');
                $(dateInput.data('end')).val('');

                var startEl = $(dateInput.data('start'));
                    start = picker.oldStartDate;//startEl.val();
                    start = start ? moment(start, 'YYYY-MM-DD HH:mm:ss') : moment().startOf('day');

                var endEl = $(dateInput.data('end'));
                    end = picker.oldEndDate;//endEl.val();
                    end = end ? moment(end, 'YYYY-MM-DD HH:mm:ss') : moment().endOf('day');

                dateInput.data('daterangepicker').setStartDate(start);
                dateInput.data('daterangepicker').setEndDate(end);

                startEl.val(isTime ? start.format('YYYY-MM-DD HH:mm:ss') : start.startOf('day').format('YYYY-MM-DD HH:mm:ss'));
                endEl.val(isTime ? end.format('YYYY-MM-DD HH:mm:ss') : end.endOf('day').format('YYYY-MM-DD HH:mm:ss'));

                dateInput.val(startEl.val() + ' to ' + endEl.val());
            }

        });

		$('#btn-submit').click(function(e){
			e.preventDefault();

			var dateInput = $('#search_game_date.dateInput');

            var $restricted_range = checkDateRangeRestrictionWithUsername();

            if ($restricted_range == '' && !$.isNumeric($restricted_range) && !isRange)
                return false;

            var start_date = new Date($('#by_date_from').val());
            var end_date = new Date($('#by_date_to').val());
            var a_day = 86400000;
            var restriction = a_day * $restricted_range;

            if($.trim(dateInput.val()) == '' || ((end_date - start_date) >= restriction)){

                if(dateInput.data('restrict-range-label') && $.trim(dateInput.data('restrict-range-label')) !== "")
                    alert(dateInput.data('restrict-range-label'));
                else{
                    var day_label = 'day';

                    if($restricted_range > 1) day_label = 'days'

                    alert('Please choose a date range not greater than '+ $restricted_range +' '+ day_label);
                }

            }
            else{
            	$('#search-form').submit();
			}
		});

		<?php endif ?>

		$("#col-player-username").removeClass("hidden-col");

	    // Get hidden, text right and flag column
	    var elem = $('#myTable thead tr th');
	    var flagColIndex = elem.filter(function(index){
	        if ($(this).hasClass('hidden-col')) {
	            hiddenColumns.push(index);
	        }

	        if ($(this).hasClass('right-text-col')) {
	            rightTextColumns.push(index);
	        }

	        if ($(this).attr("id") == "flag_col") {
	            return index;
	        }
	    }).index();

	    var realBetCol = elem.filter(function(index){
            if ($(this).attr("id") == "col-real-bet") {
                return index;
            }
        }).index();
        var avlBetCol = elem.filter(function(index){
            if ($(this).attr("id") == "col-available-bet") {
                return index;
            }
        }).index();
        var resAmtCol = elem.filter(function(index){
            if ($(this).attr("id") == "col-result-amt") {
                return index;
            }
        }).index();
        var game_provider_id_col = elem.filter(function(index){
            if ($(this).attr("id") == "game_provider_id_col") {
                return index;
            }
        }).index();

		$('.disable-esp-char').on('input', function() {
			var c = this.selectionStart,
					r = /[^a-z0-9-_.]/gi,
					v = $(this).val();
			if(r.test(v)) {
				$(this).val(v.replace(r, ''));
				c--;
			}
			this.setSelectionRange(c, c);
		});

		var dataTable = $('#myTable').DataTable({
			<?php if( ! empty($enable_freeze_top_in_list) ): ?>
            scrollY:        1000,
            scrollX:        true,
            deferRender:    true,
            // scroller:       true, // disable for resolve the unexpected block space appear under the list.
            scrollCollapse: true,
            <?php endif; // EOF if( ! empty($enable_freeze_top_in_list) ):... ?>

			autoWidth: false,
			searching: false,
			<?php if($this->utils->isEnabledFeature('column_visibility_report')){ ?>
                stateSave: true,
            <?php } else { ?>
                stateSave: false,
            <?php } ?>
         	dom: "<'panel-body' <'pull-right'B><'pull-right progress-container'>l><'dt-information-summary1 text-info pull-left' i><'dataTable-instance't><'panel-body'<'pull-right'p><'dt-information-summary2 text-info pull-left' i>>",
			buttons: [
				{
					extend: 'colvis',
					postfixButtons: [ 'colvisRestore' ],
					className:'btn btn-sm <?=$this->utils->getConfig('use_new_sbe_color') ? 'btn-linkwater' : ''?>'
				}
				<?php

                   if( $export_report_permission ){

               ?>
				,{

					text: "<?php echo lang('CSV Export'); ?>",
                    className:'btn btn-sm <?=$this->utils->getConfig('use_new_sbe_color') ? 'btn-portage' : 'btn-primary'?>',
					action: function ( e, dt, node, config ) {
						var form_params=$('#search-form').serializeArray();
						var d = {'extra_search': form_params, 'export_format': 'csv', 'export_type': export_type,
							'draw':1, 'length':-1, 'start':0};
						console.log('export', d);
						// console.log(d);
						$("#_export_excel_queue_form").attr('action', site_url('/export_data/gamesHistory'));
						$("#_export_excel_queue_form [name=json_search]").val(JSON.stringify(d));
						$("#_export_excel_queue_form").submit();

					}
				}
				<?php
                    }
                ?>
			],
			columnDefs: [
				{ className: 'text-right', targets: rightTextColumns },
				{ visible: false, targets: hiddenColumns }

			],
			order: [[1,'desc'], [0,'desc'] ],

			// SERVER-SIDE PROCESSING
			processing: true,
			serverSide: true,
			ajax: function (data, callback, settings) {

				data.extra_search = $('#search-form').serializeArray();
                var formData = $('#search-form').serializeArray();
				// $("#by_game_platform_id option:selected").removeAttr("selected");
				//$("#game_type_tree").attr("value","");
                <?php if( ! empty($enable_go_1st_page_another_search_in_list) ): ?>
                    var _api = this.api();
                        var _container$El = $(_api.table().container());
                        var _md5 = _pubutils.NON_ENG_MD5(JSON.stringify(formData));
                    _container$El.data('md5_formdata_ajax', _md5); // assign
                <?php endif;// EOF if( ! empty($enable_go_1st_page_another_search_in_list) ):... ?>

				var _ajax = $.post(base_url + "api/gamesHistory", data, function(data) {
					<?php if ($this->utils->isEnabledFeature('show_sub_total_for_game_logs_report')) {?>
                        var real_total_bet = 0, total_bet = 0, total_result = 0, total_bet_result = 0, total_win = 0, total_loss = 0;
                        // console.log(data.data);
                        // console.log(rightTextColumns);
						$.each(data.data, function(i, v){
							var rtc = rightTextColumns;
							// var arr_results = [v[7], v[8], v[9], v[10], v[11], v[12]];
							var arr_results = [v[rtc[0]], v[rtc[1]], v[rtc[2]], v[rtc[3]], v[rtc[4]], v[rtc[5]]];
							for (var x = 0; x < arr_results.length; x++) {
								// Remove html tags from string
								arr_results[x] = arr_results[x].replace(/<\/?[^>]+(>|$)/g, "");
								// Remove comma from string
								arr_results[x] = arr_results[x].replace(/,/g , "");

								if (isNaN(arr_results[x])) {
									arr_results[x]  = 0;
								}
							}

							real_total_bet += parseFloat(arr_results[0]);
							total_bet += parseFloat(arr_results[1]);
							total_result += parseFloat(arr_results[2]);
							total_bet_result += parseFloat(arr_results[3]);
							total_win += parseFloat(arr_results[4]);
							total_loss += parseFloat(arr_results[5]);
						});

                        $('.sub-real-bet-total').text(addCommas(parseFloat(real_total_bet).toFixed(2)));
                        $('.sub-bet-total').text(addCommas(parseFloat(total_bet).toFixed(2)));
                        $('.sub-result-total').text(addCommas(parseFloat(total_result).toFixed(2)));
                        $('.sub-bet-result-total').text(addCommas(parseFloat(total_bet_result).toFixed(2)));
                        $('.sub-win-total').text(addCommas(parseFloat(total_win).toFixed(2)));
                        $('.sub-loss-total').text(addCommas(parseFloat(total_loss).toFixed(2)));
					<?php }?>
					$('.real-bet-total').text(addCommas(parseFloat(data.summary[0].real_total_bet).toFixed(2)));
					$('.bet-total').text(addCommas(parseFloat(data.summary[0].total_bet).toFixed(2)));
					$('.result-total').text(addCommas(parseFloat(data.summary[0].total_result).toFixed(2)));
					$('.bet-result-total').text(addCommas(parseFloat(data.summary[0].total_bet_result).toFixed(2)));
					$('.win-total').text(addCommas(parseFloat(data.summary[0].total_win).toFixed(2)));
					$('.loss-total').text(addCommas(parseFloat(data.summary[0].total_loss).toFixed(2)));
					$('.ave-bet-total').text(addCommas(parseFloat(data.summary[0].total_ave_bet).toFixed(2)));
					$('.bet-count-total').text(addCommas(parseFloat(data.summary[0].total_count_bet)));


					$('#platform-summary').html('');
					$.each(data.sub_summary, function(i,v) {
						$('#platform-summary').append(v.system_code + ' <?php echo lang("mark.bet"); ?>: ' + addCommas(parseFloat(v.total_bet).toFixed(2)) + '<br>')
                            .append(v.system_code + ' <?php echo lang("Result"); ?>: ' + addCommas(parseFloat(v.total_result).toFixed(2)) + '<br>')
                            .append(v.system_code + ' <?php echo lang("lang.bet.plus.result"); ?>: ' + addCommas(parseFloat(v.total_bet_result).toFixed(2)) + '<br>')
                            .append(v.system_code + ' <?php echo lang("Wins"); ?>: ' + addCommas(parseFloat(v.total_win).toFixed(2)) + '<br>')
                            .append(v.system_code + ' <?php echo lang("player.ui28"); ?>: ' + addCommas(parseFloat(v.total_loss).toFixed(2)) + '<br>');
					});
					callback(data);
					if ( dataTable.rows( { selected: true } ).indexes().length === 0 ) {
					    dataTable.buttons().disable();
					}
					else {
						dataTable.buttons().enable();
					}
					var info = dataTable.page.info();
					if (info.page != 0 && info.page > (info.pages-1) ) {
						dataTable.page('first').draw();
						dataTable.ajax.reload();
					}
					<?php if($this->config->item('game_logs_show_row_colors')): ?>
					$("tbody td.text-right:contains('-'):not('.sorting_1')").addClass('text-success');
				    <?php else: ?>
				    $("tbody td.text-right:contains('-'):not('.sorting_1')").addClass('text-red');
				    <?php endif;?>

				},'json');

                var _api = this.api();
                var _container$El = $(_api.table().container());
                _ajax.always(function(jqXHR, textStatus){
                    <?php if( ! empty($enable_go_1st_page_another_search_in_list) ): ?>
                        if(_container$El.data('md5_formdata_draw') != _container$El.data('md5_formdata_ajax')){
                            // goto 1st page
                            _api.page('first').draw(false);
                            _api.ajax.reload();
                        }else{
                            // idle
                        }
                    <?php endif;// EOF if( ! empty($enable_go_1st_page_another_search_in_list) ):... ?>
                    _container$El.data('md5_formdata_draw', _container$El.data('md5_formdata_ajax') ); // assign
                });
			},

			fnRowCallback: function( nRow, aData, iDisplayIndex, iDisplayIndexFull ) {
				// utils.safelog('aData[5]'+aData[5]+"aData[14]"+aData[14]);
				if (aData[flagColIndex] == "<?=Game_logs::FLAG_TRANSACTION?>") {
					$(nRow).css('background-color', '#<?=$this->config->item('color')['trans_in_game_log']?>');
				}

				<?php if($this->config->item('game_logs_show_row_colors')): ?>
				var rlt_amount=aData[resAmtCol];
				rlt_amount=rlt_amount.replace(/<[^>]+>/, '');
				rlt_amount=parseFloat(rlt_amount);

				/* *********************************************************
				 * 	Free spin if condition met
				 * *********************************************************
				 * 		real bet = 0 or n/A
				 * 		available bet = 0 or n/A
				 * 		result amount != 0
				 * 		flag = 1
				 **********************************************************/

				var game_with_no_free_spin	= <?= json_encode($this->config->item('game_with_no_free_spin'))?>;
				var chk_game_with_no_free_spin = jQuery.inArray(parseInt(aData[game_provider_id_col]), game_with_no_free_spin);
				if (aData[flagColIndex] == "<?=Game_logs::FLAG_GAME?>" && parseFloat(aData[avlBetCol]) == 0.00 && (parseFloat(aData[realBetCol]) == 0.00 || isNaN(aData[realBetCol])) && parseFloat(aData[resAmtCol]) != 0.00 && chk_game_with_no_free_spin == "-1") {
					if (aData[29] != 'N/A' && "<?= $this->utils->isEnabledFeature('hide_free_spin_on_game_history') ?>"){
	                } else {
	                    $(nRow).css('background-color', '#<?=$this->config->item('color')['free_game']?>');
	                }
				} else {
                    if (aData[flagColIndex] == "<?=Game_logs::FLAG_GAME?>") {
                        var games_with_valid_bet_checking = <?= json_encode($this->config->item('games_with_valid_bet_checking'))?>;
                        var game_in_array_key = jQuery.inArray(parseInt(aData[game_provider_id_col]), games_with_valid_bet_checking);
                        console.log("games_with_valid_bet_checking ==>", games_with_valid_bet_checking)
                        console.log("game_in_array_key ==>", game_in_array_key)
                        if(game_in_array_key != '-1' && parseFloat(aData[avlBetCol]) == 0.00){
                            //change background color
                            $(nRow).css('background-color', '#<?=$this->config->item('color')['free_game']?>');
                        }
                    }
                }
				<?php endif;?>

			}


		});

		dataTable._alignedInTfoot = function(settings){

			/// work around for some columns are not aligned in tfoot.
			// To clone the columns, $('#myTable_wrapper > div.dataTable-instance > div.dataTables_scroll > div.dataTables_scrollHead > div > table > thead > tr')
			// and add the specified class, "scroll-head-cloned"
			// into the first of footer in the list.
			//
			var _responsive_in_dataTable$El = $(settings.nTable).closest('.table-responsive');
			var _clonedClass = 'scroll-head-cloned'; // the class also defined in CSS
			if( _responsive_in_dataTable$El.find('.'+ _clonedClass).length > 0|| true){
				// if its already exists, destroy for refresh.
				_responsive_in_dataTable$El.find('.'+ _clonedClass).remove();
			}
			if( _responsive_in_dataTable$El.find('.'+ _clonedClass).length == 0){
				var _fields_in_scrollHead$El = _responsive_in_dataTable$El.find('div.dataTables_scrollHead > div > table > thead > tr').clone();
				_fields_in_scrollHead$El.addClass(_clonedClass)
				_fields_in_scrollHead$El.find('th[id]').removeProp('id');
				_fields_in_scrollHead$El.find('th[id]').removeAttr('id');
				// renameElement('')
				// _fields_in_scrollHead$El.find('th').
				_responsive_in_dataTable$El.find('div.dataTables_scrollFoot > div > table > tfoot').prepend( _fields_in_scrollHead$El );
			}

		}; // EOF dataTable._alignedInTfoot()...

		dataTable.on( 'draw', function (e, settings) {
            $("#myTable_wrapper .dataTable-instance").floatingScroll("init");

			<?php if( ! empty($enable_freeze_top_in_list) ): ?>
				var _min_height = $('.dataTables_scrollBody').find('.table tbody tr').height();
                _min_height = _min_height* 5; // limit min height: 5 rows

                var _scrollBodyHeight = window.innerHeight;
                _scrollBodyHeight -= $('.navbar-fixed-top').height();
                _scrollBodyHeight -= $('.dataTables_scrollHead').height();
                _scrollBodyHeight -= $('.dataTables_scrollFoot').height();
                _scrollBodyHeight -= $('#myTable_paginate').closest('.panel-body').height();
                _scrollBodyHeight -= 44;// buffer
				if(_scrollBodyHeight < _min_height ){
					_scrollBodyHeight = _min_height;
				}
				$('.dataTables_scrollBody').css({'max-height': _scrollBodyHeight+ 'px'});

				dataTable._alignedInTfoot(settings);

            <?php endif; // EOF if( ! empty($enable_freeze_top_in_list) ):... ?>

        });
		dataTable.on( 'column-visibility.dt', function ( e, settings, column, state ) {

			<?php if( ! empty($enable_freeze_top_in_list) ): ?>
			dataTable._alignedInTfoot(settings);
			<?php endif; // EOF if( ! empty($enable_freeze_top_in_list) ):... ?>
		});

		$('#search-form input[type="text"],#search-form input[type="number"],#search-form input[type="email"]').keypress(function (e) {
			if (e.which == 13) {
				dataTable.ajax.reload();
			}
		});

		<?php if ($this->utils->getConfig('game_logs_use_alt_game_type_tree_layout')) : ?>
			var tree0 = new alt_game_tree();
			tree0.load();
		<?php else : ?>

		$('#gameTree')
				.on('loaded.jstree', treeLoaded)
				.jstree({
					'core' : {
						'data' : {
							"url" : "<?php echo site_url('/api/get_report_game_tree'); ?>",
							"dataType" : "json" // needed only if you do not supply JSON headers
						}
					},
					"checkbox":{
						"tie_selection": false
					},
					"plugins":[
						"search","checkbox"
					]
				});

		// if(gameTypeId) {
		// 	$('#gametype').trigger('click');
		// 	gameTypeId = gameTypeId.split(',');
		// } else {
		// 	$('#game_type_id').prop('checked', false);
		// }

		function treeLoaded(event, data) {
			data.instance.check_node(gameTypeId);
		}


		$("#game_type_id").change(function(){
			console.log('game_type_id change handler');
			var checked = $("#game_type_id").is(":checked");
			if(checked){
				$(".jstree-anchor").addClass('jstree-checked');
			}else{
				$(".jstree-anchor").removeClass('jstree-checked');
			}
			var checked = $('li ul .jstree-anchor.jstree-checked');
			var data = [];
			for(i=0; i<checked.length; i++){
				var aaa = checked[i].id;
				data.push(aaa.replace(/gp_|_anchor/gi,''));
			}
			var myJsonString = JSON.parse(JSON.stringify(data));
			$("#game_type_tree").attr('value',myJsonString);
		});



		$('.jstree').click(function(){
			console.log('jstree click handler');
			var tree = $(".jstree").jstree("get_checked",true),
				game_type_ids = [],
				game_platform_ids = [];

			$.each(tree, function(){

				var tree_game_type_id = this.id;

				if( tree_game_type_id.indexOf('gp_') > -1 ){
					var platFormId = tree_game_type_id.replace("gp_", "")
					game_platform_ids.push(platFormId);
					return true;
				}

				game_type_ids.push(tree_game_type_id);

			});

			$("#game_type_tree").attr('value',game_type_ids);
			// $.each($game_platform_ids, function(index, value){
			// 	$("#by_game_platform_id option:selected").attr('value', game_platform_ids);
			// });
			$("#by_game_platform_id option:selected").attr('value', game_platform_ids);

		});

		<?php endif; ?>

	}); // End of $(document).ready()

	/**
	 * Alternate game tree layout routines
	 * OGP-21462
	 */
	function alt_game_tree() {
		/**
		 * load from ajax endpoint, api get_report_game_tree
		 */
		function load() {
			var jq = $.get('/api/get_report_game_tree')
			.done(function(resp) {
				// console.log(resp);
				render_tree(resp);
			})
			.fail(function() {
				console.log('error', resp);
			})
		}

		/**
		 * render the tree
		 * @param	object	resp	jquery ajax response object, json decoded
		 */
		function render_tree(resp) {
			var frame = $('#gtree_alt_frame');
			for (var i in resp) {
				var gp = resp[i];
				/**
				 * id
				 * text
				 * state
				 * 		checked
				 * 		opened
				 * set_number
				 * number
				 * percentage
				 * children
				 * 		(child game types)
				 */
				var gp_item = $('#gtree_alt_gp_tmpl').clone();
				$(gp_item).removeAttr('id').css('display', 'block');
				$(gp_item).find('.gp_title').text(gp.text).attr('data-gpid', gp.id);
				$(gp_item).find('.gp_contents').attr('data-gpid', gp.id);
				$(frame).append(gp_item);

				var gtype_item = render_gtype_item({text: 'All', gtid: 'all', id: ('all_' + gp.id), extra_class: 'gtype_all'});
				$(gp_item).find('.gp_contents').append(gtype_item);

				for (var j in gp.children) {
					var gtype = gp.children[j];
					gtype.gtid = gtype.id;
					/**
					 * id
					 * text
					 * state
					 * 		checked
					 * 		opened
					 * 	set_number
					 * 	number
					 * 	percentage
					 */
					var gtype_item = render_gtype_item(gtype);
					$(gp_item).find('.gp_contents').append(gtype_item);
				}
			}
			post_render_set_values();
			post_render_reg_handlers();
		} // End function render_tree()

		/**
		 * render each gtype (game type) item
		 * @param	object	gtype	game type object
		 */
		function render_gtype_item(gtype) {
			var gtype_item = $('#gtree_alt_gtype_tmpl').clone();
			$(gtype_item).removeAttr('id').css('display', 'inline-flex');
			$(gtype_item).find('.gtype_text').text(gtype.text);
			$(gtype_item).find('.gtype_cb').attr('data-gtid', gtype.gtid).attr('id', 'gt' + gtype.id);
			if (gtype.extra_class) {
				$(gtype_item).find('.gtype_cb').addClass(gtype.extra_class);
			}

			return gtype_item;
		}

		/**
		 * post render: set values from input#gtree_alt_frame to .gtype_cb checkboxes
		 */
		function post_render_set_values() {
			console.log('gameTypeId', gameTypeId);
			var gtframe = $('#gtree_alt_frame');
			for (var i in gameTypeId) {
				var gtid = gameTypeId[i];
				$(gtframe).find("input[id=" + ('gt' + gtid) + "]").click();
			}

			set_check_all_state_all();
			// set_game_type_id();
		}

		/**
		 * Scans all .gp_contents blocks, set/unset their 'all' checkbox
		 */
		function set_check_all_state_all() {
			var gtframe = $('#gtree_alt_frame');
			$(gtframe).find('.gtree_gp .gp_contents').each(function () {
				set_check_all_state(this);
			});
		}

		/**
		 * set/unset 'all' checkbox of a single .gp_contents block
		 * @param	ref		self	reference to the .gp_contents block
		 */
		function set_check_all_state(self) {
			var all_checked = true;
			$(self).find('input.gtype_cb').each(function () {
				var cb_gtid = $(this).data('gtid');
				// console.log('cb_gtid', cb_gtid);
				if (cb_gtid != 'all' && $(this).prop('checked') == false) {
					all_checked = false;
				}
			});

			// console.log('all_checked', all_checked, $(self).data('gpid'));

			if (all_checked) {
				$(self).find('input.gtype_all').prop('checked', true);
			}
			else {
				$(self).find('input.gtype_all').prop('checked', false);
			}
		}

		/**
		 * Collect game type id from checked .gtype_cb checkboxes and set to #game_type_tree
		 */
		function set_game_type_id() {
			var gtid_hidden = $('#game_type_tree');
			var gtid_ar = [];
			$('.gtype_cb').each(function() {
				var gtid = $(this).data('gtid');
				if (gtid != 'all' && $(this).prop('checked')) {
					gtid_ar.push(parseInt(gtid));
				}
			});

			var gtid_csv = gtid_ar.join(',');
			console.log('game_type_tree', gtid_csv);
			$(gtid_hidden).val(gtid_csv);
		}

		/**
		 * Set up handlers: collapse/expand buttons, .gtype_cb checbox clicks
		 */
		function post_render_reg_handlers() {

			// game tree collapse
			$('.gtree_collapse').click(function () {
				$(this).hide();
				$(this).siblings('.gtree_expand').show();
				$(this).parent().siblings('#gtree_alt_frame').hide();
			});

			// game tree expand
			$('.gtree_expand').click(function () {
				$(this).hide();
				$(this).siblings('.gtree_collapse').show();
				$(this).parent().siblings('#gtree_alt_frame').show();
			});

			// game platform collapse
			$('.gp_collapse').click(function () {
				$(this).hide();
				$(this).siblings('.gp_expand').show();
				$(this).parent().siblings('.gp_contents').hide();
			});

			// game platform expand
			$('.gp_expand').click(function () {
				$(this).hide();
				$(this).siblings('.gp_collapse').show();
				$(this).parent().siblings('.gp_contents').show();
			});

			// individual checkbox click handler
			$('.gtype_cb').click(function () {
				var gtid = $(this).data('gtid');
				var checked = $(this).prop('checked');
				// console.log('gtid', gtid, checked, $(this).attr('id'));
				if (gtid == 'all') {
					$(this).parents('.ga_gtype').siblings('.ga_gtype').find('.gtype_cb').prop('checked', checked);
				}
				else {
					set_check_all_state($(this).parents('.gp_contents'));
				}

				set_game_type_id();
			});

		}

		return {
			load: load ,
			render_tree: render_tree
			// post_render: post_render
		};
	}  // End function alt_game_tree()

	$(document).on("click",".buttons-columnVisibility",function(){
	    $("#myTable_wrapper .dataTable-instance").floatingScroll("update");
	});

	$(document).on("click",".showCardList",function(){
		var cardList = $(this).data("card").split(" ");
		var title = "<?php echo lang('Card List');?>"
		$("#search-form").append('<div id="cardListModal" class="modal fade">\
									  <div class="modal-dialog">\
									    <div class="modal-content">\
									      <div class="modal-header">\
									        <button type="button" class="close" data-dismiss="modal">&times;</button>\
									        <h4 class="modal-title">'+title+'</h4>\
									      </div>\
									      <div class="modal-body">\
									      </div>\
									    </div>\
									  </div>\
									</div>');
		$.each(cardList, function( index, value ) {
		  $("#cardListModal").find(".modal-body").append('<i class="icon-img card-'+value+'"></i>')
		});
		$('#cardListModal').modal('show');
	});
	$(document).on("hidden.bs.modal","#cardListModal",function(){
		$("#cardListModal").remove();
	});
    $(function(){
        var flag_inputs = $('#by_game_flag');

        new SpinCheckboxCond();

        flag_inputs.on('change', function(){
            new SpinCheckboxCond();
        });

        function SpinCheckboxCond(){
            if(flag_inputs.val() == <?=Game_logs::FLAG_TRANSACTION?>){
                $('#check-free-spin').attr('disabled', true).removeAttr('checked');
            }else{
                $('#check-free-spin').removeAttr('disabled');
            }
        }
    });
</script>
