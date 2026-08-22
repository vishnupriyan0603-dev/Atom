---
name: database
description: Use when designing schemas, writing queries, adding indexes, or optimizing database performance. MySQL/MariaDB focus.
---

# Database Skills

## Design
- Normalize to 3NF unless performance dictates denormalization
- Use appropriate data types (not all text/VARCHAR)
- Define foreign keys for referential integrity
- See `.ai/skills/mysql.md` for data type reference
- Never rename columns or tables
- See `.antigravity/database.md` for project schema

## Indexing
- Index columns in WHERE, JOIN, ORDER BY clauses
- Composite indexes: most selective column first
- Avoid over-indexing
- See `.ai/core/performance.md` for strategy

## Querying
- Use EXPLAIN to analyze execution plans
- Never `SELECT *` in production
- Paginate large result sets
- Use transactions for multi-table writes
- Always use CodeIgniter Query Builder or prepared statements
