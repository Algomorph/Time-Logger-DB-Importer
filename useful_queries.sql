# query for viewing duplicate entries
SELECT `activity_id`,
       `activity_type`,
       `start`,
       `end`,
       ROW_NUMBER() OVER (
           PARTITION BY `activity_type`, `start`, `end`
           ORDER BY `activity_type`, `start`, `end`
           ) row_count
FROM (SELECT `activity`.`activity_id`, dup.`activity_type`, dup.`start`, dup.`end`
      FROM `activity`
               INNER JOIN
           (SELECT `activity_type`, `start`, `end`
            FROM (SELECT `activity_type`, `start`, `end`, COUNT(*) as ct
                  FROM `activity`
                  GROUP BY `activity_type`, `start`, `end`) ga
            WHERE ga.ct > 1) dup
           ON `activity`.`activity_type` = dup.`activity_type` AND
              `activity`.`start` = dup.`start` AND
              `activity`.`end` = dup.`end`) dup1

# query to delete duplicates
DELETE `activity`
FROM `activity`
         INNER JOIN
     (SELECT `activity_id`,
             ROW_NUMBER() OVER (
                 PARTITION BY `activity_type`, `start`, `end`
                 ORDER BY `activity_type`, `start`, `end`
                 ) row_count
      FROM (SELECT `activity`.`activity_id`, dup.`activity_type`, dup.`start`, dup.`end`
            FROM `activity`
                     INNER JOIN
                 (SELECT `activity_type`, `start`, `end`
                  FROM (SELECT `activity_type`, `start`, `end`, COUNT(*) as ct
                        FROM `activity`
                        GROUP BY `activity_type`, `start`, `end`) ga
                  WHERE ga.ct > 1) dup
                 ON `activity`.`activity_type` = dup.`activity_type` AND
                    `activity`.`start` = dup.`start` AND
                    `activity`.`end` = dup.`end`) dup1) dup2
     ON dup2.`activity_id` = `activity`.`activity_id`
WHERE row_count > 1