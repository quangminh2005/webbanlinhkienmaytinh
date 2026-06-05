<?php

return [
    'enabled' => filter_var(getenv('GOOGLE_OAUTH_ENABLED') ?: 'false', FILTER_VALIDATE_BOOLEAN),
    'client_id' => getenv('GOOGLE_CLIENT_ID') ?: '',
    'client_secret' => getenv('GOOGLE_CLIENT_SECRET') ?: '',
];
