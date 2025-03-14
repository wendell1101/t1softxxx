<style>
    .checkbox{
        margin-left: 36px;
    }
    .green {
        color:  green;
    }
    .required_hint{
        position: absolute;
        left: 0;
    }
</style>


<style type="text/css">


.registration-field-note {
    position: relative;
    z-index: 2;
    background: #bf3f4a !important;
    box-shadow: 0px 0px 20px #000;
    top: 0;
    min-height: 47px;
    padding: 10px;
    font-size: 12px;
    width: 100%;
    margin-top: -10px;
    border-radius: 4px;
    color: #fff;
}

.cstm-mod .modal-body .contact-number label.bstselect {
    color: #595959;
    left: 10px;
    margin: 0;
    position: relative;
    top: 2px;
}



select {
    -webkit-appearance: none;
    -moz-appearance: none;
    appearance: none;
    background-image: url('data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAgAAAAIACAYAAAD0eNT6AAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAyNpVFh0WE1MOmNvbS5hZG9iZS54bXAAAAAAADw/eHBhY2tldCBiZWdpbj0i77u/IiBpZD0iVzVNME1wQ2VoaUh6cmVTek5UY3prYzlkIj8+IDx4OnhtcG1ldGEgeG1sbnM6eD0iYWRvYmU6bnM6bWV0YS8iIHg6eG1wdGs9IkFkb2JlIFhNUCBDb3JlIDUuNi1jMTQwIDc5LjE2MDQ1MSwgMjAxNy8wNS8wNi0wMTowODoyMSAgICAgICAgIj4gPHJkZjpSREYgeG1sbnM6cmRmPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5LzAyLzIyLXJkZi1zeW50YXgtbnMjIj4gPHJkZjpEZXNjcmlwdGlvbiByZGY6YWJvdXQ9IiIgeG1sbnM6eG1wPSJodHRwOi8vbnMuYWRvYmUuY29tL3hhcC8xLjAvIiB4bWxuczp4bXBNTT0iaHR0cDovL25zLmFkb2JlLmNvbS94YXAvMS4wL21tLyIgeG1sbnM6c3RSZWY9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC9zVHlwZS9SZXNvdXJjZVJlZiMiIHhtcDpDcmVhdG9yVG9vbD0iQWRvYmUgUGhvdG9zaG9wIENDIChNYWNpbnRvc2gpIiB4bXBNTTpJbnN0YW5jZUlEPSJ4bXAuaWlkOjA5MjY3QjA4RkNERTExRTk4MkM0Q0VBNURGMjM2OEI5IiB4bXBNTTpEb2N1bWVudElEPSJ4bXAuZGlkOjA5MjY3QjA5RkNERTExRTk4MkM0Q0VBNURGMjM2OEI5Ij4gPHhtcE1NOkRlcml2ZWRGcm9tIHN0UmVmOmluc3RhbmNlSUQ9InhtcC5paWQ6MDkyNjdCMDZGQ0RFMTFFOTgyQzRDRUE1REYyMzY4QjkiIHN0UmVmOmRvY3VtZW50SUQ9InhtcC5kaWQ6MDkyNjdCMDdGQ0RFMTFFOTgyQzRDRUE1REYyMzY4QjkiLz4gPC9yZGY6RGVzY3JpcHRpb24+IDwvcmRmOlJERj4gPC94OnhtcG1ldGE+IDw/eHBhY2tldCBlbmQ9InIiPz6qUW39AAAQXklEQVR42uzdW6il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RADG3/gjABABIgDjb/wRAIgAEYDxN/4IAESACMD4G38EACJABGD8jT8CABEgAjD+xh8BgAgQARh/EACIABFg/I0/CABEgAgw/sYfAQAiQAQYf+OPAAARIAKMv/FHAIAIEAHG3/gjAEAEiADjb/wRACACMP7GHwGACBABGH/jjwBABIgAjL/xRwAgAkQAxt/4IwAQASIA42/8EQCIABGA8Tf+CABEgAjA+Bt/BAAiQAQYf+MPAgARIAKMv/EHAYAIEAHG3/iDAEAEiADjb/xBACACRIDxN/4gABABIsD4G38QAIgAEWD8jT8CAESACDD+xh8BACJABBh/448AABEgAoy/8UcAgAgQAcbf+CMAQASIAONv/BEAIAJEgPE3/ggAEAEiwPiDAAARkDACjD8IABABySLA+IMAABGQLAKMPwgAEAHJIsD4gwAAEZAsAow/CAAQAckiwPiDAAARkCwCjD8IABABySLA+IMAABGQLAKMPwgAEAHJIsD4gwAAEZAsAow/CAAQAckiwPiDAAARkCwCjD8IABABySLA+IMAABGQLAKMPwgAEAHJIsD4gwAAEZAsAow/CAAQAckiwPiDAAARkCwCjD8IABABySLA+IMAABGQLAKMPwgAEAHJIsD4gwAAEZAsAow/CAAQAckiwPiDAAARkCwCjD8IABABySLA+IMAABGQLAKMPyS01iWATyLgj3FOShQB1R7jDzktDAYDVwGGToizJc6JSX6/excFQQavtOO/01MdfAkAFqvDsL7J9eWALONf3/lvMP4gAOBAQ5HpewKyPKb1nb+P/UEAgAgw/iAAABFg/EEAACLA+IMAAEMiAow/CAAQARh/EAAgAjD+IABABGD8QQCACMD4gwAAEYDxBwEAIgDjDwIARADGHwQAiACMPwgAEAEYfxAAIAKMPyAAQAQYf0AAgAgw/oAAABFg/AEBACLA+IMAcAlABBh/EACACDD+IAAAEWD8QQAAIsD4gwAARIDxBwEAiADjDwIAEAHGHwQAIAKMPwgAIHUEGH8QACACkkWA8QcBACSLAOMPAgBIFgHGHwQAkCwCjD8IACBZBBh/EABAsggw/iAAgGQRYPxBAADJIsD4gwAAkkWA8QcBACSLAOMPAgBIFgHGHwQAkCwCjD8IACBZBBh/EABAsggw/iAAgGQRYPxBAAAzGuANcV6awa9df83zjT8IAGA2dsY5N87fVvHX/Gucc9pfGxAAwIy8Eee8OHfFGazwr/VwM/zY/78uOwgAYPb2xPlRnIvjbF+Bn39bnG/H+WGc3S43CABgvjwV54w4NzTT+QbBf8XZGOfMOE+7vNAfC4PBwFWAfloT51txrojznThHjfnj6sf7T8Z5JM4f4ux1KUEAAB19rcc5Lc5ZcU6Oc2ycI9r/7b04/26G39n/fJwXm5X/XgJgxta6BJBCHfSt7QHwPQAAIAAAAAEAAAgAAEAAAAACAAAQAACAAAAABAAAIAAAAAEAAAgAAEAAAAACAAAQAACAAAAABAAAIAAAQAAAAAIAABAAAIAAAAAEAAAgAAAAAQAACAAAQAAAAAIAABAAAIAAAAAEAAAgAAAAAQAACAAAQAAAgAAAAAQAACAAAAABAAAIAABAAAAAAgAAEAAAgAAAAAQAACAAAAABAAAIAABAAAAAAgAAEAAAgAAAAAEAAAgAAEAAAAACAAAQAACAAAAABAAAIAAAAAEAAAgAAEAAAAACAAAQAACAAAAABAAAIAAAAAEAAAIAABAAAIAAAAAEAAAgAAAAAQAACAAAQAAAAAIAABAAAIAAAAAEAAAgAAAAAQAACAAAQAAAAAIAABAAACAAAAABAAAIAABAAAAAAgAAEAAAgAAAAAQAACAAAAABAAAIAABAAAAAAgAAEAAAgAAAAAQAACAAAEAAAAACAAAQAACAAAAABAAAIAAAAAEAAAgAAEAAAAACAAAQAACAAAAApuBjAQYAV44CRJR0CIcAAAAASUVORK5CYII=') !important;
    background-position: 92% center !important;
    background-repeat: no-repeat !important;
    background-size: 15px !important;
}


