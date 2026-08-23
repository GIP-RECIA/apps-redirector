# ESCO Apps Redirector

ESCO Apps Redirector is a small PHP CAS-based redirector. It authenticates users with phpCAS, reads CAS attributes, resolves the requested application from the `appli` query parameter, and redirects to the configured target URL.

## Documentation

These documentation files double as opencode skills (`docs/<skill>/SKILL.md`).

- [Project context](docs/project-context/SKILL.md): runtime flow, important files, configuration rules, deployment model, and operational notes.
- [Configuration](docs/configuration/SKILL.md): supported mapping modes, variables, filters, replacements, and examples.
- [Application deployment](docs/deploy-app/SKILL.md): dry-run by default deployment helper for application sources.

Private production configuration is intentionally stored in a separate private repository and must not be committed here.
