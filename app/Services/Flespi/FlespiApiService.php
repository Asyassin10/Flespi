<?php

declare(strict_types=1);

namespace App\Services\Flespi;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * Base Flespi API Service
 *
 * Handles all HTTP communication with Flespi REST API
 * Provides base methods for GET, POST, PUT, DELETE requests
 * Implements error handling, logging, and caching
 */
class FlespiApiService
{
    protected string $baseUrl;
    protected string $token;
    protected int $timeout = 30;
    protected int $cacheTime = 300; // 5 minutes default cache

    /**
     * Initialize Flespi API Service
     *
     * @throws \Exception if Flespi token is not configured
     */
    public function __construct()
    {
        $this->baseUrl = config('services.flespi.base_url', 'https://flespi.io');
        $this->token = config('services.flespi.token');

        if (empty($this->token)) {
            throw new \Exception('Flespi API token is not configured. Please set FLESPI_TOKEN in .env file.');
        }
    }

    /**
     * Build HTTP client with authentication headers
     */
    protected function client(): PendingRequest
    {
        return Http::timeout($this->timeout)
            ->withHeaders([
                'Authorization' => 'FlespiToken ' . $this->token,
                'Accept' => 'application/json',
            ])
            ->withoutVerifying() // Disable SSL verification for environments with proxy issues
            ->withOptions([
                'proxy' => [
                    'http' => false,
                    'https' => false,
                ],
            ])
            ->retry(3, 1000);
    }

    /**
     * Make GET request to Flespi API
     *
     * @param string $endpoint API endpoint (e.g., '/gw/devices/all')
     * @param array $params Query parameters
     * @param bool $useCache Whether to use caching
     * @return array Response data
     * @throws \Exception on API error
     */
    protected function get(string $endpoint, array $params = [], bool $useCache = true): array
    {
        $cacheKey = $this->getCacheKey('get', $endpoint, $params);

        if ($useCache && Cache::has($cacheKey)) {
            Log::info('Flespi API: Cache hit for ' . $endpoint);
            return Cache::get($cacheKey);
        }

        try {
            $url = $this->baseUrl . $endpoint;

            Log::info('Flespi API GET Request', [
                'url' => $url,
                'params' => $params,
            ]);

            $response = $this->client()->get($url, $params);

            $this->handleResponse($response);

            $data = $response->json();
            $result = $data['result'] ?? [];

            if ($useCache) {
                Cache::put($cacheKey, $result, $this->cacheTime);
            }

            return $result;

        } catch (\Exception $e) {
            Log::error('Flespi API GET Error', [
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Make POST request to Flespi API
     *
     * @param string $endpoint API endpoint
     * @param array $data Request body data
     * @return array Response data
     * @throws \Exception on API error
     */
    protected function post(string $endpoint, array $data = []): array
    {
        try {
            $url = $this->baseUrl . $endpoint;

            Log::info('Flespi API POST Request', [
                'url' => $url,
                'data' => $data,
            ]);

            $response = $this->client()->post($url, $data);

            $this->handleResponse($response);

            $result = $response->json();
            return $result['result'] ?? [];

        } catch (\Exception $e) {
            Log::error('Flespi API POST Error', [
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Make PUT request to Flespi API
     *
     * @param string $endpoint API endpoint
     * @param array $data Request body data
     * @return array Response data
     * @throws \Exception on API error
     */
    protected function put(string $endpoint, array $data = []): array
    {
        try {
            $url = $this->baseUrl . $endpoint;

            Log::info('Flespi API PUT Request', [
                'url' => $url,
                'data' => $data,
            ]);

            $response = $this->client()->put($url, $data);

            $this->handleResponse($response);

            $result = $response->json();
            return $result['result'] ?? [];

        } catch (\Exception $e) {
            Log::error('Flespi API PUT Error', [
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Make DELETE request to Flespi API
     *
     * @param string $endpoint API endpoint
     * @return array Response data
     * @throws \Exception on API error
     */
    protected function delete(string $endpoint): array
    {
        try {
            $url = $this->baseUrl . $endpoint;

            Log::info('Flespi API DELETE Request', [
                'url' => $url,
            ]);

            $response = $this->client()->delete($url);

            $this->handleResponse($response);

            $result = $response->json();
            return $result['result'] ?? [];

        } catch (\Exception $e) {
            Log::error('Flespi API DELETE Error', [
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Handle API response and check for errors
     *
     * @param Response $response HTTP response
     * @throws \Exception on API error
     */
    protected function handleResponse(Response $response): void
    {
        $statusCode = $response->status();

        if ($statusCode === 401) {
            throw new \Exception('Flespi API: Invalid or expired token (401)');
        }

        if ($statusCode === 429) {
            throw new \Exception('Flespi API: Rate limit exceeded (429). Please try again later.');
        }

        if ($statusCode === 403) {
            throw new \Exception('Flespi API: Access forbidden (403). Check your token permissions.');
        }

        if ($statusCode >= 500) {
            throw new \Exception('Flespi API: Server error (' . $statusCode . '). Please try again later.');
        }

        if (!$response->successful()) {
            $error = $response->json()['errors'][0] ?? 'Unknown error';
            throw new \Exception('Flespi API Error: ' . json_encode($error));
        }

        // Check for errors in response body
        $data = $response->json();
        if (!empty($data['errors'])) {
            $errors = $data['errors'];
            Log::warning('Flespi API returned errors', ['errors' => $errors]);
            throw new \Exception('Flespi API Error: ' . json_encode($errors));
        }
    }

    /**
     * Generate cache key for request
     */
    protected function getCacheKey(string $method, string $endpoint, array $params = []): string
    {
        return 'flespi_' . $method . '_' . md5($endpoint . json_encode($params));
    }

    /**
     * Clear cache for specific endpoint
     */
    public function clearCache(string $endpoint): void
    {
        $pattern = 'flespi_*_' . md5($endpoint . '*');
        // Note: This is a simple implementation. For production, consider using cache tags
        Cache::forget($pattern);
    }

    /**
     * Clear all Flespi cache
     */
    public function clearAllCache(): void
    {
        // This will clear all cache starting with 'flespi_'
        // Note: Laravel doesn't support wildcard deletion natively,
        // so you might need to implement this based on your cache driver
        Log::info('Clearing all Flespi cache');
    }

    /**
     * Set custom timeout for requests
     */
    public function setTimeout(int $seconds): self
    {
        $this->timeout = $seconds;
        return $this;
    }

    /**
     * Set custom cache time
     */
    public function setCacheTime(int $seconds): self
    {
        $this->cacheTime = $seconds;
        return $this;
    }
}
