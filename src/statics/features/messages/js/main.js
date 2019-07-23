$(document).ready(function (e) {

	let messenger = new MessagesComponent(1, 15);
	$('.ui.dropdown').dropdown();

	messenger.loadMessages({
		onPreRequest: (form) => {
			let test = $(document.createElement('input'));
			test.attr('name', 'test');
			test.attr('value', '123456');
			form.append(test);
		},
		timeReload: 5000,
	});

	let refreshButton = $('[refresh-messages]');
	refreshButton.on('click', () => {
		messenger.loadMessages();
	});

})
