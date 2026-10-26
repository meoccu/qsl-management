#  WordPress 移植说明

**原作者：bi1kbu**  
**GitHub 仓库：<https://github.com/bi1kbu/qsl-management>**  
**WordPress 移植：砂糖橘猫**  
**网站：<https://www.luna.tel>**

## 对照关系

| 原 Halo 结构 | WordPress 实现 |
| --- | --- |
| `QsoRecord` | `wp_qslm_qso` + `QSLM_Admin::qso()` |
| `CardRecord` | `wp_qslm_cards` + `QSLM_Admin::cards()` |
| `QslCardRequest` | `wp_qslm_card_requests` + `QSLM_Admin::requests()` |
| `ExchangeRequest` | `wp_qslm_exchange_requests` + `QSLM_Admin::exchanges()` |
| `ReceiveRecord` | `wp_qslm_receives` + `QSLM_Admin::receives()` |
| `AddressBookEntry` | `wp_qslm_addresses` + `QSLM_Admin::addresses()` |
| `BureauEntry` | `wp_qslm_bureaus` + `QSLM_Admin::bureaus()` |
| `StationCard` | `wp_qslm_station_cards` + `QSLM_Admin::station_cards()` |
| `QslAuditLog` | `wp_qslm_audit` + `QSLM_Admin::audit()` |
| Halo 公开页面 | `[qsl-card]`、`[qsl-online-exchange-card]`、`[qsl-offline-exchange-card]`、`[qsl-receipt-card]` |
| Halo CustomEndpoint | WordPress `admin-post.php` + nonce 匿名表单 |

## 安全基线

- 后台：`manage_qslm` + `check_admin_referer()`。
- 公开：`wp_nonce_field()`、IP 限流、服务端字段校验、`esc_*` 输出。
- SQL：表名白名单 + `$wpdb->prepare()`。
- 数据：插件表使用 `{$wpdb->prefix}qslm_` 前缀，启用时创建，卸载时按设置决定是否删除。

## 状态约定

- 卡片：`待制卡 → 已制卡 → 已发卡 → 已签收`，收卡为 `已收卡`。
- 无卡片版本：`不发卡`，编号 `NC0001` 起。
- 实体卡申请：`待审核 → 已通过 → 已建卡` 或 `已拒绝`。
- 线上换卡：`待审核 → 已通过/已拒绝 → 已创建卡片`。

## Turnstile

线上/线下换卡短码会读取「QSL 管理 → 设置」中的 Turnstile 配置。服务端校验 `success`、`action=qslm_exchange` 与 hostname；Secret Key 保存在 WordPress option 中，不会显示在公开页面。实体卡申请与公开签收未强制 Turnstile。

## 未纳入一期

原项目的 AI 解析、QRZ.COM/QRZ.CN 抓取、ADIF/Cabrillo、CardPrint 本地打印桥接和 Halo RBAC 角色模板没有直接移植。它们依赖 Halo Secret、外部网络和本地 Python 工具，适合作为独立 WordPress 模块实现。

## 官方参考

- <https://developer.wordpress.org/plugins/plugin-basics/>
- <https://developer.wordpress.org/plugins/security/>
- <https://developer.wordpress.org/plugins/database/>
- <https://developer.wordpress.org/plugins/shortcodes/>
- <https://developer.wordpress.org/plugins/users/>
- <https://developer.wordpress.org/plugins/plugin-basics/uninstall-methods/>
