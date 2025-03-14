(
    function(){
        function whl() {}

        function getUrlVars(name) {
            var match = RegExp('[?&]' + name + '=([^&]*)').exec(window.location.search);
            var myVar = match && decodeURIComponent(match[1].replace(/\+/g, ' '));
            if (myVar != undefined) {
                if (myVar.slice(-1) == '/') myVar = myVar.slice(0, -1);
            }
            return myVar;
        }

        function parseFormat(strArg) {
            var
                output = "{",  // Output
                str = strArg.trim();  // Remove unwanted space before processing

            str.split('\n').forEach(function (line) {
                var item = line.split('=');
                output += "\"" + item[0] + "\":\"" + item[1] + "\",";
            });
            output = output.substring(0, output.length - 1);
            output += "}";
            return output;
        }

        function noop(){}

        var SportsCookie = {
            setCookie: function (cname, cvalue, exdays) {
                var d = new Date();
                d.setTime(d.getTime() + (exdays * 24 * 60 * 60 * 1000));
                var expires = "expires=" + d.toGMTString();
                document.cookie = cname + "=" + cvalue + "; path=/; " + expires + "; SameSite=None; Secure";
            },
            getCookie: function (cname) {
                var name = cname + "=";
                var ca = document.cookie.split(';');
                for (var i = 0; i < ca.length; i++) {
                    var c = ca[i].trim();
                    if (c.indexOf(name) == 0) return c.substring(name.length, c.length);
                }
                return "";
            },
            deleteCookie: function (cname) {
                document.cookie = cname + "= ; path=/; expires = Thu, 01 Jan 1970 00:00:00 GMT";
            }
        }

        // Constants
        var getStatusUrl = 'http://admin.staging.tripleonetech.t1t.in/callback/game/969/status';
        var getRefreshUrl = 'http://admin.staging.tripleonetech.t1t.in/callback/game/969/refresh_session';
        var URL_PARAM_NAME_TOKEN = "operatorToken";
        var COOKIE_PARAM_NAME_TOKEN = "operatorToken";
        var SESSION_PARAM_NAME_TOKEN = "operatorToken";

        // Init token
        var strUrlToken = getUrlVars(URL_PARAM_NAME_TOKEN);
        var strCookieToken = SportsCookie.getCookie(COOKIE_PARAM_NAME_TOKEN);
        var strStoken = sessionStorage.getItem(SESSION_PARAM_NAME_TOKEN);
        var strToken = '';

        // Set token to all potential storages
        if (strUrlToken != '' && strUrlToken != undefined) {
            strToken = strUrlToken;
            SportsCookie.setCookie(COOKIE_PARAM_NAME_TOKEN, strUrlToken, 1);
            sessionStorage.setItem(SESSION_PARAM_NAME_TOKEN, strUrlToken);
        } else if (strCookieToken != '' && strUrlToken != undefined) {
            strToken = strCookieToken;
            sessionStorage.setItem(SESSION_PARAM_NAME_TOKEN, strCookieToken);
        } else if (strStoken != '' && strStoken != undefined) {
            strToken = strStoken;
            SportsCookie.setCookie(COOKIE_PARAM_NAME_TOKEN, strStoken, 1);
        }

        whl.prototype.login = function (username, password, callback) {
            console.error('Login method is not implemented!')
        };

        whl.prototype.logout = function () {
            SportsCookie.deleteCookie(COOKIE_PARAM_NAME_TOKEN);
            sessionStorage.removeItem(SESSION_PARAM_NAME_TOKEN);
        };

        whl.prototype.resetPassword = noop;
        whl.prototype.registrationForm = noop;
        whl.prototype.bank = noop;
        whl.prototype.formClose = noop;
        whl.prototype.updateProfile = noop;
        whl.prototype.showFormPanel = noop;

        // STATUS
        whl.prototype.status = function (callback) {
            this.status_callback = callback;
            var that = this;
            var result = {};

            if (strToken != "logout" && strToken != "") {
                var xmlhttp = new XMLHttpRequest();
                xmlhttp.onreadystatechange = function () {
                    if (xmlhttp.readyState == XMLHttpRequest.DONE) {   // XMLHttpRequest.DONE == 4
                        if (xmlhttp.status == 200) {                            
                        var resultData = JSON.parse(xmlhttp.responseText);
                            result.uid = new Date().getTime();
                            result.token = strToken;
                            result.status = "real";
                            result.balance = resultData.balance;
                            result.message = "success";
                            that.statusCallback(result);
                        } else {
                            result.uid = new Date().getTime();
                            result.token = strToken;
                            result.status = "anon";
                            result.balance = 0;
                            result.message = "fail";
                            that.statusCallback(result);
                        }
                    }
                };

                xmlhttp.open("GET", getStatusUrl + '?token=' + strToken + '&r=' + new Date().getTime(), true);
                xmlhttp.send();
            } else {
                that.logout();
                UserInfo.logout();
            }
        }

        // REFRESH SESSION
        whl.prototype.refreshSession = function (callback) {
            this.refresh_callback = callback;
            var that = this;
            var result = {};

            if (strToken != "logout" && strToken != "") {
                var xmlhttp = new XMLHttpRequest();
                xmlhttp.onreadystatechange = function () {
                    if (xmlhttp.readyState == XMLHttpRequest.DONE) {   // XMLHttpRequest.DONE == 4
                        if (xmlhttp.status == 200) {            
                        var resultData = JSON.parse(xmlhttp.responseText);
                            result.uid = new Date().getTime();
                            result.token = strToken;
                            result.status = "success";
                            result.balance = resultData.balance;
                            result.message = "success";
                            that.refreshCallback(result);
                        } else {
                            result.uid = new Date().getTime();
                            result.token = strToken;
                            result.status = "failure";
                            result.balance = 0;
                            result.message = "fail";
                            that.refreshCallback(result);
                        }
                    }
                };

                xmlhttp.open("GET", getRefreshUrl + '?token=' + strToken + '&r=' + new Date().getTime(), true);
                xmlhttp.send();
            } else {
                that.logout();
                UserInfo.logout();
            }
        }

        whl.prototype.statusCallback = function (data) {
            if (this.status_callback) {
                var result = {};
                result.uid = data.uid;
                result.token = data.token;
                result.status = data.status;
                result.balance = data.balance;
                result.message = data.message;
                this.status_callback(result);
            }
        }

        whl.prototype.refreshCallback = function (data) {
            if (this.refresh_callback) {
                var result = {};
                result.token = data.token;
                result.status = data.status;
                result.balance = data.balance;
                result.message = data.message;
                this.refresh_callback(result);
            }
        }

        window.whl = new whl();
    }
)()
