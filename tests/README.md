# Tests

Automated checks are organized by module and architecture layer.

## MyLife PHP client

Run the signing and local target validation checks from the repository root:

```console
php tests/run_mylife_client_tests.php
php tests/run_mylife_tool_tests.php
```

To test the HTTP transport against synthetic responses, run the integration check from the repository root. It starts and stops the local mock server itself. It verifies the pairing proof, signed status and summary reads, signed query encoding and parameter filtering, a signed JSON write, and handling of a server validation error:

```console
php tests/run_mylife_client_integration.php
```

The mock uses a fixed test secret and fixture responses. It never contacts a phone or reads personal data.

