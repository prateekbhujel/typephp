# shop-fpm —— TypePHP FPM 迷你商城

一个完整的多页 Web 应用示例，用 TypePHP 的 FPM 模式编译成**一个自包含的 php-fpm 二进制**：

- **商品列表页**：卡片展示，一键加入购物车
- **购物车页**：改数量、删除、切换会员等级与优惠券，实时看到各项折扣
- **结算页**：填收货信息 + **假支付**（不接任何支付网关，提交即成功）
- **订单页**：下单成功页与历史订单

形态：**服务端渲染 HTML**、**SQLite 存商品与订单**、**session 存购物车**、**页面与业务逻辑全部编进二进制**。

## 结构

```
shop-fpm/
├── project.fpm.yml        # sapi: fpm + php-builder；src/ 与 promo-ext/src 走 AOT，lib/ 与 public/ 内嵌
├── src/pricing.php        # AOT：报价适配层，规则复用 promo-ext/src/promo.php → shop_pricing()
├── src/catalog.php        # AOT：商品种子 → shop_catalog()
├── src/probe.php          # AOT：请求级状态探针 → shop_probe_seq()
├── lib/bootstrap.php      # 请求脚本：session、SQLite 建表与播种、购物车、视图助手
├── public/index.php       # ① 商品列表
├── public/cart.php        # ② 购物车
├── public/checkout.php    # ③ 结算 + 假支付
├── public/order.php       # ④ 订单详情 / 订单列表
├── public/quote.php       # JSON 试算接口（并发校验打的就是它）
├── bench/reference.php    # 参考实现 + 用例生成（直接 require promo-ext 的 promo.php）
├── bench/concurrency.php  # 并发校验器
├── conf/php-fpm.conf      # listen 127.0.0.1:9910
├── conf/Caddyfile         # 前端 A（Caddy）
├── conf/nginx.conf        # 前端 B（nginx 模板，需渲染占位符）
└── .gitignore             # build/ shop_fpm var/
```

**AOT 与内嵌的分工**：`src/` 里的报价适配、商品数据被编译成机器码；折扣规则本身来自 `../promo-ext/src/promo.php`，由 `project.fpm.yml` 的 `sources` 一起编进 `shop_fpm`（不复制一份）。`lib/` 和 `public/` 的页面脚本按 opcode 内嵌进二进制，仍然用 PHP 写、按普通 PHP 跑。

**规则住在 promo-ext。** 会员折扣、满减、优惠券、免运费门槛都定义在 `examples/promo-ext/src/promo.php` 的 `promo_rules()` / `promo_quote()` 里，`shop-fpm` 和 `promo-ext` 编的是**同一份源码**。商城侧只在 `src/pricing.php` 做两件事：把无会员的等级名 `guest` 和引擎的 `none` 互转，以及把购物车行补成带 `sku`/`name`/`amount` 的明细。改规则只需改 `promo-ext/src/promo.php`。

## 折扣规则（`../promo-ext/src/promo.php`）

| 规则 | 内容 |
| --- | --- |
| 会员折扣 | `guest`/`none` 不打折、`silver` 95 折、`gold` 9 折 |
| 满减 | 满 300 减 30，满 500 减 80（取最高一档） |
| 优惠券 | `SAVE20` 满 200 减 20；`OFF10` 满 100 打 9 折 |
| 运费 | 12 元；全是可下载商品不计运费；实付满 99 免运费 |

## 运行（纯 shell，没有脚本）

### 1. 构建

在 `compiler` 目录下执行。产物名 `shop_fpm` 会成为模块命名空间，**换名字要清 `examples/shop-fpm/build`**：

```sh
php bin/tpc.php examples/shop-fpm/project.fpm.yml --no-progress -j4 -o examples/shop-fpm/shop_fpm
```

### 2. 启动 php-fpm

PID 与日志路径相对 `-p` 指定的前缀：

```sh
mkdir -p var
./examples/shop-fpm/shop_fpm -n -y examples/shop-fpm/conf/php-fpm.conf -p "$PWD/var" -t
./examples/shop-fpm/shop_fpm -n -y examples/shop-fpm/conf/php-fpm.conf -p "$PWD/var" -F -O &
```

