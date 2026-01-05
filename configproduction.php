<?php
// return [
//     'mid' => '100000000360859',
//     'aggregator_id' => '100000000360858',
//     'key' => '971d6335-a0e0-4b43-8c98-506ae39a53a8',
//     'initiate_endpoint' => 'https://pgpay.icicibank.com/pg/api/v2/initiateSale',
//     'command_endpoint' => 'https://pgpay.icicibank.com/pg/api/command',
//     'settlement_endpoint' => 'https://pgpay.icicibank.com/pg/api/settlementDetails',
//     'return_url' => 'https://unikbox.in/getway/callback.php',  // UPDATE WITH LIVE URL
    
//     // Test Mode - Set to true to simulate payments without hitting actual gateway
//     'test_mode' => false,  // LIVE PRODUCTION
    
//     // Database Configuration
//     'db_host' => 'localhost',
//     'db_name' => 'unik_newmain_final',
//     'db_user' => 'unik_main_final_user',
//     'db_pass' => 'Nine8Nine',
//     'db_charset' => 'utf8mb4'
// ];

return [
    'mid' => '100000000360859',          // Merchant MID
    'aggregator_id' => '100000000360858', // NOT numeric
    'key' => '971d6335-a0e0-4b43-8c98-506ae39a53a8',

    'initiate_endpoint' => 'https://pgpay.icicibank.com/pg/api/v2/initiateSale',
    'command_endpoint'  => 'https://pgpay.icicibank.com/pg/api/command',

    'return_url' => 'https://unikbox.in/getway/callback.php',
    'test_mode' => false,

    // 'db_host' => 'localhost',
    // 'db_name' => 'unik_newmain_final',
    // 'db_user' => 'unik_main_final_user',
    // 'db_pass' => 'Nine8Nine',
    // 'db_charset' => 'utf8mb4'

    'db_host' => 'localhost',
    'db_name' => 'ashish_test',
    'db_user' => 'root',
    'db_pass' => '',
    'db_charset' => 'utf8mb4'
];