/**
 * workaround default setting
 */

.cstm-mod .modal-body .contact-number input[type="text"].bstselect {
    border-top: 0;
    border-left: 0 !important;
    border-right: 0;
    box-shadow: none;
    border-radius: 0;
    position: absolute;
    left: 0;
    width: 100%;
    background: transparent;
    text-indent: 20px;
    padding-left: 120px;
}

/* from http://player.og.local/upload/themes/stable_center2/styles/base-theme-SexyCasino_Theme57edit.css?v=9.90.01.001-1.000.000.3780 */
input[type="text"],
input[type="password"],
input[type="email"],
select,
select.form-control,
input[type="date"],
input[type="number"],
.deposit-form input[type="text"],
.deposit-form input[name="deposit_amount"],
.deposit-form .dropdown .dropdown-toggle {
    background: transparent;
    box-shadow: none;
    height: 50px;
    left: 0;
    min-width: 100%;
    position: relative;
    text-indent: 23px !important;
    width: 100%;
    background: rgba(0, 0, 0, 0.4);
    border: 1px solid rgba(255, 255, 255, 0.7);
    font-size: 14px;
    color: #fff;
    text-indent: 25px;
    border-radius: 4px;
    outline: none !important;
    padding: 5px 10px !important;
}

/* from http://player.og.local/upload/themes/stable_center2/styles/base-theme-SexyCasino_Theme57edit.css?v=9.90.01.001-1.000.000.3780 */
.cstm-mod.registration-mod .modal-body .contact-number input[type=text].bstselect {
    border: 1px solid rgba(255, 255, 255, 0.7);
    border-radius: 4px;
    text-indent: 120px !important; /* Add important for heigh priority with m site */
}



