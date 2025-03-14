<style>

</style>

<!-- confirm_level_maintain_mode_modal -->
<div id="confirm_level_maintain_mode_modal" class="modal fade " role="dialog">
    <div class="modal-dialog">
        <!-- Modal content-->
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">
                    <?= lang('Confirm Change to Level Maintain Mode'); ?>
                </h4>
            </div>
            <div class="modal-body custom-height-modal">
                <div class="row">
                    <div class="col-md-12">
                        <?= lang('Level Maintain Mode. Downgrade setting will be ignored.')?>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <div class="row">
                    <div class="col-md-12">
                        <button type="button" class="btn btn-sm <?=$this->utils->getConfig('use_new_sbe_color') ? 'btn-linkwater' : 'btn-primary' ?> confirm_btn">
                            <i class="fa"></i> <?= lang('Confirm'); ?>
                        </button>

                        <button type="button" class="btn btn-default <?=$this->utils->getConfig('use_new_sbe_color') ? 'btn-scooter' : 'btn-primary' ?> cancel_btn">
                            <i class="fa"></i> <?= lang('lang.cancel'); ?> ( <?= lang('lang.close'); ?> )
                        </button>
                        <input type="hidden" name="isConfirmToDo" value="waiting">
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- confirm_level_downgrade_mode_modal -->
<div id="confirm_level_downgrade_mode_modal" class="modal fade " role="dialog">
    <div class="modal-dialog">
        <!-- Modal content-->
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">
                    <?= lang('Confirm Change to Downgrade setting Mode'); ?>
                </h4>
            </div>
            <div class="modal-body custom-height-modal">
                <div class="row">
                    <div class="col-md-12">
                        <?= lang('Downgrade setting Mode. Level Maintain settings will be ignored.')?>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <div class="row">
                    <div class="col-md-12">
                        <button type="button" class="btn btn-sm <?=$this->utils->getConfig('use_new_sbe_color') ? 'btn-linkwater' : 'btn-primary' ?> confirm_btn" >
                            <i class="fa"></i> <?= lang('Confirm'); ?>
                        </button>

                        <button type="button" class="btn btn-default <?=$this->utils->getConfig('use_new_sbe_color') ? 'btn-scooter' : 'btn-primary' ?> cancel_btn" >
                            <i class="fa"></i> <?= lang('lang.cancel'); ?> ( <?= lang('lang.close'); ?> )
                        </button>
                        <input type="hidden" name="isConfirmToDo" value="waiting">
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function(){



    }); // EOF $(document).ready(function(){...
</script>