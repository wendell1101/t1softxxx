<!DOCTYPE html>
<html lang="en">
<head>
<meta name="viewport" content="width=device-width,user-scalable=no,initial-scale=1,minimum-scale=1,maximum-scale=1" content="user-scalable=no">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title><?php echo @$platformName; ?></title>
<meta http-equiv="X-UA-Compatible" content="IE=edge" />
<!-- <link rel="shortcut icon" href="/<?=$this->utils->getPlayerCenterTemplate()?>/fav.ico" type="image/x-icon" /> -->
<link rel="shortcut icon" href="<?= !empty($favicon_brand) ? $favicon_brand : $this->utils->getPlayerCenterFaviconURL(); ?>" type="image/x-icon" />
</head>
<body>
	<h1>Token: <?php echo $token?></h1>
</body>
</html>
