<?php
if (!empty($this->utils->getConfig('pop_up_banner_when_player_login_img_path'))){ ?>
<div id="new-modal-container" class="">
    <div class="new-modal">
        <button type="button" style="position:relative;top:-15px;left:-10px;background-color:#fff;width:25px;border-radius:50%;" class="close" onclick="closeModal()">
            <span aria-hidden="true">×</span>
        </button>
        <a href="/player_center2/promotion">
            <img src="<?=$this->utils->getConfig('pop_up_banner_when_player_login_img_path')?>">
        </a>
    </div>
</div>

<style>
#new-modal-container {
    display: none;
}
#new-modal-container.show {
    position: fixed;
    z-index: 9999;
    width: 100vw;
    height: 100vh;
    background-color: rgba(0,0,0,.4);
    display: grid !important;
    place-content: center;
    top: 0;
    left: 0;
}
#new-modal-container img {
    vertical-align: middle;
    border-style: none;
    <?php if ($this->utils->is_mobile()):
        echo 'width: 75vw;';
    else:
        echo 'width: 400px;';
    endif;?>
}
.no-scrollbar{
    overflow:hidden;
}
</style>
<script>
    const modalContainer = document.getElementById('new-modal-container');
    const closeModal = () => {
        modalContainer.classList.remove('show');
        $('body').removeClass('no-scrollbar');
    }
_export_sbe_t1t.on('logged.t1t.player', function(e, player){
	const modalContainer2 = document.getElementById('new-modal-container');
    if (_export_sbe_t1t.variables.ui.popupBanner) {
        console.log(_export_sbe_t1t.variables.ui.popupBanner);
    }
    // $('body').addClass('no-scrollbar');
    // modalContainer.classList.add('show');
});
</script>
<?php } ?>

