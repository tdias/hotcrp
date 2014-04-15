HotCRP Conference Review Software - pt-br
=========================================

HotCRP is the best available software for managing the conference
review process, including paper submission, review and comment
management, rebuttals, and the PC meeting. Its main strengths are
flexibility and ease of use in the review process, especially through
smart paper search and an extensive tagging facility. It is widely
used in computer science conferences and for internal review processes
at several large companies.

HotCRP also has weaknesses. It requires that you run your own server,
and it does not natively support multitrack conferences (although you
can hack something together).

Main changes from original HotCRP
---------------------------------

*** Language translation for brazilian portuguese. ***

Prerequisites
-------------

HotCRP runs on Unix, including Mac OS X. It requires the following
software:

* Apache, http://apache.org/
  (You may be able to use another web server that works with PHP.)
* PHP version 5.2 or higher, http://php.net/
  - Including MySQL and GD support
* MySQL version 5 or higher, http://mysql.org/
* The zip compressor, http://www.info-zip.org/
* pdftohtml, http://poppler.freedesktop.org/ (Only required for format
  checking.)

Apache is preloaded on most Linux distributions.  You may need to install
additional packages for PHP, MySQL, and GD, such as:

* Fedora Linux: php-mysql, php-gd, zip, (poppler-utils)
* Debian Linux: php5-common, php5-gd, php5-mysql,
  libapache2-mod-php5 (or libapache-mod-php5 for Apache 1.x),
  zip, (poppler-utils)
* Ubuntu Linux: php5-common, php5-gd, php5-mysql,
  libapache2-mod-php5 (or libapache-mod-php5 for Apache 1.x),
  zip, (poppler-utils), and a package for SMTP support, such
  as sendmail

You may need to restart the Apache web server after installing these
packages (`sudo apachectl graceful` or `sudo apache2ctl graceful`).

**pdftohtml notes**: HotCRP and the banal script use pdftohtml for
paper format checking. As of 2013, many current Unix distributions
ship with a suitable version of pdftohtml, such as “pdftohtml version
0.18.4, Copyright 2005-2011 The Poppler Developers.” Older versions of
pdftohtml may not be suitable. In particular, version 0.40a can be
hundreds of times slower than other versions, and version 0.39 doesn’t
understand the most current PDF standard. If your pdftohtml is bad,
try installing Geoff Voelker’s patched version of pdftohtml; see
http://www.sysnet.ucsd.edu/sigops/banal/download.html.

**Load notes**: HotCRP requires a system with at least 256MB of
memory, more if paper format checking is used and submission load is
expected to be high. If you run HotCRP in a virtual machine, make sure
you configure suitable swap space! HotCRP uses the fast, but less
reliable, MyISAM database engine. If MySQL is killed your database may
be corrupted.

Installation
------------

1. Run `lib/createdb.sh` to create the database. Use
`lib/createdb.sh OPTIONS` to pass options to MySQL, such as `--user`
and `--password`. Many MySQL installations require privilege to create
tables, so you may need `sudo lib/createdb.sh OPTIONS`. Run
`lib/createdb.sh --help` for more information. You will need to
decide on a name for your database (no spaces allowed).

    The username and password information for the conference database
is stored in `conf/options.php`, which HotCRP marks as
world-unreadable. You must ensure that your web server can read this
file, for instance by changing its group.

2. Edit `conf/options.php`, which is annotated to guide you.
(`lib/createdb.sh` creates this file based on
`src/distoptions.php`.)

3. Redirect Apache so your server URL will point at the HotCRP
directory. (If you get an Error 500, see "Configuration notes".) This
will generally require adding a `<Directory>` for the HotCRP
directory, and an Alias redirecting a particular URL to that
directory. This section of httpd.conf makes "/testconf" point at a
HotCRP installation in /home/kohler/hotcrp; it works in Apache 2.2 or
earlier.

        <Directory "/home/kohler/hotcrp">
            Options Indexes Includes FollowSymLinks
            AllowOverride all
            Order allow,deny
            Allow from all
        </Directory>
        Alias /testconf /home/kohler/hotcrp

    Apache 2.4 or later requires this instead.

        <Directory "/home/kohler/hotcrp">
            Options Indexes Includes FollowSymLinks
            AllowOverride all
            Require all granted
        </Directory>
        Alias /testconf /home/kohler/hotcrp

    Note that the first argument to Alias should NOT end in a slash. The
"AllowOverride all" directive is required.

    All files under HOTCRPROOT (here, "/testconf") should be served by
HotCRP. This normally happens automatically. However, if HOTCRPROOT is
`/`, you may need to turn off your server’s default handlers for
subdirectories such as `/doc`.

4. Update the systemwide setting for PHP’s `session.gc_maxlifetime`
configuration variable. This provides an upper bound on HotCRP session
lifetimes (the amount of idle time before a user is logged out
automatically). On Unix machines, systemwide PHP settings are often
stored in `/etc/php.ini`. The suggested value for this setting is
86400, e.g., 24 hours:

        session.gc_maxlifetime = 86400

    If you want sessions to expire sooner, we recommend you set
