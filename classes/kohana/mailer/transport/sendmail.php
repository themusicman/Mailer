<?php defined('SYSPATH') or die('No direct script access.');

	class Kohana_Mailer_Transport_Sendmail extends Kohana_Mailer_Transport {

		public function build ( $config ) {
			return Swift_SendmailTransport::newInstance(empty($config['options']) ? "/usr/sbin/sendmail -bs" : $config['options']);
		}

	}

