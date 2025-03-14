<!DOCTYPE html>
<html lang="en">
<head>
<meta name="viewport" content="width=device-width,user-scalable=no,initial-scale=1,minimum-scale=1,maximum-scale=1" content="user-scalable=no">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title><?php echo @$platformName; ?></title>
<meta http-equiv="X-UA-Compatible" content="IE=edge" />
<!-- <link rel="shortcut icon" href="/<?=$this->utils->getPlayerCenterTemplate()?>/fav.ico" type="image/x-icon" /> -->
<link rel="shortcut icon" href="<?= !empty($favicon_brand) ? $favicon_brand : $this->utils->getPlayerCenterFaviconURL(); ?>" type="image/x-icon" />
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
<script type="text/javascript" src='<?php echo $this->utils->jsUrl("jquery-1.11.1.min.js"); ?>'></script>
</head>
<body>
	<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div>
    <ul class="navbar-nav">
      <li class="nav-item active">
        <a class="nav-link" >Player: <?php echo $player_name ?></a>
      </li>
      <li class="nav-item active">
        <a class="nav-link" >Balance: <span id="playerBalance"></span></a>
      </li>
    </ul>
  </div>
  <div>
    <button type="button" class="btn btn-default btn-xs" onclick="refreshBalance(2207)" aria-label="Left Align">
      <span class="glyphicon glyphicon-refresh"></span>
    </button>
  </div>
</nav>
	<script type="text/javascript">
		var _bt = _bt || [];
		var now = Date.now();
		_bt.push(['server', '<?php echo $url['your_production_server']?>']);
		_bt.push(['partner', '<?php echo $url['your_partner_code'] ?>']);
		_bt.push(['token', '<?php echo $url['player_token'] ?>']);
		_bt.push(['language', '<?php echo $url['language_code'] ?>']);
		_bt.push(['timezone', '<?php echo $url['timezone_utc'] ?>']);
		console.log(_bt);

		(function(){
    document.write('<'+'script type="text/javascript" src="<?php echo $url['your_production_server']; ?>/design/client/js/betgames.js?ts=' + now + '"><'+'/script>');
    })();
	</script>
	<script type="text/javascript">
		BetGames.frame(_bt);
	</script>
	
</body>
</html>
<script type="text/javascript">
  $(document).ready(function() {
    getData();
  });

  function getData() {
      $.ajax({
          type: "GET",
          url: "/async/player_query_balance_for_betgames/"+2207,
          async: true,
          timeout: 50000,
          dataType: 'json',
          success: function(data) {
            console.log(data.balance);
            var bal = data.balance;
              $("#playerBalance").html(bal);

              setTimeout("getData()", 10000);
          }
      });
  }

  function refreshBalance(game_platform_id) {
      setTimeout(function() {
        $.ajax({
          type: "GET",
          url: "/async/player_query_balance_for_betgames/"+game_platform_id,
          dataType: 'json',
          success: function(data) {
            console.log(data.success);
            var bal = data.balance;
              if(data.success){
                $("#playerBalance").html(bal);
              }
          }
        });
      }, 1000);
  }
</script>
