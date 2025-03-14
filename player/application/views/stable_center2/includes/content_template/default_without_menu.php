<?php
if(!isset($player_center_template)) { # To cover the case of /iframe_module/callback_success page
    $player_center_template = $this->utils->getPlayerCenterTemplate();
}
?>

<?= $main_content ?>

<style type="text/css">
.loader .loader_vertical_helper.top {
    vertical-align: unset;
	position: relative;
    top: 40px;
}

#promodetails_modal .modal-dialog {
	margin: 0;
}
.t1t-background-transparent {
    background:transparent;
}

div.modal-backdrop {
    background:transparent;
    opacity: 0!important;
}

</style>
<script type="text/javascript">

// ----
if( typeof(Promotions) !== 'undefined' ){
    setTimeout(function(){
        $('.version_info').addClass('hide');
        $('.copyright').addClass('hide');
        $('body').addClass('t1t-background-transparent');
    },100);

    Promotions.adjustNavs();
}

$(document).ready(function() {
    // notify embedee iframe for onReady
    Promotions.postOnReadyMessage();
});
</script>
