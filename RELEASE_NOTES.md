
This is a new function and bug fix release. We prepared a new tape storage wizard
that can be very useful for users who have the tape devices (tape library,
autoloader or single tape drive). The new wizard adds the tape storage to Bacula
and Bacularis. Users who already use the tape storage with Bacula, now using
this wizard can enable the Bacularis autochanger management. At the end
single tape drive users are able to add this device to Bacula.

Besides that we extended the Bacularis API endpoints for the device management.
At the end we fixed a couple of bugs reported by the community.

**Changes**
 - Add new tape storage wizard
 - Fix empty jobs and job report tables if used search filters
 - Fix using copy config with selecting jobdefs in new job window
 - Fix cloud device labels in cloud storage wizard

