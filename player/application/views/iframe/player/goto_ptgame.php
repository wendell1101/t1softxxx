<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0"/>
    <?php if($use_new_pt_version){ ?>
        <script type="text/javascript" src="<?php echo $api_play_pt_js; ?>"></script>
    <?php }else{ ?>
        <?php if(empty($mobile_js_url)){ ?>
         <!-- <script type="text/javascript" src="<?php #echo $api_play_pt; ?>/integrationjs.php"></script> -->
         <script type="text/javascript" src="<?php echo $api_play_pt_js; ?>"></script>
        <?php }else{?>
         <script type="text/javascript" src="<?php echo $mobile_js_url;?>"></script>
        <?php }?>
    <?php } ?>

    <script type="text/javascript" src="<?php echo $this->utils->playerResUrl('jquery-1.11.3.min.js'); ?>"></script>
</head>
<body>
<div>正在载入... <a href="javascript:window.location.reload();" class='retry_link' style='display:none'>重试</a> </div>
<script type="text/javascript">

    var game_platform_id="<?php echo (isset($game_platform_id)?$game_platform_id:1); ?>";
    var gameCode="<?php echo $game_code; ?>";
    var lang="<?php echo $lang; ?>";
    var api_play_pt="<?php echo $api_play_pt; ?>";
    var player_url="<?php echo $player_url; ?>";
    var mobile="<?php echo ( isset( $mobile ) ) ? $mobile : ''; ?>";
    var mobile_systemId="<?php echo ( isset( $mobile_systemId ) && ! empty($mobile_systemId) ) ? $mobile_systemId : ''; ?>";

    var mobile_launcher = "<?=( isset( $mobile_launcher ) && ! empty( $mobile_launcher ) ) ? $mobile_launcher : 'http://hub.gm175888.com/igaming/';?>";
    var mobile_lobby = "<?= $mobile_lobby ?>";
    var mobile_logout_url = "<?= $mobile_logout_url ?>";
    var deposit_url = "<?= $deposit_url ?>";
    var support_url = "<?= $support_button_link ?>";

    var utils={
        safelog: function(msg){
            if(typeof(console)!='undefined' && typeof(console.log)!='undefined'){
                console.log(msg);
            }
        },
        buildErrorMessage:function(msg){
            alert(msg);
        }
    };

    <?php if($use_new_pt_version) { ?>

        $(function(){

            iapiSetCallout('Login', calloutLogin);

            $.ajax({
                url: player_url+'/async/get_user_info/'+game_platform_id,
                type: 'GET',
                data: null,
                dataType: 'jsonp',
                cache: false
            }).done(
                function(data){
                if(data && data['key'] && data['secret']){
                    login(data['key'].toUpperCase(),data['secret'],data['lang']);
                }else{
                    utils.buildErrorMessage('请重新登录');
                }
            }).fail(
                function(){
                    utils.buildErrorMessage('加载PT失败');
                }
            );
        });


        function calloutLogin(response){
        let errCode = response.errorCode;
        let vpn_error_response = "<?php echo lang('login_pt_via_vpn'); ?>";

            if (errCode) {
                if(errCode==48){
                    utils.buildErrorMessage(vpn_error_response);
                }else if(errCode==12 && response['playerMessage']){
                    utils.buildErrorMessage(response['playerMessage']);
                }else if(response['playerMessage']){
                    utils.buildErrorMessage(response['playerMessage']);
                }else{
                    utils.buildErrorMessage('登录PT失败');
                }

                $(".retry_link").show();
            } else {
                launchGame();
            }
        }

        function login(username,password,lang) {
            let mode = "<?php echo $game_mode ?>";
            if (mode == 'real') {
                iapiSetClientType('casino');
                iapiSetClientPlatform('web');
                iapiLogin(username, password, 1, lang);
            } else {
                // mode is offline, which does not require login. NOTE: only supports client with ngm_desktop and ngm_mobile
                launchGameWithFunMode();
            }
        }

        function launchGame() {
            // Get variables
            let client = "<?php echo $client ?>";
            let mode = "<?php echo $game_mode ?>";
            let game = "<?php echo $game_code; ?>";
            let lang = "<?php echo $lang ?>";
            let real = (mode == 'real') ? 1 : 0;
            // Optional Variables
            let mobile_logout_url = "<?php echo $mobile_logout_url ?>";
            let home_link = "<?php echo $home_link ?>";
            let mobile_lobby = "<?php echo $mobile_lobby ?>";
            let logout_uri = "<?php echo "/".$logout_uri ?>";
            let lobbyUrl = (mobile_lobby == "") ? "<?php echo $home_link ?>" : mobile_lobby;
            let logoutUrl = (mobile_logout_url == "") ? (home_link +"/"+ logout_uri) : mobile_logout_url;
            let supportUrl = "<?php echo $support_url ?>";
            let depositUrl = "<?php echo $deposit_url ?>";

            // Slots,Table Games and other non-live games
            if (client == 'ngm_desktop' || client == 'ngm_mobile') {
                iapiSetClientParams(client, 'language=' + lang + '&real=' + real + '&lobby=' + lobbyUrl + '&logout=' + logoutUrl + '&deposit=' + depositUrl + '&support=' + supportUrl);
                iapiLaunchClient(client, game, mode, '_self');
            }

            // Live Games
            if (client == 'live_desk' || client == 'live_mob') {
                iapiSetClientParams(client, '&launch_alias=' + game + '&language=' + lang + '&real=' + real + '&lobby=' + lobbyUrl + '&logout=' + logoutUrl + '&deposit=' + depositUrl + '&support=' + supportUrl);
                iapiLaunchClient(client, null, mode, '_self');
            }

        }
        /** fun mode only that's why we use static game launch url */
        function launchGameWithFunMode() {
            // Get variables
            let client = "<?php echo $client ?>";
            let game = "<?php echo $game_code ?>";;
            let lang = "<?php echo $lang ?>";
            if (client == 'ngm_desktop') {
                window.open('https://cachedownload-am.hotspin88.com/ngmdesktop/casinoclient.html?game=' + game + '&preferedmode=offline&language=' + lang + '&real=0', '_self');
            }

            if (client == 'ngm_mobile') {
                window.open('https://games-am.hotspin88.com/casinomobile/casinoclient.html?game=' + game + '&preferedmode=offline&language=' + lang + '&real=0', '_self');
            }
        }

        function getParam(val) {
            var result = "Not found",
                tmp = [];
            var items = location.search.substr(1).split("&");
            for (var index = 0; index < items.length; index++) {
                tmp = items[index].split("=");
                if (tmp[0] === val) result = decodeURIComponent(tmp[1]);
            }
            return result;
        }

        function getUrlVars() {
            var vars = {};
            var parts = window.location.href.replace(/[?&]+([^=&]+)=([^&]*)/gi, function(m,key,value) {
            vars[key] = value;
            });
            return vars;

    }
    <?php }else{ ?>
        ///////////////////////// OLD PT HERE /////////////////////////////////////////////

        var currentgame = 'ngm';
        if(typeof mobile != 'undefined' && (mobile=='mobile' || mobile=='true')){
            // utils.safelog("7.) ismobile: "+mobile+"-"+gameCode);
            iapiSetClientPlatform("mobile&deliveryPlatform=HTML5");
        }

        iapiSetCallout('Login', calloutLogin);
        iapiSetCallout('GetTemporaryAuthenticationToken', calloutGetTemporaryAuthenticationToken);

        function calloutLogin(response){
            // utils.safelog("6.) response: "+response['errorCode']);
            let vpn_error_response = "<?php echo lang('login_pt_via_vpn'); ?>";

            if (response['errorCode']) {
                // utils.safelog(response);
                if(response['errorCode']==48){
                    utils.buildErrorMessage(vpn_error_response);
                }else if(response['errorCode']==12 && response['playerMessage']){
                    utils.buildErrorMessage(response['playerMessage']);
                }else if(response['playerMessage']){
                    utils.buildErrorMessage(response['playerMessage']);
                }else{
                    utils.buildErrorMessage('登录PT失败');// = 'Login failed. ' + response.playerMessage;
                }
                $(".retry_link").show();
            } else {
                // var mobile=getParam('mobile');
                // var gameCode=getParam('game_code');
                if(typeof mobile != 'undefined' && (mobile=='mobile' || mobile=='true')){
                    var realMode = 1;
                    iapiRequestTemporaryToken(realMode, mobile_systemId, 'GamePlay');
                }else{
                    window.location = api_play_pt +"/casinoclient.html?language="+lang+"&nolobby=1&game="+gameCode;
                }
            }
        }

        function calloutGetTemporaryAuthenticationToken(response) {
            if (response.errorCode) {
                // utils.safelog("8.) token failed!");
                alert("Token failed. " + response.playerMessage + " Error code: " + response.errorCode);
            }
            else {
                // utils.safelog("8.) sessionToken: "+response.sessionToken.sessionToken);
                launchMobileClient(response.sessionToken.sessionToken);
            }
        }

        function launchMobileClient(temptoken) {
            utils.safelog("temptoken: "+temptoken);
            $.ajax({
                url: player_url+'/async/get_user_info/'+game_platform_id,
                type: 'GET',
                data: null,
                dataType: 'jsonp',
                cache: false
            }).done(
                function(data){                
                    // var clientUrl = mobile_launcher + 'igaming/?gameId=' + gameCode + '&real=1' + '&username=' + data["key"].toUpperCase() + '&lang='+ lang + '&tempToken=' + temptoken + '&lobby=' + mobile_lobby + '&support=' + location.href.substring(0,location.href.lastIndexOf('/')+1) + 'support.html' + '&logout=' + location.href.substring(0,location.href.lastIndexOf('/')+1) + 'logout.html';
                    var clientUrl = mobile_launcher + 'igaming/?gameId=' + gameCode + '&real=1' + '&username=' + data["key"].toUpperCase() + '&lang='+ lang + '&tempToken=' + temptoken + '&lobby=' + mobile_lobby + '&deposit=' + deposit_url + '&support=' + support_url + '&logout=' + mobile_logout_url;
                    utils.safelog("clientUrl: "+clientUrl);
                    window.location = clientUrl;
                }
            );
        }

        function getParam(val) {
            var result = "Not found",
                tmp = [];
            var items = location.search.substr(1).split("&");
            for (var index = 0; index < items.length; index++) {
                tmp = items[index].split("=");
                if (tmp[0] === val) result = decodeURIComponent(tmp[1]);
            }
            return result;
        }

        function getUrlVars() {
            var vars = {};
            var parts = window.location.href.replace(/[?&]+([^=&]+)=([^&]*)/gi, function(m,key,value) {
            vars[key] = value;
            });
            return vars;

        }

        $.ajax({
            url: player_url+'/async/get_user_info/'+game_platform_id,
            type: 'GET',
            data: null,
            dataType: 'jsonp',
            cache: false
        }).done(
            function(data){
            // utils.safelog("1.) ismobile: "+mobile);
            // utils.safelog("2.) data: "+data);
            // utils.safelog("3.) data['key']: "+data['key']);
            // utils.safelog("4.) data['secret']: "+data['secret']);
            // utils.safelog("5.) data['lang']: "+data['lang']);
            if(data && data['key'] && data['secret']){
                iapiLogin(data['key'].toUpperCase(), data['secret'], '1', data['lang']);
            }else{
                utils.buildErrorMessage('请重新登录');
            }
        }).fail(
            function(){
                utils.buildErrorMessage('加载PT失败');
            }
        );

        // var ptjs = document.createElement('script');
        // ptjs.setAttribute("type","text/javascript");
        // ptjs.setAttribute("src", "<?=$this->utils->playerResUrl('pt.js')?>");
        // document.body.appendChild(ptjs);
    <?php } ?>
</script>
</body>
</html>