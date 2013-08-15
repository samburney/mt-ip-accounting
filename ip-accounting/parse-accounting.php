<?php
chdir(realpath(dirname(__FILE__)) . '/..');

require('vendor/autoload.php');
require('config.php');

// OpenVPN Management API
if(isset($hostname)){
	require('OpenVpnApi.php');
	$ovpn = new OpenVpnApi($hostname, $port, $password);

	// OpenVPN User Data
	$ovpn_users = $ovpn->connectedClientsData();
	$ovpn_ips = array();
	foreach($ovpn_users as $ovpn_user) {
		$username = User::where('cn', '=', $ovpn_user['cn'])->first();
		$ovpn_user['username'] = $username ? $username['name'] : $ovpn_user['cn'];

		$ovpn_ips[$ovpn_user['ip_vpn']] = $ovpn_user;
	}
}

// Database
require('db_setup.php');

// Fetch current accounting pairs into an array
foreach($routers as $router) {
	$lines = file('http://' . $router['hostname'] . '/accounting/ip.cgi');
	$date = strtotime(date('Y-m-d H:00:00'));

	// Iterate over lines
	foreach($lines as $line){
		$line = explode(' ', $line); // 0 = src_addr, 1 = dst_addr, 2 = bytes, 3 = packets, 4 = src_user, 5 = dst_user

		$raw_src_user = isset($ovpn_ips[$line[0]]) ? $ovpn_ips[$line[0]]['username'] : ($line[4] !== '*' ? $line[4] : NULL);
		$raw_dst_user = isset($ovpn_ips[$line[1]]) ? $ovpn_ips[$line[1]]['username'] : (trim($line[5]) !== '*' ? trim($line[5]) : NULL);
		
		if(isset($raw_src_user) || isset($raw_dst_user)) {
			$raw_hourly = array(
				'date' => $date,
				'src_addr' => $line[0],
				'dst_addr' => $line[1],
				'bytes' => $line[2],
				'packets' => $line[3],
				'src_user_name' => $raw_src_user,
				'dst_user_name' => $raw_dst_user,
				'router_hostname' => $router['hostname'],
			);

			// Insert into DB
			$raw_hour = new RawHour($raw_hourly);
			$raw_hour->save();
		}
	}
}


// Generate current aggregated download and upload lists
$users_downloaded = RawHour::where('date', '=', $date)->where('src_user_name', '!=', '')
	->groupBy('src_user_name')
	->select('users.id', 'src_user_name as user_name', 'src_addr as ipaddr', $db->connection()->raw('sum(bytes) as downloaded'))
	->leftJoin('users', 'src_user_name', '=', 'users.name')
	->get()
	->toArray();


$users_uploaded = RawHour::where('date', '=', $date)->where('dst_user_name', '!=', '')
	->groupBy('dst_user_name')
	->select('users.id', 'dst_user_name as user_name', 'dst_addr as ipaddr', $db->connection()->raw('sum(bytes) as uploaded'))
	->leftJoin('users', 'dst_user_name', '=', 'users.name')
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
?>