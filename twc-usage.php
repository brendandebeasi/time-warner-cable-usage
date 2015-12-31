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
curl_setopt($ch, CURLOPT_URL, 'https://myservices.timewarnercable.com/login/');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, array(
	'username'=>$config['username'],
	'password'=>$config['password'],
));
$login1 = curl_exec($ch);

//Wayfarer login
curl_setopt($ch, CURLOPT_URL, 'https://wayfarer.timewarnercable.com/wayfarer');
curl_setopt($ch, CURLOPT_REFERER, 'https://myservices.timewarnercable.com/login/');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, array(
	'agent'=>'mys',
	'Ecom_User_ID'=>$config['username'],
	'Ecom_Password'=>$config['password'],
	'errorUrl'=>'https://myservices.timewarnercable.com/login/validate?loginFailed=true',
	'originalResourceUrl'=>base64_encode('https://myservices.timewarnercable.com/account/index?jli=true'),
));
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
	'Connection'=>'keep-alive',
	'Cache-Control'=>'max-age=0',
	'Accept'=>'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
	'Origin'=>'https://myservices.timewarnercable.com',
	'Upgrade-Insecure-Requests'=>'1',
	'Content-Type'=>'application/x-www-form-urlencoded',
	'Accept-Encoding'=>'gzip, deflate',
	'Accept-Language'=>'en-US,en;q=0.8'
));

$login2 = curl_exec($ch);
preg_match('~<input type="hidden" name="SAMLRequest" value="(.*?)"~',$login2,$saml);
if(isset($saml[1])) $saml = str_replace(PHP_EOL,'',trim($saml[1]));
//echo $login2;die();

//SAML Request
curl_setopt($ch, CURLOPT_URL, 'https://ids.rr.com/nidp/saml2/sso');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, array(
	'SAMLRequest'=>$saml,
	'RelayState'=>base64_encode('https://myservices.timewarnercable.com/account/index?jli=true'),
	'agent'=>'mys',
	'option'=>'credential',
	'Ecom_User_ID'=>$config['username'],
	'errorUrl'=>'https://myservices.timewarnercable.com/login/validate?loginFailed=true',
	'Ecom_Password'=>$config['password'],
));
curl_setopt($ch, CURLOPT_REFERER, 'https://wayfarer.timewarnercable.com/wayfarer');
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
	'Connection'=>'keep-alive',
	'Cache-Control'=>'max-age=0',
	'Accept'=>'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
	'Origin'=>'https://wayfarer.timewarnercable.com',
	'Upgrade-Insecure-Requests'=>'1',
	'Content-Type'=>'application/x-www-form-urlencoded',
	'Accept-Encoding'=>'gzip, deflate',
	'Accept-Language'=>'en-US,en;q=0.8'
));
$saml_req = curl_exec($ch);
echo $saml_req;die();



//SAML POST
curl_setopt($ch, CURLOPT_URL, 'https://wayfarer.timewarnercable.com/wayfarer/SAML2/POST');
curl_setopt($ch, CURLOPT_REFERER, 'https://wayfarer.timewarnercable.com/wayfarer');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, array(
	'SAMLResponse'=>$saml,
	'RelayState'=>base64_encode('https://myservices.timewarnercable.com/account/index?jli=true')
));
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
	'Content-Type'=> 'application/x-www-form-urlencoded',
	'Origin'=> 'https://wayfarer.timewarnercable.com',
));
$saml = curl_exec($ch);
echo $saml;
die();


//Do SSO
curl_setopt($ch, CURLOPT_URL, 'https://ids.rr.com/nidp/saml2/sso');
curl_setopt($ch, CURLOPT_REFERER, 'https://wayfarer.timewarnercable.com/wayfarer');
curl_setopt($ch, CURLOPT_HTTPHEADER, array());
curl_setopt($ch, CURLOPT_POST, false);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');

$sso = curl_exec($ch);
echo $sso;
die();
//Get Modem Info
curl_setopt($ch, CURLOPT_URL, 'https://myservices.timewarnercable.com/internet/api_modem_info');
curl_setopt($ch, CURLOPT_REFERER, 'https://myservices.timewarnercable.com/myservices/internet/index');
curl_setopt($ch, CURLOPT_POST, false);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');

$modem_info = curl_exec($ch);
echo $modem_info;
//
//$usage = curl_exec($ch);
//if(empty($config['out'])) echo $usage;
//else file_put_contents($config['out'], $usage);
//
//unlink('/tmp/twcauth.txt');
//curl_close($ch);