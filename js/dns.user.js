initPageSpecific = function ()
{
	var p = $('#password1, #password2');
	var p1 = $('#password1');
	var p2 = $('#password2');

	p.on('keyup', function () {
		if (p1.val() != p2.val())
		{
			$('#user_hinzu_nomatch').show();
		}
		else
		{
			$('#user_hinzu_nomatch').hide();
		}
	});

	$('#password_submit').on('click', function () {
		var ok = true;
		if (p1.val() != p2.val())
		{
			dns.fehler(p1);
			dns.fehler(p2);
			ok = false;
		}
		if (!ok)
			return;
		dns.userUpdatePassword(
			p1.val()
		);
	});

	dns.userUpdatePassword = function (password) {
		dns.loadRemote.loadRemote('user/update',
			[null, null, password, null],
			undefined,
			{
				insertInDiv: $('#loadProgresses')
			}
		);
	}
};
