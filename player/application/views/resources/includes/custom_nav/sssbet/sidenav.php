<div class="container">
    <!-- Mobile Menu -->
    <div class="sidenav hide" id="mobile-menu">
        <div class="promo__nav">
            <div class="banner__nav">
                <a href="<?= $this->utils->getSystemUrl('m', '/promotions.html'); ?>">
                    <span>Promoções</span>
                    <img class="promonav-img" src="<?= $this->utils->getSystemUrl('m', 'includes/images/icon/icon-promotions.png'); ?>" alt="">
                </a>
            </div>
            <div class="banner__nav">
                <a href="<?= $this->utils->getSystemUrl('m', '#luckyspin'); ?>" class="modal-trigger">
                    <span>Rodar</span>
                    <img class="rw-img1 spin" src="<?= $this->utils->getSystemUrl('m', 'includes/images/icon/wheel.png'); ?>" alt="">
                    <img class="rw-img2" src="<?= $this->utils->getSystemUrl('m', 'includes/images/icon/coins.png'); ?>" alt="">
                </a>
            </div>
        </div>

        <ul class="collapsible expandable nav__link">
            <li>
              <div class="collapsible-header">
                <div data-toggle="collapse" data-target="#collapse_bouns" aria-expanded="false" class="promo__nav__wrapper">
                    <div>
                        <img src="<?= $this->utils->getSystemUrl('m', 'includes/images/icon/sm-promotion.png'); ?>">
                        <span>Ganhe bônus!</span>
                    </div>
                    <i class="fa fa-caret-down"></i>
                </div>
            </div>
              <div id="collapse_bouns" class="collapsible-body collapse">
                <div class="bonus__links">
                    <a href="#!"><img src="<?= $this->utils->getSystemUrl('m', 'includes/images/icon/icon-sword.png'); ?>"> Em breve</a>
                </div>
              </div>
            </li>
        </ul>

        <div class="sidenav__links nav__link">
            <ul>
                <li><a href="<?= $this->utils->getSystemUrl('m', 'slots.html?prv=pragmatic'); ?>"><img src="<?= $this->utils->getSystemUrl('m', 'includes/images/icon/icon-tab-slots.png'); ?>"> Todos os jogos</a></li>
                <li><a href="<?= $this->utils->getSystemUrl('m', 'vip.html'); ?>"><img src="<?= $this->utils->getSystemUrl('m', 'includes/images/icon/icon-tab-vip.png'); ?>"> Celebração VIP</a></li>
                <li><a href="<?= $this->utils->getSystemUrl('m', 'referral.html'); ?>"><img src="<?= $this->utils->getSystemUrl('m', 'includes/images/icon/icon-referral.png'); ?>"> Referência</a></li>
            </ul>
        </div>
        <div class="sidenav__links__info nav__link">
            <ul>
                <li><a href="<?= $this->utils->getSystemUrl('m', 'about.html'); ?>"><img src="<?= $this->utils->getSystemUrl('m', 'includes/images/icon/icon-about.png'); ?>"> Sobre nós</a></li>
                <li><a href="<?= $this->utils->getSystemUrl('m', 'app.html'); ?>"><img src="<?= $this->utils->getSystemUrl('m', 'includes/images/icon/icon-mobile.png'); ?>"> Baixar aplicativo</a></li>
            </ul>
        </div>
        <div class="sidenav__livechat__lang">
            <ul>
                <li>
                    <a href="javascript:void(0)" target="_blank" onclick="live_chat_3rd_party()">
                        <img src="<?= $this->utils->getSystemUrl('m', 'includes/images/icon/live-chat.png'); ?>"> Suporte ao vivo
                    </a>
                </li>
                <li>
                    <a href="#!"><img src="<?= $this->utils->getSystemUrl('m', '/includes/images/icon/pt.png'); ?>"> Portuguese</a>
                </li>
            </ul>
        </div>
        <div class="socialmedia-wrapper data__link">
            <div>
                <a href="https://www.facebook.com/profile.php?id=100090526314686&mibextid=ZbWKwL" target="_blank"><img src="<?= $this->utils->getSystemUrl('m', 'includes/images/icon/Facebook.svg?v2'); ?>"></a>
                <a href="https://instagram.com/sssbet047?igshid=ZDdkNTZiNTM" target="_blank"><img src="<?= $this->utils->getSystemUrl('m', 'includes/images/icon/Instagram.svg?v2'); ?>"></a>
                <a href="https://t.me/+bopeOxB05HNlN2Y9" target="_blank"><img src="<?= $this->utils->getSystemUrl('m', 'includes/images/icon/Telegram.svg?v2'); ?>"></a>
                <a href="https://twitter.com/sssbet047" target="_blank"><img src="<?= $this->utils->getSystemUrl('m', 'includes/images/icon/Twitter.svg?v2'); ?>"></a>
                <a href="https://www.youtube.com/@zhasi8377/playlistsA" target="_blank"><img src="<?= $this->utils->getSystemUrl('m', 'includes/images/icon/Youtube.svg?v2'); ?>"></a>
            </div>
        </div>
    </div>
</div>