</style>

<style type="text/css">

/**
 * ".main-wrapper" Mapping To ".registration-mod"
 */
.main-wrapper, .registration-mod {
  width: 100%;
  max-width: 500px;
  /* margin: 80px auto; */ /* Patch for workaround */
}
.registration-tabs ul {
  display: flex !important;
}
.registration-tabs ul li {
  width: 50%;
  flex-grow: 1;
  margin: 0;
}
.registration-tabs ul li a {
  background: #000;
  text-transform: uppercase;
  border: 2px solid;
  border-image-source: -webkit-linear-gradient( -84deg, rgb(254,217,129) 0%, rgb(121,91,39) 43%, rgb(62,42,6) 100%);
  border-image-slice: 1;
  border-radius: 0;
  padding: 0;
  height: 50px;
  line-height: 45px;
}
@media (max-width: 375px) {
  .registration-tabs ul li a {
    font-size: 12px;
  }
}
@media (max-width: 335px) {
  .registration-tabs ul li a {
    font-size: 10px;
  }
}
.registration-tabs ul li a:hover {
  background: none;
}
.registration-tabs ul li:first-child a {
  border-right: 0;
}
.registration-tabs ul li.active a {
  background-color: transparent !important;
  background-image: -moz-linear-gradient( -84deg, rgb(254, 217, 129) 0%, rgb(121,91,39) 43%, rgb(62,42,6) 100%);
  background-image: -webkit-linear-gradient( -84deg, rgb(254,217,129) 0%, rgb(121,91,39) 43%, rgb(62,42,6) 100%);
  background-image: -ms-linear-gradient( -84deg, rgb(254,217,129) 0%, rgb(121,91,39) 43%, rgb(62,42,6) 100%);
}
a .line-img {
  width: 20px;
  margin-right: 5px;
}
a.line-reg {
    color:#FFF;
}

.reg-form-tabs form {
  margin-top: 20px;
}

/**
 * Add "#smsVerifyForm input" for sms-veri dialog.
 */
.reg-form-tabs form input
, .reg-form-tabs form select
, #smsVerifyForm input {
  border: 2px solid !important; /* Add important for heigh priority with m site */
  border-image-source: -webkit-linear-gradient( -84deg, rgb(254,217,129) 0%, rgb(121,91,39) 43%, rgb(62,42,6) 100%) !important; /* Add important for heigh priority with m site */
  border-image-slice: 1 !important; /* Add important for heigh priority with m site */
  text-indent: 0;
  padding: 0 20px !important; /* Add important for heigh priority */
}

/**
* ".reg-form-tabs form .form-group" mapping to ".reg-form-tabs form .field_required"
*/
.reg-form-tabs form .form-group
, .reg-form-tabs form .field_required {
  position: relative;
}

/**
 * ".reg-form-tabs form .form-group .required" mapping to ".reg-form-tabs form .field_required .required_hint .required".
 */
.reg-form-tabs form .form-group .required
, .reg-form-tabs form .field_required .required_hint .required {
  color: #a00000;
  position: absolute;
  top: 12px;
  left: 10px;
  z-index: 1;
  font-size: 20px;
}
/* .contact-number */ contact-number
.reg-form-tabs .contact-number {
  display: flex;
}

/**
 * Mapping to ".reg-form-tabs .contact-number .btn-group button.dropdown-toggle".
 */
.reg-form-tabs .contact-number button.dropdown-toggle
, .reg-form-tabs .contact-number .btn-group button.dropdown-toggle {
  background: #000 !important; /* Add important for heigh priority with m site */
  border: 2px solid !important; /* Add important for heigh priority with m site */
  border-image-source: -webkit-linear-gradient( -84deg, rgb(254,217,129) 0%, rgb(121,91,39) 43%, rgb(62,42,6) 100%) !important; /* Add important for heigh priority with m site */
  border-image-slice: 1 !important; /* Add important for heigh priority with m site */
  height: 50px;
  padding: 0 20px;
}

/**
 * Mapping to ".reg-form-tabs .contact-number input[name="contactNumber"]".
 */
.reg-form-tabs .contact-number .num-field
, .reg-form-tabs .contact-number input[name="contactNumber"] {
  flex-grow: 1;
  margin-left: 10px;
  position: relative;
}

/* EOF .contact-number */

.reg-form-tabs .sms-code {
  display: flex;
}
.reg-form-tabs .sms-code .sms-veri-field{
  flex-grow: 1;
  position: relative;
}

/**
 * Mapping to #send_sms_verification
 */
