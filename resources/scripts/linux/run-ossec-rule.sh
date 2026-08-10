#!/bin/bash

set -u

policy_uid="${1:-${CYWISE_OSSEC_POLICY_UID:-cywise_ossec_unix}}"
default_cywise_url="{url}"
default_server_secret="{secret}"
cywise_url="${CYWISE_URL:-$default_cywise_url}"
server_secret="${CYWISE_SERVER_SECRET:-$default_server_secret}"
evaluator="${CYWISE_OSSEC_EVALUATOR:-/opt/cywise/lib/Test-OssecRules.ps1}"
results_log="${CYWISE_OSSEC_RESULTS_LOG:-/var/log/cywise/ossec-results.log}"
lock_file="${CYWISE_OSSEC_LOCK_FILE:-/run/lock/cywise-ossec-agent.lock}"
timeout_seconds="${CYWISE_OSSEC_RULE_TIMEOUT:-300}"

if [[ ! "$policy_uid" =~ ^[A-Za-z0-9_.-]+$ ]]; then
  echo "Usage: $0 <policy-uid>" >&2
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
policy_response="$work_dir/policy.json"
rule_file="$work_dir/rule.jsonl"
engine_output="$work_dir/engine.out"
trap 'rm -rf "$work_dir"' EXIT

mkdir -p "$(dirname "$results_log")" "$(dirname "$lock_file")"
touch "$results_log"

# Prevent two OSSEC policy runs from running concurrently on the same agent.
exec 8>"$lock_file"
if ! flock -n 8; then
  echo "Another Cywise OSSEC policy is already running." >&2
  exit 75
fi

if ! curl --fail --silent --show-error \
  "$cywise_url/ossec-agent/$server_secret/policies/$policy_uid/rules" \
  --output "$policy_response"; then
  echo "Unable to fetch OSSEC policy '$policy_uid' from Cywise." >&2
  exit 2
fi

if ! jq -e \
  --arg requested_policy_uid "$policy_uid" \
  '.uid == $requested_policy_uid
    and (.name | type == "string")
    and (.revision | type == "string")
    and (.rules | type == "array")
    and all(.rules[];
      (.uid | type == "number")
      and .policy_uid == $requested_policy_uid
      and (.title | type == "string")
      and (.revision | type == "string")
      and (.requirements | type == "object"))' \
  "$policy_response" >/dev/null; then
  echo "Cywise returned an invalid OSSEC policy." >&2
  exit 2
fi

policy_name=$(jq -r '.name' "$policy_response")
policy_revision=$(jq -r '.revision' "$policy_response")
rule_count=$(jq '.rules | length' "$policy_response")

if [ "$rule_count" -eq 0 ]; then
  echo "OSSEC policy '$policy_uid' contains no rules." >&2
  exit 0
fi

host_identifier=$(hostname)
overall_exit_code=0

for ((rule_index = 0; rule_index < rule_count; rule_index++)); do
  rule_uid=$(jq -r --argjson index "$rule_index" '.rules[$index].uid' "$policy_response")
  rule_title=$(jq -r --argjson index "$rule_index" '.rules[$index].title' "$policy_response")
  rule_revision=$(jq -r --argjson index "$rule_index" '.rules[$index].revision' "$policy_response")
  jq -c --argjson index "$rule_index" '.rules[$index].requirements' "$policy_response" >"$rule_file"

  started_ms=$(date +%s%3N)
  status="error"
  error="[]"

  timeout "${timeout_seconds}s" \
    pwsh -NoLogo -NonInteractive -File "$evaluator" -RulesFile "$rule_file" -Json \
    >"$engine_output" 2>&1
  engine_exit_code=$?

  if [ "$engine_exit_code" -eq 124 ]; then
    status="timeout"
    error=$(jq -cn --arg message "Rule execution exceeded ${timeout_seconds} seconds." \
      '[{message: $message, detail: ""}]')
  elif [ "$engine_exit_code" -ne 0 ]; then
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
      engine_details=$(tail -c 8000 "$engine_output")
      error=$(jq -cn --arg message "The OSSEC evaluator returned an invalid result." --arg detail "$engine_details" \
        '[{message: $message, detail: $detail}]')
    fi
  fi

  finished_ms=$(date +%s%3N)
  duration_ms=$((finished_ms - started_ms))
  unix_time=$(date +'%s')
  calendar_time=$(LC_TIME=C date +'%a %b %e %T %Y %Z')

  case "$status" in
    passed)
      text="OSSEC rule $rule_uid passed: the server is compliant."
      ;;
    failed)
      text="OSSEC rule $rule_uid failed: the server is not compliant."
      if [ "$overall_exit_code" -eq 0 ]; then
        overall_exit_code=1
      fi
      ;;
    timeout)
      text="OSSEC rule $rule_uid timed out."
      overall_exit_code=2
      ;;
    *)
      text="OSSEC rule $rule_uid could not be evaluated."
      overall_exit_code=2
      ;;
  esac

  event=$(jq -cn \
    --arg host_identifier "$host_identifier" \
    --arg calendar_time "$calendar_time" \
    --arg policy_uid "$policy_uid" \
    --arg policy_name "$policy_name" \
    --arg policy_revision "$policy_revision" \
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
        policy_uid: $policy_uid,
        policy_name: $policy_name,
        policy_revision: $policy_revision,
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
done

echo "Executed $rule_count rule(s) from OSSEC policy '$policy_uid'." >&2
exit "$overall_exit_code"
