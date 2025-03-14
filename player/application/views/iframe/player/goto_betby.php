<!-- <!DOCTYPE html> -->
<html>
<head>
    <title>BETBY</title>
    <script src="<?php echo $js_link; ?>"></script>
    <script>
        
    </script>
    <script type="text/javascript" src="<?= $this->utils->getSystemUrl('player','/resources/third_party/jquery/jquery-3.1.1.min.js') ?>"></script>
</head>
<body>
    <div id="betby"></div>
</body>
<script>
    var bt = new BTRenderer().initialize({
        brand_id: "<?php echo $brand_id; ?>",
        token: "<?php echo $jwt_token; ?>",
        onTokenExpired: function() {
            return new Promise((resolve, reject) => {
                $.ajax({
                    url: location.protocol + '//' + window.location.hostname +'/player_center/auth_betby',
                    type: "GET",
                    dataType: "jsonp",
                    success: (data) => { 
                        console.log("bbOnTokenExpired>> call success jwt resolve");
                        console.log(data.jwt_token);
                        resolve(data.jwt_token); 
                    },
                    error: (data) => { reject(data.jwt_token); }
                });
            });
        },
        themeName: "<?php echo $theme; ?>",
        lang: "<?php echo $lang; ?>",
        target: document.getElementById('betby'),
        betSlipOffsetTop: 0,
        betslipZIndex: 999,
        cssUrls: [],
        fontFamilies: ['Montserrat, sans-serif', 'Roboto, sans-serif'],
        onRouteChange: function() {},
        onLogin: function() {
            // window.location.replace("<?php echo $login_url; ?>");
            window.top.location.href = "<?= $login_url ?>";
        },
        onRegister: function() {
            // window.location.replace("<?php echo $register_url; ?>");
            window.top.location.href = "<?= $register_url ?>";
        },
        onRecharge: function() {
            window.top.location.href = "<?= $cashier_url ?>";
        },
        onSessionRefresh: function() {
            bbOnSessionRefresh();
        },
        onBetSlipStateChange: function() {}
    });


    function bbOnSessionRefresh(){
        console.log("bbOnSessionRefresh>>");
        url = location.protocol + '//' + window.location.hostname +'/player_center/auth_betby';
        $.ajax(url, {
            type: 'GET',
            cache: false,
            dataType:"jsonp",
            success: function (data, status, xhr) {
                console.log("bbOnSessionRefresh>> call success");
                bt.kill();
                sessionStorage.setItem('bb_jwt', data.jwt_token);
                bt.initialize({
                    brand_id: data.brand_id,
                    token: data.jwt_token,
                    onTokenExpired: function() {
                        return new Promise((resolve, reject) => {
                            $.ajax({
                                url: url,
                                type: "GET",
                                dataType: "jsonp",
                                success: (data) => { 
                                    console.log("bbOnTokenExpired>> call success jwt resolve");
                                    console.log(data.jwt_token);
                                    resolve(data.jwt_token); 
                                },
                                error: (data) => { reject(data.jwt_token); }
                            });
                        });
                    },
                    onSessionRefresh: function() {
                        bbOnSessionRefresh();
                    },
                    themeName: data.theme,
                    lang: data.lang,
                    target: document.getElementById('betby'),
                    betSlipOffsetTop: 0,
                    betslipZIndex: 999,
                    fontFamilies: ['Montserrat, sans-serif', 'Roboto, sans-serif']
                });
            }
        });
    }
    </script>
</html>