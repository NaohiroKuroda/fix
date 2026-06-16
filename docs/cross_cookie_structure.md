# 仕組み解説：なぜセッション共有 SSO に 5 つの設定が要るのか

fix と felix_total で「**1 回ログインすれば両方とも認証済み**」（＝ iframe で felix_total の
`/admin` 画面がそのまま開く）を実現するために、次の設定をそろえる。

```env
# 両アプリ（fix / felix_total）に同一で設定
APP_KEY=（同一）
SESSION_DRIVER=database          # or redis（= 共有ストア）
SESSION_CONNECTION=（同一の共有接続）
SESSION_COOKIE=felix_admin_session
SESSION_DOMAIN=.felix-japan.co.jp
# ＋（認証とは別問題）iframe 用の frame-ancestors ヘッダ
```

本書は「**なぜ各設定が要るのか**」を Laravel のセッション/Cookie の仕組みから説明する。
結論を先に言うと：**別々の 2 アプリが「同じブラウザの同じログイン状態」を共有して読むための条件**を、
1 つずつ満たしているだけ。

---

## 1. 大前提：Laravel のログインは「Cookie」と「サーバ側ストア」の二層

多くの人が「ログイン状態は Cookie に入っている」と思っているが、Laravel は違う。
**2 つの場所**に分かれている。

```
┌─────────────────────────┐         ┌──────────────────────────────┐
│ ブラウザの Cookie        │         │ サーバ側のセッションストア     │
│  名前: felix_admin_...   │         │ (file / DB sessions / Redis) │
│  値  : 暗号化された       │ ──ID──▶ │  キー: セッションID            │
│        「セッションID」    │         │  値  : { login_admin_<hash>  │
│  （APP_KEY で暗号化）     │         │          => admin_users.id } │
└─────────────────────────┘         └──────────────────────────────┘
        ↑ ブラウザが毎リクエスト送る           ↑ 実際のログイン状態はこっち
```

- **Cookie に入っているのは「セッションID」という整理番号だけ**（しかも APP_KEY で暗号化）。
- **「誰がログインしているか」という実データはサーバ側ストアにある**
  （`login_admin_<hash> => ユーザーID` という形。§ 認証ドキュメント参照）。

### リクエストのたびに起きていること（1 アプリ内）

```
1. ブラウザが Cookie（暗号化されたセッションID）を送る
2. アプリが APP_KEY で復号して「セッションID」を取り出す
3. そのIDで「セッションストア」を引く
4. ストアの中の login_admin_<hash> = 5 を読む
5. 「ユーザーID 5 でログイン中」と判定し、provider で admin_users.id=5 を取得
```

**この 1〜5 の連鎖が、別アプリ（felix_total）でも成立すれば SSO になる。**
5 つの設定は、この連鎖の各リンクを「2 アプリ間でつなぐ」ためのもの。

---

## 2. 各設定の理由（仕組み＋「やらないとどうなるか」）

fix でログインしたブラウザが felix_total にアクセスしたとき、felix_total が上の 1〜5 を
たどれるようにする。各設定がどのリンクを担うかで見ると分かりやすい。

### ① `APP_KEY` を一致させる — 「Cookie を復号できるようにする」（連鎖の 2）

- セッションID の Cookie は **APP_KEY で暗号化・署名**されている（`EncryptCookies` の働き）。
- felix_total が **別の APP_KEY** だと、fix が作った Cookie を**復号できない**。
- → felix_total から見ると「壊れた/読めない Cookie」＝**セッションID を取り出せない＝未ログイン扱い**。
- **だから両アプリで同じ APP_KEY**。これで相手の Cookie を復号して中のIDを読める。

> やらないと：iframe を開いても felix_total はログインを認識できず、ログイン画面に飛ばされる。

### ② セッションストアを共有する（`SESSION_DRIVER`＋`SESSION_CONNECTION`）— 「同じIDで同じ実データを引けるようにする」（連鎖の 3〜4）

- Cookie から取り出せるのは **セッションID（整理番号）だけ**。実データはストアにある。
- 現状は両アプリとも **`SESSION_DRIVER=file`**。file はそのアプリの `storage/` 内にファイルとして保存するので、
  **アプリ（コンテナ）ごとに完全に別物**。
- すると、fix の Cookie（ID）を felix_total に渡しても、**felix_total の file ストアにはその ID の
  データが無い** → 「知らないセッション＝空＝未ログイン」。
- → ストアを**両アプリで同じ場所**にする必要がある：
  - **共有 DB の `sessions` テーブル**（両者 `SESSION_DRIVER=database`＋同じ `SESSION_CONNECTION`）、または
  - **共有 Redis**。
- これで「fix が書いたログイン状態」を felix_total が**同じIDで引き当てられる**。

> やらないと：Cookie/ドメイン/鍵が全部合っていても、相手のストアに実体が無いので未ログインのまま。
> **ここが SSO で一番見落とされやすい。Cookie を共有しても“箱（ストア）”を共有しないと意味がない。**

### ③ `SESSION_COOKIE` 名を一致（かつ一意に）させる — 「相手が Cookie を見つけられるようにする」（連鎖の 1）

- アプリは「**決まった名前の Cookie**」からセッションID を読む。felix_total が `felix_admin_session` を
  探すのに、fix が別名で発行していたら、felix_total は**該当 Cookie が無い＝未ログイン**。
- → 両アプリで **同じ名前**にする。
- さらに名前は **一意**にする（既定の `laravel_session` を避ける）。理由は §3 の SESSION_DOMAIN と
  セットで、同じ親ドメイン上の**他の Laravel と Cookie 名が衝突して相手を壊さない**ため。
  （`felix_total`・`fix`・`felix-list` はいずれも既定なら `laravel_session` で被る。）

