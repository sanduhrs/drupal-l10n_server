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

The localization server module suite consists of two components:

 - l10n_community: A translation community interface using "Organic
   Groups" module to handle the teams. This module provides the database
   backend to store projects and releases, but does not fill these with
   actual data.
   
 - A connector module: Connectors serve the purpose of filling in the actual
   list of projects, releases and strings used in the released packages.
   Different connectors allow this suite to be used in different use cases.
   
     - l10n_drupalorg: The original connector developed for this module.
       Maintains a relation with the drupal.org project and release listing,
       downloads tarballs, collects translatables.
       
     - More connectors. To be done.

INSTALLATION
--------------------------------------------------------------------------------

- Your PHP installation should have the PEAR Tar package installed (which
  requires zzlib support). This is required for Tar extraction (eg. in
  l10n_drupalorg module) and Tar generation (eg. in l10n_community module).
- With l10n_drupalorg module, files are simply copied from the drupal.org
  server, so allow_url_fopen needs to be enabled.
- Locale (built into Drupal) and Organic Groups
  (http://drupal.org/project/og) modules are also required.

1. Enable l10n_community and *only one* of the connector modules at
   Administer > Site configuration > Modules.
2. Configure the connector at Administer > Site configuration.

HOW DOES IT WORK
--------------------------------------------------------------------------------

The connector module's duty is to maintain a list of projects and releases, as
well as fill up the database with translatable strings based on release source
codes. This modules comsume a huge amount of resources. Downloading packages,
unpacking their contents and running the string extraction process takes time,
CPU cycles and hard disk space. Although only temporal copies of the packages
are kept, some hard disk space and a decent amount of memory is required. This
is why connectors are preconfigured to scan only one project at a time. Big
projects like E-Commerce or Drupal itself take considerable time to parse.

The localization community module provides the actual interface giving
translation team capabilities relying on Organic Groups. Group members can
suggest new translations for strings, maintainers can even decide on the
official translation based on the different suggestions. To translate a
project, go to Translations, choose a language and choose the project.
There you can translate all strings.

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
a brazilian computer company, and then by Google Summer of Code 2007.
