<!DOCTYPE html>
<html lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title><?php echo $platformName;?></title>
<meta http-equiv="X-UA-Compatible" content="IE=edge" />
<link rel="icon" href="/favicon.ico" type="image/x-icon" />
<style>
*{padding:0;margin:0;}
html , body {height:100%;}
iframe{border:none;}
</style>
</head>
<body onload="document.ab_form.submit()">
<form name="ab_form" method="POST" action="<?php echo $url;?>" target="ab_iframe"></form>
<iframe name="ab_iframe" width="100%" height="100%" src="about:blank" allowfullscreen="true"></iframe>
</body>
</html>