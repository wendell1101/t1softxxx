<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="viewport" content="width=device-width,user-scalable=no,initial-scale=1,minimum-scale=1,maximum-scale=1" content="user-scalable=no">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title><?php echo $platformName;?></title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <link rel="icon" href="/favicon.ico" type="image/x-icon" />
    <style>
        *{
            padding: 0;
            margin: 0;
        }
        html , body {
            height:100%;
        }
        iframe{
            border: none;
        }
    </style>
</head>
<body>
<iframe id="wazdan_iframe" name="wazdan_iframe" width="100%" height="100%" src="<?= $url; ?>" allow="fullscreen"></iframe>
<script>
//window.postMessage({'method': 'WGEAPI.status.initializing'}, '*');

</script>
</body>
</html>