<?php

declare(strict_types=1);

namespace Tourze\PSR15StaticFileRequestHandler;

use League\MimeTypeDetection\FinfoMimeTypeDetector;
use Nyholm\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

/**
 * 专门用于处理静态资源文件的请求处理器
 */
class StaticFileRequestHandler implements RequestHandlerInterface
{
    /**
     * @var FinfoMimeTypeDetector MIME类型检测器
     */
    private readonly FinfoMimeTypeDetector $mimeTypeDetector;

    public function __construct(
        private readonly string $publicPath,
        private readonly ?Filesystem $filesystem = null,
    ) {
        $this->mimeTypeDetector = new FinfoMimeTypeDetector();
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $filesystem = $this->filesystem ?? new Filesystem();
        $filePath = $this->resolveFilePath($request, $filesystem);

        if (!$this->isValidStaticFile($filePath, $filesystem)) {
            return new Response(404, body: 'File not found');
        }

        $fileInfo = $this->getFileInfo($filePath);

        if ($this->isCacheValid($request, $fileInfo)) {
            return $this->createNotModifiedResponse($fileInfo);
        }

        $headers = $this->buildResponseHeaders($filePath, $fileInfo);

        return $this->handleFileResponse($request, $filePath, $headers, $fileInfo);
    }

    private function resolveFilePath(ServerRequestInterface $request, Filesystem $filesystem): string
    {
        $path = $request->getUri()->getPath();
        $checkFile = "{$this->publicPath}/{$path}";
        $checkFile = str_replace('..', '/', $checkFile);

        return $this->resolveDirectoryIndex($checkFile, $filesystem);
    }

    private function resolveDirectoryIndex(string $checkFile, Filesystem $filesystem): string
    {
        if (!$filesystem->exists($checkFile) || !is_dir($checkFile)) {
            return $checkFile;
        }

        $checkFile = rtrim($checkFile, '/');

        if ($filesystem->exists("{$checkFile}/index.htm")) {
            return "{$checkFile}/index.htm";
        }

        if ($filesystem->exists("{$checkFile}/index.html")) {
            return "{$checkFile}/index.html";
        }

        return $checkFile;
    }

    private function isValidStaticFile(string $filePath, Filesystem $filesystem): bool
    {
        return $filesystem->exists($filePath)
            && is_file($filePath)
            && !str_contains($filePath, '.php');
    }

    /**
     * @return array{size: int|false, lastModified: int|false, lastModifiedDate: string, etag: string}
     */
    private function getFileInfo(string $filePath): array
    {
        $fileSize = filesize($filePath);
        $lastModified = filemtime($filePath);
        $lastModifiedDate = gmdate('D, d M Y H:i:s', false !== $lastModified ? $lastModified : 0) . ' GMT';
        $etag = sprintf('"%s"', md5((string) $lastModified . (string) $fileSize));

        return [
            'size' => $fileSize,
            'lastModified' => $lastModified,
            'lastModifiedDate' => $lastModifiedDate,
            'etag' => $etag,
        ];
    }

    /**
     * @param array{size: int|false, lastModified: int|false, lastModifiedDate: string, etag: string} $fileInfo
     */
    private function isCacheValid(ServerRequestInterface $request, array $fileInfo): bool
    {
        $ifModifiedSince = $request->getHeaderLine('If-Modified-Since');
        $ifNoneMatch = $request->getHeaderLine('If-None-Match');

        return ('' !== $ifModifiedSince && $ifModifiedSince === $fileInfo['lastModifiedDate'])
            || ('' !== $ifNoneMatch && $ifNoneMatch === $fileInfo['etag']);
    }

    /**
     * @param array{size: int|false, lastModified: int|false, lastModifiedDate: string, etag: string} $fileInfo
     */
    private function createNotModifiedResponse(array $fileInfo): ResponseInterface
    {
        return new Response(
            304,
            [
                'Cache-Control' => 'public, max-age=86400',
                'Last-Modified' => $fileInfo['lastModifiedDate'],
                'ETag' => $fileInfo['etag'],
            ]
        );
    }

    /**
     * @param array{size: int|false, lastModified: int|false, lastModifiedDate: string, etag: string} $fileInfo
     * @return array<string, string>
     */
    private function buildResponseHeaders(string $filePath, array $fileInfo): array
    {
        $mimeType = $this->mimeTypeDetector->detectMimeTypeFromPath($filePath) ?? 'application/octet-stream';

        return [
            'Content-Type' => $mimeType,
            'Content-Length' => (string) $fileInfo['size'],
            'Last-Modified' => $fileInfo['lastModifiedDate'],
            'ETag' => $fileInfo['etag'],
            'Cache-Control' => 'public, max-age=86400',
        ];
    }

    /**
     * @param array<string, string> $headers
     * @param array{size: int|false, lastModified: int|false, lastModifiedDate: string, etag: string} $fileInfo
     */
    private function handleFileResponse(
        ServerRequestInterface $request,
        string $filePath,
        array $headers,
        array $fileInfo,
    ): ResponseInterface {
        $rangeHeader = $request->getHeaderLine('Range');

        if ('' !== $rangeHeader && str_starts_with($rangeHeader, 'bytes=')) {
            return $this->handleRangeRequest($filePath, $headers, $fileInfo, $rangeHeader);
        }

        return $this->handleFullFileRequest($filePath, $headers);
    }

    /**
     * @param array<string, string> $headers
     * @param array{size: int|false, lastModified: int|false, lastModifiedDate: string, etag: string} $fileInfo
     */
    private function handleRangeRequest(
        string $filePath,
        array $headers,
        array $fileInfo,
        string $rangeHeader,
    ): ResponseInterface {
        $range = substr($rangeHeader, 6);
        $rangeParts = explode('-', $range);
        $rangeStart = '' === $rangeParts[0] ? 0 : (int) $rangeParts[0];

        $fileSize = false !== $fileInfo['size'] ? $fileInfo['size'] : 0;
        $rangeEnd = '' === $rangeParts[1] ? $fileSize - 1 : (int) $rangeParts[1];
        if ($rangeStart >= $fileSize || $rangeEnd >= $fileSize) {
            return new Response(
                416,
                [
                    'Content-Range' => "bytes */{$fileSize}",
                ]
            );
        }

        $length = $rangeEnd - $rangeStart + 1;
        $headers['Content-Length'] = (string) $length;
        $headers['Content-Range'] = "bytes {$rangeStart}-{$rangeEnd}/{$fileSize}";

        try {
            $content = $this->readFileRange($filePath, $rangeStart, $length);

            return new Response(206, $headers, $content);
        } catch (IOException $e) {
            return new Response(500, body: 'Error reading file: ' . $e->getMessage());
        }
    }

    private function readFileRange(string $filePath, int $start, int $length): string
    {
        $fileHandle = fopen($filePath, 'rb');
        if (false === $fileHandle) {
            throw new IOException('Error opening file');
        }

        fseek($fileHandle, $start);
        $content = fread($fileHandle, max(1, $length));
        fclose($fileHandle);

        if (false === $content) {
            throw new IOException('Error reading file range');
        }

        return $content;
    }

    /**
     * @param array<string, string> $headers
     */
    private function handleFullFileRequest(string $filePath, array $headers): ResponseInterface
    {
        try {
            $content = file_get_contents($filePath);
            if (false === $content) {
                return new Response(500, body: 'Error reading file');
            }

            return new Response(200, $headers, $content);
        } catch (IOException $e) {
            return new Response(500, body: 'Error reading file: ' . $e->getMessage());
        }
    }
}
