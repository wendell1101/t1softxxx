<!DOCTYPE html>
<html lang="en">
    <head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title><?php echo @$platformName; ?></title>
    <style>
        html, body, div{
            height: 100%;
            width: 100%;
            margin: 0;
            overflow :hidden;
        }
        iframe{
            height: 100%;
            width: 100%;
        }
    </style>
</head>
<body>

<!--
rootURL - origin of the PNG coreweb.
htpps://www.webiste.com - operators page origin
-->

<iframe scrolling="no" noresize="noresize"src="<?php echo $url; ?>" id="gameFrame"></iframe>

<script>
    /* let operator_page_origin = "<?php echo $params['origin']; ?>"; */
    let api_domain = "<?php echo $api_domain; ?>";
    let lobby_url = "<?php echo $lobby_url; ?>";

    document.getElementById("gameFrame").onload = function () {
        GameCommunicator.init(document.getElementById("gameFrame"));
        GameCommunicator.postMessage({ messageType: "addEventListener", eventType: "reloadGame" });
        GameCommunicator.postMessage({ messageType: "addEventListener", eventType: "backToLobby" });
    }
/**
* GameCommuncator
* Basic implementation of window.postmessage communication with
Iframed PNG game.
*/
var GameCommunicator =
        {
            source: undefined,
            origin: undefined,
            /**
             * Initiates the communication with the Iframe
             * @@param {iframe} element
            */
            init: function (element) {
                window.addEventListener("message", this.processGameMessage.bind(this));
                this.source = element.contentWindow;
                this.origin = api_domain; //origin of PNG container launcher. iframe origin
            },
            /**
             * Sends the message to the Iframe
             * @@param {object} data
             * Example of adding an Engage event listener: GameCommunicator.postMessage({ messageType: "addEventListener", eventType: "roundStarted" })
             * Example of calling Engage function: GameCommunicator.postMessage({ messageType: "request", eventType: "spin" })
            */
            postMessage: function (data) {
                console.log("GameCommunicator sent the following message:", data);
                this.source.postMessage(data, this.origin);
            },
            /**
             * Receives the messages the PNG game dispatches
             * @@param {object} e
            */
            processGameMessage: function (e) {
                console.log("GameCommunicator received: ", e.data);
                switch (e.data.type) {
                    case "reloadGame":
                        console.log("reload code");
                        window.location.reload(); // stub implementation
                        break;
                    case "backToLobby":
                        console.log("backToLobby");
                        if("mobile" == "desktop") {
                            window.parent.postMessage({ type: 'rgs-backToHome', mainDomain: lobby_url }, '*');
                            // window.parent.postMessage({ type: 'backToLobby', mainDomain: lobby_url }, '*');
                        } 
                        else {
                            window.location.href = lobby_url;
                        }
                        break;
                    default:
                        // console.log("processGameMessage default", e.data.type);
                        break;
                }
            }
        }
</script>
</body>
</html>