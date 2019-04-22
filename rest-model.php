<?php

$dbUser = "cse383";
$dbPassword = "HoABBHrBfXgVwMSz";
$mysqli = mysqli_connect("localhost", $dbUser, $dbPassword, "cse383");

if(mysqli_connect_errno($mysqli)){
	echo "Failed to connect to MySql: " . mysqli_connect_error();
		echo "Failed to connect to MySQL: " . mysqli_connect_error();
			die;
}

/*	getToken($jsonData)
*	method: POST
*	route: rest.php/v1/user
*	This method is used for login credential validation
*	returns a token if and only if the password matches the password stored
*	in the database for the particular user
*/
function getToken($jsonData){
	global $mysqli;
	global $jsonData;		
	$password = $jsonData['password'];
	$token = "";
	$dbPass = "unset";
	if($stmt = $mysqli->prepare("SELECT password FROM users WHERE user = ?")){
		$stmt->bind_param("s", $jsonData['user']);
	        $stmt->execute();
        	$stmt->store_result();
		$stmt->bind_result($dbPass);
	        $stmt->fetch();

        	if(password_verify($password, $dbPass)){
	            $token = genRanString();
        	    if($query = $mysqli->prepare("INSERT INTO tokens (user, token) VALUES (?, ?)")){
                	$query->bind_param("ss", $jsonData['user'], $token);
	                $query->execute();
	                $ret = array('status'=>'OK','token'=>$token);
        	        retJson($ret);
            	     }else{
                		$error = "Error: " . $mysqli->errno . " : " . $mysqli->error;
		                $ret = array('status'=>'FAIL','msg'=>$error);
		                retJson($ret);
            	     }
        	}else{
            		$ret = array('status'=>'FAIL','msg'=>'invalid login information');
		        retJson($ret);
        	}
		$ret = array('status'=>'FAIL','msg'=>'FAILED FAILED FAILED');
		retJson($ret);
	}else{
		$ret = array('status'=>'FAIL','msg'=>'failed to retrive password from database');
		retJson($ret);
	}
}

function verifyPassword($dbPass, $password){
    global $mysqli;

}

/*	consumeItems($jsonData)
*	method: POST
*	route: rest.php/v1/items
*	This method takes in json data and then adds that to the diaryitems table in the database
* 	if and only if the user token is valid
*/
function consumeItems($jsonData){
	global $mysqli;
	global $jsonData;
        $userKey = getUserFK($jsonData['token']);
	if($userKey == "-1" || is_null($userKey)){
		$ret = array('status'=>'AUTH_FAIL','msg'=>'failed to authenticate and retrieve user key');
		retJson($ret);
	}
	if(!$stmt = $mysqli->prepare("INSERT INTO diary (userFK, itemFK) VALUES(?, ?)")){
		$ret = array('status'=>'FAIL','msg'=>'Not actual item');
		retJson($ret);
	}
	if(!$stmt->bind_param("ss",$userKey, $jsonData['itemFK'])){
		$ret = array('status'=>'FAIL','msg'=>'failed to bind parameters when consuming item');
		retJson($ret);
	}
	if(!$stmt->execute()){
		$ret = array('status'=>'FAIL','msg'=>'failed to execute consume query');
		retJson($ret);
	}
	$ret = array('status'=>'OK','msg'=>'successfully consumed item');
	retJson($ret);
}

/* 	userItems($token)
*	method: GET
*	route: rest.php/items/token
*	This method is used to retrieve the items consumed by the user
*/
function userItems($token){
	global $mysqli;
	$userKey = getUserFK($token);
	if($userKey == "-1" || is_null($userKey)){
		$ret = array('status'=>'AUTH_FAIL','msg'=>'failed to authenticate and retrieve user key');
		retJson($ret);
	}
	if(!$stmt = $mysqli->prepare("SELECT diaryItems.pk,diaryItems.item,timestamp FROM diaryItems LEFT JOIN diary ON diaryItems.pk=diary.itemFK WHERE userFK=? ORDER BY timestamp DESC LIMIT 30")){
		$ret = array('status'=>'FAIL','msg'=>'failed to prepare query when getting user items');
		retJson($ret);
	}
	if(!$stmt->bind_param("s", $userKey)){
		$ret = array('status'=>'FAIL','msg'=>'failed to bind parameters when getting user items');
		retJson($ret);
	}
	if(!$stmt->execute()){
		$ret = array('status'=>'FAIL','msg'=>'failed to execute query when getting user items');
		retJson($ret);
	}
	
    $res = array();
    $place = array();
    $pk = -1;
    $item = "";
    $timestamp = "";
    $res['status']='OK';
    $res['msg']='';
    $stmt->bind_result($pk, $item, $timestamp);
	while($stmt->fetch()){
		$place[] = array('pk'=>$pk,'item'=>$item,'timestamp'=>$timestamp);
	}
    $res['items'] = $place;
	retJson($res);
}

