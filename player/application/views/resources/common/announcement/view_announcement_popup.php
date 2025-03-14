<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title><?= $this->utils->getPlayertitle();?></title>
	<link rel="icon" href="<?= $this->utils->getPlayerCenterFaviconURL() ?>" type="image/x-icon" />
    <?=$active_template->renderStyles(); ?>
    <?=$active_template->renderScripts(); ?>
</head>
<body class="announcement_detail">
<div class="announcements_popup">
    <?php foreach ($announcements as $a): ?>
        <?php
            $isEmptyDetail = null;
            if( empty(strip_tags($a['detail']) )){
                $isEmptyDetail = true;
            }else{
                $isEmptyDetail = false;
            }

        ?>
        <div class="announcement_wrapper panel panel-default" data-news_id="<?=$a['newsId'];?>">
            <div class="panel-heading">
                <div>
                    <span class="pull-right"><?=$a['date'];?></span>
                    <h2><?=strip_tags($a['title']);?></h2>
                </div>
            </div><!-- EOF .panel-heading -->

            <div class="panel-body">
                <div class="well-content"><span>&nbsp;&nbsp;&nbsp;&nbsp;</span><?=strip_tags($a['content']);?></div>
                <?php if( ! $isEmptyDetail && $this->utils->getConfig('enabled_announcement_detail') ): ?>
                    <div class="well-detail"><?=$a['detail'];?></div>
                <?php endif; ?>
            </div><!-- EOF .panel-body -->
        </div> <!-- EOF .announcement_wrapper -->
    <?php endforeach; // EOF foreach ($announcements as $a)... ?>

</div>
<style>
.well-content,.well-detail{
}

.announcement_wrapper.panel {
    background-color: inherit;
    border-style: inherit;
}

.announcement_wrapper .panel-heading, .announcement_wrapper .panel-body {
    background-color: inherit;
    border-style: inherit;
    color: inherit;
}
</style>
</body>
</html>