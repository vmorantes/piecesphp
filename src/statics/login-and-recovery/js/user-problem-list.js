$(document).ready(function (e) {
	$("#modalNoUserButton").on('click', function (e) {
		e.preventDefault();
		$("#modalNoUser").removeClass("modal-no-user");
	})

	$("#cardModal").on('click', function (e) {
		e.stopPropagation();
	})

	$("#backgrodund-modal").on('click', function () {
		$("#modalNoUser").addClass("modal-no-user");
	})
})
