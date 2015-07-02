if (!Array.prototype.map)
{
    Array.prototype.map = function (fun)
    {
        var len = this.length;
        if (typeof fun != 'function') throw new TypeError();

        var res = new Array(len);
        var thisp = arguments[1];
        for (var i = 0; i < len; i++)
        {
            if (i in this) res[i] = fun.call(thisp, this[i], i, this);
        }
        return res;
    }
}

function initPage()
{
    dns = {};
    dns.loadRemote = new LoadRemote();

	$('body').bind('initReady', function() {
		if (typeof(initPageSpecific) == 'function')
			initPageSpecific();
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

	dns.fehler = function ($f)
	{
		$f.addClass('falsch');
		window.setTimeout(function () {
			$f.removeClass('falsch');
		}, 2000);
	};

	dns.toggleCB = function ($cb)
	{
		$cb = $cb.find('input[type="checkbox"]');
		$cb.each(function () {
			var $t = $(this);
			$t.prop('checked', !$t.prop('checked'));
		});
	};

	dns.alsZeit = function (t)
	{
		var d = new Date(parseInt(t) * 1000);
		return "%04d-%02d-%02d %02d:%02d:%02d".format(
			d.getFullYear(), d.getMonth() + 1, d.getDate(),
			d.getHours(), d.getMinutes(), d.getSeconds()
		);
	}

	$('.popupAbort').on('click', function () {
		$(this).parents('.popup').hide();
	});

	$('#login_name, #login_password').on('keyup', function (e) {
		if (e.keyCode == 13)
			$('#login_submit').trigger('click');
	});
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
	if (options) $.extend(opts, options);
	return new Spinner(opts).spin();
}

function LoadRemote()
{
	/* Usage:
	loadRemote.loadRemote(
	'manga/listItemAdd',
	[
		$this.parents('.option').attr('type'),
		$this.parent().find('.optionAddText').val()
	], function(data, success) {
		if (success) {
			Configuration.loadConfiguration();
		}
	}, settings
	);

	settings can be:
	settings = {
		progressElement: $('<span class="optionButtonProgress"></span>').insertAfter($this),
		successElement: $('<img class="optionButtonSuccess" />').insertAfter($this),
		errorElement: $('<img class="optionButtonError" />').insertAfter($this),
		detach: true,
		timeout: 10000,
		timeoutElement: 2000
	}
	or:
	settings = {
		insertAfterButton: $this
	}
	or (default):
	settings = {
		insertInDiv: $('#loadProgresses')
	}
	-> those set progressElement, successElement, errorElement and detach as in the first example
	*/
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
				prog.settings.progressElement.detach();
			}
			if (prog.settings.successElement)
                prog.settings.successElement.detach();
			if (prog.settings.errorElement)
                prog.settings.errorElement.detach();
		}

		if (!settings.spinner)
            settings.spinner = {};
		settings.spinner.top = '22px';
		settings.spinner.left = '22px';

		var item = {
			settings: settings,
			spinner: createSpinner(settings.spinner)
		};
		item.settings.progressElement.append(item.spinner.el);
		item.settings.successElement.hide();
		item.settings.errorElement.hide();
		self._activeProgresses[xhr.reqID] = item;
	};

	this._displayComplete = function(xhr, success)
    {
		var item = this._activeProgresses[xhr.reqID];
		if (!item || !(item.settings.progressElement && item.settings.successElement && item.settings.errorElement))
            return;
		item.spinner.stop();
		if (item.settings.detach) item.settings.progressElement.detach();
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
		item.settings.successElement.attr('src', 'img/success.png').attr('title', 'success (%s)'.format(item.settings.module)).attr('alt', 'success');
		item.settings.errorElement.attr('src', 'img/error.png').attr('title', 'error (%s)'.format(item.settings.module)).attr('alt', 'error');
		i.show();
		if (item.settings.detach)
            j.detach();
		if (item.settings.timeoutElement == undefined || item.settings.timeoutElement >= 0)
        {
			window.setTimeout(function () {
				i.hide();
				if (item.settings.detach)
                    i.detach();
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
			settings.progressElement = $('<span class="optionButtonProgress"></span>');
			settings.successElement = $('<img class="optionButtonSuccess" />');
			settings.errorElement = $('<img class="optionButtonError" />');
			settings.detach = true;
		}
		if (settings.insertAfterButton)
        {
			settings.insertAfterButton.after(settings.progressElement, settings.successElement, settings.errorElement);
		}
		if (settings.insertInDiv)
        {
			settings.insertInDiv.append(settings.errorElement, settings.successElement, settings.progressElement);
		}

		//query = query.map(function(a) {return encodeURIComponent(a);}).join('&');
		//console.debug(query);
		//var realQuery = [];
		//for(var q in query) {
		//	realQuery.push(query[q] + '=1');
		//}
		//realQuery = realQuery.join('&');

		var xhr = $.ajax('pingback/pingback.php/' + module, {
			cache: settings.cache,
			data: {q: JSON.stringify(query)},
			type: 'post',
			error: function(xhr)
            {
				self._displayComplete(xhr, 0);
				if (typeof(callback) == 'function')
					callback(undefined, 0);
			},
			success: function(data, status, xhr)
            {
				var success = (data && data.status == 'ok');
				self._displayComplete(xhr, success);
				if (typeof(callback) == 'function')
					callback(data, success);
			},
			timeout: settings.timeout
		});
		xhr.reqID = hex_md5(module + '?' + JSON.stringify(query));
		self._displayStart(xhr, settings);
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
                    alert('Connection problem...');
                }
            }, {
                insertInDiv: $('#loadProgresses')
            }
        );
    };

    this.updateUserInfo = function ()
    {
        $('#usertext').text(self.user.username);
        $('#userlevel').text(self.user.level);
    };

    this.doLogin = function (user, password)
    {
        dns.loadRemote.loadRemote(
            'user/login',
            [user, password],
            function (data, success) {
                //self.updateUser(data.user);
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
        var b = $('#logout_submit');
        dns.loadRemote.loadRemote(
            'user/logout',
            [],
            function (data, success)
            {
                //self.updateUser(data.user);
				window.location.reload();
            }, {
                insertInDiv: $('#loadProgresses')
            }
        );
    };

    this.updateUser = function (userdata)
    {
        self.user = userdata;
        self.updateUserInfo();
    };

    this.__init__ = function ()
    {
        $('#currentUserChangeShow').bind('click', function () {
            var $this = $(this);
            $('#login_change').toggle();
        });
        $('#login_submit').bind('click', function () {
            self.doLogin(
                $('#login_name').val(),
                $('#login_password').val()
            );
        });
        $('#logout_submit').bind('click', function () {
            self.doLogout();
        });
        $('body').trigger('initReady');
    };

    this.loadUser(this.__init__);
}
