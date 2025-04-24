<?php

declare(strict_types=1);

namespace Tourze\PSR15StaticFileRequestHandler\Tests;

use Nyholm\Psr7\ServerRequest;
use Nyholm\Psr7\Uri;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Tourze\PSR15StaticFileRequestHandler\StaticFileRequestHandler;

class StaticFileRequestHandlerTest extends TestCase
{
    private string $tempDir;
    private string $testFile;
    private string $testHtmlFile;
    private Filesystem $filesystem;

    protected function setUp(): void
    {
        $this->filesystem = new Filesystem();

        // 创建临时目录
        $this->tempDir = sys_get_temp_dir() . '/static-file-handler-test-' . uniqid();
        $this->filesystem->mkdir($this->tempDir);
        $this->filesystem->mkdir($this->tempDir . '/subdir');

        // 创建测试文件
        $this->testFile = $this->tempDir . '/test.txt';
        $this->filesystem->dumpFile($this->testFile, 'Test content');

        // 创建测试HTML文件
        $this->testHtmlFile = $this->tempDir . '/test.html';
        $this->filesystem->dumpFile($this->testHtmlFile, '<html><body>Test HTML</body></html>');

        // 创建index.html文件
        $this->filesystem->dumpFile($this->tempDir . '/subdir/index.html', '<html><body>Index</body></html>');
    }

    protected function tearDown(): void
    {
        // 清理测试文件和目录
        if ($this->filesystem->exists($this->tempDir)) {
            $this->filesystem->remove([$this->testFile, $this->testHtmlFile, $this->tempDir . '/subdir/index.html']);
            $this->filesystem->remove([$this->tempDir . '/subdir', $this->tempDir]);
        }
    }

    public function testHandleTextFile(): void
    {
        $handler = new StaticFileRequestHandler($this->tempDir, $this->filesystem);
        $request = new ServerRequest('GET', new Uri('/test.txt'));

        $response = $handler->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Test content', (string)$response->getBody());
        $this->assertEquals('text/plain', $response->getHeaderLine('Content-Type'));
        $this->assertNotEmpty($response->getHeaderLine('ETag'));
        $this->assertNotEmpty($response->getHeaderLine('Last-Modified'));
        $this->assertEquals('public, max-age=86400', $response->getHeaderLine('Cache-Control'));
    }

    public function testHandleHtmlFile(): void
    {
        $handler = new StaticFileRequestHandler($this->tempDir, $this->filesystem);
        $request = new ServerRequest('GET', new Uri('/test.html'));

        $response = $handler->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('<html><body>Test HTML</body></html>', (string)$response->getBody());
        $this->assertEquals('text/html', $response->getHeaderLine('Content-Type'));
    }

    public function testHandleDirectoryWithIndex(): void
    {
        $handler = new StaticFileRequestHandler($this->tempDir, $this->filesystem);
        $request = new ServerRequest('GET', new Uri('/subdir/'));

        $response = $handler->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('<html><body>Index</body></html>', (string)$response->getBody());
    }

    public function testHandleNonExistentFile(): void
    {
        $handler = new StaticFileRequestHandler($this->tempDir, $this->filesystem);
        $request = new ServerRequest('GET', new Uri('/non-existent.txt'));

        $response = $handler->handle($request);

        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testHandleCachedFileWithEtag(): void
    {
        $handler = new StaticFileRequestHandler($this->tempDir, $this->filesystem);

        // 首先发送请求获取ETag
        $request = new ServerRequest('GET', new Uri('/test.txt'));
        $response = $handler->handle($request);
        $etag = $response->getHeaderLine('ETag');

        // 然后使用ETag发送请求
        $request = new ServerRequest('GET', new Uri('/test.txt'));
        $request = $request->withHeader('If-None-Match', $etag);

        $response = $handler->handle($request);

        $this->assertEquals(304, $response->getStatusCode());
    }

    public function testHandleRangeRequest(): void
    {
        $handler = new StaticFileRequestHandler($this->tempDir, $this->filesystem);
        $request = new ServerRequest('GET', new Uri('/test.txt'));
        $request = $request->withHeader('Range', 'bytes=0-4');

        $response = $handler->handle($request);

        $this->assertEquals(206, $response->getStatusCode());
        $this->assertEquals('Test ', (string)$response->getBody());
        $this->assertEquals('bytes 0-4/12', $response->getHeaderLine('Content-Range'));
        $this->assertEquals('5', $response->getHeaderLine('Content-Length'));
    }
}
