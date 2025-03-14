    <?php
        $lang_prefix = '';
        $html_lang_code = $this->language_function->getCurrentLangForPromo();
        if ($html_lang_code == 'en') {
            $lang_prefix = $html_lang_code.'/';
        }
    ?>

    <div class="quickButtonBar">
        <div class="footer__nav__links">
            <ul>
                <li>
                    <a
                        href="<?=$this->utils->getSystemUrl('m', $lang_prefix.'promotions');?>">
                        <img
                            src="<?=$this->utils->getPlayerCmsUrl('/resources/css/sexycasino/promotion.png');?>">
                        <p><?=lang('sidemenu.promotions')?></p>
                    </a>
                </li>
                <?php if (!$this->authentication->isLoggedIn()) {?>
                <li class="login">
                    <a href="/iframe/auth/login">
                        <img
                            src="<?=$this->utils->getPlayerCmsUrl('/resources/css/sexycasino/login.png');?>">
                        <p><?=lang('sidemenu.login')?></p>
                    </a>
                </li>
                <?php } else { ?>
                <li class="login">
                    <a href="/iframe/auth/login">
                        <img
                            src="<?=$this->utils->getPlayerCmsUrl('/resources/css/sexycasino/login.png');?>">
                        <p><?=lang('sidemenu.account')?></p>
                    </a>
                </li>
                <?php } ?>
                <li class="qa" id="qa-btn">
                    <a>
                        <img
                            src="<?=$this->utils->getPlayerCmsUrl('/resources/css/sexycasino/quick-access.png');?>">
                        <p>Quick Access</p>
                    </a>
                </li>
                <?php if (!$this->authentication->isLoggedIn()) {?>
                <li class="register">
                    <a href="player_center/iframe_register">
                        <img
                            src="<?=$this->utils->getPlayerCmsUrl('/resources/css/sexycasino/register.png');?>">
                        <p><?=lang('sidemenu.register')?></p>
                    </a>
                </li>
                <?php } else { ?>
                <li class="deposit">
                    <a href="player_center/deposit">
                        <img src="/resources/css/sexycasino/deposit-ICONS.png">
                        <p><?=lang('sidemenu.deposit')?></p>
                    </a>
                </li>
                <?php } ?>
                <li class="support">
                    <a href="javascript:void(0)">
                        <img
                            src="<?=$this->utils->getPlayerCmsUrl('/resources/css/sexycasino/support.png');?>">
                        <p><?=lang('sidemenu.contact_us')?></p>
                    </a>
                    <ul class="contact-area">
                        <li>
                            <a href="https://lin.ee/gnelumA" target="_blank">
                                <img class="responsive-img"
                                    src="<?=$this->utils->getPlayerCmsUrl('/resources/css/sexycasino/line-rollover-01.png');?>">
                                <!-- <span>LINE</span> -->
                            </a>
                        </li>
                        <li>
                            <a href="javascript:void(0)" onclick="live_chat_3rd_party()">
                                <img class="responsive-img"
                                    src="<?=$this->utils->getPlayerCmsUrl('/resources/css/sexycasino/livechat-rollover-01.png');?>">
                                <!-- <span>LIVECHAT</span> -->
                            </a>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>

        <div class="navmenu">
            <ul class="richmenu">
                <div class="arrow"><img
                        src="<?=$this->utils->getPlayerCmsUrl('/resources/css/sexycasino/arrow.png');?>">
                </div>
                <li><a
                        href="<?=$this->utils->getSystemUrl('m', $lang_prefix.'live-casino');?>"><img
                            src="<?=$this->utils->getPlayerCmsUrl('/resources/css/sexycasino/livecasino-ic.png');?>">
                        <p><?=lang('sidemenu.live_casino')?></p>
                    </a></li>
                <li><a
                        href="<?=$this->utils->getSystemUrl('m', $lang_prefix.'slots?prv=quickspin');?>"><img
                            src="<?=$this->utils->getPlayerCmsUrl('/resources/css/sexycasino/slot-ic.png');?>">
                        <p><?=lang('sidemenu.slots')?></p>
                    </a></li>
                <li><a
                        href="<?=$this->utils->getSystemUrl('m', $lang_prefix.'sports');?>"><img
                            src="<?=$this->utils->getPlayerCmsUrl('/resources/css/sexycasino/sports-ic.png');?>">
                        <p><?=lang('sidemenu.sports')?></p>
                    </a></li>
                <li><a
                        href="<?=$this->utils->getSystemUrl('m', $lang_prefix.'fishing');?>"><img
                            src="<?=$this->utils->getPlayerCmsUrl('/resources/css/sexycasino/fishing-ic.png');?>">
                        <p><?=lang('sidemenu.fishing')?></p>
                    </a></li>
                <li><a
                        href="<?=$this->utils->getSystemUrl('m', $lang_prefix.'promotions');?>"><img
                            src="<?=$this->utils->getPlayerCmsUrl('/resources/css/sexycasino/promotion-ic.png');?>">
                        <p><?=lang('sidemenu.promotions')?></p>
                    </a></li>
            </ul>
        </div>
    </div>

    <script type="text/javascript">
        $('#qa-btn').click(function(e) {
            $('.navmenu').slideToggle();
        });

        $('.richmenu>.arrow').click(function(e) {
            $('.navmenu').slideToggle();
        });

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
    </style>