# 新Fix（new_felix_total）デプロイ手順 — 検証サーバー fix2-181

## 前提

| 項目 | 値 |
|---|---|
| 接続 | `ssh fix2-181`（= `root@192.168.10.181`、鍵認証） |
| 配置先 | `/home/felix-projects/new_felix_total`（`felix_total` と同じ階層） |
| URL | http://192.168.10.181:8090 |
| DB | 既存 `fix_db` コンテナを共有（`shared-net` 経由） |

配置・`docker.env`・`shared-net`・`vendor`・`node_modules` は作成済み。

**サーバーは外部ネットワークに出られない**（DNS も通らない）。そのため使えないものが3つある。

- `git pull`（GitHub / Backlog に到達できない）
- `composer install` / `npm ci`（レジストリに到達できない）
- `docker build` のうち**上記2つを実行するステージ**

→ **コードは手元から `git bundle` で持ち込み、ビルド済み資材も手元で用意して転送する。**
（既存の `felix_total_deploy.bundle` と同じ運用）

現行 FiX は `/home/felix-projects/felix_total`（`fix_app` / ポート 8081）。`/home/itplus4/www/` は旧配置なので触らない。

---

## 手順

### 1.（手元）bundle を作る

```bash
cd <fix リポジトリ>
git bundle create /tmp/new_fix.bundle <ブランチ>
```

### 2.（手元）ビルド済み資材を用意して転送

サーバーで `composer install` / `npm run build` ができないため、手元で用意して送る。

```bash
composer install --no-dev --optimize-autoloader
npm ci && npm run build

scp /tmp/new_fix.bundle fix2-181:/home/felix-projects/
rsync -avz --delete vendor/ fix2-181:/home/felix-projects/new_felix_total/vendor/
rsync -avz --delete public/build/ fix2-181:/home/felix-projects/new_felix_total/public/build/
```

### 3.（サーバー）現状を控える

```bash
ssh fix2-181
cd /home/felix-projects/new_felix_total
git rev-parse --short HEAD     # ← 切り戻し用に控える
git status --short             # 未コミット変更が無いことを確認
```

### 4.（サーバー）bundle から取り込む

```bash
git fetch /home/felix-projects/new_fix.bundle <ブランチ>:<ブランチ>
git checkout <ブランチ>
```

### 5.（サーバー）起動

```bash
docker compose up -d
```

**`--build` は付けない。** ビルドすると Dockerfile 内の `composer install` / `npm ci` で失敗する。
既存イメージ `new-felix-total:latest` をそのまま使う。

### 6. 確認

```bash
docker ps | grep new_fix_app          # Up になっているか
docker logs --tail 50 new_fix_app     # エラーが出ていないか
curl -I http://localhost:8090         # 200 が返るか
```

ブラウザで http://192.168.10.181:8090 を開く。

---

## 切り戻し

```bash
cd /home/felix-projects/new_felix_total
git checkout <控えたコミット>
docker compose up -d
```

イメージごと戻す場合は `new-felix-total:order-delivery-flow` などの過去タグを使う。

---

## 注意

- **DB は現行 FiX と同じ `fix_db` を共有している。** マイグレーションを流すと現行側にも影響する
- `docker.env` は git 管理外。上書きしない
- ポート 8090 は新Fix 専用。8081（現行 FiX）と混同しない
- `docker compose down` は打たない（不要に停止させない）
- Dockerfile を変更した場合は、**手元でイメージをビルドして `docker save` / `docker load` で持ち込む**必要がある

---

## 未確認事項

- 手元の `fix` リポジトリで `composer install` / `npm run build` が通るか（PHP 8.4 / Node が必要）
- サーバーの `new-felix-total:latest` が、デプロイしたいブランチのコードと互換か（Dockerfile やPHP拡張に差分があれば作り直しが必要）

---

# 実サーバー調査結果（2026-09-11）

