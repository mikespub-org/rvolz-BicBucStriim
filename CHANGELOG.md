# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [3.x.x] - 20xx-xx-xx
### Changed

## [3.4.0] - 2024-04-26 Basic json API + environment vars + clean-up
### Added
- Enable basic json api interface by configuring $settings['hasapi'] = true;
- Make $settings['hasapi'] configurable via environment variable 'BBS_HAS_API'
- Make initial app name configurable via environment variable 'BBS_APP_NAME'
- Make base path configurable via environment variable 'BBS_BASE_PATH'
- Make debug mode configurable via environment variable 'BBS_DEBUG_MODE'
### Changed
- Move default settings to Settings class + use properties to access
- Split config/config.php file based on configuration mode
- Move identification of current language + loading L10n to login middleware
- Split config/langs.php file into lang/messages.{lang}.php files for L10n
- Rename most source class files + adapt RedBeanPHP models in line with PSR-4

## [3.3.0] - 2024-04-22 Use ActionsWrapperStrategy + update dependencies
### Changed
- Invoke callable actions via ActionsWrapperStrategy instead of using wrapper for each action in routes
- Updated dependencies + switched to phpunit 10.5
- Updated middlewares/cache package to new release 4.0

## [3.2.0] - 2023-12-15 Replace mailer package
### Changed
- Replaced abandoned package swiftmailer/swiftmailer with phpmailer/phpmailer

## [3.1.0] - 2023-12-13 Custom template directories like tailwind
### Added
- Added tailwind css templates from v2.x frontend
- Added support for custom template directories
### Changed
- Aligned template extension with v2.x frontend (.twig)
- Updated javascript library versions a little bit (limited by jQuery Mobile)
- Fixed e-mail validation check - see #2
### Removed
- Dropped unused epiceditor js and related themes

## [3.0.0] - 2023-12-10 Switch to Slim 4 framework
### Changed
- Switched to PSR-3/7/11/15/17 + Slim 4 framework
### Removed
- Dropped incompatible package slim/logger from composer.json
- Removed abandoned package slim/views from lib/SlimViews

## [1.7.2] - 2023-12-10 Prepare framework switch
### Added
- Added BicBucStriim\Session and BicBucStriim\Utilities namespaces
### Changed
- Clean up actions and middleware to prepare framework switch
- Split utilities and move files to BicBucStriim\Utilities namespace
- Moved lib/BicBucStriim to src directory to clarify source code
- Moved session files to lib/BicBucStriim/Session
- Moved config files to config directory and split to clarify source code
- Changed mapping of routes to actions to make it more adaptable
- Moved Slim 2 app dependencies to trait and added standard responses
- Adapted Twig files from abandoned slim/views package in lib/SlimViews for maintenance
- Updated twig/twig package to version 2.15 and then 3.8 for maintenance
### Removed
- Dropped abandoned package slim/views from composer.json

## [1.7.0] - 2023-12-06
### Added
- Add namespaces to AppData, Calibre and Middleware classes
- New RedBeanPHP FUSE model for artefact, idtemplate, link and note
### Changed
- Split off actions by group from index.php
- Split off config and routes from index.php
- Moved middleware files to lib/BicBucStriim/Middleware
- Moved Calibre items and searchtype from utilities.php to lib/BicBucStriim/Calibre
- Moved calibre_thing.php, config.php and user.php to lib/BicBucStriim/AppData
- Fixed one-to-many relations for 'calibrething' authors with tests
- Updated gabordemooij/redbean package to version 5.7 for PHP 8.x
- Fixed a few more phpstan level 3 issues
- Moved vendor/rb.php to lib/BicBucStriim using gabordemooij/redbean package
- Moved vendor/epub.php to lib/BicBucStriim
- Replaced vendor/DateTimeFileWriter.php with package slim/logger
- Replaced abandoned package dflydev/markdown with michelf/php-markdown
### Removed
- Removed package ircmaxell/password-compat as no longer relevant (PHP < 5.5)

## [1.6.6] - 2023-12-04
### Changed
- Merged #1 by @Tocamadera, Amazon will start winding down MOBI format

## [1.6.5] - 2023-07-25
### Changed
- Use production mode by default

