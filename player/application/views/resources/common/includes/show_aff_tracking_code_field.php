<?php
    /// display only, depend by the elements, input[name="tracking_code"] and input[name="tracking_source_code"] -->

?>

<? if( $has_input_tracking_code
    && $this->utils->getConfig('show_aff_tracking_code_field')
    && ( ! empty($_viewer['register_mobile']) )
): ?>
    <div class="col-md-6 col-lg-4" data-view="show_aff_tracking_code_field" data-viewer="register_mobile">
            <div class="form-group form-inline relative ">
                <span class="custom-label">
                    <?=lang('Tracking Code')?>
                </span>
            </div>
            <div class="form-group form-inline relative ">
                <input type="text" readonly class="form-control" value="<?=$tracking_code?>" >
            </div>
    </div>
<? endif; ?>

<? if( $has_input_tracking_code
    && $this->utils->getConfig('show_aff_tracking_code_field')
    && ( ! empty($_viewer['register_mobile4sexycasino_line']) )
): ?>
    <div class="col-md-6 col-lg-4 row-fluid" data-view="show_aff_tracking_code_field" data-viewer="register_mobile4sexycasino_line">
        <div class="form-group form-inline relative ">
            <div class="col-md-6 col-lg-12">
                <span class="custom-label">
                    <?=lang('Tracking Code')?>
                </span>
            </div>
            <div class="col-md-6 col-lg-12">
                <input readonly class="form-control registration-field" value="<?=$tracking_code?>" >
            </div>
        </div>
    </div>
<? endif; ?>


<? if( $has_input_tracking_code
    && $this->utils->getConfig('show_aff_tracking_code_field')
    && ( ! empty($_viewer['register_recommended']) )
): ?>
    <div class="col-md-6 col-lg-4 row-fluid" data-view="show_aff_tracking_code_field" data-viewer="register_recommended">
        <div class="form-group form-inline relative field_required">
            <?= $require_display_symbol?>
            <div class="col-md-6">
                <span class="custom-label">
                    <?=lang('Tracking Code')?>
                </span>
            </div>

            <div class="col-md-6">
                <input readonly type="text" class="form-control" value="<?=$tracking_code?>" >
            </div>
        </div>
    </div>
<? endif; ?>

<? if( $has_input_tracking_code
    && $this->utils->getConfig('show_aff_tracking_code_field')
    && ( ! empty($_viewer['register_template_4']) )
): ?>
<style>
.width_inherit {
    width: inherit;
}
</style>
    <div class="col-md-6 col-lg-4 row-fluid" data-view="show_aff_tracking_code_field" data-viewer="register_template_4">
        <div class="form-group form-inline relative field_required">
            <?= $require_display_symbol?>
            <div class="col-md-12">
                <span>
                    <?=lang('Tracking Code')?>
                </span>
            </div>

            <div class="col-md-12">
                <input readonly class="width_inherit" value="<?=$tracking_code?>" >
            </div>
        </div>
    </div>
<? endif; ?>


<? if( $has_input_tracking_code
    && $this->utils->getConfig('show_aff_tracking_code_field')
    && ( ! empty($_viewer['register_recommended4sexycasino_line']) )
): ?>

    <div class="col-md-6 col-lg-4 row-fluid" data-view="show_aff_tracking_code_field" data-viewer="register_recommended4sexycasino_line">
        <div class="form-group form-inline relative field_required">
        <?= $require_display_symbol?>
            <div class="col-md-5">
                <label>
                    <?=lang('Tracking Code')?>
                </label>
            </div>

            <div class="col-md-6">
                <input readonly class="form-control" value="<?=$tracking_code?>" >
            </div>
        </div>
    </div>
<? endif; ?>
