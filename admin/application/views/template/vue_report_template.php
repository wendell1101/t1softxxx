<style>
    #navbar-customized-focus .navbar-nav > li > a:focus{
        background-color:#006687;
        color:#ffffff;
    }
</style>
<?php
    $this->session->set_userdata('current_url', current_url());
    $user_theme = !empty($this->session->userdata('admin_theme')) ? $this->session->userdata('admin_theme') : $this->config->item('sbe_default_theme');

    $currentLang = $this->session->userdata('login_lan');
    $_SESSION['lang'] = $currentLang;

    if (!isset($company_title)) {
    	$company_title = lang('lang.sb');
    }
    $playerUrl = $this->utils->activePlayerSidebar();
    $csUrl = $this->utils->activeCSSidebar();
    $paymentUrl = $this->utils->activePaymentSidebar();
    $marketingUrl = $this->utils->activeMarketingSidebar();
    $cmsUrl = $this->utils->activeCMSSidebar();
    $affUrl = $this->utils->activeAffiliateSidebar();
    $reportUrl = $this->utils->activeReportSidebar();
    $systemUrl = $this->utils->activeSystemSidebar();
    $agencyUrl = $this->utils->activeAgencySidebar();
    $standard_js = [
        ((strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') !== FALSE)) ? $this->utils->jsUrl('jquery-1.11.1.min.js') : $this->utils->jsUrl('jquery-2.1.4.min.js'),
        $this->utils->jsUrl('pub/pubutils.js'),
        $this->utils->thirdpartyUrl('bootstrap/3.3.7/bootstrap.min.js'),
        $this->utils->thirdpartyUrl('bootstrap-notify/bootstrap-notify.min.js'),
    ];

    if(!isset($donotLoadExtraJS) || !$donotLoadExtraJS){
        $standard_js[]=$this->utils->thirdpartyUrl('bootstrap-daterangepicker-master/moment.min.js');
        $standard_js[]=$this->utils->thirdpartyUrl('bootstrap-daterangepicker-master/daterangepicker.js');
        $standard_js[]=$this->utils->thirdpartyUrl('numeral/numeral.min.js');
        $standard_js[]=$this->utils->thirdpartyUrl('bootstrap-dialog/js/bootstrap-dialog.min.js');
        $standard_js[]=$this->utils->jsUrl('polyfiller.js');
        $standard_js[]=$this->utils->jsUrl('jquery.cookie.min.js');
    }

    $isSuperModeOnMDB = $this->utils->isSuperModeOnMDB();
    $show_notification_data = $this->utils->getSBENotificationCount();

    $is_dev=$this->utils->getConfig('is_vue_dev');
    $uri_prefix= $is_dev ? '/resources/vue/dev' : '/resources/vue/live';
?>
<!DOCTYPE html>
<html lang='en'>
    <head>
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <meta name="renderer" content="webkit" />
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
        <meta name="description" content="<?php echo isset($description) ? $description : ''; ?>"/>
        <meta name="keywords" content="<?php echo isset($keywords) ? $keywords : ''; ?>"/>
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0"/>
        <title>
            <?php if($this->utils->isEnabledFeature('include_company_name_in_title')) : ?>
                <?=htmlspecialchars($company_title);?> -
            <?php endif; ?>
            <?=$title?>
        </title>

        <link rel="shortcut icon" href="/favicon.ico" type="image/x-icon" />
        <link href="<?=$this->utils->cssUrl('themes/bootstrap.' . $user_theme . '.css')?>" rel="stylesheet">
        <?php if(!isset($donotLoadExtraCSS) || !$donotLoadExtraCSS){ ?>
            <link rel="stylesheet" type="text/css" href="<?=$this->utils->cssUrl('template.css')?>">
        <?php if($this->utils->getConfig('use_new_sbe_color')) { ?>
            <link rel="stylesheet" type="text/css" href="<?=$this->utils->cssUrl('newSBEColor.css')?>">
        <?php } ?>
            <link rel="stylesheet" type="text/css" href="<?=$this->utils->cssUrl('icons.css')?>">
            <link rel="stylesheet" type="text/css" href="<?=$this->utils->cssUrl('font-awesome.min.css')?>">
            <link rel="stylesheet" type="text/css" href="<?=$this->utils->cssUrl('daterangepicker.css')?>">
            <link rel="stylesheet" type="text/css" href="<?=$this->utils->thirdpartyUrl('bootstrap-dialog/css/bootstrap-dialog.min.css')?>">
        <?php }?>

        <script type="text/javascript">
            var _site_url = "<?=site_url()?>";
        </script>

        <?=$_styles?>

        <?php
            foreach ($standard_js as $js_url) {
                echo '<script type="text/javascript" src="'.$js_url.'"></script>'."\n";
            }
            include __DIR__."/../includes/templatejs.php";
        ?>

        <?php if(!$is_dev){ ?>

            <link href="<?=$this->utils->processAnyUrl('/css/app.css', $uri_prefix)?>" rel=preload as=style>
            <link href="<?=$this->utils->processAnyUrl('/css/chunk-vendors.css', $uri_prefix)?>" rel=preload as=style>
            <link href="<?=$this->utils->processAnyUrl('/js/app.js',$uri_prefix)?>" rel=preload as=script>
            <link href="<?=$this->utils->processAnyUrl('/js/chunk-vendors.js',$uri_prefix)?>" rel=preload as=script>
            <link href="<?=$this->utils->processAnyUrl('/css/app.css', $uri_prefix)?>" rel=stylesheet>
            <link href="<?=$this->utils->processAnyUrl('/css/chunk-vendors.css', $uri_prefix)?>" rel=stylesheet>
            <!-- <script src="<?=$this->utils->processAnyUrl('/js/chunk-vendors.js', $uri_prefix)?>"></script> -->
            <!-- <script src="<?=$this->utils->processAnyUrl('/js/app.js', $uri_prefix)?>"></script> -->

            <?php if($this->utils->isEnabledFeature('use_pwa_loader')){ ?>
                <script>
                    if('serviceWorker' in navigator) {
                        navigator.serviceWorker.register('<?=$this->utils->processAnyUrl('/sw.js', '/resources/vue')?>')
                        .then(function() {
                            console.log('Service Worker Registered');
                        });
                    }
                </script>
            <?php } ?>
        <?php }else{ ?>
            <link href="<?=$uri_prefix?>/app.js" rel="preload" as="script"></head>
            <!-- <script type="text/javascript" src="<?=$uri_prefix?>/app.js"></script> -->
        <?php } ?>

        
        
    </head>

    <body data-theme="true" data-lang="true">
        <?=$main_content?>
        <?php if(!$is_dev){ ?>
            <script src="<?=$this->utils->processAnyUrl('/js/chunk-vendors.js', $uri_prefix)?>"></script>
            <script src="<?=$this->utils->processAnyUrl('/js/app.js', $uri_prefix)?>"></script>

            <?php if($this->utils->isEnabledFeature('use_pwa_loader')){ ?>
                <script>
                    if('serviceWorker' in navigator) {
                        navigator.serviceWorker.register('<?=$this->utils->processAnyUrl('/sw.js', '/resources/vue')?>')
                        .then(function() {
                            console.log('Service Worker Registered');
                        });
                    }
                </script>
            <?php } ?>
            <?php }else{ ?>
            <script type="text/javascript" src="<?=$uri_prefix?>/app.js"></script>
        <?php } ?>

        <!-- DOCUMENT ON READY -->
        <script type="text/javascript">

            $(function() {
                console.log('vue template');
                $("#alert-success").delay(300).addClass("in").fadeOut(5000);
                $("#menu-toggle").click(function(e) {
                    e.preventDefault();
                    $("#wrapper").toggleClass("active");
                    $("#sidebar_status").attr("class", "active");
                    if ($("#wrapper").hasClass('active')) {
                        $('#sidebar-wrapper').removeClass('active');
                        $(".list-group-item #hide_text").show();
                        $(".list-group-item #icon").removeClass("pull-right");
                        $("#main_icon").attr("class", "icon-arrow-left pull-right");

                    } else {
                        $('#sidebar-wrapper').attr('class', 'active');
                        $(".list-group-item #hide_text").hide();
                        $(".list-group-item #icon").addClass("pull-right");
                        $("#main_icon").attr("class", "icon-arrow-right pull-right");
                        $("#sidebar_status").attr("class", "inactive");
                    }
                });
                resizeSidebar();
            });
        </script>
    </body>
</html>
