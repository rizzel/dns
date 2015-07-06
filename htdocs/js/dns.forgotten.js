window.initPageSpecific = function ()
{
	var $p = $('#forgotten2_password1, #forgotten2_password2');
	var $p1 = $('#forgotten2_password1');
	var $p2 = $('#forgotten2_password2');
	var $name = $('#forgotten_name');
	var $email = $('#forgotten_email');
	var $token = $('#forgotten2_token');

	dns.user = {
		requestToken: function (name, email) {
			dns.loadRemote.loadRemote('user/forgottenRequest',
				[name, email],
				function ()
				{
					$('#forgotten2').show();
				}
			);
		},
		verifyToken: function (token, password) {
			if (typeof(dns.user.name) == 'undefined')
			{
				alert("Bitte erneut versuchen");
				return;
			}
			dns.loadRemote.loadRemote('user/forgottenResponse',
				[dns.user.name, token, password],
				function (data, success)
				{
					if (success)
					{
						alert("Passwort wurde gesetzt");
						window.location = '/index.php';
					}
					else
					{
						alert("Fehler beim Setzen des Passwortes");
						window.location.reload(false);
					}
				}
			);
		}
	};

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

	$('#forgotten2_submit').on('click', function () {
		var ok = true;
		if ($token.val().length < 3)
		{
			dns.fehler($token);
			ok = false;
		}
		if ($p1.val() != $p2.val())
		{
			dns.fehler($p1);
			dns.fehler($p2);
			ok = false;
		}
		if (ok)
		{
			if (typeof(dns.user.name) == 'undefined' && $name.val().length > 0)
				dns.user.name = $name.val();
			dns.user.verifyToken($token.val(), $p1.val());
		}
	});

	$('#forgotten_submit').on('click', function () {
		var ok = true;
		if ($name.val().length < 1)
		{
			dns.fehler($name);
			ok = false;
		}
		if (!$email.val().match(/@/) || $email.val().length <= 3)
		{
			dns.fehler($email);
			ok = false;
		}
		if (ok)
		{
			dns.user.name = $name.val();
			dns.user.requestToken($name.val(), $email.val());
		}
	});
};
