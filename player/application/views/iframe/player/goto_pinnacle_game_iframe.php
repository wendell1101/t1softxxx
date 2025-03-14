<!DOCTYPE html>
<html lang="en">
    <head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title><?php echo @$platformName; ?></title>
    <style type="text/css">
        html, body, div {
            height: 100%;
            width: 100%;
            margin: 0;
            overflow :hidden;
        }
        iframe {
            height: 100%;
            width: 100%;
        }

        .iframeContainer {
            box-sizing: border-box;
            margin: 0;
            height: 917px;  /* set the default height for all type for views (desktop) */
            border: 1px solid #333333;
        }

        @media screen and (max-width: 768px) { /* condition to identify the mobile view */
            .iframeContainer {
                height: calc(100vh - 115px);
            }
        } /* this line to calculate the iframe height with 115px is the height from the top to the iframe */
    </style>
    <script>
        // Allow window to listen for a postMessage
        var pinnacleOrigin = "<?= $origin; ?>";
        window.addEventListener("message", (event) => {
            // check pinnacle origin
            if (event.origin && event.origin.toLowerCase().endsWith(pinnacleOrigin)) {
                var postData = event.data;
                switch (postData.action) {
                    case 'OPEN_WINDOW':
                        var url = postData.url;
                        window.open(url);
                        break;
                    default:
                        break;
                }
            }
        });
    </script>
</head>
<body>

<!-- <iframe id="gameFrame" src="<?= $url; ?>" scrolling="no" noresize="noresize"></iframe> -->

<div class="iframeContainer">
	<iframe id="gameFrame" src="<?= $url; ?>" title="background-iframe" scrolling="yes" noresize="noresize"></iframe>
</div>

</body>
</html>