.reg-form-tabs .sms-code .sms-send
, .reg-form-tabs #send_sms_verification {
  width: 150px;
  border-radius: 0;
  background-color: transparent !important;
  background-image: -moz-linear-gradient( -84deg, rgb(254, 217, 129) 0%, rgb(121,91,39) 43%, rgb(62,42,6) 100%) !important;
  background-image: -webkit-linear-gradient( -84deg, rgb(254,217,129) 0%, rgb(121,91,39) 43%, rgb(62,42,6) 100%) !important;
  background-image: -ms-linear-gradient( -84deg, rgb(254,217,129) 0%, rgb(121,91,39) 43%, rgb(62,42,6) 100%) !important;
}

/**
 * Mapping to ".btn.btn-primary"
 * Add "#smsVerifyForm .btn.btn-primary" for sms-veri dialog.
 */
.reg-form-tabs .register-btn
, .reg-form-tabs .btn.btn-primary
, #smsVerifyForm .btn.btn-primary {
  width: 100%;
  border-radius: 0;
  background-color: transparent !important;
  background-image: -moz-linear-gradient( -84deg, rgb(254, 217, 129) 0%, rgb(121,91,39) 43%, rgb(62,42,6) 100%) !important;
  background-image: -webkit-linear-gradient( -84deg, rgb(254,217,129) 0%, rgb(121,91,39) 43%, rgb(62,42,6) 100%) !important;
  background-image: -ms-linear-gradient( -84deg, rgb(254,217,129) 0%, rgb(121,91,39) 43%, rgb(62,42,6) 100%) !important;
}

/**
 * workaround
 */

#smsVerifyForm .btn.btn-primary {
    color:#FFF;
}
.reg-form-tabs form .field_required .required_hint .required {
    top:auto;
}

.reg-form-tabs .contact-number input[name="contactNumber"] {
  border: 2px solid !important;
  border-image-source: -webkit-linear-gradient( -84deg, rgb(254,217,129) 0%, rgb(121,91,39) 43%, rgb(62,42,6) 100%) !important;
  border-image-slice: 1 !important;
  background-color: #FFF !important;
  /* text-indent: 120px !important; */
  padding: 0 20px !important;
  top:0;
}


button#send_sms_verification {
    position: absolute;
    right: 0px;
    top: 0px;
    bottom: 0px;
    border-top-width: 0px;
    border-right-width: 0px;
    border-bottom-width: 0px;
    border-left-width: 0px;
    height: 50px;
    width: 100px;
}

.reg-form-tabs form .form-group .required
, .reg-form-tabs form .field_required .required_hint .required {
  color: #a00000;
  position: absolute;
  top: -2px; /* workaround */
  left: 20px;
  z-index: 1;
  font-size: 20px;
}

.reg-form-tabs form input
, .reg-form-tabs form select
, #smsVerifyForm input {
  background-color: #FFF !important;
}

/** ---- */

.registration-tabs {
    margin-top: 16px;
}

</style>

<script>
    $(document).ready(function(){

        // move registration-tabs to bottom.
        // $( ".col-register-now" ).after( '<div class="col-md-6 col-lg-12 col-register-now-fixed"></div>' );
        // $('.registration-tabs').prependTo(".col-register-now-fixed");

        var tplFile = 'views/resources/common/auth/register_mobile4sexycasino_line';

        $('body').on('shown.bs.select refreshed.bs.select', '#dialing_code', function(e){
            $(e.target).data('adjust-by-329', tplFile)
                        .attr('data-adjust-by-329',tplFile);
            $('input[name="contactNumber"]').data('adjust-by-319', tplFile);
            var css = 'width: 120px !important;';
            $('.contact-number .btn-group.bootstrap-select')
                .attr('style',css)
                .css({
                    width:'120px !important' /* Add for heigh priority with m site */
            });
        });
        $('.selectpicker').selectpicker('refresh');



        if($('#dialing_code').length > 0){
            // visitable, offset placehold
            $('input[name="contactNumber"]').data('adjust-by-319', tplFile);
            $('input[name="contactNumber"]').css({ 'text-indent': '120px !important'
                                                    , 'margin-left': '0px'
                                                    });

        }else{
            $('input[name="contactNumber"]').data('adjust-by- 325', tplFile);
            $('input[name="contactNumber"]').css({ 'text-indent': ''
                                                    , 'margin-left': '0px'
                                                    });
        }

        if($('#send_sms_verification').length > 0){
            $('#send_sms_verification').data('adjust-by-328', tplFile);
            $('#send_sms_verification').css({
                                            top:''
                                            , right:''
                                            , width: '100px'
                                        });
        }
        if($('button[type="submit"]').length > 0){
            $('input[name="contactNumber"]').data('adjust-by-319', tplFile);
            $('button[type="submit"]').css({    'margin-left':'' // clear
                                            , height:'40px'
                                        });
        }

    });
</script>