监听 `127.0.0.1:9910`，PID 与错误日志写在 `var/fpm.pid`、`var/fpm-error.log`（相对 `-p` 指定的前缀）。FPM 只讲 FastCGI，需要一个 HTTP 前端，下面二选一。

### 3a. 前端：Caddy

从 `examples/shop-fpm` 目录启动（`root` 是相对路径）：

```sh
cd examples/shop-fpm
caddy run --watch --config conf/Caddyfile &
```

### 3b. 前端：nginx

```sh
cd compiler
mkdir -p var/nginx/tmp
sed -e "s|__RUN_DIR__|$PWD/var/nginx|g" \
    -e "s|__FPM_PORT__|9910|g" \
    -e "s|__NGINX_PORT__|8081|g" \
    -e "s|__DOCROOT__|$PWD/examples/shop-fpm/public|g" \
    examples/shop-fpm/conf/nginx.conf > var/nginx.conf
nginx -t -p "$PWD/var/nginx" -c "$PWD/var/nginx.conf"
nginx -p "$PWD/var/nginx" -c "$PWD/var/nginx.conf" &
```

### 4. 走一遍流程

浏览器打开 `http://localhost:8081/` 即可。用 curl 走完整链路：

```sh
B=http://127.0.0.1:8081
rm -f /tmp/shop.cookie

# 商品列表
curl -s -c /tmp/shop.cookie "$B/index.php" | grep -o '<h3>[^<]*</h3>'

# 加购：手册 129 + 键盘 399 + 视频课 99 = 627
for sku in BK-1001 KB-2001 DL-9001; do
  curl -s -b /tmp/shop.cookie -c /tmp/shop.cookie -o /dev/null "$B/cart.php?action=add&sku=$sku"
done

# 选 gold 会员 + OFF10 券
curl -s -b /tmp/shop.cookie -c /tmp/shop.cookie -o /dev/null "$B/cart.php?level=gold"
curl -s -b /tmp/shop.cookie -c /tmp/shop.cookie -o /dev/null "$B/cart.php?coupon=OFF10"
curl -s -b /tmp/shop.cookie "$B/cart.php" | sed 's/<[^>]*>//g' | grep -E '商品小计|会员折扣|满减|优惠券|运费|应付'

# 假支付
curl -s -b /tmp/shop.cookie -c /tmp/shop.cookie -o /dev/null -w '%{http_code} -> %{redirect_url}\n' \
  -X POST -d 'customer=张三&address=上海市浦东新区xx路1号&card=4242424242424242&expiry=12/29&cvc=123' \
  "$B/checkout.php"
```

实测输出：

```
商品小计¥627.00
会员折扣（gold）-¥62.70
满减-¥80.00
优惠券（OFF10）-¥62.70
运费+¥0.00
应付¥421.60
302 -> http://127.0.0.1:8081/order.php?id=1&paid=1
```

几个边界场景也都实测过：

| 场景 | 结果 |
| --- | --- |
| 只买数据线 19 元（实物） | 运费 +12.00，应付 31.00；同时挂 `SAVE20` 会提示 `threshold_not_met` |
| 只买电子书 59 元（可下载） | 运费 0，应付 59.00 |
| 结算表单留空 | 5 条校验错误，不落订单 |
| 重启 php-fpm 后访问 `/order.php` | 订单仍在（SQLite 持久化） |

### 5. 停止

```sh
kill "$(cat var/fpm.pid)"            # php-fpm，在 compiler 目录
pkill -f 'caddy run'                 # Caddy
kill "$(cat var/nginx/nginx.pid)"    # nginx
```

## 并发正确性校验

`bench/concurrency.php` 打 `public/quote.php`：给每个请求带唯一 `id` 和互不相同的购物车参数，
重复请求同一批用例并分散到不同 worker，然后逐字段比对。

```sh
php bench/concurrency.php http://127.0.0.1:8081/quote.php 1,8,32,64 200 32
```

参数：`<base-url> <并发级别> <每轮请求数> <用例数> <随机种子>`。任一项不符即计为失败并以非 0 退出。

三项校验：

1. **`id` 回显一致** —— 请求之间没有串扰
2. **`seq` 恒为 1** —— `shop_probe_seq()` 的函数静态变量按请求重置，没有跨请求状态泄漏
3. **结果与 `bench/reference.php` 逐字段一致**（金额精确到分，含优惠券命中/未命中原因）。参考实现直接 `require` `promo-ext/src/promo.php`，所以这一项校验的是 **AOT 编译产物 vs 同一份源码的解释执行**，顺带覆盖请求隔离。

