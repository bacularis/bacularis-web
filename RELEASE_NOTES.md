
This is a minor new feature. We added a couple of functions that make daily work
with Bacula jobs and volumes easier. We added the previous/next job buttons to
the job view page, a new window on job list page to view live job logs. On the
volume pages we added drawer to modify volume properties without need to go to
volume details page. Next new function is a window to display jobs stored on volumes
that is accessible directly from the volume list page.

For the other functions, we enabled support for API instances that serve compressed
responses. Besides of that we prepared a few new controls.

Changes:
 - Add to job list new button to display current job log
 - Add prev and next job buttons to job view page
 - Add to volume list new button to display jobs stored on volume
 - Add quick volume edit settings drawer on volume list page
 - Add support for compressed HTTP response in API client
 - Add volume job list control to list jobs stored on volume
 - New volume config control
 - Enable updating size control
 - Enable updating time period control
 - Remove no longer needed action step
 - Remove loading controls config on page loading
 - Update volume list on save volume properties
 - Update php-cs-fixer config
