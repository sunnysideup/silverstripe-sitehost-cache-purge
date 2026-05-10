<?php

use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Environment;
use SilverStripe\Core\Flushable;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Core\Injector\Injector;

/**
 * SiteHost API Client
 *
 * A minimal PHP class for interacting with the SiteHost API v1.5.
 *
 * @see https://docs.sitehost.nz/api/v1.5/
 */
class SiteHostPurgeCache implements Flushable
{
    use Injectable;
    use Configurable;
    public static function flush()
    {
        $apiKey = (string) (Config::inst()->get(SiteHostPurgeCache::class, 'apiKey') ?: Environment::getEnv('SS_SITEHOST_API_KEY'));
        $clientId = (int) (Config::inst()->get(SiteHostPurgeCache::class, 'clientId') ?: Environment::getEnv('SS_SITEHOST_CLIENT_ID'));
        $server = (string) (Config::inst()->get(SiteHostPurgeCache::class, 'server') ?: Environment::getEnv('SS_SITEHOST_SERVER'));
        $name = (string) (Config::inst()->get(SiteHostPurgeCache::class, 'name') ?: Environment::getEnv('SS_SITEHOST_NAME'));
        SiteHostPurgeCache::create($apiKey, $clientId, $server, $name)->purgeCache($server, $name);
    }

    private const BASE_URL = 'https://api.sitehost.nz/1.5';

    private static string $api_key;
    private static int $client_id;


    private string $apiKey;
    private int $clientId;
    private string $server;
    private string $name;

    public function __construct(?string $apiKey = null, ?int $clientId = null)
    {
        $this->apiKey   = (string) ($apiKey ?? $this->config()->get('apiKey') ?: Environment::getEnv('SS_SITEHOST_API_KEY'));
        $this->clientId = (int) ($clientId ?? $this->config()->get('clientId') ?: Environment::getEnv('SS_SITEHOST_CLIENT_ID'));
    }

    /**
     * Purge the cache for a Cloud Container stack.
     *
     * Calls POST /cloud/stack/purge_cache.json
     *
     * @param  string $server  The server name, e.g. "ch-servername"
     * @param  string $name    The stack/container name, e.g. "examplenz"
     * @return array           Decoded JSON response: ['status' => bool, 'msg' => string]
     * @throws RuntimeException On cURL error or a non-200 HTTP response
     */
    public function purgeCache(?string $server = null, ?string $name = null): array
    {
        $this->server = $server ?: $this->server;
        $this->name = $name ?: $this->name;
        return $this->post('/cloud/stack/purge_cache.json', [
            'server' => $this->server ?: $server,
            'name'   => $this->name ?: $name,
        ]);
    }

    // -------------------------------------------------------------------------
    // Internal helpers
    // -------------------------------------------------------------------------

    /**
     * Send a POST request to the SiteHost API.
     *
     * @param  string $path    Endpoint path, e.g. "/cloud/stack/purge_cache.json"
     * @param  array  $params  Additional form fields (apikey and client_id are added automatically)
     * @return array           Decoded JSON response body
     * @throws RuntimeException
     */
    private function post(string $path, array $params = []): array
    {
        $fields = array_merge([
            'apikey'    => $this->apiKey,
            'client_id' => $this->clientId,
        ], $params);

        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL            => self::BASE_URL . $path,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $fields,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            // Keep SSL verification enabled in production
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $body   = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error  = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new RuntimeException("cURL error: {$error}");
        }

        if ($status !== 200) {
            throw new RuntimeException("SiteHost API returned HTTP {$status}: {$body}");
        }

        $decoded = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException('Failed to decode API response: ' . json_last_error_msg());
        }

        return $decoded;
    }
}
