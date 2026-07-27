<?php
declare( strict_types = 1 );

$cfg = require __DIR__ . '/../vendor/mediawiki/mediawiki-phan-config/src/config-library.php';

$cfg['target_php_version'] = '8.1';
$cfg['directory_list'] = [
	'demo',
	'src',
	'tests',
	'vendor',
];
$cfg['exclude_analysis_directory_list'] = [
	'vendor/',
];

// Globals provided via demo/config.php
$cfg['globals_type_map'] = [
	'oauthUrl' => 'string',
	'consumerKey' => 'string',
	'consumerSecret' => 'string',
];

return $cfg;
