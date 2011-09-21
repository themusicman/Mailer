<?php defined('SYSPATH') or die('No direct script access.');

	class Kohana_Mailer_Transport_Native extends Kohana_Mailer_Transport {

		public function build ( $config ) {
			return Swift_MailTransport::newInstance();
		}

	}

