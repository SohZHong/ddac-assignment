<?php

namespace App\Services;

use Aws\CloudWatch\CloudWatchClient;

class CloudWatchMetrics
{
    private CloudWatchClient $client;
    private string $namespace;

    public function __construct(string $namespace = 'App/Admin')
    {
        $this->client = new CloudWatchClient([
            'version' => '2010-08-01',
            'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
        ]);
        $this->namespace = $namespace;
    }

    /**
     * Put a timing metric in milliseconds with optional dimensions.
     * Dimensions example: ['route' => 'approve', 'result' => 'approved']
     */
    public function putTiming(string $metricName, float $milliseconds, array $dimensions = []): void
    {
        try {
            $dims = [];
            foreach ($dimensions as $name => $value) {
                $dims[] = ['Name' => (string) $name, 'Value' => (string) $value];
            }

            $this->client->putMetricData([
                'Namespace' => $this->namespace,
                'MetricData' => [[
                    'MetricName' => $metricName,
                    'Dimensions' => $dims,
                    'Unit' => 'Milliseconds',
                    'Value' => $milliseconds,
                ]],
            ]);
        } catch (\Throwable $e) {
            // Fail-soft: do not block request if metrics fail
        }
    }
}


