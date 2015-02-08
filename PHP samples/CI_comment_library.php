<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

// -------------------------------------------------------------------------------------------------
/**
 * Comment Library
 *
 *
 * @package		CodeIgniter
 * @subpackage	Libraries
 * @category	Asset Management
 * @author		Nathan Quam
 * @version		1.00
 *
 */

/*
	===============================================================================================
	 USAGE
	===============================================================================================

	Load the library as normal:
	-----------------------------------------------------------------------------------------------
		$this->load->library('rr_comments');
	-----------------------------------------------------------------------------------------------

	Configuration can happen in either a config file (included), or by passing an array of values
	to the config() method. Config options passed to the config() method will override options in
	the	config file.

	See the included config file for more info.

	To configure Carabiner using the config() method, do this:
	-----------------------------------------------------------------------------------------------
		$rr_comments_config = array(
			'script_dir' => 'assets/scripts/',
			'style_dir'  => 'assets/styles/',
			'cache_dir'  => 'assets/cache/',
			'base_uri'	 => base_url()
		);
            $rr_comments_config = array(
                'word'		 => '', preset word (prefer not to use except for testing)
                'img_path'	 => './assets/captcha/',
                'img_url'	 => '/assets/captcha/',
                'font_path'	 => 'assets/fonts/Menlo.ttc', if using a custom font
                'img_width'	 => rand(140, 140), set to random but you can use static size
                'img_height' => rand(35, 35),
                'expiration' => 7200 how long to keep the captcha
            );

		$this->rr_comments->config($rr_comments_config);
	-----------------------------------------------------------------------------------------------
	===============================================================================================
*/

class Rr_comments {

    public $base_uri = '';

    public $cap_word = '';
    public $cap_img_path = './assets/captcha/';
    public $cap_img_url = '/assets/captcha/';
    public $cap_font_path = '';
    public $cap_img_width = '';
    public $cap_img_height = '';
    public $cap_expiration = '';

    private $loaded = array();

    private $CI;


	/**
	* Class Constructor
	*/
	public function __construct()
	{
		$this->CI =& get_instance();
		log_message('debug', 'rr_comments: Library initialized.');

		if( $this->CI->config->load('rr_comments', TRUE, TRUE) ){
                        $rr_comments_config = $this->CI->config->item('rr_comments');
			$this->config($rr_comments_config);
		}

	}

/**
	* Load Config
	* @access	public
	* @param	Array of config variables. Requires script_dir(string), style_dir(string), and cache_dir(string).
	*			base_uri(string), dev(bool), combine(bool), minify_js(bool), minify_css(bool), and force_curl(bool) are optional.
	* @return   Void
	*/
	public function config($config)
	{

		foreach ($config as $key => $value)
		{
			if($key == 'groups') {

				foreach($value as $group_name => $assets){

					$this->group($group_name, $assets);
				}

				break;
			}

			$this->$key = $value;
		}

		// set the default value for base_uri from the config
		if($this->base_uri == '') $this->base_uri = $this->CI->config->item('base_url');

		log_message('debug', 'Carabiner: library configured.');
	}
/**********************************
 * CONTROL FUNCTIONS
 */

