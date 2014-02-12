<?php
parse_str($cookieString, $params);
$params["extension"] = $extension;
$params["url"] = urldecode($url);


$descriptorspec = array(
				0 => array('pipe', 'r'),
				1 => array('pipe', 'w'),
				2 => array('pipe', 'w')
		);
$pipes = null;

$casperParams = "";
foreach ($params as $key=>$value){
 $casperParams .= " --$key='$value'";
}


$proccess = "casperjs ".dirname(__FILE__)."/amazon.js $casperParams";

$pdfReader = proc_open($proccess, $descriptorspec, $pipes, null, null);
$page = "";
$error = "";
if (is_resource($pdfReader)) {
	

	$stdin = $pipes[0];

	$stdout = $pipes[1];

	$stderr = $pipes[2];

	while (!feof($stdout)) {
		$page .= fgets($stdout);
	}

	while (!feof($stderr)) {
		$error .= fgets($stderr);
	}

	fclose($stdin);
	fclose($stdout);
	fclose($stderr);

	$exit_code = proc_close($pdfReader);
}
echo $page;

echo $error;
