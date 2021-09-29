.. include:: ../Includes.txt


.. _configuration:

=============
Configuration
=============

EXT:sync_crop_areas itself does not need any configuration, but it needs a configured `crop` column in TCA
of table `sys_file_reference`. Usually you should have a file `Configuration/TCA/Overrides/sys_file_reference.php`
in your SitePackage where CropVariants are defined:

.. code-block:: php

   $GLOBALS['TCA']['sys_file_reference']['columns']['crop']['config']['cropVariants'] = [
       'desktop' => [
           'title' => 'Desktop',
           'allowedAspectRatios' => [
               '4:3' => [
                   'title' => '4/3',
                   'value' => 4 / 3
               ],
               'NaN' => [
                   'title' => 'Free',
                   'value' => 0.0
               ],
           ],
       ],
       'mobile' => [
           'title' => 'Mobile',
           'allowedAspectRatios' => [
               '4:3' => [
                   'title' => '4/3',
                   'value' => 4 / 3
               ],
               'NaN' => [
                   'title' => 'Free',
                   'value' => 0.0
               ],
           ],
       ],
   ];

.. attention::

   EXT:sync_crop_areas will only sync values of CropVariants with same ratio. In example above `4:3` exists
   in both CropVariants so syncing these CropVariants will not be a problem.

Usually a TCA reference to `sys_file_reference` in your extension should look like:

.. code-block:: php

   'images' => [
       'exclude' => true,
       'label' => 'LLL:EXT:glossary2/Resources/Private/Language/locallang_db.xlf:tx_glossary2_domain_model_glossary.images',
       'config' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getFileFieldTCAConfig(
           'images',
           [
               ...
               'overrideChildTca' => [
                   'types' => [
                       '0' => [
                           'showitem' => '
                           --palette--;;imageoverlayPalette,
                           --palette--;;filePalette'
                       ],
                       \TYPO3\CMS\Core\Resource\File::FILETYPE_TEXT => [
                           'showitem' => '
                           --palette--;;imageoverlayPalette,
                           --palette--;;filePalette'
                       ],
                       ...
                   ],
               ],
           ],
           $GLOBALS['TYPO3_CONF_VARS']['SYS']['mediafile_ext']
       )
   ]

.. attention::

   If you or the used extension does not make use of the palette `imageoverlayPalette` please have a look into
   section :ref:`known-problems_not-working`.
