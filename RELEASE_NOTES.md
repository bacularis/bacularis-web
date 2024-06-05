
This is fully new function release. We prepared the configs and patterns function. It can be significantly helpful in repeatable tasks with Bacula configuration. This feature enables bulk adding configuration to multiple or single resources at once. Config function is designed for adding configuration to Bacula resources (Client, Pool, Device...) while patterns are for adding configuration with many resources to local and remote Bacula components (Director, File Daemon, Storage Daemon...). More information about these two functions you can find in the Bacularis documentation.

To maintain continuity of work in multiple Bacularis instance environments advised upgrading method to version 3.2.0 is to upgrade all Bacularis API hosts first and Bacularis Web at the end. It is because of fact that Bacularis API 3.2.0 works well with Bacularis Web lower than 3.2.0, while Bacularis Web 3.2.0 does not work with Bacularis API lower than 3.2.0.

**Changes**
 - Implement configs and patterns function
 - New bulk actions for applying configs and patterns
 - Switch web interface to use new API version 3
 - Improve writing values in multiple text box control
 - Add enabled property to table toolbar
 - Remove old data dependencies module

