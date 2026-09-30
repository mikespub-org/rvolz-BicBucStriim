## Tailwind CSS Custom Templates

This directory contains assets and scripts for *front-end development* of the Tailwind CSS templates,
slightly adapted from the v2.x frontend branch.

All Tailwind-specific development happens in this directory:

```
cd assets/tailwind
yarn install
```

Local development artifacts (`yarn.lock`, `dist/`, `.pnp.*`, `.yarn/`) are not committed;
only the generated production file `style/tailwind/style.css` is.

Build scripts (run from this directory, Node >= 22):

- `yarn development` — intermediate build to `dist/style/style.css` (untracked)
- `yarn serve` — same, in watch mode
- `yarn production` — production build to `../../style/tailwind/style.css` (committed)

The composer-scripts.json file contains examples of development workflow scripts you could adapt
for your own composer.json file.
