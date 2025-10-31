# PSR-15 静态文件请求处理器

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

一个兼容 PSR-15 规范的 PHP 静态文件请求处理器，高效处理静态资源请求。
支持缓存头、范围请求、目录索引和 MIME 类型自动检测。

## 功能特性

- 符合 PSR-15 的静态文件请求处理器
- 支持目录索引（index.html、index.htm）
- 基于 league/mime-type-detection 自动检测 MIME 类型
- 支持 HTTP 缓存（ETag、Last-Modified、304 Not Modified）
- 支持范围请求（分片下载，206）
- 自动阻止 PHP 文件访问，提升安全性
- 可无缝集成到任何 PSR-15 中间件栈

## 安装说明

**Composer 安装命令：**

```bash
composer require tourze/psr15-static-file-request-handler
```

## 依赖要求

**环境要求：**

- PHP >= 8.1
- Composer

**依赖包：**

- `psr/http-message` - PSR-7 HTTP 消息接口
- `psr/http-server-handler` - PSR-15 HTTP 处理器
- `league/mime-type-detection` - MIME 类型检测
- `nyholm/psr7` - PSR-7 实现
- `symfony/filesystem` - 文件系统操作

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

### 公共 API

- **构造函数：** `StaticFileRequestHandler::__construct(string $publicPath, ?Filesystem $filesystem = null)`
- **处理方法：** `handle(ServerRequestInterface $request): ResponseInterface`

### 响应状态码

- **200 OK：** 文件找到并成功提供
- **206 Partial Content：** 范围请求成功提供
- **304 Not Modified：** 文件未修改（缓存命中）
- **404 Not Found：** 文件不存在
- **416 Range Not Satisfiable：** 无效的范围请求
- **500 Internal Server Error：** 文件读取错误

### 配置说明

- `publicPath`：静态文件根目录
- 可选传入 Symfony Filesystem 实例

## 高级用法

### 自定义文件系统集成

```php
use Symfony\Component\Filesystem\Filesystem;

$filesystem = new Filesystem();
$handler = new StaticFileRequestHandler($publicPath, $filesystem);
```

### 中间件集成

```php
use Slim\App;

$app = new App();
$app->add(function ($request, $handler) {
    $staticHandler = new StaticFileRequestHandler(__DIR__ . '/public');
    return $staticHandler->handle($request);
});
```

### 缓存头

处理器自动设置缓存头：
- `ETag` 用于缓存验证
- `Last-Modified` 用于条件请求
- `Cache-Control: public, max-age=86400` 用于浏览器缓存

### 范围请求支持

支持 HTTP 范围请求，适用于：
- 大文件下载
- 视频流媒体
- 渐进式加载

### 目录索引

自动提供索引文件：
- `index.html`（优先）
- `index.htm`（备选）

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
