<?php
/**
 * *********************************************************************************
 *    @package    com_joomgallery                                                 **
 *    @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>          **
 *    @copyright  2008 - 2025  JoomGallery::ProjectTeam                           **
 *    @license    GNU General Public License version 3 or later                   **
 * *********************************************************************************
 */

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') || die;
// phpcs:enable PSR1.Files.SideEffects

use Joomgallery\Component\Joomgallery\Administrator\Helper\JoomHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

extract($displayData);

$columns        = max(1, min((int) $num_columns, 6));
$gridAttributes = $layout == 'masonry' ? 'uk-grid="masonry: pack"' : 'uk-grid';
$captionClass   = 'uk-text-' . ($caption_align == 'right' ? 'right' : ($caption_align == 'center' ? 'center' : 'left'));
$useLightbox    = $image_link == 'lightgallery' || $title_link == 'lightgallery';

/**
 * Layout variables
 * -----------------
 * @var   int      $id                Layout id
 * @var   string   $layout            Layout selection (columns, masonry, justified)
 * @var   array    $items             List of objects that are displayed in a grid layout (id, catid, title, description, date, author)
 * @var   int      $num_columns       Number of columns of this layout
 * @var   string   $caption_align     Alignment class for the caption
 * @var   string   $image_class       Class to be added to the image box
 * @var   string   $image_type        The imagetype used for the grid
 * @var   string   $lightbox_type     The imagetype used for the lightbox
 * @var   string   $image_link        Type of link to be added to the image
 * @var   bool     $image_title       True to display the image title
 * @var   string   $title_link        Type of link to be added to the image title
 * @var   bool     $image_desc        True to display the image description
 * @var   bool     $image_desc_label  True to display the image description label
 * @var   bool     $image_date        True to display the image date
 * @var   bool     $image_author      True to display the image author
 * @var   bool     $image_tags        True to display the image tags
 */
?>

<div class="uk-margin-large" itemscope="" itemtype="https://schema.org/ImageGallery">
  <div id="lightgallery-<?php echo $id; ?>" class="uk-grid uk-grid-match uk-child-width-1-2@s uk-child-width-1-<?php echo $columns; ?>@m" <?php echo $gridAttributes; ?>>
    <?php $index = 0; ?>
    <?php foreach($items as $key => $item) : ?>
      <div class="uk-panel">
        <div class="uk-card uk-card-default uk-card-hover uk-overflow-hidden">
          <div class="uk-card-media-top uk-cover-container uk-height-medium<?php if($image_class && $layout != 'justified') : ?> uk-padding-small<?php endif; ?>" tabindex="0">

          <?php if($useLightbox) : ?>
            <img src="<?php echo JoomHelper::getImg($item, $image_type); ?>" alt="<?php echo $item->title; ?>" itemprop="image" itemscope="" itemtype="https://schema.org/image" uk-cover<?php if( $layout != 'justified') : ?> loading="lazy"<?php
                      endif; ?>>
            <a class="lightgallery-item uk-position-cover" href="#" data-src="<?php echo JoomHelper::getImg($item, $lightbox_type); ?>" data-sub-html="#uk-gallery-caption-<?php echo $item->id; ?>" data-thumb="<?php echo JoomHelper::getImg($item, $image_type); ?>">
              <?php if($image_title && $layout == 'justified') : ?>
                <div class="uk-position-bottom uk-overlay uk-overlay-primary uk-transition-slide-bottom-small <?php echo $captionClass; ?>">
                  <?php echo $this->escape($item->title); ?>
                </div>
              <?php endif; ?>
            </a>
              <?php // lightgallery image caption via data-sub-html ?>
              <?php if($image_title || $image_desc) : ?>
                <div id="uk-gallery-caption-<?php echo $item->id; ?>" style="display: none">
                  <?php if($image_title) : ?>
                    <div class="<?php echo $captionClass; ?>">
                      <?php echo $this->escape($item->title); ?>
                    </div>
                  <?php endif; ?>
                  <?php if($image_desc) : ?>
                    <div class="<?php echo $captionClass; ?>">
                      <?php echo $item->description; ?>
                    </div>
                  <?php endif; ?>
                </div>
              <?php endif; ?>
          <?php elseif($image_link == 'defaultview') : ?>
            <img src="<?php echo JoomHelper::getImg($item, $image_type); ?>" alt="<?php echo $item->title; ?>" itemprop="image" itemscope="" itemtype="https://schema.org/image" uk-cover<?php if( $layout != 'justified') : ?> loading="lazy"<?php
                      endif; ?>>
            <a class="uk-position-cover" href="<?php echo Route::_(JoomHelper::getViewRoute('image', (int) $item->id, (int) $item->catid)); ?>">
              <?php if($image_title && $layout == 'justified') : ?>
                <div class="uk-position-bottom uk-overlay uk-overlay-primary uk-transition-slide-bottom-small <?php echo $captionClass; ?>">
                  <?php echo $this->escape($item->title); ?>
                </div>
              <?php endif; ?>
            </a>
          <?php else : ?>
            <img src="<?php echo JoomHelper::getImg($item, $image_type); ?>" alt="<?php echo $item->title; ?>" itemprop="image" itemscope="" itemtype="https://schema.org/image" uk-cover<?php if( $layout != 'justified') : ?> loading="lazy"<?php
                      endif; ?>>
          <?php endif; ?>

          <?php if($layout == 'justified') : ?>
            <div class="uk-position-bottom uk-overlay uk-overlay-primary uk-transition-slide-bottom-small <?php echo $captionClass; ?>">
              <?php echo $this->escape($item->title); ?>
            </div>
          <?php endif; ?>
          </div>

        <?php if($layout != 'justified') : ?>
          <div class="uk-card-body uk-padding-small uk-text-meta <?php echo $captionClass; ?>" style="font-size: 17px;">
            <?php if($image_title) : ?>
              <?php if($title_link == 'defaultview') : ?>
                  <a href="<?php echo Route::_(JoomHelper::getViewRoute('image', (int) $item->id, (int) $item->catid)); ?>">
                    <?php echo $this->escape($item->title); ?>
                  </a>
                <?php elseif($title_link != 'lightgallery') : ?>
                  <?php echo $this->escape($item->title); ?>
                <?php endif; ?>
                <?php if($layout != 'justified' && $title_link == 'lightgallery') : ?>
                  <a href="#" class="caption-trigger-<?php echo $id; ?>" data-index="<?php echo $index; ?>">
                    <?php echo $this->escape($item->title); ?>
                  </a>
                <?php endif; ?>
            <?php endif; ?>

            <?php if($image_desc) : ?>
              <div>
              <?php if($image_desc_label) : ?>
                <?php echo Text::_('JGLOBAL_DESCRIPTION') . ': '; ?>
              <?php endif; ?>
              <?php echo $item->description; ?>
              </div>
            <?php endif; ?>
            <?php if($image_date) : ?>
              <div><?php echo Text::_('COM_JOOMGALLERY_DATE') . ': ' . HTMLHelper::_('date', $item->date, Text::_('DATE_FORMAT_LC6')); ?></div>
            <?php endif; ?>
            <?php if($image_author) : ?>
              <div><?php echo Text::_('JAUTHOR') . ': ' . $this->escape($item->author); ?></div>
            <?php endif; ?>
            <?php if($image_tags) : ?>
              <div><?php echo Text::_('COM_JOOMGALLERY_TAGS') . ': '; ?></div>
            <?php endif; ?>
          </div>
        <?php endif; ?>
        </div>
      </div>
    <?php $index++; ?>
    <?php endforeach; ?>
  </div>
</div>
