<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Node Binary Path
    |--------------------------------------------------------------------------
    |
    | The path to the Node.js binary. On most systems, this is /usr/bin/node
    | or /usr/local/bin/node. You can also use `which node` to find it.
    |
    */
    'node_binary' => env('BROWSERSHOT_NODE_BINARY', '/usr/bin/node'),

    /*
    |--------------------------------------------------------------------------
    | NPM Binary Path
    |--------------------------------------------------------------------------
    |
    | The path to the NPM binary. On most systems, this is /usr/bin/npm
    | or /usr/local/bin/npm. You can also use `which npm` to find it.
    |
    */
    'npm_binary' => env('BROWSERSHOT_NPM_BINARY', '/usr/bin/npm'),

    /*
    |--------------------------------------------------------------------------
    | Chrome/Chromium Path (Optional)
    |--------------------------------------------------------------------------
    |
    | If you have a specific Chrome or Chromium installation you want to use,
    | specify its path here. Leave null to use Puppeteer's bundled Chromium.
    |
    */
    'chrome_path' => env('BROWSERSHOT_CHROME_PATH'),
];
