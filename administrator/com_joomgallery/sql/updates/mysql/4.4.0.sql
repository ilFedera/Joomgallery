ALTER TABLE `#__joomgallery_configs` ADD `jg_category_view_subcategories_image_count` TINYINT(1) NOT NULL DEFAULT 0 AFTER `jg_category_view_subcategories_random_subimages`;
ALTER TABLE `#__joomgallery_configs` ADD `jg_category_view_browse_categories_link` TINYINT(1) NOT NULL DEFAULT 1 AFTER `jg_gallery_view_image_link`;
