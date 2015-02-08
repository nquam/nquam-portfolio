<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

// -------------------------------------------------------------------------------------------------
/**
 * WordCloud
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
		$this->load->library('wordcloud');
	-----------------------------------------------------------------------------------------------


	-----------------------------------------------------------------------------------------------

	-----------------------------------------------------------------------------------------------
	===============================================================================================
*/

class Wordcloud {

    public $base_uri = '';

    public $script_dir  = '';
	public $script_path = '';
	public $script_uri  = '';

	public $style_dir  = '';
	public $style_path = '';
	public $style_uri  = '';

	public $cache_dir  = '';
	public $cache_path = '';
	public $cache_uri  = '';

	private $loaded = array();

    private $CI;


	/**
	* Class Constructor
	*/
	public function __construct()
	{
		$this->CI =& get_instance();
		log_message('debug', 'Wordcloud: Library initialized.');

		if( $this->CI->config->load('wordcloud', TRUE, TRUE) ){

		}

	}


	/**
	* Display HTML references to the assets
	* @access	public
	* @param	String flag the asset type: css || js || both, OR the group name
	* @param	String flag the asset type to filter a group (e.g. only show 'js' for this group)
	* @return   Void
	*/
	public function get_tags($section_id = -1, $entry_name = 'entry_keywords', $table)
	{

            // Get all Tags
            if($section_id != -1){

                $tags = $this->_get_all_tags_by_category($section_id, $entry_name, $table);

                $raw_tags = array();
                $data['tags'] = '';
                if (!empty($tags)){
                    foreach ($tags as $t){
                        $string = explode(',', $t->$entry_name);
                        foreach ($string as $s){
                            $raw_tags[] = trim($s);
                        }
                    }

                    $raw_tags = (array_count_values($raw_tags));

                    return $this->_return_cloud($raw_tags);
    //                $data['tags'] = substr($data['tags'], 0, -1);
                }
            }
	}

        /**
	* Format the cloud data pieces and return them
	* @access	public
	* @param	String flag the asset type: css || js || both, OR the group name
	* @param	String flag the asset type to filter a group (e.g. only show 'js' for this group)
	* @return   Void
	*/
	private function _return_cloud($raw_tags)
	{
            $wordcloud = array();
            if ($raw_tags):
                // Mix it up
                function shuffle_assoc($list) {
                    if (!is_array($list)) return $list;
                    $keys = array_keys($list);
                    shuffle($keys);
                    return $keys;
                }

                $shuffle = shuffle_assoc($raw_tags);

                // Weight the Scale
                $largest = max($raw_tags);
                $multipl = (10/$largest);
                                    if($largest <= 2){
                                            $multipl = 4;
                                    }
                foreach ($shuffle as $i=>$value):

                    $wordcloud[$i]->encoded_word = urlencode(strtolower($value));
                    $wordcloud[$i]->class = 'size_'.round($raw_tags[$value] * $multipl);
                    $wordcloud[$i]->keyword = $value;

                endforeach;
            endif;



            return $wordcloud;
	}
        private function _get_all_tags_by_category($section_id, $entry_name, $table){//!! move to model

		$this->CI->db->select($entry_name);
		$this->CI->db->where($entry_name.' !=', '');
		$this->CI->db->where($entry_name.' !=', '#');
//		$this->CI->db->where('entry_published', '1');
		$q = $this->CI->db->get($table);

		if ($q->num_rows() > 0){
                    return $q->result();
		}

	}
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
