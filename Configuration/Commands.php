<?php

return [
    'sync_crop_areas:sync' => [
        'class' => \JWeiland\SyncCropAreas\Command\SynchronizeCropVariantsCommand::class,
        'schedulable' => true,
    ],
];
