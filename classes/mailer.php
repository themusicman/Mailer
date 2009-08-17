<?php defined('SYSPATH') or die('No direct script access.');

class Mailer {

	protected $_class_name					= NULL;
		
	protected $_mailer 						= NULL;
	
	protected $message_type					= 'text/html';
	
	protected $from							= NULL;
	
	protected $to							= NULL;

	protected $cc							= NULL;

	protected $bcc							= NULL;
		
	protected $subject						= NULL;
		
	protected $body_html					= NULL;
	
	protected $body_text					= NULL;
	
	protected $body_data					= NULL;
	
	protected $attachments					= NULL;
	
	protected $message						= NULL;
	
	protected $batch_send					= FALSE;
	
	protected $result						= NULL;
	
	
	public function __construct()
	{		
		$this->_class_name = get_class($this);
				
		//setup SwiftMailer 
		$this->connect();
	}
	
	
	/**
	 * factory
	 *
	 * @access public
	 * @param  void	
	 * @return void
	 * 
	 **/
	
	public static function factory($mailer_name) 
	{
		$class = 'Mailer_'.ucfirst($mailer_name);
		return new $class;
	}
	
		
	/**
	 * _connect
	 *
	 * @access public
	 * @param  void	
	 * @return void
	 * 
	 **/
	
	public function connect($config = NULL) 
	{
		if ( ! class_exists('Swift', FALSE))
		{
			// Load SwiftMailer Autoloader
			require_once Kohana::find_file('vendor', 'swift/swift_required');
		}

		// Load default configuration
		($config === NULL) and $config = Kohana::config('mailer');
		
		//get the configuration options
		$options = $config->options;
		
		switch ($config->transport)
		{
			case 'smtp':
				
				//Create the Transport
				$transport = Swift_SmtpTransport::newInstance()
								->setHost($options['hostname'])
								->setUsername($options['username'])
								->setPassword($options['password']);
				
				//Port?
				$port = empty($options['port']) ? NULL : (int) $options['port'];
				$transport->setPort($port);
				
				//Use encryption?
				if (! empty($options['encryption']))
				{
					$transport->setEncryption($options['encryption']);
				}
				
			break;
			
			case 'sendmail':
				/*
					TODO Finish
				*/
			break;
			
			default:
				/*
					TODO Finish
				*/
			break;
		}

		//Create the Mailer using the appropriate transport
		return $this->_mailer = Swift_Mailer::newInstance($transport);
	}
	
	
	/**
	 * __call
	 *
	 * @access public
	 * @param  void	
	 * @return void
	 * 
	 **/
	
	public function __call($name, $args = array()) 
	{
		//catch all the send_ requests
		if (substr($name, 0, 4) === 'send')
		{
			//determine the method name by stripping the send_
			$method = substr($name, 5, strlen($name));
			
			//see if the method exists	
			if (method_exists($this, $method))
			{				
				//call the method
				$this->$method($args);
				
				//setup the message
				$this->setup_message($method);
				
				//send the message
				$this->send();
			}
			else
			{
				//the method does not exist so throw exception
				throw new Exception('Method: '.$method.' does not exist.');
			}
		}		
	}
	
	
	/**
	 * setup
	 *
	 * @access public
	 * @param  void	
	 * @return void
	 * 
	 **/
	
	public function setup_message($method) 
	{
		// Create the message
		$this->message = Swift_Message::newInstance();
		
		//set the subject
		$this->message->setSubject($this->subject);
		
		//do we need to process the HTML?
		if ($this->message_type == 'text/html' OR $this->message_type == 'multipart/alternative')
		{
			//has it already been set?
			if ($this->body_html === NULL)
			{
				//find the messsage view
				$base_dir = strtolower(preg_replace('/_/', '/', $this->_class_name));
				$this->body_html = new View($base_dir.'/'.$method);
			}
			
			//add the body data to it
			if (is_array($this->body_data))
			{
				foreach ($this->body_data as $variable => $data) 
				{
					$this->body_html->bind($variable, $data);
				}
			}
			
			$this->body_html = $this->body_html->render();
			
			$this->message->setBody($this->body_html, 'text/html');
			
			if ($this->body_text !== NULL AND is_string($this->body_text))
			{
				$this->message->setPart($this->body_text, 'text/plain');
			}
		}
		else
		{
			$this->message->setBody($this->body_text, 'text/plain');
		}
		
		//is there any attachments?
		if ($this->attachments !== NULL)
		{
			//only one or more?
			if (is_string($this->attachments))
			{
				//Add the attachment
				$this->message->attach(Swift_Attachment::fromPath($this->attachments));
			}
			else if (is_array($this->attachments))
			{
				foreach ($this->attachments as $file) 
				{
					//Add the attachment
					$this->message->attach(Swift_Attachment::fromPath($file));				
				}
			}
		}
		
		//set the to field
		if (is_string($this->to))
		{
			$this->to = array($this->to);
		}
		
		$this->message->setTo($this->to);

		//set the cc field		
		if ($this->cc !== NULL)
		{
			if (is_string($this->cc))
			{
				$this->cc = array($this->cc);
			}
			$this->message->setCc($this->cc);	
		}
		
		//set the bcc field		
		if ($this->bcc !== NULL)
		{
			if (is_string($this->bcc))
			{
				$this->bcc = array($this->bcc);
			}
			$this->message->setBcc($this->bcc);
		}
		
		//who is it from?
		$this->message->setFrom($this->from);
		
	}
	
	
	/**
	 * send
	 *
	 * @access public
	 * @param  void	
	 * @return void
	 * 
	 **/
	
	public function send() 
	{	
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
		
		return $this->result;
	}
	
	
	
	/**
	 * get_class
	 *
	 * @access public
	 * @param  void	
	 * @return void
	 * 
	 **/
	
	public function get_class_name() 
	{
		return $this->_class_name;
	}
	
	
	/**
	 * get_mailer
	 *
	 * @access public
	 * @param  void	
	 * @return void
	 * 
	 **/
	
	public function get_mailer() 
	{
		return $this->_mailer;
	}
	
	
	/**
	 * set_mailer
	 *
	 * @access public
	 * @param  void	
	 * @return void
	 * 
	 **/
	
	public function set_mailer($mailer) 
	{
		if ($mailer instanceof Swift_Mailer)
		{
			$this->_mailer = $mailer;
		}
	}
	
	
	/**
	 * get_message
	 *
	 * @access public
	 * @param  void	
	 * @return void
	 * 
	 **/
	
	public function get_message() 
	{
		if ($this->message !== NULL)
		{
			return $this->message;
		}
	}
	
		
	
	
}// end of Mailer

?>