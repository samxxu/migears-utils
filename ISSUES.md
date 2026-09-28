# migears-utils — Known Issues / 已知问题

> Summary of this module's issues. The items themselves are in [`issues/`](issues/README.md), one file
> per item: a front-matter header and a thread. This file is generated from them and can be rewritten at
> any time; edit an item, never this file.
>
> 本模块问题的概览。条目本体在 [`issues/`](issues/README.md)，一条目一文件：前置字段加讨论串。
> 本文件由条目生成，随时可以整段重写；请改条目，不要改本文件。
>
> From the miGears Full-Module Code Review Report (4th round, 2026-09-27).

| | |
|---|---|
| Status / 状态 | **P0 open / P0 未修** |
| Size / 体量 | src 758 lines (419 net) · 130 tests · 3 src files |

Legend / 图例 — **P0** functional or security · **P1** documentation that fails when copied · **P2** robustness · **P3** metadata and docs
级别说明 — **P0** 功能性或安全级 · **P1** 文档照抄即错 · **P2** 健壮性 · **P3** 元数据与文档

## At a glance / 状态一览

| | |
|---|---|
| Items / 条目 | P0 1 · P1 0 · P2 0 · P3 2 · other 1 |
| Answered / 已回复 | 0 of 4 |
| Waiting / 等待回复 | `P0-1`, `P3-1`, `P3-2`, `G4` |

| id | level | status | title |
|---|---|---|---|
| [`P0-1`](issues/P0-1.md) | P0 | **open** | The scheme, reg-name and IP-literal patterns use `$` without `/D` or … |
| [`P3-1`](issues/P3-1.md) | P3 | **open** | Query rebuilding is form-encoded and not byte-preserving: … |
| [`P3-2`](issues/P3-2.md) | P3 | **open** | `query()` and `toString()` still disagree for non-string scalars: with … |
| [`G4`](issues/G4.md) | - | **open** | Document standard: the project standard is that every document is … |

## Verdict / 结论

The paginator semantics are now consistent, but hardening the host validator introduced the round's only P0: the new regular expressions use `$` anchors, and in PCRE `$` also matches before a trailing newline, so a host or scheme ending in a single LF passes validation and lands in the authority.

分页器语义现已一致，但新加的 host 校验引入了本轮唯一的 P0：新正则用了 $ 锚点，而 PCRE 的 $ 还允许匹配结尾换行之前，因此以单个 LF 结尾的 host 或 scheme 能通过校验并进入 authority。

## Fixed since the last round / 本轮已修复确认

上一轮 P1 与四条 P2 全部修复：hasMore 与 nextCursor 的冲突（空 items 强制 hasMore=false、末条无游标即抛）、两分页器的 pageSize 归一化对齐、端口 1–65535 校验、host 的常规非法字符（CRLF/空格/斜杠/@/坏转义）与 query 中 null 在两个方法间的一致性。 

## Test gaps / 测试盲区

No case for a single trailing LF in host/scheme or an IP literal — the existing CRLF cases carry trailing text, which hid the anchor problem; no assertion for `%20`↔`+` query round-tripping; no assertion that query() and toString() agree on bool/int values.

无「host/scheme 或 IP 字面量以单个 LF 结尾」用例——现有 CRLF 用例后面还有文本，恰好遮住了锚点问题；无 query 的 %20↔+ 往返断言；无「query() 与 toString() 对 bool/int 一致」的断言。

## Verification protocol / 验证方式

- `./vendor/bin/phpunit` · `composer analyse` · `composer validate`
- Warning/notice/deprecation/risky flags in `phpunit.xml.dist`: all four on
- A PHP warning counts as a test failure only where those flags are on; otherwise run `./vendor/bin/phpunit --fail-on-warning` explicitly.
- 只有在上述开关打开时 PHP 警告才会导致套件失败；否则请显式加 `--fail-on-warning`。
