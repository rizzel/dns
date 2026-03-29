if (!String.prototype.format) {
    String.prototype.format = function (...args) {
        if (!args.length)
            return this;

        const htmlEsc = [/&/g, '&#38;', /"/g, '&#34;', /'/g, '&#39;', /</g, '&#60;', />/g, '&#62;'];
        const quotEsc = [/"/g, '&#34;', /'/g, '&#39;'];

        const esc = (s, r) => {
            for (let i = 0; i < r.length; i += 2)
                s = s.replace(r[i], r[i + 1]);
            return s;
        };

        let str = this.toString();
        let out = '';
        const re = /^(([^%]*)%('.|0|\x20)?(-)?(\d+)?(\.\d+)?(%|b|c|d|i|u|f|o|s|x|X|q|h|j|t|m))/;
        let numSubstitutions = 0;
        let a;

        while ((a = re.exec(str))) {
            const m = a[1];
            const leftpart = a[2], pPad = a[3], pJustify = a[4], pMinLength = a[5];
            const pPrecision = a[6], pType = a[7];

            let subst;

            if (pType === '%') {
                subst = '%';
            } else if (numSubstitutions < args.length) {
                const param = args[numSubstitutions++];

                let pad;
                if (pPad && pPad.substr(0, 1) === "'")
                    pad = leftpart.substr(1, 1);
                else if (pPad)
                    pad = pPad;
                else
                    pad = ' ';

                const precision = (pPrecision && pType === 'f')
                    ? parseInt(pPrecision.substring(1))
                    : -1;

                subst = param;

                switch (pType) {
                    case 'b':
                        subst = (parseInt(param) || 0).toString(2);
                        break;
                    case 'c':
                        subst = String.fromCharCode(parseInt(param) || 0);
                        break;
                    case 'd':
                    case 'i':
                        subst = (parseInt(param) || 0).toString();
                        break;
                    case 'u':
                        subst = Math.abs(parseInt(param) || 0);
                        break;
                    case 'f':
                        subst = (precision > -1)
                            ? (parseFloat(param) || 0.0).toFixed(precision)
                            : (parseFloat(param) || 0.0);
                        break;
                    case 'o':
                        subst = (parseInt(param) || 0).toString(8);
                        break;
                    case 's':
                        subst = param;
                        break;
                    case 'x':
                        subst = ('' + (parseInt(param) || 0).toString(16)).toLowerCase();
                        break;
                    case 'X':
                        subst = ('' + (parseInt(param) || 0).toString(16)).toUpperCase();
                        break;
                    case 'h':
                        subst = esc(param, htmlEsc);
                        break;
                    case 'q':
                        subst = esc(param, quotEsc);
                        break;
                    case 't': {
                        let ts = param || 0;
                        let tm = 0, th = 0, td = 0;
                        if (ts > 60) { tm = Math.floor(ts / 60); ts = ts % 60; }
                        if (tm > 60) { th = Math.floor(tm / 60); tm = tm % 60; }
                        if (th > 24) { td = Math.floor(th / 24); th = th % 24; }
                        subst = (td > 0)
                            ? String.format('%dd %dh %dm %ds', td, th, tm, ts)
                            : String.format('%dh %dm %ds', th, tm, ts);
                        break;
                    }
                    case 'm': {
                        const pr = pPrecision ? Math.floor(10 * parseFloat('0' + pPrecision)) : 2;
                        let val = (parseFloat(param || 0)).toFixed(pr).toString();
                        let len = val.indexOf('.');
                        if (len > -1)
                            val = val.replace('.', ',');
                        else
                            len = val.length;
                        for (let i = len - 3; i > 0; i -= 3)
                            val = val.substring(0, i) + '.' + val.substring(i);
                        subst = val;
                        break;
                    }
                }

                if (pMinLength) {
                    subst = subst.toString();
                    for (let i = subst.length; i < pMinLength; i++)
                        subst = pJustify === '-' ? subst + ' ' : pad + subst;
                }
            }

            out += leftpart + subst;
            str = str.substr(m.length);
        }

        return out + str;
    };
}
