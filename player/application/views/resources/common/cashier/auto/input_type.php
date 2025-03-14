<?php foreach($playerInputInfo as $inputInfo): ?>
    <?php
    $label = isset($inputInfo['label']) ? $inputInfo['label'] : (isset($inputInfo['label_lang']) ? lang($inputInfo['label_lang']) : '');
    $crypto_currency_tag = isset($inputInfo['crypto_currency_label']) ? $inputInfo['crypto_currency_label'] : (isset($inputInfo['crypto_currency_lang']) ? $inputInfo['crypto_currency_lang'] : '');
    $crypto_currency_label = isset($inputInfo['crypto_currency_label']) ? $inputInfo['crypto_currency_label'] : (isset($inputInfo['crypto_currency_lang']) ? lang($inputInfo['crypto_currency_lang']) : '');
    $default_currency_label = isset($inputInfo['default_currency_label']) ? $inputInfo['default_currency_label'] : (isset($inputInfo['default_currency_lang']) ? lang($inputInfo['default_currency_lang']) : '');

    /* @var $external_system_api Abstract_payment_api */

    if($inputInfo['type'] == 'bank_list' || $inputInfo['type'] == 'bank_box'){
        include __DIR__ . '/input_type/' . $inputInfo['type'] . '.php';
    }else{
        include __DIR__ . '/input_type/' . $inputInfo['type'] . '.php';
    }
    ?>
<?php endforeach; ?>