实测（`pm.max_children = 4`，某个 4 核开发机）：

```
并发     请求     一致     失败     worker     seq 取值
1          200        200        0          4          1x200
8          200        200        0          4          1x200
32         200        200        0          4          1x200
64         200        200        0          4          1x200

合计 800 请求，一致 800，失败 0
参与处理的 worker 进程：4 个；seq 取值分布：1x800
```

校验器本身不是空转：把 `examples/promo-ext/src/promo.php` 里的 gold 折扣故意改成 0.8
（**不要重新构建** `shop_fpm`，让二进制里还是旧的 0.9），立刻报出
`一致 31，失败 9` 并打印差异样例；还原后恢复全过。这里对比的就是"宿主解释执行同一份源码"与
"已经编进二进制的 AOT 版本"。

## 注意事项

**先看这张排查表。** 前端配置错了只会表现为"页面打不开"，不会给出明确的错误：

| 症状 | 原因 |
| --- | --- |
| `GET /` 返回 **200 空响应**（浏览器一片空白） | Caddy/nginx 的 `root` 解析错了（最常见是从错误的目录启动 Caddy），没有任何路由命中。Caddy 对未处理的请求返回空 200，**不是 404**，所以很容易误判 |
| `GET /index.php` 返回 `File not found` | 请求走到了 FPM，但 `SCRIPT_FILENAME` 既不是内嵌记录的路径，磁盘上也没这个文件——同样是 `root` 不对 |
| `GET /index.php` 返回一段 **JSON** | 请求落到了别的页面（本示例里只有 `quote.php` 输出 JSON），说明 `root` 指向了别的目录 |
| 改完配置浏览器还是旧结果 | 出错那次响应通常不带缓存头，会被浏览器启发式缓存；强制刷新（Ctrl/Cmd+Shift+R） |

一条命令确认到底在服务哪个文件：

```sh
curl -s -i 'http://127.0.0.1:8081/index.php' | sed -n '1,3p'
# Content-Type: text/html   正常
# File not found            路径不对
```

**内嵌脚本里 `$_SERVER` 可用。** `$_GET` / `$_POST` / `$_COOKIE` / `$_FILES` / `$_SERVER` 都正常，页面里直接读 `$_SERVER['REQUEST_METHOD']` 即可。

**docroot 必须等于构建时路径。** 页面是按 opcache opcode 内嵌的，运行期按**构建时的绝对路径**匹配；`root` 不一致就会回落磁盘，报 `File not found`。

**产物名影响缓存。** `-o` 的文件名决定模块命名空间（`typephp_project_shop_fpm`）。换了名字但沿用同一个 `build-dir`，会串用上一次的目标文件，链接期报一堆 `undefined reference to typephp_project_xxx::get_str`；这时删掉 `examples/shop-fpm/build` 重新构建即可。

**数据库位置**：默认 `examples/shop-fpm/var/shop.db`（用 `__DIR__` 锚定，不看进程 CWD），可用环境变量 `SHOP_DB` 覆盖。请求里 `getcwd()` 是页面脚本所在目录（即 docroot），不能用来定位数据文件。SQLite 的 `-wal`/`-shm` 需要可写目录。

**session**：`save_path` 没配置时落到系统临时目录；生产环境建议通过 `PHPRC` / `PHP_INI_SCAN_DIR` 指定。

**改了就要重新构建**：`src/` 与 `../promo-ext/src` 改的是 AOT 代码，`lib/` `public/` 改的是内嵌脚本，两者都在二进制里；增量构建大约几秒。**折扣规则改动要改 `../promo-ext/src/promo.php`**（`promo-ext` 扩展和 `shop_fpm` 共用这一份）。商品数据以 `src/catalog.php` 为准，每次启动按 SKU upsert 同步，不需要手动清库。

**扩展依赖**：只用到 `pdo` / `pdo-sqlite` / `sqlite3` / `session`，它们已在缓存运行时的 `enabled_extensions` 里，所以 `php-builder: extensions: []` 就能命中缓存、不会重编 PHP。把 `sapi` 改成 `[cli, fpm]` 会触发全量重建。
