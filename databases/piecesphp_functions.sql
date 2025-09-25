-- Adminer 4.8.1 MySQL 5.5.5-10.5.13-MariaDB-1:10.5.13+maria~focal dump

SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

DELIMITER ;;

DROP FUNCTION IF EXISTS strTemplateReplace;;
CREATE FUNCTION strTemplateReplace(templateText text, replacementJSON text) RETURNS text CHARSET utf8mb4
BEGIN
    DECLARE replacementKeys TEXT;
    DECLARE replacementQty INT UNSIGNED DEFAULT 0;
    DECLARE resultText TEXT;
    DECLARE counterVar INT UNSIGNED DEFAULT 0;
    DECLARE currentKey TEXT;
    DECLARE currentValue TEXT;
    SET replacementKeys = JSON_KEYS(replacementJSON);
    SET replacementQty = JSON_LENGTH(replacementJSON);
    SET resultText = templateText;

    WHILE counterVar < replacementQty DO
        SET currentKey = JSON_UNQUOTE(
            JSON_EXTRACT(
                replacementKeys,
                CONCAT('$[', counterVar, ']')
            )
        );
        SET currentValue = JSON_UNQUOTE(
            JSON_EXTRACT(
                replacementJSON,
                CONCAT('$.', currentKey)
            )
        );
        SET resultText = REPLACE(
            resultText,
            currentKey,
            currentValue
        );
        SET counterVar = counterVar + 1;
    END WHILE;

    RETURN ( resultText );
END;;

DELIMITER ;

-- 2022-03-15 20:34:48
