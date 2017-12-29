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
			@import url('https://fonts.googleapis.com/css?family=Poppins:300,400');
			img{
				width:100%;
			}
			td{
				font-family:'Poppins',sans-serif;
				font-weight:300;
			}
			table tr:nth-child(even) {
				background: #dddddd;
			}
			body{
				background-color:#F4F4F4;
			}
		</style>
	</head>";
	$e.='<div id="container" style="background-color:#F4F4F4;">
	<div id="chatBox" style="border:2px solid black; max-height:90%; height:90%; background-color:white; overflow-y:scroll;">
	<table id="chat" style="width:100%;">
	</table>
	</div>';
	$e.='<center><div id="tools">
	<br />
	<input type="text" id="message" placeholder="Message" style="padding:9px; color:#6d6d6d; font-wight:bold; font-size:14px;">
	<input type="button" id="send" value="Send" style="padding:10px; border:0px; color:white; background-color:#F14B25; font-weight:bold; font-size:14px;"/>
	<input type="button" id="fs" value="FS" style="padding:10px; border:0px; color:white; background-color:#26a2ef; font-weight:bold; font-size:14px;"/>
	<input type="button" id="efs" value="FS" style="padding:10px; border:0px; color:white; background-color:#26a2ef; font-weight:bold; font-size:14px; display:none;"/>
	</div></center>
	</div>';
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
				var height = $("#chat").height();
				$("#chatBox").animate({ scrollTop: height }, 0);
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
					var height = $("#chat").height();
					$("#chatBox").animate({ scrollTop: height }, 1000);
					nextMessage = product.id;
				}
				if(product.ack == "tinv"){
					alert("Session token expired - you need to log back in");
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
	
	function enterFS(element) {
	if(element.requestFullscreen)
		element.requestFullscreen();
	else if(element.mozRequestFullScreen)
		element.mozRequestFullScreen();
	else if(element.webkitRequestFullscreen)
		element.webkitRequestFullscreen();
	else if(element.msRequestFullscreen)
		element.msRequestFullscreen();
	}
	
	function exitFullscreen() {
	if(document.exitFullscreen) {
		document.exitFullscreen();
	} else if(document.mozCancelFullScreen) {
		document.mozCancelFullScreen();
	} else if(document.webkitExitFullscreen) {
		document.webkitExitFullscreen();
		}
	}
	
	$("#fs").click(function(){
		enterFS(document.querySelector("#container"));
		$("#efs").css("display","inline");
		$("#fs").css("display","none");
	});
	
	$("#efs").click(function(){
		exitFullscreen();
		$("#efs").css("display","none");
		$("#fs").css("display","inline");
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
