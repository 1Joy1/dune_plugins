#!/bin/sh
echo -e "Content-Type: application/vnd.apple.mpegURL\n"


if (echo "$QUERY_STRING" | grep -q ".m3u8") then
echo "$(/codecpack/bin/wget  -q --no-check-certificate $QUERY_STRING -O - | sed "s|http|http://127.0.0.1/cgi-bin/plugins/CurrentTimeTV/current.sh?http|")"
else
/codecpack/bin/wget -q --no-check-certificate $QUERY_STRING  -O -
fi
