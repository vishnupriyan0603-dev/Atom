# SQL Query Template

```sql
-- SELECT
SELECT t.id, t.name, t.status
FROM table_name t
WHERE t.status = 1
  AND t.deleted_at IS NULL
ORDER BY t.created_at DESC
LIMIT 15 OFFSET 0;

-- INSERT
INSERT INTO table_name (name, status, created_at)
VALUES (?, ?, NOW());

-- UPDATE
UPDATE table_name
SET status = ?, updated_at = NOW()
WHERE id = ?;

-- DELETE
DELETE FROM table_name WHERE id = ?;
```