<div class="register-form-container">

    <?php
    $registration_mod_prepend_html_list = $this->utils->getConfig('registration_mod_prepend_html_list');
    $tpl_filename = basename(__FILE__, '.php'); // register_mobile4sexycasino_line
    if( ! empty($registration_mod_prepend_html_list[ $tpl_filename ]) ): ?>
        <?=$registration_mod_prepend_html_list[$tpl_filename]?>
    <?php endif; // EOF if( ! empty($registration_mod_prepend_html_list[$tpl_filename) ]) ):.... ?>

    <div class="cstm-mod registration-mod" role="document" >
        <div class="modal-content">

            <div class="modal-header text-center">
                <h4 class="modal-title f24" id="myModalLabel"><?php echo lang('Register Your Account'); ?></h4>
            </div>

            <div class="modal-body reg-form-tabs">
                <?php if ($this->utils->getConfig('line_credential') && $this->utils->getConfig('enable_line_registration_in_mobile')):?>
                    <div class="registration-tabs">
                        <ul class="nav nav-pills nav-justified">
                            <li class="nav-item active">
                                <a class="nav-link normal-reg"><?=lang('Registration')?></a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link line-reg">
                                <img class="line-img" src="/images/line.svg" alt="">
                                <?=lang('Line Registration')?></a>
                            </li>
                        </ul>
                    </div>
                <?php endif;?>
                    <form action="<?=site_url('player_center/postRegisterPlayer')?>"
                        data-post_register_player-action="<?=site_url('player_center/postRegisterPlayer')?>"
                        data-post_register_line-action="<?=site_url('iframe/auth/line_register')?>"
                    method="post" id="registration_form">
                        <?php if($this->utils->getConfig('line_credential')):?>
                            <input type="hidden" name="line_reg" value="0"/>
                        <?php endif;?>
                        <?php if($this->utils->getConfig('gotoAddBankAfterRegister')):?>
                            <input type="hidden" value="<?=$this->utils->getPlayerBankAccountUrl('triggerAddBank')?>" name="goto_url" />
                        <?php endif;?>
                        <?php if(!$displayAffilateCode && (!empty($tracking_code) || !empty($tracking_source_code))){
                            $has_input_tracking_code = true;
                        }else{
                            $has_input_tracking_code = false;
                        } ?>
                        <?php if(!$displayAffilateCode && (!empty($tracking_code) || !empty($tracking_source_code))): ?>
                            <input type="hidden" value="<?=set_value('tracking_code', $tracking_code)?>" name="tracking_code"/>
                            <input type="hidden" value="<?=set_value('tracking_source_code', $tracking_source_code)?>" name="tracking_source_code"/>
                        <?php endif ?>
                        <?php if(!$displayAgencyCode && (!empty($agent_tracking_code) || !empty($agent_tracking_source_code))): ?>
                            <input type="hidden" value="<?=set_value('agent_tracking_code', $agent_tracking_code)?>" name="agent_tracking_code"/>
                            <input type="hidden" value="<?=set_value('agent_tracking_source_code', $agent_tracking_source_code)?>" name="agent_tracking_source_code"/>
                        <?php endif ?>
                        <?php if(!$displayReferralCode && !empty($referral_code)): ?>
                            <input type="hidden" value="<?=set_value('invitationCode', $referral_code)?>" name="invitationCode"/>
                        <?php endif ?>

                        <?php if($this->utils->isEnabledFeature('enable_income_access') && isset($btag) && !empty($btag)) : ?>
                            <input type="hidden" name="btag" value="<?=set_value('btag', $btag)?>" />
                        <?php endif; ?>

                        <?php if ($this->utils->isEnabledFeature('enable_player_register_form_keep_error_prompt_msg')) : ?>
                            <input type="hidden" name="keepErrorMsg" value="enabled" />
                        <?php endif; ?>

                        <?php if($is_iovation_enabled):?>
                            <input type="hidden" name="ioBlackBox" id="ioBlackBox"/>
                        <?php endif; ?>
                        <div class="row">

                            <?php
                                $item_sequence = $this->utils->getConfig('player_register_item_sequence_mobile');
                                foreach ($item_sequence as $item) {
                                    switch ($item) {
                                        case 'USERNAME': ?>
                                            <?php // USERNAME?>
                                            <div class="col-md-6 col-lg-12">
                                                <div class="form-group form-inline relative field_required">
                                                    <?= $require_display_symbol?>
                                                    <label><i class="icon-user"></i></label>
                                                    <input type="text" class="form-control registration-field fcname" name="username" id="username" placeholder="<?php echo lang('Username');?> <?=$require_placeholder_text?>"
                                                    onKeyUp="Register.lowerCase(this)"
                                                    onfocus="return Register.validateUsernameRequirements(this.value)"
                                                    value="<?=set_value('username')?>">
                                                </div>
                                                <div class="fcname-note registration-field-note hide mb20">
                                                    <p class="pl15 mb0">
                                                        <i id="username_len" class="icon-warning red f16 mr5"></i> <?php echo lang('Username') .' '. sprintf(lang('validation.lengthRangeStandard'), $min_username_length, $max_username_length);?>
                                                    </p>
                                                    <?php if (!empty($this->utils->isRestrictUsernameEnabled())) : ?>
                                                        <p class="pl15 mb0">
                                                            <i id="username_charcombi" class="icon-warning red f16 mr5"></i> <?=lang('validation.validateUsername01')?>
                                                        </p>
                                                    <?php endif; ?>
                                                    <p class="pl15 mb0" id="username_exist_checking" style="display: none;">
                                                        <i class="f16 mr5"></i> <?=lang('validation.availabilityUsername_checking')?>
                                                    </p>
                                                    <p class="pl15 mb0" id="username_exist_failed">
                                                        <i class="f16 mr5"></i> <?=lang('validation.availabilityUsername_2')?>
                                                    </p>
                                                    <p class="pl15 mb0" id="username_exist_available" style="display: none;">
                                                        <i class="icon-checked green f16 mr5"></i> <?=lang('validation.availabilityUsername_available')?>
                                                    </p>
                                                    <p class="pl15">
                                                        <i id="username_lan" class="icon-checked f16 mr5" style="visibility: hidden;"></i> <?php echo lang('username.lan')?>
                                                    </p>
                                                </div>
                                            </div>
                                            <?php break;

                                        case 'PASSWORD': ?>
                                                <?php // PASSWORD?>
                                                <div class="col-md-6 col-lg-12">
                                                    <div class="form-group form-inline relative field_required">
                                                        <?= $require_display_symbol?>
                                                        <label><i class="icon-pass"></i></label>
                                                        <input type="password" class="form-control registration-field fcpass" name="password" id="password" onfocus="return Register.validatePasswordRequirements(this.value)"  oninput="return Register.validatePasswordRequirements(this.value)" placeholder="<?php echo lang('Password'); ?> <?=$require_placeholder_text?>" value="<?=set_value('password')?>">
                                                    </div>
                                                    <div class="fcpass-note registration-field-note hide mb20">
                                                        <p class="pl15 mb0"><i id="password_len" class="icon-warning red f16 mr5"></i> <?php echo lang('Password') .' '. sprintf(lang('validation.lengthRangeStandard'), $min_password_length, $max_password_length) . ' ' .lang('validation.lengthRangeContent');?></p>
                                                        <p class="pl15 mb0"><i id="password_regex" class="icon-warning red f16 mr5"></i> <?=lang('validation.contentPassword01')?></p>
                                                        <p class="pl15"><i id="password_not_username" class="icon-warning red f16 mr5"></i> <?=lang('validation.contentPassword02')?></p>
                                                    </div>
                                                </div>
                                                <?php // CONFIRM PASSWORD?>
                                                <div class="col-md-6 col-lg-12">
                                                    <div class="form-group form-inline relative field_required">
                                                        <?= $require_display_symbol?>
                                                        <label><i class="icon-pass"></i></label>
                                                        <input type="password" class="form-control registration-field fccpass" name="cpassword" onfocus="return Register.validateConfirmPassword(this.value)"  oninput="return Register.validateConfirmPassword(this.value)" placeholder="<?php echo lang('Confirm Password'); ?> <?=$require_placeholder_text?>" value="<?=set_value('cpassword')?>">
                                                    </div>
                                                    <div class="fccpass-note registration-field-note hide mb20">
                                                        <p class="pl15 mb0"><i id="cpassword_reenter" class="icon-warning red f16 mr5"></i> <?=lang('validation.retypePassword')?></p>
                                                    </div>
                                                </div>
                                            <?php break;
                                        case 'EMAIL': ?>
                                                    <?php
                                                    if (in_array('email', $visibled_fields)) {
                                                        $email_required = $registration_fields['Email']['required'];
                                                        // EMAIL?>
                                                        <div class="col-md-6 col-lg-12">
                                                            <div class="form-group form-inline relative <?php if ($registration_fields['Email']['required'] == Registration_setting::REQUIRED) {
                                                            echo 'field_required';
                                                        } ?> ">
                                                                <?php if ($registration_fields['Email']['required'] == Registration_setting::REQUIRED) {?>
                                                                    <?= $this->registration_setting->displaySymbolHint($registration_fields['Email']["field_name"])?>
                                                                <?php } ?>
                                                                <label><i class="glyphicon glyphicon-envelope"></i></label>
                                                                <input type="text" class="form-control registration-field fcemail" name="email" id="email" data-validateRequired="<?= ($email_required == Registration_setting::REQUIRED) ? 0 : 1 ?>" placeholder="<?php echo lang('Email Address'); ?> <?= $this->registration_setting->displayPlaceholderHint($registration_fields['Email']["field_name"])?>" value="<?=set_value('email')?>" onfocus="return Register.validateEmail(this.value)" oninput="return Register.validateEmail(this.value)">
                                                            </div>
                                                            <div class="fcemail-note registration-field-note hide mb20">
                                                                <p class="pl15 mb0"><i id="email_required" class="registration-field-required-icon icon-warning red f16 mr5"></i> <?=lang('validation.requiredEmail')?></p>
                                                            </div>
                                                        </div>
                                                    <?php
                                                    }?>
                                            <?php break;
                                        case 'REGISTRATION_FIELDS': ?>
                                                <?php // REGISTRATION FIELDS?>
                                                <?php include_once VIEWPATH . '/resources/common/includes/registration_fields.php'; ?>

                                                <?php if ($this->operatorglobalsettings->getSettingJson('registration_captcha_enabled')):?>
                                                    <div class="col-md-6 col-lg-12">
                                                        <div class="form-group form-inline relative">
                                                            <label><i class="glyphicon glyphicon-qrcode"></i></label>
                                                            <input required name='captcha' id='captcha' type="text" class="form-control registration-field fcrecaptcha" placeholder="<?php echo lang('label.captcha'); ?> <?=$require_placeholder_text?>" style="width:60%">
                                                            <i class="fa fa-refresh" style="cursor:pointer; float: right; font-size: 1.4em; color: #888; margin-left: 3px; " aria-hidden="true" onclick="refreshCaptcha()"></i>
                                                            <img class="captcha" id='image_captcha' src='<?php echo site_url('/iframe/auth/captcha?' . random_string('alnum')); ?>' onclick="refreshCaptcha()">
                                                        </div>
                                                        <div class="fcrecaptcha-note registration-field-note hide mb20">
                                                            <p class="pl15 mb0">
                                                                <i id="referral_code" class="registration-field-required-icon icon-warning red f16 mr5"></i>
                                                                <?=lang('captcha.required')?>
                                                            </p>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>
                                                <?php
                                                    /// The related params,
                                                    // - $has_input_tracking_code
                                                    $_viewer['register_mobile4sexycasino_line'] = 1;
                                                    include_once VIEWPATH . '/resources/common/includes/show_aff_tracking_code_field.php';
                                                ?>
                                            <?php break;
                                        case 'COMMUNICATION_PREFERENCES': ?>
                                                <?php // COMMUNICATION PREFERENCES?>
                                                <?php if ($this->utils->isEnabledFeature('enable_communication_preferences') && !empty($this->utils->getConfig('communication_preferences')) && $registration_fields['Player Preference']['visible'] == Registration_setting::VISIBLE): ?>
                                                <div class="col-md-6 col-lg-12">
                                                    <div class="checkbox pr10 reg-communication_preference-field">
                                                        <p><?=sprintf(lang('pi.player_pref.hint1'), lang('pi.player_pref_custom_name'))?></p>
                                                        <p><?=lang('pi.player_pref.hint2')?></p>
                                                    </div>
                                                    <?php
                                                    $config_preferences = $this->utils->getConfig('communication_preferences');
                                                    ?>
                                                    <div class="checkbox pl10 pr10 comm_pref_mob_reg_items">
                                                        <?php foreach ($config_preferences as $key => $config_preference): ?>
                                                            <?php
                                                            $player_pref_key = 'Player Preference '.lang($config_preference);
                                                            if ($registration_fields[$player_pref_key]['visible'] != Registration_setting::VISIBLE) {
                                                                continue;
                                                            }
                                                            ?>
                                                        <?php $genPlayerFromKet = 'communication_preferences_' . $key ?>
                                                        <input type="checkbox" name="pref-data-<?=$key?>" id="pref-data-<?=$key?>" value="true" <?=set_value($genPlayerFromKet, $this->CI->config->item($genPlayerFromKet, 'player_form_registration')) ? 'checked="checked"' : ''?>/>
                                                        <div for="pref-data-<?=$key?>" class="lh24">
                                                            <?=lang($player_pref_key)?>
                                                        </div>
                                                        <?php endforeach ?>
                                                    </div>
                                                </div>
                                                <?php endif; ?>
                                            <?php break;
                                        case 'TERMS_AND_CONDITIONS': ?>
                                            <?php // TERMS AND CONDITIONS?>
                                            <?php if ($registration_fields['At Least 18 Yrs. Old and Accept Terms and Conditions']['visible'] == Registration_setting::VISIBLE): ?>
                                            <div class="col-md-6 col-lg-12">
                                                <div class="checkbox pl10 pr10 reg-age-terms-field">
                                                    <div class="form-group">
                                                        <input type="checkbox" class="registration-field" name="terms" id="terms"
                                                            onfocus="return Register.validateTerms(this.checked)" onclick="return Register.validateTerms(this.checked)"
                                                            data-validateRequired="undefined"
                                                        >
                                                        <div for="terms" class="lh24">
                                                        <?=$this->utils->renderLangWithReplaceList('register.18age.hint',[
                                                            '{{age_limit}}' => $age_limit,
                                                            '{{web_user_terms_url}}' => $web_user_terms_url,
                                                            '{{web_privacy_policy_url}}' => $web_privacy_policy_url,
                                                        ]); ?>
                                                        </div>
                                                    </div>

                                                    <div class="registration-field-note hide mb20 fterms">
                                                        <p class="mb0">
                                                            <i class="registration-field-required-icon icon-warning red f16 mr5 terms_required"></i>
                                                            <?=sprintf(lang('formvalidation.required'), $this->utils->renderLang('reminder.age.limit', ["$age_limit"]))?>
                                                        </p>
                                                    </div>

                                                </div>
                                            </div>
                                            <?php endif; ?>
                                            <?php break;
                                        case 'REGISTER_BTN': ?>
                                            <div class="col-md-6 col-lg-12 col-register-now">
                                                <div class="error-message <?=(! empty($this->session->userdata('result'))) ? '' : 'hide'?>">
                                                    <?=$this->session->userdata('message')?>
                                                </div>

                                                <?php // REGISTER BTN ?>
                                                <div>
                                                    <button type="submit" class="btn btn-primary" style="margin-left: 16px;"><?= lang('Register Now'); ?></button>
                                                </div>
                                            </div>
                                            <?php break;
                                        case 'HAVE_ACCOUNT': ?>
                                            <?php // HAVE ACCOUNT?>
                                            <div class="col-md-6 col-lg-12">
                                                <p class="pl10 pr10 pt20" style="margin-top: 15px">
                                                    <?=sprintf(lang('Already have account and Please Login'), site_url('iframe/auth/login'))?>
                                                </p>
                                            </div>
                                            <?php break;
                                        case 'LIVE_CHAT': ?>
                                            <?php // LIVE CHAT?>
                                            <div class="col-md-6 col-lg-12">
                                                <div class="form-group form-link live-chat-box">
                                                    <a id="contact_customer_service" href="javascript:void(0)" onclick="<?=$this->utils->getLiveChatOnClick();?>"><i class="icon-bubble"></i> <?=lang('Live Chat')?></a>
                                                </div>
                                            </div>
                                            <?php break;
                                        case 'CUSTOM_BLOCK' : ?>
                                            <div class="col-md-12 col-lg-12">
                                            <?php // CUSTOM BLOCK
                                                echo $this->CI->config->item('register_form_custom_block');
                                            ?>
                                            </div>
                                            <?php break;
                                        default:
                                            break;
                                    }
                                } // EOF foreach
                            ?>
                        </div>
                    </form>

            </div>

        </div>
    </div>
