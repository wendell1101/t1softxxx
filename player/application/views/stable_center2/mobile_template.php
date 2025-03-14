<?php
$html_lang_code = $this->language_function->getCurrentLangForPromo();
$deposit_process_mode = $this->operatorglobalsettings->getSettingIntValue('deposit_process');
?>
<?=$this->CI->load->widget('lang')?>

<!DOCTYPE html>

<html xmlns="http://www.w3.org/1999/xhtml">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta name="viewport"
        content="width=device-width, initial-scale=1.0, user-scalable=1, minimum-scale=1.0, maximum-scale=3.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta content="yes" name="apple-mobile-web-app-capable">
    <meta content="black" name="apple-mobile-web-app-status-bar-style">
    <meta content="telephone=no" name="format-detection">
    <title><?= lang($this->utils->getPlayertitle());?>
    </title>
    <link rel="icon"
        href="<?= $this->utils->getPlayerCenterFaviconURL() ?>" />
    <link rel="apple-touch-icon"
        href="<?=site_url($template_name . '/mobile/images/touch-icon-iphone.png')?>">
    <link rel="apple-touch-icon" sizes="76x76"
        href="<?=site_url($template_name . '/mobile/images/touch-icon-ipad.png')?>">
    <link rel="apple-touch-icon" sizes="120x120"
        href="<?=site_url($template_name . '/mobile/images/touch-icon-iphone-retina.png')?>">
    <link rel="apple-touch-icon" sizes="152x152"
        href="<?=site_url($template_name . '/mobile/images/touch-icon-ipad-retina.png')?>">
    <link rel="apple-touch-startup-image"
        href="<?=site_url($template_name . '/mobile/images/milanoo_startup.png')?>" />
    <!-- css -->
    <?=$active_template->renderStyles(); ?>
    <!-- js -->
    <script type="text/javascript">
        var base_url = "<?=site_url()?>";
    </script>
    <?=$this->utils->getTrackingScriptWithDoamin('player_mobile', 'logRocket');?>
    <?=$active_template->renderScripts(); ?>

    <?php echo $_styles; ?>
    <?php echo $_scripts; ?>
    <style type="text/css">
        .ismobile {
            display: block !important;
        }

        .isweb {
            display: none !important;
        }

        .header .header_right {
            position: absolute;
            right: 1rem;
            top: 1rem;
            color: #fff;
            z-index: 10;
        }

        /*.loginbox{
            display: none !important;
        }*/
    </style>
    <script type="text/javascript">
        var imgloader = "/resources/images/ajax-loader-big2.gif";
        var base_url = "<?=site_url()?>";
        var player_id = "<?php echo $this->authentication->getPlayerId(); ?>";
        var homePageUrl= "<?=$this->utils->getSystemUrl('m')?>";
        function number_format(n) {
            n += "";
            var arr = n.split(".");
            var re = /(\d{1,3})(?=(\d{3})+$)/g;
            return arr[0].replace(re, "$1,") + (arr.length == 2 ? "." + arr[1] : "");
        }
    </script>

    <?=$this->load->view('resources/third_party/DateTimePicker');?>
    <?=$this->utils->startEvent('Load custom header'); ?>
    <?=$this->utils->getMobilePlayerCenterCustomHeader()?>
    <?=$this->utils->endEvent('Load custom header'); ?>

    <?=$this->utils->getTrackingScriptWithDoamin('player_mobile', 'gtm', 'header');?>
    <?=$this->utils->getTrackingScriptWithDoamin('player_mobile', 'ga');?>
</head>

