<?php if ($this->authentication->isLoggedIn()) {?>
	<div class="main-nav-footer">
		<div class="mobile-nav__menu">
				<ul>
					<li class="mobile-nav__item">
						<a href="<?= $this->utils->getSystemUrl('m', ''); ?>">
							<img src="/includes/images/c042/home-footer-icon.png"/>
							<span class="mobile-nav__item__title"><?=lang('Home')?></span>
						</a>
					</li>
					<li class="mobile-nav__item">
						<a href="<?= $this->utils->getSystemUrl('m', ''); ?>">
							<img src="/includes/images/c042/sports-footer-icon.png"/>
							<span class="mobile-nav__item__title"><?=lang('Football')?></span>
						</a>
					</li>
					<li class="mobile-nav__item">
						<a href="<?= $this->utils->getSystemUrl('player', 'player_center2/promotion'); ?>">
							<img src="/includes/images/c042/promos-footer-icon.png"/>
							<span class="mobile-nav__item__title"><?=lang('Promos')?></span>
						</a>
					</li>
					<li class="mobile-nav__item">
						<a href="<?= $this->utils->getSystemUrl('m', 'live-casino/roulette'); ?>">
							<img src="/includes/images/c042/live-roulette-footer-icon.png"/>
							<span class="mobile-nav__item__title"><?=lang('Live roulette')?></span>
						</a>
					</li>
					<li class="mobile-nav__item">
						<a id="show-more-footer" href="javascript:void(0)">
							<img src="/includes/images/c042/more-footer-icon.png"/>
							<span class="mobile-nav__item__title"><?=lang('More')?></span>
						</a>
					</li>
				</ul>
			<!-- </div> -->
		</div>
		<div class="mobile-nav__more hide">
			<div class="mobile-nav__more__contents">
				<div class="subnav-content-header">
					<span><?=lang('More')?></span>
					<a id="close-sub-nav" href="javascript:void(0)">
						<img src="/includes/images/c042/close-footer.png" alt="">
					</a>
				</div>
				<div class="sub-contents">
					<ul>
						<li class="coming-soon-container">
							<img src="/includes/images/c042/coming-soon-footer.png" class="coming-soon-img">
							<a href="javascript:void(0)" class="sub-nav-footer">
								<img src="/includes/images/c042/messages-footer.png" class="subnav-icon">
								<span><?=lang('My Messages')?></span>
							</a>
						</li>
						<li class="coming-soon-container">
							<img  src="/includes/images/c042/coming-soon-footer.png" class="coming-soon-img">
							<a href="javascript:void(0)" class="sub-nav-footer" onclick="live_chat_3rd_party()">
								<img src="/includes/images/c042/live-chat-footer.png" class="subnav-icon">
								<span><?=lang('Live Chat')?></span>
							</a>
						</li>
						<li>
							<a href="<?= $this->utils->getSystemUrl('m', 'help'); ?>" class="sub-nav-footer">
								<img src="/includes/images/c042/help-footer.png" class="subnav-icon">
								<span><?=lang('Help & Contact')?></span>
							</a>
						</li>
						<li>
							<a href="<?= $this->utils->getSystemUrl('m', ''); ?>" class="sub-nav-footer">
								<img src="/includes/images/c042/cricket.svg" class="subnav-icon">
								<span><?=lang('Cricket')?></span>
							</a>
						</li>
						<li>
							<a href="<?= $this->utils->getSystemUrl('m', ''); ?>" class="sub-nav-footer">
								<img src="/includes/images/c042/football2.svg" class="subnav-icon">
								<span><?=lang('Football')?></span>
							</a>
						</li>
						<li>
							<a href="<?= $this->utils->getSystemUrl('m', ''); ?>" class="sub-nav-footer">
								<img src="/includes/images/c042/field-hockey.svg" class="subnav-icon">
								<span><?=lang('Field hockey')?></span>
							</a>
						</li>
					</ul>
				</div>
				<div class="subnav-content-footer">
					<span><?=lang('FOLLOW US')?></span>
					<div class="social-media-container">
						<a id="social-media-links" href="https://www.facebook.com/alphabookbet" target="_blank">
							<img src="/includes/images/c042/facebook-footer.png" alt="">
						</a>
						<a id="social-media-links" href="https://www.instagram.com/alphabook_bet" target="_blank">
							<img src="/includes/images/c042/instagram-footer.png" alt="">
						</a>
						<a id="social-media-links" href="https://twitter.com/alphabook_bet" target="_blank">
							<img src="/includes/images/c042/twitter-footer.png" alt="">
						</a>
						<a id="social-media-links" href="https://t.me/alphabook_bet" target="_blank">
							<img src="/includes/images/c042/telegram-footer.png" alt="">
						</a>
						<!-- <a id="social-media-links" href="/">
							<img src="/includes/images/c042/close-footer.png" alt="">
						</a> -->
					</div>
				</div>
			</div>
		</div>
	</div>

<?php } ?>
<script>
	$('#show-more-footer').on('click', function(event) {
		event.preventDefault();
    $('.mobile-nav__more').toggleClass('hide');
  });

	$('#close-sub-nav').on('click', function(event) {
		event.preventDefault();
    $('.mobile-nav__more').addClass('hide');
  });

</script>