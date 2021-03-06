<?php
/**
 *
 * Makes HTTP Request to Sailthru API server
 * Response from server depends on the format being queried
 * if 'json' format is requested, client will recieve JSON object and 'php' is requested, client will recieve PHP array
 * XML format is also available but not has not been tested thoroughly
 *
 */
class Sailthru_Client {
    /**
     *
     * Sailthru API Key
     * @var string
     */
    private $api_key;

    /**
     *
     * SAilthru Secret
     * @var string
     */
    private $secret;

    /**
     *
     * Sailthru API URL, can be different for different users according to their settings
     * @var string
     */
    private $api_uri = 'https://api.sailthru.com';

    /**
     *
     * cURL or non-cURL request
     * @var string
     */
    private $http_request_type;

    /**
     *
     * User agent making request to Sailthru API server
     * Even, if you modify user-agent, please try to include 'PHP5' somewhere in the user-agent
     * @var String
     */
    protected $user_agent_string;


    /**
     * Instantiate a new client; constructor optionally takes overrides for api_uri and whether
     * to share the version of PHP that is being used.
     *
     * @param string $api_key
     * @param string $secret
     * @param string $api_uri
     * @param boolean $show_version
     */
    public function  __construct($api_key, $secret, $api_uri = false, $show_version = true) {
        $this->api_key = $api_key;
        $this->secret = $secret;
        if ($api_uri !== false) {
            $this->api_uri = $api_uri;
        }

        $this->http_request_type = function_exists('curl_init') ? 'httpRequestCurl' : 'httpRequestWithoutCurl';
        $this->user_agent_string = "Sailthru API PHP5 Client";
        if ($show_version) {
         $this->user_agent_string .= " PHP Version: " . phpversion();
        }
    }


    /**
     * Remotely send an email template to a single email address.
     *
     * If you pass the $schedule_time parameter, the send will be scheduled for a future time.
     *
     * Options:
     *   replyto: override Reply-To header
     *   test: send as test email (subject line will be marked, will not count towards stats)
     *
     * @param string $template_name
     * @param string $email
     * @param array $vars
     * @param array $options
     * @param string $schedule_time
     * @link http://docs.sailthru.com/api/send
     */
    public function send($template, $email, $vars = array(), $options = array(), $schedule_time = null) {
        $post = array();
        $post['template'] = $template;
        $post['email'] = $email;
        $post['vars'] = $vars;
        $post['options'] = $options;
        if ($schedule_time) {
            $post['schedule_time'] = $schedule_time;
        }
        $result = $this->apiPost('send', $post);
        return $result;
    }

    /**
     * Remotely send an email template to multiple email addresses.
     *
     * Use the evars parameter to set replacement vars for a particular email address.
     *
     * @param string $template_name
     * @param array $emails
     * @param array $vars
     * @param array $evars
     * @param array $options
     * @link http://docs.sailthru.com/api/send
     */
    public function multisend($template_name, $emails, $vars = array(), $evars = array(), $options = array()) {
        $post['template'] = $template_name;
        $post['email'] = is_array($emails) ? implode(',', $emails) : $emails;
        $post['vars'] = $vars;
        $post['evars'] = $evars;
        $post['options'] = $options;
        $result = $this->apiPost('send', $post);
        return $result;
    }


    /**
     * Get the status of a send.
     *
     * @param string $send_id
     * @link http://docs.sailthru.com/api/send
     */
    public function getSend($send_id) {
        return $this->apiGet('send', array('send_id' => $send_id));
    }


    /**
     * Cancel a send that was scheduled for a future time.
     *
     * @param string $send_id
     * @link http://docs.sailthru.com/api/send
     */
    public function cancelSend($send_id) {
        return $this->apiPost('send', array('send_id' => $send_id), 'DELETE');
    }


    /**
     * Return information about an email address, including replacement vars and lists.
     *
     * @param string $email
     * @link http://docs.sailthru.com/api/email
     */
    public function getEmail($email) {
        return $this->apiGet('email', array('email' => $email));
    }


