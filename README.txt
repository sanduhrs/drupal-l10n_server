ABOUT
--------------------------------------------------------------------------------

Localization Server provides a community localization editor, which allows
people from around the world to collaborate on translating Drupal projects to
different languages. It is inspired by Launchpad Rosetta
(https://launchpad.net/rosetta) but is highly tailored to Drupal needs.

This module suite powers the base functionality of http://localize.drupal.org.

The module suite solves the Drupal project translation problem with a web
based interface. The various Drupal projects release source code on a daily
basis. The goal is to help translators to keep up with this pace by sharing
already translated strings and distributing translations effectively.

The localization server module suite consists of a few possible components:

 - l10n_server: Required. The base module for storing projects and releases and
   their translation source strings and translations as well as suggestions with
   historic data.

 - l10n_community: Required. A translation community interface which provides
   the means to enter translations and suggestions for strings. Uses a role
   based permission model.

 - l10n_groups: Optional. An "Organic Groups" module binder, which provides
   permission handling based on language groups (in addition to the default
   role based model used by l10n_community).

 - l10n_packager: Optional. Automated .po file generation system to export
   release translations regularly to the file system.

 - l10n_remote: Optional. Support for remote translation submission, so people
   can use client modules like Localization Client to submit translations.

 - A connector module: One required, only use one at a time. Connectors serve
   the purpose of filling in the actual list of projects, releases and strings
   used in the released packages. Different connectors allow this suite to be
   used in different use cases.

     - l10n_gettext: Creates projects and releases based on the name of .po
       files uploaded on the fly.

     - l10n_drupal: Works based on a list of Drupal project release files
       uploaded to a local file system directory. The projects and releases are
       identified based on placement and naming of the package files.

     - l10n_drupal_rest: To be used on Drupal.org only! Maintains a relation
       with the Drupal.org project and release listings, syncronizes tarballs,
       collects translatables automatically.

INSTALLATION
--------------------------------------------------------------------------------

- Your PHP installation should have the PEAR Tar package installed (which
  requires zzlib support). This is required for Tar extraction (in the
  l10n_localpacks module) and Tar generation (in the l10n_community module).

- Locale (built into Drupal) is required. Organic Groups
  (http://drupal.org/project/og) is required by l10n_groups.

- For the easiest local testing setup, install "Localization Server connector
  for Gettext files" which should enable all dependencies. Enable the connector
  in the administration interface and set up a project with a .po file.

CONTRIBUTORS
--------------------------------------------------------------------------------

Bruno Massa  http://drupal.org/user/67164 (original author)
Gábor Hojtsy http://drupal.org/user/4166 (current maintainer)
Sébastien Corbin https://www.drupal.org/u/sebcorbin (port to Drupal 7)
Tobias Bähr https://www.drupal.org/u/tobiasb (in progress port to Drupal 9)

This module was originally sponsored by Titan Atlas (http://www.titanatlas.com),
a Brazilian computer company, and then by Google Summer of Code 2007. The
localization server is currently a free time project.
