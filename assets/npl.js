document.addEventListener( 'DOMContentLoaded', function () {
	var detail = document.getElementById( 'npl_pay_detail' );
	if ( ! detail ) {
		return;
	}
	var hints = {
		esewa: 'Your eSewa ID (mobile number or eSewa ID)',
		khalti: 'Your Khalti ID (mobile number)',
		bank: 'Your bank account number (with bank name)'
	};
	document.querySelectorAll( 'input[name="npl_pay_method"]' ).forEach( function ( radio ) {
		radio.addEventListener( 'change', function () {
			if ( hints[ radio.value ] ) {
				detail.placeholder = hints[ radio.value ];
			}
		} );
	} );
} );
