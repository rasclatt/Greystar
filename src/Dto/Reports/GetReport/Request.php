<?php
namespace Greystar\Dto\Reports\GetReport;

/**
 * @notes   You may use any other filters that the report has by using the same variable name
 *          as the original report filter variable name
 */
class Request extends \SmartDto\Dto
{
    public string $reportcode;
    public ?int $inv = null;
    public ?string $username = null;
    public ?string $country = null;
    public ?string $state = null;
    public ?string $startdate = null;
    public ?string $enddate = null;
    public ?string $applytoshipping = null; //Y|N
    public string $textonly = 'text';
    public string $format = 'csv';
}