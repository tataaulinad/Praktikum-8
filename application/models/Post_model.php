<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Post_model extends CI_Model {

    private $table = 'posts';

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    /**
     * Get all posts
     */
    public function get_all()
    {
        $query = $this->db->get($this->table);
        $posts = $query->result();
        
        // Add image_url to each post
        foreach ($posts as $post) {
            if (!empty($post->image)) {
                $post->image_url = 'http://localhost/ci3_project/uploads/posts/' . $post->image;
            } else {
                $post->image_url = null;
            }
        }
        
        return $posts;
    }

    /**
     * Get post by ID
     */
    public function get_by_id($id)
    {
        $query = $this->db->get_where($this->table, array('id' => $id));
        $post = $query->row();
        
        if ($post && !empty($post->image)) {
            $post->image_url = 'http://localhost/ci3_project/uploads/posts/' . $post->image;
        } else if ($post) {
            $post->image_url = null;
        }
        
        return $post;
    }

    /**
     * Insert new post
     */
    public function insert($data)
    {
        $this->db->insert($this->table, $data);
        return $this->db->insert_id();
    }

    /**
     * Update post
     */
    public function update($id, $data)
    {
        $this->db->where('id', $id);
        return $this->db->update($this->table, $data);
    }

    /**
     * Delete post
     */
    public function delete($id)
    {
        $this->db->where('id', $id);
        return $this->db->delete($this->table);
    }

    /**
     * Check if post exists
     */
    public function exists($id)
    {
        $query = $this->db->get_where($this->table, array('id' => $id));
        return $query->num_rows() > 0;
    }
}
