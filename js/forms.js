(function($) {

	$('.hc-form .datepicker').pikaday({
		firstDay: 1,
		minDate: new Date(),
		format: 'DD-MM-YYYY',
		formatStrict: true,
		i18n: {
			previousMonth: '',
			nextMonth: '',
			months: ['January','February','March','April','May','June','July','August','September','October','November','December'],
			weekdays: ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'],
			weekdaysShort: ['S','M','T','W','T','F','S']
		}
	});

})( window.jQuery );
