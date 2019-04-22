<?php
require_once("rest-model.php");

$method = strtolower($_SERVER['REQUEST_METHOD']);
if (isset($_SERVER['PATH_INFO']))
	$path = $_SERVER['PATH_INFO'];
else
	$path = "";


$pathParts = explode("/", $path);
if(count($pathParts) < 3){
	$ret = array('status'=>'FAIL', 'msg'=>'Invalid URL');
	retJson($ret);
}
if($pathParts[1] != "v1"){
	if($pathParts[1] != "items"){
		$ret = array('status'=>'FAIL', 'msg'=>'Invalid URL');
		retJson($ret);
	}
}

$jsonData = array();
try{
	$rawData = file_get_contents("php://input");
	$jsonData = json_decode($rawData, true);
	if($rawData !== "" && $jsonData==NULL){
		$ret = array('status'=>'FAIL','msg'=>'invalid json');
		retJson($ret);
	}
} catch(Exception $e){
	$ret = array('status'=>'FAIL','msg'=>$e);
	retJson($ret);
};

if($method === "post" && count($pathParts) == 3){
	if($pathParts[2] == "user"){
		getToken($jsonData);
		$ret = array('status'=>'FAIL','msg'=>'MADE IT PAST THE METHOD CALL');
		retJson($ret);
	} elseif($pathParts[2] = "items"){
		consumeItems($jsonData);
	}
} elseif($method === "get" && (count($pathParts) == 3 || count($pathParts) == 4)){
	if($pathParts[1] == "items"){
		if($pathParts[2] != ""){
			userItems($pathParts[2]);
		}else{
			$ret = array('status'=>'FAIL','msg'=>'Incorrect API call');
			retJson($ret);
		}
		
	} 
	if($pathParts[1] == "v1" && $pathParts[2] == "items"){
		listItems();
	}
	if($pathParts[1] == "v1" && $pathParts[2] == "itemsSummary"){
		if($pathParts[3] != ""){
			userItemSummary($pathParts[3]);
		}else{
			$ret = array('status'=>'FAIL','msg'=>'Incorrect API call');
			retJson($ret);
		}
	}
}
$ret = array('status'=>'FAIL','msg'=>"Incorrect API call");
retJson($ret);


