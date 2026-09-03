<?php

namespace Paypercut\Telemetry;

use Paypercut\Http\Client;
use Paypercut\Support\Environment;

/**
 * Exchanges the store's API key for a short-lived telemetry token.
 *
 * Deliberately does not reuse the module's inline API calls: those log request
 * and response bodies (which would write the minted token into the store's log
 * files), share their timeout budget with the payment paths, and do not branch
 * on every status the way this must.
 */
class TokenMinter
{
    const PATH = 'v1/telemetry/tokens';

    private $client;

    public function __construct($client = null)
    {
        $this->client = $client === null ? new Client() : $client;
    }

    /**
     * Request a telemetry token.
     *
     * @param string $secret    The store's API key.
     * @param string $mint_base Base URI of the payment API for this environment.
     */
    public function mint($secret, $mint_base)
    {
        // The store's long-lived API key travels on this request, so the
        // destination is validated here rather than trusted.
        if (Environment::allowedPaypercutBase($mint_base) === '') {
            return self::failure();
        }

        $response = $this->client->postJson(
            rtrim($mint_base, '/') . '/' . self::PATH,
            array(
                'Authorization: Bearer ' . $secret,
                'Accept: application/json'
            ),
            null,
            TelemetrySession::MINT_TIMEOUT_SECONDS,
            TelemetrySession::MINT_CONNECT_TIMEOUT_SECONDS
        );

        $decoded = json_decode($response['body'], true);
        $body = is_array($decoded) ? $decoded : array();

        $header_trace = Client::header($response['headers'], 'trace-id');

        return array(
            'status' => (int)$response['status'],
            'body' => $body,
            'token' => isset($body['token']) && is_string($body['token']) ? $body['token'] : '',
            'expires_at' => isset($body['expires_at']) && is_string($body['expires_at']) ? $body['expires_at'] : '',
            'date' => Client::header($response['headers'], 'date'),
            // On an error the gateway repeats the trace id in the body, which
            // is the more reliable of the two.
            'trace_id' => $header_trace !== ''
                ? $header_trace
                : (isset($body['trace_id']) && is_string($body['trace_id']) ? $body['trace_id'] : ''),
            'request_id' => Client::header($response['headers'], 'x-request-id')
        );
    }

    /**
     * How long the token is good for, measured on the MINT's clock.
     *
     * Stores routinely drift by minutes, so `expires_at` and this server's
     * `time()` are not comparable: copying the timestamp would either overrun
     * the token (clock behind) or make Start permanently impossible (clock
     * ahead). `expires_at - Date` is a duration, portable to any clock.
     */
    public static function deriveLifetime($expires_at, $date_header, $now)
    {
        $expiry = strtotime($expires_at);

        if ($expiry === false) {
            return 0;
        }

        $issued = $date_header !== '' ? strtotime($date_header) : false;

        if ($issued === false) {
            $issued = $now;
        }

        return (int)$expiry - (int)$issued;
    }

    /**
     * Signed difference between the mint's clock and this server's, in seconds.
     *
     * Logged on every successful mint so support can spot a drifting store
     * before it turns into an unexplainable failure.
     */
    public static function skew($date_header, $now)
    {
        if ($date_header === '') {
            return 0;
        }

        $issued = strtotime($date_header);

        return $issued === false ? 0 : (int)$issued - $now;
    }

    private static function failure()
    {
        return array(
            'status' => 0,
            'body' => array(),
            'token' => '',
            'expires_at' => '',
            'date' => '',
            'trace_id' => '',
            'request_id' => ''
        );
    }
}
