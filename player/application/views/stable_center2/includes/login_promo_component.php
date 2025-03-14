<?php
$is_mobile = $this->utils->is_mobile();
?><?php if( ! $is_mobile ): // PC ?>
<!-- promo in pc -->
<div class="reg__left__wrapper" data-ticket="OGP-27932">
        <h3><?=lang('lang.login_mod_promo_title_html_in_pc')?></h3>
        <div class="img__center"></div>
        <div class="welcome__text"><?=lang('lang.login_mod_promo_welcome_html')?></div>
        <div class="gamling__license__wrapper">
            <div class="gambling__text">
                <?=lang('lang.login_mod_promo_gambling_html_in_pc')?>
            </div>
            <div class="antillephone__wrapper"></div>
        </div>
    </div>
<?php endif; ?>
<?php if( $is_mobile ): // Mobile ?>
    <!-- promo in mobi -->
    <div class="reg-left-col" data-ticket="OGP-27932">
        <div class="icon-close" style=""></div>
        <h3 class="bonus-h3"><?=lang('lang.login_mod_promo_title_html_in_mobi')?></h3>
        <div class="license-wrapper">
            <div class="license-text">
                <p class="text-gambling"><?=lang('lang.login_mod_promo_gambling_html_in_mobi')?></p>
            </div>
            <div class="license-regulate">
                <p class="text-regulate"><?=lang('lang.login_mod_promo_regulated_by_in_mobi')?></p>
                <div class="license-logo"></div>
            </div>
        </div>
    </div>
<?php endif; ?>