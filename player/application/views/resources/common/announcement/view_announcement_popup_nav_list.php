<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title><?= $this->utils->getPlayertitle();?></title>
    <?=$active_template->renderStyles(); ?>
    <?=$active_template->renderScripts(); ?>
</head>
<body class="announcements_nav_list">
    <?=$this->CI->load->widget('lang')?>
    <?php $activeId = (isset($cms_category_list[0]) ? $cms_category_list[0]['id'] : ""); ?>
    <ul class="nav nav-tabs" role="tablist">
        <?php foreach ($cms_category_list as $key => $list) : ?>
        <?php  ?>
        <li class="Tabli <?= ($list['id'] == $activeId) ? "active" : "" ?>" role="presentation"><a href="<?= "#cms".$list['id'] ?>" role="tab" data-toggle="tab"><?= strip_tags($list['name']); ?></a></li>
        <?php endforeach; ?>
    </ul>
    <div class="tab-content">
        <?php foreach ($cms_category_list as $key => $list) : ?>
        <div role="tabpanel" class="tab-pane <?= ($list['id'] == $activeId) ? "active" : "" ?>" id="<?= "cms" . $list['id'] ?>">
            <ul class="acontentUL">
                <?php foreach ($cms_list as $_list) : ?>
                    <?php
                    $isEmptyDetail = null;
                    if ( empty( strip_tags($_list['detail']) )):
                        $isEmptyDetail = true;
                    else:
                        $isEmptyDetail = false;
                    endif;

                    $_content = strip_tags($_list['content']);
                    $_detail = $_list['detail'];
                    ?>
                <?php if ($_list['categoryId'] == $list['id']) : ?>
                    <li class="well well-lg" data-news_id="<?=$_list['newsId']?>" data-category_id="<?=$_list['categoryId']?>">
                        <div class="announcement-wrapper panel panel-default">
                            <div class="announcement-content panel-heading"><?= $_content; ?></div>
                            <?php if (  ! $isEmptyDetail && $this->utils->getConfig('enabled_announcement_detail') ) : ?>
                                <?php if ( ! empty( strip_tags($_detail) )) : ?>
                                    <div class="announcement-detail panel-body"><?=$_detail?></div>
                                <?php endif; // EOF if ( ! empty( strip_tags($_list['detail']) ))...?>
                            <?php endif; // EOF if ( ! empty( strip_tags($_list['detail']) ))...?>
                        </div> <!-- EOF announcement-wrapper -->
                    </li>
                <?php endif; ?>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endforeach; ?>
    </div>
</body>
</html>