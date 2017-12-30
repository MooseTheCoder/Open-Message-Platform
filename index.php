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
	$name = omp_name();
	$e.='
	<head>
		<meta name="viewport" content="width=device-width, initial-scale=1" />
	</head>
	<center>
	<h1>'.$name.'</h1>
	<form method="post" action="?p=login">
		<input type="text" name="username" placeholder="Username" style="padding:9px; color:#6d6d6d; font-wight:bold; font-size:14px; margin:2px; "><br />
		<input type="password" name="password" placeholder="Password" style="padding:9px; color:#6d6d6d; font-wight:bold; font-size:14px; margin:2px;"><br />
		<input type="submit" name="login" value="Login" style="padding:10px; border:0px; color:white; background-color:#F14B25; font-weight:bold; font-size:14px;"/>
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
		<script src='//twemoji.maxcdn.com/2/twemoji.min.js?2.4'></script>
		<style>
			@import url('https://fonts.googleapis.com/css?family=Poppins:300,400');
			img{
				width:100%;
				max-width:950px;
			}
			td{
				font-family:'Poppins',sans-serif;
				font-weight:300;
			}
			table tr:nth-child(even) {
				background: #dddddd;
			}
			.emoji{
				width:16px !important;
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
	<input type="button" id="im" value="I" style="padding:10px; border:0px; color:white; background-color:#ed9625; font-weight:bold; font-size:14px;"/>
	<input type="text" id="message" placeholder="Message" style="padding:9px; color:#6d6d6d; font-wight:bold; font-size:14px;">
	<input type="button" id="send" value="Send" style="padding:10px; border:0px; color:white; background-color:#F14B25; font-weight:bold; font-size:14px;"/>
	<input type="button" id="fs" value="FS" style="padding:10px; border:0px; color:white; background-color:#26a2ef; font-weight:bold; font-size:14px;"/>
	<input type="button" id="efs" value="FS" style="padding:10px; border:0px; color:white; background-color:#26a2ef; font-weight:bold; font-size:14px; display:none;"/>
	<input type="checkbox" id="scroll" />Scroll
	</div></center>
	</div>';
	$e.='
	<script>
	var nextMessage = 1;
	var currentMessageValue = "";
	var windowFocus = true;
	$(window).blur(function(){
		windowFocus = false;
	});
    $(window).focus(function(){
		windowFocus = true;
    });
	$(document).ready(function(){
		$.ajax({
			url : "?p=chatRecent",
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
	function notify(title,message){
		if (Notification.permission != "granted"){
			Notification.requestPermission();
		}else{
			var sysNote = new Notification(title, {
				body: message,
			});
		}
	}
	function awaitNextMessage(){
		$.ajax({
			url : "?p=nextMessage&id="+nextMessage,
			success : function(data){
				var product = $.parseJSON(data);
				if(product.ack == "conf"){
					var messageString = "<span style=\'margin-left:2px;\'>"+product.user + "</span> : " + product.message;
					$("#chat").html($("#chat").html()+"<tr><td>"+messageString+"</td></tr>");
					if($("#scroll").prop("checked")){
						var height = $("#chat").height();
						$("#chatBox").animate({ scrollTop: height }, 1000);
					}
					if(product.message != currentMessageValue){
						var notification = new Audio("notif.mp3");
						notification.play();
						if(windowFocus == false){
							notify(product.user,product.message);
						}

					}
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
		message = twemoji.parse(message);
		message = message.replace(/\'/g,"&#39;");
		message = message.replace(/%20/g," ");
		message = message.replace(/<(\/?)script.*/g,"I tried to use a script tag! Lol!!");
		message = message.replace(/[^\^a-zA-Z 0-9()!$£*\\/\-&#;,<>=\.\_:"%]+/g,"");
		currentMessageValue = message;
		$.ajax({
			url : "?p=sendMessage&message="+encodeURIComponent(message)
		});
		$("#message").val("").focus();
	});
	
	$("#im").click(function(){
		$("#message").val($("#message").val()+"<img src=\"\">");
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

if($p == "chatRecent"){
	echo json_encode(omp_recent_message_history($_SESSION['user'],$_SESSION['token']));
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