</div>

<!-- The Modal -->
<div class="modal fade" id="summaryModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header" style="display: none;"></div>
            <div class="modal-body"></div>
            <div class="modal-footer">
            <button type="button" id="close_btn" class="btn btn-primary btn-close no" data-dismiss="modal"><?= lang('lang.cancel') ?></button>
            </div>
        </div>
    </div>
</div>

<?php if ( ! isset($preview) || ! $preview): ?>
<script>
var modal = $('#summaryModal').modal({
    "backdrop": "static",
    "keyboard": false,
    "show": false
});

modal.off('show.bs.modal').on('show.bs.modal', function(){
    $('button.btn-close', modal).prop('disabled', true).attr('disabled', 'disabled').addClass('disabled');
});

modal.off('hide.bs.modal').on('hide.bs.modal', function(){
    $('.modal-body', modal).html('');
});

var display_registration_announcement = '<?=$this->utils->getConfig('display_registration_announcement')?>';

    if (display_registration_announcement) {
        //clearCookie('registration_announcement');
        var registration_announcement = getCookie('registration_announcement');
        if (registration_announcement == '' || registration_announcement == null) {
            setCookie('registration_announcement','true',1);
            MessageBox.info(
                "<?=lang('registration_announcement.message')?>", '<?=lang('lang.info')?>', function(){
                },
                [{
                    'text': '<?=lang('lang.close')?>',
                    'attr':{
                        'class':'btn btn-info',
                        'data-dismiss':"modal"
                    }
                }]
            );
        }
    }

    //设置cookie
    function setCookie(cname, cvalue, exdays) {
        var d = new Date();
        d.setTime(d.getTime() + (exdays * 24 * 60 * 60 * 1000));
        var expires = "expires=" + d.toUTCString();
        document.cookie = cname + "=" + cvalue + "; " + expires;
    }
    //获取cookie
    function getCookie(cname) {
        var name = cname + "=";
        var ca = document.cookie.split(';');
        for (var i = 0; i < ca.length; i++) {
            var c = ca[i];
            while (c.charAt(0) == ' ') c = c.substring(1);
            if (c.indexOf(name) != -1) return c.substring(name.length, c.length);
        }
        return "";
    }
    //清除cookie
    function clearCookie(name) {
        setCookie(name, "", -1);
    }

</script>
<?php endif ?>