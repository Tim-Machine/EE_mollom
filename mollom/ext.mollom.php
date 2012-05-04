<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Mollom_ext {

    var $name           = 'Mollom Spam Filter';
    var $version        = '1.0';
    var $description    = 'Protects your comments from spam';
    var $settings_exist = 'y';
    var $docs_url       = ''; // 'http://expressionengine.com/user_guide/';
    var $mollomVersion        = '1.0';
    var $settings       = array();

    /**
     * Constructor
     *
     * @param   mixed   Settings array or empty string if none exist.
     */
    function __construct($settings=''){
        $this->EE =& get_instance();

        $this->settings = $settings;
    }
    // END


    /**
    * Activate Extension
    *
    * This function enters the extension into the exp_extensions table
    *
    * @see http://codeigniter.com/user_guide/database/index.html for
    * more information on the db class.
    *
    * @return void
    */
    function activate_extension(){
        $this->settings = array(
        'privateKey'    => "",
        'publicKey'     => ''
        );


        $data = array(
        'class'     => __CLASS__,
        'method'    => 'filter',
        'hook'      => 'insert_comment_insert_array',
        'settings'  => serialize($this->settings),
        'priority'  => 10,
        'version'   => $this->version,
        'enabled'   => 'y'
        );

        $this->EE->db->insert('extensions', $data);
    }





    /**
     * Update Extension
     *
     * This function performs any necessary db updates when the extension
     * page is visited
     *
     * @return  mixed   void on update / false if none
     */
    function update_extension($current = ''){
        if ($current == '' OR $current == $this->version)
        {
            return FALSE;
        }

        if ($current < '1.0')
        {
            // Update to version 1.0
        }

        $this->EE->db->where('class', __CLASS__);
        $this->EE->db->update(
                    'extensions',
                    array('version' => $this->version)
        );
    }

    /**
     * Disable Extension
     *
     * This method removes information from the exp_extensions table
     *
     * @return void
     */
    function disable_extension()
    {
        $this->EE->db->where('class', __CLASS__);
        $this->EE->db->delete('extensions');
    }

    // --------------------------------
    //  Settings
    // --------------------------------

    function settings()
    {
        $settings = array();

        // Creates a text input with a default value of "EllisLab Brand Butter"
        $settings['privateKey']      = array('i', '', "Private Key");
        $settings['publicKey']      = array('i', '', "Public Key");

        return $settings;
    }
    // END


    function filter($data){
            // load mollom library
            $this->EE->load->library('mollom');
            // get our keys
            $keys = $this->_keysExist(); 

            if($keys){
                // set our keys
                Mollom::setPublicKey($keys['public']);
                Mollom::setPrivateKey($keys['private']);

                $servers = $this->_mollom_retrieve_server_list();

                // populate serverlist (get them from your db, or file, or ...
                Mollom::setServerList($servers);    

                if(Mollom::verifyKey()){
                    // get feed back
                    $data = $this->feedback($data);
                }
                else{
                    echo "Key(s) are incorrect";
                }

            }

            return $data;
    }





    function _keysExist(){

        if( $this->settings['publicKey'] != null && $this->settings['privateKey']){
            $keys = array('public'=> $this->settings['publicKey'], 'private'=>$this->settings['privateKey']);
        }
        else{
            $keys = false;
        }

        return $keys;
    }

    /*
    temp server list. looking for better way to build this server list.
    */
    function _mollom_retrieve_server_list() {
        // Start from a hard coded list of servers:
        $servers = array('http://xmlrpc1.mollom.com','http://xmlrpc2.mollom.com','http://xmlrpc3.mollom.com');

        return $servers;
    }


    function feedback($data){

        // get feedback
        //checkContent($sessionId , $postTitle , $postBody , $authorName , $authorUrl , $authorEmail , $authorOpenId, $authorId)
        $feedback = Mollom::checkContent(null, null, $data['comment'], $data['name'],$data['url'],$data['email']);
        
        if($feedback['spam'] != 'ham'){
           $data =  $this->updateData($data,$feedback);
        }

        return $data;
    }


    function updateData($data, $feedback){
        $stats = $feedback['spam'];
        if($stats = 'spam'){
            $data['status'] = 'c';
        }
        else{
            $data['status'] = 'p';
        }

        return $data;
    }


}
// END CLASS