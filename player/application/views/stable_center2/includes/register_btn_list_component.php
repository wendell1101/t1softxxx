<?php
    $registration_mod_register_btn_list = $this->config->item('registration_mod_register_btn_list');
?>
<div class="col-md-12 col-lg-12">
    <div class="row">
    <?
        foreach( $registration_mod_register_btn_list as $indexNumber => $_btn_setting ):

            $btn_html = $this->utils->btn_case_str_to_html( $_btn_setting['btn_case_str']
                , empty($_btn_setting['wrapper_class'])? null: $_btn_setting['wrapper_class']
                , empty($_btn_setting['btn_type'])? null: $_btn_setting['btn_type']
                , empty($_btn_setting['btn_class'])? null: $_btn_setting['btn_class']
                , empty($_btn_setting['btn_lang'])? null: lang($_btn_setting['btn_lang'])
                , empty($_btn_setting['extra_attr'])? '': $_btn_setting['extra_attr']
            );
            echo $btn_html;
        endforeach; // EOF foreach( $registration_mod_register_btn_list as $indexNumber => $_btn_setting ):
    ?>
    </div>
</div>



<script>
    $(document).ready(function(){

        $('body').on('click','[data-btn_case]', function(e){
            var _target$El = $(e.target);
            // detect data-link
            var _link = _target$El.data('link');
            if( ! $.isEmptyObject(_link) && _target$El.data('do_link') ){
                window.location = _link;
            }
        });

    });
</script>