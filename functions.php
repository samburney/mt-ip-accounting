<?php
// Get $userdata from user_subnets if possible
function get_subnet_user($ip, $user_subnets)
{
  foreach($user_subnets as $subnet) {
    $subnet_explode = explode('/', $subnet['subnet']);
    $subnet_addr = $subnet_explode[0];
    $subnet_cidr = $subnet_explode[1];
    if(ip_in_network($ip, $subnet_addr, $subnet_cidr)) {
      return($subnet['userdata']);
    }
  }

  // No results
  return false;
}

// Check that IP is in specified network; Source: http://php.net/manual/en/function.ip2long.php#92544
function ip_in_network($ip, $net_addr, $net_mask){
    if($net_mask <= 0){ return false; }
        $ip_binary_string = sprintf("%032b",ip2long($ip));
        $net_binary_string = sprintf("%032b",ip2long($net_addr));
        return (substr_compare($ip_binary_string,$net_binary_string,0,$net_mask) === 0);
}
