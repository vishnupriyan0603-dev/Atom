# MySQL Reference

## Data Types
- **INT**: `TINYINT` (1B), `SMALLINT` (2B), `MEDIUMINT` (3B), `INT` (4B), `BIGINT` (8B).
- **Strings**: `VARCHAR(255)` for variable, `CHAR` for fixed, `TEXT` for long.
- **Dates**: `DATE`, `DATETIME`, `TIMESTAMP`, `TIME`, `YEAR`.
- **Decimals**: `DECIMAL(10,2)` for precise values.

## Indexes
- `PRIMARY KEY` - unique row identifier.
- `INDEX` (or `KEY`) - simple index.
- `UNIQUE INDEX` - unique constraint + index.
- `FULLTEXT INDEX` - text search.
- Composite indexes: column order = equality first, then range.

## Query Optimization
- `EXPLAIN [query]` to analyze execution plan.
- Use `type: ref` or `range` over `ALL` (full scan).
- Avoid functions on indexed columns in WHERE: `WHERE DATE(col) = ...`.
- Use `LIMIT` with `OFFSET` for pagination with indexes.
- Consider `EXISTS` vs `IN` for subqueries.

## Transactions
```sql
START TRANSACTION;
-- statements
COMMIT; -- or ROLLBACK;
```
- Use transactions for multi-table writes.
- Choose appropriate isolation level.
