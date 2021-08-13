function message(text) {
	$('#chat-result').append(text);
}

function send(action, message = {}) {
	message = Object.assign({ action: action }, message);
	console.log('send \n', message);
	send.socket.send(JSON.stringify(message));
}

function ajax(url, target) {
	$.ajax({
		type: "POST",
		url: url, // указываем URL
		data: { action: target.value },
		success: function (data, status) { // вешаем свой обработчик на функцию success
			console.log(data);
			//data = JSON.parse(data);
			//console.log(data);
			$("#result").html(`${target.value}<br>${data}`);
		}
	})
}

console.log(Config);
const { PROTOCOL, PROTOCOL_SHORT, HOST, PORT, IP_LISTEN, SERVER, SERVER_ACTION } = Config;

$(document).ready(function ($) {
	//let server = "ws://chat:8090/WebForMyself/server.php",
	//let server = `${PROTOCOL_SHORT}://${HOST}:${PORT}/${SERVER}`,
	let server = `${PROTOCOL_SHORT}://${HOST}:${PORT}`,
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
			send('Pong');
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

	$("#chat-action").on('click', function(event) {
		console.log(event.target);
		console.log(event.target.value);
		ajax(SERVER_ACTION, event.target);
	});
});
