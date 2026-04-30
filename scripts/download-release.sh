#!/usr/bin/env bash
set -euo pipefail

if [[ $# -lt 2 ]]; then
  echo "Usage: $0 <owner/repo> <tag>"
  echo "Example: $0 piyush/codeigniter-tutorial v1.0.0"
  exit 1
fi

REPO="$1"
TAG="$2"
APP_NAME="codeigniter-tutorial"
ARCHIVE_NAME="${APP_NAME}-${TAG}.tar.gz"
URL="https://github.com/${REPO}/releases/download/${TAG}/${ARCHIVE_NAME}"

mkdir -p dist/releases
curl -fL "${URL}" -o "dist/releases/${ARCHIVE_NAME}"
tar -xzf "dist/releases/${ARCHIVE_NAME}" -C dist/releases

echo "Downloaded and unpacked dist/releases/${ARCHIVE_NAME}"
