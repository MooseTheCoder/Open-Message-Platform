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
		$auth = omp_user_auth(strval($_POST['username']),sha1(strval($_POST['password'])));
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
		<meta name='viewport' content='width=device-width, initial-scale=1' />
		<script src='https://cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js'></script>
		<style>
			table tr:nth-child(even) {
				background: #dddddd;
			}
			body{
				background-color:#F4F4F4;
			}
		</style>
	</head>";
	$e.='<div id="chatBox" style="border:2px solid black; max-height:90%; height:90%; background-color:white; overflow-y:scroll;">
	<table id="chat" style="width:100%;">
	</table>
	</div>';
	$e.='<center><div id="tools">
	<br />
	<input type="text" id="message" placeholder="Message" style="padding:9px; color:#B2B3B8;">
	<input type="button" id="send" value="Send!" style="padding:10px; border:0px; color:white; background-color:#F14B25;"/>
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
					$("#chat").html($("#chat").html()+"<tr><td>"+messageString+"</td></tr>");
					c++;
					if(c<m){
						nextMessage = ++product.id;
					}
				});
				$("#chat").animate({ scrollTop: $("#chat").scrollHeight}, 50);
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
					$("#chat").html($("#chat").html()+"<tr><td>"+messageString+"</td></tr>");
					nextMessage = product.id;
					$("#chat").animate({ scrollTop: $("#chat").scrollHeight}, 50);
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
		message = message.replace(/[^\^a-zA-Z 0-9()!$£*\\/\-&#;,<>=\.\_:]+/g,"");
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
