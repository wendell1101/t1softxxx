<?php

$config['mattermost_channels'] = [
'timeout_alert_from_log' => "https://talk.letschatchat.com/hooks/qownygpek7d1zmfx7s6wgbih4r",
];


if (file_exists(dirname(__FILE__) . '/config_local.php')) {
    require_once dirname(__FILE__) . '/config_local.php';
}
