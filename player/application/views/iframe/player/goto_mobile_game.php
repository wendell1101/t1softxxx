<!DOCTYPE html>
<html lang="en">
<head>
<meta name="viewport" content="width=device-width,user-scalable=no,initial-scale=1,minimum-scale=1,maximum-scale=1" content="user-scalable=no">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title><?php echo @$platformName; ?></title>
<meta http-equiv="X-UA-Compatible" content="IE=edge" />
<!-- <link rel="shortcut icon" href="/<?=$this->utils->getPlayerCenterTemplate()?>/fav.ico" type="image/x-icon" /> -->
<link rel="shortcut icon" href="<?= !empty($favicon_brand) ? $favicon_brand : $this->utils->getPlayerCenterFaviconURL(); ?>" type="image/x-icon" />
<link rel="stylesheet" href="/stable_center2/css/font-awesome.min.css?v=<?php echo $this->CI->utils->getCmsVersion(); ?>" />
<style>

*{padding:0;margin:0;}
    html , body {height:100%;overflow:hidden;}
    iframe{border:none;}
    body {
        background-color: #ffffff;
        font-family: Tahoma, Helvetica, Arial, "Microsoft Yahei", 微软雅黑, STXihei, 华文细黑, sans-serif;
        padding: 0;
        margin: 0;
    }
    .container {
        box-sizing: border-box;
        width: 100%;
        height: 100%;
        overflow: auto;
    }
    .row {
        margin-right: -15px;
        margin-left: -15px;
    }
    .content {
        height: 100%;
    }
    .container .row.content>.fishing-content {
        display: flex;
        justify-content: center;
        align-items: center;
        height: 100%;
    }
    .fishing-size {
        width: 70%;
    }
    .img-logo {
        display: flex;
        justify-content: center;
        align-items: center;
        margin-top: -100px;
    }
    a.fishing-playbtn {
        background-color: #004595;
        margin: 0 auto;
        border: 0;
        height: 55px;
        width: 85%;
        line-height: 56px;
        color: #fff;
        border-radius: 5px;
        font-weight: 600;
        text-decoration: none;
        font-size: 15px;
        text-align: center;
        display: block;
        padding: 0 0px;
        text-transform: uppercase;
    }
    a.fishing-downloadbtn {
        background-color: #004595;
        margin: 0 auto;
        border: 0;
        height: 55px;
        width: 85%;
        color: #fff;
        border-radius: 5px;
        font-weight: 600;
        text-decoration: none;
        font-size: 15px;
        text-align: center;
        display: block;
        padding: 0 0px;
        line-height: 56px;
        text-transform: uppercase;
    }
    .fa-gamepad:before {
        content: "\f11b";
        margin-right: 15px;
        font-size: 25px;
    }
    .fa-download:before {
        content: "\f019";
        font-size: 25px;
        margin-right: 15px;
    }
    .main-content {
        display: flex;
        justify-content: center;
        align-items: center;
        align-self: center;
        flex-direction: column;
        row-gap: 30px;
        margin: 5% 0;
        padding: 50px;
    }
    .logo-content {
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 5px;
        width: 100%;
    }
    .play-content {
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 5px;
        width: 100%;
    }
    .download-content {
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 5px;
        width: 100%;
    }
    .game-logo {
        width: 150px;
    }
    .main-btn {
        color: #ffffff;
        background-color: #0584C6;
        line-height: 30px;
        text-transform: uppercase;
        border: 0;
        border-radius: 5px;
        padding: 10px 5px;
        font-weight: 600;
        text-decoration: none;
        font-size: 15px;
        text-align: center;
        max-width: 50%;
        width: 100%;
    }
    @media only screen and (max-device-width: 600px) {
        .main-content {
            margin: 20% 0;
            padding: 5px;
        }
        .main-btn {
            max-width: 80%;
        }
    }
</style>
</head>
<body onload="launch_mobile_game()">
<!-- <?php if(isset($empty) && $empty) { echo lang('goto_game.sysMaintenance'); } else { ?> -->
    <div class="container">
        <div class="main-content">
            <div class="logo-content">
            <?php if(!empty($logo_url)) { ?>
                <img class="game-logo" id="gameLogo" src="<?php echo $logo_url; ?>">
            <?php } ?>
            </div>
            <div class="play-content">
                <a href="<?php echo $url; ?>" id="playBtn" class="main-btn">
                    <i class="fa fa-gamepad"></i><?php echo lang('goto_game.tapToPlay') ?>
                </a>
            </div>
            <div class="download-content">
                <a href="<?php echo $download_url; ?>" id="downloadBtn" class="main-btn"><i class="fa fa-download">
                    </i><?php echo lang('goto_game.downloadApp'); ?>
                </a>
            </div>
        </div>
    </div>
    <?php } ?>
</body>
<script type="text/javascript" src="<?= $this->utils->jsUrl('jquery-2.1.4.min.js') ?>"></script>
<script type="text/javascript">
let platformName = '<?= $platformName ?>';
let url = '<?= $url ?>';

$(document).ready(function() {
    //Customize theme style
    switch(platformName) {
        case 'LIONKING_GAME_API':
            $("#playBtn").css({
                "color": "#ffffff",
                "background-color": "#9c2604"
            });
            $("#downloadBtn").css({
                "color": "#ffffff",
                "background-color": "#531fd0"
            });
            break;
        default:
            $("#playBtn").css({
                "color": "#ffffff",
                "background-color": "#0584C6"
            });
            $("#downloadBtn").css({
                "color": "#ffffff",
                "background-color": "#0584C6"
            });
            break;
    }
});

function launch_mobile_game() {
    window.location = url;
}
</script>
</html>
