
جدول boss_settings
CREATE TABLE boss_settings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  boss_id INT NOT NULL,
  setting_key VARCHAR(100) NOT NULL,
  setting_value VARCHAR(100) NOT NULL,
  UNIQUE (boss_id, setting_key),
  FOREIGN KEY (boss_id) REFERENCES boss(boss_id)
);


جدولsettings_master


CREATE TABLE settings_master (
  setting_key VARCHAR(100) PRIMARY KEY,
  label VARCHAR(255),
  default_value VARCHAR(100)
);


كود اضافة الستنج للبوس السابقسن
INSERT INTO boss_settings (id_boss, setting_key, setting_value)
SELECT b.id_boss, sm.setting_key, sm.default_value
FROM boss b
CROSS JOIN settings_master sm
WHERE NOT EXISTS (
  SELECT 1 FROM boss_settings bs
  WHERE bs.id_boss = b.id_boss AND bs.setting_key = sm.setting_key
);



