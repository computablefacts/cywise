#!/bin/bash

apt install wget curl jq -y

# Install Osquery
if [ ! -f /etc/osquery/osquery.conf ]; then
    wget https://pkg.osquery.io/deb/osquery_5.11.0-1.linux_amd64.deb
    apt install ./osquery_5.11.0-1.linux_amd64.deb
    rm osquery_5.11.0-1.linux_amd64.deb
fi

# Install LogParser
if [ ! -f /opt/logparser/parser ]; then 
  mkdir -p /opt/logparser
fi

# Install LogAlert
if [ ! -f /opt/logalert/config.json ]; then 
  mkdir -p /opt/logalert
  curl -L https://github.com/jhuckaby/logalert/releases/latest/download/logalert-linux-x64 >/opt/logalert/logalert.bin
  chmod 755 /opt/logalert/logalert.bin
fi
{install_performa}
# Stop Osquery then LogAlert because reloading resets LogAlert internal state (see https://github.com/jhuckaby/logalert for details)  
systemctl stop osqueryd
systemctl stop logalert

# For debian-like oses, get the list of installed packages
if [ -f /etc/os-release ]; then

    id_like=$(grep '^ID_LIKE=' /etc/os-release | cut -d'=' -f2 | tr -d '"')
    
    if [ -z "$id_like" ]; then
      id_like=$(grep '^ID=' /etc/os-release | cut -d'=' -f2 | tr -d '"')
    fi

    # Ensure that the OS is debian-based
    if [[ "$id_like" == *"debian"* ]]; then

      apt_packages=$(apt list --installed 2>/dev/null | awk -F'[ /]' '{print $1 " " $3 " " $4 " apt"}' | tail -n +2)
      snap_packages=$(snap list 2>/dev/null | awk 'NR>1 {print $1 " " $2 " " $3 " snap"}')
      dpkg_packages=$(dpkg-query -W -f='${binary:Package} ${Version} ${Architecture} dpkg\n' 2>/dev/null)
      all_packages=$(echo -e "$apt_packages\n$snap_packages\n$dpkg_packages" | sort -u)
    
      echo "$all_packages" | awk '{
        key = $1 " " $2 " " $3
        if (key in seen) {
          seen[key] = seen[key] "," $4
        } else {
          seen[key] = $4
        }
      } END {
        for (key in seen) {
          print key " " seen[key]
        }
      }' \
      | sort \
      | awk -v hostname="$(hostname)" -v epoch="$(date +'%s')" -v date="$(LC_TIME=C date +'%a %b %e %T %Y %Z')" -v uid="$(tr -dc A-Za-z0-9 </dev/urandom | head -c 15; echo)" '{print "{\"row\":0,\"name\":\"deb_packages_installed_snapshot\",\"hostIdentifier\":\""hostname"\",\"calendarTime\":\""date"\",\"unixTime\":\""epoch"\",\"epoch\":0,\"counter\":0,\"numerics\":0,\"action\":\"snapshot\",\"columns\":{\"uid\":\""uid"\",\"name\":\""$1"\",\"version\":\""$2"\",\"arch\":\""$3"\",\"manager\":\""$4"\",\"status\":\"installed\"}}"}' \
      | gzip -c >/opt/logparser/osquery.jsonl.gz
        
      if [ -f /opt/logparser/osquery.jsonl.gz ]; then
        curl -X POST \
          -H "Content-Type: multipart/form-data" \
          -F "data=@/opt/logparser/osquery.jsonl.gz" \
          {url}/logparser/{secret}
        rm -f /opt/logparser/osquery.jsonl.gz
      fi
    fi
fi

# Parse local history to get back dropped metrics and events
if [ -f /var/log/osquery/osqueryd.snapshots.log ] && [ -f /var/log/osquery/osqueryd.results.log ]; then

  cat /var/log/osquery/osqueryd.snapshots.log /var/log/osquery/osqueryd.results.log \
    | gzip -c >/opt/logparser/osquery.jsonl.gz

  if [ -f /opt/logparser/osquery.jsonl.gz ]; then
    curl -X POST \
      -H "Content-Type: multipart/form-data" \
      -F "data=@/opt/logparser/osquery.jsonl.gz" \
      {url}/logparser/{secret}
    rm /opt/logparser/osquery.jsonl.gz
  fi
fi
        
# Update LogAlert configuration
wget -O /opt/logalert/config2.json {url}/logalert/{secret}

