
# Update performa-satellite configuration
wget -O /opt/performa/config2.json {url}/performa/{secret}

if [ -s /opt/performa/config2.json ]; then
  if jq empty /opt/performa/config2.json; then
    mv -f /opt/performa/config2.json /opt/performa/config.json
  fi
else
  rm /opt/performa/config2.json
fi

