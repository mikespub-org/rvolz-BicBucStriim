BicBucStriim
============

Forked Branches for PHP 8.x
---------------------------

| Release | Status | PHP Version | Branch | Back-end | Front-end | Upstream (archived) |
|---------|--------|-------------|--------|----------|-----------|---------------------|
| [v3.6.3](https://github.com/mikespub-org/rvolz-BicBucStriim/releases/tag/v3.6.3) | Production | >= 8.2 | [main](https://github.com/mikespub-org/rvolz-BicBucStriim/tree/main) | v3.x | - | [v1](https://github.com/rvolz/BicBucStriim/tree/v1) |
| [v1.7.2](https://github.com/mikespub-org/rvolz-BicBucStriim/releases/tag/v1.7.2) | Maintenance | >= 7.4 |[v1.7.x](https://github.com/mikespub-org/rvolz-BicBucStriim/tree/v1.7.x) | v1.x | - | [v1](https://github.com/rvolz/BicBucStriim/tree/v1) |
| - | Unused | - | [backend](https://github.com/mikespub-org/rvolz-BicBucStriim/tree/backend) | v2.x | - | [master](https://github.com/rvolz/BicBucStriim/tree/master) |
| - | Unfinished | - | [frontend](https://github.com/mikespub-org/rvolz-BicBucStriim/tree/frontend) | v2.x | v2.x | [BicBucStriim-352](https://github.com/rvolz/BicBucStriim/tree/BicBucStriim-352) |

Note: the release packages `bicbucstriim-3.x.x-php8x.zip` include the vendor/ packages for a particular PHP version. If you have an older or newer (supported) PHP version, you can download the `Source code (zip)` for that release, and run *composer* to update the dependencies:
```
$ wget -O bbs-3.x.x.zip https://github.com/mikespub-org/rvolz-BicBucStriim/archive/refs/tags/v3.x.x.zip
$ unzip bbs-3.x.x.zip
$ cd rvolz-BicBucStriim-3.x.x
$ composer update --no-dev -o
```

Updated Framework since v3.0.x
------------------------------

BicBucStriim is now running on the Slim 4 framework with Nikic FastRoute, PSR-15 middleware, PHP-DI container and Nyholm PSR-7 packages.

Note: the jump from v1.x to v3.x is to avoid overlap with 2.x tags from the other branches above, which are all suspended unless someone has an interest in working on them further...

New Code Structure since v1.7.x
-------------------------------

Most of the legacy code inside `index.php` was split off and moved under `config` and `src`, with `config/settings.php` containing the configuration settings, and `config/routes.php` adding the routes to the different `src/Actions` by group. There were no functional changes, but this should make code maintenance a bit easier than one huge file with everything in it :-)

```bash
config/
├── bootstrap.php
├── config.php
├── constants.php
├── langs.php
├── middleware.php
├── routes.php
└── settings.php
index.php
src
├── Actions
│   ├── admin.php
│   ├── default.php
│   ├── main.php
│   ├── metadata.php
│   └── opds.php
├── AppData
│   ├── bicbucstriim.php
│   ├── calibre_thing.php
│   └── ...
├── Calibre
│   ├── calibre.php
│   └── ...
├── Middleware
│   ├── caching_middleware.php
│   └── ...
├── Session
│   ├── ...
│   └── session_factory.php
├── Traits
│   ├── app_trait.php
│   └── ...
├── app.php
├── ...
└── view.php
```

Introduction (original)
-----------------------

BicBucStriim streams books, digital books. It fills a gap in the functionality of current NAS devices that provide access to music, videos and photos -- but not books. BicBucStriim fills this gap and provides web-based access to your e-book collection.

BicBucStriim was created when I bought a NAS device (Synology DS 512+) to store my media on it. NAS devices like the Synology DS typically include media servers that publish audio, video, photos, so that you can access your media from all kinds of devices (TV, smart phone, laptop ...) inside the house, which is very convenient. Unfortunately there is nothing like that for e-books. So BicBucStriim was created.

BicBucStriim is a simple PHP application that runs in the Apache/PHP environment provided by the NAS. It assumes that you manage your e-book collection with [Calibre](https://calibre-ebook.com/). The application reads the Calibre data and publishes it in HTML form. To access the e-book catalog you simply point your ebook reader to your NAS, select one of your e-books and download it. 

Features & Issues
-----------------

* shows the most recent titles of your library on the main page
* there are sections for browsing through book titles, authors, tags and series
* individual books can be downloaded or emailed 
* information about your favourite authors can be added (links, picture)
* global search 
* speaks Dutch, English, French, German, Galician, Italian
* is ready for mobile clients
* provides login-based access control 
* users can be restricted by book language and/or tag
* provides OPDS book catalogs for reading apps like Stanza
* has an admin GUI for configuration

* no support for Calibre's virtual libraries
* only simple custom columns supported


Install
-------

There are 3 options for installation:

1. [Download](http://projekte.textmulch.de/bicbucstriim/downloads/) an installation archive. These are stable releases with a reduced footprint, unnecesary files are removed.
2. Install directly from Github by cloning a [release tag](https://github.com/rvolz/BicBucStriim/releases). These are also stable releases, but contain all files in the repository.
3. Live dangerously and clone/fork the Github master. Please be aware that this branch contains most often a version under development, which could be slow or partially broken.

The easy way assumes that BicBucStriim lives right below the web root of your device and can be addressed like `http://<your ip>/bbs/`:

* Unarchive the downloaded archive below the web server root of your NAS (e.g. "/volume1/web" on a Synology device).
* Rename the newly created directory (e.g. BicBucStriim-1.2.0.zip) to "bbs".
* The "data" directory and its contents must be writeable for all. Depending on your method of unarchiving this might be already the case. However, in case you experience access error just use a terminal to correct this: `chmod -R ga+w data`. 
* BicBucStriim should now be working, start your web browser and navigate to `http://<address of your NAS>/bbs/`
* Login as the administrator with the default login *admin/admin* (please don't forget to change the password afterwards).
* A freshly installed BicBucStriim app will show you the admin section, where you will have tell the app where your Calibre library is located. Everything else is optional. Just have a look.
* OPDS catalogs are available at http://.../bbs/opds/


Upgrading
---------

The database structure of version 1.2 is incompatible with previous versions, so exisiting users should start with a fresh install.
However, if you have lots of books and don't want to regenerate all the thumbnails for them:

* Backup your old BicBucStriim installation, eg. `mv bbs bbs.old`
* Install the new version and run it
* There should be a new directory: `bbs/data/titles`
* Copy the thumbnail files (`thumb_*.png`) from your old `data` directory to `bbs/data/titles`
* Use `chmod -R ga+w bbs/data/titles` to correct the permissions after copying if there are access errors

After that the thumbnails should appear again.


Troubleshooting
---------------

If you encounter problems, use the installation test to check your environment. Invoke this test by navigating to `http://<NAS address>/bbs/installcheck.php`. This test checks for certain problems, which users experienced in the past.


Requirements
------------

BicBucStriim publishes Calibre libraries via a web server, so it requires some modules to be pre-installed on your machine. The required modules are common ones for NAS, however you should check first if your device supports them:

* Apache web server with PHP ~~5.3.7+~~8.0+, including support for ~~mcrypt~~sodium and sqlite3
* Optional: if PHP module *intl* (php~~5~~-intl) is installed, book languages will be displayed

If you can't/won't use Apache: BicBucStriim is known to work with other web servers too. Check the wiki for other configurations.

License
-------

BicBucStriim itself is licensed under the MIT license, for the licenses of the libraries used see the file NOTICE.

(The MIT License)

Copyright (c) 2012-2015 Rainer Volz

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the 'Software'), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED 'AS IS', WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.


