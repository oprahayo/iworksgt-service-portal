<?php
declare(strict_types=1);

// Dedicated boundary lets database/concurrency tests simulate the remote API
// without replacing application decisions or ever reaching a live gateway.
function iworks_agent_paystack_get(string $url, string $secret): array
{
    return gateway_json_get($url, $secret);
}

function iworks_agent_paystack_post(string $url, array $payload, string $secret): array
{
    return gateway_json_post($url, $payload, $secret);
}