    /**
     * Set replacement vars and/or list subscriptions for an email address.
     *
     * $lists should be an assoc array mapping list name => 1 for subscribed, 0 for unsubscribed
     *
     * @param string $email
     * @param array $vars
     * @param array $lists

     * @param array $templates
     * @param integer $verified 1 or 0
     * @param string $optout
     * @param string $send
     * @param array $send_vars
     * @link http://docs.sailthru.com/api/email
     */
    public function setEmail($email, $vars = array(), $lists = array(), $templates = array(), $verified = 0, $optout = null, $send = null, $send_vars = array()) {
        $data = array('email' => $email);
        if ($vars) {
            $data['vars'] = $vars;
        }
        if ($lists) {
            $data['lists'] = $lists;
        }
        if ($templates) {
            $data['templates'] = $templates;
        }
        $data['verified'] = (int)$verified;
        if ($optout !== null)   {
            $data['optout'] = $optout;
        }
        if ($send !== null) {
            $data['send'] = $send;
        }
        if (!empty($send_vars)) {
            $data['send_vars'] = $send_vars;
        }

        return $this->apiPost('email', $data);
    }


    /**
     * Schedule a mass mail blast
     *
     * @param string $name the name to give to this new blast
     * @param string $list the mailing list name to send to
     * @param string $schedule_time when the blast should send. Dates in the past will be scheduled for immediate delivery. Any English textual datetime format known to PHP's strtotime function is acceptable, such as 2009-03-18 23:57:22 UTC, now (immediate delivery), +3 hours (3 hours from now), or February 14, 9:30 EST. Be sure to specify a timezone if you use an exact time.
     * @param string $from_name the name appearing in the "From" of the email
     * @param string $from_email The email address to use as the "from" – choose from any of your verified emails
     * @param string $subject the subject line of the email
     * @param string $content_html the HTML-format version of the email
     * @param string $content_text the text-format version of the email
     * @param array $options associative array
     * 		blast_id
     * 		copy_blast
     * 		copy_template
     * 		replyto
     *		report_email
     *		is_link_tracking
     *		is_google_analytics
     *		is_public
     *		suppress_list
     *		test_vars
     *		email_hour_range
     *		abtest
     *		test_percent
     *		data_feed_url
     * @link http://docs.sailthru.com/api/blast
     */
    public function scheduleBlast($name,
            $list,
            $schedule_time,
            $from_name,
            $from_email,
            $subject,
            $content_html,
            $content_text,
            $options = array()) {

        $data = $options;
        $data['name'] = $name;
        $data['list'] = $list;
        $data['schedule_time'] = $schedule_time;
        $data['from_name'] = $from_name;
        $data['from_email'] = $from_email;
        $data['subject'] = $subject;
        $data['content_html'] = $content_html;
        $data['content_text'] = $content_text;

        return $this->apiPost('blast', $data);
    }

    /**
     * Schedule a mass mail from a template
     *
     * @param String $template
     * @param String $list
     * @param String $schedule_time
     * @param Array $options
     * @link http://docs.sailthru.com/api/blast
     **/
    public function scheduleBlastFromTemplate($template, $list, $schedule_time, $options = array()) {
        $data = $options;
        $data['copy_template'] = $template;
        $data['list'] = $list;
        $data['schedule_time'] = $schedule_time;
        return $this->apiPost('blast', $data);
    }

    /**
     * Schedule a mass mail blast from previous blast
     *
     * @param String|Integer $blast_id
     * @param String $schedule_time
     * @param array $options
     * @link http://docs.sailthru.com/api/blast
     **/
    public function scheduleBlastFromBlast($blast_id, $schedule_time, $options = array()) {
        $data = $options;
        $data['copy_blast'] = $blast_id;
        $data['schedule_time'] = $schedule_time;
        return $this->apiPost('blast', $data);
    }

