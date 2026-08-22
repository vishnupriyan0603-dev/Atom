# Performance Optimization

## Database
- Index columns used in WHERE, JOIN, ORDER BY.
- Avoid N+1 queries: use eager loading.
- Use EXPLAIN to analyze slow queries.
- Paginate large result sets.
- Avoid `SELECT *`: fetch only needed columns.
- Use query caching for expensive queries.
- Consider read replicas for reporting queries.

## Backend
- Cache expensive computations.
- Use opcode caching (OPcache).
- Implement lazy loading for resources.
- Optimize autoloader with Composer.
- Batch database operations in transactions.
- Use queues for heavy processing.

## Frontend
- Minify and bundle CSS/JS.
- Lazy load images and non-critical assets.
- Use browser caching with proper headers.
- Reduce HTTP requests.
- Enable Gzip/Brotli compression.
- Use CDN for static assets.

## Caching Strategy
- **Application cache**: Hot data in memory (Redis/Memcached).
- **HTTP cache**: Browser and reverse proxy cache.
- **Database cache**: Query result cache.
- **Content cache**: Pre-rendered pages.

## Monitoring
- Track response times and query times.
- Set up alerts for performance degradation.
- Profile code to find bottlenecks.
