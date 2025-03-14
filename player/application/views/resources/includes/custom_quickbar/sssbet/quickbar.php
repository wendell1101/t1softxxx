<div class="footer__nav__links data__links">
        <ul>
            <li class="mobile-nav__item">
                <a href="<?= $this->utils->getSystemUrl('m', '/slots.html?prv=pragmatic'); ?>">
                    <img src="<?= $this->utils->getSystemUrl('m', 'includes/images/icon/game-icon.png'); ?>"/>
                    <span class="mobile-nav__item__title"><?=lang('lang.games')?></span>
                </a>
            </li>
            <li class="mobile-nav__item">
                <a href="<?= $this->utils->getSystemUrl('m', 'referral.html'); ?>">
                    <img src="<?= $this->utils->getSystemUrl('m', 'includes/images/icon/Reference-icon.png'); ?>">
                    <span class="mobile-nav__item__title"><?=lang('Reference')?></span>
                </a>
            </li>
            <li class="home_footer">
                <a href="<?= $this->utils->getSystemUrl('m', '/'); ?>">
                    <img class="smash-icon" src="<?= $this->utils->getSystemUrl('m', 'includes/images/sambabet-logo.png?v=' . $this->utils->getCmsVersion()); ?>">
                </a>
            </li>
            <li class="mobile-nav__item">
                <a href="<?= $this->utils->getSystemUrl('m', 'player_center2/deposit'); ?>">
                    <img src="<?= $this->utils->getSystemUrl('m', 'includes/images/icon/deposit-icon.png'); ?>"/>
                    <span class="mobile-nav__item__title"><?=lang('Deposit')?></span>
                </a>
            </li>
            <li class="mobile-nav__item">
                <a id="show-more-footer" class="sidenav-trigger" href="javascript:void(0)">
                    <img src="<?= $this->utils->getSystemUrl('m', 'includes/images/icon/hamburger-icon.png'); ?>"/>
                    <span class="mobile-nav__item__title"><?=lang('lang.menu')?></span>
                </a>
            </li>
        </ul>
</div>

<div id="luckyspin" class="modal" tabindex="0" style="z-index: 1003; display: none; opacity: 0; top: 4%; transform: scaleX(0.8) scaleY(0.8);">
    <div class="modal-content">
        <div class="lspin__wrapper">
            <button class="close__modal modal-close" type="button">
                <i class="material-icons">close</i>
            </button>
            <div class="ls__box wheel__content" style="display: block;">
                <div class="ls__images">
                    <img class="ls__img1 responsive-img" src="<?= $this->utils->getSystemUrl('m', 'includes/images/luckyspin/circle-spin-1.png'); ?>">
                    <img class="ls__img2 responsive-img" src="<?= $this->utils->getSystemUrl('m', 'includes/images/luckyspin/circle-spin-2.png'); ?>">
                    <img class="ls__img3 responsive-img" src="<?= $this->utils->getSystemUrl('m', 'includes/images/luckyspin/luckywheel-bg-1.png'); ?>">
                    <img class="ls__img4 responsive-img" src="<?= $this->utils->getSystemUrl('m', 'includes/images/luckyspin/wheel.png'); ?>">
                    <img class="ls__img5 responsive-img" src="<?= $this->utils->getSystemUrl('m', 'includes/images/luckyspin/spin-point.png'); ?>">
                    <img class="ls__img6 responsive-img" src="<?= $this->utils->getSystemUrl('m', 'includes/images/luckyspin/spin-win.png'); ?>" style="pointer-events: all; filter: grayscale(0);">
                </div>
            </div>

            <div class="lspin__mid wheel__content" style="display: block;">
                <img class="ls__img7 responsive-img" src="<?= $this->utils->getSystemUrl('m', 'includes/images/luckyspin/lucky-spin-img.png'); ?>">
                <div class="wheel__content__footer">
                    <a href="/iframe/auth/login" class="login__spin__btn login__to__win not__login__content" style="display: none;">Log in
                        and Spin</a>
                    <a href="#!" class="login__spin__btn spin__win__btn" style="display: block;">Spin and Win</a>
                    <div class="next__spins next__spin__wrapper" style="display: none;">
                        <p>Next Spin bonus in</p>
                        <div class="timer__wrapper">
                            <span class="hours">-13</span>
                            <p>:</p>
                            <span class="minutes">-40</span>
                            <p>:</p>
                            <span class="seconds">-11</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="you__win__wrapper" style="display: none">
                <div class="img__top">
                    <img class="youwin__img1 responsive-img" src="<?= $this->utils->getSystemUrl('m', 'includes/images/luckyspin/chips-bg.png'); ?>">
                    <img class="youwin__img2 responsive-img" src="<?= $this->utils->getSystemUrl('m', 'includes/images/luckyspin/lucky-spin-img.png'); ?>">
                </div>
                <div class="win__info">
                    <div class="price__win__wrapper">
                        <p>R$ <span id="youwin__prize">0 </span></p>
                    </div>
                    <p>
                        Parabéns por seu prêmio e divirta-se jogando no <span>SSSBET!</span>
                    </p>
                    <a href="#!" class="view__battle__btn">View Battle</a>
                </div>
            </div>
        </div>

    </div>
</div>


<script>
    $('#show-more-footer').on('click', function(event) {
        event.preventDefault();
        $('#mobile-menu').toggleClass('hide');
      });
</script>