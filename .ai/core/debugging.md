# Debug Workflow

## Step 1: Reproduce
- Get exact steps, input data, environment details.
- Confirm the bug exists in the current state.

## Step 2: Isolate
- Locate the exact file and line causing the issue.
- Use error logs, debugger, or `var_dump`/`dd`.
- Add logging at suspected points.

## Step 3: Root Cause
- Trace the data flow to find why the error occurs.
- Check assumptions about input values and types.
- Verify database queries and results.

## Step 4: Fix
- Apply minimal change to fix the root cause.
- Do not fix unrelated issues.

## Step 5: Verify
- Confirm the bug is fixed.
- Check that no regression was introduced.
- Run relevant tests.

## Common Checks
- Missing or incorrect type checks.
- Null/undefined values.
- Off-by-one errors in loops.
- SQL injection or malformed queries.
- Incorrect conditional logic.
- Outdated cache.
- Permission or authentication issues.
