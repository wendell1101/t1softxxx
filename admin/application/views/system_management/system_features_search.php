<?php
$deprecated_features = $this->utils->getConfig('deprecated_features');
$show_deprecated_system_feature = $this->utils->getConfig('disable_show_deprecated_system_feature');
$system_feature_page_read_only = $this->utils->getConfig('system_feature_page_read_only');
if (empty($system_feature_page_read_only)) {
    $system_feature_page_read_only = 0;
} else {
    $system_feature_page_read_only = 1;
}
?>
<form id='search-form'
    action="<?=site_url('system_management/system_features'); ?>"
    method="POST">
    <div class="panel panel-primary panel_main">
        <div class="panel-heading" id="">
            <h4 class="panel-title"><i class="fa fa-search"></i><?=lang('Search')?>
                <!-- <a href="#search-result" data-toggle="collapse" class="pull-right"><i class="fa fa-caret-down"></i></a> -->
            </h4>
        </div>
        <div class="panel-body">
            <div class="pull-right">
                <?=lang('Download Results');?>
                <button type="button" class="btn btn-sm btn-portage" id='export_csv'><?=lang('Export CSV');?></button>
            </div>
            <div class="form-group">
                <div class="col-md-3">
                    <label class="control-label" for='keyword'><?=lang('Keyword');?></label>
                    <input type="text" name="keyword"
                        value="<?php echo(empty($search_conditions['keyword']) ? '': $search_conditions['keyword']);?>"
                        id="keyword" class="form-control input-sm" placeholder="Keyword">
                    <div class="checkbox">
                        <label>
                            <input type="checkbox" name="show_default_features" id="show_default_features" <?php echo empty($search_conditions['show_default_features']) ? '': ($search_conditions['show_default_features'] == true ? 'checked' :'');?>>
                            <?=lang('Show Default Features for New Clients');?>
                        </label>
                    </div>
                </div>
            </div>
        </div>
        <div class="panel-footer">
            <div class="text-center">
                <input class="btn btn-sm btn-linkwater" type="reset"
                    value="<?=lang('sys.vu16');?>">
                <button class="btn btn-sm btn-portage" type="submit" id="search_form-submit" form="search-form"><i
                        class="fa fa-search"></i> <?=lang('sys.vu15');?></button>
            </div>
        </div>
    </div>
</form>

