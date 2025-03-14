<div class="footer__nav__links data__links">
    <div class="footer__wrapper">
        <ul id="footer_icons">
            <li>
                <a href="<?= $this->utils->getSystemUrl('m') ?>">
                    <img src="<?= $this->utils->getSystemUrl('m', 'includes/images/under_home.png?v=' . $this->utils->getCmsVersion()); ?>">
                    <span class="trn"><?= lang('header.home') ?></span>
                </a>
            </li>
            <li>
                <a href="<?= $this->utils->getSystemUrl('m', 'promotions.html'); ?>">
                    <img src="<?= $this->utils->getSystemUrl('m', 'includes/images/under_gift.png?v=' . $this->utils->getCmsVersion()); ?>">
                    <span class="trn"><?= lang('Coupon') ?></span>
                </a>
            </li>
            <li>
                <a href="<?= $this->utils->getSystemUrl('m', '/'); ?>">
                    <img src="<?= $this->utils->getSystemUrl('m', 'includes/images/kash-logo.png?v=' . $this->utils->getCmsVersion()); ?>">
                </a>
            </li>
            <li>
                <a href="<?= $this->utils->getSystemUrl('m', 'player_center2/deposit'); ?>">
                    <img src="<?= $this->utils->getSystemUrl('m', 'includes/images/under_bank.png?v=' . $this->utils->getCmsVersion()); ?>">
                    <span class="trn"><?= lang('Recharge') ?></span>
                </a>
            </li>
            <li>
                <a href="<?= $this->utils->getSystemUrl('m', 'player_center/menu'); ?>">
                    <img src="<?= $this->utils->getSystemUrl('m', 'includes/images/under_me.png?v=' . $this->utils->getCmsVersion()); ?>">
                    <span class="trn"><?= lang('Perfil') ?></span>
                </a>
            </li>
        </ul>
    </div>
</div>