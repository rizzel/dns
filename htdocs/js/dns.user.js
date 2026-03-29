window.initPageSpecific = () => {
    const p1 = $('#password1');
    const p2 = $('#password2');
    const email = $('#email');
    const token = $('#token');

    dns.user = {
        updatePassword(password) {
            dns.loadRemote.loadRemote('user/updatePasswordSelf',
                [password],
                (data, success) => {
                    if (success)
                        alert(i18n.pgettext('EmailVerification', "Verification email has been sent."));
                }
            );
        },

        updateEmail(email) {
            dns.loadRemote.loadRemote('user/updateEmailSelf',
                [email],
                (data, success) => {
                    if (success)
                        alert(i18n.pgettext('EmailVerification', "Verification email has been sent."));
                }
            );
        },

        verifyToken(token) {
            dns.loadRemote.loadRemote('user/verifyToken',
                [token],
                (data, success) => {
                    if (success) {
                        alert(i18n.pgettext('EmailVerification', "Token successfully verified."));
                        window.location.reload();
                    } else {
                        alert(i18n.pgettext('EmailVerification', "Error verifying token - token invalid"));
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

    $('#password_submit').addEventListener('click', () => {
        if (p1.value !== p2.value) {
            dns.fehler(p1);
            dns.fehler(p2);
            return;
        }
        dns.user.updatePassword(p1.value);
    });

    $('#email_submit').addEventListener('click', () => {
        if (!email.value.match(/@/) || email.value.length <= 3) {
            dns.fehler(email);
            return;
        }
        dns.user.updateEmail(email.value);
    });

    $('#token_submit').addEventListener('click', () => {
        if (token.value.length === 0) {
            dns.fehler(token);
            return;
        }
        dns.user.verifyToken(token.value);
    });
};
