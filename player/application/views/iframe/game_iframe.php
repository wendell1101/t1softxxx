<!DOCTYPE html>
<html lang="en">
<head>
<meta name="viewport" content="width=device-width,user-scalable=no,initial-scale=1,minimum-scale=1,maximum-scale=1" content="user-scalable=no">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title><?php echo @$platformName; ?></title>
<meta http-equiv="X-UA-Compatible" content="IE=edge" />
<?=$this->utils->getTrackingScriptWithDoamin('player', 'logRocket');?>
<!-- <link rel="shortcut icon" href="/<?=$this->utils->getPlayerCenterTemplate()?>/fav.ico" type="image/x-icon" /> -->
<link rel="shortcut icon" href="<?= !empty($favicon_brand) ? $favicon_brand : $this->utils->getPlayerCenterFaviconURL(); ?>" type="image/x-icon" />
<link href="<?= $this->utils->appendCmsVersionToUri($this->CI->utils->getSystemUrl('www', 'includes/css/style.css')) ?>" rel="stylesheet">
<link href="<?= $this->utils->appendCmsVersionToUri($this->utils->getSystemUrl('www', 'includes/css/materialize.min.css')) ?>" rel="stylesheet">
<style>
*{padding:0;margin:0;}
html , body {height:100%;overflow:hidden;}
iframe{
    border:none;
    position: absolute;
    top: 0;
}
a.btn.btn-info {
    margin:2px 0;
    border-radius: 25px;
    padding: 5px 15px;
    background: #2693ff;
    background: linear-gradient(to top, #0059B1 0%, #2693ff 100%);
    border: 0;
}
a.btn.btn-info:hover{
    background: linear-gradient(to bottom, #0059B1 0%, #2693ff 100%);
}
</style>
<script type="text/javascript">
<?php
$loggedPlayerUsername=$this->authentication->getUsername();
echo $this->utils->generateStatCode($loggedPlayerUsername);

$min_width = !empty($set_min_width) ? 'style="min-width:'.$default_frame_min_width.';"' : '';
$allow_fullscreen = !empty($allow_fullscreen) ? 'allowfullscreen="true"' : 'allowfullscreen="false"';
$scrolling = !empty($no_scrolling) ? 'scrolling = "no"' : 'scrolling = "yes"';
?>
</script>

<?=$this->utils->getTrackingScriptWithDoamin('player', 'gtm', 'header');?>
<?=$this->utils->getTrackingScriptWithDoamin('player', 'ga');?>

</head>
<body>
<?php if(isset($empty) && $empty){ echo lang('goto_game.sysMaintenance'); } else {?>
<?php if (isset($getPlayerGameHistoryURL)) { ?>
<div style="background-color: #000">
	<a href="<?=$getPlayerGameHistoryURL?>" target="_blank" class="btn btn-info"><i class="glyphicon glyphicon-time"></i> <?=lang('player.ui48')?></a>
	<a href="<?=site_url('/player_center2/deposit')?>" target="_blank" class="btn btn-info"><i class="glyphicon glyphicon-open"></i> <?=lang('cashier.13')?></a>
	<a href="<?=site_url('/player_center/withdraw')?>" target="_blank" class="btn btn-info"><i class="glyphicon glyphicon-save"></i> <?=lang('cashier.14')?></a>
</div>
<?php } ?>
<iframe  <?php echo isset($iframeName) && !empty($iframeName) ? 'name="' . $iframeName . '"' : ''; ?> width="100%" height="100%" <?php echo $min_width; ?>  src="<?php echo $url; ?>" <?php echo $allow_fullscreen; ?> <?php echo $scrolling; ?> ></iframe>
<?php } ?>

<?php if(isset($show_low_balance_prompt) && $show_low_balance_prompt) : ?>
<div class="popup__notice__wrapper">
    <div id="modal-notice" class="modal block" tabindex="0">
        <div class="modal-content">
            <p><?= lang('low_balance_prompt.message') ?></p>
            <div class="button__wrapper">
                <div>
                    <a href="<?= $this->utils->getPlayerDepositUrl(); ?>" class="notice__btn dep_btn"><?= lang('low_balance_prompt.deposit_now') ?></a>
                    <a id="hide_modal" href="javascript:void(0)" class="notice__btn ago_btn"><?= lang('low_balance_prompt.not_now') ?></a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.block {
    display: block;
}
</style>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        function hideModal() {
            document.getElementById('modal-notice').classList.remove('block')
        }
        const anchor = document.getElementById('hide_modal');
        if(anchor.addEventListener)
        {
            anchor.addEventListener('click', hideModal);
        }
    })
</script>
<?php endif; ?>

<?=$this->utils->getTrackingScriptWithDoamin('player', 'gtm', 'footer');?>
<?php if(isset($customGameJs) && $customGameJs){ echo load_game_js($customGameJs);} ?>
</body>
</html>