/*	listItems()
*	method: GET
*	route: rest.php/v1/items
*	This method is to retrieve all the possible items from the database
*/
function listItems(){
	global $mysqli;
	if(!$stmt = $mysqli->prepare("SELECT pk, item FROM diaryItems")){
		$ret = array('status'=>'FAIL','msg'=>'failed to prepare query when getting list of items');
		retJson($ret);
	}
	if(!$stmt->execute()){
		$ret = array('status'=>'FAIL','msg'=>'failed to execute query when getting list of items');
		retJson($ret);
	}

    $res = array();
    $place = array();
    $pk = -1;
    $item = "";
    $stmt->bind_result($pk, $item);
    $res['status']='OK';
    $res['msg']='';
	while($stmt->fetch()){
		$place[] = array('pk'=>$pk,'item'=>$item);
    }
    $res['items']=$place;
	retJson($res);
}
/* 	userItemSummary($token)
*	method: GET
*	route: rest.php/v1/itemsSummary/token
*	gets the item summary for a user
*	gets the user primary key using the getUserFK method
*	determines if the returned key is valid and if so then proceeds to query the database
*	it then iterates over the return statement and adds that to an array which is then passed to retJson()
*/
function userItemSummary($token){
	global $mysqli;

	$userKey = getUserFK($token);
	if($userKey == "-1" || is_null($userKey)){
		$ret = array('status'=>'AUTH_FAIL','msg'=>'failed to authenticate and retrieve user key');
		retJson($ret);
	}

	if(!$stmt = $mysqli->prepare("SELECT diaryItems.item,count(timestamp) AS count FROM diaryItems LEFT JOIN diary ON diaryItems.pk=diary.itemFK WHERE userFK=? GROUP BY diaryItems.item")){
		$ret = array('status'=>'FAIL','msg'=>'failed to prepare select statement in item summary');
		retJson($ret);
	}
	if(!$stmt->bind_param("s", $userKey)){
		$ret = array('status'=>'FAIL','msg'=>'failed to bind parameters in item summary');
		retJson($ret);
	}
	if(!$stmt->execute()){
		$ret = array('status'=>'FAIL','msg'=>'failed to execute item summary query');
		retJson($ret);
	}

    $res = array();
    $place = array();
    $item = "";
    $count = -1;
    $stmt->bind_result($item, $count);
    $res['status']='OK';
    $res['msg']='';
	while($stmt->fetch()){
		$place[] = array('item'=>$item,'count'=>$count);
	}
    $res['items'] = $place;
	retJson($res);
}

/* getUserFK($token)
*	this method takes in the user's token and returns their primary key
*	it works by joining the tokens table and the users table on the username text field
*	default value of $userToken is -1 and that is used to check in the other methods
*	whether or not the token passed in is valid (if it's set to null after the query then that means)
*	the token is invalid
*/
function getUserFK($token){
	global $mysqli;
	$userToken = "-1";
	if(!$res = $mysqli->prepare("SELECT users.pk FROM tokens INNER JOIN users ON tokens.user=users.user WHERE token = ?")){
		return $userToken;
	}
	if(!$res->bind_param("s", $token)){
		return $userToken;
	}
	if(!$res->execute()){
		return $userToken;
    }
    
    $res->bind_result($userToken);
	$res->fetch();
	return $userToken;
}

/*	genRanString()
*	Used to generate a random string for the tokens
*/
function genRanString(){
	$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < 15; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
	
}

/*	retJson($data)
*	Used to return json data to the front end
*/
function retJson($data){
	header('content-type: application/json');
	print json_encode($data);
	exit;
}
?>
