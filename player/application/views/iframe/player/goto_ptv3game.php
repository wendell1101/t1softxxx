<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PT V3 Game Launching</title>
</head>

<body onload="login()">
    <!-- We do not support testing games via localhost -->
    <!-- Please host this html file on your server and assign one domain with it,
    remeber that domain needs to be whitelisted otherwise you will get error code 6 -->
    <!-- You can try testing games with fun mode.it doesnot require login.-->
    <div>
        <!-- <p id="loading_checking">Loading please wait...</p> -->
        <a href="javascript:window.location.reload();" id='retry_link' style='display:none'>Retry</a>
    </div>
    <script>
        let username = "<?= strtoupper($username); ?>";
        let password = "<?= $password; ?>";
        let lang = "<?= $lang; ?>";
        let client = "<?= $client; ?>";
        let mode = "<?= $mode; ?>";
        let game = "<?= $game; ?>";
        let loading_checking = document.getElementById("loading_checking");
        let retry_link = document.getElementById("retry_link");

        // Optional Variables
        let lobbyUrl = "<?= $lobbyUrl; ?>";
        let supportUrl = "<?= $supportUrl; ?>";
        let depositUrl = "<?= $depositUrl; ?>";
        let backUrl = "<?= $backUrl; ?>";
        let logoutUrl = "<?= $logoutUrl; ?>";
        let integration_script_js = "<?= $integration_script_js; ?>";
        let game_launch_url = "<?= $game_launch_url; ?>";

        console.log(username);

        function login() 
        {
            if(mode == 'real') 
            {
                iapiSetClientType('casino');
                iapiSetClientPlatform('web');
                iapiLogin(username, password, 1, lang);
            }else{
                // mode is offline, which does not require login. NOTE: only supports client with ngm_desktop and ngm_mobile
                launchGameWithFunMode();
            }
        }

        function launchGame(response) 
        {
            let real = (mode == 'real') ? 1 : 0;
            
            // Slots,Table Games and other non-live games
            if(client == 'ngm_desktop' || client == 'ngm_mobile')
            {
                iapiSetClientParams(client, 'language=' + lang + '&real=' + real + '&lobby=' + lobbyUrl + '&logout=' + logoutUrl + '&deposit=' + depositUrl + '&support=' + supportUrl + '&backurl=' + backUrl);
                iapiLaunchClient(client, game, mode, '_self');
            }
            // Live Games
            if(client == 'live_desk' || client == 'live_mob') 
            {
                iapiSetClientParams(client, '&launch_alias=' + game + '&language=' + lang + '&real=' + real + '&lobby=' + lobbyUrl + '&logout=' + logoutUrl + '&deposit=' + depositUrl + '&support=' + supportUrl);
                iapiLaunchClient(client, null, mode, '_self');
            }
            const sessionToken = response.rootSessionToken.sessionToken || null;
            const userName = response.username.username || null;
            const base_url = `${game_launch_url}/GameLauncher`;
            const launchUrl =  `${base_url}?gameCodeName=${game}&username=${userName}&tempToken=${sessionToken}&casino=agdragon&clientPlatform=web&language=${lang}&playMode=${real}&deposit=${depositUrl}&lobby=&swipeOff=true`;
            
            console.log('generatedLaunchUrl: '+launchUrl);
            window.location.href=launchUrl;
        }

        function launchGameWithFunMode() 
        {
            if (client == 'ngm_desktop' || client == 'ngm_mobile') 
            {
                iapiSetClientParams(client, 'language=' + lang + '&real=0');
                iapiLaunchClient(client, game, mode, '_self');
            }
            const base_url = `${game_launch_url}/GameLauncher`;
            const launchUrl =  `${base_url}?gameCodeName=${game}&casino=agdragon&clientPlatform=web&language=${lang}&playMode=0`;
            console.log('generatedLaunchUrlForDemo: '+launchUrl);
            window.location.href=launchUrl;
        }
        
        function calloutLogin(response) 
        {
            console.log('calloutLogin response: ', response);
            if (response.errorCode) 
            {
                // Login failed
                if (response.errorCode == 48) 
                {
                    alert('Login failed, error: ' + response.errorCode + ' playerMessage: ' + response.actions.PlayerActionShowMessage[0].message);
                    loading_checking.textContent = 'Login failed, error: ' + response.errorCode + ' playerMessage: ' + response.actions.PlayerActionShowMessage[0].message;
                    retry_link.style.display = "block";
                    //console.log(response);
                }else{
                    alert('Login failed, error: ' + response.errorCode + ' playerMessage: ' + response.playerMessage);
                    loading_checking.textContent = 'Login failed, error: ' + response.errorCode + ' playerMessage: ' + response.playerMessage;
                    retry_link.style.display = "block";
                    //console.log(response);
                    
                }

            }else{
                launchGame(response);
            }
        }
    </script>
    <script>
        let script = document.createElement('script');
        script.setAttribute('src', '<?= $integration_script_js?>');
        // script.setAttribute('src', 'https://login-am.hotspin88.com/jswrapper/hotspin88am/integration.js'); //old
        // script.setAttribute('src', 'https://login-am.nptgp.com/jswrapper/hotspin88am/integration.js');
        document.head.appendChild(script);
        // Set up callback after JS file is loaded
        script.onload = () => {
            iapiSetCallout('Login', calloutLogin);
        }
    </script>
</body>

</html>