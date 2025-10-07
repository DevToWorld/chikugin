-- 経済統計レポートの重複データ削除スクリプト
-- 各タイトルごとに最初に作成されたレコードのみを残し、重複を削除

BEGIN;

-- 重複データを削除（最初に作成されたものを残す）
WITH ranked_reports AS (
  SELECT id, title, created_at,
         ROW_NUMBER() OVER (PARTITION BY title ORDER BY created_at ASC) as rn
  FROM economic_reports
)
DELETE FROM economic_reports
WHERE id IN (
  SELECT id FROM ranked_reports WHERE rn > 1
);

-- 結果確認
SELECT 
    '削除後のレコード数' as status,
    COUNT(*) as count 
FROM economic_reports;

-- 残ったレコードの一覧
SELECT 
    id,
    title,
    category,
    year,
    publication_date,
    created_at
FROM economic_reports 
ORDER BY title, created_at;

COMMIT;
