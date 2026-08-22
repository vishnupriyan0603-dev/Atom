# Standard Workflow

## Step 1: Analyze
- Read all relevant files before making changes.
- Understand the full context of the request.

## Step 2: Plan
- Determine which files need modification.
- Identify potential side effects.
- Consider backward compatibility.

## Step 3: Backup
- Backup affected files before modification.

## Step 4: Implement
- Write clean, maintainable code following project standards.
- One change at a time, verify each step.

## Step 5: Test
- Run existing tests. Add new tests for new functionality.
- Verify the specific change works correctly.

## Step 6: Verify UI
- Check that UI changes match requirements.
- Ensure responsive design works.

## Step 7: Verify Database
- Confirm migrations, queries, and schema changes are correct.
- Check for N+1 queries and missing indexes.

## Step 8: Verify API
- Test API endpoints for correct request/response.
- Check status codes, validation, error handling.

## Step 9: Review Performance
- Check query performance, cache usage, and load impact.

## Step 10: Security Review
- Validate input, escape output, check auth/permissions.

## Step 11: Regression Test
- Verify existing functionality still works.

## Step 12: Final Review
- Review all changes. Confirm nothing is broken.
