#!/bin/bash

set -u

rule_uid="${1:-}"
cywise_url="${CYWISE_URL:-{url}}"
server_secret="${CYWISE_SERVER_SECRET:-{secret}}"
evaluator="${CYWISE_OSSEC_EVALUATOR:-/opt/cywise/lib/Test-OssecRules.ps1}"
results_log="${CYWISE_OSSEC_RESULTS_LOG:-/var/log/cywise/ossec-results.log}"
timeout_seconds="${CYWISE_OSSEC_RULE_TIMEOUT:-300}"

if [[ ! "$rule_uid" =~ ^[0-9]+$ ]]; then
  echo "Usage: $0 <numeric-rule-uid>" >&2
  exit 2
fi

if [[ ! "$timeout_seconds" =~ ^[1-9][0-9]*$ ]]; then
  echo "CYWISE_OSSEC_RULE_TIMEOUT must be a positive integer." >&2
  exit 2
fi

if [ ! -f "$evaluator" ]; then
  echo "OSSEC evaluator not found: $evaluator" >&2
  exit 2
fi

work_dir=$(mktemp -d)
rule_response="$work_dir/rule.json"
rule_file="$work_dir/rule.jsonl"
engine_output="$work_dir/engine.out"
trap 'rm -rf "$work_dir"' EXIT

mkdir -p "$(dirname "$results_log")" /run/lock
touch "$results_log"

# Prevent two OSSEC checks from running concurrently on the same agent.
exec 8>/run/lock/cywise-ossec-agent.lock
if ! flock -n 8; then
  echo "Another Cywise OSSEC rule is already running." >&2
  exit 75
fi

started_ms=$(date +%s%3N)
status="error"
error="[]"
rule_title=""
rule_revision=""

if curl --fail --silent --show-error \
  "$cywise_url/ossec-agent/$server_secret/rules/$rule_uid" \
  --output "$rule_response"; then
  if jq -e \
    --argjson requested_uid "$rule_uid" \
    '.uid == $requested_uid
      and .policy_uid == "cywise_ossec_unix"
      and (.revision | type == "string")
      and (.requirements | type == "object")' \
    "$rule_response" >/dev/null; then
    jq -c '.requirements' "$rule_response" >"$rule_file"
    rule_title=$(jq -r '.title' "$rule_response")
    rule_revision=$(jq -r '.revision' "$rule_response")

    timeout "${timeout_seconds}s" \
      pwsh -NoLogo -NonInteractive -File "$evaluator" -RulesFile "$rule_file" -Json \
      >"$engine_output" 2>&1
    engine_exit_code=$?

    if [ "$engine_exit_code" -eq 124 ]; then
      status="timeout"
      error=$(jq -cn --arg message "Rule execution exceeded ${timeout_seconds} seconds." \
        '[{message: $message, detail: ""}]')
    elif [ "$engine_exit_code" -ne 0 ]; then
      status="error"
      engine_details=$(tail -c 8000 "$engine_output")
      error=$(jq -cn --arg message "The OSSEC evaluator failed." --arg detail "$engine_details" \
        '[{message: $message, detail: $detail}]')
    else
      engine_result=$(tail -n 1 "$engine_output")
      if echo "$engine_result" | jq -e \
        '.status == "passed" or .status == "failed" or .status == "error"' >/dev/null 2>&1; then
        status=$(echo "$engine_result" | jq -r '.status')
        error=$(echo "$engine_result" | jq -c '.errors // []')
      else
        status="error"
        engine_details=$(tail -c 8000 "$engine_output")
        error=$(jq -cn --arg message "The OSSEC evaluator returned an invalid result." --arg detail "$engine_details" \
          '[{message: $message, detail: $detail}]')
      fi
    fi
  else
    error='[{"message":"Cywise returned an invalid OSSEC rule.","detail":""}]'
  fi
else
  error='[{"message":"Unable to fetch the OSSEC rule from Cywise.","detail":""}]'
fi

finished_ms=$(date +%s%3N)
duration_ms=$((finished_ms - started_ms))
unix_time=$(date +'%s')
calendar_time=$(LC_TIME=C date +'%a %b %e %T %Y %Z')
host_identifier=$(hostname)

case "$status" in
  passed)
    text="OSSEC rule $rule_uid passed: the server is compliant."
    ;;
  failed)
    text="OSSEC rule $rule_uid failed: the server is not compliant."
    ;;
  timeout)
    text="OSSEC rule $rule_uid timed out."
    ;;
  *)
    text="OSSEC rule $rule_uid could not be evaluated."
    ;;
esac

event=$(jq -cn \
  --arg host_identifier "$host_identifier" \
  --arg calendar_time "$calendar_time" \
  --arg rule_uid "$rule_uid" \
  --arg rule_title "$rule_title" \
  --arg rule_revision "$rule_revision" \
  --arg status "$status" \
  --arg duration_ms "$duration_ms" \
  --arg error "$error" \
  --arg text "$text" \
  --argjson unix_time "$unix_time" \
  '{
    row: 0,
    name: "cywise_ossec_rule_result",
    hostIdentifier: $host_identifier,
    calendarTime: $calendar_time,
    unixTime: $unix_time,
    epoch: 0,
    counter: 0,
    numerics: 0,
    action: "snapshot",
    columns: {
      policy_uid: "cywise_ossec_unix",
      rule_uid: $rule_uid,
      rule_title: $rule_title,
      rule_revision: $rule_revision,
      status: $status,
      duration_ms: $duration_ms,
      error: $error,
      text: $text
    }
  }')

# LogAlert tails this file and forwards each osquery-compatible JSON line to Cywise.
exec 9>>"$results_log.lock"
flock 9
printf '%s\n' "$event" >>"$results_log"
flock -u 9

printf '%s\n' "$event"

if [ "$status" = "passed" ]; then
  exit 0
fi
if [ "$status" = "failed" ]; then
  exit 1
fi
exit 2
