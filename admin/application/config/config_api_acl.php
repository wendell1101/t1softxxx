<?php
/**
 * config_api_acl.php
 *
 * Available ACL Type:
 *   ip_bandwidth:
 *     support rule type:
 *       time_range
 *
 *   total_reqs_of_day: @TODO
 *
 * @author Elvis Chen
 */

$config['api_acl'] = [];
$config['api_acl_override'] = []; // for config_secrect_local.php or config_locao.php to used.

/**
 * For api_acl_override example.
 *
 * $config['api_acl_override']['default']['ip_bandwidth']['enabled'] = FALSE;
 * or
 * $config['api_acl_override']['default']['ip_bandwidth']['limit_value'] = 5;
 */

$config['api_acl']['default'] = [
    'ip_bandwidth' => [
        'enabled' => TRUE,
        'type' => 'time_range',
        'time_range' => 60,
        'limit_value' => 30,
        'blocked_time' => 600,
        'response' => [
            'code' => 403,
            'msg' => 'Access Denied'
        ]
    ]
];

$config['api_acl']['login_notify'] = [
        'ip_bandwidth' => [
        'enabled' => TRUE,
        'type' => 'time_range',
        'time_range' => 3600,
        'limit_value' => 3,
        'blocked_time' => 3600,
        'response' => [
            'code' => 403,
            'msg' => 'Access Denied'
        ]
    ]
];

$config['api_acl']['update_sms_verification'] = [
    'ip_bandwidth' => [
        'enabled' => TRUE,
        'type' => 'time_range',
        'time_range' => 60,
        'limit_value' => 5,
        'blocked_time' => 180,
        'response' => [
            'code' => 403,
            'msg' => 'Access Denied'
        ]
    ]
];

// The following apis, XXXX_of_smash_promo_auth are the same.
$config['api_acl']['invite_friend_of_smash_promo_auth'] =
$config['api_acl']['bet_amount_of_smash_promo_auth'] =
$config['api_acl']['bet_info_of_smash_promo_auth'] =
$config['api_acl']['useinfo_of_smash_promo_auth'] = [
        'ip_bandwidth' => [
            'enabled' => TRUE,
            'type' => 'time_range',
            /// Within 60 seconds,
            // the accesses total count is accumulated 10 times.
            // If the above conditions are met, then block the ip access for 60 seconds.
            // unit: seconds,
            'time_range' => 60,
            'limit_value' => 10,
            'blocked_time' => 60,
            'response' => [
                'code' => 403,
                'msg' => 'Access Denied'
            ]
    ]
];

$config['api_acl']['getAllMessages'] = [
    'ip_bandwidth' => [
        'enabled' => TRUE,
        'type' => 'time_range',
        'time_range' => 60,
        'limit_value' => 10,
        'blocked_time' => 60,
        'response' => [
            'code' => 403,
            'msg' => 'Access Denied'
        ]
    ]
];

$config['api_acl']['sendMessage'] = [
    'ip_bandwidth' => [
        'enabled' => TRUE,
        'type' => 'time_range',
        'time_range' => 60,
        'limit_value' => 10,
        'blocked_time' => 60,
        'response' => [
            'code' => 403,
            'msg' => 'Access Denied'
        ]
    ]
];

// OGP-29624
$config['api_acl']['getAllFailedLoginAttempts_of_ole777_wormhole'] = [
    'ip_bandwidth' => [
        'enabled' => TRUE,
        'type' => 'time_range',
        /// Within 60 seconds,
        // the accesses total count is accumulated 10 times.
        // If the above conditions are met, then block the ip access for 60 seconds.
        // unit: seconds,
        'time_range' => 60,
        'limit_value' => 10,
        'blocked_time' => 60,
        'response' => [
            'code' => 403,
            'msg' => 'Access Denied'
        ]
]
];

// OGP-29888
$config['api_acl']['getPlayerProfileV2'] = [
    'ip_bandwidth' => [
        'enabled' => TRUE,
        'type' => 'time_range',
        'time_range' => 60,
        'limit_value' => 30,
    ]  
];  
// OGP-29889
$config['api_acl']['getAllPlayer'] = [
    'ip_bandwidth' => [
        'enabled' => TRUE,
        'type' => 'time_range',
        /// Within 60 seconds,
        // the accesses total count is accumulated 10 times.
        // If the above conditions are met, then block the ip access for 60 seconds.
        // unit: seconds,
        'time_range' => 60,
        'limit_value' => 10,
        'blocked_time' => 60,
        'response' => [
            'code' => 403,
            'msg' => 'Access Denied'
        ]
    ]
];
// OGP-33683
$config['api_acl']['getAllPlayer2'] = [
    'ip_bandwidth' => [
        'enabled' => TRUE,
        'type' => 'time_range',
        /// Within 60 seconds,
        // the accesses total count is accumulated 10 times.
        // If the above conditions are met, then block the ip access for 60 seconds.
        // unit: seconds,
        'time_range' => 60,
        'limit_value' => 10,
        'blocked_time' => 60,
        'response' => [
            'code' => 403,
            'msg' => 'Access Denied'
        ]
    ]
];

// OGP-34607
$config['api_acl']['adminuserLogin'] = [
    'ip_bandwidth' => [
        'enabled' => TRUE,
        'type' => 'time_range',
        'time_range' => 60,
        'limit_value' => 10,
        'blocked_time' => 3600,
        'response' => [
            'code' => 403,
            'msg' => 'Access Denied'
        ]
    ]
];

// OGP-30821
$config['api_acl']['manualDepositTo3rdParty'] = [
    'ip_bandwidth' => [
        'enabled' => TRUE,
        'type' => 'time_range',
        'time_range' => 60,
        'limit_value' => 10,
        'blocked_time' => 60,
        'response' => [
            'code' => 403,
            'msg' => 'Access Denied'
        ]
    ]
];
