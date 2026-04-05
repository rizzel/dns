const $ = (sel) => document.querySelector(sel);
const $$ = (sel) => Array.from(document.querySelectorAll(sel));

function initPage() {
    dns = {};
    dns.loadRemote = new LoadRemote();

    document.body.addEventListener('initReady', () => {
        if (typeof window.initPageSpecific === 'function')
            window.initPageSpecific();
    });

    dns.user = new User();

    dns.createRandomString = (length) => {
        const possible = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        const values = new Uint32Array(length);
        crypto.getRandomValues(values);
        return Array.from(values, (v) => possible.charAt(v % possible.length)).join('');
    };

    dns.fehler = (el) => {
        el.classList.add('falsch');
        setTimeout(() => el.classList.remove('falsch'), 2000);
    };

    dns.toggleCB = (el) => {
        el.querySelectorAll('input[type="checkbox"]').forEach((cb) => {
            cb.checked = !cb.checked;
        });
    };

    dns.alsZeit = (t) => {
        if (!t) return '';
        const d = new Date(t.replace(' ', 'T'));
        return "%04d-%02d-%02d %02d:%02d:%02d".format(
            d.getFullYear(), d.getMonth() + 1, d.getDate(),
            d.getHours(), d.getMinutes(), d.getSeconds()
        );
    };

    $$('.popupAbort').forEach((el) => {
        el.addEventListener('click', function () {
            this.closest('.popup').style.display = 'none';
        });
    });

    const loginName = $('#login_name');
    const loginPassword = $('#login_password');
    if (loginName) {
        loginName.addEventListener('keyup', (e) => {
            if (e.key === 'Enter') $('#login_submit').click();
        });
    }
    if (loginPassword) {
        loginPassword.addEventListener('keyup', (e) => {
            if (e.key === 'Enter') $('#login_submit').click();
        });
    }
}

function createSpinner(options) {
    const opts = {
        lines: 11,
        length: 6,
        width: 2,
        radius: 4,
        rotate: 0,
        color: '#FF0000',
        speed: 0.7,
        trail: 30,
        shadow: false,
        hwaccel: false,
        className: 'spinner',
        top: 'auto',
        left: 'auto'
    };
    if (options) Object.assign(opts, options);
    return new Spinner(opts).spin();
}

class LoadRemote {
    constructor() {
        this._activeProgresses = {};
    }

    _displayStart(xhr, settings) {
        if (!(settings.progressElement && settings.successElement && settings.errorElement))
            return;
        const prog = this._activeProgresses[xhr.reqID];
        if (prog) {
            if (prog.settings.progressElement) {
                prog.spinner.stop();
                prog.settings.progressElement.remove();
            }
            if (prog.settings.successElement)
                prog.settings.successElement.remove();
            if (prog.settings.errorElement)
                prog.settings.errorElement.remove();
        }

        if (!settings.spinner)
            settings.spinner = {};
        settings.spinner.top = '22px';
        settings.spinner.left = '22px';

        const item = {
            settings,
            spinner: createSpinner(settings.spinner)
        };
        item.settings.progressElement.appendChild(item.spinner.el);
        item.settings.successElement.style.display = 'none';
        item.settings.errorElement.style.display = 'none';
        this._activeProgresses[xhr.reqID] = item;
    }

    _displayComplete(xhr, success) {
        const item = this._activeProgresses[xhr.reqID];
        if (!item || !(item.settings.progressElement && item.settings.successElement && item.settings.errorElement))
            return;
        item.spinner.stop();
        if (item.settings.detach) item.settings.progressElement.remove();

        const [shown, hidden] = success
            ? [item.settings.successElement, item.settings.errorElement]
            : [item.settings.errorElement, item.settings.successElement];

        item.settings.successElement.src = 'img/success.png';
        item.settings.successElement.title = Jed.sprintf(i18n.pgettext('LoadRemoteStatus', 'success (%s)'), item.settings.module);
        item.settings.successElement.alt = i18n.pgettext('LoadRemoteStatus', 'success');

        item.settings.errorElement.src = 'img/error.png';
        item.settings.errorElement.title = Jed.sprintf(i18n.pgettext('LoadRemoteStatus', 'error (%s)'), item.settings.module);
        item.settings.errorElement.alt = i18n.pgettext('LoadRemoteStatus', 'error');

        shown.style.display = '';
        if (item.settings.detach)
            hidden.remove();
        if (item.settings.timeoutElement === undefined || item.settings.timeoutElement >= 0) {
            setTimeout(() => {
                shown.style.display = 'none';
                if (item.settings.detach)
                    shown.remove();
            }, item.settings.timeoutElement === undefined ? (success ? 2000 : 10000) : item.settings.timeoutElement);
        }
    }

