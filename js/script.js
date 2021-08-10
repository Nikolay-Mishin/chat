function message(text) {
	$('#chat-result').append(text);
}

function send(action, message = {}) {
	message = Object.assign({ action: action }, message);
	console.log('send \n', message);
	send.socket.send(JSON.stringify(message));
}

console.log(Config);
const { PROTOCOL, PROTOCOL_SHORT, HOST, PORT, IP_LISTEN, SERVER } = Config;

$(document).ready(function($) {
	//let server = "ws://chat:8090/WebForMyself/server.php",
	let server = `${PROTOCOL_SHORT}://${HOST}:${PORT}/${SERVER}`,
		socket = new WebSocket(server);

	send.socket = socket;

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
		let data = JSON.parse(event.data);
		message("<div>" + data.action + " - " + data.message + "</div>");
		console.log(data);
		if (data.action == 'Ping') {
			//send('Pong');
		}
	}

	$("#chat").on('submit', function() {
		let message = {
			chat_message: $("#chat-message").val(),
			chat_user: $("#chat-user").val()
		};

		$("#chat-user").attr("type", "hidden");

		//socket.send(JSON.stringify(message));
		send(message);

		return false;
	});
});
