#!/usr/bin/env bash
# setup-puppeteer.sh
# Purpose: Prepare a headless Chrome environment for Puppeteer under a web user (default: www-data).
# Usage:
#   sudo bash setup-puppeteer.sh
# Optional envs:
#   WEB_USER=www-data RUNTIME_DIR=/var/cache/puppeteer bash setup-puppeteer.sh

set -euo pipefail

WEB_USER="${WEB_USER:-www-data}"
RUNTIME_DIR="${RUNTIME_DIR:-/var/cache/puppeteer}"

echo "==> Using WEB_USER=${WEB_USER}"
echo "==> Using RUNTIME_DIR=${RUNTIME_DIR}"

# 1) Create writable runtime directories
echo "==> Creating runtime directories..."
mkdir -p "${RUNTIME_DIR}/profiles" "${RUNTIME_DIR}/run" "${RUNTIME_DIR}/tmp" "${RUNTIME_DIR}/chrome"
chown -R "${WEB_USER}:${WEB_USER}" "${RUNTIME_DIR}"

# 2) Ensure npm/npx exists
if ! command -v npx >/dev/null 2>&1; then
  echo "ERROR: npx not found. Please install Node.js + npm first."
  echo "On Debian/Ubuntu: apt-get install -y nodejs npm (or use NodeSource)."
  exit 1
fi

# 3) Install Chrome for Puppeteer into shared cache (as WEB_USER)
echo "==> Installing Chrome for Puppeteer as ${WEB_USER} (this may take a minute)..."
sudo -u "${WEB_USER}" -H bash -lc "PUPPETEER_CACHE_DIR='${RUNTIME_DIR}' npx puppeteer browsers install chrome"

# 4) Create a stable symlink to the latest installed Chrome
echo '==> Creating stable symlink RUNTIME_DIR/chrome/current -> <installed chrome>'
CHROME_PATH=$(sudo -u "${WEB_USER}" -H bash -lc "find '${RUNTIME_DIR}' -type f -path '*/chrome-linux64/chrome' | sort | tail -n1")
if [ -z "${CHROME_PATH}" ]; then
  echo "ERROR: Could not locate installed Chrome binary under ${RUNTIME_DIR}."
  exit 1
fi
ln -sfn "${CHROME_PATH}" "${RUNTIME_DIR}/chrome/current"
echo "==> Chrome binary: ${CHROME_PATH}"
echo "==> Symlink created: ${RUNTIME_DIR}/chrome/current -> ${CHROME_PATH}"

# 5) Print recommended .env entries for Laravel
cat <<EOF

==> Add these to your Laravel .env (adjust if needed):

PUPPETEER_EXECUTABLE_PATH=${RUNTIME_DIR}/chrome/current
PUPPETEER_CACHE_DIR=${RUNTIME_DIR}
PPTR_RUNTIME_DIR=${RUNTIME_DIR}

# If your storage path differs, adjust permissions accordingly.
# Make sure your PHP code passes HOME/XDG_RUNTIME_DIR/TMPDIR envs or your JS sets them.

==> Quick CLI test (should print 'launched'):
sudo -u ${WEB_USER} -H bash -lc '
PUPPETEER_EXECUTABLE_PATH="${RUNTIME_DIR}/chrome/current" \
HOME="${RUNTIME_DIR}" \
XDG_RUNTIME_DIR="${RUNTIME_DIR}/run" \
TMPDIR="${RUNTIME_DIR}/tmp" \
node -e "
const p=require(\"puppeteer\");
(async()=>{
  const b=await p.launch({
    executablePath: process.env.PUPPETEER_EXECUTABLE_PATH,
    headless: \"new\",
    userDataDir: \"${RUNTIME_DIR}/profiles/test\",
    args: [\"--user-data-dir=${RUNTIME_DIR}/profiles/test\", \"--no-sandbox\", \"--disable-setuid-sandbox\", \"--disable-dev-shm-usage\", \"--no-zygote\", \"--single-process\", \"--disable-gpu\", \"--disable-crashpad\", \"--disable-breakpad\", \"--no-first-run\", \"--no-default-browser-check\"]
  });
  console.log(\"launched\");
  await b.close();
})().catch(e=>{console.error(e); process.exit(1);});
"'
EOF

echo "==> Done."
