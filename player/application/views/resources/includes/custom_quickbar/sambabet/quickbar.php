<div class="footer__nav__links data__links">
    <ul>
        <li>
            <a href="<?= $this->utils->getSystemUrl('m', 'promotions.html'); ?>">
                <img class="gray" src="<?= $this->utils->getSystemUrl('m', 'includes/images/icon/footer-coupon.png?v=' . $this->utils->getCmsVersion()); ?>">
                <img class="white-icon" src="<?= $this->utils->getSystemUrl('m', 'includes/images/icon/footer-coupon-white.png?v={CMS_VERSION}'); ?>">
                <span class="trn" data-trn-key="Coupon" style="opacity: 1;"><?= lang('Coupon') ?></span>
            </a>
        </li>
        <li>
            <a href="<?= $this->utils->getSystemUrl('m', 'player_center2/deposit'); ?>">
                <img class="gray" src="<?= $this->utils->getSystemUrl('m', 'includes/images/icon/footer-recharge.png?v=' . $this->utils->getCmsVersion()); ?>">
                <img class="white-icon" src="<?= $this->utils->getSystemUrl('m', 'includes/images/icon/footer-recharge-white.png?v=' . $this->utils->getCmsVersion()); ?>">
                <span class="trn" data-trn-key="Recharge" style="opacity: 1;"><?= lang('Recharge') ?></span>
            </a>
        </li>
        <li>
            <a href="<?= $this->utils->getSystemUrl('m', '/'); ?>">
                <img class="smash-icon" src="<?= $this->utils->getSystemUrl('m', 'includes/images/sambabet-logo.png?v=' . $this->utils->getCmsVersion()); ?>">
            </a>
        </li>
        <li>
            <a href="javascript:void(0)" onclick="live_chat_3rd_party()">
                <img class="gray" src="<?= $this->utils->getSystemUrl('m', 'includes/images/icon/footer-cs.png?v=' . $this->utils->getCmsVersion()); ?>">
                <img class="white-icon" src="<?= $this->utils->getSystemUrl('m', 'includes/images/icon/footer-cs-white.png?v=' . $this->utils->getCmsVersion()); ?>">
                <span class="trn" data-trn-key="Service" style="opacity: 1;"><?= lang('customer.service.mobile') ?></span>
            </a>
        </li>
        <li>
            <a href="<?= $this->utils->getSystemUrl('m', 'player_center/menu'); ?>">
                <img class="gray" src="<?= $this->utils->getSystemUrl('m', 'includes/images/icon/footer-user.png?v=' . $this->utils->getCmsVersion()); ?>">
                <img class="white-icon" src="<?= $this->utils->getSystemUrl('m', 'includes/images/icon/footer-user-white.png?v=' . $this->utils->getCmsVersion()); ?>">
                <span class="trn" data-trn-key="Users" style="opacity: 1;"><?= lang('Users') ?></span>
            </a>
        </li>
    </ul>
</div>