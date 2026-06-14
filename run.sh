#!/usr/bin/env bash
cd "$(dirname "$0")"
export PYTHONPATH="$(pwd)"
export SSL_CERT_FILE="$(.venv/bin/python -c 'import certifi; print(certifi.where())')"
export REQUESTS_CA_BUNDLE="$SSL_CERT_FILE"
exec .venv/bin/python "$@"
