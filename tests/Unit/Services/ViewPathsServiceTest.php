<?php

namespace Tests\Unit\Services;

use App\Services\ViewPathsService;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Log\LogManager;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\View;
use IurieMalai\ViewPaths\Tests\TestCase;
use Livewire\Volt\Volt;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Statamic\Sites\Site;

class ViewPathsServiceTest extends TestCase
{
    protected $cacheMock;

    protected $loggerMock;

    protected ViewPathsService $service;

    protected $fakePath;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock the Site class
        $mockedSite = $this->createMock(Site::class);
        // Define how the mock should behave, e.g., returning a default value for `$handle`
        $mockedSite->method('getHandle')->willReturn('default-handle');

        // Bind the mock to the application container
        App::instance(Site::class, $mockedSite);

        $this->cacheMock = Mockery::mock(CacheRepository::class);
        $this->loggerMock = Mockery::mock(LogManager::class);

        // Create test directory if needed
        $this->fakePath = base_path('tests/Fixtures/child_views');
        if (! is_dir($this->fakePath)) {
            mkdir($this->fakePath, 0777, true);
        }

        config()->set('view_paths.paths', ['/some/path']);
        config()->set('view_paths.namespaced_paths', ['custom' => '/some/namespace/path']);
        config()->set('view_paths.cache_enabled', true);
        config()->set('view_paths.cache_duration', 'forever');
        config()->set('view_paths.cache_key', 'view_paths_test');
        config()->set('view_paths.logging.enabled', false);

