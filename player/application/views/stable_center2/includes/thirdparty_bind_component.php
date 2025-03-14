<?php
if( empty( $_lang_thirdparty_login_component_in_or__wrapper) ){
    $_lang_thirdparty_login_component_in_or__wrapper = "Bind";
}
?>
<div class="row">
    <div class="col-md-12 col-lg-12">

        <span class="or__wrapper"><?=$_lang_thirdparty_login_component_in_or__wrapper?></span>
        <div class="sso-btn-wrapper">
            <?php
            $thirdparty_sso_type = $this->config->item('thirdparty_sso_bind_type');
            foreach ($thirdparty_sso_type as $type) {
                $type = strtolower($type);
                switch ($type) {
                    case 'facebook': ?>
                        <div class="sso-btn-group">
                            <a href="/iframe/auth/fb_login">
                                <div class="sso-btn sso-btn-facebook" title="Facebook">
                                </div>
                            </a>
                        </div>
                <?php   break;
                    case 'google': ?>

                        <div class="sso-btn-group">
                            <a href="/iframe/auth/google_login">
                                <div class="sso-btn sso-btn-google" title="Google">
                                </div>
                            </a>
                        </div>
                <?php   break;
                    case 'line': ?>
                        <div class="sso-btn-group">
                            <a href="/iframe/auth/line_login?type=bind">
                                <div class="sso-btn sso-btn-line" title="Line">
                                </div>
                            </a>
                        </div>
                <?php   break;

                    default:
                        // code...
                        break;
                }
            }
            ?>
        </div>
    </div>
</div>

<script>
    $(document).ready(function(){
        var loginBtn;
        $('.sso-btn').on('click', function(e){
            console.log(this);
            loginBtn = this;
        });
    });
</script>

<style>
.sso-btn-wrapper{
    width: 100%;
    text-align: center;
    display: flex;
    justify-content: center;
}
.sso-btn{
    display: inline-block;
    width: 50px;
    height: 50px;
    border-radius: 3px;
    position: relative;
    -webkit-box-sizing: border-box;
    box-sizing: border-box;
    cursor: pointer;
    margin: 0 1rem;
}
.sso-btn::before{

    content: "";
    position: absolute;
    top: 50%;
    left: 50%;
    width: 50px;
    height: 42px;
    margin: -21px 0 0 -25px;
    background-position: 50%;
    background-repeat: no-repeat;
    background-size: 50%;
}
.sso-btn:after {
    content: "";
    display: block;
    width: 100%;
    padding-bottom: 100%;
}
/* .sso-btn a{
    width: 100%;
    height: 100%;
    display: inline-block;
} */
.sso-btn.sso-btn-line{
    background-color: #33cf33;
}
.sso-btn.sso-btn-line::before{
    background-image: url("/includes/images/line-sso-logo.svg");
    background-size: 22px auto;
}
.sso-btn.sso-btn-facebook{
    background-color: #1877f2;
}
.sso-btn.sso-btn-facebook::before{
    background-image: url("/includes/images/facebook-sso-logo.svg");
    background-size: 22px auto;
}
.sso-btn.sso-btn-google{
    background-color: #fff;
    border: 1px solid #bfbfc1;
}
.sso-btn.sso-btn-google::before{
    background-image: url("/includes/images/google-sso-logo.svg");
    background-size: 22px auto;
}
/** START of thirdparty_login_component_style */
<?=empty($this->config->item('thirdparty_login_component_style'))? '': $this->config->item('thirdparty_login_component_style')?>
/** EOF of thirdparty_login_component_style */
</style>