window.initPageSpecific = function () {
    dns.record = {
        'add': function (domain, type, name, content, password, ttl) {
            dns.loadRemote.loadRemote('domains/addRecord',
                [domain, type, name, content, password, ttl],
                function (data, success) {
                    dns.record.list();
                    if (success)
                        $('#addRecord_button').trigger('click');
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
                    var $table = $('#recordList');
                    $table.find('tr:not(:first)').detach();
                    for (var i in data.data) {
                        if (!data.data.hasOwnProperty(i)) continue;
                        $table.append('<tr data-rid="%d" data-rName="%s" data-rContent="%s" data-rTtl="%d"> \
									<td>%d</td> \
									<td>%s</td> \
									<td>%s</td> \
									<td>%s</td> \
									<td>%s</td> \
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
                            '<span class="table_password" data-p="%s">%s</span>'.format(
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
                    $table.find('.table_password').one('click', function () {
                        //noinspection JSPotentiallyInvalidUsageOfThis
                        this.innerHTML = this.getAttribute('data-p');
                    });

                    $table.find('.recordListDel').on('click', function () {
                        var r = $(this).parents('tr').attr('data-rid');
                        if (confirm(Jed.sprintf(i18n.pgettext('RecordListRemove', "Remove record %d?"), r)))
                            dns.record.del(r);
                        return false;
                    });

                    $table.find('.recordListName').on('click', function () {
                        $('.popup').not(this).hide();
                        var $this = $(this);
                        var pos = $this.offset();
                        var r = $this.parents('tr').attr('data-rid');
                        $('#recordListName').val($this.parents('tr').attr('data-rName'));
                        $('#recordListNamePopup')
                            .attr('data-rid', r)
                            .css('left', pos.left - 60)
                            .css('top', pos.top + 20)
                            .show();
                        return false;
                    });

                    $table.find('.recordListContent').on('click', function () {
                        $('.popup').not(this).hide();
                        var $this = $(this);
                        var pos = $this.offset();
                        var r = $this.parents('tr').attr('data-rid');
                        $('#recordListContent').val($this.parents('tr').attr('data-rContent'));
                        $('#recordListContentPopup')
                            .attr('data-rid', r)
                            .css('left', pos.left - 60)
                            .css('top', pos.top + 20)
                            .show();
                        return false;
                    });

                    $table.find('.recordListPassword').on('click', function () {
                        $('.popup').not(this).hide();
                        var $this = $(this);
                        var pos = $this.offset();
                        var r = $this.parents('tr').attr('data-rid');
                        $('#recordListPassword').val(dns.createRandomString(32));
                        $('#recordListPasswordPopup')
                            .attr('data-rid', r)
                            .css('left', pos.left - 60)
                            .css('top', pos.top + 20)
                            .show();
                        return false;
                    });

                    $table.find('.recordListTTL').on('click', function () {
                        $('.popup').not(this).hide();
                        var $this = $(this);
                        var pos = $this.offset();
                        var r = $this.parents('tr').attr('data-rid');
                        $('#recordListTTL').val($this.parents('tr').attr('data-rTtl'));
                        $('#recordListTTLPopup')
                            .attr('data-rid', r)
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
                        $('#recordListNamePopup').hide();
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
                        $('#recordListPasswordPopup').hide();
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
                        $('#recordListContentPopup').hide();
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
                        $('#recordListTTLPopup').hide();
                    dns.record.list();
                },
                {insertInDiv: $('#loadProgresses')}
            );
        }
    };

    dns.domainOptionList = function ($select) {
        dns.loadRemote.loadRemote('domains/miniList',
            [],
            function (data, success) {
                if (!success)
                    return;
                $select.empty();
                for (var i in data.data) {
                    if (!data.data.hasOwnProperty(i)) continue;
                    $select.append('<option value="%d">%s</option>'.format(
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

    $('#addRecord_button').on('click', function () {
        var div = $('#addRecord');
        div.toggleClass('active');
        if (div.hasClass('active')) {
            document.getElementById('addRecordType').selectedIndex = 0;
            $('#addRecordType').trigger('change');
            $('#addRecordPassword').val(dns.createRandomString(32));
            dns.domainOptionList($('#addRecordDomain'));
        }
    });

    $('#addRecordType').on('change', function () {
        var p = $('#addRecord_d_password');
        var l_content = $('label[for="addRecordContent"]');
        var c = $('#addRecordContent');
        var ttl = $('#addRecordTTL');
        switch (this.value) {
            case 'A':
                p.show();
                l_content.text('IPv4:');
                ttl.val(60);
                dns.loadRemote.loadRemote('user/myIP',
                    [],
                    function (data, success) {
                        if (!success)
                            return;
                        $('#addRecordContent').val(data.data[0]);
                    },
                    {
                        insertInDiv: $('#loadProgresses')
                    }
                );
                break;
            case 'AAAA':
                p.show();
                l_content.text(i18n.pgettext('AddRecordTypeFields', 'IPv6:'));
                c.val('');
                ttl.val(120);
                break;
            case 'CNAME':
                p.hide();
                l_content.text(i18n.pgettext('AddRecordTypeFields', 'Original URI:'));
                c.val('');
                ttl.val(86400);
                break;
        }
        $('#addRecordName').trigger('keyup');
    });

    $('#addRecordName').on('keyup', function () {
        dns.loadRemote.loadRemote('domains/recordTest',
            [
                $('#addRecordDomain').val(),
                $('#addRecordName').val(),
                $('#addRecordType').val()
            ],
            function (data, success) {
                var $testSpan = $('#addRecordTest');
                if (!success || !data.data) {
                    $testSpan.hide();
                    return;
                }
                $testSpan
                    .removeClass()
                    .attr('title', data.data.status)
                    .show()
                    .text("%s: %s".format(data.data.type, data.data.domain));
                if (data.data.free)
                    $testSpan.addClass('frei');
                else if (data.data.invalid)
                    $testSpan.addClass('invalid')
                else
                    $testSpan.addClass('belegt');
            }
        );
    });

    $('#addRecordSubmit').on('click', function () {
        var type = $('#addRecordType').val();
        var name = $('#addRecordName');
        var content = $('#addRecordContent');
        var password = $('#addRecordPassword');
        var ttl = $('#addRecordTTL');
        var ok = true;
        if (name.val().length < 1) {
            dns.fehler(name);
            ok = false;
        }
        if (content.val().length == 0) {
            dns.fehler(content);
            ok = false;
        }
        if (isNaN(parseInt(ttl.val()))) {
            dns.fehler(ttl);
            ok = false;
        }
        if ((type == 'A' || type == 'AAAA') &&
            password.length == 0) {
            dns.fehler(password);
            ok = false;
        }
        if (!ok)
            return;
        dns.record.add(
            $('#addRecordDomain').val(),
            type,
            name.val(),
            content.val(),
            password.val(),
            ttl.val()
        );
    });

    $('#recordListReload').on('click', dns.record.list);

    $('#recordListNameSubmit').on('click', function () {
        var r = $('#recordListNamePopup').attr('data-rid');
        dns.record.updateName(r, $('#recordListName').val());
    });

    $('#recordListContentSubmit').on('click', function () {
        var r = $('#recordListContentPopup').attr('data-rid');
        dns.record.updateContent(r, $('#recordListContent').val());
    });

    $('#recordListPasswordSubmit').on('click', function () {
        var r = $('#recordListPasswordPopup').attr('data-rid');
        dns.record.updatePassword(r, $('#recordListPassword').val());
    });

    $('#recordListTTLSubmit').on('click', function () {
        var r = $('#recordListTTLPopup').attr('data-rid');
        dns.record.updateTTL(r, $('#recordListTTL').val());
    });

    dns.record.list();
};