> やらないと：名前不一致なら SSO 不成立。名前が `laravel_session` のまま親ドメインに広げると、
> 同ドメインの他 Laravel のログインを巻き込んで壊す恐れ。

### ④ `SESSION_DOMAIN=.felix-japan.co.jp` — 「Cookie を別サブドメインにも送らせる」（連鎖の 1 の前提）

- そもそも Cookie が felix_total に**送られなければ**、①〜③以前に話が始まらない。
- Cookie には**届く範囲（Domain 属性）**がある：
  - 既定（`SESSION_DOMAIN=null`）= **host-only Cookie**。発行したホスト（例 `budget.felix-japan.co.jp`）
    にしか送られない → **`felix.felix-japan.co.jp` には送られない**。
  - `SESSION_DOMAIN=.felix-japan.co.jp` = **親ドメイン Cookie**。`*.felix-japan.co.jp` の
    **どのサブドメインにも送られる** → fix と felix_total の両方に届く。
- → 両サブドメインで Cookie を共有するため、**共通の親ドメイン**を指定する。
- 補足（SameSite）：fix と felix_total は **same-site（同じ `felix-japan.co.jp`）** なので、
  `SameSite=Lax` のままでも **iframe 内のリクエストに Cookie が送られる**。別ドメイン同士なら
  `SameSite=None; Secure` が必要だが、今回は不要。

> やらないと：host-only のままだと felix_total に Cookie 自体が届かず、SSO は原理的に不成立。

### ⑤ `frame-ancestors`（CSP）— ★これだけは「認証」ではなく「埋め込み許可」の話

- ①〜④は全部「**felix_total に認証を認識させる**」ための設定。
- ⑤は別問題で「**felix_total の画面を、別オリジンの fix の iframe に表示してよいか**」という
  **ブラウザの埋め込み制御**。
- 既定や laravel-admin は `X-Frame-Options: SAMEORIGIN` を返しがちで、**別サブドメインの iframe では
  ブラウザが表示をブロック**する（認証が通っていても画面が出ない）。
- → felix_total の `/admin` 応答に
  `Content-Security-Policy: frame-ancestors 'self' https://budget.felix-japan.co.jp` を付け、
  fix からの埋め込みを**明示的に許可**する。

> やらないと：ログインは通っているのに、iframe が真っ白／「表示できません」になる。
> **「認証（①〜④）」と「埋め込み許可（⑤）」は別の関門**で、両方そろって初めて画面が出る。

---

## 3. 全部そろうと、こう動く（SSO 成立時の 1 リクエスト）

```
[ユーザーは budget.felix-japan.co.jp（fix）にログイン済み]
   └ ブラウザに Cookie: felix_admin_session=<暗号化セッションID>（Domain=.felix-japan.co.jp）

fix 画面の明細リンク → モーダル iframe が
   src = https://felix.felix-japan.co.jp/admin/estimate-unit-companies/123/edit/?iframe=on を読み込む
        │
        ▼  ブラウザは Domain=.felix-japan.co.jp の Cookie を felix_total にも送る（④, SameSite=Lax ③）
   felix_total が受信
     1. Cookie 名 felix_admin_session を探す（③ 名前一致）
     2. APP_KEY で復号してセッションIDを得る（① 鍵一致）
     3. 共有ストアをそのIDで引く（② ストア共有）
     4. login_admin_<hash> = 5 を読む → admin ガードで admin_users.id=5 として認証済み
        │
        ▼  さらにブラウザの埋め込みチェック
     5. CSP frame-ancestors が budget... を許可（⑤）→ iframe に描画OK
   → fix の画面上に、felix_total の編集画面が「ログイン済み」で表示される
```

①〜④のどれか 1 つでも欠けると 4 で「未ログイン」、⑤が欠けると 5 で「表示ブロック」。

---

## 4. 早見表：省くと何が起きるか

| 設定 | 担う役割（連鎖） | 省くと起きる症状 |
| --- | --- | --- |
| ① `APP_KEY` 一致 | Cookie を復号（2） | 相手が Cookie を読めず未ログイン → ログイン画面へ |
| ② 共有ストア | 同じIDで実データを引く（3-4） | Cookie は届くのに実体が無く未ログイン（最頻の罠） |
| ③ `SESSION_COOKIE` 一致＋一意 | Cookie を見つける（1） | 名前不一致で SSO 不成立／`laravel_session` だと他Laravelを破壊 |
| ④ `SESSION_DOMAIN=.親` | Cookie を相手に届かせる（1の前提） | host-only で相手に届かず原理的に不成立 |
| ⑤ `frame-ancestors` | iframe 埋め込み許可（認証とは別） | 認証は通るが iframe が真っ白／表示拒否 |

---

## 5. まとめ：2 つの関門を分けて理解する

- **関門A＝認証（①〜④）**：felix_total に「このブラウザは admin としてログイン済み」と
  認識させる。そのために *Cookie が届き（④）/ 名前が分かり（③）/ 復号でき（①）/ 同じ実データを引ける（②）* を全部満たす。
- **関門B＝埋め込み許可（⑤）**：認証とは無関係に、ブラウザが「別オリジンの画面を iframe に出してよいか」を
  チェックする。これを CSP で許可する。

この 2 つは独立しているので、**両方そろって初めて**「fix の画面上に felix_total の画面がログイン済みで開く」。

---

## 6. 関連ドキュメント

- `docs/architecture/authentication.md` … 認証アーキテクチャ全体（ガード/トークン/SSO）
- `docs/plans/iframe-embed-auth-plan.md` … 本 SSO ＋ iframe 埋め込みの実装手順
- `docs/plans/auth-integration-plan.md` … fix への admin 認証移植
