<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width,user-scalable=no,initial-scale=1,minimum-scale=1,maximum-scale=1" content="user-scalable=no">
    <title><?php echo @$platformName; ?></title>

    <script type="text/javascript" src="<?php echo $this->utils->playerResUrl('jquery-1.11.3.min.js'); ?>"></script><link rel="shortcut icon" href="<?= !empty($favicon_brand) ? $favicon_brand : $this->utils->getPlayerCenterFaviconURL(); ?>" type="image/x-icon" />
    <style>
    *{padding:0;margin:0;}
    html , body {height:100%;overflow:hidden;}
    iframe{border:none;}
    #bodyMessage{padding:10px;}
    </style>
    <?php
    $min_width = !empty($set_min_width) ? 'style="min-width:'.$default_frame_min_width.';"' : '';
    $allow_fullscreen = ($allow_fullscreen) ? 'allowfullscreen="true"' : 'allowfullscreen="false"';
    $scrolling = !empty($no_scrolling) ? 'scrolling = "no"' : '';
    ?>
    <script type="text/javascript" src="<?php echo $api_play_pt_js; ?>"></script>
    <!-- <script type="text/javascript" src="https://login-ag.nptgp.com/jswrapper/integration.js.php?casino=agdragon"></script> -->
</head>
<body onload="login()">
<iframe id="bodyIframe"  <?php echo isset($iframeName) && !empty($iframeName) ? 'name="' . $iframeName . '"' : ''; ?> width="100%" height="100%" <?php echo $min_width; ?>  src="<?php echo isset($url)?$url:''; ?>" <?php echo $allow_fullscreen; ?> <?php echo $scrolling; ?> ></iframe>

<script type="text/javascript">

var game_platform_id="<?php echo (isset($game_platform_id)?$game_platform_id:1); ?>";
var gameCode="<?php echo $game_code; ?>";
var lang="<?php echo $lang; ?>";
var api_play_pt="<?php echo $api_play_pt; ?>";
var player_url="<?php echo $player_url; ?>";
var mobile="<?php echo ( isset( $mobile ) ) ? $mobile : ''; ?>";
var mobile_systemId="<?php echo ( isset( $mobile_systemId ) && ! empty($mobile_systemId) ) ? $mobile_systemId : ''; ?>";

var mobile_launcher = "<?=$mobile_launcher?>";
var mobile_lobby = "<?= $mobile_lobby ?>";
var mobile_logout_url = "<?= $mobile_logout_url ?>";
var deposit_url = "<?= $deposit_url ?>";

var username = "";

iapiSetCallout('LoginAndGetTempToken', calloutLogin);

function calloutLogin(response) {
    console.log(response);
    if (response.errorCode) {
        if (response.errorText !== undefined) {
            alert("[" + response.errorCode + "] " + response.errorText.replace("<br>", "\r\n"));
        }
        else if (response.playerMessage !== undefined) {
            alert("[" + response.errorCode + "] " + response.playerMessage.replace("<br>", "\r\n"));
        }
        else {
            alert("[" + response.errorCode + "] Login Fail.");
        }
    }
    else {
        // window.location.href = "https://login-ag.nptgp.com/GameLauncher?gameCodeName=" + gameCode + "&username=" + username
        // + "&tempToken=" + response.sessionToken.sessionToken + "&casino=" + "agdragon" + "&clientPlatform=web&language=" + lang
        // + "&playMode=" + 1 + "&deposit=" + "&lobby=" + "&swipeOff=true";

        launch_url =  `${api_play_pt}?gameCodeName=${gameCode}&username=${username}&tempToken=${response.sessionToken.sessionToken}&casino=agdragon&clientPlatform=web&language=${lang}&playMode=1&deposit=${deposit_url}&lobby=&swipeOff=true`;
        document.getElementById("bodyIframe").style.display = "block";
        document.getElementById("bodyIframe").src=launch_url;   
    }
}


function login() {
    $.ajax({
        url: player_url+'/async/get_user_info/'+game_platform_id,
        type: 'GET',
        data: null,
        dataType: 'jsonp',
        cache: false
    }).done(
        function(data){     
            console.log(data);   
            username = data["key"].toUpperCase();
            iapiLoginAndGetTempToken(username, data["secret"], 1, 'ZH-CN', mobile_systemId);
        }
    );
}

function test(){
    console.log(username);
}
    
</script>



</body>
</html>