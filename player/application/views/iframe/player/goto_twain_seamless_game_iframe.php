<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title><?php echo @$platformName; ?></title>
    <style>
        html,
        body {
            height: 100%;
        }
 
        html {
            overflow: hidden;
        }
 
        body {
            margin: 0;
        }

        .game_iframe {
            width: 100%;
            height: 100%;
            min-height: 500px;
            border: 0;
        }
    </style>
</head>
<body>
    <iframe
        name="twainsport_iframe"
        allow="autoplay; fullscreen; encrypted-media; clipboard-write self <?php echo isset($params['clientUrl']) ? $params['clientUrl'] : null; ?>"
        src="<?php echo $url; ?>"
        class="game_iframe">
    </iframe>
</body>
</html>