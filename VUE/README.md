# ちくぎん地域経済研究所 — 本番移行手順書（Xserver VPS / ConoHa / PostgreSQL）

この手順書だけで、現状のテスト環境から Xserver VPS または ConoHa へ完全移行できるようにまとめています。初めてこのシステムを見る方でも手順通りに作業すれば移行できるよう、前提の確認から DNS、サーバー構築、アプリ配置、メール設定、API 切替、データ移行、動作確認、運用までを順序立てて記載しています。絵文字は使用していません。

## 構成と前提

- フロントエンド: Vue.js 2.x（Vue CLI 4）／ビルド成果物は `dist/`
- バックエンド: Laravel 10（`laravel-backend/`）
- データベース: PostgreSQL（本番は PostgreSQL を利用します）
- 推奨構成: フロント= Xserver 共有ホスティング（静的配信）, API= ConoHa または Xserver VPS（Nginx + PHP-FPM + PostgreSQL）
- ドメイン例: `www.example.com`（フロント） / `api.example.com`（API）

ディレクトリ概要
- `src/` フロントのソース
- `dist/` フロントのビルド出力
- `laravel-backend/` Laravel API 一式
- `deploy/konoha/` ConoHa向け Nginx/PHP-FPM サンプル

## 必要要件

- Xserver: FTP/SFTP で `dist/` をアップできる権限、.htaccess 有効、独自ドメイン + SSL
- ConoHa または Xserver VPS: Ubuntu 22.04 など、Nginx、PHP 8.2、Composer 2、PostgreSQL 14+、git（任意）

事前準備（推奨）
- フロント用ドメインと API 用サブドメインを取得し、DNS を設定します。
  - 例: `www.example.com` → Xserver、`api.example.com` → ConoHa または Xserver VPS のグローバル IP へ A レコードを向けます。
- SSL をフロント・API それぞれのサーバーで有効化します（Let’s Encrypt 等）。
- 現行テスト環境（Railway 等）の DB バックアップ取得方法を確認します（PostgreSQL 推奨）。

## 本番手順の全体像（チェックリスト）

1) フロント・API のドメインを確定し、DNS を設定します。
2) API サーバー（ConoHa または Xserver VPS）を構築し、PostgreSQL をインストールして初期化します（手順 A）。
3) Laravel API を配置し、環境変数を設定、マイグレーション／シーディングを実行します（手順 A）。
4) フロントをビルドし、Xserver に `dist/` を配置、.htaccess を設定します（手順 B）。
5) フロントの API 接続先を本番 API ドメインに切り替えます（`src/config/api.js` 参照）。
6) 画像/PDF 配信の整合性（/storage）を調整します（`PUBLIC_STORAGE_URL` または .htaccess リライト）。
7) メール送信（Xserver の SMTP など）の環境変数を設定し、送信テストを行います。
8) スケジューラ（cron）とキューワーカー（queue:work）を有効化します。
9) 既存データがある場合は、Railway などのテスト DB から PostgreSQL に移行します（付録参照）。

---

## 手順 A: API（Laravel + PostgreSQL）をデプロイ

以下は ConoHa を例に記載します。Xserver VPS でも同様に実施できます（相違点は「手順 A-2」参照）。

1. サーバー準備（Ubuntu 22.04 の例）
```bash
sudo apt update && sudo apt upgrade -y
sudo apt install -y nginx php8.2 php8.2-fpm \
  php8.2-mbstring php8.2-xml php8.2-curl php8.2-zip php8.2-gd \
  php8.2-pgsql unzip git curl
sudo apt install -y postgresql postgresql-contrib
# Composer
cd /usr/local/bin && sudo curl -sS https://getcomposer.org/installer | sudo php -- --install-dir=/usr/local/bin --filename=composer
```

2. PostgreSQL 初期設定
```bash
sudo systemctl enable postgresql --now
sudo -u postgres psql <<'SQL'
CREATE ROLE cri WITH LOGIN PASSWORD 'strong_password_here';
CREATE DATABASE cri OWNER cri ENCODING 'UTF8';
ALTER ROLE cri SET client_encoding TO 'utf8';
ALTER ROLE cri SET timezone TO 'Asia/Tokyo';
SQL
```
DB サーバーとアプリが同一ホストの場合はこのままで構いません。別ホストに分ける場合は `postgresql.conf` と `pg_hba.conf` の調整とファイアウォール設定が必要になります。

3. コード配置
```bash
sudo mkdir -p /var/www/cri-app && sudo chown -R $USER:www-data /var/www/cri-app
cd /var/www/cri-app
# いずれか（git clone か SFTPアップロード）
git clone <this-repo-url> .
cd laravel-backend
composer install --no-dev --optimize-autoloader
cp .env.example .env
php artisan key:generate
```

