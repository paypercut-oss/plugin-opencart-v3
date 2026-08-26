<?php

namespace Paypercut\Telemetry;

use Paypercut\Http\Client;

/**
 * Delivers a batch of diagnostic events to the public telemetry edge.
 *
 * The edge verifies the bearer token offline and never calls back into the
 * platform, so a request never blocks on the payment platform.
 *
 * The body is worth reading: a 202 carries {"accepted":N,"dropped":M} — the
 * only way a client learns the edge discarded part of a batch it accepted — and
 * a 413 carries the limits a batch must be split to satisfy.
 */
class EdgeClient
{
    const PATH = 'v1/telemetry';

    /**
     * The edge's own responses are a few dozen bytes; anything larger is not one.
     */
    const MAX_RESPONSE_BYTES = 4096;

    private $client;

    public function __construct($client = null)
    {
        $this->client = $client === null ? new Client() : $client;
    }

    /**
     * POST one batch. A status of 0 means the request never completed.
     */
    public function send($edge_base, $jwt, $json_body)
    {
        $response = $this->client->postJson(
            rtrim($edge_base, '/') . '/' . self::PATH,
            array(
                'Authorization: Bearer ' . $jwt,
                'Content-Type: application/json'
            ),
            $json_body,
            TelemetrySession::EDGE_TIMEOUT_SECONDS,
            TelemetrySession::EDGE_CONNECT_TIMEOUT_SECONDS
        );

        return array(
            'status' => (int)$response['status'],
            'retry_after' => (int)Client::header($response['headers'], 'retry-after'),
            'body' => self::decode($response['body'])
        );
    }

    /**
     * Anything that is not a small JSON object is no answer at all.
     *
     * A 413 from a proxy in front of the edge is an HTML page, and a captive
     * portal will happily return 200 with a login form.
     */
    private static function decode($body)
    {
        $body = (string)$body;

        if ($body === '' || strlen($body) > self::MAX_RESPONSE_BYTES) {
            return array();
        }

        $decoded = json_decode($body, true);

        return is_array($decoded) ? $decoded : array();
    }
}
