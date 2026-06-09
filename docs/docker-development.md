# new_felix_total Docker 開発ガイド（Windows / Mac）

`new_felix_total` を **別リポジトリ**として管理し、**Docker起動した felix_total の DB に接続**して開発・表示するための手順。

- DB は持たず、felix_total（既存）の MySQL に接続する（読み取り主体／既存DBにテーブルは作らない）。
- 接続は **`host.docker.internal:3307`**（felix_total がホストに公開している MySQL ポート）経由。felix_total 側の変更は不要。
- Windows / Mac の Docker Desktop で同じ手順で動く。

---

## 1. 前提

- Docker Desktop（Windows は WSL2 バックエンド推奨 / Mac は標準）
- **felix_total を先に docker 起動**しておくこと（MySQL がホスト `3307` に公開される）
  ```bash
  cd ../felix_total
  docker compose up -d db        # 最低限 DB だけでも可
  ```
  ※ felix_total と new_felix_total は別リポジトリでよい。場所も任意（同階層である必要はない）。

---

## 2. 初回セットアップ

```bash
# 別リポジトリとして clone した new_felix_total 内で
cp .env.example .env
docker compose up -d --build
```

初回はコンテナ内で自動的に以下が走る（`.docker/dev-entrypoint.sh`）:
- `composer install` / `npm install`（名前付きボリュームに保持）
- `.env` が無ければ生成・`APP_KEY` 生成
- `php artisan serve`（:8000）と `Vite dev server`（:5173, HMR）を同時起動

### アクセス
- アプリ: **http://localhost:8090/**
- Vite（HMR）: http://localhost:5173/（直接開く必要はない）

---

## 3. 日常操作

```bash
docker compose up -d            # 起動
docker compose logs -f app      # ログ（artisan serve + vite）
docker compose exec app bash    # コンテナに入る
docker compose exec app php artisan tinker
docker compose down             # 停止
docker compose down -v && docker compose up -d --build   # 依存を入れ直す（vendor/node_modules 再取得）
```

ソースはバインドマウントされ、Vue/Blade の変更は **HMR / 自動リロード**で即時反映される。
Windows ではファイル監視に polling を使う（`VITE_USE_POLLING=1` を compose で付与済み）。

---

## 4. DB 接続の仕組み

| 環境 | DB_HOST | DB_PORT | 備考 |
| --- | --- | --- | --- |
| docker compose（本ガイド） | `host.docker.internal` | `3307` | compose の `environment` が `.env` を上書き |
| ホストで直接開発（任意） | `127.0.0.1` | `3307` | `.env` の値をそのまま使用 |

- DB名/ユーザー/パスワードは `.env` の `DB_DATABASE` / `DB_USERNAME` / `DB_PASSWORD`（既定: `fix` / `root` / `root_password`）。
- felix_total の `.env`（`MYSQL_ROOT_PASSWORD` 等）と一致させること。
- `SESSION_DRIVER=file` / `CACHE_STORE=file` / `QUEUE_CONNECTION=sync`：**既存DBにテーブルを作らない**ための設定。

### 接続確認
```bash
docker compose exec app php artisan tinker --execute="dd(DB::select('select 1 as ok'));"
```

---

## 5. 本番（社内サーバー）

本番は事前ビルド版を使う:
```bash
cp docker.env.example docker.env   # APP_URL / APP_KEY / DB_* を記入
docker compose -f docker-compose.prod.yml up -d --build
# http://<サーバーIP>:8090/
```

---

## 6. 別リポジトリ化の手順（参考）

現状 `new_felix_total` は felix_total リポジトリ配下にある。独立リポジトリにする例:

```bash
# new_felix_total を別の場所へコピー（履歴を引き継がない場合）
cp -R new_felix_total ~/work/new_felix_total
cd ~/work/new_felix_total
rm -rf .git              # 念のため（無ければスキップ）
git init
git add .
git commit -m "chore: bootstrap new_felix_total as standalone repo"
git branch -M main
git remote add origin <新リポジトリのURL>
git push -u origin main
```

- 機密ファイルはコミットしない: `.env` / `docker.env` は `.gitignore` 済み。
- `.env.example` / `docker.env.example` はコミットしてよい（値はダミー）。
- felix_total 側は変更不要。new_felix_total 単独で `docker compose up` できる。

> 履歴ごと分離したい場合は `git subtree split` / `git filter-repo` を使う。必要なら別途案内する。

---

## 7. トラブルシュート

| 症状 | 対処 |
| --- | --- |
| DB に繋がらない（SQLSTATE[HY000] [2002]） | felix_total の DB が起動し `3307` を公開しているか確認（`docker ps`）。`host.docker.internal` が解決しているか（Win/Mac は標準、Linux は compose の `extra_hosts` で対応済み）。 |
| 画面は出るがスタイル/JSが当たらない | Vite が起動しているか（`docker compose logs -f app`）。`http://localhost:5173/` が応答するか。 |
| Windows でファイル変更が反映されない | polling 有効（`VITE_USE_POLLING=1`）を確認。重い場合は WSL2 上の Linux ファイルシステムにソースを置く。 |
| 依存が壊れた | `docker compose down -v && docker compose up -d --build` |
| ポート競合（8090/5173） | 既存プロセスを停止するか、`docker-compose.yml` の `ports` を変更。 |
