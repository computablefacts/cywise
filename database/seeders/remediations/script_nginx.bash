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

detect_nginx_docroot() {
    log_info "Recherche de la racine du site (root)..."
    
    NGINX_CMD="nginx"
    if ! command -v nginx &> /dev/null; then
        POSSIBLE_BINS="/usr/sbin/nginx /usr/local/nginx/sbin/nginx /usr/local/bin/nginx"
        for bin in $POSSIBLE_BINS; do
            if [ -x "$bin" ]; then NGINX_CMD="$bin"; break; fi
        done
    fi

    FULL_CONFIG=$($NGINX_CMD -T 2>/dev/null || true)
    
    if [ -z "$FULL_CONFIG" ]; then
        log_error "Impossible de lire la configuration Nginx."
        exit 1
    fi

    ROOT_PATH=$(echo "$FULL_CONFIG" | awk -v domain="$TARGET_DOMAIN" -v port="$TARGET_PORT" '
        BEGIN { in_server=0; current_root=""; found_root=""; }
        
        /server \{/ { in_server=1; current_root=""; has_domain=0; has_port=0; }
        
        in_server && /listen.*port/ { has_port=1 }
        in_server && /server_name.*domain/ { has_domain=1 }
        in_server && /^\s*root\s+/ { 
            current_root=$2; 
            gsub(";", "", current_root);
        }
        
        in_server && /\}/ { 
            if (has_domain && has_port && current_root != "") {
                print current_root;
                exit;
            }
            if (has_port && current_root != "") {
                found_root = current_root;
            }
            in_server=0;
        }
        
        END { if (found_root != "") print found_root }
    ')

    if [ -z "$ROOT_PATH" ]; then
        ROOT_PATH=$(echo "$FULL_CONFIG" | grep "root" | head -n 1 | awk '{print $2}' | tr -d ';')
    fi

    if [ -n "$ROOT_PATH" ]; then
        echo "$ROOT_PATH"
    else
        if [ -d "/usr/share/nginx/html" ]; then echo "/usr/share/nginx/html"; fi
        if [ -d "/var/www/html" ]; then echo "/var/www/html"; fi
    fi
}

clean_file() {
    DOC_ROOT=$(detect_nginx_docroot)
    
    if [ -z "$DOC_ROOT" ]; then
        log_error "Impossible de déterminer le dossier racine."
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