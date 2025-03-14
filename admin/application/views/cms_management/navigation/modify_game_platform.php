<input type="hidden" name="id" value="<?= $game_platform['id']?>" />
<input type="hidden" name="navigation_setting_id" value="<?= $game_platform['navigation_setting_id']?>" />
<div class="form-group">
    <label for="english_name"><?= lang('cms.navigation.englishName') ?>:</label>
    <input type="text" class="form-control" name="english_name" value="<?= $game_platform['game_platform_lang']['en'] ?>" id="english_name">
</div>
<div class="form-group">
    <label for="chinese_name"><?= lang('cms.navigation.chineseName') ?>:</label>
    <input type="text" class="form-control" name="chinese_name" value="<?= $game_platform['game_platform_lang']['cn'] ?>" id="chinese_name">
</div>
<div class="form-group">
    <label for="indonesian_name"><?= lang('cms.navigation.indonesianName') ?>:</label>
    <input type="text" class="form-control" name="indonesian_name" value="<?= $game_platform['game_platform_lang']['id'] ?>" id="indonesian_name">
</div>
<div class="form-group">
    <label for="vietnamese_name"><?= lang('cms.navigation.vietnameseName') ?>:</label>
    <input type="text" class="form-control" name="vietnamese_name" value="<?= $game_platform['game_platform_lang']['vt'] ?>" id="vietnamese_name">
</div>
<div class="form-group">
    <label for="korean_name"><?= lang('cms.navigation.koreanName') ?>:</label>
    <input type="text" class="form-control" name="korean_name" value="<?= $game_platform['game_platform_lang']['kr'] ?>" id="korean_name">
</div>
<div class="form-group">
    <label for="thailand_name"><?= lang('cms.navigation.thailandName') ?>:</label>
    <input type="text" class="form-control" name="thailand_name" value="<?= empty($game_platform['game_platform_lang']['th']) ? $game_platform['game_platform_lang']['en'] : $game_platform['game_platform_lang']['th'] ?>" id="thailand_name">
</div>
<div class="form-group">
    <label for="order"><?= lang('cms.navigation.order') ?>:</label>
    <input type="text" class="form-control" name="order" value="<?= $game_platform['order'] ?>" id="order" aria-describedby="order-help">
    <small id="order-help" class="form-text text-muted"><?= lang('cms.navigation.order.help') ?></small>
</div>
<div class="form-group">
    <label for="icon"><?= lang('cms.navigation.icon') ?>:</label>
    <input type="file" class="form-control" name="icon" value="<?= $game_platform['icon'] ?>" id="icon" aria-describedby="icon-help">
    <small id="icon-help" class="form-text text-muted"><?= lang('cms.navigation.icon.help') ?></small>
</div>
<div class="form-group">
    <label for="status"><?= lang('cms.navigation.status') ?>:</label>
    <select name="status" id="status" class="form-control">
        <option value='0' <?= $game_platform['status'] ? '' : 'selected'?>><?= lang('cms.navigation.inactive') ?></option>
        <option value='1' <?= $game_platform['status'] ? 'selected' : ''?>><?= lang('cms.navigation.active') ?></option>
    </select>
</div>