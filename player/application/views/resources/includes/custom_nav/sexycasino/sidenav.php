    <!-- use_custom_hamburger_menu -->
    <!-- Burger Icon Trigger -->
    <?php
        $lang_prefix = '';
        if ($html_lang_code == 'en') {
            $lang_prefix = $html_lang_code.'/';
        }
    ?>
    <a href="javascript:void(0)" class="sidenav-trigger"><i class="hamburder__icon"></i></a>

    <!-- Header Navigation -->
    <div id="header__navigation">
        <nav id="sidebar" class="new">

            <div class="side__t">
                <div class="c__station active">

                    <span class="flag__icon__wrapper">
                        <img src="en-flag-icon.png?v2"> En
                    </span>

                    <span class="flag__icon__wrapper">
                        <img src="th-flag-icon.png?v2"> Thai
                    </span>

                </div>

                <div class="close__sidebar cl-frc">
                    <img
                        src="<?=$this->utils->getPlayerCmsUrl('/resources/css/sexycasino/x-close.png');?>">
                    <span><?=lang('lang.close')?></span>
                </div>
            </div>

            <div class="login__reg__wrapper">
                <div id="_player_login_area" class="loginbox">
                    <div class="mobile-log">
                        <span>
                            <a href="/iframe/auth/login" class="login__btn"><?=lang('sidemenu.login')?></a>
                            <a href="/player_center/iframe_register" class="reg__btn"><?=lang('sidemenu.register')?></a>
                        </span>
                    </div>
                </div>
            </div>

            <div class="side__c">
                <div class="menu__group">
                    <ul class="hammenu">
                        <li>
                            <a class="menu__item"
                                href="<?=$this->utils->getSystemUrl('m', $lang_prefix)?>">
                                <p><?=lang('sidemenu.home')?>
                                </p>
                            </a>
                        </li>
                        <!-- slot -->
                        <li>
                            <a class="menu__item"
                                href="<?=$this->utils->getSystemUrl('m', $lang_prefix.'slots?prv=quickspin');?>">
                                <p><?=lang('sidemenu.slots')?>
                                </p>
                            </a>
                            <span class="arrow">
                                <img
                                    src="<?=$this->utils->getSystemUrl('m', 'wp-content/themes/s8aff/includes/images/header/menu/white.png');?>">
                            </span>
                            <div class="submenu">
                                <ul class="menu-list">
                                    <!-- <li>
                                        <a href="<?=$this->utils->getSystemUrl('m', $lang_prefix.'slots?prv=quickspin');?>"
                                            class="link">
                                            <img src="<?=$this->utils->getSystemUrl('m', 'includes/images/header/menu/slots/quickspin.png?v2');?>"
                                                class="icon-prv" alt="">
                                            <h4><?=lang('Quickspin')?>
                                            </h4>
                                        </a>
                                    </li> -->
                                    <li>
                                        <a href="<?=$this->utils->getSystemUrl('m', $lang_prefix.'slots?prv=pragmatic');?>"
                                            class="link">
                                            <img src="<?=$this->utils->getSystemUrl('m', 'includes/images/header/menu/slots/pragmatic.png?v2');?>"
                                                class="icon-prv" alt="">
                                            <h4><?=lang('Pragmatic Pay')?>
                                            </h4>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="<?=$this->utils->getSystemUrl('m', $lang_prefix.'slots?prv=pgsoft');?>"
                                            class="link">
                                            <img src="<?=$this->utils->getSystemUrl('m', 'wp-content/uploads/2021/03/PG-gold.png');?>"
                                                class="icon-prv" alt="">
                                            <h4><?=lang('PGSoft')?>
                                            </h4>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="<?=$this->utils->getSystemUrl('m', $lang_prefix.'slots?prv=png');?>"
                                            class="link">
                                            <img src="<?=$this->utils->getSystemUrl('m', 'includes/images/header/menu/slots/playngo.png?v2');?>"
                                                class="icon-prv" alt="">
                                            <h4><?=lang('Play N Go')?>
                                            </h4>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="<?=$this->utils->getSystemUrl('m', $lang_prefix.'slots?prv=genesis');?>"
                                            class="link">
                                            <img src="<?=$this->utils->getSystemUrl('m', 'includes/images/header/menu/slots/genesis.png');?>"
                                                class="icon-prv" alt="">
                                            <h4><?=lang('Genesis')?>
                                            </h4>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="<?=$this->utils->getSystemUrl('m', $lang_prefix.'slots?prv=habanero');?>"
                                            class="link">
                                            <img src="<?=$this->utils->getSystemUrl('m', 'includes/images/header/menu/slots/habanero.png?v=1');?>"
                                                class="icon-prv" alt="">
                                            <h4><?=lang('Habanero')?>
                                            </h4>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="<?=$this->utils->getSystemUrl('www', $lang_prefix.'slots?prv=astro_tech');?>"
                                            class="link">
                                            <img src="<?=$this->utils->getSystemUrl('www', 'includes/images/sexy/Astrotech-miniicon.png');?>"
                                                class="icon-prv" alt="">
                                            <h4><?=lang('Astro Tech')?>
                                            </h4>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="<?=$this->utils->getSystemUrl('m', $lang_prefix.'slots?prv=elysium');?>"
                                            class="link">
                                            <img src="<?=$this->utils->getSystemUrl('m', 'includes/images/home/topgame17/logo_prv/elysium_gold.png?v=1');?>"
                                                class="icon-prv" alt="">
                                            <h4><?=lang('Elysium')?>
                                            </h4>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="<?=$this->utils->getSystemUrl('m', $lang_prefix.'slots?prv=netent');?>"
                                            class="link">
                                            <img src="<?=$this->utils->getSystemUrl('m', 'includes/images/header/menu/slots/netent.png?v2');?>"
                                                class="icon-prv" alt="">
                                            <h4><?=lang('Netent')?>
                                            </h4>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="<?=$this->utils->getSystemUrl('www', $lang_prefix.'slots?prv=evoplay');?>"
                                            class="link">
                                            <img src="<?=$this->utils->getSystemUrl('www', 'wp-content/uploads/2021/06/evo_play_logo.png');?>"
                                                class="icon-prv" alt="">
                                            <h4><?=lang('Evoplay')?>
                                            </h4>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="<?=$this->utils->getSystemUrl('www', $lang_prefix.'slots?prv=spadegaming');?>"
                                            class="link">
                                            <img src="<?=$this->utils->getSystemUrl('www', 'wp-content/uploads/2021/06/spade_logo.png');?>"
                                                class="icon-prv" alt="">
                                            <h4><?=lang('Spadegaming')?>
                                            </h4>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="<?=$this->utils->getSystemUrl('www', $lang_prefix.'/slots?prv=redtiger');?>"
                                            class="link">
                                            <img src="<?=$this->utils->getSystemUrl('www', 'wp-content/uploads/2021/06/tiger_logo.png');?>"
                                                class="icon-prv" alt="">
                                            <h4><?=lang('Red Tiger')?>
                                            </h4>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="<?=$this->utils->getSystemUrl('www', $lang_prefix.'/slots?prv=playtech');?>"
                                            class="link">
                                            <img src="<?=$this->utils->getSystemUrl('m', 'includes/images/home/topgame17/logo_prv/playtech_gold.png');?>"
                                                class="icon-prv" alt="">
                                            <h4><?=lang('Playtech')?>
                                            </h4>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="<?=$this->utils->getSystemUrl('www', $lang_prefix.'/slots?prv=microgaming');?>"
                                            class="link">
                                            <img src="<?=$this->utils->getSystemUrl('m', 'includes/images/home/topgame17/logo_prv/mg_gold.png');?>"
                                                class="icon-prv" alt="">
                                            <h4><?=lang('Microgaming')?>
                                            </h4>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="<?=$this->utils->getSystemUrl('www', $lang_prefix.'slots?prv=hacksaw');?>"
                                            class="link">
                                            <img src="<?=$this->utils->getSystemUrl('www', 'includes/images/sexy/Hacksaw-logogogo.png');?>"
                                                class="icon-prv" alt="">
                                            <h4><?=lang('Hacksaw')?>
                                            </h4>
                                        </a>
                                    </li>
                                    <!-- <li>
                                        <a href="<?=$this->utils->getSystemUrl('m', $lang_prefix.'slots?prv=iconic_gaming');?>"
                                            class="link">
                                            <img src="<?=$this->utils->getSystemUrl('m', 'includes/images/header/menu/slots/iconic.png?v2');?>"
                                                class="icon-prv" alt="">
                                            <h4><?=lang('Iconic Gaming')?>
                                            </h4>
                                        </a>
                                    </li> -->
                                    <!-- <li>
                                        <a href="<?=$this->utils->getSystemUrl('m', $lang_prefix.'slots?prv=yggdrasil');?>"
                                            class="link">
                                            <img src="<?=$this->utils->getSystemUrl('m', 'includes/images/header/menu/slots/ygg.png?v2');?>"
                                                class="icon-prv" alt="">
                                            <h4><?=lang('YGGDrasil')?>
                                            </h4>
                                        </a>
                                    </li> -->
                                    <!-- <li>
                                        <a href="<?=$this->utils->getSystemUrl('m', $lang_prefix.'slots?prv=relaxgaming');?>"
                                            class="link">
                                            <img src="<?=$this->utils->getSystemUrl('m', 'includes/images/header/menu/slots/relax.png?v2');?>"
                                                class="icon-prv" alt="">
                                            <h4><?=lang('Relax Gaming')?>
                                            </h4>
                                        </a>
                                    </li> -->
                                </ul>
                            </div>
                        </li>
                        <!-- end slot -->

                        <!-- live-casino -->
                        <li>
                            <a class="menu__item"
                                href="<?=$this->utils->getSystemUrl('m', $lang_prefix.'live-casino.html');?>">
                                <p><?=lang('sidemenu.live_casino')?>
                                </p>
                            </a>

                            <span class="arrow">
                                <img
                                    src="<?=$this->utils->getSystemUrl('m', 'wp-content/themes/s8aff/includes/images/header/menu/white.png');?>">
                            </span>
                            <div class="submenu">
                                <ul class="menu-list">
                                    <li>
                                        <a href="<?=$this->utils->getSystemUrl('m', 'player_center/goto_common_game/2133');?>"
                                            class="link">
                                            <img src="<?=$this->utils->getSystemUrl('m', 'includes/images/header/menu/evo.png?v2');?>"
                                                class="icon-prv" alt="">
                                            <h4><?=lang('Evolution Gaming')?>
                                            </h4>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="<?=$this->utils->getSystemUrl('m', 'player_center/goto_common_game/5701');?>"
                                            class="link">
                                            <img src="<?=$this->utils->getSystemUrl('m', 'includes/images/header/menu/ae.png?v2');?>"
                                                class="icon-prv" alt="">
                                            <h4><?=lang('Sexy Baccarat')?>
                                            </h4>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="<?=$this->utils->getSystemUrl('m', 'player_center/goto_common_game/2140');?>"
                                            class="link">
                                            <img src="<?=$this->utils->getSystemUrl('m', 'includes/images/header/menu/sa.png?v2');?>"
                                                class="icon-prv" alt="">
                                            <h4><?=lang('SA Gaming')?>
                                            </h4>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="<?=$this->utils->getSystemUrl('m', 'player_center/goto_hgseamless_game/5617/null/real/en');?>"
                                            class="link">
                                            <img src="<?=$this->utils->getSystemUrl('m', 'includes/images/header/menu/ho-gaming.png?v2');?>"
                                                class="icon-prv" alt="">
                                            <h4><?=lang('Ho Gamimg')?>
                                            </h4>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="<?=$this->utils->getSystemUrl('m', 'player_center/goto_common_game/2213');?>"
                                            class="link">
                                            <img src="<?=$this->utils->getSystemUrl('m', 'includes/images/header/menu/slots/pretty.png?v1');?>"
                                                class="icon-prv" alt="">
                                            <h4><?=lang('Pretty Gaming')?>
                                            </h4>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="<?=$this->utils->getSystemUrl('m', 'player_center/goto_common_game/2137');?>"
                                            class="link">
                                            <img src="<?=$this->utils->getSystemUrl('m', 'includes/images/header/menu/ag.png?v2');?>"
                                                class="icon-prv" alt="">
                                            <h4>
                                                <?=lang('Asia Gaming')?>
                                            </h4>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="<?=$this->utils->getSystemUrl('m', 'player_center/goto_common_game/2151');?>"
                                            class="link">
                                            <img src="<?=$this->utils->getSystemUrl('m', 'includes/images/header/menu/lucky.png?v2');?>"
                                                class="icon-prv" alt="">
                                            <h4><?=lang('Lucky Streak')?>
                                            </h4>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="<?=$this->utils->getSystemUrl('m', 'player_center/goto_dggame_seamless');?>"
                                            class="link">
                                            <img src="<?=$this->utils->getSystemUrl('m', 'includes/images/header/menu/dream.png?v2');?>"
                                                class="icon-prv" alt="">
                                            <h4><?=lang('Dream Gaming')?>
                                            </h4>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="<?=$this->utils->getSystemUrl('m', 'player_center/goto_common_game/5849/');?>"
                                            class="link">
                                            <img src="<?=$this->utils->getSystemUrl('m', 'wp-content/uploads/2021/03/icons-amb.png');?>"
                                                class="icon-prv" alt="">
                                            <h4><?=lang('AMB POKER')?>
                                            </h4>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="<?=$this->utils->getSystemUrl('m', 'player_center/goto_common_game/5800/null/real/live_dealer/th');?>"
                                            class="link">
                                            <img src="<?=$this->utils->getSystemUrl('m', 'includes/images/header/menu/slots/bg.png?v2');?>"
                                                class="icon-prv" alt="">
                                            <h4><?=lang('BG')?>
                                            </h4>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="<?=$this->utils->getSystemUrl('m', 'player_center/goto_common_game/5950/5763-6758');?>"
                                            class="link">
                                            <img src="<?=$this->utils->getSystemUrl('www', 'includes/images/sexy/pt_logo.png');?>"
                                                class="icon-prv" alt="">
                                            <h4><?=lang('Playtech')?>
                                            </h4>
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </li>
                        <!-- end live-casino -->

                        <li>
                            <a class="menu__item"
                                href="<?=$this->utils->getSystemUrl('m', $lang_prefix.'sports');?>">
                                <p><?=lang('sidemenu.sports')?>
                                </p>
                            </a>
                        </li>
                        <li>
                            <a class="menu__item"
                                href="<?=$this->utils->getSystemUrl('m', $lang_prefix.'fishing');?>">
                                <p><?=lang('sidemenu.fishing')?>
                                </p>
                            </a>
                        </li>
                        <li>
                            <a class="menu__item"
                                href="<?=$this->utils->getSystemUrl('m', $lang_prefix.'vip');?>">
                                <p><?=lang('sidemenu.vip')?>
                                </p>
                            </a>
                        </li>
                        <li>
                            <a class="menu__item" href="https://s8aff.com/">
                                <p><?=lang('sidemenu.affiliate')?>
                                </p>
                            </a>
                        </li>
                        <li>
                            <a class="menu__item"
                                href="<?=$this->utils->getSystemUrl('m', $lang_prefix.'about-us');?>">
                                <p><?=lang('sidemenu.about_us')?>
                                </p>
                            </a>
                        </li>
                        <li>
                            <a class="menu__item"
                                href="<?=$this->utils->getSystemUrl('m', $lang_prefix.'contact-us');?>">
                                <p><?=lang('sidemenu.contact_us')?>
                                </p>
                            </a>
                        </li>
                    </ul>
                    <div class="language-block">
                        <div class="lang-flag-block">
                            <div class="lang-flag-item current-lang">
                                <a href="javascript: void(0);" data-lang-id="1" data-lang-code="en"
                                    class="wpml-ls-link en _og_switch_lang"> <img
                                        src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABIAAAAMCAIAAADgcHrrAAAAK3RFWHRDcmVhdGlvbiBUaW1lAHphIDMxIGRlYyAyMDA1IDE3OjEzOjA3ICswMTAwvq7DvAAAAAd0SU1FB9UMHxAUBQFPDV4AAAAJcEhZcwAACxIAAAsSAdLdfvwAAAAEZ0FNQQAAsY8L/GEFAAABsElEQVR42mP4X1n5////r1//y8nNYGBohaBly179l5ICIiADLghUcKprw3cLiz9r1zJ90o5gcHXlenTj4cP0oiJ7BoafQPTjxxeG//+BCMQAi+SnGDz0v2d6aQ3HwYNbmJUZGBh6SrMOPEnI+Bge/v/Pn0OHnkpK1nd3H34qJgZEQAaQu6V67mszs/+bNgHdFRMzj4EhlgHsgDo0uatXP0C0XT7xBM1EsJ5QRqAihh8/GB49YkAFTy0sgKT0iRNo4o/u3Xv9/j3jfwYGIELXhBvcYWB4z8DARLR6FECmI1kYGduA4SspybhyZZqtlfin6OjPnILvM2qEWFmBiq79EdfS4mfYvPlNS8tJ1/TUeY+eP7/HwPAD6Mgf2dlWz5412H44+8bK6rCKm+lO8R2H70PM3rbtspRUw2EBY5Fjx2zv7Drt/tLbRQ6oheX06WITPc67AQFPmDgmiQSsaz0AVP3q1cM7f/5AGEDj7ewqQkJMOjq636xdHnVsrqqPNZPho73XLSx2iZsEH+Jat+080CQg+vr147v//9+BEt1HiMiaNUfMzev3/xNjLS8XfnwOAOzh9X0yojgNAAAAAElFTkSuQmCC"
                                        alt="" /> </a>
                            </div>
                            <div class="lang-flag-item other-lang">
                                <a href="javascript: void(0);" data-lang-id="6" data-lang-code="th"
                                    class="wpml-ls-link th _og_switch_lang"> <img
                                        src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABIAAAAMCAIAAADgcHrrAAAAK3RFWHRDcmVhdGlvbiBUaW1lAHphIDMxIGRlYyAyMDA1IDE3OjAzOjMzICswMTAwBeA/jAAAAAd0SU1FB9UMHxAKIJ4K5sYAAAAJcEhZcwAACxIAAAsSAdLdfvwAAAAEZ0FNQQAAsY8L/GEFAAAAaElEQVR42r3QPQ7AIAgF4IfxFN7/Mr2Ds/GvLqRDJ2qj7tihBIY3fCFAgi9lYcyuERHqs8tKKXbXxNhVJuAALiCpYQB4+7BR/7L5yRC0wHvfWp2fdE7LODHdbEE08qkwdfW7TVZQsgw8JqUsOWKCvfIAAAAASUVORK5CYII="
                                        alt="" /> </a>
                            </div>
                        </div>
                    </div>
                    <!-- end language-block -->
                </div>
                <!-- end menu group -->
            </div>
        </nav>
    </div>

    <script type="text/javascript">
        // Sidenav

        $('.sidenav-trigger').click(function() {
            $('#header__navigation').addClass('shw');
            $('.sidenav-overlay').addClass('shw');
            $('body').addClass('ovl');
        });
        $('.sidenav-overlay, .close__sidebar').click(function() {
            $('#header__navigation').removeClass('shw');
            $('.sidenav-overlay').removeClass('shw');
            $('body').removeClass('ovl');
        });
        $(".hammenu>li>span").click(function() {
            $('.hammenu>li>.submenu').not($(this).siblings()).slideUp();
            $(".hammenu>li>span").not(this).removeClass("rotate");
            $(this).siblings(".hammenu>li>.submenu").slideToggle();
            $(this).toggleClass("rotate");
        });

        var languageSwitcher = (function() {
            "use strict";
            var currentLang = $('.lang-flag-item.current-lang');
            var otherLang = $('.lang-flag-item.other-lang');
            var flagEn = currentLang.html();
            var flagTh = otherLang.html();
            var init = function() {
                var lang = _export_smartbackend.variables.currentLang;
                if (lang != 1) {
                    currentLang.html(flagTh);
                    otherLang.html(flagEn);
                } else {
                    currentLang.html(flagEn);
                    otherLang.html(flagTh);
                }
                _export_smartbackend.renderUI.bindSwitchLanguage();

            };
            return {
                'init': init
            };
        })();

        _export_smartbackend.on('run.t1t.smartbackend', function() {
            languageSwitcher.init();
        });
    </script>

    <style>
        .language-block {
            margin-top: 15px;
            line-height: 0;
        }

        .language-block .lang-flag-block {
            display: inline-flex;
        }

        .language-block .lang-flag-block .lang-flag-item {
            padding: 5px 10px;
            position: relative;

        }

        .language-block .lang-flag-block .lang-flag-item.current-lang {
            background: #9c8347;
        }

        .language-block .lang-flag-block .lang-flag-item.current-lang::after {
            content: "";
            vertical-align: middle;
            display: inline-block;
            position: absolute;
            height: 20px;
            top: 1px;
            border: 1px solid white;
            right: 0;
        }
    </style>