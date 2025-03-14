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
                        <a href="<?= $this->utils->getSystemUrl('m', '/player_center/menu') ?>">
                            <img src="<?= $this->utils->getSystemUrl('m') . '/includes/images/under_me.png' ?>" />
                            <p><?= lang('Player') ?></p>
                        </a>
                    </li>
                    <li>
                        <a href="<?= $this->utils->getSystemUrl('m', '/player_center2/deposit') ?>">
                            <img src="<?= $this->utils->getSystemUrl('m') . '/includes/images/under_bank.png' ?>" />
                            <p><?= lang('lang.deposit') ?></p>
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
                <li>
                    <a href="javascript:void(0)" onclick="live_chat_3rd_party()">
                        <img src="<?= $this->utils->getSystemUrl('m') . '/includes/images/under_chat.png' ?>" />
                        <p><?= lang('role.97') ?></p>
                    </a>
                </li>
<!--                 <li>
                    <a href="<?=$this->utils->getSystemUrl('m', 'promotions.html');?>">
                        <img src="<?=$this->utils->getPlayerCmsUrl('/resources/css/sexycasino/promotion.png');?>">  <p>โปรโมชั่น</p>
                    </a>
                </li>
                <?php if(!$this->authentication->isLoggedIn()){?>
                <li class="login">
                    <a href="<?=$this->utils->getSystemUrl('m', '/iframe/auth/login');?>">
                        <img src="<?=$this->utils->getPlayerCmsUrl('/resources/css/sexycasino/login.png');?>">  <p>ล็อคอิน</p>
                    </a>
                </li>
                <?php }else{ ?>
                <li class="login">
                    <a href="<?=$this->utils->getSystemUrl('m', '/iframe/auth/login');?>">
                        <img src="<?=$this->utils->getPlayerCmsUrl('/resources/css/sexycasino/login.png');?>">  <p>ข้อมูลบัญชี</p>
                    </a>
                </li>
                <?php } ?>
                <li class="qa" id="qa-btn">
                    <a>
                        <img src="<?=$this->utils->getPlayerCmsUrl('/resources/css/sexycasino/quick-access.png');?>">
                        <p>Quick Access</p>
                    </a>
                </li>
                <?php if(!$this->authentication->isLoggedIn()){?>
                <li class="register">
                    <a href="<?=$this->utils->getSystemUrl('m', 'player_center/iframe_register');?>">
                        <img src="<?=$this->utils->getPlayerCmsUrl('/resources/css/sexycasino/register.png');?>">
                        <p>สมัครสมาชิก</p>
                    </a>
                </li>
                <?php }else{ ?>
                <li class="deposit">
                    <a href="<?=$this->utils->getSystemUrl('m', 'player_center/deposit');?>">
                        <img src="/resources/css/sexycasino/deposit-ICONS.png">
                        <p>ฝากเงิน</p>
                    </a>
                </li>
                <?php } ?>
                <li class="support">
                    <a href="javascript:void(0)" onclick="live_chat_3rd_party()">
                        <img src="<?=$this->utils->getPlayerCmsUrl('/resources/css/sexycasino/support.png');?>">
                        <p>ติดต่อ</p>
                    </a>
                </li> -->
            </ul>
        </div>

<!--         <div class="navmenu">
            <ul class="richmenu">
                <div class="arrow"><img src="<?=$this->utils->getPlayerCmsUrl('/resources/css/sexycasino/arrow.png');?>"></div>
                <li><a href="<?=$this->utils->getSystemUrl('m', 'live-casino.html');?>"><img src="<?=$this->utils->getPlayerCmsUrl('/resources/css/sexycasino/livecasino-ic.png');?>">  <p>คาสิโนสด</p></a></li>
                <li><a href="<?=$this->utils->getSystemUrl('m', 'slots.html?prv=quickspin');?>"><img src="<?=$this->utils->getPlayerCmsUrl('/resources/css/sexycasino/slot-ic.png');?>">  <p>สล็อต</p></a></li>
                <li><a href="<?=$this->utils->getSystemUrl('player', 'iframe_module/goto_sbobet_game/5661/th/false/black/EU/true');?>"><img src="<?=$this->utils->getPlayerCmsUrl('/resources/css/sexycasino/sports-ic.png');?>">  <p>กีฬา</p></a></li>
                <li><a href="<?=$this->utils->getSystemUrl('m', 'fishing.html');?>"><img src="<?=$this->utils->getPlayerCmsUrl('/resources/css/sexycasino/fishing-ic.png');?>">  <p>ยิงปลา</p></a></li>
                <li><a href="<?=$this->utils->getSystemUrl('m', 'vip.html');?>"><img src="<?=$this->utils->getPlayerCmsUrl('/resources/css/sexycasino/vip-ic.png');?>">  <p>VIP</p></a></li>
                <li><a href="<?=$this->utils->getSystemUrl('m', 'promotions.html');?>"><img src="<?=$this->utils->getPlayerCmsUrl('/resources/css/sexycasino/promotion-ic.png');?>">  <p>โปรโมชั่นใหม่</p></a></li>
            </ul>
        </div> -->
    </div>

<!-- <script type="text/javascript">
    $('#qa-btn').click(function(e){
        $('.navmenu').slideToggle();
    });

    $('.arrow').click(function(e){
        $('.navmenu').slideToggle();
    });
</script> -->