## [1.6.4] - 2023-03-26
### Added
- Added placeholders in data/titles and data/authors
### Changed
- Adapted Dockerfile for permissions
- Updated version and changelog

## [1.6.3] - 2023-03-25
### Added
- Added docker files

## [1.6.2] - 2023-03-22
### Changed
- Added fixes for PHP 8.2

## [1.6.1] - 2023-03-20
### Changed
- Run php-cs-fixer with PHP80Migration rule

## [1.6.0] - 2023-03-20
### Added
- Added deprecated.php file for get_magic_quotes_gpc()
### Changed
- Updated package versions in composer.json
- Replaced JS packaging library with PHP version due to packaging error, #369

## [1.5.3] - 2021-07-01
### Added
- installcheck: Check for Sodium when using PHP 7.4+
### Changed
- Changed packaging script to avoid duplicate files and reduce archive size

## [1.5.2] - 2021-06-28
### Changed
- Fixed packaging script, #367 

## [1.5.1] - 2021-06-20 
### Added
- JS dependencies for build
### Changed
- ChangeLog to CHANGELOG.md
- Added workaround for PHP 7.4 and Aura Auth, see #348
### Removed
- Ruby dependencies for build

## [1.5.0] - 2018-11-16 
### Changed
- Merged Polish translation by @xro, #299
- Updated infrastructure

## [1.4.1] - 2017-11-28 
### Changed
- Updated MIME type for AZW3 download, #265

## [1.4.0] - 2017-11-08
### Added
- Enabled language processing für ES, GL, HU
- Merged code by @rand82 for showing series in author pages, #255
- Merged #260 by @ramsnerm, human readable filesize for book download
- Added forwarding-sensitive url generation to login middleware, #210
- Added Hungarian translation by @gersey, #202 
### Changed
- Merged #257 by @josefglatz, .htaccess for Apache 2.3+
- Fixed #262, added unicode handling for ID templates
- Integrated spanish translation updates by @Tocamadera, #223
- Integrated french translation updates by @Draky50110, #220, #222
- Corrected OPDS navigation links for series, fixes #215
- Search fields in panels will now get automatic focus on desktops, #183
- Added workaround to search for items with non-ascii names, #206
- Fixed #204, failing GD test in installation check due to new phpinfo format
- Fixed #208, Problems due to strict error handling in newer PHP versions
- Fixed #200, bad parameter tests for id templates

## [1.3.6] - 2015-12-21
### Changed
- Handled more edge cases for old or incorrect session cookies to make the transition easier

## [1.3.5] - 2015-11-22
### Changed 
- Fixed tag resolution and easy mode, #190

## [1.3.4] - 2015-10-08
### Changed 
- Merged fixes by @Chouchen for installcheck.php (Calibre library problem detection)
- Fixed authentication for OPDS request

## [1.3.3] - 2015-10-05 
### Changed
- Fixed bad SQL for OPDS tag handling
- Merged improved counter handling for empty DBs by @OzzieIsaacs

## [1.3.2] - 2015-10-03
### Changed 
- More SQL changes for QNAP users

## [1.3.1] - 2015-10-01
### Changed
- Correction for configuration loading in PHP 5.5+

## [1.3.0] - 2015-10-01
### Added
- Added sorting by date to books view #116 #99 #50
- Added admin configuration to specify the kind of date used for sorting (timestamp, pubdate or modified)
- Added Aura\Session library to handle sessions properly, #173
### Changed
- Users can switch between title and date sorting in titles view
- Updated layout
- Replaced outdated auth library Slim\Strong with Aura\Auth
- Added workaround for SQL statements due to outdated SQLITE libs on QNAP devices, #146

## [1.2.6] - 2015-09-11
### Changed 
* Security changes: protection against SQL injection, see #175

