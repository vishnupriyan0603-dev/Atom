# Caching Reference

## Types
- **Browser cache**: Static assets (CSS, JS, images) via `Cache-Control` headers.
- **Application cache**: Expensive computations or DB results in memory.
- **Database cache**: Query result cache, prepared statement cache.
- **CDN cache**: Geographic distribution of static content.
- **OPcache**: PHP bytecode cache.

## Cache Headers
- `Cache-Control: public, max-age=31536000, immutable` - versioned assets.
- `Cache-Control: no-cache, must-revalidate` - dynamic content.
- `ETag` / `Last-Modified` for conditional requests.
- `Expires` header for HTTP/1.0 fallback.

## Redis
```bash
redis-cli
SET key value EX 3600  # set with 1hr expiry
GET key
DEL key
```

## Cache Invalidation
- The two hardest problems: naming things and cache invalidation.
- Strategies: TTL-based, event-based, version-based.
- Never trust cache without invalidation strategy.
