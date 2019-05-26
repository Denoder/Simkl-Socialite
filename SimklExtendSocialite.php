<?php

namespace SocialiteProviders\Simkl;

use SocialiteProviders\Manager\SocialiteWasCalled;

class SimklExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('simkl', __NAMESPACE__.'\Provider');
    }
}
