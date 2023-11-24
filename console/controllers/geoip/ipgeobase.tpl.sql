DROP TABLE IF EXISTS `tmp_ipgeobase_cidr_location`;

DROP TABLE IF EXISTS `tmp_ipgeobase_cidr_city`;

CREATE TABLE IF NOT EXISTS `tmp_ipgeobase_cidr_location` (
  `startIpNum` int(10) unsigned NOT NULL,
  `endIpNum` int(10) unsigned NOT NULL,
  `countryCode` varchar(2) default NULL,
  `cityId` int(10) default NULL,
   KEY `cityId` (`cityId`),
   KEY `countryCode` (`countryCode`)  
) ENGINE=MyISAM;

CREATE TABLE IF NOT EXISTS `tmp_ipgeobase_cidr_city` (
	cityId int(10) AUTO_INCREMENT,
	cityName VARCHAR(150),
  oblName VARCHAR(150),
  okrugName VARCHAR(150),
  latitude double,
  longitude double,
	PRIMARY KEY (cityId),
	KEY `cityName` (`cityName`)
) ENGINE=MyISAM;

LOAD DATA LOCAL INFILE ':IPGeoBase_Cities'
INTO TABLE `tmp_ipgeobase_cidr_city`
CHARACTER SET cp1251
FIELDS
  TERMINATED BY "\t"
  ENCLOSED BY ""
LINES
  TERMINATED BY "\n"
(
  @cityId,
  @cityName,
  @obl,
  @okrug,
  @latitude,
  @longitude
)
SET
  cityId  := @cityId,
  cityName:= @cityName,
  oblName := @obl,
  okrugName:= @okrug,
  latitude := @latitude,
  longitude := @longitude
;

LOAD DATA LOCAL INFILE ':IPGeoBaseCitiesLocation'
INTO TABLE `tmp_ipgeobase_cidr_location`
CHARACTER SET cp1251
FIELDS
  TERMINATED BY "\t"
  ENCLOSED BY ""
LINES
  TERMINATED BY "\n"
(
  @startIpNum,
  @endIpNum,
  @ipRange,
  @countryCode,
  @cityId
)
SET
  startIpNum := @startIpNum,
  endIpNum := @endIpNum,
  countryCode := @countryCode,
  cityId := IF(@cityId="-",0,@cityId)
;
