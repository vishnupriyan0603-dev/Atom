# CodeIgniter Reference

## MVC Structure
- Models: `application/models/` - database interactions.
- Views: `application/views/` - presentation templates.
- Controllers: `application/controllers/` - request handling.
- Libraries: `application/libraries/` - reusable components.
- Helpers: `application/helpers/` - utility functions.
- Config: `application/config/` - configuration files.

## Key Classes
- `CI_Controller` - base controller.
- `CI_Model` - base model.
- `CI_DB_query_builder` - database query builder.
- `CI_Input` - input handling.
- `CI_Output` - response handling.
- `CI_Session` - session management.
- `CI_Form_validation` - form/input validation.

## Query Builder
```php
$this->db->select('id, name');
$this->db->from('users');
$this->db->where('status', 'active');
$this->db->order_by('name', 'ASC');
$query = $this->db->get();
$results = $query->result();
```
- Always use query builder for safety.
- Never use raw queries unless absolutely necessary.
- Use `$this->db->escape()` for raw query values.
