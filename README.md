# PSR-15 Static File Request Handler

[English](README.md) | [中文](README.zh-CN.md)

[![Latest Version][badge-version]][link-packagist]
[![PHP Version][badge-php]][link-packagist]
[![License][badge-license]][link-license]
[![Build Status][badge-build]][link-travis]
[![Quality Score][badge-quality]][link-scrutinizer]
[![Total Downloads][badge-downloads]][link-packagist]

[badge-version]: https://img.shields.io/packagist/v/tourze/psr15-static-file-request-handler.svg?style=flat-square
[badge-php]: https://img.shields.io/packagist/php-v/tourze/psr15-static-file-request-handler.svg?style=flat-square
[badge-license]: https://img.shields.io/github/license/tourze/php-monorepo.svg?style=flat-square
[badge-build]: https://img.shields.io/travis/tourze/psr15-static-file-request-handler/master.svg?style=flat-square
[badge-quality]: https://img.shields.io/scrutinizer/g/tourze/psr15-static-file-request-handler.svg?style=flat-square
[badge-downloads]: https://img.shields.io/packagist/dt/tourze/psr15-static-file-request-handler.svg?style=flat-square
[link-packagist]: https://packagist.org/packages/tourze/psr15-static-file-request-handler
[link-license]: LICENSE
[link-travis]: https://travis-ci.org/tourze/psr15-static-file-request-handler
[link-scrutinizer]: https://scrutinizer-ci.com/g/tourze/psr15-static-file-request-handler

A PSR-15 compatible request handler for serving static files efficiently in PHP applications.
Supports cache headers, range requests, directory index, and MIME type detection.

## Features

- PSR-15 compatible static file request handler
- Supports directory index (index.html, index.htm)
- MIME type auto-detection using league/mime-type-detection
- HTTP cache support (ETag, Last-Modified, 304 Not Modified)
- Range requests (partial content, 206)
- Prevents serving PHP files for security
- Easy integration with any PSR-15 middleware stack

## Installation

**Install via Composer:**

```bash
composer require tourze/psr15-static-file-request-handler
```

## Dependencies

**Requirements:**

- PHP >= 8.1
- Composer

**Dependencies:**

- `psr/http-message` - PSR-7 HTTP message interfaces
- `psr/http-server-handler` - PSR-15 HTTP handlers
- `league/mime-type-detection` - MIME type detection
- `nyholm/psr7` - PSR-7 implementation
- `symfony/filesystem` - File system operations

## Quick Start

```php
use Tourze\PSR15StaticFileRequestHandler\StaticFileRequestHandler;
use Nyholm\Psr7\ServerRequest;

$publicPath = __DIR__ . '/public';
$handler = new StaticFileRequestHandler($publicPath);
$request = new ServerRequest('GET', '/test.txt');
$response = $handler->handle($request);

// Output response body
echo $response->getBody();
```

### Example: Handling Range Requests

```php
$request = $request->withHeader('Range', 'bytes=0-4');
$response = $handler->handle($request);
// Will return partial content (206) with specified range
```

## Documentation

### Public API

- **Constructor:** `StaticFileRequestHandler::__construct(string $publicPath, ?Filesystem $filesystem = null)`
- **Handler method:** `handle(ServerRequestInterface $request): ResponseInterface`

### Response Codes

- **200 OK:** File found and served successfully
- **206 Partial Content:** Range request served successfully
- **304 Not Modified:** File not modified (cache hit)
- **404 Not Found:** File does not exist
- **416 Range Not Satisfiable:** Invalid range request
- **500 Internal Server Error:** File read error

### Configuration

- `publicPath`: The root directory for static files
- Optionally pass a Symfony Filesystem instance

## Advanced Usage

### Custom Filesystem Integration

```php
use Symfony\Component\Filesystem\Filesystem;

$filesystem = new Filesystem();
$handler = new StaticFileRequestHandler($publicPath, $filesystem);
```

### Middleware Integration

```php
use Slim\App;

$app = new App();
$app->add(function ($request, $handler) {
    $staticHandler = new StaticFileRequestHandler(__DIR__ . '/public');
    return $staticHandler->handle($request);
});
```

### Cache Headers

The handler automatically sets cache headers:
- `ETag` for cache validation
- `Last-Modified` for conditional requests
- `Cache-Control: public, max-age=86400` for browser caching

### Range Request Support

Supports HTTP range requests for:
- Large file downloads
- Video streaming
- Progressive loading

### Directory Index

Automatically serves index files:
- `index.html` (preferred)
- `index.htm` (fallback)

## Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/fooBar`)
3. Commit your changes (`git commit -am 'Add some fooBar'`)
4. Push to the branch (`git push origin feature/fooBar`)
5. Create a new Pull Request

**Coding Style:** PSR-12

**Testing:**

```bash
composer install
vendor/bin/phpunit
```

## License

MIT License. See [LICENSE](LICENSE) for details.

## Changelog

See [Releases](https://packagist.org/packages/tourze/psr15-static-file-request-handler#releases) for version history and upgrade notes.
