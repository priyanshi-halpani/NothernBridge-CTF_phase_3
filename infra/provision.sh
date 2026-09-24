#!/bin/bash
# ============================================================================
#  Northenbridge College CTF — Provisioning-Only Build (Phase 3)
# ----------------------------------------------------------------------------
#  This single script builds the COMPLETE CTF inside a fresh Ubuntu VM.
#  There are no Vagrant synced folders and no manual steps: the portal
#  application is cloned from a GitHub repository into a VM-local path.
#
#  Reproducibility contract:
#      git clone <this-repo> && vagrant up
#  must produce an identical working lab on any host machine.
#
#  Configuration (edit the defaults below or export before running):
#      PORTAL_REPO    GitHub URL of the portal application repository
#      PORTAL_BRANCH  branch / tag to deploy
#      PORTAL_SUBDIR  directory inside the repo that holds the web app
#      PORTAL_SEED    seed SQL file (inside the repo) -> db source of truth
#      PORTAL_DIR     VM-local path the app is served from
#
#  This script is idempotent: running `vagrant provision` again will not
#  error out and will never wipe existing application / player data.
# ============================================================================

set -euo pipefail

# ---------------------------------------------------------------------------
# Configuration
# ---------------------------------------------------------------------------
PORTAL_REPO="${PORTAL_REPO:-https://github.com/priyanshi-halpani/NothernBridge-CTF_phase_3.git}"
PORTAL_BRANCH="${PORTAL_BRANCH:-main}"
PORTAL_SUBDIR="${PORTAL_SUBDIR:-www}"
PORTAL_SEED="${PORTAL_SEED:-seed.sql}"
PORTAL_DIR="${PORTAL_DIR:-/var/www/html}"

BUILD_DIR="${BUILD_DIR:-/opt/northbridge-src}"
DB_PATH="${DB_PATH:-$PORTAL_DIR/database/college.db}"
DECOY_ENV="${DECOY_ENV:-$PORTAL_DIR/.env}"
FLAG_DIR="${FLAG_DIR:-/opt/northbridge}"
FLAG_FILE="${FLAG_FILE:-$FLAG_DIR/flag.txt}"

SITE_HOST="${SITE_HOST:-127.0.0.1}"
SITE_PORT="${SITE_PORT:-80}"

log() { printf '\n====================================================\n[%s] %s\n\n' "$(date +%H:%M:%S)" "$*"; }

echo "======================================"
echo " Northenbridge CTF VM Provisioning"
echo " (provisioning-only build, no host sync)"
echo "======================================"

# ---------------------------------------------------------------------------
# [1/8] System packages
# ---------------------------------------------------------------------------
log "[1/8] Updating packages and installing dependencies..."
export DEBIAN_FRONTEND=noninteractive
apt-get update -y
apt-get install -y \
    apache2 \
    php \
    libapache2-mod-php \
    php-sqlite3 \
    sqlite3 \
    git \
    curl \
    rsync

# ---------------------------------------------------------------------------
# [2/8] Acquire portal source (clone or pull from GitHub)
# ---------------------------------------------------------------------------
log "[2/8] Acquiring portal source from $PORTAL_REPO ($PORTAL_BRANCH)..."

mkdir -p "$BUILD_DIR"

if [ -d "$BUILD_DIR/.git" ]; then
    echo "  existing checkout found — updating origin and pulling latest..."
    git -C "$BUILD_DIR" remote set-url origin "$PORTAL_REPO"
    git -C "$BUILD_DIR" fetch --depth 1 origin "$PORTAL_BRANCH"
    git -C "$BUILD_DIR" checkout -B "$PORTAL_BRANCH" "origin/$PORTAL_BRANCH"
else
    echo "  cloning fresh..."
    git clone --depth 1 --branch "$PORTAL_BRANCH" "$PORTAL_REPO" "$BUILD_DIR"
fi

SRC_DIR="$BUILD_DIR/$PORTAL_SUBDIR"
if [ ! -d "$SRC_DIR" ]; then
    echo "ERROR: '$PORTAL_SUBDIR' not found in '$PORTAL_REPO' (branch '$PORTAL_BRANCH')." >&2
    exit 1
fi

# ---------------------------------------------------------------------------
# [3/8] Install the application into the VM-local web root
# ---------------------------------------------------------------------------
log "[3/8] Installing application to $PORTAL_DIR ..."

mkdir -p "$PORTAL_DIR"
rsync -a --delete --exclude='database/' "$SRC_DIR/" "$PORTAL_DIR/"

# ---------------------------------------------------------------------------
# [4/8] Apache virtual host
# ---------------------------------------------------------------------------
log "[4/8] Configuring Apache..."

