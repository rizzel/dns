window.initPageSpecific = function () {
    var ps = $$('#user_add_password1, #user_add_password2');
    var p1 = $('#user_add_password1');
    var p2 = $('#user_add_password2');
    var pDef = $('#user_add_password_default');

    function showPopup(popup, anchor, offsetLeft, offsetTop) {
        $$('.popup').forEach(function (p) { p.style.display = 'none'; });
        var rect = anchor.getBoundingClientRect();
        popup.style.left = (rect.left + window.scrollX + (offsetLeft || -60)) + 'px';
        popup.style.top = (rect.top + window.scrollY + (offsetTop || 20)) + 'px';
        popup.style.display = '';
    }

    dns.admin = {
        'users': {
            'add': function (name, password, level, email) {
                dns.loadRemote.loadRemote('user/add',
                    [name, password, level, email],
                    function (data, success) {
                        dns.admin.users.list();
                        if (success)
                            $('#user_add_button').click();
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
                        var table = $('#users');
                        table.querySelectorAll('tr:not(:first-child)').forEach(function (el) { el.remove(); });
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
                            table.insertAdjacentHTML('beforeend', '<tr data-uid="%q" data-level="%q"> \
									<td>%h</td> \
									<td>%h</td> \
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
                                data.data[i].email.length > 0 ? '<a href="mailto:%q">%h</a>'.format(
                                    data.data[i].email, data.data[i].email) : i18n.pgettext('MailToUnknown', 'unknown'),
                                records.length,
                                records.join('<br />'),
                                i18n.pgettext('UserList', 'Level'),
                                i18n.pgettext('UserList', 'Remove')
                            ));
                        }

                        table.querySelectorAll('.userListDel').forEach(function (el) {
                            el.addEventListener('click', function (e) {
                                e.preventDefault();
                                var u = this.closest('tr').getAttribute('data-uid');
                                if (confirm(i18n.pgettext("Really delete user %s?").format(u)))
                                    dns.admin.users.del(u);
                            });
                        });

                        table.querySelectorAll('.userListLevel').forEach(function (el) {
                            el.addEventListener('click', function (e) {
                                e.preventDefault();
                                var tr = this.closest('tr');
                                var u = tr.getAttribute('data-uid');
                                $('#userListLevel').value = tr.getAttribute('data-level');
                                var popup = $('#userListLevelPopup');
                                popup.setAttribute('data-uid', u);
                                showPopup(popup, this);
                            });
                        });

                        table.querySelectorAll('.userListZeigen').forEach(function (el) {
                            el.addEventListener('click', function () {
                                this.parentElement.querySelectorAll('.userListZeigen').forEach(function (s) {
                                    s.style.display = s.style.display === 'none' ? '' : 'none';
                                });
                            });
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
                            $('#userListLevelPopup').style.display = 'none';
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
                            $('#domain_add_button').click();
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
                        var table = $('#domains');
                        table.querySelectorAll('tr:not(:first-child)').forEach(function (el) { el.remove(); });
                        for (var i in data.data) {
                            if (!data.data.hasOwnProperty(i)) continue;
                            var tpl = document.createElement('template');
                            tpl.innerHTML = '<tr data-did="%d" data-dname="%q"> \
									<td>%d</td> \
									<td>%h</td> \
									<td>%h</td> \
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
                            );
                            var row = tpl.content.firstElementChild;
                            var special = row.querySelector('.specialRecords');
                            special.insertAdjacentHTML('beforeend',
                                '<table border="1"><tr><th>%s</th><th>%s</th><th>%s</th><th>%s</th><th>%s</th><th>%s</th></tr></table>'
                                    .format(
                                    i18n.pgettext('DomainListHeader', 'ID'),
                                    i18n.pgettext('DomainListHeader', 'Name'),
                                    i18n.pgettext('DomainListHeader', 'Type'),
                                    i18n.pgettext('DomainListHeader', 'Content'),
                                    i18n.pgettext('DomainListHeader', 'TTL'),
                                    i18n.pgettext('DomainListHeader', 'OP')
                                ));
                            var innerTable = special.querySelector('table');
                            for (var j in data.data[i].records) {
                                if (!data.data[i].records.hasOwnProperty(j)) continue;
                                var r = data.data[i].records[j];
                                innerTable.insertAdjacentHTML('beforeend', '<tr data-rid="%d" data-rName="%q" data-rType="%q" data-rContent="%q" data-rTtl="%q"> \
																  <td>%d</td><td>%h</td><td>%h</td><td class="content">%h</td><td>%d</td> \
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
                            table.appendChild(row);
                        }

                        table.querySelectorAll('.domainListDel').forEach(function (el) {
                            el.addEventListener('click', function (e) {
                                e.preventDefault();
                                var tr = this.closest('tr[data-did]');
                                var did = tr.getAttribute('data-did');
                                var dName = tr.getAttribute('data-dname');
                                if (confirm(i18n.pgettext('RemoveDomain', "Remove domain %s?").format(dName)))
                                    dns.admin.domains.del(did);
                            });
                        });
                        table.querySelectorAll('.domainListName').forEach(function (el) {
                            el.addEventListener('click', function (e) {
                                e.preventDefault();
                                var tr = this.closest('tr[data-did]');
                                var d = tr.getAttribute('data-did');
                                $('#domainsListName').value = tr.getAttribute('data-dname');
                                var popup = $('#domainsListNamePopup');
                                popup.setAttribute('data-did', d);
                                showPopup(popup, this);
                            });
                        });

                        table.querySelectorAll('.domainListRecordAdd').forEach(function (el) {
                            el.addEventListener('click', function (e) {
                                e.preventDefault();
                                var tr = this.closest('tr[data-did]');
                                $('#domainsListRecordName').value = tr.getAttribute('data-dname');
                                $('#domainsListRecordType').selectedIndex = 0;
                                $('#domainsListRecordContent').value = '';
                                $('#domainsListRecordTTL').value = '86400';
                                var popup = $('#domainsListRecordPopup');
                                popup.removeAttribute('data-rid');
                                popup.setAttribute('data-did', tr.getAttribute('data-did'));
                                showPopup(popup, this);
                            });
                        });

                        table.querySelectorAll('.domainListRecordEdit').forEach(function (el) {
                            el.addEventListener('click', function (e) {
                                e.preventDefault();
                                var tr = this.closest('tr[data-rid]');
                                var r = tr.getAttribute('data-rid');
                                $('#domainsListRecordName').value = tr.getAttribute('data-rName');
                                $('#domainsListRecordType').value = tr.getAttribute('data-rType');
                                $('#domainsListRecordContent').value = tr.getAttribute('data-rContent');
                                $('#domainsListRecordTTL').value = tr.getAttribute('data-rTtl');
                                var popup = $('#domainsListRecordPopup');
                                popup.setAttribute('data-rid', r);
                                showPopup(popup, this);
                            });
                        });
                        table.querySelectorAll('.domainListRecordDelete').forEach(function (el) {
                            el.addEventListener('click', function (e) {
                                e.preventDefault();
                                var tr = this.closest('tr[data-rid]');
                                var rid = tr.getAttribute('data-rid');
                                var rName = tr.getAttribute('data-rName');
                                var rType = tr.getAttribute('data-rType');
                                var dName = tr.closest('tr[data-dname]').getAttribute('data-dname');
                                if (confirm(Jed.sprintf(i18n.pgettext(
                                        'RemoveRecordFromDomainConfirmation',
                                        "Really remove record %1$s (%2$s) on domain %3$s?"),
                                        rName, rType, dName
                                    )))
                                    dns.admin.domains.recordDel(rid);
                            });
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
                            $('#domainsListNamePopup').style.display = 'none';
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
                            $('#domainsListRecordPopup').style.display = 'none';
                        dns.admin.domains.list();
                    }
                );
            },
            'recordUpdate': function (rid, rName, rType, rContent, rTtl) {
                dns.loadRemote.loadRemote('domains/updateDomainRecord',
                    [rid, rName, rType, rContent, rTtl],
                    function (data, success) {
                        if (success)
                            $('#domainsListRecordPopup').style.display = 'none';
                        dns.admin.domains.list();
                    }
                );
            }
        }
    };

    $('#user_add_button').addEventListener('click', function () {
        var div = $('#user_add');
        div.classList.toggle('active');
        if (div.classList.contains('active')) {
            $('#user_add_username').value = '';
            var r = dns.createRandomString(12);
            ps.forEach(function (el) { el.value = r; });
            pDef.textContent = r;
            $('#user_add_default').style.display = '';
            $('#user_add_level').value = 'user';
        }
    });

    ps.forEach(function (el) {
        el.addEventListener('focus', function () {
            if (p1.value == pDef.textContent)
                p1.value = '';
            if (p2.value == pDef.textContent)
                p2.value = '';
            $('#user_add_default').style.display = 'none';
        });
        el.addEventListener('blur', function () {
            if (p1.value.length == 0 && p2.value.length == 0) {
                ps.forEach(function (p) { p.value = pDef.textContent; });
                $('#user_add_default').style.display = '';
                $('#user_add_nomatch').style.display = 'none';
            }
        });
        el.addEventListener('keyup', function () {
            if (p1.value != p2.value) {
                $('#user_add_nomatch').style.display = '';
            }
            else {
                $('#user_add_nomatch').style.display = 'none';
            }
        });
    });

    $('#user_add_submit').addEventListener('click', function () {
        var nameEl = $('#user_add_username');
        var emailEl = $('#user_add_email');
        var ok = true;
        if (nameEl.value.length == 0) {
            dns.fehler(nameEl);
            ok = false;
        }
        if (p1.value != p2.value) {
            dns.fehler(p1);
            dns.fehler(p2);
            ok = false;
        }
        if (!emailEl.value.match(/@/) || emailEl.value.length <= 3) {
            dns.fehler(emailEl);
            ok = false;
        }
        if (!ok)
            return;
        dns.admin.users.add(
            nameEl.value,
            p1.value,
            $('#user_add_level').value,
            emailEl.value
        );
    });

    $('#userListLevelSubmit').addEventListener('click', function () {
        var u = $('#userListLevelPopup').getAttribute('data-uid');
        dns.admin.users.changeLevel(u, $('#userListLevel').value);
    });

    $('#userListReload').addEventListener('click', dns.admin.users.list);


    $('#domain_add_button').addEventListener('click', function () {
        var div = $('#domain_add');
        div.classList.toggle('active');
        if (div.classList.contains('active')) {
            $('#domain_add_name').value = '';
            $('#domain_add_type').value = 'NATIVE';
        }
    });

    $('#domain_add_submit').addEventListener('click', function () {
        var nameEl = $('#domain_add_name');
        var ok = true;
        if (nameEl.value.length == 0) {
            dns.fehler(nameEl);
            ok = false;
        }
        if (!ok)
            return;
        dns.admin.domains.add(
            nameEl.value,
            $('#domain_add_type').value,
            $('#domain_add_soa').value
        );
    });

    $('#domainListReload').addEventListener('click', dns.admin.domains.list);

    $('#domainsListNameSubmit').addEventListener('click', function () {
        var d = $('#domainsListNamePopup').getAttribute('data-did');
        dns.admin.domains.updateName(d, $('#domainsListName').value);
    });

    $('#domainsListRecordSubmit').addEventListener('click', function () {
        var popup = $('#domainsListRecordPopup');
        var d = popup.getAttribute('data-did');
        var r = popup.getAttribute('data-rid');
        var rName = $('#domainsListRecordName').value;
        var rType = $('#domainsListRecordType').value;
        var rContent = $('#domainsListRecordContent').value;
        var rTtl = $('#domainsListRecordTTL').value;
        if (r === null) { // new record
            dns.admin.domains.recordAdd(d, rName, rType, rContent, rTtl);
        }
        else {
            dns.admin.domains.recordUpdate(r, rName, rType, rContent, rTtl);
        }
    });

    dns.admin.users.list();
    dns.admin.domains.list();
};
