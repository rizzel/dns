#!/usr/bin/env bash

BASEFOLDER=$(dirname $(readlink -f $0))

cd "$BASEFOLDER/htdocs"

find -name '*.php' -exec xgettext -L PHP --keyword="pgettext:1c,2" -c --from-code=UTF-8 -o locale/templates/php.pot {} \+
find -name '*.js' -exec xgettext -L JavaScript -c --from-code=UTF-8 -o locale/templates/js.pot {} \+

for i in locale/*; do
    if [ -d "$i" ] && [ "locale/templates" != "$i" ]; then
        PO_LANGUAGE=${i##*/}
        echo "Language: $PO_LANGUAGE"
        PO_PHP="$i/LC_MESSAGES/php.po"
        if [ -f "$PO_PHP" ]; then
            msgmerge -U -N "$PO_PHP" "locale/templates/php.pot"
        else
            msginit -l $PO_LANGUAGE -i "locale/templates/php.pot" -o "$PO_PHP"
        fi

        PO_JS="$i/LC_MESSAGES/js.po"
        if [ -f "$PO_JS" ]; then
            msgmerge -U -N "$PO_JS" "locale/templates/js.pot"
        else
            msginit -l $PO_LANGUAGE -i "locale/templates/js.pot" -o "$PO_JS"
        fi
    fi
done

for i in locale/*; do
    if [ -d "$i" ] && [ "locale/templates" != "$i" ]; then
        PO_JS="$i/LC_MESSAGES/js"
        poedit "$i/LC_MESSAGES/php.po"
        poedit "${PO_JS}.po"

        "$BASEFOLDER/po2json/node_modules/po2json/bin/po2json" "${PO_JS}.po" "${PO_JS}.json" -f jed -d js
    fi
done
