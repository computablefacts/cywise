#!/bin/bash

CISO_ASSISTANT=/opt/a_src/ciso-assistant-community/tools
TOWERIFY=/opt/a_src/towerify/database/seeders/frameworks

rm $TOWERIFY/anssi/* \
  $TOWERIFY/dora/* \
  $TOWERIFY/gdpr/* \
  $TOWERIFY/ncsc/* \
  $TOWERIFY/nis/* \
  $TOWERIFY/nis2/* \
  $TOWERIFY/nist/* \
  $TOWERIFY/owasp/*

php artisan framework:prepare $CISO_ASSISTANT/anssi/ $TOWERIFY/anssi/
php artisan framework:prepare $CISO_ASSISTANT/dora/ $TOWERIFY/dora/
php artisan framework:prepare $CISO_ASSISTANT/gdpr/ $TOWERIFY/gdpr/
php artisan framework:prepare $CISO_ASSISTANT/ncsc/ $TOWERIFY/ncsc/
php artisan framework:prepare $CISO_ASSISTANT/NIS/ $TOWERIFY/nis/
php artisan framework:prepare $CISO_ASSISTANT/NIS2/ $TOWERIFY/nis2/
php artisan framework:prepare $CISO_ASSISTANT/nist/ $TOWERIFY/nist/
php artisan framework:prepare $CISO_ASSISTANT/owasp/ $TOWERIFY/owasp/

find "$TOWERIFY" -name "*.jsonl" -type f -exec gzip {} \;
