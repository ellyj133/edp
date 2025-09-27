# Database Compatibility Guide

## MariaDB Schema Import Fix

### Issue Resolved
Fixed critical SQL syntax error in `database/schema.sql` that was causing ERROR 1064 during MariaDB import:

```
ERROR 1064 (42000) at line 7: You have an error in your SQL syntax; 
check the manual that corresponds to your MariaDB server version for 
the right syntax to use near 'STRICT_TRANS_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO'''
```

### Root Cause
The schema contained invalid SQL syntax with nested quotes in the SQL mode declaration:
```sql
-- INCORRECT (nested quotes)
SET sql_mode = ''STRICT_TRANS_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO'';

-- CORRECT
SET sql_mode = 'STRICT_TRANS_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO';
```

### Fixes Applied

#### 1. SQL Mode Syntax Error ✅
- **Fixed**: Removed nested quotes in SET sql_mode statement
- **Impact**: Eliminates ERROR 1064 on line 7 during import

#### 2. SQLite-Specific Syntax ✅ 
- **Fixed**: Replaced `INSERT OR IGNORE` with `INSERT IGNORE`
- **Impact**: Ensures compatibility with MariaDB/MySQL

#### 3. Invalid ENGINE Clauses ✅
- **Fixed**: Removed 400+ invalid ENGINE clauses from:
  - CREATE INDEX statements
  - INSERT statements  
  - ADD KEY statements
  - Stored procedure dynamic SQL
- **Impact**: Prevents multiple syntax errors during import

#### 4. Stored Procedure Fixes ✅
- **Fixed**: Cleaned ENGINE clauses from dynamic SQL generation
- **Impact**: Ensures stored procedures execute correctly

### Database Compatibility

#### Supported Versions
- **MariaDB**: 5.5 and newer
- **MySQL**: 5.5 and newer

#### Required Features
- InnoDB storage engine support
- UTF8MB4 character set support
- Foreign key constraint support
- JSON data type support (MariaDB 10.2+/MySQL 5.7+)

### Testing Schema Import

#### Automatic Validation
Run the compatibility test to verify schema syntax:
```bash
php scripts/test_schema_import.php --verbose
```

#### Manual Import Testing
```bash
# Method 1: Direct MySQL import
mysql -u username -p database_name < database/schema.sql

# Method 2: Using setup script
php setup_database.php
```

### Troubleshooting

#### Common Import Errors

1. **ERROR 1064 (Syntax Error)**
   - **Cause**: Invalid SQL syntax
   - **Solution**: Run `php scripts/test_schema_import.php` to validate

2. **ERROR 1050 (Table Already Exists)**
   - **Cause**: Database not empty
   - **Solution**: Use fresh database or add `DROP TABLE IF EXISTS` statements

3. **ERROR 1215 (Foreign Key Constraint)**
   - **Cause**: Referenced table doesn't exist
   - **Solution**: Ensure `foreign_key_checks = 0` at start of import

#### Verification Steps
After successful import:
```sql
-- Check table count (should be 240+)
SELECT COUNT(*) FROM information_schema.tables 
WHERE table_schema = 'your_database_name';

-- Verify foreign key constraints
SELECT COUNT(*) FROM information_schema.key_column_usage 
WHERE referenced_table_name IS NOT NULL 
AND table_schema = 'your_database_name';

-- Check storage engines
SELECT engine, COUNT(*) FROM information_schema.tables 
WHERE table_schema = 'your_database_name' 
GROUP BY engine;
```

### Migration Scripts Compatibility

All database migration scripts use consistent SQL mode handling:
- `database/migrate.php` ✅
- `setup_database.php` ✅  
- `scripts/migrate.php` ✅

### Performance Considerations

The schema includes optimizations for MariaDB:
- InnoDB storage engine for ACID compliance
- UTF8MB4 character set for full Unicode support
- Proper indexing for foreign key relationships
- JSON validation constraints where supported

### Future Maintenance

When modifying the schema:
1. Test syntax with `php scripts/test_schema_import.php`
2. Avoid SQLite-specific syntax
3. Don't add ENGINE clauses to CREATE INDEX statements
4. Use standard SQL syntax compatible with both MariaDB and MySQL