<!-- <!DOCTYPE html> -->
<html>
<head>
    <title>BETBY TESTING</title>

    <script src="https://ui.invisiblesport.com/bt-renderer.min.js"></script>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script>
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
    <script>
        
    </script>
</head>
<body onload="loadBetBy()">
<div class="container">
<h2>Modal Example</h2>
<!-- Trigger the modal with a button -->
<button type="button" id= "login-registration" class="btn btn-info btn-lg" data-toggle="modal" data-target="#myModal">Open Modal</button>

<!-- Modal -->
<div class="modal fade" id="myModal" role="dialog">
<div class="modal-dialog">

  <!-- Modal content-->
  <div class="modal-content">
    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal">&times;</button>
      <h4 class="modal-title">Modal Header</h4>
    </div>
    <div class="modal-body">
      <p>Some text in the modal.</p>
    </div>
    <div class="modal-footer">
      <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
    </div>
  </div>
  
</div>
</div>

</div>

    <div id="betby"></div>
</body>
<script>
    var HttpClient = function() {
        this.get = function(url, callback) {
            var httpRequest = new XMLHttpRequest();
            httpRequest.onreadystatechange = function() { 
                if (httpRequest.readyState == 4 && httpRequest.status == 200)
                    callback(httpRequest.responseText);
            }

            httpRequest.open( "GET", url, true );            
            httpRequest.send( null );
        }
    }
    function loadBetBy() { 
        var client = new HttpClient();
        client.get('/player_center/auth_betby', function(response) {  
            data = JSON.parse(response);
            // console.log(response);
            var bt = new BTRenderer().initialize({
                brand_id: data.brand_id,
                token: data.jwt_token,
                onTokenExpired: function() {},
                themeName: data.theme,
                lang: data.lang,
                target: document.getElementById('betby'),
                betSlipOffsetTop: 0,
                betslipZIndex: 999,
                cssUrls: [],
                fontFamilies: ['Montserrat, sans-serif', 'Roboto, sans-serif'],
                onRouteChange: function() {},
                onLogin: function() {
                    document.getElementById('login-registration').click();
                    // window.top.location.href = data.login_url;
                },
                onRegister: function() {
                    window.top.location.href = data.register_url;
                },
                onSessionRefresh: function() {},
                onBetSlipStateChange: function() {}
            })
        });
    }
    
    </script>
</html>