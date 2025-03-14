(function(){
    window.addEventListener('message',function(event) {
        fullScreen();
    },false);
    var fullScreen = function(){
        var document= window.document;
        var fullScreenElement = document.fullscreenElement || document.webkitFullscreenElement || document.mozFullscreenElement || document.msFullscreenElement;
        if (fullScreenElement) {
            var exitFullscreen = document.exitFullscreen || document.webkitExitFullscreen || document.mozExitFullscreen || document.msExitFullscreen
            exitFullscreen.call(document);
            var contentFrame = document.querySelector("iframe");
            contentFrame && contentFrame.contentWindow.postMessage({fullScreen:false},"*");
        } else {
            var documentElement = document.documentElement;
            var requestFullscreen = documentElement.requestFullscreen || documentElement.webkitRequestFullscreen || documentElement.mozRequestFullscreen || documentElement.msRequestFullscreen
            requestFullscreen.call(documentElement);
            var contentFrame = document.querySelector("iframe");
            contentFrame && contentFrame.contentWindow.postMessage({fullScreen:true},"*");
        }
    }
})();