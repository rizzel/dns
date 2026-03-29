window.initPageSpecific = () => {
    const p1 = $('#forgotten2_password1');
    const p2 = $('#forgotten2_password2');
    const nameEl = $('#forgotten_name');
    const emailEl = $('#forgotten_email');
    const tokenEl = $('#forgotten2_token');

    dns.user = {
        name: undefined,

        requestToken(name, email) {
            dns.loadRemote.loadRemote('user/forgottenRequest',
                [name, email],
                () => {
                    $('#forgotten2').style.display = '';
                }
            );
        },

        verifyToken(token, password) {
            if (dns.user.name === undefined) {
                alert(i18n.pgettext('VerifyToken', "Please try again."));
                return;
            }
            dns.loadRemote.loadRemote('user/forgottenResponse',
                [dns.user.name, token, password],
                (data, success) => {
                    if (success) {
                        alert(i18n.pgettext('VerifyToken', "Password has been reset"));
                        window.location = '/index.php';
                    } else {
                        alert(i18n.pgettext('VerifyToken', "Error setting the password"));
                        window.location.reload();
                    }
                }
            );
        }
    };

    [p1, p2].forEach((el) => {
        el.addEventListener('keyup', () => {
            $('#user_add_nomatch').style.display = p1.value !== p2.value ? '' : 'none';
        });
    });

    $('#forgotten2_submit').addEventListener('click', () => {
        let ok = true;
        if (tokenEl.value.length < 3) {
            dns.fehler(tokenEl);
            ok = false;
        }
        if (p1.value !== p2.value) {
            dns.fehler(p1);
            dns.fehler(p2);
            ok = false;
        }
        if (!ok)
            return;
        if (dns.user.name === undefined && nameEl.value.length > 0)
            dns.user.name = nameEl.value;
        dns.user.verifyToken(tokenEl.value, p1.value);
    });

    $('#forgotten_submit').addEventListener('click', () => {
        let ok = true;
        if (nameEl.value.length < 1) {
            dns.fehler(nameEl);
            ok = false;
        }
        if (!emailEl.value.match(/@/) || emailEl.value.length <= 3) {
            dns.fehler(emailEl);
            ok = false;
        }
        if (!ok)
            return;
        dns.user.name = nameEl.value;
        dns.user.requestToken(nameEl.value, emailEl.value);
    });
};
