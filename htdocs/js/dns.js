function $(sel) { return document.querySelector(sel); }
function $$(sel) { return Array.from(document.querySelectorAll(sel)); }

function initPage()
{
    dns = {};
    dns.loadRemote = new LoadRemote();

	document.body.addEventListener('initReady', function() {
		if (typeof(window.initPageSpecific) == 'function')
			window.initPageSpecific();
	});

    dns.user = new User();

	dns.createRandomString = function (length)
	{
		var ret = [];
		var possible = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
		for (var i = 0; i < length; i++)
			ret.push(possible.charAt(Math.floor(Math.random() * possible.length)));
		return ret.join('');
	};

	dns.fehler = function (el)
	{
		el.classList.add('falsch');
		window.setTimeout(function () {
			el.classList.remove('falsch');
		}, 2000);
	};

	dns.toggleCB = function (el)
	{
		el.querySelectorAll('input[type="checkbox"]').forEach(function (cb) {
			cb.checked = !cb.checked;
		});
	};

	dns.alsZeit = function (t)
	{
		var d = new Date(parseInt(t) * 1000);
		return "%04d-%02d-%02d %02d:%02d:%02d".format(
			d.getFullYear(), d.getMonth() + 1, d.getDate(),
			d.getHours(), d.getMinutes(), d.getSeconds()
		);
	};

	$$('.popupAbort').forEach(function (el) {
		el.addEventListener('click', function () {
			this.closest('.popup').style.display = 'none';
		});
	});

	var loginName = $('#login_name');
	var loginPassword = $('#login_password');
	if (loginName) {
		loginName.addEventListener('keyup', function (e) {
			if (e.keyCode == 13) $('#login_submit').click();
		});
	}
	if (loginPassword) {
		loginPassword.addEventListener('keyup', function (e) {
			if (e.keyCode == 13) $('#login_submit').click();
		});
	}
}

