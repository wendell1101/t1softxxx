<!--main-->
<div class="panel panel-primary hidden">

    <div class="panel-heading">
        <h4 class="panel-title">
            <i class="fa fa-search"></i> <?=lang("lang.search")?>
            <span class="pull-right">
                <a data-toggle="collapse" href="#collapseTaggedList" class="btn btn-info btn-xs <?=$this->config->item('default_open_search_panel') ? '' : 'collapsed'?>"></a>
            </span>
        </h4>
    </div>

    <div id="collapseTaggedList" class="panel-collapse <?=$this->config->item('default_open_search_panel') ? '' : 'collapse in'?>">
        <div class="panel-body">
			<form class="" id="search-form">
				<div class="row">
					<!-- date of tag -->
					<div class="col-lg-3 col-md-3 col-sm-12 col-xs-12">
						<label class="control-label" for="search_date"><?=lang('tagged_players.date_of_tag');?></label>
						<div class="input-group">
		                    <input id="search_date" class="form-control input-sm dateInput user-success" data-time="true" data-start="#date_from" data-end="#date_to">
		                    <input type="hidden" id="date_from" name="date_from" value="<?= $date_from ?>">
		                    <input type="hidden" id="date_to" name="date_to" value="<?= $date_to ?>">
		                    <span class="input-group-addon input-sm">
                                <input type="checkbox" name="search_reg_date" id="search_reg_date" class="user-success">
		                    </span>
		                </div>
		            </div>
				</div>
			</form>
		</div>
	</div>
</div>