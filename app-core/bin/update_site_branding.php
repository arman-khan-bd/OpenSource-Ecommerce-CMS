<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use App\Service\DatabaseService;
use App\Repository\OptionRepository;

$dbConfig = require dirname(__DIR__) . '/var/database.php';
$dbService = new DatabaseService(dirname(__DIR__));
$optionRepo = new OptionRepository($dbService);

$optionRepo->set('site_title', 'OpenSource eCommerce CMS');
$optionRepo->set('site_tagline', 'Alternative to WordPress');
$optionRepo->set('site_description', 'The modern, lightweight, monolithic & headless alternative to WordPress.');

echo "Site branding updated successfully:\n";
echo "Title: " . $optionRepo->get('site_title') . "\n";
echo "Tagline: " . $optionRepo->get('site_tagline') . "\n";
echo "Description: " . $optionRepo->get('site_description') . "\n";
