<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width,user-scalable=no,initial-scale=1,minimum-scale=1,maximum-scale=1" content="user-scalable=no">
    <title><?php echo @$platformName; ?></title>
    <style>
    *{padding:0;margin:0;}
    html , body {height:100%;overflow:hidden;}
    iframe{border:none;}
    #bodyMessage{padding:10px;}
    </style>    
</head>
<body>
<div id="bodyMessage"><?php echo lang('Loading game'); ?>... <a href="javascript:window.location.reload();" class='retry_link' style='display:none'><?php echo lang('Reload'); ?></a></div>

<form id="myForm" action="<?php echo @$url; ?>"  method="post">
  <input type="hidden" name="session_id" value="<?php echo @$session_id; ?>">
  <input type="hidden" name="lang" value="<?php echo @$lang; ?>">
  <input type="hidden" name="login_id" value="<?php echo @$login_id; ?>">  
</form> 

</div>
<script type="text/javascript">
    document.addEventListener('DOMContentLoaded', function(event) {
        document.getElementById("myForm").submit();
    })
</script>
</body>
</html>