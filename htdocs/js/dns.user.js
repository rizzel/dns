window.initPageSpecific = function ()
{
	var $p = $('#password1, #password2');
	var $p1 = $('#password1');
	var $p2 = $('#password2');
	var $email = $('#email');
	var $token = $('#token');

	dns.user = {
		updatePassword: function (password) {
			dns.loadRemote.loadRemote('user/updatePasswordSelf',
				[password],
				function (data, success)
				{
					if (success)
						alert("Bestätigungs-Email wurde versandt.");
				}
			);
		},
		updateEmail: function (email) {
			dns.loadRemote.loadRemote('user/updateEmailSelf',
				[email],
				function (data, success)
				{
					if (success)
						alert("Bestätigungs-Email wurde versandt.");
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
						alert("Token erfolgreich verifiziert");
						window.location.reload();
					}
					else
					{
						alert("Fehler bei Token-Verifizierung - Token ungültig");
					}
				}
			);
		}
	}

	$p.on('keyup', function () {
		if ($p1.val() != $p2.val())
		{
			$('#user_add_nomatch').show();
		}
		else
		{
			$('#user_add_nomatch').hide();
		}
	});

	$('#password_submit').on('click', function () {
		var ok = true;
		if ($p1.val() != $p2.val())
		{
			dns.fehler($p1);
			dns.fehler($p2);
			ok = false;
		}
		if (ok)
			dns.user.updatePassword($p1.val());
	});

	$('#email_submit').on('click', function () {
		var ok = true;
		if (!$email.val().match(/@/) || $email.val().length <= 3)
		{
			dns.fehler($email);
			ok = false;
		}
		if (ok)
			dns.user.updateEmail($email.val());
	});

	$('#token_submit').on('click', function () {
		var ok = true;
		if ($token.val().length == 0)
		{
			dns.fehler($token);
			ok = false;
		}
		if (ok)
			dns.user.verifyToken($token.val());
	});
};
