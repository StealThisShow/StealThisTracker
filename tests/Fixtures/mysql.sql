# --------------------------------------------------------
# Server version:               5.1.52
# Server OS:                    redhat-linux-gnu
# --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;

# Dumping structure for table stealthistracker_peers
CREATE TABLE IF NOT EXISTS `stealthistracker_peers` (
  `id` int(11) AUTO_INCREMENT PRIMARY KEY COMMENT 'ID.',
  `peer_id` binary(20) NOT NULL COMMENT 'Peer unique ID.',
  `info_hash_id` int(11) NOT NULL COMMENT 'Info Hash ID.',
  `ip_address` varbinary(16) NOT NULL COMMENT 'IP address of the client.',
  `port` smallint(20) unsigned NOT NULL COMMENT 'Listening port of the peer.',
  `bytes_uploaded` int(10) unsigned DEFAULT NULL COMMENT 'Uploaded bytes since started.',
  `bytes_downloaded` int(10) unsigned DEFAULT NULL COMMENT 'Downloaded bytes since started.',
  `bytes_left` int(10) unsigned DEFAULT NULL COMMENT 'Bytes left to download.',
  `status` enum('complete','incomplete') NOT NULL DEFAULT 'incomplete' COMMENT 'Status of the peer (seeder/leecher).',
  `expires` timestamp NULL DEFAULT NULL COMMENT 'Timestamp when peer is considered as expired.',
  KEY `Index 1` (`peer_id`,`info_hash_id`),
  KEY `Index 3` (`bytes_left`),
  KEY `Index 4` (`expires`),
  KEY `Index 5` (`info_hash_id`),
  KEY `Index 6` (`status`,`info_hash_id`),
  KEY `Index 7` (`peer_id`,`expires`,`info_hash_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Current peers for torrents.';

# Dumping structure for table stealthistracker_torrents
CREATE TABLE IF NOT EXISTS `stealthistracker_torrents` (
  `id` int(11) AUTO_INCREMENT PRIMARY KEY COMMENT 'ID.',
  `info_hash` binary(20) NOT NULL COMMENT 'Info hash.',
  `length` int(11) unsigned NOT NULL COMMENT 'Size of the contained file in bytes.',
  `pieces_length` int(11) unsigned NOT NULL COMMENT 'Size of one piece in bytes.',
  `name` varchar(255) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL COMMENT 'Basename of the contained file.',
  `pieces` mediumblob NOT NULL COMMENT 'Concatenated hashes of all pieces.',
  `path` varchar(1024) NOT NULL COMMENT 'Full path of the physical file.',
  `private` tinyint(1) NOT NULL COMMENT 'Private flag of the torrent (BEP 27).',
  `url_list` blob NOT NULL COMMENT 'URL list of the torrent (BEP 19).',
  `announce_list` blob NOT NULL COMMENT 'Announce list of the torrent (BEP 12).',
  `nodes` blob NOT NULL COMMENT 'DHT Nodes of the torrent (BEP 5).',
  `created_by` varchar(255) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL COMMENT 'Created by.',
  `status` enum('active','inactive') NOT NULL DEFAULT 'active' COMMENT 'Activity status of the torrent.',
  KEY `Index 1` (`info_hash`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Table to store basic torrent file information upon creation.';

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
