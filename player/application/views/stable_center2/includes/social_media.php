<div class="social-media-wrapper">
    <?php
    // var_dump($social_media);

    foreach ($social_media as $type => $field) {
        $type = strtolower($type);
        switch ($type) {
            case 'facebook': ?>
                <span class="social-media-btn-group">
                    <a href="<?= $field['url'] ?>" target="_blank">
                        <div class="social-media-btn social-media-btn-facebook" title="Facebook">
                        </div>
                    </a>
                </span>
        <?php   break;
            case 'twitter': ?>
                <span class="social-media-btn-group">
                    <a href="<?= $field['url'] ?>" target="_blank">
                        <div class="social-media-btn social-media-btn-twitter" title="Twitter">
                        </div>
                    </a>
                </span>
        <?php   break;
            case 'telegram': ?>
                <span class="social-media-btn-group">
                    <a href="<?= $field['url'] ?>" target="_blank">
                        <div class="social-media-btn social-media-btn-telegram" title="Telegram">
                        </div>
                    </a>
                </span>
        <?php   break;
        case 'youtube': ?>
                <span class="social-media-btn-group">
                    <a href="<?= $field['url'] ?>" target="_blank">
                        <div class="social-media-btn social-media-btn-youtube" title="Youtube">
                        </div>
                    </a>
                </span>
        <?php   break;
        case 'instagram': ?>
                <span class="social-media-btn-group">
                    <a href="<?= $field['url'] ?>" target="_blank">
                        <div class="social-media-btn social-media-btn-instagram" title="Instagram">
                        </div>
                    </a>
                </span>
        <?php   break;
            default:
                // code...
                break;
        }
    }
    ?>
</div>

<style>
.social-media-btn.social-media-btn-instagram::before{
    background-image: url("/includes/images/instagram.png");
    background-size: 22px auto;
}

.social-media-btn.social-media-btn-facebook::before{
    background-image: url("/includes/images/facebook.png");
    background-size: 22px auto;
}

.social-media-btn.social-media-btn-youtube::before{
    background-image: url("/includes/images/youtube.png");
    background-size: 22px auto;
}

.social-media-btn.social-media-btn-telegram::before{
    background-image: url("/includes/images/telegram.png");
    background-size: 22px auto;
}

.social-media-btn.social-media-btn-twitter::before{
    background-image: url("/includes/images/twitter.png");
    background-size: 22px auto;
}
</style>