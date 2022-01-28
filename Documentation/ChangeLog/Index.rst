.. include:: ../Includes.txt


.. _changelog:

ChangeLog
=========

**Version 1.2.0**

- Feature: Respect TCA overrideChildTca
- Feature: Respect TCA type based columnsOverrides
- Feature: Add missing CropVariants. f.e. if new config was added
- Feature: Use TYPO3 CropVariant API to build crop JSON

**Version 1.1.0**

- Move CropVariant synchronization into its own Service class
- DataHandler hook uses synchronization service to process records
- Add schedulable symfony command to process all sys_file_reference records

**Version 1.0.4**

- Add documentation how to extend foreign extensions with sync_crop_areas

**Version 1.0.1**

- Change Extension Icon

**Version 1.0.0**

- Initial commit
