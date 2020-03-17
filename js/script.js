function message(text) {
	$('#chat-result').append(text);
}

console.log(Config);
const { PORT, HOST, IP_LISTEN } = Config;
console.log(PORT, HOST, IP_LISTEN);

$(document).ready(function($) {
	var server = "ws://chat:8090/WebForMyself/server.php",
		socket = new WebSocket(server);

	socket.onopen = function() {
		message("<div>Соединение установлено</div>");
	};

	socket.onerror = function(error) {
		message("<div>Ошибка при соединении" + (error.message ? error.message : "") + "</div>");
	}

	socket.onclose = function() {
		message("<div>Соединение закрыто</div>");
	}

	socket.onmessage = function(event) {
		var data = JSON.parse(event.data);
		message("<div>" + data.type + " - " + data.message + "</div>");
	}

	$("#chat").on('submit',function() {
		var message = {
			chat_message:$("#chat-message").val(),
			chat_user:$("#chat-user").val(),
		};

		$("#chat-user").attr("type","hidden");

		socket.send(JSON.stringify(message));

		return false;
	});
});