        /** Make captcha *************************
         * Ajax call to make a captcha
         */
        public function make_captcha(){ //info can be found in dx_captcha_pi.php

            $this->CI->load->helper('captcha');
            $vals = array(
                'word'		=> $this->cap_word,
                'img_path'	=> $this->cap_img_path,
                'img_url'	=> $this->cap_img_url,
                'font_path'	=> $this->cap_font_path,
                'img_width'	=> $this->cap_img_width,
                'img_height'    => $this->cap_img_height,
                'expiration'    => $this->cap_expiration
            );
            
            return create_captcha($vals);

        }
        public function notify_comments(){
            $this->load->model('mcomments');
            $users = array_unique($this->mcomments->get_notification_comments());

            foreach($users as $user){
                $data[$user['comment_id']] = $this->mcomments->notify_comments($user['comment_entry_id'],$user['comment_last_notified']);
            }
            foreach($data as $notification){

                $detail = array();
                $detail['name'] = $notification['comment_name'];
                $detail['email'] = $notification['comment_email'];
                $detail['body'] = $notification['entry_title'];
                $detail['body'] .= '<a href="'.BASE_URL.'/'.$notification['section_furl'].'/details/'.$notification['entry_furl'].'" target="_blank">'.$notification['entry_title'].'</a>';
                print_r($detail);
            }

        }
        public function add_comment($post_id = -1){

            $val = $this->form_validation;

            // Set form validation rules
            $val->set_rules('name', 'Name', 'trim|required|xss_clean');
            $val->set_rules('email', 'Email', 'trim|required|xss_clean|valid_email');
            $val->set_rules('message', 'Message', 'trim|required|xss_clean');
            $val->set_rules('remember', 'Remember', 'trim|xss_clean');
            $val->set_rules('notify', 'Notify', 'trim|xss_clean');
            if ($val->run() && $post_id != -1) {
                $data['comment_name'] = $val->set_value('name');
                $data['comment_email'] = $val->set_value('email');
                $data['comment_body'] = $val->set_value('message');
                $data['comment_ip'] = $this->input->ip_address();
                $data['comment_entry_id'] = $post_id;
                $date = date('Y-m-d H:i:s');
                $data['comment_pub_date'] = $date;
                $data['comment_last_notified'] = $date;
                if($val->set_value('remember') == 'on'){
                    $newdata = array(
                        'name'  => $val->set_value('name'),
                        'email' => $val->set_value('email')
                    );
                    $this->session->set_userdata($newdata);
                    $data['comment_remember'] = 1;
                }else{
                    $this->session->sess_destroy();
                }
                if($val->set_value('notify') == "on"){
                    $data['comment_notify'] = 1;
                }

                $this->load->model('mcomments');
                $this->mcomments->store_comment($data);
            }else{
                echo validation_errors();
            }
        }
        public function get_comments($post_id, $offset = 0){
            $this->load->model('mcomments');
            $limit=5;
            $comments = $this->mcomments->get_comments($post_id);

            $data['comments'] = '';

            foreach($comments as $entry){
                $data['comments'] .= '
                <div class="user-comment">
                    <div class="comment-info">
                        <div class="user-name">
                            '.$entry['name'].'
                        </div>
                        <div class="comment-date">
                            '.date('M d, Y',strtotime($entry['comment_pub_date'])).'
                        </div>
                    </div>
                    <div class="comment-entry">
                        '.str_replace("\n", "<br>\n", $entry['comment_body']).'
                    </div>
                    <div style="clear:both"></div>
                </div>
                <hr />';
            }
            $data['count'] = ($this->mcomments->count_comments($post_id));

            echo json_encode($data);
        }
        public function email($details){

            $val = $this->form_validation;

            // Set form validation rules
            $val->set_rules('name', 'Name', 'trim|required|xss_clean');
            $val->set_rules('email', 'Email', 'trim|required|xss_clean|valid_email');
            $val->set_rules('message', 'Message', 'trim|required|xss_clean');
            $body = '';
            if ($val->run()) {
                $email_conf['mailtype'] = 'html';
                $this->load->library('email');
                $this->email->initialize($email_conf);
                $body .= 'This was submitted from redacted.com contact form<br /><br />';
                    $body .= 'Name: '.$val->set_value('name').
                        '<br />Email: '.$val->set_value('email').
                        '<br />Message:<br /><div style="border:1px solid #ccc;padding:10px;">'.$val->set_value('message').'</div>';
                $this->email->from($val->set_value('email'));
                $this->email->to('nquam@redacted.com');
                $this->email->subject('Message submitted from redacted.com');
                $this->email->message($body);
                if ($this->email->send()) {
                    echo 'sent';
                } else {
                    echo 'email not sent';
                }
            }else{
                echo validation_errors();
            }


        }

/**********************************
 * OUTPUT FUNCTIONS
 */




/***********************************
 * DATABASE FUNCTIONS
 */

