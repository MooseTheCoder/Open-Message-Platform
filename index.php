<?php

session_start();

include 'omp.php';

$p = "";

if(isset($_GET['p'])){
	$p = $_GET['p'];
}else{
	header("Location: ?p=login");
}

if($p == "login"){
	$e="";
	if(isset($_POST['login'])){
		$auth = omp_user_auth(strval($_POST['username']),strval($_POST['password']));
		if($auth['ack'] != 'auth'){
			$e.=omp_ref_ack($auth['ack']);
		}else{
			$_SESSION['user'] = strval($_POST['username']);
			$_SESSION['token'] = $auth['token'];
			header("Location: ?p=home");
			exit;
		}
	}
	$e.='
	<form method="post" action="?p=login">
		<input type="text" name="username" placeholder="Username"><br />
		<input type="password" name="password" placeholder="Username"><br />
		<input type="submit" name="login" value="Login">
	</form>
	';
	echo $e;
}

if($p == "home"){
	$auth = omp_token_auth($_SESSION['user'],$_SESSION['token']);
	if($auth['ack'] == "tinv"){
		header("Location: ?p=login");
		exit;
	}
	$e="<head>
		<meta charset='utf-8' />
		<script src='https://cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js'></script>
	</head>";
	$e.='<div id="chat" style="border:2px solid black; max-height:80%; height:80%; overflow-y:scroll;"></div>';
	$e.='<center><div id="tools">
	<input type="text" id="message" placeholder="Message">
	<input type="button" id="send" value="Send!" />
	</div></center>';
	$e.='
	<script>
	var nextMessage = 1;
		
	$(document).ready(function(){
		$.ajax({
			url : "?p=chatHistory",
			success : function(data){
				var c = 0;
				var chatData = $.parseJSON(data);
				var m = chatData.length;
				chatData.forEach(function(product){
					var messageString = "<span style=\'margin-left:2px;\'>"+product.user + "</span> : " + product.message;
					$("#chat").html($("#chat").html()+messageString+"<br />");
					c++;
					if(c<m){
						nextMessage = ++product.id;
					}
				});
				awaitNextMessage();
			}
		});
	});
		
	function awaitNextMessage(){
		$.ajax({
			url : "?p=nextMessage&id="+nextMessage,
			success : function(data){
				var product = $.parseJSON(data);
				if(product.ack == "conf"){
					var messageString = "<span style=\'margin-left:2px;\'>"+product.user + "</span> : " + product.message;
					$("#chat").html($("#chat").html()+messageString+"<br />");
					nextMessage = product.id;
				}
				window.setTimeout(function(){
					awaitNextMessage();
				},500);
			}
		});
	}
	
	$("#send").click(function(){
		var message = $("#message").val();
		message = message.replace(/"/g,"&#34;");
		message = message.replace(/\'/g,"&#39;");
		message = message.replace(/<(\/?)script.*/g,"I tried to use a script tag! Lol!!");
		message = message.replace(/[^a-zA-Z 0-9()!$£*\\/\-&#;,<>=\.\_:]+/g,"");
		$.ajax({
			url : "?p=sendMessage&message="+encodeURIComponent(message)
		});
		$("#message").val("").focus();
	});
	</script>
	';
	echo $e;
}

if($p == "chatHistory"){
	echo json_encode(omp_message_history($_SESSION['user'],$_SESSION['token']));
}

if($p == "nextMessage"){
	$id = $_GET['id'];
	++$id;
	echo json_encode(omp_get_next_message($_SESSION['user'],$_SESSION['token'],$id));
}

if($p == "sendMessage"){
	$message = urldecode($_GET['message']);
	echo json_encode(omp_send_message($_SESSION['user'],$_SESSION['token'],$message));
}
