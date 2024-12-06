
This is a new feature and bug fix release. We prepared two new Bacularis plugins:
MySQL and MariaDB database backup plugins. Using them there is possible to do
the databases backup in various ways: dump backup (in three variants), binary physical
online backup, backup for Point-in-Time Recovery (PITR), file backup for crucial
database server files. This two plugin solution also introduces real incremental
and differential database backups for the dump backup method. We are very glad
that we provides these plugins for the Community. More information about
the database plugins you can find in the Bacularis documentation.

Besides new plugins, we also did some changes and small improvements in the
deployment process. At the end we fixed a couple of bugs reported by the Community.

## Main changes

**Changes**

 * Add restore to original location in restore wizard
 * Add window in fileset config page for setting up Bacula backup plugins
 * Add reinstall parameter to deploy Bacularis and Bacula using deb packages
 * Use sorting in the plugin and config lists
 * Remove repository key after successful copying it to destination host
 * Move Web plugin base part to common module
 * Fix restore wizard error if plugin config not exists
 * Fix hanging the deployment process in some cases
 * Fix sources.list entry in deploying Bacularis on DEB-based systems

