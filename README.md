# ESCO Apps Redirector

ESCO Apps Redirector is a small PHP CAS-based redirector. It authenticates users with phpCAS, reads CAS attributes, resolves the requested application from the `appli` query parameter, and redirects to the configured target URL.

## Documentation

- [Project context](docs/CONTEXT.md): runtime flow, important files, configuration rules, deployment model, and operational notes.
- [Configuration](docs/CONFIGURATION.md): supported mapping modes, variables, filters, replacements, and examples.
- [Application deployment](docs/DEPLOY_APP.md): dry-run by default deployment helper for application sources.

Private production configuration is intentionally stored in a separate private repository and must not be committed here.
