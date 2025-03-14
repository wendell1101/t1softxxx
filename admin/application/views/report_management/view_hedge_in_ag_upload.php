<?php
$default_open_upload_panel = true; // default_open_search_panel
$agin_prefix_for_username = $this->utils->getConfig('agin_prefix_for_username');

?><div class="panel panel-primary hidden">
    <div class="panel-heading">
        <h4 class="panel-title">
            <i class="fa fa-search"></i> <?=lang("Upload Hedging Total Detail Info")?>
            <span class="pull-right">
                <a data-toggle="collapse" href="#collapseHedgeUpload" class="btn btn-xs <?=$this->utils->getConfig('use_new_sbe_color') ? 'btn-primary' : 'btn-info'?> <?=$default_open_upload_panel ? '' : 'collapsed'?>"></a>
            </span>
        </h4>
    </div>
    <div id="collapseHedgeUpload" class="panel-collapse <?=$default_open_upload_panel ? '' : 'collapse in'?>">
        <div class="panel-body">
            <form id="upload-form" action="<?= site_url('/report_management/uploadHedgeInAG4HedgingDetailInfoXls'); ?>" method="post" enctype="multipart/form-data">

                <div class="row">
                    <!-- xls_file -->
                    <div class="form-group col-md-12">

                        <div>
                            <label class="control-label">
                                <?= lang('hint.hedge_in_ag4update') ?>
                            </label>
                        </div>
                        <label class="control-label">
                            <?= lang('The xls file'); ?> :
                        </label>
                        <div>
                            <input type="file" name="userfile" id="userfile" >
                        </div>


                    </div>
                </div>

                <div class="row">
                    <div class="form-group col-md-2 col-md-offset-10">
                        <div class="pull-right">
                            <button type="submit" class="btn btn-sm <?=$this->utils->getConfig('use_new_sbe_color') ? 'btn-portage' : 'btn-info'?>"><?=lang("Upload")?></button>
                        </div>
                    </div>
                    <div class="form-group col-md-2 col-md-offset-10">
                        <label class="control-label list-group-item-danger pls-setup-config hide">
                            <?= lang('Please setup the configure for enable the submit.'); ?>
                        </label>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>


<script type="text/javascript">


    $(document).ready(function(){

        var agin_prefix_for_username = '<?=$agin_prefix_for_username?>';
        if(agin_prefix_for_username == ''){
            $('#collapseHedgeUpload button:submit').addClass('disable');
            $('#collapseHedgeUpload button:submit').prop('disabled', 'disabled');
            $('.pls-setup-config').removeClass('hide');
        }else{
            $('#collapseHedgeUpload button:submit').removeClass('disable');
            $('.pls-setup-config').addClass('hide');
        }

    });
</script>