#!/usr/bin/env bash
# Usage: scripts/smoke.sh [API_BASE_URL]
# Assumes a freshly seeded database. Does not change any data except re-saving one task as TODO.
BASE="${1:-http://127.0.0.1:8000/api}"
PASS=0
FAIL=0

code() {
  local args=(-s -o /dev/null -w "%{http_code}" -X "$1" "$BASE$2" -H "Accept: application/json" -H "Content-Type: application/json" --max-time 120)
  [ -n "$3" ] && args+=(-H "Authorization: Bearer $3")
  [ -n "$4" ] && args+=(-d "$4")
  curl "${args[@]}"
}

login() {
  curl -s -X POST "$BASE/login" -H "Accept: application/json" -H "Content-Type: application/json" \
    -d "{\"email\":\"$1\",\"password\":\"$2\"}" --max-time 120 \
    | python3 -c "import sys,json;print(json.load(sys.stdin)['data']['token'])"
}

count_tasks() {
  curl -s "$BASE/tasks" -H "Accept: application/json" -H "Authorization: Bearer $1" --max-time 120 \
    | python3 -c "import sys,json;print(len(json.load(sys.stdin)['data']))"
}

check() {
  if [ "$2" = "$3" ]; then
    echo "PASS  $1"
    PASS=$((PASS + 1))
  else
    echo "FAIL  $1 (expected $2, got $3)"
    FAIL=$((FAIL + 1))
  fi
}

echo "Testing $BASE"
ADMIN=$(login admin@example.com 'Admin@123')
USER=$(login user@example.com 'User@123')

check "admin login returns a token" "yes" "$([ -n "$ADMIN" ] && echo yes || echo no)"
check "user login returns a token" "yes" "$([ -n "$USER" ] && echo yes || echo no)"
check "wrong password is rejected" "401" "$(code POST /login '' '{"email":"admin@example.com","password":"nope"}')"
check "no token is rejected" "401" "$(code GET /me '')"
check "user can list projects" "200" "$(code GET /projects "$USER")"
check "user cannot create a project" "403" "$(code POST /projects "$USER" '{"name":"Hack"}')"
check "user cannot open a project with none of their tasks" "403" "$(code GET /projects/6 "$USER")"
check "user cannot list users" "403" "$(code GET /users "$USER")"
check "admin can list users" "200" "$(code GET /users "$ADMIN")"
check "admin sees more tasks than the user" "yes" "$([ "$(count_tasks "$ADMIN")" -gt "$(count_tasks "$USER")" ] && echo yes || echo no)"
check "user cannot move an overdue task to IN_PROGRESS" "422" "$(code PATCH /tasks/4/status "$USER" '{"status":"IN_PROGRESS"}')"
check "user cannot close an overdue task" "422" "$(code PATCH /tasks/4/status "$USER" '{"status":"DONE"}')"
check "user cannot touch someone else's task" "403" "$(code PATCH /tasks/1/status "$USER" '{"status":"DONE"}')"
check "user can update their own normal task" "200" "$(code PATCH /tasks/3/status "$USER" '{"status":"TODO"}')"

echo "Passed: $PASS  Failed: $FAIL"
[ "$FAIL" -eq 0 ]
