<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Crudjs_model extends CI_Model {

    private $table = 'posts';

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    /**
     * Get all records
     */
    public function get_all()
    {
        $query = $this->db->get($this->table);
        $records = $query->result();
        
        // Add image_url to each record
        foreach ($records as $record) {
            if ($record->image) {
                $record->image_url = 'http://localhost/ci3_project/uploads/posts/' . $record->image;
            } else {
                $record->image_url = null;
            }
        }
        
        return $records;
    }

    /**
     * Get record by ID
     */
    public function get_by_id($id)
    {
        $query = $this->db->get_where($this->table, array('id' => $id));
        $record = $query->row();
        
        if ($record && $record->image) {
            $record->image_url = 'http://localhost/ci3_project/uploads/posts/' . $record->image;
        } else if ($record) {
            $record->image_url = null;
        }
        
        return $record;
    }

    /**
     * Insert new record
     */
    public function insert($data)
    {
        $this->db->insert($this->table, $data);
        return $this->db->insert_id();
    }

    /**
     * Update record
     */
    public function update($id, $data)
    {
        $this->db->where('id', $id);
        return $this->db->update($this->table, $data);
    }

    /**
     * Delete record
     */
    public function delete($id)
    {
        $this->db->where('id', $id);
        return $this->db->delete($this->table);
    }

    /**
     * Check if record exists
     */
    public function exists($id)
    {
        $query = $this->db->get_where($this->table, array('id' => $id));
        return $query->num_rows() > 0;
    }
}