# Application Deployment Script

This document describes `deploy-app.sh`, the application deployment helper.

The script synchronizes the application files from the directory containing the script to a web deployment directory. It intentionally does not deploy private configuration files, logs, Git metadata, or GitHub workflow metadata.

## Quick Usage

Preview changes from the application repository:

```bash
./deploy-app.sh
```

By default, the script runs in dry-run mode and does not modify the destination.

Files are synchronized to:

```text
/var/www/esco-apps-redirector/
```

Deploy for real:

```bash
DRY_RUN=0 ./deploy-app.sh
```

Deploy to a custom destination for real:

```bash
DRY_RUN=0 ./deploy-app.sh /path/to/destination/
```

## Table of Contents

- [Quick Usage](#quick-usage)
- [Custom Destination](#custom-destination)
- [Dry Run](#dry-run)
- [Source Directory](#source-directory)
- [Synchronization Behavior](#synchronization-behavior)
- [Excluded Paths](#excluded-paths)
- [Recommended Workflow](#recommended-workflow)
- [Private Configuration](#private-configuration)

## Custom Destination

Pass the destination directory as the first argument:

```bash
./deploy-app.sh /path/to/destination/
```

Or use the `DEST_DIR` environment variable:

```bash
DEST_DIR=/path/to/destination/ ./deploy-app.sh
```

The first argument has priority over `DEST_DIR`.

## Dry Run

Dry-run mode is enabled by default. Running the script without `DRY_RUN=0` previews changes without modifying the destination:

```bash
./deploy-app.sh
```

This enables `rsync --dry-run --itemize-changes`.

Set `DRY_RUN=0` to perform the synchronization:

```bash
DRY_RUN=0 ./deploy-app.sh
```

## Source Directory

The source directory is resolved from the script location, not from the current working directory.

This means the script can be called from another directory and still deploy the repository where `deploy-app.sh` is stored.

## Synchronization Behavior

The script uses:

```bash
rsync -a --delete
```

`--delete` removes destination files that no longer exist in the application source, except for excluded paths.

## Excluded Paths

The following paths are excluded from application deployment:

```text
.git/
.github/
conf/conf.inc.php
conf/conf.inc.test.php
conf/cas.inc.php
conf/cas-test.inc.php
conf/*.bkp.*
logs/*
```

Private configuration files must be deployed separately and are never overwritten by this script.

Log files are not synchronized. The script ensures that the destination `logs/` directory exists after synchronization.

## Recommended Workflow

1. Update the application source repository.
2. Preview deployment with `./deploy-app.sh`.
3. Review the listed changes.
4. Run `DRY_RUN=0 ./deploy-app.sh`.
5. Deploy private configuration separately if needed.
6. Verify the application through the web entry point.

## Private Configuration

Keep private configuration in a separate private repository or secure location.

Typical private files are:

```text
conf/conf.inc.php
conf/conf.inc.test.php
conf/cas.inc.php
conf/cas-test.inc.php
```

Before changing private configuration files in production, create a timestamped backup as described in `docs/CONTEXT.md`.
