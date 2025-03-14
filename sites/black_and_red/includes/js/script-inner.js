//Materialize Initialization Script
$(document).ready(function () {
    var pathname = top.window.location.pathname;
    if (pathname && pathname !== "/" && false) {
        top.window.location.href = "/?target=" + encodeURIComponent(pathname + top.window.location.search);
        return;
    }

    $('.sidenav').sidenav();

    $('.tabs').tabs();

    // all top_link link target top window
    $("a.top_link").attr("target", "_top");

    setTimeout(() => {
        $('#loading').hide()
    }, 2500)
});