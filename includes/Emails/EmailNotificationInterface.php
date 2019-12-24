<?php

/**
 * Interface BaseEmailNotification should be implemented on classes which require to add new item to queue.
 *
 * @author Alexander Teshabaev <sasha.tesh@gmail.com>
 */
interface EmailNotificationInterface {

	/**
	 * Prepare email content before it will be sent.
	 *
	 * @return bool
	 */
	public function prepare ();

	/**
	 * Add new item to email queue.
	 *
	 * @return bool
	 */
	public function add_to_queue ();
}