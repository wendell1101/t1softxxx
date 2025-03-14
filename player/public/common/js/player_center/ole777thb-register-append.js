var ole777thb_iframe_register = {
    append_custom_js: function(){
        var head_specify_script = "<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start': new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0], j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','GTM-N6LQ5WJ');</script>";
        $('head').append(head_specify_script);

        var body_specify_script = "<noscript><iframe src='https://www.googletagmanager.com/ns.html?id=GTM-N6LQ5WJ' height='0' width='0' style='display:none;visibility:hidden'></iframe></noscript>";
        $('body').append(body_specify_script);
    }
};