function message(text) {
    $('#chat-result').append(text);
}

$(document).ready(function($) {
    var socket = new WebSocket("ws://localhost:8090/chat/server.php");

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
});
