<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Post extends MX_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Post_model');
        $this->load->library('jwt');
        $this->load->library('upload');
    }

    /**
     * Parse multipart/form-data from php://input (for PUT/DELETE requests)
     */
    private function parse_multipart_form()
    {
        $content_type = $_SERVER['CONTENT_TYPE'] ?? '';
        $input = [];
        
        // If it's multipart/form-data, parse manually
        if (strpos($content_type, 'multipart/form-data') !== false) {
            $boundary = '';
            if (preg_match('/boundary=([^;]+)/', $content_type, $matches)) {
                $boundary = trim($matches[1], '"');
            }
            
            if (!$boundary) {
                return [];
            }
            
            $raw = file_get_contents('php://input');
            $parts = preg_split('/' . preg_quote("--$boundary") . '/', $raw);
            
            foreach ($parts as $part) {
                if (empty(trim($part)) || $part === "--\r\n" || $part === "--") {
                    continue;
                }
                
                // Split headers from content
                $split = preg_split("/\r\n\r\n/", trim($part), 2);
                if (count($split) !== 2) {
                    continue;
                }
                
                $headers = $split[0];
                $content = rtrim($split[1], "\r\n");
                
                // Extract field name
                if (preg_match('/name="([^"]+)"/', $headers, $matches)) {
                    $name = $matches[1];
                    
                    // Check if it's a file upload
                    if (preg_match('/filename="([^"]+)"/', $headers, $filename_matches)) {
                        // Extract Content-Type for file
                        $file_content_type = 'application/octet-stream';
                        if (preg_match('/Content-Type:\s*([^\r\n]+)/', $headers, $type_matches)) {
                            $file_content_type = trim($type_matches[1]);
                        }
                        
                        $input[$name] = [
                            'name' => $filename_matches[1],
                            'type' => $file_content_type,
                            'content' => $content
                        ];
                    } else {
                        // Regular form field
                        $input[$name] = $content;
                    }
                }
            }
            
            return $input;
        }
        
        // Fallback for x-www-form-urlencoded
        if (strpos($content_type, 'application/x-www-form-urlencoded') !== false) {
            parse_str(file_get_contents('php://input'), $input);
            return $input ?? [];
        }
        
        // Return merged POST/FILES as last resort
        return array_merge($_POST ?? [], $_FILES ?? []);
    }

    /**
     * Generate full image URL
     */
    private function get_image_url($image_filename)
    {
        if (empty($image_filename)) {
            return null;
        }
        return 'http://localhost/ci3_project/uploads/posts/' . $image_filename;
    }

    /**
     * Route handler - dispatches to appropriate method based on HTTP verb
     */
    public function handle($id = null)
    {
        $method = $_SERVER['REQUEST_METHOD'];

        if ($method === 'GET') {
            if ($id) {
                $this->show($id);
            } else {
                $this->index();
            }
        } elseif ($method === 'POST') {
            $this->store();
        } elseif ($method === 'PUT') {
            $this->update($id);
        } elseif ($method === 'DELETE') {
            $this->delete($id);
        } else {
            http_response_code(405);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'status' => false,
                'message' => 'Method not allowed'
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    /**
     * Get all posts (no auth needed)
     * GET /api/post
     */
    public function index()
    {
        header('Content-Type: application/json; charset=utf-8');
        ob_clean();

        try {
            // Get pagination params
            $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
            $per_page = isset($_GET['per_page']) ? max(1, min(100, (int)$_GET['per_page'])) : 10;
            $offset = ($page - 1) * $per_page;

            // Get posts
            $posts = $this->Post_model->get_all($per_page, $offset);
            $total = $this->Post_model->count_all();

            // Add full image URLs
            foreach ($posts as &$post) {
                if (!empty($post->image)) {
                    $post->image_url = $this->get_image_url($post->image);
                } else {
                    $post->image_url = null;
                }
            }

            http_response_code(200);
            echo json_encode([
                'status' => true,
                'message' => 'Posts retrieved successfully',
                'data' => $posts,
                'pagination' => [
                    'total' => (int)$total,
                    'per_page' => $per_page,
                    'current_page' => $page,
                    'last_page' => ceil($total / $per_page)
                ]
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            exit;

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'status' => false,
                'message' => 'Server error: ' . $e->getMessage()
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    /**
     * Get single post (no auth needed)
     * GET /api/post/{id}
     */
    public function show($id)
    {
        header('Content-Type: application/json; charset=utf-8');
        ob_clean();

        try {
            // Validate ID
            if (!is_numeric($id)) {
                http_response_code(400);
                echo json_encode([
                    'status' => false,
                    'message' => 'Invalid post ID'
                ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                exit;
            }

            // Get post
            $post = $this->Post_model->get_by_id($id);

            if (!$post) {
                http_response_code(404);
                echo json_encode([
                    'status' => false,
                    'message' => 'Post not found'
                ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                exit;
            }

            // Add full image URL
            if (!empty($post->image)) {
                $post->image_url = $this->get_image_url($post->image);
            } else {
                $post->image_url = null;
            }

            http_response_code(200);
            echo json_encode([
                'status' => true,
                'message' => 'Post retrieved successfully',
                'data' => $post
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            exit;

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'status' => false,
                'message' => 'Server error: ' . $e->getMessage()
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    /**
     * Create new post (requires auth)
     * POST /api/post
     */
    public function store()
    {
        header('Content-Type: application/json; charset=utf-8');
        ob_clean();

        try {
            // Verify authorization
            $token = $this->jwt->get_token_from_request();

            if (!$token) {
                http_response_code(401);
                echo json_encode([
                    'status' => false,
                    'message' => 'Unauthorized: No token provided'
                ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                exit;
            }

            $decoded = $this->jwt->verify($token);

            if (!$decoded) {
                http_response_code(401);
                echo json_encode([
                    'status' => false,
                    'message' => 'Unauthorized: Invalid or expired token'
                ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                exit;
            }

            // Get input data - handle both JSON and form-data
            $input = json_decode(file_get_contents('php://input'), true);
            
            // For POST with form-data, PHP auto-populates $_POST
            if (!$input && !empty($_POST)) {
                $input = $_POST;
            }

            // Validate input
            if (empty($input['title']) || empty($input['article'])) {
                http_response_code(400);
                echo json_encode([
                    'status' => false,
                    'message' => 'Title and article are required'
                ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                exit;
            }

            $post_data = [
                'title' => htmlspecialchars($input['title']),
                'author' => htmlspecialchars($input['author'] ?? 'Anonymous'),
                'article' => htmlspecialchars($input['article'])
            ];

            // Handle file upload if present
            $image_name = '';
            if (!empty($_FILES['image']['name'])) {
                $image_name = $this->_upload_image();
                if (!$image_name) {
                    http_response_code(400);
                    echo json_encode([
                        'status' => false,
                        'message' => 'Image upload failed'
                    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                    exit;
                }
                $post_data['image'] = $image_name;
            }

            // Create post
            $post_id = $this->Post_model->create($post_data);

            if (!$post_id) {
                http_response_code(500);
                echo json_encode([
                    'status' => false,
                    'message' => 'Failed to create post'
                ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                exit;
            }

            // Get created post
            $post = $this->Post_model->get_by_id($post_id);

            if (!empty($post->image)) {
                $post->image_url = $this->get_image_url($post->image);
            } else {
                $post->image_url = null;
            }

            http_response_code(201);
            echo json_encode([
                'status' => true,
                'message' => 'Post created successfully',
                'data' => $post
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            exit;

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'status' => false,
                'message' => 'Server error: ' . $e->getMessage()
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    /**
     * Update post (requires auth)
     * PUT /api/post/{id}
     */
    public function update($id)
    {
        header('Content-Type: application/json; charset=utf-8');
        ob_clean();

        try {
            // Verify authorization
            $token = $this->jwt->get_token_from_request();

            if (!$token) {
                http_response_code(401);
                echo json_encode([
                    'status' => false,
                    'message' => 'Unauthorized: No token provided'
                ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                exit;
            }

            $decoded = $this->jwt->verify($token);

            if (!$decoded) {
                http_response_code(401);
                echo json_encode([
                    'status' => false,
                    'message' => 'Unauthorized: Invalid or expired token'
                ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                exit;
            }

            // Validate ID
            if (!is_numeric($id)) {
                http_response_code(400);
                echo json_encode([
                    'status' => false,
                    'message' => 'Invalid post ID'
                ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                exit;
            }

            // Check if post exists
            if (!$this->Post_model->exists($id)) {
                http_response_code(404);
                echo json_encode([
                    'status' => false,
                    'message' => 'Post not found'
                ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                exit;
            }

            // Get input data - handle both JSON and form-data
            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) {
                $input = $this->parse_multipart_form();
                
                // If file exists in parsed data, repopulate $_FILES for upload library
                if (!empty($input['image']) && is_array($input['image']) && isset($input['image']['content'])) {
                    $_FILES['image'] = [
                        'name' => $input['image']['name'] ?? 'image_' . time() . '.png',
                        'type' => $input['image']['type'] ?? 'image/png',
                        'tmp_name' => $this->_save_temp_file($input['image']['content']),
                        'error' => 0,
                        'size' => strlen($input['image']['content'])
                    ];
                }
            }

            // Validate input
            if (empty($input['title']) || empty($input['article'])) {
                http_response_code(400);
                echo json_encode([
                    'status' => false,
                    'message' => 'Title and article are required'
                ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                exit;
            }

            $post_data = [
                'title' => htmlspecialchars($input['title']),
                'author' => htmlspecialchars($input['author'] ?? 'Anonymous'),
                'article' => htmlspecialchars($input['article'])
            ];

            // Handle file upload if present (optional for PUT)
            if (!empty($_FILES['image']['name']) || (isset($input['image']) && is_array($input['image']) && !empty($input['image']['name']))) {
                $old_post = $this->Post_model->get_by_id($id);
                
                // Delete old image
                if (!empty($old_post->image)) {
                    $old_file = FCPATH . 'uploads/posts/' . $old_post->image;
                    if (file_exists($old_file)) {
                        unlink($old_file);
                    }
                }

                $image_name = $this->_upload_image();
                if ($image_name) {
                    $post_data['image'] = $image_name;
                }
                // If image upload fails, just skip it - don't fail the whole request for PUT
            }

            // Update post
            $result = $this->Post_model->update($id, $post_data);

            if (!$result) {
                http_response_code(500);
                echo json_encode([
                    'status' => false,
                    'message' => 'Failed to update post'
                ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                exit;
            }

            // Get updated post
            $post = $this->Post_model->get_by_id($id);

            if (!empty($post->image)) {
                $post->image_url = $this->get_image_url($post->image);
            } else {
                $post->image_url = null;
            }

            http_response_code(200);
            echo json_encode([
                'status' => true,
                'message' => 'Post updated successfully',
                'data' => $post
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            exit;

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'status' => false,
                'message' => 'Server error: ' . $e->getMessage()
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    /**
     * Delete post (requires auth)
     * DELETE /api/post/{id}
     */
    public function delete($id)
    {
        header('Content-Type: application/json; charset=utf-8');
        ob_clean();

        try {
            // Verify authorization
            $token = $this->jwt->get_token_from_request();

            if (!$token) {
                http_response_code(401);
                echo json_encode([
                    'status' => false,
                    'message' => 'Unauthorized: No token provided'
                ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                exit;
            }

            $decoded = $this->jwt->verify($token);

            if (!$decoded) {
                http_response_code(401);
                echo json_encode([
                    'status' => false,
                    'message' => 'Unauthorized: Invalid or expired token'
                ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                exit;
            }

            // Validate ID
            if (!is_numeric($id)) {
                http_response_code(400);
                echo json_encode([
                    'status' => false,
                    'message' => 'Invalid post ID'
                ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                exit;
            }

            // Get post
            $post = $this->Post_model->get_by_id($id);

            if (!$post) {
                http_response_code(404);
                echo json_encode([
                    'status' => false,
                    'message' => 'Post not found'
                ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                exit;
            }

            // Delete image if exists
            if (!empty($post->image)) {
                $file = FCPATH . 'uploads/posts/' . $post->image;
                if (file_exists($file)) {
                    unlink($file);
                }
            }

            // Delete post
            $result = $this->Post_model->delete($id);

            if (!$result) {
                http_response_code(500);
                echo json_encode([
                    'status' => false,
                    'message' => 'Failed to delete post'
                ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                exit;
            }

            http_response_code(200);
            echo json_encode([
                'status' => true,
                'message' => 'Post deleted successfully'
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            exit;

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'status' => false,
                'message' => 'Server error: ' . $e->getMessage()
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    /**
     * Upload image
     */
    private function _save_temp_file($content)
    {
        $temp_path = sys_get_temp_dir() . '/ci3_upload_' . uniqid() . '.tmp';
        file_put_contents($temp_path, $content);
        return $temp_path;
    }

    private function _upload_image()
    {
        $config['upload_path'] = FCPATH . 'uploads/posts/';
        $config['allowed_types'] = 'gif|jpg|png|jpeg';
        $config['max_size'] = 2048; // 2MB
        $config['file_name'] = 'post_' . time() . '_' . uniqid();

        // Create directory if not exists
        if (!is_dir($config['upload_path'])) {
            mkdir($config['upload_path'], 0755, true);
        }

        $this->upload->initialize($config);

        if (!$this->upload->do_upload('image')) {
            return false;
        }

        $upload_data = $this->upload->data();
        return $upload_data['file_name'];
    }
}
