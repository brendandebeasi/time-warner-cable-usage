<?php

$config = array();

if(isset($argv[1])) $config['username']=$argv[1];
else die('No username set (argument 1)');

if(isset($argv[2])) $config['password']=$argv[2];
else die('No password set (argument 2)');

if(isset($argv[3])) $config['out']=$argv[3];
else $config['out'] = '';


$ch = curl_init();
curl_setopt($ch, CURLOPT_COOKIEJAR, '/tmp/twcauth.txt');
curl_setopt($ch, CURLOPT_COOKIEFILE, '/tmp/twcauth.txt');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_2) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/47.0.2526.106 Safari/537.36');

//Get initial cookies
curl_setopt($ch, CURLOPT_URL, 'http://timewarnercable.com/');
curl_exec($ch);


//Submit login info
curl_setopt($ch, CURLOPT_URL, 'https://myservices.timewarnercable.com/myservices/login/validate');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, array(
	'username'=>$config['username'],
	'password'=>$config['password'],
));
$login1 = curl_exec($ch);

//Wayfarer login
curl_setopt($ch, CURLOPT_URL, 'https://wayfarer.timewarnercable.com/wayfarer');
curl_setopt($ch, CURLOPT_REFERER, 'https://myservices.timewarnercable.com/login/validate');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(array(
	'agent'=>'mys',
	'Ecom_User_ID'=>$config['username'],
	'Ecom_Password'=>$config['password'],
	'errorUrl'=>'https://myservices.timewarnercable.com/login/validate?loginFailed=true',
	'originalResourceUrl'=>base64_encode('https://myservices.timewarnercable.com/account/index?jli=true'),
)));
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
	'Connection'=>'keep-alive',
	'Cache-Control'=>'max-age=0',
	'Accept'=>'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
	'Origin'=>'https://myservices.timewarnercable.com',
	'Upgrade-Insecure-Requests'=>'1',
	'Accept-Encoding'=>'gzip, deflate',
	'Accept-Language'=>'en-US,en;q=0.8',
));

$login2 = curl_exec($ch);
preg_match('~<input type="hidden" name="SAMLRequest" value="(.*?)"~',$login2,$saml);
if(isset($saml[1])) $saml = str_replace(PHP_EOL,'',trim($saml[1]));

//SAML Request
curl_setopt($ch, CURLOPT_URL, 'https://ids.rr.com/nidp/saml2/sso');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(array(
	'SAMLRequest'=>$saml,
	'RelayState'=>base64_encode('https://myservices.timewarnercable.com/account/index?jli=true'),
	'agent'=>'mys',
	'option'=>'credential',
	'Ecom_User_ID'=>$config['username'],
	'errorUrl'=>'https://myservices.timewarnercable.com/login/validate?loginFailed=true',
	'Ecom_Password'=>$config['password'],
)));
curl_setopt($ch, CURLOPT_REFERER, 'https://wayfarer.timewarnercable.com/wayfarer');
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
	'Connection'=>'keep-alive',
	'Cache-Control'=>'max-age=0',
	'Accept'=>'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
	'Origin'=>'https://wayfarer.timewarnercable.com',
	'Upgrade-Insecure-Requests'=>'1',
	'Accept-Encoding'=>'gzip, deflate',
	'Accept-Language'=>'en-US,en;q=0.8',
));

$saml_resp = curl_exec($ch);
preg_match('~<input type="hidden" name="SAMLResponse" value="(.*?)"~',$saml_resp,$saml);
if(isset($saml[1])) $saml = str_replace(PHP_EOL,'',trim($saml[1]));

//SAML POST
curl_setopt($ch, CURLOPT_URL, 'https://wayfarer.timewarnercable.com/wayfarer/SAML2/POST');
curl_setopt($ch, CURLOPT_REFERER, 'https://ids.rr.com/nidp/saml2/sso?sid=0');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(array(
	'SAMLResponse'=>$saml,
	'RelayState'=>base64_encode('https://myservices.timewarnercable.com/account/index?jli=true'),
	'x'=>75,
	'y'=>12
)));
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
	'Connection'=>'keep-alive',
	'Cache-Control'=>'max-age=0',
	'Accept'=>'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
	'Origin'=>'https://wayfarer.timewarnercable.com',
	'Upgrade-Insecure-Requests'=>'1',
	'Accept-Encoding'=>'gzip, deflate',
	'Accept-Language'=>'en-US,en;q=0.8',
));


$saml = curl_exec($ch);


//Get Modem Info
curl_setopt($ch, CURLOPT_URL, 'https://myservices.timewarnercable.com/internet/api_modem_info');
curl_setopt($ch, CURLOPT_REFERER, 'https://myservices.timewarnercable.com/myservices/internet/index');
curl_setopt($ch, CURLOPT_POST, false);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');

$modems = json_decode(curl_exec($ch),true);

foreach($modems as &$modem) {
	curl_setopt($ch, CURLOPT_URL, 'https://myservices.timewarnercable.com/internet/api_modem_usage?mac='.$modem['mac'].'&mode=currentMonth');
	curl_setopt($ch, CURLOPT_REFERER, 'https://myservices.timewarnercable.com/myservices/internet/index');
	curl_setopt($ch, CURLOPT_POST, false);
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
	$usage = json_decode(curl_exec($ch),true);
	$modem['usage'] = $usage['result']['usage'];
}


if(empty($config['out'])) echo json_encode($modems);
else file_put_contents($config['out'], json_encode($modems));

unlink('/tmp/twcauth.txt');
curl_close($ch);