## [1.2.5] - 2015-05-27
### Added
* Added a switch for disabling the multi-user login requirement, #88
* Added icon for Apple home screens etc, #97
### Changed
* Updated dependencies: Slim 2.4.3, Twig 1.16
* Updated jQuery Mobile dependency
* Added french typo corrections, #111 by @murdos
* Added pull request #102 (@jampot), all series in OPDS
* Added pull request #140 (@alexandregz), Galician language
* Modified book download, added headers for better Tolino compatibility 
* Fixed issue #107, the app returns now 401 for unauthorized download requests via OPDS
* Added pull request #103 (@jampot), add series name/number to emailed files
* Clarified the requirements for the ap, #122
* Added pull request #104 (@jampot), set user roles in admin menu

## [1.2.4] - 2014-07-20
### Changed
* Fixes issue #101 etc. undeclared function "fnmatch" in configuration page
* Fixes issue #101 etc. incorrect display for URL reqriting in installcheck

## [1.2.3] - 2014-07-13
### Changed
* Included fix for issue #90 (@OzzieIsaacs), changing the clipping of image thumbnails 
* Included fixes for searches with embedded apostophes (@OzzieIsaacs)
* Included deletion of thumbnails on library change (@OzzieIsaacs)

## [1.2.2] - 2014-03-30
### Changed
* Fixed issue #85 
* Included fix for issue #68 (@janeczku)

## [1.2.1] - 2014-02-18
### Changed
* Included fix for issue #68 (@janeczku)

## [1.2.0] - 2014-02-15
### Added
* Added author metadata handling (thumbnail, links)
* Added cache-control for admin pages, see issue #81
* Added ID links to title details and link management to admin section, issue #60
* Added dependeny slim/views, removed slim/extras
### Changed
* Separated Calibre and BicBucStriim DB handling
* Replaced PHP mail support with more flexible SwiftMailer
* Add book languages to the listviews and title details page, issue #56
* The series index number is now displayed in the series and title details scrrens, issuea #58, #71
* Dropped download protection and added login system, issue #47
* Upgraded dependency jQuery Mobile to 1.4.0
* Upgraded dependency jQuery to 1.10.2
* Upgraded dependency Slim to version 2.3.1
* Upgraded dependency Twig to 1.13.1
* PHP mcrypt extension is now required? (php5-mcrypt)

## [1.1.0] - 2013-05-07
### Added
- Added custom columns display to title details page, issue #33
- Added a global search, displaying the first X results for every category, issue #17
- Added link to installation check page if the admin page has errors, issue #28
- Added tag based download protection (@blowk, issue #39)
### Changed
- Upgraded Javascript dependencies
- Fixed: display of back button, issue #31
- Fixed: database file was accessible (@janeczku, issue #45)
- Added sending books via e-mail (@janeczku)
- Internal DB will be created if it doesn't exist
- Fixed: Logging configuration (@janeczku, issue #34)

## [1.0.0] - 2012-12-XX 
### Added
- new: app title is now configurable
- new: title search for OPDS
- new: installation check page, installcheck.php
- new: dutch translation
### Changed
* changed: title entries now show available formats
* updated dependencies (jQuery 1.8.3, jQuery Mobile 1.2.0)

## [0.9.3] - 2012-09-22
### Changed 
- security fix: issue #12, admin password was displayed in password dialog

## [0.9.2] - 2012-08-08
### Changed 
- fixed issue #4, sort order of books in series now according to field series_index

## [0.9.1] - 2012-08-08 
### Changed
- updated dependencies (jQuery 1.7.2, jQuery Mobile 1.2.0 alpha 1)

## [0.9.0] - 2012-08-02 
### Added
- new: french translation, thanks to Thomas Parvais
- new: display of book series, thanks to DarkHunter85 (https://github.com/DarkHunter85)
- new: pagination for titles, authors, series and tags makes navigation in larger libraries easier
- new: OPDS catalog support
### Changed
- updated: added more details to list views, thanks to DarkHunter85 

## [0.8.0] - 2012-06-18 
### Added
- new: admin section for configuration, no config.php editing anymore
- new: thumbnail support for book title listings
- new: author and tags listings show the number of books per item
### Changed
- changed: removed the RedBean PHP dependency

## [0.7.0] - 2012-05-31
### Added
- new: support for Calibre book tags, code donated by mapero (https://github.com/mapero)
- new: optional download protection (password)
### Changed 
- updated dependencies (Slim 162, Redbean PHP 3.2.1)