4. .env 設定（代表値）
`laravel-backend/.env` を編集。最低限以下を設定してください。
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://api.example.com
LOG_CHANNEL=stack
LOG_LEVEL=info

# CORS（フロントの本番ドメインを許可）
CORS_ALLOWED_ORIGINS=https://www.example.com

# ストレージURL（絶対URLで返す）
PUBLIC_STORAGE_URL=https://api.example.com/storage
# 共有ホスティング等でシンボリックリンクが使えない場合は true
PUBLIC_DISK_IN_PUBLIC=false

# DB: PostgreSQL の例（デフォルト）
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=cri
DB_USERNAME=cri
DB_PASSWORD=your_password
DB_SSLMODE=prefer

# DB: MySQL を使う場合はこちら
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=cri
# DB_USERNAME=cri
# DB_PASSWORD=your_password

# Queue / Cache / Session
QUEUE_CONNECTION=database
CACHE_DRIVER=file
SESSION_DRIVER=file
SESSION_LIFETIME=120

# メール（XserverのSMTPを使う例）
MAIL_MAILER=smtp
MAIL_HOST=svXXX.xserver.jp
MAIL_PORT=465
MAIL_ENCRYPTION=ssl
# 587/TLS を使う場合
# MAIL_PORT=587
# MAIL_ENCRYPTION=tls
MAIL_USERNAME=info@your-domain.jp
MAIL_PASSWORD=mailbox_password
MAIL_FROM_ADDRESS=info@your-domain.jp
MAIL_FROM_NAME="ちくぎん地域経済研究所"
MAIL_EHLO_DOMAIN=your-domain.jp

# 送信を一時停止したい場合（テスト用）
# MAIL_DRY_RUN=true

# 代替: Resend を使う場合
# MAIL_MAILER=resend
# RESEND_API_KEY=your_resend_api_key
```

5. ストレージとDB初期化
```bash
cd /var/www/cri-app/laravel-backend
php artisan storage:link
php artisan migrate --force
php artisan db:seed --force
```
初期管理者（Seeder）
- Admin（API管理画面用）
  - `admin@chikugin-cri.co.jp` / `admin123`（初回ログイン後に必ず変更）
  - `editor@chikugin-cri.co.jp` / `editor123`
  - `viewer@chikugin-cri.co.jp` / `viewer123`

6. パーミッション
```bash
sudo chown -R www-data:www-data storage bootstrap/cache
sudo find storage -type d -exec chmod 775 {} \;
sudo find storage -type f -exec chmod 664 {} \;
```

7. Nginx 設定
- サンプル: `deploy/konoha/nginx.conf`
  - `root` を `.../laravel-backend/public` に変更
  - `server_name` を `api.example.com` に変更
```bash
sudo cp deploy/konoha/nginx.conf /etc/nginx/sites-available/cri-app
sudo ln -s /etc/nginx/sites-available/cri-app /etc/nginx/sites-enabled/cri-app
sudo nginx -t && sudo systemctl reload nginx
```

8. PHP-FPM 調整（任意）
- サンプル: `deploy/konoha/php-fpm-pool.conf`
```bash
sudo systemctl restart php8.2-fpm
```

9. スケジューラ/キュー
```bash
# 毎分スケジューラ（メール配信などのジョブ投入）
crontab -e
* * * * * cd /var/www/cri-app/laravel-backend && php artisan schedule:run >> /dev/null 2>&1

# キューワーカー（常駐）
# Supervisor例: /etc/supervisor/conf.d/laravel-worker.conf を作成
# [program:laravel-worker]
# command=php /var/www/cri-app/laravel-backend/artisan queue:work --queue=default --sleep=3 --tries=3 --max-time=3600
# autostart=true
# autorestart=true
# user=www-data
# redirect_stderr=true
# stdout_logfile=/var/log/supervisor/laravel-worker.log
# 反映
# sudo supervisorctl reread && sudo supervisorctl update && sudo supervisorctl start laravel-worker:*
```

10. 動作確認
```bash
curl -s https://api.example.com/api/health | jq
curl -s https://api.example.com/api/test | jq
```

### 手順 A-2: Xserver VPS で API を運用する場合

基本手順は上記と同じです。違いは以下の点です。
- OS イメージは Xserver VPS が提供する標準イメージを利用します（Ubuntu 系が推奨です）。
- ファイアウォールやセキュリティグループの開放設定を Xserver VPS のコントロールパネルで有効化します（HTTP/HTTPS）。
- Nginx/PHP/PostgreSQL のインストールと設定は同様です。Let’s Encrypt の証明書発行方法は Xserver VPS のガイドに従ってください。
- 共有ホスティングではないため、`PUBLIC_DISK_IN_PUBLIC=true` は通常不要です。`php artisan storage:link` が問題なく動作します。

---

## 手順 B: Xserver へフロント（Vueビルド）をデプロイ

1. フロントのビルド
```bash
npm ci
# API先を本番に切り替える（2つの方法のどちらか）
# A) `src/config/api.js` の production.baseURL を https://api.example.com に変更
# B) 環境変数方式に改修した場合: VUE_APP_API_URL=https://api.example.com を設定してビルド
npm run build
```

2. Xserver にアップロード
- サーバーパネルで対象ドメインの「公開フォルダ（ドキュメントルート）」を確認
- `dist/` の中身をその公開フォルダ直下へアップロード（SFTP/FTP）

3. .htaccess を配置（SPA 用 + /storage リライト）
`deploy/xserver/.htaccess` を公開フォルダ直下に置くか、以下内容を作成してください。
```apache
RewriteEngine On
RewriteBase /

