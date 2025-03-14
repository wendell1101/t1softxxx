<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title><?php echo @$platformName; ?></title>
    <style>
        body {
            margin: 0;
        }

        iframe{
            margin: 0;
            padding: 0;
            border: 0;
        }

        .game_iframe {
            min-width: 100vw;
            width: 100vw;
            height: 100vh; /* Full height of the viewport */
        }
    </style>
</head>
<body>
    <iframe
        allow="clipboard-write; autoplay;"
        scrolling="no"
        frameborder="0"
        src="<?php echo $url; ?>" 
        class="game_iframe" 
        id="aviatrix">
    </iframe>
</body>
</html>