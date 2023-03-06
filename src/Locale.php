<?php

namespace Greystar;

use \Greystar\Dto\Reports\GetReport\Request;
use \Greystar\Dto\Locale\GetCountryList\Response;

class Locale extends \Greystar\Reports
{
    private array $list = [];
    /**
     *  @description
     */
    public function getCountryList(): array
    {
        $countries = [];
        foreach ($this->getCountries() as $cou) {
            $countries[$cou->country_code] = $cou->country_name;
        }
        return $countries;
    }

    public function getCountries(): array
    {
        if (empty($this->list)) {
            $dto = new Request();
            $dto->reportcode = 'countrylisting';
            $this->list = array_map(function($v) {
                return new Response($v);
            }, $this->getReport($dto));
        }

        return $this->list;
    }
}
