# Atom AI - Security & Permissions

This document covers the safety layer and execute policies within Atom AI.

## Safe Mode

Safe mode limits destructive actions. All operations are classified and restricted:

1. **READ**: Safely execute automatically (e.g. read files, search project, query knowledge).
2. **WRITE**: Require confirmation (e.g. write files, edit source code, change configs).
3. **DELETE**: Require explicit confirmation (e.g. delete memories, remove documents).
4. **EXECUTE**: Require validation depending on scripts (e.g. php/composer commands).
5. **DANGEROUS**: Always require explicit permission (e.g. DROP DATABASE, DROP TABLE).
