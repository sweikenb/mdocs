# mdocs

Beside php 8.1+ mdocs also rely on `scss` / `yarn` for the compiling of the theme styles.

## Add GIT commit hook

Execute in project root:

```bash
rm -f "$(pwd)/.git/hooks/pre-commit"
ln -s "$(pwd)/bin/codequality" "$(pwd)/.git/hooks/pre-commit"
```
