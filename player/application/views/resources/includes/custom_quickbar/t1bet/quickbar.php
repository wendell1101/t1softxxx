<div class="footer__wrapper data__links">
    <ul>
        <li>
            <a class="sidenav-trigger" id="show-more-footer" href="javascript:void(0)">
                <img class="gray" src="<?= $this->utils->getSystemUrl('m', '/includes/images/icon/hamburger-icon.png');?>">
                <img class="white-icon" src="<?= $this->utils->getSystemUrl('m', '/includes/images/icon/hamburger-icon-white.png');?>">
                <span class="trn"><?=lang('lang.menu')?></span>
            </a>
        </li>
        <li class="home__footer">
            <a href="<?= $this->utils->getSystemUrl('m', '/sports.html');?>" class="">
                <img class="gray" src="<?= $this->utils->getSystemUrl('m', '/includes/images/icon/football-icon.png?v3');?>">
                <img class="white-icon" src="<?= $this->utils->getSystemUrl('m', '/includes/images/icon/football-icon.png?v3');?>">
                <span class="trn"><?=lang('Sports')?></span>
            </a>
        </li>
        <li class="promo__footer">
            <a style="opacity: 1;" href="<?= $this->utils->getSystemUrl('m', '/');?>">
                <img class="smash-icon" src="<?= $this->utils->getSystemUrl('m', 'includes/images/t1bet-menu.png?v0.1');?>">
            </a>
        </li>
        <li>
            <a href="<?= $this->utils->getSystemUrl('m', '/slots.html?prv=all_games');?>">
                <img class="gray" src="<?= $this->utils->getSystemUrl('m', '/includes/images/icon/slotsmachine-icon.png?v3');?>">
                <img class="white-icon" src="<?= $this->utils->getSystemUrl('m', '/includes/images/icon/slotsmachine-icon.png?v3');?>">
                <span class="trn"><?=lang('Casino');?></span>
            </a>
        </li>
        <li>
            <a href="<?= $this->utils->getSystemUrl('m', '/player_center/menu');?>">
                <img class="gray" src="<?= $this->utils->getSystemUrl('m', '/includes/images/icon/footer-user.png?v3');?>">
                <img class="white-icon" src="<?= $this->utils->getSystemUrl('m', '/includes/images/icon/footer-user-white.png?v3');?>">
                <span class="trn"><?=lang('Users')?></span>
            </a>
        </li>

    </ul>
</div>

<div class="social_links">
    <div class="floating-item email">
        <a class="floting-icons" href="javascript:void(0);" onclick="live_chat_3rd_party()">
            <img src="<?= $this->utils->getSystemUrl('m', 'includes/images/icon/footer-cs.png?v2');?>" alt="">
            <span class="notif"></span>
        </a>
    </div>
</div>

<script>
    $('#show-more-footer').on('click', function(event) {
        event.preventDefault();
        $('#mobile-menu').toggleClass('hide');
    });
</script>