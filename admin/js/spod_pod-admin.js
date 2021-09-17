(function( $ ) {
	'use strict';

	$(window).load(function () {
		// form spod key submit
		$('#spod-api-key-form-register').on('submit', function (e) {
			jQuery('.box__form .notice-error').addClass('hidden');
			jQuery('.box__form .notice-api-success').addClass('hidden');
			jQuery('.box__form .notice-key-success').addClass('hidden');
			jQuery('.box__form .notice-syncing').addClass('hidden');
			jQuery('.box__form .notice-loading').removeClass('hidden');
			jQuery('.box__form input[type="text"]').removeClass('hidden');
			jQuery('#import_product_spod').attr('disabled', 'disabled');
			var import_product = jQuery("#import_product_spod:checked").val();


			var check_data = false;
			jQuery.when(
				connectPlugin($('#spod-api-key').val(),import_product)
				).done(function(returndata, textStatus, XMLHttpRequest) {
					if (returndata.notice == 'error') {
						jQuery('.box__form .notice-error').removeClass('hidden');
						jQuery('.box__form input[type="text"]').addClass('invalid');
					}
					if (returndata.notice == 'success') {
						jQuery('.box__form .notice-key-success').removeClass('hidden');
						jQuery('.box__form .notice-syncing').removeClass('hidden');
						jQuery('.box__form input[type="text"]').removeClass('invalid');

						//var return_data = returndata;
						jQuery('.box__form').addClass('is-connected');
						jQuery('.box__form .notice-syncing').addClass('hidden');
						jQuery('.box__form #spod-api-key').attr('disabled', 'disabled');
						jQuery('.box__form #spod-api-key-form-register .input-submit').addClass('hidden');
						jQuery('.box__form .notice-key-success').addClass('hidden');
						jQuery('.box__form .notice-api-success').removeClass('hidden');
						//jQuery('.box__form .notice-complete').removeClass('hidden');
						jQuery('.box__form.disconnect').removeClass('hidden');
						jQuery('.box__form .api-date').removeClass('hidden');
						jQuery('.box__form .api-date span').html(returndata.connected_date);
					}
					

					jQuery('.box__form .notice-loading').addClass('hidden')
				});

			e.preventDefault();
			return false;
		});


		// disconnect
		$('#spod-api-key-form-disconnect').on('submit', function (e) {
			$('#wpbody-content .modal').addClass('active');
			$('#wpbody-content .modal.success').removeClass('active');
			e.preventDefault();
			return false;
		});

		// modal close
		$('#wpbody-content .modal .closer').on('click', function (e) {
			$('#wpbody-content .modal').removeClass('active');

			//if($(this).parents('.modal').hasClass('success')) {
			//	window.location = window.location.href+'&eraseCache='+Date.now();
			//}
			//e.preventDefault();
			//return false;
		});

		// disconnector click and follow up actions
		$('#wpbody-content .modal .disconnecter').on('click', function (e) {
			jQuery.when(
				disconnectPlugin()
			).done(
				$('#wpbody-content .modal').removeClass('active'),
				$('#wpbody-content .modal.success').addClass('active')
			);
			e.preventDefault();
			return false;
		});
	});

	

})( jQuery );

function disconnectPlugin() {
	return jQuery.ajax({
		type: 'POST',
		url: ng_spod_pod_unique.ajaxurl,
		data: {
			action: 'serversidefunction',
			method: 'disconnect'
		},
		dataType: 'json',
		success: function (returndata, textStatus, XMLHttpRequest) {},
		error: function (XMLHttpRequest, textStatus, errorThrown) {}
	});
}

function connectPlugin(token,import_product) {
	return jQuery.ajax({
		type: 'POST',
		url: ng_spod_pod_unique.ajaxurl,
		data: {
			action: 'serversidefunction',
			method: 'connect',
			import: import_product,
			token: token,
		},
		dataType: 'json',
		success: function (returndata, textStatus, XMLHttpRequest) {console.log(returndata)},
		error: function (returndata, textStatus, errorThrown) {}
	});
}



