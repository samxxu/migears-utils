# migears/utils

![Version](https://img.shields.io/badge/version-2.0.0-blue)

Minimalist utility collection for PHP. Three classes covering URLs and pagination — no dependencies, no magic.

Each class is small enough to read in a few minutes. Use what you need, ignore the rest.

> Previously `migears/common`. Renamed to `migears/utils` in v2.0 — a release that also dropped `Str` and `Arr`, which duplicated helpers belonging to the upstream libraries they were modeled on. Reach for `illuminate/support`, `symfony/string`, or your own helper set instead.

> **Background**: miGears is the open-source successor of **TinyGears**, a
> self-developed PHP framework. It was renamed and open-sourced recently because
> the name *TinyGears* is already taken in the open-source community.

## Features

- **`URL`** — Immutable URL builder for absolute URLs: explicit schemes, userinfo, and query parameter manipulation
- **`Paginator`** — Offset-based pagination DTO with `toArray()` for JSON
- **`FlowPaginator`** — Cursor-based (infinite scroll) pagination DTO

## Installation

```bash
composer require migears/utils
```

Requires: PHP 8.1+. No extensions.

## Quick Start

### URL — Immutable URL Builder

`URL` represents absolute URLs only. Both the scheme and the host are required, and the scheme is never guessed.

```php
use MiGears\Utils\URL;

// A string carrying its own scheme parses as-is
$url = URL::parse('https://example.com/path?page=1');

$url->scheme();             // 'https'
$url->host();               // 'example.com'
$url->path();               // '/path'
$url->query();              // ['page' => '1']
$url->queryParam('page');   // '1'
$url->isHttps();            // true

// http() and https() force their scheme
URL::https('example.com/path');    // https://example.com/path
URL::http('https://example.com');  // http://example.com/

// Without a scheme and without a factory, the string is rejected
URL::parse('example.com/path');    // throws InvalidArgumentException
```

`http()` and `https()` override whatever scheme the string carries, so `URL::https('http://example.com')` produces `https://example.com/`.

Credentials are kept rather than dropped, encoded when written out and decoded when read back:

```php
$secure = URL::parse('https://user:secret@example.com/');

$secure->user();   // 'user'
$secure->pass();   // 'secret'
echo $secure;      // 'https://user:secret@example.com/'

$secure->withUser(null)->toString();   // 'https://example.com/'
```

A password without a user is rejected — `new URL(scheme: 'https', host: 'example.com', pass: 'secret')` throws. An empty user or password counts as absent, so it never reaches the output as `https://@example.com/`.

The scheme, host, port, path, and fragment are normalized as they come in:

```php
new URL(scheme: '1a', host: 'example.com');            // throws — not a valid scheme
new URL(scheme: 'https', host: 'exa mple.com');        // throws — a host cannot carry a space
new URL(scheme: 'https', host: 'a.com', port: 70000);  // throws — not a destination port
(new URL(scheme: 'https', host: 'a.com'))->withPath('/a b/c')->toString();
// 'https://a.com/a%20b/c'
```

Characters that cannot appear literally in a path or fragment are percent-encoded, while existing escape sequences are left untouched — an already-encoded URL survives a parse-and-rebuild cycle unchanged.

The host accepts an RFC 3986 reg-name (a hostname, an IPv4 address, or the permissive unreserved/sub-delims set) or an IP literal in brackets, such as `[::1]`. A port must be 1–65535: `parse_url()` already rejects anything larger, and `0` is not a destination. Both rules exist to keep a host from smuggling a space, a CRLF, or a path separator into a request line.

A `null` query value means the parameter is absent, everywhere: the constructor drops it and `withQuery()` deletes the key with it, so `query()` never reports a key that the built string omits.

All `with*` methods return new instances:

```php
$url = URL::https('example.com')
    ->withPath('/users')
    ->withQuery(['page' => 1, 'limit' => 20])
    ->withFragment('top')
    ->appendPath('search');

echo $url;  // 'https://example.com/users/search?page=1&limit=20#top'
```

Query keys keep their original characters. Unlike `parse_str()`, dots and spaces are not rewritten to underscores:

```php
URL::parse('https://example.com/?a.b=1&c%20d=2')->query();
// ['a.b' => '1', 'c d' => '2']
```

Bracket notation is not expanded, so every key is flat and every value is a scalar.

### Paginator — Offset-Based Pagination

```php
use MiGears\Utils\Paginator;

$paginator = new Paginator(
    pageSize: 10,
    currentPage: 2,
    total: 53,
    items: [...],
);

$paginator->totalPages();   // 6
$paginator->hasPrev();      // true
$paginator->hasNext();      // true
$paginator->prevPage();     // 1
$paginator->nextPage();     // 3
$paginator->offset();       // 10
$paginator->toArray();      // for JSON response
```

`pageSize` and `currentPage` are raised to 1 when they arrive lower, so a page size of 0 cannot divide by zero and a page number below 1 cannot produce a negative offset. `firstPage()` and `lastPage()` both return 0 when there are no pages.

### FlowPaginator — Cursor-Based Pagination

```php
use MiGears\Utils\FlowPaginator;

$paginator = FlowPaginator::first(20, 'id');

// After fetching data...
$next = $paginator->nextPage($items, $hasMore);

$next->nextCursor();        // cursor for the next page
$next->hasMore;             // whether there are more pages
$next->toArray();           // for JSON response
```

An empty page ends the sequence: passing `hasMore: true` together with no items stores `hasMore` as false. Otherwise the paginator would claim more pages while holding a null cursor, and the next fetch would restart from the first page.

## API Reference

### URL

| Method | Description |
|--------|-------------|
| `new URL($scheme, $host, $port, $path, $query, $fragment, $user, $pass)` | Constructor — scheme and host required |
| `URL::parse($url)` | Parse a string that carries its own scheme |
| `URL::http($url)` | Parse and force the http scheme |
| `URL::https($url)` | Parse and force the https scheme |
| `scheme()` / `host()` / `port()` / `path()` | Get parts |
| `query()` / `queryParam($key, $default)` | Query params |
| `fragment()` | Get fragment |
| `user()` / `pass()` | Get userinfo, percent-decoded |
| `withScheme($s)` / `withHost($h)` / `withPort($p)` | Modify (immutable) |
| `withPath($path)` / `appendPath($segment)` | Modify path |
| `withQuery($params)` / `withQueryParam($k, $v)` | Modify query |
| `withoutQueryParam($key)` | Remove query param |
| `withFragment($f)` | Modify fragment |
| `withUser($user, $pass)` | Set userinfo — null removes it |
| `isHttps()` | Check HTTPS |
| `toString()` / `__toString()` | Build URL string |

### Paginator

| Method | Description |
|--------|-------------|
| `totalPages()` | Total page count |
| `hasPrev()` / `hasNext()` | Page existence |
| `prevPage()` / `nextPage()` | Page numbers |
| `firstPage()` / `lastPage()` | First/last page |
| `offset()` | 0-based offset for DB queries |
| `count()` | Items on current page |
| `isEmpty()` | Whether empty |
| `withItems($items)` | New paginator with items |
| `toArray()` | Convert to array for JSON |

### FlowPaginator

| Method | Description |
|--------|-------------|
| `FlowPaginator::first($size, $column)` | Create first-page paginator |
| `count()` / `isEmpty()` | Item count |
| `isFirstPage()` | Whether first page |
| `nextCursor()` | Cursor for next page |
| `withItems($items, $hasMore)` | New paginator with items |
| `nextPage($items, $hasMore)` | Advance to next cursor |
| `toArray()` | Convert to array for JSON |

## Upgrading to 2.0

The package was `migears/common` before this release. `Str` and `Arr` are gone, `URL` no longer guesses protocols, and `Date` moved to `migears/i18n` as `LocalizedDate`. See [CHANGELOG.md](CHANGELOG.md) for the full list and migration steps.

## Design Philosophy

miGears Utils follows the miGears philosophy: **minimal, readable, and useful**.

- **Three focused classes** — each does one thing well
- **No inheritance chains** — everything is final
- **Static methods where it makes sense** — no unnecessary instantiation
- **Immutable where it matters** — URL never mutates
- **Small enough to read** — each class reads in a single sitting

## License

MIT

---

# migears/utils

![Version](https://img.shields.io/badge/version-2.0.0-blue)

极简 PHP 工具集。三个类，涵盖 URL 和分页——无依赖，零魔法。

每个类都小到可以几分钟内读完。用你需要的，忽略其余的。

> 前身为 `migears/common`，v2.0 更名为 `migears/utils`。同一版本移除了 `Str` 与 `Arr`——它们复刻了本该由上游库提供的工具方法。需要时请直接使用 `illuminate/support`、`symfony/string`，或你自己的工具集。

## 特性

- **`URL`** — 面向绝对 URL 的不可变构建器：协议显式化、支持凭据与查询参数操作
- **`Paginator`** — 基于偏移量的分页 DTO，带 `toArray()` 支持 JSON
- **`FlowPaginator`** — 基于游标（无限滚动）的分页 DTO

## 安装

```bash
composer require migears/utils
```

要求：PHP 8.1+，无扩展依赖。

## 快速开始

### URL — 不可变 URL 构建器

`URL` 只表示绝对 URL。scheme 与 host 都是必填，且 scheme 从不被猜测。

```php
use MiGears\Utils\URL;

// 自带 scheme 的字符串按原样解析
$url = URL::parse('https://example.com/path?page=1');

$url->scheme();             // 'https'
$url->host();               // 'example.com'
$url->path();               // '/path'
$url->query();              // ['page' => '1']
$url->queryParam('page');   // '1'
$url->isHttps();            // true

// http() 与 https() 强制指定协议
URL::https('example.com/path');    // https://example.com/path
URL::http('https://example.com');  // http://example.com/

// 既没有 scheme 也没走工厂方法，直接拒绝
URL::parse('example.com/path');    // 抛出 InvalidArgumentException
```

`http()` 与 `https()` 会覆盖字符串里原有的 scheme，因此 `URL::https('http://example.com')` 产出 `https://example.com/`。

凭据不再被丢弃，输出时编码、读入时解码：

```php
$secure = URL::parse('https://user:secret@example.com/');

$secure->user();   // 'user'
$secure->pass();   // 'secret'
echo $secure;      // 'https://user:secret@example.com/'

$secure->withUser(null)->toString();   // 'https://example.com/'
```

只有密码没有用户名会被拒绝——`new URL(scheme: 'https', host: 'example.com', pass: 'secret')` 抛异常。空字符串的用户名或密码等同于未设置，不会输出成 `https://@example.com/`。

scheme、host、port、path、fragment 在入口处即做规范化：

```php
new URL(scheme: '1a', host: 'example.com');            // 抛出 — 不是合法 scheme
new URL(scheme: 'https', host: 'exa mple.com');        // 抛出 — host 不能含空格
new URL(scheme: 'https', host: 'a.com', port: 70000);  // 抛出 — 不是可用的目标端口
(new URL(scheme: 'https', host: 'a.com'))->withPath('/a b/c')->toString();
// 'https://a.com/a%20b/c'
```

路径与片段中不能直接出现的字符会被百分号编码，已有的转义序列则原样保留——一个已编码的 URL 经过解析再重建后不会改变。

host 接受 RFC 3986 的 reg-name（主机名、IPv4，或更宽松的 unreserved/sub-delims 集合），或方括号形式的 IP 字面量如 `[::1]`。端口必须落在 1–65535：超出范围的 `parse_url()` 本身就会拒绝，而 `0` 不是可用的目标端口。这两条规则的目的，是让 host 无法把空格、CRLF 或路径分隔符偷渡进请求行。

查询参数中的 `null` 在任何位置都表示「该参数不存在」：构造器会丢弃它，`withQuery()` 借同一条规则删除该键，因此 `query()` 绝不会报出一个 `toString()` 不会输出的键。

所有 `with*` 方法返回新实例：

```php
$url = URL::https('example.com')
    ->withPath('/users')
    ->withQuery(['page' => 1, 'limit' => 20])
    ->withFragment('top')
    ->appendPath('search');

echo $url;  // 'https://example.com/users/search?page=1&limit=20#top'
```

查询参数的键保持原样。与 `parse_str()` 不同，点和空格不会被改写成下划线：

```php
URL::parse('https://example.com/?a.b=1&c%20d=2')->query();
// ['a.b' => '1', 'c d' => '2']
```

方括号写法不做展开，因此每个键都是扁平的，每个值都是标量。

### Paginator — 偏移量分页

```php
use MiGears\Utils\Paginator;

$paginator = new Paginator(
    pageSize: 10,
    currentPage: 2,
    total: 53,
    items: [...],
);

$paginator->totalPages();   // 6
$paginator->hasPrev();      // true
$paginator->hasNext();      // true
$paginator->prevPage();     // 1
$paginator->nextPage();     // 3
$paginator->offset();       // 10
$paginator->toArray();      // 用于 JSON 响应
```

`pageSize` 与 `currentPage` 传入小于 1 的值时会被抬到 1，因此 pageSize 为 0 不会除零崩溃，页码小于 1 也不会产生负的 offset。无页可翻时 `firstPage()` 与 `lastPage()` 都返回 0。

### FlowPaginator — 游标分页

```php
use MiGears\Utils\FlowPaginator;

$paginator = FlowPaginator::first(20, 'id');

// 获取数据后...
$next = $paginator->nextPage($items, $hasMore);

$next->nextCursor();        // 下一页的游标
$next->hasMore;             // 是否还有更多页
$next->toArray();           // 用于 JSON 响应
```

空页即序列结束：items 为空时即使传入 `hasMore: true` 也会存为 false。否则分页器会一边声称还有更多页、一边握着 null 游标，下一次取数会回到首页。

## API 参考

### URL

| 方法 | 说明 |
|------|------|
| `new URL($scheme, $host, $port, $path, $query, $fragment, $user, $pass)` | 构造函数 — scheme 与 host 必填 |
| `URL::parse($url)` | 解析自带 scheme 的字符串 |
| `URL::http($url)` | 解析并强制 http 协议 |
| `URL::https($url)` | 解析并强制 https 协议 |
| `scheme()` / `host()` / `port()` / `path()` | 获取各部分 |
| `query()` / `queryParam($key, $default)` | 查询参数 |
| `fragment()` | 获取片段 |
| `user()` / `pass()` | 获取凭据，已做百分号解码 |
| `withScheme($s)` / `withHost($h)` / `withPort($p)` | 修改（不可变） |
| `withPath($path)` / `appendPath($segment)` | 修改路径 |
| `withQuery($params)` / `withQueryParam($k, $v)` | 修改查询参数 |
| `withoutQueryParam($key)` | 移除查询参数 |
| `withFragment($f)` | 修改片段 |
| `withUser($user, $pass)` | 设置凭据 — 传 null 则移除 |
| `isHttps()` | 检查 HTTPS |
| `toString()` / `__toString()` | 构建 URL 字符串 |

### Paginator

| 方法 | 说明 |
|------|------|
| `totalPages()` | 总页数 |
| `hasPrev()` / `hasNext()` | 是否有上/下一页 |
| `prevPage()` / `nextPage()` | 上/下一页页码 |
| `firstPage()` / `lastPage()` | 第一/最后一页 |
| `offset()` | 0 基偏移量，用于数据库查询 |
| `count()` | 当前页条目数 |
| `isEmpty()` | 是否为空 |
| `withItems($items)` | 带条目的新分页器 |
| `toArray()` | 转为数组用于 JSON |

### FlowPaginator

| 方法 | 说明 |
|------|------|
| `FlowPaginator::first($size, $column)` | 创建首页分页器 |
| `count()` / `isEmpty()` | 条目数 |
| `isFirstPage()` | 是否首页 |
| `nextCursor()` | 下一页游标 |
| `withItems($items, $hasMore)` | 带条目的新分页器 |
| `nextPage($items, $hasMore)` | 前进到下一个游标 |
| `toArray()` | 转为数组用于 JSON |

## 升级到 2.0

本版本之前包名为 `migears/common`。`Str` 与 `Arr` 已移除，`URL` 不再猜测协议，`Date` 已迁至 `migears/i18n` 并更名为 `LocalizedDate`。完整清单与迁移步骤见 [CHANGELOG.md](CHANGELOG.md)。

## 设计哲学

miGears Utils 遵循 miGears 设计哲学：**极简、可读、实用**。

- **三个专注的类** — 每个类做好一件事
- **没有继承链** — 所有类都是 final
- **该静态就静态** — 不需要的实例化就省了
- **该不可变就不可变** — URL 永不修改自身
- **小到可以读完** — 每个类都能一次读完

## 许可证

MIT
