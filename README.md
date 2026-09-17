# migears/utils

![Version](https://img.shields.io/badge/version-2.0.0-blue)

Minimalist utility collection for PHP. Six classes covering strings, arrays, dates, URLs, and pagination — no dependencies, no magic.

Each class is small enough to read in a few minutes. Use what you need, ignore the rest.

> Previously `migears/common` — renamed to `migears/utils` in v2.0.

## Features

- **`Str`** — Multibyte-safe string utilities (slug, truncate, camel/snake/studly, random, ascii, etc.)
- **`Arr`** — Array utilities with dot notation, pluck, where, first/last, flatten, etc.
- **`Date`** — Date/time with relative time, human-friendly formatting, timezone handling
- **`URL`** — Immutable URL builder with query parameter manipulation
- **`Paginator`** — Offset-based pagination DTO with `toArray()` for JSON
- **`FlowPaginator`** — Cursor-based (infinite scroll) pagination DTO

## Installation

```bash
composer require migears/utils
```

Requires: PHP 8.1+, ext-mbstring.

## Quick Start

### Str — String Utilities

```php
use MiGears\Utils\Str;

Str::slug('Hello World!');                 // 'hello-world'
Str::camel('user_profile');                // 'userProfile'
Str::studly('user_profile');               // 'UserProfile'
Str::snake('userProfile');                 // 'user_profile'
Str::truncate('Long text here', 10);       // 'Long te...'
Str::random(16);                           // cryptographically secure random string
Str::ascii('café');                        // 'cafe'
Str::contains('Hello World', 'World');     // true
Str::startsWith('Hello', 'He');            // true
Str::endsWith('Hello', 'llo');             // true
Str::reverse('abc');                       // 'cba'
Str::length('你好');                        // 2 (multibyte-safe)
```

### Arr — Array Utilities

```php
use MiGears\Utils\Arr;

// Dot notation access
Arr::get($config, 'database.host', 'localhost');
Arr::set($config, 'database.port', 3306);
Arr::has($config, 'database.password');

// Functional operations
Arr::pluck($users, 'name');                 // ['Alice', 'Bob']
Arr::pluck($users, 'email', 'id');          // [1 => 'alice@...', 2 => 'bob@...']
Arr::first([1, 2, 3], fn($v) => $v > 1);   // 2
Arr::last([1, 2, 3], fn($v) => $v < 3);    // 2
Arr::where([1, 2, 3, 4], fn($v) => $v > 2); // [3, 4]
Arr::only($data, ['name', 'email']);        // whitelist keys
Arr::except($data, ['password']);           // blacklist keys
Arr::flatten([1, [2, [3]]]);                // [1, 2, 3]
Arr::every([2, 4, 6], fn($v) => $v % 2 === 0); // true
Arr::some([1, 2, 3], fn($v) => $v > 2);     // true
Arr::unique([1, 2, 2, 3]);                  // [1, 2, 3]
```

### Date — Date/Time Utilities

```php
use MiGears\Utils\Date;

$date = new Date('2024-01-15 10:30:00', 'Asia/Shanghai');

$date->toDateString();      // '2024-01-15'
$date->toTimeString();      // '2024-01-15 10:30'
$date->format('M j, Y');    // 'Jan 15, 2024'
$date->relativeTime();      // '2 hours ago' / 'in 3 days'
$date->humanize();          // 'Today 10:30' / 'Yesterday 09:15' / 'Monday 14:30'
$date->isToday();           // bool
$date->isYesterday();       // bool
$date->isTomorrow();        // bool
$date->dayOfWeek();         // 0 (Sun) - 6 (Sat)
$date->withTimezone('UTC'); // new Date instance
```

### URL — Immutable URL Builder

```php
use MiGears\Utils\URL;

$url = URL::parse('https://example.com/path?page=1');

$url->scheme();             // 'https'
$url->host();               // 'example.com'
$url->path();               // '/path'
$url->query();              // ['page' => '1']
$url->queryParam('page');   // '1'
$url->isHttps();            // true

// Immutable — all with* methods return new instances
$url = URL::parse('https://example.com')
    ->withPath('/users')
    ->withQuery(['page' => 1, 'limit' => 20])
    ->withFragment('top')
    ->appendPath('search');

echo $url;  // 'https://example.com/users/search?page=1&limit=20#top'
```

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

## API Reference

### Str

| Method | Description |
|--------|-------------|
| `startsWith($haystack, $needle)` | Check if string starts with substring |
| `endsWith($haystack, $needle)` | Check if string ends with substring |
| `contains($haystack, $needle)` | Check if string contains substring |
| `length($string, $encoding = null)` | Multibyte-safe length |
| `slug($string, $separator = '-')` | URL-friendly slug |
| `truncate($string, $length = 100, $ellipsis = '...')` | Truncate with ellipsis |
| `words($string, $words = 100, $end = '...')` | Limit word count |
| `camel($string)` | Convert to camelCase |
| `studly($string)` | Convert to StudlyCase |
| `snake($string, $delimiter = '_')` | Convert to snake_case |
| `kebab($string)` | Convert to kebab-case |
| `ucfirst($string)` | Multibyte-safe ucfirst |
| `lcfirst($string)` | Multibyte-safe lcfirst |
| `ascii($string)` | Transliterate to ASCII |
| `random($length = 16)` | Cryptographically secure random string |
| `reverse($string)` | Multibyte-safe reverse |

### Arr

| Method | Description |
|--------|-------------|
| `get($array, $key, $default = null)` | Get item by dot notation |
| `set(&$array, $key, $value)` | Set item by dot notation |
| `has($array, $key)` | Check existence by dot notation |
| `pluck($array, $value, $key = null)` | Extract column values |
| `first($array, $callback = null, $default = null)` | First matching element |
| `last($array, $callback = null, $default = null)` | Last matching element |
| `where($array, $callback, $preserveKeys = false)` | Filter by callback |
| `only($array, $keys)` | Whitelist keys |
| `except($array, $keys)` | Blacklist keys |
| `flatten($array, $depth = 0)` | Flatten nested array |
| `collapse($array)` | Collapse one level |
| `every($array, $callback)` | All elements pass? |
| `some($array, $callback)` | Any element passes? |
| `unique($array)` | Unique values (preserves order) |

### Date

| Method | Description |
|--------|-------------|
| `new Date($input = null, $timezone = null)` | Create from timestamp/string/DateTime |
| `Date::fromTimestamp($ts, $tz = null)` | Create from Unix timestamp |
| `Date::fromString($str, $tz = null)` | Create from datetime string |
| `toDateTime()` | Get DateTimeImmutable |
| `timestamp()` | Get Unix timestamp |
| `toDateString()` | Format: Y-m-d |
| `toTimeString()` | Format: Y-m-d H:i |
| `format($pattern)` | Custom format |
| `dayOfWeek()` | 0 (Sun) - 6 (Sat) |
| `isToday()` / `isYesterday()` / `isTomorrow()` | Comparison |
| `relativeTime()` | Human-readable relative time |
| `humanize()` | Friendly datetime string |
| `withTimezone($tz)` | Convert timezone (immutable) |
| `timezone()` | Get current timezone |

### URL

| Method | Description |
|--------|-------------|
| `new URL($scheme, $host, $port, $path, $query, $fragment)` | Constructor |
| `URL::parse($url)` | Parse from string |
| `scheme()` / `host()` / `port()` / `path()` | Get parts |
| `query()` / `queryParam($key, $default)` | Query params |
| `fragment()` | Get fragment |
| `withScheme($s)` / `withHost($h)` / `withPort($p)` | Modify (immutable) |
| `withPath($path)` / `appendPath($segment)` | Modify path |
| `withQuery($params)` / `withQueryParam($k, $v)` | Modify query |
| `withoutQueryParam($key)` | Remove query param |
| `withFragment($f)` | Modify fragment |
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

## Design Philosophy

miGears Utils follows the miGears philosophy: **minimal, readable, and useful**.

- **Six focused classes** — each does one thing well
- **No inheritance chains** — everything is final
- **Static methods where it makes sense** — no unnecessary instantiation
- **Immutable where it matters** — Date and URL never mutate
- **Small enough to read** — no class exceeds 200 lines

## License

MIT

---

# migears/utils

![Version](https://img.shields.io/badge/version-2.0.0-blue)

极简 PHP 工具集。六个类，涵盖字符串、数组、日期、URL 和分页——无依赖，零魔法。

每个类都小到可以几分钟内读完。用你需要的，忽略其余的。

> 前身为 `migears/common` — v2.0 起更名为 `migears/utils`。

## 特性

- **`Str`** — 多字节安全的字符串工具（slug、truncate、camel/snake/studly、random、ascii 等）
- **`Arr`** — 数组工具，支持点号访问、pluck、where、first/last、flatten 等
- **`Date`** — 日期时间处理，支持相对时间、人性化格式、时区转换
- **`URL`** — 不可变 URL 构建器，支持查询参数操作
- **`Paginator`** — 基于偏移量的分页 DTO，带 `toArray()` 支持 JSON
- **`FlowPaginator`** — 基于游标（无限滚动）的分页 DTO

## 安装

```bash
composer require migears/utils
```

要求：PHP 8.1+，ext-mbstring。

## 快速开始

### Str — 字符串工具

```php
use MiGears\Utils\Str;

Str::slug('Hello World!');                 // 'hello-world'
Str::camel('user_profile');                // 'userProfile'
Str::studly('user_profile');               // 'UserProfile'
Str::snake('userProfile');                 // 'user_profile'
Str::truncate('长文本在这里', 10);            // '长文本在...'
Str::random(16);                           // 加密安全的随机字符串
Str::ascii('café');                        // 'cafe'
Str::contains('Hello World', 'World');     // true
Str::startsWith('Hello', 'He');            // true
Str::endsWith('Hello', 'llo');             // true
Str::reverse('abc');                       // 'cba'
Str::length('你好');                        // 2 (多字节安全)
```

### Arr — 数组工具

```php
use MiGears\Utils\Arr;

// 点号访问
Arr::get($config, 'database.host', 'localhost');
Arr::set($config, 'database.port', 3306);
Arr::has($config, 'database.password');

// 函数式操作
Arr::pluck($users, 'name');                 // ['Alice', 'Bob']
Arr::pluck($users, 'email', 'id');          // [1 => 'alice@...', 2 => 'bob@...']
Arr::first([1, 2, 3], fn($v) => $v > 1);   // 2
Arr::last([1, 2, 3], fn($v) => $v < 3);    // 2
Arr::where([1, 2, 3, 4], fn($v) => $v > 2); // [3, 4]
Arr::only($data, ['name', 'email']);        // 白名单键
Arr::except($data, ['password']);           // 黑名单键
Arr::flatten([1, [2, [3]]]);                // [1, 2, 3]
Arr::every([2, 4, 6], fn($v) => $v % 2 === 0); // true
Arr::some([1, 2, 3], fn($v) => $v > 2);     // true
Arr::unique([1, 2, 2, 3]);                  // [1, 2, 3]
```

### Date — 日期时间工具

```php
use MiGears\Utils\Date;

$date = new Date('2024-01-15 10:30:00', 'Asia/Shanghai');

$date->toDateString();      // '2024-01-15'
$date->toTimeString();      // '2024-01-15 10:30'
$date->format('M j, Y');    // 'Jan 15, 2024'
$date->relativeTime();      // '2小时前' / '3天后' (英文输出)
$date->humanize();          // 'Today 10:30' / 'Yesterday 09:15'
$date->isToday();           // bool
$date->isYesterday();       // bool
$date->isTomorrow();        // bool
$date->dayOfWeek();         // 0 (周日) - 6 (周六)
$date->withTimezone('UTC'); // 新的 Date 实例
```

### URL — 不可变 URL 构建器

```php
use MiGears\Utils\URL;

$url = URL::parse('https://example.com/path?page=1');

$url->scheme();             // 'https'
$url->host();               // 'example.com'
$url->path();               // '/path'
$url->query();              // ['page' => '1']
$url->queryParam('page');   // '1'
$url->isHttps();            // true

// 不可变 — 所有 with* 方法返回新实例
$url = URL::parse('https://example.com')
    ->withPath('/users')
    ->withQuery(['page' => 1, 'limit' => 20])
    ->withFragment('top')
    ->appendPath('search');

echo $url;  // 'https://example.com/users/search?page=1&limit=20#top'
```

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

## API 参考

### Str

| 方法 | 说明 |
|------|------|
| `startsWith($haystack, $needle)` | 检查字符串是否以子串开头 |
| `endsWith($haystack, $needle)` | 检查字符串是否以子串结尾 |
| `contains($haystack, $needle)` | 检查字符串是否包含子串 |
| `length($string, $encoding = null)` | 多字节安全长度 |
| `slug($string, $separator = '-')` | URL 友好的 slug |
| `truncate($string, $length = 100, $ellipsis = '...')` | 截断加省略号 |
| `words($string, $words = 100, $end = '...')` | 限制词数 |
| `camel($string)` | 转为 camelCase |
| `studly($string)` | 转为 StudlyCase |
| `snake($string, $delimiter = '_')` | 转为 snake_case |
| `kebab($string)` | 转为 kebab-case |
| `ucfirst($string)` | 多字节安全首字母大写 |
| `lcfirst($string)` | 多字节安全首字母小写 |
| `ascii($string)` | 转写为 ASCII |
| `random($length = 16)` | 加密安全随机字符串 |
| `reverse($string)` | 多字节安全反转 |

### Arr

| 方法 | 说明 |
|------|------|
| `get($array, $key, $default = null)` | 点号获取元素 |
| `set(&$array, $key, $value)` | 点号设置元素 |
| `has($array, $key)` | 点号检查存在性 |
| `pluck($array, $value, $key = null)` | 提取列值 |
| `first($array, $callback = null, $default = null)` | 第一个匹配元素 |
| `last($array, $callback = null, $default = null)` | 最后一个匹配元素 |
| `where($array, $callback, $preserveKeys = false)` | 按回调过滤 |
| `only($array, $keys)` | 白名单键 |
| `except($array, $keys)` | 黑名单键 |
| `flatten($array, $depth = 0)` | 展平嵌套数组 |
| `collapse($array)` | 展平一层 |
| `every($array, $callback)` | 全部通过？ |
| `some($array, $callback)` | 任一通过？ |
| `unique($array)` | 去重（保持顺序） |

### Date

| 方法 | 说明 |
|------|------|
| `new Date($input = null, $timezone = null)` | 从时间戳/字符串/DateTime 创建 |
| `Date::fromTimestamp($ts, $tz = null)` | 从 Unix 时间戳创建 |
| `Date::fromString($str, $tz = null)` | 从日期时间字符串创建 |
| `toDateTime()` | 获取 DateTimeImmutable |
| `timestamp()` | 获取 Unix 时间戳 |
| `toDateString()` | 格式：Y-m-d |
| `toTimeString()` | 格式：Y-m-d H:i |
| `format($pattern)` | 自定义格式 |
| `dayOfWeek()` | 0 (周日) - 6 (周六) |
| `isToday()` / `isYesterday()` / `isTomorrow()` | 比较 |
| `relativeTime()` | 人性化相对时间 |
| `humanize()` | 友好日期时间字符串 |
| `withTimezone($tz)` | 转换时区（不可变） |
| `timezone()` | 获取当前时区 |

### URL

| 方法 | 说明 |
|------|------|
| `new URL($scheme, $host, $port, $path, $query, $fragment)` | 构造函数 |
| `URL::parse($url)` | 从字符串解析 |
| `scheme()` / `host()` / `port()` / `path()` | 获取各部分 |
| `query()` / `queryParam($key, $default)` | 查询参数 |
| `fragment()` | 获取片段 |
| `withScheme($s)` / `withHost($h)` / `withPort($p)` | 修改（不可变） |
| `withPath($path)` / `appendPath($segment)` | 修改路径 |
| `withQuery($params)` / `withQueryParam($k, $v)` | 修改查询参数 |
| `withoutQueryParam($key)` | 移除查询参数 |
| `withFragment($f)` | 修改片段 |
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

## 设计哲学

miGears Utils 遵循 miGears 设计哲学：**极简、可读、实用**。

- **六个专注的类** — 每个类做好一件事
- **没有继承链** — 所有类都是 final
- **该静态就静态** — 不需要的实例化就省了
- **该不可变就不可变** — Date 和 URL 永不修改自身
- **小到可以读完** — 没有类超过 200 行

## 许可证

MIT
