# Laravel Reference

## Artisan Commands
- `php artisan make:model -m` - Model with migration.
- `php artisan make:controller --resource` - Resource controller.
- `php artisan make:request` - Form request validation.
- `php artisan route:list` - List all routes.
- `php artisan migrate` - Run migrations.
- `php artisan db:seed` - Seed database.

## Eloquent ORM
- Use `Model::query()` for complex queries.
- Eager load relationships: `User::with('posts.comments')`.
- Use `$guarded` or `$fillable` for mass assignment protection.
- Cast attributes: `protected $casts = ['is_admin' => 'boolean']`.
- Use accessors/mutators for computed attributes.

## Validation
- Use Form Requests for complex validation.
- Available in controllers for simple cases.
- Custom validation rules extend `Rule` class.

## Common Patterns
- Service Provider for binding interfaces.
- Event/Listener for decoupled logic.
- Job/Queue for background processing.
- Policy for authorization.
- Notification for user alerts.
