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

BACKUP_DIR="/var/backups/apache_cipher_fix_$(date +%Y%m%d_%H%M%S)"
STRONG_SSL_CIPHERS="ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256:ECDHE-ECDSA-AES256-GCM-SHA384:ECDHE-RSA-AES256-GCM-SHA384:ECDHE-ECDSA-CHACHA20-POLY1305:ECDHE-RSA-CHACHA20-POLY1305:DHE-RSA-AES128-GCM-SHA256:DHE-RSA-AES256-GCM-SHA384"

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

detect_apache_config() {
    log_info "Recherche du binaire et de la configuration..."
    
    if command -v apache2ctl &> /dev/null; then APACHE_CMD="apache2ctl"
    elif command -v apachectl &> /dev/null; then APACHE_CMD="apachectl"
    elif command -v httpd &> /dev/null; then APACHE_CMD="httpd"
    else
        log_error "Aucun binaire Apache trouvé."
        exit 1
    fi

    log_info "Analyse des VirtualHosts ($APACHE_CMD -S)..."
    DUMP_VHOSTS=$($APACHE_CMD -S 2>/dev/null)

    if [ "$TARGET_DOMAIN" != "127.0.0.1" ]; then
        CONFIG_FILE=$(echo "$DUMP_VHOSTS" | grep -E ":$TARGET_PORT.*$TARGET_DOMAIN" | grep -o '(/.*:[0-9]*)' | head -n 1 | awk -F: '{print $1}' | tr -d '()')
    fi

    if [ -z "$CONFIG_FILE" ]; then
        CONFIG_FILE=$(echo "$DUMP_VHOSTS" | grep -E "port $TARGET_PORT" | grep -o '(/.*:[0-9]*)' | head -n 1 | awk -F: '{print $1}' | tr -d '()')
    fi

    if [ -z "$CONFIG_FILE" ] || [ ! -f "$CONFIG_FILE" ]; then
        log_info "Recherche manuelle dans les dossiers..."
        SEARCH_DIRS="/etc/apache2/sites-enabled /etc/httpd/conf.d /usr/local/apache2/conf/extra /usr/local/apache2/conf"
        
        for dir in $SEARCH_DIRS; do
            if [ -d "$dir" ]; then
                FOUND=$(grep -lR "VirtualHost.*:$TARGET_PORT" "$dir" 2>/dev/null | head -n 1)
                if [ -n "$FOUND" ]; then CONFIG_FILE="$FOUND"; break; fi
            fi
        done
    fi

    if [ -n "$CONFIG_FILE" ] && [ -f "$CONFIG_FILE" ]; then
        log_success "Cible détectée : $CONFIG_FILE"
    else
        log_error "Fichier de configuration introuvable pour le port $TARGET_PORT."
        exit 1
    fi
}

reload_apache() {
    log_info "Rechargement du service..."
    if is_docker; then
        if $APACHE_CMD -k graceful 2>/dev/null || $APACHE_CMD graceful 2>/dev/null; then
            log_success "Apache rechargé (Docker)."
        else
            log_error "Le reload a échoué. Veuillez redémarrer le conteneur manuellement."
        fi
    else
        if command -v systemctl &>/dev/null; then
            systemctl reload apache2 || systemctl reload httpd
            log_success "Apache rechargé (Systemd)."
        else
            $APACHE_CMD graceful
        fi
    fi
}

fix_apache_ciphers() {
    detect_apache_config
    
    mkdir -p "$BACKUP_DIR"
    cp "$CONFIG_FILE" "$BACKUP_DIR/apache.conf.backup"
    
    awk -v ciphers="$STRONG_SSL_CIPHERS" -v port="$TARGET_PORT" '
    in_vhost == 0 && /^[[:space:]]*(SSLProtocol|SSLCipherSuite|SSLHonorCipherOrder)/ { next }
    
    /<VirtualHost.*:'"$TARGET_PORT"'/ || /<VirtualHost.*:443/ {
        in_vhost = 1
        print
        next
    }
    
    in_vhost == 1 && /^[[:space:]]*SSLProtocol/ {
        print "    SSLProtocol all -SSLv2 -SSLv3 -TLSv1 -TLSv1.1"
        found_proto = 1
        next
    }
    in_vhost == 1 && /^[[:space:]]*SSLCipherSuite/ {
        print "    SSLCipherSuite " ciphers
        found_cipher = 1
        next
    }
    in_vhost == 1 && /^[[:space:]]*SSLHonorCipherOrder/ {
        print "    SSLHonorCipherOrder on"
        next
    }
    
    in_vhost == 1 && /<\/VirtualHost>/ {
        if (!found_proto) print "    SSLProtocol all -SSLv2 -SSLv3 -TLSv1 -TLSv1.1"
        if (!found_cipher) print "    SSLCipherSuite " ciphers
        print "    SSLHonorCipherOrder on"
        in_vhost = 0
        print
        next
    }
    { print }
    ' "$CONFIG_FILE" > "$CONFIG_FILE.tmp"
    
    cat "$CONFIG_FILE.tmp" > "$CONFIG_FILE"
    rm -f "$CONFIG_FILE.tmp"
    
    log_success "Configuration appliquée."
    
    if $APACHE_CMD -t 2>/dev/null; then
        reload_apache
    else
        log_error "Erreur de syntaxe Apache ! Restauration..."
        cp "$BACKUP_DIR/apache.conf.backup" "$CONFIG_FILE"
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

fix_apache_ciphers
verify_fix