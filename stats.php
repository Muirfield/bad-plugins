<?php
//
// Get github download stats
//
function req($url) {
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	// Set so curl_exec returns the result instead of outputting it.
	curl_setopt($ch,CURLOPT_USERAGENT,"alejandroliu");
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	// Get the response and close the channel.
	$response = curl_exec($ch);
	curl_close($ch);
	return json_decode($response);
}

//$data = file_get_contents();
//echo $data;

///$url = "https://api.github.com/users/alejandroliu/repos";
//print_r( req("https://api.github.com/users/alejandroliu/repos"));

//echo req("https://api.github.com/repos/alejandroliu/bad-plugins/releases");
//print_r( req("https://api.github.com/repos/alejandroliu/pocketmine-plugins/releases"));
print_r( req("https://api.github.com/repos/alejandroliu/bad-plugins/releases"));
