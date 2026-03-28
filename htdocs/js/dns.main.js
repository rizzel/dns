window.initPageSpecific = function () {
    function showPopup(popup, anchor, offsetLeft, offsetTop) {
        $$('.popup').forEach(function (p) { p.style.display = 'none'; });
        var rect = anchor.getBoundingClientRect();
        popup.style.left = (rect.left + window.scrollX + (offsetLeft || -60)) + 'px';
        popup.style.top = (rect.top + window.scrollY + (offsetTop || 20)) + 'px';
        popup.style.display = '';
    }

    dns.record = {
        'add': function (domain, type, name, content, password, ttl) {
            dns.loadRemote.loadRemote('domains/addRecord',
                [domain, type, name, content, password, ttl],
                function (data, success) {
                    dns.record.list();
                    if (success)
                        $('#addRecord_button').click();
                },
                {
                    insertInDiv: $('#loadProgresses')
                }
            );
        },
        'list': function () {
            if (dns.user.user.level == 'nobody')
                return;
            dns.loadRemote.loadRemote('domains/myRecords',
                [],
                function (data, success) {
                    if (!success)
                        return;
                    var table = $('#recordList');
                    table.querySelectorAll('tr:not(:first-child)').forEach(function (el) { el.remove(); });
                    for (var i in data.data) {
                        if (!data.data.hasOwnProperty(i)) continue;
                        table.insertAdjacentHTML('beforeend', '<tr data-rid="%d" data-rName="%q" data-rContent="%q" data-rTtl="%d"> \
									<td>%d</td> \
									<td>%h</td> \
									<td>%h</td> \
									<td>%h</td> \
									<td>%h</td> \
									<td>%d</td> \
									<td>%s</td> \
									<td>%s</td> \
									<td> \
										<a href="#" class="recordListName">%s</a> \
										<a href="#" class="recordListPassword">%s</a> \
										<a href="#" class="recordListContent">%s</a> \
										<a href="#" class="recordListTTL">%s</a> \
										<a href="#" class="recordListDel">%s</a> \
									</td> \
								</tr>'.format(
                            data.data[i].id,
                            data.data[i].name,
                            data.data[i].content,
                            data.data[i].ttl,
                            data.data[i].id,
                            data.data[i].domain_name,
                            data.data[i].name,
                            data.data[i].type,
                            data.data[i].content,
                            data.data[i].ttl,
                            '<span class="table_password" data-p="%q">%s</span>'.format(
                                data.data[i].password,
                                i18n.pgettext('RecordListPasswordShow', 'Click')
                            ),
                            dns.alsZeit(data.data[i].change_date),
                            i18n.pgettext('RecordListHeader', 'Name'),
                            i18n.pgettext('RecordListHeader', 'Password'),
                            i18n.pgettext('RecordListHeader', 'Content'),
                            i18n.pgettext('RecordListHeader', 'TTL'),
                            i18n.pgettext('RecordListHeader', 'Remove')
                        ));
                    }
                    table.querySelectorAll('.table_password').forEach(function (el) {
                        el.addEventListener('click', function () {
                            this.innerHTML = this.getAttribute('data-p');
                        }, {once: true});
                    });

                    table.querySelectorAll('.recordListDel').forEach(function (el) {
                        el.addEventListener('click', function (e) {
                            e.preventDefault();
                            var r = this.closest('tr').getAttribute('data-rid');
                            if (confirm(Jed.sprintf(i18n.pgettext('RecordListRemove', "Remove record %s?"), r)))
                                dns.record.del(r);
                        });
                    });

                    table.querySelectorAll('.recordListName').forEach(function (el) {
                        el.addEventListener('click', function (e) {
                            e.preventDefault();
                            var tr = this.closest('tr');
                            var r = tr.getAttribute('data-rid');
                            $('#recordListName').value = tr.getAttribute('data-rName');
                            var popup = $('#recordListNamePopup');
                            popup.setAttribute('data-rid', r);
                            showPopup(popup, this);
                        });
                    });

                    table.querySelectorAll('.recordListContent').forEach(function (el) {
                        el.addEventListener('click', function (e) {
                            e.preventDefault();
                            var tr = this.closest('tr');
                            var r = tr.getAttribute('data-rid');
                            $('#recordListContent').value = tr.getAttribute('data-rContent');
                            var popup = $('#recordListContentPopup');
                            popup.setAttribute('data-rid', r);
                            showPopup(popup, this);
                        });
                    });

                    table.querySelectorAll('.recordListPassword').forEach(function (el) {
                        el.addEventListener('click', function (e) {
                            e.preventDefault();
                            var tr = this.closest('tr');
                            var r = tr.getAttribute('data-rid');
                            $('#recordListPassword').value = dns.createRandomString(32);
                            var popup = $('#recordListPasswordPopup');
                            popup.setAttribute('data-rid', r);
                            showPopup(popup, this);
                        });
                    });

                    table.querySelectorAll('.recordListTTL').forEach(function (el) {
                        el.addEventListener('click', function (e) {
                            e.preventDefault();
                            var tr = this.closest('tr');
                            var r = tr.getAttribute('data-rid');
                            $('#recordListTTL').value = tr.getAttribute('data-rTtl');
                            var popup = $('#recordListTTLPopup');
                            popup.setAttribute('data-rid', r);
                            showPopup(popup, this);
                        });
                    });
                },
                {
                    insertInDiv: $('#loadProgresses')
                }
            );
        },
        'del': function (rid) {
            dns.loadRemote.loadRemote('domains/deleteRecord',
                [rid],
                function () {
                    dns.record.list();
                },
                {
                    insertInDiv: $('#loadProgresses')
                }
            );
        },
        'updateName': function (recordId, name) {
            dns.loadRemote.loadRemote('domains/updateRecordName',
                [recordId, name],
                function (data, success) {
                    if (success)
                        $('#recordListNamePopup').style.display = 'none';
                    dns.record.list();
                },
                {insertInDiv: $('#loadProgresses')}
            );
        },
        'updatePassword': function (recordId, password) {
            dns.loadRemote.loadRemote('domains/updateRecordPassword',
                [recordId, password],
                function (data, success) {
                    if (success)
                        $('#recordListPasswordPopup').style.display = 'none';
                    dns.record.list();
                },
                {insertInDiv: $('#loadProgresses')}
            );
        },
        'updateContent': function (recordId, content) {
            dns.loadRemote.loadRemote('domains/updateRecordContent',
                [recordId, content],
                function (data, success) {
                    if (success)
                        $('#recordListContentPopup').style.display = 'none';
                    dns.record.list();
                },
                {insertInDiv: $('#loadProgresses')}
            );
        },
        'updateTTL': function (recordId, ttl) {
            dns.loadRemote.loadRemote('domains/updateRecordTTL',
                [recordId, ttl],
                function (data, success) {
                    if (success)
                        $('#recordListTTLPopup').style.display = 'none';
                    dns.record.list();
                },
                {insertInDiv: $('#loadProgresses')}
            );
        }
    };

    dns.domainOptionList = function (select) {
        dns.loadRemote.loadRemote('domains/miniList',
            [],
            function (data, success) {
                if (!success)
                    return;
                select.innerHTML = '';
                for (var i in data.data) {
                    if (!data.data.hasOwnProperty(i)) continue;
                    select.insertAdjacentHTML('beforeend', '<option value="%d">%h</option>'.format(
                        data.data[i].id,
                        data.data[i].name
                    ));
                }
            },
            {
                insertInDiv: $('#loadProgresses')
            }
        );
    };

    $('#addRecord_button').addEventListener('click', function () {
        var div = $('#addRecord');
        div.classList.toggle('active');
        if (div.classList.contains('active')) {
            $('#addRecordType').selectedIndex = 0;
            $('#addRecordType').dispatchEvent(new Event('change'));
            $('#addRecordPassword').value = dns.createRandomString(32);
            dns.domainOptionList($('#addRecordDomain'));
        }
    });

    $('#addRecordType').addEventListener('change', function () {
        var p = $('#addRecord_d_password');
        var l_content = $('label[for="addRecordContent"]');
        var c = $('#addRecordContent');
        var ttl = $('#addRecordTTL');
        switch (this.value) {
            case 'A':
                p.style.display = '';
                l_content.textContent = 'IPv4:';
                ttl.value = 60;
                dns.loadRemote.loadRemote('user/myIP',
                    [],
                    function (data, success) {
                        if (!success)
                            return;
                        $('#addRecordContent').value = data.data[0];
                    },
                    {
                        insertInDiv: $('#loadProgresses')
                    }
                );
                break;
            case 'AAAA':
                p.style.display = '';
                l_content.textContent = i18n.pgettext('AddRecordTypeFields', 'IPv6:');
                c.value = '';
                ttl.value = 120;
                break;
            case 'CNAME':
                p.style.display = 'none';
                l_content.textContent = i18n.pgettext('AddRecordTypeFields', 'Original URI:');
                c.value = '';
                ttl.value = 86400;
                break;
        }
        $('#addRecordName').dispatchEvent(new Event('keyup'));
    });

    $('#addRecordName').addEventListener('keyup', function () {
        dns.loadRemote.loadRemote('domains/recordTest',
            [
                $('#addRecordDomain').value,
                $('#addRecordName').value,
                $('#addRecordType').value
            ],
            function (data, success) {
                var testSpan = $('#addRecordTest');
                if (!success || !data.data) {
                    testSpan.style.display = 'none';
                    return;
                }
                testSpan.className = '';
                testSpan.title = data.data.status;
                testSpan.style.display = '';
                testSpan.textContent = "%s: %s".format(data.data.type, data.data.domain);
                if (data.data.free)
                    testSpan.classList.add('frei');
                else if (data.data.invalid)
                    testSpan.classList.add('invalid');
                else
                    testSpan.classList.add('belegt');
            }
        );
    });

    $('#addRecordSubmit').addEventListener('click', function () {
        var type = $('#addRecordType').value;
        var name = $('#addRecordName');
        var content = $('#addRecordContent');
        var password = $('#addRecordPassword');
        var ttl = $('#addRecordTTL');
        var ok = true;
        if (name.value.length < 1) {
            dns.fehler(name);
            ok = false;
        }
        if (content.value.length == 0) {
            dns.fehler(content);
            ok = false;
        }
        if (isNaN(parseInt(ttl.value))) {
            dns.fehler(ttl);
            ok = false;
        }
        if ((type == 'A' || type == 'AAAA') &&
            password.value.length == 0) {
            dns.fehler(password);
            ok = false;
        }
        if (!ok)
            return;
        dns.record.add(
            $('#addRecordDomain').value,
            type,
            name.value,
            content.value,
            password.value,
            ttl.value
        );
    });

    $('#recordListReload').addEventListener('click', dns.record.list);

    $('#recordListNameSubmit').addEventListener('click', function () {
        var r = $('#recordListNamePopup').getAttribute('data-rid');
        dns.record.updateName(r, $('#recordListName').value);
    });

    $('#recordListContentSubmit').addEventListener('click', function () {
        var r = $('#recordListContentPopup').getAttribute('data-rid');
        dns.record.updateContent(r, $('#recordListContent').value);
    });

    $('#recordListPasswordSubmit').addEventListener('click', function () {
        var r = $('#recordListPasswordPopup').getAttribute('data-rid');
        dns.record.updatePassword(r, $('#recordListPassword').value);
    });

    $('#recordListTTLSubmit').addEventListener('click', function () {
        var r = $('#recordListTTLPopup').getAttribute('data-rid');
        dns.record.updateTTL(r, $('#recordListTTL').value);
    });

    dns.record.list();
};
