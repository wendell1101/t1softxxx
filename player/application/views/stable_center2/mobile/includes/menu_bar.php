<?php
    $mobile_menu_bar = $this->config->item('mobile_menu_bar');
    $mobile_sub_menu_games = $this->config->item('mobile_sub_menu_games');
    usort($mobile_menu_bar, function($a, $b) { return $a['sort'] - $b['sort']; });
    if(isset($playerId)){
        $player_id = $playerId = (!empty($playerId)) ? $playerId : playerProperty($player, 'playerId');
    } else {
        $playerId = null;
    }
?>

<div class="quickButtonBar">
    <?php 
    $custom_quickbar_menu = $this->CI->utils->getConfig('use_custom_hamburger_menu');
    $file_exists = false;
    if ( $custom_quickbar_menu ){
        $quickbar_file = VIEWPATH . '/resources/includes/custom_quickbar/'.$custom_quickbar_menu.'/quickbar.php';
        if(file_exists($quickbar_file)) {
            include $quickbar_file;
            $file_exists = true;
        }
    } 
    if(!$custom_quickbar_menu || !$file_exists) {?>
    <?php if (!empty($mobile_sub_menu_games)) : ?>
        <div class="sub">
            <?php foreach ($mobile_sub_menu_games as $sub_games) : ?>
                <?php
                $url = $this->utils->getSystemUrl($sub_games['site'], $sub_games['url']);
                $img = $this->utils->getAnyCmsUrl($sub_games['img']);
                $lang = ucwords(strtolower(lang($sub_games['lang_code'])));
                ?>
                <div class="navi-button ">
                    <a href="<?=$url?>"><?=$lang?><i class="<?=$sub_games['key']?>"><img src="<?=$img?>"></i></a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <?php foreach ($mobile_menu_bar as $setting) {
            if (!$setting['enable']) {
                continue;
            }

            if (isset($setting['is_login'])) {
                if ((!isset($playerId) && $setting['is_login']) || (isset($playerId) && !$setting['is_login'])) {
                    continue;
                }
            }

            $url = "";
            $img = "";
            $lang = "";

            switch ($setting['site']) {
                case 'external':
                    $url = $setting['url'];
                    break;
                case 'player':
                    $url = site_url($setting['url']);
                    break;
                default:
                    $url = $this->utils->getSystemUrl($setting['site'], $setting['url']);
                    break;
            }
            if (!empty($setting['img'])) {
                $img = $this->utils->getAnyCmsUrl($setting['img']);
            }
            if (!empty($setting['lang_code'])) {
                $lang = ucwords(strtolower(lang($setting['lang_code'])));
            }

            $attrs =  '';
            $is_active_page = false;

            switch ($setting['key']) {
                case 'under_chat':
                    if (!$this->utils->isEnabledFeature('enable_player_center_mobile_live_chat')) {
                        continue 2;
                    }

                    $url = 'javascript:void(0);';
                    $attrs .= ' onclick="' . $this->CI->utils->getLiveChatOnClick() . '"';
                    break;
                case 'under_game':
                    if (!$this->utils->isEnabledFeature('enable_player_center_mobile_footer_menu_games')) {
                        continue 2;
                    }
                    break;
                case 'under_games':
                    if (empty($mobile_sub_menu_games)) {
                        continue 2;
                    }
                    $url = 'javascript:void(0);';
                    $attrs .= ' onclick="initSubGames();"';
                    break;
                case 'under_gift':
                    $custom_promo_www_url = $this->config->item('custom_promo_www_url');
                    $url = (!$this->authentication->isLoggedIn()) ? $this->utils->getSystemUrl($custom_promo_www_url['site'], $custom_promo_www_url['url']) : $url;
                    break;
                case 'under_me':
                    $current_uri = $this->uri->uri_string();
                    $check_key = strrpos($current_uri, '/menu');
                    if(($check_key > 0) || ($current_uri == '/') || empty($current_uri)){
                        $is_active_page = 'active';
                    }
                    break;
                case 'under_bank':
                    $current_uri = $this->uri->uri_string();
                    $check_key = strrpos($current_uri, '/deposit');
                    if (($check_key > 0)) {
                        $is_active_page = 'active';
                    }
                    break;

                default:
                    break;
            }

            $attrs .= (!empty($setting['target'])) ? ' target="' . $setting['target'] . '"' : ''; ?>
            <a href="<?= $url ?>" style="cursor:pointer;" class="<?=$setting['key']?> <?=$is_active_page?>" data-entry="<?=$setting['key']?>" <?=$attrs?>>
                <img src="<?=$img?>">
                <p><?=$lang?></p>
            </a>
        <?php }?>
    <?php }?>
</div>
<script type="text/javascript">
    function initSubGames(){
        if($(".quickButtonBar .under_games").hasClass('active')){
            $(".quickButtonBar .sub").hide();
            $(".quickButtonBar").removeAttr('style');
            $(".under_games").removeClass('active');
            $(".under_games").find('img').attr('src','<?=$this->utils->getAnyCmsUrl("/includes/images/under_game.png")?>');
        } else {
            $(".quickButtonBar").attr('style','height: 120px !important;');
            $(".quickButtonBar .sub").show();
            $(".quickButtonBar .under_games").addClass('active');
            $(".quickButtonBar .under_games").find('img').attr('src','<?=$this->utils->getAnyCmsUrl("/includes/images/under_game_on.png")?>');
        }
    }

    $(".quickButtonBar .sub").hide();
</script>
