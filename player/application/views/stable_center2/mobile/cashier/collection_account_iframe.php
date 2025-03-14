
<iframe id="deposit-response-iframe" src="<?=$target_href?>"></iframe>

<?php if(!$use_default_backbtn) : ?>
<script>
    $( document ).ready(function() {
        $(".back_btnPT").removeAttr('onClick').on('click', ()=>{
            if(!!(`<?=$back_btn_href?>`)) {

                window.location.href = `<?=$back_btn_href?>`;
            } else {
                window.history.back();
            }
        });
    });
</script>
<?php endif;?>