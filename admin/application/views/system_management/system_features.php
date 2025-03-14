<?php
$deprecated_features = $this->utils->getConfig('deprecated_features');
$system_feature_page_read_only = $this->utils->getConfig('system_feature_page_read_only');
if( empty($system_feature_page_read_only) ){
    $system_feature_page_read_only = 0;
}else{
    $system_feature_page_read_only = 1;
}
?>
<div class="panel panel-primary panel_main">
	<div class="panel-heading" id="">
		<h4 class="panel-title"><i class="fa fa-cogs"></i> &nbsp;<?php echo $title; ?>
            <a href="#main_panel" data-toggle="collapse" class="pull-right"><i class="fa fa-caret-down"></i></a>
		</h4>
	</div>
	<div id="main_panel" class="panel-collapse collapse in ">
		<div class="panel-body">
            <ul class="nav nav-tabs">
                <?php foreach(System_feature::FEATURE_TYPES as $system_feature_type): ?>
                <li><a data-toggle="tab" href="#system_feature_type_<?=$system_feature_type?>"><?=lang('system_feature_type_'. $system_feature_type)?></a></li>
                <?php endforeach ?>
            </ul>
            <div class="tab-content">
                <?php foreach(System_feature::FEATURE_TYPES as $system_feature_type): ?>
                <div id="system_feature_type_<?=$system_feature_type?>" class="tab-pane fade in table-responsive">
                    <table class="table table-hover table-striped table-bordered list_features">
                        <thead>
                            <tr>
                                <th class="col-md-3"><?php echo lang('aff.al36'); ?></th>
                                <th class="col-md-7"><?php echo lang('sys.description'); ?></th>
                                <th class="col-md-2"><?php echo lang('Value'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($system_feature as $idx => $feature) { ?>
                            <?php if($feature['type'] != $system_feature_type) continue; ?>
                            <tr>
                                <?php if(in_array($feature['name'], $deprecated_features)): ?>
                                    <?php if($this->utils->getConfig('disable_show_deprecated_system_feature')): ?>
                                    <td><del><?=$feature['name']?></del></td>
                                    <td><del><?=lang('system_feature_desc_' . $feature['name'])?><?=(in_array($feature['name'], $deprecated_features)) ? '<p><span class="text-danger">Deprecated</span></p>' : ''?></del></td>
                                    <td>
                                        <div class="form-group disabled">
                                            <input type="checkbox" id="item_<?=$feature['id']?>" name="enabled[]" value="<?=$feature['id']?>" readonly="readonly" onclick="return false;">
                                        </div>
                                    </td>
                                    <?php endif ?>
                                <?php else: ?>
                                    <td><?=$feature['name']?></td>
                                    <td><?=lang('system_feature_desc_' . $feature['name'])?><?=(in_array($feature['name'], $deprecated_features)) ? '<p><span class="text-danger">Deprecated</span></p>' : ''?></td>
                                    <td>
                                        <div class="form-group">
                                            <input type="checkbox" id="item_<?=$feature['id']?>" name="enabled[]" value="<?=$feature['id']?>" <?=( $feature['enabled'] > 0 ) ? 'checked="checked"' : ''?>>
                                        </div>
                                    </td>
                                <?php endif ?>
                            </tr>
                        <?php } ?>
                        </tbody>
                    </table>
                </div>
                <?php endforeach ?>
            </div>
		</div>
		<div class="panel-footer">
			<input type="submit" class="btn btn_save btn-scooter" value="<?php echo lang('Save'); ?>">
		</div>


	</div>

</div>

<script type="text/javascript">
$(function(){
    $('.nav-tabs a:first').tab('show');
    var system_feature_page_read_only = "<?=$system_feature_page_read_only?>";
    var system_feature_page_read_only_message = "<?=lang('con.vsm01')?>";

    var  base_url = "<?=base_url()?>";

    if(system_feature_page_read_only == 1){ // just UI handle.
        $('[name="enabled[]"]').prop('readonly','readonly');
        $('.btn_save').addClass('disabled');
    }

    $('.btn_save').on('click', function(e){
        e.preventDefault();
        if(system_feature_page_read_only != 1){ // just UI handle.
            var item = [];
            $('.list_features').find('input[type="checkbox"]').each(function(){
                var enabled = 0;
                if( $(this).is(':checked') ) enabled = 1;
                var json = {
                    'id' : $(this).val(),
                    'enabled' : enabled
                }
                item.push(json);

            });

            $.ajax({
                url: base_url + 'system_management/saveSystemFeatures',
                type: 'POST',
                data: {
                    enabled: item
                },
                success: function(data){
                    window.location = base_url + 'system_management/system_features';
                }
            });
        }else{
            var  notifyErr = _pubutils.notifyErr(system_feature_page_read_only_message);
            setTimeout(function(){
                _pubutils.closeNotify(notifyErr);
            },2000);
        }
    });
});
</script>