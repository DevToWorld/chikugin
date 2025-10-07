-- Seed/merge CMS texts into page_contents for multiple pages (non-destructive)
-- Usage:
--   psql "postgresql://user:pass@host:port/db?sslmode=require" -f laravel-backend/scripts/seed_cms_texts.sql

-- 1) navigation (texts only)
WITH defaults AS (
  SELECT
    'navigation'::text AS page_key,
    jsonb_build_object(
      'texts', '{
        "nav_about":"私たちについて",
        "nav_seminars":"セミナー",
        "nav_publications":"刊行物",
        "nav_information":"各種情報",
        "nav_membership":"入会案内",
        "nav_premium_benefits":"プレミアム会員の特典",
        "nav_news":"お知らせ",
        "nav_company":"会社概要",
        "nav_faq":"よくある質問",
        "nav_indicators":"経済指標",
        "nav_statistics":"経済・調査統計",
        "nav_membership_standard":"スタンダード",
        "nav_membership_premium":"プレミアム"
      }'::jsonb
    ) AS content_defaults,
    'ナビゲーション'::text AS title
)
, ins AS (
  INSERT INTO page_contents(page_key, title, content, is_published, published_at, created_at, updated_at)
  SELECT page_key, title, content_defaults, true, now(), now(), now()
  FROM defaults
  ON CONFLICT (page_key) DO NOTHING
  RETURNING 1
)
UPDATE page_contents p
SET content = (
              jsonb_set(
                COALESCE(p.content::jsonb, '{}'::jsonb),
                '{texts}',
                (SELECT content_defaults->'texts' FROM defaults)
                || COALESCE((p.content::jsonb)->'texts','{}'::jsonb),
                true
              )
            )::json,
    updated_at = now()
FROM defaults
WHERE p.page_key = defaults.page_key;

-- 2) footer (texts only)
WITH defaults AS (
  SELECT
    'footer'::text AS page_key,
    jsonb_build_object(
      'texts', '{
        "link_home":"トップページ",
        "link_company":"会社概要",
        "link_about":"私たちについて",
        "link_news":"お知らせ",
        "link_faq":"よくある質問",
        "link_contact":"お問い合わせ",
        "link_seminar":"セミナー",
        "link_publication":"刊行物",
        "link_glossary":"用語集",
        "link_membership":"入会案内",
        "link_membership_standard":"スタンダード",
        "link_membership_premium":"プレミアム",
        "link_cri_consulting":"CRI 経営コンサルティング",
        "link_member_login":"会員ログイン",
        "link_legal":"特定商取引法に関する表記",
        "link_privacy":"プライバシーポリシー",
        "link_terms":"利用規約",
        "link_sitemap":"サイトマップ"
      }'::jsonb
    ) AS content_defaults,
    'フッター'::text AS title
)
, ins AS (
  INSERT INTO page_contents(page_key, title, content, is_published, published_at, created_at, updated_at)
  SELECT page_key, title, content_defaults, true, now(), now(), now()
  FROM defaults
  ON CONFLICT (page_key) DO NOTHING
  RETURNING 1
)
UPDATE page_contents p
SET content = (
              jsonb_set(
                COALESCE(p.content::jsonb, '{}'::jsonb),
                '{texts}',
                (SELECT content_defaults->'texts' FROM defaults)
                || COALESCE((p.content::jsonb)->'texts','{}'::jsonb),
                true
              )
            )::json,
    updated_at = now()
FROM defaults
WHERE p.page_key = defaults.page_key;

-- 3) publications (texts only)
WITH defaults AS (
  SELECT
    'publications'::text AS page_key,
    jsonb_build_object(
      'texts', '{
        "page_title":"刊行物",
        "page_subtitle":"publications",
        "cta_primary":"お問い合わせはこちら",
        "cta_secondary":"入会はこちら",
        "show_more":"さらに表示",
        "all_categories":"すべてのカテゴリ",
        "join_to_download":"入会してダウンロード",
        "loading":"読み込み中..."
      }'::jsonb
    ) AS content_defaults,
    '刊行物'::text AS title
)
, ins AS (
  INSERT INTO page_contents(page_key, title, content, is_published, published_at, created_at, updated_at)
  SELECT page_key, title, content_defaults, true, now(), now(), now()
  FROM defaults
  ON CONFLICT (page_key) DO NOTHING
  RETURNING 1
)
UPDATE page_contents p
SET content = (
              jsonb_set(
                COALESCE(p.content::jsonb, '{}'::jsonb),
                '{texts}',
                (SELECT content_defaults->'texts' FROM defaults)
                || COALESCE((p.content::jsonb)->'texts','{}'::jsonb),
                true
              )
            )::json,
    updated_at = now()
FROM defaults
WHERE p.page_key = defaults.page_key;

