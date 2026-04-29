<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Posts extends CI_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Post_model');
        $this->load->helper(array('url', 'form'));
        $this->load->library(array('form_validation', 'upload', 'session'));
    }

    /**
     * Display all posts
     */
    public function index()
    {
        $data['posts'] = $this->Post_model->get_all();
        $data['title'] = 'All Posts';
        
        $this->load->view('posts/index', $data);
    }

    /**
     * Show create form
     */
    public function create()
    {
        $data['title'] = 'Create New Post';
        $this->load->view('posts/create', $data);
    }

    /**
     * Store new post
     */
    public function store()
    {
        // Set validation rules
        $this->form_validation->set_rules('title', 'Title', 'required|max_length[255]');
        $this->form_validation->set_rules('author', 'Author', 'required|max_length[255]');
        $this->form_validation->set_rules('article', 'Article', 'required');

        if ($this->form_validation->run() === FALSE) {
            $this->session->set_flashdata('error', validation_errors());
            redirect('posts/create');
            return;
        }

        $data = array(
            'title' => $this->input->post('title'),
            'author' => $this->input->post('author'),
            'article' => $this->input->post('article'),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        );

        // Handle image upload
        if (!empty($_FILES['image']['name'])) {
            $upload_result = $this->_upload_image();
            
            if ($upload_result['status']) {
                $data['image'] = 'posts/' . $upload_result['file_name'];
            } else {
                $this->session->set_flashdata('error', $upload_result['error']);
                redirect('posts/create');
                return;
            }
        }

        $post_id = $this->Post_model->insert($data);

        if ($post_id) {
            $this->session->set_flashdata('success', 'Post created successfully!');
            redirect('posts');
        } else {
            $this->session->set_flashdata('error', 'Failed to create post');
            redirect('posts/create');
        }
    }

    /**
     * Show single post
     */
    public function show($id)
    {
        $post = $this->Post_model->get_by_id($id);

        if (!$post) {
            show_404();
            return;
        }

        $data['post'] = $post;
        $data['title'] = $post->title;
        
        $this->load->view('posts/show', $data);
    }

    /**
     * Show edit form
     */
    public function edit($id)
    {
        $post = $this->Post_model->get_by_id($id);

        if (!$post) {
            show_404();
            return;
        }

        $data['post'] = $post;
        $data['title'] = 'Edit Post';
        
        $this->load->view('posts/edit', $data);
    }

    /**
     * Update post
     */
    public function update($id)
    {
        if (!$this->Post_model->exists($id)) {
            show_404();
            return;
        }

        // Set validation rules
        $this->form_validation->set_rules('title', 'Title', 'max_length[255]');
        $this->form_validation->set_rules('author', 'Author', 'max_length[255]');

        if ($this->form_validation->run() === FALSE) {
            $this->session->set_flashdata('error', validation_errors());
            redirect('posts/edit/' . $id);
            return;
        }

        $data = array(
            'updated_at' => date('Y-m-d H:i:s')
        );

        // Only update fields that are provided
        if ($this->input->post('title')) {
            $data['title'] = $this->input->post('title');
        }
        if ($this->input->post('author')) {
            $data['author'] = $this->input->post('author');
        }
        if ($this->input->post('article')) {
            $data['article'] = $this->input->post('article');
        }

        // Handle image upload
        if (!empty($_FILES['image']['name'])) {
            // Delete old image
            $old_post = $this->Post_model->get_by_id($id);
            if ($old_post->image) {
                $old_image_path = './uploads/' . $old_post->image;
                if (file_exists($old_image_path)) {
                    unlink($old_image_path);
                }
            }

            // Upload new image
            $upload_result = $this->_upload_image();
            
            if ($upload_result['status']) {
                $data['image'] = 'posts/' . $upload_result['file_name'];
            } else {
                $this->session->set_flashdata('error', $upload_result['error']);
                redirect('posts/edit/' . $id);
                return;
            }
        }

        $result = $this->Post_model->update($id, $data);

        if ($result) {
            $this->session->set_flashdata('success', 'Post updated successfully!');
            redirect('posts');
        } else {
            $this->session->set_flashdata('error', 'Failed to update post');
            redirect('posts/edit/' . $id);
        }
    }

    /**
     * Delete post
     */
    public function delete($id)
    {
        $post = $this->Post_model->get_by_id($id);

        if (!$post) {
            $this->session->set_flashdata('error', 'Post not found');
            redirect('posts');
            return;
        }

        // Delete image if exists
        if ($post->image) {
            $image_path = './uploads/' . $post->image;
            if (file_exists($image_path)) {
                unlink($image_path);
            }
        }

        $result = $this->Post_model->delete($id);

        if ($result) {
            $this->session->set_flashdata('success', 'Post deleted successfully!');
        } else {
            $this->session->set_flashdata('error', 'Failed to delete post');
        }

        redirect('posts');
    }

    /**
     * Private method to handle image upload
     */
    private function _upload_image()
    {
        $upload_path = './uploads/posts/';
        
        // Create directory if not exists
        if (!is_dir($upload_path)) {
            mkdir($upload_path, 0777, true);
        }

        // Sanitize filename
        $original_name = $_FILES['image']['name'];
        $sanitized_name = preg_replace('/[^A-Za-z0-9._-]/', '_', $original_name);
        $file_name = time() . '_' . $sanitized_name;

        $config['upload_path']      = $upload_path;
        $config['allowed_types']    = 'jpg|jpeg|png';
        $config['max_size']         = 2048; // 2MB
        $config['file_name']        = $file_name;
        $config['overwrite']        = FALSE;

        $this->upload->initialize($config);

        if ($this->upload->do_upload('image')) {
            $upload_data = $this->upload->data();
            return array(
                'status' => TRUE,
                'file_name' => $upload_data['file_name']
            );
        } else {
            return array(
                'status' => FALSE,
                'error' => $this->upload->display_errors('', '')
            );
        }
    }
}
