window.initPageSpecific = function () {
    var $ps = $('#user_add_password1, #user_add_password2');
    var $p1 = $('#user_add_password1');
    var $p2 = $('#user_add_password2');
    var $pDef = $('#user_add_password_default');

    dns.admin = {
        'users': {
            'add': function (name, password, level, email) {
                dns.loadRemote.loadRemote('user/add',
                    [name, password, level, email],
                    function (data, success) {
                        dns.admin.users.list();
                        if (success)
                            $('#user_add_button').trigger('click');
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
                        var $table = $('#users');
                        $table.find('tr:not(:first)').detach();
                        for (var i in data.data) {
                            if (!data.data.hasOwnProperty(i)) continue;
                            var records = [];
                            for (var ri in data.data[i].records) {
                                if (!data.data[i].records.hasOwnProperty(ri)) continue;
                                var r = data.data[i].records[ri];
                                records.push("%s: %s (%s)".format(
                                    r.type,
                                    r.name,
                                    r.domain_name
                                ));
                            }
                            $table.append('<tr data-uid="%s" data-level="%s"> \
									<td>%s</td> \
									<td>%s</td> \
									<td>%s</td> \
									<td> \
										<span class="userListZeigen">%s</span> \
										<span class="userListZeigen" style="display: none">%s</span> \
									</td> \
									<td> \
										<a href="#" class="userListLevel">%s</a> \
										<a href="#" class="userListDel">%s</a> \
									</td> \
								</tr>'.format(
                                data.data[i].username,
                                data.data[i].level,
                                data.data[i].username,
                                data.data[i].level,
                                data.data[i].email.length > 0 ? '<a href="mailto:%s">%s</a>'.format(
                                    data.data[i].email, data.data[i].email) : i18n.pgettext('MailToUnknown', 'unknown'),
                                records.length,
                                records.join('<br />'),
                                i18n.pgettext('UserList', 'Level'),
                                i18n.pgettext('UserList', 'Remove')
                            ));
                        }

                        $table.find('.userListDel').on('click', function () {
                            var u = $(this).parents('tr').attr('data-uid');
                            if (confirm(i18n.pgettext("Really delete user %s?").format(u)))
                                dns.admin.users.del(u);
                            return false;
                        });

                        $table.find('.userListLevel').on('click', function () {
                            $('.popup').not(this).hide();
                            var $this = $(this);
                            var pos = $this.offset();
                            var u = $this.parents('tr').attr('data-uid');
                            $('#userListLevel').val($this.parents('tr').attr('data-level'));
                            $('#userListLevelPopup')
                                .attr('data-uid', u)
                                .css('left', pos.left - 60)
                                .css('top', pos.top + 20)
                                .show();
                            return false;
                        });

                        $table.find('.userListZeigen').on('click', function () {
                            $(this).parent().find('.userListZeigen').toggle();
                        });
                    },
                    {
                        insertInDiv: $('#loadProgresses')
                    }
                );
            },
            'del': function (userName) {
                dns.loadRemote.loadRemote('user/delete',
                    [userName],
                    function () {
                        dns.admin.users.list();
                    },
                    {
                        insertInDiv: $('#loadProgresses')
                    }
                );
            },
            'changeLevel': function (userName, level) {
                dns.loadRemote.loadRemote('user/update',
                    [
                        userName, level
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
            'add': function (name, type, soa) {
                dns.loadRemote.loadRemote('domains/add',
                    [name, type, soa],
                    function (data, success) {
                        dns.admin.domains.list();
                        if (success)
                            $('#domain_add_button').trigger('click');
                    },
                    {
                        insertInDiv: $('#loadProgresses')
                    }
                );
            },
            'list': function () {
                dns.loadRemote.loadRemote('domains/get',
                    [],
                    function (data) {
                        var $table = $('#domains');
                        $table.find('tr:not(:first)').detach();
                        for (var i in data.data) {
                            if (!data.data.hasOwnProperty(i)) continue;
                            var $row = $('<tr data-did="%d" data-dname="%s"> \
									<td>%d</td> \
									<td>%s</td> \
									<td>%s</td> \
									<td>%d</td> \
									<td class="specialRecords"></td> \
									<td> \
										<a href="#" class="domainListName">%s</a> \
										<a href="#" class="domainListRecordAdd">%s</a> \
										<a href="#" class="domainListDel">%s</a> \
									</td> \
								</tr>'.format(
                                data.data[i].id,
                                data.data[i].name,
                                data.data[i].id,
                                data.data[i].name,
                                data.data[i].type,
                                data.data[i].last_check,
                                i18n.pgettext('DomainList', 'Name'),
                                i18n.pgettext('DomainList', '+Record'),
                                i18n.pgettext('DomainList', 'Remove')
                            ));
                            var $special = $row.find('.specialRecords').append(
                                '<table border="1"><tr><th>%s</th><th>%s</th><th>%s</th><th>%s</th><th>%s</th><th>%s</th></tr>'
                                    .format(
                                    i18n.pgettext('DomainListHeader', 'ID'),
                                    i18n.pgettext('DomainListHeader', 'Name'),
                                    i18n.pgettext('DomainListHeader', 'Type'),
                                    i18n.pgettext('DomainListHeader', 'Content'),
                                    i18n.pgettext('DomainListHeader', 'TTL'),
                                    i18n.pgettext('DomainListHeader', 'OP')
                                ));
                            for (var j in data.data[i].records) {
                                if (!data.data[i].records.hasOwnProperty(j)) continue;
                                var r = data.data[i].records[j];
                                $special.find('table').append('<tr data-rid="%d" data-rName="%s" data-rType="%s" data-rContent="%s" data-rTtl="%s"> \
															  <td>%d</td><td>%s</td><td>%s</td><td class="content">%s</td><td>%d</td> \
															  <td> \
															  <a href="#" title="%s" class="domainListRecordEdit">#</a> \
															  <a href="#" title="%s" class="domainListRecordDelete">-</a> \
															  </td> \
															  </tr>'.format(
                                    r.id, r.name, r.type, r.content, r.ttl,
                                    r.id, r.name, r.type, r.content, r.ttl,
                                    i18n.pgettext('DomainListRecordButtons', 'Edit'),
                                    i18n.pgettext('DomainListRecordButtons', 'Remove')
                                ));
                            }
                            $row.find('.specialRecords').append('</table>');
                            $table.append($row);
                        }

                        $table.find('.domainListDel').on('click', function () {
                            var did = $(this).parents('tr').attr('data-did');
                            var dName = $(this).parents('tr').attr('data-dName');
                            if (confirm(i18n.pgettext('RemoveDomain', "Remove domain %s?").format(dName)))
                                dns.admin.domains.del(did);
                            return false;
                        });
                        $table.find('.domainListName').on('click', function () {
                            $('.popup').not(this).hide();
                            var $this = $(this);
                            var pos = $this.offset();
                            var d = $this.parents('tr').attr('data-did');
                            $('#domainsListName').val($this.parents('tr').attr('data-dName'));
                            $('#domainsListNamePopup')
                                .attr('data-did', d)
                                .css('left', pos.left - 60)
                                .css('top', pos.top + 20)
                                .show();
                            return false;
                        });

                        $table.find('.domainListRecordAdd').on('click', function () {
                            $('.popup').not(this).hide();
                            var $this = $(this);
                            var pos = $this.offset();
                            $('#domainsListRecordName').val($this.parents('tr').attr('data-dName'));
                            $('#domainsListRecordType')[0].selectedIndex = 0;
                            $('#domainsListRecordContent').val('');
                            $('#domainsListRecordTTL').val('86400');
                            $('#domainsListRecordPopup')
                                .removeAttr('data-rid')
                                .attr('data-did', $(this).parents('tr[data-did]').attr('data-did'))
                                .css('left', pos.left - 60)
                                .css('top', pos.top + 20)
                                .show();
                            return false;
                        });

                        $table.find('.domainListRecordEdit').on('click', function () {
                            $('.popup').not(this).hide();
                            var $this = $(this);
                            var pos = $this.offset();
                            var r = $this.parents('tr').attr('data-rid');
                            var $tr = $this.parents('tr');
                            $('#domainsListRecordName').val($tr.attr('data-rName'));
                            $('#domainsListRecordType').val($tr.attr('data-rType'));
                            $('#domainsListRecordContent').val($tr.attr('data-rContent'));
                            $('#domainsListRecordTTL').val($tr.attr('data-rTtl'));
                            $('#domainsListRecordPopup')
                                .attr('data-rid', r)
                                .css('left', pos.left - 60)
                                .css('top', pos.top + 20)
                                .show();
                            return false;
                        });
                        $table.find('.domainListRecordDelete').on('click', function () {
                            var rid = $(this).parents('tr').attr('data-rid');
                            var rName = $(this).parents('tr').attr('data-rName');
                            var rType = $(this).parents('tr').attr('data-rType');
                            var dName = $(this).parents('tr').parents('tr').attr('data-dName');
                            if (confirm(Jed.sprintf(i18n.pgettext(
                                    'RemoveRecordFromDomainConfirmation',
                                    "Really remove record %1$s (%2$s) on domain %3$s?"),
                                    rName, rType, dName
                                )))
                                dns.admin.domains.recordDel(rid);
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
                    function () {
                        dns.admin.domains.list();
                    },
                    {
                        insertInDiv: $('#loadProgresses')
                    }
                );
            },
            'recordDel': function (rid) {
                dns.loadRemote.loadRemote('domains/deleteDomainRecord',
                    [rid],
                    function () {
                        dns.admin.domains.list();
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
            'recordAdd': function (did, rName, rType, rContent, rTtl) {
                dns.loadRemote.loadRemote('domains/addDomainRecord',
                    [did, rName, rType, rContent, rTtl],
                    function (data, success) {
                        if (success)
                            $('#domainsListRecordPopup').hide();
                        dns.admin.domains.list();
                    }
                );
            },
            'recordUpdate': function (rid, rName, rType, rContent, rTtl) {
                dns.loadRemote.loadRemote('domains/updateDomainRecord',
                    [rid, rName, rType, rContent, rTtl],
                    function (data, success) {
                        if (success)
                            $('#domainsListRecordPopup').hide();
                        dns.admin.domains.list();
                    }
                );
            }
        }
    };

    $('#user_add_button').on('click', function () {
        var $div = $('#user_add');
        $div.toggleClass('active');
        if ($div.hasClass('active')) {
            $('#user_add_username').val('');
            var r = dns.createRandomString(12);
            $ps.val(r);
            $pDef.text(r);
            $('#user_add_default').show();
            $('#user_add_level').val('user');
        }
    });

    $ps.on('focus', function () {
        if ($p1.val() == $pDef.text())
            $p1.val('');
        if ($p2.val() == $pDef.text())
            $p2.val('');
        $('#user_add_default').hide();
    }).on('blur', function () {
        if ($p1.val().length == 0 && $p2.val().length == 0) {
            $ps.val($pDef.text());
            $('#user_add_default').show();
            $('#user_add_nomatch').hide();
        }
    }).on('keyup', function () {
        if ($p1.val() != $p2.val()) {
            $('#user_add_nomatch').show();
        }
        else {
            $('#user_add_nomatch').hide();
        }
    });

    $('#user_add_submit').on('click', function () {
        var $name = $('#user_add_username');
        var $email = $('#user_add_email');
        var ok = true;
        if ($name.val().length == 0) {
            dns.fehler($name);
            ok = false;
        }
        if ($p1.val() != $p2.val()) {
            dns.fehler($p1);
            dns.fehler($p2);
            ok = false;
        }
        if (!$email.val().match(/@/) || $email.val().length <= 3) {
            dns.fehler($email);
            ok = false;
        }
        if (!ok)
            return;
        dns.admin.users.add(
            $name.val(),
            $p1.val(),
            $('#user_add_level').val(),
            $email.val()
        );
    });

    $('#userListLevelSubmit').on('click', function () {
        var u = $('#userListLevelPopup').attr('data-uid');
        dns.admin.users.changeLevel(u, $('#userListLevel').val());
    });

    $('#userListReload').on('click', dns.admin.users.list);


    $('#domain_add_button').on('click', function () {
        var $div = $('#domain_add');
        $div.toggleClass('active');
        if ($div.hasClass('active')) {
            $('#domain_add_name').val('');
            $('#domain_add_type').val('NATIVE');
        }
    });

    $('#domain_add_submit').on('click', function () {
        var $name = $('#domain_add_name');
        var ok = true;
        if ($name.val().length == 0) {
            dns.fehler($name);
            ok = false;
        }
        if (!ok)
            return;
        dns.admin.domains.add(
            $name.val(),
            $('#domain_add_type').val(),
            $('#domain_add_soa').val()
        );
    });

    $('#domainListReload').on('click', dns.admin.domains.list);

    $('#domainsListNameSubmit').on('click', function () {
        var d = $('#domainsListNamePopup').attr('data-did');
        dns.admin.domains.updateName(d, $('#domainsListName').val());
    });

    $('#domainsListRecordSubmit').on('click', function () {
        var $domainsListRecordPopup = $('#domainsListRecordPopup');
        var d = $domainsListRecordPopup.attr('data-did');
        var r = $domainsListRecordPopup.attr('data-rid');
        var rName = $('#domainsListRecordName').val();
        var rType = $('#domainsListRecordType').val();
        var rContent = $('#domainsListRecordContent').val();
        var rTtl = $('#domainsListRecordTTL').val();
        if (typeof(r) == 'undefined') { // new record
            dns.admin.domains.recordAdd(d, rName, rType, rContent, rTtl);
        }
        else {
            dns.admin.domains.recordUpdate(r, rName, rType, rContent, rTtl);
        }
    });

    dns.admin.users.list();
    dns.admin.domains.list();
};
