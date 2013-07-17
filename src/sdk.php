<?php

require_once('../vendor/autoload.php');

use Aws\CloudFront\CloudFrontClient;

if(!isSet($_ENV['ACCESS_KEY_ID'])){
  echo "ACCESS_KEY_ID enviroment variable is not set. Make sure you have set variables_order to 'EGPCS' in php.ini.";
  exit();
}

if(!isSet($_ENV['SECRET_KEY_ID'])){
  echo "SECRET_KEY_ID enviroment variable is not set. Make sure you have set variables_order to 'EGPCS' in php.ini.";
  exit();
}

$client = CloudFrontClient::factory(array(
    'key' => $_ENV['ACCESS_KEY_ID'],
    'secret' => $_ENV['SECRET_KEY_ID']
));

$distributions =  $client->listDistributions();

foreach($distributions['Items'] as $distribution){
    printf("Id: %s \t Status: %s \t LastModified: %s \n", $distribution['Id'], $distribution['Status'], $distribution['LastModifiedTime']);
}