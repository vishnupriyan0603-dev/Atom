# Git Reference

## Workflow
- `main` = production-ready code.
- `develop` = integration branch.
- Feature branches: `feature/description`.
- Bugfix branches: `fix/description`.
- Release branches: `release/version`.

## Commands
```bash
git checkout -b feature/new-feature
git add -p                    # staged changes interactively
git commit -m "type: message" # conventional commits
git rebase main               # rebase feature on main
git push origin feature/name  # push to remote
git log --oneline --graph     # visualize history
```

## Commit Convention
- `feat:` - new feature
- `fix:` - bug fix
- `refactor:` - code restructuring
- `perf:` - performance improvement
- `docs:` - documentation
- `style:` - formatting, no logic change
- `chore:` - maintenance, deps, config

## Best Practices
- Commit often with atomic changes.
- Write meaningful commit messages.
- Pull/rebase before pushing.
- Never force-push to shared branches.
