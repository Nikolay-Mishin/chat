<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<title>Chat</title>
	<link rel="stylesheet" href="css/style.css" />
</head>

<body>
	<div id="chat-action">
		<input type="button" id="chat-start" value="start" >
		<input type="button" id="chat-stop" value="stop" >
	</div>

	<form id="chat" action="">
		<div class="chat-result" id="chat-result">
			<input type="text" name="chat-user" id="chat-user" placeholder="Name">
			<input type="text" name="chat-message" id="chat-message"  placeholder="Message">
			<input type="submit" value="Send" >
		</div>
	</form>

	<div id="result"></div>

	<? require_once __DIR__ . '/config/config.php'; ?>
	<? session_start(); ?>
	<?= $Config; ?>
	<script src="http://code.jquery.com/jquery-1.9.1.js"></script>
	<script src="js/chat.js"></script>
</body>
</html>
