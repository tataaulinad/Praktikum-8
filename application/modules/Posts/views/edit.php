<?php $this->load->view('posts/header'); ?>

<a href="<?php echo base_url('posts'); ?>" class="back-link btn btn-info">← Back to List</a>

<div class="card">
    <h2>Edit Post</h2>
    <hr style="margin: 20px 0; border: none; border-top: 2px solid #e0e0e0;">
    
    <?php echo form_open_multipart('posts/update/' . $post->id); ?>
        
        <div class="form-group">
            <label for="title">Title</label>
            <input type="text" 
                   name="title" 
                   id="title" 
                   value="<?php echo set_value('title', $post->title); ?>">
        </div>
        
        <div class="form-group">
            <label for="author">Author</label>
            <input type="text" 
                   name="author" 
                   id="author" 
                   value="<?php echo set_value('author', $post->author); ?>">
        </div>
        
        <div class="form-group">
            <label for="article">Article</label>
            <textarea name="article" id="article"><?php echo set_value('article', $post->article); ?></textarea>
        </div>
        
        <div class="form-group">
            <label for="image">Image</label>
            
            <?php if($post->image_url): ?>
                <div style="margin-bottom: 10px;">
                    <p style="margin-bottom: 10px; font-weight: 500;">Current Image:</p>
                    <img src="<?php echo $post->image_url; ?>" 
                         alt="Current image" 
                         style="max-width: 200px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                </div>
            <?php endif; ?>
            
            <input type="file" 
                   name="image" 
                   id="image" 
                   accept="image/jpeg,image/jpg,image/png">
            <small style="display: block; margin-top: 5px; color: #666;">
                Allowed formats: JPG, JPEG, PNG. Maximum size: 2MB
                <?php if($post->image_url): ?>
                    <br>Leave empty to keep current image
                <?php endif; ?>
            </small>
        </div>
        
        <div style="display: flex; gap: 10px; margin-top: 30px;">
            <button type="submit" class="btn btn-success">Update Post</button>
            <a href="<?php echo base_url('posts'); ?>" class="btn btn-danger">Cancel</a>
        </div>
        
    <?php echo form_close(); ?>
</div>

<?php $this->load->view('posts/footer'); ?>
