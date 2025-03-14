
<?php if($this->config->item('enable_drag_drop_deposit_proof') && $this->utils->is_mobile()) :?>
<style>
    .deposit-upload {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .deposit-upload .upload-browse {
        width: 48%;
    }

    .deposit-upload .upload-browse input[type="file"] {
        display: none;
    }

    .deposit-upload .upload-browse label {
        display: flex !important;
        justify-content: center;
        min-height: 150px;
        margin: 10px;
        width: 100%;
        margin: 10px 0;
        align-items: center;
        background-image: url("data:image/svg+xml,%3csvg width='100%25' height='100%25' xmlns='http://www.w3.org/2000/svg'%3e%3crect width='100%25' height='100%25' fill='none' rx='10' ry='10' stroke='%23666666FF' stroke-width='9' stroke-dasharray='16%2c 8%2c 9%2c 17' stroke-dashoffset='0' stroke-linecap='square'/%3e%3c/svg%3e");
        border-radius: 10px;
    }

    .deposit-upload .upload-browse label img {
        width: 100%;
        height: auto;
    }

    .deposit-upload .upload-browse label span {
        font-size: 30px;
        font-weight: 700;
    }
</style>
<script>
$(function() {
    $(".deposit-upload .upload-browse input[type='file']").on('change' , function(e) {
        const file = e.target.files[0];
        const reader = new FileReader();
        const preview = $('#img-preview-' + $(this).attr('id'));
        const label = $('#file-label-' + $(this).attr('id'));

        reader.addEventListener("load", function () {
            preview.show();
            label.hide();
            preview.attr('src', reader.result);
        }, false);

        if (file) {
            reader.readAsDataURL(file);
        }
    });
});
</script>
<?php endif; ?>

<?php if ($this->utils->isEnabledFeature('enable_deposit_upload_documents')) :?>
    <div class="row deposit-process-mode-<?=in_array($deposit_process_mode, array('2','3')) ? '2' : $deposit_process_mode ?> setup-deposit-uploads">
        <div class="form-group has-feedback">
            <p class="step"><span class="step-icon"><?=$deposit_step++?></span><label class="control-label"><?=lang('Upload Attachment')?></label>
                <?php if($this->system_feature->isEnabledFeature('enable_display_manual_deposit_upload_documents_step_hint')):?>
                    <span class="step_hint manual_deposit_upload_documents_step_hint"><?=lang('pay.manual_deposit.step_hint.upload_documents')?></span>
                <?php endif;?>
            </p>
            <span id="errfm_txtImage" class="text-danger"><?=$this->utils->isEnabledFeature('enable_deposit_page_make_manual_deposit_upload_helptext_always_showing')?lang('Please upload at least one file when using ATM/Cashier payment account.'):'';?></span>

            <?php if($this->config->item('enable_drag_drop_deposit_proof') && $this->utils->is_mobile()) :?>

                <div class="deposit-upload">
                    <div class="input-group upload-browse">
                        <label for="file1" class="">
                            <span id="file-label-file1">+</span>
                            <img id="img-preview-file1" style="display:none" />
                        </label>
                        <input type="file" class="txtImage" id="file1" name="file1[]" title="ไม่มีไฟล์ที่เลือก" hidden>
                    </div>

                    <?php if(!$this->config->item('disable_deposit_upload_file_2')) :?>
                        <div class="input-group upload-browse">
                            <label for="file2" class="">
                                <span id="file-label-file2">+</span>
                                <img id="img-preview-file2" style="display:none" />
                            </label>
                            <input type="file" class="txtImage" id="file2" name="file2[]" title="ไม่มีไฟล์ที่เลือก" hidden>
                        </div>
                    <?php endif; ?>
                </div>

            <?php else: ?>

                <div class="input-group col col-xs-6 col-sm-6 col-md-6 upload-browse">
                    <span type="text" class="form-control upload-depo" maxlength="100"><?=lang('File 1')?>:</span>
                    <label class="btn btn-default btn-file">
                        <?= lang('Browse') ?>
                        <input type="file" class="txtImage" id="file1" name="file1[]" title="<?=lang('upload_no_file_tooltip')?>" onchange="getFileData(this)" hidden />
                    </label>
                </div>
                <?php if(!$this->config->item('disable_deposit_upload_file_2')) :?>
                    <div class="input-group col col-xs-6 col-sm-6 col-md-6 upload-browse">
                        <span type="text" class="form-control upload-depo" maxlength="100"><?=lang('File 2')?>:</span>
                        <label class="btn btn-default btn-file">
                            <?= lang('Browse') ?>
                            <input type="file" class="txtImage" id="file2" name="file2[]" title="<?=lang('upload_no_file_tooltip')?>" onchange="getFileData(this)" hidden />
                        </label>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
            <?php if ($this->CI->config->item('enable_hints_for_sexycasino_on_deposit_upload_file')): ?>
                <span id="errfm_txtImage" class="text-danger"><?=lang('If you do not enter funds within 3 minutes, please contact the staff through LINE Add.')?></span>
            <?php endif;?>
        </div>
        <hr />
    </div>
<?php endif; ?>