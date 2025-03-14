(function () {
    smartbackend.on('logged.t1t.player', function (e, player) {
        var fasttrackSetting = smartbackend.variables.fastTrack;
        if (fasttrackSetting.enabled) {
            window.sid = smartbackend.variables.token;
            window.fasttrackbrand = fasttrackSetting.brand;
            if (smartbackend.variables.template.playercenter_logo) {
                fasttrackSetting.options.inbox.navBarLogo = smartbackend.variables.template.playercenter_logo;
            }
            window.fasttrack = fasttrackSetting.options;
            var fastTrackCrmScript = document.createElement('script');
            fastTrackCrmScript.async = true;
            fastTrackCrmScript.onload = function () {
                new window.FastTrackLoader();
            }
            fastTrackCrmScript.src = fasttrackSetting.scriptSrc;
            document.body.appendChild(fastTrackCrmScript);

            if(fasttrackSetting.showLuckyWheelIcon) {

                function addStyle(src) {
                    var s = document.createElement('link');
                    s.setAttribute('href', src);
                    s.setAttribute('rel', 'stylesheet');
                    document.head.appendChild(s);
                }

                var player_domain = window.location.protocol + '//' + utils.getHost('player');
                var lucky_wheel_dom = `
<div class="lucky-wheel">

    <!-- Trigger / Floating Icon -->
    <div class="preview-trigger">
        <div class="close"></div>
        <div class="title">
            Lucky Wheel
        </div>
        <div class="preview">
            <span class="badge" style="display:none">0</span>
            <img src="${fasttrackSetting.luckyWheelIcon}" alt="Lucky Wheel">
        </div>
    </div>

    <!-- Modal / Spin Wheel -->
    <div class="luckywheel-bonus spin-wheel">
        <div class="bonus-content">
            <div class="bonus-body">
                <div class="bonus-header">
                    <div class="header-text">
                        <p>Questions? Mail us at</p>
                        <a href="mailto:support@sexycasino.com">support@sexycasino.com</a>
                    </div>
                    <div class="close"></div>
                </div>
                <div class="main-content">
                    <div class="wheelContainer">
                    <img src="https://sexycasino-staging.ft-crm.com/media-api/serve/50a1edab-4129-4615-a5cf-f3f50c88efd8_Spin.gif">
                        <div class="toast"></div>
                    </div>
                </div>
            </div>
            <div class="bonus-footer">
                <button class="spinBtn">CLICK TO SPIN!</button>
            </div>
        </div>
        <div class="overlay-fog"></div>
    </div>
</div>
`;
                document.body.insertAdjacentHTML('beforeend', lucky_wheel_dom);
                addStyle(player_domain + '/resources/player/addons/luckywheel/css/style.css');
                addStyle(player_domain + '/resources/player/addons/luckywheel/css/custom-style.css');

                $('.preview-trigger .close').click(function(){
                    $('.preview-trigger').addClass('hide');
                });
                $('.luckywheel-modal .close').click(function(){
                    $('.luckywheel-modal').removeClass('active');
                });
                $('.preview-trigger .preview').click(function(){
                    $('.luckywheel-bonus.spin-wheel').addClass('active')
                });
                $('.bonus').click(function(){
                    $('.luckywheel-bonus.terms').addClass('active')
                });
                $('.spinBtn').click(function(){
                    $('.luckywheel-bonus .toast').fadeTo("fast", 0);
                    $('.luckywheel-bonus .toast').html('');

                    $.get(player_domain + "/async/addLuckyWheelPromoFunds/", function(data) {
                    if(data.success) {
                        $('.luckywheel-bonus .toast').html('You won ' + data.message + ' THB');
                    }
                    else {
                        $('.luckywheel-bonus .toast').html(data.message);
                    }

                    $('.luckywheel-bonus .toast').fadeTo("fast", 1);
                    });
                });
                $('.luckywheel-bonus.terms .close').click(function(){
                    $('.luckywheel-bonus.terms').removeClass('active')
                })
                $('.luckywheel-bonus.spin-wheel .close').click(function(){
                    $('.luckywheel-bonus.spin-wheel').removeClass('active')
                })
            }
        }
    });
})();