`ssh fix2-181` で接続して実機の状態を確認した。**上の手順のままではデプロイが完了しない。**
サーバーの状態が手順書の前提とずれているため、下記を先に解消する必要がある。

## 1. 手順どおりでは新しいコードが反映されない ★

サーバーの `docker-compose.yml` には**ボリュームマウントが無い**。Dockerfile は `COPY . .` で
コードをイメージへ焼き込む作りなので、

```
git checkout <ブランチ> → docker compose up -d（--build なし）
```

では**イメージ内の古いコードが起動するだけ**。`vendor/` `public/build/` を rsync しても、
マウントされていないので使われない。

→ 実際に必要なのは **手元で `--platform linux/amd64` でイメージをビルド → `docker save` →
転送 → `docker load` → `docker compose up -d`**。サーバーに `new-felix-total-amd64.tar.gz` が
残っており、以前もこの方法で入れたと思われる。上の「注意」に書いてある方法が実は本手順。

## 2. サーバー側リポジトリが旧世代のまま汚れている

```
HEAD  e4d72a1  feature/mockData 「見積管理画面仮コミット」
未コミット変更 40ファイル超（Dockerfile / docker-compose.yml / .env.example /
  app/Http/Controllers/EstimateManagementController.php / resources/js/components/… ほか）
```

現在の `feature/quotations` は構成ごと変わっている（FSD 化・コントローラ分割）ため
`git checkout` は衝突する。`docker.env` を退避して `reset --hard` する運用に変える。

## 3. DB が旧世代スキーマ ★最大の課題

サーバー `fix_db` データベースの新テーブルは**前の世代**だった。

| | テーブル |
| --- | --- |
| ある | `t_buildings` `t_building_cost_items` `t_cost_quotations` `t_orders` `t_invoices` `m_companies` ほか |
| **無い** | `t_building_budget_items` `t_payable_partners` `t_payable_orders` `t_billing_partners` `t_billing_quotations` `t_billing_orders` `t_building_group_statuses` `m_roles` `m_permissions` `m_menu_items` `p_user_roles` `p_role_permissions` `p_permission_menu_items` ほか |

現在の新Fix はこれらが無いと**1画面も開かない**。作成するマイグレーションは**すべて
felix_total 側のリポジトリ**にある（新Fix 側は5本だけで、中身はリネームとバックフィル）。

## 4. felix_total が7月のスナップショット

```
/home/felix-projects/felix_total  d954548cc8  snapshot-before-deploy-20260715
```

新Fix が依存する felix_total 側の実装（見積の新旧同期・業者承諾・発注書発行・メニュー/ロール・
請求予定データ作成など）が入っていない。**felix_total を先にデプロイしてマイグレーションを
流すのが前提条件**。

## 5. その他の実機状態

- `new_fix_app` コンテナは**起動していない**（`list_app` / `fix_app` / `fix_db` のみ Up）
- イメージ `new-felix-total:latest` の PHP は 8.3.32（`composer.json` の `~8.3.0` と一致）
- ただし `gd` / `exif` 拡張が**入っていない**（現在の Dockerfile は入れる）。
  添付画像の圧縮は `function_exists()` で握ってあるため動作はするが、圧縮は効かない
- 現行 `http://192.168.10.181:8081` は 302 応答、新Fix `:8090` は無応答（未起動のため）

---

# 必要な設定

## A. ドメイン（要決定）★

`cross_auth` は両アプリで共有するクッキーで、`CROSS_AUTH_DOMAIN` を親ドメインにして共有する。
しかし検証サーバーは IP アクセス（現行 `192.168.10.181:8081` / 新Fix `:8090`）。
**IP にドメイン属性は使えない**（`.192.168.10.181` は不正）。

| 案 | 内容 |
| --- | --- |
| 1 | **`CROSS_AUTH_DOMAIN` を空にして IP のまま使う**。同一ホストなのでポートが違ってもクッキーは共有される。手っ取り早い |
| 2 | 共通の親ドメインを振る（例 `fix.felix2.local` / `new.felix2.local` を hosts か社内DNSへ登録し `CROSS_AUTH_DOMAIN=.felix2.local`）。本番構成に近い |

