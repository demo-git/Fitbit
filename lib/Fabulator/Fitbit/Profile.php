<?php
namespace Fabulator\Fitbit;

use Fabulator\Fitbit\Module;

use \Datetime;
use \Exception;

class Profile extends Module
{
    /**
     * Get information about profile https://dev.fitbit.com/docs/user/#get-profile
     * @return object profile information
     */
    public function get()
    {
        return $this->fitbit->get('profile');
    }
}
