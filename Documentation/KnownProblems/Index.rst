.. include:: ../Includes.txt


.. _known-problems:

==============
Known Problems
==============

I can't copy 2nd or 3rd cropVariant to all other cropVariants
=============================================================

Currently we only support copying the cropArea of the first cropVariant. So, you can't decide to copy
the cropArea of the second or fourth cropVariant back to all other cropVariants

In our DataHandler Hook we check, if first cropArea with its ratio is allowed for all other cropVariants. If a
CropVariant does not have the ratio of the first CropVariant sync_crop_areas keeps this CropVariant untouched.

Please check, if your editors have access rights to column sys_file_reference:sync_crop_area. This column is deactivated
by default, so, if an editor has no rights for this column, the CropVariants can't be synchronized.


.. _known-problems_project-upgrade:

Is there an automatism to upgrade all sys_file_reference records?
=================================================================

If you add sync_crop_areas the first time to a project or you have added further CropVariants, you may have the problem
that you have 1000s of sys_file_reference records which have CropVariants out-of-sync. Instead of opening each
record and save it, you can use a CLI command or scheduler task.

**Command**: `vendor/bin/typo3 sync_crop_areas:sync`

**Task**: Choose `Execute console commands` -> `sync_crop_areas:sync` -> execute task once.


.. _known-problems_not-working:

sync_crop_areas does not work for column of extension XY
========================================================

First of all it's not the TYPO3 core itself which adds cropping feature to `sys_file_reference`, it's TYPO3 sysext
`frontend`. The `core` initializes `sys_file_reference` with just `title` and `description` for all kind of files in
TCA palette `basicoverlayPalette`. For each table in TYPO3 universe which contains a relation to `sys_file_reference`
it is possible to overwrite the displayed columns or palettes. Sysext `frontend` contains the TCA for
table `tt_content` which of cause has a relation to `sys_file_reference`. In its TCA it overwrite palette
`basicoverlayPalette` to `imageoverlayPalette` which also contains the column `crop` for all image filetypes.

EXT:sync_crop_areas simply searches for `crop` in palette `imageoverlayPalette` and adds our own column
`sync_crop_area` before `crop`.

Some extension authors like `bootstrap_package` do not make use of TYPO3 palette `imageoverlayPalette`. That's why
column `sync_crop_area` is not visible. So it's up to you to add this missing column to such extensions.

You need the table name of the foreign extension and the column name where the `sync_crop_areas` feature is missing.

As we want to modify the TCA of one or more foreign extensions we should be sure to load the foreign
extensions BEFORE your site_package. Go into

`typo3conf/ext/[my_site_package]/ext_emconf.php`

and check, if all extensions with missing `sync_crop_area` column is listed
in section `depends`. Here an example for `bootstrap_package`:

.. code-block:: php

   ...
   'constraints' => [
       'depends' => [
           'typo3' => '10.4.0-10.4.99',
           'maps2' => '9.3.0-9.99.99',
           'tt_address' => '5.2.0-5.99.99',
           'bootstrap_package' => '12.0.1-12.99.99'
       ],
       'conflicts' => [],
       'suggests' => [],
   ],
   ...


Go into your SitePackage extension and create a new file:

`typo3conf/ext/[my_site_package]/Configuration/TCA/Overrides/[tableName].php`

Add following PHP block and update the variables `$table` and `$columns`
to your needs.:


.. code-block:: php

   <?php
   if (!defined('TYPO3')) {
       die('Access denied.');
   }

   call_user_func(static function () {
       // Only needed, if foreign extension author do not use
       // "imageoverlayPalette" palette from core.
       // Example for bootstrap table "tx_bootstrappackage_card_group_item":
       $table = 'tx_bootstrappackage_card_group_item';
       $columns = ['image'];
       $imgFileType = \TYPO3\CMS\Core\Resource\File::FILETYPE_IMAGE;

       if (isset($GLOBALS['TCA'][$table]['columns'])) {
           foreach ($columns as $column) {
               if (isset($GLOBALS['TCA'][$table]['columns'][$column]['config'])) {
                   $imgConfig = $GLOBALS['TCA'][$table]['columns'][$column]['config'];
                   if (isset($imgConfig['overrideChildTca']['types'][$imgFileType]['showitem'])) {
                       // Add column "sync_crop_area" before "crop" column
                       $imgConfig['overrideChildTca']['types'][$imgFileType]['showitem'] = str_replace(
                           'crop',
                           'sync_crop_area, crop',
                           $imgConfig['overrideChildTca']['types'][$imgFileType]['showitem']
                       );
                       $GLOBALS['TCA'][$table]['columns'][$column]['config'] = $imgConfig;
                   }
               }
           }
       }
   });

Clear System Cache and you should be done.
