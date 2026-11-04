# Contributing to Lunar Download

Thank you for taking the time to help. Bug reports, ideas, and pull requests are all welcome.

By taking part, you agree to follow the [Code of Conduct](CODE_OF_CONDUCT.md). To report a security problem, please do not open a public issue; follow [SECURITY.md](SECURITY.md) instead.

## Reporting a bug

Open an [issue](https://github.com/irvangen09/lunar-download/issues) and include:

- What you expected, and what happened instead.
- The steps to reproduce it, ideally starting from a new post with a single block.
- Your versions of Lunar Download, WordPress, and PHP, and the theme you use.
- A screenshot or a short screen recording for visual problems.
- Any message from the browser console or from `debug.log`.

## Suggesting a change

Open an issue before you start on anything larger than a small fix, so the idea can be discussed first. Lunar Download is deliberately small: it has one block for presenting download information, so a new feature is accepted when it serves that purpose and works in any theme.

## Setting up

You need Node.js (`^22.22.2`, `^24.15.0`, or `>=26`) with npm, and PHP 8.0 or later with Composer for PHP linting and tests. See the [README](README.md#development) for the setup steps and the list of commands.

Use a local WordPress 6.9 or later site, with the plugin folder placed in `wp-content/plugins/`. The plugin loads the compiled files from `build/`, so run `npm run build` (or `npm start`) after every change to `src/`.

## Before you open a pull request

Run all of these and make sure they pass:

```bash
npm run lint:js
npm run lint:css
npm test
composer lint:php
composer test
npm run build
```

The same checks run on every pull request, with PHP 8.0 and PHP 8.3.

Then check your change in the editor and on the front end, with at least one block theme and one classic theme if you can. For anything interactive, try it with the keyboard only, and with a screen reader if you have one.

## Guidelines

**Code**

- PHP follows the WordPress Coding Standards (`phpcs.xml`). JavaScript and Sass follow the `@wordpress/scripts` lint rules. Files end with a single line break and use LF line endings (see `.editorconfig`).
- Code comments are in English, short, and explain only what is not obvious from the code. Do not use `@since` tags.
- Every user-facing string goes through `__()`, `_n()`, or a related function with the text domain `lunar-download`.
- The plugin has no Composer packages at runtime. Composer is used for development tools only.
- Logic that does not need WordPress is kept free of WordPress function calls, so it can be tested directly with PHPUnit.

**Design**

- Use the design tokens from `assets/css/tokens.css` (`--lunar-dl-*`) instead of fixed colors, radii, and spacing. Lengths outside the spacing scale are written as `calc(var(--lunar-dl-unit) * N)`, and font sizes use `em`. A token is added only when an existing one cannot do the job.
- Do not hard-code colors and do not add a dark mode. The block inherits fonts and text color from the theme.
- Use the standard radius, `var(--lunar-dl-radius)`.
- Show a keyboard focus ring only for keyboard users, with `:focus-visible`.
- Use logical properties (`margin-inline-start`, `padding-inline-end`, `text-align: start`) for anything that depends on the reading direction.
- The block must work with the keyboard and with assistive technology, and must show its information and links without JavaScript.

**Saved content**

The block is rendered on the server, so a change to the markup it prints applies to every saved entry and needs no migration. What is stored in people's posts and settings is different: the block name, the attribute names and types, the field slugs, the option keys, and the format of stored values. After the first public release, changing any of them needs a tested migration. The defaults in `block.json` are applied when the page is rendered, so changing one changes the meaning of every saved entry that does not write that value; treat it as a change in behavior and discuss it first.

**Commits and pull requests**

- Write commit messages in the style `type(scope): short summary` in lowercase, for example `fix(links): keep nofollow on external links`. Types in use are `feat`, `fix`, `perf`, `refactor`, `docs`, `test`, and `chore`.
- Keep a pull request to one topic, and describe what changed and how you checked it.
- For a change users can notice, add a line to the `[Unreleased]` section of [CHANGELOG.md](CHANGELOG.md), written for site owners rather than developers. Do not change version numbers; maintainers do that when a release is prepared.

## Translations

The text domain is `lunar-download`. Translation files are not bundled yet. When they are, they will live in the `languages/` folder.

## License

By contributing, you agree that your contribution is licensed under the [GNU General Public License v2.0 or later](LICENSE), the same as the plugin.
