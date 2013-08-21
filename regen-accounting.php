<?php
chdir(realpath(dirname(__FILE__)) . '/..');

require('vendor/autoload.php');
require('config.php');

// Database
require('db_setup.php');

// Truncate 'hours' table
Hour::truncate();

// Reprocess 'raw_hours' data by hour
$dates = RawHour::groupBy('date')->orderBy('date')->lists('date');
foreach($dates as $date) {
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
?>