    /**
     * updates existing blast
     *
     * @param string/integer $blast_id
     * @param string $name
     * @param string $list
     * @param string $schedule_time
     * @param string $from_name
     * @param string $from_email
     * @param string $subject
     * @param string $content_html
     * @param string $content_text
     * @param array $options associative array
     * 		blast_id
     * 		copy_blast
     * 		copy_template
     * 		replyto
     *		report_email
     *		is_link_tracking
     *		is_google_analytics
     *		is_public
     *		suppress_list
     *		test_vars
     *		email_hour_range
     *		abtest
     *		test_percent
     *		data_feed_url
     * @link http://docs.sailthru.com/api/blast
     */
    public function updateBlast($blast_id,
            $name = null,
            $list = null,
            $schedule_time = null,
            $from_name = null,
            $from_email = null,
            $subject = null,
            $content_html = null,
            $content_text = null,
            $options = array()) {
        $data = $options;
        $data['blast_id'] = $blast_id;
        if (!is_null($name)) {
            $data['name'] = $name;
        }
        if (!is_null($list)) {
            $data['list'] = $list;
        }
        if (!is_null($schedule_time)) {
            $data['schedule_time'] = $schedule_time;
        }
        if (!is_null($from_name))  {
            $data['from_name'] = $from_name;
        }
        if (!is_null($from_email)) {
            $data['from_email'] = $from_email;
        }
        if (!is_null($subject)) {
            $data['subject'] = $subject;
        }
        if (!is_null($content_html)) {
            $data['content_html'] = $content_html;
        }
        if (!is_null($content_text)) {
            $data['content_text'] = $content_text;
        }

        return $this->apiPost('blast', $data);
    }


    /**
     * Get Blast information
     * @param string/integer $blast_id
     * @link http://docs.sailthru.com/api/blast
     */
    public function getBlast($blast_id) {
        return $this->apiGet('blast', array('blast_id' => $blast_id));
    }


    /**
     * Delete Blast
     * @param ineteger/string $blast_id
     * @link http://docs.sailthru.com/api/blast
     */
    public function deleteBlast($blast_id) {
        return $this->apiDelete('blast', array('blast_id' => $blast_id));
    }


    /**
     * Cancel a scheduled Blast
     * @param ineteger/string $blast_id
     * @link http://docs.sailthru.com/api/blast
     */
    public function cancelBlast($blast_id) {
        $data = array(
            'blast_id' => $blast_id,
            'schedule_time' => ''
        );
        return $this->apiPost('blast', $data);
    }

    /**
     * Fetch information about a template
     *
     * @param string $template_name
     * @link http://docs.sailthru.com/api/template
     */
    function getTemplate($template_name) {
        return $this->apiGet('template', array('template' => $template_name));
    }

    /**
     * Save a template.
     *
     * @param string $template_name
     * @param array $template_fields
     * @link http://docs.sailthru.com/api/template
     */
    public function saveTemplate($template_name, $template_fields = array()) {
        $data = $template_fields;
        $data['template'] = $template_name;
        return $this->apiPost('template', $data);
    }



    /**
     * Download a list. Obviously, this can potentially be a very large download.
     * 'txt' is default format since, its more compact as compare to others
     * @param String $list
     * @param String $format
     * @return txt | json | xml
     * @link http://docs.sailthru.com/api/list
     */
    public function getList($list, $format = "txt") {
        $data = array(
            'list' => $list,
            'format' => $format
        );
        return $this->apiGet('list', $data);
    }

    
    /**
     * Get all lists metadata of a user
    **/
    public function getLists() {
        return $this->apiGet('list', array('list' => ""));
    }


    /**
     * Upload a list. The list import job is queued and will happen shortly after the API request.
     * @param String $list
     * @param String $emails
     * @link http://docs.sailthru.com/api/list
     */
    public function saveList($list, $emails) {
        $data = array(
            'list' => $list,
            'emails' => $emails
        );
        return $this->apiPost('list', $data);
    }


