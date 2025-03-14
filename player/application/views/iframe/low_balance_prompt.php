<!DOCTYPE html>
<html lang="en">
<head>
<meta name="viewport" content="width=device-width,user-scalable=no,initial-scale=1,minimum-scale=1,maximum-scale=1" content="user-scalable=no">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title><?php echo @$platformName; ?></title>
<meta http-equiv="X-UA-Compatible" content="IE=edge" />
<link rel="shortcut icon" href="<?= !empty($favicon_brand) ? $favicon_brand : $this->utils->getPlayerCenterFaviconURL(); ?>" type="image/x-icon" />
<link href="<?= $this->utils->appendCmsVersionToUri($this->CI->utils->getSystemUrl('www', 'includes/css/style.css')) ?>" rel="stylesheet">
<link href="<?= $this->utils->appendCmsVersionToUri($this->utils->getSystemUrl('www', 'includes/css/materialize.min.css')) ?>" rel="stylesheet">

</head>
<body>
<?php if($show_low_balance_prompt) : ?>
<div class="popup__notice__wrapper">
    <div id="modal-notice" class="modal block" tabindex="0">
        <div class="modal-content">
            <p><?= lang('low_balance_prompt.message') ?></p>
            <div class="button__wrapper">
                <div>
                    <a href="<?= $this->utils->getPlayerDepositUrl(); ?>" class="notice__btn dep_btn"><?= lang('low_balance_prompt.deposit_now') ?></a>
                    <a href="<?= $url ?>" class="notice__btn ago_btn"><?= lang('low_balance_prompt.not_now') ?></a>
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
<?php endif; ?>
</body>
</html>
