#!/bin/bash

function install_cywise_powershell {
  if command -v pwsh >/dev/null 2>&1; then
    return 0
  fi

  if [ ! -f /etc/os-release ]; then
    echo "Unable to detect the Linux distribution." >&2
    return 2
  fi

  source /etc/os-release

  case "$ID" in
    debian|ubuntu)
      apt-get update
      apt-get install -y wget ca-certificates

      package_file=$(mktemp --suffix=.deb)
      wget -q "https://packages.microsoft.com/config/$ID/$VERSION_ID/packages-microsoft-prod.deb" \
        -O "$package_file"
      dpkg -i "$package_file"
      rm -f "$package_file"
      apt-get update
      apt-get install -y powershell
      ;;
    *)
      echo "Unsupported Linux distribution for the OSSEC agent: $ID" >&2
      return 2
      ;;
  esac
}

install_cywise_powershell || exit $?
