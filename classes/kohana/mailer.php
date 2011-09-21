<?php defined('SYSPATH') or die('No direct script access.');
/**
 * System for sending email using Swift Mailer integrates into any web app written
 * in PHP 5, offering a flexible and elegant object-oriented approach to sending
 * emails with a multitude of features. 
 *
 * @package		Kohana/Mailer
 * @author		Eduardo Pacheco
 * @copyright	(c) 2007-2009 Kohana Team
 * @fork		http://github.com/themusicman/Mailer
 * @license		http://kohanaphp.com/license
 */
class Kohana_Mailer {

    /**
	* Swift_Mailer
	* @var object
	*/
    protected $_mailer = null;

    /**
	* Mail Type
	* @var string
	*/
    protected $type = null;

    /**
	* Sender mail
	* @var string
	*/
    protected $from = null;

    /**
	* Receipents mail
	* @var string
	*/
    protected $to = null;

    /**
	* CC
	* @var string
	*/
    protected $cc = null;

    /**
	* BCC
	* @var string
	*/
    protected $bcc = null;

    /**
	* Mail Subject
	* @var string
	*/
    protected $subject = null;

    /**
	* Data binding
	* @var array
	*/
    protected $data = null;

    /**
	* Attachments
	* @var array
	*/
    protected $attachments = null;

    /**
	* Whether in batch send or no
	* @var boolean
	*/
    protected $batch_send = false;

    /**
	* Swift_Message Object
	* @var object
	*/
    protected $message = null;

    /**
	* Mailer Config
	* @var object
	*/
    protected $config = "default";

    /**
	* Mailer DebugMode
	* @var bool
	*/
    protected $debug = FALSE;

    /**
	* Mailer Method
	* @var string
	*/
    protected $method = NULL;
	
	/**
	* Mailer Instance
	* @var object
	*/
	public static $instances = array();

    /**
	* Mail template
	* @var array
	*/
    protected $view = array(
        'html' => null,
        'text' => null,
    );

	/**
	 * Automatically executed before the controller action. Can be used to set
	 * class properties, do authorization checks, and execute other custom code.
	 *
	 * @return  void
	 */
	public function before()
	{
		// Nothing by default
	}

	/**
	 * Automatically executed after the controller action. Can be used to apply
	 * transformation to the request response, add extra output, and execute
	 * other custom code.
	 *
	 * @return  void
	 */
	public function after()
	{
		// Nothing by default
	}
	
	/**
	 * Creates a new Mailer object.
	 *
	 * @param   string  configuration
	 * @return  void
	 */
	public function __construct($config = "default")
    {
		if ( ! class_exists('Swift', FALSE))
		{
			// Load SwiftMailer Autoloader
			require_once Kohana::find_file('vendor', 'swift/swift_required');
		};
		// Load configuration
		$this->before();
    }
	
	/**
	 * Creates a new Mailer object.
	 *
	 * @access public
	 * @param  string	mailer_name
	 * @param  string	method
	 * @param  data		array
	 * @return Mailer
	 * 
	 **/
	public static function factory( $mailer_name = NULL, $method = NULL, $data = array() )
	{
		$class = ( $mailer_name !== NULL ) ? 'Mailer_'.ucfirst($mailer_name) : 'Mailer';
		$class = new $class;
		
		if ( $method === NULL )
		{
			return $class;
		} else {
			//see if the method exists	
			if (method_exists($class, $method))
			{
				//call the method
				call_user_func_array( array( $class, $method ), array( $data ) );
				// $class->$method( $data );
				
				//setup the message
				$class->setup( $method );
				
				//send the message
				return $class->send();
			}
			else
			{
				//the method does not exist so throw exception
				throw new Exception('Method: '.$method.' does not exist.');
			}
		};
	}
	
	/**
	 * Singleton pattern
	 *
	 * @access public
	 * @param  string mailer_name
	 * @return Mailer
	 * 
	 **/
	public static function instance( $mailer_name = NULL, $config = "default" ) 
	{
		$className = ( $mailer_name !== NULL) ? 'Mailer_'.ucfirst($mailer_name) : "Mailer";
		
		if ( ! isset( self::$instances[$className] ) )
		{
			self::$instances[$className] = new $className;
		};
		return self::$instances[$className]->config($config);
	}
	
		
	/**
	 * Connect to server
	 *
	 * @access public
	 * @param  string	config	
	 * @return object	Swift_Mailer
	 * 
	 **/
	public function connect( $config = "default" ) 
	{
		// Load configuration
		$config = Kohana::config('mailer.'.$config);

		$transport = ( is_null( $config['transport'] ) ) ? 'native' : $config['transport'];
		$config = $config['options'];

		$klass = 'Mailer_Transport_' . ucfirst($transport);
		$factory = new $klass();

		//Create the Mailer
		return $this->_mailer = Swift_Mailer::newInstance($factory->build($config));
	}

