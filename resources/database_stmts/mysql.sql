-- #!mysql
-- #{ uhc

-- #  { init
CREATE TABLE IF NOT EXISTS player_data(
    xuid VARCHAR(36) PRIMARY KEY,
    playername VARCHAR(16) NOT NULL,
    cape VARCHAR(32) NOT NULL
);
-- #  }

-- #  { loadplayer
-- #    :xuid string
SELECT
    xuid,
    playername,
    cape
FROM player_data
WHERE xuid=:xuid;
-- #  }

-- #  { register
-- #      :xuid string
-- #      :playername string
-- #      :cape string
INSERT IGNORE INTO player_data(
    xuid,
    playername,
    cape
) VALUES (
    :xuid,
    :playername,
    :cape
);
-- #    }

-- #  { update
-- #      :xuid string
-- #      :playername string
-- #      :cape string
UPDATE player_data
SET playername=:playername,
    cape=:cape
WHERE xuid=:xuid;
-- #  }
-- # }