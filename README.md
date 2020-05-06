# TYPO3 Extension `sync_crop_areas`

## What does it do?

e, if you want to copy the crop area of first crop variant over to all other crop
variants. E.g. you have defined 4 crop variants "Desktop", "Landscape", "Tablet" and "Mobile". If you move
the crop area of "Desktop", you have to do so with "Landscape", "Tablet" and "Mobile", too, which is a lot of work
for your editors. Further it is hard to match the exact position (cropArea) in all other cropVariants.

## Installation

### Installation using Composer

Run the following command within your Composer based TYPO3 project:

```
composer require jweiland/sync-crop-areas
```

### Installation using Extension Manager

Login into TYPO3 Backend of your project and click on `Extensions` in the left menu.
Press the `Retrieve/Update` button and search for the extension key `sync_crop_areas`.
Import the extension from TER (TYPO3 Extension Repository)

## Configuration

There is no configuration for this extensions.
Just save the content (tt_content) record to sync the cropAreas over all cropVariants
