# Behat Features

Behat uses Mink's BrowserKit HTTP session to exercise the app over HTTP. A `--dry-run` checks step matching only; it does not execute the steps.

The initial setup scenarios in `initial/`, the home-page scenario in `general/home.feature`, and the first four Books list/pagination scenarios in `general/titles.feature` are implemented. Most other generated context steps are still pending. BrowserKit form submissions require `symfony/mime` in the shared Behat tools install.

`FeatureSetupContext` runs before each scenario in the admin, author, OPDS, and general suites. It sets the isolated app DB's Calibre library path to `tests/fixtures/lib2`, page size to 2, and disables login-required mode. The initial suite is deliberately excluded because it tests the unconfigured installation flow. Set `BBS_DATA_DB` in both the app-server and Behat processes; the hook refuses to run without a writable DB path.

## Run the Implemented Scenarios

Use a disposable app database. The initial suite uses the fixture admin (`admin` / `admin`) to test login, redirects, and valid/invalid Calibre directory configuration. The home scenario configures `tests/fixtures/lib2` and its requested page size through the web form, then checks the rendered page.

1. Start the app from the repository root:

   ```sh
   mkdir -p /tmp/bbs-behat
   cp tests/fixtures/data2.db /tmp/bbs-behat/data.db
   BBS_DATA_DB=/tmp/bbs-behat/data.db php -S 127.0.0.1:8081 index.php
   ```

2. In a second terminal, run scenarios from the repository root:

   ```sh
   BBS_DATA_DB=/tmp/bbs-behat/data.db BBS_BEHAT_BASE_URL=http://127.0.0.1:8081/ behat --suite=initial_features
   BBS_DATA_DB=/tmp/bbs-behat/data.db BBS_BEHAT_BASE_URL=http://127.0.0.1:8081/ behat --suite=general_features features/general/home.feature
   BBS_DATA_DB=/tmp/bbs-behat/data.db BBS_BEHAT_BASE_URL=http://127.0.0.1:8081/ behat --suite=general_features features/general/titles.feature:6
   BBS_DATA_DB=/tmp/bbs-behat/data.db BBS_BEHAT_BASE_URL=http://127.0.0.1:8081/ behat --suite=general_features features/general/titles.feature:24
   BBS_DATA_DB=/tmp/bbs-behat/data.db BBS_BEHAT_BASE_URL=http://127.0.0.1:8081/ behat --suite=general_features features/general/titles.feature:36
   BBS_DATA_DB=/tmp/bbs-behat/data.db BBS_BEHAT_BASE_URL=http://127.0.0.1:8081/ behat --suite=general_features features/general/titles.feature:47
   ```

`BBS_DATA_DB` must be set for both the server and Behat processes. `BBS_BEHAT_BASE_URL` overrides the Mink URL for the Behat process; without it, the PHP config defaults to `http://localhost/`. The initial suite's valid-directory scenario changes the database, so copy `tests/fixtures/data2.db` over `/tmp/bbs-behat/data.db` before each fresh initial-suite run. Stop the server with Ctrl+C when finished.

To check step matching across all suites without making HTTP requests:

```sh
behat --dry-run --format=progress
```
