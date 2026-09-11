# 新Fix デプロイ手順 — 検証サーバー fix2-181（配置先 `/home/felix-projects/fix2`）

最終更新: 2026-09-11（実機調査を反映して全面改訂）

## 0. この文書の使い方

新Fix を検証サーバーへ入れるための手順書。**§1〜§3 を満たしてから §4 の手順を上から順に実行する。**
§1 の決定事項が未確定のうちは作業を始めない（途中で止まる）。

---

## 1. 決定事項

| # | 論点 | 決定 |
| --- | --- | --- |
| 1-1 | `cross_auth` のドメイン共有方式 | **IP 運用**。`CROSS_AUTH_DOMAIN` は**空**にする。現行と新Fix は同一ホスト（`192.168.10.181`）なので、ポートが違ってもクッキーは共有される。hosts / DNS の作業は不要 |
| 1-2 | felix_total のデプロイ対象 | **`epic/NewEstimate`**（現在サーバーは `snapshot-before-deploy-20260715` = `d954548cc8`） |

### 1-1 に伴う対応（新Fix 側のみ・実装済み）

同一ホストになるため、両アプリの **`XSRF-TOKEN` クッキーが互いに上書きされる**（クッキーはポートを
区別しない）。新Fix は現行を iframe で開くので、開いた直後の POST が 419 になる。

対策として **新Fix の XSRF クッキー名を env で変えられるようにした**（`SESSION_XSRF_COOKIE`）。

- 未設定なら Laravel 標準の `XSRF-TOKEN` のまま＝**本番・ローカルは影響なし**
- 検証サーバーだけ `SESSION_XSRF_COOKIE=XSRF-TOKEN-FIX2` を設定する
- **現行 felix_total 側のコード修正は不要**（現行は `XSRF-TOKEN` のまま）
- セッションクッキーはもともと別名（現行 `laravel_session` / 新Fix `laravel-session`）なので衝突しない

---

## 2. 前提と制約

| 項目 | 値 |
| --- | --- |
| 接続 | `ssh fix2-181`（= `root@192.168.10.181`、鍵認証。ホスト名 `customer-uat-fix`） |
| 新Fix 配置先 | **`/home/felix-projects/fix2`（新規に作る）**。既存の `new_felix_total` は使わない（旧世代のまま汚れているため放置） |
| 現行 felix_total | `/home/felix-projects/felix_total`（コンテナ `fix_app` / ポート 8081） |
| 新Fix の URL | http://192.168.10.181:8090（コンテナ `fix2_app`） |
| DB | 共通 MySQL コンテナ **`felix-db`**（`felix-local-dev` の compose。ホスト **3307** に公開 / MySQL 5.7.44）。コンテナからは `host.docker.internal:3307` で繋ぐ |
| メールキュー DB | `felix-db` に相乗り予定（**DB名は確定待ち**。§3.3 の `DB_*_2`） |
| サーバーのアーキテクチャ | amd64（手元が Apple Silicon なら **`--platform linux/amd64` でビルドすること**） |

`/home/itplus4/www/` は旧配置。触らない。

### 2.1 サーバーは外部ネットワークに出られない（DNS も通らない）

使えないもの:

- `git pull`（GitHub / Backlog に到達できない）
- `composer install` / `npm ci`（レジストリに到達できない）
- `docker build`（上記2つを実行するステージで失敗する）

→ **コードは `git bundle` で持ち込み、イメージは手元でビルドして `docker save` / `docker load` で運ぶ。**

### 2.2 コードはイメージに焼き込まれる ★重要

サーバーの `docker-compose.yml` に**ボリュームマウントは無い**。Dockerfile は `COPY . .` で
コードをイメージへ取り込む。したがって

```
git checkout <ブランチ> → docker compose up -d      # ← これでは新しいコードにならない
```

**サーバー上の作業ディレクトリを更新しても、起動するのはイメージ内の古いコードのまま。**
`vendor/` や `public/build/` を rsync しても同じ理由で使われない。
コードを反映する唯一の方法は **手元でビルドしたイメージを持ち込むこと**（§4-4）。

サーバー上の作業ディレクトリを更新するのは、`docker-compose.yml` / `docker.env` を最新にするため。

---

## 3. 事前に済ませること

### 3.1 felix_total（現行）を先に上げる ★必須

