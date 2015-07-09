#!/usr/bin/env bash

cd "$(dirname $0)/htdocs"

find -name '*.php' -exec xgettext --keyword="pgettext:1c,2" -c --from-code=UTF-8 -o locale/templates/php.pot {} \+
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
