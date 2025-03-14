<div class="quickButtonBar">
    <div class="footer__nav__links">
        <ul>
            <li>
                <a href="<?= $this->utils->getSystemUrl('m') ?>">
                    <img src="<?= $this->utils->getSystemUrl('m') . '/includes/images/under_home.png' ?>" />
                    <p><?= lang('header.home') ?></p>
                </a>
            </li>
            <li>
                <a href="<?= $this->utils->getSystemUrl('m', 'promotions.html') ?>">
                    <img src="<?= $this->utils->getSystemUrl('m') . '/includes/images/under_gift.png' ?>" />
                    <p><?= lang('lang.promo') ?></p>
                </a>
            </li>
            <li>
                <a href="<?= $this->utils->getSystemUrl('m', 'sport.html') ?>">
                    <img src="<?= $this->utils->getSystemUrl('m') . '/includes/images/under_sport.png' ?>" />
                    <p><?= lang('Sports') ?></p>
                </a>
            </li>
            <?php if ($this->authentication->isLoggedIn()) : ?>
                <li>
                    <a href="<?= $this->utils->getSystemUrl('m', '/player_center2/deposit') ?>">
                        <img src="<?= $this->utils->getSystemUrl('m') . '/includes/images/under_bank.png' ?>" />
                        <p><?= lang('pay.deposit') ?></p>
                    </a>
                </li>
                <li>
                    <a href="<?= $this->utils->getSystemUrl('m', '/player_center/menu') ?>">
                        <img src="<?= $this->utils->getSystemUrl('m') . '/includes/images/under_me.png' ?>" />
                        <p><?= lang('lang.quickbar.menu') ?></p>
                    </a>
                </li>
            <?php else : ?>
                <li>
                    <a href="<?= $this->utils->getSystemUrl('m'), '/iframe/auth/login' ?>">
                        <img src="<?= $this->utils->getSystemUrl('m') . '/includes/images/footer/icon/footer-login-icon.png' ?>" />
                        <p><?= lang('Login') ?></p>
                    </a>
                </li>
                <li>
                    <a href="<?= $this->utils->getSystemUrl('m') ?>">
                        <img src="<?= $this->utils->getSystemUrl('m') . '/includes/images/footer/icon/footer-reg-icon.png' ?>" />
                        <p><?= lang('Register') ?></p>
                    </a>
                </li>
            <?php endif; ?>
        </ul>
    </div>
</div>

