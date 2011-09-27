<?php defined('SYSPATH') or die('No direct script access.');

	class Kohana_Mailer_Transport_SMTP extends Kohana_Mailer_Transport {

		public function build ( $config ) {
			//Create the Transport
			$transport = Swift_SmtpTransport::newInstance()
							->setHost(empty($config['hostname']) ? "localhost" : (string) $config['hostname'])
							->setUsername(empty($config['username']) ? NULL : (string) $config['username'])
							->setPassword(empty($config['password']) ? NULL : (string) $config['password']);
			
			//Port?
			$port = empty($config['port']) ? 25 : (int) $config['port'];
			$transport->setPort($port);
			
			//Use encryption?
			if (! empty($config['encryption']))
			{
				$transport->setEncryption($config['encryption']);
			}

			return $transport;
		}

	}

