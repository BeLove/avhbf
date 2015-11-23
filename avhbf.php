<?php
// main script

// check CLI mode
if (php_sapi_name() <> 'cli') die("Use this script only via console"); 
// check PHP version
if (version_compare(phpversion(), '5.3', '<')) die("Sorry, but this script can be running only with PHP version > 5.3"); 
// check cURL
if (!function_exists('curl_init')) die ("You need to install cURL (and php cURL lib)");
// Check libxdiff
if (function_exists('xdiff_string_bdiff')) $xdiff_installed = true;
	else $xdiff_installed = false;

// functions
function getResponse($target, $port, $vhost) {
	if (!$vhost) $vhost = md5(time());
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, "http://$target:$port/");
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array(
		'User-Agent: Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/31.0.1650.57 Safari/537.36',
		'Host: '. $vhost
		));
	// preventing false positive results
	$result = str_replace($target, "", curl_exec($ch));
	curl_close ($ch);
	return $result; // str_replace for
}

function compareResponses($non_exist, $test) { // return bool
	if (1) {
		$lines_non_matched = 0;
		$non_exist_linebyline = explode("\n", $non_exist);
		$test_linebyline = explode("\n", $test);
		// trust me, array_diff it a poor solution... we need hashmap
		// create hashmap
		foreach($test_linebyline as $line) {
			$test_hashmap[md5(trim($line))] = '';
		}
		$lines = '';
		foreach($non_exist_linebyline as $line) {
			if (!array_key_exists(md5(trim($line)), $test_hashmap)) {
				$lines_non_matched++;
				//$lines .= $line." - ".md5($line)."\n"; // / uncomment this if you want to see diff between non-exist and testing domains
			}
		}
		if ($lines_non_matched > 3) { // if you get a lot of false positives increase it
			echo $lines;
			//echo "--------non exist\n";
			//foreach ($non_exist_linebyline as $line)
			//	echo $line." - ".md5($line)."\n";
			//echo "--------\n";
			//foreach ($test_linebyline as $line)
			//	echo $line." - ".md5($line)."\n";
				
			return array(true, $lines_non_matched);
		} else return array(false);
	} else { // use xdiff library
		die("xdiff in development"); //todo
	}
}
$banner = "
================== (A) Virtual Host BruteForcer (AVHBF) =============================
| Usage:
|	php avhbf.php [-p [1-65535]] [-z [0|1]] -t [domain|ip] 
|	
|	-p - port (80 by default)
|	-z - 0 or 1. If you provide domain name you can disable auto zone remove\n (e.g. domain.com will not cut to domain.test, 
|	if 0 is provided it will be domain.com.test). By default is enabled
|	-v - verbose mode
|	-t - target. Domain name or IP address (required)	
|		
| Example:
|	php avhbf.php -t example.com
|		
================================================================================";

$options = getopt("t:p:z:v:");
if (!isset($options['t'])) {
	echo $banner."\n\nPlease, set target (-t option)\n\n";
	die();
} else {
	if (filter_var($options['t'], FILTER_VALIDATE_IP)) {
		$isDomain = false;
		$target = $options['t'];
		$ip = $options['t'];
		echo "Okay, IP as a target: ".$target."\n";
	} else
	if ($record = dns_get_record($options['t'], DNS_A)) {
			$isDomain = true;
			$target = $options['t'];
			$ip = $record[0]['ip']; // we take first ([0]) IP from DNS response. Change this if you need
			echo "Okay, domain as a target: ".$target.", IP: ".$ip."\n";
	} else die("Provided target is not an IP address or domain name isn't valid\n\n");
}

if (isset($options['p'])) {
	if (is_numeric($options['p']) && $options['p'] > 1 && $options['p'] < 65535) {
		$port = $options['p'];
	} else die("Port must be numeric and start from 1 to 65535\n\n");
} else $port = 80;

if (isset($options['z']) && is_numeric($options['z'])) {
	if ($options['z'] == 0) {
		$removeZone = 0;
	} else $removeZone = 1;
} else $removeZone = 1;

if (isset($options['v'])) {
	$verbose = true;
} else $verbose = false;

if ($isDomain && $removeZone) { // remove current zone for zones checking (only for domains)
	$target_for_zone = substr($target, 0, strrpos($target, "."));
} else {
	$target_for_zone = $target;
}
// get response for non exist domain
$non_exist_vhost = getResponse($ip, $port, NULL); 

// start bruteforce
$domains = file('domains.txt');
$zones = file('zones.txt');

// check zones for current domain (without subdomain)
foreach ($zones as $zone) {
	$zone = trim($zone);
	if ($isDomain) {
		if ($verbose) echo "Testing $target_for_zone.$zone...\n";
		$compareResult = compareResponses($non_exist_vhost, getResponse($ip, $port, $target_for_zone.".".$zone));
		if ($compareResult[0]) {				
			echo "Found: ".$target_for_zone.".".$zone.", non-matched lines: {$compareResult[1]}\n";
		}
	} else { // if IP provided as a target
		if ($verbose) echo "Testing $domain.$zone...\n";
		$compareResult = compareResponses($non_exist_vhost, getResponse($ip, $port, $domain.".".$zone));
		if($compareResult[0]) 
			echo "Found: ".$domain.".".$zone.", non-matched lines: {$compareResult[1]}\n";
	}
}	
foreach ($domains as $domain) {
	$domain = trim($domain);
	// check domain
	if ($isDomain) {
		if ($verbose) echo "Testing $domain.$target...\n";
		$compareResult = compareResponses($non_exist_vhost, getResponse($ip, $port, $domain.".".$target));
		if($compareResult[0])
			echo "Found: $domain.$target, non-matched lines: {$compareResult[1]}\n";
	} else { // if IP provided as a target
		if ($verbose) echo "Testing $domain...\n";
		$compareResult = compareResponses($non_exist_vhost, getResponse($ip, $port, $domain));
		if($compareResult[0]) 
			echo "Found: ".$domain.", non-matched lines: {$compareResult[1]}\n";
	}
	// check with zones
	foreach ($zones as $zone) {
		$zone = trim($zone);
		if ($isDomain) {
			if ($verbose) echo "Testing $domain.$target_for_zone.$zone...\n";
			$compareResult = compareResponses($non_exist_vhost, getResponse($ip, $port, $domain.".".$target_for_zone.".".$zone));
			if ($compareResult[0]) {				
				echo "Found: ".$domain.".".$target_for_zone.".".$zone.", non-matched lines: {$compareResult[1]}\n";
			}
		} else { // if IP provided as a target
			if ($verbose) echo "Testing $domain.$zone...\n";
			$compareResult = compareResponses($non_exist_vhost, getResponse($ip, $port, $domain.".".$zone));
			if($compareResult[0]) 
				echo "Found: ".$domain.".".$zone.", non-matched lines: {$compareResult[1]}\n";
		}
	}
}
// end of script
echo "Finished!\n"; 
?>