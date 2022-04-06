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

ARCHITECTURE
--------------------------------------------------------------------------------

### Localization Server

1. The l10n_server base module provides a database backend to store projects,
   releases and the translatable strings found in them. The data model is as
   follows:

    project =(1:n)> release =(1:n)> file =(1:n)> line =(1:n)> string =(1:n)> translation

   Which means that granular information is available about where strings are
   used. Extraction warning reports also have their place connected to releases.
   All these tables are only provided by this module though, and not filled
   up with actual data. Connector modules are expected to provide the project,
   release, file, line and string data, based on their own discovery mechanisms.

   This design allows the module suite to be used in corporate settings, where
   in-house translation teams need their own project releases translated, and
   they have no connection to drupal.org projects.

2. The localization community module provides a user interface on top of the
   database that allows users to enter suggestions and translations as well
   as moderate submissions.

3. The community server is designed to be able to work with Organic Groups.
   Each language can have one organic group on the site, which provides a
   discusson space for group members *and* makes it possible to hand out
   permissions (ie. differentiate between group managers and members if a
   group needs this level of permission).

   Group managers can choose a permission model of either open or controlled.
   A controlled model allows members of the group to suggest translations,
   while approval rights are limited to group admins. An open model allows
   all members to suggest and approve as well.

4. Translations can be approached from the list of language groups or the
   list of projects. On the second level, detailed summaries related to the
   selected language or project are shown as well as other stats.
   These two interfaces allow people to get different overviews (summaries)
   of the translations hosted on the site, as well as make it possible to
   import and export translations based on languages or projects.

5. Translation can either happen on the site (which only requires a user
   account with privileges to translate) or off-site. The online interface
   allows translators to provide suggestions for strings.

6. Off-site translation support is possible with exporting Gettext PO(T)
   files of a specific project release. Translators can work with offline
   translation tools and import the modified PO files later. Exports can be
   generated in various formats.

7. Extracted strings are stored related to projects, releases, files
   and lines. So if a string appears multiple times in the same file
   but on different lines, these are stored as separate relations.
   Strings are only stored once, relations are made between lines and
   strings. Source strings also store optinal context information.

### Localization Client

Unfortunately Localization Client is not yet ported to Drupal 8/9 in its full
feature set yet. However, when/if it is ported, it would provide a user
interface to update local translations as well as submit translations remotely
to drupal.org if the localization sharing setting is enabled. Once sharing is
set up, to keep attribution intact, per-user API keys should also be set up.
Each user should request and set their own API key via their user (My account)
page on the client site. The l10n_remote module supports this on the server
side.

CONTRIBUTORS
--------------------------------------------------------------------------------

Bruno Massa  http://drupal.org/user/67164 (original author)
Gábor Hojtsy http://drupal.org/user/4166 (current maintainer)
Sébastien Corbin https://www.drupal.org/u/sebcorbin (port to Drupal 7)
Tobias Bähr https://www.drupal.org/u/tobiasb (in progress port to Drupal 9)

This module was originally sponsored by Titan Atlas (http://www.titanatlas.com),
a Brazilian computer company, and then by Google Summer of Code 2007. The
localization server is currently a free time project.
