#!/bin/sh
# Installs the PHP of an end-of-life distribution from its archive.
# usage: eol-php.sh <debian|ubuntu> <suite> <expected version prefix>
set -e

DISTRO="$1"
SUITE="$2"
EXPECTED="$3"

if [ "$DISTRO" = 'ubuntu' ]; then
    printf 'deb http://archive.ubuntu.com/ubuntu %s main universe\ndeb http://archive.ubuntu.com/ubuntu %s-updates main universe\n' "$SUITE" "$SUITE" > /etc/apt/sources.list
    printf 'Acquire::Check-Valid-Until "false";\n' > /etc/apt/apt.conf.d/99eol
    PACKAGES='php5-cli php5-curl ca-certificates'
else
    printf 'deb http://archive.debian.org/debian %s main\ndeb http://archive.debian.org/debian-security %s/updates main\n' "$SUITE" "$SUITE" > /etc/apt/sources.list
    printf 'Acquire::Check-Valid-Until "false";\n' > /etc/apt/apt.conf.d/99eol
    PACKAGES='php5-cli php5-curl ca-certificates'
fi

tries=3
until apt-get -qq update; do
    tries=$((tries - 1))
    if [ "$tries" -le 0 ]; then
        echo "apt-get update failed after three attempts" >&2
        exit 1
    fi
    sleep 20
done

apt-get -y --force-yes --allow-unauthenticated install $PACKAGES </dev/null

installed=$(php -r 'echo PHP_VERSION;')
echo "installed PHP $installed for $DISTRO $SUITE"
case "$installed" in
    "$EXPECTED"*) ;;
    *)
        echo "expected PHP $EXPECTED*, got $installed" >&2
        exit 1
        ;;
esac

php -r 'exit(extension_loaded("curl") && extension_loaded("dom") && extension_loaded("xml") ? 0 : 1);' \
    || { echo "curl, dom and xml are required" >&2; exit 1; }
