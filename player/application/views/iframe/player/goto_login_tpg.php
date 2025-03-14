<!DOCTYPE html>
<html>
<head>
    <title><?= lang('lang.login_title', $lang) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
</head>
<style type="text/css">
    body {
        background: #001e5a;
    }
    .container .login__wrapper {
        margin: 300px 20px 0;
        border: 1px #032e61 solid;
        padding: 20px;
    }
    .container .login__wrapper h3 {
        color: #fff;
    }
    .container .login__wrapper form .input-group {
        display: block;
        position: relative;
    }
    .container .login__wrapper form .input-group span.input__icon {
        display: block;
        position: absolute;
        left: 0;
        top: 3px;
    }
    .container .login__wrapper form .input-group span.input__icon img {
        width: 30px;
    }
    .container .login__wrapper form input {
        display: block;
        width: 100%;
        background: transparent;
        border: 0;
        border-bottom: 1px #064b9d solid;
        height: 40px;
        color: #fff;
        padding-left: 35px;
        margin-bottom: 15px;
    }
    .container .login__wrapper form input:focus {
        border-bottom: 1px #fff solid;
        outline: none;
    }
    .container .login__wrapper form .btn__login {
        background: #fff;
        display: block;
        padding: 0 0;
        height: 40px;
        line-height: 40px;
        border-radius: 120px;
        color: #003c82;
        font-size: 18px;
        margin-top: 30px;
        width: 100%;
        border: 0;
        outline: 0;
        -ms-transition-timing-function: ease-in-out;
        -moz-transition-timing-function: ease-in-out;
        -webkit-transition-timing-function: ease-in-out;
        -o-transition-timing-function: ease-in-out;
        transition-duration: .2s;
        -ms-transition-duration: .2s;
        -moz-transition-duration: .2s;
        -webkit-transition-duration: .2s;
        -o-transition-duration: .2s;
    }
    .container .login__wrapper form .btn__login:hover {
        text-decoration: none;
        background: transparent;
        border: 1px #fff solid;
        color: #fff;
    }
