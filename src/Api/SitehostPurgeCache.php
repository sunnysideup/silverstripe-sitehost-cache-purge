<?php

declare(strict_types=1);

namespace Sunnysideup\SitehostCachePurge\Api;

use RuntimeException;
use SilverStripe\Control\Director;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Environment;
use SilverStripe\Core\Flushable;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\ORM\DB;

/**
 * SiteHost API Client
 *
 * A minimal PHP class for interacting with the SiteHost API v1.5.
 *
 * @see https://docs.sitehost.nz/api/v1.5/
 */
class SitehostPurgeCache implements Flushable
{
    use Injectable;
    use Configurable;
    public static function flush()
    {
        $outcome = SitehostPurgeCache::create()->purgeCache();
        if (Director::is_cli()) {
            DB::alteration_message(
                "Sitehost cache purge: " .
                ($outcome['status'] ?? 'ERROR: NO STATUS') . " - " .
                ($outcome['msg'] ?? 'NO MESSAGE'),
                $outcome['status'] ? 'created' : 'deleted'
            );
        }
    }

    private const BASE_URL = 'https://api.sitehost.nz/1.5';

    private static string $api_key = '';
    private static int $client_id = 0;
    private static string $server_id = '';
    private static string $name_id = '';


    private string $apiKey;
    private int $clientId;
    private string $server;
    private string $name;

    public function __construct(?string $apiKey = null, ?int $clientId = null)
    {
        $this->apiKey   = (string) ($apiKey ?: $this->config()->get('api_key') ?: Environment::getEnv('SS_SITEHOST_API_KEY'));
        $this->clientId = (int) ($clientId ?: $this->config()->get('client_id') ?: Environment::getEnv('SS_SITEHOST_CLIENT_ID'));
    }

    /**
     * Purge the cache for a Cloud Container stack.
     *
     * Calls POST /cloud/stack/purge_cache.json
     *
     * @param  string $server  The server name, e.g. "ch-servername"
     * @param  string $name    The stack/container name, e.g. "examplenz"
     * @return array           Decoded JSON response: ['status' => bool, 'msg' => string]
     * @throws \RuntimeException On cURL error or a non-200 HTTP response
     */
    public function purgeCache(?string $server = null, ?string $name = null): array
    {
        $this->server = (string) ($server ?: $this->config()->get('server_id') ?: Environment::getEnv('SS_SITEHOST_SERVER'));
        $this->name = (string) ($name ?: $this->config()->get('name_id') ?: Environment::getEnv('SS_SITEHOST_NAME'));
        if ($this->isReadyToPurge()) {
            return $this->post('/cloud/stack/purge_cache.json', [
                'server' => $this->server ?: $server,
                'name'   => $this->name ?: $name,
            ]);
        } else {
            return [
                'status' => false,
                'msg' => 'SitehostPurgeCache::purgeCache() missing configuration: apiKey, clientId, server, and name are required',
            ];
        }
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

    protected function isReadyToPurge(): bool
    {
        if ($this->apiKey || $this->clientId || $this->server || $this->name) {
            if ($this->apiKey && $this->clientId && $this->server && $this->name) {
                return true;
            } else {
                user_error('SitehostPurgeCache::flush() missing some configuration: apiKey, clientId, server, and name are required', E_USER_NOTICE);
            }
        }
        return false;
    }
}
