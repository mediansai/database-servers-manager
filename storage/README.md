# Storage Directory

This directory contains database schema files managed by the File Manager feature.

## Structure

```
storage/
└── schemas/          # Database schema files (.dbschema)
    ├── example1.dbschema
    ├── example2.dbschema
    └── ...
```

## File Format

Files are stored in JSON format with `.dbschema` extension.

## Permissions

Ensure this directory has write permissions:
- Linux/Mac: `chmod 755 storage/schemas`
- The web server user needs write access

## Backup

**Important**: Back up this directory regularly or add to version control.

## Git

To ignore all schema files but keep the directory:
- Add `*.dbschema` to `.gitignore`
- Keep `.gitkeep` file to track empty directory

To track schema files in Git:
- Commit `.dbschema` files for version control
- Great for team collaboration and history tracking

## Security

- Don't store sensitive data in schemas
- Set appropriate directory permissions
- Regular backups recommended
