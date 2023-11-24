DROP TABLE IF EXISTS `tmp_mm_country_whois`;

CREATE TABLE IF NOT EXISTS `tmp_mm_country_whois` (	
	country_code VARCHAR(100),
	country_name VARCHAR(100),	
	startIpNum int unsigned NOT NULL,
	endIpNum int unsigned NOT NULL,
	KEY `country_code` (`country_code`)	
) ENGINE=MyISAM;

LOAD DATA LOCAL INFILE ':GeoLiteCountriesWhois'
INTO TABLE `tmp_mm_country_whois`
FIELDS
	TERMINATED BY ','
	ENCLOSED BY '\"'
LINES
	TERMINATED BY '\n'
IGNORE 2 LINES	
(
	@startIpStr,
	@endIpStr,
	@startIpNum,
	@endIpNum,
	@country_code,
	@country_name
)
SET
	startIpNum		:= @startIpNum,
	endIpNum		:= @endIpNum,
	country_code	:= @country_code,
	country_name	:= @country_name
;
