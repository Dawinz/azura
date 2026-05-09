-- Adds route key so PHP helpers such as generate_url('privacy_policy') resolve to privacy-policy.
-- Safe for existing databases (skips if route_key already exists).

INSERT INTO routes (route_key, route)
SELECT 'privacy_policy', 'privacy-policy'
WHERE NOT EXISTS (
    SELECT 1 FROM routes AS r WHERE r.route_key = 'privacy_policy' LIMIT 1
);
