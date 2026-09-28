# migears-utils — Known Issues

> Summary of this module's issues. The items themselves are in [`issues/`](issues/README.md), one file
> per item: a front-matter header and a thread. This file is generated from them and can be rewritten at
> any time; edit an item, never this file.
>
> From the miGears Full-Module Code Review Report (5th round, 2026-09-28).

| | |
|---|---|
| Status | **P2 open** |
| Size | src 419 lines (net) · 138 tests · 3 src files |

Legend — **P0** functional or security · **P1** documentation that fails when copied · **P2** robustness · **P3** metadata and docs

## At a glance

| | |
|---|---|
| Unsettled | P0 0 · P1 0 · P2 1 · P3 0 · other 1 |
| Settled | 3 of 5 |
| Waiting on the owner | `P2-1` |
| Waiting on the reviewer | `G4` |
| Waiting on the coordinator | _nothing_ |
| Deferred, owing nobody | _nothing_ |

| id | level | status | title |
|---|---|---|---|
| [`P0-1`](issues/P0-1.md) | P0 | **verified** | The scheme, reg-name and IP-literal patterns use `$` without `/D` or … |
| [`P2-1`](issues/P2-1.md) | P2 | **open** | URL::parse() uses the native parse_url() which returns false for … |
| [`P3-1`](issues/P3-1.md) | P3 | **verified** | Query rebuilding is form-encoded and not byte-preserving: … |
| [`P3-2`](issues/P3-2.md) | P3 | **verified** | `query()` and `toString()` still disagree for non-string scalars: with … |
| [`G4`](issues/G4.md) | - | **rejected** | Document standard: the project standard is that every document is … |

## Unclosed

What is left to do here: every item whose `status` is not `verified` or `closed`,
highest severity first. `waiting on` is the party who acts next, read from that status.

| | |
|---|---|
| Unclosed | **2** of 5 |
| By status | `open` 1 · `rejected` 1 |
| Waiting on | owner 1 · reviewer 1 |

| level | item | status | waiting on | title |
|---|---|---|---|---|
| **P2** | [`P2-1`](issues/P2-1.md) | `open` | owner | URL::parse() uses the native parse_url() which returns false for … |
| **-** | [`G4`](issues/G4.md) | `rejected` | reviewer | Document standard: the project standard is that every document is … |

## Verdict

A focused utility library with URL parsing/validation and paginators; the P0 URL anchor issue is fixed. A parse_url truncation risk remains for URLs with unusual schemes or malformed hosts.

## Fixed since the last round

P0-1 confirmed fixed — all three $ anchors changed to \z in URL validation, with explanatory comments; P3-1/P3-2 byte-preserving and query-representation claims now scoped correctly; G4 CHANGELOG bilingual standard pending.

## Test gaps

No test for URL::build() with port 0 (valid per RFC but edge case); no test for Paginator with total 0 and page 1 (boundary); no test for FlowPaginator with empty first page but more data available.

## Verification protocol

- `./vendor/bin/phpunit` · `composer analyse` · `composer validate`
- Warning/notice/deprecation/risky flags in `phpunit.xml.dist`: all four on
- A PHP warning counts as a test failure only where those flags are on; otherwise run `./vendor/bin/phpunit --fail-on-warning` explicitly.


---

# migears-utils — 已知问题

> 本模块问题的概览。条目本体在 [`issues/`](issues/README.md)，一条目一文件：前置字段加讨论串。
> 本文件由条目生成，随时可以整段重写；请改条目，不要改本文件。
>
> 出自 miGears 全模块代码评审报告（5th round，2026-09-28）。

| | |
|---|---|
| 状态 | **P2 待修** |
| 体量 | src 419 行（净）· 138 个用例 · 3 个源文件 |

级别说明 — **P0** 功能性或安全级 · **P1** 文档照抄即错 · **P2** 健壮性 · **P3** 元数据与文档

## 状态一览

| | |
|---|---|
| 未了结 | P0 0 · P1 0 · P2 1 · P3 0 · 其他 1 |
| 已了结 | 3 / 5 |
| 等负责人 | `P2-1` |
| 等评审方 | `G4` |
| 等协调人 | _无_ |
| 已暂缓，不欠谁 | _无_ |

| id | 级别 | 状态 | 标题 |
|---|---|---|---|
| [`P0-1`](issues/P0-1.md) | P0 | **verified** | 方案、reg-name 与 IP 字面量的正则使用 $ 且未加 /D 或 \z，因此尽管字符类排除了 \n，结尾单个 LF … |
| [`P2-1`](issues/P2-1.md) | P2 | **open** | URL::parse() 使用原生 parse_url()，对严重畸形的 URL 返回 false，但对不常见但合法的输入（如下划线 … |
| [`P3-1`](issues/P3-1.md) | P3 | **verified** | query 重建使用 form … |
| [`P3-2`](issues/P3-2.md) | P3 | **verified** | query() 与 toString() 对非字符串标量仍不一致：query: ["flag"=>true,"n"=>5] 时 query() … |
| [`G4`](issues/G4.md) | - | **rejected** | 文档标准：项目标准是每一份文档都上英下汉——英文块在前，完全相同的中文块在后。本模块的 `CHANGELOG.md` 为纯英文。 … |

## 未关闭

本模块还剩什么要做：所有 `status` 不是 `verified` 或 `closed` 的条目，按严重度从高到低。
`waiting on` 是下一步该动手的一方，由其状态读出。

| | |
|---|---|
| 未关闭 | **2** / 5 |
| 按状态 | `open` 1 · `rejected` 1 |
| 等在谁 | 负责人 1 · 评审方 1 |

| 级别 | 条目 | 状态 | 等在谁 | 标题 |
|---|---|---|---|---|
| **P2** | [`P2-1`](issues/P2-1.md) | `open` | 负责人 | URL::parse() 使用原生 parse_url()，对严重畸形的 URL 返回 false，但对不常见但合法的输入（如下划线 … |
| **-** | [`G4`](issues/G4.md) | `rejected` | 评审方 | 文档标准：项目标准是每一份文档都上英下汉——英文块在前，完全相同的中文块在后。本模块的 `CHANGELOG.md` 为纯英文。 … |

## 结论

一个专注的工具库，含 URL 解析/校验和分页器；P0 级 URL 锚点问题已修复。parse_url 在异常 scheme 或畸形主机下仍有截断风险。

## 本轮已修复确认

P0-1 confirmed fixed — all three $ anchors changed to \z in URL validation, with explanatory comments; P3-1/P3-2 byte-preserving and query-representation claims now scoped correctly; G4 CHANGELOG bilingual standard pending.

## 测试盲区

无 URL::build() 端口为 0 的测试（按 RFC 合法但属边界情况）；无 total 为 0 且 page 为 1 的 Paginator 测试（边界）；无 FlowPaginator 首页为空但有更多数据的测试。

## 验证方式

- `./vendor/bin/phpunit` · `composer analyse` · `composer validate`
- `phpunit.xml.dist` 中的 warning/notice/deprecation/risky 开关：四个全开
- 只有在上述开关打开时 PHP 警告才会导致套件失败；否则请显式加 `--fail-on-warning`。
