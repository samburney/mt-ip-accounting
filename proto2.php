<?php
include('config.php');
require('OpenVpnApi.php');

$ovpn = new OpenVpnApi($hostname, $port, $password);

echo "Connected clients: " . $ovpn->numConnectedClients() . "<br>";
echo "Client list:<br>";
?><pre><? print_r($ovpn->connectedClients()); ?></pre><?
echo "Client data:<br>";
?><pre><? print_r($ovpn->connectedClientsData()); ?></pre><?

foreach($ovpn->connectedClients() as $client) {
	echo "Client data for " . $client . ":"
	?><pre><? print_r($ovpn->clientData($client)); ?></pre><?
}

?>