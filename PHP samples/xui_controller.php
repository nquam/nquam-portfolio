<?php
if (! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * This controller is used to dynamically load views
 * via ajax
 *
 * @author Nathan Quam - 08/04/2011 created class
 */
class Xhr extends CI_Controller
{
    function __construct()
    {
        parent::__construct();
    }

    /**
     * used to dynamically load our ajax views
     *
     * @author  - 08/04/2011 created method
     * @param string $method
     * @param array $params
     */
    public function _remap($method, $params = array())
    {
        $data = array();
        $data['mobile_enabled'] = TRUE;
        $data['method'] = $method;
        $data['logged_in'] = $this->tank_auth->is_logged_in();
        $data['user_id'] = $this->tank_auth->get_user_id();
        $data['user_details'] = $this->session->userdata('user_details');
        
        // get data specific to the page we want to display
        switch ($method) {
            case 'process_ajax_form' :
                $this->process_ajax_form($this->uri->segment(3));
                break;
            
            case 'save_checked_shots' :
                $this->save_checked_shots($this->uri->segment(3));
                break;
            
            case 'createnewuser' :
                $this->session->unset_userdata('user_details');
                $method = 'home';
                break;

            case 'logout':
                $this->tank_auth->logout();
                redirect('home');
                break;
        }
        $this->load->view('inc/xhr_header.php', $data);
        $this->load->view('xhr/' . $method . '.php', $data);
        $this->load->view('inc/xhr_footer.php', $data);
    }

    public function save_checked_shots($schedule_id)
    {
        $shot_id_array = explode(',', file_get_contents('php://input'));

        $this->load->model('mcheckedshot');
        $this->mcheckedshot->destroy_all($schedule_id);
        foreach ( $shot_id_array as $shot_id ) {
            $data = array(
                'schedule_id' => $schedule_id, 
                'vaccine_shot_id' => $shot_id
            );
            $this->mcheckedshot->create($data);
        }
//        var_dump($this->mcheckedshot->find_all($schedule_id));
        exit();
    }

    /**
     * the starting point for working with forms submitted with ajax
     *
     * @author  08/11/2011 created method
     * @author  08/27/2011 DRYer code
     * 
     * @param string $form_name
     */
    public function process_ajax_form($form_name)
    {
        $post_data = $this->_get_query_array(file_get_contents('php://input'));
        $result = $this->{'_process_'.$form_name}($post_data);
        echo json_encode($result);
        exit();
    }

    /**
     * processes account update
     *
     * @author  08/27/2011 created method
     * @param array $post_data
     */
    private function _process_frm_account($post_data)
    {
        $result = array();
        
        // validate our values
        $errors = array();
       
        if (!isset($post_data['txt_email']) || !preg_match(EMAIL_REGEX, $post_data['txt_email'])) {
            $errors[] = 'You must enter a valid email address.';
        }
        
        $change_password = '';
        if (isset($post_data['txt_password']) && strlen($post_data['txt_password']) > 0) {
            if (strlen($post_data['txt_password']) < 4) {
                $errors[] = 'You must enter a password at least 4 characters long.';
            } else {
                $change_password = $post_data['txt_password'];
            }
        }

        if (count($errors) == 0) {
            // update user
            $this->load->model('mmembers');

            $data = array(
                'email' => strtolower($post_data['txt_email'])
            );

            if (!empty($change_password)) {
                $data['password'] = $change_password;
            }

            $this->mmembers->edit_user($this->tank_auth->get_user_id(), $data);

            $result['success'] = 'success';
            $result['form_name'] = 'frm_account';
        } else {
            $result['success'] = 'error';
            $result['errors'] = '';
            foreach ( $errors as $error ) {
                $result['errors'] .= "$error\n";
            }
        }
        
        return $result;
    }

    /**
     * processes reset password
     *
     * @author  08/29/2011 created method
     * @param array $post_data
     */
    private function _process_frm_reset_password($post_data)
    {

        $result = array();
        
        // validate our values
        $errors = array();

        if (!isset($post_data['txt_email']) || !preg_match(EMAIL_REGEX, $post_data['txt_email'])) {
            $errors[] = 'You must enter a valid email address.';
        }

        if (count($errors) == 0) {
            $data = $this->tank_auth->forgot_password($post_data['txt_email']);

            if (!is_null($data)) {
                $new_password = '';

                // from http://codegolf.stackexchange.com/questions/1360/how-to-write-a-single-line-password-generator
                while(strlen($new_password.=array_rand(array_flip(array_merge(range('a','z'),range('A','Z'),range('0','9')))))<8){} 

                if ($this->tank_auth->reset_password($data['user_id'], $data['new_pass_key'], $new_password)) {
                    $subject = 'Your Password was Reset';
                    $body = 'Your new password is: '.$new_password;

                    $this->load->library('sendgrid');
                    Sendgrid::sendgrid_email($data['email'], $subject, $body);

                    $result['success'] = 'success';
                    $result['form_name'] = 'frm_reset_password';
                } else {
                    $errors[] = 'There was an error while attempting to reset your password.';
                }

            } else {
                foreach ($this->tank_auth->get_error_message() as $key => $value) {
                    $errors[] = $value;
                } 
            }
        }
        
        if (count($errors) > 0) {
            $result['success'] = 'error';
            $result['errors'] = '';
            foreach ( $errors as $error ) {
                $result['errors'] .= "$error\n";
            }
        }
        
        return $result;
    }

    /**
     * processes user authentication
     *
     * @author  08/26/2011 created method
     * @param array $post_data
     */
    private function _process_frm_authentication($post_data)
    {
        $result = array();
        
        // validate our values
        $errors = array();
       
	    if ($this->tank_auth->is_logged_in()) {
            $result['success'] = 'success';
            $result['form_name'] = 'frm_authentication';
	    } else {
            if (!isset($post_data['txt_email']) || !preg_match(EMAIL_REGEX, $post_data['txt_email'])) {
                $errors[] = 'You must enter a valid email address.';
            }
            
            if (!isset($post_data['txt_password']) || !(strlen($post_data['txt_password']) > 0)) {
                $errors[] = 'You must enter a password.';
            }


            if (count($errors) == 0) {
                $data = array(
                    'remember_me' => TRUE,
                    'login_by_username' => ($this->config->item('login_by_username', 'tank_auth') AND
					                                  $this->config->item('use_username', 'tank_auth')),
                    'login_by_email' => $this->config->item('login_by_email', 'tank_auth')
                );
                if ($this->tank_auth->login($post_data['txt_email'],
                                            $post_data['txt_password'],
                                            $data['remember_me'],
                                            $data['login_by_username'],
                                            $data['login_by_email'])) {
                    $result['success'] = 'success';
                    $result['form_name'] = 'frm_authentication';	
                } else {
                    $errors = $this->tank_auth->get_error_message();
                    if (isset($errors['banned'])) {
                        $errors[] = $errors['banned'];
                    } elseif (isset($errors['not_activated'])) {
                        $errors[] = $errors['not_activated'];
                    } else {	
                      foreach ($errors as $k => $v)	$errors[] = $this->lang->line($v);
                    }
                }
            }
        }
        
        if (count($errors) > 0) {
            $result['success'] = 'error';
            $result['errors'] = 'Invalid email or password';
        }
        
        return $result;
    }

    /**
     * processes user registration
     *
     * @author  08/26/2011 created method
     * @param array $post_data
     */
    private function _process_frm_registration($post_data)
    {
        $result = array();
        
        // validate our values
        $errors = array();
       
        if (!isset($post_data['txt_email']) || !preg_match(EMAIL_REGEX, $post_data['txt_email'])) {
            $errors[] = 'You must enter a valid email address.';
        }
        
        if (!isset($post_data['txt_password']) || !(strlen($post_data['txt_password']) > 3)) {
            $errors[] = 'You must enter a password at least 4 characters long.';
        }

        if (count($errors) == 0) {
            // register user
            $this->load->model('mmembers');
                
            $id = $this->mmembers->create_user(strtolower($post_data['txt_email']), $post_data['txt_password']);
            if (!empty($id['error'])) {
                $errors[] = $id['error'];
            } else {
                $data = array(
                    'remember_me' => TRUE,
                    'login_by_username' => ($this->config->item('login_by_username', 'tank_auth') AND
					                                  $this->config->item('use_username', 'tank_auth')),
                    'login_by_email' => $this->config->item('login_by_email', 'tank_auth')
                );
                $this->load->model('mreminder');
                $this->mreminder->send_welcome_email($post_data['txt_email']);
                $this->tank_auth->login($post_data['txt_email'], 
                                        $post_data['txt_password'],
                                        $data['remember_me'],
                                        $data['login_by_username'],
                                        $data['login_by_email']);
                $result['success'] = 'success';
                $result['form_name'] = 'frm_registration';
            }
        }
        
        if (count($errors) > 0) {
            $result['success'] = 'error';
            $result['errors'] = '';
            foreach ( $errors as $error ) {
                $result['errors'] .= "$error\n";
            }
        }
        
        return $result;
    }

    /**
     * processes the form for all unchecked reminders
     *
     * @author Nathan Quam 08/31/2011 created method
     * @param array $post_data
     */
    private function _process_frm_set_reminders($post_data)
    {
        $result = array();
        
        // validate our values
        $errors = array();
        
        // create reminders for them
        $this->load->model('mreminder');
        
        $this->mreminder->add_mult_reminders($post_data['schedule_id']);
        // send them a welcome email
        //$this->mreminder->send_welcome_email($post_data['txt_email']);
        //$this->mreminder->update(FALSE, $post_data['txt_email'], '');

        $result['success'] = 'success';
        $result['form_name'] = 'frm_set_reminders';
        
        return $result;
    }
    /**
     * processes the form for setting a single reminder
     *
     * @author Nathan Quam 09/01/2011 created method
     * @param array $post_data
     */
    private function _process_frm_set_reminder($post_data)
    {
        $result = array();
        
        // validate our values
        $errors = array();
        
        // create reminders for them
        $this->load->model('mreminder');
        
        $this->mreminder->add_single_reminder($post_data['schedule_id'],$post_data['shot_id']);
        // send them a welcome email
        //$this->mreminder->send_welcome_email($post_data['txt_email']);
        //$this->mreminder->update(FALSE, $post_data['txt_email'], '');

        $result['success'] = 'success';
        $result['form_name'] = 'frm_set_reminder';
        
        return $result;
    }
    /**
     * cancel a single reminder from the shot page
     * 
     *
     * @author Nathan Quam 09/01/2011 created method
     * @param array $post_data
     */
    private function _process_frm_cancel_reminder($post_data)
    {
        $result = array();
        // validate our values
        $errors = array();
        
        // create reminders for them
        $this->load->model('mreminder');
        $this->mreminder->delete_single_reminder($post_data['schedule_id'],$post_data['shot_id']);
        
        //$this->mreminder->add_mult_reminders($schedule_id);
        // send them a welcome email
        //$this->mreminder->send_welcome_email($post_data['txt_email']);
        //$this->mreminder->update(FALSE, $post_data['txt_email'], '');

        $result['success'] = 'success';
        $result['form_name'] = 'frm_cancel_reminder';
        
        return $result;
    }
    /**
     * cancel a multiple reminder from the set reminders page
     * 
     *
     * @author Nathan Quam 09/01/2011 created method
     * @param array $post_data
     */
    private function _process_frm_cancel_reminders($post_data)
    {
        $result = array();
        // validate our values
        $errors = array();
        
        // create reminders for them
        $this->load->model('mreminder');
        $this->mreminder->wipe_all_reminders($post_data['schedule_id']);

        $result['success'] = 'success';
        $result['form_name'] = 'frm_cancel_reminders';
        
        return $result;
    }  

    /**
     * processes the form that they filled out to record when they
     * received a particular shot
     *
     * @author  08/12/2011 created method
     * @param array $post_data
     */
    private function _process_frm_email_schedule($post_data)
    {
        $result = array();
        
        // validate our values
        $errors = array();
       
        $schedule_id = isset($post_data['schedule_id']) ? (int)$post_data['schedule_id'] : 0;
        $this->load->model('mschedule');
        $schedule = $this->mschedule->find_by_id($schedule_id);
        if (empty($schedule)) {
            $errors[] = 'A valid schedule was not supplied.';
        }
        
        if (count($errors) > 0) {
            $result['success'] = 'error';
            $result['errors'] = '';
            foreach ( $errors as $error ) {
                $result['errors'] .= "$error\n";
            }
        } else {
            // send out their email
            $this->load->library('sendgrid');
            $this->load->model('mmembers');
            $this->load->model('mshot');
            $data = array();
            $user = $this->mmembers->get_user($schedule->user_id);
            $data['schedule'] = $schedule;
            $data['shots'] = $this->mshot->fetch_all_grouped($schedule->id);
            
            Sendgrid::sendgrid_email($user->email, 'Your Good To Go Immunization Schedule', $this->load->view('email/raw.php', $data, TRUE));
            
            $result['success'] = 'success';
            $result['form_name'] = 'frm_email_schedule';
        }
        
        return $result;
    }

    /**
     * processes the form that they filled out to record when they
     * received a particular shot
     *
     * @author  08/12/2011 created method
     * @param array $post_data
     */
    private function _process_frm_submit_received_date($post_data)
    {
        $result = array();
        
        // validate our values
        $errors = array();
        
        if (isset($post_data['shot_id'])) {
            if ('' == $post_data['shot_id']) {
                $errors[] = 'There was an error with the form.';
            }
        }
        
        if (isset($post_data['txt_shot_d'])) {
            if ('' == $post_data['txt_shot_d']) {
                $errors[] = 'You must enter a day.';
            } elseif (! is_numeric($post_data['txt_shot_d']) || $post_data['txt_shot_d'] < 0 || $post_data['txt_shot_d'] > 31) {
                $errors[] = 'That is not a valid day. It should be in the form \'dd\'.';
            }
        }
        
        if (isset($post_data['txt_shot_m'])) {
            if ('' == $post_data['txt_shot_m']) {
                $errors[] = 'You must enter a month.';
            } elseif (! is_numeric($post_data['txt_shot_m']) || $post_data['txt_shot_m'] < 0 || $post_data['txt_shot_m'] > 12) {
                $errors[] = 'That is not a valid month. It should be in the form \'mm\'.';
            }
        }
        
        if (isset($post_data['txt_shot_y'])) {
            if ('' == $post_data['txt_shot_y']) {
                $errors[] = 'You must enter a year.';
            } elseif (! is_numeric($post_data['txt_shot_y']) || $post_data['txt_shot_y'] < (date('Y') - 100) || $post_data['txt_shot_y'] > date('Y')) {
                $errors[] = 'That is not a valid year. It should be in the form \'yyyy\'.';
            }
        }
        
        if (count($errors) > 0) {
            $result['success'] = 'error';
            $result['errors'] = '';
            foreach ( $errors as $error ) {
                $result['errors'] .= "$error\n";
            }
        } else {
            $data = array(
                'received_on' => strtotime($post_data['txt_shot_m'].'/'.$post_data['txt_shot_d'].'/'.$post_data['txt_shot_y'])
            );

            $this->load->model('mcheckedshot');
            $this->mcheckedshot->update($post_data['shot_id'], $data);


            $data['shot_d'] = date('j', $data['received_on']);
            $data['shot_y'] = date('Y', $data['received_on']);
            $data['shot_m_readable'] = date('F', $data['received_on']);
            
            $result['success'] = 'success';
            $result['form_name'] = 'frm_submit_received_date';
            $result['data'] = $data;
        }
        
        return $result;
    }

    /**
     * processes the form for submitting a child
     *
     * @author  08/11/2011 created method
     * @param array $post_data
     * @return array
     */
    private function _process_frm_submit_child($post_data)
    {
        $result = array();
        
        // validate our values
        $errors = array();
        
        if (isset($post_data['txt_name'])) {
            if ('' == $post_data['txt_name'] || 'Enter Child\'s Name' == $post_data['txt_name']) {
                $errors[] = 'You must enter a name.';
            }
        }
        
        if (isset($post_data['txt_dob_d'])) {
            if ('' == $post_data['txt_dob_d']) {
                $errors[] = 'You must enter a day.';
            } elseif (! is_numeric($post_data['txt_dob_d']) || $post_data['txt_dob_d'] < 0 || $post_data['txt_dob_d'] > 31) {
                $errors[] = 'That is not a valid day. It should be in the form \'dd\'.';
            }
        }
        
        if (isset($post_data['txt_dob_m'])) {
            if ('' == $post_data['txt_dob_m']) {
                $errors[] = 'You must enter a month.';
            } elseif (! is_numeric($post_data['txt_dob_m']) || $post_data['txt_dob_m'] < 0 || $post_data['txt_dob_m'] > 12) {
                $errors[] = 'That is not a valid month. It should be in the form \'mm\'.';
            }
        }
        
        if (isset($post_data['txt_dob_y'])) {
            if ('' == $post_data['txt_dob_y']) {
                $errors[] = 'You must enter a year.';
            } elseif (! is_numeric($post_data['txt_dob_y']) || $post_data['txt_dob_y'] < (date('Y') - 100) || $post_data['txt_dob_y'] > date('Y')) {
                $errors[] = 'That is not a valid year. It should be in the form \'yyyy\'.';
            }
        }
        
        if (count($errors) > 0) {
            $result['success'] = 'error';
            $result['errors'] = '';
            foreach ( $errors as $error ) {
                $result['errors'] .= "$error\n";
            }
        } else {
            $data = array();
            $data['user_id'] = $this->tank_auth->get_user_id();
            $data['name'] = ucwords($post_data['txt_name']);
            $data['dob'] = strtotime($post_data['txt_dob_y'].'-'.$post_data['txt_dob_m'].'-'.$post_data['txt_dob_d']);

            $this->load->model('mschedule');
            $schedule = $this->mschedule->create($data);
            $data['id'] = $schedule->id;
            $data['url'] = '/schedule/'.$schedule->id;
            
            $result['success'] = 'success';
            $result['form_name'] = 'frm_submit_child';
            $result['data'] = $data;
        }
        
        return $result;
    }
    private function _process_frm_update_child($post_data)
    {
        $result = array();
        
        // validate our values
        $errors = array();
        
        if (isset($post_data['txt_name'])) {
            if ('' == $post_data['txt_name'] || 'Enter Child\'s Name' == $post_data['txt_name']) {
                $errors[] = 'You must enter a name.';
            }
        }
        
        if (isset($post_data['txt_dob_d'])) {
            if ('' == $post_data['txt_dob_d']) {
                $errors[] = 'You must enter a day.';
            } elseif (! is_numeric($post_data['txt_dob_d']) || $post_data['txt_dob_d'] < 0 || $post_data['txt_dob_d'] > 31) {
                $errors[] = 'That is not a valid day. It should be in the form \'dd\'.';
            }
        }
        
        if (isset($post_data['txt_dob_m'])) {
            if ('' == $post_data['txt_dob_m']) {
                $errors[] = 'You must enter a month.';
            } elseif (! is_numeric($post_data['txt_dob_m']) || $post_data['txt_dob_m'] < 0 || $post_data['txt_dob_m'] > 12) {
                $errors[] = 'That is not a valid month. It should be in the form \'mm\'.';
            }
        }
        
        if (isset($post_data['txt_dob_y'])) {
            if ('' == $post_data['txt_dob_y']) {
                $errors[] = 'You must enter a year.';
            } elseif (! is_numeric($post_data['txt_dob_y']) || $post_data['txt_dob_y'] < (date('Y') - 100) || $post_data['txt_dob_y'] > date('Y')) {
                $errors[] = 'That is not a valid year. It should be in the form \'yyyy\'.';
            }
        }
        
        if (count($errors) > 0) {
            $result['success'] = 'error';
            $result['errors'] = '';
            foreach ( $errors as $error ) {
                $result['errors'] .= "$error\n";
            }
        } else {
            $data = array();
            $data['user_id'] = $this->tank_auth->get_user_id();
            $data['name'] = ucwords($post_data['txt_name']);
            $data['dob'] = strtotime($post_data['txt_dob_y'].'-'.$post_data['txt_dob_m'].'-'.$post_data['txt_dob_d']);

            $this->load->model('mschedule');
            $this->mschedule->update($post_data['schedule_id'], $data);
            $data['id'] = $post_data['schedule_id'];
            $data['url'] = site_url('xhr/schedule/'.$post_data['schedule_id']);
            
            $data['shot_d'] = date('j', $data['dob']);
            $data['shot_y'] = date('Y', $data['dob']);
            $data['shot_m_readable'] = date('F', $data['dob']);
            
            $result['success'] = 'success';
            $result['form_name'] = 'frm_update_child';
            $result['data'] = $data;
        }
        
        return $result;
    }

    /**
     * takes a query string and returns a key value array
     *
     * @author  08/11/2011 created method
     * @param string $query_string
     */
    private function _get_query_array($query_string)
    {
        $result = array();
        $query_string_raw_array = explode('&', $query_string);
        $query_array = array();
        foreach ( $query_string_raw_array as $row ) {
            $row_array = explode('=', $row);
            if (isset($row_array[1])) {
                $result[$row_array[0]] = $row_array[1];
            }
        }
        return $result;
    }

}
