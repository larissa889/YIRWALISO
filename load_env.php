<?php
// Load environment variables from temp_env file
if(file_exists('temp_env')) {
    $lines = file('temp_env');
    foreach($lines as $line) {
        if(trim($line) !== '') {
            putenv(trim($line));
        }
    }
    echo "Environment loaded successfully\n";
} else {
    echo "temp_env file not found\n";
}

// Set APP_KEY if not already set
if (getenv('APP_KEY') === false || getenv('APP_KEY') === '') {
    $key = 'base64:' . base64_encode(random_bytes(32));
    putenv('APP_KEY=' . $key);
    echo "Generated APP_KEY: " . $key . "\n";
}
