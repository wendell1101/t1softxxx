var getUrlParameter = function getUrlParameter(sParam) {
    var sPageURL = window.location.search.substring(1),
        sURLVariables = sPageURL.split('&'),
        sParameterName,
        i;

    for (i = 0; i < sURLVariables.length; i++) {
        sParameterName = sURLVariables[i].split('=');

        if (sParameterName[0] === sParam) {
            return typeof sParameterName[1] === undefined ? true : decodeURIComponent(sParameterName[1]);
        }
    }
    return false;
};

//Materialize Initialization Script
$(document).ready(function () {
    $("#contentFrame").on("load", function () {

        // On scroll on header
        var iframe = $(this).contents();

        const scrollHeader = function () {
            var scrollTop = $(this).scrollTop();

            if (scrollTop >= 50) {
                $(".top__header__wrapper").addClass("is-sticky");
                $(".top__header__wrapper").css("top", 0);
            } else {
                $(".top__header__wrapper").removeClass("is-sticky");
                $(".top__header__wrapper").css("top", -scrollTop + "px");
            }
        }

        // reset header adter frame loaded
        scrollHeader();

        $(iframe).scroll(scrollHeader);

        try {
            var subWindow = this.contentWindow;
            var frameLocation = subWindow.location.href;

            updateStatus(frameLocation);
        } catch { }

    });

    var updateStatus = function (frameLocation) {
        if (frameLocation) {
            // nav active status
            $("nav .nav-wrapper ul.left li a").each(function () {
                const href = $(this).data('href')
                if (href && frameLocation.indexOf(href) !== -1) {
                    $(this).addClass("active");
                } else {
                    $(this).removeClass("active");
                }
            });

            //page name
            if (frameLocation.indexOf("crash.html") > -1) {
                $('.inner__header__wrapper .page__name').text('Crash');
            }
            if (frameLocation.indexOf("double.html") > -1) {
                $('.inner__header__wrapper .page__name').text('Double');
            }
            if (frameLocation.indexOf("promotion.html") > -1) {
                $('.inner__header__wrapper .page__name').text('Promotion');
            }
            if (frameLocation.indexOf("vip.html") > -1) {
                $('.inner__header__wrapper .page__name').text('VIP Club');
            }
            if (frameLocation.indexOf("live-casino.html") > -1) {
                $('.inner__header__wrapper .page__name').text('真人游戏');
            }
            if (frameLocation.indexOf("slots.html") > -1) {
                $('.inner__header__wrapper .page__name').text('Slots');
            }
        }
    }

    var redirectContentTo = function (t) {
        $("#contentFrame").attr("src", t);
        updateStatus(t);
    }

    // set contentFrame when document loaded
    var target = getUrlParameter('target');
    if (!target || true) redirectContentTo("//www.og.local/smash.home.html?dbg=1"); // UPDATE For DEV.
    // if (!target) redirectContentTo("home.html?dbg=1"); // UPDATE For DEV.
    else {
        target = decodeURIComponent(target);
        if (target[0] !== "/") target = "/" + target;
        console.log(target);
        redirectContentTo(target);
    }

    // nav click event handler
    $("nav .nav-wrapper .nav__link a").each(function () {
        $(this).click(function () {
            const target = $(this).data('href');
            if (target) {
                redirectContentTo(target);
            }
        })
    });

    $('.sidenav').sidenav();

    $('.tabs').tabs();

    $("a.brand-logo").click(function () {
        $("header nav").toggleClass("hide-menu");
    });

    $('a.brand-logo').on('click', function () {
        $("body").toggleClass("om-side");
    });
});

_export_sbe_t1t.on('not_login.t1t.player', function (e, player) {
    // When user is not login you can add your script here
    let url = window.location.href;
    let fcrash = url + encodeURIComponent('?target=crash.html');
    let fdouble = url + encodeURIComponent('?target=double.html');
    let fdice = url + encodeURIComponent('?target=dice.html');
    $('li.crash a').attr('href', '//' + _export_sbe_t1t.variables.hosts.player + '/iframe/auth/login?referrer=' + fcrash);
    $('li.double a').attr('href', '//' + _export_sbe_t1t.variables.hosts.player + '/iframe/auth/login?referrer=' + fdouble);
    $('.dice a').attr('href', '//' + _export_sbe_t1t.variables.hosts.player + '/iframe/auth/login?referrer=' + fdice);
    // $('li.crash a').attr('href', '/iframe/auth/login?referrer='+fcrash);
    // $('li.double a').attr('href', '/iframe/auth/login?referrer='+fdouble);
});

_export_sbe_t1t.on('logged.t1t.player', function (e, player) {
    // When user is login you can add your script here
    $('li.crash a').data('href', '/crash.html');
    $('li.double a').data('href', '/double.html');
    $('li.dice a').data('href', '/dice.html');
    const refreshBtn = document.getElementById("refresh-button");
    const refreshImg = refreshBtn.getElementsByTagName("IMG");
    let rotateDeg = 0;

    refreshBtn.addEventListener("click", () => {
        rotateDeg += 360;
        refreshImg[0].style.transition = 'all 0.5s ease-in-out';
        refreshImg[0].style.transform = `rotate(${rotateDeg}deg)`
    });

    console.log('logged in')

});

_export_sbe_t1t.on('logout.t1t.player', function (e) {
    // When user is got logged out you can add your script here
});


// Initialize Dropdown
$('.dropdown-trigger').dropdown();