    loadRemote(module, query, callback, settings) {
        if (settings === undefined)
            settings = { insertInDiv: $('#loadProgresses') };

        settings.module = module;

        if (settings.insertAfterButton || settings.insertInDiv) {
            settings.progressElement = document.createElement('span');
            settings.progressElement.className = 'optionButtonProgress';
            settings.successElement = document.createElement('img');
            settings.successElement.className = 'optionButtonSuccess';
            settings.errorElement = document.createElement('img');
            settings.errorElement.className = 'optionButtonError';
            settings.detach = true;
        }
        if (settings.insertAfterButton) {
            const ref = settings.insertAfterButton;
            ref.after(settings.progressElement, settings.successElement, settings.errorElement);
        }
        if (settings.insertInDiv) {
            settings.insertInDiv.appendChild(settings.errorElement);
            settings.insertInDiv.appendChild(settings.successElement);
            settings.insertInDiv.appendChild(settings.progressElement);
        }

        const reqID = hex_md5(module + '?' + JSON.stringify(query));
        const fakeXhr = { reqID };
        this._displayStart(fakeXhr, settings);

        const body = new URLSearchParams();
        body.append('q', JSON.stringify(query));

        const controller = new AbortController();
        if (settings.timeout) {
            setTimeout(() => controller.abort(), settings.timeout);
        }

        const csrfMeta = document.querySelector('meta[name="csrf-token"]');
        const headers = csrfMeta ? { 'X-CSRF-Token': csrfMeta.content } : {};

        fetch('rpc.php/' + module, {
            method: 'POST',
            body,
            headers,
            signal: controller.signal
        }).then((response) => {
            return response.json();
        }).then((data) => {
            const success = (data && data.status === 'ok');
            this._displayComplete(fakeXhr, success);
            if (typeof callback === 'function')
                callback(data, success);
        }).catch(() => {
            this._displayComplete(fakeXhr, 0);
            if (typeof callback === 'function')
                callback(undefined, 0);
        });
    }
}

class User {
    constructor() {
        this.loadUser(() => this._init());
    }

    loadUser(callback) {
        dns.loadRemote.loadRemote(
            'user/getInfo',
            [],
            (data, success) => {
                if (success) {
                    this.updateUser(data.user);
                    this.userLevels = data.userLevels;
                    if (callback) callback();
                } else {
                    alert(i18n.pgettext('UserGetInfo', 'Connection problem...'));
                }
            },
            { insertInDiv: $('#loadProgresses') }
        );
    }

    updateUserInfo() {
        const usertext = $('#usertext');
        const userlevel = $('#userlevel');
        if (usertext) usertext.textContent = this.user.username;
        if (userlevel) userlevel.textContent = this.user.level;
    }

    doLogin(user, password) {
        dns.loadRemote.loadRemote(
            'user/login',
            [user, password],
            (data, success) => {
                if (success) {
                    window.location.reload();
                } else {
                    dns.fehler($('#login_password'));
                }
            },
            { insertInDiv: $('#loadProgresses') }
        );
    }

    doLogout() {
        dns.loadRemote.loadRemote(
            'user/logout',
            [],
            () => window.location.reload(),
            { insertInDiv: $('#loadProgresses') }
        );
    }

    updateUser(userData) {
        this.user = userData;
        this.updateUserInfo();
    }

    _init() {
        const changeShow = $('#currentUserChangeShow');
        if (changeShow) {
            changeShow.addEventListener('click', () => {
                const el = $('#login_change');
                el.style.display = el.style.display === 'none' ? '' : 'none';
            });
        }
        const loginSubmit = $('#login_submit');
        if (loginSubmit) {
            loginSubmit.addEventListener('click', () => {
                this.doLogin($('#login_name').value, $('#login_password').value);
            });
        }
        const logoutSubmit = $('#logout_submit');
        if (logoutSubmit) {
            logoutSubmit.addEventListener('click', () => this.doLogout());
        }
        document.body.dispatchEvent(new Event('initReady'));
    }
}
