<?php
class OpenVpnApi
{
	private $hostname;
	private $port;
	private $password;
	private $cache_file = '/tmp/ovpnapicache.json';

	public function __construct($hostname, $port, $password) {
		$this->hostname = $hostname;
		$this->port = $port;
		$this->password = $password;
	}

	public function numConnectedClients() {
		$clientlines = $this->getclientlines();
		return($clientlines[1] - $clientlines[0] + 1);
	}

	public function numRoutes() {
		$routetablelines = $this->getroutetablelines();
		return($routetablelines[1] - $routetablelines[0] + 1);
	}

	public function connectedClients() {
		$statusdata = $this->getstatusdata();
		$clientlines = $this->getclientlines();

		$clients = array();
		for($line = $clientlines[0]; $line <= $clientlines[1]; $line++) {
			$clientdata = explode(',', $statusdata[$line]);

			$clients[] = $clientdata[1];
		}

		return $clients;
	}

	public function connectedClientsData() {
		$statusdata = $this->getstatusdata();
		$clientlines = $this->getclientlines();

		$clients = array();
		for($line = $clientlines[0]; $line <= $clientlines[1]; $line++) {
			$clientdata = explode(',', $statusdata[$line]);

			$clients[$clientdata[1]] = array(
				'cn' => $clientdata[1],
				'ip_remote' => $clientdata[2],
				'ip_vpn' => $clientdata[3],
				'bytes_rx' => $clientdata[4],
				'bytes_tx' => $clientdata[5],
				'connected' => $clientdata[7],
			);
		}

		return $clients;
	}

	public function clientData($client) {
		$clientdata = $this->connectedClientsData();

		if(isset($clientdata[$client])) {
			return $clientdata[$client];
		}

		return false;
	}

	private function getstatusdata() {
		if($this->cache_exists('status_array_raw' . $this->hostname . $this->port)){
			return $this->cache_fetch('status_array_raw' . $this->hostname . $this->port);
		}
		else{
			$sock = fsockopen($this->hostname, $this->port, $errno, $errstr, 30);
			if(!$sock) {
				echo "$errstr ($errno)<br />\n";
			}
			else{
				fread($sock, 1024);
				fwrite($sock, "$this->password\n");
				$password_response_raw = fread($sock, 1024);
				$password_response = substr($password_response_raw, 0, strpos($password_response_raw, "\r\n"));
				
				if($password_response == "SUCCESS: password is correct") {
					fwrite($sock, "status 2\n");
					
					$status_raw_chunk = fread($sock, 1024);
					$status_raw = $status_raw_chunk;
					while(strlen($status_raw_chunk) == 1024){
						$status_raw_chunk = fread($sock, 1024);
						$status_raw .= $status_raw_chunk;
					}

					fclose($sock);
					
					$status_array_raw = explode("\r\n", $status_raw);
					$this->cache_store('status_array_raw' . $this->hostname . $this->port, $status_array_raw, 60);
					return $status_array_raw;
				}
				else{
					fclose($sock);
					exit('Password Incorrect');
				}
			}

			if($sock) {
				fclose($sock);
			}
		}
	}

	private function getclientlines() {
		$statusdata = $this->getstatusdata();

		$client_header_line = array_search("HEADER,CLIENT_LIST,Common Name,Real Address,Virtual Address,Bytes Received,Bytes Sent,Connected Since,Connected Since (time_t),Username", $statusdata);
		$routing_header_line = array_search("HEADER,ROUTING_TABLE,Virtual Address,Common Name,Real Address,Last Ref,Last Ref (time_t)", $statusdata);

		return array($client_header_line + 1, $routing_header_line - 1);
	}


	private function getroutetablelines() {
		$statusdata = $this->getstatusdata();

		$routing_header_line = array_search("HEADER,ROUTING_TABLE,Virtual Address,Common Name,Real Address,Last Ref,Last Ref (time_t)", $statusdata);

		return array($routing_header_line + 1, sizeof($statusdata) - 4);
	}

	private function cache_store($key, $value, $ttl = 0) {
		if(!$cache_array = $this->getcachearray()) {
			$cache_array = array();
		}

		$cache_array[$key] = array(
			'key' => $key,
			'value' => $value,
			'ttl' => $ttl > 0 ? date('U') + $ttl : 0,
		);

		file_put_contents($this->cache_file, json_encode($cache_array));
	}

	private function cache_exists($key) {
		if($cache_array = $this->getcachearray()) {
			if(isset($cache_array[$key])) {
				if($cache_array[$key]['ttl'] > date('U')) {
					return true;
				}
			}
		}

		return false;
	}

	private function cache_fetch($key) {
		if($cache_array = $this->getcachearray()) {
			if(isset($cache_array[$key])) {
				if($cache_array[$key]['ttl'] > date('U')) {
					return $cache_array[$key]['value'];
				}
			}
		}

	return false;
	}

	private function getcachearray() {
		if(file_exists($this->cache_file)) {
			$cache_array = json_decode(file_get_contents($this->cache_file), true);
			return $cache_array;
		}

		return false;
	}
}
?>