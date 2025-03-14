<div class="footer__wrapper data__links">
    <ul>
        <li>
            <a class="sidenav-trigger" id="show-more-footer" href="javascript:void(0)">
                
                <img class="gray" src="<?= $this->utils->getSystemUrl('m', '/includes/images/icon/hamburger-icon.png?v2');?>">
                <img class="white-icon" src="<?= $this->utils->getSystemUrl('m', '/includes/images/icon/hamburger-icon-white.png?v2');?>">
                <span class="trn" data-trn-key="Menu" style="opacity: 1;"><?=lang('lang.menu')?></span>
            </a>
        </li>
        <li class="home__footer">
            <a href="<?= $this->utils->getSystemUrl('m', '/sports.html');?>" class="">
                <img class="gray" src="<?= $this->utils->getSystemUrl('m', '/includes/images/icon/football-icon.png?v4');?>">
                <img class="white-icon" src="<?= $this->utils->getSystemUrl('m', '/includes/images/icon/football-icon.png?v4');?>">
                <span class="trn" data-trn-key="Esportes" style="opacity: 1;"><?=lang('Sports')?></span>
            </a>
        </li>
        <li class="promo__footer">
            <a style="opacity: 1;" href="<?= $this->utils->getSystemUrl('m', '/');?>">
                <img class="smash-icon" src="<?= $this->utils->getSystemUrl('m', 'includes/images/R99-menu.png?v0.2');?>">
            </a>
        </li>
        <li>
            <a href="<?= $this->utils->getSystemUrl('m', '/slots.html?prv=all_games');?>">
                <img class="gray" src="<?= $this->utils->getSystemUrl('m', '/includes/images/icon/slotsmachine-icon.png?v4');?>">
                <img class="white-icon" src="<?= $this->utils->getSystemUrl('m', '/includes/images/icon/slotsmachine-icon.png?v4');?>">
                <span class="trn" data-trn-key="Cassino" style="opacity: 1;"><?=lang('Casino');?></span>
            </a>
        </li>
        <li>
            <a href="<?= $this->utils->getSystemUrl('m', '/player_center/menu');?>">
                <img class="gray" src="<?= $this->utils->getSystemUrl('m', '/includes/images/icon/footer-user.png?v5');?>">
                <img class="white-icon" src="<?= $this->utils->getSystemUrl('m', '/includes/images/icon/footer-user-white.png?v5');?>">
                <span class="trn" data-trn-key="Jogador" style="opacity: 1;"><?=lang('Users')?></span>
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