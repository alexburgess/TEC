<?php

namespace TEC\Tickets_Wallet_Plus\Contracts\Traits;

use TEC\Tickets_Wallet_Plus\Template;

trait Generic_Template {

	/**
	 * Stores the instance of the template engine that we will use for rendering the elements.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @var Template
	 */
	protected Template $template;

	/**
	 * Gets the template instance used to render html.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @return Template
	 */
	public function get_template(): Template {

		if ( empty( $this->template ) ) {
			$this->template = new Template();
			$this->template->set_template_folder( $this->template_folder ?? 'src/views/tickets-wallet-plus' );
		}

		return $this->template;
	}
}
