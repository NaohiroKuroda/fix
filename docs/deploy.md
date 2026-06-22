# new_felix_total 社内サーバー Docker デプロイ手順

`new_felix_total` を既存 felix_total と **同じ階層（兄弟ディレクトリ）** に置き、felix_total の
MySQL（`fix_db`）へ接続して実装画面を社内ネットワークに **8090** で公開する。

## 配置（兄弟ディレクトリ）

```
<親ディレクトリ>/
├── felix_total/            ← 既存（サーバー稼働中。fix_db / shared-net を作成済み）
│   ├── docker-compose.yml
│   └── .env
└── new_felix_total/        ← ここに配置
    ├── docker-compose.yml  … 本アプリ用（8090 / shared-net / fix_db 接続）
    ├── docker.env          … ★各自で作成（機密。Git管理外）
    ├── docker.env.example  … テンプレート
    └── Dockerfile
```

## 構成

```
[ブラウザ] ──:8090──▶ new_fix_app (PHP8.3 + Apache, Inertia/Vue)
                          │  shared-net 経由, DB_HOST=fix_db
                          ▼
                       fix_db (MySQL5.7 / DB:fix)   ← 既存 felix_total
```

- 既存 `fix_db` が居る外部ネットワーク **`shared-net`** に相乗りして接続。
- DB認証・APP_URL 等のサイト固有値は **`docker.env`** から読む（配置場所に依存しない）。
- **マイグレーションは行わない**（session/cache/queue は file/sync）。

## 前提

```bash
docker ps | grep fix_db                 # 既存DBが Up
docker network ls | grep shared-net     # 外部ネットワークが存在
```

## デプロイ手順

> 社内サーバーが **インターネット未接続** の場合（base image / npm / composer を取得できない）は
> サーバー上でビルドできない。**ネット接続のある端末でイメージを作って持ち込む**（手順A）。
> サーバーがネット接続できる場合のみ手順B（サーバー上でビルド）。

### 重要: CPUアーキテクチャを合わせる
イメージは **サーバーと同じアーキテクチャ** でビルドすること。サーバーで確認：
```bash
uname -m          # x86_64 → amd64 / aarch64 → arm64
```
M系Macでビルドして amd64 サーバーに載せる場合は `--platform linux/amd64` が必須
（合わないと `exec format error` で起動しない）。

### 手順A: オフラインサーバー（イメージ持ち込み）★今回はこちら

1. **ネット接続のある端末**（例: 開発Mac）でイメージをビルドして保存：
   ```bash
   cd new_felix_total
   # サーバーが x86_64 の場合
   docker build --platform linux/amd64 -t new-felix-total:latest .
   docker save new-felix-total:latest | gzip > new-felix-total-amd64.tar.gz
   ```

2. `new-felix-total-amd64.tar.gz` と `new_felix_total/` 一式（docker-compose.yml / docker.env.example 等。
   `vendor` や `node_modules` は不要）をサーバーへ転送（scp / USB 等）。
   配置先は felix_total と同じ階層。

3. サーバーでイメージを読み込み：
   ```bash
   cd new_felix_total
   gunzip -c new-felix-total-amd64.tar.gz | docker load
   ```

4. `docker.env` を作成・記入：
   ```bash
   cp docker.env.example docker.env
   vi docker.env
   ```
   - **`APP_URL`**（必須）… 実アクセスURL。例 `http://192.168.10.180:8090`
   - **`DB_PASSWORD`** … felix_total の `.env` の `MYSQL_ROOT_PASSWORD` と同じ値（空なら空）
   - **`DB_DATABASE`** … ★環境によって名前が違う。必ずサーバーで確認：
     ```bash
     grep DB_DATABASE ../felix_total/.env
     # または
     docker exec fix_db mysql -uroot -p<パスワード> -e "SHOW DATABASES;"
     ```
   - `DB_USERNAME`（既定 `root`）… 通常そのまま

5. **ビルドせずに**起動（イメージは読み込み済み）：
   ```bash
   docker compose up -d --no-build
   ```

6. アクセス：`http://<サーバーIP>:8090/`

### 手順B: サーバーがネット接続できる場合

```bash
cd new_felix_total
cp docker.env.example docker.env && vi docker.env   # APP_URL / DB_PASSWORD を記入
docker compose up -d --build
```

## 運用

| 操作 | コマンド |
| --- | --- |
| 起動 / 停止 | `docker compose up -d` / `docker compose down` |
| 再ビルド（更新後） | `docker compose up -d --build` |
| ログ | `docker logs -f new_fix_app` |

## 注意点

- **APP_URL は必ずアクセスURLに合わせる**（最頻出のトラブル要因）。変更後 `docker compose up -d` で再キャッシュ。
- `docker.env` は機密（DBパスワード等）を含む。Git にコミットしない（`.gitignore` 済）。
- ポート 8090 が使用中なら compose の `ports` と `docker.env` の `APP_URL` を合わせて変更。
- `shared-net` の名称がサーバーで異なる場合は compose の `networks.shared-net.name` を実名に。
- 既存DBは共有のみ。new 側でテーブル作成・マイグレーションはしない。

## トラブルシュート

- `failed to resolve ... docker/dockerfile:1` / `registry-1.docker.io ... connection refused`
  → サーバーがオフラインでビルドしようとしている。**手順A（イメージ持ち込み + `--no-build`）** を使う。
- `exec format error` で起動しない → イメージのアーキテクチャ不一致。
  サーバーで `uname -m` を確認し、`docker build --platform linux/amd64`（または arm64）で作り直す。
- 画面は出るが CSS/JS 当たらない → `docker.env` の `APP_URL` 不一致。修正後 `docker compose up -d --no-build`。
- `getaddrinfo for fix_db failed` → `shared-net` に乗れていない／DB未起動。`docker network ls`・`docker ps` を確認。
- DB 認証エラー → `docker.env` の `DB_USERNAME`/`DB_PASSWORD` を felix_total の値に合わせる。

> 検証: ローカルで `fix_db` を一時的に `shared-net` へ接続し、本 compose + `docker.env` で起動
> → 実行予算 462件を fix_db から取得・全ルート 200 を確認済み（サーバーと同一機構）。