<body lang="<?=$html_lang_code?>" data-mobile="1">
<?=$this->utils->getTrackingScriptWithDoamin('player_mobile', 'gtm', 'body');?>
    <div class="container-fluid">
        <?php include VIEWPATH . '/resources/common/includes/flash_message.php';?>

        <div class="header" id="header_template">
            <div class="head_back">
                <?php
            $enable_mobile_custom_sidenav = $this->utils->isEnabledFeature('enable_mobile_custom_sidenav');
            $enable_mobile_custom_sidenav_on_main_page_only = $this->utils->isEnabledFeature('enable_mobile_custom_sidenav_on_main_page_only');

            if ($this->CI->utils->getConfig('use_custom_hamburger_menu')
            && ((($enable_mobile_custom_sidenav && !$enable_mobile_custom_sidenav_on_main_page_only) ||
                    ($enable_mobile_custom_sidenav_on_main_page_only && $active_template->isHomePage())))):

                    $folder_name = $this->CI->utils->getConfig('use_custom_hamburger_menu');
                    $sidenav_file = VIEWPATH . '/resources/includes/custom_nav/'.$folder_name.'/sidenav.php';
                    if(file_exists($sidenav_file)) {
                        include $sidenav_file;
                        // include VIEWPATH . '/resources/includes/custom_nav/'.$folder_name.'/sidenav.php';
                    }?>
            <?php else:?>
                <?php if (!$active_template->isHomePage() && !in_array($this->uri->segment(2), ['callback_success'])): ?>
                <!-- <div class="back_btnPT" onclick="history.go(-1);"></div> -->
                <div class="back_btnPT" onclick="_pubutils.backBtn();"></div>
                <?php endif; ?>
            <?php endif; ?>
            </div>
            <?php if ($this->utils->getConfig('use_custom_player_info_mobile')== 'sexycasino'):
                $playerId = $this->authentication->getPlayerId();
                if(!empty($playerId)):
                    $big_wallet = $this->utils->getSimpleBigWallet($playerId);
            ?>
                <div class="header_right">
                    <div class="header_balance">
                        <p class="text"><?=lang("Main Wallet Total")?> </p>
                        <p class="money playerTotalBalance">
                            <span class="_player_balance_span nofrozen"> <?=$this->CI->utils->displayCurrency($big_wallet['main_wallet']['balance'])?> </span>
                            <span> <a href="javascript:void(0);" class="refreshBalanceButton" onclick="_export_sbe_t1t.player_wallet.refreshPlayerBalance();"> <i class="glyphicon glyphicon-refresh"></i> </a> </span>
                        </p>
                    </div>
                </div>
                <?php endif;?>
            <?php endif;?>
            <?php if ($this->utils->getConfig('use_custom_live_chat_in_mobile_player_top_right')):?>
                <div class="header_right">
                    <div class="header_live_chat">
                        <a href="javascript:void(0);" style="cursor:pointer;" class="header_contact_customer_service"  onclick="<?=$this->utils->getLiveChatOnClick();?>">
                            <img src="<?=$this->utils->getAnyCmsUrl('/includes/images/under_chat.png')?>">
                        </a>
                    </div>
                </div>
            <?php endif;?>
            <div class="header_title"><?php if ($this->utils->isEnabledFeature('enable_mobile_logo_add_link')): ?><a
                    href="<?=$this->utils->getSystemUrl('m')?>"><?=$active_template->renderFunctionTitle()?></a><?php else: ?><?=$active_template->renderFunctionTitle()?><?php endif; ?>
            </div>
            <div class="header_player">
                <div id="_player_login_area" class="loginbox fn-right pull-right hidden"></div>
            </div>
        </div>

        <div class="row content">
            <?=$main_content?>
        </div>
        <?=$this->load->view($template_name . '/mobile/includes/menu_bar');?>
    </div>
    <?php  echo $this->utils->getAnalyticCode('player'); ?>
    <script type="text/javascript">
        window.onpageshow = function(event) {
            if (event.persisted) {
                window.location.reload()
            }
        };

        <?php $this->utils->startEvent('Load custom script'); ?>
        <?= $this->utils->getPlayerCenterCustomScript();?>
        <?php $this->utils->endEvent('Load custom script'); ?>
    </script>

    <?=$this->utils->startEvent('Load custom header'); ?>
    <?=$this->utils->processMobileCustomFooterTemplate()?>
    <?=$this->utils->getMobilePlayerCenterCustomFooter()?>
    <?=$this->utils->endEvent('Load custom header');?>

    <?php if ($this->utils->isEnabledFeature('enable_embedded_lottery_sdk')) {
                include VIEWPATH . '/resources/includes/lottery_sdk.php';
            }
    ?>

    <?php if ($this->utils->isEnabledFeature('enable_agency_support_on_player_center')) {
                include VIEWPATH . '/resources/includes/agency_sdk.php';
            }
    ?>

    <?=$this->utils->getTrackingScriptWithDoamin('player_mobile', 'gtm', 'footer');?>
    <?php
    // $enable_pop_up_banner = $this->utils->getConfig('enable_pop_up_banner_when_player_login_mobile') && !empty($this->utils->getConfig('pop_up_banner_when_player_login_img_path'));
    // if($enable_pop_up_banner && $active_template->isHomePage()) {
    //     include VIEWPATH . '/resources/includes/pop_up_banner.php';
    // }
    ?>

    <?php
    $enable_pop_up_banner = $this->utils->getConfig('enable_pop_up_banner_function');
    if ($enable_pop_up_banner && $active_template->isHomePage()) {
        include VIEWPATH . '/resources/includes/pop_up_banner.php';
    }?>

    <?php
    $popup = $this->utils->getConfig('custom_registered_popup') === false ? 'default_popup' : 'smash';
    $filepath = '';
    if($popup = true && $this->utils->isEnabledFeature('enable_registered_show_success_popup')) {
        $filepath = VIEWPATH . '/resources/includes/custom_popup_register/' . $this->utils->getConfig      ('custom_registered_popup') . '.php';
       }
    
    if (file_exists($filepath)) {
        include $filepath;
    }
    ?>

</body>

</html>

<?php include VIEWPATH . '/stable_center2/cashier/deposit/modal.php'; ?>