    /**
     * Deletes a list
     * @param String $list
	 * @link http://docs.sailthru.com/api/list
     */
    public function deleteList($list) {
        $data = array(
            'list' => $list
        );
        return $this->apiDelete('list', $data);
    }


    /**
     *
     * Fetch email contacts from a user's address book on one of the major email websites. Currently supports AOL, Gmail, Hotmail, and Yahoo! Mail.
     *
     * @param String $email
     * @param String $password
     * @param boolean $include_names
     * @link http://docs.sailthru.com/api/contacts
     */
    public function importContacts($email, $password, $include_names = true) {
        $data = array(
            'email' => $email,
            'password' => $password
        );
        if ($include_names === true) {
            $data['names'] = 1;
        }
        return $this->apiPost('contacts', $data);
    }


    /**
     *
     * Push a new piece of content to Sailthru, triggering any applicable alerts.
     *
     * @param String $title
     * @param String $url
     * @param String $date
     * @param Mixed $tags Null for empty values, or String or arrays
     * @link http://docs.sailthru.com/api/content
     */
    public function pushContent($title, $url, $date = null, $tags = null, $vars = array()) {
        $data = array();
        $data['title'] = $title;
        $data['url'] = $url;
        if (!is_null($tags)) {
                $data['tags'] = is_array($tags) ? implode(",", $tags) : $tags;
        }
        if (!is_null($date)) {
            $data['date'] = $date;
        }
        if (!empty($vars)) {
            $data['vars'] = $vars;
        }
        return $this->apiPost('content', $data);
    }


    /**
     *
     * Retrieve a user's alert settings.
     *
     * @link http://docs.sailthru.com/api/alert
     * @param String $email
     */
    public function getAlert($email) {
        $data = array(
            'email' => $email
        );
        return $this->apiGet('alert', $data);
    }


    /**
     *
     * Add a new alert to a user. You can add either a realtime or a summary alert (daily/weekly).
     * $when is only required when alert type is weekly or daily
     *
     * <code>
     * <?php
     * $options = array(
     * 		'match' => array(
     *   		'type' => array(
     *   		'shoes', 'shirts'
     *   	),
     *   	'min' => array(
     *   		 'price' => 3000
     *   	),
     *   	'tags' => array('blue', 'red')
     * );
     * $response = $sailthruClient->saveAlert("praj@sailthru.com", 'realtime', 'default', null, $options);
     * ?>
     * </code>
     *
     * @link http://docs.sailthru.com/api/alert
     * @param String $email
     * @param String $type
     * @param String $template
     * @param String $when
     * @param array $options Associative array of additive nature
     * 		match 		Exact-match a custom variable		match[type]=shoes
     * 		min		 	Minimum-value variables				min[price]=30000
     * 		max			Maximum-value match					max[price]=50000
 	 * 		tags		Tag-match							tags[]=blue
     */
    public function saveAlert($email, $type, $template, $when = null, $options = array()) {
        $data = $options;
        $data['email'] = $email;
        $data['type'] = $type;
        $data['template'] = $template;
        if ($type == 'weekly' || $type == 'daily') {
            $data['when'] = $when;
        }
        return $this->apiPost('alert', $data);
    }


    /**
     * Remove an alert from a user's settings.
     * @link http://docs.sailthru.com/api/alert
     * @param <type> $email
     * @param <type> $alert_id
     */
    public function deleteAlert($email, $alert_id) {
        $data = array(
            'email' => $email,
            'alert_id' => $alert_id
        );
        return $this->apiDelete('alert', $data);
    }


    /**
     * Record that a user has made a purchase, or has added items to their purchase total.
     * @link http://docs.sailthru.com/api/purchase
     */
    public function purchase($email, array $items, $incomplete = null, $message_id = null) {
        $data = array(
            'email' => $email,
            'items' => $items
        );
        if (!is_null($incomplete)) {
            $data['incomplete'] = (int)$incomplete;
        }
        if (!is_null($message_id)) {
            $data['incomplete'] = $message_id;
        }
        return $this->apiPost('purchase', $data);
    }


