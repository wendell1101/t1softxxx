<style>
    .selected_aff-item {
        background: #ccc;
        margin: 3px;
        padding: 4px;
        display: inline-block;
    }
</style>
<div class="panel panel-default">
    <div class="panel-body">
        <div class="row">
            <div class="col-lg-12">
                <div class="form-group">
                    <label><?php echo lang("Selected affiliate have affiliate Set to Reviewed");?></label>
                    <div class="selected_aff-list">
                        <h3>
                            <?php echo lang("Affiliates");?>
                        </h3>
                        <?php
                            if (!empty($aff_skip_list)) {
                                foreach ($aff_skip_list as $key => $aff) {
                                    echo '<span class="selected_aff-item"> '.$aff['username'].' </span>';
                                }
                            }
                        ?>
                    </div>
                </div>
            </div>
            <div class="col-lg-12 text-right">
                <button type="button" class="btn btn-default btn-sm btn-clear close_btn" onClick=""><?php echo lang('lang.cancel')?></button>
            </div>
        </div>
    </div>
</div>
<script type="text/javascript">
    $(document).ready(function() {
        $('#mainModalLabel').html(
            "<?=lang("Notice")?>");
    });

    $('.close_btn').click(function() {

        $('#mainModal').modal('hide');
    });
</script>