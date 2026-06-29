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
use Joomla\CMS\Router\Route;

extract($displayData);

$columns        = max(1, min((int) $num_columns, 6));
$gridAttributes = $layout == 'masonry' ? 'uk-grid="masonry: pack"' : 'uk-grid';
$captionClass   = 'uk-text-' . ($caption_align == 'right' ? 'right' : ($caption_align == 'center' ? 'center' : 'left'));

/**
 * Layout variables
 * -----------------
 * @var   string   $layout          Layout selection (columns, masonry, justified)
 * @var   array    $items           List of objects that are displayed in a grid layout (properties: id, title, thumbnail)
 * @var   int      $num_columns     Number of columns of this layout
 * @var   string   $image_type      The imagetype used for the grid
 * @var   string   $image_class     Class to be added to the image box
 * @var   string   $caption_align   Alignment class for the caption
 * @var   string   $description     Category description
 * @var   bool     $random_image    True, if a random inage should be loaded (only for categories)
 */
?>

<div class="uk-margin-large" itemscope="" itemtype="https://schema.org/ImageGallery">
  <div class="uk-grid uk-grid-match uk-child-width-1-2@s uk-child-width-1-<?php echo $columns; ?>@m" <?php echo $gridAttributes; ?>>
    <?php foreach($items as $key => $item) : ?>
      <?php
        $img_type = $image_type;

        if($item->thumbnail == 0 && $random_image)
        {
          $item->thumbnail = $item->id;
          $img_type        = 'rnd_cat:' . $image_type;
        }
      ?>

      <div class="uk-panel">
        <div class="uk-card uk-card-default uk-card-hover uk-overflow-hidden">
          <div class="uk-card-media-top uk-cover-container uk-height-medium<?php if($image_class && $layout != 'justified') : ?> uk-padding-small<?php endif; ?>" tabindex="0">
          <img src="<?php echo JoomHelper::getImg($item->thumbnail, $img_type); ?>" alt="<?php echo $this->escape($item->title); ?>" itemprop="image" itemscope="" itemtype="https://schema.org/image" uk-cover<?php if( $layout != 'justified') : ?> loading="lazy"<?php
                    endif; ?>>
          <a class="uk-position-cover" href="<?php echo Route::_(JoomHelper::getViewRoute('category', (int) $item->id)); ?>">
            <?php if($layout == 'justified') : ?>
              <div class="uk-position-bottom uk-overlay uk-overlay-primary uk-transition-slide-bottom-small <?php echo $captionClass; ?>">
                <?php echo $this->escape($item->title); ?>
              </div>
            <?php endif; ?>
          </a>
          </div>
        <?php if($layout != 'justified') : ?>
          <div class="uk-card-body uk-padding-small uk-text-meta <?php echo $captionClass; ?>" style="font-size: 17px;">
            <a href="<?php echo Route::_(JoomHelper::getViewRoute('category', (int) $item->id)); ?>">
              <?php echo $this->escape($item->title); ?>
            </a>
          </div>
          <?php if($description) : ?>
            <?php echo $item->description; ?>
          <?php endif; ?>
        <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>
