
<?php if (count($view['items']) > 1) : ?>
    <i class="somc-subpages-sort fa fa-sort-alpha-desc" data-sort="asc" data-postId="<?php echo $view['post_id'] ?>"></i>
<?php endif; ?>

<div class="somc-subpages-clear"></div>

<ul>
<?php foreach ($view['items'] as $item) : ?>
    
    <li class="somc-subpages-item">
        
        <?php if ($item->thumbnail_url) : ?>
            <a href="<?php echo $item->permalink; ?>">
                <img src="<?php echo $item->thumbnail_url; ?>" alt="post thumbnail">
            </a>
            <div class="somc-subpages-title-with-image">
                <a href="<?php echo $item->permalink; ?>" data-postParentId="<?php echo $item->post_parent; ?>"><?php echo $item->post_short_title; ?></a>
            </div>
        <?php else : ?>
            <div class="somc-subpages-title">
                <a href="<?php echo $item->permalink; ?>" data-postParentId="<?php echo $item->post_parent; ?>"><?php echo $item->post_short_title; ?></a>
            </div>
        <?php endif; ?>
        
        <div class="somc-subpages-clear"></div>
        
        <?php if (count($item->items) > 0) : ?>
            <i class="somc-subpages-expand fa fa-plus-square"></i>
            <div class="somc-subpages-wrapper">
                <?php self::viewListSubpages($item->id, $item->items); ?>
            </div>
            <div class="somc-subpages-clear"></div>
        <?php endif; ?>
        
    </li>
    
    <?php if (!$item->is_last) : ?>
        <li class="somc-subpages-separator"></li>
    <?php endif; ?>
    
<?php endforeach; ?>
    
</ul>