<!-- System Features -->
<div class="panel panel-primary panel_main">
    <div class="panel-heading" id="">
        <h4 class="panel-title"><?=lang('System Feature')?>
        </h4>
    </div>
    <div class="panel-body" id="add_panel_body">
        <?php if ($this->utils->isEnabledMDB() && $this->permissions->checkPermissions(['system_features', 'sync_system_feature_to_other_currency'])) : ?>
            <button type="button" id="sync_all_system_feature_to_currency" class="btn btn-sm btn-portage" ><?=lang('Sync All System Feature To Other Currency'  )?></button>
        <?php endif;?>
        <button type="button" id="collapse-all" class="btn btn-sm pull-right btn-scooter" data-action='close'><?=lang('Expand / Close All')?></button>
        <div class="clearfix" style="margin-bottom: 2rem;"></div>
        <div class="panel-group" id="feature-group">
            <?php //foreach ($system_feature as $system_feature_type => $feature_item) :?>
            <?php foreach (System_feature::FEATURE_TYPES as $feature_type) :?>
            <?php if (isset($system_feature[$feature_type])):
                    $system_feature_type = $feature_type;
                    $feature_item = $system_feature[$system_feature_type];
            ?>
            <div class="panel panel-default parent-panel">
                <div class="panel-heading">
                    <h4 class="panel-title">
                        <a data-toggle="collapse" style="text-decoration: none"
                            data-parent="#system_feature_type_<?=$system_feature_type?>"
                            href="#system_feature_type_<?=$system_feature_type?>">
                            <?=lang('system_feature_type_'. $system_feature_type)?>
                            &nbsp;&nbsp;
                            <span class="text text-success" style="font-size: 12pt"><?=lang('lang.active')?>:
                                <span class="active_count"></span></span>

                            <span class="text text-warning" style="font-size: 12pt"><?=lang('lang.inactive')?>:
                                <span class="inactive_count"></span></span>

                            <?php if($show_deprecated_system_feature):?>
                                <span class="text text-danger" style="font-size: 12pt"><?=lang('Deprecated')?>:
                                <span class="deprecated_count"></span></span>
                            <?php endif;?>
                        </a>
                    </h4>
                </div>
                <div id="system_feature_type_<?=$system_feature_type?>" class="panel-collapse collapse in">
                    <div class="panel-body">
                        <div class="tab-pane fade in table-responsive">
                            <table class="table table-hover table-striped table-bordered list_features">
                                <thead>
                                    <tr>
                                        <th class="col-md-3"><?=lang('aff.al36'); ?></th>
                                        <th class="col-md-7"><?=lang('sys.description'); ?></th>
                                        <th class="col-md-2">
                                            <input type="checkbox" name='group_<?=$system_feature_type?>' id='group_<?=$system_feature_type?>' onclick="checkAll(this.id)">
                                            <label for='group_<?=$system_feature_type?>'><?=lang('Select All'); ?></label>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($feature_item as $idx => $feature) : ?>
                                    <tr>
                                        <?php if(!empty($feature['is_deprecated'])): ?>
                                            <td><del><?=$feature['name']?></del></td>
                                            <td><del><?=lang('system_feature_desc_' . $feature['name'])?><?=(!empty($feature['is_deprecated'])) ? '<p><span class="text-danger">Deprecated</span></p>' : ''?></del></td>
                                            <td>
                                                <div class="form-group disabled">
                                                    <input type="checkbox" class='count is_deprecated' id="item_<?=$feature['id']?>" name="enabled[]" value="<?=$feature['id']?>" readonly="readonly" onclick="return false;">
                                                </div>
                                            </td>
                                        <?php else: ?>
                                            <td>
                                                <?=$feature['name']?>
                                                <?php if($this->utils->isEnabledMDB() && $this->permissions->checkPermissions(['system_features', 'sync_system_feature_to_other_currency'])): ?>
                                                <div class="sync_btn">
                                                    <button type="button" class="btn btn-sm btn-primary btn_feature" data-featurekey="<?=$feature['name']?>"><?=lang('Sync To Currency')?></button>
                                                </div>
                                                <?php endif;?>
                                            </td>
                                            <td><?=lang('system_feature_desc_' . $feature['name'])?></td>
                                            <td>
                                                <div class="form-group">
                                                    <input type="checkbox" class='group_<?=$system_feature_type?> feature-item count' id="item_<?=$feature['id']?>" name="enabled[]" value="<?=$feature['id']?>" <?=( $feature['enabled'] > 0 ) ? 'checked="checked"' : ''?>>
                                                </div>
                                            </td>
                                        <?php endif ?>
                                    </tr>
                                <?php endforeach; //systeam feature item ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif;?>

            <?php endforeach;?>
        </div>
    </div>
    <div class="panel-footer">
        <input type="submit" class="btn btn_save btn-scooter"
            value="<?php echo lang('Save'); ?>">
    </div>
</div>
<?php //var_dump($system_feature); ?>

<form id="_export_search_result_form" class="hidden" method="POST" target="_blank">
    <input name='json_search' type="hidden">
</form>