# APIが返す相対パス /storage/... をAPIへ転送
RewriteRule ^storage/(.*)$ https://api.example.com/storage/$1 [L,PT]

# SPA の履歴リライト
RewriteRule ^index\.html$ - [L]
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule . /index.html [L]
```
注: API側で `PUBLIC_STORAGE_URL=https://api.example.com/storage` を設定していれば、返却URLが絶対URLになり、上記 /storage リライトは不要です（どちらでも可）。

4. SSL と動作確認
- Xserver で独自SSLを有効化し、`https://www.example.com` にアクセス
- 管理ログイン: `https://www.example.com/#/admin`（ハッシュモード）
- 管理ログイン後は API の `POST /api/admin/login` が成功することを確認

---

## メール送信（本番）

本プロジェクトは SMTP（例: Xserver）、Resend、SendGrid、Gmail API（制限環境用）に対応します。最小構成は SMTP です。

- SMTP（Xserver）の例は上記 .env を参照
- 送信テスト（管理API）: `POST /api/admin/emails/send-simple`
  - Body 例:
  ```json
  {
    "to": "your-address@example.com",
    "subject": "SMTP テスト",
    "body_text": "Hello via SMTP",
    "from": {"address": "info@your-domain.jp", "name": "ちくぎん地域経済研究所"}
  }
  ```
- うまく届かない場合の典型:
  - 530/535 認証: ユーザー名=メールアドレス、ポート/暗号化の組み合わせを再確認
  - 553 From 拒否: `MAIL_FROM_ADDRESS` が送信許可されたドメインであるか
  - タイムアウト: サーバー外向きSMTP制限→Resend/SendGrid検討

詳細なSMTPガイドは `laravel-backend/README.md` 参照。

---

## 動的データ（API）と確認手順

代表的な公開 API と想定呼び出し元を記載します。実際のルーティングは `laravel-backend/routes/api.php` を参照してください。

- 刊行物（公開）
  - 一覧: `GET /api/publications-v2`
  - カテゴリ: `GET /api/publications-v2/categories`
  - 詳細: `GET /api/publications-v2/{id}`
  - ダウンロード: `GET /api/publications-v2/{id}/download`
- お知らせ（公開）
  - 一覧: `GET /api/news-v2`
  - 詳細: `GET /api/news-v2/{id}`
- 経済指標（公開）
  - カテゴリ: `GET /api/economic-indicators/categories`
  - 一覧: `GET /api/economic-indicators`
- ページコンテンツ（公開）
  - 一覧: `GET /api/public/pages`
  - 詳細: `GET /api/public/pages/{pageKey}`

確認例
```bash
curl -s https://api.example.com/api/publications-v2 | jq '.[0] | {id,title,file_url}'
curl -s https://api.example.com/api/news-v2 | jq '.[0] | {id,title}'
```

管理 API での更新（例）
- 管理ログイン: `POST /api/admin/login`
- 刊行物の作成/更新/削除: `POST/PUT/DELETE /api/admin/publications`
- 画像置換（モデル画像）: `POST /api/admin/media/replace-model-image`

これらが正常に動作すれば、フロントの刊行物やニュースなどの動的データは自動的に反映されます。

---

## API ベースURLの切替（フロント）

- 現状（既定）: `src/config/api.js` の `production.baseURL` を本番APIへ書き換える
- 改修（推奨）: `process.env.VUE_APP_API_URL` があればそれを優先する実装に変更し、
  ビルド時に `VUE_APP_API_URL=https://api.example.com npm run build`

---

## CORS とストレージ

