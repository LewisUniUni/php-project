#!/bin/sh
set -eu

root=$(CDPATH= cd -- "$(dirname "$0")/../.." && pwd)
bindir="$root/tools/ci/bin"
mkdir -p "$bindir"
tmp=$(mktemp -d)
trap 'rm -rf "$tmp"' EXIT

download() {
    url=$1
    file=$2
    checksum=$3
    curl -fsSL -o "$tmp/$file" "$url"
    echo "$checksum  $tmp/$file" | sha256sum -c -
}

download \
    "https://github.com/gitleaks/gitleaks/releases/download/v8.30.1/gitleaks_8.30.1_linux_x64.tar.gz" \
    gitleaks.tar.gz \
    "551f6fc83ea457d62a0d98237cbad105af8d557003051f41f3e7ca7b3f2470eb"
tar -xzf "$tmp/gitleaks.tar.gz" -C "$bindir" gitleaks

download \
    "https://github.com/rhysd/actionlint/releases/download/v1.7.12/actionlint_1.7.12_linux_amd64.tar.gz" \
    actionlint.tar.gz \
    "8aca8db96f1b94770f1b0d72b6dddcb1ebb8123cb3712530b08cc387b349a3d8"
tar -xzf "$tmp/actionlint.tar.gz" -C "$bindir" actionlint

download \
    "https://github.com/hadolint/hadolint/releases/download/v2.15.1/hadolint-linux-x86_64" \
    hadolint \
    "c7187db94eeeeca956519a6af171adc31453941a1e777961f6e680f697c8c507"
mv "$tmp/hadolint" "$bindir/hadolint"

chmod +x "$bindir/gitleaks" "$bindir/actionlint" "$bindir/hadolint"
