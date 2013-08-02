<?php
chdir(realpath(dirname(__FILE__)));
require('../OpenVpnApi.php');

$hostname = $_SERVER['argv'][1];
$command = $_SERVER['argv'][2];
$query = @$_SERVER['argv'][3];
$index = @$_SERVER['argv'][4];

include('../config.php');

$ovpn = new OpenVpnApi($hostname, $port, $password);

if($command == 'num_indexes') {
	echo count($clients = $ovpn->connectedClients()) . "\n";
}

if($command == 'index' || $command == 'query') {
	$clients = $ovpn->connectedClients();

	foreach($clients as $client){
		if($command == 'index') {
			echo "$client\n";
		}
		else if ($command == 'query') {
			echo "$client!$client\n";
		}
	}
}

if($command == 'get') {
	$client = $ovpn->clientData($index);

	switch($query) {
		case 'index':
			echo $client['cn'] . "\n";
			return;

		case 'bytesRx':
			echo $client['bytes_rx'] . "\n";
			return;

		case 'bytesTx':
			echo $client['bytes_tx'] . "\n";
			return;
	}
}
?>
