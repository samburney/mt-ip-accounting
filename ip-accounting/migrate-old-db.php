<?php
/***
	This Script is only needed in a very special set of circumstances, you probably don't need it
	***/

chdir(realpath(dirname(__FILE__)) . '/..');

require('vendor/autoload.php');
require('config.php');

// Database
require('db_setup.php');
$db->connection()->disableQueryLog();

// Generate User <-> CN map
$user_cn_map = array();
$users = User::where('cn', '!=', '')->get();
foreach($users as $user) {
	$user_cn_map[$user->cn] = $user->name;
}
unset($users);
unset($user);

// Router List
$routers = array(
	array(
		'hostname' => 'hotspot.ridgehaven.wan',
		'db' => 'ipaccounting_hotspot',
		'schema_version' => 1,
	),
	array(
		'hostname' => 'cor1.ridgehaven.wan',
		'db' => 'ipaccounting_cor1',
		'schema_version' => 2,
	),
);

foreach($routers as $router) {
	$db->addConnection(array(
	    'driver'    => $db_driver,
	    'host'      => $db_host,
	    'database'  => $router['db'],
	    'username'  => $db_user,
	    'password'  => $db_pass,
	    'charset'   => 'utf8',
	    'collation' => 'utf8_unicode_ci',
	    'prefix'    => $db_prefix,
	), $router['hostname']);

	switch($router['schema_version']) {
		case 1:
			$colpfx = 'raw_';
			$tbl_hour = 'raw_hourly';
			$colsfx_user = '';

			break;

		case 2:
			$colpfx = '';
			$tbl_hour = 'raw_hours';
			$colsfx_user = '_name';

			break;
	}

	// Disable queryLog
	$db->connection($router['hostname'])->disableQueryLog();

	$last_date = RawHour::where('router_hostname', '=', $router['hostname'])
		->orderBy('date', 'desc')
		->take(1)
		->pluck('date');

	$last_date = isset($last_date) ? $last_date : 0;

	$dates = $db->connection($router['hostname'])->table($tbl_hour)
		->where($colpfx . 'date', '>', $last_date)
		->groupBy($colpfx . 'date')
		->orderBy($colpfx . 'date')
		->lists($colpfx . 'date');

	// Fetch current accounting pairs into an array
	foreach($dates as $date) {
		echo date('Y-m-d H:i', $date) . "\n";
		$lines = $db->connection($router['hostname'])->table($tbl_hour)->where($colpfx . 'date', '=', $date)->get();

		// Iterate over lines
		foreach($lines as $line){
			//$line = explode(' ', $line); // 0 = src_addr, 1 = dst_addr, 2 = bytes, 3 = packets, 4 = src_user, 5 = dst_user

			$raw_src_user = $line[$colpfx . 'src_user' . $colsfx_user] !== '*'
				? $line[$colpfx . 'src_user' . $colsfx_user]
				: NULL;
			$raw_dst_user = $line[$colpfx . 'dst_user' . $colsfx_user] !== '*'
				? $line[$colpfx . 'dst_user' . $colsfx_user]
				: NULL;
			
			if(isset($raw_src_user) || isset($raw_dst_user)) {
				$raw_src_user = isset($user_cn_map[$raw_src_user]) ? $user_cn_map[$raw_src_user] : $raw_src_user;
				$raw_dst_user = isset($user_cn_map[$raw_dst_user]) ? $user_cn_map[$raw_dst_user] : $raw_dst_user;

				$raw_hourly = array(
					'date' => $date,
					'src_addr' => $line[$colpfx . 'src_addr'],
					'dst_addr' => $line[$colpfx . 'dst_addr'],
					'bytes' => $line[$colpfx . 'bytes'],
					'packets' => $line[$colpfx . 'packets'],
					'src_user_name' => $raw_src_user,
					'dst_user_name' => $raw_dst_user,
					'router_hostname' => $router['hostname'],
				);

				// Insert into DB
				$raw_hour = new RawHour($raw_hourly);
				$raw_hour->save();
			}
		}

		// Generate current aggregated download and upload lists
		$users_downloaded = RawHour::where('date', '=', $date)->where('dst_user_name', '!=', '')
			->groupBy('dst_user_name')
			->select('users.id', 'dst_user_name as user_name', 'dst_addr as ipaddr', $db->connection()->raw('sum(bytes) as downloaded'))
			->leftJoin('users', 'dst_user_name', '=', 'users.name')
			->get()
			->toArray();

		$users_uploaded = RawHour::where('date', '=', $date)->where('src_user_name', '!=', '')
			->groupBy('src_user_name')
			->select('users.id', 'src_user_name as user_name', 'src_addr as ipaddr', $db->connection()->raw('sum(bytes) as uploaded'))
			->leftJoin('users', 'src_user_name', '=', 'users.name')
			->get()
			->toArray();

		// Parse user data
		foreach($users_downloaded as $user_downloaded) {
			// Update users table
			if(!$user = User::where('name', '=', $user_downloaded['user_name'])->first()) {
				$user = new User;
				$user->name = $user_downloaded['user_name'];
			}

			$user->lastip = $user_downloaded['ipaddr'];
			$user->firstseen = (isset($user->firstseen) && $user->firstseen != false) ? $user->firstseen : $date;
			$user->lastseen = $date;

			$user->save();

			// Determine user usage data
			$downloaded = $user_downloaded['downloaded'];
			$uploaded = 0;
			foreach($users_uploaded as $user_uploaded) {
				if($user_uploaded['user_name'] == $user->name) {
					$uploaded = $user_uploaded['uploaded'];
				}
			}

			// Add Quota data to hours table (Incrementing if data already exists)
			if(!$hour = $user->hours()->where('date', '=', $date)->first()){
				$hour = new Hour;
			}

			$hour->date = $date;
			$hour->downloaded = $downloaded;
			$hour->uploaded = $uploaded;

			$user->hours()->save($hour);
		}
	}
}
?>