#!/bin/bash
set -e

### CONFIGURATION - L'IA DOIT MODIFIER CES 2 LIGNES ###
TARGET_DOMAIN="127.0.0.1"
TARGET_PORT="443"
### FIN CONFIGURATION ###

RED='\033[0;31m'
GREEN='\033[0;32m'
BLUE='\033[0;34m'
NC='\033[0m'

BACKUP_DIR="/var/backups/nginx_cipher_fix_$(date +%Y%m%d_%H%M%S)"
STRONG_SSL_CIPHERS="ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256:ECDHE-ECDSA-AES256-GCM-SHA384:ECDHE-RSA-AES256-GCM-SHA384:ECDHE-ECDSA-CHACHA20-POLY1305:ECDHE-RSA-CHACHA20-POLY1305:DHE-RSA-AES128-GCM-SHA256:DHE-RSA-AES256-GCM-SHA384"
STRONG_SSL_PROTOCOLS="TLSv1.2 TLSv1.3"

log_info() { echo -e "${BLUE}[INFO]${NC} $1"; }
log_success() { echo -e "${GREEN}[✓]${NC} $1"; }
log_error() { echo -e "${RED}[✗]${NC} $1"; }

is_docker() {
    if [ -f /.dockerenv ] || grep -q "docker" /proc/1/cgroup 2>/dev/null; then
        return 0
    else
        return 1
    fi
}

detect_nginx_config() {
    log_info "Détection intelligente de la configuration Nginx..."
    
    NGINX_CMD=""
    POSSIBLE_BINS="/usr/sbin/nginx /usr/local/nginx/sbin/nginx /usr/local/bin/nginx /bin/nginx"

    if command -v nginx &> /dev/null; then NGINX_CMD="nginx"
    else
        for bin in $POSSIBLE_BINS; do
            if [ -x "$bin" ]; then NGINX_CMD="$bin"; break; fi
        done
    fi

    if [ -z "$NGINX_CMD" ]; then
        log_error "Aucun exécutable Nginx trouvé."
        exit 1
    fi

    log_info "Analyse de la configuration ($NGINX_CMD -T)..."
    FULL_CONFIG_DUMP=$($NGINX_CMD -T 2>/dev/null || true)

    if [ -z "$FULL_CONFIG_DUMP" ]; then
        log_error "Impossible de lire la configuration. Vérifiez 'nginx -t'."
        exit 1
    fi

    CONFIG_FILE=$(echo "$FULL_CONFIG_DUMP" | awk -v port="$TARGET_PORT" -v domain="$TARGET_DOMAIN" '
        /^# configuration file / { 
            current_file = $4; gsub(":", "", current_file);
            has_domain = 0; has_port = 0;
        }
        $0 ~ "listen.*" port { has_port = 1 }
        $0 ~ "server_name.*" domain { has_domain = 1 }
        has_port == 1 && has_domain == 1 && domain != "127.0.0.1" { print current_file; exit; }
        /^# configuration file / { if (has_port == 1) { fallback_file = current_file } }
        END { if (domain == "127.0.0.1" || domain == "") { print fallback_file } }
    ')
    
    if [ -z "$CONFIG_FILE" ]; then
        log_info "Recherche physique (grep)..."
        SEARCH_DIRS="/etc/nginx/sites-enabled /etc/nginx/conf.d /usr/local/nginx/conf /etc/nginx"
        for dir in $SEARCH_DIRS; do
            if [ -d "$dir" ]; then
                FOUND=$(grep -lR "listen.*$TARGET_PORT" "$dir" 2>/dev/null | head -n 1)
                if [ -n "$FOUND" ]; then CONFIG_FILE="$FOUND"; break; fi
            fi
        done
    fi

    if [ -n "$CONFIG_FILE" ] && [ -f "$CONFIG_FILE" ]; then
        log_success "Fichier détecté : $CONFIG_FILE"
    else
        log_error "Impossible de trouver le fichier de configuration."
        exit 1
    fi
}

reload_nginx() {
    log_info "Rechargement du service..."
    if is_docker; then
        if $NGINX_CMD -s reload 2>/dev/null; then
            log_success "Nginx rechargé (Docker signal)."
        else
            log_error "Echec du reload. Redémarrez le conteneur."
        fi
    else
        if command -v systemctl &>/dev/null; then
            systemctl reload nginx
            log_success "Nginx rechargé (Systemd)."
        else
            $NGINX_CMD -s reload
        fi
    fi
}

fix_nginx_ciphers() {
    detect_nginx_config
    
    mkdir -p "$BACKUP_DIR"
    cp "$CONFIG_FILE" "$BACKUP_DIR/nginx.conf.backup"
    
    if grep -q "### SECURITY FIX START" "$CONFIG_FILE"; then
        sed -i '/### SECURITY FIX START/,/### SECURITY FIX END/d' "$CONFIG_FILE"
    fi

    cat <<EOF > /tmp/nginx_ssl_snippet.conf
    
    ### SECURITY FIX START - $(date +%F) ###
    ssl_protocols $STRONG_SSL_PROTOCOLS;
    ssl_ciphers $STRONG_SSL_CIPHERS;
    ssl_prefer_server_ciphers on;
    ### SECURITY FIX END ###
EOF

    sed -i -E 's/^\s*ssl_protocols/#OLD_ssl_protocols/' "$CONFIG_FILE"
    sed -i -E 's/^\s*ssl_ciphers/#OLD_ssl_ciphers/' "$CONFIG_FILE"
    sed -i -E 's/^\s*ssl_prefer_server_ciphers/#OLD_ssl_prefer_server_ciphers/' "$CONFIG_FILE"

    if grep -q "listen.*$TARGET_PORT" "$CONFIG_FILE"; then
        sed -i "/listen.*$TARGET_PORT/r /tmp/nginx_ssl_snippet.conf" "$CONFIG_FILE"
    else
        sed -i "/server {/r /tmp/nginx_ssl_snippet.conf" "$CONFIG_FILE"
    fi
    rm -f /tmp/nginx_ssl_snippet.conf
    
    log_info "Test de la syntaxe..."
    if $NGINX_CMD -t 2>&1 | grep -q "successful"; then
        reload_nginx
    else
        log_error "Erreur de syntaxe détectée ! Restauration..."
        echo "$($NGINX_CMD -t 2>&1)"
        cp "$BACKUP_DIR/nginx.conf.backup" "$CONFIG_FILE"
        exit 1
    fi
}

verify_fix() {
    echo ""
    log_info "Vérification finale..."
    if echo "Q" | timeout 3 openssl s_client -connect ${TARGET_DOMAIN}:${TARGET_PORT} -tls1_1 2>/dev/null | grep -q "Cipher is"; then
        log_error "ÉCHEC : TLS 1.1 est toujours accepté."
    else
        log_success "SUCCÈS : TLS 1.1 est bien refusé."
    fi
    
    if echo "Q" | timeout 3 openssl s_client -connect ${TARGET_DOMAIN}:${TARGET_PORT} -tls1_2 2>/dev/null | grep -q "Cipher is"; then
        log_success "SUCCÈS : TLS 1.2 est accepté."
    else
        log_error "ATTENTION : TLS 1.2 ne semble pas répondre."
    fi
}

if [ "$EUID" -ne 0 ]; then
    log_error "Root requis (sudo)."
    exit 1
fi

fix_nginx_ciphers
verify_fix