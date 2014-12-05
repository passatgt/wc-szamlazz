jQuery(document).ready(function($) {
    $('#wc_szamlazz_generate').click(function(e) {
        e.preventDefault();
        var nonce = $(this).data('nonce');
        var order = $(this).data('order');
        var button = $('#wc-szamlazz-generate-button');
        
        var data = {
            action: 'wc_szamlazz_generate_invoice',
            nonce: nonce,
            order: order
        };

		button.block({message: null, overlayCSS: {background: '#fff url(' + wc_szamlazz_params.loading + ') no-repeat center', backgroundSize: '16px 16px', opacity: 0.6}});

        $.post( ajaxurl, data, function( response ) {
			//Remove old messages
			$('.wc-szamlazz-message').remove();

			var responseText = response;	

			//Generate the error/success messages
			if(responseText.data.error) {
				button.before('<div class="wc-szamlazz-error error wc-szamlazz-message"></div>');
			} else {
				button.before('<div class="wc-szamlazz-success updated wc-szamlazz-message"></div>');
			}

			//Get the error messages
			var ul = $('<ul>');
			$.each(responseText.data.messages, function(i,value){
				var li = $('<li>')
				li.append(value);           
				ul.append(li);
			});
			$('.wc-szamlazz-message').append(ul);

			//If success, hide the button
			if(!responseText.data.error) {
				button.slideUp();
				button.before(responseText.data.link);
			}
			
			button.unblock();


        });
    });
});