</style>
<body>
    <div class="container">
        <div class="row">
            <div class="login__wrapper text-center">
                <h3><?= lang('lang.login_title', $lang) ?></h3>
                <form class="form-group" method="POST">
                    <?php if($this->session->flashdata('auth_error')) : ?>
                    <div class="alert alert-danger" role="alert">
                        <?= lang($this->session->flashdata('auth_error'), $lang) ?>
                    </div>
                    <?php endif; ?>
                    <?php if($this->session->flashdata('auth_success')) : ?>
                    <div class="alert alert-success" role="alert">
                        <?= lang($this->session->flashdata('auth_success'), $lang) ?>
                    </div>
                    <?php endif; ?>
                    <div class="input-group">
                        <span class="input__icon"><img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAD4AAAA+CAYAAABzwahEAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAyZpVFh0WE1MOmNvbS5hZG9iZS54bXAAAAAAADw/eHBhY2tldCBiZWdpbj0i77u/IiBpZD0iVzVNME1wQ2VoaUh6cmVTek5UY3prYzlkIj8+IDx4OnhtcG1ldGEgeG1sbnM6eD0iYWRvYmU6bnM6bWV0YS8iIHg6eG1wdGs9IkFkb2JlIFhNUCBDb3JlIDUuNi1jMDY3IDc5LjE1Nzc0NywgMjAxNS8wMy8zMC0yMzo0MDo0MiAgICAgICAgIj4gPHJkZjpSREYgeG1sbnM6cmRmPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5LzAyLzIyLXJkZi1zeW50YXgtbnMjIj4gPHJkZjpEZXNjcmlwdGlvbiByZGY6YWJvdXQ9IiIgeG1sbnM6eG1wPSJodHRwOi8vbnMuYWRvYmUuY29tL3hhcC8xLjAvIiB4bWxuczp4bXBNTT0iaHR0cDovL25zLmFkb2JlLmNvbS94YXAvMS4wL21tLyIgeG1sbnM6c3RSZWY9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC9zVHlwZS9SZXNvdXJjZVJlZiMiIHhtcDpDcmVhdG9yVG9vbD0iQWRvYmUgUGhvdG9zaG9wIENDIDIwMTUgKFdpbmRvd3MpIiB4bXBNTTpJbnN0YW5jZUlEPSJ4bXAuaWlkOkEyOTVEMEI2QjZCNTExRUE5RDRBQTczOUMwMDlBMTg3IiB4bXBNTTpEb2N1bWVudElEPSJ4bXAuZGlkOkEyOTVEMEI3QjZCNTExRUE5RDRBQTczOUMwMDlBMTg3Ij4gPHhtcE1NOkRlcml2ZWRGcm9tIHN0UmVmOmluc3RhbmNlSUQ9InhtcC5paWQ6QTI5NUQwQjRCNkI1MTFFQTlENEFBNzM5QzAwOUExODciIHN0UmVmOmRvY3VtZW50SUQ9InhtcC5kaWQ6QTI5NUQwQjVCNkI1MTFFQTlENEFBNzM5QzAwOUExODciLz4gPC9yZGY6RGVzY3JpcHRpb24+IDwvcmRmOlJERj4gPC94OnhtcG1ldGE+IDw/eHBhY2tldCBlbmQ9InIiPz4vseJNAAADZElEQVR42uya30tUQRTH701Lqq3c2MoIJfpFRFTWW/gjsx6i3sUXKSILeggXsoceoggs2grpD4ioBJU2ISKiCHyoheihoDAikuihNLPSkgq37Ts0C4fDvbA/Zu7OxTnwwZnlzpnz9d6dPXPmuplMxpmJNsuZoWaFW+FWuBVuhVvhVnh4rTzAudaBfWA7WA+q5OejYAg8AXfB60CiESmrZraA/kzulgS1uuPSLfqkj7gfYBi8A5M+15wKq/AeJmQUnAf1IEquqwR1oAt8YmP6wib8BhNwGSzIYVwEJNjY3rAIP8YCby3ARwvzETddeBULuK0IX63M13KThV9V/IheJ/6uqYzVVViBiYAJ4II0WCz7xdh88B2Uyf5CMGla5rZHihbWr0C0sJ+gl81hXMpaR9p3FPq9Rdr1JgpfQ9oq086XPnMYI7yStD8q9DsOpj3mMEb4X02bnzKydqRNFD5G2tUK/S4jq/qYicKHSHuTQr/bfOYwRvhD0m5R6LfNZ46iTGUCI/6JX2WSIWyDgju0GryVbZEXRNlaYsziliD9mwp89pB2QpVoHRWYcjBB8uvuInzR7akoVsw2fVva7LEXz9fHReZjd1gKEXEW+GPQkMM4UYkZZGM7dcToajwf7wCX2GeDspL6XFZXhcVALdgLdrDrO8EFHcG5ml8MECXlB6Amz3Ef5E7sVRgPFBrAflBRwNgK+fu9U1dwOu74RnBOPrpe9kXe0XHZj8oUN+Zz/X1wArww+UAh7lEfT4MBcEQeLszzGDcXbAbt8kBh2sPPcVNX9T4W6C9ZK19ZgK8acBZMeZyyGCNcfF1SHrXwagW+V3gcTDwFc0wQzkW3a/jdPcTmeFZq4fwwsFnjkVQTm+t2qYQfZoE0BXDy2sjmPBq08BgL4GAAorMcYHMvDVI4XcHvBSg6y0Cxj3whCYxIP9+TvkhAvgX8JsciNucqMKw7Ze0g7SslEO3IY6Vu0o/rTlldmXJGZV+8xzLilMaWkB1e3mWpfO94PRH9qISihX0mxUdR52vU+ajvIu2kU3pL+sSmXPhW0k4ZIDzlE5ty4Wvl3z/gjQHCRQy/WWxaFrfsxVPO/0N7E0y8KBAhi6+WOy4KDOIk9LRjjp2RMXWZVHMz1uzby1a4FW6FW+FWuBUeXvsnwACqlhwneyRhkgAAAABJRU5ErkJggg=="></span>
                        <input type="text" name="username" placeholder="<?= lang('system.word38', $lang) ?>">
                    </div>
                    <div class="input-group">
                        <span class="input__icon"><img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAD4AAAA+CAYAAABzwahEAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAyZpVFh0WE1MOmNvbS5hZG9iZS54bXAAAAAAADw/eHBhY2tldCBiZWdpbj0i77u/IiBpZD0iVzVNME1wQ2VoaUh6cmVTek5UY3prYzlkIj8+IDx4OnhtcG1ldGEgeG1sbnM6eD0iYWRvYmU6bnM6bWV0YS8iIHg6eG1wdGs9IkFkb2JlIFhNUCBDb3JlIDUuNi1jMDY3IDc5LjE1Nzc0NywgMjAxNS8wMy8zMC0yMzo0MDo0MiAgICAgICAgIj4gPHJkZjpSREYgeG1sbnM6cmRmPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5LzAyLzIyLXJkZi1zeW50YXgtbnMjIj4gPHJkZjpEZXNjcmlwdGlvbiByZGY6YWJvdXQ9IiIgeG1sbnM6eG1wPSJodHRwOi8vbnMuYWRvYmUuY29tL3hhcC8xLjAvIiB4bWxuczp4bXBNTT0iaHR0cDovL25zLmFkb2JlLmNvbS94YXAvMS4wL21tLyIgeG1sbnM6c3RSZWY9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC9zVHlwZS9SZXNvdXJjZVJlZiMiIHhtcDpDcmVhdG9yVG9vbD0iQWRvYmUgUGhvdG9zaG9wIENDIDIwMTUgKFdpbmRvd3MpIiB4bXBNTTpJbnN0YW5jZUlEPSJ4bXAuaWlkOjc2N0U5NDI0QjZCRDExRUFCNUJBREMzNkYyNjM0MTYzIiB4bXBNTTpEb2N1bWVudElEPSJ4bXAuZGlkOjc2N0U5NDI1QjZCRDExRUFCNUJBREMzNkYyNjM0MTYzIj4gPHhtcE1NOkRlcml2ZWRGcm9tIHN0UmVmOmluc3RhbmNlSUQ9InhtcC5paWQ6NzY3RTk0MjJCNkJEMTFFQUI1QkFEQzM2RjI2MzQxNjMiIHN0UmVmOmRvY3VtZW50SUQ9InhtcC5kaWQ6NzY3RTk0MjNCNkJEMTFFQUI1QkFEQzM2RjI2MzQxNjMiLz4gPC9yZGY6RGVzY3JpcHRpb24+IDwvcmRmOlJERj4gPC94OnhtcG1ldGE+IDw/eHBhY2tldCBlbmQ9InIiPz4qjfBlAAADL0lEQVR42uyaz0sVURTH79N4xEs0EwqyjZgt+olou8TNc2FERAT92LSpVVjuMldRUH9B26JNBgVWUPZDoh9QQWEUJlmPWgShaT+JoKe+6XvxDB4vDPlm7tw7zsyBD3NmeO/M+c7ceffcMy/jOI5IolWIhFoqPBWeCo+3LTF4rhWgDXSCFrAG5MAf8AkMgVvgCZgMPRs5nRngEHjlLMyGQVfYOYUtuAncdfzZTVAfVm6ZEAuYzeAKWMeO/QCD4AYogO+gFjSBnSAPatjnX4O94O1iGep1YES5g31g/X++twFcVr43BKoXw1CXo+gqS7wEjpUZo1sRLy9GRdSFdyhJH/cZp0eJsyPqwu+zZAcCxrrDYj2IsvAWlmgRtAaMtxVMsZjNunLVXbl1Mv8ReBEw3nOK49r2qJaszcwf0BTztkf8SAlfzfyPmmJ+8IgfKeE55n/TFPOrR/xICc8wf0ZTzJkw8tUt3PG4CLouphNV4WkjImnCp5g/oSnmhEd86x2YjaCO/OXseAdYqSH+FubL+O3s137YxrK0gZaaRceOyfNeojyMNSLqwUPQGIHHtUCj4LOJoX5UEV3QOdUscIpbS77cdoETJob6OzbkThlqWKqcZjmMmhrqP0E1+avAFwtDXP5wjpP/S+nVhTadZSz15r0e07KrRL/C+TCptCS8Mkgpa7tyq7F1YlvCD1N//TG4R/vWnhNTdhb0sP1NYvZFQgPojesdb1dEc5PzcFtcheeZLyu/3WJ+MzEfV+H8x+wl6Keta7VxfcZHmH8QtNLqzrU3cb3j18Rc91Xe3W1sKSu7qdfjKnxMzL4UeKYcf0rHx+I8j8t33ftAkfbldj8YTUIBI//jUiK/JOb3zmMtfBlbWMhtLinChSI8MbW6PG+W/KyNPGwJ/y3m2sbjtB/7RYor/ADYRdVbYoQLWpYOJm09bt109NymLeU+HUSHX+H8nXWVJeFVQS6+32dcLjTc/6OcASdZCWrCsnROwRY4RoRfYML3EDbtfNnPqs8XCktBH01Htq2fFjl/TQiXJvvaR6ih0Gih9HwPLoJzbMFjRHgip7NUeCo8FZ4Kj7T9E2AAhcNnYyFk2vQAAAAASUVORK5CYII="></span>
                        <input type="password" name="password" placeholder="<?=lang('sys.em3', $lang)?>">
                    </div>
                    <input type="hidden" name="token" value="<?= $token ?>">
                    <button type="submit" class="btn__login"><?= lang('lang.logIn', $lang) ?></button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>