jQuery(document).ready(function ($) {

	// Dismiss admin review notice ajax call
	$(".fdbgp_dismiss_notice").on("click", function (event) {
		var $this = $(this);
		var wrapper = $this.parents(".fdbgp-review-notice-wrapper");
		var ajaxURL = wrapper.data("ajax-url");
		var ajaxCallback = wrapper.data("ajax-callback");
		$.post(ajaxURL, { "action": ajaxCallback }, function (data) {
			wrapper.slideUp("fast");
		}, "json");
	});

})
