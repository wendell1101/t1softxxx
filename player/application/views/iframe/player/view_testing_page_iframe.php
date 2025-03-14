<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Test Front end</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.1/css/bootstrap.min.css" integrity="sha384-WskhaSGFgHYWDcbwN70/dfYBj47jz9qbsMId/iRN3ewGhXQFZCSftd1LZCfmhktB" crossorigin="anonymous">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.0.13/css/all.css" integrity="sha384-DNOHZ68U8hZfKXOrtjWvjxusGo9WQnrNx2sqG0tfsghAvtVlRW3tvkXWZh58N9jp" crossorigin="anonymous">
    <script src="https://code.jquery.com/jquery-3.3.1.min.js" integrity="sha256-FgpCb/KJQlLNfOu91ta32o/NMZxltwRo8QtmkMRdAu8=" crossorigin="anonymous"></script>
</head>
<body style="padding: 2rem; min-width: 320px;">
    <div class="container">
        <div class="row">
            <div class="col">
                <h1 style="margin-bottom: 2rem;">Testing front end for event listener</h1>
                <hr style="margin-bottom: 2rem;">
                <p class="status-message">Waiting for data from <code>og local</code>...</p>
                <div class="receive-data alert alert-info" style="display: none;">
                    <p class="data-message"></p>
                </div>
                <div class="embed-responsive embed-responsive-16by9" style="border: 1px solid rgba(0,0,0,.1); border-radius: 0.25rem; min-height: 400px;">
                    <iframe class="embed-responsive-item" src="<?= $iframe_url ?>"></iframe>
                </div>
            </div>
        </div>
    </div>
    <script type="text/javascript">
        // Determine how the browser should listen for messages from other
        // windows. If `addEventListener` exists, then use that. Otherwise, use
        // `attachEvent` because an older browser is probably being used. Also,
        // use a callback to handle messages so that both methods of "message
        // listening" can be routed to the same function. The callback in this
        // example is `handleMessage` and it will take one argument (the
        // `MessageEvent` object).
        if (window.addEventListener) {
            window.addEventListener("message", handleMessage);
            // window.addEventListener('popstate', function(event) {
            //     console.log('History change detected. Current URL:', window.location.href);
            //     event.preventDefault(); // For modern browsers
            // event.returnValue = ''; // For some browsers (required for prompt)
            // });
            window.addEventListener('beforeunload', function(event) {
                console.log('History change detected. Current URL:', window.location.href);
                const confirmationMessage = 'You have unsaved changes. Are you sure you want to leave?';
            
            // Modern browsers ignore the custom message and show their own standard message
            event.preventDefault(); // Standard for modern browsers
            event.returnValue = confirmationMessage; // Some browsers use this to display a default message
            
            // The dialog shown to the user is handled by the browser and cannot be customized
            });
            

        } else {
            window.attachEvent("onmessage", handleMessage);
        }

        /**
         * Handle a message that was sent from some window.
         *
         * @param {MessageEvent} event The MessageEvent object holding the message/data.
         */
        function handleMessage(event) {
            var dataFromIframe = event.data;
            // console.log("Received data", dataFromIframe);
            if (dataFromIframe.type  !== undefined){
                console.log("Received a message from " + event.origin + ".");
                console.log("Received data", dataFromIframe);
                
                $(".receive-data").slideDown();
                if (dataFromIframe.type == "gotoLobby") {
                    $(".receive-data .data-message").html("<strong>Message received. Goto lobby ...</strong>.");
                } else {
                    $(".receive-data .data-message").html("<strong>No post message go to lobby</strong>.");
                }
                
                // $(".receive-data").slideDown(200, function() {
                //     setTimeout(function() {
                //         // $(".receive-data").slideUp(200);
                //         $(".status-message").html("Waiting for data from <code>og local</code>...");
                //     }, 5000);
                // });
            }
            
        }

        // setInterval(checkForRedirect, 1000); 
        // function checkForRedirect() {
        //     url = window.location.href; 
        //     if (url.includes('/player_center/post_message_origin')) {
        //         console.log('The URL contains the specified pattern.');
        //         window.history.back();
        //     } else {
        //         console.log('The URL does not contain the specified pattern.');
        //     }
        // }
    </script>
</body>
</html>