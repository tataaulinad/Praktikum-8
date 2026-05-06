<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Crudjs extends CI_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Crudjs_model');
        $this->load->helper(array('url', 'form'));
        $this->load->library(array('form_validation', 'upload', 'session'));
    }

    /**
     * Debug page to check setup
     */
    public function debug()
    {
        $debug_info = array();
        
        // Check database connection
        $debug_info['database'] = array(
            'connected' => $this->db->conn_id ? 'Yes' : 'No',
            'database' => $this->db->database
        );
        
        // Check table exists
        $debug_info['table_exists'] = $this->db->table_exists('posts') ? 'Yes' : 'No';
        
        // Try to get records
        $query = $this->db->get('posts', 1);
        $debug_info['table_accessible'] = $query ? 'Yes' : 'No';
        $debug_info['record_count'] = $this->db->count_all('posts');
        
        // Check upload folder
        $upload_path = './uploads/posts/';
        $debug_info['upload_folder'] = array(
            'exists' => is_dir($upload_path) ? 'Yes' : 'No',
            'writable' => is_writable($upload_path) ? 'Yes' : 'No'
        );
        
        // Get records for testing
        $debug_info['sample_records'] = array();
        try {
            $records = $this->Crudjs_model->get_all();
            if (!empty($records)) {
                // Show first record as sample
                $first = $records[0];
                $debug_info['sample_records'] = array(
                    'count' => count($records),
                    'first_record' => $first
                );
            }
        } catch (Exception $e) {
            $debug_info['sample_records']['error'] = $e->getMessage();
        }
        
        echo '<pre>' . json_encode($debug_info, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . '</pre>';
    }

    /**
     * Display main index page
     */
    public function index()
    {
        $data['title'] = 'CRUD with AJAX';
        $this->load->view('crudjs/index', $data);
    }

    /**
     * Get all records via AJAX
     */
    public function get_all()
    {
        // Clear any previous output
        ob_clean();
        header('Content-Type: application/json; charset=utf-8');
        
        try {
            $records = $this->Crudjs_model->get_all();
            
            // Format response
            $response = array(
                'status' => 'success',
                'data' => $records
            );
            
            echo json_encode($response, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            echo json_encode(array(
                'status' => 'error',
                'message' => 'Failed to load records: ' . $e->getMessage()
            ));
        }
        exit;
    }

    /**
     * Get single record via AJAX
     */
    public function get_record($id)
    {
        ob_clean();
        header('Content-Type: application/json; charset=utf-8');
        
        try {
            if (!$this->Crudjs_model->exists($id)) {
                echo json_encode(array(
                    'status' => 'error',
                    'message' => 'Record not found'
                ));
                exit;
            }

            $record = $this->Crudjs_model->get_by_id($id);

            echo json_encode(array(
                'status' => 'success',
                'data' => $record
            ), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            echo json_encode(array(
                'status' => 'error',
                'message' => 'Failed to load record: ' . $e->getMessage()
            ));
        }
        exit;
    }

    /**
     * Store new record via AJAX
     */
    public function store()
    {
        ob_clean();
        header('Content-Type: application/json; charset=utf-8');
        
        try {
            // Set validation rules
            $this->form_validation->set_rules('title', 'Title', 'required|max_length[255]');
            $this->form_validation->set_rules('author', 'Author', 'required|max_length[255]');
            $this->form_validation->set_rules('article', 'Article', 'required');

            if ($this->form_validation->run() === FALSE) {
                echo json_encode(array(
                    'status' => 'error',
                    'message' => 'Validation error: ' . strip_tags(validation_errors())
                ));
                exit;
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
                    echo json_encode(array(
                        'status' => 'error',
                        'message' => $upload_result['error']
                    ));
                    exit;
                }
            }

            $record_id = $this->Crudjs_model->insert($data);

            if ($record_id) {
                $new_record = $this->Crudjs_model->get_by_id($record_id);
                echo json_encode(array(
                    'status' => 'success',
                    'message' => 'Record created successfully!',
                    'data' => $new_record
                ), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            } else {
                echo json_encode(array(
                    'status' => 'error',
                    'message' => 'Failed to create record'
                ));
            }
        } catch (Exception $e) {
            echo json_encode(array(
                'status' => 'error',
                'message' => 'Exception error: ' . $e->getMessage()
            ));
        }
        exit;
    }

    /**
     * Update record via AJAX
     */
    public function update($id)
    {
        ob_clean();
        header('Content-Type: application/json; charset=utf-8');
        
        try {
            if (!$this->Crudjs_model->exists($id)) {
                echo json_encode(array(
                    'status' => 'error',
                    'message' => 'Record not found'
                ));
                exit;
            }

            // Set validation rules
            $this->form_validation->set_rules('title', 'Title', 'max_length[255]');
            $this->form_validation->set_rules('author', 'Author', 'max_length[255]');

            if ($this->form_validation->run() === FALSE) {
                echo json_encode(array(
                    'status' => 'error',
                    'message' => 'Validation error: ' . strip_tags(validation_errors())
                ));
                exit;
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
                $old_record = $this->Crudjs_model->get_by_id($id);
                if ($old_record->image) {
                    $old_image_path = './uploads/' . $old_record->image;
                    if (file_exists($old_image_path)) {
                        unlink($old_image_path);
                    }
                }

                // Upload new image
                $upload_result = $this->_upload_image();
                
                if ($upload_result['status']) {
                    $data['image'] = 'posts/' . $upload_result['file_name'];
                } else {
                    echo json_encode(array(
                        'status' => 'error',
                        'message' => $upload_result['error']
                    ));
                    exit;
                }
            }

            $result = $this->Crudjs_model->update($id, $data);

            if ($result) {
                $updated_record = $this->Crudjs_model->get_by_id($id);
                echo json_encode(array(
                    'status' => 'success',
                    'message' => 'Record updated successfully!',
                    'data' => $updated_record
                ), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            } else {
                echo json_encode(array(
                    'status' => 'error',
                    'message' => 'Failed to update record'
                ));
            }
        } catch (Exception $e) {
            echo json_encode(array(
                'status' => 'error',
                'message' => 'Exception error: ' . $e->getMessage()
            ));
        }
        exit;
    }

    /**
     * Delete record via AJAX
     */
    public function delete($id)
    {
        ob_clean();
        header('Content-Type: application/json; charset=utf-8');
        
        try {
            $record = $this->Crudjs_model->get_by_id($id);

            if (!$record) {
                echo json_encode(array(
                    'status' => 'error',
                    'message' => 'Record not found'
                ));
                exit;
            }

            // Delete image if exists
            if ($record->image) {
                $image_path = './uploads/' . $record->image;
                if (file_exists($image_path)) {
                    unlink($image_path);
                }
            }

            $result = $this->Crudjs_model->delete($id);

            if ($result) {
                echo json_encode(array(
                    'status' => 'success',
                    'message' => 'Record deleted successfully!'
                ));
            } else {
                echo json_encode(array(
                    'status' => 'error',
                    'message' => 'Failed to delete record'
                ));
            }
        } catch (Exception $e) {
            echo json_encode(array(
                'status' => 'error',
                'message' => 'Exception error: ' . $e->getMessage()
            ));
        }
        exit;
    }

    /**
     * Test simple endpoint
     */
    public function test()
    {
        ob_clean();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(array('status' => 'success', 'message' => 'API is working'));
        exit;
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
