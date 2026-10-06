#!/bin/bash
set -e
: "${FPPDIR:=/opt/fpp}"
. "${FPPDIR}/scripts/common"
echo "$(date '+%Y-%m-%d %H:%M:%S') Ferris Jukebox uninstalled" >> "${LOGDIR}/plugin-fpp-ferris-jukebox.log"
exit 0
