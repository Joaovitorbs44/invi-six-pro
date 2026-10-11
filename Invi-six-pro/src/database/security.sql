-- The PHP backend uses direct PostgreSQL access. No public Data API access.
-- Review this separately when existing tables already have Supabase policies.
BEGIN;
DO $$
DECLARE
    table_name text;
    api_role text;
BEGIN
    FOREACH table_name IN ARRAY ARRAY[
        'users','players','access_requests','account_logs','seasons','player_stats',
        'matchmaking_queue','matches','match_players','captain_votes','draft_picks',
        'maps','map_veto','match_map_pool','logs','support_tickets','support_messages',
        'punishments','season_closures','season_final_standings','app_sessions','app_rate_limits'
    ] LOOP
        EXECUTE format('ALTER TABLE public.%I ENABLE ROW LEVEL SECURITY', table_name);
        EXECUTE format('REVOKE ALL ON TABLE public.%I FROM PUBLIC', table_name);
        FOREACH api_role IN ARRAY ARRAY['anon','authenticated'] LOOP
            IF EXISTS (SELECT 1 FROM pg_roles WHERE rolname=api_role) THEN
                EXECUTE format('REVOKE ALL ON TABLE public.%I FROM %I', table_name, api_role);
            END IF;
        END LOOP;
    END LOOP;
END $$;
COMMIT;
