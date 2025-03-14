<div class="footer__wrapper data__links">
    <ul id="footer_icons">
        <li class="crash_footer">
            <a href="<?= $this->utils->getSystemUrl('m', 'crash.html'); ?>">
                <img class="gray"src="<?= $this->utils->getSystemUrl('m', 'includes/images/icon/crash_icon.png?v=' . $this->utils->getCmsVersion()); ?>">
                <img class="white-icon" src="<?= $this->utils->getSystemUrl('m', 'includes/images/icon/crash_active.png?v=' . $this->utils->getCmsVersion()); ?>">
                <span class="trn"><?= lang('Crash') ?></span>
            </a>
        </li>

        <li class="sports_footer">
            <a href="<?= $this->utils->getSystemUrl('m', 'sports.html'); ?>">
                <img class="gray" src="<?= $this->utils->getSystemUrl('m', 'includes/images/icon/sports_icon.png?v=' . $this->utils->getCmsVersion()); ?>">
                <img class="white-icon" src="<?= $this->utils->getSystemUrl('m', 'includes/images/icon/sports_active.png?v=' . $this->utils->getCmsVersion()); ?>">
                <span class="trn"><?= lang('Sports') ?></span>
            </a>
        </li>
        <li class="home_footer">
            <a style="opacity: 1;" href="<?= $this->utils->getSystemUrl('m', '/'); ?>">
                <img class="smash-icon" src="<?= $this->utils->getSystemUrl('m', 'includes/images/icon/home_icon.png?v=' . $this->utils->getCmsVersion()); ?>">
            </a>
        </li>
        <li class="service_footer">
            <a data-link="player_center2/deposit" href="<?= $this->utils->getSystemUrl('m', 'player_center2/deposit'); ?>">
                <img class="gray" src="<?= $this->utils->getSystemUrl('m', 'includes/images/icon/deposit_icon.png?v=' . $this->utils->getCmsVersion()); ?>">
                <img class="white-icon" src="<?= $this->utils->getSystemUrl('m', 'includes/images/icon/deposit_active.png?v=' . $this->utils->getCmsVersion()); ?>">
                <span class="trn"><?= lang('Recharge') ?></span>
            </a>
        </li>
        <li class="user_footer">
            <a data-link="player_center/menu" href="<?= $this->utils->getSystemUrl('m', 'player_center/menu'); ?>">
                <img class="gray" src="<?= $this->utils->getSystemUrl('m', 'includes/images/icon/user_icon.png?v=' . $this->utils->getCmsVersion()); ?>">
                <img class="white-icon" src="<?= $this->utils->getSystemUrl('m', 'includes/images/icon/user_active.png?v=' . $this->utils->getCmsVersion()); ?>">
                <span class="trn"><?= lang('Users') ?></span>
            </a>
        </li>
    </ul>
</div>

<script type="text/javascript" src="<?= $this->utils->getSystemUrl('m', 'includes/js/script-ga.js?v=' . $this->utils->getCmsVersion()); ?>"></script>
<style>
footer {
  background: #191919;
  position: fixed;
  width: 100%;
  bottom: 0;
  z-index: 999;
  padding: 5px 0;
  background: #23262b;
  box-shadow: 0 -3px 11px rgb(0 0 0 / 50%);
}
footer .footer__wrapper ul {
  display: grid;
  align-items: center;
  grid-template-columns: repeat(5,1fr);
}
footer .footer__wrapper ul li {
  flex-grow: 1;
  text-align: center;
  position: relative;
}
.foot:active{
  filter: invert(52%) sepia(37%) saturate(5942%) hue-rotate(163deg) brightness(97%) contrast(101%);
}
li.home_footer:active{
  filter: invert(52%) sepia(37%) saturate(5942%) hue-rotate(163deg) brightness(97%) contrast(101%);
}

#sportsIcon:active{
  filter: invert(52%) sepia(37%) saturate(5942%) hue-rotate(163deg) brightness(97%) contrast(101%);
}

footer .footer__wrapper ul li a {
  color: #fff;
}

footer .footer__wrapper ul li a span {
  opacity: 0.5 !important;
}

footer .footer__wrapper ul li a.active {
  opacity: 1;
}

footer .footer__wrapper ul li a img {
  width: 16px;
  height: auto;
  display: block;
  margin: auto;
}

footer .footer__wrapper ul li a span {
  display: block;
  font-size: 10px;
  margin-top: 6px;
}
.footer__wrapper #footer_icons li.sports_footer{
  margin-left: 13px;
}
.footer__wrapper #footer_icons li.service_footer{
  margin-right: 13px;
}
.footer__wrapper #footer_icons a .white-icon {
  display: none;
}
.footer__wrapper #footer_icons a.active .gray {
  display: none;
}
.footer__wrapper #footer_icons a.active .white-icon {
  display: block;
  width: 16px;
  height: auto;
  margin: auto;
}
.footer__wrapper ul li a img.smash-icon {
  height: 50px;
  width: auto !important;
  padding-bottom: 10px;
}

footer .footer__wrapper ul li a.active span {
    opacity: 1 !important;
    color: limegreen;
}

.quickButtonBar .footer__wrapper ul li a img {
    width: 16px;
    height: auto;
    display: block;
    margin: auto;
}
.quickButtonBar .footer__wrapper ul li a span {
    display: block;
    font-size: 10px;
    margin-top: 6px;
}
</style>
<script type="text/javascript">
$(document).ready(function(){

    $(".footer__wrapper ul li a").each(function () {
        var link = $(this).data('link');
        var frameLocation = window.location.href;
        console.log('link: ' +link);
        console.log('frameLocation: ' +frameLocation);
        if(link){
            if (link && frameLocation.indexOf(link) !== -1) {

            console.log('added class tp ' +link);
                $(this).addClass("active");
            }
            else {
                $(this).removeClass("active");
            }
        }
    });

});

</script>