---
title: mdocs
description: Static site generator for documentations based on Markdown files.
keywords: Markdown, Documentation
---

# mdocs Documentation

[[[ CONTENT_INDEX ]]]

This documentation generator aims to simplify the process of generating documentation websites for any size of project
using the simple Markdown markup language.

If you are new to Markdown, please check this excellent guid: [Markdown Guide](https://www.markdownguide.org/)

## Quick Start {#Custom-anchor-ID}

If you are using relative links to all your documents and assets you are already good to go without any additional
configuration.

### Example

Just run this command tu build your documents:

```bash
# source and target directory must be absolute or relative to the mdocs project root
php bin/console build:dir "/path/so/dir/with/markdown-files" "/build/directory/path"
```

### Extended Example

If you want to use absolute links inside the documentation, use these flags:

```bash
php bin/console build:dir "/path/so/dir/with/markdown-files" "/build/directory/path" \
  --base-url="http://example.com" \
  --base-path="/my/sub-directory"
```

* `--base-url` Defines the URL under which the documentation will be hosted and should be used for links
* `--base-path` Defines and additional path-prefix in case you do not want to directly serve the documentation from the
  root-path (`/`)

## Topics

[[[ PAGE_INDEX ]]]
