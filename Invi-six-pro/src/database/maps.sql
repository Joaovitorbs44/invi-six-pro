-- Real catalog already used by the application. Preserve existing pool choices.
BEGIN;
INSERT INTO maps(name,sort_order) VALUES
('Chalet',1),('Bank',2),('Kafe Dostoyevsky',3),('Border',4),('Clubhouse',5),
('Lair',6),('Nighthaven Labs',7),('Fortress',8),('Villa',9)
ON CONFLICT(name) DO NOTHING;
COMMIT;
