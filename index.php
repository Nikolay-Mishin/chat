<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<title>Chat</title>
	<link rel="stylesheet" href="css/style.css" />
</head>

<body>
	<form id="chat" action="">
		<div class="chat-result" id="chat-result">
			<input type="text" name="chat-user" id="chat-user" placeholder="Name">
			<input type="text" name="chat-message" id="chat-message"  placeholder="Message">
			<input type="submit" value="Send" >
		</div>
	</form>

	<? require_once __DIR__ . '/config/config.php'; ?>

	<script>
		const Config = <?= json_encode(get_defined_constants(true)['user']); ?>;
		Object.freeze(Config); // замораживает объект
	</script>

	<script src="http://code.jquery.com/jquery-1.9.1.js"></script>
	<script src="js/script.js"></script>
</body>
</html>