-- 4) contact (texts only)
WITH defaults AS (
  SELECT
    'contact'::text AS page_key,
    jsonb_build_object(
      'texts', '{
        "page_title":"お問い合わせ",
        "page_subtitle":"contact",
        "form_title":"お問い合わせ",
        "contact_title":"株式会社ちくぎん地域経済研究所",
        "contact_label":"About us",
        "contact_subtitle":"様々な分野の調査研究を通じ、企業活動などをサポートします。",
        "contact_cta":"お問い合わせはこちら",
        "form_label_subject":"件名",
        "form_placeholder_select":"選択してください",
        "form_option_inquiry":"サービスに関するお問い合わせ",
        "form_option_membership":"会員に関するお問い合わせ",
        "form_option_seminar":"セミナーに関するお問い合わせ",
        "form_option_other":"その他",
        "form_label_name":"お名前",
        "form_label_kana":"ふりがな（全角ひらがな）",
        "form_label_company":"会社名",
        "form_label_position":"役職",
        "form_label_phone":"電話番号",
        "form_label_email":"メールアドレス",
        "form_label_content":"お問い合わせ内容",
        "form_privacy_note":"弊社のプライバシーポリシー（個人情報保護方針）に同意をされる場合は、下記のチェックボックスにチェックを入れてください。",
        "form_privacy_agree":"個人情報保護方針に同意します",
        "form_button_confirm":"確認する"
      }'::jsonb
    ) AS content_defaults,
    'お問い合わせ'::text AS title
)
, ins AS (
  INSERT INTO page_contents(page_key, title, content, is_published, published_at, created_at, updated_at)
  SELECT page_key, title, content_defaults, true, now(), now(), now()
  FROM defaults
  ON CONFLICT (page_key) DO NOTHING
  RETURNING 1
)
UPDATE page_contents p
SET content = (
              jsonb_set(
                COALESCE(p.content::jsonb, '{}'::jsonb),
                '{texts}',
                (SELECT content_defaults->'texts' FROM defaults)
                || COALESCE((p.content::jsonb)->'texts','{}'::jsonb),
                true
              )
            )::json,
    updated_at = now()
FROM defaults
WHERE p.page_key = defaults.page_key;

-- 5) glossary (texts only)
WITH defaults AS (
  SELECT
    'glossary'::text AS page_key,
    jsonb_build_object(
      'texts', '{
        "page_title":"用語集",
        "page_subtitle":"glossary",
        "download_pdf":"PDFダウンロード"
      }'::jsonb
    ) AS content_defaults,
    '用語集'::text AS title
)
, ins AS (
  INSERT INTO page_contents(page_key, title, content, is_published, published_at, created_at, updated_at)
  SELECT page_key, title, content_defaults, true, now(), now(), now()
  FROM defaults
  ON CONFLICT (page_key) DO NOTHING
  RETURNING 1
)
UPDATE page_contents p
SET content = (
              jsonb_set(
                COALESCE(p.content::jsonb, '{}'::jsonb),
                '{texts}',
                (SELECT content_defaults->'texts' FROM defaults)
                || COALESCE((p.content::jsonb)->'texts','{}'::jsonb),
                true
              )
            )::json,
    updated_at = now()
FROM defaults
WHERE p.page_key = defaults.page_key;

-- 6) cri-consulting (texts + htmls)
WITH defaults AS (
  SELECT
    'cri-consulting'::text AS page_key,
    jsonb_build_object(
      'texts', '{
        "page_title":"CRI 経営コンサルティング",
        "page_subtitle":"consulting",
        "achievements_item1_date":"2025.6.27",
        "achievements_item1_category":"カテゴリー",
        "achievements_item1_title":"分析（新たな課題認識）",
        "achievements_item2_date":"2025.6.27",
        "achievements_item2_category":"カテゴリー",
        "achievements_item2_title":"分析（新たな課題認識）",
        "achievements_item3_date":"2025.6.27",
        "achievements_item3_category":"カテゴリー",
        "achievements_item3_title":"分析（新たな課題認識）",
        "achievements_item4_date":"2025.6.27",
        "achievements_item4_category":"カテゴリー",
        "achievements_item4_title":"分析（新たな課題認識）",
        "achievements_note":"このほか『Hot Information』の配信や『経営参考BOOK』の配布も定期的に行っております。",
        "cta_primary":"お問い合わせはこちら",
        "cta_secondary":"入会はこちら"
      }'::jsonb,
      'htmls', '{
        "what_content_body":"",
        "duties_intro":"",
        "duties_list":"",
        "achievements_item1_desc":"",
        "achievements_item2_desc":"",
        "achievements_item3_desc":"",
        "achievements_item4_desc":""
      }'::jsonb
    ) AS content_defaults,
    'CRI 経営コンサルティング'::text AS title
)
, ins AS (
  INSERT INTO page_contents(page_key, title, content, is_published, published_at, created_at, updated_at)
  SELECT page_key, title, content_defaults, true, now(), now(), now()
  FROM defaults
  ON CONFLICT (page_key) DO NOTHING
  RETURNING 1
)
UPDATE page_contents p
SET content = (
              jsonb_set(
                jsonb_set(
                  COALESCE(p.content::jsonb, '{}'::jsonb),
                  '{texts}',
                  (SELECT content_defaults->'texts' FROM defaults)
                  || COALESCE((p.content::jsonb)->'texts','{}'::jsonb),
                  true
                ),
                '{htmls}',
                (SELECT content_defaults->'htmls' FROM defaults)
                || COALESCE((p.content::jsonb)->'htmls','{}'::jsonb),
                true
              )
            )::json,
    updated_at = now()
FROM defaults
WHERE p.page_key = defaults.page_key;
