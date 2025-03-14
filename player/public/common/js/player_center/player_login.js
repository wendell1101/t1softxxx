var PlayerLogin = {
	$form: '',

	init: function(){
		var self = this;
		self.$form = $('#frm_login');
		self.rules();
	},

	rules: function(){
		var self = this;
		self.$form.validate({
			rules: {
				username: 'required',
				password: 'required'
			},
			onkeyup: false,
			focusInvalid: true,
			errorElement: 'span',
			errorPlacement: function(error, element) {
				// $(error).insertAfter(element);
			},
			highlight: function(element, errorClass) {
				$(element).addClass('error-class');
			},
			unhighlight: function(element, errorClass) {
				$(element).removeClass('error-class');
			},
			submitHandler: function(form) {
				form.submit();
			},
			invalidHandler: function(e, validator) {
				var username = $('#username').val().trim(), password = $('#password').val().trim();
				if (username.length == 0) {
					$('.error-message').addClass('hide');
					$('#username_empty').removeClass('hide');
				} else {
					$('#username_empty').addClass('hide');
				}
				if (password.length == 0) {
					$('.error-message').addClass('hide');
					$('#password_empty').removeClass('hide');
				} else {
					$('#password_empty').addClass('hide');
				}
			}
		});

	}

}