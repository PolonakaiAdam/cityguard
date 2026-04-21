<?php
return [
  'db' => [
    'host'    => 'localhost',
    'name'    => 'cityguard',
    'user'    => 'root',
    'pass'    => '',
    'charset' => 'utf8mb4'
  ],
  'session_name' => 'CITYGUARDSESSID',
  'mail' => array (
  'gmail_user' => 'toth.zolika100@gmail.com',
  'gmail_password' => 'sdbo rxjp pgng tzxo',
  'from_email' => 'toth.zolika100@gmail.com',
  'from_name' => 'CityGuard Rendszer',
),
  'app_url' => 'http://localhost/cityguard-local',

  // Anthropic API kulcs az AI supporthoz
  // Szerezd be itt: https://console.anthropic.com/settings/keys
  'anthropic_api_key' => '',
];