`session.gc_maxlifetime` to 86400 anyway, then edit `conf/options.php`
to set `$Opt["sessionLifetime"]` to the correct session timeout.

5. Edit MySQL’s my.cnf (typical location: `/etc/mysql/my.cnf`) to ensure
that MySQL can handle paper-sized objects.  It should contain something
like this:

        [mysqld]
        max_allowed_packet=32M

    max_allowed_packet must be at least as large as the largest paper
you are willing to accept. It defaults to 1M on some systems, which is
not nearly large enough. HotCRP will warn you if it is too small. Some
MySQL setups, such as on Mac OS X, may not have a my.cnf by default;
just create one. If you edit my.cnf, also restart the mysqld server.
On Linux try something like `sudo /etc/init.d/mysql restart`.

6. Sign in to the site to create an account. The first account created
automatically receives system administrator privilege.

    If your server configuration doesn’t let .htaccess files set
options, Apache will report an “Error 500” when you try to load
HotCRP. Change your Apache configuration to `AllowOverride All` in the
HotCRP directory, as our example does above.

You can set up everything else through the web site itself.

* Configuration notes

  - Uploaded papers and reviews are limited in size by several PHP
    configuration variables, set by default to 15 megabytes in the HotCRP
    directory’s `.htaccess`.

  - HotCRP PHP scripts can take a lot of memory, particularly if they're
    doing things like generating MIME-encoded mail messages.  By default
    HotCRP sets the PHP memory limit to 128MB.

  - HotCRP benefits from Apache’s `mod_expires` and `mod_rewrite`
    modules; consider enabling them.

  - Most HotCRP settings are assigned in the conference database’s
    Settings table. The Settings table can also override values in
    `conf/options.php`: a Settings record with name "opt.XXX" takes
    precedence over option $Opt["XXX"].

Database access
---------------

Run `lib/backupdb.sh` at the shell prompt to back up the database.
This will write the database’s current structure and comments to the
standard output. HotCRP stores all paper submissions in the database,
so the backup file may be quite large.

Run `lib/restoredb.sh BACKUPFILE` at the shell prompt to restore the
database from a backup stored in `BACKUPFILE`.

Run `lib/runsql.sh` at the shell prompt to get a MySQL command prompt
for the conference database.

Updates
-------

HotCRP code can be updated at any time without bringing down the site.
If you obtained the code from git, use `git pull`. if you obtained
the code from a tarball, copy the new version over your old code,
preserving `conf/options.php`. For instance, using GNU tar:

    % cd HOTCRPINSTALLATION
    % tar --strip=1 -xf ~/hotcrp-NEWVERSION.tar.gz

Multiconference support
-----------------------

HotCRP can run multiple conferences from a single installation. The
last directory component of the URL will define the conference name.
For instance:

    http://read.seas.harvard.edu/conferences/testconf/doc/testconf-paper1.pdf
                                             ^^^^^^^^
                                          conference name

The conference name can only contain characters in [-_.A-Za-z0-9], and
it must not start with a period. HotCRP will check for funny
conference names and replace them with `__invalid__`.

To turn on multiconference support, edit `conf/options.php` and set
$Opt["multiconference"] to true. You will then need to tell HotCRP how
to find the options relevant for each conference. The most flexible
mechanism is to use $Opt["include"] to include a conference-specific
options file. For example (note the single quotes):

    $Opt["include"] = 'conf/options-${confname}.php';

The `${confname}` substring is replaced with the conference name.
HotCRP will refuse to proceed if the conference-specific options file
doesn’t exist. To ignore nonexistent options files, use wildcards:

    $Opt["include"] = 'conf/[o]ptions-${confname}.php';

`${confname}` replacement is also performed on these settings: dbName,
dbUser, dbPassword, sessionName, downloadPrefix, conferenceSite,
and paperSite.

You will still need to create a database for each conference using the
`lib/createdb.sh` script (the `-c CONFIGFILE` option will be useful).
Also, you will need to convince your Apache to use the HotCRP install
directory for all relevant URLs.

If you don't want to use the last directory component, set
$Opt["multiconferenceUrl"] to a URL regular expression, a space, and a
replacement pattern. HotCRP matches the input URL to the regex. If it
matches, it constructs a conference name from the replacement pattern.
For example, this setting will use "conf_CONFNAME" as the conference
name for a URL like "http://CONFNAME.crap.com/":

    $Opt["multiconferenceUrl"] = '\w+://([^./]+)[.]crap[.]com[.]?/ conf_$1';

License
-------

HotCRP is available under the Click license, a BSD-like license. See the
LICENSE file for full license terms.

Authors
-------

Eddie Kohler, Harvard/UCLA

* HotCRP is based on CRP, which was written by Dirk Grunwald,
  University of Colorado
* banal by Geoff Voelker, UCSD
