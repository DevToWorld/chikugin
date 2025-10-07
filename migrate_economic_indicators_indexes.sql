-- Unique index for ON CONFLICT (name, category)
DO $$
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM pg_indexes 
    WHERE schemaname = 'public' 
      AND indexname = 'economic_indicators_name_category_unique'
  ) THEN
    CREATE UNIQUE INDEX economic_indicators_name_category_unique 
      ON economic_indicators(name, category);
  END IF;
END $$;

