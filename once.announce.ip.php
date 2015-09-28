<?php

// 101.45.75.219:12345
// 101.45.75.219 &port= 12345
// ::FFFF:101.45.75.219 &port= 12345
// dead:beef::1234 &port= 12345
// [dead:beef::1234] &port= 12345
// [dead:beef::1234]:12345

$addresses = array();

if ( isset($_GET['ip']) ) {
	$addresses[] = $_GET['ip'];
}
if ( isset($_GET['ipv4']) ) {
	$addresses[] = $_GET['ipv4'];
}
if ( isset($_GET['ipv6']) ) {
	$addresses[] = $_GET['ipv6'];
}
if ( isset($_SERVER['REMOTE_ADDR']) ) {
	$addresses[] = $_SERVER['REMOTE_ADDR'];
}
// If we're honoring X_FORWARDED_FOR, we check and use that first if its present.
if ( isset($_SERVER['HTTP_CLIENT_IP']) && $settings['honor_xff'] ) {
	$addresses[] = $_SERVER['HTTP_CLIENT_IP'];
}
if ( isset($_SERVER['HTTP_X_FORWARDED_FOR']) && $settings['honor_xff'] ) {
	$addresses[] = $_SERVER['HTTP_X_FORWARDED_FOR'];
}

// Error if we can't find any addresses.
if ( !count($addresses) ) {
	tracker_error('Unable to obtain client IP');
}

$peer['ipv4'] = false;
$peer['ipv6'] = false;
$peer['port'] = false;
$peer['portv4'] = false;
$peer['portv6'] = false;

if (
	isset($_GET['port']) &&
	is_int($_GET['port'])
) {
	$peer['port'] = $_GET['port'];
}

foreach ( $addresses as $address ) {

	if ( !$peer['ipv4'] ) {
		// Trim IPv6 Padding
		$address_ipv4 = trim($address, '::ffff:');
		// Try and find a port
		if ( strpos($address_ipv4, ':') !== false ) {
			$address_ipv4 = explode(':', $address_ipv4);
			$address_portv4 = $address_ipv4[1];
			$address_ipv4 = $address_ipv4[0];
		}
		// Validate
		if ( filter_var($address_ipv4, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) ) {
			$peer['ipv4'] = $address_ipv4;
			if ( !$peer['portv4'] && is_int($address_portv4) ) {
				$peer['portv4'] = $address_portv4;
			}
		}
	}

	$address_port = false;

	if ( !$peer['ipv6'] ) {
		$address_ipv6 = $address;
		// Try and find a port
		if ( strpos($address_ipv6, ']:') !== false ) {
			$address_ipv6 = explode(']:', $address);
			$address_portv6 = $address_ipv6[1];
			$address_ipv6 = $address_ipv6[0];
		}
		// Trim any brackets
		if ( strpos($address_ipv6, '[') !== false ) {
			$address_ipv6 = trim($address_ipv6, '[]');
		}
		// Validate
		if ( filter_var($address_ipv6, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) ) {
			$peer['ipv6'] = $address_ipv6;
			if ( !$peer['portv6'] && is_int($address_portv6) ) {
				$peer['portv6'] = $address_portv6;
			}
		}
	}

}

if ( $peer['port'] && !$peer['portv4'] )
	$peer['portv4'] = $peer['port'];
}
if ( $peer['port'] && !$peer['portv6'] )
	$peer['portv6'] = $peer['port'];
}

if (
	(
		!$peer['ipv4'] &&
		!$peer['portv4']
	) ||
	(
		!$peer['ipv6'] &&
		!$peer['portv6']
	)
) {
	tracker_error('Unable to get IP and Port');
}
