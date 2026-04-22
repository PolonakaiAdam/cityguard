<?php
require_once __DIR__ . '/../app/bootstrap.php';
$base = base_url();
$api  = rtrim($base,'/').'/api/';
$pub  = rtrim($base,'/').'/public/';
echo "<pre style='font:14px monospace;padding:20px'>";
echo "base_url()  = $base\n";
echo "api url     = $api\n";
echo "public url  = $pub\n";
echo "SCRIPT_NAME = ".($_SERVER['SCRIPT_NAME']??'?')."\n";
echo "HTTP_HOST   = ".($_SERVER['HTTP_HOST']??'?')."\n";
echo "\nLeaflet CSS: ";
echo file_exists(__DIR__.'/assets/css/leaflet.css') ? "✓ megvan\n" : "✗ HIÁNYZIK!\n";
echo "Leaflet JS:  ";
echo file_exists(__DIR__.'/assets/js/leaflet.js')  ? "✓ megvan\n" : "✗ HIÁNYZIK!\n";
echo "map.js:      ";
echo file_exists(__DIR__.'/assets/js/map.js')      ? "✓ megvan\n" : "✗ HIÁNYZIK!\n";
echo "</pre>";
