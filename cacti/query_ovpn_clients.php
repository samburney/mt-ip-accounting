<?php
require('../OpenVpnApi.php');

$hostname = $_SERVER['argv'][1];
$port = $_SERVER['argv'][2];
$password = $_SERVER['argv'][3];
$command = $_SERVER['argv'][4];
$query = @$_SERVER['argv'][5];
$index = @$_SERVER['argv'][6];

$ovpn = new OpenVpnApi($hostname, $port, $password);

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
	if($query == 'bytesRx') {
		$client = $ovpn->clientData($index);

		echo $client['bytes_rx'] . "\n";
	}

	if($query == 'bytesTx') {
		$client = $ovpn->clientData($index);

		echo $client['bytes_tx'] . "\n";
	}
}
?>