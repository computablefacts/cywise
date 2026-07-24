FROM ubuntu:24.04

ENV DEBIAN_FRONTEND=noninteractive

RUN apt-get update \
    && apt-get install -y --no-install-recommends ca-certificates curl jq util-linux wget \
    && wget -q https://packages.microsoft.com/config/ubuntu/24.04/packages-microsoft-prod.deb \
        -O /tmp/packages-microsoft-prod.deb \
    && dpkg -i /tmp/packages-microsoft-prod.deb \
    && rm -f /tmp/packages-microsoft-prod.deb \
    && apt-get update \
    && apt-get install -y --no-install-recommends powershell \
    && rm -rf /var/lib/apt/lists/*

RUN mkdir -p /opt/cywise/bin /opt/cywise/lib /var/log/cywise

COPY resources/scripts/linux/run-ossec-rule.sh /opt/cywise/bin/run-ossec-rule
COPY resources/ossec/powershell/Test-OssecRules.ps1 /opt/cywise/lib/Test-OssecRules.ps1
COPY tests/Agent/entrypoint.sh /usr/local/bin/cywise-agent-test

RUN chmod 700 /opt/cywise/bin/run-ossec-rule /usr/local/bin/cywise-agent-test \
    && chmod 600 /opt/cywise/lib/Test-OssecRules.ps1 \
    && touch /var/log/cywise/ossec-results.log

ENTRYPOINT ["/usr/local/bin/cywise-agent-test"]
