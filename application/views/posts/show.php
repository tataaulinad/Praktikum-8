<?php $this->load->view('posts/header'); ?>

<a href="<?php echo base_url('posts'); ?>" class="back-link btn btn-info">← Back to List</a>

<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2><?php echo htmlspecialchars($post->title); ?></h2>
        <div class="actions">
            <a href="<?php echo base_url('posts/edit/' . $post->id); ?>" class="btn btn-warning">Edit</a>
            <a href="<?php echo base_url('posts/delete/' . $post->id); ?>" 
               class="btn btn-danger" 
               onclick="return confirm('Are you sure you want to delete this post?');">Delete</a>
        </div>
    </div>
    
    <hr style="margin: 20px 0; border: none; border-top: 2px solid #e0e0e0;">
    
    <div style="margin-bottom: 20px;">
        <p style="color: #666; font-size: 14px;">
            <strong>Author:</strong> <?php echo htmlspecialchars($post->author); ?>
        </p>
        <?php if(isset($post->created_at)): ?>
            <p style="color: #666; font-size: 14px;">
                <strong>Created:</strong> <?php echo date('F d, Y H:i', strtotime($post->created_at)); ?>
            </p>
        <?php endif; ?>
        <?php if(isset($post->updated_at) && $post->updated_at != $post->created_at): ?>
            <p style="color: #666; font-size: 14px;">
                <strong>Updated:</strong> <?php echo date('F d, Y H:i', strtotime($post->updated_at)); ?>
            </p>
        <?php endif; ?>
    </div>
    
    <?php if($post->image_url): ?>
        <div style="margin: 30px 0;">
            <img src="<?php echo $post->image_url; ?>" 
                 alt="<?php echo htmlspecialchars($post->title); ?>" 
                 class="post-detail-image">
        </div>
    <?php endif; ?>
    
    <div style="margin-top: 30px;">
        <h3 style="margin-bottom: 15px; color: #333;">Article Content</h3>
        <div style="line-height: 1.8; color: #555; white-space: pre-wrap;">
            <?php echo nl2br(htmlspecialchars($post->article)); ?>
        </div>
    </div>
</div>

<?php $this->load->view('posts/footer'); ?>
