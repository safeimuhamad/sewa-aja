<?php

require_once __DIR__ . '/../shared/Support/EnvLoader.php';
require_once __DIR__ . '/../shared/Database/Connection.php';

use Shared\Database\Connection;
use Shared\Support\EnvLoader;

EnvLoader::load(__DIR__ . '/../../.env');

$baseUrl = rtrim(getenv('APP_URL') ?: 'http://localhost/sewaaja', '/');
$db = Connection::make([
    'host' => getenv('DB_HOST') ?: '127.0.0.1',
    'port' => getenv('DB_PORT') ?: '3306',
    'database' => getenv('DB_DATABASE') ?: 'sewaaja',
    'username' => getenv('DB_USERNAME') ?: 'root',
    'password' => getenv('DB_PASSWORD') ?: '',
]);

$urls = [
    ['loc' => "{$baseUrl}/", 'priority' => '1.0'],
    ['loc' => "{$baseUrl}/products", 'priority' => '0.9'],
];

$statement = $db->query("SELECT slug, updated_at FROM products WHERE status = 'active' AND deleted_at IS NULL ORDER BY updated_at DESC");

foreach ($statement->fetchAll() as $product) {
    $urls[] = [
        'loc' => "{$baseUrl}/product-detail?slug=" . rawurlencode($product['slug']),
        'priority' => '0.8',
        'lastmod' => substr((string) $product['updated_at'], 0, 10),
    ];
}

$xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"></urlset>');

foreach ($urls as $url) {
    $node = $xml->addChild('url');
    $node->addChild('loc', htmlspecialchars($url['loc'], ENT_XML1));
    if (!empty($url['lastmod'])) {
        $node->addChild('lastmod', $url['lastmod']);
    }
    $node->addChild('priority', $url['priority']);
}

$target = __DIR__ . '/../../frontend/public/sitemap.xml';
file_put_contents($target, $xml->asXML());

echo "Sitemap generated: {$target}" . PHP_EOL;
