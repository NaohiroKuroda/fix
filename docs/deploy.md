# new_felix_total 社内サーバー Docker デプロイ手順

`new_felix_total` 単体を Docker 化し、既存 felix_total の MySQL（`fix_db`）へ接続して
実装画面（実行予算一覧・詳細・ステータス管理）を社内ネットワークに公開する手順。

## 構成概要

```
[ブラウザ] ──:8090──▶ new_fix_app (PHP8.4 + Apache, Inertia/Vue)
                          │  DB_HOST=db (felix_total_default 上の alias)
                          ▼
                       fix_db (MySQL5.7 / DB:fix)   ← 既存 felix_total スタック
```

- Web: `new_fix_app` コンテナ（Apache, docroot=public）。ホスト **8090** で公開。
- DB: 既存 `fix_db` に接続。**マイグレーションは行わず**読み取り中心（session/cache/queue は file/sync）。
- ネットワーク: 既存 `felix_total_default`（`fix_db` が居る）に相乗り。

## 前提

1. サーバーで既存 felix_total の Docker スタックが起動済み（`fix_db` が Up）。
   ```bash
   docker ps | grep fix_db
   docker network ls | grep felix_total_default   # ← このネットワークが必要
   ```
   ※ ネットワーク名が異なる場合は `docker-compose.yml` の `networks.felix_shared.name` を実際の名前に変更。
2. イメージビルドにはインターネット接続が必要（base image / npm / composer / Web フォント取得）。
   オフラインサーバーの場合は「オフライン配置」を参照。

## デプロイ手順

1. `new_felix_total/` ディレクトリ一式をサーバーへ配置（git clone / rsync 等）。

2. **`docker-compose.yml` の environment を 2 箇所だけ編集**：
   - `APP_URL` を **実際のアクセスURL**に変更（重要。アセットURLに使われる）
     例: `APP_URL: "http://192.168.10.50:8090"`
   - `APP_KEY` を自前のキーに変更（推奨）
     ```bash
     # ローカルで生成して値をコピー
     docker run --rm new-felix-total:latest php artisan key:generate --show
     ```

3. ビルド & 起動：
   ```bash
   cd new_felix_total
   docker compose up -d --build
   ```

4. アクセス確認：
   ```
   http://<サーバーIP>:8090/
   ```
   - 実行予算一覧（トップ）
   - 物件名クリック → `/estimates/{id}` 詳細（新規タブ）
   - サイドメニュー「ステータス管理」→ `/status-management`

## 運用コマンド

| 操作 | コマンド |
| --- | --- |
| 起動 | `docker compose up -d` |
| 停止 | `docker compose down` |
| 再ビルド（コード更新後） | `docker compose up -d --build` |
| ログ | `docker logs -f new_fix_app` |
| コンテナ内シェル | `docker exec -it new_fix_app bash` |

## ポイント / 注意

- **APP_URL は必ずアクセスURLに合わせる**こと。`@vite` のアセットURLが絶対パス（APP_URL基準）で出力されるため、
  localhost のままサーバーIPでアクセスすると CSS/JS が 404 になる。
- ポート 8090 が使用中なら `docker-compose.yml` の `ports` を変更（例 `"8095:80"`）。合わせて APP_URL も変更。
- DB は既存 `fix_db` を共有。`new_fix_app` 側でテーブル作成・マイグレーションは行わない設計。
- 文字コードは `utf8mb4`（既存データ準拠）。

## オフライン配置（サーバーに外部接続が無い場合）

ネット接続のある端末でイメージを作って持ち込む：
```bash
# ビルド端末
cd new_felix_total
docker build -t new-felix-total:latest .
docker save new-felix-total:latest | gzip > new-felix-total.tar.gz

# サーバー
docker load < new-felix-total.tar.gz
# docker-compose.yml の build 行を消すか、--no-build で起動
docker compose up -d --no-build
```

## トラブルシュート

- 画面は出るが CSS/JS が当たらない → `APP_URL` がアクセスURLと不一致。修正後 `docker compose up -d`（再起動で config 再キャッシュ）。
- DB 接続エラー（`getaddrinfo for db failed` 等）→ `felix_total_default` に乗れていない。ネットワーク名を確認し `docker network ls`。
- 502/403 → `docker logs new_fix_app` を確認。storage 権限はエントリポイントで補正済み。
