$(document).ready(function(){

    

    /*********************************************************************
    *Language swiching function
    *********************************************************************
    * switchLanguage
    *********************************************************************
    * @param {String} newDefault Language('en' , 'cn' or 'id').
    *********************************************************************/  

    function switchLanguage(newDefault){

        var lang = _export_smartbackend.variables.currentLang;
        // var currency = 'brl';

        switch(newDefault) {
            case 'en':
                lang = 1;
                // currency = 'brl';
            break;
            case 'pt':
                lang = 8;
                // currency = 'brl';
            break;
        }

        // $.ajax({
        //     url: _export_smartbackend.variables.apiBaseUrl+"/set_language/"+lang,
        //     type: 'GET',
        //     complete: function (data) {
                setCookie("_lang", newDefault);
                window.location.reload();
                // _export_smartbackend.renderUI.switchPlayerCurrency(currency);
        //     },
        // });

        // if(newDefault === 'en') {
        //   lang = 1;
        // }else if(newDefault === 'idr'){
        //   lang = 3;
        // } else if(newDefault === 'vn'){
        //   lang = 4;
        // } else if(newDefault === 'inr'){
        //    lang = 2;
        // 
    }


    /*********************************************************************
    *Language swiching function
    *********************************************************************
    * getLang
    *********************************************************************
    * return : language value being passed as cookie
    * i.e : 'en' or 'cn' 
    *********************************************************************/
    function getLang(){
        var currentLang = getCookie('_lang');
        if(currentLang){
            return currentLang;
        } else {
            setCookie("_lang", "pt");
            return getCookie('_lang');
        }
    }


    /*********************************************************************
    *Setting Cookie
    *********************************************************************
    * setCookie
    *********************************************************************
    * @param {String} key Cookie name.
    * i.e : '_lang'
    * ********************************************************************
    * @param {String} val Cookie value.
    * i.e : 'en' , 'cn' or 'id' 
    *********************************************************************/
    function setCookie(key, val) {
        var mainDomain = window.location.hostname.replace('www', '');
        var date = new Date();
        date.setTime(date.getTime() + (0.5 * 24 * 60 * 60 * 1000));
        expires = "; expires=" + date.toGMTString();
        var cookie = document.cookie = key + "=" + val + expires + ";domain="+ mainDomain +";path=/";
    }


    /*********************************************************************
    *Getting Cookie
    *********************************************************************
    * getCookie
    *********************************************************************
    * @param {String} key Cookie name.
    * i.e : '_lang'
    *********************************************************************/
    function getCookie(data) {
        var data_set = data + "=";
        var ca = document.cookie.split(';');
        for(var i=0;i < ca.length;i++) {
            var c = ca[i];
            while (c.charAt(0)==' ') c = c.substring(1,c.length);
            if (c.indexOf(data_set) == 0) return c.substring(data_set.length,c.length);
        }
        return "";
    }

    // ********************************************************************
    //Translating DOM texts
    // ********************************************************************
    if(!getCookie("_lang"))
        setCookie("_lang", "pt");
        

    var rc = setInterval(function(){
        if(getCookie("_lang")){
            var translator = $('body').translate({lang: getCookie("_lang"), t: dict});  
            clearInterval(rc);
        }else{
            console.log("sbe not yet detected.repeating check");
        }
    },200);


    // ********************************************************************
    // Trigger event for switching between languages.
    // ********************************************************************
    $(".change-lang .dropdown-content li a").click(function(){
        console.log($(this).attr("name"))
        var newLng = $(this).attr("name");
        switchLanguage(newLng);
    });

    /*********************************************************************
    *Flag switch
    *********************************************************************/
   if(getCookie('_lang') == "en") {
       $('.dropdown-trigger img').attr('src','includes/images/icon/en.png');
       $('.dropdown-trigger span').text('English');
   }
   if(getCookie('_lang') == "pt") {
    $('.dropdown-trigger img').attr('src','includes/images/icon/pt.png');
    $('.dropdown-trigger span').text('Portuguese');
}
    
    /*********************************************************************
    *images switch
    *********************************************************************/

   let langimg = getCookie("_lang");
   let pathBannerpromo= "/includes/images/banner/promo/";
   let pathBannerpromoinner= "/includes/images/banner/";
   let pathBannerSlider= "/includes/images/banner/slider/";

   const changeImg = () => {

        //promo top banner
        $('.promotopbanner1').attr("src",pathBannerpromo + langimg + "/promotion-banner-desktop.jpg");
        $('.promotopbanner2').attr("src",pathBannerpromo + langimg + "/banner0916_750x375.jpg");
        $('.promotopbanner3').attr("src",pathBannerpromo + langimg + "/promo3-ptg-Friend-Referral.jpg");

        //promo inner banner
        $('.promobanner1').attr("src",pathBannerpromoinner + langimg + "/deposit-slider-banner.jpg");
        $('.promobanner2').attr("src",pathBannerpromoinner + langimg + "/banner0916_1610x550.jpg");
        $('.promobanner3').attr("src",pathBannerpromoinner + langimg + "/promo3-ptg-Friend-Referral.jpg");

        //slider banner
        // $('.sliderbanner1').attr("src",pathBannerSlider + langimg + "/deposit-slider-banner.jpg");
        // $('.sliderbanner2').attr("src",pathBannerSlider + langimg + "/banner0916_1610x550.jpg");
        // $('.sliderbanner3').attr("src",pathBannerSlider + langimg + "/promo3-ptg-Friend-Referral.jpg");
   }
//    changeImg();   
    
});

