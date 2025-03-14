(function(){
    function T1T_Resetpassword(){
        this.name = 'player_resetpassword';
    }

    T1T_Resetpassword.prototype.start = function(){
        var self = this;
        var player_resetpassword = variables.player_resetpassword;
        var redirect_url = player_resetpassword.url;
        var title = player_resetpassword.title;
        var message = player_resetpassword.message;
        var btnlang = player_resetpassword.button_lang;

        self.showResetpasswordModal(title, message, btnlang, redirect_url);
    };

    T1T_Resetpassword.prototype.showResetpasswordModal = function(title, message, btnlang, url){
        var pathname = window.location.pathname;

        if (pathname != url) {
            window.location.href = url;
        }

        // MessageBox.danger(message, title, function(){
        //     // show_loading();
        //     // window.location.href = url;
        // },
        // [
        //     {
        //         'text': btnlang,
        //         'attr':{
        //             'class':'btn btn-primary resetpassword_btn',
        //             'data-dismiss':"modal"
        //         }
        //     }
        // ]);

        // $('.close ,.resetpassword_btn').on('click', function(){
        //     window.location.href = url;
        // });
    };

    var resetpassword = new T1T_Resetpassword();
    smartbackend.addAddons(resetpassword.name, smartbackend);
    smartbackend.on('logged.t1t.player', function(){
        var data = {};
        utils.getJSONP(utils.getApiUrl('player_resetpassword'), data, function(result){
            if (result['status'] !== "success") {
                return false;
            }

            var player_resetpassword = result?.data?.variables?.player_resetpassword;
            console.log('player_resetpassword',player_resetpassword);
            if (typeof player_resetpassword != 'undefined') {
                if(player_resetpassword.enabled && player_resetpassword.force_reset_password){
                    resetpassword.start();
                }
            }
        });
    });
    return resetpassword;
})();