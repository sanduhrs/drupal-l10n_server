$Id$

Localization server module suite
--------------------------------------------------------------------------------
Project page:  http://drupal.org/project/l10n_server
Support queue: http://drupal.org/project/issues/l10n_server

ABOUT
--------------------------------------------------------------------------------

The localization server project (formerly known as lt_server) provides a 
community localization editor, which allows people from around the world to 
collaborate on translating Drupal projects to different languages. It is
inspired by Launchpad Rosetta (https://launchpad.net/rosetta) but is highly
tailored to Drupal needs.

The module suite solves the Drupal project translation problem with a web
based interface. The various Drupal projects release source code on a daily
basis. The goal is to help translators to keep up with this pace by sharing
already translated strings and distributing translations effectively.

The localization server module suite consists of two modules:

 - l10n_server: Scans Drupal projects for translatable strings and stores
   these in the database. The translations are syndicated to sites
   running l10n_client.module (in a separate project).
   
 - l10n_community: A translation community interface using "Organic
   Groups" module to handle the teams.

INSTALLATION
--------------------------------------------------------------------------------

- Your PHP installation should have the PEAR Tar package installed (which
  requires zzlib support). Files are simply copied from the drupal.org
  server, so allow_url_fopen needs to be enabled.
- Locale (built into Drupal) and Organic Groups
  (http://drupal.org/project/og) modules are required.

1. Enable both modules at Administer > Site configuration > Modules.
2. Configure the server at Administer > Site configuration > Localization Server.

HOW DOES IT WORK
--------------------------------------------------------------------------------

The localization server scans new/updated projects for new strings on every
cron run. Manual scan of projects is possible by going to Administer > Site
configuration > Localization Server > Scan.

This module comsumes a huge amount of resources. It downloads each project
package from drupal.org, extracts their contents and scans them for translatable
strings. Although only temporal copies of the packages are kept, some hard disk
space and a decent amount of memory is required. This is why the module is
preconfigured to scan only one project at a time. Big projects like
E-Commerce or Drupal itself take considerable time to parse.

The localization community module couples the server with translation team
capabilities relying on Organic Groups. Group members can suggest new
translations for strings, maintainers can even decide on the official
translation based on the different suggestions. To translate a project, go
to Translations, choose a language and choose the project. There you
can translate all strings.

For more details, look into the documentation directory.

DEVELOPERS
--------------------------------------------------------------------------------

This module suite is in heavy development now. Better cooperation with existing
modules and more interoperability functions are planned. The goal is to have
this module used as the official translation interface for Drupal modules.
We take pride by coding to Drupal, PHP E_ALL and E_STRICT coding standards,
as well as XHTML Strict and CSS 2 compliance.

CONTRIBUTORS
--------------------------------------------------------------------------------
Bruno Massa  http://drupal.org/user/67164 (original author)
Gábor Hojtsy http://drupal.org/user/4166 (current maintainer)

This module was originally sponsored by Titan Atlas (http://www.titanatlas.com),
a brazilian computer company, and is sponsored now by Google Summer of Code 2007.
