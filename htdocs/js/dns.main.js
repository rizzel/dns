window.initPageSpecific = () => {
    function showPopup(popup, anchor, offsetLeft, offsetTop) {
        $$('.popup').forEach((p) => { p.style.display = 'none'; });
        const rect = anchor.getBoundingClientRect();
        popup.style.left = (rect.left + window.scrollX + (offsetLeft || -60)) + 'px';
        popup.style.top = (rect.top + window.scrollY + (offsetTop || 20)) + 'px';
        popup.style.display = '';
    }

    dns.record = {
        add(domain, type, name, content, password, ttl) {
            dns.loadRemote.loadRemote('domains/addRecord',
                [domain, type, name, content, password, ttl],
                (data, success) => {
                    dns.record.list();
                    if (success)
                        $('#addRecord_button').click();
                },
                { insertInDiv: $('#loadProgresses') }
            );
        },

        list() {
            if (dns.user.user.level === 'nobody')
                return;
            dns.loadRemote.loadRemote('domains/myRecords',
                [],
                (data, success) => {
                    if (!success)
                        return;
                    const table = $('#recordList');
                    const tbody = table.tBodies[0] || table;
                    tbody.querySelectorAll('tr:not(:first-child)').forEach((el) => el.remove());
                    for (const rec of data.data) {
                        tbody.insertAdjacentHTML('beforeend', '<tr data-rid="%d" data-rName="%q" data-rContent="%q" data-rTtl="%d"> \
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
                            rec.id,
                            rec.name,
                            rec.content,
                            rec.ttl,
                            rec.id,
                            rec.domain_name,
                            rec.name,
                            rec.type,
                            rec.content,
                            rec.ttl,
                            '<span class="table_password" data-p="%q">%s</span>'.format(
                                rec.password,
                                i18n.pgettext('RecordListPasswordShow', 'Click')
                            ),
                            dns.alsZeit(rec.change_date),
                            i18n.pgettext('RecordListHeader', 'Name'),
                            i18n.pgettext('RecordListHeader', 'Password'),
                            i18n.pgettext('RecordListHeader', 'Content'),
                            i18n.pgettext('RecordListHeader', 'TTL'),
                            i18n.pgettext('RecordListHeader', 'Remove')
                        ));
                    }
                    table.querySelectorAll('.table_password').forEach((el) => {
                        el.addEventListener('click', function () {
                            this.innerHTML = this.getAttribute('data-p');
                        }, { once: true });
                    });

                    table.querySelectorAll('.recordListDel').forEach((el) => {
                        el.addEventListener('click', function (e) {
                            e.preventDefault();
                            const r = this.closest('tr').getAttribute('data-rid');
                            if (confirm(Jed.sprintf(i18n.pgettext('RecordListRemove', "Remove record %s?"), r)))
                                dns.record.del(r);
                        });
                    });

                    table.querySelectorAll('.recordListName').forEach((el) => {
                        el.addEventListener('click', function (e) {
                            e.preventDefault();
                            const tr = this.closest('tr');
                            const r = tr.getAttribute('data-rid');
                            $('#recordListName').value = tr.getAttribute('data-rName');
                            const popup = $('#recordListNamePopup');
                            popup.setAttribute('data-rid', r);
                            showPopup(popup, this);
                        });
                    });

                    table.querySelectorAll('.recordListContent').forEach((el) => {
                        el.addEventListener('click', function (e) {
                            e.preventDefault();
                            const tr = this.closest('tr');
                            const r = tr.getAttribute('data-rid');
                            $('#recordListContent').value = tr.getAttribute('data-rContent');
                            const popup = $('#recordListContentPopup');
                            popup.setAttribute('data-rid', r);
                            showPopup(popup, this);
                        });
                    });

                    table.querySelectorAll('.recordListPassword').forEach((el) => {
                        el.addEventListener('click', function (e) {
                            e.preventDefault();
                            const tr = this.closest('tr');
                            const r = tr.getAttribute('data-rid');
                            $('#recordListPassword').value = dns.createRandomString(32);
                            const popup = $('#recordListPasswordPopup');
                            popup.setAttribute('data-rid', r);
                            showPopup(popup, this);
                        });
                    });

                    table.querySelectorAll('.recordListTTL').forEach((el) => {
                        el.addEventListener('click', function (e) {
                            e.preventDefault();
                            const tr = this.closest('tr');
                            const r = tr.getAttribute('data-rid');
                            $('#recordListTTL').value = tr.getAttribute('data-rTtl');
                            const popup = $('#recordListTTLPopup');
                            popup.setAttribute('data-rid', r);
                            showPopup(popup, this);
                        });
                    });
                },
                { insertInDiv: $('#loadProgresses') }
            );
        },

        del(rid) {
            dns.loadRemote.loadRemote('domains/deleteRecord',
                [rid],
                () => dns.record.list(),
                { insertInDiv: $('#loadProgresses') }
            );
        },

        updateName(recordId, name) {
            dns.loadRemote.loadRemote('domains/updateRecordName',
                [recordId, name],
                (data, success) => {
                    if (success)
                        $('#recordListNamePopup').style.display = 'none';
                    dns.record.list();
                },
                { insertInDiv: $('#loadProgresses') }
            );
        },

        updatePassword(recordId, password) {
            dns.loadRemote.loadRemote('domains/updateRecordPassword',
                [recordId, password],
                (data, success) => {
                    if (success)
                        $('#recordListPasswordPopup').style.display = 'none';
                    dns.record.list();
                },
                { insertInDiv: $('#loadProgresses') }
            );
        },

        updateContent(recordId, content) {
            dns.loadRemote.loadRemote('domains/updateRecordContent',
                [recordId, content],
                (data, success) => {
                    if (success)
                        $('#recordListContentPopup').style.display = 'none';
                    dns.record.list();
                },
                { insertInDiv: $('#loadProgresses') }
            );
        },

        updateTTL(recordId, ttl) {
            dns.loadRemote.loadRemote('domains/updateRecordTTL',
                [recordId, ttl],
                (data, success) => {
                    if (success)
                        $('#recordListTTLPopup').style.display = 'none';
                    dns.record.list();
                },
                { insertInDiv: $('#loadProgresses') }
            );
        }
    };

    dns.domainOptionList = (select) => {
        dns.loadRemote.loadRemote('domains/miniList',
            [],
            (data, success) => {
                if (!success)
                    return;
                select.innerHTML = '';
                for (const item of data.data) {
                    select.insertAdjacentHTML('beforeend', '<option value="%d">%h</option>'.format(
                        item.id,
                        item.name
                    ));
                }
            },
            { insertInDiv: $('#loadProgresses') }
        );
    };

    if (!$('#addRecord_button'))
        return;

    $('#addRecord_button').addEventListener('click', () => {
        const div = $('#addRecord');
        div.classList.toggle('active');
        if (div.classList.contains('active')) {
            $('#addRecordType').selectedIndex = 0;
            $('#addRecordType').dispatchEvent(new Event('change'));
            $('#addRecordPassword').value = dns.createRandomString(32);
            dns.domainOptionList($('#addRecordDomain'));
        }
    });

    $('#addRecordType').addEventListener('change', function () {
        const p = $('#addRecord_d_password');
        const labelContent = $('label[for="addRecordContent"]');
        const c = $('#addRecordContent');
        const ttl = $('#addRecordTTL');
        switch (this.value) {
            case 'A':
                p.style.display = '';
                labelContent.textContent = 'IPv4:';
                ttl.value = 60;
                dns.loadRemote.loadRemote('user/myIP',
                    [],
                    (data, success) => {
                        if (success)
                            $('#addRecordContent').value = data.data[0];
                    },
                    { insertInDiv: $('#loadProgresses') }
                );
                break;
            case 'AAAA':
                p.style.display = '';
                labelContent.textContent = i18n.pgettext('AddRecordTypeFields', 'IPv6:');
                c.value = '';
                ttl.value = 120;
                break;
            case 'CNAME':
                p.style.display = 'none';
                labelContent.textContent = i18n.pgettext('AddRecordTypeFields', 'Original URI:');
                c.value = '';
                ttl.value = 86400;
                break;
        }
        $('#addRecordName').dispatchEvent(new Event('keyup'));
    });

    $('#addRecordName').addEventListener('keyup', () => {
        dns.loadRemote.loadRemote('domains/recordTest',
            [
                $('#addRecordDomain').value,
                $('#addRecordName').value,
                $('#addRecordType').value
            ],
            (data, success) => {
                const testSpan = $('#addRecordTest');
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

    $('#addRecordSubmit').addEventListener('click', () => {
        const type = $('#addRecordType').value;
        const name = $('#addRecordName');
        const content = $('#addRecordContent');
        const password = $('#addRecordPassword');
        const ttl = $('#addRecordTTL');
        let ok = true;
        if (name.value.length < 1) {
            dns.fehler(name);
            ok = false;
        }
        if (content.value.length === 0) {
            dns.fehler(content);
            ok = false;
        }
        if (isNaN(parseInt(ttl.value))) {
            dns.fehler(ttl);
            ok = false;
        }
        if ((type === 'A' || type === 'AAAA') &&
            password.value.length === 0) {
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

    $('#recordListNameSubmit').addEventListener('click', () => {
        const r = $('#recordListNamePopup').getAttribute('data-rid');
        dns.record.updateName(r, $('#recordListName').value);
    });

    $('#recordListContentSubmit').addEventListener('click', () => {
        const r = $('#recordListContentPopup').getAttribute('data-rid');
        dns.record.updateContent(r, $('#recordListContent').value);
    });

    $('#recordListPasswordSubmit').addEventListener('click', () => {
        const r = $('#recordListPasswordPopup').getAttribute('data-rid');
        dns.record.updatePassword(r, $('#recordListPassword').value);
    });

    $('#recordListTTLSubmit').addEventListener('click', () => {
        const r = $('#recordListTTLPopup').getAttribute('data-rid');
        dns.record.updateTTL(r, $('#recordListTTL').value);
    });

    dns.record.list();
};
