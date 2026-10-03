CREATE TABLE IF NOT EXISTS `site_settings` (
  `id` INT PRIMARY KEY DEFAULT 1,
  `popup_html` TEXT,
  `popup_link` VARCHAR(255),
  `is_popup_active` TINYINT(1) DEFAULT 0,
  `ad_header` TEXT,
  `ad_footer` TEXT,
  `ad_sidebar` TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO `site_settings` (id, popup_html, is_popup_active) VALUES (1, '<h3>Hoşgeldiniz!</h3>', 1);
