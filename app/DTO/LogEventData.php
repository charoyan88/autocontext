<?php

namespace App\DTO;

class LogEventData
{
    public function __construct(
        public string $timestamp,
        public string $level,
        public string $message,
        public array $context = [],
        public ?string $service = null,
        public ?string $region = null,
        public ?string $path = null,
        public ?int $deploymentId = null,
        public ?string $deploymentVersion = null,
        public ?string $deploymentEnvironment = null,
        public bool $deploymentRelated = false
    ) {
    }

    public static function fromArray(array $event): self
    {
        return new self(
            timestamp: (string) ($event['timestamp'] ?? ''),
            level: strtoupper((string) ($event['level'] ?? '')),
            message: (string) ($event['message'] ?? ''),
            context: is_array($event['context'] ?? null) ? $event['context'] : [],
            service: isset($event['service']) ? (string) $event['service'] : null,
            region: isset($event['region']) ? (string) $event['region'] : null,
            path: isset($event['path']) ? (string) $event['path'] : null,
            deploymentId: isset($event['deployment_id']) ? (int) $event['deployment_id'] : null,
            deploymentVersion: $event['deployment_version'] ?? null,
            deploymentEnvironment: $event['deployment_environment'] ?? null,
            deploymentRelated: (bool) ($event['deployment_related'] ?? false),
        );
    }

    public function toArray(): array
    {
        return [
            'timestamp' => $this->timestamp,
            'level' => $this->level,
            'message' => $this->message,
            'context' => $this->context,
            'service' => $this->service,
            'region' => $this->region,
            'path' => $this->path,
            'deployment_id' => $this->deploymentId,
            'deployment_version' => $this->deploymentVersion,
            'deployment_environment' => $this->deploymentEnvironment,
            'deployment_related' => $this->deploymentRelated,
        ];
    }
}
