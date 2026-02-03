#!/bin/bash
set -e

### CONFIGURATION - L'IA DOIT MODIFIER CES 3 LIGNES ###
TARGET_DOMAIN="127.0.0.1"
TARGET_PORT="443"
TARGET_FILE="error.log"
### FIN CONFIGURATION ###

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m'

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

detect_apache_docroot() {
    log_info "Recherche de la racine du site (DocumentRoot)..."
    
    if command -v apache2ctl &> /dev/null; then APACHE_CMD="apache2ctl"
    elif command -v apachectl &> /dev/null; then APACHE_CMD="apachectl"
    elif command -v httpd &> /dev/null; then APACHE_CMD="httpd"
    else
        log_error "Aucun binaire Apache trouvé."
        exit 1
    fi

    DUMP_VHOSTS=$($APACHE_CMD -S 2>/dev/null)

    if [ "$TARGET_DOMAIN" != "127.0.0.1" ]; then
        CONFIG_FILE=$(echo "$DUMP_VHOSTS" | grep -E ":$TARGET_PORT.*$TARGET_DOMAIN" | grep -o '(/.*:[0-9]*)' | head -n 1 | awk -F: '{print $1}' | tr -d '()')
    fi

    if [ -z "$CONFIG_FILE" ]; then
        CONFIG_FILE=$(echo "$DUMP_VHOSTS" | grep -E "port $TARGET_PORT" | grep -o '(/.*:[0-9]*)' | head -n 1 | awk -F: '{print $1}' | tr -d '()')
    fi

    if [ -n "$CONFIG_FILE" ] && [ -f "$CONFIG_FILE" ]; then
        DOC_ROOT=$(grep -i "DocumentRoot" "$CONFIG_FILE" | head -n 1 | awk '{print $2}' | tr -d '"')
        if [ -n "$DOC_ROOT" ]; then
            echo "$DOC_ROOT"
            return
        fi
    fi

    if is_docker; then
        SEARCH_FILES="/usr/local/apache2/conf/httpd.conf /etc/httpd/conf/httpd.conf /etc/apache2/apache2.conf"
    else
        SEARCH_FILES="/etc/apache2/sites-enabled/000-default.conf /etc/httpd/conf/httpd.conf"
    fi

    for file in $SEARCH_FILES; do
        if [ -f "$file" ]; then
            DOC_ROOT=$(grep -i "DocumentRoot" "$file" | head -n 1 | awk '{print $2}' | tr -d '"')
            if [ -n "$DOC_ROOT" ]; then
                echo "$DOC_ROOT"
                return
            fi
        fi
    done
    
    if [ -d "/var/www/html" ]; then echo "/var/www/html"; return; fi
    if [ -d "/usr/local/apache2/htdocs" ]; then echo "/usr/local/apache2/htdocs"; return; fi
}

clean_file() {
    DOC_ROOT=$(detect_apache_docroot)
    
    if [ -z "$DOC_ROOT" ]; then
        log_error "Impossible de déterminer le DocumentRoot."
        exit 1
    fi
    
    FULL_PATH="${DOC_ROOT}/${TARGET_FILE}"
    FULL_PATH=$(echo "$FULL_PATH" | sed 's;//;/;g') 
    
    log_info "Cible détectée : $FULL_PATH"

    if [ ! -f "$FULL_PATH" ]; then
        echo -e "${YELLOW}Le fichier n'existe pas ou a déjà été supprimé.${NC}"
        exit 0
    fi

    echo ""
    echo -e "${CYAN}=== Aperçu (Head) ===${NC}"
    head -n 10 "$FULL_PATH"
    echo -e "${CYAN}=====================${NC}"
    echo ""

    echo -e "${YELLOW}ACTION REQUISE :${NC}"
    echo "1) Supprimer définitivement"
    echo "2) Archiver (Backup sécurisé)"
    read -p "Votre choix (1/2) : " CHOICE

    if [ "$CHOICE" = "1" ]; then
        rm -f "$FULL_PATH"
        log_success "Fichier supprimé."
    elif [ "$CHOICE" = "2" ]; then
        BACKUP_DIR="/var/backups/exposed_files"
        mkdir -p "$BACKUP_DIR"
        chmod 700 "$BACKUP_DIR"
        mv "$FULL_PATH" "$BACKUP_DIR/${TARGET_FILE}_$(date +%s)"
        log_success "Fichier déplacé dans $BACKUP_DIR"
    else
        log_error "Annulé."
        exit 1
    fi
}

if [ "$EUID" -ne 0 ]; then
    log_error "Root requis (sudo)."
    exit 1
fi

clean_file