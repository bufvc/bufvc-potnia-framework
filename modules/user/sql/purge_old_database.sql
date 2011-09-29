# Purge users with no data
DELETE FROM 
    User 
USING
    User 
LEFT JOIN 
    Orders ON Orders.off_air_rep_id = User.id
WHERE 
    ifnull(data, '') IN('', 'MIGRATED', 'a:1:{s:9:"page_size";s:2:"10";}')
    AND Orders.id is NULL
;
