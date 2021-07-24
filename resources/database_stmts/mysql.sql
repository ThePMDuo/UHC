-- #!mysql
-- #{ uhc

-- #  { data
-- #    { init
CREATE TABLE IF NOT EXISTS player_data(
    uuid VARCHAR(36) PRIMARY KEY,
    playername VARCHAR(16) NOT NULL,
    cape VARCHAR(32) NOT NULL
);
-- #  }

-- #  { loadplayer
-- #    :uuid string
SELECT
    uuid,
    playername,
    cape
FROM player_data
WHERE uuid=:uuid;
-- #  }

-- #  { register
-- #      :uuid string
-- #      :playername string
-- #      :cape string
INSERT IGNORE INTO player_data(
    uuid,
    playername,
    cape
) VALUES (
    :uuid,
    :playername,
    :cape
);
-- #    }

-- #  { update
-- #      :uuid string
-- #      :playername string
-- #      :cape string
UPDATE player_data
SET playername=:playername,
    cape=:cape
WHERE uuid=:uuid;
-- #   }
-- #  }
-- # }