        $this->service = new ViewPathsService($this->cacheMock, $this->loggerMock);
    }

    #[Test]
    public function it_can_check_if_cache_is_present()
    {
        $this->cacheMock->shouldReceive('has')
            ->once()
            ->with('view_paths_test')
            ->andReturn(true);

        $this->assertTrue($this->service->isCached());
    }

    #[Test]
    public function it_loads_paths_from_cache_when_enabled()
    {
        // Prepare cache data
        $cachedData = [
            'paths' => ['/cached/path'],
            'namespaced_paths' => ['cached_namespace' => '/cached/namespace/path'],
        ];

        // Mock cache is enabled
        config()->set('view_paths.cache_enabled', true);

        // Mock cache has the data
        $this->cacheMock->shouldReceive('has')
            ->with('view_paths_test')
            ->andReturn(true);

        $this->cacheMock->shouldReceive('get')
            ->with('view_paths_test', Mockery::type('array'))
            ->andReturn($cachedData);

        // The service should not try to get paths from filesystem
        $serviceSpy = Mockery::mock(ViewPathsService::class, [$this->cacheMock, $this->loggerMock])
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $serviceSpy->shouldReceive('getValidPaths')->never();
        $serviceSpy->shouldReceive('getValidNamespacedPaths')->never();

        // Get paths method should return cached paths
        $paths = $this->invokeMethod($serviceSpy, 'getPathsFromCache');
        $namespacedPaths = $this->invokeMethod($serviceSpy, 'getNamespacedPathsFromCache');

        // Assert paths match what was in cache
        $this->assertEquals($cachedData['paths'], $paths);
        $this->assertEquals($cachedData['namespaced_paths'], $namespacedPaths);
    }

    #[Test]
    public function it_loads_paths_directly_when_cache_disabled()
    {
        config()->set('view_paths.cache_enabled', false);
        config()->set('view_paths.paths', [
            $this->fakePath,
        ]);

        $service = new ViewPathsService($this->cacheMock, $this->loggerMock);
        $paths = $this->invokeMethod($service, 'getValidPaths');

        $this->assertNotEmpty($paths);
        $this->assertStringContainsString('child_views', $paths[0]);
    }

    #[Test]
    public function it_loads_namespaced_paths_directly_when_cache_disabled()
    {
        config()->set('view_paths.cache_enabled', false);
        config()->set('view_paths.namespaced_paths', [
            'test' => $this->fakePath,
        ]);

        $service = new ViewPathsService($this->cacheMock, $this->loggerMock);
        $paths = $this->invokeMethod($service, 'getValidNamespacedPaths');

        $this->assertNotEmpty($paths);
        $this->assertStringContainsString('child_views', $paths['test']);
    }

    #[Test]
    public function it_loads_and_filters_valid_paths()
    {
        // Set up a mix of valid and invalid paths
        $validPath = $this->fakePath;
        $invalidPath = base_path('tests/Fixtures/nonexistent_folder');

        config()->set('view_paths.paths', [$validPath, $invalidPath]);
        config()->set('view_paths.namespaced_paths', [
            'valid' => $validPath,
            'invalid' => $invalidPath,
        ]);

        $service = new ViewPathsService($this->cacheMock, $this->loggerMock);

        // Test regular paths
        $validPaths = $this->invokeMethod($service, 'getValidPaths');
        $this->assertCount(1, $validPaths);
        $this->assertEquals($validPath, $validPaths[0]);

        // Test namespaced paths
        $validNamespacedPaths = $this->invokeMethod($service, 'getValidNamespacedPaths');
        $this->assertCount(1, $validNamespacedPaths);
        $this->assertEquals($validPath, $validNamespacedPaths['valid']);
        $this->assertArrayNotHasKey('invalid', $validNamespacedPaths);
    }

    #[Test]
    public function it_loads_view_paths_correctly()
    {
        // Set up test data
        $regularPaths = ['/path/one', '/path/two'];
        $namespacedPaths = [
            'admin' => '/admin/views',
            'volt-livewire' => '/volt/components',
        ];

        // Mock cache behavior
        $this->cacheMock->shouldReceive('has')->andReturn(true);
        $this->cacheMock->shouldReceive('get')->andReturn([
            'paths' => $regularPaths,
            'namespaced_paths' => $namespacedPaths,
        ]);

        // Mock View and Volt
        View::shouldReceive('prependLocation')->once()->with('/path/one');
        View::shouldReceive('prependLocation')->once()->with('/path/two');

        View::shouldReceive('prependNamespace')->once()->with('admin', '/admin/views');

        Volt::shouldReceive('mount')->once()->with('/volt/components');

        // Execute the method
        $this->service->loadViewPaths();

        // If no exceptions, the test passes
        $this->assertTrue(true);
    }

    #[Test]
    public function it_warms_the_cache_forever()
    {
        $this->cacheMock->shouldReceive('has')
            ->once()
            ->with('view_paths_test')
            ->andReturn(false);

        $this->cacheMock->shouldReceive('forever')
            ->once()
            ->with('view_paths_test', Mockery::type('array'))
            ->andReturnTrue();

        $this->service->warmCache();

        $this->assertTrue(true); // Basic assert to satisfy PHPUnit
    }

    #[Test]
    public function it_clears_the_cache()
    {
        $this->cacheMock->shouldReceive('forget')
            ->once()
            ->with('view_paths_test')
            ->andReturn(true);

        $this->assertTrue($this->service->clearCache());
    }

    #[Test]
    public function it_does_not_warm_cache_when_disabled()
    {
        config()->set('view_paths.cache_enabled', false);

        // Need to mock has() for clearCache() which is called by warmCache()
        $this->cacheMock->shouldReceive('has')
            ->with('view_paths_test')
            ->andReturn(false);

        $this->cacheMock->shouldReceive('forget')->never(); // Won't clear if not present
        $this->cacheMock->shouldReceive('forever')->never();
        $this->cacheMock->shouldReceive('put')->never();

        $service = new ViewPathsService($this->cacheMock, $this->loggerMock);
        $service->warmCache();

        $this->assertTrue(true);
    }

    #[Test]
    public function it_handles_invalid_cache_duration_gracefully()
    {
        Carbon::setTestNow(now());

        config()->set('view_paths.cache_duration', 'invalid');
        $this->service = new ViewPathsService($this->cacheMock, $this->loggerMock);

        $this->cacheMock->shouldReceive('has')->once()->with('view_paths_test')->andReturn(false);

        // Now expect put() because invalid duration is treated as 'put with timestamp'
        $this->cacheMock->shouldReceive('put')
            ->once()
            ->with('view_paths_test', Mockery::type('array'), Mockery::type(Carbon::class))
            ->andReturnTrue();

        $this->service->warmCache();

        $this->assertTrue(true);
    }

    #[Test]
    public function it_parses_duration_correctly()
    {
        Carbon::setTestNow(now()); // Freeze now FIRST

        $testCases = [
            'forever' => null,
            null => null,
            '1s' => now()->addSecond(),
            '30m' => now()->addMinutes(30),
            '2h' => now()->addHours(2),
            '7d' => now()->addDays(7),
            '2w' => now()->addWeeks(2),
            '3M' => now()->addMonths(3),
            '1y' => now()->addYear(),
            'invalid' => now()->addHour(),
        ];

        foreach ($testCases as $input => $expected) {
            $result = $this->invokeMethod($this->service, 'parseDuration', [$input]);

            if ($expected === null) {
                $this->assertNull($result);
            } else {
                $this->assertEquals($expected->timestamp, $result->timestamp);
            }
        }

        $this->assertTrue(true);
    }

    #[Test]
    public function it_logs_messages_when_enabled()
    {
        // Enable logging
        config()->set('view_paths.logging.enabled', true);
        config()->set('view_paths.logging.level', 'info');

        $service = new ViewPathsService($this->cacheMock, $this->loggerMock);

        // Set expectation
        $this->loggerMock->shouldReceive('info')
            ->once()
            ->with('Test message');

        // Call the log method
        $this->invokeMethod($service, 'log', ['Test message']);

        $this->assertTrue(true);
    }

    #[Test]
    public function it_doesnt_log_when_disabled()
    {
        // Disable logging
        config()->set('view_paths.logging.enabled', false);

        $service = new ViewPathsService($this->cacheMock, $this->loggerMock);

        // Set expectation that logger should never be called
        $this->loggerMock->shouldReceive('debug')->never();
        $this->loggerMock->shouldReceive('info')->never();

        // Call the log method
        $this->invokeMethod($service, 'log', ['Test message']);

        $this->assertTrue(true);
    }

    #[Test]
    public function it_returns_cache_info()
    {
        $this->cacheMock->shouldReceive('has')
            ->with('view_paths_test')
            ->andReturn(true);

        $this->cacheMock->shouldReceive('get')
            ->with('view_paths_test', Mockery::type('array'))
            ->andReturn([
                'paths' => ['/test/path'],
                'namespaced_paths' => ['test' => '/test/namespace'],
            ]);

        $cacheInfo = $this->service->getCacheInfo();

        $this->assertIsArray($cacheInfo);
        $this->assertArrayHasKey('config', $cacheInfo);
        $this->assertArrayHasKey('paths', $cacheInfo);
        $this->assertEquals(true, $cacheInfo['config']['enabled']);
        $this->assertEquals('forever', $cacheInfo['config']['duration']);
        $this->assertEquals(true, $cacheInfo['config']['is_cached']);
    }

    #[Test]
    public function it_gets_namespaced_paths()
    {
        config()->set('view_paths.namespaced_paths', [
            'test' => '/test/path',
            'admin' => '/admin/path',
        ]);

        $service = new ViewPathsService($this->cacheMock, $this->loggerMock);
        $paths = $service->getNamespacedPaths();

        $this->assertIsArray($paths);
        $this->assertCount(2, $paths);
        $this->assertEquals('/test/path', $paths['test']);
        $this->assertEquals('/admin/path', $paths['admin']);
    }

    /**
     * Helper to call protected/private methods.
     */
    protected function invokeMethod(object $object, string $methodName, array $parameters = [])
    {
        $reflection = new \ReflectionClass($object);
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
