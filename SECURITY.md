# Security Policy

## Reporting

If you discover a security issue, do not open a public issue with exploit details. Report it privately to the project maintainer before disclosure.

## Current trust model

This project is still an MVP and should be treated as trusted-admin software.

- Dashboard users with configuration access can define downstream HTTP, Sentry, and file targets
- Do not expose admin access to untrusted users
- Do not point downstream integrations at untrusted internal services or sensitive filesystem paths

## Hardening notes

Before using this outside local development, review at least:

- environment variables and admin bootstrap flow
- outbound HTTP trust boundaries
- downstream file path handling
- registration and admin assignment rules
