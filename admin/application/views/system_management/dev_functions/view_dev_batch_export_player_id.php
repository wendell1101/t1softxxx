<style type="text/css">
	.panel-body .row{
		margin: 4px;
	}
</style>

<form id='batch_export_player_id_form' action="<?=site_url('system_management/remote_batch_export_player_id'); ?>" enctype="multipart/form-data" method="POST">
    <div class="panel panel-primary panel_main">
        <div class="panel-heading">
            <h4 class="panel-title">
                <?=lang('Batch Export Player Id')?>
                <!-- <span class="lock_count" style="color: blue;">20</span> -->
            </h4>
        </div>
        <div id="toggle_batch_export_player_id" class="panel-collapse collapse in">
            <div class="panel-body">
                <div class="row">
                    <label>Download Sample:  <a href="/sample_batch_export_player_ids.csv" target="_blank">Click Here</a></label>
                </div>
        
                <div class="form-group row">
                    <label class="col-md-1"><?php echo lang('Upload File'); ?></label>
                    <div class="col-md-2">
                        <div class="">
                            <input type="file" name="batch_export_player_id_file" class="form-control input-sm" required="required" accept=".csv"/>
                        </div>
                        <section id="note-footer" style="color:red; font-size: 12px; margin-top: 4px;" class="five"><?=lang('Note: Upload file format must be CSV')?></section>
                    </div>
                </div>
            </div>
            <div class="panel-footer">
                <button type="submit" id="batch_export_player_id_button" class="btn btn-primary btn-lg" id="load" data-loading-text="<i class='fa fa-spinner fa-spin'></i>   Ongoing batch export. Kindly wait ..."><i aria-hidden="true"></i>Batch Export</button>
            </div>
        </div>
    </div>
</form>