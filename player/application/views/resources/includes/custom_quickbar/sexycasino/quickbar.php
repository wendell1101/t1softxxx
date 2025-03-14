    <?php
        $lang_prefix = '';
        $html_lang_code = $this->language_function->getCurrentLangForPromo();
        if ($html_lang_code == 'en') {
            $lang_prefix = $html_lang_code.'/';
        }
    ?>
    <div class="quick-menu">
        <div class="quickmenu__wrapper">
            <div class="footer__links">
                <div class="footer__nav__links">
                    <ul>
                        <li class="promotion">
                            <a href="<?=$this->utils->getSystemUrl('www', $lang_prefix.'promotions');?>">
                                <img
                                    src="<?=$this->utils->getSystemUrl('www', 'wp-content/themes/s8aff/includes/images/footer/promotion.png');?>"
                                    >
                                <p><?=lang('sidemenu.promotions')?></p>
                            </a>
                        </li>

                        <?php if (!$this->authentication->isLoggedIn()) {?>
                            <li class="register">
                                <a href="player_center/iframe_register">
                                    <img class="lazy loaded"
                                        src="<?=$this->utils->getSystemUrl('www', 'wp-content/themes/s8aff/includes/images/footer/register.png');?>"
                                        data-src="<?=$this->utils->getSystemUrl('www', 'wp-content/themes/s8aff/includes/images/footer/register.png');?>"
                                        data-was-processed="true">
                                    <p><?=lang('sidemenu.register')?></p>
                                </a>
                            </li>
                            <?php } else { ?>
                            <li class="deposit in">
                                <a href="player_center/deposit">
                                    <img class="lazy loaded"
                                        src="<?=$this->utils->getSystemUrl('www', 'wp-content/themes/s8aff/includes/images/footer/deposit-ICONS.png');?>"
                                        data-src="<?=$this->utils->getSystemUrl('www', 'wp-content/themes/s8aff/includes/images/footer/deposit-ICONS.png');?>"
                                        data-was-processed="true">
                                    <p><?=lang('sidemenu.deposit')?></p>
                                </a>
                            </li>
                        <?php } ?>

                        <li class="home">
                            <a href="<?=$this->utils->getSystemUrl('www','');?>">
                                <img class="lazy loaded"
                                    src="<?=$this->utils->getSystemUrl('www', 'wp-content/themes/s8aff/includes/images/footer/new_home.png');?>"
                                    data-src="<?=$this->utils->getSystemUrl('www', 'wp-content/themes/s8aff/includes/images/footer/new_home.png');?>"
                                    data-was-processed="true">
                                <p><?=lang('sidemenu.home')?></p>
                            </a>
                        </li>

                        <?php if (!$this->authentication->isLoggedIn()) {?>
                            <li class="player in">
                                <a href="/iframe/auth/login" style="display: list-item;">
                                    <img
                                        src="<?=$this->utils->getSystemUrl('www', 'wp-content/themes/s8aff/includes/images/footer/login-ICONS.png');?>">
                                    <p><?=lang('sidemenu.login')?></p>
                                </a>
                            </li>
                            <?php } else { ?>
                            <li class="player in">
                                <a href="/iframe/auth/login">
                                    <img
                                        src="<?=$this->utils->getSystemUrl('www', 'wp-content/themes/s8aff/includes/images/footer/login-ICONS.png');?>">
                                    <p><?=lang('sidemenu.account')?></p>
                                </a>
                            </li>
                        <?php } ?>

                        <li class="support"><a href="javascript:void(0)">
                            <img
                                src="<?=$this->utils->getSystemUrl('www', 'wp-content/themes/s8aff/includes/images/support.png');?>">
                                <p><?=lang('sidemenu.contact_us')?></p>
                            </a>
                            <ul class="contact-area">
                                <li>
                                    <a href="https://lin.ee/gnelumA" target="_blank">
                                        <img class="responsive-img"
                                            src="<?=$this->utils->getSystemUrl('www', 'wp-content/themes/s8aff/includes/images/footer/line-green.png');?>">
                                    </a>
                                </li>
                                <li>
                                    <a href="javascript:void(0)" onclick="live_chat_3rd_party()">
                                        <img class="responsive-img"
                                            src="<?=$this->utils->getSystemUrl('www', 'wp-content/themes/s8aff/includes/images/footer/livechat-blue.png');?>">
                                    </a>
                                </li>
                            </ul>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <script type="text/javascript">
        $('li.support').on('click', function(event) {
            $('ul.contact-area ').toggleClass('visible');
        });
    </script>

    <style>
        ul.contact-area {
            background: rgb(27 25 25);
            bottom: -100%;
            color: #fff;
            text-align: center;
            position: absolute;
            transition: bottom 0.5s ease-out;
            visibility: hidden;
            display: block !important;
            bottom: 0px;
            width: 100% !important;
            right: 0;
            z-index: 1;
            bottom: -65px;
        }

        ul.contact-area li img {
            max-width: 80px !important;
        }

        ul.contact-area li {
            margin-top: 10px !important;
            margin-bottom: 10px !important;
        }

        ul.contact-area li a {
            display: inline-block;
        }

        ul.contact-area li span {
            font-size: 9px;
            width: 100%;
            text-align: center;
            clear: both;
            display: block;
        }

        .visible {
            visibility: visible !important;
            bottom: 65px !important;
        }

        .quickmenu__wrapper .footer__links .footer__nav__links {
            position: fixed;
            bottom: -1000px;
            bottom: 0;
            width: 100%;
            height: 75.5px;
            z-index: 2001;
            /* display: flex; */
            display: table;
            justify-content: flex-start;
            align-items: center;
            background: #1b1b1b;
            border-top: 2px solid #513a0d;
            transition: 0.7s;
            transition-timing-function: ease-in-out;
        }
        .quickmenu__wrapper .footer__links .footer__nav__links ul {
            padding: 0;
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            width: 100%;
        }
        .quickmenu__wrapper .footer__links .footer__nav__links ul li {
            list-style: none;
            text-align: center;
            margin-top: 12px;
            position: relative;
            width: 100%;
        }
        .quickmenu__wrapper .footer__links .footer__nav__links ul li a {
            color: #fff;
            font-size: 12px;
            text-decoration: none;
        }
        .quickmenu__wrapper .footer__links .footer__nav__links ul li a img {
            display: inline-block;
            padding: 0;
            margin: 0;
            width: 30px;
        }
        .quick-menu ul.contact-area {
            background: rgb(27 25 25);
            bottom: -100%;
            color: #fff;
            text-align: center;
            position: absolute;
            transition: bottom 0.5s ease-out;
            visibility: hidden;
            display: block !important;
            bottom: 0px;
            width: 100% !important;
            right: 0;
            z-index: 1;
            bottom: -65px;
        }
        .quick-menu ul.contact-area li {
            margin-top: 10px !important;
            margin-bottom: 10px !important;
        }
        .quick-menu ul.contact-area li a {
            display: inline-block;

        }
        .quick-menu ul.contact-area li img {
            max-width: 80px !important;
            width: 37px !important;
        }
        .quickmenu__wrapper .footer__links .footer__nav__links ul li a p {
            display: block;
            margin: 0;
            color: #fff;
            font-size: 12px;
            text-align: center;
        }
    </style>