# QSL 卡片管理系统（WordPress 插件）

[English README](README.en.md)

> **原作者：bi1kbu**  
> **GitHub 仓库：<https://github.com/bi1kbu/qsl-management>**  
> **Halo 应用市场：<https://www.halo.run/store/apps/app-z7ouhnfn>**  
> **WordPress 移植：砂糖橘猫**  
> **移植作者网站：<https://www.luna.tel>**  
> **本仓库：<https://github.com/meoccu/qsl-management>**

本项目把原 Halo 2 插件 `qsl-management` 的核心 QSL 业务移植为 WordPress 插件。原作者、仓库出处与移植信息同时显示在插件后台每个页面顶部、公开短码表单底部和本文档顶部。

## 功能范围

已实现 WordPress 原生版本的核心闭环：

- 后台「QSL 管理」菜单：
  - 总览
  - 通联记录（QSO / SWL）增删改查
  - 卡片创建、制卡、发卡、签收、删除
  - 实体卡申请审核、通过后建卡
  - 线上换卡申请审核、通过后建卡
  - 收卡登记与收卡编号
  - 地址簿、卡片局、本台卡片版本
  - 设置、审计日志
  - JSON 全量备份、CSV 通联导出、JSON 恢复
  - 线上/线下换卡公开表单可启用 Cloudflare Turnstile 人机验证
- 公开短码：
  - `[qsl-card]` / `[qsl-card-query]`：按呼号查询通联与卡片状态，并提交实体卡申请
  - `[qsl-online-exchange-card]`：线上换卡申请
  - `[qsl-offline-exchange-card]`：线下换卡确认
  - `[qsl-receipt-card]`：公开签收
- 匿名提交具备 nonce、IP 限流、输入清洗、服务端校验、审计记录。
- 数据结构使用 WordPress 自定义表，前缀 `{$wpdb->prefix}qslm_`。
- 支持 `uninstall.php`；默认保留数据，设置中可选择卸载时删除。

### 尚未移植

原 Halo 插件中的 AI 解析、QRZ.COM / QRZ.CN 地址获取、ADIF / Cabrillo 导入导出、CardPrint 本地打印桥接、复杂状态机与 Halo RBAC 模板未在本次一期中实现。它们依赖第三方服务和独立打印工具，建议在 WordPress 版中按独立模块逐步接入。

## 正确的线上换卡顺序

1. **本台卡片样式**：后台先配置访客可选的卡片样式、库存和图片。
2. **访客提交申请**：访客填写地址、选择已有样式后提交，系统生成「线上换卡申请记录」，此时不直接创建正式卡片。公开换卡页会直接显示卡面图片、版本名称和剩余数量；没有上传图片时显示渐变占位卡面。
3. **管理员审核**：在「线上换卡」通过或拒绝申请。
4. **审核通过后建卡**：管理员在审核通过的申请上点击「创建卡片」，才生成正式卡片记录并进入制卡、发卡流程。

因此「本台卡片样式」是预先配置的模板；「卡片记录」是 QSO 或审核通过后生成的正式卡片，两者不是同一个东西。

## 贡献者

- **meoccu**：WordPress 移植维护者 · <https://github.com/meoccu>
- **bi1kbu**：原 Halo 插件作者 · <https://github.com/bi1kbu>
- **砂糖橘猫**：WordPress 移植作者 · <https://www.luna.tel>

完整说明见 [CONTRIBUTORS.md](CONTRIBUTORS.md)。

## 安装

1. 将 `qsl-management` 目录上传到 `wp-content/plugins/`，或在后台「插件 → 安装插件 → 上传插件」上传 ZIP。
2. 启用插件。启用时会自动创建数据表、默认设置、默认卡片版本，并添加 `manage_qslm` 权限。
3. 在「QSL 管理 → 设置」填写本台呼号、邮箱、通信地址、线上换卡策略和默认备注。
4. 在「QSL 管理 → 本台卡片」配置卡片版本和库存。
5. 新建页面并插入公开短码。

### Cloudflare Turnstile

在「QSL 管理 → 设置」勾选「启用 Turnstile」，填入 Cloudflare Turnstile 的 Site Key 与 Secret Key。启用后：

- `[qsl-online-exchange-card]` 与 `[qsl-offline-exchange-card]` 会显示 Turnstile 验证框；
- 提交时由 WordPress 服务端调用 `siteverify` 校验 token、action 与 hostname；
- Secret Key 只保存在后台设置中，不会输出到公开页面；
- Secret Key 输入框留空表示保持已保存的密钥不变。

Turnstile 只保护线上/线下换卡表单；实体卡申请与公开签收不强制 Turnstile。

## 数据表

| 表 | 用途 |
| --- | --- |
| `qslm_qso` | QSO / SWL 通联记录 |
| `qslm_cards` | 卡片、编号、制卡/发卡/签收状态 |
| `qslm_card_requests` | 实体卡申请 |
| `qslm_exchange_requests` | 线上换卡申请 |
| `qslm_receives` | 收卡记录 |
| `qslm_addresses` | 地址簿 |
| `qslm_bureaus` | 卡片局 |
| `qslm_station_cards` | 本台卡片版本 |
| `qslm_activities` | 线下活动（预留） |
| `qslm_audit` | 审计日志 |
| `qslm_sequences` | 卡片/收卡/申请编号序列 |

## 安全设计

- 后台操作使用 `manage_qslm` 能力与 `check_admin_referer()`。
- 公开表单使用 `wp_nonce_field()`，匿名限流按 IP + 任务维度使用 transient。
- 所有 SQL 使用 `$wpdb->prepare()`，表名来自白名单。
- 输出使用 `esc_html()`、`esc_attr()`、`esc_url()`、`wp_kses_post()`。
- 公开接口不返回地址、邮箱等敏感字段，地址只在服务端保存并在后台按权限查看。

## WordPress 参考手册

- Plugin Basics: <https://developer.wordpress.org/plugins/plugin-basics/>
- Security: <https://developer.wordpress.org/plugins/security/>
- Database: <https://developer.wordpress.org/plugins/database/>
- Shortcodes: <https://developer.wordpress.org/plugins/shortcodes/>
- Users and Capabilities: <https://developer.wordpress.org/plugins/users/>
- Uninstall: <https://developer.wordpress.org/plugins/plugin-basics/uninstall-methods/>

## 许可与出处

原项目使用 GPL-3.0；本 WordPress 移植同样使用 **GPL-3.0-or-later**，并保留原项目 `LICENSE`。

- 原项目：<https://github.com/bi1kbu/qsl-management>
- 原作者：bi1kbu
- WordPress 移植：砂糖橘猫
- 移植作者网站：<https://www.luna.tel>
