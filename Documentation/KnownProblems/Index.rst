.. include:: ../Includes.txt


.. _known-problems:

==============
Known Problems
==============

I can't copy 2nd or 3rd cropVariant to all other cropVariants
-------------------------------------------------------------

Currently we only support copying the cropArea of the first cropVariant. So, you can't decide to copy
the cropArea of the second or fourth cropVariant back to all other cropVariants

In our DataHandler Hook we currently don't check, if first cropArea with its ratio is allowed for all other
cropVariants. So please try to keep the ratios for all cropVariants the same! In detail: the ratio of the first
cropVariant has to be configured in all other cropVariants. It is no problem, if you have configured
further ratios for all other cropVariants.

sync_crop_areas does not work for columns of extension XY
---------------------------------------------------------

If you have a look  into the TCA of `tt_content` column `image` you will see
that TYPO3 will change the visible columns based on the file type. For image
file types the core loads the TCA palette `imageoverlayPalette` which contains
the columns `alternative`, `description`, `link`, `title` and `crop`.

EXT:sync_crop_areas adds the cropping feature (column: sync_crop_area)
before the `crop` column of palette: `imageoverlayPalette`

Some extension authors like `bootstrap_package` do not make use of this TYPO3
palette. That's why column `sync_crop_area` is not visible. So it's up to you
to add this missing column to these foreign extensions.

You need the table name of the foreign extension and the column name where
the sync_crop_areas feature is missing.

As we want to modify the TCA of one or more foreign extensions we should be sure
to load the foreign extensions BEFORE your site_package. Go into

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


Go into your site_package extension and create a new file:

`typo3conf/ext/[my_site_package]/Configuration/TCA/Overrides/[tableName].php`

Add following PHP block and update the variables `$table` and `$columns`
to your needs.:


.. code-block:: php

   <?php
   if (!defined('TYPO3_MODE')) {
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