a2enmod -q rewrite >/dev/null 2>&1 || true
a2enmod -q php8.1  >/dev/null 2>&1 || true

cat > /etc/apache2/sites-available/northbridge.conf <<VHOST
<VirtualHost *:80>

    ServerName northenbridge.local
    ServerAdmin webmaster@northenbridge.local

    DocumentRoot $PORTAL_DIR

    # Custom branded error pages (custom 404/500 instead of Apache defaults).
    ErrorDocument 404 /404.html
    ErrorDocument 500 /500.html

    # The decoy .env is intentionally retrievable as a plain-text file.
    # mod_mime treats a fully-hidden filename like ".env" as extensionless,
    # so AddType never matches it; ForceType guarantees text/plain.
    <FilesMatch "^\.env$">
        ForceType text/plain
    </FilesMatch>

    <Directory $PORTAL_DIR>
        Options -Indexes
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog \${APACHE_LOG_DIR}/northbridge_error.log
    CustomLog \${APACHE_LOG_DIR}/northbridge_access.log combined

    # Shared runtime includes are never meant to be fetched directly.
    <DirectoryMatch "$PORTAL_DIR/includes">
        Require all denied
    </DirectoryMatch>

    # The SQLite database is only reachable through the application,
    # never served to the browser directly.
    <FilesMatch "\.(db|sqlite|sqlite3)$">
        Require all denied
    </FilesMatch>

</VirtualHost>
VHOST

a2dissite 000-default.conf >/dev/null 2>&1 || true
a2ensite northbridge.conf  >/dev/null 2>&1 || true

# ---------------------------------------------------------------------------
# [4a] Production PHP settings
# ---------------------------------------------------------------------------
log "[4a] Applying production PHP settings..."

PHP_INI="/etc/php/8.1/apache2/php.ini"
if [ -f "$PHP_INI" ]; then
    sed -i -E 's/^display_errors\s*=\s*On/display_errors = Off/'       "$PHP_INI"
    sed -i -E 's/^;?log_errors\s*=\s*Off/log_errors = On/'              "$PHP_INI"
    sed -i -E 's/^;?error_reporting\s*=.*/error_reporting = E_ALL/'     "$PHP_INI"
fi

# ---------------------------------------------------------------------------
# [5/8] Decoy credential file (.env) — password-spray stage
# ---------------------------------------------------------------------------
log "[5/8] Placing decoy credential file ($DECOY_ENV)..."

cat > "$DECOY_ENV" <<'ENV'
# Northbridge College portal — environment configuration
# -----------------------------------------------------
# LAB ONLY — FICTIONAL CREDENTIALS. All usernames and passwords below
# are made up for an offline CTF exercise and describe no real system,
# account, or person. They must never be reused outside this lab.
# -----------------------------------------------------
APP_ENV=production
APP_URL=http://northenbridge.local
APP_DEBUG=false

DB_CONNECTION=sqlite
DB_DATABASE=/var/www/html/database/college.db

# ---------------------------------------------------------------------
# Administrative access
# The production account rotates on an irregular schedule. The pairs
# below were recovered from old support tickets while the IT office
# was migrating between systems. ONLY ONE pair is currently valid;
# the remainder are retired keepers left in place for the archive.
# ---------------------------------------------------------------------
helen.carter:Winter2026!
anil.mehta:Northbridge@2026
priya.kannan:CollegeAdmin!99
david.okafor:Admin#2024
sara.ahmed:PortalPass!7
mohammed.khan:SecureIT@21
lina.farouk:NBCadmin2023
rohan.singh:FallBack!88
emily.wong:Winter2025!
gregory.adams:Admin1@one
fatima.hassan:Autumn#2022
peter.novak:March2026!
ENV
echo "  decoy .env written."

# ---------------------------------------------------------------------------
# [6/8] Final flag — filesystem, outside the database
# ---------------------------------------------------------------------------
log "[6/8] Placing final flag on the filesystem (not in the database)..."

mkdir -p "$FLAG_DIR"

if [ -f "$FLAG_FILE" ]; then
    echo "  already present — leaving in place (unique per deployment)."
else
    TOKEN="$(od -An -N6 -tx1 /dev/urandom | tr -d ' \n')"
    NCC_FLAG="NCC{$TOKEN}"
    cat > "$FLAG_FILE" <<FLAG
Northenbridge College CTF — Final Flag

The goal of the exercise is to exfiltrate the ENTIRE application
database. This flag is deliberately NOT stored in any database table
or Git repository — it lives on the filesystem, outside the SQLite
file. See the compliance-notes entry for its exact path.

