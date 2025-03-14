<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title><?php echo @$platformName; ?></title>
    <style>
        body {
            background-color: white;
        }
        .iframe-content {
            margin: 0 auto;
            padding-bottom: 100px
        }
        .gameIframe {
            min-width: 100%;
            height: 1410px;
        }
    </style>
</head>
<body>
    <!-- <div id="iframe-content" class="iframe-content"> -->
    <div id="iframe-content">
        <iframe
            frameborder="0"
            allow="fullscreen" 
            scrolling="no"
            src="<?php echo $url; ?>"
            class="gameIframe",
            id="inner_iframe"
            >
        </iframe>
    </div>
</body>
</html>