    /**
     * Retrieve information about your subscriber counts on a particular list, on a particular day.
     * @link http://docs.sailthru.com/api/stats
     * @param String $list
     * @param String $date
     */
    public function stats_list($list = null, $date = null) {
        $data = array();
        if (!is_null($list)) {
            $data['list'] = $list;
        }

        if (!is_null($date)) {
            $data['date'] = $date;
        }
        $data['stat'] = 'list';
        return $this->stats($data);
    }


    /**
     * Retrieve information about a particular blast or aggregated information from all of blasts over a specified date range.
     * @param array $data
     */
    public function stats_blast($blast_id = null, $start_date = null, $end_date = null, array $data = array()) {
        $data['stat'] = 'blast';
        if (!is_null($blast_id)) {
            $data['blast_id'] = $blast_id;
        }
        if (!is_null($start_date)) {
            $data['start_date'] = $start_date;
        }
        if (!is_null($end_date)) {
            $data['end_date'] = $end_date;
        }
        return $this->stats($data);
    }


    /**
     * Make Stats API Request
     * @param array $data
     */
    protected function stats(array $data) {
        return $this->apiGet('stats', $data);
    }


    /**
     *
     * Returns true if the incoming request is an authenticated verify post.
     * @link http://docs.sailthru.com/api/postbacks
     * @return boolean
     */
    public function receiveVerifyPost() {
        $params = $_POST;
        foreach (array('action', 'email', 'send_id', 'sig') as $k) {
            if (!isset($params[$k])) {
                return false;
            }
        }

        if ($params['action'] != 'verify') {
            return false;
        }
        $sig = $params['sig'];
        unset($params['sig']);
        if ($sig != Sailthru_Util::getSignatureHash($params, $this->secret)) {
            return false;
        }
        $send = $this->getSend($params['send_id']);
        if (!isset($send['email'])) {
            return false;
        }
        if ($send['email'] != $params['email']) {
            return false;
        }
        return true;
    }


    /**
     *
     * Optout postbacks
     * @return boolean
     * @link http://docs.sailthru.com/api/postbacks
     */
    public function receiveOptoutPost() {
         $params = $_POST;
        foreach (array('action', 'email', 'sig') as $k) {
            if (!isset($params[$k])) {
                return false;
            }
        }

        if ($params['action'] != 'optout') {
            return false;
        }
        $sig = $params['sig'];
        unset($params['sig']);
        if ($sig != Sailthru_Util::getSignatureHash($params, $this->secret)) {
            return false;
        }
        return true;
    }


    /**
     *
     * Get horizon data
     * @param string $email horizon user email
     * @param boolean $hid_only if true, server will only return Horizon Id of the user
     * @link http://docs.sailthru.com/api/horizon
     */
    public function getHorizon($email, $hid_only = false) {
        $data = array('email' => $email);
        if ($hid_only === true) {
            $data['hid_only'] = 1;
        }
        return $this->apiGet('horizon', $data);
    }


    /**
     *
     * Set horizon user data
     * @param string $email
     * @param Mixed $tags Null for empty values, or String or arrays
     */
    public function setHorizon($email, $tags = null) {
        $data = array('email' => $email);
        if (!is_null($tags)) {
            $data['tag'] = is_array($tags) ? implode(",", $tags) : $tags;
        }
        return $this->apiPost('horizon', $data);
    }


