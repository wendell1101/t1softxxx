<?php
# TODO
$title = isset($platformName) ? $platformName : null;
$gameId = isset($gameId) ? $gameId : null;
$staticServerURL = isset($staticServerURL) ? $staticServerURL : null;
$gameServerURL = isset($gameServerURL) ? $gameServerURL : null;
$sessionId = isset($sessionId) ? $sessionId : null;
$walletMode = "seamlesswallet";
$gameJsUrl = isset($gameJsUrl) ? $gameJsUrl : null;
$gamePlatformMode = ($gamePlatformMode == "mobg") ? "mobile" : "web";
$gameMode = isset($gameMode) ? $gameMode : null;
$allowFullscreen = $allow_fullscreen;
$casinoBrand = isset($casinoBrand) ? $casinoBrand : null;
$lobbyURL = isset($lobbyUrl) ? $lobbyUrl : "#";
$platformName = !empty($platformName) ? $platformName : $title;

?>
<html>
  <head>
    <title><?php echo $title; ?></title>
    <script type="text/javascript" src="<?php echo $gameJsUrl;?>"></script>

    <script type="text/javascript">

      var webStartGame = function () {
            var demoSessionId = "DEMO-"+Math.floor(Math.random()*10000000)+"-THB";
            var webConfig = {
                gameId: "<?php echo $gameId; ?>",
                staticServerURL: "<?php echo $staticServerURL; ?>",
                gameServerURL: "<?php echo $gameServerURL ?>",
                sessionId: "<?echo $sessionId ?>",
                targetElement: "gameArea",
                walletMode : "<?php echo $walletMode; ?>",
                language: "<?php echo $lang ?>",
                fullScreen: "<?php echo $allowFullscreen ?>",
                width:"100%",
                height:"100%",
                enforceRatio:false,
                disabledevicedetection:true,
                casinoBrand: "<?php echo $casinoBrand ?>"
            };

            <?php if($gameMode != "real"){ ?>
              webConfig.sessionId = demoSessionId;
            <?php } ?>

            //alert(JSON.stringify(webConfig));

            // Game launch successful.
            var success = function (netEntExtend) {
                netEntExtend.addEventListener("gameReady", function () {
                    netEntExtend.get("volumeLevel", function (volumeLevel) {
                        console.log(volumeLevel);
                    });
                    netEntExtend.set("volumeLevel", 50);
                });
            };

            // Error handling here.
            var error = function (e) {
            };

        netent.launch(webConfig, success, error);
      };

      var mobileStartGame = function () {
            var demoSessionId = "DEMO-"+Math.floor(Math.random()*10000000)+"-THB";
            var mobileConfig = {
                gameId: "<?php echo $gameId; ?>",
                staticServerURL: "<?php echo $staticServerURL; ?>",
                gameServerURL: "<?php echo $gameServerURL ?>",
                language: "<?php echo $lang ?>",
                sessionId: "<?echo $sessionId ?>",
                targetElement: "gameAreaMobile",
                lobbyURL:"<?php echo $lobbyURL; ?>",
                fullScreen: "<?php echo $allowFullscreen ?>",
                width:"100%",
                height:"100%",
                enforceRatio:false,
                disabledevicedetection:true,
                launchType:"iframe",
                applicationType:"browser",
                casinoBrand: "<?php echo $casinoBrand; ?>",
                walletMode : "<?php echo $walletMode; ?>",
                iframeSandbox:"allow-scripts allow-popups allow-popups-to-escape-sandbox allow-top-navigation allow-top-navigation-by-user-activation allow-same-origin allow-forms allow-pointer-lock"
            };

            <?php if($gameMode != "real"){ ?>
              mobileConfig.sessionId = demoSessionId;
            <?php } ?>

            //alert(JSON.stringify(mobileConfig));

            // Game launch successful.
            var success = function (netEntExtend) {
                netEntExtend.addEventListener("gameReady", function () {
                    netEntExtend.get("volumeLevel", function (volumeLevel) {
                        console.log(volumeLevel);
                    });
                    netEntExtend.set("volumeLevel", 50);
                });
            };

            // Error handling here.
            var error = function (e) {
            };

        netent.launch(mobileConfig, success, error);
      };

      <?php if($gamePlatformMode == 'mobile'){ ?>
        // alert('this is mobile launch');
        window.addEventListener("load", mobileStartGame);
      <?php }else{ ?>
        // alert('this is web launch');
        window.addEventListener("load", webStartGame);
      <?php } ?>

    </script>
  </head>
  <body>
    <div id="gameArea"></div>
    <div id="gameAreaMobile">
        <iframe allowfullscreen="true" scrolling="auto"></iframe>
    </div>

    <style>
        #gameAreaMobile {
            position: relative;
            overflow: hidden;
            width:100%;
            height:60.85vw;
            margin:0 auto;
        }

        #gameAreaMobile iframe {
            position: absolute;
            top: 0;
            left:0;
            right: 0;
            bottom: 0;
            width: 100%;
            height: 100%;
        }
    </style>
  </body>
</html>