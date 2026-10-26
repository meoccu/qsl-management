=== QSL 卡片管理系统 ===
Contributors: meoccu, bi1kbu
Tags: qsl, amateur radio, qso, ham radio, card management
Requires at least: 6.2
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.0.4
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

业余无线电 QSO/SWL、QSL 卡片、线上/线下换卡、收卡与公开查询的 WordPress 管理插件。

== 描述 ==

本插件将 bi1kbu/qsl-management 的核心 QSL 业务移植到 WordPress：

* QSO / SWL 记录
* 卡片编号、制卡、发卡、签收、收卡
* 实体卡申请审核
* 线上换卡申请
* 地址簿、卡片局、本台卡片版本
* 审计日志、JSON 备份、CSV 导出
* 线上/线下换卡公开表单可启用 Cloudflare Turnstile
* 公开短码：[qsl-card]、[qsl-online-exchange-card]、[qsl-offline-exchange-card]、[qsl-receipt-card]

== 安装 ==

1. 上传 `qsl-management` 到 `/wp-content/plugins/`。
2. 启用插件。
3. 打开「QSL 管理 → 设置」配置本台资料。
4. 新建页面并插入短码。

== 版权与出处 ==

原作者：bi1kbu
GitHub 仓库：https://github.com/bi1kbu/qsl-management
Halo 应用市场：https://www.halo.run/store/apps/app-z7ouhnfn
WordPress 移植：砂糖橘猫
移植作者网站：https://www.luna.tel
本仓库：https://github.com/meoccu/qsl-management

原项目与本移植均使用 GPL-3.0-or-later。

== 变更日志 ==

= 1.0.4 =
* 增加英文 README、CHANGELOG 和自动 GitHub Release 工作流。

= 1.0.3 =
* 公开线上换卡与实体卡申请改为可视化卡片样式选择：显示卡面图片、版本名称和剩余数量；未上传图片时显示渐变占位卡面。
* 后台本台卡片样式增加卡面预览。

= 1.0.2 =
* 明确卡片样式与正式卡片记录的先后：先配置「本台卡片样式」，访客提交线上换卡申请后生成申请记录，审核通过后才创建正式卡片记录。
* 后台卡片页、线上换卡页增加流程说明；未配置样式时公开换卡表单显示明确提示。

= 1.0.1 =
* 优化卡片创建：明确卡片版本选择、重复建卡/库存不足/数据库错误提示；无可建卡 QSO 或无本台卡片版本时显示后台提示。
* 线上换卡、线下换卡公开表单增加 Cloudflare Turnstile；设置中可配置 Site Key / Secret Key。

= 1.0.0 =
* 首个 WordPress 移植版本：后台 CRUD、公开短码、审核、收卡、备份与审计。
