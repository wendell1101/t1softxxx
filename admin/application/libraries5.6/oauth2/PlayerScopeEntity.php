<?php

use \League\OAuth2\Server\Entities\ScopeEntityInterface;
use \League\OAuth2\Server\Entities\Traits\EntityTrait;

class PlayerScopeEntity implements JsonSerializable, ScopeEntityInterface{

	use EntityTrait;

	/**
	 * Create a new scope instance.
	 *
	 * @param  string  $name
	 * @return void
	 */
	public function __construct($name)
	{
		$this->setIdentifier($name);
	}

	/**
	 * Get the data that should be serialized to JSON.
	 *
	 * @return mixed
	 */
	public function jsonSerialize()
	{
		return $this->getIdentifier();
	}

	public function __toString(){
		return $this->getIdentifier();
	}

}