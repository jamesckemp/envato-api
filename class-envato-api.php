<?php
/**
 * Envato API
 */
if ( ! class_exists( 'Envato_API' ) ) {

    class Envato_API {
        /**
         * The buyer's Username
         *
         * @var       string
         *
         * @access    public
         * @since     1.0
         */
        public $user_name;

        /**
         * The buyer's API Key
         *
         * @var       string
         *
         * @access    public
         * @since     1.0
         */
        public $personal_token;

        /**
         * The default API URL
         *
         * @var       string
         *
         * @access    private
         * @since     1.0
         */
        protected $public_url = 'https://api.envato.com/v3/';

        /**
         * Error messages
         *
         * @var       array
         *
         * @access    public
         * @since     1.0
         */
        public $errors = array( 'errors' => '' );

        /**
         * Class contructor method
         *
         * @param     string      The buyer's Username
         * @param     string      The buyer's API Key can be accessed on the marketplaces via My Account -> My Settings -> API Key
         * @return    void        Sets error messages if any.
         *
         * @access    public
         * @since     1.0
         */
        public function __construct( $user_name = '', $personal_token = '' ) {

            if ( $user_name == '' ) {
                $this->set_error( 'user_name', __( 'Please enter your Envato Marketplace Username.', 'envato-wordpress-toolkit' ) );
            }

            if ( $personal_token == '' ) {
                $this->set_error( 'personal_token', __( 'Please enter your Envato Marketplace Personal Token.', 'envato-wordpress-toolkit' ) );
            }

            $this->user_name = $user_name;
            $this->personal_token = $personal_token;

        }

        /**
         * Retrieve the details for a specific marketplace item.
         *
         * @param     string      $item_id The id of the item you need information for.
         * @return    object      Details for the given item.
         *
         * @access    public
         * @since     1.0
         * @updated   1.3
         */
        public function item_details( $item_id, $allow_cache = true, $timeout = 300 ) {

            $url = $this->public_url . '/market/catalog/item?id=' . $item_id;

            /* set transient ID for later */
            $transient = substr( md5( 'item_' . $item_id ), 0, 16 );

            if ( $allow_cache ) {
                $cache_results = $this->set_cache( $transient, $url, $timeout );
                $results = $cache_results;
            } else {
                $results = $this->remote_request( $url );
            }

            if ( isset( $results->error ) ) {
                $this->set_error( 'error_item_' . $item_id, $results->error );
            }

            if ( $errors = $this->api_errors() ) {
                $this->clear_cache( $transient );
                return $errors;
            }

            if ( isset( $results ) ) {
                return $results;
            }

            return false;

        }

        /**
         * Set cache with the Transients API.
         *
         * @link      http://codex.wordpress.org/Transients_API
         *
         * @param     string      Transient ID.
         * @param     string      The URL of the API request.
         * @param     int         Set transient timeout. Default 300 seconds (5 minutes).
         * @return    mixed
         *
         * @access    public
         * @since     1.3
         */
        public function set_cache( $transient = '', $url = '', $timeout = 300 ) {

            if ( $transient == '' || $url == '' ) {
                return false;
            }

            /* keep the code below cleaner */
            $transient = $this->validate_transient( $transient );
            $transient_timeout = '_transient_timeout_' . $transient;

            /* set original cache before we destroy it */
            $old_cache = get_option( $transient_timeout ) < time() ? get_option( $transient ) : '';

            /* look for a cached result and return if exists */
            if ( false !== $results = get_transient( $transient ) ) {
                return $results;
            }

            /* create the cache and allow filtering before it's saved */
            if ( $results = apply_filters( 'envato_api_set_cache', $this->remote_request( $url ), $transient ) ) {
                set_transient( $transient, $results, $timeout );
                return $results;
            }

            return false;

        }

        /**
         * Clear cache with the Transients API.
         *
         * @link      http://codex.wordpress.org/Transients_API
         *
         * @param     string      Transient ID.
         * @return    void
         *
         * @access    public
         * @since     1.3
         */
        public function clear_cache( $transient = '' ) {

            delete_transient( $transient );

        }

        /**
         * Helper function to validate transient ID's.
         *
         * @param     string      The transient ID.
         * @return    string      Returns a DB safe transient ID.
         *
         * @access    public
         * @since     1.3
         */
        public function validate_transient( $id = '' ) {

            return preg_replace( '/[^A-Za-z0-9\_\-]/i', '', str_replace( ':', '_', $id ) );

        }

        /**
         * Helper function to set error messages.
         *
         * @param     string      The error array id.
         * @param     string      The error message.
         * @return    void
         *
         * @access    public
         * @since     1.0
         */
        public function set_error( $id, $error ) {

            $this->errors['errors'][$id] = $error;

        }

        /**
         * Helper function to return the set errors.
         *
         * @return    array       The errors array.
         *
         * @access    public
         * @since     1.0
         */
        public function api_errors() {

            if ( ! empty( $this->errors['errors'] ) ) {
                return $this->errors['errors'];
            }

        }

        /**
         * Helper function to query the marketplace API via wp_remote_request.
         *
         * @param     string      The url to access.
         * @return    object      The results of the wp_remote_request request.
         *
         * @access    private
         * @since     1.0
         */
        protected function remote_request( $url ) {

            if ( empty( $url ) ) {
                return false;
            }

            $args = array(
                'headers'    => array( 'Accept-Encoding' => '', 'Authorization' => sprintf('Bearer %s', $this->personal_token) ),
                'timeout'    => 30,
                'user-agent' => 'Toolkit/1.7.3',
            );

            $request = wp_safe_remote_request( $url, $args );

            if ( is_wp_error( $request ) ) {
                echo $request->get_error_message();
                return false;
            }

            $data = json_decode( $request['body'] );

            if ( $request['response']['code'] == 200 ) {
                return $data;
            } else {
                $this->set_error( 'http_code', $request['response']['code'] );
            }

            if ( isset( $data->error ) ) {
                $this->set_error( 'api_error', $data->error );
            }

            return false;
        }

        /**
         * Helper function to print arrays to the screen ofr testing.
         *
         * @param     array       The array to print out
         * @return    string
         *
         * @access    public
         * @since     1.0
         */
        public function pretty_print( $array ) {

            echo '<pre>';
            print_r( $array );
            echo '</pre>';

        }

    }

}

/* End of file class-envato-api.php */
