<?php

use \League\OAuth2\Server\Entities\RefreshTokenEntityInterface;
use \League\OAuth2\Server\Entities\Traits\EntityTrait;
use \League\OAuth2\Server\Entities\Traits\RefreshTokenTrait;

class PlayerRefreshTokenEntity implements RefreshTokenEntityInterface{

	use EntityTrait, RefreshTokenTrait;

}
