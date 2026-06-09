<?php

return [
    'image_driver' => getenv('IMAGE_STORAGE_DRIVER') ?: 'local',
    'cloudinary' => [
        'cloud_name' => getenv('CLOUDINARY_CLOUD_NAME') ?: '',
        'api_key' => getenv('CLOUDINARY_API_KEY') ?: '',
        'api_secret' => getenv('CLOUDINARY_API_SECRET') ?: '',
        'folder' => getenv('CLOUDINARY_FOLDER') ?: 'pc-parts-shop/products',
    ],
];