- CORS 許可: `laravel-backend/config/cors.php` は `CORS_ALLOWED_ORIGINS`（カンマ区切り）を参照
- 画像/PDFのURL: `PUBLIC_STORAGE_URL` を設定すると絶対URLを返すため、フロント側での `/storage` リライトが不要に

## CDN 配信（推奨）

- 目的: `/storage/...` の静的ファイル（画像・PDF）を API サーバー経由ではなく CDN から高速配信します。
- 手順:
  - CDN 側のオリジン（バックエンド）を `https://api.example.com/storage` に設定（パスベースのオリジンでも可）。
  - API 環境変数に `PUBLIC_STORAGE_URL=https://cdn.example.com/storage` を設定。
  - フロントは何も変更不要。Laravel は `Storage::disk('public')->url($path)` を通じて常に CDN の絶対URLを返します。
- 補足:
  - 既存の `vercel.json` の `/storage` リライトは、絶対URL（CDN）を返す構成では呼ばれません。残しておいても支障はありません。
  - 画像差し替え時は新しいファイル名（タイムスタンプ付き）になるため、CDNキャッシュを個別パージしなくても URL 変更で実質バイパスされます。
  - Canvas 等で画像を扱う場合は CDN 側で `Access-Control-Allow-Origin` を適切に付与してください。

---

## ローカル開発（参考）

前提
- Node.js 16+ / npm
- PHP 8.1+ / Composer

セットアップ
```bash
npm ci
cd laravel-backend && composer install && cp .env.example .env && php artisan key:generate && cd ..
```

起動
```bash
# フロント（http://localhost:8080）
npm run dev

# API（http://localhost:8000）
cd laravel-backend && php artisan serve
```

APIをローカルに向けたい場合は `src/config/api.js` の baseURL を `http://127.0.0.1:8000` に一時変更するか、
環境変数方式へ改修してください。

---

## 付録: データ移行（Railway 等 → 本番 PostgreSQL）

既存のテスト環境データを本番へ移行する場合は、PostgreSQL でダンプ・リストアを行います。MySQL を利用していた場合は `database_migration_guide.md` に従いダンプ形式の変換が必要になります。

1) テスト環境でダンプを取得します
```bash
pg_dump \
  --no-owner --no-privileges \
  --format=custom \
  --file=backup.dump \
  "$TEST_DATABASE_URL"
```

2) 本番 PostgreSQL にリストアします
```bash
# 事前に空のDBを作成済みであること（本手順Aのとおり）
pg_restore \
  --no-owner --no-privileges \
  --clean --if-exists \
  --dbname=postgresql://cri:your_password@127.0.0.1:5432/cri \
  backup.dump
```

3) Laravel マイグレーションの整合性を確認します
```bash
php artisan migrate --force
php artisan db:seed --force
```
既存レコードを壊さずに不足データだけを補うため、シーダは冪等に作成されています。必要に応じて `php artisan tinker` や管理 API でデータを確認します。

4) ストレージファイル（画像・PDF）を移行します
- 旧環境の `storage/app/public`（または公開 `public/storage`）を新環境へコピーします。
- API 側の `PUBLIC_STORAGE_URL` を設定しておくと、返却URLが絶対化されフロントのリライト設定が簡素化できます。

5) 最終確認
- フロントから動的ページ（刊行物、ニュース、セミナー等）が期待通り表示されること
- ダウンロードや画像が 200 で配信されること
- 管理画面から新規作成・更新・削除が行えること

---

## 付録: よく使う確認コマンド

- API稼働確認: `curl -s https://api.example.com/api/health | jq`
- ルート一覧: `curl -s https://api.example.com/api/debug-routes | jq`
- 権限/パス: `ls -ld laravel-backend/storage laravel-backend/bootstrap/cache`
- ジョブテーブル: `
  php artisan migrate:status | grep jobs
  `（`2019_08_19_000001_create_jobs_table.php` 等が適用済みであること）

---

## トラブルシューティング（抜粋）

- CORS エラーが出る: `laravel-backend/config/cors.php` と `CORS_ALLOWED_ORIGINS` を確認します。フロントのドメインが完全一致で列挙されている必要があります。
- 画像やPDFが表示されない: `php artisan storage:link` の実行と `PUBLIC_STORAGE_URL` の設定を確認します。Xserver 共有ホスティングでは `PUBLIC_DISK_IN_PUBLIC=true` も検討します。
- メールが届かない: ポートと暗号化（465/SSL または 587/TLS）、`MAIL_FROM_ADDRESS`、`MAIL_EHLO_DOMAIN` を確認します。Xserver の SMTP クライアント情報に合わせてください。
- 管理ログインに失敗する: シード済みの初期ユーザーでログインできない場合、`/api/debug/run-admin-seeder`（一時利用）で再投入し、直ちに無効化してください。
