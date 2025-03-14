<?php
    $frm_login_login_now_btn_list = $this->config->item('frm_login_login_now_btn_list');
?>
<div class="col-md-12 col-lg-12">
    <div class="row">
    <?
        foreach( $frm_login_login_now_btn_list as $indexNumber => $_btn_setting ):

            $btn_html = $this->utils->btn_case_str_to_html( $_btn_setting['btn_case_str']
                , empty($_btn_setting['wrapper_class'])? null: $_btn_setting['wrapper_class']
                , empty($_btn_setting['btn_type'])? null: $_btn_setting['btn_type']
                , empty($_btn_setting['btn_class'])? null: $_btn_setting['btn_class']
                , empty($_btn_setting['btn_lang'])? null: lang($_btn_setting['btn_lang'])
                , empty($_btn_setting['extra_attr'])? '': $_btn_setting['extra_attr']
            );
            echo $btn_html;
        endforeach; // EOF foreach( $frm_login_login_now_btn_list as $indexNumber => $_btn_setting ):
    ?>
    </div>
</div>

<script>
    var is_mobile = "<?= $this->utils->is_mobile() ?>";
    $(document).ready(function(){

        $('body').on('click','[data-btn_case]', function(e){
            var _target$El = $(e.target);
            // detect data-link
            var _link = _target$El.data('link');
            if( ! $.isEmptyObject(_link) && _target$El.data('do_link') ){
                window.location = _link;
            }
        });

        if (is_mobile) {
            var modal = $('#login-modal').modal({
                'show': false
            });

            $("#login_now_btn").click(function (e) {
                e.preventDefault();
                var dltext1 = $("#dl1").val();
                var dltext2 = $("#dl2").val();
                var captcha = ($("#captcha").length) ? $("#captcha").val() : '';

                if (dltext1.length == 0) {
                    $("#dltext1").show();
                    return false;
                } else {
                    $("#dltext1").hide();
                }
                if (dltext2.length == 0) {
                    $("#dltext2").show();
                    return false;
                } else {
                    $("#dltext2").hide();
                }

                if($("#captcha").length){
                    if (!$('.captcha').hasClass('hide') && captcha.length == 0) {
                        $("#dltext3").show();
                        return false;
                    } else {
                        $("#dltext3").hide();
                    }
                }

                var ajax_options = {
                    url: $('#frm_login').attr('action'),
                    type: 'POST',
                    dataType: 'json',
                    data: $('#frm_login').serialize(),
                    success: function (data) {
                        if (data.status == "error") {
                            var message = JSON.parse(data.msg);
                            show_error_in_modal();
                            $('button.btn-close', modal).removeClass('disabled');
                            $('button.btn-close', modal).prop('disabled', false);
                            $('button.btn-close', modal).removeAttr('disabled');

                            if(typeof message == "object"){
                                var ul = $('<ul>');

                                $.each(message, function(key, value){
                                    switch(key){
                                        case "login":
                                            <?php if($this->utils->isEnabledFeature('responsible_gaming')):?>
                                            ul.append($('<li>').text(value));
                                            <?php else:?>
                                            ul.append($('<li>').text("<?=lang('macaopj.auth.user_not_exists')?>"));
                                            <?php endif;?>
                                        break;
                                        case "password":
                                            ul.append($('<li>').text("<?=lang('macaopj.auth.password_incorrect')?>"));
                                        break;
                                        default:
                                            ul.append($('<li>').text(value));
                                        break;
                                    }
                                });

                                $('.modal-body', modal).html(ul);
                            }else{
                                $('.modal-body', modal).html(data.msg);
                            }

                            // initCaptcha();
                            return;
                        }else{
                            //success and redirect
                            window.location.href = data.redirect_url;
                        }
                    }
                };

                function show_error_in_modal () {
                    modal.off('show.bs.modal').on('show.bs.modal', function(){
                        $('button.btn-close', modal).addClass('disabled');
                        $('button.btn-close', modal).prop('disabled', true);
                        $('button.btn-close', modal).attr('disabled', 'disabled');
                        $('.modal-body', modal).html('<img class="loading" src="/<?=$this->utils->getPlayerCenterTemplate(FALSE)?>/images/loading.gif">');
                    });
                    modal.off('hide.bs.modal').on('hide.bs.modal', function(){
                        $('.modal-body', modal).html('');
                        <?php if ($login_captcha_enabled):?>
                            location.reload(true);
                        <?php endif; ?>
                    });
                    modal.modal('show');
                }
                $.ajax(ajax_options);
            });
        }
    });
</script>
<style>
/** Begin of frm_login_login_now_btn_list_style */
<?= empty($this->config->item('frm_login_login_now_btn_list_style') )? '': $this->config->item('frm_login_login_now_btn_list_style') ?>
/** EOF frm_login_login_now_btn_list_style */
</style>
