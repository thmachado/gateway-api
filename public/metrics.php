<?php

declare(strict_types=1);

use Prometheus\CollectorRegistry;
use Prometheus\RenderTextFormat;
use Prometheus\Storage\InMemory;

require_once __DIR__ . '/../vendor/autoload.php';

$registry = new CollectorRegistry(new InMemory());
$counter = $registry->getOrRegisterCounter("app", "requests", "Number all requests", ["method", "endpoint"]);

/** @var array<string> $labels  */
$labels = [
    "method" => $_SERVER["REQUEST_METHOD"] ?? "GET",
    "endpoint" => $_SERVER["REQUEST_URI"] ?? "/"
];

$counter->inc($labels);

header("Content-Type: " . RenderTextFormat::MIME_TYPE);
$renderer = new RenderTextFormat();
echo $renderer->render($registry->getMetricFamilySamples());
exit;
