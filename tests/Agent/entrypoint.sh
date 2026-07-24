#!/bin/bash

set -u

: "${CYWISE_URL:?CYWISE_URL is required}"
: "${CYWISE_SERVER_SECRET:?CYWISE_SERVER_SECRET is required}"

rule_uid="${CYWISE_RULE_UID:-50004}"
export CYWISE_OSSEC_RESULTS_LOG=/var/log/cywise/ossec-results.log

set +e
/opt/cywise/bin/run-ossec-rule "$rule_uid"
runner_exit_code=$?
set -e

echo
echo "Event written to $CYWISE_OSSEC_RESULTS_LOG:"
event=$(tail -n 1 "$CYWISE_OSSEC_RESULTS_LOG")
echo "$event" | jq .

payload_file=$(mktemp)
trap 'rm -f "$payload_file"' EXIT
jq -n \
  --arg hostname "$(hostname)" \
  --arg line "$event" \
  '{
    name: "Monitor Cywise OSSEC Results",
    file: "/var/log/cywise/ossec-results.log",
    date: (now | todate),
    hostname: $hostname,
    lines: [$line]
  }' >"$payload_file"

echo
echo "Forwarding the event through the LogAlert/osquery pipeline..."
curl --fail --silent --show-error \
  -H "Content-Type: application/json" \
  --data-binary "@$payload_file" \
  "$CYWISE_URL/logalert/$CYWISE_SERVER_SECRET" \
  | jq .

exit "$runner_exit_code"
