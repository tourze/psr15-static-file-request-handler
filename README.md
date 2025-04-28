# PSR-15 Static File Request Handler

[English](README.md) | [中文](README.zh-CN.md)

[![Latest Version](https://img.shields.io/packagist/v/tourze/psr15-static-file-request-handler.svg?style=flat-square)](https://packagist.org/packages/tourze/psr15-static-file-request-handler)
[![Build Status](https://img.shields.io/travis/tourze/psr15-static-file-request-handler/master.svg?style=flat-square)](https://travis-ci.org/tourze/psr15-static-file-request-handler)
[![Quality Score](https://img.shields.io/scrutinizer/g/tourze/psr15-static-file-request-handler.svg?style=flat-square)](https://scrutinizer-ci.com/g/tourze/psr15-static-file-request-handler)
[![Total Downloads](https://img.shields.io/packagist/dt/tourze/psr15-static-file-request-handler.svg?style=flat-square)](https://packagist.org/packages/tourze/psr15-static-file-request-handler)

A PSR-15 compatible request handler for serving static files efficiently in PHP applications. Supports cache headers, range requests, directory index, and MIME type detection.

## Features

- PSR-15 compatible static file request handler
- Supports directory index (index.html, index.htm)
- MIME type auto-detection using league/mime-type-detection
- HTTP cache support (ETag, Last-Modified, 304 Not Modified)
- Range requests (partial content, 206)
- Prevents serving PHP files for security
- Easy integration with any PSR-15 middleware stack

## Installation

**Requirements:**

- PHP >= 8.1
- Composer

**Install via Composer:**

```bash
composer require tourze/psr15-static-file-request-handler
```

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

- **Public API:** `StaticFileRequestHandler::__construct(string $publicPath, ?Filesystem $filesystem = null)`
- **handle(ServerRequestInterface $request): ResponseInterface`**
- Automatically detects MIME type and handles cache headers
- Returns 404 for non-existent files, 416 for invalid ranges

### Configuration

- `publicPath`: The root directory for static files
- Optionally pass a Symfony Filesystem instance

### Advanced Features

- Range request support (for large files, video, etc.)
- Directory index resolution (index.html/index.htm)
- ETag and Last-Modified cache validation

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
