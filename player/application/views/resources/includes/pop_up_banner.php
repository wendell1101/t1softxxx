<div id="new-modal-container" class="">
    <div class="new-modal">
        <button type="button" class="close" onclick="closeModal()">
            <span aria-hidden="true">×</span>
        </button>
        <div class="banner-container">
            <div class="banner-img">

                <img src="" alt="" class="banner">
            </div>
            <div class="banner-detail">

                <div class="bannerTitle">
                </div>
                <div class="bannerDetail">
                </div>
                <div class="bannerRedirection">
                    <button class="redriectTo"></button>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    const modalContainer = document.getElementById('new-modal-container');
    const closeModal = () => {
        modalContainer.classList.remove('show');
        $('body').removeClass('no-scrollbar');
    }
    _export_sbe_t1t.on('logged.t1t.player', function(e, player) {
        var is_mobile = _export_sbe_t1t.variables.is_mobile;
        const modalContainer2 = document.getElementById('new-modal-container');
        let _popupDetail = JSON.parse(_export_sbe_t1t.variables.ui.popupBanner);
        var is_popup_banner_showed = _export_sbe_t1t.cookies.get('is_popup_banner_showed');
        var sess_og_player = _export_sbe_t1t.cookies.get('sess_og_player');
        var showPopup = (is_mobile && _popupDetail.displayInPlayerMobile == 1) || (!is_mobile && _popupDetail.displayInPlayerDesktop == 1);
        if (is_popup_banner_showed != _popupDetail.popupBannerId+sess_og_player && showPopup) {
            $('body').addClass('no-scrollbar');
            modalContainer.classList.add('show');
            _popupDetail.title? $('.bannerTitle').text(_popupDetail.title): $('.bannerTitle').text('');
            _popupDetail.bannerBackgroundElement ? $('.banner-img').html(_export_sbe_t1t.utils.decodeHtmlEntities(_popupDetail.bannerBackgroundElement)) : $('.banner-img').html('');
            _popupDetail.redriectBtnName ? $('button.redriectTo').text(_popupDetail.redriectBtnName) : $('button.redriectTo').hide();
            _popupDetail.content ? $('.bannerDetail').html(_export_sbe_t1t.utils.decodeHtmlEntities(_popupDetail.content, 'default')) : $('.bannerDetail').html('');
            if(_popupDetail.redriectLink) {
                $('button.redriectTo').on('click',()=>{
                    document.location.href=_popupDetail.redriectLink
                });
            }
            var date = new Date();
            var minutes = 60;
            date.setTime(date.getTime() + (minutes * 60 * 1000));
            _export_sbe_t1t.cookies.set('is_popup_banner_showed', _popupDetail.popupBannerId+sess_og_player, { expires: date });
        }
    });
</script>
<style>
    .new-modal {
        position: relative;
    }

    button.close {
        position: absolute;
        top: -15px;
        right: -15px;
        background-color: #fff;
        width: 25px;
        border-radius: 50%;
    }

    #new-modal-container {
        display: none;
    }

    #new-modal-container.show {
        position: fixed;
        z-index: 9999;
        width: 100vw;
        height: 100vh;
        background-color: rgba(0, 0, 0, .4);
        display: grid !important;
        place-content: center;
        top: 0;
        left: 0;
    }

    .banner-container {
        width: 75vw;
        max-width: 600px;
        height: 300px;
        background-color: #555;
    }

    .no-scrollbar {
        overflow: hidden;
    }

    .banner-img{
        width: 100%;
        height: 100%;
    }
    .banner-img>img {
        vertical-align: middle;
        border-style: none;
        width: 100%;
        max-width: 600px;
        height: 100%;
        /* max-height: 300px; */
    }
    .banner-img>#bannerColor{
        width: 100%;
        height: 100%;
    }

    .banner-detail {
        position: absolute;
        top: 0;
        width: 100%;
        height: 100%;
    }

    .bannerTitle {
        text-align: center;
    }

    .bannerRedirection {
        position: absolute;
        bottom: 0;
        text-align: center;
        width: 100%;
    }
    .bannerDetail{
        padding: 0 1rem;
        overflow: auto;
        max-height: 250px;
    }
</style>
