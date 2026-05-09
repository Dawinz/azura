-- Run against the storefront MySQL used by Railway when categories lack images
-- but active physical products with images exist (backs admin/category uploads).
-- Safe to run repeatedly; only fills empty category.image.

UPDATE categories c
SET c.image = (
  SELECT img.image_small
  FROM images img
  INNER JOIN products p ON p.id = img.product_id
  WHERE p.category_id = c.id
    AND p.product_type = 'physical'
    AND p.status = 1
    AND p.visibility = 1
    AND p.is_deleted = 0
    AND p.is_draft = 0
    AND img.image_small IS NOT NULL
    AND TRIM(img.image_small) <> ''
  ORDER BY img.is_main DESC, img.id ASC
  LIMIT 1
)
WHERE c.image IS NULL OR TRIM(c.image) = '';
