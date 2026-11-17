/**
 * API 对接层 barrel —— 对接 https://xiaoxia.ren/api（Laravel 真实后端，一级域名）
 *
 * 模块划分：
 *   ./request  HTTP 基建：request/ApiError/token/401 事件
 *   ./user     登录/注册/资料/密码/验证码
 *   ./goods    商品/分类/收藏/评论
 *   ./address  收货地址
 *   ./cart     购物车
 *   ./orders   订单
 *   ./pay      结算/下单/支付
 *   ./article  文章
 *   ./misc     消息/售后/浏览历史/积分
 *
 * 统一响应：{ code, msg, data }，code === 0 表示成功。
 * 策略：优先请求真实接口；商品读接口失败时回退本地静态目录。
 */
export * from "./request";
export * from "./user";
export * from "./goods";
export * from "./address";
export * from "./cart";
export * from "./orders";
export * from "./pay";
export * from "./article";
export * from "./misc";
export * from "./user";
