SET AUTOCOMMIT = 0;
START TRANSACTION;

DROP VIEW IF EXISTS presentations_active_date_elements;
CREATE VIEW presentations_active_date_elements AS (
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

COMMIT;
