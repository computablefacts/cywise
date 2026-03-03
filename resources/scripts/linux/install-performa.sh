
# Install performa-satellite
if [ ! -f /opt/performa/config.json ]; then 
    mkdir -p /opt/performa
    curl -L https://github.com/jhuckaby/performa-satellite/releases/latest/download/performa-satellite-linux-x64 > /opt/performa/satellite.bin
    chmod 755 /opt/performa/satellite.bin
    /opt/performa/satellite.bin --install
fi

