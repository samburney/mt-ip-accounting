<?php
$dsn = "mysqli://ipaccounting:ipaccounting@localhost/ipaccounting";

require_once('MDB2.php');
$db =& MDB2::connect($dsn);
if(PEAR::isError($db)){
	die($db->getUserinfo());
}
$db->setFetchMode(MDB2_FETCHMODE_ASSOC);

// Fetch current accounting pairs into an array
$lines = file('http://hostname/accounting/ip.cgi');
$hour = strtotime(date('Y-m-d H:00:00'));

// Iterate over lines
foreach($lines as $line){
$line = explode(' ', $line); // 0 = src_addr, 1 = dst_addr, 2 = bytes, 3 = packets, 4 = src_user, 5 = dst_user

$src_addr = $line[0];
$dst_addr = $line[1];
$bytes = $line[2];
$packets = $line[3];
$src_user = $line[4];
$dst_user = trim($line[5]);

// Add to raw_hourly graph
if($src_user != '*' || $dst_user != '*'){
	$sql = "insert into raw_hourly (raw_date, raw_src_addr, raw_dst_addr, raw_bytes, raw_packets, raw_src_user, raw_dst_user) value ($hour, '$src_addr', '$dst_addr', '$bytes', '$packets', '$src_user', '$dst_user')";
	$result = $db->exec($sql);
	if(PEAR::isError($result)){
		die($result->getUserInfo());
	}
}

// Parse Users
// src_users
$sql = "select raw_src_user as user, raw_src_addr as ipaddr, sum(raw_bytes) bytes_downloaded, user_id from raw_hourly left join users on raw_src_user=user_name where raw_date = '$hour' and raw_src_user != '*' group by raw_src_user";
$result = $db->queryAll($sql);
if(PEAR::isError($result)){
	die($result->getUserInfo());
}
else{
	foreach($result as $user){
		$users[$user['user']] = $user;
	}

	// dst_users
	$sql = "select raw_dst_user as user, raw_dst_addr as ipaddr, sum(raw_bytes) bytes_uploaded, user_id from raw_hourly left join users on raw_dst_user=user_name where raw_date = '$hour' and raw_dst_user != '*' group by raw_dst_user";
	$result = $db->queryAll($sql);
	if(PEAR::isError($result)){
		die($result->getUserInfo());
	}
	else{
		foreach($result as $user){
			$users[$user['user']] = array_merge($users[$user['user']], $user);
		}
	}
}

foreach($users as $user){
	$user_id = $user['user_id'];
	$user_name = $user['user'];
	$user_ip = $user['ipaddr'];
	$user_uploaded = $user['bytes_uploaded'];
	$user_downloaded = $user['bytes_downloaded'];

	// Add / Update users in users table
	if(!$user_id){
		$sql = "insert into users (user_name, user_lastip, user_firstseen, user_lastseen) values ('$user_name', '$user_ip', '$hour', '$hour')";
	}
	else{
		$sql = "update users set user_lastip = '$user_ip', user_lastseen = '$hour' where user_id = $user_id";
	}
	$result = $db->exec($sql);
	if(PEAR::isError($result)){
		die($result->getUserInfo());
	}
	else{
		if(!$user_id){
			$user_id = $db->lastInsertID();
		}
	}

	// Increment user_hourly values
	$sql = "replace into user_hourly (hourly_date, user_id, hourly_download, hourly_upload) values ($hour, $user_id, $user_uploaded, $user_downloaded)";
	$result = $db->exec($sql);
	if(PEAR::isError($result)){
		die($result->getUserInfo());
	}
}
?>