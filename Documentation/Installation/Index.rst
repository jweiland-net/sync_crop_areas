..  include:: /Includes.rst.txt


..  _installation:

============
Installation
============

Composer
========

If your TYPO3 installation works in composer mode, please execute
following command:

..  code-block:: bash

    composer req jweiland/sync-crop-areas
    vendor/bin/typo3 extension:setup --extension=sync_crop_areas

If you work with DDEV please execute this command:

..  code-block:: bash

    ddev composer req jweiland/sync-crop-areas
    ddev exec vendor/bin/typo3 extension:setup --extension=sync_crop_areas

ExtensionManager
================

On non composer based TYPO3 installations you can install `sync_crop_areas`
still over the ExtensionManager:

..  rst-class:: bignums

1.  Login

    Login to backend of your TYPO3 installation as an administrator
    or system maintainer.

2.  Open ExtensionManager

    Click on `Extensions` from the left menu to open the ExtensionManager.

3.  Update Extensions

    Choose `Get Extensions` from the upper selectbox and click on
    the `Update now` button at the upper right.

4.  Install `sync_crop_areas`

    Use the search field to find `sync_crop_areas`. Choose
    the `sync_crop_areas` line from the search result and click on the cloud
    icon to install `sync_crop_areas`.

Next step
=========

:ref:`Configure sync_crop_areas <configuration>`.
