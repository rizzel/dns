window.initPageSpecific = function ()
{
	var p1 = $('#password1');
	var p2 = $('#password2');
	var email = $('#email');
	var token = $('#token');

	dns.user = {
		updatePassword: function (password) {
			dns.loadRemote.loadRemote('user/updatePasswordSelf',
				[password],
				function (data, success)
				{
					if (success)
						alert(i18n.pgettext('EmailVerification', "Verification email has been sent."));
				}
			);
		},
		updateEmail: function (email) {
			dns.loadRemote.loadRemote('user/updateEmailSelf',
				[email],
				function (data, success)
				{
					if (success)
						alert(i18n.pgettext('EmailVerification', "Verification email has been sent."));
				}
			);
		},
		verifyToken: function (token) {
			dns.loadRemote.loadRemote('user/verifyToken',
				[token],
				function (data, success)
				{
					if (success)
					{
						alert(i18n.pgettext('EmailVerification', "Token successfully verified."));
						window.location.reload();
					}
					else
					{
						alert(i18n.pgettext('EmailVerification', "Error verifying token - token invalid"));
					}
				}
			);
		}
	};

	[p1, p2].forEach(function (el) {
		el.addEventListener('keyup', function () {
			if (p1.value != p2.value)
			{
				$('#user_add_nomatch').style.display = '';
			}
			else
			{
				$('#user_add_nomatch').style.display = 'none';
			}
		});
	});

	$('#password_submit').addEventListener('click', function () {
		var ok = true;
		if (p1.value != p2.value)
		{
			dns.fehler(p1);
			dns.fehler(p2);
			ok = false;
		}
		if (ok)
			dns.user.updatePassword(p1.value);
	});

	$('#email_submit').addEventListener('click', function () {
		var ok = true;
		if (!email.value.match(/@/) || email.value.length <= 3)
		{
			dns.fehler(email);
			ok = false;
		}
		if (ok)
			dns.user.updateEmail(email.value);
	});

	$('#token_submit').addEventListener('click', function () {
		var ok = true;
		if (token.value.length == 0)
		{
			dns.fehler(token);
			ok = false;
		}
		if (ok)
			dns.user.verifyToken(token.value);
	});
};
