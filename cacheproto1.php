<?php
	require('vendor/autoload.php');

	$filesystem = new Illuminate\Filesystem\Filesystem;
	$filestore = new Illuminate\Cache\FileStore($filesystem, '/tmp/filesystem_cache.bin');
	$cache = new Illuminate\Cache\Section($filestore, 'proto1');

	echo "live1 " . date("U") . "\n";
	if($cache->has('proto1_test')) {
		echo "cache1 " . $cache->get('proto1_test') . "\n";
	}
	else {
		$cache->put('proto1_test', date('U'), 1);
	}
?>