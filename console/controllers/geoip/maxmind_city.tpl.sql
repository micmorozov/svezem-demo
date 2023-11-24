DROP TABLE IF EXISTS `tmp_mm_country_codes`;

DROP TABLE IF EXISTS `tmp_mm_csv_blocks`;

DROP TABLE IF EXISTS `tmp_mm_csv_location`;

CREATE TABLE IF NOT EXISTS `tmp_mm_csv_location` (
	locId INT NOT NULL,
	country_code VARCHAR(100),
	region VARCHAR(100),
	city VARCHAR(150),
	postalCode VARCHAR(100),
	latitude VARCHAR(100),
	metroCode VARCHAR(100),
	areaCode VARCHAR(100),
	PRIMARY KEY (locId)
	-- PRIMARY KEY (city)
) ENGINE=MyISAM;

CREATE TABLE IF NOT EXISTS `tmp_mm_csv_blocks` (
	id INT NOT NULL AUTO_INCREMENT,
	startIpNum int unsigned NOT NULL,
	endIpNum int unsigned NOT NULL,
	locId int NOT NULL,
	PRIMARY KEY (id),
	KEY `locId` (`locId`)  
) ENGINE=MyISAM;

LOAD DATA LOCAL INFILE ':GeoLiteCity-Location'
INTO TABLE `tmp_mm_csv_location`
FIELDS
	TERMINATED BY ','
	ENCLOSED BY '\"'
LINES
	TERMINATED BY '\n'
IGNORE 2 LINES	
(
	@locId,
	@country_code,
	@region,
	@city,
	@postalCode,
	@latitude,
	@metroCode,
	@areaCode
)
SET
	locId			:= @locId,
	country_code	:= @country_code,
	region			:= @region,
	city			:= @city,
	postalCode		:= @postalCode,
	latitude		:= @latitude,
	metroCode		:= @metroCode,
	areaCode		:= @areaCode
;

LOAD DATA LOCAL INFILE ':GeoLiteCity-Blocks'
INTO TABLE `tmp_mm_csv_blocks`
FIELDS
	TERMINATED BY ','
	ENCLOSED BY '"'
LINES
	TERMINATED BY '\n'
IGNORE 2 LINES	
(
	@startIpNum,
	@endIpNum,
	@locId
)
SET
	id			:= NULL,
	startIpNum	:= @startIpNum,
	endIpNum	:= @endIpNum,
	locId		:= @locId
;