新Fix が依存する felix_total 側の実装（見積の新旧同期・業者承諾・発注書発行・メニュー/ロール・
請求予定データ作成など）が、サーバーの 7/15 スナップショットには入っていない。
**新テーブルを作るマイグレーションもすべて felix_total 側にある**（新Fix 側は5本だけで、
中身はリネームとバックフィル）。

旧 `fix_db` コンテナのデータベースにあった新テーブルは**前の世代**のままだった（2026-09-11 時点）。
なお `fix_db` はその後停止し、共通 MySQL の **`felix-db`（3307 / MySQL 5.7.44）** に置き換わっている。
**`felix-db` は 2026-09-11 時点で空**なので、どこにデータを持たせるか（移行するのか作り直すのか）を
先に確定させること。

| | テーブル |
| --- | --- |
| ある | `t_buildings` `t_building_cost_items` `t_cost_quotations` `t_orders` `t_invoices` `m_companies` ほか |
| **無い** | `t_building_budget_items` `t_payable_partners` `t_payable_orders` `t_billing_partners` `t_billing_quotations` `t_billing_orders` `t_building_group_statuses` `m_roles` `m_permissions` `m_menu_items` `p_user_roles` `p_role_permissions` `p_permission_menu_items` ほか |

現在の新Fix はこれらが無いと**1画面も開かない**。

### 3.2 `felix_total/.env` に追記するキー

サーバーの現行 `.env` には `CROSS_AUTH_*` も `FRAME_ANCESTOR` も**無い**。

```dotenv
CROSS_AUTH_SECRET=<新Fix と同じ値>
CROSS_AUTH_DOMAIN=                # 空（IP 運用。§1-1）
CROSS_AUTH_TTL=1800
CROSS_AUTH_SECURE=false          # http のため
FRAME_ANCESTOR=http://192.168.10.181:8090
```

`FRAME_ANCESTOR` が無いと、新Fix から iframe で開く現行画面（業者マイページ・発注書プレビュー・
見積先詳細）が**真っ白**になる。追記後は `docker exec fix_app php artisan config:clear`。

### 3.3 `fix2/docker.env` の中身

`docker.env.example` をコピーして作る。**リポジトリ同梱の例は最小限**（`APP_URL` / `APP_KEY` /
`DB_DATABASE` / `DB_USERNAME` / `DB_PASSWORD` ほか）なので、下記を足さないと現在のコードは動かない。

```dotenv
# 現行との連携
FELIX_TOTAL_URL=http://192.168.10.181:8081                # ブラウザから見た現行（iframe 用）
FELIX_TOTAL_INTERNAL_URL=http://host.docker.internal:8081 # コンテナから見た現行
# cross_auth（felix_total と同一値にする）
CROSS_AUTH_SECRET=<両アプリ共通のランダム値>
CROSS_AUTH_DOMAIN=                             # 空（IP 運用。§1-1）
CROSS_AUTH_TTL=1800
CROSS_AUTH_SECURE=false
# 現行と同一ホストのため XSRF クッキー名を分ける（§1-1。本番では設定しない）
SESSION_XSRF_COOKIE=XSRF-TOKEN-FIX2
# 業者マイページ・通知メール
MAIL_QUEUE_VENDOR_BASE_URL=http://192.168.10.181:8081
MAIL_QUEUE_OVERRIDE_TO=<検証中は自分のアドレスへ寄せる>
# メールキューの別DB（felix-db に相乗りする場合。DB名は確定待ち）
DB_HOST_2=host.docker.internal
DB_PORT_2=3307
DB_DATABASE_2=<確定したDB名>
DB_USERNAME_2=root
DB_PASSWORD_2=root
```

DB 本体（`DB_HOST` / `DB_PORT`）は `docker-compose.prod.yml` の `environment` で
`host.docker.internal:3307` に固定しているため `docker.env` には書かない
（`docker.env` に書いても compose の `environment` が優先される）。

`FELIX_TOTAL_INTERNAL_URL` は特に重要。**見積依頼・部長承認・取消は、コンテナから現行を
サーバ間 HTTP で叩く**ため、到達できないと連携が失敗する（失敗時は画面にエラーが出る）。

`docker.env` は git 管理外（`.gitignore` 済み）。clone しても付いてこないので、§4-5 で作る。

