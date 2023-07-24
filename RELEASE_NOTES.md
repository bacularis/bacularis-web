
This is a minor bug fix release. We fixed problems that were detected by static
code analysis tools and we did a couple of other minor fixes. Apart from that
there has been improved logging/debugging to file. We slightly changed the log
format to make logs more readable. At the end we introduced changes to automate
code analysing and releasing process. We hope it will help in providing new releases
more frequent.

Changes:
 - Use new logging interface
 - Update Polish translations
 - Update dependencies in composer.json
 - Fix remove directive button for storage directives
 - Fix qrcodejs version in composer.json
 - Fix problem found in static code analysis
 - Hide reset to default value button for storage directives
 - Improve coding style according to PSR-12
 - Update flotr2 include path
 - Update LICENSE file
