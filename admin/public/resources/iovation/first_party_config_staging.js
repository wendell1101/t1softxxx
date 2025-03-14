/* Copyright(c) 2016, iovation, inc. All rights reserved. */
window.io_global_object_name = "IGLOO"
window.IGLOO = window.IGLOO || {
  "enable_flash" : false,
  "bbout_element_id" : "ioBlackBox",  // this can be changed to store in a different hidden field (or removed to use a different collection method)
  "loader" : {
    "uri_hook" : "../iojstest",
    "version" : "general5",    
    }
};

if(typeof(window.IGLOO.loader.uri_hook) !== 'undefined'){
    // check PC deposit
    if(window.location.pathname.includes('/player_center2/deposit/auto_payment') ||
        window.location.pathname.includes('/player_center2/deposit/manual_payment') ||
        window.location.pathname.includes('/player_center2/deposit/deposit_custom_view')
    ){
        window.IGLOO.loader.uri_hook = "../../../iojstest";
    }

    // check mobile deposit
    if(window.location.pathname.includes('/player_center/auto_payment') ||
        window.location.pathname.includes('/player_center/manual_payment') ||
        window.location.pathname.includes('/player_center2/deposit/deposit_custom_view')
    ){
        window.IGLOO.loader.uri_hook = "../../../../iojstest";
    }
}