---

## 4. 手順

### 4-0.（サーバー）DB を dump する ★切り戻し用

マイグレーションは現行と共有の DB に流れる。**必ず先に取る。**

```bash
ssh fix2-181
docker exec felix-db sh -c 'mysqldump -uroot -proot --databases <DB名>' > /home/felix-projects/db_$(date +%Y%m%d_%H%M).sql
ls -lh /home/felix-projects/db_*.sql
```

### 4-1.（手元）bundle を作る

```bash
cd <fix リポジトリ>
git bundle create /tmp/new_fix.bundle develop

cd <felix_total リポジトリ>
git bundle create /tmp/felix_total.bundle epic/NewEstimate
```

### 4-2.（サーバー）felix_total を最新化してマイグレーション

**前提**: 2026-09-11 時点で現行の `fix_app` コンテナが**存在しない**（8081 が無応答）。
先に復旧してから進める。

```bash
ssh fix2-181
cd /home/felix-projects/felix_total
docker compose up -d
docker ps | grep fix_app
curl -I http://localhost:8081
```

```bash
scp /tmp/felix_total.bundle fix2-181:/home/felix-projects/
ssh fix2-181
cd /home/felix-projects/felix_total
git rev-parse --short HEAD                       # ← 切り戻し用に控える
git fetch /home/felix-projects/felix_total.bundle epic/NewEstimate:epic/NewEstimate
git checkout epic/NewEstimate
docker exec fix_app php artisan migrate           # ★ 共有DBに流れる。4-0 の dump 必須
docker exec fix_app php artisan config:clear
curl -I http://localhost:8081                     # 現行が生きているか
```

権限まわりのマイグレーションは順番がある（自動で順に流れるが、個別実行するときは注意）:

```
create_p_user_permissions_table
  → backfill_new_user_tables_from_admin_tables
  → seed_fix_menu_items_and_estimate_manager_role
  → show_order_acceptance_menu_to_estimate_manager
  → grant_all_permission_to_engineer_manager_role
```

旧世代テーブルが残っているため、**7/15 以降の差分が素直に通るかは要検証**。
落ちたらその場で止めて、dump から戻す判断をする。

続けて §3.2 のキーを `.env` へ追記し、`docker exec fix_app php artisan config:clear`。

### 4-3.（手元）新Fix のイメージをビルドする

```bash
cd <fix リポジトリ>
git checkout develop
docker build --platform linux/amd64 -t new-felix-total:$(git rev-parse --short HEAD) -t new-felix-total:latest .
docker save new-felix-total:latest | gzip > /tmp/new-felix-total-amd64.tar.gz
ls -lh /tmp/new-felix-total-amd64.tar.gz
```

Dockerfile 内で `composer install` / `npm ci` / `npm run build` が走るので、**手元はネットに繋がっていること**。
コミットハッシュのタグも付けておくと切り戻しやすい。

### 4-4.（サーバー）イメージを持ち込む

```bash
scp /tmp/new-felix-total-amd64.tar.gz fix2-181:/home/felix-projects/
scp /tmp/new_fix.bundle fix2-181:/home/felix-projects/
ssh fix2-181
docker load < /home/felix-projects/new-felix-total-amd64.tar.gz
docker images | grep new-felix-total              # 新しい latest が入ったか
```

### 4-5.（サーバー）`fix2` を作る

既存の `new_felix_total` は触らず、**bundle から clone して新しく作る**。
`vendor` / `node_modules` は不要（コードはイメージに入っているため）。

```bash
ssh fix2-181
cd /home/felix-projects
git clone -b develop /home/felix-projects/new_fix.bundle fix2
cd fix2
git log --oneline -1                              # 目的のコミットか確認
cp docker.env.example docker.env
vi docker.env                                     # §3.3 の内容にする
```

### 4-6.（サーバー）起動

```bash
cd /home/felix-projects/fix2
docker compose -f docker-compose.prod.yml up -d   # --build は付けない（サーバーではビルドできない）
docker ps | grep fix2_app
docker logs --tail 50 fix2_app
```

`docker-compose.prod.yml` はリポジトリに入っている（コンテナ名 `fix2_app` / ポート 8090 /
DB は `host.docker.internal:3307`）。サーバー上で compose を編集する必要はない。