if [ -s /opt/logalert/config2.json ]; then
  if jq empty /opt/logalert/config2.json; then
    mv -f /opt/logalert/config2.json /opt/logalert/config.json
  fi
else
  rm /opt/logalert/config2.json
fi

# TODO : remove deprecated LogParser script
if [ -f /opt/logparser/parser ]; then 
  rm -rf /opt/logparser/parser
fi
if [ -f /opt/logparser/parser2 ]; then 
  rm -rf /opt/logparser/parser2
fi
{update_performa_config}
# Set LogAlert as a daemon
echo '[Unit]' > /etc/systemd/system/logalert.service
echo 'Description=LogAlert (cywise)' >> /etc/systemd/system/logalert.service
echo '[Service]' >> /etc/systemd/system/logalert.service
echo 'ExecStart=/opt/logalert/logalert.bin' >> /etc/systemd/system/logalert.service
echo '[Install]' >> /etc/systemd/system/logalert.service
echo 'WantedBy=multi-user.target' >> /etc/systemd/system/logalert.service

# Update Osquery configuration
wget -O /etc/osquery/osquery2.conf {url}/osquery/{secret}

if [ -s /etc/osquery/osquery2.conf ]; then
  if jq empty /etc/osquery/osquery2.conf; then
    mv -f /etc/osquery/osquery2.conf /etc/osquery/osquery.conf
  fi
else
  rm /etc/osquery/osquery2.conf
fi

# Set Osquery flags
echo '--config_plugin=filesystem' > /etc/osquery/osquery.flags # overwrite file!
echo '--disable_events=false' >> /etc/osquery/osquery.flags
echo '--disable_logging=false' >> /etc/osquery/osquery.flags
echo '--enable_file_events=true' >> /etc/osquery/osquery.flags
echo '--enable_ntfs_publisher=true' >> /etc/osquery/osquery.flags
echo '--enable_syslog=true' >> /etc/osquery/osquery.flags
echo '--force=true' >> /etc/osquery/osquery.flags
echo '--audit_allow_config=true' >> /etc/osquery/osquery.flags
echo '--audit_allow_sockets=true' >> /etc/osquery/osquery.flags
echo '--audit_persist=true' >> /etc/osquery/osquery.flags
echo '--disable_audit=false' >> /etc/osquery/osquery.flags
echo '--events_expiry=1' >> /etc/osquery/osquery.flags
echo '--events_max=500000' >> /etc/osquery/osquery.flags
echo '--logger_min_status=1' >> /etc/osquery/osquery.flags
echo '--logger_plugin=filesystem' >> /etc/osquery/osquery.flags
echo '--schedule_default_interval=3600' >> /etc/osquery/osquery.flags
echo '--verbose=false' >> /etc/osquery/osquery.flags
echo '--watchdog_memory_limit=350' >> /etc/osquery/osquery.flags
echo '--watchdog_utilization_limit=130' >> /etc/osquery/osquery.flags
echo '--worker_threads=2' >> /etc/osquery/osquery.flags

# Drop Osquery daemon's output every sunday at 01:11 am
cat <(fgrep -i -v 'rm /var/log/osquery/osqueryd.results.log /var/log/osquery/osqueryd.snapshots.log' <(crontab -l)) <(echo '11 1 * * 0 rm /var/log/osquery/osqueryd.results.log /var/log/osquery/osqueryd.snapshots.log') | crontab -

# Drop LogAlert's logs every day at 02:22 am
cat <(fgrep -i -v 'rm /opt/logalert/log.txt' <(crontab -l)) <(echo '22 2 * * * rm /opt/logalert/log.txt') | crontab -

# Auto-update the server every day at 03:33 am
cat <(crontab -l | sed '/curl -s https:\/\/.*\/update\/.*| bash/d') <(echo '33 3 * * * curl -s {url}/update/{secret} | bash') | crontab -

# Delete entry that parse web logs every hour
crontab -l | grep -v "logparser" | crontab -

# Delete entry that call old domain app.towerify.io
crontab -l | grep -v "app\.towerify\.io" | crontab -

# Start LogAlert then Osquery because reloading resets LogAlert internal state (see https://github.com/jhuckaby/logalert for details)  
systemctl start logalert
systemctl start osqueryd

# If fail2ban is up-and-running, whitelist Cywise's IP addresses
if systemctl is-active --quiet fail2ban; then
  if [ -f /etc/fail2ban/jail.conf ]; then
    {whitelist}
    systemctl restart fail2ban
  fi
fi
