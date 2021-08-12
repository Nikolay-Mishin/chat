$(document).ready(function ($) {
	$("#chat-action").on('click', function (event) {
		console.log(event.target);
		console.log(event.target.value);
		ajax('action.php', event.target);
		return false;
	});

	function ajax(url, target) { // вешаем свой обработчик на функцию success
		$.ajax({
			type: "POST",
			url: url, // указываем URL
			data: { action: target.value},
			success: function(data, textStatus) { // вешаем свой обработчик на функцию success
				console.log(data);
				$("#result").html(`${target.value}<br>${data}`);
			}
		})
	}
});