	/**
	 * Use the call object to configure the sending
	 *
	 * @access public
	 * @param  void	
	 * @return void
	 * 
	 **/
	public function __call($name, $args = array())
	{
		$pattern = '/^(type|from|to|cc|bcc|subject|data|attachments|batch_send|config|html|text|debug)$/i';
		if ( isset($args[0]) && is_array( $args[0] ) )
		{
			foreach ($args[0] as $key => $value)
			{
				if (preg_match($pattern, $key))
				{
					$this->$key = $value;
				};
			};
		};
		
		if ( preg_match($pattern, $name) )
		{
			$this->$name = $args[0];
			return $this;
		};

		if (preg_match('/^sen(d|t)_/i', $name))
		{
			$method = substr($name, 5, strlen($name));
			
			//see if the method exists	
			if (method_exists($this, $method))
			{
				//call the method
				call_user_func_array(array($this, $method), $args);
				
				//setup the message
				$this->setup($method);
				
				//send the message
				return $this->send();
			}
			else
			{
				//the method does not exist so throw exception
				throw new Exception('Method: '.$method.' does not exist.');
			};
		}
	}

	/**
	 * Setup the message
	 *
	 * @access public
	 * @param  string	method
	 * @return object
	 * 
	 **/
	public function setup($method = NULL) 
	{
		$this->message = Swift_Message::newInstance();
        $this->message->setSubject($this->subject);
		$is_html = isset( $this->html );
		$is_text = isset( $this->text );

		if ( $is_html || $is_text )
		{
			if ( $is_html )
			{
				$this->view["html"] = $this->html;
				$this->message->setBody($this->view['html'], 'text/html');
			};
			if ( $is_text )
			{
				$this->view["text"] = $this->text;
				if ( $is_html )
				{
					$this->message->addPart($this->view['text'], 'text/plain');
				}
				else
				{
					$this->message->setBody($this->view['text'], 'text/plain');
				};
			};
		}
		else
		{
			// View
			$template = strtolower(preg_replace('/_/', '/', get_class($this)) . "/{$method}");

			$text = View::factory($template);
			$this->set_data($text);
			$this->view['text'] = $text->render();
		
			if ($this->type === 'html')
			{
				$template = View::factory( $template . "_text" );
				$this->set_data( $template );
				$this->view['html'] = $this->view['text'];
				$this->view['text'] = $template->render();

				$this->message->setBody($this->view['html'], 'text/html');
				$this->message->addPart($this->view['text'], 'text/plain');
			} else {
				$this->message->setBody($this->view['text'], 'text/plain');
			}
		};

        if ($this->attachments !== null)
        {
            if (! is_array($this->attachments))
            {
                $this->attachments = array($this->attachments);
            }

            foreach ($this->attachments as $file)
            {
                $this->message->attach(Swift_Attachment::fromPath($file));
            }
        }

        // to
        if (! is_array($this->to))
        {
            $this->to = array($this->to);
        }
        $this->message->setTo($this->to);

        // cc
        if ($this->cc !== null)
        {
            if (! is_array($this->cc))
            {
                $this->cc = array($this->cc);
            }
            $this->message->setCc($this->cc);
        }

        // bcc
        if ($this->bcc !== null)
        {
            if (! is_array($this->bcc))
            {
                $this->bcc = array($this->bcc);
            }
            $this->message->setBcc($this->bcc);
        }

        // from
        $this->message->setFrom($this->from);

        return $this;
	}

	/**
	 * Send the mail
	 *
	 * @access public
	 * @param  void
	 * @return bool	Mailer_result
	 * 
	 **/
	public function send() 
	{
		if ( $this->message === NULL )
		{
			$this->setup();
		};
	
		$this->connect( $this->config );
		
		try {
			//should we batch send or not?
			if ( ! $this->batch_send)
			{
				//Send the message
				$this->result = $this->_mailer->send($this->message);
			}
			else
			{
				$this->result = $this->_mailer->batchSend($this->message);
			}
		} catch (Exception $e) {
			return false;
			if ( self::$debug ) {
				throw new Kohana_Exception('Server is not responding');
			} else {
				return false;
			};
		};
		
		$this->after();
		return $this->result;
	}

	/**
	 * Set data into the View
	 *
	 * @param &View
	 * @return &View
	 */
	protected function set_data(& $view)
    {
        if ($this->data != null)
        {
            if (! is_array($this->data))
            {
                $this->data = array($this->data);
			};

            foreach ($this->data as $key => $value)
            {
                $view->bind($key, $this->data[$key]);
            };
        };
        return $view;
    }
	
}// end of Mailer