<script type="text/javascript">
    $(function() {
        $('.nav-tabs a:first').tab('show');
        var system_feature_page_read_only =
            "<?=$system_feature_page_read_only?>";
        var system_feature_page_read_only_message =
            "<?=lang('con.vsm01')?>";

        var base_url = "<?=base_url()?>";

        if (system_feature_page_read_only == 1) { // just UI handle.
            $('[name="enabled[]"]').prop('readonly', 'readonly');
            $('.btn_save').addClass('disabled');
        }

        $('.feature-item').click(function(){
            calculateActiveInactiveCounter();
        });

        $('#collapse-all').on('click', function () {

        	if($(this).data('action') == 'open'){
		    	$('#feature-group .panel-collapse').collapse('show');
		    	$(this).data('action','close');
        	}
        	else{
        		$('#feature-group .panel-collapse').collapse('hide');
		    	$(this).data('action','open');
        	}

		});

        $('.btn_save').on('click', function(e) {
            e.preventDefault();
            if (system_feature_page_read_only != 1) { // just UI handle.
                var item = [];
                $('.list_features').find('input[type="checkbox"]').each(function() {
                    var enabled = 0;
                    if ($(this).is(':checked')) enabled = 1;
                    var json = {
                        'id': $(this).val(),
                        'enabled': enabled
                    }
                    item.push(json);

                });

                $.ajax({
                    url: base_url + 'system_management/saveSystemFeatures',
                    type: 'POST',
                    data: {
                        enabled: item
                    },
                    success: function(data) {
                        window.location = base_url +
                            'system_management/system_features_search';
                    }
                });
            } else {
                var notifyErr = _pubutils.notifyErr(system_feature_page_read_only_message);
                setTimeout(function() {
                    _pubutils.closeNotify(notifyErr);
                }, 2000);
            }
        });

        //export btn
        $('#export_csv').on('click', function(e) {
            e.preventDefault();
            var form_params = $('#search-form').serializeArray();
            $.ajax({
                url: site_url('/export_data/export_system_feature_search_result'),
                type: 'POST',
                data: {
                    json_search: JSON.stringify(form_params)
                }
            }).done(function(data) {
                if (data && data.success) {
                    $('body').append('<iframe src="' + data.link +
                        '" frameborder="0" scrolling="no" style="border:0px;width:0px;height:0px"></iframe>'
                        );
                    $('.export_csv').removeAttr("disabled");
                } else {
                    $('.export_csv').removeAttr("disabled");
                    alert('export failed');
                }
            }).fail(function() {
                $('.export_csv').removeAttr("disabled");
                alert('export failed');
            });
        });

        calculateActiveInactiveCounter();
        $('#sync_all_system_feature_to_currency').click(function(e){
            e.preventDefault();
            if(confirm("<?=lang('Do you want to overwrite features to all currency')?>?")){
                window.location.href="<?=site_url('/system_management/sync_all_features')?>";
            }
        });
        $('.btn_feature').click(function(e){
            e.preventDefault();
            if(confirm("<?=lang('Do you want to overwrite this feature to all currency')?>?")){
                var featurekey=$(this).data('featurekey');
                window.location.href="<?=site_url('/system_management/sync_one_feature')?>/"+featurekey;
            }
        });
    });

    function checkAll(id) {
        var list = document.getElementsByClassName(id);
        var all = document.getElementById(id);

        if (all.checked) {
            for (i = 0; i < list.length; i++) {
                list[i].checked = 1;
            }
        } else {
            all.checked;

            for (i = 0; i < list.length; i++) {
                list[i].checked = 0;
            }
        }
        calculateActiveInactiveCounter();
    }
    function calculateActiveInactiveCounter(){

        var active_ctr = 0;
        var inactive_ctr = 0;

        $('.parent-panel').each(function(){
            $(this).find('.feature-item').each(function(){

                if($(this).is(':checked'))
                    active_ctr++;
                else
                    inactive_ctr++;
            });

            $(this).find('.active_count').text(active_ctr);
            $(this).find('.inactive_count').text(inactive_ctr);
            $(this).find('.deprecated_count').text($(this).find('.is_deprecated').length);

            active_ctr = 0;
            inactive_ctr = 0;
        });
    }
</script>