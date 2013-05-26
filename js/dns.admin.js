initPageSpecific = function ()
{
	var ps = $('#user_hinzu_password1, #user_hinzu_password2');
	var p1 = $('#user_hinzu_password1');
	var p2 = $('#user_hinzu_password2');
	var pdef = $('#user_hinzu_password_default');

	dns.admin = {
		'users': {
			'add': function (name, password, level) {
				dns.loadRemote.loadRemote('user/add',
					[name, password, level, 'unused'],
					function (data, success) {
						dns.admin.users.list();
						if (success)
							$('#user_hinzu_button').trigger('click');
					},
					{
						insertInDiv: $('#loadProgresses')
					}
				);
			},
			'list': function () {
				dns.loadRemote.loadRemote('user/get',
					[],
					function (data, success) {
						if (!success)
							return;
						$('#users tr:not(:first)').detach();
						var $table = $('#users');
						for (var i in data.data)
						{
							var records = [];
							for (var ri in data.data[i].records)
							{
								var r = data.data[i].records[ri];
								records.push("%s: %s (%s)".format(
									r.type,
									r.name,
									r.domain_name
								));
							}
							$('#users').append('<tr uid="%s" level="%s"> \
									<td>%s</td> \
									<td>%s</td> \
									<td>%s</td> \
									<td> \
										<a href="#" class="userListDel">Entf.</a> \
										<a href="#" class="userListLevel">Level</a> \
									</td> \
								</tr>'.format(
								data.data[i].username,
								data.data[i].level,
								data.data[i].username,
								data.data[i].level,
								records.join('<br />')
							));
						}

						$table.find('.userListDel').on('click', function () {
							var u = $(this).parents('tr').attr('uid');
							if (confirm("User %s wirklich loeschen?".format(u)))
								dns.admin.users.del(u);
							return false;
						});

						$table.find('.userListLevel').on('click', function () {
							var $this = $(this);
							var pos = $this.offset();
							var u = $this.parents('tr').attr('uid');
							$('#userListLevel').val($this.parents('tr').attr('level'));
							$('#userListLevelPopup')
								.attr('uid', u)
								.css('left', pos.left - 60)
								.css('top', pos.top + 20)
								.show();
							return false;
						});
					},
					{
						insertInDiv: $('#loadProgresses')
					}
				);
			},
			'del': function (uid) {
				dns.loadRemote.loadRemote('user/delete',
					[uid],
					function (data, success) {
						dns.admin.users.list();
					},
					{
						insertInDiv: $('#loadProgresses')
					}
				);
			},
			'changeLevel': function (uid, level) {
				dns.loadRemote.loadRemote('user/update',
					[
						uid, null, null, level
					],
					function (data, success) {
						if (success)
							$('#userListLevelPopup').hide();
						dns.admin.users.list();
					},
					{
						insertInDiv: $('#loadProgresses')
					}
				);
			}
		},

		'domains': {
			'add': function (name, type, soa, mx) {
				dns.loadRemote.loadRemote('domains/add',
					[name, type, soa, mx],
					function (data, success) {
						dns.admin.domains.list();
						if (success)
							$('#domain_hinzu_button').trigger('click');
					},
					{
						insertInDiv: $('#loadProgresses')
					}
				);
			},
			'list': function () {
				dns.loadRemote.loadRemote('domains/get',
					[],
					function (data, success) {
						$('#domains tr:not(:first)').detach();
						var $table = $('#domains');
						for (var i in data.data)
						{
							$('#domains').append('<tr did="%d" dname="%s" dsoa="%s" dmx="%s"> \
									<td>%d</td> \
									<td>%s</td> \
									<td>%s</td> \
									<td>%d</td> \
									<td>%s</td> \
									<td>%s</td> \
									<td> \
										<a href="#" class="domainListDel">Entf.</a> \
										<a href="#" class="domainListName">Name</a> \
										<a href="#" class="domainListSOA">SOA</a> \
										<a href="#" class="domainListMX">MX</a> \
									</td> \
								</tr>'.format(
								data.data[i].id,
								data.data[i].name,
								data.data[i].soa === null ? '' : data.data[i].soa,
								data.data[i].mx === null ? '' : data.data[i].mx,
								data.data[i].id,
								data.data[i].name,
								data.data[i].type,
								data.data[i].last_check,
								data.data[i].soa === null ? 'Keiner' : data.data[i].soa,
								data.data[i].mx === null ? 'Keiner' : data.data[i].mx
							));
						}

						$table.find('.domainListDel').on('click', function () {
							var did = $(this).parents('tr').attr('did');
							var dname = $(this).parents('tr').attr('dname');
							if (confirm("Domain %s wirklich loeschen?".format(dname)))
								dns.admin.domains.del(did);
							return false;
						});
						$table.find('.domainListName').on('click', function () {
							var $this = $(this);
							var pos = $this.offset();
							var d = $this.parents('tr').attr('did');
							$('#domainsListName').val($this.parents('tr').attr('dname'));
							$('#domainsListNamePopup')
								.attr('did', d)
								.css('left', pos.left - 60)
								.css('top', pos.top + 20)
								.show();
							return false;
						});
						$table.find('.domainListSOA').on('click', function () {
							var $this = $(this);
							var pos = $this.offset();
							var d = $this.parents('tr').attr('did');
							$('#domainsListSOA').val($this.parents('tr').attr('soa'));
							$('#domainsListSOAPopup')
								.attr('did', d)
								.css('left', pos.left - 60)
								.css('top', pos.top + 20)
								.show();
							return false;
						});
						$table.find('.domainListMX').on('click', function () {
							var $this = $(this);
							var pos = $this.offset();
							var d = $this.parents('tr').attr('did');
							$('#domainsListMX').val($this.parents('tr').attr('mx'));
							$('#domainsListMXPopup')
								.attr('did', d)
								.css('left', pos.left - 60)
								.css('top', pos.top + 20)
								.show();
							return false;
						});
					},
					{
						insertInDiv: $('#loadProgresses')
					}
				);
			},
			'del': function (did) {
				dns.loadRemote.loadRemote('domains/delete',
					[did],
					function (data, success) {
						dns.admin.domains.list();
					},
					{
						insertInDiv: $('#loadProgresses')
					}
				);
			},
			'updateName': function (did, name) {
				dns.loadRemote.loadRemote('domains/updateName',
					[did, name],
					function (data, success) {
						if (success)
							$('#domainsListNamePopup').hide();
						dns.admin.domains.list();
					},
					{insertInDiv: $('#loadProgresses')}
				);
			},
			'updateSOA': function (did, soa) {
				dns.loadRemote.loadRemote('domains/updateSOA',
					[did, soa],
					function (data, success) {
						if (success)
							$('#domainsListSOAPopup').hide();
						dns.admin.domains.list();
					},
					{insertInDiv: $('#loadProgresses')}
				);
			},
			'updateMX': function (did, mx) {
				dns.loadRemote.loadRemote('domains/updateMX',
					[did, mx],
					function (data, success) {
						if (success)
							$('#domainsListMXPopup').hide();
						dns.admin.domains.list();
					},
					{insertInDiv: $('#loadProgresses')}
				);
			}
		}
	};

	$('#user_hinzu_button').on('click', function () {
		var div = $('#user_hinzu');
		div.toggleClass('active');
		if (div.hasClass('active'))
		{
			$('#user_hinzu_username').val('');
			var r = dns.createRandomString(12);
			ps.val(r);
			pdef.text(r);
			$('#user_hinzu_default').show();
			$('#user_hinzu_level').val('user');
		}
	});

	ps.on('focus', function () {
		if (p1.val() == pdef.text())
			p1.val('');
		if (p2.val() == pdef.text())
			p2.val('');
		$('#user_hinzu_default').hide();
	}).on('blur', function () {
		if (p1.val().length == 0 && p2.val().length == 0)
		{
			ps.val(pdef.text());
			$('#user_hinzu_default').show();
			$('#user_hinzu_nomatch').hide();
		}
	}).on('keyup', function () {
		if (p1.val() != p2.val())
		{
			$('#user_hinzu_nomatch').show();
		}
		else
		{
			$('#user_hinzu_nomatch').hide();
		}
	});

	$('#user_hinzu_submit').on('click', function () {
		var name = $('#user_hinzu_username');
		var ok = true;
		if (name.val().length == 0)
		{
			dns.fehler(name);
			ok = false;
		}
		if (p1.val() != p2.val())
		{
			dns.fehler(p1);
			dns.fehler(p2);
			ok = false;
		}
		if (!ok)
			return;
		dns.admin.users.add(
			$('#user_hinzu_username').val(),
			p1.val(),
			$('#user_hinzu_level').val()
		);
	});

	$('#userListLevelSubmit').on('click', function () {
		var u = $('#userListLevelPopup').attr('uid');
		dns.admin.users.changeLevel(u, $('#userListLevel').val());
	});

	$('#userListReload').on('click', dns.admin.users.list);


	$('#domain_hinzu_button').on('click', function () {
		var div = $('#domain_hinzu');
		div.toggleClass('active');
		if (div.hasClass('active'))
		{
			$('#domain_hinzu_name').val('');
			$('#domain_hinzu_type').val('NATIVE');
		}
	});


	$('#domain_hinzu_submit').on('click', function () {
		var name = $('#domain_hinzu_name');
		var ok = true;
		if (name.val().length == 0)
		{
			dns.fehler(name);
			ok = false;
		}
		if (!ok)
			return;
		dns.admin.domains.add(
			$('#domain_hinzu_name').val(),
			$('#domain_hinzu_type').val(),
			$('#domain_hinzu_soa').val(),
			$('#domain_hinzu_mx').val()
		);
	});

	$('#domainListReload').on('click', dns.admin.domains.list);

	$('#domainsListNameSubmit').on('click', function () {
		var d = $('#domainsListNamePopup').attr('did');
		dns.admin.domains.updateName(d, $('#domainsListName').val());
	});

	$('#domainsListSOASubmit').on('click', function () {
		var d = $('#domainsListSOAPopup').attr('did');
		dns.admin.domains.updateSOA(d, $('#domainsListSOA').val());
	});

	$('#domainsListMXSubmit').on('click', function () {
		var d = $('#domainsListMXPopup').attr('did');
		dns.admin.domains.updateMX(d, $('#domainsListMX').val());
	});

	dns.admin.users.list();
	dns.admin.domains.list();
}
