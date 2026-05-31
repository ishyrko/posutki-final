#!/bin/sh
set -e

cp /opt/cron/crontab /etc/crontabs/root
chmod 600 /etc/crontabs/root

exec crond -f -l 2
