window.initPageSpecific = () => {
    const ps = $$('#user_add_password1, #user_add_password2');
    const p1 = $('#user_add_password1');
    const p2 = $('#user_add_password2');
    const pDef = $('#user_add_password_default');

    function showPopup(popup, anchor, offsetLeft, offsetTop) {
        $$('.popup').forEach((p) => { p.style.display = 'none'; });
        const rect = anchor.getBoundingClientRect();
        popup.style.left = (rect.left + window.scrollX + (offsetLeft || -60)) + 'px';
        popup.style.top = (rect.top + window.scrollY + (offsetTop || 20)) + 'px';
        popup.style.display = '';
    }

    dns.admin = {
        users: {
            add(name, password, level, email) {
                dns.loadRemote.loadRemote('user/add',
                    [name, password, level, email],
                    (data, success) => {
                        dns.admin.users.list();
                        if (success)
                            $('#user_add_button').click();
                    },
                    { insertInDiv: $('#loadProgresses') }
                );
            },

            list() {
                dns.loadRemote.loadRemote('user/get',
                    [],
                    (data, success) => {
                        if (!success)
                            return;
                        const table = $('#users');
                        table.querySelectorAll('tr:not(:first-child)').forEach((el) => el.remove());
                        for (const user of data.data) {
                            const records = [];
                            for (const r of user.records) {
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
                                user.username,
                                user.level,
                                user.username,
                                user.level,
                                user.email.length > 0 ? '<a href="mailto:%q">%h</a>'.format(
                                    user.email, user.email) : i18n.pgettext('MailToUnknown', 'unknown'),
                                records.length,
                                records.join('<br />'),
                                i18n.pgettext('UserList', 'Level'),
                                i18n.pgettext('UserList', 'Remove')
                            ));
                        }

                        table.querySelectorAll('.userListDel').forEach((el) => {
                            el.addEventListener('click', function (e) {
                                e.preventDefault();
                                const u = this.closest('tr').getAttribute('data-uid');
                                if (confirm(i18n.pgettext("Really delete user %s?").format(u)))
                                    dns.admin.users.del(u);
                            });
                        });

                        table.querySelectorAll('.userListLevel').forEach((el) => {
                            el.addEventListener('click', function (e) {
                                e.preventDefault();
                                const tr = this.closest('tr');
                                const u = tr.getAttribute('data-uid');
                                $('#userListLevel').value = tr.getAttribute('data-level');
                                const popup = $('#userListLevelPopup');
                                popup.setAttribute('data-uid', u);
                                showPopup(popup, this);
                            });
                        });

                        table.querySelectorAll('.userListZeigen').forEach((el) => {
                            el.addEventListener('click', function () {
                                this.parentElement.querySelectorAll('.userListZeigen').forEach((s) => {
                                    s.style.display = s.style.display === 'none' ? '' : 'none';
                                });
                            });
                        });
                    },
                    { insertInDiv: $('#loadProgresses') }
                );
            },

            del(userName) {
                dns.loadRemote.loadRemote('user/delete',
                    [userName],
                    () => dns.admin.users.list(),
                    { insertInDiv: $('#loadProgresses') }
                );
            },

            changeLevel(userName, level) {
                dns.loadRemote.loadRemote('user/update',
                    [userName, level],
                    (data, success) => {
                        if (success)
                            $('#userListLevelPopup').style.display = 'none';
                        dns.admin.users.list();
                    },
                    { insertInDiv: $('#loadProgresses') }
                );
            }
        },

        domains: {
            add(name, type, soa) {
                dns.loadRemote.loadRemote('domains/add',
                    [name, type, soa],
                    (data, success) => {
                        dns.admin.domains.list();
                        if (success)
                            $('#domain_add_button').click();
                    },
                    { insertInDiv: $('#loadProgresses') }
                );
            },

            list() {
                dns.loadRemote.loadRemote('domains/get',
                    [],
                    (data) => {
                        const table = $('#domains');
                        table.querySelectorAll('tr:not(:first-child)').forEach((el) => el.remove());
                        for (const domain of data.data) {
                            const tpl = document.createElement('template');
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
                                domain.id,
                                domain.name,
                                domain.id,
                                domain.name,
                                domain.type,
                                domain.last_check,
                                i18n.pgettext('DomainList', 'Name'),
                                i18n.pgettext('DomainList', '+Record'),
                                i18n.pgettext('DomainList', 'Remove')
                            );
                            const row = tpl.content.firstElementChild;
                            const special = row.querySelector('.specialRecords');
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
                            const innerTable = special.querySelector('table');
                            for (const r of domain.records) {
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

                        table.querySelectorAll('.domainListDel').forEach((el) => {
                            el.addEventListener('click', function (e) {
                                e.preventDefault();
                                const tr = this.closest('tr[data-did]');
                                const did = tr.getAttribute('data-did');
                                const dName = tr.getAttribute('data-dname');
                                if (confirm(i18n.pgettext('RemoveDomain', "Remove domain %s?").format(dName)))
                                    dns.admin.domains.del(did);
                            });
                        });
                        table.querySelectorAll('.domainListName').forEach((el) => {
                            el.addEventListener('click', function (e) {
                                e.preventDefault();
                                const tr = this.closest('tr[data-did]');
                                const d = tr.getAttribute('data-did');
                                $('#domainsListName').value = tr.getAttribute('data-dname');
                                const popup = $('#domainsListNamePopup');
                                popup.setAttribute('data-did', d);
                                showPopup(popup, this);
                            });
                        });

                        table.querySelectorAll('.domainListRecordAdd').forEach((el) => {
                            el.addEventListener('click', function (e) {
                                e.preventDefault();
                                const tr = this.closest('tr[data-did]');
                                $('#domainsListRecordName').value = tr.getAttribute('data-dname');
                                $('#domainsListRecordType').selectedIndex = 0;
                                $('#domainsListRecordContent').value = '';
                                $('#domainsListRecordTTL').value = '86400';
                                const popup = $('#domainsListRecordPopup');
                                popup.removeAttribute('data-rid');
                                popup.setAttribute('data-did', tr.getAttribute('data-did'));
                                showPopup(popup, this);
                            });
                        });

                        table.querySelectorAll('.domainListRecordEdit').forEach((el) => {
                            el.addEventListener('click', function (e) {
                                e.preventDefault();
                                const tr = this.closest('tr[data-rid]');
                                const r = tr.getAttribute('data-rid');
                                $('#domainsListRecordName').value = tr.getAttribute('data-rName');
                                $('#domainsListRecordType').value = tr.getAttribute('data-rType');
                                $('#domainsListRecordContent').value = tr.getAttribute('data-rContent');
                                $('#domainsListRecordTTL').value = tr.getAttribute('data-rTtl');
                                const popup = $('#domainsListRecordPopup');
                                popup.setAttribute('data-rid', r);
                                showPopup(popup, this);
                            });
                        });
                        table.querySelectorAll('.domainListRecordDelete').forEach((el) => {
                            el.addEventListener('click', function (e) {
                                e.preventDefault();
                                const tr = this.closest('tr[data-rid]');
                                const rid = tr.getAttribute('data-rid');
                                const rName = tr.getAttribute('data-rName');
                                const rType = tr.getAttribute('data-rType');
                                const dName = tr.closest('tr[data-dname]').getAttribute('data-dname');
                                if (confirm(Jed.sprintf(i18n.pgettext(
                                        'RemoveRecordFromDomainConfirmation',
                                        "Really remove record %1$s (%2$s) on domain %3$s?"),
                                        rName, rType, dName
                                    )))
                                    dns.admin.domains.recordDel(rid);
                            });
                        });
                    },
                    { insertInDiv: $('#loadProgresses') }
                );
            },

            del(did) {
                dns.loadRemote.loadRemote('domains/delete',
                    [did],
                    () => dns.admin.domains.list(),
                    { insertInDiv: $('#loadProgresses') }
                );
            },

            recordDel(rid) {
                dns.loadRemote.loadRemote('domains/deleteDomainRecord',
                    [rid],
                    () => dns.admin.domains.list()
                );
            },

            updateName(did, name) {
                dns.loadRemote.loadRemote('domains/updateName',
                    [did, name],
                    (data, success) => {
                        if (success)
                            $('#domainsListNamePopup').style.display = 'none';
                        dns.admin.domains.list();
                    },
                    { insertInDiv: $('#loadProgresses') }
                );
            },

            recordAdd(did, rName, rType, rContent, rTtl) {
                dns.loadRemote.loadRemote('domains/addDomainRecord',
                    [did, rName, rType, rContent, rTtl],
                    (data, success) => {
                        if (success)
                            $('#domainsListRecordPopup').style.display = 'none';
                        dns.admin.domains.list();
                    }
                );
            },

            recordUpdate(rid, rName, rType, rContent, rTtl) {
                dns.loadRemote.loadRemote('domains/updateDomainRecord',
                    [rid, rName, rType, rContent, rTtl],
                    (data, success) => {
                        if (success)
                            $('#domainsListRecordPopup').style.display = 'none';
                        dns.admin.domains.list();
                    }
                );
            }
        }
    };

    $('#user_add_button').addEventListener('click', () => {
        const div = $('#user_add');
        div.classList.toggle('active');
        if (div.classList.contains('active')) {
            $('#user_add_username').value = '';
            const r = dns.createRandomString(12);
            ps.forEach((el) => { el.value = r; });
            pDef.textContent = r;
            $('#user_add_default').style.display = '';
            $('#user_add_level').value = 'user';
        }
    });

    ps.forEach((el) => {
        el.addEventListener('focus', () => {
            if (p1.value === pDef.textContent)
                p1.value = '';
            if (p2.value === pDef.textContent)
                p2.value = '';
            $('#user_add_default').style.display = 'none';
        });
        el.addEventListener('blur', () => {
            if (p1.value.length === 0 && p2.value.length === 0) {
                ps.forEach((p) => { p.value = pDef.textContent; });
                $('#user_add_default').style.display = '';
                $('#user_add_nomatch').style.display = 'none';
            }
        });
        el.addEventListener('keyup', () => {
            $('#user_add_nomatch').style.display = p1.value !== p2.value ? '' : 'none';
        });
    });

    $('#user_add_submit').addEventListener('click', () => {
        const nameEl = $('#user_add_username');
        const emailEl = $('#user_add_email');
        let ok = true;
        if (nameEl.value.length === 0) {
            dns.fehler(nameEl);
            ok = false;
        }
        if (p1.value !== p2.value) {
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

    $('#userListLevelSubmit').addEventListener('click', () => {
        const u = $('#userListLevelPopup').getAttribute('data-uid');
        dns.admin.users.changeLevel(u, $('#userListLevel').value);
    });

    $('#userListReload').addEventListener('click', dns.admin.users.list);

    $('#domain_add_button').addEventListener('click', () => {
        const div = $('#domain_add');
        div.classList.toggle('active');
        if (div.classList.contains('active')) {
            $('#domain_add_name').value = '';
            $('#domain_add_type').value = 'NATIVE';
        }
    });

    $('#domain_add_submit').addEventListener('click', () => {
        const nameEl = $('#domain_add_name');
        if (nameEl.value.length === 0) {
            dns.fehler(nameEl);
            return;
        }
        dns.admin.domains.add(
            nameEl.value,
            $('#domain_add_type').value,
            $('#domain_add_soa').value
        );
    });

    $('#domainListReload').addEventListener('click', dns.admin.domains.list);

    $('#domainsListNameSubmit').addEventListener('click', () => {
        const d = $('#domainsListNamePopup').getAttribute('data-did');
        dns.admin.domains.updateName(d, $('#domainsListName').value);
    });

    $('#domainsListRecordSubmit').addEventListener('click', () => {
        const popup = $('#domainsListRecordPopup');
        const d = popup.getAttribute('data-did');
        const r = popup.getAttribute('data-rid');
        const rName = $('#domainsListRecordName').value;
        const rType = $('#domainsListRecordType').value;
        const rContent = $('#domainsListRecordContent').value;
        const rTtl = $('#domainsListRecordTTL').value;
        if (r === null) {
            dns.admin.domains.recordAdd(d, rName, rType, rContent, rTtl);
        } else {
            dns.admin.domains.recordUpdate(r, rName, rType, rContent, rTtl);
        }
    });

    dns.admin.users.list();
    dns.admin.domains.list();
};