---

## 5. 確認

```bash
curl -I http://localhost:8090                     # 200 か 302 が返ること
```

ブラウザで http://192.168.10.181:8090 を開き、次の順に見る。

| # | 確認 | 落ちたときの原因の当たり |
| --- | --- | --- |
| 1 | ログインできる | `APP_KEY` / DB 接続 |
| 2 | サイドメニューがロールどおり出る | `m_menu_items` 系のマイグレーション未適用 |
| 3 | 一覧にデータが出る | 新テーブル（`t_building_budget_items` ほか）未作成 |
| 4 | 見積先名リンク・業者マイページの iframe が表示される | `FRAME_ANCESTOR` / `CROSS_AUTH_*`（§3.2） |
| 4b | 現行を iframe で開いた**直後**にコメント送信などの POST ができる（419 にならない） | `SESSION_XSRF_COOKIE`（§1-1） |
| 5 | 見積依頼を送信できる | `FELIX_TOTAL_INTERNAL_URL` / 現行側の権限（`admin_role_permissions`） |
| 6 | 部長承認 → 現行に発注書ができる | 同上。失敗すれば画面にエラーが出る |

---

## 6. 切り戻し

```bash
# アプリ（イメージを戻す）
ssh fix2-181
cd /home/felix-projects/fix2
docker tag new-felix-total:<戻したいタグ> new-felix-total:latest
docker compose -f docker-compose.prod.yml up -d

# コードを戻す（clone なので checkout でよい）
git checkout <控えたコミット>

# 丸ごとやり直す場合は fix2 を消して 4-5 から
docker compose -f docker-compose.prod.yml down && cd .. && rm -rf fix2

# DB（マイグレーションを流して壊れた場合）
docker exec -i felix-db sh -c 'mysql -uroot -proot <DB名>' < /home/felix-projects/<dump>.sql
```

サーバーに残っている過去タグ: `new-felix-total:order-delivery-flow` / `:order-delivery-flow-0bdf511`。

---

## 7. 注意

- **DB は現行と共有**。マイグレーションは現行にも影響する（`migrations` テーブルも共通）
- `docker.env` は git 管理外。clone には含まれないので新規作成する
- ポート 8090 は新Fix 専用。8081（現行）と混同しない
- **既存の `/home/felix-projects/new_felix_total` は使わない**（旧世代のまま。消すかどうかは別途判断）
- `docker compose down` は打たない（不要に停止させない）
- サーバーの `new-felix-total:latest` は PHP 8.3.32（`composer.json` の `~8.3.0` と一致）だが、
  **`gd` / `exif` 拡張が入っていない**。現在の Dockerfile は入れるので、§4-3 でビルドし直せば解消する
  （添付画像の圧縮は `function_exists()` で握ってあるため、無くても動作自体はする）

---

## 付録. 実機調査の記録（2026-09-11）

`ssh fix2-181` で確認した内容。上の手順はこの結果を反映済み。

```
接続           ssh fix2-181 → customer-uat-fix（疎通OK）
新Fix リポジトリ HEAD e4d72a1 feature/mockData / 未コミット変更 40ファイル超
新Fix コンテナ   new_fix_app は起動していない（Up は list_app / fix_app / fix_db のみ）
イメージ        new-felix-total:latest / :order-delivery-flow / :order-delivery-flow-0bdf511
イメージの PHP  8.3.32（gd・exif 無し）
compose        volumes 無し（コードはイメージ焼き込み）
docker.env     APP_URL / APP_KEY / DB_DATABASE / DB_USERNAME / DB_PASSWORD / APP_DEBUG の6キーのみ
felix_total    d954548cc8 snapshot-before-deploy-20260715 / .env に CROSS_AUTH_* も FRAME_ANCESTOR も無し
DB             fix_db データベース（434テーブル）。新テーブルは旧世代のみ
応答           8081 → 302 / 8090 → 無応答（未起動）
```

同日夕方に環境が入れ替わり、次の状態になった。

```
felix-db      Up  mysql:5.7.44  3307→3306  network felix-shared-net  ※中身は空
felix-mailhog Up  1025 / 8025（Web UI）
fix_db        Exited            ← 旧・共有DB。停止
fix_app       存在しない        ← 現行アプリ。要復旧
```
