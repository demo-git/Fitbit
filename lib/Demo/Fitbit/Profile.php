<?php

namespace Demo\Fitbit;

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