        public function db_get_comments($post_id){
        $data = array();
        $this->db->select('*, comment_name AS "name", comment_email AS "email"');
        $this->db->where('comment_entry_id', $post_id);
        $this->db->where('comment_published', 1);
        $this->db->order_by('comment_pub_date','DESC');
        $Q = $this->db->get('comments');

        if ($Q->num_rows() > 0)
        {
            foreach ($Q->result_array() as $row){
                        $data[] = $row;
           }
        }
        $Q->free_result();

        return $data;
    }
    public function db_count_comments($post_id){
        $data = array();

//        $this->db->where('comment_entry_id', $post_id);
        $this->db->where('comment_entry_id', $post_id);
        $this->db->where('comment_published', 1);
        $this->db->from('comments');
        $data = $this->db->count_all_results();

        return $data;
    }
    public function db_store_comment($data){
        $this->db->insert('comments', $data);
    }
    public function db_get_remember(){
        $data = array();
        $this->db->select('comment_name AS "name", comment_email AS "email"');
        $this->db->where('comment_ip', $this->input->ip_address());
        $this->db->where('comment_remember', 1);
        $this->db->order_by('comment_pub_date', 'DESC');
        $this->db->limit(1);
        $Q = $this->db->get('comments');

        if ($Q->num_rows() > 0)
        {
            foreach ($Q->result_array() as $row){
                        $data = $row;
           }
        }
        $Q->free_result();
        return $data;
    }

    public function db_notify_comments($post_id,$last_notify = -1){
        $data = array();
        $this->db->select('*');
        $this->db->join('entries','entries.entry_id = comments.comment_entry_id');
        $this->db->join('section','section.section_id = entries.entry_section_id');
        $this->db->where('entries.entry_id', $post_id);
        $this->db->where("TIMESTAMPDIFF(MINUTE,'comments.comment_pub_date','".$last_notify."') >", 0);
        $Q = $this->db->get('comments');
        echo $this->db->last_query();
        if ($Q->num_rows() > 0)
        {
            foreach ($Q->result_array() as $row){
                        $data = $row;
           }
        }
        $Q->free_result();
        //$this->email_comments($data);

        return $data;
    }

    public function db_get_notification_comments(){
        $data = array();
        $this->db->select('*');
        $this->db->where('comment_notify', 1);
        $Q = $this->db->get('comments');

        if ($Q->num_rows() > 0)
        {
            foreach ($Q->result_array() as $row){
                        $data[] = $row;
           }
        }
        $Q->free_result();

        return $data;
    }
    public function db_email_comments($data){
        $email_conf['mailtype'] = 'html';
        $this->load->library('email');
        $this->email->initialize($email_conf);
        foreach($data as $entry){
            $body = '';
            $body .= 'Hello, '.$entry['name'].'<br /><br />You requested that we send you updates to comments on '.$entry['entry_title'].'<br /><br />';
            $body .= '<br />Email: '.$entry['email'].
                '<br />Message:<br /><div style="border:1px solid #ccc;padding:10px;"><a href="'.BASE_URL.'">Go to this post</a></div>';
            $this->email->from($entry['email']);
            $this->email->to('nquam@redacted.com');
            $this->email->subject('New Comment Added to '.$entry['entry_title']);
            $this->email->message($body);
            echo $body;
//            $this->email->send();
        }
    }


/***********************************
 * SYSTEM FUNCTIONS
 */
	/**
	* Function used to prevent multiple load calls for the same CI library
	* @access	private
	* @param	String library name
	* @return   FALSE on empty call and when library is already loaded, TRUE when library loaded
	*/
	private function _load($lib=NULL)
	{
		if($lib == NULL) return FALSE;

		if( isset($this->loaded[$lib]) ):
			return FALSE;
		else:
			$this->CI->load->library($lib);
			$this->loaded[$lib] = TRUE;

			log_message('debug', 'Wordcloud: Codeigniter library '."'$lib'".' loaded');
			return TRUE;
		endif;
	}


	/**
	* isURL
	* Checks if the provided string is a URL. Allows for port, path and query string validations.
	* This should probably be moved into a helper file, but I hate to add a whole new file for
	* one little 2-line function.
	* @access	public
	* @param	string to be checked
	* @return   boolean	Returns TRUE/FALSE
	*/
	public static function isURL($string)
	{
		$pattern = '@(https?://([-\w\.]+)+(:\d+)?(/([\w/_\.]*(\?\S+)?)?)?)@';
		return preg_match($pattern, $string);
	}
}
