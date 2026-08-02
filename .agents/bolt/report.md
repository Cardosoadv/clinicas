## Registros do Jules 🧠

## 2026-03-26 - [CodeIgniter 4 Migration index naming]
**Learning:** For database migrations in CodeIgniter 4, relying on default index names for composite keys can be fragile, especially when using `dropKey()`. Providing explicit index names (e.g., `idx_age_data_hora`) ensures that both the `up` and `down` methods are reliable and predictable across different database drivers.
**Action:** Use the fourth parameter of `addKey()` to specify an explicit index name, and use that same name when calling `dropKey()`.

## 2026-03-26 - [Agendamentos performance bottleneck]
**Learning:** The `agendamentos` table was missing indexes on `age_data` and `age_hora`, which are the primary filtering and sorting fields for the agenda view. This would lead to full table scans as the appointment history grows.
**Action:** Added a composite index on `(age_data, age_hora)` to optimize date-based retrieval and sorting, and a single index on `age_status` for dashboard statistics queries.
## 2026-03-27 - [Indexing and SARGability for Date-based Queries]
**Learning:** Using SQL functions like `MONTH()` and `YEAR()` in `WHERE` clauses prevents the database from using indexes (non-SARGable). Also, single queries with conditional sums (`SUM(CASE...)`) often force full table scans even if indexes are present.
**Action:** Always prefer date range comparisons (`>= '2024-01-01' AND <= '2024-01-31'`) over functions, and use separate `countAllResults()` calls to leverage indexes on specific fields.

## 2026-03-29 - [Optimizing "Greatest-N-Per-Group" Queries]
**Learning:** Selecting `*` in subqueries for "greatest-n-per-group" patterns (like finding the latest appointment) is highly inefficient as it materializes temporary tables with unused large fields (TEXT/BLOB). A composite index on `(foreign_key, primary_key)` significantly accelerates these lookups.
**Action:** Always use explicit column selection in JOIN subqueries and add composite indexes on the grouping and ordering fields.

## Registros Antigravidade 🚀

