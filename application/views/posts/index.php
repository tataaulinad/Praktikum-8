<?php $this->load->view('posts/header'); ?>

<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2>All Posts</h2>
        <a href="<?php echo base_url('posts/create'); ?>" class="btn btn-success">+ Create New Post</a>
    </div>
    
    <?php if(empty($posts)): ?>
        <div class="text-center" style="padding: 40px;">
            <p style="font-size: 18px; color: #666;">No posts found. Create your first post!</p>
            <a href="<?php echo base_url('posts/create'); ?>" class="btn btn-primary" style="margin-top: 20px;">Create Post</a>
        </div>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th style="width: 50px;">ID</th>
                    <th style="width: 100px;">Image</th>
                    <th>Title</th>
                    <th>Author</th>
                    <th style="width: 250px;">Article Preview</th>
                    <th style="width: 200px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($posts as $post): ?>
                    <tr>
                        <td><?php echo $post->id; ?></td>
                        <td>
                            <?php if($post->image_url): ?>
                                <img src="<?php echo $post->image_url; ?>" alt="<?php echo htmlspecialchars($post->title); ?>" class="post-image">
                            <?php else: ?>
                                <span style="color: #999;">No image</span>
                            <?php endif; ?>
                        </td>
                        <td><strong><?php echo htmlspecialchars($post->title); ?></strong></td>
                        <td><?php echo htmlspecialchars($post->author); ?></td>
                        <td>
                            <div class="article-preview">
                                <?php echo htmlspecialchars(substr($post->article, 0, 100)); ?>
                                <?php echo strlen($post->article) > 100 ? '...' : ''; ?>
                            </div>
                        </td>
                        <td>
                            <div class="actions">
                                <a href="<?php echo base_url('posts/show/' . $post->id); ?>" class="btn btn-info">View</a>
                                <a href="<?php echo base_url('posts/edit/' . $post->id); ?>" class="btn btn-warning">Edit</a>
                                <a href="<?php echo base_url('posts/delete/' . $post->id); ?>" 
                                   class="btn btn-danger" 
                                   onclick="return confirm('Are you sure you want to delete this post?');">Delete</a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php $this->load->view('posts/footer'); ?>
