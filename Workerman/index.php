<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<title>Server</title>
</head>

<body>
	<? require_once __DIR__ . '/../config/config.php'; ?>
	<? session_start(); ?>

	<div id="chat-action">
		<input type="button" id="chat-start" value="start" >
		<input type="button" id="chat-stop" value="stop" >
	</div>
	<div id="result"></div>

	<script src="http://code.jquery.com/jquery-1.9.1.js"></script>
	<script src="js/chat.js"></script>
</body>
</html>
