-- 既存の経済指標データをデータベースに移行するSQLスクリプト
-- 現在のVueファイルのgetIndicatorData()メソッドから抽出したデータ

-- まず、既存のデータをクリア（必要に応じて）
-- DELETE FROM economic_indicator_data;
-- DELETE FROM economic_indicators;

-- 景気カテゴリーの指標データ
INSERT INTO economic_indicators (name, category, description, source, frequency, unit, link_url, sort_order) VALUES
-- 景気
('GDP速報', 'keiki', '国内総生産の速報値', '内閣府', 'quarterly', '', 'https://www.esri.cao.go.jp/jp/sna/data/data_list/sokuhou/gaiyou/gaiyou_top.html', 1),
('景気動向指数(全国)', 'keiki', '全国の景気動向を示す指数', '内閣府', 'monthly', '', 'https://www.esri.cao.go.jp/jp/stat/di/di.html', 2),
('景気動向指数(福岡県)', 'keiki', '福岡県の景気動向を示す指数', '福岡県', 'monthly', '', 'https://www.pref.fukuoka.lg.jp/contents/keiki.html', 3),
('日銀短観(全国)', 'keiki', '全国の企業短期経済観測調査', '日本銀行', 'quarterly', '', 'https://www.boj.or.jp/statistics/dl/tankan/index.htm/', 4),
('日銀短観(九州)', 'keiki', '九州の企業短期経済観測調査', '日本銀行 福岡支店', 'quarterly', '', 'https://www.boj.or.jp/statistics/dl/tankan/index.htm/', 5),
('景気ウォッチャー調査(全国、九州)', 'keiki', '景気の現状と先行きに関する調査', '内閣府', 'monthly', '', 'https://www5.cao.go.jp/keizai3/watcher/index.html', 6),
('福岡県内の経済情勢報告', 'keiki', '福岡県内の経済情勢に関する報告', '福岡財務支局', 'quarterly', '', 'https://www.mof.go.jp/fukuoka/', 7),
('福岡市の地場企業経営動向調査', 'keiki', '福岡市の地場企業の経営動向調査', '福岡商工会議所', 'quarterly', '', 'https://www.fukunet.or.jp/', 8),
('北九州市の地場企業経営動向調査', 'keiki', '北九州市の地場企業の経営動向調査', '北九州商工会議所', 'quarterly', '', 'https://www.kitaq.or.jp/', 9),
('久留米市の地場企業景況調査', 'keiki', '久留米市の地場企業の景況調査', '久留米商工会議所', 'quarterly', '', 'https://www.kurume-cci.or.jp/', 10),

-- 個人消費
('商業販売額速報(全国)', 'kojin-shohi', '全国の商業販売額の速報', '経済産業省', 'monthly', '', 'https://www.meti.go.jp/statistics/tyo/syougyo/index.html', 1),
('大型小売店業態別販売額(全国)', 'kojin-shohi', '全国の大型小売店の業態別販売額', '経済産業省', 'monthly', '', 'https://www.meti.go.jp/statistics/tyo/syougyo/index.html', 2),
('大型小売店業態別販売額(九州、福岡県)', 'kojin-shohi', '九州・福岡県の大型小売店の業態別販売額', '九州経済産業局', 'monthly', '', 'https://www.kyushu.meti.go.jp/statistics/index.html', 3),
('コンビニエンスストア・専門量販店販売額(九州)', 'kojin-shohi', '九州のコンビニエンスストア・専門量販店販売額', '九州経済産業局', 'monthly', '', 'https://www.kyushu.meti.go.jp/statistics/index.html', 4),
('消費動向調査(全国)', 'kojin-shohi', '全国の消費動向に関する調査', '内閣府', 'monthly', '', 'https://www.esri.cao.go.jp/jp/stat/shouhi/index.html', 5),
('消費活動指数', 'kojin-shohi', '消費活動の動向を示す指数', '日本銀行', 'monthly', '', 'https://www.boj.or.jp/statistics/dl/boj_fx/index.htm/', 6),

-- 投資
('建築着工統計(全国、福岡県)', 'toshi', '建築着工に関する統計', '国土交通省', 'monthly', '', 'https://www.mlit.go.jp/toukei/toukei.html', 1),
('公共工事請負金額(福岡県)', 'toshi', '福岡県の公共工事請負金額', '福岡県', 'monthly', '', 'https://www.pref.fukuoka.lg.jp/contents/construction.html', 2),
('機械受注統計(全国)', 'toshi', '全国の機械受注に関する統計', '内閣府', 'monthly', '', 'https://www.esri.cao.go.jp/jp/stat/di/di.html', 3),
('設備投資計画調査(全国、九州)', 'toshi', '設備投資計画に関する調査', '日本政策投資銀行', 'annual', '', 'https://www.dbj.jp/', 4),

-- 貿易
('貿易統計(九州、福岡県)', 'boeki', '九州・福岡県の貿易に関する統計', '門司税関', 'monthly', '', 'https://www.customs.go.jp/toukei/info/index.htm', 1),

-- 生産
('鉱工業生産指数(全国)', 'seisan', '全国の鉱工業生産指数', '経済産業省', 'monthly', '', 'https://www.meti.go.jp/statistics/tyo/iip/index.html', 1),
('鉱工業生産指数(九州)', 'seisan', '九州の鉱工業生産指数', '九州経済産業局', 'monthly', '', 'https://www.kyushu.meti.go.jp/statistics/index.html', 2),
('鉱工業生産指数(福岡県)', 'seisan', '福岡県の鉱工業生産指数', '福岡県', 'monthly', '', 'https://www.pref.fukuoka.lg.jp/contents/keiki.html', 3),

-- 雇用・所得
('完全失業率(全国)', 'koyou-shotoku', '全国の完全失業率', '総務省', 'monthly', '%', 'https://www.stat.go.jp/data/roudou/index.html', 1),
('有効求人倍率(全国、福岡県)', 'koyou-shotoku', '全国・福岡県の有効求人倍率', '厚生労働省', 'monthly', '倍', 'https://www.mhlw.go.jp/toukei/list/114-1.html', 2),
('毎月勤労統計調査地方調査(福岡県の所得・雇用実態)', 'koyou-shotoku', '福岡県の所得・雇用実態に関する調査', '福岡県', 'monthly', '', 'https://www.pref.fukuoka.lg.jp/contents/roudou.html', 3),

-- 金融
('企業倒産状況(全国、福岡県)', 'kinyuu', '全国・福岡県の企業倒産状況', '東京商工リサーチ', 'monthly', '', 'https://www.tsr-net.co.jp/news/statistics/', 1),
('日経平均株価', 'kinyuu', '日経平均株価の動向', '東京商工リサーチ', 'daily', '円', 'https://www.nikkei.com/markets/kabu/', 2),

-- 海外指標
('米ドル対円相場', 'kaigai-shihyou', '米ドル対円の為替相場', '日本銀行', 'daily', '円', 'https://www.boj.or.jp/statistics/dl/boj_fx/index.htm/', 1),
('ユーロ対円相場', 'kaigai-shihyou', 'ユーロ対円の為替相場', '日本銀行', 'daily', '円', 'https://www.boj.or.jp/statistics/dl/boj_fx/index.htm/', 2)

ON CONFLICT (name, category) DO NOTHING;
