#!/usr/bin/env bash
ALP=2.8.2
AMH=1.2.2
ATDA=1.1.0
TUL=5.2.0
wget -O alpine-${ALP}.min.js https://cdn.jsdelivr.net/gh/alpinejs/alpine@${ALP}/dist/alpine.min.js
wget -O alpine-magic-helpers-${AMH}.min.js https://cdn.jsdelivr.net/gh/alpine-collective/alpine-magic-helpers@${AMH}/dist/index.min.js
wget -O alpine-turbo-drive-adapter-${ATDA}.min.js https://cdn.jsdelivr.net/npm/alpine-turbo-drive-adapter@${ATDA}/dist/alpine-turbo-drive-adapter.min.js
#wget -O turbolinks-${TUL}.min.js https://cdn.jsdelivr.net/npm/turbolinks@${TUL}/dist/turbolinks.min.js
