#!/usr/bin/env bash
#
# Semeia o ambiente local com o conteudo de demonstracao.
# Requer o ambiente de pe: rode 'npm start' antes.

set -euo pipefail

cd "$(dirname "$0")/.."

if ! npx wp-env run cli wp core is-installed >/dev/null 2>&1; then
	echo "O WordPress nao respondeu. Rode 'npm start' e tente de novo." >&2
	exit 1
fi

npx wp-env run cli wp eval-file wp-content/seed/seed.php

porta="${WP_ENV_PORT:-8888}"

echo
echo "Site:  http://localhost:${porta}/"
echo "Admin: http://localhost:${porta}/wp-admin/ (admin / password)"
