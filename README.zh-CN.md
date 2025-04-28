# PSR-15 静态文件请求处理器

[English](README.md) | [中文](README.zh-CN.md)

[![Latest Version](https://img.shields.io/packagist/v/tourze/psr15-static-file-request-handler.svg?style=flat-square)](https://packagist.org/packages/tourze/psr15-static-file-request-handler)
[![Build Status](https://img.shields.io/travis/tourze/psr15-static-file-request-handler/master.svg?style=flat-square)](https://travis-ci.org/tourze/psr15-static-file-request-handler)
[![Quality Score](https://img.shields.io/scrutinizer/g/tourze/psr15-static-file-request-handler.svg?style=flat-square)](https://scrutinizer-ci.com/g/tourze/psr15-static-file-request-handler)
[![Total Downloads](https://img.shields.io/packagist/dt/tourze/psr15-static-file-request-handler.svg?style=flat-square)](https://packagist.org/packages/tourze/psr15-static-file-request-handler)

一个兼容 PSR-15 规范的 PHP 静态文件请求处理器，高效处理静态资源请求，支持缓存头、范围请求、目录索引和 MIME 类型自动检测。

## 功能特性

- 符合 PSR-15 的静态文件请求处理器
- 支持目录索引（index.html、index.htm）
- 基于 league/mime-type-detection 自动检测 MIME 类型
- 支持 HTTP 缓存（ETag、Last-Modified、304 Not Modified）
- 支持范围请求（分片下载，206）
- 自动阻止 PHP 文件访问，提升安全性
- 可无缝集成到任何 PSR-15 中间件栈

## 安装说明

**环境要求：**

- PHP >= 8.1
- Composer

**Composer 安装命令：**

```bash
composer require tourze/psr15-static-file-request-handler
```

## 快速开始

```php
use Tourze\PSR15StaticFileRequestHandler\StaticFileRequestHandler;
use Nyholm\Psr7\ServerRequest;

$publicPath = __DIR__ . '/public';
$handler = new StaticFileRequestHandler($publicPath);
$request = new ServerRequest('GET', '/test.txt');
$response = $handler->handle($request);

echo $response->getBody();
```

### 范围请求示例

```php
$request = $request->withHeader('Range', 'bytes=0-4');
$response = $handler->handle($request);
// 返回指定范围内容（206 Partial Content）
```

## 详细文档

- **公共 API：** `StaticFileRequestHandler::__construct(string $publicPath, ?Filesystem $filesystem = null)`
- **handle(ServerRequestInterface $request): ResponseInterface**
- 自动检测 MIME 类型并处理缓存相关头部
- 不存在的文件返回 404，范围无效返回 416

### 配置说明

- `publicPath`：静态文件根目录
- 可选传入 Symfony Filesystem 实例

### 高级特性

- 范围请求支持（适用于大文件、视频等）
- 目录索引自动解析（index.html/index.htm）
- ETag 和 Last-Modified 缓存校验

## 贡献指南

1. Fork 本仓库
2. 新建特性分支（`git checkout -b feature/fooBar`）
3. 提交更改（`git commit -am 'Add some fooBar'`）
4. 推送分支（`git push origin feature/fooBar`）
5. 创建 Pull Request

**代码风格：** 遵循 PSR-12

**测试方法：**

```bash
composer install
vendor/bin/phpunit
```

## 版权和许可

MIT 协议，详见 [LICENSE](LICENSE)。

## 更新日志

版本历史与升级说明见 [Releases](https://packagist.org/packages/tourze/psr15-static-file-request-handler#releases)。