function createSpinner(options)
{
	var opts = {
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

function LoadRemote()
{
	var self = this;

	this._activeProgresses = {};

	this._displayStart = function (xhr, settings)
    {
		if (!(settings.progressElement && settings.successElement && settings.errorElement))
            return;
		var prog = self._activeProgresses[xhr.reqID];
		if (prog)
        {
			if (prog.settings.progressElement)
            {
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

		var item = {
			settings: settings,
			spinner: createSpinner(settings.spinner)
		};
		item.settings.progressElement.appendChild(item.spinner.el);
		item.settings.successElement.style.display = 'none';
		item.settings.errorElement.style.display = 'none';
		self._activeProgresses[xhr.reqID] = item;
	};

	this._displayComplete = function(xhr, success)
    {
		var item = this._activeProgresses[xhr.reqID];
		if (!item || !(item.settings.progressElement && item.settings.successElement && item.settings.errorElement))
            return;
		item.spinner.stop();
		if (item.settings.detach) item.settings.progressElement.remove();
		var i, j;
		if (success)
        {
			i = item.settings.successElement;
			j = item.settings.errorElement;
		}
        else
        {
			i = item.settings.errorElement;
			j = item.settings.successElement;
		}
		item.settings.successElement.src = 'img/success.png';
		item.settings.successElement.title = Jed.sprintf(i18n.pgettext('LoadRemoteStatus', 'success (%s)'), item.settings.module);
		item.settings.successElement.alt = i18n.pgettext('LoadRemoteStatus', 'success');

		item.settings.errorElement.src = 'img/error.png';
		item.settings.errorElement.title = Jed.sprintf(i18n.pgettext('LoadRemoteStatus', 'error (%s)'), item.settings.module);
		item.settings.errorElement.alt = i18n.pgettext('LoadRemoteStatus', 'error');

		i.style.display = '';
		if (item.settings.detach)
            j.remove();
		if (item.settings.timeoutElement == undefined || item.settings.timeoutElement >= 0)
        {
			window.setTimeout(function () {
				i.style.display = 'none';
				if (item.settings.detach)
                    i.remove();
			}, item.settings.timeoutElement == undefined ? (success ? 2000 : 10000) : item.settings.timeoutElement);
		}
	};

	this.loadRemote = function(module, query, callback, settings)
    {
		if (settings == undefined)
            settings = {insertInDiv: $('#loadProgresses')};

		settings.module = module;

		if (settings.insertAfterButton || settings.insertInDiv)
        {
			settings.progressElement = document.createElement('span');
			settings.progressElement.className = 'optionButtonProgress';
			settings.successElement = document.createElement('img');
			settings.successElement.className = 'optionButtonSuccess';
			settings.errorElement = document.createElement('img');
			settings.errorElement.className = 'optionButtonError';
			settings.detach = true;
		}
		if (settings.insertAfterButton)
        {
			var ref = settings.insertAfterButton;
			ref.after(settings.progressElement, settings.successElement, settings.errorElement);
		}
		if (settings.insertInDiv)
        {
			settings.insertInDiv.appendChild(settings.errorElement);
			settings.insertInDiv.appendChild(settings.successElement);
			settings.insertInDiv.appendChild(settings.progressElement);
		}

		var reqID = hex_md5(module + '?' + JSON.stringify(query));
		var fakeXhr = { reqID: reqID };
		self._displayStart(fakeXhr, settings);

		var body = new URLSearchParams();
		body.append('q', JSON.stringify(query));

		var controller = new AbortController();
		if (settings.timeout) {
			setTimeout(function() { controller.abort(); }, settings.timeout);
		}

		fetch('rpc.php/' + module, {
			method: 'POST',
			body: body,
			signal: controller.signal
		}).then(function(response) {
			return response.json();
		}).then(function(data) {
			var success = (data && data.status == 'ok');
			self._displayComplete(fakeXhr, success);
			if (typeof(callback) == 'function')
				callback(data, success);
		}).catch(function() {
			self._displayComplete(fakeXhr, 0);
			if (typeof(callback) == 'function')
				callback(undefined, 0);
		});
	};
}

function User()
{
    var self = this;

    this.loadUser = function (callback)
    {
        dns.loadRemote.loadRemote(
            'user/getInfo',
            [],
            function (data, success)
            {
                if (success)
                {
                    self.updateUser(data.user);
                    self.userLevels = data.userLevels;
                    if (callback)
                        callback();
                }
                else
                {
                    alert(i18n.pgettext('UserGetInfo', 'Connection problem...'));
                }
            }, {
                insertInDiv: $('#loadProgresses')
            }
        );
    };

    this.updateUserInfo = function ()
    {
        $('#usertext').textContent = self.user.username;
        $('#userlevel').textContent = self.user.level;
    };

    this.doLogin = function (user, password)
    {
        dns.loadRemote.loadRemote(
            'user/login',
            [user, password],
            function (data, success) {
				if (success) {
					window.location.reload();
				}
				else
				{
					dns.fehler($('#login_password'));
				}
            }, {
                insertInDiv: $('#loadProgresses')
            }
        );
    };

    this.doLogout = function ()
    {
        dns.loadRemote.loadRemote(
            'user/logout',
            [],
            function () {
				window.location.reload();
            }, {
                insertInDiv: $('#loadProgresses')
            }
        );
    };

    this.updateUser = function (userData)
    {
        self.user = userData;
        self.updateUserInfo();
    };

    this.__init__ = function ()
    {
        var changeShow = $('#currentUserChangeShow');
        if (changeShow) {
            changeShow.addEventListener('click', function () {
                var el = $('#login_change');
                el.style.display = el.style.display === 'none' ? '' : 'none';
            });
        }
        var loginSubmit = $('#login_submit');
        if (loginSubmit) {
            loginSubmit.addEventListener('click', function () {
                self.doLogin($('#login_name').value, $('#login_password').value);
            });
        }
        var logoutSubmit = $('#logout_submit');
        if (logoutSubmit) {
            logoutSubmit.addEventListener('click', function () {
                self.doLogout();
            });
        }
        document.body.dispatchEvent(new Event('initReady'));
    };

    this.loadUser(this.__init__);
}
