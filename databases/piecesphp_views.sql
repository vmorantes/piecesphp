SET AUTOCOMMIT = 0;
START TRANSACTION;

-- Módulo de artículos

CREATE VIEW pcsphp_articles_view AS (
	SELECT 
		main.id,
		sub.id AS sub_id,
		main.author,
		main.category,
		sub.lang,
		sub.title,
		sub.friendly_url,
		sub.content,
		sub.seo_description,
		main.folder,
		main.visits,
		main.images,
		sub.meta,
		main.start_date,
		main.end_date,
		main.created,
		main.updated
	FROM pcsphp_articles AS main
	INNER JOIN pcsphp_articles_content AS sub
	ON sub.content_of = main.id
);

COMMIT;
