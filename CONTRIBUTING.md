# Contributing

Thank you for your interest in improving the iWorks GT Service Portal.

## Development workflow

1. Create a focused branch from `main`.
2. Keep credentials and runtime data out of the repository.
3. Follow the existing PHP style and use strict types in new PHP files.
4. Run the PHP lint command and both smoke tests before opening a pull request.
5. Describe the user-facing impact and any database changes in the pull request.

## Database changes

Schema changes should be safe for existing installations. Update `database.sql` and the relevant runtime upgrade function when a change must support deployed environments.

## Security

Do not open a public issue for a suspected vulnerability. Follow the private reporting guidance in `SECURITY.md`.
