DROP TABLE IF EXISTS `stealthistracker_peers`;

CREATE TABLE `stealthistracker_peers` (
  -- Current peers for torrents.
  `peer_id` TEXT NOT NULL,  -- Peer unique ID.
  `ip_address` BLOB NOT NULL, -- IP address of the client.
  `port` UNSIGNED INT NOT NULL, -- Listening port of the peer.
  `info_hash` TEXT NOT NULL, -- Info hash of the torrent.
  `bytes_uploaded` UNSIGNED INT DEFAULT NULL, -- Uploaded bytes since started.
  `bytes_downloaded` UNSIGNED INT DEFAULT NULL, -- Downloaded bytes since started.
  `bytes_left` UNSIGNED INT DEFAULT NULL, -- Bytes left to download.
  `status` TEXT NOT NULL DEFAULT 'incomplete', -- Status of the peer (seeder/leecher).
  `expires` TIMESTAMP DEFAULT NULL, -- Timestamp when peer is considered as expired.
  PRIMARY KEY (`peer_id`,`info_hash`)
);

CREATE INDEX `index_info_hash` ON `stealthistracker_peers` (`info_hash`);
CREATE INDEX `index_bytes_left` ON `stealthistracker_peers` (`bytes_left`);


DROP TABLE IF EXISTS `stealthistracker_stats`;

CREATE TABLE `stealthistracker_stats` (
  -- Table to store torrent stats.
  `info_hash` TEXT NOT NULL, -- Info hash.
  `complete` UNSIGNED INT NOT NULL, -- Number of seeders.
  `incomplete` UNSIGNED INT NOT NULL, -- Number of leechers.
  `downloaded` UNSIGNED INT NOT NULL, -- Number of downloads.
  `timestamp` TIMESTAMP DEFAULT NULL, -- Timestamp.
  PRIMARY KEY (`info_hash`)
);

DROP TABLE IF EXISTS `stealthistracker_torrents`;

CREATE TABLE `stealthistracker_torrents` (
  -- Table to store basic torrent file information upon creation.
  `info_hash` TEXT NOT NULL, -- Info hash.
  `length` UNSIGNED INT NOT NULL, -- Size of the contained file in bytes.
  `pieces_length` UNSIGNED INT NOT NULL, -- Size of one piece in bytes.
  `name` TEXT, -- Basename of the contained file.
  `pieces` BLOB NOT NULL, -- Concatenated hashes of all pieces.
  `path` TEXT NOT NULL, -- Full path of the physical file.
  `private` UNSIGNED INT NOT NULL, -- Private flag of the torrent (BEP 27).
  `url_list` BLOB NOT NULL, -- URL list of the torrent (BEP 19).
  `announce_list` BLOB NOT NULL, -- Announce list of the torrent (BEP 12).
  `nodes` BLOB NOT NULL, -- DHT nodes of the torrent (BEP 5).
  `created_by` TEXT, -- Created by.
  `status` NOT NULL DEFAULT 'active', -- Activity status of the torrent.
  PRIMARY KEY (`info_hash`)
);