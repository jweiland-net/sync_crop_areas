..  include:: /Includes.rst.txt


..  _changelog:

=========
ChangeLog
=========

Version 4.1.0
=============

*   TASK: Add TYPO3 14 compatibility (TYPO3 13.4 is still supported)
*   TASK: Add extension metadata (`version`, `providesPackages`) to composer.json
    to avoid the `ext_emconf.php` deprecation of TYPO3 14.2
*   TASK: Add missing dependency to `typo3/cms-backend`
*   FEATURE: Respect CropVariant option `excludeFromSync` (introduced with
    TYPO3 14.2, also evaluated with TYPO3 13.4)
*   BUGFIX: Do not rewrite column `crop` if nothing was synchronized. Since
    TYPO3 14 the serialized crop contains additional keys, which led to
    needless updates and wrong statistics of the CLI command
*   BUGFIX: Use explicit nullable parameter in command (PHP 8.4 deprecation)
*   TASK: Run functional tests against TYPO3 13.4 and 14.3 with PHP 8.2 - 8.4

Version 4.0.1
=============

*   TASK: Update testing directory
*   TASK: Replaced deprecations in test cases

Version 4.0.0
=============

*   TASK: Add TYPO3 13 compatibility
*   TASK: Remove TYPO3 12 compatibility
*   TASK: Remove TYPO3 11 compatibility
*   TASK: Update Test Suite removing testing docker

Version 3.0.0
=============

*   TASK: Add TYPO3 12 compatibility
*   TASK: Remove TYPO3 10 compatibility

Version 2.2.3
=============

*   TASK: Update .editorconfig
*   TASK: Update .gitignore
*   TASK: Update .gitattributes

Version 2.2.2
=============

*   Bugfix: Respect crop variants of non-types tables in columnsOverride
*   TASK: Migrate Prophesize in tests to MockObjects

Version 2.2.1
=============

*   Bugfix: Repair sync for new sys_file_reference records
*   TASK: Update and add more functional tests

Version 2.2.0
=============

*   Feature: Respect TCA overrideChildTca
*   Feature: Respect TCA type based columnsOverrides
*   Feature: Add missing CropVariants. f.e. if new config was added
*   Feature: Use TYPO3 CropVariant API to build crop JSON

Version 2.1.0
=============

*   Move CropVariant synchronization into its own Service class
*   DataHandler hook uses synchronization service to process records
*   Add schedulable symfony command to process all sys_file_reference records

Version 2.0.1
=============

*   Remove PHP 7.2 and PHP 7.3 compatibility
*   BUGFIX: Check selected ratio of other cropVariants before copy

Version 2.0.0
=============

*   Remove TYPO3 9 compatibility
*   Add TYPO3 11 compatibility

Version 1.0.4
=============

*   Add documentation how to extend foreign extensions with sync_crop_areas

Version 1.0.1
=============

*   Change Extension Icon

Version 1.0.0
=============

*   Initial commit
