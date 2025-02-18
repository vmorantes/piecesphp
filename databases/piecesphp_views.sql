SET AUTOCOMMIT = 0;
START TRANSACTION;

DROP VIEW IF EXISTS publications_active_date_elements;
CREATE VIEW publications_active_date_elements AS (
    SELECT
        pe.id,
        pe.startDate,
        pe.endDate,
        pe.status,
        UNIX_TIMESTAMP(NOW()) AS nowDate
    FROM publications_elements AS pe
    HAVING
        (UNIX_TIMESTAMP(pe.startDate) <= nowDate OR pe.startDate IS NULL) AND
        (UNIX_TIMESTAMP(pe.endDate) > nowDate OR pe.endDate IS NULL)
);
DROP VIEW IF EXISTS news_active_date_elements;
CREATE VIEW news_active_date_elements AS (
    SELECT
        pe.id,
        pe.startDate,
        DATE_ADD(pe.endDate, INTERVAL 15 DAY) AS endDate,
        pe.endDate AS realEndDate,
        pe.status,
        UNIX_TIMESTAMP(NOW()) AS nowDate
    FROM news_elements AS pe
    HAVING
        (UNIX_TIMESTAMP(pe.startDate) <= nowDate OR pe.startDate IS NULL) AND
        (UNIX_TIMESTAMP(DATE_ADD(pe.endDate, INTERVAL 15 DAY)) > nowDate OR pe.endDate IS NULL)
);
DROP VIEW IF EXISTS image_repository_images_view;
CREATE VIEW image_repository_images_view AS (
    SELECT
        YEAR(img.captureDate) AS imageYear,
        (SELECT lc.name FROM locations_cities AS lc WHERE lc.id = img.city) AS cityName,
        (SELECT lc.state FROM locations_cities AS lc WHERE lc.id = img.city) AS stateID,
        (SELECT ls.name FROM locations_states AS ls WHERE ls.id = stateID) AS stateName,
        img.*
    FROM image_repository_images AS img
);
DROP VIEW IF EXISTS built_in_banner_active_date_elements;
CREATE VIEW built_in_banner_active_date_elements AS (
    SELECT
        bibe.id,
        bibe.startDate,
        bibe.endDate,
        bibe.status,
        UNIX_TIMESTAMP(NOW()) AS nowDate
    FROM built_in_banner_elements AS bibe
    HAVING
        (UNIX_TIMESTAMP(bibe.startDate) <= nowDate OR bibe.startDate IS NULL) AND
        (UNIX_TIMESTAMP(bibe.endDate) > nowDate OR bibe.endDate IS NULL)
);
COMMIT;