    /**
     *
     * Set Horizon cookie
     *
     * @param string $email horizon user email
     * @param string $domain
     * @param integer $duration
     * @param boolean $secure
     * @return boolean
     */
    public function setHorizonCookie($email, $domain = null, $duration = null, $secure = false) {
        $data = $this->getHorizon($email, true);
        if (!isset($data['hid'])) {
            return false;
        }
        if (!$domain) {
            $domain_parts = explode('.', $_SERVER['HTTP_HOST']);
            $domain = $domain_parts[sizeof($domain_parts)-2] . '.' . $domain_parts[sizeof($domain_parts)-1];
        }
        if ($duration === null) {
            $expire = time() + 31556926;
        } else if ($duration) {
            $expire = time() + $duration;
        } else {
            $expire = 0;
        }
        return setcookie('sailthru_hid', $data['hid'], $expire, '/', $domain, $secure);
    }


    /**
     * Perform an HTTP request using the curl extension
     *
     * @param string $url
     * @param array $data
     * @param array $headers
     * @return string
     */
    private function httpRequestCurl($url, $data, $method = 'POST') {
        $ch = curl_init();
        if ($method == 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data, '', '&'));
        } else {
            $url .= '?' . http_build_query($data, '', '&');
            if ($method != 'GET') {
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            }
        }
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("User-Agent: {$this->user_agent_string}"));
        $data = curl_exec($ch);
        if (!$data) {
            throw new Sailthru_Client_Exception("Bad response received from $url");
        }
        return $data;
    }


    /**
     * Adapted from: http://netevil.org/blog/2006/nov/http-post-from-php-without-curl
     *
     * @param string $url
     * @param array $data
     * @param array $headers
     * @return string
     */
    private function httpRequestWithoutCurl($url, $data, $method = 'POST') {
        $params = array('http' => array('method' => $method));
        if ($method == 'POST') {
            $params['http']['content'] = is_array($data) ? http_build_query($data, '', '&') : $data;
        } else {
            $url .= '?' . http_build_query($data, '', '&');
        }
        $params['http']['header'] = "User-Agent: {$this->user_agent_string}\nContent-Type: application/x-www-form-urlencoded";
        $ctx = stream_context_create($params);
        $fp = @fopen($url, 'rb', false, $ctx);
        if (!$fp) {
            throw new Sailthru_Client_Exception("Unable to open stream: $url");
        }
        $response = @stream_get_contents($fp);
        if ($response === false) {
            throw new Sailthru_Client_Exception("No response received from stream: $url");
        }
        return $response;
    }


    /**
     * Perform an HTTP request, checking for curl extension support
     *
     * @param string $url
     * @param array $data
     * @param array $headers
     * @return string
     */
    protected function httpRequest($url, $data, $method = 'POST') {
        return $this->{$this->http_request_type}($url, $data, $method);
    }

    /**
     * Perform an API POST (or other) request, using the shared-secret auth hash.
     *
     * @param array $data
     * @return array
     */
    public  function apiPost($action, $data, $method = 'POST') {
        $data['api_key'] = $this->api_key;
        $data['format'] = isset($data['format']) ? $data['format'] : 'php';
        $data['sig'] = Sailthru_Util::getSignatureHash($data, $this->secret);
        $result = $this->httpRequest("$this->api_uri/$action", $data, $method);
        $unserialized = @unserialize($result);
        return $unserialized ? $unserialized : $result;
    }


    /**
     * Perform an API GET request, using the shared-secret auth hash.
     *
     * @param string $action
     * @param array $data
     * @return array
     */
    public  function apiGet($action, $data) {
        $data['api_key'] = $this->api_key;
        $data['format'] = isset($data['format']) ? $data['format'] : 'php';
        $data['sig'] = Sailthru_Util::getSignatureHash($data, $this->secret);
        $result = $this->httpRequest("$this->api_uri/$action", $data, 'GET');
        $unserialized = @unserialize($result);
        return $unserialized ? $unserialized : $result;
    }


     /**
     * Perform an API DELETE request, using the shared-secret auth hash.
     *
     * @param string $action
     * @param array $data
     * @return array

     */
    public function apiDelete($action, $data) {
        return $this->apiPost($action, $data, 'DELETE');
    }

}
?>
