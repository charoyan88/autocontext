<?php

namespace App\Services;

class LogNormalizer
{
    /**
     * Normalize a batch of log events.
     */
    public function normalize(array $events): array
    {
        $normalized = [];

        foreach ($events as $event) {
            $normalized[] = $this->normalizeEvent($event);
        }

        return $normalized;
    }

    /**
     * Normalize a single log event.
     */
    private function normalizeEvent(array $event): array
    {
        if (isset($event['level'])) {
            $event['level'] = strtoupper((string) $event['level']);
        }

        if (isset($event['service']) && $event['service'] === '') {
            unset($event['service']);
        }

        if (isset($event['region']) && $event['region'] === '') {
            unset($event['region']);
        }

        if (isset($event['path']) && $event['path'] === '') {
            unset($event['path']);
        }

        return $event;
    }
}