あわせて `CROSS_AUTH_SECURE=false`（http のため）。

## B. `new_felix_total/docker.env` に足りないキー

現状は **6キーのみ**（`APP_URL` `APP_KEY` `DB_DATABASE` `DB_USERNAME` `DB_PASSWORD` `APP_DEBUG`）。
現在のコードはこれだけでは動かない。

```dotenv
# 現行との連携
FELIX_TOTAL_URL=http://192.168.10.181:8081     # ブラウザから見た現行（iframe 用）
FELIX_TOTAL_INTERNAL_URL=http://fix_app        # コンテナ間（shared-net 上の container_name）
# cross_auth（felix_total と同じ値にする）
CROSS_AUTH_SECRET=<両アプリ共通のランダム値>
CROSS_AUTH_DOMAIN=                              # ← A の決定次第（IP運用なら空）
CROSS_AUTH_TTL=1800
CROSS_AUTH_SECURE=false
# 業者マイページ・通知メール
MAIL_QUEUE_VENDOR_BASE_URL=http://192.168.10.181:8081
MAIL_QUEUE_OVERRIDE_TO=<検証中は自分のアドレスへ寄せる>
# メールキューの別DB（現行 .env の DB_*_2 と同じ値）
DB_HOST_2= / DB_PORT_2= / DB_DATABASE_2= / DB_USERNAME_2= / DB_PASSWORD_2=
```

`FELIX_TOTAL_INTERNAL_URL` は重要。**部長承認・見積依頼・取消はコンテナから現行を HTTP で叩く**
ため、到達できないと連携が全部失敗する（失敗時は画面にエラーが出るようになっている）。

## C. `felix_total/.env` に足りないキー

サーバーの現行 `.env` には `CROSS_AUTH_*` も `FRAME_ANCESTOR` も**無い**。

```dotenv
CROSS_AUTH_SECRET=<新Fix と同じ値>
CROSS_AUTH_DOMAIN=<A の決定に合わせる>
CROSS_AUTH_TTL=1800
CROSS_AUTH_SECURE=false
FRAME_ANCESTOR=http://192.168.10.181:8090   # 新Fix から iframe で開くための CSP
```

`FRAME_ANCESTOR` が無いと、業者マイページ・発注書プレビュー・見積先詳細の **iframe が真っ白**になる。

## D. マイグレーション

- 流す場所は **felix_total 側**（`docker exec fix_app php artisan migrate`）
- **DB は現行と共有**（`migrations` テーブルも共通）。流すと現行にも影響する
- 旧世代テーブルが残っているため、7/15 以降の差分が素直に通るかは要検証。
  **実行前に `fix_db` を dump すること**
- 権限まわりは順番がある:
  `create_p_user_permissions_table` → `backfill_new_user_tables_from_admin_tables`
  → `seed_fix_menu_items_and_estimate_manager_role`
  → `show_order_acceptance_menu_to_estimate_manager`
  → `grant_all_permission_to_engineer_manager_role`

---

# 進める順番

1. サーバーの `fix_db` を dump（切り戻し用）
2. **felix_total を最新化**（bundle 持ち込み）→ `php artisan migrate` → 現行が動くことを確認
3. `felix_total/.env` に **C** を追記 → `php artisan config:clear`
4. 手元で新Fix のイメージを **amd64 でビルド** → `docker save` → 転送 → `docker load`
5. サーバーの `new_felix_total` は `docker.env` を退避して `reset --hard` ＋ 目的ブランチへ
6. `docker.env` に **B** を記入 → `docker compose up -d` → `curl -I http://localhost:8090`
7. ログイン → サイドメニュー表示 → iframe 表示 → 見積依頼送信 の順に疎通確認

## 先に決めること

- **A**: ドメインを IP のままにするか、名前を振るか
- felix_total をどのブランチ・どこまで上げるか
