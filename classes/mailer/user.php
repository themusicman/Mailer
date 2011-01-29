<?php defined('SYSPATH') or die('No direct script access.');

class Mailer_User extends Mailer {

	public function before()
	{
		$this->config		= "default";
	}

	public function welcome() 
	{
		$this->to 			= array('tom@example.com' => 'Tom');
		$this->bcc			= array('admin@theweapp.com' => 'Admin');
		$this->from 		= array('theteam@theweapp.com' => 'The Team');
		$this->subject		= 'Welcome!';
		$this->attachments	= array('/path/to/file/file.txt', '/path/to/file/file2.txt');
		$this->data 		= array('user' => array('name' => 'Tom'));
	}
			
}

?>