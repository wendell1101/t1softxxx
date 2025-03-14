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
            <?php if ($this->authentication->isLoggedIn()) : ?>
                <li>
                    <a href="<?= $this->utils->getSystemUrl('m', '/player_center2/deposit') ?>">
                        <img src="<?= $this->utils->getSystemUrl('m') . '/includes/images/under_bank.png' ?>" />
                        <p><?= lang('lang.deposit') ?></p>
                    </a>
                </li>
                <li>
                    <a href="<?= $this->utils->getSystemUrl('m', '/player_center/menu') ?>">
                        <img src="<?= $this->utils->getSystemUrl('m') . '/includes/images/under_me.png' ?>" />
                        <p><?= lang('Player') ?></p>
                    </a>
                </li>
            <?php else : ?>
                <li>
                    <a href="javascript:void(0)" onclick="live_chat_3rd_party()">
                        <img src="<?= $this->utils->getSystemUrl('m') . '/includes/images/under_chat.png' ?>" />
                        <p><?= lang('role.97') ?></p>
                    </a>
                </li>
                <li>
                    <a href="<?= $this->utils->getSystemUrl('m'), '/iframe/auth/login' ?>">
                        <img src="<?= $this->utils->getSystemUrl('m') . '/includes/images/under_me.png' ?>" />
                        <p><?= lang('Login') ?></p>
                    </a>
                </li>
            <?php endif; ?>
        </ul>
    </div>
</div>

