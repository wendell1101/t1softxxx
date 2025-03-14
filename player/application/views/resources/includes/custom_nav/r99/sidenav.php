<div class="container">
    <!-- Mobile Menu -->
    <div class="sidenav nav__link data__links hide" id="mobile-menu">

        <ul class="collapsible">
            <li>
                <div class="collapsible-header"><a data-href="<?= $this->utils->getSystemUrl('m', '/home.html');?>"><i class="menu_icon home"></i><span class="trn"><?=lang('Home')?></span></a></div>
            </li>
            <li>
                <div class="collapsible expandable collapsible-header t1_original__nav__wrapper"><a><i class="menu_icon t1-original"></i>T1 Originals <i class="material-icons">expand_more</i></a></div>
                <ul class="collapsible-body t1_original_toggle hide">
                    <a data-href="<?= $this->utils->getSystemUrl('m', '/crash.html');?>" class="trn"><i class="menu_icon crash"></i>Crash</a>
                    <a data-href="<?= $this->utils->getSystemUrl('m', '/double.html');?>" class="trn"><i class="menu_icon double"></i>Double</a>
                    <a data-href="<?= $this->utils->getSystemUrl('m', '/dice.html');?>" class="trn"><i class="menu_icon dice"></i>Dice</a>
                    <a data-href="<?= $this->utils->getSystemUrl('m', '/mine.html');?>" class="trn"><i class="menu_icon mine"></i>Mine</a>
                    <a data-href="<?= $this->utils->getSystemUrl('m', '/mini-games.html');?>" class="see_all">
                        <span class="trn">Ver tudo</span>
                    </a>
                </ul>
            </li>

            <li>
                <div class="collapsible expandable collapsible-header slots__nav__wrapper"><a><i class="menu_icon slots trn"></i>Slots <i class="material-icons">expand_more</i></a></div>
                <ul class="collapsible-body slots_toggle hide">
                    <a data-href="<?= $this->utils->getSystemUrl('m', '/slots.html?prv=habanero');?>" class="trn"><img src="<?= $this->utils->getSystemUrl('m', '/includes/images/icon/hb-icon.png');?>" alt=""></i>Habanero</a>
                    <a data-href="<?= $this->utils->getSystemUrl('m', '/slots.html?prv=pragmatic');?>" class="trn"><img src="<?= $this->utils->getSystemUrl('m', '/includes/images/icon/pp-icon.png');?>" alt=""> Pragmatic Play</a>
                    <a data-href="<?= $this->utils->getSystemUrl('m', '/slots.html?prv=bigpot');?>" class="trn"><img src="<?= $this->utils->getSystemUrl('m', '/includes/images/icon/bp-icon.svg');?>" alt=""> Big Pot</a>
                    <a data-href="<?= $this->utils->getSystemUrl('m', '/slots.html?prv=png');?>" class="trn"><img src="<?= $this->utils->getSystemUrl('m', '/includes/images/icon/png-icon.png');?>" alt=""> Play N Go</a>
                    <a data-href="<?= $this->utils->getSystemUrl('m', '/slots.html?prv=microgaming');?>" class="trn"><img src="<?= $this->utils->getSystemUrl('m', '/includes/images/icon/mg-icon.svg');?>" alt=""> Microgaming</a>
                    <a data-href="<?= $this->utils->getSystemUrl('m', '/slots.html?prv=all_games');?>" class="see_all2">
                        <span class="trn">Ver tudo</span>
                    </a>
                </ul>
            </li>
            <li><div class="collapsible-header"><a data-href="<?= $this->utils->getSystemUrl('m', '/live-casino.html');?>"><i class="menu_icon livecasino trn"></i><span class="trn"><?=lang('Live Casino');?></span></a></div></li>
            <li><div class="collapsible-header"><a data-href="<?= $this->utils->getSystemUrl('m', '/sports.html');?>"><i class="menu_icon sports trn"></i><span class="trn"><?=lang('Sports');?></span></a></div></li>
            <li><div class="collapsible-header"><a data-href="<?= $this->utils->getSystemUrl('m', '/e-sports.html');?>"><i class="menu_icon esports"></i><span class="trn">Eesportivas</span></a></div></li>
        </ul>

        <ul class="second">
            <li><a data-href="<?= $this->utils->getSystemUrl('m', '/live-casino.html');?>" class="trn"><?=lang('lang.promo');?>></a></li>
            <li><a data-href="<?= $this->utils->getSystemUrl('m', '/vip.html');?>" class="trn"><?=lang('lang.vipclubbutton');?></a></li>
            <li><a data-href="<?= $this->utils->getSystemUrl('m', '/affiliate.html');?>" class="trn"><?=lang('Affiliates');?></a></li>
            <li><a data-href="<?= $this->utils->getSystemUrl('m', '/coming-soon.html');?>" class="trn">Programa de Referência</a></li>
            <li><a data-href="<?= $this->utils->getSystemUrl('m', '/fairness.html');?>" class="trn">Imparcialidade</a></li>
        </ul>

    </div>
</div>

<script>
    $('.t1_original__nav__wrapper').on('click', function(event) {
        event.preventDefault();
        $('.t1_original_toggle').toggleClass('hide');
    });

    $('.slots__nav__wrapper').on('click', function(event) {
        event.preventDefault();
        $('.slots_toggle').toggleClass('hide');
    });
</script>