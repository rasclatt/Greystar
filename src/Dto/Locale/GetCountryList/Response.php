<?php
namespace Greystar\Dto\Locale\GetCountryList;

class Response extends \SmartDto\Dto
{
    public  ?string $country_code = null;
    public  ?string $country_name = null;
}