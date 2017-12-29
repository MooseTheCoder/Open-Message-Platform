<?php
const VERSION = "0.0.1";

// Open Message Platform using MySQL

function db(){
	return mysqli_connect("localhost","root","password","omp");
}

function omp_ref_ack($err){
	$ERR_ARR = [
	'unf'=>'User not found',
	'wpw'=>'Wrong Password',
	'auth'=>'User authenticated',
	'tinv'=>'Token Invalid',
	'tval'=>'Token Valid',
	'msent'=>'Message Sent',
	'nmf'=>'No Message Found',
	'conf'=>'Confirmed',
	];
	return $ERR_ARR[$err];
}

function omp_user_auth($username,$password){
	$db = db();
	$userq = mysqli_query($db,"SELECT * FROM user WHERE username='$username'");
	if(mysqli_num_rows($userq) == 0){
		mysqli_close($db);
		return ['ack'=>'unf'];
	}
	$userq = mysqli_fetch_assoc($userq);
	if($password != $userq['password']){
		mysqli_close($db);
		return ['ack'=>'wpw'];
	}
	$token = sha1(rand(0,rand(0,getrandmax())));
	$id = $userq['id'];
	mysqli_query($db,"UPDATE user SET token='$token' WHERE id='$id'");
	mysqli_close($db);
	return ['ack'=>'auth','token'=>$token];
}

function omp_token_auth($user,$token){
	$db = db();
	$userq = mysqli_query($db,"SELECT * FROM user WHERE username='$user' AND token='$token'");
	if(mysqli_num_rows($userq) == 0){
		mysqli_close($db);
		return ['ack'=>'tinv'];
	}
	mysqli_close($db);
	return ['ack'=>'tval'];
}

function omp_send_message($user,$token,$message){
	$tokenAuth = omp_token_auth($user,$token);
	if($tokenAuth['ack'] != 'tval'){
		return$tokenAuth['ack'];
	}
	$db = db();
	mysqli_query($db,"INSERT INTO message (user,message) VALUES ('$user','$message')");
	mysqli_close($db);
	return ['ack'=>'msent'];
}

function omp_message_history($user,$token){
	$tokenAuth = omp_token_auth($user,$token);
	if($tokenAuth['ack'] != 'tval'){
		return $tokenAuth['ack'];
	}
	$db = db();
	$mrq = mysqli_query($db,"SELECT * FROM message");
	$m=[];
	while($x = mysqli_fetch_assoc($mrq)){
		$m[]=$x;
	}
	return $m;
	
}

function omp_get_next_message($user,$token,$mid){
	$tokenAuth = omp_token_auth($user,$token);
	if($tokenAuth['ack'] != 'tval'){
		return $tokenAuth['ack'];
	}
	$db = db();
	$mrq = mysqli_query($db,"SELECT * FROM message WHERE id='$mid'");
	mysqli_close($db);
	if(mysqli_num_rows($mrq) == 0){
		return ['ack'=>'nmf'];
	}
	$m = ['ack'=>'conf'];
	return array_merge($m,mysqli_fetch_assoc($mrq));
}
