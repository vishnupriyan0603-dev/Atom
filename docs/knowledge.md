# Atom AI - Knowledge Base & RAG

This document details the knowledge base and retrieval-augmented generation (RAG) pipeline.

## Ingestion Pipeline

```
PDF / Document
      ↓
File Hash Check (Duplicate Detection)
      ↓
Text Extraction & Ingestion
      ↓
Cleaning & Pre-processing
      ↓
Semantic Chunking (1000 chars, 200 overlap)
      ↓
MySQL Indexing (`atom_document_chunks` with FULLTEXT index)
```

## Retrieval Pipeline

1. **Query analysis**: Determine if the query requests reference books or documentation.
2. **Database Search**: Query the FULLTEXT index matching the query terms.
3. **Relevance Selection**: Extract the top matching chunks (up to 3) and inject them into the system prompt context.
