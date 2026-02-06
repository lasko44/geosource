<?php

namespace Tests\Unit\Services;

use App\Services\BlogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BlogServiceTest extends TestCase
{
    protected BlogService $blogService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->blogService = new BlogService;
    }

    // ==========================================
    // Visitor Hash Tests
    // ==========================================

    public function test_create_visitor_hash_returns_sha256_hash(): void
    {
        $request = Request::create('/blog/test', 'GET', [], [], [], [
            'REMOTE_ADDR' => '192.168.1.1',
            'HTTP_USER_AGENT' => 'Mozilla/5.0',
            'HTTP_ACCEPT_LANGUAGE' => 'en-US,en;q=0.9',
        ]);

        $hash = $this->blogService->createVisitorHash($request);

        $this->assertEquals(64, strlen($hash)); // SHA256 produces 64 hex characters
        $this->assertMatchesRegularExpression('/^[a-f0-9]+$/', $hash);
    }

    public function test_create_visitor_hash_is_consistent(): void
    {
        $request = Request::create('/blog/test', 'GET', [], [], [], [
            'REMOTE_ADDR' => '192.168.1.1',
            'HTTP_USER_AGENT' => 'Mozilla/5.0',
            'HTTP_ACCEPT_LANGUAGE' => 'en-US',
        ]);

        $hash1 = $this->blogService->createVisitorHash($request);
        $hash2 = $this->blogService->createVisitorHash($request);

        $this->assertEquals($hash1, $hash2);
    }

    public function test_create_visitor_hash_differs_for_different_ips(): void
    {
        $request1 = Request::create('/blog/test', 'GET', [], [], [], [
            'REMOTE_ADDR' => '192.168.1.1',
            'HTTP_USER_AGENT' => 'Mozilla/5.0',
        ]);

        $request2 = Request::create('/blog/test', 'GET', [], [], [], [
            'REMOTE_ADDR' => '192.168.1.2',
            'HTTP_USER_AGENT' => 'Mozilla/5.0',
        ]);

        $hash1 = $this->blogService->createVisitorHash($request1);
        $hash2 = $this->blogService->createVisitorHash($request2);

        $this->assertNotEquals($hash1, $hash2);
    }

    // ==========================================
    // Geo Data Tests
    // ==========================================

    public function test_get_geo_data_returns_empty_for_localhost(): void
    {
        $result = $this->blogService->getGeoData('127.0.0.1');

        $this->assertEquals([], $result);
    }

    public function test_get_geo_data_returns_empty_for_private_ip(): void
    {
        $result = $this->blogService->getGeoData('192.168.1.1');

        $this->assertEquals([], $result);
    }

    public function test_get_geo_data_returns_empty_for_10_network(): void
    {
        $result = $this->blogService->getGeoData('10.0.0.1');

        $this->assertEquals([], $result);
    }

    public function test_get_geo_data_returns_empty_for_null_ip(): void
    {
        $result = $this->blogService->getGeoData(null);

        $this->assertEquals([], $result);
    }

    public function test_get_geo_data_returns_empty_for_empty_ip(): void
    {
        $result = $this->blogService->getGeoData('');

        $this->assertEquals([], $result);
    }

    public function test_get_geo_data_returns_country_on_success(): void
    {
        Http::fake([
            'ip-api.com/*' => Http::response([
                'status' => 'success',
                'country' => 'United States',
            ], 200),
        ]);

        $result = $this->blogService->getGeoData('8.8.8.8');

        $this->assertArrayHasKey('country', $result);
        $this->assertEquals('United States', $result['country']);
    }

    public function test_get_geo_data_returns_empty_on_api_failure(): void
    {
        Http::fake([
            'ip-api.com/*' => Http::response([
                'status' => 'fail',
            ], 200),
        ]);

        $result = $this->blogService->getGeoData('8.8.8.8');

        $this->assertEquals([], $result);
    }

    public function test_get_geo_data_returns_empty_on_http_error(): void
    {
        Http::fake([
            'ip-api.com/*' => Http::response('Server Error', 500),
        ]);

        $result = $this->blogService->getGeoData('8.8.8.8');

        $this->assertEquals([], $result);
    }

    public function test_get_geo_data_handles_timeout(): void
    {
        Http::fake([
            'ip-api.com/*' => function () {
                throw new \Illuminate\Http\Client\ConnectionException('Connection timed out');
            },
        ]);

        $result = $this->blogService->getGeoData('8.8.8.8');

        $this->assertEquals([], $result);
    }
}
