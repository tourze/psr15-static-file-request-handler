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
        $path = $request->getUri()->getPath();

        // 静态文件的支持
        $checkFile = "{$this->publicPath}/{$path}";
        $checkFile = str_replace('..', '/', $checkFile);

        // 兼容访问目录
        if ($filesystem->exists($checkFile) && is_dir($checkFile)) {
            $checkFile = rtrim($checkFile, '/');
            if ($filesystem->exists("{$checkFile}/index.htm")) {
                $checkFile = "{$checkFile}/index.htm";
            }
            if ($filesystem->exists("{$checkFile}/index.html")) {
                $checkFile = "{$checkFile}/index.html";
            }
        }

        // 只处理存在的静态文件，不处理PHP文件
        if ($filesystem->exists($checkFile) && is_file($checkFile) && !str_contains($checkFile, '.php')) {
            $fileSize = filesize($checkFile);
            $lastModified = filemtime($checkFile);
            $lastModifiedDate = gmdate('D, d M Y H:i:s', $lastModified) . ' GMT';
            $etag = sprintf('"%s"', md5((string)$lastModified . (string)$fileSize));

            // 检查缓存头信息
            $ifModifiedSince = $request->getHeaderLine('If-Modified-Since');
            $ifNoneMatch = $request->getHeaderLine('If-None-Match');

            // 处理缓存有效情况
            if (
                (!empty($ifModifiedSince) && $ifModifiedSince === $lastModifiedDate) ||
                (!empty($ifNoneMatch) && $ifNoneMatch === $etag)
            ) {
                return new Response(
                    304,
                    [
                        'Cache-Control' => 'public, max-age=86400',
                        'Last-Modified' => $lastModifiedDate,
                        'ETag' => $etag,
                    ]
                );
            }

            // 获取MIME类型
            $mimeType = $this->mimeTypeDetector->detectMimeTypeFromPath($checkFile) ?? 'application/octet-stream';

            // 准备响应头
            $headers = [
                'Content-Type' => $mimeType,
                'Content-Length' => (string)$fileSize,
                'Last-Modified' => $lastModifiedDate,
                'ETag' => $etag,
                'Cache-Control' => 'public, max-age=86400',
            ];

            // 处理范围请求
            $rangeHeader = $request->getHeaderLine('Range');
            if (!empty($rangeHeader) && str_starts_with($rangeHeader, 'bytes=')) {
                // 提取范围
                $range = substr($rangeHeader, 6);
                $rangeParts = explode('-', $range);
                $rangeStart = $rangeParts[0] === '' ? 0 : (int)$rangeParts[0];
                $rangeEnd = $rangeParts[1] === '' ? $fileSize - 1 : (int)$rangeParts[1];

                // 验证范围有效性
                if ($rangeStart >= $fileSize || $rangeEnd >= $fileSize) {
                    return new Response(
                        416,
                        [
                            'Content-Range' => "bytes */{$fileSize}",
                        ]
                    );
                }

                $length = $rangeEnd - $rangeStart + 1;
                $headers['Content-Length'] = (string)$length;
                $headers['Content-Range'] = "bytes {$rangeStart}-{$rangeEnd}/{$fileSize}";

                try {
                    // 读取文件指定范围内容
                    $fileHandle = fopen($checkFile, 'rb');
                    if ($fileHandle === false) {
                        return new Response(500, body: 'Error opening file');
                    }

                    fseek($fileHandle, $rangeStart);
                    $content = fread($fileHandle, $length);
                    fclose($fileHandle);

                    return new Response(
                        206,
                        $headers,
                        $content
                    );
                } catch (IOException $e) {
                    return new Response(500, body: 'Error reading file: ' . $e->getMessage());
                }
            }

            try {
                // 读取整个文件内容
                $content = file_get_contents($checkFile);
                if ($content === false) {
                    return new Response(500, body: 'Error reading file');
                }

                return new Response(
                    200,
                    $headers,
                    $content
                );
            } catch (IOException $e) {
                return new Response(500, body: 'Error reading file: ' . $e->getMessage());
            }
        }

        // 如果不是静态文件，则返回404
        return new Response(404, body: 'File not found');
    }
}