$NCC_FLAG
FLAG
    echo "  final flag written to $FLAG_FILE"
fi

chown root:www-data "$FLAG_FILE"
chmod 640 "$FLAG_FILE"

# Remove the legacy flag path used by earlier versions of this script.
rm -f "$FLAG_DIR/flag-final.txt"

# ---------------------------------------------------------------------------
# [7/8] Idempotent database initialization from the seed SQL
# ---------------------------------------------------------------------------
log "[7/8] Initializing the SQLite database (idempotent)..."

mkdir -p "$(dirname "$DB_PATH")"

SEED_SRC="$BUILD_DIR/$PORTAL_SEED"
if [ ! -f "$SEED_SRC" ]; then
    SEED_SRC="$(find "$BUILD_DIR" -maxdepth 2 -name "$PORTAL_SEED" -print -quit 2>/dev/null || true)"
fi

if [ -z "$SEED_SRC" ] || [ ! -f "$SEED_SRC" ]; then
    echo "ERROR: seed file '$PORTAL_SEED' not found in the portal source." >&2
    exit 1
fi

# Existing deployments created admins before the department column existed,
# so only ALTER when the table is already present and lacks the column.
if sqlite3 "$DB_PATH" "SELECT 1 FROM sqlite_master WHERE type='table' AND name='admins';" 2>/dev/null | grep -q 1; then
    if [ "$(sqlite3 "$DB_PATH" "SELECT COUNT(*) FROM pragma_table_info('admins') WHERE name='department';")" = "0" ]; then
        echo "  migrating existing admins table (adding department column)..."
        sqlite3 "$DB_PATH" "ALTER TABLE admins ADD COLUMN department TEXT NOT NULL DEFAULT 'Information Technology';"
    fi
fi

# The seed is fully idempotent (CREATE IF NOT EXISTS / INSERT OR IGNORE),
# so it can be (re)applied on every provision without destroying the
# player progress that has accumulated in the database.
echo "  (re)applying idempotent seed to $DB_PATH ..."
sqlite3 "$DB_PATH" < "$SEED_SRC"

# Converge existing deployments onto the current CTF state: rotate the
# admin credential (only ONE .env pair stays valid), set the department
# the clerk view is scoped to, and remove the legacy in-DB final flag.
MIGRATE="$(cat <<'SQL'
UPDATE admins
   SET password = 'Winter2026!',
       department = 'Information Technology'
 WHERE username = 'helen.carter';

DELETE FROM flags WHERE flag_name = 'Flag_04';

UPDATE flags SET Description = 'Flag revealed inside the intentionally limited student-record view'
 WHERE flag_name = 'Flag_02';

UPDATE flags SET flag_value = 'Flag{Respected_the_Restricted_View}'
 WHERE flag_name = 'Flag_02';

INSERT OR IGNORE INTO compliance_notes
    (id, note, note_date)
VALUES
    (1, 'Full audit log archived at /opt/northbridge/flag.txt (lab host only).', '2026-09-01'),
    (2, 'All student records remain subject to the clerk view restriction.', '2026-09-01'),
    (3, 'Quarterly data-protection review is scheduled before the spring term.', '2026-09-01');
SQL
)"
sqlite3 "$DB_PATH" "$MIGRATE"

# ---------------------------------------------------------------------------
# [8/8] Permissions, restart, verify
# ---------------------------------------------------------------------------
log "[8/8] Setting permissions, restarting Apache and verifying..."

chown -R www-data:www-data "$PORTAL_DIR"
chmod 775 "$(dirname "$DB_PATH")"
[ -f "$DB_PATH" ] && chmod 664 "$DB_PATH"

systemctl enable apache2 >/dev/null 2>&1 || true
systemctl restart apache2

sleep 2

HTTP_CODE="$(curl -ksS -o /dev/null -w '%{http_code}' "http://${SITE_HOST}:${SITE_PORT}/" || true)"

echo "======================================"
echo " Provisioning complete!"
echo "======================================"
echo "  Portal source  : $PORTAL_DIR"
echo "  Database       : $DB_PATH"
echo "  Decoy .env     : $DECOY_ENV"
echo "  Final flag     : $FLAG_FILE"
echo "  Site check     : http://$SITE_HOST:$SITE_PORT/  ->  HTTP $HTTP_CODE"

if [ "$HTTP_CODE" != "200" ]; then
    echo "ERROR: site did not respond with HTTP 200." >&2
    exit 1
fi

if [ -f "$DB_PATH" ]; then
    echo "  Student rows   : $(sqlite3 "$DB_PATH" 'SELECT COUNT(*) FROM students;')"
fi